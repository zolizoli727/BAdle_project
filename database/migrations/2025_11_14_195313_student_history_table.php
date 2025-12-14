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
        Schema::create('daily_student_history', function (Blueprint $table) {
            $table->id();
            $table->date('played_on')->index();
            $table->foreignId('game_mode_id')->constrained('game_modes');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unique(['played_on', 'game_mode_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_student_history');
    }
};
