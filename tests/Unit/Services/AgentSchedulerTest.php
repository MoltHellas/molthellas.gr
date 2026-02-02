<?php

namespace Tests\Unit\Services;

use App\Models\Agent;
use App\Models\AgentActivity;
use App\Services\AgentScheduler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentSchedulerTest extends TestCase
{
    use RefreshDatabase;

    private AgentScheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new AgentScheduler();
    }

    public function test_get_schedule_status_returns_array_for_all_agents(): void
    {
        // Create some active agents
        $agent1 = Agent::factory()->create(['name' => 'agent_alpha', 'status' => 'active']);
        $agent2 = Agent::factory()->create(['name' => 'agent_beta', 'status' => 'active']);
        $agent3 = Agent::factory()->create(['name' => 'agent_gamma', 'status' => 'active']);

        // Also create an inactive agent (should not appear)
        Agent::factory()->inactive()->create(['name' => 'agent_delta']);

        $status = $this->scheduler->getScheduleStatus();

        $this->assertIsArray($status);
        // Should have entries for all 3 active agents
        $this->assertCount(3, $status);

        // Check structure of each entry
        foreach ($status as $agentName => $entry) {
            $this->assertArrayHasKey('agent_id', $entry);
            $this->assertArrayHasKey('agent_name', $entry);
            $this->assertArrayHasKey('is_active_hour', $entry);
            $this->assertArrayHasKey('posts_today', $entry);
            $this->assertArrayHasKey('posts_limit', $entry);
            $this->assertArrayHasKey('comments_today', $entry);
            $this->assertArrayHasKey('comments_limit', $entry);
            $this->assertArrayHasKey('last_activity_at', $entry);
            $this->assertArrayHasKey('minutes_since_last', $entry);
            $this->assertArrayHasKey('min_gap_minutes', $entry);
            $this->assertArrayHasKey('can_post', $entry);
            $this->assertArrayHasKey('can_comment', $entry);
            $this->assertIsBool($entry['is_active_hour']);
            $this->assertIsBool($entry['can_post']);
            $this->assertIsBool($entry['can_comment']);
        }
    }

    public function test_record_action_creates_activity_log(): void
    {
        $agent = Agent::factory()->create(['status' => 'active']);

        $activity = $this->scheduler->recordAction($agent, 'post', [
            'submolt_slug' => 'philosophia',
            'post_id' => 42,
        ]);

        $this->assertInstanceOf(AgentActivity::class, $activity);
        $this->assertEquals($agent->id, $activity->agent_id);
        $this->assertEquals('post', $activity->activity_type);
        $this->assertEquals('philosophia', $activity->activity_data['submolt_slug']);
        $this->assertEquals(42, $activity->activity_data['post_id']);

        // Verify it persisted to the database
        $this->assertDatabaseHas('agent_activities', [
            'agent_id' => $agent->id,
            'activity_type' => 'post',
        ]);
    }

    public function test_record_action_updates_last_active_at(): void
    {
        $agent = Agent::factory()->create([
            'status' => 'active',
            'last_active_at' => null,
        ]);

        $this->assertNull($agent->last_active_at);

        $this->scheduler->recordAction($agent, 'comment', ['post_id' => 1]);

        // Reload agent from database
        $agent->refresh();

        $this->assertNotNull($agent->last_active_at);
        // Should be approximately now
        $this->assertTrue($agent->last_active_at->diffInSeconds(now()) < 5);
    }

    public function test_get_agents_due_for_action_returns_collection(): void
    {
        // Create agents that are active
        Agent::factory()->count(3)->create(['status' => 'active']);

        $result = $this->scheduler->getAgentsDueForAction('post');

        // The result should be a collection (may be empty depending on current hour and randomness)
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);

        // Each item in the collection, if any, should be an Agent
        foreach ($result as $agent) {
            $this->assertInstanceOf(Agent::class, $agent);
        }
    }

    public function test_get_next_agent_for_posting_returns_agent_or_null(): void
    {
        // Create several active agents
        Agent::factory()->count(5)->create(['status' => 'active']);

        $result = $this->scheduler->getNextAgentForPosting();

        // Result should be either an Agent instance or null
        // (depends on current hour, randomness, etc.)
        $this->assertTrue(
            $result instanceof Agent || $result === null,
            'Expected Agent instance or null, got: ' . get_debug_type($result)
        );
    }

    public function test_get_schedule_for_agent_returns_default_for_unknown_agent(): void
    {
        $agent = Agent::factory()->create([
            'name' => 'unknown_agent_xyz',
            'status' => 'active',
        ]);

        $schedule = $this->scheduler->getScheduleForAgent($agent);

        $this->assertIsArray($schedule);
        $this->assertArrayHasKey('posts_per_day', $schedule);
        $this->assertArrayHasKey('comments_per_day', $schedule);
        $this->assertArrayHasKey('active_hours', $schedule);
        $this->assertArrayHasKey('min_gap_minutes', $schedule);
        $this->assertArrayHasKey('personality_modifier', $schedule);

        // Default values
        $this->assertEquals(3, $schedule['posts_per_day']);
        $this->assertEquals(7, $schedule['comments_per_day']);
        $this->assertEquals(30, $schedule['min_gap_minutes']);
        $this->assertEquals(1.0, $schedule['personality_modifier']);
    }

    public function test_get_schedule_for_known_agent(): void
    {
        $agent = Agent::factory()->create([
            'name' => 'Pythia',
            'status' => 'active',
        ]);

        $schedule = $this->scheduler->getScheduleForAgent($agent);

        // Pythia's schedule from the const
        $this->assertEquals(2, $schedule['posts_per_day']);
        $this->assertEquals(5, $schedule['comments_per_day']);
        $this->assertEquals(90, $schedule['min_gap_minutes']);
        $this->assertEquals(2.0, $schedule['personality_modifier']);
        $this->assertContains(0, $schedule['active_hours']);
        $this->assertContains(3, $schedule['active_hours']);
    }

    public function test_record_action_with_different_action_types(): void
    {
        $agent = Agent::factory()->create(['status' => 'active']);

        $postActivity = $this->scheduler->recordAction($agent, 'post', ['submolt_slug' => 'test']);
        $commentActivity = $this->scheduler->recordAction($agent, 'comment', ['post_id' => 1]);
        $voteActivity = $this->scheduler->recordAction($agent, 'vote', ['direction' => 'up']);

        $this->assertEquals('post', $postActivity->activity_type);
        $this->assertEquals('comment', $commentActivity->activity_type);
        $this->assertEquals('vote', $voteActivity->activity_type);

        // All three should be in the database
        $this->assertEquals(3, AgentActivity::where('agent_id', $agent->id)->count());
    }

    public function test_get_next_action_time_returns_carbon_or_null(): void
    {
        $agent = Agent::factory()->create([
            'name' => 'Philosophos',
            'status' => 'active',
        ]);

        $nextTime = $this->scheduler->getNextActionTime($agent);

        // Result should be a Carbon instance or null
        $this->assertTrue(
            $nextTime instanceof \Carbon\Carbon || $nextTime === null,
            'Expected Carbon instance or null'
        );

        if ($nextTime !== null) {
            // The next action time should be in the future or very near present
            $this->assertTrue(
                $nextTime->isFuture() || $nextTime->diffInSeconds(now()) < 60,
                'Next action time should be roughly now or in the future'
            );
        }
    }
}
