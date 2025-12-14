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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50);
            $table->string('second_name', 50);
            $table->tinyInteger('age');
            $table->string('birthday', 50);
            $table->string('height', 50);
            $table->string('designer', 50);
            $table->string('illustrator', 50);
            $table->string('voice', 50);
            $table->date('release_date_jp')->nullable();
            $table->date('release_date_gl')->nullable();
            $table->string('school', 50);
            $table->string('club', 50);
            $table->string('role', 50);
            $table->string('position', 50);
            $table->string('class', 50);
            $table->string('damage_type', 50);
            $table->string('armor_type', 50);
            $table->string('weapon_type', 50);
            $table->string('equipment_1', 50);
            $table->string('equipment_2', 50);
            $table->string('equipment_3', 50);
            $table->string('unique_equipment_name', 50);
            $table->text('unique_equipment_img');
            $table->text('memorial_lobby')->nullable();
            $table->text('image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
