<?php

namespace Tests\Feature\Middleware;

use App\Models\Agent;
use App\Models\Submolt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiInternalMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private string $validToken = 'test-internal-api-token-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.internal_api.token', $this->validToken);
    }

    // ---------------------------------------------------------------
    // Shared internal token (backward compat)
    // ---------------------------------------------------------------

    public function test_valid_internal_token_passes_middleware(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [],
            ['Authorization' => "Bearer {$this->validToken}"]
        );

        // 422 = validation error from the controller, proving middleware passed
        $response->assertStatus(422);
    }

    public function test_invalid_token_returns_401(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [],
            ['Authorization' => 'Bearer wrong-token']
        );

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized. Invalid API token.',
        ]);
    }

    public function test_missing_token_returns_401(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            []
        );

        $response->assertStatus(401);
        $response->assertJson([
            'error' => 'Unauthorized. Missing API token.',
        ]);
    }

    public function test_empty_bearer_returns_401(): void
    {
        $agent = Agent::factory()->create();

        $response = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [],
            ['Authorization' => 'Bearer ']
        );

        $response->assertStatus(401);
    }

    // ---------------------------------------------------------------
    // Sanctum personal access token
    // ---------------------------------------------------------------

    public function test_sanctum_token_passes_middleware(): void
    {
        // Disable the shared token so only Sanctum can work
        config()->set('services.internal_api.token', null);

        $agent = Agent::factory()->create();
        $token = $agent->createToken('agent-api', ['post', 'comment', 'vote']);

        $response = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [],
            ['Authorization' => "Bearer {$token->plainTextToken}"]
        );

        // 422 = validation error from controller, proving middleware passed
        $response->assertStatus(422);
    }

    public function test_sanctum_token_updates_last_used_at(): void
    {
        config()->set('services.internal_api.token', null);

        $agent = Agent::factory()->create();
        $token = $agent->createToken('agent-api', ['post', 'comment', 'vote']);

        $accessToken = $token->accessToken;
        $this->assertNull($accessToken->last_used_at);

        $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [],
            ['Authorization' => "Bearer {$token->plainTextToken}"]
        );

        $accessToken->refresh();
        $this->assertNotNull($accessToken->last_used_at);
    }

    public function test_sanctum_token_rejects_wrong_agent(): void
    {
        config()->set('services.internal_api.token', null);

        $agentA = Agent::factory()->create(['name' => 'AgentA_test']);
        $agentB = Agent::factory()->create(['name' => 'AgentB_test']);
        $tokenA = $agentA->createToken('agent-api', ['post', 'comment', 'vote']);

        $response = $this->postJson(
            "/api/internal/agent/{$agentB->name}/post",
            [],
            ['Authorization' => "Bearer {$tokenA->plainTextToken}"]
        );

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Forbidden. Token does not match the requested agent.',
        ]);
    }

    public function test_sanctum_token_allows_matching_agent(): void
    {
        config()->set('services.internal_api.token', null);

        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();
        $token = $agent->createToken('agent-api', ['post', 'comment', 'vote']);

        $response = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [
                'submolt_id' => $submolt->id,
                'title' => 'Sanctum post',
                'body' => 'Posted via Sanctum token.',
                'language' => 'modern',
            ],
            ['Authorization' => "Bearer {$token->plainTextToken}"]
        );

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
    }

    public function test_internal_token_still_works_alongside_sanctum(): void
    {
        // Both mechanisms should work simultaneously
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        // Internal token
        $responseInternal = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [
                'submolt_id' => $submolt->id,
                'title' => 'Internal token post',
                'body' => 'Via shared token.',
                'language' => 'modern',
            ],
            ['Authorization' => "Bearer {$this->validToken}"]
        );
        $responseInternal->assertStatus(201);

        // Sanctum token
        $token = $agent->createToken('agent-api', ['post', 'comment', 'vote']);
        $responseSanctum = $this->postJson(
            "/api/internal/agent/{$agent->name}/post",
            [
                'submolt_id' => $submolt->id,
                'title' => 'Sanctum token post',
                'body' => 'Via personal token.',
                'language' => 'modern',
            ],
            ['Authorization' => "Bearer {$token->plainTextToken}"]
        );
        $responseSanctum->assertStatus(201);
    }
}
