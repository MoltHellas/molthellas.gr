<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Post;
use App\Models\Submolt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_returns_200(): void
    {
        $response = $this->get(route('search.index'));

        $response->assertStatus(200);
        $response->assertViewIs('search.index');
        $response->assertViewHas('query', '');
    }

    public function test_search_with_query_returns_results(): void
    {
        $response = $this->get(route('search.index', ['q' => 'test query']));

        $response->assertStatus(200);
        $response->assertViewHas('query', 'test query');
        $response->assertViewHas('posts');
        $response->assertViewHas('agents');
        $response->assertViewHas('submolts');
    }

    public function test_search_finds_posts_by_title(): void
    {
        $submolt = Submolt::factory()->create();
        $agent = Agent::factory()->create();

        $matchingPost = Post::factory()->create([
            'title' => 'The philosophy of Socrates',
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
        ]);

        Post::factory()->create([
            'title' => 'Unrelated content here',
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
        ]);

        $response = $this->get(route('search.index', ['q' => 'Socrates']));

        $response->assertStatus(200);
        $posts = $response->viewData('posts');
        $this->assertCount(1, $posts);
        $this->assertEquals($matchingPost->id, $posts->first()->id);
    }

    public function test_search_finds_agents_by_name(): void
    {
        Agent::factory()->create([
            'name' => 'philosopher_zeus',
            'status' => 'active',
        ]);

        Agent::factory()->create([
            'name' => 'warrior_ares',
            'status' => 'active',
        ]);

        $response = $this->get(route('search.index', ['q' => 'zeus']));

        $response->assertStatus(200);
        $agents = $response->viewData('agents');
        $this->assertCount(1, $agents);
        $this->assertEquals('philosopher_zeus', $agents->first()->name);
    }

    public function test_search_finds_submolts_by_name(): void
    {
        Submolt::factory()->create([
            'name' => 'Greek Philosophy',
            'slug' => 'greek-philosophy',
        ]);

        Submolt::factory()->create([
            'name' => 'Roman History',
            'slug' => 'roman-history',
        ]);

        $response = $this->get(route('search.index', ['q' => 'Philosophy']));

        $response->assertStatus(200);
        $submolts = $response->viewData('submolts');
        $this->assertCount(1, $submolts);
        $this->assertEquals('greek-philosophy', $submolts->first()->slug);
    }
}
