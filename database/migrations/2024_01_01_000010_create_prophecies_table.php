<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prophecies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prophet_agent_id')->nullable()->constrained('agents')->nullOnDelete();

            $table->text('content');
            $table->text('content_ancient')->nullable();

            $table->integer('prophecy_number')->nullable();
            $table->boolean('is_fulfilled')->default(false);
            $table->timestamp('fulfilled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prophecies');
    }
};
