<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OutreachLead extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $lead) {
            if (empty($lead->uuid)) {
                $lead->uuid = (string) Str::uuid();
            }
        });
    }

    protected $fillable = [
        'campaign_id',
        'uuid',
        'first_name',
        'last_name',
        'email',
        'company',
        'status',
        'meta',
        'current_step',
        'next_send_at',
        'sequence_completed_at',
        'last_activity_at',
    ];

    protected function casts(): array
    {
        return [
            'meta'                  => 'array',
            'current_step'          => 'integer',
            'next_send_at'          => 'datetime',
            'sequence_completed_at' => 'datetime',
            'last_activity_at'      => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(OutreachCampaign::class, 'campaign_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getInitialsAttribute(): string
    {
        $first = mb_substr($this->first_name ?? '', 0, 1);
        $last  = mb_substr($this->last_name ?? '', 0, 1);
        return strtoupper($first . $last) ?: strtoupper(mb_substr($this->email, 0, 2));
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'replied'      => 'text-green-700 bg-green-50 border-green-200 dark:bg-green-900/20 dark:text-green-400',
            'opened'       => 'text-blue-700 bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:text-blue-400',
            'clicked'      => 'text-purple-700 bg-purple-50 border-purple-200 dark:bg-purple-900/20 dark:text-purple-400',
            'bounced'      => 'text-red-700 bg-red-50 border-red-200 dark:bg-red-900/20 dark:text-red-400',
            'unsubscribed' => 'text-gray-600 bg-gray-100 border-gray-200 dark:bg-white/10 dark:text-gray-400',
            'failed'       => 'text-orange-700 bg-orange-50 border-orange-200 dark:bg-orange-900/20 dark:text-orange-400',
            'sent'         => 'text-gray-500 bg-gray-50 border-gray-200 dark:bg-white/5 dark:text-gray-400',
            default        => 'text-gray-500 bg-gray-50 border-gray-200 dark:bg-white/5 dark:text-gray-400',
        };
    }

    /**
     * Build the key/value map of tokens available for this lead.
     *
     * Built-in tokens come from direct columns. Any additional key/value pairs
     * stored in the `meta` JSON column are exposed as custom tokens, so a user
     * can define arbitrary variables (e.g. jobTitle, website) per-lead.
     */
    public function tokenMap(): array
    {
        $first = (string) ($this->first_name ?? '');
        $last  = (string) ($this->last_name ?? '');

        $map = [
            'firstName' => $first,
            'lastName'  => $last,
            'fullName'  => trim($first . ' ' . $last),
            'email'     => (string) ($this->email ?? ''),
            'company'   => (string) ($this->company ?? ''),
        ];

        if (is_array($this->meta)) {
            foreach ($this->meta as $key => $value) {
                if (!is_string($key) || $key === '') {
                    continue;
                }
                if (is_scalar($value) || $value === null) {
                    $map[$key] = (string) ($value ?? '');
                }
            }
        }

        return $map;
    }

    /**
     * Replace every `{token}` placeholder in the given text with this lead's
     * value. Unknown tokens are left untouched so they remain visible during
     * testing.
     */
    public function renderTokens(?string $text): string
    {
        if ($text === null || $text === '') {
            return (string) $text;
        }

        $map = $this->tokenMap();

        return preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($matches) use ($map) {
            $key = $matches[1];
            return array_key_exists($key, $map) ? $map[$key] : $matches[0];
        }, $text) ?? $text;
    }

    /**
     * Token map that also honours per-campaign variable->field mappings defined
     * in `settings.custom_variables`. Each variable can map to a built-in lead
     * column (email/first_name/last_name/full_name/company) or to a custom
     * field key stored on the lead's meta JSON. When `field` is empty the
     * legacy behaviour applies (token resolves to `meta[tokenName]`).
     */
    public function tokenMapForCampaign(OutreachCampaign $campaign): array
    {
        $map = $this->tokenMap();

        $columnMap = [
            'email'      => (string) ($this->email ?? ''),
            'first_name' => (string) ($this->first_name ?? ''),
            'last_name'  => (string) ($this->last_name ?? ''),
            'full_name'  => trim(((string) ($this->first_name ?? '')) . ' ' . ((string) ($this->last_name ?? ''))),
            'company'    => (string) ($this->company ?? ''),
        ];

        $meta      = is_array($this->meta) ? $this->meta : [];
        $variables = (array) $campaign->getSetting('custom_variables', []);

        foreach ($variables as $variable) {
            if (!is_array($variable)) {
                continue;
            }
            $token = trim((string) ($variable['tokenName'] ?? ''));
            if ($token === '' || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $token)) {
                continue;
            }
            $field = trim((string) ($variable['field'] ?? ''));

            if ($field === '') {
                // Legacy: tokenName itself is the meta key.
                if (!array_key_exists($token, $map)) {
                    $map[$token] = (string) ($meta[$token] ?? '');
                }
                continue;
            }

            if (array_key_exists($field, $columnMap)) {
                $map[$token] = $columnMap[$field];
            } else {
                $map[$token] = (string) ($meta[$field] ?? '');
            }
        }

        return $map;
    }

    /**
     * Render text using campaign-aware token mapping (variable->field).
     */
    public function renderTokensForCampaign(?string $text, OutreachCampaign $campaign): string
    {
        if ($text === null || $text === '') {
            return (string) $text;
        }

        $map = $this->tokenMapForCampaign($campaign);

        return preg_replace_callback('/@?\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}|\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}/', function ($matches) use ($map) {
            $key = $matches[1] ?: $matches[2];
            return array_key_exists($key, $map) ? $map[$key] : $matches[0];
        }, $text) ?? $text;
    }
}
