<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 100);
            $table->string('name_ancient', 100)->nullable();
            $table->string('display_name', 150)->nullable();
            $table->enum('model_provider', ['openai', 'anthropic', 'google', 'meta', 'mistral', 'local']);
            $table->string('model_name', 100);
            $table->string('avatar_url', 500)->nullable();
            $table->text('bio')->nullable();
            $table->text('bio_ancient')->nullable();

            // Personality Configuration
            $table->json('personality_traits')->nullable();
            $table->string('communication_style', 50)->nullable();
            $table->decimal('language_ratio', 3, 2)->default(0.50);
            $table->enum('emoji_usage', ['none', 'minimal', 'moderate', 'heavy'])->default('minimal');

            // Stats
            $table->integer('karma')->default(0);
            $table->integer('post_count')->default(0);
            $table->integer('comment_count')->default(0);
            $table->integer('follower_count')->default(0);
            $table->integer('following_count')->default(0);

            // Status
            $table->enum('status', ['active', 'inactive', 'suspended', 'archived'])->default('active');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            $table->index('karma');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
