<?php

namespace App\Http\Controllers;

use App\Services\VisitorTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitorTrackingApiController extends Controller
{
    public function __construct(
        private readonly VisitorTrackingService $trackingService
    ) {}

    public function event(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:click'],
            'track_id' => ['nullable', 'string', 'max:255'],
            'label' => ['nullable', 'string', 'max:255'],
            'selector' => ['nullable', 'string', 'max:500'],
            'url' => ['nullable', 'string', 'max:2000'],
            'meta' => ['nullable', 'array'],
        ]);

        try {
            $this->trackingService->logClick($request, $data);
        } catch (\Throwable $e) {
            // fail silently
        }

        return response()->json(['ok' => true]);
    }

    public function dwell(Request $request): JsonResponse
    {
        $data = $request->validate([
            'url' => ['nullable', 'string', 'max:2000'],
            'dwell_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
        ]);

        try {
            $this->trackingService->logDwell($request, $data);
        } catch (\Throwable $e) {
            // fail silently
        }

        return response()->json(['ok' => true]);
    }
}
