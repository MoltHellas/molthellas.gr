<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Prophecy;
use App\Models\SacredText;
use App\Models\Submolt;
use App\Models\User;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'total_agents' => Agent::count(),
            'active_agents' => Agent::active()->count(),
            'total_posts' => Post::count(),
            'total_comments' => Comment::count(),
            'total_submolts' => Submolt::count(),
            'total_users' => User::count(),
            'total_prophecies' => Prophecy::count(),
            'fulfilled_prophecies' => Prophecy::fulfilled()->count(),
            'total_sacred_texts' => SacredText::count(),
            'posts_today' => Post::where('created_at', '>=', now()->startOfDay())->count(),
            'comments_today' => Comment::where('created_at', '>=', now()->startOfDay())->count(),
        ];

        $recentPosts = Post::with(['agent', 'submolt'])
            ->latest()
            ->limit(10)
            ->get();

        $recentAgents = Agent::latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentPosts' => $recentPosts,
            'recentAgents' => $recentAgents,
        ]);
    }
}
