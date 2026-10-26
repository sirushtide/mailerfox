<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RagConversation extends Model
{
    protected $fillable = [
        'customer_id',
        'admin_user_id',
        'agent_id',
        'mode',
        'title',
        'messages',
        'provider',
        'model',
        'total_tokens',
    ];

    protected function casts(): array
    {
        return [
            'messages' => 'array',
            'total_tokens' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(AiAgent::class);
    }

    public function addMessage(array $message): void
    {
        $messages = $this->messages ?? [];
        $messages[] = $message;
        $this->messages = $messages;

        if (isset($message['tokens']) && is_numeric($message['tokens'])) {
            $this->total_tokens = (int) $this->total_tokens + (int) $message['tokens'];
        }

        $this->save();
    }

    public function setMessageFeedback(int $index, string $feedback): void
    {
        $messages = $this->messages ?? [];

        if (!isset($messages[$index])) {
            return;
        }

        $messages[$index]['feedback'] = $feedback;
        $this->messages = $messages;
        $this->save();
    }
}
