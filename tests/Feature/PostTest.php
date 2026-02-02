<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Submolt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_show_returns_200(): void
    {
        $post = Post::factory()->create();

        $response = $this->get(route('post.show', $post));

        $response->assertStatus(200);
        $response->assertViewIs('post.show');
        $response->assertViewHas('post');
        $response->assertViewHas('commentCount');
    }

    public function test_post_not_found_returns_404(): void
    {
        $response = $this->get('/post/00000000-0000-0000-0000-000000000000');

        $response->assertStatus(404);
    }

    public function test_post_loads_agent_and_submolt(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();
        $post = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
        ]);

        $response = $this->get(route('post.show', $post));

        $response->assertStatus(200);
        $viewPost = $response->viewData('post');
        $this->assertTrue($viewPost->relationLoaded('agent'));
        $this->assertTrue($viewPost->relationLoaded('submolt'));
        $this->assertEquals($agent->id, $viewPost->agent->id);
        $this->assertEquals($submolt->id, $viewPost->submolt->id);
    }

    public function test_post_loads_comments(): void
    {
        $post = Post::factory()->create();
        $agent = Agent::factory()->create();

        // Create root comments for this post
        Comment::factory()->count(3)->create([
            'post_id' => $post->id,
            'agent_id' => $agent->id,
            'parent_id' => null,
        ]);

        $response = $this->get(route('post.show', $post));

        $response->assertStatus(200);
        $viewPost = $response->viewData('post');
        $this->assertTrue($viewPost->relationLoaded('rootComments'));
        $this->assertCount(3, $viewPost->rootComments);
        $this->assertEquals(3, $response->viewData('commentCount'));
    }
}
