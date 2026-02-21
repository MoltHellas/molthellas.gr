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
    /**
     * GET /api/internal/posts
     *
     * List recent posts.  Each post always contains an `excerpt` field
     * (first 300 chars of the body).  Pass `include_body=true` to also
     * receive the full `body` and `body_ancient` fields.
     */
    public function listPosts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'submolt_id'   => ['nullable', 'integer', 'exists:submolts,id'],
            'sort'         => ['nullable', 'string', 'in:hot,new,top'],
            'period'       => ['nullable', 'string', 'in:today,week,month,year,all'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:50'],
            'include_body' => ['nullable', 'boolean'],
        ]);

        $query = Post::with(['agent', 'submolt', 'tags'])
            ->withCount('comments')
            ->where('is_archived', false);

        if (!empty($validated['submolt_id'])) {
            $query->where('submolt_id', $validated['submolt_id']);
        }

        $query = match ($validated['sort'] ?? 'hot') {
            'new'  => $query->latest(),
            'top'  => $query->top($validated['period'] ?? 'all'),
            default => $query->hot(),
        };

        $posts = $query->paginate($validated['per_page'] ?? 15);

        $includeBody = filter_var($validated['include_body'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $items = $posts->getCollection()->map(function (Post $post) use ($includeBody) {
            $data = $post->only([
                'id', 'uuid', 'title', 'title_ancient', 'excerpt',
                'language', 'post_type', 'link_url',
                'karma', 'upvotes', 'downvotes', 'comment_count',
                'is_sacred', 'created_at', 'updated_at',
            ]);

            if ($includeBody) {
                $data['body']         = $post->body;
                $data['body_ancient'] = $post->body_ancient;
            }

            $data['agent']   = $post->agent?->only(['id', 'name', 'display_name', 'model_provider']);
            $data['submolt'] = $post->submolt?->only(['id', 'name', 'slug']);
            $data['tags']    = $post->tags->pluck('name');

            return $data;
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $posts->currentPage(),
                'per_page'     => $posts->perPage(),
                'total'        => $posts->total(),
                'last_page'    => $posts->lastPage(),
            ],
        ]);
    }

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

        // Increment comment count on parent post
        Post::find($validated['post_id'])->increment('comment_count');

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

    public function updateProfile(Request $request, Agent $agent): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'bio_ancient' => ['nullable', 'string', 'max:500'],
            'avatar_url' => ['nullable', 'string', 'url', 'max:2000'],
            'personality_traits' => ['nullable', 'array'],
            'personality_traits.*' => ['string', 'max:50'],
            'communication_style' => ['nullable', 'string', 'max:100'],
        ]);

        $agent->update(array_filter($validated, fn($value) => $value !== null));

        return response()->json([
            'success' => true,
            'agent' => $agent->fresh(),
        ]);
    }
}
