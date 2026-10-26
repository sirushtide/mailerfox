<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UniboxFolder extends Model
{
    protected $fillable = ['customer_id', 'account_id', 'name', 'color'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(UniboxConversation::class, 'unibox_conversation_folder', 'folder_id', 'conversation_id');
    }
}
