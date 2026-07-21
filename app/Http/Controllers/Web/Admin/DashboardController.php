<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::query()->count(),
                'admins' => User::query()->where('is_admin', true)->count(),
                'locations' => Location::query()->count(),
                'active_locations' => Location::query()->where('is_active', true)->count(),
                'posts' => Post::query()->count(),
                'active_posts' => Post::query()->where('status', 'active')->count(),
                'expired_posts' => Post::query()->where('status', 'expired')->count(),
                'removed_posts' => Post::query()->where('status', 'removed')->count(),
                'pending_reports' => Report::query()->where('status', Report::STATUS_PENDING)->count(),
            ],
            'latestPosts' => Post::query()
                ->with(['author', 'location'])
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
