<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UniboxNote extends Model
{
    protected $fillable = ['conversation_id', 'customer_id', 'author_name', 'content'];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(UniboxConversation::class, 'conversation_id');
    }
}
