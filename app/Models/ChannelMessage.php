<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelMessage extends Model
{
    protected $fillable = [
        'customer_id',
        'thread_id',
        'account_id',
        'contact_id',
        'channel',
        'direction',
        'provider_message_id',
        'from_address',
        'to_address',
        'body',
        'media',
        'payload',
        'status',
        'error',
        'sent_at',
        'delivered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'media'        => 'array',
            'payload'      => 'array',
            'sent_at'      => 'datetime',
            'delivered_at' => 'datetime',
            'read_at'      => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChannelThread::class, 'thread_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class, 'account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ChannelContact::class, 'contact_id');
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'in');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'out');
    }
}
