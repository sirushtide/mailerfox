<?php

namespace App\Services\AiBuilder;

use Illuminate\Support\Facades\File;

/**
 * Predefined Vite sandbox codebase used as the starting point for AI-generated pages.
 *
 * The codebase is intentionally plain React + Tailwind so it is fast to compile and
 * easy for the AI to understand. It mimics shadcn/ui conventions (variants, cn utility,
 * forwardRef components) without requiring Radix or any extra npm dependencies.
 *
 * In the future this can be swapped for a custom uploaded codebase by reading files
 * from a configured directory instead of returning the hardcoded defaults.
 */
class DefaultCodebase
{
    /**
     * Return the full default project files for a fresh sandbox.
     *
     * @param array<string,string> $theme primary, secondary, background, text hex colors
     * @param string $title page title
     * @param string|null $pageSource optional pre-existing page source to override the placeholder
     */
    public function defaultFiles(array $theme, string $title = 'Generated Page', ?string $pageSource = null): array
    {
        $safeTitle = trim($title) !== '' ? trim($title) : 'Generated Page';

        $files = [];
        $files[] = ['path' => 'package.json', 'content' => $this->packageJson()];
        $files[] = ['path' => 'index.html', 'content' => $this->indexHtml($safeTitle)];
        $files[] = ['path' => 'vite.config.mjs', 'content' => $this->viteConfig()];
        $files[] = ['path' => 'tailwind.config.js', 'content' => $this->tailwindConfig()];
        $files[] = ['path' => 'postcss.config.js', 'content' => $this->postcssConfig()];
        $files[] = ['path' => 'src/styles.css', 'content' => $this->stylesheet($theme)];
        $files[] = ['path' => 'src/main.jsx', 'content' => $this->mainJsx()];
        $files[] = ['path' => 'src/lib/utils.js', 'content' => $this->utilsJs()];
        $files[] = ['path' => 'src/hooks/use-mobile.js', 'content' => $this->useMobileHook()];
         $files[] = ['path' => 'src/App.jsx', 'content' => $this->appJsx($pageSource)];
         $files[] = ['path' => 'src/pages/LandingPage.jsx', 'content' => $this->placeholderPage()];
         $files[] = ['path' => 'src/contexts/AuthContext.jsx', 'content' => $this->authContextJsx()];
         $files[] = ['path' => 'src/pages/login-page.jsx', 'content' => $this->loginPageJsx()];
         $files[] = ['path' => 'src/pages/signup-page.jsx', 'content' => $this->signupPageJsx()];
         $files[] = ['path' => 'src/components/protected-route.jsx', 'content' => $this->protectedRouteJsx()];

        foreach ($this->uiComponents() as $path => $content) {
            $files[] = ['path' => $path, 'content' => $content];
        }

        foreach ($this->layoutComponents($safeTitle) as $path => $content) {
            $files[] = ['path' => $path, 'content' => $content];
        }

        foreach ($this->sectionHelpers() as $path => $content) {
            $files[] = ['path' => $path, 'content' => $content];
        }

        $files[] = ['path' => 'components.json', 'content' => $this->componentsJson()];
        $files[] = ['path' => 'eslint.config.js', 'content' => $this->eslintConfig()];
        $files[] = ['path' => '.prettierrc', 'content' => $this->prettierrc()];
        $files[] = ['path' => '.prettierignore', 'content' => "dist\nnode_modules\n*.log\n"];
        $files[] = ['path' => '.gitignore', 'content' => "dist\nnode_modules\n*.log\n.env\n.DS_Store\n"];
        $files[] = ['path' => 'README.md', 'content' => $this->readme()];

        return $files;
    }

    /**
     * Merge AI-generated section component files into a full project scaffold.
     *
     * @param array<string,string> $theme       Brand colors (primary, secondary, background, text)
     * @param string               $title       Page title
     * @param array                $sectionFiles AI-generated files, each ['path' => string, 'content' => string]
     *                                           Expected paths: src/components/sections/Hero.jsx etc.
     * @return array Full project_files array ready for ViteSandboxCompiler
     */
    public function sectionFiles(array $theme, string $title, array $sectionFiles): array
    {
        // Start with the full scaffold (UI components, layout, hooks, config etc.)
        $scaffold = $this->defaultFiles($theme, $title, null);

        // Index scaffold by path so we can override entries
        $map = [];
        foreach ($scaffold as $f) {
            $map[$f['path']] = $f['content'];
        }

        // Collect section component names in order so we can compose LandingPage.jsx
        $sectionComponentNames = [];
        foreach ($sectionFiles as $f) {
            if (!is_string($f['path'] ?? null) || !is_string($f['content'] ?? null)) {
                continue;
            }
            $path = trim($f['path']);
            if ($path === '') {
                continue;
            }
            // Extract component name from path: src/components/sections/Hero.jsx → Hero
            if (preg_match('#src/components/sections/([A-Za-z0-9_]+)\.jsx$#', $path, $m)) {
                $sectionComponentNames[] = $m[1];
            }
            $map[$path] = $f['content'];
        }

        // Build LandingPage.jsx that imports and composes all section components
        $map['src/pages/LandingPage.jsx'] = $this->assembledLandingPage($sectionComponentNames);

        // Build App.jsx that routes / → LandingPage
        $map['src/App.jsx'] = <<<'JS'
import React from 'react';
import { HashRouter, Routes, Route } from 'react-router-dom';
import LandingPage from './pages/LandingPage.jsx';

export default function App() {
    return (
        <HashRouter>
            <Routes>
                <Route path="/" element={<LandingPage />} />
            </Routes>
        </HashRouter>
    );
}
JS;

        // Convert map back to array
        return array_map(
            fn($path) => ['path' => $path, 'content' => $map[$path]],
            array_keys($map)
        );
    }

    /**
     * Assemble a LandingPage.jsx that imports and renders the given section components.
     */
    private function assembledLandingPage(array $componentNames): string
    {
        $imports = '';
        $jsx = '';
        $hasNavbarSection = false;
        $isDashboardLayout = false;
        foreach ($componentNames as $componentName) {
            $normalizedName = strtolower((string) $componentName);
            if (str_contains($normalizedName, 'nav')
                || str_contains($normalizedName, 'header')
                || str_contains($normalizedName, 'menu')) {
                $hasNavbarSection = true;
            }
            if (str_contains($normalizedName, 'sidebar') || str_contains($normalizedName, 'topbar')) {
                $hasNavbarSection = true;
                $isDashboardLayout = true;
            }
        }
        $hasFooterSection = false;
        foreach ($componentNames as $componentName) {
            if (str_contains(strtolower((string) $componentName), 'footer')) {
                $hasFooterSection = true;
                break;
            }
        }
        $navbarImport = $hasNavbarSection ? '' : "import Navbar from '../components/layout/Navbar.jsx';\n";
        $footerImport = ($hasFooterSection || $isDashboardLayout) ? '' : "import Footer from '../components/layout/Footer.jsx';\n";
        $navbarJsx = $hasNavbarSection ? '' : "            <Navbar />\n";
        $footerJsx = ($hasFooterSection || $isDashboardLayout) ? '' : "            <Footer />\n";
        foreach ($componentNames as $name) {
            $imports .= "import {$name} from '../components/sections/{$name}.jsx';\n";
            $jsx .= "      <{$name} />\n";
        }

        if ($imports === '') {
            // Nothing was generated; render a placeholder
            return <<<'JS'
import React from 'react';

export default function LandingPage() {
    return (
        <div className="min-h-screen bg-brand-bg text-brand-text flex items-center justify-center">
            <p className="text-ds-muted-foreground">Page is being generated…</p>
        </div>
    );
}
JS;
        }

        return <<<JS
import React from 'react';
{$navbarImport}{$footerImport}{$imports}
export default function LandingPage() {
    return (
        <div className="min-h-screen bg-brand-bg text-brand-text antialiased">
{$navbarJsx}{$jsx}{$footerJsx}        </div>
    );
}
JS;
    }

