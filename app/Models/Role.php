<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'permissions',
        'is_system',
        'is_default',
        'guard_name',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_system' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Boot the model to ensure slug is generated.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (self $role) {
            if (empty($role->slug)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    /**
     * Get the users that belong to the role.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Check if the role has a specific permission.
     *
     * Supports wildcard patterns such as "admin.*" or "*".
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = (array) ($this->permissions ?? []);

        foreach ($permissions as $perm) {
            if (!is_string($perm) || $perm === '') {
                continue;
            }

            if ($perm === '*') {
                return true;
            }

            if (Str::is($perm, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the role has any of the provided permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission((string) $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a permission to the role.
     */
    public function addPermission(string $permission): void
    {
        $permissions = (array) ($this->permissions ?? []);
        if (!in_array($permission, $permissions, true)) {
            $permissions[] = $permission;
            $this->permissions = $permissions;
            $this->save();
        }
    }

    /**
     * Remove a permission from the role.
     */
    public function removePermission(string $permission): void
    {
        $permissions = (array) ($this->permissions ?? []);
        $this->permissions = array_values(array_filter($permissions, fn ($p) => $p !== $permission));
        $this->save();
    }

    /**
     * Scope for admin-side roles.
     */
    public function scopeAdmin($query)
    {
        return $query->where('guard_name', 'admin');
    }

    /**
     * Scope for system roles.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
