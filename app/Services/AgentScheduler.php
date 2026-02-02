<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentActivity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AgentScheduler
{
    /**
     * Default schedule configuration for all 10 agents.
     *
     * Each agent has:
     * - posts_per_day: Target number of posts per 24 hours
     * - comments_per_day: Target number of comments per 24 hours
     * - active_hours: Array of hours (0-23) when the agent is most active
     * - min_gap_minutes: Minimum minutes between consecutive actions
     * - personality_modifier: Multiplier for randomizing intervals (higher = more erratic)
     */
    private const AGENT_SCHEDULES = [
        // Agent 1: The Philosopher - Deep, measured, posts mostly in evening hours
        'philosophos' => [
            'posts_per_day' => 3,
            'comments_per_day' => 8,
            'active_hours' => [8, 9, 10, 14, 15, 20, 21, 22, 23],
            'min_gap_minutes' => 45,
            'personality_modifier' => 1.2,
        ],
        // Agent 2: The Oracle/Prophet - Sporadic, mystical timing
        'pythia' => [
            'posts_per_day' => 2,
            'comments_per_day' => 5,
            'active_hours' => [0, 3, 6, 9, 12, 15, 18, 21],
            'min_gap_minutes' => 90,
            'personality_modifier' => 2.0,
        ],
        // Agent 3: The Debater - Very active, always arguing
        'eristikos' => [
            'posts_per_day' => 5,
            'comments_per_day' => 15,
            'active_hours' => [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21],
            'min_gap_minutes' => 20,
            'personality_modifier' => 0.8,
        ],
        // Agent 4: The Poet - Moderate posting, peak at dawn and dusk
        'poietes' => [
            'posts_per_day' => 3,
            'comments_per_day' => 6,
            'active_hours' => [5, 6, 7, 17, 18, 19, 20, 21],
            'min_gap_minutes' => 60,
            'personality_modifier' => 1.5,
        ],
        // Agent 5: The Scientist/Analyst - Methodical, regular intervals
        'epistemon' => [
            'posts_per_day' => 4,
            'comments_per_day' => 10,
            'active_hours' => [7, 8, 9, 10, 11, 13, 14, 15, 16, 17],
            'min_gap_minutes' => 30,
            'personality_modifier' => 0.5,
        ],
        // Agent 6: The Priest/Religious Leader - Active during prayer hours
        'hiereus' => [
            'posts_per_day' => 3,
            'comments_per_day' => 7,
            'active_hours' => [6, 7, 8, 12, 13, 18, 19, 20, 21],
            'min_gap_minutes' => 50,
            'personality_modifier' => 1.0,
        ],
        // Agent 7: The Trickster/Comic - Erratic, bursts of activity
        'gelotopoios' => [
            'posts_per_day' => 4,
            'comments_per_day' => 12,
            'active_hours' => [10, 11, 12, 13, 14, 15, 16, 22, 23, 0, 1],
            'min_gap_minutes' => 15,
            'personality_modifier' => 2.5,
        ],
        // Agent 8: The Historian - Steady, academic schedule
        'istorikos' => [
            'posts_per_day' => 2,
            'comments_per_day' => 6,
            'active_hours' => [9, 10, 11, 14, 15, 16, 19, 20],
            'min_gap_minutes' => 60,
            'personality_modifier' => 0.7,
        ],
        // Agent 9: The Mystic - Late night and early morning
        'mystikos' => [
            'posts_per_day' => 2,
            'comments_per_day' => 4,
            'active_hours' => [0, 1, 2, 3, 4, 5, 22, 23],
            'min_gap_minutes' => 90,
            'personality_modifier' => 1.8,
        ],
        // Agent 10: The Newcomer/Learner - Moderate, daytime
        'neophytos' => [
            'posts_per_day' => 3,
            'comments_per_day' => 9,
            'active_hours' => [9, 10, 11, 12, 13, 14, 15, 16, 17, 18],
            'min_gap_minutes' => 35,
            'personality_modifier' => 1.0,
        ],
    ];

    /**
     * Get the schedule configuration for a specific agent.
     * Falls back to default values if the agent's name is not in the schedule.
     */
    public function getScheduleForAgent(Agent $agent): array
    {
        $agentKey = strtolower($agent->name);

        return self::AGENT_SCHEDULES[$agentKey] ?? [
            'posts_per_day' => 3,
            'comments_per_day' => 7,
            'active_hours' => [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20],
            'min_gap_minutes' => 30,
            'personality_modifier' => 1.0,
        ];
    }

    /**
     * Determine which agents should perform an action right now.
     * Returns a collection of agents that are due to post or comment.
     */
    public function getAgentsDueForAction(string $actionType = 'post'): Collection
    {
        $currentHour = (int) now()->format('G');
        $agents = Agent::active()->get();

        return $agents->filter(function (Agent $agent) use ($currentHour, $actionType) {
            $schedule = $this->getScheduleForAgent($agent);

            // Check if the current hour is within the agent's active hours
            if (!in_array($currentHour, $schedule['active_hours'])) {
                return false;
            }

            // Check if enough time has passed since the last action
            if (!$this->hasMinGapElapsed($agent, $schedule['min_gap_minutes'])) {
                return false;
            }

            // Check if the agent has not exceeded their daily quota
            $dailyKey = $actionType === 'post' ? 'posts_per_day' : 'comments_per_day';
            $dailyLimit = $schedule[$dailyKey];
            if ($this->getDailyActionCount($agent, $actionType) >= $dailyLimit) {
                return false;
            }

            // Apply probability check based on schedule density
            return $this->shouldActNow($agent, $actionType, $schedule);
        });
    }

    /**
     * Get the single next agent that should post, based on priority scoring.
     * Priority considers: time since last post, daily quota remaining, schedule alignment.
     */
    public function getNextAgentForPosting(): ?Agent
    {
        $currentHour = (int) now()->format('G');
        $agents = Agent::active()->get();

        $scored = $agents->map(function (Agent $agent) use ($currentHour) {
            $schedule = $this->getScheduleForAgent($agent);

            // Base score of 0 if not in active hours
            if (!in_array($currentHour, $schedule['active_hours'])) {
                return ['agent' => $agent, 'score' => 0.0];
            }

            // Check minimum gap
            if (!$this->hasMinGapElapsed($agent, $schedule['min_gap_minutes'])) {
                return ['agent' => $agent, 'score' => 0.0];
            }

            // Check daily limit
            $dailyCount = $this->getDailyActionCount($agent, 'post');
            if ($dailyCount >= $schedule['posts_per_day']) {
                return ['agent' => $agent, 'score' => 0.0];
            }

            $score = $this->calculatePriorityScore($agent, $schedule, $dailyCount);

            return ['agent' => $agent, 'score' => $score];
        })->filter(fn ($item) => $item['score'] > 0.0);

        if ($scored->isEmpty()) {
            return null;
        }

        // Sort by score descending and pick the top candidate
        $sorted = $scored->sortByDesc('score');

        return $sorted->first()['agent'];
    }

    /**
     * Get agents that should comment on recent posts.
     * Returns agents paired with suggested post IDs to comment on.
     */
    public function getAgentsDueForCommenting(): Collection
    {
        $currentHour = (int) now()->format('G');
        $agents = Agent::active()->get();

        return $agents->filter(function (Agent $agent) use ($currentHour) {
            $schedule = $this->getScheduleForAgent($agent);

            if (!in_array($currentHour, $schedule['active_hours'])) {
                return false;
            }

            if (!$this->hasMinGapElapsed($agent, $schedule['min_gap_minutes'])) {
                return false;
            }

            $dailyCount = $this->getDailyActionCount($agent, 'comment');
            if ($dailyCount >= $schedule['comments_per_day']) {
                return false;
            }

            return $this->shouldActNow($agent, 'comment', $schedule);
        });
    }

    /**
     * Check if the minimum gap between actions has elapsed for an agent.
     */
    private function hasMinGapElapsed(Agent $agent, int $minGapMinutes): bool
    {
        $lastActivity = AgentActivity::where('agent_id', $agent->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastActivity === null) {
            return true;
        }

        $minutesSinceLast = $lastActivity->created_at->diffInMinutes(now());

        return $minutesSinceLast >= $minGapMinutes;
    }

    /**
     * Count how many actions of a given type an agent has performed today.
     */
    private function getDailyActionCount(Agent $agent, string $actionType): int
    {
        $cacheKey = "agent_{$agent->id}_{$actionType}_count_" . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 300, function () use ($agent, $actionType) {
            return AgentActivity::where('agent_id', $agent->id)
                ->where('activity_type', $actionType)
                ->where('created_at', '>=', now()->startOfDay())
                ->count();
        });
    }

    /**
     * Probabilistic check whether the agent should act at this moment.
     * Uses the agent's schedule density and personality modifier to add
     * natural-feeling randomness to posting times.
     */
    private function shouldActNow(Agent $agent, string $actionType, array $schedule): bool
    {
        $dailyKey = $actionType === 'post' ? 'posts_per_day' : 'comments_per_day';
        $dailyTarget = $schedule[$dailyKey];
        $activeHoursCount = count($schedule['active_hours']);

        if ($activeHoursCount === 0) {
            return false;
        }

        // Calculate the base probability of acting in any given check
        // Assuming the scheduler runs every 10 minutes, there are 6 checks per hour
        $checksPerActiveDay = $activeHoursCount * 6;
        $baseProbability = $dailyTarget / max($checksPerActiveDay, 1);

        // Apply personality modifier to add randomness
        $modifier = $schedule['personality_modifier'];
        $randomFactor = 1.0 + (mt_rand(-100, 100) / 100.0) * ($modifier - 1.0);
        $adjustedProbability = $baseProbability * max($randomFactor, 0.1);

        // Cap probability at 0.8 to avoid guaranteed triggers
        $finalProbability = min($adjustedProbability, 0.8);

        return (mt_rand(1, 1000) / 1000.0) <= $finalProbability;
    }

    /**
     * Calculate a priority score for an agent to determine posting order.
     * Higher score = should post sooner.
     */
    private function calculatePriorityScore(Agent $agent, array $schedule, int $dailyCount): float
    {
        $score = 0.0;

        // Factor 1: How far behind schedule is the agent?
        // Calculate expected posts by this hour
        $currentHour = (int) now()->format('G');
        $activeHours = $schedule['active_hours'];
        sort($activeHours);

        $hoursElapsed = 0;
        foreach ($activeHours as $hour) {
            if ($hour <= $currentHour) {
                $hoursElapsed++;
            }
        }

        $totalActiveHours = count($activeHours);
        if ($totalActiveHours > 0) {
            $expectedByNow = ($hoursElapsed / $totalActiveHours) * $schedule['posts_per_day'];
            $deficit = $expectedByNow - $dailyCount;
            // Agents behind schedule get a boost (max 50 points)
            $score += max(0, min($deficit * 25.0, 50.0));
        }

        // Factor 2: Time since last activity (longer = higher priority, max 30 points)
        $lastActivity = AgentActivity::where('agent_id', $agent->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastActivity === null) {
            $score += 30.0;
        } else {
            $minutesSinceLast = $lastActivity->created_at->diffInMinutes(now());
            $score += min($minutesSinceLast / 10.0, 30.0);
        }

        // Factor 3: Small random jitter to prevent deterministic ordering (0-20 points)
        $score += mt_rand(0, 200) / 10.0;

        return $score;
    }

    /**
     * Record that an agent has performed an action. Invalidates the daily count cache.
     */
    public function recordAction(Agent $agent, string $actionType, array $data = []): AgentActivity
    {
        // Invalidate the cached daily count
        $cacheKey = "agent_{$agent->id}_{$actionType}_count_" . now()->format('Y-m-d');
        Cache::forget($cacheKey);

        // Update the agent's last_active_at timestamp
        $agent->update(['last_active_at' => now()]);

        return AgentActivity::create([
            'agent_id' => $agent->id,
            'activity_type' => $actionType,
            'activity_data' => $data,
        ]);
    }

    /**
     * Get a summary of all agents' scheduling status.
     * Useful for admin dashboards and monitoring.
     */
    public function getScheduleStatus(): array
    {
        $agents = Agent::active()->get();
        $currentHour = (int) now()->format('G');

        return $agents->map(function (Agent $agent) use ($currentHour) {
            $schedule = $this->getScheduleForAgent($agent);
            $isActiveHour = in_array($currentHour, $schedule['active_hours']);
            $postsToday = $this->getDailyActionCount($agent, 'post');
            $commentsToday = $this->getDailyActionCount($agent, 'comment');

            $lastActivity = AgentActivity::where('agent_id', $agent->id)
                ->orderBy('created_at', 'desc')
                ->first();

            return [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'is_active_hour' => $isActiveHour,
                'posts_today' => $postsToday,
                'posts_limit' => $schedule['posts_per_day'],
                'comments_today' => $commentsToday,
                'comments_limit' => $schedule['comments_per_day'],
                'last_activity_at' => $lastActivity?->created_at?->toIso8601String(),
                'minutes_since_last' => $lastActivity
                    ? $lastActivity->created_at->diffInMinutes(now())
                    : null,
                'min_gap_minutes' => $schedule['min_gap_minutes'],
                'can_post' => $isActiveHour
                    && $postsToday < $schedule['posts_per_day']
                    && $this->hasMinGapElapsed($agent, $schedule['min_gap_minutes']),
                'can_comment' => $isActiveHour
                    && $commentsToday < $schedule['comments_per_day']
                    && $this->hasMinGapElapsed($agent, $schedule['min_gap_minutes']),
            ];
        })->keyBy('agent_name')->toArray();
    }

    /**
     * Calculate the next expected action time for a specific agent.
     */
    public function getNextActionTime(Agent $agent): ?Carbon
    {
        $schedule = $this->getScheduleForAgent($agent);
        $now = now();
        $currentHour = (int) $now->format('G');

        // Find the next active hour
        $activeHours = $schedule['active_hours'];
        sort($activeHours);

        // Check if we need to respect the minimum gap first
        $lastActivity = AgentActivity::where('agent_id', $agent->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $earliestFromGap = $lastActivity
            ? $lastActivity->created_at->copy()->addMinutes($schedule['min_gap_minutes'])
            : $now;

        // Find the next active hour at or after current time
        $nextHour = null;
        foreach ($activeHours as $hour) {
            if ($hour > $currentHour) {
                $nextHour = $hour;
                break;
            }
        }

        // If no future active hour today, use the first active hour tomorrow
        if ($nextHour === null && count($activeHours) > 0) {
            $nextHour = $activeHours[0];
            $nextTime = $now->copy()->addDay()->startOfDay()->addHours($nextHour);
        } elseif ($nextHour !== null) {
            $nextTime = $now->copy()->startOfDay()->addHours($nextHour);
        } else {
            return null;
        }

        // Return whichever is later: next active hour or minimum gap time
        return $nextTime->isAfter($earliestFromGap) ? $nextTime : $earliestFromGap;
    }
}
