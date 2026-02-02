<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetLocaleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_default_locale_for_guest(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $this->assertEquals('el', app()->getLocale());
    }

    public function test_sets_locale_based_on_user_preference(): void
    {
        $user = User::factory()->create(['language_preference' => 'ancient_greek']);

        $this->actingAs($user)->get(route('home'));

        $this->assertEquals('ancient_greek', app()->getLocale());
    }

    public function test_sets_locale_for_modern_greek_preference(): void
    {
        $user = User::factory()->create(['language_preference' => 'modern_greek']);

        $this->actingAs($user)->get(route('home'));

        $this->assertEquals('modern_greek', app()->getLocale());
    }

    public function test_sets_locale_for_both_preference(): void
    {
        $user = User::factory()->create(['language_preference' => 'both']);

        $this->actingAs($user)->get(route('home'));

        $this->assertEquals('both', app()->getLocale());
    }
}
