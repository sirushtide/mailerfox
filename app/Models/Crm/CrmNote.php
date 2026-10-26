<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmNote extends Model
{
    use HasFactory;

    protected $table = 'crm_notes';

    protected $fillable = [
        'workspace_id',
        'notable_type',
        'notable_id',
        'created_by',
        'content',
        'type',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Workspace::class);
    }

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WorkspaceMember::class, 'created_by');
    }
}
