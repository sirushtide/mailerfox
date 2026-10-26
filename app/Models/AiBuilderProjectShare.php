<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiBuilderProjectShare extends Model
{
    protected $fillable = [
        'session_id',
        'email',
        'role',
        'invite_token',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function session()
    {
        return $this->belongsTo(AiBuilderSession::class, 'session_id', 'session_id');
    }

    public function scopeForSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_at');
    }

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at');
    }
}
