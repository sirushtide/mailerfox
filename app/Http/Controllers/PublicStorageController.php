<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicStorageController extends Controller
{
    public function show(Request $request, string $path)
    {
        $path = ltrim($path, '/');

        if ($path === '' || Str::contains($path, '..') || Str::contains($path, "\0")) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (config('filesystems.disks.public.driver') !== 'local') {
            return redirect()->away($disk->url($path));
        }

        if (!$disk->exists($path)) {
            abort(404);
        }

        $absolutePath = $disk->path($path);

        if (!is_file($absolutePath)) {
            abort(404);
        }

        $headers = [
            'Cache-Control' => 'public, max-age=31536000',
        ];

        // Derive the Content-Type from the file extension instead of letting
        // Symfony guess it from the file contents. fileinfo/libmagic reports
        // SVG as text/plain on some servers, which makes the browser display
        // the raw SVG markup instead of rendering the image.
        $mimeType = $this->mimeTypeForExtension(strtolower(pathinfo($path, PATHINFO_EXTENSION)));

        if ($mimeType !== null) {
            $headers['Content-Type'] = $mimeType;
        }

        return response()->file($absolutePath, $headers);
    }

    private function mimeTypeForExtension(string $extension): ?string
    {
        return match ($extension) {
            'svg', 'svgz' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg', 'jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'avif' => 'image/avif',
            'ico' => 'image/x-icon',
            'pdf' => 'application/pdf',
            'css' => 'text/css',
            'js', 'mjs' => 'application/javascript',
            'json' => 'application/json',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'otf' => 'font/otf',
            default => null,
        };
    }
}
