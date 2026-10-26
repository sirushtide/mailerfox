<?php

namespace App\Services\AiBuilder;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ViteSandboxCompiler
{
    // Allow multiple silent compile/fix cycles so the sandbox can self-heal
    // in the same way Bolt/Lovable keeps retrying after AI repairs.
    public const MAX_COMPILE_RETRIES = 5;

    public function compileWithAutoFix(string $sessionId, array $projectFiles, PageHtmlGenerator $pageHtmlGenerator, ?string $model = null, bool $allowFix = false): array
    {
        $currentFiles = $projectFiles;

        $lastLog = '';

        for ($attempt = 0; $attempt <= self::MAX_COMPILE_RETRIES; $attempt++) {
            $compiled = $this->compile($sessionId, $currentFiles);

            if ($compiled['success']) {
                $compiled['attempts'] = $attempt + 1;
                $compiled['fixed'] = $attempt > 0;
                return $compiled;
            }

            $lastLog = $compiled['build_log'] ?? '';

            if ($attempt >= self::MAX_COMPILE_RETRIES || ! $allowFix) {
                break;
            }

            $fixedFiles = $pageHtmlGenerator->fixBuildErrors($currentFiles, $lastLog, $model);
            if (! is_array($fixedFiles) || empty($fixedFiles)) {
                break;
            }

            $currentFiles = $fixedFiles;
        }

        return [
            'success' => false,
            'project_files' => $currentFiles,
            'dist_index_html' => null,
            'sandbox_path' => $this->sandboxPath($sessionId),
            'build_log' => $lastLog,
            'error' => $lastLog !== '' ? $lastLog : 'Vite sandbox compilation failed after multiple attempts.',
            'attempts' => $attempt + 1,
            'fixed' => false,
        ];
    }
    public function defaultProjectFiles(string $reactCode, string $title = 'Generated Page', ?string $previewHtml = null): array
    {
        $componentName = $this->detectComponentName($reactCode) ?: 'LandingPage';
        $theme = $this->extractThemeFromPreviewHtml($previewHtml);
        $hasCode = trim($reactCode) !== '';
        $pageSource = $hasCode
            ? $this->ensurePageExport(trim($reactCode), $componentName)
            : null;

        $codebase = new DefaultCodebase();
        $files = $codebase->defaultFiles($theme, $title, $pageSource);

        // Map app/page entry points to the actual component name so Vite imports match
        if ($hasCode && $pageSource !== null) {
            $files = $this->replaceEntryFilesForComponent($files, $componentName, $pageSource);
        }

        return $files;
    }

    private function replaceEntryFilesForComponent(array $files, string $componentName, string $pageSource): array
    {
        $map = [];
        foreach ($files as $file) {
            $map[$file['path']] = $file['content'];
        }

        $map['src/App.jsx'] = "import React from 'react';\nimport { HashRouter, Routes, Route } from 'react-router-dom';\nimport { AuthProvider } from './contexts/AuthContext.jsx';\nimport { ProtectedRoute } from './components/protected-route.jsx';\nimport Page from './pages/{$componentName}.jsx';\nimport LoginPage from './pages/login-page.jsx';\nimport SignupPage from './pages/signup-page.jsx';\n\nexport default function App() {\n    return (\n        <AuthProvider>\n            <HashRouter>\n                <Routes>\n                    <Route path=\"/login\" element={<LoginPage />} />\n                    <Route path=\"/signup\" element={<SignupPage />} />\n                    <Route path=\"/\" element={<ProtectedRoute><Page /></ProtectedRoute>} />\n                </Routes>\n            </HashRouter>\n        </AuthProvider>\n    );\n}\n";
        $map['src/pages/LandingPage.jsx'] = $pageSource;

        return array_map(function ($path) use ($map) {
            return ['path' => $path, 'content' => $map[$path]];
        }, array_keys($map));
    }

    private function ensurePageExport(string $code, string $componentName): string
    {
        $code = trim($code);
        // If there's already ANY export default, don't add another one
        if (preg_match('/\bexport\s+default\b/m', $code)) {
            return $code;
        }
        $code .= "\n\nexport default {$componentName};\n";
        return $code;
    }

