<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannelWebhookEvent extends Model
{
    protected $fillable = [
        'channel',
        'provider',
        'event_type',
        'payload',
        'processed',
        'error',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'processed'    => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