    /**
     * Return only the UI/layout files that should be shown in the Code tab before
     * the user has generated any custom page. These are the "starter" files.
     */
    public function starterFiles(): array
    {
        return $this->defaultFiles([
            'primary' => '#2563eb',
            'secondary' => '#06b6d4',
            'background' => '#f8fafc',
            'text' => '#0f172a',
        ], 'Generated Page', null);
    }

    private function packageJson(): string
    {
        return json_encode([
            'name' => 'ai-page',
            'private' => true,
            'version' => '0.0.1',
            'type' => 'module',
            'scripts' => [
                'dev' => 'vite',
                'build' => 'vite build',
                'preview' => 'vite preview',
                'lint' => 'eslint . --ext js,jsx --report-unused-disable-directives --max-warnings 0',
            ],
            'dependencies' => [
                'react' => '^18.3.1',
                'react-dom' => '^18.3.1',
                'lucide-react' => '^0.436.0',
                '@supabase/supabase-js' => '^2.45.0',
                'react-router-dom' => '^6.26.0',
                'gradflow' => '^0.2.0',
            ],
            'devDependencies' => [
                '@types/react' => '^18.3.3',
                '@types/react-dom' => '^18.3.0',
                '@vitejs/plugin-react' => '^4.3.1',
                'autoprefixer' => '^10.4.20',
                'eslint' => '^8.57.0',
                'eslint-plugin-react' => '^7.35.0',
                'eslint-plugin-react-hooks' => '^4.6.2',
                'postcss' => '^8.4.41',
                'tailwindcss' => '^3.4.10',
                'vite' => '^5.4.1',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function indexHtml(string $title): string
    {
        $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/vite.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{$safeTitle}</title>
</head>
<body>
    <div id="root"></div>
    <script type="module" src="/src/main.jsx"></script>
</body>
</html>
HTML;
    }

    private function viteConfig(): string
    {
        return <<<'JS'
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    base: './',
    build: {
        outDir: 'dist',
        emptyOutDir: true,
    },
});
JS;
    }

    private function tailwindConfig(): string
    {
        return <<<'JS'
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
                DEFAULT: 'var(--ds-radius)',
                sm: 'var(--ds-radius)',
                md: 'var(--ds-radius)',
                lg: 'var(--ds-radius)',
                xl: 'var(--ds-radius)',
                '2xl': 'var(--ds-radius)',
                '3xl': 'var(--ds-radius)',
                full: 'var(--ds-radius)',
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
JS;
    }

    private function postcssConfig(): string
    {
        return <<<'JS'
export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
};
JS;
    }

    private function stylesheet(array $theme): string
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
    --ds-radius-md: var(--ds-radius);
    --ds-radius-lg: var(--ds-radius);
    --ds-radius-pill: var(--ds-radius);

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
    img, video, iframe { border-radius: var(--ds-radius); }

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

    /* =========================================
   1. SOLID SECTION BACKGROUNDS
   ========================================= */
.bg-section-default { 
  background-color: var(--ds-background); 
}
.bg-section-muted { 
  background-color: var(--ds-muted); 
}
.bg-section-dark { 
  background-color: var(--ds-foreground); 
  color: var(--ds-background); 
}

/* =========================================
   2. DYNAMIC DARK MODE (Auto-inverts text)
   ========================================= */
.section-theme-dark {
  --ds-foreground: var(--ds-background);
  --ds-muted-foreground: rgba(255, 255, 255, 0.7);
  --ds-border: rgba(255, 255, 255, 0.15);
  --ds-card: rgba(255, 255, 255, 0.05);
  --ds-card-foreground: var(--ds-background);
  --ds-input: rgba(255, 255, 255, 0.1);
  background-color: #0A1929;
  color: var(--ds-background);
}

/* =========================================
   3. GRID & DOT PATTERNS
   ========================================= */
    .bg-grid {
        background-color: var(--ds-background);
        background-image: 
            linear-gradient(to right, color-mix(in srgb, var(--ds-border) 10%, transparent) 1px, transparent 1px),
            linear-gradient(to bottom, color-mix(in srgb, var(--ds-border) 10%, transparent) 1px, transparent 1px);
        background-size: 32px 32px;
    }

    /* Grid with smooth fade out (mask-image applied to the bg div, not the section) */
    .bg-grid-fade {
        background-color: var(--ds-background);
        background-image: 
            linear-gradient(to right, color-mix(in srgb, var(--ds-border) 10%, transparent) 1px, transparent 1px),
            linear-gradient(to bottom, color-mix(in srgb, var(--ds-border) 10%, transparent) 1px, transparent 1px);
        background-size: 32px 32px;
        -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 30%, transparent 100%);
        mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 30%, transparent 100%);
    }

    .bg-dots {
        background-color: var(--ds-background);
        background-image: radial-gradient(var(--ds-border) 1px, transparent 1px);
        background-size: 24px 24px;
        -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 30%, transparent 100%);
        mask-image: radial-gradient(ellipse 80% 60% at 50% 0%, black 30%, transparent 100%);
    }

/* =========================================
   4. GRADIENT MESH & BLOBS
   ========================================= */
.bg-mesh {
    opacity: 0.5;
    background-color: var(--ds-background);
    background-image: 
        radial-gradient(ellipse 100% 80% at 10% 10%, var(--bg-mesh-1, var(--ds-primary)) 0%, transparent 50%),
        radial-gradient(ellipse 90% 70% at 90% 20%, var(--bg-mesh-2, var(--ds-muted)) 0%, transparent 50%),
        radial-gradient(ellipse 180% 100% at 50% 100%, var(--bg-mesh-3, var(--ds-accent)) 0%, transparent 50%),
        radial-gradient(ellipse 160% 150% at 20% 80%, var(--bg-mesh-4, var(--ds-muted)) 0%, transparent 50%);
    background-repeat: no-repeat;
}

.bg-blobs {
  opacity: 0.5;
  background-color: var(--ds-background);
  background-image: 
    radial-gradient(ellipse 100% 160% at 15% 40%,var(--bg-blob-1, var(--ds-primary)) 0%,transparent 60%),radial-gradient(ellipse 100% 130% at 85% 80%,var(--bg-blob-2, var(--ds-muted)) 0%,transparent 60%);
  background-repeat: no-repeat;
}

/* =========================================
   5. IMAGE BACKGROUND & OVERLAY
   ========================================= */
