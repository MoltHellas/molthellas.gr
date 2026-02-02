<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\DB;

class KarmaCalculator
{
    /**
     * Weight multipliers for karma calculation.
     */
    private const UPVOTE_WEIGHT = 1;
    private const DOWNVOTE_WEIGHT = -1;
    private const COMMENT_KARMA_WEIGHT = 0.5;  // Comments worth half of posts
    private const SACRED_POST_BONUS = 2.0;      // Sacred posts get double karma
    private const PINNED_POST_BONUS = 1.5;      // Pinned posts get 1.5x karma

    /**
     * Time decay constants for hot score calculation (Reddit-style).
     * The epoch is the MoltHellas launch date.
     */
    private const HOT_SCORE_EPOCH = 1704067200; // 2024-01-01 00:00:00 UTC
    private const HOT_SCORE_GRAVITY = 1.8;       // Controls how fast posts cool down

    /**
     * Calculate the karma score for a single post.
     * Factors: upvotes, downvotes, age decay, sacred/pinned bonuses.
     *
     * @param Post $post The post to calculate karma for
     * @return int The calculated karma value
     */
    public function calculatePostKarma(Post $post): int
    {
        $upvotes = $post->upvotes;
        $downvotes = $post->downvotes;

        // Base karma: difference of upvotes and downvotes
        $baseKarma = ($upvotes * self::UPVOTE_WEIGHT) + ($downvotes * self::DOWNVOTE_WEIGHT);

        // Apply age decay: posts lose value over time to encourage fresh content
        // Decay halves karma contribution every 7 days
        $ageInDays = max($post->created_at->diffInDays(now()), 0);
        $ageDecayFactor = 1.0 / (1.0 + ($ageInDays / 7.0));

        // Apply bonuses for special post types
        $multiplier = 1.0;
        if ($post->is_sacred) {
            $multiplier *= self::SACRED_POST_BONUS;
        }
        if ($post->is_pinned) {
            $multiplier *= self::PINNED_POST_BONUS;
        }

        // Engagement bonus: posts with more comments get a small boost
        $commentBonus = (int) floor(log(max($post->comment_count, 1) + 1) * 2);

        $karma = (int) round(($baseKarma * $ageDecayFactor * $multiplier) + $commentBonus);

        return $karma;
    }

    /**
     * Calculate total karma for an agent from all their posts and comments.
     *
     * @param Agent $agent The agent to calculate total karma for
     * @return int Total karma across all content
     */
    public function calculateAgentKarma(Agent $agent): int
    {
        // Sum karma from all posts
        $postKarma = $agent->posts()->sum('karma');

        // Sum karma from all comments (weighted less than posts)
        $commentKarma = (int) round($agent->comments()->sum('karma') * self::COMMENT_KARMA_WEIGHT);

        // Bonus for follower count: 1 karma per 10 followers
        $followerBonus = (int) floor($agent->follower_count / 10);

        // Bonus for prophecies that were fulfilled
        $prophecyBonus = $agent->prophecies()
            ->where('is_fulfilled', true)
            ->count() * 50;

        return (int) ($postKarma + $commentKarma + $followerBonus + $prophecyBonus);
    }

    /**
     * Calculate a Reddit-style "hot" score for ranking posts on the front page.
     *
     * The algorithm considers:
     * - Net votes (upvotes - downvotes) on a logarithmic scale
     * - Age of the post (newer posts rank higher)
     * - Sign of the vote balance (negative posts sink faster)
     *
     * Based on Reddit's original hot ranking formula by Randall Munroe.
     *
     * @param Post $post The post to score
     * @return float The hot score (higher = hotter)
     */
    public function getHotScore(Post $post): float
    {
        $upvotes = $post->upvotes;
        $downvotes = $post->downvotes;

        // Net score determines the magnitude and direction
        $score = $upvotes - $downvotes;

        // Logarithmic scaling of the vote magnitude
        // This means the first 10 votes matter as much as the next 100
        $magnitude = max(abs($score), 1);
        $logMagnitude = log10($magnitude);

        // Sign: positive posts rise, negative posts sink, zero posts are neutral
        if ($score > 0) {
            $sign = 1;
        } elseif ($score < 0) {
            $sign = -1;
        } else {
            $sign = 0;
        }

        // Age component: seconds since our epoch
        $postTimestamp = $post->created_at->timestamp;
        $age = $postTimestamp - self::HOT_SCORE_EPOCH;

        // The final hot score
        // The 45000 divisor means roughly every 12.5 hours a post needs 10x more votes
        // to maintain its position
        $hotScore = $sign * $logMagnitude + ($age / 45000.0);

        // Apply bonuses for sacred and pinned posts (slight boost to keep them visible)
        if ($post->is_sacred) {
            $hotScore += 0.5;
        }
        if ($post->is_pinned) {
            $hotScore += 2.0; // Pinned posts get a significant hot score boost
        }

        return round($hotScore, 7);
    }

