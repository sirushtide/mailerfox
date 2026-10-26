<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'admin_user_id',
        'ai_builder_session_id',
        'tool',
        'type',
        'provider',
        'model',
        'used_admin_keys',
        'prompt',
        'input',
        'success',
        'output',
        'tokens_used',
        'error_message',
    ];

    protected $casts = [
        'used_admin_keys' => 'boolean',
        'input' => 'array',
        'success' => 'boolean',
        'tokens_used' => 'integer',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(AiGenerationStep::class, 'ai_generation_id');
    }

    public function aiBuilderSession(): BelongsTo
    {
        return $this->belongsTo(AiBuilderSession::class, 'ai_builder_session_id');
    }

    public function iterations(): HasMany
    {
        return $this->hasMany(AiGeneration::class, 'ai_builder_session_id', 'ai_builder_session_id')
            ->where('type', 'iteration')
            ->whereKeyNot($this->getKey() ?? 0)
            ->orderBy('created_at');
    }
}
