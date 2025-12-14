<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_statistics', function (Blueprint $table) {
            $table->id();
            $table->uuid('guest_token')->index();
            $table->foreignId('daily_student_history_id')
                ->constrained('daily_student_history')
                ->cascadeOnDelete();
            $table->foreignId('game_mode_id')
                ->constrained('game_modes');
            $table->unsignedInteger('attempts');
            $table->timestamps();

            $table->unique(['guest_token', 'daily_student_history_id'], 'guest_stats_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_statistics');
    }
};
