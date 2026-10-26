<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'customer_id',
        'email',
        'name',
        'workspace_role_id',
        'status',
        'invited_by',
        'invited_at',
        'accepted_at',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'invited_at'     => 'datetime',
            'accepted_at'    => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(WorkspaceRole::class, 'workspace_role_id');
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(self::class, 'invited_by');
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isOwner(): bool
    {
        return $this->workspace && (int) $this->workspace->customer_id === (int) $this->customer_id;
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->role && $this->role->hasPermission($permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->role && $this->role->hasAnyPermission($permissions);
    }

    public function displayName(): string
    {
        if ($this->customer) {
            return $this->customer->full_name;
        }

        return $this->name ?? $this->email;
    }
}
