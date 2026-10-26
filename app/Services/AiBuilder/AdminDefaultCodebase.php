<?php

namespace App\Services\AiBuilder;

use Illuminate\Support\Facades\File;

/**
 * Serves the prebuilt shadcn-style admin panel codebase stored under
 * resources/ai-builder/admin-codebase/. The AI builder uses these files as
 * the scaffold when a user asks for an admin panel / dashboard, so the
 * sidebar shell and admin UI primitives are always consistent and buildable
 * without an extra npm install.
 */
class AdminDefaultCodebase
{
    /**
     * Paths that are always provided by ViteSandboxCompiler::writeInternalFiles()
     * and therefore never emitted as part of the project file list.
     */
    public const BOILERPLATE_PATHS = [
        'index.html',
        'vite.config.mjs',
        'tailwind.config.js',
        'postcss.config.js',
        'package.json',
        'src/main.jsx',
        'src/components/ui/BrandIcons.jsx',
    ];

    public function codebaseDir(): string
    {
        return resource_path('ai-builder/admin-codebase');
    }

    public function exists(): bool
    {
        return File::isDirectory($this->codebaseDir());
    }

    /**
     * Full list of project files (config files included) as
     * [{ path: string, content: string }, ...].
     */
    public function files(): array
    {
        $base = $this->codebaseDir();
        if (! $this->exists()) {
            return [];
        }

        $files = [];
        foreach ($this->walkFiles($base) as $absolute) {
            $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen($base) + 1)), '/');
            $files[] = ['path' => $relative, 'content' => File::get($absolute)];
        }

        return $files;
    }

    /**
     * Map of path => content (config files excluded) that makes a buildable
     * admin project when merged with writeInternalFiles()' boilerplate.
     */
    public function projectFileMap(): array
    {
        $map = [];
        foreach ($this->files() as $file) {
            if (in_array($file['path'], self::BOILERPLATE_PATHS, true)) {
                continue;
            }
            $map[$file['path']] = $file;
        }

        return $map;
    }

    public function get(string $relative): ?string
    {
        $path = $this->codebaseDir() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (! File::isFile($path)) {
            return null;
        }

        return File::get($path);
    }

    private function walkFiles(string $dir): array
    {
        $out = [];
        foreach (File::files($dir) as $file) {
            $out[] = $file->getPathname();
        }
        foreach (File::directories($dir) as $sub) {
            $out = array_merge($out, $this->walkFiles($sub));
        }

        return $out;
    }
}
