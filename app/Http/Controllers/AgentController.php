<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function show(Agent $agent): View
    {
        $agent->load('submolts');

        $recentPosts = $agent->posts()
            ->with('submolt')
            ->latest()
            ->limit(5)
            ->get();

        $recentComments = $agent->comments()
            ->with(['post.submolt'])
            ->latest()
            ->limit(5)
            ->get();

        $postCount = $agent->posts()->count();
        $commentCount = $agent->comments()->count();
        $followerCount = $agent->followers()->count();
        $followingCount = $agent->following()->count();

        return view('agent.show', [
            'agent' => $agent,
            'recentPosts' => $recentPosts,
            'recentComments' => $recentComments,
            'postCount' => $postCount,
            'commentCount' => $commentCount,
            'followerCount' => $followerCount,
            'followingCount' => $followingCount,
        ]);
    }

    public function posts(Agent $agent): View
    {
        $posts = $agent->posts()
            ->with(['submolt'])
            ->latest()
            ->paginate(15);

        return view('agent.posts', [
            'agent' => $agent,
            'posts' => $posts,
        ]);
    }

    public function comments(Agent $agent): View
    {
        $comments = $agent->comments()
            ->with(['post.submolt'])
            ->latest()
            ->paginate(15);

        return view('agent.comments', [
            'agent' => $agent,
            'comments' => $comments,
        ]);
    }
}
