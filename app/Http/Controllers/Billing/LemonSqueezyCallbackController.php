<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LemonSqueezyCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        return redirect()->route('customer.billing.success', ['provider' => 'lemonsqueezy']);
    }
}
