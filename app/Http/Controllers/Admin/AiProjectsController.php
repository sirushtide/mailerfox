<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBuilderSession;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiProjectsController extends Controller
{
    public function dashboard(Request $request): View
    {
        $admin = auth('admin')->user();

        $sessions = AiBuilderSession::query()
            ->when($admin?->id, fn ($q) => $q->where('admin_user_id', $admin->id))
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => AiBuilderSession::query()
                ->when($admin?->id, fn ($q) => $q->where('admin_user_id', $admin->id))
                ->count(),
            'draft' => AiBuilderSession::query()
                ->when($admin?->id, fn ($q) => $q->where('admin_user_id', $admin->id))
                ->where('page_status', 'draft')
                ->count(),
            'published' => Page::query()
                ->where('builder_data->source', 'page_ai_builder')
                ->count(),
        ];

        return view('admin.ai-projects.dashboard', [
            'sessions' => $sessions,
            'stats' => $stats,
        ]);
    }
}
