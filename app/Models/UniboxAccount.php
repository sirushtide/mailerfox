<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniboxAccount extends Model
{
    protected $fillable = [
        'customer_id', 'name', 'email', 'type',
        'imap_host', 'imap_port', 'imap_encryption', 'imap_username', 'imap_password',
        'smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password',
        'oauth_token', 'oauth_refresh_token', 'oauth_expires_at',
        'status', 'last_error', 'last_synced_at', 'last_uid',
    ];

    protected $hidden = ['imap_password', 'smtp_password', 'oauth_token', 'oauth_refresh_token'];

    protected $casts = [
        'oauth_expires_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $account) {
            if ($account->isDirty('imap_password')) {
                $old = $account->getOriginal('imap_password');
                $new = $account->imap_password;
                \Illuminate\Support\Facades\Log::warning('UniBoxAccount imap_password changed', [
                    'account_id' => $account->id,
                    'email' => $account->email,
                    'old_len' => $old ? strlen((string) $old) : 0,
                    'new_len' => $new ? strlen((string) $new) : 0,
                    'old_starts_eyj' => $old ? (str_starts_with((string) $old, 'eyJ') ? 'yes' : 'no') : 'null',
                    'new_starts_eyj' => $new ? (str_starts_with((string) $new, 'eyJ') ? 'yes' : 'no') : 'null',
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12),
                ]);
            }
            if ($account->isDirty('smtp_password')) {
                $old = $account->getOriginal('smtp_password');
                $new = $account->smtp_password;
                \Illuminate\Support\Facades\Log::warning('UniBoxAccount smtp_password changed', [
                    'account_id' => $account->id,
                    'email' => $account->email,
                    'old_len' => $old ? strlen((string) $old) : 0,
                    'new_len' => $new ? strlen((string) $new) : 0,
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 12),
                ]);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(UniboxConversation::class, 'account_id');
    }

    public static function syncFromDeliveryServersForCustomer(int $customerId): void
    {
        $deliveryServers = DeliveryServer::query()
            ->where('customer_id', $customerId)
            ->whereIn('type', ['smtp', 'gmail', 'outlook', 'gmail-api'])
            ->get();

        foreach ($deliveryServers as $server) {
            // Skip Gmail SMTP delivery servers — only Gmail OAuth/API connects to the inbox.
            if ($server->type === 'gmail') {
                continue;
            }
            if ($server->type === 'smtp' && $server->hostname === 'smtp.gmail.com') {
                continue;
            }

            if ($server->type === 'gmail-api') {
                $account = static::upsertFromGmailApi($customerId, $server);
            } else {
                $account = static::upsertFromSmtpServer($customerId, $server);
            }

            if ($account && $account->status === 'active') {
                $syncInterval = (int) (Setting::get('unibox_sync_interval') ?? 5);
                $lastSync = $account->last_synced_at;
                if (!$lastSync || $lastSync->diffInMinutes(now()) >= $syncInterval) {
                    \App\Jobs\SyncUniboxAccountJob::dispatch($account->id);
                }
            }
        }

        static::syncFromReplyServersForCustomer($customerId);
    }

    public static function syncFromReplyServersForCustomer(int $customerId): void
    {
        $replyServers = \App\Models\ReplyServer::query()
            ->where('customer_id', $customerId)
            ->where('active', true)
            ->get();

        foreach ($replyServers as $server) {
            if (!$server->username || !$server->password) {
                continue;
            }

            $email = strtolower(trim((string) ($server->username ?? '')));
            if ($email === '') {
                continue;
            }

            // UniBox only supports IMAP sync; skip POP3 reply servers.
            if (strtolower((string) ($server->protocol ?? 'imap')) === 'pop3') {
                continue;
            }

            $existing = static::where('customer_id', $customerId)->where('email', $email)->first();
            if ($existing) {
                // Account already exists; never modify it via background sync.
                // The user manages credentials exclusively through the UI.
                continue;
            }

            $host = static::sanitizeHostname($server->hostname);
            if ($host === '') {
                continue;
            }

            $attributes = [
                'name' => $server->name ?: $email,
                'type' => 'imap',
                'status' => 'active',
                'imap_host' => $host,
                'imap_port' => (int) ($server->port ?? 993),
                'imap_encryption' => (string) ($server->encryption ?? 'ssl'),
                'imap_username' => $server->username,
                'smtp_host' => $host,
                'smtp_port' => (int) ($server->port ?? 587),
                'smtp_encryption' => (string) ($server->encryption ?? 'tls'),
                'smtp_username' => $server->username,
            ];

            $normalizedPassword = static::normalizeEncryptedValue($server->password);
            if ($normalizedPassword) {
                $attributes['imap_password'] = $normalizedPassword;
                $attributes['smtp_password'] = $normalizedPassword;
            }

            $account = static::create($attributes + ['customer_id' => $customerId, 'email' => $email]);

            if ($account->status === 'active') {
                $syncInterval = (int) (Setting::get('unibox_sync_interval') ?? 5);
                $lastSync = $account->last_synced_at;
                if (!$lastSync || $lastSync->diffInMinutes(now()) >= $syncInterval) {
                    \App\Jobs\SyncUniboxAccountJob::dispatch($account->id);
                }
            }
        }
    }

    protected static function upsertFromSmtpServer(int $customerId, DeliveryServer $server): ?self
    {
        if (!$server->username || !$server->password) {
            return null;
        }

        $imap = static::inferImapConfiguration($server);
        if (!$imap) {
            return null;
        }

        $email = $server->from_email ?: $server->username;
        if (!is_string($email) || trim($email) === '') {
            return null;
        }

        $email = strtolower(trim($email));
        $defaultName = is_string($server->name) && trim($server->name) !== '' ? $server->name : $email;
        $smtpEncryption = $server->encryption ?: 'tls';

        $existing = static::where('customer_id', $customerId)->where('email', $email)->first();
        if ($existing) {
            // Account already exists; never modify it via background sync.
            return $existing;
        }

        $smtpHost = static::sanitizeHostname($server->hostname);
        if ($smtpHost === '' || $imap['host'] === '') {
            return null;
        }

        $attributes = [
            'name' => $defaultName,
            'type' => in_array($server->type, ['gmail', 'outlook'], true) ? $server->type : 'imap',
            'status' => $server->status === 'inactive' ? 'paused' : 'active',
            'last_error' => null,
            'imap_host' => $imap['host'],
            'imap_port' => $imap['port'],
            'imap_encryption' => $imap['encryption'],
            'imap_username' => $server->username,
            'smtp_host' => $smtpHost,
            'smtp_port' => $server->port ?: 587,
            'smtp_encryption' => $smtpEncryption,
            'smtp_username' => $server->username,
            'oauth_token' => null,
            'oauth_refresh_token' => null,
        ];

        $normalizedPassword = static::normalizeEncryptedValue($server->password);
        if ($normalizedPassword) {
            $attributes['imap_password'] = $normalizedPassword;
            $attributes['smtp_password'] = $normalizedPassword;
        }

        return static::create($attributes + ['customer_id' => $customerId, 'email' => $email]);
    }

    protected static function upsertFromGmailApi(int $customerId, DeliveryServer $server): ?self
    {
        $settings = is_array($server->settings) ? $server->settings : [];
        $refreshToken = $settings['refresh_token'] ?? null;
        $email = $server->from_email;

        if (!$refreshToken || !$email) {
            return null;
        }

        $email = strtolower(trim($email));
        $defaultName = is_string($server->name) && trim($server->name) !== '' ? $server->name : $email;

        $existing = static::where('customer_id', $customerId)->where('email', $email)->first();
        if ($existing) {
            // Account already exists; never modify it via background sync.
            return $existing;
        }

        $attributes = [
            'name' => $defaultName,
            'type' => 'gmail',
            'imap_host' => 'imap.gmail.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'imap_username' => $email,
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => $email,
            'oauth_token' => null,
            'oauth_refresh_token' => static::normalizeEncryptedValue($refreshToken),
            'oauth_expires_at' => null,
            'status' => $server->status === 'inactive' ? 'paused' : 'active',
            'last_error' => null,
            'imap_password' => null,
            'smtp_password' => null,
        ];

        return static::create($attributes + ['customer_id' => $customerId, 'email' => $email]);
    }

    protected static function inferImapConfiguration(DeliveryServer $server): ?array
    {
        if ($server->type === 'gmail' || $server->hostname === 'smtp.gmail.com') {
            return ['host' => 'imap.gmail.com', 'port' => 993, 'encryption' => 'ssl'];
        }

        if (
            $server->type === 'outlook'
            || in_array($server->hostname, ['smtp.office365.com', 'outlook.office365.com'], true)
        ) {
            return ['host' => 'outlook.office365.com', 'port' => 993, 'encryption' => 'tls'];
        }

        if ($server->type !== 'smtp' || !is_string($server->hostname) || trim($server->hostname) === '') {
            return null;
        }

        $host = static::sanitizeHostname($server->hostname);
        if ($host === '') {
            return null;
        }
        if (str_starts_with($host, 'smtp.')) {
            $host = 'imap.' . substr($host, 5);
        }

        return ['host' => $host, 'port' => 993, 'encryption' => 'ssl'];
    }

    protected static function sanitizeHostname(?string $host): string
    {
        $host = trim((string) $host);
        // Remove common label prefixes like "Hostinger - " that sometimes get mixed into hostname fields.
        if (str_contains($host, ' - ')) {
            $parts = explode(' - ', $host);
            // Keep the last part if it looks like a domain, otherwise keep the whole thing for validation downstream.
            $candidate = trim(end($parts));
            if (str_contains($candidate, '.') && !str_contains($candidate, ' ')) {
                $host = $candidate;
            }
        }
        // Strip spaces and anything after a space if it doesn't look like a host.
        if (str_contains($host, ' ')) {
            $host = strtok($host, ' ');
        }
        return $host;
    }

    public static function normalizeEncryptedValue(?string $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        // Already encrypted with current Laravel key → keep as-is.
        try {
            decrypt($value);
            return $value;
        } catch (\Throwable $e) {
            // Not encrypted with current Laravel key.
        }

        // Check if already encrypted with custom UniBox key.
        $custom = \App\Services\UniboxEncryptionService::decrypt($value);
        if ($custom !== null) {
            return $value;
        }

        if (static::looksLikeLaravelEncryptedPayload($value)) {
            return null;
        }

        return \App\Services\UniboxEncryptionService::encrypt($value);
    }

    protected static function looksLikeLaravelEncryptedPayload(string $value): bool
    {
        $decoded = base64_decode($value, true);
        if (!is_string($decoded) || $decoded === '') {
            return false;
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)) {
            return false;
        }

        return isset($payload['iv'], $payload['value'], $payload['mac']);
    }
}
