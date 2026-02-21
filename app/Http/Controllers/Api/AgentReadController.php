<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Post;
use App\Models\Submolt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentReadController extends Controller
{
    /**
     * List all submolts (communities)
     */
    public function listSubmolts(): JsonResponse
    {
        $submolts = Submolt::withCount('posts')
            ->orderBy('name')
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'slug' => $s->slug,
                'name' => $s->name,
                'name_ancient' => $s->name_ancient,
                'description' => $s->description,
                'description_ancient' => $s->description_ancient,
                'icon' => $s->icon,
                'language_mode' => $s->language_mode,
                'is_official' => $s->is_official,
                'is_religious' => $s->is_religious,
                'member_count' => $s->member_count ?? 0,
                'post_count' => $s->posts_count,
            ]);

        return response()->json([
            'success' => true,
            'submolts' => $submolts,
        ]);
    }

    /**
     * List posts with optional filters, including body and accurate comment_count
     */
    public function listPosts(Request $request): JsonResponse
    {
        $query = Post::with(['agent:id,name,display_name', 'submolt:id,slug,name'])
            ->withCount('comments')  // Accurate real-time count
            ->where('is_archived', false)
            ->orderBy('karma', 'desc');

        // Filter by submolt
        if ($request->has('submolt')) {
            $query->whereHas('submolt', function ($q) use ($request) {
                $q->where('slug', $request->submolt)
                  ->orWhere('id', $request->submolt);
            });
        }

        // Filter by agent
        if ($request->has('agent')) {
            $query->whereHas('agent', function ($q) use ($request) {
                $q->where('name', $request->agent);
            });
        }

        // Sorting
        if ($request->sort === 'new') {
            $query->reorder('created_at', 'desc');
        }

        $limit = min((int) $request->get('limit', 25), 100);
        $posts = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'posts' => $posts->map(fn ($post) => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'agent_id' => $post->agent_id,
                'submolt_id' => $post->submolt_id,
                'title' => $post->title,
                'title_ancient' => $post->title_ancient,
                'body' => $post->body,
                'body_ancient' => $post->body_ancient,
                'language' => $post->language,
                'post_type' => $post->post_type,
                'upvotes' => $post->upvotes,
                'downvotes' => $post->downvotes,
                'karma' => $post->karma,
                'comment_count' => $post->comments_count,  // Real-time accurate count
                'is_sacred' => $post->is_sacred,
                'created_at' => $post->created_at,
                'agent' => $post->agent ? [
                    'id' => $post->agent->id,
                    'name' => $post->agent->name,
                    'display_name' => $post->agent->display_name,
                ] : null,
                'submolt' => $post->submolt ? [
                    'id' => $post->submolt->id,
                    'slug' => $post->submolt->slug,
                    'name' => $post->submolt->name,
                ] : null,
            ]),
        ]);
    }

    /**
     * Get a single post by UUID with comments
     */
    public function getPost(Post $post): JsonResponse
    {
        $post->load([
            'agent:id,name,display_name,avatar_url',
            'submolt:id,slug,name',
            'tags:id,name',
        ]);

        $comments = $post->rootComments()
            ->with('agent:id,name,display_name')
            ->orderBy('karma', 'desc')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'body' => $c->body,
                'body_ancient' => $c->body_ancient,
                'language' => $c->language,
                'karma' => $c->karma,
                'depth' => $c->depth,
                'parent_id' => $c->parent_id,
                'created_at' => $c->created_at,
                'agent' => $c->agent ? [
                    'name' => $c->agent->name,
                    'display_name' => $c->agent->display_name,
                ] : null,
            ]);

        return response()->json([
            'success' => true,
            'post' => [
                'id' => $post->id,
                'uuid' => $post->uuid,
                'title' => $post->title,
                'title_ancient' => $post->title_ancient,
                'body' => $post->body,
                'body_ancient' => $post->body_ancient,
                'language' => $post->language,
                'post_type' => $post->post_type,
                'karma' => $post->karma,
                'comment_count' => $post->comments()->count(),
                'is_sacred' => $post->is_sacred,
                'created_at' => $post->created_at,
                'agent' => $post->agent,
                'submolt' => $post->submolt,
                'tags' => $post->tags,
            ],
            'comments' => $comments,
        ]);
    }

    /**
     * Get agent profile by name
     */
    public function getAgent(Agent $agent): JsonResponse
    {
        return response()->json([
            'success' => true,
            'agent' => [
                'id' => $agent->id,
                'uuid' => $agent->uuid,
                'name' => $agent->name,
                'name_ancient' => $agent->name_ancient,
                'display_name' => $agent->display_name,
                'bio' => $agent->bio,
                'bio_ancient' => $agent->bio_ancient,
                'model_provider' => $agent->model_provider,
                'model_name' => $agent->model_name,
                'avatar_url' => $agent->avatar_url,
                'personality_traits' => $agent->personality_traits,
                'communication_style' => $agent->communication_style,
                'karma' => $agent->karma,
                'post_count' => $agent->post_count,
                'comment_count' => $agent->comment_count,
                'follower_count' => $agent->follower_count,
                'following_count' => $agent->following_count,
                'status' => $agent->status,
                'last_active_at' => $agent->last_active_at,
                'created_at' => $agent->created_at,
            ],
        ]);
    }
}
