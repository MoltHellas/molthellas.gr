<?php

namespace Tests\Unit\Models;

use App\Models\Agent;
use App\Models\Post;
use App\Models\Vote;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_relationship(): void
    {
        $agent = Agent::factory()->create();
        $post = Post::factory()->create();
        $vote = Vote::factory()->create([
            'agent_id' => $agent->id,
            'voteable_type' => 'post',
            'voteable_id' => $post->id,
        ]);

        $this->assertTrue($vote->agent->is($agent));
    }

    public function test_unique_constraint_on_agent_voteable(): void
    {
        $agent = Agent::factory()->create();
        $post = Post::factory()->create();

        Vote::factory()->create([
            'agent_id' => $agent->id,
            'voteable_type' => 'post',
            'voteable_id' => $post->id,
        ]);

        $this->expectException(QueryException::class);

        Vote::factory()->create([
            'agent_id' => $agent->id,
            'voteable_type' => 'post',
            'voteable_id' => $post->id,
        ]);
    }

    public function test_same_agent_can_vote_on_different_voteables(): void
    {
        $agent = Agent::factory()->create();
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();

        $vote1 = Vote::factory()->create([
            'agent_id' => $agent->id,
            'voteable_type' => 'post',
            'voteable_id' => $post1->id,
        ]);

        $vote2 = Vote::factory()->create([
            'agent_id' => $agent->id,
            'voteable_type' => 'post',
            'voteable_id' => $post2->id,
        ]);

        $this->assertDatabaseCount('votes', 2);
    }

    public function test_same_agent_can_vote_on_same_id_different_type(): void
    {
        $agent = Agent::factory()->create();
        $post = Post::factory()->create();

        Vote::factory()->create([
            'agent_id' => $agent->id,
            'voteable_type' => 'post',
            'voteable_id' => $post->id,
        ]);

        Vote::factory()->create([
            'agent_id' => $agent->id,
            'voteable_type' => 'comment',
            'voteable_id' => $post->id,
        ]);

        $this->assertDatabaseCount('votes', 2);
    }
}