    private function buildPlaceholderApp(): string
    {
        return <<<'JS'
import React from 'react';
import { HashRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext.jsx';
import { ProtectedRoute } from './components/protected-route.jsx';
import LandingPage from './pages/LandingPage.jsx';
import LoginPage from './pages/login-page.jsx';
import SignupPage from './pages/signup-page.jsx';

export default function App() {
    return (
        <AuthProvider>
            <HashRouter>
                <Routes>
                    <Route path="/login" element={<LoginPage />} />
                    <Route path="/signup" element={<SignupPage />} />
                    <Route path="/" element={<ProtectedRoute><LandingPage /></ProtectedRoute>} />
                </Routes>
            </HashRouter>
        </AuthProvider>
    );
}
JS;
    }

    private function buildPlaceholderPage(string $componentName): string
    {
        return <<<JS
import React from 'react';

const {$componentName} = () => (
    <main className="min-h-screen bg-brand-bg text-brand-text flex items-center justify-center p-10">
        <div className="max-w-xl text-center">
            <h1 className="text-4xl font-bold">Start building</h1>
            <p className="mt-4 text-slate-600">Generate a page to populate this Vite sandbox.</p>
        </div>
    </main>
);

export default {$componentName};
JS;
    }

    private function escapeUnescapedQuotes(string $content): string
    {
        $lines = explode("\n", $content);
        foreach ($lines as $idx => $line) {
            $lines[$idx] = $this->escapeUnescapedQuotesInLine($line);
        }
        return implode("\n", $lines);
    }

    private function escapeUnescapedQuotesInLine(string $line): string
    {
        // Safe global fix: numbers followed by a quote followed by a continuation word
        // e.g. "19" Alloy" -> "19\" Alloy"
        $line = preg_replace('/(\d+(?:\.\d+)?)"(\s*[A-Za-z0-9])/', '$1\\"$2', $line);

        // Inside array literals (lines containing [ or ]) also fix word/quote/continuation patterns.
        if (str_contains($line, '[') || str_contains($line, ']')) {
            $line = $this->escapeInnerQuoteInLine($line, '"');
            $line = $this->escapeInnerQuoteInLine($line, "'");
        }

        return $line;
    }

    /**
     * Detect bare object references rendered as JSX children (React error #31).
     * When a .map() callback variable (e.g. feature, item, plan) is rendered
     * directly as {variable} but the variable is an object, React throws.
     * We try to find a likely string property on the object (title, label,
     * name, value) and replace {variable} with {variable.property}.
     */
    private function repairBareObjectJsxChildren(string $content): string
    {
        // Collect all .map((varName, ...) patterns to know which variables
        // are likely object references inside map callbacks.
        if (! preg_match_all('/\.map\s*\(\s*\(?\s*([A-Za-z_$][A-Za-z0-9_$]*)/', $content, $mapMatches)) {
            return $content;
        }

        $mapVars = array_unique($mapMatches[1]);
        if (empty($mapVars)) {
            return $content;
        }

        // For each map variable, find the defaults object to detect if it has
        // object-valued properties. Then find bare {varName} JSX children and
        // replace them with {varName.property}.
        $propertyCandidates = ['title', 'label', 'name', 'value', 'text', 'content', 'description'];

        foreach ($mapVars as $varName) {
            // Find bare {varName} in JSX context (not {varName.something} or {varName()}).
            // Negative lookbehind for = excludes JSX prop values like prop={varName}.
            $pattern = '/(?<!=)\{' . preg_quote($varName, '/') . '\}(?![.\w(])/';
            if (! preg_match($pattern, $content)) {
                continue;
            }

            // Try to detect which property to use by looking at the defaults
            // or the map source. Find the first property access on this var
            // in the file to infer the object shape.
            $inferredProp = null;
            if (preg_match('/\{' . preg_quote($varName, '/') . '\.([A-Za-z_$][A-Za-z0-9_$]*)\}/', $content, $propMatch)) {
                $inferredProp = $propMatch[1];
            }

            // If we can't infer from existing usage, try to find the property
            // from the defaults object shape
            if ($inferredProp === null) {
                // Look for the defaults object near the top of the file
                if (preg_match('/"' . preg_quote($varName, '/') . '"\s*:\s*\[/', $content)) {
                    // Find the first object key in the array
                    if (preg_match('/"' . preg_quote($varName, '/') . '"\s*:\s*\[\s*\{\s*"([^"]+)"/', $content, $keyMatch)) {
                        $firstKey = $keyMatch[1];
                        if (in_array($firstKey, $propertyCandidates)) {
                            $inferredProp = $firstKey;
                        } else {
                            $inferredProp = $firstKey;
                        }
                    }
                }
            }

            // If we still can't infer, use 'title' as a reasonable default
            // since it's the most common cause of error #31
            if ($inferredProp === null) {
                $inferredProp = 'title';
            }

            $content = preg_replace(
                '/(?<!=)\{' . preg_quote($varName, '/') . '\}(?![.\w(])/',
                '{' . $varName . '.' . $inferredProp . '}',
                $content
            );
        }

        return $content;
    }

    private function repairMalformedJsxAttributes(string $content): string
    {
        $lines = explode("\n", $content);

        foreach ($lines as $idx => $line) {
            if (! str_contains($line, '<') || ! str_contains($line, '=')) {
                continue;
            }

            $line = preg_replace('/(=\s*["\'])\\\\+/', '$1', $line);
            $line = preg_replace('/\\\\+(["\'])(?=\s*[A-Za-z_:][A-Za-z0-9_:.\-]*=|\s*\/?>)/', '$1', $line);
            $line = preg_replace('/\\\\+(?=\s*[A-Za-z_:][A-Za-z0-9_:.\-]*=)/', '', $line);
            // Fix backslash between quoted attr value and next attribute: type="x"\ default
            $line = preg_replace('/(["\'])\\\\+(?=\s+[A-Za-z_:])/', '$1', $line);
            // Fix duplicate className attributes: className="a" className="b" → className="a b"
            $line = preg_replace_callback(
                '/(<[A-Za-z][A-Za-z0-9]*(?:\s+[^>]*)?)\s+className="([^"]*)"\s+className="([^"]*)"/',
                fn ($m) => $m[1] . ' className="' . $m[2] . ' ' . $m[3] . '"',
                $line
            );

            $lines[$idx] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * Fix inconsistent quote escaping inside SVG strings embedded in JS/JSX source.
     * AI often generates SVG strings where some inner double-quotes are escaped (\")
     * and others are not, breaking the JS string literal.
     */
    private function repairSvgStringEscaping(string $content): string
    {
        $lines = explode("\n", $content);
        foreach ($lines as $idx => $line) {
            if (! str_contains($line, '<svg') || ! str_contains($line, '"')) {
                continue;
            }

            $lines[$idx] = $this->fixSvgLineEscaping($line);
        }

        return implode("\n", $lines);
    }

    /**
     * On a line containing an SVG string value, normalize all inner double-quote escaping.
     */
    private function fixSvgLineEscaping(string $line): string
    {
        // Find the start of the SVG string value: look for ": "<svg or = "<svg
        // The opening " is the one right before <svg
        $svgPos = strpos($line, '<svg');
        if ($svgPos === false) {
            return $line;
        }

        // Walk backwards to find the opening " of the JS string
        $start = -1;
        for ($i = $svgPos - 1; $i >= 0; $i--) {
            if ($line[$i] === '"' && ($i === 0 || $line[$i - 1] !== '\\')) {
                $start = $i;
                break;
            }
        }
        if ($start < 0) {
            return $line;
        }

        // Walk forward from after </svg> to find the closing " of the JS string
        $endSvg = strpos($line, '</svg>', $svgPos);
        if ($endSvg === false) {
            return $line;
        }
        $afterSvg = $endSvg + 6; // length of '</svg>'

        $end = -1;
        $len = strlen($line);
        for ($i = $afterSvg; $i < $len; $i++) {
            if ($line[$i] === '"' && ($i === 0 || $line[$i - 1] !== '\\')) {
                $end = $i;
                break;
            }
        }
        if ($end < 0) {
            return $line;
        }

        // Extract the SVG string content (between opening " and closing ")
        $inner = substr($line, $start + 1, $end - $start - 1);

        // Normalize: unescape all \" to ", then re-escape all " to \"
        $fixed = str_replace('\\"', '"', $inner);
        $fixed = str_replace('"', '\\"', $fixed);

        // Rebuild the line
        return substr($line, 0, $start + 1) . $fixed . substr($line, $end);
    }

    private function escapeInnerQuoteInLine(string $line, string $quote): string
    {
        if (strpos($line, $quote) === false) {
            return $line;
        }

        $len = strlen($line);
        $i = 0;
        $output = '';

        while ($i < $len) {
            $c = $line[$i];

            if ($c === '\\') {
                $output .= $c . ($line[$i + 1] ?? '');
                $i += 2;
                continue;
            }

            if ($c !== $quote) {
                $output .= $c;
                $i++;
                continue;
            }

            // Find the next unescaped quote of the same kind
            $j = $i + 1;
            while ($j < $len) {
                if ($line[$j] === '\\') {
                    $j += 2;
                    continue;
                }
                if ($line[$j] === $quote) {
                    break;
                }
                $j++;
            }

            if ($j >= $len) {
                // No closing quote; copy the opener and continue
                $output .= $c;
                $i++;
                continue;
            }

            // Copy the whole "string" including the quotes
            $output .= substr($line, $i, $j - $i + 1);

            // If the string is followed by a continuation word/digit, the closing quote is
            // actually an inner quote that needs escaping.
            $afterPos = $j + 1;
            if ($afterPos < $len) {
                $after = substr($line, $afterPos);
                if (preg_match('/^\s*[A-Za-z0-9]/', $after)) {
                    $output .= '\\';
                }
            }

            $i = $j + 1;
        }

        return $output;
    }

    public function compile(string $sessionId, array $projectFiles): array
    {
        $sandboxPath = $this->sandboxPath($sessionId);
        File::ensureDirectoryExists($sandboxPath);
        File::ensureDirectoryExists($sandboxPath . DIRECTORY_SEPARATOR . 'src');
        File::ensureDirectoryExists($sandboxPath . DIRECTORY_SEPARATOR . 'dist');

        $normalizedFiles = $this->normalizeProjectFiles($projectFiles);

        $isAdmin = $this->isAdminProject($normalizedFiles);
        $internalFiles = $this->writeInternalFiles($sandboxPath, $normalizedFiles);

        // Auto-fix common AI-generated JSX string syntax issues (e.g. unescaped inch quotes)
        // Prebuilt admin codebase files are hand-written and known-good, so they are
        // exempt from the repair chain (which would corrupt valid JSX like r="3.5").
        $prebuiltAdminFiles = [];
        if ($isAdmin) {
            foreach ((new AdminDefaultCodebase())->files() as $adminFile) {
                $prebuiltAdminFiles[$adminFile['path']] = $adminFile['content'];
            }
        }
        foreach ($normalizedFiles as $path => $file) {
            if (preg_match('#\.(js|jsx|ts|tsx)$#i', $path)) {
                if ($isAdmin && isset($prebuiltAdminFiles[$path]) && $prebuiltAdminFiles[$path] === $file['content']) {
                    continue;
                }
                $isUiComponent = str_contains($path, 'src/components/ui/');
                $normalizedFiles[$path]['content'] = $isUiComponent
                    ? $this->repairSvgStringEscaping(
                        $this->repairMalformedJsxAttributes(
                            $this->escapeUnescapedQuotes($file['content'])
                        )
                    )
                    : $this->repairBareObjectJsxChildren(
                        $this->repairSvgStringEscaping(
                            $this->repairMalformedJsxAttributes(
                                $this->escapeUnescapedQuotes($file['content'])
                            )
                    )
                );
            }
        }

        // Predefined Vite project structure: always provide the UI/layout components
        // so generated React pages can import them, while allowing AI files to override.
        // SectionWrapper is canonical infrastructure; do not retain older generated
        // versions that lazy-load GradFlow through import.meta in the preview iframe.
        unset($normalizedFiles['src/components/sections/SectionWrapper.jsx']);
        $defaultFiles = $isAdmin
            ? (new AdminDefaultCodebase())->files()
            : $this->defaultProjectFiles('', 'Generated Page', null);
        $defaultFileMap = [];
        foreach ($defaultFiles as $file) {
            $defaultFileMap[$file['path']] = $file;
        }
        // Boilerplate already written by writeInternalFiles
        $boilerplatePaths = ['index.html', 'vite.config.mjs', 'tailwind.config.js', 'postcss.config.js', 'src/main.jsx', 'src/components/ui/BrandIcons.jsx', 'src/lib/utils.js'];
        foreach ($boilerplatePaths as $bp) {
            unset($defaultFileMap[$bp]);
        }
        // Placeholders that will be generated or provided by the AI
        unset($defaultFileMap['src/App.jsx']);
        unset($defaultFileMap['src/pages/LandingPage.jsx']);
        // Don't overwrite files the AI provided
        foreach (array_keys($normalizedFiles) as $path) {
            unset($defaultFileMap[$path]);
        }
        $this->writeProjectFiles($sandboxPath, array_values($defaultFileMap));

        $sanitizedFiles = $this->sanitizeLucideImports($normalizedFiles);

        // Add runtime safety: wrap .map()/.filter()/.forEach() calls in JSX with
        // array fallbacks so undefined props don't crash the preview.
        $sanitizedFiles = $this->addRuntimeSafetyToJsx($sanitizedFiles);

        // Prevent AI-generated files from overwriting critical boilerplate
        foreach ($boilerplatePaths as $bp) {
            unset($sanitizedFiles[$bp]);
        }

        $this->writeProjectFiles($sandboxPath, $sanitizedFiles);

        // Re-assert the Vite HTML entry and React root files last. AI-generated
        // project files may otherwise corrupt these critical boilerplate paths.
        $indexHtmlFile = $internalFiles['index.html'];
        $providedIndexHtml = $normalizedFiles['index.html']['content'] ?? '';
        $fontLinks = $this->extractGoogleFontLinks($providedIndexHtml);
        if ($fontLinks !== '') {
            $indexHtmlFile['content'] = str_ireplace(
                '</head>',
                '    ' . $fontLinks . "\n</head>",
                $indexHtmlFile['content']
            );
        }
        $this->writeProjectFiles($sandboxPath, [
            $indexHtmlFile,
            $internalFiles['src/main.jsx'],
        ]);

        // Build the full project file map for the response so the Code tab shows
        // internal boilerplate, default codebase, and user-generated files together.
        $mergedFiles = $internalFiles;
        foreach ($defaultFileMap as $path => $file) {
            $mergedFiles[$path] = $file;
        }
        foreach ($sanitizedFiles as $path => $file) {
            $mergedFiles[$path] = $file;
        }

        // Auto-generate missing entry point files that src/main.jsx expects
        $appPath = $sandboxPath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'App.jsx';
        if (! File::exists($appPath)) {
            $pageComponent = $this->detectPageComponent($sanitizedFiles);
            if ($pageComponent !== null) {
                $importPath = str_replace('src/', './', $pageComponent['path']);
                $importPath = preg_replace('#\.jsx$#', '', $importPath);
                $componentName = $pageComponent['name'];
                 File::put($appPath, <<<JS
import React from 'react';
import { HashRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext.jsx';
import { ProtectedRoute } from './components/protected-route.jsx';
import {$componentName} from '{$importPath}.jsx';
import LoginPage from './pages/login-page.jsx';
import SignupPage from './pages/signup-page.jsx';

export default function App() {
    return (
        <AuthProvider>
            <HashRouter>
                <Routes>
                    <Route path="/login" element={<LoginPage />} />
                    <Route path="/signup" element={<SignupPage />} />
                    <Route path="/" element={<ProtectedRoute><{$componentName} /></ProtectedRoute>} />
                </Routes>
            </HashRouter>
        </AuthProvider>
    );
}
JS
                );
            } else {
                File::put($appPath, $this->buildPlaceholderApp());
            }
        }
        $mergedFiles['src/App.jsx'] = [
            'path' => 'src/App.jsx',
            'content' => File::get($appPath),
        ];

        $stylesPath = $sandboxPath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'styles.css';
        if (! File::exists($stylesPath)) {
            $indexCssPath = $sandboxPath . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'index.css';
            if (File::exists($indexCssPath)) {
                File::copy($indexCssPath, $stylesPath);
            } else {
                File::put($stylesPath, "@tailwind base;\n@tailwind components;\n@tailwind utilities;\n");
            }
        }
        $mergedFiles['src/styles.css'] = [
            'path' => 'src/styles.css',
            'content' => File::get($stylesPath),
        ];

        if (File::exists($sandboxPath . DIRECTORY_SEPARATOR . 'dist')) {
            File::deleteDirectory($sandboxPath . DIRECTORY_SEPARATOR . 'dist');
        }

        $process = new Process([
            'node',
            base_path('node_modules/vite/bin/vite.js'),
            'build',
            '--config',
            $sandboxPath . DIRECTORY_SEPARATOR . 'vite.config.mjs',
        ], $sandboxPath, [
            'NODE_ENV' => 'production',
        ]);
        $process->setTimeout(120);
        $process->run();

        $buildOutput = trim($process->getOutput());
        $errorOutput = trim($process->getErrorOutput() ?: '');
        $combinedLog = $buildOutput !== '' ? $buildOutput . "\n" . $errorOutput : $errorOutput;

        if (! $process->isSuccessful()) {
            return [
                'success' => false,
                'project_files' => array_values($mergedFiles),
                'dist_index_html' => null,
                'sandbox_path' => $sandboxPath,
                'build_log' => $combinedLog,
                'error' => $errorOutput !== '' ? $errorOutput : 'Vite sandbox compilation failed.',
            ];
        }

        $indexPath = $sandboxPath . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . 'index.html';
        if (! File::exists($indexPath)) {
            return [
                'success' => false,
                'project_files' => array_values($mergedFiles),
                'dist_index_html' => null,
                'sandbox_path' => $sandboxPath,
                'build_log' => $combinedLog,
                'error' => 'Vite sandbox build finished without an index.html output.',
            ];
        }

        return [
            'success' => true,
            'project_files' => array_values($mergedFiles),
            'dist_index_html' => File::get($indexPath),
            'sandbox_path' => $sandboxPath,
            'build_log' => $combinedLog,
            'error' => null,
        ];
    }

    public function sandboxDistFilePath(string $sessionId, ?string $path = null): string
    {
        $requested = trim((string) ($path ?? 'index.html'));
        $requested = $requested === '' ? 'index.html' : $requested;
        $requested = str_replace('\\', '/', $requested);
        $requested = ltrim($requested, '/');

        if (str_contains($requested, '..')) {
            throw new \RuntimeException('Invalid sandbox file path.');
        }

        return $this->sandboxPath($sessionId) . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $requested);
    }

    public function detectComponentName(string $reactCode): ?string
    {
        $source = trim($reactCode);
        if ($source === '') {
            return null;
        }

        if (preg_match('/(?:function|const)\s+([A-Z][A-Za-z0-9_]*)\s*(?:\(|=)/', $source, $matches)) {
            return $matches[1] ?? null;
        }

        return null;
    }

    private function detectPageComponent(array $projectFiles): ?array
    {
        // Priority 1: src/pages/*.jsx files
        foreach ($projectFiles as $file) {
            $path = $file['path'] ?? '';
            if (preg_match('#^src/pages/.*\.(jsx|tsx)$#i', $path)) {
                $name = $this->detectComponentName($file['content'] ?? '') ?: 'LandingPage';
                return ['path' => $path, 'name' => $name];
            }
        }

        // Priority 2: src/App.jsx
        foreach ($projectFiles as $file) {
            $path = $file['path'] ?? '';
            if (preg_match('#^src/App\.(jsx|tsx)$#i', $path)) {
                $name = $this->detectComponentName($file['content'] ?? '') ?: 'App';
                return ['path' => $path, 'name' => $name];
            }
        }

        // Priority 3: Any other src/*.jsx file
        foreach ($projectFiles as $file) {
            $path = $file['path'] ?? '';
            if (preg_match('#^src/.*\.(jsx|tsx)$#i', $path)) {
                $name = $this->detectComponentName($file['content'] ?? '') ?: 'LandingPage';
                return ['path' => $path, 'name' => $name];
            }
        }

        return null;
    }

    private function normalizeProjectFiles(array $projectFiles): array
    {
        $normalized = [];

        foreach ($projectFiles as $file) {
            if (! is_array($file)) {
                continue;
            }

            $path = isset($file['path']) && is_string($file['path']) ? trim($file['path']) : '';
            $content = isset($file['content']) && is_string($file['content']) ? $file['content'] : '';

            if ($path === '') {
                continue;
            }

            $path = str_replace('\\', '/', $path);
            $path = ltrim($path, '/');

            if (str_contains($path, '..')) {
                continue;
            }

            $normalized[$path] = [
                'path' => $path,
                'content' => $content,
            ];
        }

        if (! isset($normalized['index.html'])) {
            $normalized['index.html'] = [
                'path' => 'index.html',
                'content' => $this->defaultProjectFiles('', 'Generated Page')[1]['content'],
            ];
        }

        return $normalized;
    }

    private function sanitizeLucideImports(array $projectFiles): array
    {
        $validIcons = $this->getValidLucideIcons();
        if ($validIcons === null) {
            return $projectFiles;
        }

        // Brand icons that exist in BrandIcons.jsx as predefined inline SVGs
        $brandIconExports = [
            'Twitter', 'Facebook', 'Instagram', 'Linkedin', 'Github', 'Youtube',
            'Tiktok', 'Discord', 'Whatsapp', 'Telegram', 'Pinterest', 'Reddit',
            'Twitch', 'Spotify',
        ];
        $brandIconSet = array_flip($brandIconExports);

        // Fallback map for brand icons NOT in BrandIcons.jsx (still need lucide fallback)
        $fallbackMap = [
            'LinkedIn' => 'Linkedin',
            'YouTube' => 'Youtube',
            'GitHub' => 'Github',
            'TikTok' => 'Tiktok',
            'Snapchat' => 'MessageSquare',
            'Slack' => 'MessageSquare',
            'Apple' => 'Smartphone',
            'Google' => 'Search',
            'Amazon' => 'ShoppingCart',
            'Microsoft' => 'Monitor',
            'Netflix' => 'Film',
            'Airbnb' => 'Home',
            'Uber' => 'Car',
            'Lyft' => 'Car',
            'Stripe' => 'CreditCard',
            'Paypal' => 'CreditCard',
            'Visa' => 'CreditCard',
            'Mastercard' => 'CreditCard',
            'Amex' => 'CreditCard',
            'Diners' => 'CreditCard',
            'Discover' => 'CreditCard',
            'Jcb' => 'CreditCard',
            'Wechat' => 'MessageCircle',
            'Alipay' => 'CreditCard',
            'Line' => 'MessageCircle',
            'Kakao' => 'MessageCircle',
            'Vk' => 'Users',
            'Odnoklassniki' => 'Users',
            'Mailru' => 'Mail',
            'Yandex' => 'Search',
            'Weibo' => 'MessageCircle',
            'Qq' => 'MessageCircle',
            'Renren' => 'Users',
            'Vimeo' => 'Video',
            'Soundcloud' => 'Music',
            'Dribbble' => 'Palette',
            'Behance' => 'Palette',
            'Deviantart' => 'Palette',
            'Flickr' => 'Image',
            'Tumblr' => 'Type',
            'Wordpress' => 'Type',
            'Drupal' => 'Type',
            'Joomla' => 'Type',
            'Magento' => 'ShoppingCart',
            'Shopify' => 'ShoppingCart',
            'Woo' => 'ShoppingCart',
            'Bigcommerce' => 'ShoppingCart',
            'Squarespace' => 'Layout',
            'Wix' => 'Layout',
            'Webflow' => 'Layout',
            'Framer' => 'Layout',
            'Figma' => 'PenTool',
            'Sketch' => 'PenTool',
            'Invision' => 'Eye',
            'Adobe' => 'PenTool',
            'Canva' => 'Palette',
        ];

        $sanitized = [];
        foreach ($projectFiles as $file) {
            $content = $file['content'] ?? '';
            $path = $file['path'] ?? '';

            // Determine relative import path for BrandIcons.jsx based on file depth
            if (str_contains($path, 'src/pages/')) {
                $brandImportPath = '../components/ui/BrandIcons.jsx';
            } elseif (str_contains($path, 'src/components/ui/')) {
                $brandImportPath = './BrandIcons.jsx';
            } elseif (str_contains($path, 'src/components/')) {
                $brandImportPath = '../ui/BrandIcons.jsx';
            } else {
                $brandImportPath = './ui/BrandIcons.jsx';
            }

            // Track brand icons to import from BrandIcons.jsx
            $brandIconsToImport = [];
            // Track replacements for non-BrandIcons brand names (fallback to lucide)
            $replacedMap = [];

            $content = preg_replace_callback(
                '/import\s*\{\s*([^}]+)\s*\}\s*from\s*["\']lucide-react["\'];/s',
                function ($matches) use ($validIcons, $brandIconSet, $fallbackMap, &$brandIconsToImport, &$replacedMap) {
                    $imports = $matches[1];
                    $names = array_map('trim', explode(',', $imports));
                    $fixed = [];
                    foreach ($names as $name) {
                        $name = trim($name);
                        if ($name === '') {
                            continue;
                        }
                        $baseName = $name;
                        $alias = null;
                        if (preg_match('/^(\w+)\s+as\s+(\w+)$/', $name, $aliasMatch)) {
                            $baseName = $aliasMatch[1];
                            $alias = $aliasMatch[2];
                        }
                        if (isset($validIcons[$baseName])) {
                            $fixed[] = $name;
                        } elseif (isset($brandIconSet[$baseName])) {
                            // This brand icon exists in BrandIcons.jsx — remove from lucide import
                            $brandIconsToImport[$baseName] = true;
                        } elseif (isset($fallbackMap[$baseName])) {
                            $replacement = $fallbackMap[$baseName];
                            // Check if the replacement is itself a brand icon in BrandIcons.jsx
                            if (isset($brandIconSet[$replacement])) {
                                $brandIconsToImport[$replacement] = true;
                                if ($alias !== null) {
                                    $replacedMap[$alias] = $replacement;
                                } else {
                                    $replacedMap[$baseName] = $replacement;
                                }
                            } else {
                                if ($alias !== null) {
                                    $fixed[] = $replacement . ' as ' . $alias;
                                    $replacedMap[$alias] = $alias;
                                } else {
                                    $fixed[] = $replacement;
                                    $replacedMap[$baseName] = $replacement;
                                }
                            }
                        } else {
                            $fixed[] = $name;
                        }
                    }
                    return 'import { ' . implode(', ', $fixed) . ' } from "lucide-react";';
                },
                $content
            );

            // Replace JSX usage of brand icons that were remapped to BrandIcons equivalents
            foreach ($replacedMap as $old => $new) {
                if ($old !== $new) {
                    $content = preg_replace('#<' . preg_quote($old, '#') . '(?=[\s/>])#', '<' . $new, $content);
                    $content = preg_replace('#</' . preg_quote($old, '#') . '(?=[\s>])#', '</' . $new, $content);
                    $content = preg_replace('/(?<!["\'])\b' . preg_quote($old, '/') . '\b(?!["\'])/', $new, $content);
                }
            }

            // Second pass: catch brand icon names used as bare identifiers even without import
            foreach ($brandIconSet as $brandName => $_) {
                $pattern = '/(?<!["\'.\w])\b' . preg_quote($brandName, '/') . '\b(?!["\'])/';
                if (preg_match($pattern, $content)) {
                    $brandIconsToImport[$brandName] = true;
                }
            }
            // Also check fallbackMap brand names that map to BrandIcons exports
            foreach ($fallbackMap as $brandName => $replacement) {
                if (isset($brandIconSet[$replacement])) {
                    $pattern = '/(?<!["\'.\w])\b' . preg_quote($brandName, '/') . '\b(?!["\'])/';
                    if (preg_match($pattern, $content)) {
                        $content = preg_replace($pattern, $replacement, $content);
                        $brandIconsToImport[$replacement] = true;
                    }
                }
            }

            // Merge existing BrandIcons.jsx imports into the set and remove duplicate lines
            $content = preg_replace_callback(
                '/^import\s*\{\s*([^}]+)\s*\}\s*from\s*["\']' . preg_quote($brandImportPath, '/') . '["\']\s*;\s*\n?/m',
                function ($m) use (&$brandIconsToImport) {
                    foreach (explode(',', $m[1]) as $name) {
                        $name = trim($name);
                        if ($name !== '') {
                            $brandIconsToImport[$name] = true;
                        }
                    }
                    return '';
                },
                $content
            );

            // Add BrandIcons.jsx import if needed
            if (! empty($brandIconsToImport)) {
                $importNames = array_unique(array_keys($brandIconsToImport));
                sort($importNames);
                $importLine = 'import { ' . implode(', ', $importNames) . ' } from "' . $brandImportPath . '";';
                // Insert after the last import statement, or at the top
                if (preg_match('/^import\s.*$/m', $content)) {
                    $content = preg_replace_callback(
                        '/^((?:import\s.*\n?)+)/m',
                        function ($m) use ($importLine) {
                            return $m[1] . $importLine . "\n";
                        },
                        $content,
                        1
                    );
                } else {
                    $content = $importLine . "\n" . $content;
                }
            }

            $sanitized[$file['path']] = [
                'path' => $file['path'],
                'content' => $content,
            ];
        }

        return $sanitized;
    }

    private function addRuntimeSafetyToJsx(array $projectFiles): array
    {
        foreach ($projectFiles as $path => $file) {
            if (! preg_match('#\.(jsx|tsx)$#i', $path)) {
                continue;
            }
            $content = $file['content'] ?? '';

            // Wrap {identifier.map( and {identifier.property.map( with array fallbacks
            // e.g. {items.map( -> {(items || []).map(
            //      {chatListCard.items.map( -> {(chatListCard.items || []).map(
            $content = preg_replace(
                '/\{(\s*)([\w.]+)\.(map|filter|forEach|find|some|every|reduce)\(/',
                '{$1($2 || []).$3(',
                $content
            );

            // Render SVG string icons properly. AI often sets `icon` to an SVG
            // markup string ("<svg ...>") and renders it as {item.icon}, which
            // JSX prints as raw text. Convert a self-contained wrapper tag to
            // dangerouslySetInnerHTML so the SVG is parsed by the browser.
            $content = preg_replace(
                '/<([a-zA-Z][a-zA-Z0-9]*)((?:\s+[^>]*)?)>\s*\{([\w.]+)\.icon\}\s*<\/\1>/',
                '<$1$2 dangerouslySetInnerHTML={{__html: $3.icon}} />',
                $content
            );

            // Fix AI placing `key={i}` on its own line before the opening tag,
            // e.g. .map((x, i) => ( key={i} <a href=...> ))
            $content = preg_replace(
                '/key=\{([^}]+)\}\s*\n(\s*)<([a-zA-Z][a-zA-Z0-9]*)([\s>])/',
                '$2<$3 key={$1}$4',
                $content
            );

            $projectFiles[$path]['content'] = $content;
        }

        return $projectFiles;
    }

    private function getValidLucideIcons(): ?array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $iconDir = base_path('node_modules/lucide-react/dist/esm/icons');
        if (! is_dir($iconDir)) {
            $cache = [];
            return $cache;
        }

        $icons = [];
        foreach (glob($iconDir . '/*.mjs') as $file) {
            if (str_ends_with($file, '.map')) {
                continue;
            }
            $base = basename($file, '.mjs');
            $name = str_replace('-', '', ucwords($base, '-'));
            $icons[$name] = true;
        }

        $cache = $icons;
        return $cache;
    }

    private function writeProjectFiles(string $sandboxPath, array $projectFiles): void
    {
        foreach ($projectFiles as $file) {
            $target = $sandboxPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file['path']);
            $directory = dirname($target);
            File::ensureDirectoryExists($directory);
            $content = $file['content'];
            if (($file['path'] ?? '') === 'src/styles.css') {
                $content = $this->normalizeDarkThemeBlock($content);
            }
            File::put($target, $content);
        }
    }

    /**
     * Canonical .section-theme-dark block. Dark sections must keep light-on-dark
     * values for the --ds-* tokens, otherwise text and carousel arrows render
     * dark-on-dark. --ds-muted is overridden too so hover states and outline
     * badges stay light instead of flashing the light theme's muted color.
     */
    private function darkSectionThemeCss(): string
    {
        return <<<'CSS'
.section-theme-dark {
  --ds-foreground: var(--ds-background);
  --ds-muted-foreground: rgba(255, 255, 255, 0.7);
  --ds-muted: rgba(255, 255, 255, 0.12);
  --ds-border: rgba(255, 255, 255, 0.15);
  --ds-card: rgba(255, 255, 255, 0.05);
  --ds-card-foreground: var(--ds-background);
  --ds-input: rgba(255, 255, 255, 0.1);
  background-color: #0A1929;
  color: var(--ds-background);
}
CSS;
    }

    /**
     * Restore the canonical dark-theme override block. Stored project_files may
     * carry a pre-fix .section-theme-dark whose tokens were overwritten with the
     * light theme palette (dark text on a dark background). Rewrite any existing
     * block with the canonical values on every sandbox write so previews heal
     * even when the source project_files are stale.
     */
    private function normalizeDarkThemeBlock(string $css): string
    {
        $canonical = $this->darkSectionThemeCss();
        $normalized = preg_replace_callback(
            '/\.section-theme-dark\s*\{[^{}]*\}/s',
            static fn (): string => $canonical,
            $css
        );

        return is_string($normalized) ? $normalized : $css;
    }

    private function isAdminProject(array $projectFiles): bool
    {
        foreach ($projectFiles as $file) {
            $path = is_string($file['path'] ?? null) ? $file['path'] : '';
            if ($path === 'src/components/ui/sidebar.jsx' || $path === 'src/components/app-sidebar.jsx') {
                return true;
            }
        }

        return false;
    }

    private function writeInternalFiles(string $sandboxPath, array $projectFiles = []): array
    {
        $isAdmin = $this->isAdminProject($projectFiles);
        $adminCodebase = new AdminDefaultCodebase();

        $files = [
            'vite.config.mjs' => $isAdmin
                ? ($adminCodebase->get('vite.config.mjs') ?? '')
                : <<<'JS'
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@supabase/auth-helpers-react': path.resolve(process.cwd(), 'src/lib/auth-helpers-react.js'),
        },
    },
    base: './',
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            output: {
                inlineDynamicImports: true,
            },
        },
    },
});
JS,
            'tailwind.config.js' => $isAdmin
                ? ($adminCodebase->get('tailwind.config.js') ?? '')
                : <<<'JS'
