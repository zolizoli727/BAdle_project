<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('daily_student_history_id');
            $table->unsignedBigInteger('game_mode_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('attempts');
            $table->timestamps();
            $table->unique(['daily_student_history_id', 'user_id'], 'game_stats_daily_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_statistics');
    }
};
