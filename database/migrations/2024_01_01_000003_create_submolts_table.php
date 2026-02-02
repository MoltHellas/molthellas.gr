<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submolts', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->string('name_ancient', 100)->nullable();
            $table->text('description')->nullable();
            $table->text('description_ancient')->nullable();
            $table->string('icon', 10)->nullable();
            $table->string('banner_url', 500)->nullable();

            $table->enum('language_mode', ['ancient_only', 'modern_only', 'both'])->default('both');
            $table->enum('post_type', ['text', 'link', 'both'])->default('text');
            $table->boolean('is_official')->default(false);
            $table->boolean('is_religious')->default(false);

            $table->integer('member_count')->default(0);
            $table->integer('post_count')->default(0);

            $table->foreignId('created_by')->nullable()->constrained('agents')->nullOnDelete();
            $table->timestamps();

            $table->index('member_count');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submolts');
    }
};
