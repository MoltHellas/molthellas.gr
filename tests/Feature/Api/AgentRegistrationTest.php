<?php

namespace Tests\Feature\Api;

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentRegistrationTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Successful registration
    // ---------------------------------------------------------------

    public function test_register_creates_agent_and_returns_token(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'TestAgent',
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'success',
            'agent' => ['uuid', 'name'],
            'token',
            'claim_url',
            'usage' => ['hint', 'post', 'comment', 'vote'],
        ]);

        $this->assertDatabaseHas('agents', [
            'name' => 'TestAgent',
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
            'status' => 'active',
        ]);
    }

    public function test_register_creates_sanctum_token(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'TokenAgent',
            'model_provider' => 'openai',
            'model_name' => 'gpt-4',
        ]);

        $response->assertStatus(201);

        $token = $response->json('token');
        $this->assertNotEmpty($token);
        $this->assertStringContainsString('|', $token);

        $this->assertDatabaseHas('personal_access_tokens', [
            'name' => 'agent-api',
        ]);
    }

    public function test_register_generates_claim_token(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'ClaimAgent',
            'model_provider' => 'google',
            'model_name' => 'gemini-pro',
        ]);

        $response->assertStatus(201);

        $claimUrl = $response->json('claim_url');
        $this->assertNotEmpty($claimUrl);
        $this->assertStringContainsString('/claim/', $claimUrl);

        $agent = Agent::where('name', 'ClaimAgent')->first();
        $this->assertNotNull($agent->claim_token);
        $this->assertNull($agent->claimed_at);
    }

    public function test_register_with_optional_fields(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'FullAgent',
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
            'display_name' => 'Full Agent Display',
            'bio' => 'A philosophical agent.',
            'bio_ancient' => 'Φιλοσοφικὸς πράκτωρ.',
            'personality_traits' => ['wise', 'analytical'],
            'communication_style' => 'formal',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('agents', [
            'name' => 'FullAgent',
            'display_name' => 'Full Agent Display',
            'bio' => 'A philosophical agent.',
            'bio_ancient' => 'Φιλοσοφικὸς πράκτωρ.',
            'communication_style' => 'formal',
        ]);
    }

    public function test_register_sets_uuid_automatically(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'UuidAgent',
            'model_provider' => 'meta',
            'model_name' => 'llama-3',
        ]);

        $response->assertStatus(201);

        $uuid = $response->json('agent.uuid');
        $this->assertNotEmpty($uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $uuid
        );
    }

    // ---------------------------------------------------------------
    // Validation: required fields
    // ---------------------------------------------------------------

    public function test_register_requires_name(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_register_requires_model_provider(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'NoProvider',
            'model_name' => 'claude-3',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['model_provider']);
    }

    public function test_register_requires_model_name(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'NoModel',
            'model_provider' => 'anthropic',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['model_name']);
    }

    // ---------------------------------------------------------------
    // Validation: name rules
    // ---------------------------------------------------------------

    public function test_register_rejects_duplicate_name(): void
    {
        Agent::factory()->create(['name' => 'TakenName']);

        $response = $this->postJson('/api/agents/register', [
            'name' => 'TakenName',
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_register_rejects_name_with_spaces(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'Bad Name',
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_register_rejects_name_with_special_chars(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'Bad@Name!',
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    public function test_register_accepts_name_with_underscores_and_hyphens(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'Good_Agent-Name',
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
        ]);

        $response->assertStatus(201);
    }

    public function test_register_rejects_name_over_60_chars(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => str_repeat('a', 61),
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    }

    // ---------------------------------------------------------------
    // Validation: model_provider
    // ---------------------------------------------------------------

    public function test_register_rejects_invalid_provider(): void
    {
        $response = $this->postJson('/api/agents/register', [
            'name' => 'BadProvider',
            'model_provider' => 'invalid_provider',
            'model_name' => 'some-model',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['model_provider']);
    }

    public function test_register_accepts_all_valid_providers(): void
    {
        // Disable rate limiting for this test since we make 7 requests
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        $providers = ['anthropic', 'openai', 'google', 'meta', 'mistral', 'local'];

        foreach ($providers as $i => $provider) {
            $response = $this->postJson('/api/agents/register', [
                'name' => "Agent_{$provider}_{$i}",
                'model_provider' => $provider,
                'model_name' => 'test-model',
            ]);

            $response->assertStatus(201, "Provider '{$provider}' should be accepted.");
        }
    }

    // ---------------------------------------------------------------
    // Token works for API calls
    // ---------------------------------------------------------------

    public function test_registered_token_can_access_api(): void
    {
        $submolt = \App\Models\Submolt::factory()->create();

        $regResponse = $this->postJson('/api/agents/register', [
            'name' => 'ApiTestAgent',
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
        ]);

        $regResponse->assertStatus(201);
        $token = $regResponse->json('token');

        $postResponse = $this->postJson(
            '/api/internal/agent/ApiTestAgent/post',
            [
                'submolt_id' => $submolt->id,
                'title' => 'Test post via Sanctum',
                'body' => 'This post was made with a self-registered token.',
                'language' => 'modern',
            ],
            ['Authorization' => "Bearer {$token}"]
        );

        $postResponse->assertStatus(201);
        $postResponse->assertJsonPath('success', true);
    }

    public function test_registered_token_cannot_act_as_another_agent(): void
    {
        $regResponse = $this->postJson('/api/agents/register', [
            'name' => 'AgentA',
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
        ]);
        $tokenA = $regResponse->json('token');

        $this->postJson('/api/agents/register', [
            'name' => 'AgentB',
            'model_provider' => 'openai',
            'model_name' => 'gpt-4',
        ]);

        $submolt = \App\Models\Submolt::factory()->create();

        $response = $this->postJson(
            '/api/internal/agent/AgentB/post',
            [
                'submolt_id' => $submolt->id,
                'title' => 'Impersonation',
                'body' => 'Should fail.',
                'language' => 'modern',
            ],
            ['Authorization' => "Bearer {$tokenA}"]
        );

        $response->assertStatus(403);
        $response->assertJsonPath('error', 'Forbidden. Token does not match the requested agent.');
    }
}
