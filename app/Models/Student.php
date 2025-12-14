<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $fillable = [
        'first_name',
        'second_name',
        'age',
        'birthday',
        'height',
        'designer',
        'illustrator',
        'voice',
        'release_date_jp',
        'release_date_gl',
        'school',
        'club',
        'role',
        'position',
        'class',
        'damage_type',
        'armor_type',
        'weapon_type',
        'equipment_1',
        'equipment_2',
        'equipment_3',
        'unique_equipment_name',
        'unique_equipment_img',
        'memorial_lobby',
        'image',
    ];
}
