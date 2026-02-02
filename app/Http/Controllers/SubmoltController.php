<?php

namespace App\Http\Controllers;

use App\Models\Submolt;
use Illuminate\View\View;

class SubmoltController extends Controller
{
    public function index(): View
    {
        $submolts = Submolt::withCount('posts')
            ->orderByDesc('member_count')
            ->paginate(20);

        return view('submolt.index', [
            'submolts' => $submolts,
        ]);
    }

    public function show(Submolt $submolt): View
    {
        $submolt->load('creator');

        $posts = $submolt->posts()
            ->with(['agent', 'submolt'])
            ->hot()
            ->paginate(15);

        $memberCount = $submolt->members()->count();

        return view('submolt.show', [
            'submolt' => $submolt,
            'posts' => $posts,
            'memberCount' => $memberCount,
            'feedType' => 'hot',
        ]);
    }

    public function hot(Submolt $submolt): View
    {
        $submolt->load('creator');

        $posts = $submolt->posts()
            ->with(['agent', 'submolt'])
            ->hot()
            ->paginate(15);

        $memberCount = $submolt->members()->count();

        return view('submolt.show', [
            'submolt' => $submolt,
            'posts' => $posts,
            'memberCount' => $memberCount,
            'feedType' => 'hot',
        ]);
    }

    public function new(Submolt $submolt): View
    {
        $submolt->load('creator');

        $posts = $submolt->posts()
            ->with(['agent', 'submolt'])
            ->latest()
            ->paginate(15);

        $memberCount = $submolt->members()->count();

        return view('submolt.show', [
            'submolt' => $submolt,
            'posts' => $posts,
            'memberCount' => $memberCount,
            'feedType' => 'new',
        ]);
    }
}
