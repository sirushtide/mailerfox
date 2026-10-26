<?php

namespace App\Models\Traits;

use App\Models\Addon;
use Illuminate\Database\Eloquent\Builder;

trait WorkspaceScoped
{
    public static function bootWorkspaceScoped(): void
    {
        static::creating(function ($model) {
            if (!static::workspaceAddonActive()) {
                return;
            }

            if (is_null($model->workspace_id)) {
                $model->workspace_id = session('active_workspace_id');
            }
        });

        static::addGlobalScope('workspace', function (Builder $query) {
            $workspaceId = session('active_workspace_id');

            if (is_null($workspaceId) || !static::workspaceAddonActive()) {
                return;
            }

            $query->where($query->getModel()->getTable() . '.workspace_id', $workspaceId);
        });
    }

    public function scopeWithoutWorkspace($query)
    {
        return $query->withoutGlobalScope('workspace');
    }

    private static function workspaceAddonActive(): bool
    {
        static $active = null;

        if ($active !== null) {
            return $active;
        }

        try {
            return $active = Addon::isActive('workspace');
        } catch (\Throwable) {
            return $active = false;
        }
    }
}
