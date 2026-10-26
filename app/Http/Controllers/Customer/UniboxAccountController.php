<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Addon;
use App\Models\ReplyServer;
use App\Models\UniboxAccount;
use App\Services\UniboxEncryptionService;
use App\Services\UniboxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UniboxAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Addon::isActive('unibox')) {
                return redirect()->route('customer.dashboard')
                    ->with('error', 'UniBox addon is not active.');
            }
            return $next($request);
        });
    }

    protected function customer()
    {
        return Auth::guard('customer')->user();
    }

    public function index()
    {
        $customer = $this->customer();
        UniboxAccount::syncFromDeliveryServersForCustomer($customer->id);
        $accounts = UniboxAccount::where('customer_id', $customer->id)
            ->where('status', '!=', 'paused')
            ->get();
        return view('customer.unibox.accounts', compact('accounts'));
    }

    public function create()
    {
        return view('customer.unibox.account-form');
    }

    public function store(Request $request)
    {
        $customer = $this->customer();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:191',
            'type' => 'required|in:imap,gmail,outlook',
            'imap_host' => 'required|string|max:191|regex:/^[a-zA-Z0-9._-]+$/',
            'imap_port' => 'required|integer|min:1|max:65535',
            'imap_encryption' => 'required|in:ssl,tls,none',
            'imap_username' => 'required|string|max:191',
            'imap_password' => 'required|string',
            'smtp_host' => 'required|string|max:191|regex:/^[a-zA-Z0-9._-]+$/',
            'smtp_port' => 'required|integer|min:1|max:65535',
            'smtp_encryption' => 'required|in:ssl,tls,none',
            'smtp_username' => 'required|string|max:191',
            'smtp_password' => 'required|string',
        ], [
            'imap_host.regex' => 'The IMAP host must be a valid hostname (no spaces or special characters).',
            'smtp_host.regex' => 'The SMTP host must be a valid hostname (no spaces or special characters).',
        ]);

        if ($request->boolean('test_connection')) {
            try {
                app(UniboxService::class)->testImapConnection($data);
                return back()->with('success', 'IMAP connection successful!');
            } catch (\Throwable $e) {
                return back()->withErrors(['imap_host' => $e->getMessage()])->withInput();
            }
        }

        $data['customer_id'] = $customer->id;
        $data['email'] = strtolower(trim($data['email']));
        $data['imap_password'] = UniboxEncryptionService::encrypt($data['imap_password']);
        $data['smtp_password'] = UniboxEncryptionService::encrypt($data['smtp_password']);
        $data['status'] = 'active';
        $data['last_error'] = null;

        // Reuse the existing row for this email if it already exists,
        // otherwise create a new account.
        UniboxAccount::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'email' => $data['email'],
            ],
            $data
        );

        return redirect()->route('customer.unibox.accounts.index')
            ->with('success', 'Email account connected successfully.');
    }

    public function edit(UniboxAccount $account)
    {
        abort_if($account->customer_id !== $this->customer()->id, 403);
        return view('customer.unibox.account-form', compact('account'));
    }

    public function update(Request $request, UniboxAccount $account)
    {
        abort_if($account->customer_id !== $this->customer()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'imap_host' => 'required|string|max:191|regex:/^[a-zA-Z0-9._-]+$/',
            'imap_port' => 'required|integer',
            'imap_encryption' => 'required|in:ssl,tls,none',
            'imap_username' => 'required|string|max:191',
            'smtp_host' => 'required|string|max:191|regex:/^[a-zA-Z0-9._-]+$/',
            'smtp_port' => 'required|integer',
            'smtp_encryption' => 'required|in:ssl,tls,none',
            'smtp_username' => 'required|string|max:191',
        ], [
            'imap_host.regex' => 'The IMAP host must be a valid hostname (no spaces or special characters).',
            'smtp_host.regex' => 'The SMTP host must be a valid hostname (no spaces or special characters).',
        ]);

        $imapPassword = $request->filled('imap_password') ? (string) $request->imap_password : null;
        $smtpPassword = $request->filled('smtp_password') ? (string) $request->smtp_password : null;

        \Illuminate\Support\Facades\Log::debug('UniBox account update', [
            'account_id' => $account->id,
            'has_imap_password' => $request->has('imap_password'),
            'imap_password_filled' => $request->filled('imap_password'),
            'imap_password_length' => $imapPassword !== null ? strlen($imapPassword) : null,
            'has_smtp_password' => $request->has('smtp_password'),
            'smtp_password_filled' => $request->filled('smtp_password'),
            'smtp_password_length' => $smtpPassword !== null ? strlen($smtpPassword) : null,
        ]);

        if ($imapPassword !== null) {
            $data['imap_password'] = UniboxEncryptionService::encrypt($imapPassword);
        }
        if ($smtpPassword !== null) {
            $data['smtp_password'] = UniboxEncryptionService::encrypt($smtpPassword);
        }

        \Illuminate\Support\Facades\Log::debug('UniBox account data prepared', [
            'account_id' => $account->id,
            'data_keys' => array_keys($data),
            'will_update_imap_password' => isset($data['imap_password']),
            'will_update_smtp_password' => isset($data['smtp_password']),
        ]);

        // Clear any previous error state so the account is eligible for
        // auto-sync again and the UI stops showing a stale error.
        $data['status'] = 'active';
        $data['last_error'] = null;

        $account->update($data);

        $freshAccount = $account->fresh();
        $smtpDecryptOk = false;
        $imapDecryptOk = false;
        if ($freshAccount && $freshAccount->smtp_password) {
            try {
                decrypt($freshAccount->smtp_password);
                $smtpDecryptOk = true;
            } catch (\Throwable $e) {
                $smtpDecryptOk = UniboxEncryptionService::decrypt($freshAccount->smtp_password) !== null;
            }
        }
        if ($freshAccount && $freshAccount->imap_password) {
            try {
                decrypt($freshAccount->imap_password);
                $imapDecryptOk = true;
            } catch (\Throwable $e) {
                $imapDecryptOk = UniboxEncryptionService::decrypt($freshAccount->imap_password) !== null;
            }
        }
        \Illuminate\Support\Facades\Log::debug('UniBox account updated', [
            'account_id' => $account->id,
            'smtp_password_present' => $freshAccount ? !empty($freshAccount->smtp_password) : null,
            'smtp_password_starts_with_encrypt' => $freshAccount ? (str_starts_with((string) $freshAccount->smtp_password, 'eyJ') ? 'yes' : 'no') : null,
            'smtp_password_decrypts_ok' => $smtpDecryptOk,
            'imap_password_present' => $freshAccount ? !empty($freshAccount->imap_password) : null,
            'imap_password_starts_with_encrypt' => $freshAccount ? (str_starts_with((string) $freshAccount->imap_password, 'eyJ') ? 'yes' : 'no') : null,
            'imap_password_decrypts_ok' => $imapDecryptOk,
        ]);

        $replyServerData = [
            'name' => $data['name'],
            'hostname' => $data['imap_host'],
            'port' => $data['imap_port'],
            'encryption' => $data['imap_encryption'],
            'username' => $data['imap_username'],
        ];

        if ($imapPassword !== null) {
            $replyServerData['password'] = $imapPassword;
        }

        ReplyServer::where('customer_id', $account->customer_id)
            ->whereRaw('LOWER(username) = ?', [strtolower($account->email)])
            ->update($replyServerData);

        return redirect()->route('customer.unibox.accounts.index')
            ->with('success', 'Account updated.');
    }

    public function rename(Request $request, UniboxAccount $account)
    {
        abort_if($account->customer_id !== $this->customer()->id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $account->update(['name' => trim($data['name'])]);

        return response()->json([
            'ok' => true,
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'email' => $account->email,
            ],
        ]);
    }

    public function destroy(UniboxAccount $account)
    {
        abort_if($account->customer_id !== $this->customer()->id, 403);
        $account->delete();
        return redirect()->route('customer.unibox.accounts.index')
            ->with('success', 'Account deleted permanently.');
    }

    public function sync(UniboxAccount $account)
    {
        abort_if($account->customer_id !== $this->customer()->id, 403);
        UniboxAccount::syncFromDeliveryServersForCustomer($account->customer_id);
        \App\Jobs\SyncUniboxAccountJob::dispatch($account->id);
        return back()->with('success', 'Sync queued.');
    }
}
