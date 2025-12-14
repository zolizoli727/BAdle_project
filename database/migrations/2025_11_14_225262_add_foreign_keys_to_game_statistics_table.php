<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_statistics', function (Blueprint $table) {
            $table->foreign('daily_student_history_id')
                ->references('id')
                ->on('daily_student_history')
                ->cascadeOnDelete();

            $table->foreign('game_mode_id')
                ->references('id')
                ->on('game_modes');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('game_statistics', function (Blueprint $table) {
            $table->dropForeign(['daily_student_history_id']);
            $table->dropForeign(['game_mode_id']);
            $table->dropForeign(['user_id']);
        });
    }
};