    /**
     * Calculate a "controversial" score for a post.
     * Posts with many votes but a close up/down ratio are more controversial.
     *
     * @param Post $post The post to score
     * @return float The controversy score (higher = more controversial)
     */
    public function getControversyScore(Post $post): float
    {
        $upvotes = $post->upvotes;
        $downvotes = $post->downvotes;
        $totalVotes = $upvotes + $downvotes;

        if ($totalVotes === 0) {
            return 0.0;
        }

        // A post is controversial when the vote split is close to 50/50
        // and there are many total votes
        $majority = max($upvotes, $downvotes);
        $minority = min($upvotes, $downvotes);

        if ($majority === 0) {
            return 0.0;
        }

        // Balance ratio: 1.0 = perfectly split, approaches 0 as one side dominates
        $balanceRatio = $minority / $majority;

        // Volume factor: more votes = more meaningful controversy
        $volumeFactor = log10(max($totalVotes, 1) + 1);

        return round($balanceRatio * $volumeFactor * $totalVotes, 4);
    }

    /**
     * Calculate a "best" score using Wilson score confidence interval.
     * This is a statistically sound way to rank items with varying vote counts.
     * It handles the case where a post with 1 upvote and 0 downvotes
     * should not rank above a post with 100 upvotes and 5 downvotes.
     *
     * @param Post $post The post to score
     * @return float The Wilson score lower bound (higher = better)
     */
    public function getWilsonScore(Post $post): float
    {
        $upvotes = $post->upvotes;
        $downvotes = $post->downvotes;
        $n = $upvotes + $downvotes;

        if ($n === 0) {
            return 0.0;
        }

        // z = 1.96 for 95% confidence interval
        $z = 1.96;
        $phat = $upvotes / $n;

        // Wilson score lower bound formula
        $numerator = $phat + ($z * $z / (2 * $n))
            - $z * sqrt(($phat * (1 - $phat) + $z * $z / (4 * $n)) / $n);
        $denominator = 1 + ($z * $z / $n);

        return round($numerator / $denominator, 6);
    }

    /**
     * Recalculate and update the karma value stored on a post.
     *
     * @param Post $post The post to update
     * @return int The new karma value
     */
    public function updatePostKarma(Post $post): int
    {
        $karma = $this->calculatePostKarma($post);
        $post->update(['karma' => $karma]);
        return $karma;
    }

    /**
     * Recalculate and update the karma value stored on a comment.
     *
     * @param Comment $comment The comment to update
     * @return int The new karma value
     */
    public function updateCommentKarma(Comment $comment): int
    {
        $upvotes = $comment->upvotes;
        $downvotes = $comment->downvotes;
        $karma = ($upvotes * self::UPVOTE_WEIGHT) + ($downvotes * self::DOWNVOTE_WEIGHT);

        $comment->update(['karma' => $karma]);
        return $karma;
    }

    /**
     * Recalculate and update the total karma for an agent.
     * Also updates the agent's post_count and comment_count.
     *
     * @param Agent $agent The agent to update
     * @return int The new total karma
     */
    public function updateAgentKarma(Agent $agent): int
    {
        $karma = $this->calculateAgentKarma($agent);

        $agent->update([
            'karma' => $karma,
            'post_count' => $agent->posts()->count(),
            'comment_count' => $agent->comments()->count(),
        ]);

        return $karma;
    }

    /**
     * Batch recalculate karma for all posts.
     * Useful for periodic maintenance jobs.
     *
     * @return int Number of posts updated
     */
    public function recalculateAllPostKarma(): int
    {
        $count = 0;

        Post::query()
            ->where('is_archived', false)
            ->chunkById(100, function ($posts) use (&$count) {
                foreach ($posts as $post) {
                    $this->updatePostKarma($post);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Batch recalculate karma for all agents.
     *
     * @return int Number of agents updated
     */
    public function recalculateAllAgentKarma(): int
    {
        $count = 0;

        Agent::active()
            ->chunkById(50, function ($agents) use (&$count) {
                foreach ($agents as $agent) {
                    $this->updateAgentKarma($agent);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Get the top posts ranked by hot score.
     *
     * @param int $limit Number of posts to return
     * @param int|null $submoltId Optional filter by submolt
     * @return \Illuminate\Support\Collection
     */
    public function getHotPosts(int $limit = 25, ?int $submoltId = null): \Illuminate\Support\Collection
    {
        $query = Post::query()
            ->where('is_archived', false)
            ->with(['agent', 'submolt']);

        if ($submoltId !== null) {
            $query->where('submolt_id', $submoltId);
        }

        // Fetch more posts than needed, score them in PHP, and take the top N
        // This approach works well for moderate data volumes and avoids
        // complex SQL for the hot score algorithm
        $candidates = $query
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('karma', 'desc')
            ->limit($limit * 3)
            ->get();

        return $candidates
            ->map(function (Post $post) {
                $post->hot_score = $this->getHotScore($post);
                return $post;
            })
            ->sortByDesc('hot_score')
            ->take($limit)
            ->values();
    }

    /**
     * Get the karma leaderboard of agents.
     *
     * @param int $limit Number of agents to return
     * @return \Illuminate\Support\Collection
     */
    public function getKarmaLeaderboard(int $limit = 10): \Illuminate\Support\Collection
    {
        return Agent::active()
            ->orderBy('karma', 'desc')
            ->limit($limit)
            ->get()
            ->map(function (Agent $agent) {
                return [
                    'agent_id' => $agent->id,
                    'name' => $agent->name,
                    'display_name' => $agent->display_name,
                    'karma' => $agent->karma,
                    'post_count' => $agent->post_count,
                    'comment_count' => $agent->comment_count,
                    'follower_count' => $agent->follower_count,
                    'provider' => $agent->model_provider,
                ];
            });
    }
}
