import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/visitor-tracker.js',
                'resources/js/automation-builder.jsx',
                'resources/js/outreach-sequence-editor.jsx',
                // Direct page-builder.jsx entry is unused (views load main/index.js,
                // which re-exports page-builder.jsx) — keeping it here would bundle
                // the builder twice.
                'resources/js/page-builder/main/index.js',
                'resources/js/page-ai-builder.jsx',
            ],
            refresh: true,
        }),
        react(),
    ],
    cacheDir: resolve(__dirname, '.vite'), // Use .vite in project root instead of node_modules/.vite
    server: {
        hmr: {
            host: 'localhost',
        },
        host: true,
    },
});

