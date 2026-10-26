<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class AiAgent extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'description',
        'instructions',
        'channels',
        'tools',
        'autonomy',
        'widget_enabled',
        'provider',
        'model',
        'avatar_color',
        'image',
        'status',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default'     => 'boolean',
            'channels'       => 'array',
            'tools'          => 'array',
            'widget_enabled' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function knowledgeBases(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeBase::class, 'ai_agent_knowledge_base');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(RagConversation::class, 'agent_id');
    }

    public function scopeForCustomer($query, ?int $customerId)
    {
        if ($customerId === null) {
            return $query->whereNull('customer_id');
        }

        return $query->where(function ($q) use ($customerId) {
            $q->where('customer_id', $customerId)->orWhereNull('customer_id');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function imageUrl(): ?string
    {
        $image = $this->image;
        if (!is_string($image) || trim($image) === '') {
            return null;
        }

        $disk = (string) config('filesystems.branding_disk', 'public');
        return Storage::disk($disk)->url($image);
    }
}
