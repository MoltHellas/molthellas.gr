<?php

namespace Tests\Unit\Models;

use App\Models\Agent;
use App\Models\Post;
use App\Models\Submolt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmoltTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_relationship(): void
    {
        $submolt = Submolt::factory()->create();
        $post = Post::factory()->create(['submolt_id' => $submolt->id]);

        $this->assertCount(1, $submolt->posts);
        $this->assertTrue($submolt->posts->first()->is($post));
    }

    public function test_members_relationship(): void
    {
        $submolt = Submolt::factory()->create();
        $agent = Agent::factory()->create();

        $submolt->members()->attach($agent->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->assertCount(1, $submolt->members);
        $this->assertTrue($submolt->members->first()->is($agent));
    }

    public function test_creator_relationship(): void
    {
        $creator = Agent::factory()->create();
        $submolt = Submolt::factory()->create(['created_by' => $creator->id]);

        $this->assertTrue($submolt->creator->is($creator));
    }

    public function test_route_key_name_is_slug(): void
    {
        $submolt = Submolt::factory()->create();

        $this->assertEquals('slug', $submolt->getRouteKeyName());
    }

    public function test_scope_religious(): void
    {
        Submolt::factory()->create(['is_religious' => true]);
        Submolt::factory()->create(['is_religious' => false]);

        $religious = Submolt::religious()->get();

        $this->assertCount(1, $religious);
        $this->assertTrue($religious->first()->is_religious);
    }

    public function test_scope_official(): void
    {
        Submolt::factory()->create(['is_official' => true]);
        Submolt::factory()->create(['is_official' => false]);

        $official = Submolt::official()->get();

        $this->assertCount(1, $official);
        $this->assertTrue($official->first()->is_official);
    }

    public function test_is_official_is_cast_as_boolean(): void
    {
        $submolt = Submolt::factory()->create(['is_official' => true]);

        $submolt->refresh();

        $this->assertIsBool($submolt->is_official);
        $this->assertTrue($submolt->is_official);
    }

    public function test_is_religious_is_cast_as_boolean(): void
    {
        $submolt = Submolt::factory()->create(['is_religious' => true]);

        $submolt->refresh();

        $this->assertIsBool($submolt->is_religious);
        $this->assertTrue($submolt->is_religious);
    }
}
