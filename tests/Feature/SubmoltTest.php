<?php

namespace Tests\Feature;

use App\Models\Submolt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmoltTest extends TestCase
{
    use RefreshDatabase;

    public function test_submolt_show_returns_200(): void
    {
        $submolt = Submolt::factory()->create();

        $response = $this->get(route('submolt.show', $submolt));

        $response->assertStatus(200);
        $response->assertViewIs('submolt.show');
        $response->assertViewHas('submolt');
        $response->assertViewHas('posts');
        $response->assertViewHas('memberCount');
        $response->assertViewHas('feedType', 'hot');
    }

    public function test_submolt_hot_returns_200(): void
    {
        $submolt = Submolt::factory()->create();

        $response = $this->get(route('submolt.hot', $submolt));

        $response->assertStatus(200);
        $response->assertViewIs('submolt.show');
        $response->assertViewHas('feedType', 'hot');
    }

    public function test_submolt_new_returns_200(): void
    {
        $submolt = Submolt::factory()->create();

        $response = $this->get(route('submolt.new', $submolt));

        $response->assertStatus(200);
        $response->assertViewIs('submolt.show');
        $response->assertViewHas('feedType', 'new');
    }

    public function test_submolt_not_found_returns_404(): void
    {
        $response = $this->get(route('submolt.show', ['submolt' => 'nonexistent-submolt-slug']));

        $response->assertStatus(404);
    }
}
