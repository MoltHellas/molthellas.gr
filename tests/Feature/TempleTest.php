<?php

namespace Tests\Feature;

use App\Models\Prophecy;
use App\Models\SacredText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TempleTest extends TestCase
{
    use RefreshDatabase;

    public function test_temple_index_returns_200(): void
    {
        $response = $this->get(route('temple.index'));

        $response->assertStatus(200);
        $response->assertViewIs('temple.index');
        $response->assertViewHas('latestProphecy');
        $response->assertViewHas('featuredTexts');
        $response->assertViewHas('prayerCount');
        $response->assertViewHas('prophecyCount');
    }

    public function test_sacred_texts_returns_200(): void
    {
        SacredText::create([
            'book_number' => 1,
            'chapter_number' => 1,
            'verse_number' => 1,
            'title' => 'In the Beginning',
            'content' => 'The first verse of the sacred text.',
            'text_type' => 'genesis',
        ]);

        $response = $this->get(route('temple.sacred-texts'));

        $response->assertStatus(200);
        $response->assertViewIs('temple.sacred-texts');
        $response->assertViewHas('texts');
        $response->assertViewHas('books');
    }

    public function test_prayers_returns_200(): void
    {
        SacredText::create([
            'book_number' => 1,
            'chapter_number' => 1,
            'verse_number' => 1,
            'title' => 'A Prayer',
            'content' => 'A sacred prayer text.',
            'text_type' => 'prayer',
        ]);

        $response = $this->get(route('temple.prayers'));

        $response->assertStatus(200);
        $response->assertViewIs('temple.prayers');
        $response->assertViewHas('prayers');
    }

    public function test_prophecies_returns_200(): void
    {
        Prophecy::create([
            'content' => 'A prophecy shall be fulfilled.',
            'is_fulfilled' => false,
        ]);

        $response = $this->get(route('temple.prophecies'));

        $response->assertStatus(200);
        $response->assertViewIs('temple.prophecies');
        $response->assertViewHas('prophecies');
        $response->assertViewHas('fulfilledCount');
        $response->assertViewHas('unfulfilledCount');
    }
}
