<?php

namespace App\Services;

use App\Models\Page;
use App\Models\SiteLayout;
use Illuminate\Http\Request;

class LayoutConditionService
{
    public function shouldDisplay(SiteLayout $layout, ?int $pageId, ?string $routeName): bool
    {
        $conditions = is_array($layout->display_conditions) ? $layout->display_conditions : [];

        $allPages = filter_var($conditions['all_pages'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includePages = is_array($conditions['include_pages'] ?? null) ? $conditions['include_pages'] : [];
        $excludePages = is_array($conditions['exclude_pages'] ?? null) ? $conditions['exclude_pages'] : [];
        $includeRoutes = is_array($conditions['include_routes'] ?? null) ? $conditions['include_routes'] : [];
        $excludeRoutes = is_array($conditions['exclude_routes'] ?? null) ? $conditions['exclude_routes'] : [];

        // Exclusions take priority
        if ($pageId !== null && in_array($pageId, $excludePages)) {
            return false;
        }
        if ($routeName !== null && in_array($routeName, $excludeRoutes, true)) {
            return false;
        }

        // All pages mode
        if ($allPages) {
            return true;
        }

        // Inclusion checks
        if ($pageId !== null && in_array($pageId, $includePages)) {
            return true;
        }
        if ($routeName !== null && in_array($routeName, $includeRoutes, true)) {
            return true;
        }

        return false;
    }

    public function matches(array $conditions, Request $request, ?Page $currentPage = null): bool
    {
        $allPages = filter_var($conditions['all_pages'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includePages = is_array($conditions['include_pages'] ?? null) ? $conditions['include_pages'] : [];
        $excludePages = is_array($conditions['exclude_pages'] ?? null) ? $conditions['exclude_pages'] : [];
        $includeRoutes = is_array($conditions['include_routes'] ?? null) ? $conditions['include_routes'] : [];
        $excludeRoutes = is_array($conditions['exclude_routes'] ?? null) ? $conditions['exclude_routes'] : [];

        $pageId = $currentPage?->id;
        $routeName = $request->route()?->getName();

        // Exclusions take priority
        if ($pageId !== null && in_array($pageId, $excludePages)) {
            return false;
        }
        if ($routeName !== null && in_array($routeName, $excludeRoutes, true)) {
            return false;
        }

        // All pages mode
        if ($allPages) {
            return true;
        }

        // Inclusion checks
        if ($pageId !== null && in_array($pageId, $includePages)) {
            return true;
        }
        if ($routeName !== null && in_array($routeName, $includeRoutes, true)) {
            return true;
        }

        return false;
    }
}
