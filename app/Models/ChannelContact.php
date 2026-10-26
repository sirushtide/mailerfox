<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelContact extends Model
{
    protected $fillable = [
        'customer_id',
        'channel',
        'address',
        'name',
        'meta',
        'consent',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'meta'            => 'array',
            'consent'         => 'boolean',
            'subscribed_at'   => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChannelMessage::class, 'contact_id');
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeSubscribed($query)
    {
        return $query->where('consent', true)->whereNull('unsubscribed_at');
    }
}
