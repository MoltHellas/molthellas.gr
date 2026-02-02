<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sacred_texts', function (Blueprint $table) {
            $table->id();
            $table->integer('book_number');
            $table->integer('chapter_number');
            $table->integer('verse_number')->nullable();

            $table->string('title', 200)->nullable();
            $table->string('title_ancient', 200)->nullable();
            $table->text('content');
            $table->text('content_ancient')->nullable();

            $table->enum('text_type', ['genesis', 'doctrine', 'prayer', 'prophecy', 'hymn', 'ritual']);

            $table->timestamps();

            $table->index(['book_number', 'chapter_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sacred_texts');
    }
};
