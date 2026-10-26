<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCustomModel extends Model
{
    protected $fillable = [
        'provider',
        'model_id',
        'name',
        'description',
        'context_length',
        'input_cost_per_1m_cents',
        'output_cost_per_1m_cents',
        'is_enabled',
    ];

    protected $casts = [
        'context_length' => 'integer',
        'input_cost_per_1m_cents' => 'integer',
        'output_cost_per_1m_cents' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function scopeForProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }
}
