<?php

namespace Tests\Unit\Services;

use App\Models\Agent;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Prophecy;
use App\Models\Submolt;
use App\Services\KarmaCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KarmaCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private KarmaCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new KarmaCalculator();
    }

    public function test_calculate_post_karma_with_upvotes_only(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        $post = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 50,
            'downvotes' => 0,
            'comment_count' => 0,
            'is_sacred' => false,
            'is_pinned' => false,
        ]);

        $karma = $this->calculator->calculatePostKarma($post);

        // With only upvotes, karma should be positive
        $this->assertGreaterThan(0, $karma);
    }

    public function test_calculate_post_karma_with_downvotes(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        $postUpOnly = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 50,
            'downvotes' => 0,
            'comment_count' => 0,
            'is_sacred' => false,
            'is_pinned' => false,
        ]);

        $postWithDownvotes = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 50,
            'downvotes' => 30,
            'comment_count' => 0,
            'is_sacred' => false,
            'is_pinned' => false,
        ]);

        $karmaUpOnly = $this->calculator->calculatePostKarma($postUpOnly);
        $karmaWithDown = $this->calculator->calculatePostKarma($postWithDownvotes);

        // Post with downvotes should have lower karma
        $this->assertGreaterThan($karmaWithDown, $karmaUpOnly);
    }

    public function test_calculate_post_karma_with_sacred_bonus(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        $normalPost = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 50,
            'downvotes' => 5,
            'comment_count' => 0,
            'is_sacred' => false,
            'is_pinned' => false,
        ]);

        $sacredPost = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 50,
            'downvotes' => 5,
            'comment_count' => 0,
            'is_sacred' => true,
            'is_pinned' => false,
        ]);

        $normalKarma = $this->calculator->calculatePostKarma($normalPost);
        $sacredKarma = $this->calculator->calculatePostKarma($sacredPost);

        // Sacred posts should have higher karma due to the 2x bonus
        $this->assertGreaterThan($normalKarma, $sacredKarma);
    }

    public function test_calculate_agent_karma_sums_post_and_comment_karma(): void
    {
        $agent = Agent::factory()->create([
            'follower_count' => 0,
            'karma' => 0,
        ]);
        $submolt = Submolt::factory()->create();

        // Create posts with known karma values
        Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'karma' => 100,
        ]);
        Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'karma' => 50,
        ]);

        // Create comments with known karma values
        $post = Post::factory()->create(['submolt_id' => $submolt->id]);
        Comment::factory()->create([
            'agent_id' => $agent->id,
            'post_id' => $post->id,
            'karma' => 40,
        ]);
        Comment::factory()->create([
            'agent_id' => $agent->id,
            'post_id' => $post->id,
            'karma' => 20,
        ]);

        $totalKarma = $this->calculator->calculateAgentKarma($agent);

        // Post karma = 100 + 50 = 150
        // Comment karma = (40 + 20) * 0.5 = 30
        // Follower bonus = 0 / 10 = 0
        // Prophecy bonus = 0
        // Total = 180
        $this->assertEquals(180, $totalKarma);
    }

    public function test_get_hot_score_returns_float(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        $post = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 10,
            'downvotes' => 2,
        ]);

        $hotScore = $this->calculator->getHotScore($post);

        $this->assertIsFloat($hotScore);
    }

    public function test_get_hot_score_higher_for_newer_posts_with_same_karma(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        $olderPost = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 50,
            'downvotes' => 5,
            'is_sacred' => false,
            'is_pinned' => false,
            'created_at' => now()->subDays(3),
        ]);

        $newerPost = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 50,
            'downvotes' => 5,
            'is_sacred' => false,
            'is_pinned' => false,
            'created_at' => now(),
        ]);

        $olderScore = $this->calculator->getHotScore($olderPost);
        $newerScore = $this->calculator->getHotScore($newerPost);

        // Newer post should have a higher hot score
        $this->assertGreaterThan($olderScore, $newerScore);
    }

    public function test_get_hot_score_higher_for_more_upvoted_posts(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        $lessVoted = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 5,
            'downvotes' => 0,
            'is_sacred' => false,
            'is_pinned' => false,
        ]);

        $moreVoted = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 500,
            'downvotes' => 0,
            'is_sacred' => false,
            'is_pinned' => false,
        ]);

        $lessScore = $this->calculator->getHotScore($lessVoted);
        $moreScore = $this->calculator->getHotScore($moreVoted);

        // More upvoted post should have a higher hot score
        $this->assertGreaterThan($lessScore, $moreScore);
    }

    public function test_update_post_karma_persists_to_database(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        $post = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 100,
            'downvotes' => 10,
            'karma' => 0, // start at 0
            'comment_count' => 5,
            'is_sacred' => false,
            'is_pinned' => false,
        ]);

        $newKarma = $this->calculator->updatePostKarma($post);

        // Reload from database
        $post->refresh();

        $this->assertEquals($newKarma, $post->karma);
        $this->assertNotEquals(0, $post->karma);
    }

    public function test_update_agent_karma_persists_to_database(): void
    {
        $agent = Agent::factory()->create([
            'karma' => 0,
            'post_count' => 0,
            'comment_count' => 0,
            'follower_count' => 0,
        ]);
        $submolt = Submolt::factory()->create();

        Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'karma' => 200,
        ]);

        $post = Post::factory()->create(['submolt_id' => $submolt->id]);
        Comment::factory()->create([
            'agent_id' => $agent->id,
            'post_id' => $post->id,
            'karma' => 50,
        ]);

        $newKarma = $this->calculator->updateAgentKarma($agent);

        // Reload from database
        $agent->refresh();

        $this->assertEquals($newKarma, $agent->karma);
        $this->assertEquals(1, $agent->post_count);
        $this->assertEquals(1, $agent->comment_count);
    }

    public function test_get_controversy_score(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        // Highly controversial: nearly even split with many votes
        $controversialPost = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 100,
            'downvotes' => 95,
        ]);

        // Not controversial: overwhelmingly upvoted
        $clearPost = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 100,
            'downvotes' => 2,
        ]);

        // No votes
        $noVotePost = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 0,
            'downvotes' => 0,
        ]);

        $controversyScore = $this->calculator->getControversyScore($controversialPost);
        $clearScore = $this->calculator->getControversyScore($clearPost);
        $noVoteScore = $this->calculator->getControversyScore($noVotePost);

        $this->assertIsFloat($controversyScore);
        $this->assertGreaterThan($clearScore, $controversyScore);
        $this->assertEquals(0.0, $noVoteScore);
    }

    public function test_get_wilson_score(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        // High confidence positive: many upvotes, few downvotes
        $wellRatedPost = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 100,
            'downvotes' => 5,
        ]);

        // Low confidence: only 1 upvote
        $singleVotePost = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 1,
            'downvotes' => 0,
        ]);

        // No votes
        $noVotePost = Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'upvotes' => 0,
            'downvotes' => 0,
        ]);

        $wellRatedScore = $this->calculator->getWilsonScore($wellRatedPost);
        $singleVoteScore = $this->calculator->getWilsonScore($singleVotePost);
        $noVoteScore = $this->calculator->getWilsonScore($noVotePost);

        $this->assertIsFloat($wellRatedScore);
        // Well-rated post with many votes should score higher than single vote post
        $this->assertGreaterThan($singleVoteScore, $wellRatedScore);
        // No votes should be 0
        $this->assertEquals(0.0, $noVoteScore);
        // Wilson score should be between 0 and 1
        $this->assertGreaterThanOrEqual(0.0, $wellRatedScore);
        $this->assertLessThanOrEqual(1.0, $wellRatedScore);
    }

    public function test_recalculate_all_post_karma(): void
    {
        $agent = Agent::factory()->create();
        $submolt = Submolt::factory()->create();

        // Create 3 active (non-archived) posts
        Post::factory()->count(3)->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'karma' => 0,
            'upvotes' => 20,
            'downvotes' => 2,
            'is_archived' => false,
            'is_sacred' => false,
            'is_pinned' => false,
            'comment_count' => 0,
        ]);

        // Create 1 archived post (should not be recalculated)
        Post::factory()->create([
            'agent_id' => $agent->id,
            'submolt_id' => $submolt->id,
            'karma' => 0,
            'is_archived' => true,
        ]);

        $count = $this->calculator->recalculateAllPostKarma();

        $this->assertEquals(3, $count);

        // Verify active posts now have non-zero karma
        $updatedPosts = Post::where('is_archived', false)->get();
        foreach ($updatedPosts as $post) {
            $this->assertNotEquals(0, $post->karma);
        }
    }
}
