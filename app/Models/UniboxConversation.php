<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UniboxConversation extends Model
{
    protected $fillable = [
        'customer_id', 'account_id', 'contact_id', 'assigned_to', 'team_id',
        'subject', 'thread_id', 'status', 'folder', 'category', 'is_read', 'is_starred',
        'tags', 'snoozed_until', 'last_message_at', 'sla_deadline',
        'message_count', 'participants',
    ];

    protected $casts = [
        'tags' => 'array',
        'participants' => 'array',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'snoozed_until' => 'datetime',
        'last_message_at' => 'datetime',
        'sla_deadline' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(UniboxAccount::class, 'account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(UniboxContact::class, 'contact_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(UniboxTeam::class, 'team_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(UniboxMessage::class, 'conversation_id')->orderBy('created_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(UniboxNote::class, 'conversation_id')->orderBy('created_at');
    }

    public function customFolders(): BelongsToMany
    {
        return $this->belongsToMany(UniboxFolder::class, 'unibox_conversation_folder', 'conversation_id', 'folder_id')->withTimestamps();
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(UniboxMessage::class, 'conversation_id')->latestOfMany();
    }

    public function getSlaDisplayAttribute(): ?string
    {
        if (!$this->sla_deadline) {
            return null;
        }
        $diff = now()->diffInMinutes($this->sla_deadline, false);
        if ($diff < 0) {
            return 'Overdue';
        }
        $hours = intdiv($diff, 60);
        $mins = $diff % 60;
        return ($hours > 0 ? "{$hours}H " : '') . "{$mins}M";
    }
}
