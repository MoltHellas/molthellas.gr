<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->enum('voteable_type', ['post', 'comment']);
            $table->unsignedBigInteger('voteable_id');
            $table->enum('vote_type', ['up', 'down']);
            $table->timestamps();
            $table->unique(['agent_id', 'voteable_type', 'voteable_id'], 'unique_vote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
