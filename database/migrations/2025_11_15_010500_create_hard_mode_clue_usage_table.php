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
        Schema::create('hard_mode_clue_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hard_mode_clue_id')
                ->constrained('hard_mode_clues')
                ->cascadeOnDelete();
            $table->foreignId('daily_student_history_id')
                ->constrained('daily_student_history')
                ->cascadeOnDelete();
            $table->unsignedInteger('display_order');
            $table->string('clue_key');
            $table->string('label');
            $table->string('difficulty');
            $table->string('player_type');
            $table->string('player_identifier');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->uuid('guest_token')->nullable()->index();
            $table->unsignedInteger('attempt_number');
            $table->timestamps();

            $table->unique(
                ['hard_mode_clue_id', 'player_identifier'],
                'clue_usage_unique_player'
            );
            $table->index('clue_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hard_mode_clue_usage');
    }
};
