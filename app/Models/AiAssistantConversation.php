<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAssistantConversation extends Model
{
    protected $fillable = [
        'customer_id',
        'agent_id',
        'title',
        'channel',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiAssistantMessage::class, 'conversation_id')->orderBy('id');
    }
}
