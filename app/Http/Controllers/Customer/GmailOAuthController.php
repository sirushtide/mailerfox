<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\DeliveryServer;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GmailOAuthController extends Controller
{
    public function redirect(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $clientId = Setting::get('google_client_id');

        if (!$clientId) {
            return redirect()->route('customer.delivery-servers.index')
                ->withErrors(['gmail_oauth' => 'Gmail OAuth is not configured. Please contact the administrator.']);
        }

        $customer = auth('customer')->user();
        $customer->enforceGroupLimit(
            'servers.limits.max_delivery_servers',
            $customer->deliveryServers()->count(),
            'Delivery server limit reached.'
        );

        $state = Str::random(40);

        session([
            'gmail_oauth_state' => $state,
            'gmail_server_name' => $request->input('name'),
        ]);

        $params = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => route('customer.oauth.gmail.callback'),
            'response_type' => 'code',
            'scope'         => 'https://mail.google.com/ https://www.googleapis.com/auth/userinfo.email',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $params);
    }

    public function callback(Request $request)
    {
        if ($request->input('state') !== session('gmail_oauth_state')) {
            return redirect()->route('customer.delivery-servers.index')
                ->withErrors(['gmail_oauth' => 'Invalid OAuth state. Please try again.']);
        }

        if ($request->has('error')) {
            return redirect()->route('customer.delivery-servers.index')
                ->withErrors(['gmail_oauth' => 'Google authorization denied: ' . $request->input('error_description', $request->input('error'))]);
        }

        $clientId     = Setting::get('google_client_id');
        $clientSecret = Setting::get('google_client_secret');

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code'          => $request->input('code'),
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => route('customer.oauth.gmail.callback'),
            'grant_type'    => 'authorization_code',
        ]);

        $tokens = $tokenResponse->json();

        if (empty($tokens['refresh_token'])) {
            $errMsg = $tokens['error_description'] ?? $tokens['error'] ?? 'No refresh token received. Try revoking access at myaccount.google.com and reconnecting.';
            return redirect()->route('customer.delivery-servers.index')
                ->withErrors(['gmail_oauth' => 'Gmail OAuth failed: ' . $errMsg]);
        }

        $userInfo = Http::withToken($tokens['access_token'])
            ->get('https://www.googleapis.com/oauth2/v2/userinfo')
            ->json();

        $email      = $userInfo['email'] ?? null;
        $serverName = session('gmail_server_name', 'My Gmail');

        $customer = auth('customer')->user();

        DeliveryServer::create([
            'customer_id' => $customer->id,
            'name'        => $serverName,
            'type'        => 'gmail-api',
            'status'      => 'active',
            'from_email'  => $email,
            'settings'    => [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $tokens['refresh_token'],
            ],
        ]);

        session()->forget(['gmail_oauth_state', 'gmail_server_name']);

        return redirect()->route('customer.delivery-servers.index')
            ->with('success', 'Gmail connected successfully' . ($email ? ' as ' . $email : '') . '.');
    }
}
