<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelThread extends Model
{
    protected $fillable = [
        'customer_id',
        'channel',
        'account_id',
        'contact_id',
        'subject',
        'status',
        'unread_count',
        'is_starred',
        'assigned_to',
        'tags',
        'meta',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'tags'            => 'array',
            'meta'            => 'array',
            'is_starred'      => 'boolean',
            'last_message_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(ChannelContact::class, 'contact_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChannelAccount::class, 'account_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChannelMessage::class, 'thread_id')->orderBy('created_at');
    }

    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }
}
