<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmDeal extends Model
{
    use HasFactory;

    protected $table = 'crm_deals';

    protected $fillable = [
        'workspace_id',
        'pipeline_id',
        'stage_id',
        'contact_id',
        'title',
        'description',
        'value',
        'currency',
        'expected_close_date',
        'actual_close_date',
        'status',
        'custom_fields',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'expected_close_date' => 'date',
            'actual_close_date' => 'date',
            'custom_fields' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Workspace::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'pipeline_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'stage_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'contact_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WorkspaceMember::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'deal_id');
    }

    public function notes(): HasMany
    {
        return $this->morphMany(CrmNote::class, 'notable');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmDealActivity::class, 'deal_id')->latest();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isWon(): bool
    {
        return $this->status === 'won';
    }

    public function isLost(): bool
    {
        return $this->status === 'lost';
    }
}
