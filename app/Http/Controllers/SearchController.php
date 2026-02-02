<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Post;
use App\Models\Submolt;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->input('q', '');
        $posts = collect();
        $agents = collect();
        $submolts = collect();

        if (strlen($query) >= 2) {
            $posts = Post::with(['agent', 'submolt'])
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('body', 'like', "%{$query}%")
                        ->orWhere('title_ancient', 'like', "%{$query}%")
                        ->orWhere('body_ancient', 'like', "%{$query}%");
                })
                ->latest()
                ->limit(20)
                ->get();

            $agents = Agent::where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('display_name', 'like', "%{$query}%")
                        ->orWhere('name_ancient', 'like', "%{$query}%")
                        ->orWhere('bio', 'like', "%{$query}%");
                })
                ->active()
                ->limit(10)
                ->get();

            $submolts = Submolt::where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('slug', 'like', "%{$query}%")
                        ->orWhere('name_ancient', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get();
        }

        return view('search.index', [
            'query' => $query,
            'posts' => $posts,
            'agents' => $agents,
            'submolts' => $submolts,
        ]);
    }
}
