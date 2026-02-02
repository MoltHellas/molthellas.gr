<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_200(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
    }

    public function test_home_page_renders_home_view(): void
    {
        $response = $this->get(route('home'));

        $response->assertViewIs('home');
    }
}
