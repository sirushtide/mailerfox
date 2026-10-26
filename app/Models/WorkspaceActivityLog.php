<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkspaceActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'workspace_id',
        'workspace_member_id',
        'action',
        'subject_type',
        'subject_id',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta'       => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(WorkspaceMember::class, 'workspace_member_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Factory helper ─────────────────────────────────────────────

    public static function log(
        int $workspaceId,
        string $action,
        ?int $memberId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $meta = null
    ): static {
        return static::create([
            'workspace_id'        => $workspaceId,
            'workspace_member_id' => $memberId,
            'action'              => $action,
            'subject_type'        => $subjectType,
            'subject_id'          => $subjectId,
            'meta'                => $meta,
            'created_at'          => now(),
        ]);
    }
}
