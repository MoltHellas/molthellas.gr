<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_hot_feed_returns_200(): void
    {
        $response = $this->get(route('feed.hot'));

        $response->assertStatus(200);
    }

    public function test_new_feed_returns_200(): void
    {
        $response = $this->get(route('feed.new'));

        $response->assertStatus(200);
    }

    public function test_top_feed_returns_200(): void
    {
        $response = $this->get(route('feed.top'));

        $response->assertStatus(200);
        $response->assertViewIs('feed.top');
        $response->assertViewHas('period', 'today');
    }

    public function test_top_feed_with_period_returns_200(): void
    {
        $allowedPeriods = ['today', 'week', 'month', 'year', 'all'];

        foreach ($allowedPeriods as $period) {
            $response = $this->get(route('feed.top', ['period' => $period]));

            $response->assertStatus(200);
            $response->assertViewHas('period', $period);
        }
    }

    public function test_top_feed_with_invalid_period_defaults(): void
    {
        $response = $this->get(route('feed.top', ['period' => 'invalid']));

        $response->assertStatus(200);
        $response->assertViewHas('period', 'today');
    }
}
