<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Billing\PaddlePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaddleWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $service = app()->make(PaddlePaymentService::class);
            $service->handleWebhook($request);
        } catch (\Throwable $exception) {
            Log::error('Paddle webhook processing failed', ['message' => $exception->getMessage()]);
            return response()->json(['error' => 'Webhook processing failed'], 400);
        }

        return response()->json(['status' => 'ok']);
    }
}