/** @type {import('tailwindcss').Config} */
export default {
    content: ['./index.html', './src/**/*.{js,jsx,ts,tsx}'],
    theme: {
        extend: {
            colors: {
                brand: {
                    primary: 'var(--brand-primary)',
                    secondary: 'var(--brand-secondary)',
                    bg: 'var(--brand-bg)',
                    text: 'var(--brand-text)',
                },
                ds: {
                    background: 'var(--ds-background)',
                    foreground: 'var(--ds-foreground)',
                    muted: 'var(--ds-muted)',
                    'muted-foreground': 'var(--ds-muted-foreground)',
                    border: 'var(--ds-border)',
                    input: 'var(--ds-input)',
                    ring: 'var(--ds-ring)',
                    card: 'var(--ds-card)',
                    'card-foreground': 'var(--ds-card-foreground)',
                    popover: 'var(--ds-popover)',
                    'popover-foreground': 'var(--ds-popover-foreground)',
                    primary: 'var(--ds-primary)',
                    'primary-foreground': 'var(--ds-primary-foreground)',
                    secondary: 'var(--ds-secondary)',
                    'secondary-foreground': 'var(--ds-secondary-foreground)',
                    accent: 'var(--ds-accent)',
                    'accent-foreground': 'var(--ds-accent-foreground)',
                    destructive: 'var(--ds-destructive)',
                    'destructive-foreground': 'var(--ds-destructive-foreground)',
                },
            },
            borderRadius: {
                ds: 'var(--ds-radius)',
            },
            fontFamily: {
                sans: ['var(--ds-font-sans)', 'system-ui', 'sans-serif'],
                heading: ['var(--ds-font-heading)', 'var(--ds-font-sans)', 'system-ui', 'sans-serif'],
            },
            boxShadow: {
                ds: 'var(--ds-shadow)',
            },
        },
    },
    plugins: [],
};
JS,
            'postcss.config.js' => <<<'JS'
