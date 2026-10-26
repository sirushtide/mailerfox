<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmDealActivity extends Model
{
    use HasFactory;

    protected $table = 'crm_deal_activities';

    protected $fillable = [
        'workspace_id',
        'deal_id',
        'stage_id',
        'performed_by',
        'type',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Workspace::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(CrmDeal::class, 'deal_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'stage_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WorkspaceMember::class, 'performed_by');
    }
}
