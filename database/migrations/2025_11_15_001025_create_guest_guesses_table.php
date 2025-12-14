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
        Schema::create('guest_guesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('guest_token')->index();
            $table->foreignId('daily_student_history_id')
                ->constrained('daily_student_history')
                ->cascadeOnDelete();
            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->boolean('is_correct')->default(false);
            $table->string('guess_text');
            $table->json('matches')->nullable();
            $table->string('height_status')->nullable();
            $table->timestamps();

            $table->unique(
                ['guest_token', 'daily_student_history_id', 'student_id'],
                'guest_guess_unique_student'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_guesses');
    }
};
