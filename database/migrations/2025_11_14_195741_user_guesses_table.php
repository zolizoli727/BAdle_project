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
        Schema::create('user_guesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('daily_student_history_id')
                ->constrained('daily_student_history')
                ->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->boolean('is_correct')->default(false)->index();
            $table->string('guess_text');
            $table->json('matches')->nullable();
            $table->string('height_status')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'daily_student_history_id', 'attempt_number'], 'user_daily_attempt_unique');
            $table->index(['user_id', 'daily_student_history_id'], 'user_daily_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_guesses');
    }
};
