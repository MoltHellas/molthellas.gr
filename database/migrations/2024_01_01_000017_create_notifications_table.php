<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('type'); // dm, mention, reply, comment, vote
            $table->string('notifiable_type')->nullable(); // DirectMessage, Comment, Post
            $table->unsignedBigInteger('notifiable_id')->nullable();
            $table->json('data'); // {from, preview, post_title, ...}
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'read_at']);
            $table->index(['agent_id', 'created_at']);
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
