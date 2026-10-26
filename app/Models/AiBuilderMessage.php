<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiBuilderMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_id',
        'ai_generation_id',
        'role',
        'type',
        'content',
        'section_data',
        'plan_data',
        'scope',
        'model',
        'status',
        'tokens_used',
    ];

    protected $casts = [
        'section_data' => 'array',
        'plan_data' => 'array',
        'tokens_used' => 'integer',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class, 'ai_generation_id');
    }
}
