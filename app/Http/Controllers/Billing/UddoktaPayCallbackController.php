<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\UddoktaPayPaymentService;
use Illuminate\Http\Request;

class UddoktaPayCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        $invoiceId = $request->query('invoice_id');
        if (!is_string($invoiceId) || trim($invoiceId) === '') {
            $invoiceId = $request->input('invoice_id');
        }

        if (!is_string($invoiceId) || trim($invoiceId) === '') {
            return redirect()->route('customer.billing.index')->with('error', 'Missing UddoktaPay invoice id.');
        }

        try {
            $service = app(UddoktaPayPaymentService::class);
            $subscription = $service->applyVerifiedPaymentByInvoice(trim($invoiceId));
        } catch (\Throwable $e) {
            return redirect()->route('customer.billing.index')->with('error', $e->getMessage());
        }

        if (!$subscription) {
            return redirect()->route('customer.billing.index')->with('error', 'Unable to match subscription for this UddoktaPay payment.');
        }

        if (in_array($subscription->status, ['active', 'trialing'], true)) {
            return redirect()->route('customer.billing.success', ['provider' => 'uddoktapay', 'invoice_id' => trim($invoiceId)]);
        }

        return redirect()->route('customer.billing.index')->with('error', 'Payment not completed.');
    }
}
