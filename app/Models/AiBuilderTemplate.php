<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiBuilderTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'category',
        'name',
        'description',
        'tags',
        'thumbnail',
        'component_source',
        'fields',
        'defaults',
        'example_props',
        'is_active',
        'sort_order',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'fields' => 'array',
            'defaults' => 'array',
            'example_props' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Example props fall back to defaults when not explicitly set.
     */
    public function getExamplePropsAttribute(?string $value): ?array
    {
        if ($value !== null && $value !== '') {
            return json_decode($value, true);
        }
        return $this->defaults;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
