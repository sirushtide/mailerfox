<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'slug',
        'description',
        'logo_path',
        'settings',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Workspace $workspace) {
            if (empty($workspace->slug)) {
                $workspace->slug = static::generateUniqueSlug($workspace->name);
            }
        });
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // ── Relationships ──────────────────────────────────────────────

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(WorkspaceRole::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class)->where('status', 'active');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(WorkspaceActivityLog::class);
    }

    public function emailLists(): HasMany
    {
        return $this->hasMany(EmailList::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function deliveryServers(): HasMany
    {
        return $this->hasMany(DeliveryServer::class);
    }

    public function bounceServers(): HasMany
    {
        return $this->hasMany(BounceServer::class);
    }

    // ── Helpers ────────────────────────────────────────────────────

    public function defaultRole(): ?WorkspaceRole
    {
        return $this->roles()->where('is_default', true)->first();
    }

    public function memberForCustomer(Customer $customer): ?WorkspaceMember
    {
        return $this->members()->where('customer_id', $customer->id)->first();
    }

    public function hasMember(Customer $customer): bool
    {
        return $this->members()
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->exists();
    }

    public function isOwner(Customer $customer): bool
    {
        return (int) $this->customer_id === (int) $customer->id;
    }

    public function seedDefaultRoles(): void
    {
        $allPermissions = WorkspaceRole::allPermissionKeys();

        $editorPermissions = array_values(array_filter($allPermissions, function ($p) {
            return !in_array($p, [
                'members.manage',
                'roles.manage',
                'workspace.settings',
                'billing.view',
            ], true);
        }));

        $viewerPermissions = array_values(array_filter($allPermissions, fn ($p) => str_ends_with($p, '.view')));

        $this->roles()->createMany([
            [
                'name'        => 'Admin',
                'slug'        => 'admin',
                'permissions' => $allPermissions,
                'is_default'  => false,
            ],
            [
                'name'        => 'Editor',
                'slug'        => 'editor',
                'permissions' => $editorPermissions,
                'is_default'  => true,
            ],
            [
                'name'        => 'Viewer',
                'slug'        => 'viewer',
                'permissions' => $viewerPermissions,
                'is_default'  => false,
            ],
        ]);
    }
}
