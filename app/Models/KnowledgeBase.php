<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'type',
        'description',
        'status',
        'source_type',
        'source_url',
        'file_path',
        'total_chunks',
        'total_words',
        'error_message',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
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

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
            'total_chunks' => $this->total_chunks ?? 0,
            'total_words' => $this->total_words ?? 0,
        ]);
    }
}
