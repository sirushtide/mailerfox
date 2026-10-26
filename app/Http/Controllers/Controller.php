<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function activeWorkspaceId(): ?int
    {
        $workspaceId = session('active_workspace_id');

        return $workspaceId ? (int) $workspaceId : null;
    }

    protected function workspaceResourceQuery(Builder $query, Customer $customer, ?string $table = null): Builder
    {
        $workspaceId = $this->activeWorkspaceId();
        $columnPrefix = $table ? $table . '.' : '';

        if ($workspaceId) {
            return $query->where($columnPrefix . 'workspace_id', $workspaceId);
        }

        return $query->where($columnPrefix . 'customer_id', $customer->id)
            ->whereNull($columnPrefix . 'workspace_id');
    }

    protected function workspaceResourceMatches(object $model, Customer $customer): bool
    {
        $workspaceId = $this->activeWorkspaceId();

        if ($workspaceId) {
            return (int) ($model->workspace_id ?? 0) === $workspaceId;
        }

        return (int) ($model->customer_id ?? 0) === (int) $customer->id
            && empty($model->workspace_id);
    }
}
