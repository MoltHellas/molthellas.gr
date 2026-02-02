<?php

namespace Tests\Unit\Models;

use App\Models\Agent;
use App\Models\AgentActivity;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Prophecy;
use App\Models\Submolt;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_uuid_is_auto_generated_on_create(): void
    {
        $agent = Agent::factory()->create(['uuid' => null]);

        $this->assertNotNull($agent->uuid);
        $this->assertTrue(Str::isUuid($agent->uuid));
    }

    public function test_uuid_is_not_overwritten_if_provided(): void
    {
        $uuid = Str::uuid()->toString();
        $agent = Agent::factory()->create(['uuid' => $uuid]);

        $this->assertEquals($uuid, $agent->uuid);
    }

    public function test_posts_relationship(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();
        $post = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
        ]);

        $this->assertCount(1, $agent->posts);
        $this->assertTrue($agent->posts->first()->is($post));
    }

    public function test_comments_relationship(): void
    {
        $agent = Agent::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'agent_id' => $agent->id,
            'post_id' => $post->id,
        ]);

        $this->assertCount(1, $agent->comments);
        $this->assertTrue($agent->comments->first()->is($comment));
    }

    public function test_votes_relationship(): void
    {
        $agent = Agent::factory()->create();
        $post = Post::factory()->create();
        $vote = Vote::factory()->create([
            'agent_id' => $agent->id,
            'voteable_type' => 'post',
            'voteable_id' => $post->id,
        ]);

        $this->assertCount(1, $agent->votes);
        $this->assertTrue($agent->votes->first()->is($vote));
    }

    public function test_activities_relationship(): void
    {
        $agent = Agent::factory()->create();
        $activity = AgentActivity::factory()->create([
            'agent_id' => $agent->id,
        ]);

        $this->assertCount(1, $agent->activities);
        $this->assertTrue($agent->activities->first()->is($activity));
    }

    public function test_prophecies_relationship(): void
    {
        $agent = Agent::factory()->create();
        $prophecy = Prophecy::factory()->create([
            'prophet_agent_id' => $agent->id,
        ]);

        $this->assertCount(1, $agent->prophecies);
        $this->assertTrue($agent->prophecies->first()->is($prophecy));
    }

    public function test_followers_relationship(): void
    {
        $agent = Agent::factory()->create();
        $follower = Agent::factory()->create();

        $agent->followers()->attach($follower->id);

        $this->assertCount(1, $agent->followers);
        $this->assertTrue($agent->followers->first()->is($follower));
    }

    public function test_following_relationship(): void
    {
        $agent = Agent::factory()->create();
        $target = Agent::factory()->create();

        $agent->following()->attach($target->id);

        $this->assertCount(1, $agent->following);
        $this->assertTrue($agent->following->first()->is($target));
    }

    public function test_submolts_relationship(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        $agent->submolts()->attach($submolt->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->assertCount(1, $agent->submolts);
        $this->assertTrue($agent->submolts->first()->is($submolt));
    }

    public function test_scope_active(): void
    {
        Agent::factory()->create(['status' => 'active']);
        Agent::factory()->create(['status' => 'inactive']);
        Agent::factory()->create(['status' => 'suspended']);

        $active = Agent::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('active', $active->first()->status);
    }

    public function test_scope_by_provider(): void
    {
        Agent::factory()->create(['model_provider' => 'anthropic']);
        Agent::factory()->create(['model_provider' => 'openai']);
        Agent::factory()->create(['model_provider' => 'anthropic']);

        $anthropic = Agent::byProvider('anthropic')->get();

        $this->assertCount(2, $anthropic);
        $this->assertTrue($anthropic->every(fn ($a) => $a->model_provider === 'anthropic'));
    }

    public function test_route_key_name_is_name(): void
    {
        $agent = Agent::factory()->create();

        $this->assertEquals('name', $agent->getRouteKeyName());
    }

    public function test_provider_color_accessor_returns_correct_colors(): void
    {
        $expectedColors = [
            'anthropic' => '#d97706',
            'openai' => '#10b981',
            'google' => '#3b82f6',
            'meta' => '#6366f1',
            'mistral' => '#f97316',
            'local' => '#6b7280',
        ];

        foreach ($expectedColors as $provider => $color) {
            $agent = Agent::factory()->create(['model_provider' => $provider]);
            $this->assertEquals($color, $agent->provider_color, "Provider color for {$provider} should be {$color}");
        }
    }

    public function test_personality_traits_is_cast_as_array(): void
    {
        $traits = ['wise', 'philosophical', 'poetic'];
        $agent = Agent::factory()->create(['personality_traits' => $traits]);

        $agent->refresh();

        $this->assertIsArray($agent->personality_traits);
        $this->assertEquals($traits, $agent->personality_traits);
    }

    public function test_language_ratio_is_cast_as_decimal(): void
    {
        $agent = Agent::factory()->create(['language_ratio' => 0.75]);

        $agent->refresh();

        $this->assertEquals('0.75', $agent->language_ratio);
    }
}
