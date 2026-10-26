<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class ReplyServer extends Model
{
    use HasFactory, SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($replyServer) {
            $replyServer->hostname = static::sanitizeHostname($replyServer->hostname);
        });

        static::creating(function ($replyServer) {
            // Auto-disable SSL for known problematic hosting providers
            $problematicHosts = [
                'siteground.eu',
                'siteground.net',
                'siteground.com',
                'hostinger.com',
                'hostinger.co.uk',
                'bluehost.com',
                'bluehost.in',
                'godaddy.com',
                'namecheap.com',
                'uk1006.siteground.eu'
            ];

            foreach ($problematicHosts as $host) {
                if (str_contains(strtolower($replyServer->hostname ?? ''), $host)) {
                    $replyServer->validate_ssl = false;
                    Log::info('Auto-disabled SSL validation for known problematic host', [
                        'hostname' => $replyServer->hostname,
                        'host_pattern' => $host
                    ]);
                    break;
                }
            }
        });
    }

    protected static function sanitizeHostname(?string $host): string
    {
        $host = trim((string) $host);
        if (str_contains($host, ' - ')) {
            $parts = explode(' - ', $host);
            $candidate = trim(end($parts));
            if (str_contains($candidate, '.') && !str_contains($candidate, ' ')) {
                $host = $candidate;
            }
        }
        if (str_contains($host, ' ')) {
            $host = strtok($host, ' ');
        }
        return $host;
    }

    protected $fillable = [
        'customer_id',
        'name',
        'reply_domain',
        'protocol',
        'hostname',
        'port',
        'encryption',
        'username',
        'password',
        'mailbox',
        'active',
        'delete_after_processing',
        'max_emails_per_batch',
        'validate_ssl',
        'process_logs',
        'error_logs',
        'last_processed_at',
        'last_error_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'delete_after_processing' => 'boolean',
            'validate_ssl' => 'boolean',
            'port' => 'integer',
            'max_emails_per_batch' => 'integer',
            'process_logs' => 'array',
            'error_logs' => 'array',
            'last_processed_at' => 'datetime',
            'last_error_at' => 'datetime',
        ];
    }

    public function setPasswordAttribute($value): void
    {
        $value = (string) ($value ?? '');
        $value = rtrim($value);
        $this->attributes['password'] = $value;
    }

    public function getPasswordAttribute($value): string
    {
        return (string) ($value ?? '');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isActive(): bool
    {
        return $this->active;
    }
}