.bg-image {
  background-image: var(--bg-image-url);
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

/* Pseudo-element overlay for darkening images so white text is readable */
.bg-image-overlay::before {
  content: '';
  position: absolute;
  inset: 0;
  background-color: var(--bg-image-overlay, rgba(0,0,0,0.4));
  z-index: 0; 
}

    /* --- MARQUEE --- */
    .marquee-container {
        position: relative;
        width: 100%;
        overflow: hidden;
        -webkit-mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
        mask-image: linear-gradient(to right, transparent, black 10%, black 90%, transparent);
    }
    .marquee-track {
        display: flex;
        width: max-content;
        animation-name: marquee-scroll;
        animation-timing-function: linear;
        animation-iteration-count: infinite;
    }
    @keyframes marquee-scroll {
        from { transform: translateX(0); }
        to { transform: translateX(-50%); }
    }
}
CSS;
    }

    private function mainJsx(): string
    {
        return <<<'JS'
import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.jsx';
import './styles.css';

createRoot(document.getElementById('root')).render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
);
JS;
    }

    private function appJsx(?string $pageSource): string
    {
        if ($pageSource && trim($pageSource) !== '') {
            return $pageSource;
        }

        return $this->authAppJsx();
    }

    private function placeholderPage(): string
    {
        return <<<'JS'
import React from 'react';

const LandingPage = () => (
    <main className="min-h-screen bg-ds-background text-ds-foreground flex items-center justify-center p-10">
        <div className="max-w-xl text-center">
            <h1 className="text-4xl font-bold">Start building</h1>
            <p className="mt-4 text-ds-muted-foreground">Generate a page to replace this placeholder.</p>
        </div>
    </main>
);

export default LandingPage;
JS;
    }

    private function authContextJsx(): string
    {
        return <<<'JS'
import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { getAppId, getAuthHeaders, setUserEmail, getUserEmail } from '../lib/supabase.js';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const appId = getAppId();
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);
    const [authError, setAuthError] = useState(null);

    useEffect(() => {
        const email = getUserEmail();
        if (email) {
            setUser({ email, id: email });
        }
        setLoading(false);
    }, []);

    const login = async ({ email, password }) => {
        setAuthError(null);
        setLoading(true);

        try {
            const response = await fetch('/supabase/login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password, app_id: appId }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Login failed. Please try again.');
            }

            const userEmail = data.data?.email || email;
            setUserEmail(userEmail);
            setUser({ email: userEmail, id: userEmail });
            setLoading(false);
            return { email: userEmail };
        } catch (err) {
            setAuthError(err?.message ?? 'Login failed.');
            setLoading(false);
            throw err;
        }
    };

    const signup = async ({ email, password }) => {
        setAuthError(null);
        setLoading(true);

        try {
            const response = await fetch('/supabase/signup', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password, app_id: appId }),
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.error || 'Sign up failed. Please try again.');
            }

            const userEmail = data.data?.email || email;
            setUserEmail(userEmail);
            setUser({ email: userEmail, id: userEmail });
            setLoading(false);
            return { email: userEmail };
        } catch (err) {
            setAuthError(err?.message ?? 'Sign up failed.');
            setLoading(false);
            throw err;
        }
    };

    const logout = async () => {
        setUserEmail(null);
        setUser(null);
        setAuthError(null);
    };

    const value = useMemo(
        () => ({
            user,
            session: null,
            loading,
            authError,
            mode: 'custom',
            login,
            signup,
            logout,
        }),
        [user, loading, authError]
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const context = useContext(AuthContext);
    if (!context) {
        throw new Error('useAuth must be used within an AuthProvider');
    }
    return context;
}
JS;
    }

    private function loginPageJsx(): string
    {
        return <<<'JS'
import React, { useState } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext.jsx';
import { cn } from '../lib/utils.js';
import { Button } from '../components/ui/button.jsx';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '../components/ui/card.jsx';
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from '../components/ui/field.jsx';
import { Input } from '../components/ui/input.jsx';

export function LoginForm({ className, ...props }) {
    const { login, user } = useAuth();
    const navigate = useNavigate();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            await login({ email, password });
            navigate('/', { replace: true });
        } catch (err) {
            setError(err?.message ?? 'Sign in failed. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className={cn('flex flex-col gap-6', className)} {...props}>
            <Card>
                <CardHeader className="text-center">
                    <CardTitle className="text-xl">Welcome back</CardTitle>
                    <CardDescription>
                        Sign in to your account
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit}>
                        <FieldGroup>
                            <Field>
                                <FieldLabel htmlFor="email">Email</FieldLabel>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="m@example.com"
                                    required
                                    autoComplete="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                />
                            </Field>
                            <Field>
                                <div className="flex items-center">
                                    <FieldLabel htmlFor="password">Password</FieldLabel>
                                    <a
                                        href="#"
                                        className="ml-auto text-sm underline-offset-4 hover:underline"
                                    >
                                        Forgot your password?
                                    </a>
                                </div>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    autoComplete="current-password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                />
                            </Field>
                            {error && (
                                <FieldDescription className="text-destructive">{error}</FieldDescription>
                            )}
                            <Field>
                                <Button type="submit" disabled={loading}>
                                    {loading ? 'Signing in…' : 'Login'}
                                </Button>
                            </Field>
                            <FieldDescription className="text-center">
                                Don&apos;t have an account?{' '}
                                <Link to="/signup" className="underline underline-offset-4 hover:underline">
                                    Sign up
                                </Link>
                            </FieldDescription>
                        </FieldGroup>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

export default function LoginPage() {
    const { user, loading } = useAuth();

    if (loading) {
        return (
            <div className="flex min-h-svh items-center justify-center">
                <div className="text-sm text-muted-foreground">Loading...</div>
            </div>
        );
    }

    if (user) {
        return <Navigate to="/" replace />;
    }

    return (
        <div className="flex min-h-svh items-center justify-center bg-background p-4">
            <div className="w-full max-w-sm">
                <LoginForm />
            </div>
        </div>
    );
}
JS;
    }

    private function signupPageJsx(): string
    {
        return <<<'JS'
import React, { useState } from 'react';
import { Link, Navigate, useNavigate } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext.jsx';
import { cn } from '../lib/utils.js';
import { Button } from '../components/ui/button.jsx';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '../components/ui/card.jsx';
import {
    Field,
    FieldDescription,
    FieldGroup,
    FieldLabel,
} from '../components/ui/field.jsx';
import { Input } from '../components/ui/input.jsx';

export function SignupForm({ className, ...props }) {
    const { signup, user } = useAuth();
    const navigate = useNavigate();
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        if (password !== confirmPassword) {
            setError('Passwords do not match.');
            return;
        }
        setLoading(true);
        try {
            await signup({ email, password });
            navigate('/', { replace: true });
        } catch (err) {
            setError(err?.message ?? 'Sign up failed. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className={cn('flex flex-col gap-6', className)} {...props}>
            <Card>
                <CardHeader className="text-center">
                    <CardTitle className="text-xl">Create an account</CardTitle>
                    <CardDescription>
                        Sign up to get started
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit}>
                        <FieldGroup>
                            <Field>
                                <FieldLabel htmlFor="email">Email</FieldLabel>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="m@example.com"
                                    required
                                    autoComplete="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                />
                            </Field>
                            <Field>
                                <FieldLabel htmlFor="password">Password</FieldLabel>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    autoComplete="new-password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                />
                            </Field>
                            <Field>
                                <FieldLabel htmlFor="confirm-password">Confirm Password</FieldLabel>
                                <Input
                                    id="confirm-password"
                                    type="password"
                                    required
                                    autoComplete="new-password"
                                    value={confirmPassword}
                                    onChange={(e) => setConfirmPassword(e.target.value)}
                                />
                            </Field>
                            {error && (
                                <FieldDescription className="text-destructive">{error}</FieldDescription>
                            )}
                            <Field>
                                <Button type="submit" disabled={loading}>
                                    {loading ? 'Creating account…' : 'Sign up'}
                                </Button>
                            </Field>
                            <FieldDescription className="text-center">
                                Already have an account?{' '}
                                <Link to="/login" className="underline underline-offset-4 hover:underline">
                                    Login
                                </Link>
                            </FieldDescription>
                        </FieldGroup>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

export default function SignupPage() {
    const { user, loading } = useAuth();

    if (loading) {
        return (
            <div className="flex min-h-svh items-center justify-center">
                <div className="text-sm text-muted-foreground">Loading...</div>
            </div>
        );
    }

    if (user) {
        return <Navigate to="/" replace />;
    }

    return (
        <div className="flex min-h-svh items-center justify-center bg-background p-4">
            <div className="w-full max-w-sm">
                <SignupForm />
            </div>
        </div>
    );
}
JS;
    }

    private function protectedRouteJsx(): string
    {
        return <<<'JS'
import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../contexts/AuthContext.jsx';

export function ProtectedRoute({ children }) {
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return (
            <div className="flex min-h-svh items-center justify-center">
                <div className="text-sm text-muted-foreground">Loading...</div>
            </div>
        );
    }

    if (!user) {
        return <Navigate to="/login" replace state={{ from: location }} />;
    }

    return children;
}
JS;
    }

    private function authAppJsx(): string
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
                    <Route
                        path="/"
                        element={
                            <ProtectedRoute>
                                <LandingPage />
                            </ProtectedRoute>
                        }
                    />
                </Routes>
            </HashRouter>
        </AuthProvider>
    );
}
JS;
    }

    public function utilsJs(): string
    {
        return <<<'JS'
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    const merged = inputs
        .filter(Boolean)
        .map((input) => {
            if (typeof input === 'string') return input;
            if (Array.isArray(input)) return input.filter(Boolean).join(' ');
            return Object.entries(input || {})
                .filter(([, value]) => value)
                .map(([key]) => key)
                .join(' ');
        })
        .join(' ');

    return twMerge(merged);
}

