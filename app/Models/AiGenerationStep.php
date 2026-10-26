<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGenerationStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_generation_id',
        'step_name',
        'provider',
        'model',
        'tokens_used',
        'cost_cents',
        'status',
        'error_message',
    ];

    protected $casts = [
        'tokens_used' => 'integer',
        'cost_cents' => 'decimal:3',
    ];

    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class, 'ai_generation_id');
    }
}
