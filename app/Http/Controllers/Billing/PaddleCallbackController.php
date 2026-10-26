<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaddleCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        return redirect()->route('customer.billing.success', ['provider' => 'paddle']);
    }
}