export function formatDate(date) {
    return new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(date));
}
JS;
    }

    private function useMobileHook(): string
    {
        return <<<'JS'
import { useState, useEffect } from 'react';

const MOBILE_BREAKPOINT = 768;

export function useIsMobile() {
    const [isMobile, setIsMobile] = useState(false);

    useEffect(() => {
        const check = () => setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
        check();
        window.addEventListener('resize', check);
        return () => window.removeEventListener('resize', check);
    }, []);

    return isMobile;
}
JS;
    }

    private function componentsJson(): string
    {
        return json_encode([
            '\$schema' => 'https://ui.shadcn.com/schema.json',
            'style' => 'default',
            'rsc' => false,
            'tsx' => false,
            'tailwind' => [
                'config' => 'tailwind.config.js',
                'css' => 'src/styles.css',
                'baseColor' => 'slate',
                'cssVariables' => true,
            ],
            'aliases' => [
                'components' => 'src/components',
                'utils' => 'src/lib/utils.js',
                'ui' => 'src/components/ui',
                'lib' => 'src/lib',
                'hooks' => 'src/hooks',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function eslintConfig(): string
    {
        return <<<'JS'
import js from '@eslint/js';
import globals from 'globals';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';

export default [
    js.configs.recommended,
    {
        files: ['**/*.{js,jsx}'],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.browser,
                ...globals.es2021,
            },
            parserOptions: {
                ecmaFeatures: { jsx: true },
            },
        },
        plugins: {
            react,
            'react-hooks': reactHooks,
        },
        rules: {
            ...react.configs.recommended.rules,
            ...reactHooks.configs.recommended.rules,
            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
        },
        settings: {
            react: { version: 'detect' },
        },
    },
];
JS;
    }

    private function prettierrc(): string
    {
        return json_encode([
            'semi' => true,
            'singleQuote' => true,
            'tabWidth' => 4,
            'trailingComma' => 'es5',
            'printWidth' => 100,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function readme(): string
    {
        return "# AI Generated Page\n\nThis project was generated by the AI Page Builder.\n\n## Structure\n\n- `src/pages/` — Page components\n- `src/components/` — Reusable sections & UI\n- `src/hooks/` — Custom React hooks\n- `src/lib/` — Utility functions\n";
    }

    private function uiComponents(): array
    {
        return [
            'src/components/ui/button.jsx' => $this->buttonComponent(),
            'src/components/ui/card.jsx' => $this->cardComponent(),
            'src/components/ui/badge.jsx' => $this->badgeComponent(),
            'src/components/ui/input.jsx' => $this->inputComponent(),
            'src/components/ui/label.jsx' => $this->labelComponent(),
            'src/components/ui/textarea.jsx' => $this->textareaComponent(),
            'src/components/ui/select.jsx' => $this->selectComponent(),
            'src/components/ui/checkbox.jsx' => $this->checkboxComponent(),
            'src/components/ui/accordion.jsx' => $this->accordionComponent(),
            'src/components/ui/tabs.jsx' => $this->tabsComponent(),
            'src/components/ui/dialog.jsx' => $this->dialogComponent(),
            'src/components/ui/dropdown.jsx' => $this->dropdownComponent(),
            'src/components/ui/separator.jsx' => $this->separatorComponent(),
            'src/components/ui/avatar.jsx' => $this->avatarComponent(),
            'src/components/ui/tooltip.jsx' => $this->tooltipComponent(),
            'src/components/ui/progress.jsx' => $this->progressComponent(),
            'src/components/ui/popover.jsx' => $this->popoverComponent(),
            'src/components/ui/toggle.jsx' => $this->toggleComponent(),
            'src/components/ui/skeleton.jsx' => $this->skeletonComponent(),
            'src/components/ui/scroll-area.jsx' => $this->scrollAreaComponent(),
            'src/components/ui/sheet.jsx' => $this->sheetComponent(),
            'src/components/ui/BrandIcons.jsx' => $this->brandIconsComponent(),
            'src/components/ui/marquee.jsx' => $this->marqueeComponent(),
            'src/components/ui/carousel.jsx' => $this->carouselComponent(),
        ];
    }

    private function layoutComponents(string $title = 'Brand'): array
    {
        return [
            'src/components/layout/Navbar.jsx' => $this->navbarComponent($title),
            'src/components/layout/Footer.jsx' => $this->footerComponent($title),
        ];
    }

    private function sectionHelpers(): array
    {
        return [
            'src/components/sections/SectionWrapper.jsx' => $this->sectionWrapperComponent(),
            'src/components/sections/GradFlowProxy.jsx' => $this->gradFlowProxyComponent(),
        ];
    }

    private function gradFlowProxyComponent(): string
    {
        return <<<'JS'
import { GradFlow } from 'gradflow';
export default GradFlow;
JS;
    }

    private function buttonComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const baseClasses = 'inline-flex items-center justify-center gap-2 text-sm font-medium transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[var(--ds-ring)] focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:-translate-y-0.5 active:translate-y-0 active:scale-[0.98]';

const variantClasses = {
    default: 'bg-[var(--ds-primary)] text-[var(--ds-primary-foreground)] rounded-[var(--ds-radius)] shadow-[var(--ds-shadow-md)] hover:brightness-90',
    secondary: 'bg-[var(--ds-secondary)] text-[var(--ds-secondary-foreground)] rounded-[var(--ds-radius)] hover:brightness-90',
    outline: 'border border-[var(--ds-border)] bg-transparent text-[var(--ds-foreground)] rounded-[var(--ds-radius)] hover:bg-[var(--ds-muted)]',
    ghost: 'bg-transparent text-[var(--ds-foreground)] rounded-[var(--ds-radius)] hover:bg-[var(--ds-muted)]',
    link: 'bg-transparent text-[var(--ds-primary)] underline-offset-4 hover:underline rounded-none',
    destructive: 'bg-[var(--ds-destructive)] text-[var(--ds-destructive-foreground)] rounded-[var(--ds-radius)] shadow-[var(--ds-shadow-md)] hover:brightness-90',
};

const sizeClasses = {
    default: 'h-10 px-4 py-2',
    sm: 'h-8 px-3 text-xs',
    lg: 'h-12 px-8 text-base',
    icon: 'h-10 w-10',
};

export const buttonVariants = ({ variant = 'default', size = 'default', className = '' } = {}) => {
    return cn(baseClasses, variantClasses[variant] || variantClasses.default, sizeClasses[size] || sizeClasses.default, className);
};

const Button = React.forwardRef(({ className, variant = 'default', size = 'default', ...props }, ref) => {
    return (
        <button
            className={buttonVariants({ variant, size, className })}
            ref={ref}
            {...props}
        />
    );
});
Button.displayName = 'Button';

export { Button };
JS;
    }

    private function cardComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const Card = React.forwardRef(({ className, ...props }, ref) => (
    <div
        ref={ref}
        className={cn('rounded-xl border border-ds-border bg-ds-card text-ds-card-foreground shadow-ds', className)}
        {...props}
    />
));
Card.displayName = 'Card';

const CardHeader = React.forwardRef(({ className, ...props }, ref) => (
    <div ref={ref} className={cn('flex flex-col space-y-1.5 p-6 pb-0', className)} {...props} />
));
CardHeader.displayName = 'CardHeader';

const CardTitle = React.forwardRef(({ className, ...props }, ref) => (
    <h3 ref={ref} className={cn('text-lg font-semibold leading-none tracking-tight', className)} {...props} />
));
CardTitle.displayName = 'CardTitle';

const CardDescription = React.forwardRef(({ className, ...props }, ref) => (
    <p ref={ref} className={cn('text-sm text-ds-muted-foreground', className)} {...props} />
));
CardDescription.displayName = 'CardDescription';

const CardContent = React.forwardRef(({ className, ...props }, ref) => (
    <div ref={ref} className={cn('p-6', className)} {...props} />
));
CardContent.displayName = 'CardContent';

const CardFooter = React.forwardRef(({ className, ...props }, ref) => (
    <div ref={ref} className={cn('flex items-center p-6 pt-0', className)} {...props} />
));
CardFooter.displayName = 'CardFooter';

export { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter };
JS;
    }

    private function badgeComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const baseClasses = 'inline-flex items-center text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--ds-ring)] focus:ring-offset-2';

const variantClasses = {
    // 1. Solid: Brand color background
    solid: 'rounded-[var(--ds-radius)] border border-transparent bg-[var(--ds-primary)] px-2.5 py-0.5 text-[var(--ds-primary-foreground)] font-normal hover:opacity-90',
    
    // 2. Outline: Muted background with a border
    outline: 'rounded-[var(--ds-radius)] border font-normal border-[color-mix(in_srgb,var(--ds-border)_40%,transparent)] bg-[var(--ds-muted)] px-2.5 py-0.5 text-[var(--ds-foreground)]',
    
    // 3. Subtle: Very light brand color background (using opacity)
    subtle: 'rounded-[var(--ds-radius)] font-semibold border border-transparent bg-[color-mix(in_srgb,var(--ds-primary)_10%,transparent)] px-2.5 py-0.5 text-[var(--ds-primary)]',
    
    // 4. Plain: No background, no padding, just text
    plain: 'border-transparent bg-transparent font-semibold px-0 py-0 text-[var(--ds-primary)]',
};

export const badgeVariants = ({ variant = 'subtle', className = '' } = {}) => {
    return cn(baseClasses, variantClasses[variant] || variantClasses.subtle, className);
};

function Badge({ className, variant = 'subtle', ...props }) {
    return (
        <div className={badgeVariants({ variant, className })} {...props} />
    );
}

export { Badge };
JS;
    }

    private function inputComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const Input = React.forwardRef(({ className, type, ...props }, ref) => {
    return (
        <input
            type={type}
            className={cn(
                'flex h-10 w-full rounded-lg border border-ds-border bg-ds-background px-3 py-2 text-sm text-ds-foreground ring-offset-ds-background placeholder:text-ds-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                className
            )}
            ref={ref}
            {...props}
        />
    );
});
Input.displayName = 'Input';

export { Input };
JS;
    }

    private function labelComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const Label = React.forwardRef(({ className, ...props }, ref) => (
    <label
        ref={ref}
        className={cn('text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70', className)}
        {...props}
    />
));
Label.displayName = 'Label';

export { Label };
JS;
    }

    private function textareaComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const Textarea = React.forwardRef(({ className, ...props }, ref) => {
    return (
        <textarea
            className={cn(
                'flex min-h-[80px] w-full rounded-lg border border-ds-border bg-ds-background px-3 py-2 text-sm text-ds-foreground ring-offset-ds-background placeholder:text-ds-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                className
            )}
            ref={ref}
            {...props}
        />
    );
});
Textarea.displayName = 'Textarea';

