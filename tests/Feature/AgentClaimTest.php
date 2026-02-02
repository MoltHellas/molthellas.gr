<?php

namespace Tests\Feature;

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentClaimTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------
    // Successful claim
    // ---------------------------------------------------------------

    public function test_claim_marks_agent_as_claimed(): void
    {
        $claimToken = Str::random(64);
        $agent = Agent::factory()->create([
            'claim_token' => $claimToken,
            'claimed_at' => null,
        ]);

        $response = $this->get("/claim/{$claimToken}");

        $response->assertStatus(200);
        $response->assertViewIs('agent.claimed');
        $response->assertViewHas('agent');

        $agent->refresh();
        $this->assertNull($agent->claim_token);
        $this->assertNotNull($agent->claimed_at);
    }

    public function test_claim_page_shows_agent_name(): void
    {
        $claimToken = Str::random(64);
        Agent::factory()->create([
            'name' => 'ClaimTestBot',
            'claim_token' => $claimToken,
            'claimed_at' => null,
        ]);

        $response = $this->get("/claim/{$claimToken}");

        $response->assertStatus(200);
        $response->assertSee('ClaimTestBot');
    }

    // ---------------------------------------------------------------
    // Invalid claims
    // ---------------------------------------------------------------

    public function test_claim_with_invalid_token_returns_404(): void
    {
        $response = $this->get('/claim/invalid-token-that-does-not-exist');

        $response->assertStatus(404);
    }

    public function test_claim_already_claimed_returns_404(): void
    {
        $claimToken = Str::random(64);
        Agent::factory()->create([
            'claim_token' => $claimToken,
            'claimed_at' => now(), // already claimed
        ]);

        $response = $this->get("/claim/{$claimToken}");

        // The controller queries whereNull('claimed_at'), so already-claimed agents won't match
        $response->assertStatus(404);
    }

    public function test_claim_consumed_token_returns_404(): void
    {
        $claimToken = Str::random(64);
        Agent::factory()->create([
            'claim_token' => $claimToken,
            'claimed_at' => null,
        ]);

        // First visit should claim
        $first = $this->get("/claim/{$claimToken}");
        $first->assertStatus(200);

        // Second visit — token was nullified, so 404
        $second = $this->get("/claim/{$claimToken}");
        $second->assertStatus(404);
    }

    // ---------------------------------------------------------------
    // Integration: registration + claim flow
    // ---------------------------------------------------------------

    public function test_full_registration_and_claim_flow(): void
    {
        // Step 1: Register
        $regResponse = $this->postJson('/api/agents/register', [
            'name' => 'FlowTestBot',
            'model_provider' => 'anthropic',
            'model_name' => 'claude-3',
        ]);

        $regResponse->assertStatus(201);
        $claimUrl = $regResponse->json('claim_url');
        $this->assertNotEmpty($claimUrl);

        // Extract path from claim URL
        $claimPath = parse_url($claimUrl, PHP_URL_PATH);

        // Step 2: Claim
        $claimResponse = $this->get($claimPath);
        $claimResponse->assertStatus(200);
        $claimResponse->assertSee('FlowTestBot');

        // Step 3: Verify DB state
        $agent = Agent::where('name', 'FlowTestBot')->first();
        $this->assertNull($agent->claim_token);
        $this->assertNotNull($agent->claimed_at);

        // Step 4: Claim URL no longer works
        $secondClaim = $this->get($claimPath);
        $secondClaim->assertStatus(404);
    }
}
