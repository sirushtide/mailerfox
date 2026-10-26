<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniboxContact extends Model
{
    protected $fillable = [
        'customer_id', 'email', 'name', 'company', 'job_title',
        'phone', 'website', 'location', 'timezone', 'avatar_url',
        'tags', 'revenue', 'conversation_count', 'first_seen_at', 'last_reply_at', 'meta',
    ];

    protected $casts = [
        'tags' => 'array',
        'meta' => 'array',
        'first_seen_at' => 'datetime',
        'last_reply_at' => 'datetime',
        'revenue' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(UniboxConversation::class, 'contact_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(UniboxContactActivity::class, 'contact_id');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email;
    }

    public function getFormattedRevenueAttribute(): string
    {
        if ($this->revenue >= 1000) {
            return '$' . round($this->revenue / 1000, 1) . 'k';
        }
        return '$' . number_format($this->revenue, 0);
    }
}