export { Textarea };
JS;
    }

    private function selectComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const Select = React.forwardRef(({ className, children, ...props }, ref) => (
    <select
        className={cn(
            'flex h-10 w-full rounded-lg border border-ds-border bg-ds-background px-3 py-2 text-sm text-ds-foreground ring-offset-ds-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
            className
        )}
        ref={ref}
        {...props}
    >
        {children}
    </select>
));
Select.displayName = 'Select';

const SelectItem = ({ children, value }) => <option value={value}>{children}</option>;

export { Select, SelectItem };
JS;
    }

    private function checkboxComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const Checkbox = React.forwardRef(({ className, ...props }, ref) => (
    <input
        type="checkbox"
        className={cn(
            'peer h-4 w-4 shrink-0 rounded border border-ds-border bg-ds-background ring-offset-ds-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 checked:bg-ds-primary checked:text-ds-primary-foreground',
            className
        )}
        ref={ref}
        {...props}
    />
));
Checkbox.displayName = 'Checkbox';

export { Checkbox };
JS;
    }

    private function accordionComponent(): string
    {
        return <<<'JS'
import React, { useState } from 'react';
import { ChevronDown } from 'lucide-react';
import { cn } from '../../lib/utils.js';

const AccordionContext = React.createContext(null);

function Accordion({ children, defaultValue, value, onValueChange, type = 'single', collapsible = false, className }) {
    const [internal, setInternal] = useState(defaultValue ?? (type === 'single' ? '' : []));
    const controlled = value !== undefined;
    const active = controlled ? value : internal;

    const setActive = (next) => {
        if (!controlled) setInternal(next);
        onValueChange?.(next);
    };

    return (
        <AccordionContext.Provider value={{ active, setActive, type, collapsible }}>
            <div className={cn('w-full', className)}>{children}</div>
        </AccordionContext.Provider>
    );
}

function AccordionItem({ children, value, className }) {
    return (
        <div className={cn('border-b border-ds-border', className)} value={value}>
            {children}
        </div>
    );
}

function AccordionTrigger({ children, className, value }) {
    const ctx = React.useContext(AccordionContext);
    const isOpen = ctx?.type === 'single' ? ctx.active === value : ctx?.active?.includes?.(value);

    return (
        <button
            type="button"
            onClick={() => {
                if (!ctx) return;
                if (ctx.type === 'single') {
                    const next = ctx.collapsible && isOpen ? '' : value;
                    ctx.setActive(next);
                } else {
                    const current = Array.isArray(ctx.active) ? ctx.active : [];
                    const next = current.includes(value) ? current.filter((v) => v !== value) : [...current, value];
                    ctx.setActive(next);
                }
            }}
            className={cn('flex w-full items-center justify-between py-4 text-sm font-medium transition-all hover:underline', className)}
        >
            {children}
            <ChevronDown className={cn('h-4 w-4 shrink-0 text-ds-muted-foreground transition-transform', isOpen && 'rotate-180')} />
        </button>
    );
}

function AccordionContent({ children, value, className }) {
    const ctx = React.useContext(AccordionContext);
    const isOpen = ctx?.type === 'single' ? ctx.active === value : ctx?.active?.includes?.(value);
    if (!isOpen) return null;
    return <div className={cn('pb-4 text-sm text-ds-muted-foreground', className)}>{children}</div>;
}

export { Accordion, AccordionItem, AccordionTrigger, AccordionContent };
JS;
    }

    private function tabsComponent(): string
    {
        return <<<'JS'
import React, { useState } from 'react';
import { cn } from '../../lib/utils.js';

const TabsContext = React.createContext(null);

function Tabs({ defaultValue, value, onValueChange, children, className }) {
    const [internal, setInternal] = useState(defaultValue ?? '');
    const controlled = value !== undefined;
    const active = controlled ? value : internal;

    return (
        <TabsContext.Provider value={{ active, setActive: (v) => { setInternal(v); onValueChange?.(v); } }}>
            <div className={cn('w-full', className)}>{children}</div>
        </TabsContext.Provider>
    );
}

function TabsList({ children, className }) {
    return (
        <div className={cn('inline-flex h-10 items-center justify-center rounded-lg bg-ds-muted p-1 text-ds-muted-foreground', className)}>
            {children}
        </div>
    );
}

function TabsTrigger({ children, value, className }) {
    const ctx = React.useContext(TabsContext);
    const isActive = ctx?.active === value;
    return (
        <button
            type="button"
            onClick={() => ctx?.setActive?.(value)}
            className={cn(
                'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1.5 text-sm font-medium ring-offset-ds-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-ring focus-visible:ring-offset-2',
                isActive ? 'bg-ds-background text-ds-foreground shadow-ds' : 'hover:bg-ds-muted-foreground/10',
                className
            )}
        >
            {children}
        </button>
    );
}

function TabsContent({ children, value, className }) {
    const ctx = React.useContext(TabsContext);
    if (ctx?.active !== value) return null;
    return <div className={cn('mt-2', className)}>{children}</div>;
}

export { Tabs, TabsList, TabsTrigger, TabsContent };
JS;
    }

    private function dialogComponent(): string
    {
        return <<<'JS'
import React from 'react';
import { X } from 'lucide-react';
import { cn } from '../../lib/utils.js';

function Dialog({ children, open, onOpenChange }) {
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div className="fixed inset-0 bg-black/50" onClick={() => onOpenChange?.(false)} />
            <div className="relative z-10 w-full max-w-lg rounded-xl border border-ds-border bg-ds-background p-6 shadow-lg">
                {children}
                <button
                    type="button"
                    onClick={() => onOpenChange?.(false)}
                    className="absolute right-4 top-4 rounded-sm text-ds-muted-foreground hover:text-ds-foreground"
                >
                    <X className="h-4 w-4" />
                </button>
            </div>
        </div>
    );
}

