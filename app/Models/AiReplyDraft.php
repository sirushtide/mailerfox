<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiReplyDraft extends Model
{
    protected $fillable = [
        'customer_id',
        'agent_id',
        'channel',
        'thread_id',
        'message_id',
        'draft',
        'status',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChannelThread::class, 'thread_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class, 'agent_id');
    }
}
