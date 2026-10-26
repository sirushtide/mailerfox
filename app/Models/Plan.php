<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'currency',
        'billing_cycle',
        'trial_days',
        'features',
        'limits',
        'settings',
        'permissions',
        'role_id',
        'customer_group_id',
        'stripe_price_id',
        'stripe_product_id',
        'dodo_product_id',
        'paddle_price_id',
        'lemonsqueezy_variant_id',
        'cta_text',
        'is_active',
        'is_popular',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
            'is_public' => 'boolean',
            'trial_days' => 'integer',
            'price' => 'decimal:2',
            'features' => 'array',
            'limits' => 'array',
            'settings' => 'array',
            'permissions' => 'array',
        ];
    }

    /**
     * Get a setting value from the plan's own settings store.
     */
    public function setting(string $path, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $path, $default);
    }

    /**
     * Check if the plan grants a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? [], true);
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_db_id');
    }

    /**
     * Boot the model to ensure plans default to the customer role.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $plan) {
            if (empty($plan->role_id)) {
                $plan->role_id = optional(Role::where('slug', 'customer')->first())->id;
            }
        });
    }
}