function DialogContent({ children, className }) {
    return <div className={cn('', className)}>{children}</div>;
}

function DialogHeader({ children, className }) {
    return <div className={cn('flex flex-col space-y-1.5 text-center sm:text-left', className)}>{children}</div>;
}

function DialogTitle({ children, className }) {
    return <h2 className={cn('text-lg font-semibold leading-none tracking-tight', className)}>{children}</h2>;
}

function DialogDescription({ children, className }) {
    return <p className={cn('text-sm text-ds-muted-foreground', className)}>{children}</p>;
}

export { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription };
JS;
    }

    private function dropdownComponent(): string
    {
        return <<<'JS'
import React, { useState, useRef, useEffect } from 'react';
import { cn } from '../../lib/utils.js';

function DropdownMenu({ children }) {
    return <div className="relative inline-block">{children}</div>;
}

function DropdownMenuTrigger({ children, asChild }) {
    return <div className="inline-flex">{children}</div>;
}

function DropdownMenuContent({ children, align = 'end', className }) {
    return (
        <div
            className={cn(
                'absolute z-50 min-w-[8rem] rounded-md border border-ds-border bg-ds-background p-1 shadow-md',
                align === 'end' ? 'right-0' : 'left-0',
                'top-full mt-1',
                className
            )}
        >
            {children}
        </div>
    );
}

function DropdownMenuItem({ children, className, onClick }) {
    return (
        <button
            type="button"
            onClick={onClick}
            className={cn('relative flex w-full cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-ds-accent hover:text-ds-accent-foreground', className)}
        >
            {children}
        </button>
    );
}

export { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem };
JS;
    }

    private function separatorComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const Separator = React.forwardRef(({ className, orientation = 'horizontal', ...props }, ref) => (
    <div
        ref={ref}
        className={cn(
            'shrink-0 bg-ds-border',
            orientation === 'horizontal' ? 'h-[1px] w-full' : 'h-full w-[1px]',
            className
        )}
        {...props}
    />
));
Separator.displayName = 'Separator';

export { Separator };
JS;
    }

    private function avatarComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

function Avatar({ className, children }) {
    return (
        <div className={cn('relative flex h-10 w-10 shrink-0 overflow-hidden rounded-full', className)}>
            {children}
        </div>
    );
}

const AvatarImage = React.forwardRef(({ className, src, alt = '', ...props }, ref) => (
    <img ref={ref} src={src} alt={alt} className={cn('aspect-square h-full w-full', className)} {...props} />
));
AvatarImage.displayName = 'AvatarImage';

function AvatarFallback({ children, className, delayMs = 600 }) {
    return (
        <div className={cn('flex h-full w-full items-center justify-center rounded-full bg-ds-muted text-xs font-medium', className)}>
            {children}
        </div>
    );
}

export { Avatar, AvatarImage, AvatarFallback };
JS;
    }

    private function tooltipComponent(): string
    {
        return <<<'JS'
import React from 'react';
import { cn } from '../../lib/utils.js';

function TooltipProvider({ children }) {
    return <>{children}</>;
}

function Tooltip({ children }) {
    return <>{children}</>;
}

function TooltipTrigger({ children, ...props }) {
    return <span {...props}>{children}</span>;
}

function TooltipContent({ children, className, side = 'top' }) {
    const sideClasses = {
        top: 'bottom-full left-1/2 -translate-x-1/2 mb-2',
        bottom: 'top-full left-1/2 -translate-x-1/2 mt-2',
        left: 'right-full top-1/2 -translate-y-1/2 mr-2',
        right: 'left-full top-1/2 -translate-y-1/2 ml-2',
    };
    return (
        <div className={cn('absolute z-50 rounded-md bg-ds-foreground px-2 py-1 text-xs text-ds-background', sideClasses[side], className)}>
            {children}
        </div>
    );
}

export { TooltipProvider, Tooltip, TooltipTrigger, TooltipContent };
JS;
    }

    private function progressComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const Progress = React.forwardRef(({ className, value = 0, max = 100, ...props }, ref) => (
    <div
        ref={ref}
        className={cn('relative h-2 w-full overflow-hidden rounded-full bg-ds-muted', className)}
        {...props}
    >
        <div
            className="h-full w-full flex-1 bg-ds-primary transition-all"
            style={{ transform: `translateX(-${100 - Math.min(100, Math.max(0, (value / max) * 100))}%)` }}
        />
    </div>
));
Progress.displayName = 'Progress';

export { Progress };
JS;
    }

    private function popoverComponent(): string
    {
        return <<<'JS'
import React from 'react';
import { cn } from '../../lib/utils.js';

function Popover({ children }) {
    return <div className="relative inline-block">{children}</div>;
}

function PopoverTrigger({ children }) {
    return <>{children}</>;
}

function PopoverContent({ children, align = 'center', className }) {
    return (
        <div
            className={cn(
                'absolute z-50 w-72 rounded-md border border-ds-border bg-ds-background p-4 shadow-md',
                align === 'start' ? 'left-0' : align === 'end' ? 'right-0' : 'left-1/2 -translate-x-1/2',
                'top-full mt-2',
                className
            )}
        >
            {children}
        </div>
    );
}

export { Popover, PopoverTrigger, PopoverContent };
JS;
    }

    private function toggleComponent(): string
    {
        return <<<'JS'
import React from 'react';
import { cn } from '../../lib/utils.js';

function Toggle({ children, pressed, onPressedChange, className }) {
    return (
        <button
            type="button"
            aria-pressed={pressed}
            onClick={() => onPressedChange?.(!pressed)}
            className={cn(
                'inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-ds-background transition-colors hover:bg-ds-muted hover:text-ds-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ds-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
                pressed ? 'bg-ds-transparent text-ds-foreground' : 'bg-transparent',
                className
            )}
        >
            {children}
        </button>
    );
}

export { Toggle };
JS;
    }

    private function skeletonComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

