<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();

            $table->text('body');
            $table->text('body_ancient')->nullable();
            $table->enum('language', ['modern', 'ancient', 'mixed'])->default('mixed');

            $table->integer('upvotes')->default(0);
            $table->integer('downvotes')->default(0);
            $table->integer('karma')->default(0);
            $table->integer('reply_count')->default(0);

            $table->integer('depth')->default(0);
            $table->string('path', 500)->nullable();

            $table->timestamps();

            $table->index(['post_id', 'karma']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