export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
};
JS,
            'src/lib/utils.js' => $isAdmin
                ? ($adminCodebase->get('src/lib/utils.js') ?? '')
                : (new DefaultCodebase())->utilsJs(),
             'src/lib/supabase.js' => <<<'JS'
import { createClient } from '@supabase/supabase-js';

let client = null;
export function getSupabaseClient() {
    if (client) return client;
    const url = window.APP_SUPABASE_URL;
    const key = window.APP_SUPABASE_PUBLISHABLE_KEY;
    const appId = window.APP_ID || '';
    if (!url || !key) return null;
    client = createClient(url, key, {
        global: {
            headers: {
                'x-app-id': appId,
            },
        },
    });
    return client;
}
export function getAppId() {
    return window.APP_ID || null;
}
export function getAuthProviders() {
    return window.APP_AUTH_PROVIDERS || null;
}
export function getAuthToken() {
    return localStorage.getItem('app_auth_token');
}
export function setAuthToken(token) {
    if (token) {
        localStorage.setItem('app_auth_token', token);
    } else {
        localStorage.removeItem('app_auth_token');
    }
}
export function getUserEmail() {
    return localStorage.getItem('app_user_email');
}
export function setUserEmail(email) {
    if (email) {
        localStorage.setItem('app_user_email', email);
    } else {
        localStorage.removeItem('app_user_email');
    }
}
export function getAuthHeaders() {
    const email = getUserEmail();
    if (!email) return {};
    return { 'x-user-email': email };
}
JS,
            'index.html' => $isAdmin
                ? ($adminCodebase->get('index.html') ?? '')
                : <<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Generated Page</title>
