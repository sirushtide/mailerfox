<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'permissions',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_default'  => 'boolean',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    // ── Permission helpers ─────────────────────────────────────────

    public function hasPermission(string $permission): bool
    {
        $perms = (array) ($this->permissions ?? []);

        return in_array($permission, $perms, true) || in_array('*', $perms, true);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if ($this->hasPermission($p)) {
                return true;
            }
        }

        return false;
    }

    // ── Static permission registry ─────────────────────────────────

    public static function allPermissionKeys(): array
    {
        return [
            // Lists
            'lists.view',
            'lists.create',
            'lists.edit',
            'lists.delete',

            // Campaigns
            'campaigns.view',
            'campaigns.create',
            'campaigns.edit',
            'campaigns.delete',
            'campaigns.send',

            // Automations
            'automations.view',
            'automations.create',
            'automations.edit',
            'automations.delete',

            // Templates
            'templates.view',
            'templates.create',
            'templates.edit',
            'templates.delete',

            // Delivery Servers
            'delivery_servers.view',
            'delivery_servers.create',
            'delivery_servers.edit',
            'delivery_servers.delete',

            // Bounce Servers
            'bounce_servers.view',
            'bounce_servers.create',
            'bounce_servers.edit',
            'bounce_servers.delete',

            // Domains
            'domains.view',
            'domains.create',
            'domains.edit',
            'domains.delete',

            // Warmups
            'warmups.view',
            'warmups.create',
            'warmups.edit',
            'warmups.delete',

            // Workspace management
            'members.manage',
            'roles.manage',
            'workspace.settings',
            'billing.view',
            'activity_log.view',

            // CRM
            'crm.dashboard.view',
            'crm.contacts.view',
            'crm.contacts.create',
            'crm.contacts.edit',
            'crm.contacts.delete',
            'crm.companies.view',
            'crm.companies.create',
            'crm.companies.edit',
            'crm.companies.delete',
            'crm.pipelines.view',
            'crm.pipelines.create',
            'crm.pipelines.edit',
            'crm.pipelines.delete',
            'crm.deals.view',
            'crm.deals.create',
            'crm.deals.edit',
            'crm.deals.delete',
            'crm.tasks.view',
            'crm.tasks.create',
            'crm.tasks.edit',
            'crm.tasks.delete',
        ];
    }

    public static function permissionGroups(): array
    {
        return [
            'Lists'            => ['lists.view', 'lists.create', 'lists.edit', 'lists.delete'],
            'Campaigns'        => ['campaigns.view', 'campaigns.create', 'campaigns.edit', 'campaigns.delete', 'campaigns.send'],
            'Automations'      => ['automations.view', 'automations.create', 'automations.edit', 'automations.delete'],
            'Templates'        => ['templates.view', 'templates.create', 'templates.edit', 'templates.delete'],
            'Delivery Servers' => ['delivery_servers.view', 'delivery_servers.create', 'delivery_servers.edit', 'delivery_servers.delete'],
            'Bounce Servers'   => ['bounce_servers.view', 'bounce_servers.create', 'bounce_servers.edit', 'bounce_servers.delete'],
            'Domains'          => ['domains.view', 'domains.create', 'domains.edit', 'domains.delete'],
            'Warmups'          => ['warmups.view', 'warmups.create', 'warmups.edit', 'warmups.delete'],
            'Workspace'        => ['members.manage', 'roles.manage', 'workspace.settings', 'billing.view', 'activity_log.view'],
            'CRM'              => ['crm.dashboard.view', 'crm.contacts.view', 'crm.contacts.create', 'crm.contacts.edit', 'crm.contacts.delete', 'crm.companies.view', 'crm.companies.create', 'crm.companies.edit', 'crm.companies.delete', 'crm.pipelines.view', 'crm.pipelines.create', 'crm.pipelines.edit', 'crm.pipelines.delete', 'crm.deals.view', 'crm.deals.create', 'crm.deals.edit', 'crm.deals.delete', 'crm.tasks.view', 'crm.tasks.create', 'crm.tasks.edit', 'crm.tasks.delete'],
        ];
    }
}
