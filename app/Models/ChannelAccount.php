<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChannelAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'customer_id',
        'channel',
        'provider',
        'name',
        'status',
        'is_platform',
        'from_address',
        'external_id',
        'credentials',
        'settings',
        'webhook_secret',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'credentials'    => 'encrypted:array',
            'settings'       => 'array',
            'is_platform'    => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChannelMessage::class, 'account_id');
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAccessibleBy($query, int $customerId)
    {
        return $query->where(function ($q) use ($customerId) {
            $q->where('customer_id', $customerId)
                ->orWhere('is_platform', true);
        });
    }

    public function credential(string $key, $default = null)
    {
        $credentials = is_array($this->credentials) ? $this->credentials : [];

        return $credentials[$key] ?? $default;
    }
}
