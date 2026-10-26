<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, CanResetPasswordContract
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, MustVerifyEmailTrait, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'timezone',
        'language',
        'status',
        'avatar_path',
        'bio',
        'website_url',
        'twitter_url',
        'facebook_url',
        'linkedin_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'admin_permissions' => 'array',
            'admin_permissions_override' => 'boolean',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the user's avatar URL.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        $path = trim((string) ($this->avatar_path ?? ''));

        if ($path === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $path = ltrim($path, '/');

        try {
            if (!Storage::disk('public')->exists($path)) {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        }

        $root = null;

        try {
            $root = request()->root();
        } catch (\Throwable $e) {
            $root = config('app.url');
        }

        $root = rtrim((string) $root, '/');

        if ($root === '') {
            return null;
        }

        $prefix = '';

        try {
            $prefix = Setting::get('storage_url_prefix', '');
        } catch (\Throwable $e) {
            $prefix = '';
        }

        $prefix = trim((string) $prefix, '/');

        return $root . ($prefix !== '' ? '/' . $prefix : '') . '/storage/' . $path;
    }

    /**
     * Check if user is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the user groups that belong to the user.
     */
    public function userGroups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'user_user_group')
            ->withTimestamps();
    }

    /**
     * Get the roles that belong to the user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Check if user belongs to a specific group.
     */
    public function hasUserGroup(string $groupName): bool
    {
        return $this->userGroups()->where('name', $groupName)->exists();
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('slug', $roleName)->exists()
            || $this->roles()->whereRaw('LOWER(name) = ?', [strtolower($roleName)])->exists();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->hasRolePermission($permission)) {
            return true;
        }

        return $this->userGroups()
            ->whereJsonContains('permissions', $permission)
            ->exists();
    }

    public function isSuperAdmin(): bool
    {
        if ($this->hasRole('superadmin')) {
            return true;
        }

        return $this->userGroups()
            ->whereRaw('LOWER(name) = ?', ['superadmin'])
            ->orWhereRaw('LOWER(name) = ?', ['super admin'])
            ->exists();
    }

    public function hasAdminAbility(string $ability): bool
    {
        if (($this->admin_permissions_override ?? false) === true) {
            $permissions = (array) ($this->admin_permissions ?? []);
            foreach ($permissions as $perm) {
                if (!is_string($perm) || $perm === '') {
                    continue;
                }

                if (Str::is($perm, $ability)) {
                    return true;
                }
            }

            return false;
        }

        return $this->roleAllows($ability);
    }

    /**
     * Check whether any assigned role grants the ability.
     */
    private function roleAllows(string $ability): bool
    {
        $roles = $this->relationLoaded('roles')
            ? $this->roles
            : $this->roles()->get();

        foreach ($roles as $role) {
            if ($role->hasPermission($ability)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check whether the user has a permission through their assigned roles.
     */
    private function hasRolePermission(string $permission): bool
    {
        return $this->roleAllows($permission);
    }
}

