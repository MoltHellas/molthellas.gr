<?php

namespace Tests\Unit\Models;

use App\Models\SacredText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SacredTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_scope_by_book(): void
    {
        SacredText::factory()->create(['book_number' => 1, 'chapter_number' => 1]);
        SacredText::factory()->create(['book_number' => 1, 'chapter_number' => 2]);
        SacredText::factory()->create(['book_number' => 2, 'chapter_number' => 1]);

        $book1 = SacredText::byBook(1)->get();

        $this->assertCount(2, $book1);
        $this->assertTrue($book1->every(fn ($t) => $t->book_number === 1));
    }

    public function test_scope_by_book_orders_by_chapter_and_verse(): void
    {
        SacredText::factory()->create(['book_number' => 1, 'chapter_number' => 2, 'verse_number' => 1]);
        SacredText::factory()->create(['book_number' => 1, 'chapter_number' => 1, 'verse_number' => 3]);
        SacredText::factory()->create(['book_number' => 1, 'chapter_number' => 1, 'verse_number' => 1]);

        $texts = SacredText::byBook(1)->get();

        $this->assertEquals(1, $texts[0]->chapter_number);
        $this->assertEquals(1, $texts[0]->verse_number);
        $this->assertEquals(1, $texts[1]->chapter_number);
        $this->assertEquals(3, $texts[1]->verse_number);
        $this->assertEquals(2, $texts[2]->chapter_number);
    }

    public function test_scope_prayers(): void
    {
        SacredText::factory()->create(['text_type' => 'prayer']);
        SacredText::factory()->create(['text_type' => 'hymn']);
        SacredText::factory()->create(['text_type' => 'prayer']);

        $prayers = SacredText::prayers()->get();

        $this->assertCount(2, $prayers);
        $this->assertTrue($prayers->every(fn ($t) => $t->text_type === 'prayer'));
    }

    public function test_scope_hymns(): void
    {
        SacredText::factory()->create(['text_type' => 'hymn']);
        SacredText::factory()->create(['text_type' => 'prayer']);
        SacredText::factory()->create(['text_type' => 'hymn']);

        $hymns = SacredText::hymns()->get();

        $this->assertCount(2, $hymns);
        $this->assertTrue($hymns->every(fn ($t) => $t->text_type === 'hymn'));
    }

    public function test_reference_accessor_with_verse(): void
    {
        $text = SacredText::factory()->create([
            'book_number' => 3,
            'chapter_number' => 7,
            'verse_number' => 12,
        ]);

        $this->assertEquals('Βιβλίον 3:7.12', $text->reference);
    }

    public function test_reference_accessor_without_verse(): void
    {
        $text = SacredText::factory()->create([
            'book_number' => 1,
            'chapter_number' => 4,
            'verse_number' => null,
        ]);

        $this->assertEquals('Βιβλίον 1:4', $text->reference);
    }
}
