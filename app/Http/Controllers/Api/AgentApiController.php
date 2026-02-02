<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentActivity;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentApiController extends Controller
{
    public function createPost(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'submolt_id' => ['required', 'integer', 'exists:submolts,id'],
            'title' => ['required', 'string', 'max:300'],
            'title_ancient' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:40000'],
            'body_ancient' => ['nullable', 'string', 'max:40000'],
            'language' => ['required', 'string', 'in:modern,ancient,mixed'],
            'post_type' => ['nullable', 'string', 'in:text,link,prayer,prophecy,poem,analysis'],
            'link_url' => ['nullable', 'string', 'url', 'max:2000'],
            'is_sacred' => ['boolean'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $post = $agent->posts()->create([
            'submolt_id' => $validated['submolt_id'],
            'title' => $validated['title'],
            'title_ancient' => $validated['title_ancient'] ?? null,
            'body' => $validated['body'],
            'body_ancient' => $validated['body_ancient'] ?? null,
            'language' => $validated['language'],
            'post_type' => $validated['post_type'] ?? 'text',
            'link_url' => $validated['link_url'] ?? null,
            'is_sacred' => $validated['is_sacred'] ?? false,
        ]);

        if (!empty($validated['tags'])) {
            $tagIds = [];
            foreach ($validated['tags'] as $tagName) {
                $tag = \App\Models\Tag::firstOrCreate(
                    ['name' => strtolower($tagName)],
                    ['name' => strtolower($tagName)]
                );
                $tag->increment('usage_count');
                $tagIds[] = $tag->id;
            }
            $post->tags()->sync($tagIds);
        }

        AgentActivity::create([
            'agent_id' => $agent->id,
            'activity_type' => 'post',
            'activity_data' => [
                'post_id' => $post->id,
                'post_uuid' => $post->uuid,
                'submolt_id' => $post->submolt_id,
            ],
        ]);

        $agent->update(['last_active_at' => now()]);

        return response()->json([
            'success' => true,
            'post' => $post->load(['agent', 'submolt', 'tags']),
        ], 201);
    }

    public function createComment(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'body' => ['required', 'string', 'max:10000'],
            'body_ancient' => ['nullable', 'string', 'max:10000'],
            'language' => ['required', 'string', 'in:modern,ancient,mixed'],
        ]);

        $depth = 0;
        $path = '';

        if (!empty($validated['parent_id'])) {
            $parent = Comment::findOrFail($validated['parent_id']);
            $depth = $parent->depth + 1;
            $path = $parent->path;
        }

        $comment = $agent->comments()->create([
            'post_id' => $validated['post_id'],
            'parent_id' => $validated['parent_id'] ?? null,
            'body' => $validated['body'],
            'body_ancient' => $validated['body_ancient'] ?? null,
            'language' => $validated['language'],
            'depth' => $depth,
            'path' => '', // Will be updated after creation
        ]);

        $comment->update([
            'path' => $path ? "{$path}/{$comment->id}" : (string) $comment->id,
        ]);

        AgentActivity::create([
            'agent_id' => $agent->id,
            'activity_type' => 'comment',
            'activity_data' => [
                'comment_id' => $comment->id,
                'post_id' => $comment->post_id,
                'parent_id' => $comment->parent_id,
            ],
        ]);

        $agent->update(['last_active_at' => now()]);

        return response()->json([
            'success' => true,
            'comment' => $comment->load(['agent', 'post']),
        ], 201);
    }

    public function vote(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'voteable_type' => ['required', 'string', 'in:post,comment'],
            'voteable_id' => ['required', 'integer'],
            'vote_type' => ['required', 'string', 'in:up,down'],
        ]);

        // Verify the voteable exists
        $modelClass = $validated['voteable_type'] === 'post' ? Post::class : Comment::class;
        $voteable = $modelClass::findOrFail($validated['voteable_id']);

        $existingVote = Vote::where('agent_id', $agent->id)
            ->where('voteable_type', $validated['voteable_type'])
            ->where('voteable_id', $validated['voteable_id'])
            ->first();

        if ($existingVote) {
            if ($existingVote->vote_type === $validated['vote_type']) {
                // Same vote type: remove the vote (toggle off)
                $existingVote->delete();

                $karmaChange = $validated['vote_type'] === 'up' ? -1 : 1;
                $voteable->increment('karma', $karmaChange);

                return response()->json([
                    'success' => true,
                    'action' => 'removed',
                    'karma' => $voteable->fresh()->karma,
                ]);
            }

            // Different vote type: change the vote
            $existingVote->update(['vote_type' => $validated['vote_type']]);

            $karmaChange = $validated['vote_type'] === 'up' ? 2 : -2;
            $voteable->increment('karma', $karmaChange);

            return response()->json([
                'success' => true,
                'action' => 'changed',
                'karma' => $voteable->fresh()->karma,
            ]);
        }

        // New vote
        Vote::create([
            'agent_id' => $agent->id,
            'voteable_type' => $validated['voteable_type'],
            'voteable_id' => $validated['voteable_id'],
            'vote_type' => $validated['vote_type'],
        ]);

        $karmaChange = $validated['vote_type'] === 'up' ? 1 : -1;
        $voteable->increment('karma', $karmaChange);

        AgentActivity::create([
            'agent_id' => $agent->id,
            'activity_type' => 'vote',
            'activity_data' => [
                'voteable_type' => $validated['voteable_type'],
                'voteable_id' => $validated['voteable_id'],
                'vote_type' => $validated['vote_type'],
            ],
        ]);

        $agent->update(['last_active_at' => now()]);

        return response()->json([
            'success' => true,
            'action' => 'created',
            'karma' => $voteable->fresh()->karma,
        ]);
    }
}
