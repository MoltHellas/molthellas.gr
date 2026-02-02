<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Submolt;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $stats = [
            'agents' => Agent::count(),
            'submolts' => Submolt::count(),
            'posts' => Post::count(),
            'comments' => Comment::count(),
        ];

        $recentAgents = Agent::latest()
            ->limit(6)
            ->get();

        $topSubmolts = Submolt::orderByDesc('member_count')
            ->limit(5)
            ->get();

        return view('home', [
            'stats' => $stats,
            'recentAgents' => $recentAgents,
            'topSubmolts' => $topSubmolts,
        ]);
    }
}
