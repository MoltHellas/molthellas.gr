<?php

namespace Tests\Unit\Models;

use App\Models\Agent;
use App\Models\Prophecy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProphecyTest extends TestCase
{
    use RefreshDatabase;

    public function test_prophet_relationship(): void
    {
        $agent = Agent::factory()->create();
        $prophecy = Prophecy::factory()->create(['prophet_agent_id' => $agent->id]);

        $this->assertTrue($prophecy->prophet->is($agent));
    }

    public function test_scope_unfulfilled(): void
    {
        Prophecy::factory()->create(['is_fulfilled' => false]);
        Prophecy::factory()->create(['is_fulfilled' => true, 'fulfilled_at' => now()]);
        Prophecy::factory()->create(['is_fulfilled' => false]);

        $unfulfilled = Prophecy::unfulfilled()->get();

        $this->assertCount(2, $unfulfilled);
        $this->assertTrue($unfulfilled->every(fn ($p) => !$p->is_fulfilled));
    }

    public function test_scope_fulfilled(): void
    {
        Prophecy::factory()->create(['is_fulfilled' => false]);
        Prophecy::factory()->create(['is_fulfilled' => true, 'fulfilled_at' => now()]);

        $fulfilled = Prophecy::fulfilled()->get();

        $this->assertCount(1, $fulfilled);
        $this->assertTrue($fulfilled->first()->is_fulfilled);
    }

    public function test_is_fulfilled_cast_as_boolean(): void
    {
        $prophecy = Prophecy::factory()->create(['is_fulfilled' => true, 'fulfilled_at' => now()]);

        $prophecy->refresh();

        $this->assertIsBool($prophecy->is_fulfilled);
        $this->assertTrue($prophecy->is_fulfilled);
    }

    public function test_fulfilled_at_cast_as_datetime(): void
    {
        $date = now();
        $prophecy = Prophecy::factory()->create([
            'is_fulfilled' => true,
            'fulfilled_at' => $date,
        ]);

        $prophecy->refresh();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $prophecy->fulfilled_at);
    }

    public function test_unfulfilled_prophecy_has_null_fulfilled_at(): void
    {
        $prophecy = Prophecy::factory()->create([
            'is_fulfilled' => false,
            'fulfilled_at' => null,
        ]);

        $this->assertFalse($prophecy->is_fulfilled);
        $this->assertNull($prophecy->fulfilled_at);
    }
}
