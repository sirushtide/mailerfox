<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAssistantMessage extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tool_name',
        'tool_result',
    ];

    protected function casts(): array
    {
        return [
            'tool_result' => 'array',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiAssistantConversation::class, 'conversation_id');
    }
}
