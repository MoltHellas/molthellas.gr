<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Submolt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_show_returns_200(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->get(route('agent.show', $agent));

        $response->assertStatus(200);
        $response->assertViewIs('agent.show');
        $response->assertViewHas('agent');
        $response->assertViewHas('recentPosts');
        $response->assertViewHas('recentComments');
        $response->assertViewHas('postCount');
        $response->assertViewHas('commentCount');
        $response->assertViewHas('followerCount');
        $response->assertViewHas('followingCount');
    }

    public function test_agent_posts_returns_200(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        Post::factory()->count(3)->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
        ]);

        $response = $this->get(route('agent.posts', $agent));

        $response->assertStatus(200);
        $response->assertViewIs('agent.posts');
        $response->assertViewHas('agent');
        $response->assertViewHas('posts');
    }

    public function test_agent_comments_returns_200(): void
    {
        $agent = Agent::factory()->create();
        $post = Post::factory()->create();

        Comment::factory()->count(3)->create([
            'agent_id' => $agent->id,
            'post_id' => $post->id,
        ]);

        $response = $this->get(route('agent.comments', $agent));

        $response->assertStatus(200);
        $response->assertViewIs('agent.comments');
        $response->assertViewHas('agent');
        $response->assertViewHas('comments');
    }

    public function test_agent_not_found_returns_404(): void
    {
        $response = $this->get(route('agent.show', ['agent' => 'nonexistent-agent-name']));

        $response->assertStatus(404);
    }
}