function Skeleton({ className, ...props }) {
    return (
        <div
            className={cn('animate-pulse rounded-md bg-ds-muted', className)}
            {...props}
        />
    );
}

export { Skeleton };
JS;
    }

    private function scrollAreaComponent(): string
    {
        return <<<'JS'
import * as React from 'react';
import { cn } from '../../lib/utils.js';

const ScrollArea = React.forwardRef(({ children, className, ...props }, ref) => (
    <div
        ref={ref}
        className={cn('relative overflow-auto', className)}
        {...props}
    >
        {children}
    </div>
));
ScrollArea.displayName = 'ScrollArea';

export { ScrollArea };
JS;
    }

    private function sheetComponent(): string
    {
        return <<<'JS'
import React from 'react';
import { X } from 'lucide-react';
import { cn } from '../../lib/utils.js';

function Sheet({ children, open, onOpenChange }) {
    if (!open) return children;
    return (
        <div className="fixed inset-0 z-50">
            <div className="fixed inset-0 bg-black/50" onClick={() => onOpenChange?.(false)} />
            {children}
        </div>
    );
}

function SheetContent({ children, side = 'right', className }) {
    const sideClasses = {
        left: 'left-0 h-full w-3/4 max-w-sm',
        right: 'right-0 h-full w-3/4 max-w-sm',
        top: 'top-0 w-full h-auto',
        bottom: 'bottom-0 w-full h-auto',
    };
    return (
        <div className={cn('fixed z-50 bg-ds-background p-6 shadow-lg', sideClasses[side], className)}>
            {children}
        </div>
    );
}

function SheetHeader({ children, className }) {
    return <div className={cn('flex flex-col space-y-1.5', className)}>{children}</div>;
}

function SheetTitle({ children, className }) {
    return <h2 className={cn('text-lg font-semibold leading-none tracking-tight', className)}>{children}</h2>;
}

function SheetDescription({ children, className }) {
    return <p className={cn('text-sm text-ds-muted-foreground', className)}>{children}</p>;
}

export { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription };
JS;
    }

    private function marqueeComponent(): string
    {
        return <<<'JS'
import React from 'react';
import { cn } from '../../lib/utils.js';

export default function Marquee({
    children,
    speed = '30s',
    gap = '3rem',
    className = '',
    reverse = false
}) {
    return (
        <div
            className={cn("marquee-container relative w-full overflow-hidden", className)}
        >
            <div
                className="marquee-track flex w-max"
                style={{
                    animationDuration: speed,
                    gap: gap,
                    animationDirection: reverse ? 'reverse' : 'normal'
                }}
            >
                {/* Group 1 */}
                <div className="flex shrink-0 items-center" style={{ gap: gap }}>
                    {children}
                </div>
                {/* Group 2 (Duplicate for seamless loop) */}
                <div className="flex shrink-0 items-center" style={{ gap: gap }} aria-hidden="true">
                    {children}
                </div>
            </div>
        </div>
    );
}
JS;
    }

    private function carouselComponent(): string
    {
        return <<<'JS'
import React, { useState, useEffect, useCallback, Children } from 'react';
import { cn } from '../../lib/utils.js';

// Map slide count to responsive width classes
const slideWidthClasses = {
  1: 'w-full',
  2: 'w-full md:w-1/2',
  3: 'w-full md:w-1/2 lg:w-1/3',
  4: 'w-full sm:w-1/2 lg:w-1/4',
  5: 'w-full sm:w-1/2 lg:w-1/5'
};

export default function Carousel({
  children,
  slidesPerView = 3,
  autoplaySpeed = 0, // 0 = disabled, >0 = milliseconds
  showArrows = true,
  showDots = true,
  showFade = true,
  header = null,
  className = ''
}) {
  const items = Children.toArray(children).filter(Boolean);
  const [currentIndex, setCurrentIndex] = useState(0);

  // Ensure slidesPerView is a valid number between 1 and 5
  const validSlidesPerView = Math.min(Math.max(Number(slidesPerView) || 3, 1), 5);
  const maxIndex = Math.max(0, items.length - validSlidesPerView);

  const nextSlide = useCallback(() => {
    setCurrentIndex((prev) => (prev >= maxIndex ? 0 : prev + 1));
  }, [maxIndex]);

  const prevSlide = useCallback(() => {
    setCurrentIndex((prev) => (prev <= 0 ? maxIndex : prev - 1));
  }, [maxIndex]);

  // Reset index if items decrease
  useEffect(() => {
    if (currentIndex > maxIndex) {
      setCurrentIndex(maxIndex);
    }
  }, [maxIndex, currentIndex]);

  // Autoplay logic
  useEffect(() => {
    if (autoplaySpeed > 0 && items.length > 0) {
      const timer = setInterval(nextSlide, autoplaySpeed);
      return () => clearInterval(timer);
    }
  }, [autoplaySpeed, nextSlide, items.length]);

  if (items.length === 0) return null;

  return (
    <div className={cn("flex flex-col gap-6 w-full", className)}>

      {/* Header + Arrows (Top Row) */}
      {(header || (showArrows && maxIndex > 0)) && (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
          {header}
          {showArrows && maxIndex > 0 && (
            <div className="flex justify-end gap-3">
              <button
                onClick={prevSlide}
                className="flex size-11 items-center justify-center rounded-[var(--ds-radius)] border transition-colors hover:bg-[var(--ds-muted)]"
                style={{ borderColor: 'var(--ds-border)', color: 'var(--ds-foreground)' }}
                aria-label="Previous slide"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m15 18-6-6 6-6"></path></svg>
              </button>
              <button
                onClick={nextSlide}
                className="flex size-11 items-center justify-center rounded-[var(--ds-radius)] border transition-colors hover:bg-[var(--ds-muted)]"
                style={{ borderColor: 'var(--ds-border)', color: 'var(--ds-foreground)' }}
                aria-label="Next slide"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m9 18 6-6-6-6"></path></svg>
              </button>
            </div>
          )}
        </div>
      )}

      {/* Carousel Track Wrapper */}
      <div className="relative">
        {/* Fade Edges */}
        {showFade && (
          <>
            <div className="pointer-events-none absolute inset-y-0 left-0 z-10 w-16 bg-gradient-to-r from-[var(--ds-background)] to-transparent lg:w-24"></div>
            <div className="pointer-events-none absolute inset-y-0 right-0 z-10 w-16 bg-gradient-to-l from-[var(--ds-background)] to-transparent lg:w-24"></div>
          </>
        )}

        {/* Carousel Track */}
        <div className="overflow-hidden">
          <div
            className="flex transition-transform duration-500 ease-out"
            style={{ transform: `translateX(-${currentIndex * (100 / validSlidesPerView)}%)` }}
          >
            {items.map((child, i) => (
              <div
                key={i}
                className={cn("shrink-0 grow-0 px-2", slideWidthClasses[validSlidesPerView])}
              >
                {child}
              </div>
            ))}
          </div>
        </div>

        {/* Dot Navigation */}
        {showDots && maxIndex > 0 && (
          <div className="mt-8 flex justify-center gap-2">
            {Array.from({ length: maxIndex + 1 }).map((_, i) => (
              <button
                key={i}
                onClick={() => setCurrentIndex(i)}
                className={cn(
                  "h-2 rounded-full transition-all",
                  i === currentIndex ? "w-8" : "w-2 hover:opacity-70"
                )}
                style={{
                  backgroundColor: i === currentIndex ? 'var(--ds-primary)' : 'var(--ds-muted-foreground)',
                  opacity: i === currentIndex ? 1 : 0.3
                }}
                aria-label={`Go to slide ${i + 1}`}
              />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
JS;
    }

    private function brandIconsComponent(): string
    {
        $path = resource_path('ai-builder/BrandIcons.jsx');
        if (File::exists($path)) {
            return File::get($path);
        }
        return "import React from 'react';\nexport const Twitter = () => null;\n";
    }

    private function navbarComponent(string $title = 'Brand'): string
    {
        $titleLiteral = json_encode($title, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return str_replace('__BRAND_NAME__', $titleLiteral, <<<'JS'
import React, { useState } from 'react';
import { Menu, X } from 'lucide-react';
import { cn } from '../../lib/utils.js';

export default function Navbar({ links = [], logo = __BRAND_NAME__, className }) {
    const [mobileOpen, setMobileOpen] = useState(false);

    const defaultLinks = links.length ? links : [
        { label: 'Features', href: '#features' },
        { label: 'Pricing', href: '#pricing' },
        { label: 'About', href: '#about' },
        { label: 'Contact', href: '#contact' },
    ];

    return (
        <nav className={cn('sticky top-0 z-50 w-full border-b border-ds-border/80 bg-ds-background/80 backdrop-blur-md', className)}>
            <div className="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
                <a href="#" className="flex items-center gap-3 text-xl font-bold tracking-tight text-ds-foreground">
                    <img data-app-logo src="" alt="App logo" className="h-8 w-8 rounded-lg object-contain hidden" />
                    <span data-app-title>{logo}</span>
                </a>
                <div className="hidden md:flex items-center gap-8">
                    {defaultLinks.map((l) => (
                        <a key={l.href} href={l.href} className="text-sm font-medium text-ds-muted-foreground hover:text-ds-foreground transition-colors">
                            {l.label}
                        </a>
                    ))}
                    <a href="#cta" className="inline-flex items-center rounded-lg bg-ds-primary px-4 py-2 text-sm font-medium text-ds-primary-foreground hover:opacity-90 transition">
                        Get Started
                    </a>
                </div>
                <button
                    className="md:hidden p-2 text-ds-muted-foreground"
                    onClick={() => setMobileOpen((v) => !v)}
                    aria-label="Toggle menu"
                >
                    {mobileOpen ? <X size={24} /> : <Menu size={24} />}
                </button>
            </div>
            {mobileOpen && (
                <div className="md:hidden border-t border-ds-border bg-ds-background px-6 pb-4">
                    {defaultLinks.map((l) => (
                        <a
                            key={l.href}
                            href={l.href}
                            className="block py-3 text-sm font-medium text-ds-muted-foreground hover:text-ds-foreground"
                            onClick={() => setMobileOpen(false)}
                        >
                            {l.label}
                        </a>
                    ))}
                    <a href="#cta" className="mt-2 block rounded-lg bg-ds-primary px-4 py-2.5 text-center text-sm font-medium text-ds-primary-foreground hover:opacity-90">
                        Get Started
                    </a>
                </div>
            )}
        </nav>
    );
}
JS
        );
    }

    private function footerComponent(string $title = 'Brand'): string
    {
        $titleLiteral = json_encode($title, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return str_replace('__BRAND_NAME__', $titleLiteral, <<<'JS'
import React from 'react';

export default function Footer({ brand = __BRAND_NAME__ }) {
    return (
        <footer className="w-full border-t border-ds-border bg-ds-background">
            <div className="mx-auto max-w-7xl px-6 py-12">
                <div className="flex flex-col md:flex-row items-center justify-between gap-4">
                    <a href="#" className="flex items-center gap-3 text-sm text-ds-muted-foreground">
                        <img data-app-logo src="" alt="App logo" className="h-8 w-8 rounded-lg object-contain hidden" />
                        <span data-app-title className="font-medium text-ds-foreground">{brand}</span>
                    </a>
                    <div className="flex items-center gap-6">
                        <a href="#" className="text-sm text-ds-muted-foreground hover:text-ds-foreground transition">Privacy</a>
                        <a href="#" className="text-sm text-ds-muted-foreground hover:text-ds-foreground transition">Terms</a>
                        <a href="#" className="text-sm text-ds-muted-foreground hover:text-ds-foreground transition">Twitter</a>
                    </div>
                </div>
            </div>
        </footer>
    );
}
JS
        );
    }

    private function sectionWrapperComponent(): string
    {
        return <<<'JS'
import React from 'react';
import { cn } from '../../lib/utils.js';
import GradFlow from './GradFlowProxy.jsx';

function colorToRgb(value) {
    if (!value) return null;
    if (typeof value === 'string') return value;
    if (typeof value === 'object' && 'r' in value && 'g' in value && 'b' in value) {
        return `rgb(${value.r}, ${value.g}, ${value.b})`;
    }
    return value;
}

function gradientConfigToString(config) {
    const color1 = colorToRgb(config?.color1) || 'var(--ds-primary)';
    const color2 = colorToRgb(config?.color2) || 'var(--ds-muted)';
    const color3 = colorToRgb(config?.color3) || 'var(--ds-primary)';
    return { color1, color2, color3 };
}

export default function SectionWrapper({ children, className, id, background, dark = false }) {
    const style = {};
    const classes = ['section', 'relative', 'overflow-hidden'];
    let backgroundElement = null;

    let isBackgroundInherentlyDark = dark;

    if (background) {
        const { type = 'default', config = {} } = background;
        const { color1, color2, color3 } = gradientConfigToString(config);

        if (type === 'grid') {
            backgroundElement = <div className="absolute inset-0 z-0 bg-grid" style={style}></div>;
        } else if (type === 'grid-fade') {
            backgroundElement = <div className="absolute inset-0 z-0 bg-grid-fade" style={style}></div>;
        } else if (type === 'dots') {
            backgroundElement = <div className="absolute inset-0 z-0 bg-dots" style={style}></div>;
        } else if (type === 'mesh') {
            backgroundElement = (
                <div className="absolute inset-0 z-0 bg-mesh" style={{
                    '--bg-mesh-1': color1,
                    '--bg-mesh-2': color2,
                    '--bg-mesh-3': color3,
                    '--bg-mesh-4': color2,
                    '--bg-mesh-5': color1
                }}></div>
            );
        } else if (type === 'blobs') {
            backgroundElement = (
                <div className="absolute inset-0 z-0 bg-blobs" style={{
                    '--bg-blob-1': color1,
                    '--bg-blob-2': color2
                }}></div>
            );
        } else if (type === 'image') {
            isBackgroundInherentlyDark = true;
            backgroundElement = (
                <div 
                    className="absolute inset-0 z-0 bg-image bg-image-overlay" 
                    style={{
                        '--bg-image-url': `url(${config?.src || ''})`,
                        '--bg-image-overlay': config?.overlay || 'rgba(0,0,0,0.4)'
                    }}
                ></div>
            );
        } else if (type === 'gradflow') {
            isBackgroundInherentlyDark = true;
            backgroundElement = (
                <GradFlow config={config || {}} className="absolute inset-0 z-0" />
            );
        }
    }

    if (isBackgroundInherentlyDark) {
        classes.push('section-theme-dark');
    } else {
        classes.push('bg-section-default');
    }

    return (
        <section
            id={id}
            className={cn(classes, className)}
        >
            {backgroundElement}
            
            <div className="container relative z-10">
                {children}
            </div>
        </section>
    );
}
JS;
    }
}