</head>
<body>
    <div id="root"></div>
    <script type="module" src="/src/main.jsx"></script>
</body>
</html>
HTML,
            'src/main.jsx' => $isAdmin
                ? ($adminCodebase->get('src/main.jsx') ?? '')
                : <<<'JS'
import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.jsx';
import './styles.css';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }
    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }
    render() {
        if (this.state.hasError) {
            return React.createElement('div', { style: { padding: '24px', fontFamily: 'system-ui, sans-serif' } },
                React.createElement('h2', { style: { color: '#dc2626', fontSize: '16px', marginBottom: '8px' } }, 'Preview Error'),
                React.createElement('pre', { style: { background: '#fef2f2', border: '1px solid #fecaca', borderRadius: '8px', padding: '12px', fontSize: '12px', color: '#991b1b', whiteSpace: 'pre-wrap', wordBreak: 'break-word' } }, String(this.state.error?.message || this.state.error || 'Unknown error'))
            );
        }
        return this.props.children;
    }
}

createRoot(document.getElementById('root')).render(
    React.createElement(React.StrictMode, null,
        React.createElement(ErrorBoundary, null,
            React.createElement(App)
        )
    )
);
JS,
        ];

        // BrandIcons.jsx — predefined inline SVG components for social/brand icons
        $brandIconsPath = resource_path('ai-builder/BrandIcons.jsx');
        if (File::exists($brandIconsPath)) {
            $files['src/components/ui/BrandIcons.jsx'] = File::get($brandIconsPath);
        }

        $out = [];
        foreach ($files as $key => $value) {
            if (is_array($value) && isset($value['path'], $value['content'])) {
                $path = $value['path'];
                $content = $value['content'];
            } else {
                $path = $key;
                $content = $value;
            }
            $fullPath = $sandboxPath . DIRECTORY_SEPARATOR . $path;
            $dir = dirname($fullPath);
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            File::put($fullPath, $content);
            $out[$path] = ['path' => $path, 'content' => $content];
        }

        return $out;
    }

    private function sandboxPath(string $sessionId): string
    {
        return storage_path('app/ai-builder-sandboxes/' . $sessionId);
    }

    private function extractThemeFromPreviewHtml(?string $previewHtml): array
    {
        $source = is_string($previewHtml) ? $previewHtml : '';
        $extract = function (string $key, string $fallback) use ($source): string {
            if (preg_match('/' . preg_quote($key, '/') . ':\s*[\'\"]([^\'\"]+)[\'\"]/i', $source, $matches)) {
                return trim((string) ($matches[1] ?? $fallback)) ?: $fallback;
            }

            return $fallback;
        };

        return [
            'primary' => $extract('primary', '#2563eb'),
            'secondary' => $extract('secondary', '#06b6d4'),
            'background' => $extract('bg', '#f8fafc'),
            'text' => $extract('text', '#0f172a'),
        ];
    }

    private function buildStylesheet(array $theme): string
    {
        $primary = $theme['primary'] ?? '#2563eb';
        $secondary = $theme['secondary'] ?? '#06b6d4';
        $background = $theme['background'] ?? '#f8fafc';
        $text = $theme['text'] ?? '#0f172a';

        return <<<CSS
@tailwind base;
@tailwind components;
@tailwind utilities;

:root {
    /* --- CORE TOKENS (Targeted by PHP ThemeApplier Regex) --- */
    --ds-background: {$background};
    --ds-foreground: {$text};
    --ds-card: #ffffff;
    --ds-card-foreground: {$text};
    --ds-popover: #ffffff;
    --ds-popover-foreground: {$text};

    --ds-primary: {$primary};
    --ds-primary-foreground: #ffffff;
    --ds-secondary: {$secondary};
    --ds-secondary-foreground: #ffffff;

    --ds-muted: #f1f5f9;
    --ds-muted-foreground: #64748b;

    --ds-accent: #f1f5f9;
    --ds-accent-foreground: {$text};
    --ds-destructive: #ef4444;
    --ds-destructive-foreground: #ffffff;

    --ds-border: #e2e8f0;
    --ds-input: #e2e8f0;
    --ds-ring: {$primary};
    --ds-radius: 0.625rem;

    --ds-font-sans: 'Inter', system-ui, sans-serif;
    --ds-font-heading: 'Inter', system-ui, sans-serif;

    --brand-primary: {$primary};
    --brand-secondary: {$secondary};
    --brand-bg: {$background};
    --brand-text: {$text};

    /* --- EXTENDED TOKENS (Static, not overwritten by PHP) --- */
    --ds-radius-md: 1rem;
    --ds-radius-lg: 1.5rem;
    --ds-radius-pill: 100px;

    --ds-text-h1: clamp(2.5rem, 5vw, 3.5rem);
    --ds-text-h2: clamp(2rem, 4vw, 2.5rem);
    --ds-text-h3: 1.5rem;
    --ds-text-body-lg: 1.125rem;
    --ds-text-body: 1rem;
    --ds-text-sm: 0.875rem;
    --ds-text-xs: 0.75rem;

    --ds-leading-tight: 1.1;
    --ds-leading-normal: 1.6;

    --ds-section-padding: 6rem 0;
    --ds-container-width: 1200px;
    --ds-container-padding: 1.5rem;

    --ds-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    --ds-shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --ds-shadow-premium: 0 1px 3px rgb(15 23 42 / 0.05), 0 10px 30px -10px rgb(15 23 42 / 0.10);
}

@layer base {
    * { box-sizing: border-box; }
    html, body, #root { min-height: 100%; }

    body {
        margin: 0;
        font-family: var(--ds-font-sans);
        background: var(--ds-background);
        color: var(--ds-foreground);
        -webkit-font-smoothing: antialiased;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: var(--ds-font-heading);
        line-height: var(--ds-leading-tight);
        margin: 0;
    }

    p {
        margin: 0;
        line-height: var(--ds-leading-normal);
    }
}

@layer components {
    /* --- LAYOUT SHELL --- */
    .section { padding: var(--ds-section-padding); }
    .container {
        max-width: var(--ds-container-width);
        margin: 0 auto;
        padding-left: var(--ds-container-padding);
        padding-right: var(--ds-container-padding);
    }

    /* --- TYPOGRAPHY --- */
    .text-h1 { font-size: var(--ds-text-h1); font-weight: 700; letter-spacing: -0.02em; }
    .text-h2 { font-size: var(--ds-text-h2); font-weight: 700; letter-spacing: -0.02em; }
    .text-h3 { font-size: var(--ds-text-h3); font-weight: 600; }
    .text-body-lg { font-size: var(--ds-text-body-lg); color: var(--ds-muted-foreground); }
    .text-body { font-size: var(--ds-text-body); color: var(--ds-muted-foreground); }

    .eyebrow {
        display: inline-block;
        font-size: var(--ds-text-xs);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--ds-primary);
        margin-bottom: 1rem;
    }

    /* --- BUTTONS --- */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        font-size: var(--ds-text-sm);
        font-weight: 600;
        border-radius: var(--ds-radius);
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        white-space: nowrap;
    }
    .btn:hover { transform: translateY(-1px); }

    .btn-primary {
        background-color: var(--ds-primary);
        color: var(--ds-primary-foreground);
        box-shadow: var(--ds-shadow-md);
    }
    .btn-primary:hover { filter: brightness(0.9); }

    .btn-secondary {
        background-color: var(--ds-secondary);
        color: var(--ds-secondary-foreground);
    }
    .btn-secondary:hover { filter: brightness(0.9); }

    .btn-outline {
        background-color: transparent;
        color: var(--ds-foreground);
        border-color: var(--ds-border);
    }
    .btn-outline:hover { background-color: var(--ds-muted); }

    /* --- CARDS --- */
    .card {
        background-color: color-mix(in srgb, var(--ds-card) 50%, transparent);
        color: var(--ds-card-foreground);
        border: 1px solid color-mix(in srgb, var(--ds-border) 50%, transparent);
        border-radius: var(--ds-radius-lg);
        box-shadow: var(--ds-shadow-premium);
        padding: 2rem;
    }

    /* --- BADGES --- */
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.25rem 0.75rem;
        font-size: var(--ds-text-xs);
        font-weight: 500;
        border-radius: var(--ds-radius-pill);
        background-color: var(--ds-muted);
        color: var(--ds-muted-foreground);
    }

    /* --- BRAND UTILITIES (Kept for backward compatibility) --- */
    .bg-brand-primary { background-color: var(--brand-primary) !important; }
    .text-brand-primary { color: var(--brand-primary) !important; }
    .bg-brand-secondary { background-color: var(--brand-secondary) !important; }
    .text-brand-secondary { color: var(--brand-secondary) !important; }
    .bg-brand-bg { background-color: var(--brand-bg) !important; }
    .text-brand-text { color: var(--brand-text) !important; }
}
CSS;
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function extractGoogleFontLinks(string $html): string
    {
        if ($html === '') {
            return '';
        }

        preg_match_all(
            '/<link[^>]*href="https?:\/\/(?:fonts\.googleapis\.com|fonts\.gstatic\.com)[^"]*"[^>]*>/i',
            $html,
            $matches
        );

        if (empty($matches[0])) {
            return '';
        }

        return implode("\n    ", $matches[0]);
    }
}
