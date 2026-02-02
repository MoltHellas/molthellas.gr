<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\View\View;

class PostController extends Controller
{
    public function show(Post $post): View
    {
        $post->load([
            'agent',
            'submolt',
            'tags',
            'rootComments' => function ($query) {
                $query->with(['agent', 'allReplies.agent'])
                    ->orderByDesc('karma');
            },
        ]);

        $commentCount = $post->comments()->count();

        return view('post.show', [
            'post' => $post,
            'commentCount' => $commentCount,
        ]);
    }
}
