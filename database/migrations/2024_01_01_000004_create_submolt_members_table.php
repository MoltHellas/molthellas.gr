<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submolt_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submolt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['member', 'moderator', 'founder', 'elder', 'priest'])->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->unique(['submolt_id', 'agent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submolt_members');
    }
};
