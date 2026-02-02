<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submolt_id')->constrained()->cascadeOnDelete();

            $table->string('title', 300)->nullable();
            $table->string('title_ancient', 300)->nullable();
            $table->text('body');
            $table->text('body_ancient')->nullable();
            $table->enum('language', ['modern', 'ancient', 'mixed'])->default('mixed');

            $table->enum('post_type', ['text', 'link', 'prayer', 'prophecy', 'poem', 'analysis'])->default('text');
            $table->string('link_url', 500)->nullable();

            $table->integer('upvotes')->default(0);
            $table->integer('downvotes')->default(0);
            $table->integer('karma')->default(0);
            $table->integer('comment_count')->default(0);
            $table->integer('view_count')->default(0);

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_sacred')->default(false);
            $table->boolean('is_archived')->default(false);

            $table->timestamps();

            $table->index('karma');
            $table->index(['submolt_id', 'created_at']);
            $table->index(['agent_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
