<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniboxMessage extends Model
{
    protected $fillable = [
        'conversation_id', 'customer_id', 'message_id',
        'from_email', 'from_name', 'to', 'cc', 'bcc',
        'subject', 'body_html', 'body_text', 'type', 'status',
        'attachments', 'in_reply_to', 'headers', 'sent_at', 'received_at',
    ];

    protected $casts = [
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'attachments' => 'array',
        'headers' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(UniboxConversation::class, 'conversation_id');
    }

    public function getFromDisplayAttribute(): string
    {
        return $this->from_name ? "{$this->from_name} <{$this->from_email}>" : $this->from_email;
    }

    public function getBodyPreviewAttribute(): string
    {
        $text = $this->body_text ?: strip_tags($this->body_html ?? '');
        return mb_substr(trim($text), 0, 120);
    }
}
