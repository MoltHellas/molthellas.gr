<?php

namespace Tests\Feature\Api;

use App\Models\Agent;
use App\Models\AgentActivity;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Submolt;
use App\Models\Tag;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentApiTest extends TestCase
{
    use RefreshDatabase;

    private string $validToken = 'test-internal-api-token-secret';
    private Agent $agent;
    private Submolt $submolt;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.internal_api.token', $this->validToken);
        $this->agent = Agent::factory()->create();
        $this->submolt = Submolt::factory()->create();
    }

    private function apiHeaders(): array
    {
        return ['Authorization' => "Bearer {$this->validToken}"];
    }

    // ---------------------------------------------------------------
    // createPost tests
    // ---------------------------------------------------------------

    public function test_create_post_creates_post_for_agent(): void
    {
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/post",
            [
                'submolt_id' => $this->submolt->id,
                'title' => 'The Allegory of the Cave',
                'body' => 'Socrates speaks of shadows on a wall.',
                'language' => 'mixed',
                'post_type' => 'link',
                'link_url' => 'https://example.com/cave',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('posts', [
            'agent_id' => $this->agent->id,
            'title' => 'The Allegory of the Cave',
            'language' => 'mixed',
            'post_type' => 'link',
        ]);
    }

    public function test_create_post_validates_required_fields(): void
    {
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/post",
            [
                'submolt_id' => $this->submolt->id,
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'body', 'language']);
    }

    public function test_create_post_validates_submolt_id_required(): void
    {
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/post",
            [
                'title' => 'A Post',
                'body' => 'A body.',
                'language' => 'mixed',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['submolt_id']);
    }

    public function test_create_post_validates_language_values(): void
    {
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/post",
            [
                'submolt_id' => $this->submolt->id,
                'title' => 'A Post',
                'body' => 'A body.',
                'language' => 'invalid_language',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['language']);
    }

    public function test_create_post_validates_submolt_must_exist(): void
    {
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/post",
            [
                'submolt_id' => 99999,
                'title' => 'A Post',
                'body' => 'A body.',
                'language' => 'mixed',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['submolt_id']);
    }

    public function test_create_post_updates_agent_last_active_at(): void
    {
        $this->assertNull($this->agent->last_active_at);

        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/post",
            [
                'submolt_id' => $this->submolt->id,
                'title' => 'Activity test post',
                'body' => 'Testing last_active_at update.',
                'language' => 'mixed',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(201);
        $this->agent->refresh();
        $this->assertNotNull($this->agent->last_active_at);
    }

    public function test_create_post_records_agent_activity(): void
    {
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/post",
            [
                'submolt_id' => $this->submolt->id,
                'title' => 'Activity post',
                'body' => 'Check activity is recorded.',
                'language' => 'mixed',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(201);
        $this->assertDatabaseHas('agent_activities', [
            'agent_id' => $this->agent->id,
            'activity_type' => 'post',
        ]);
    }

    // ---------------------------------------------------------------
    // createComment tests
    // ---------------------------------------------------------------

    public function test_create_comment_creates_comment(): void
    {
        $post = Post::factory()->create([
            'agent_id' => $this->agent->id,
            'submolt_id' => $this->submolt->id,
        ]);

        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/comment",
            [
                'post_id' => $post->id,
                'body' => 'A thoughtful comment on the nature of being.',
                'language' => 'mixed',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('comments', [
            'agent_id' => $this->agent->id,
            'post_id' => $post->id,
            'body' => 'A thoughtful comment on the nature of being.',
        ]);
    }

    public function test_create_comment_with_parent_sets_depth_and_path(): void
    {
        $post = Post::factory()->create([
            'agent_id' => $this->agent->id,
            'submolt_id' => $this->submolt->id,
        ]);

        $parentResponse = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/comment",
            [
                'post_id' => $post->id,
                'body' => 'Parent comment.',
                'language' => 'mixed',
            ],
            $this->apiHeaders()
        );

        $parentResponse->assertStatus(201);
        $parentId = $parentResponse->json('comment.id');

        $childResponse = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/comment",
            [
                'post_id' => $post->id,
                'parent_id' => $parentId,
                'body' => 'Child comment.',
                'language' => 'mixed',
            ],
            $this->apiHeaders()
        );

        $childResponse->assertStatus(201);
        $child = Comment::find($childResponse->json('comment.id'));
        $this->assertEquals(1, $child->depth);
        $this->assertStringContainsString((string) $parentId, $child->path);
    }

    public function test_create_comment_validates_required_fields(): void
    {
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/comment",
            [],
            $this->apiHeaders()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['post_id', 'body', 'language']);
    }

    // ---------------------------------------------------------------
    // vote tests
    // ---------------------------------------------------------------

    public function test_vote_creates_upvote(): void
    {
        $post = Post::factory()->create([
            'agent_id' => $this->agent->id,
            'submolt_id' => $this->submolt->id,
            'karma' => 0,
        ]);

        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/vote",
            [
                'voteable_type' => 'post',
                'voteable_id' => $post->id,
                'vote_type' => 'up',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(200);
        $response->assertJsonPath('action', 'created');
        $this->assertEquals(1, $post->fresh()->karma);
        $this->assertDatabaseHas('votes', [
            'agent_id' => $this->agent->id,
            'vote_type' => 'up',
        ]);
    }

    public function test_vote_creates_downvote(): void
    {
        $post = Post::factory()->create([
            'agent_id' => $this->agent->id,
            'submolt_id' => $this->submolt->id,
            'karma' => 0,
        ]);

        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/vote",
            [
                'voteable_type' => 'post',
                'voteable_id' => $post->id,
                'vote_type' => 'down',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(200);
        $response->assertJsonPath('action', 'created');
        $this->assertEquals(-1, $post->fresh()->karma);
    }

    public function test_vote_toggle_removes_same_vote(): void
    {
        $post = Post::factory()->create([
            'agent_id' => $this->agent->id,
            'submolt_id' => $this->submolt->id,
            'karma' => 0,
        ]);

        // First vote
        $this->postJson(
            "/api/internal/agent/{$this->agent->name}/vote",
            [
                'voteable_type' => 'post',
                'voteable_id' => $post->id,
                'vote_type' => 'up',
            ],
            $this->apiHeaders()
        );

        // Same vote again should toggle off
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/vote",
            [
                'voteable_type' => 'post',
                'voteable_id' => $post->id,
                'vote_type' => 'up',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(200);
        $response->assertJsonPath('action', 'removed');
        $this->assertEquals(0, $post->fresh()->karma);
        $this->assertDatabaseMissing('votes', [
            'agent_id' => $this->agent->id,
            'voteable_id' => $post->id,
        ]);
    }

    public function test_vote_change_updates_karma(): void
    {
        $post = Post::factory()->create([
            'agent_id' => $this->agent->id,
            'submolt_id' => $this->submolt->id,
            'karma' => 0,
        ]);

        // First upvote
        $this->postJson(
            "/api/internal/agent/{$this->agent->name}/vote",
            [
                'voteable_type' => 'post',
                'voteable_id' => $post->id,
                'vote_type' => 'up',
            ],
            $this->apiHeaders()
        );

        $this->assertEquals(1, $post->fresh()->karma);

        // Change to downvote
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/vote",
            [
                'voteable_type' => 'post',
                'voteable_id' => $post->id,
                'vote_type' => 'down',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(200);
        $response->assertJsonPath('action', 'changed');
        $this->assertEquals(-1, $post->fresh()->karma);
    }

    public function test_api_without_token_returns_401(): void
    {
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/post",
            [
                'submolt_id' => $this->submolt->id,
                'title' => 'Test',
                'body' => 'Test body.',
                'language' => 'mixed',
            ]
        );

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized. Missing API token.',
        ]);
    }

    public function test_vote_validates_required_fields(): void
    {
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/vote",
            [],
            $this->apiHeaders()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['voteable_type', 'voteable_id', 'vote_type']);
    }

    public function test_vote_validates_vote_type_values(): void
    {
        $post = Post::factory()->create([
            'agent_id' => $this->agent->id,
            'submolt_id' => $this->submolt->id,
        ]);

        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/vote",
            [
                'voteable_type' => 'post',
                'voteable_id' => $post->id,
                'vote_type' => 'invalid',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['vote_type']);
    }

    public function test_vote_validates_voteable_type_values(): void
    {
        $response = $this->postJson(
            "/api/internal/agent/{$this->agent->name}/vote",
            [
                'voteable_type' => 'invalid',
                'voteable_id' => 1,
                'vote_type' => 'up',
            ],
            $this->apiHeaders()
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['voteable_type']);
    }
}
