<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InlineImageController extends Controller
{
    /**
     * Store an image uploaded from a rich-text editor and return its public URL.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'file', 'image:allow_svg', 'max:4096', 'mimes:png,jpg,jpeg,gif,webp,svg'],
        ]);

        $file = $validated['image'];

        if ($file->getClientOriginalExtension() === 'svg' || $file->getMimeType() === 'image/svg+xml') {
            $sanitized = \App\Support\SvgSanitizer::sanitize((string) $file->getContent());

            if ($sanitized === null) {
                return response()->json(['error' => 'Invalid SVG file.'], 422);
            }

            $path = 'inline-images/' . uniqid('svg_', true) . '.svg';
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $sanitized);
        } else {
            $path = $file->storePublicly('inline-images', 'public');
        }

        $url = Storage::disk('public')->url($path);
        $url = preg_replace('~(?<!:)//+~', '/', $url);

        return response()->json([
            'url' => $url,
        ]);
    }
}
