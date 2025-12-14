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
        Schema::create('hard_mode_clues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_student_history_id')
                ->constrained('daily_student_history')
                ->cascadeOnDelete();
            $table->unsignedInteger('display_order');
            $table->string('label');
            $table->text('value')->nullable();
            $table->string('difficulty');
            $table->string('field')->nullable();
            $table->string('pair')->nullable();
            $table->timestamps();

            $table->unique(['daily_student_history_id', 'display_order'], 'daily_clues_unique_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hard_mode_clues');
    }
};
