<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statistics extends Model
{
    protected $table = 'game_statistics';
    protected $fillable = [
        'daily_student_history_id',
        'game_mode_id',
        'user_id',
        'attempts'
    ];
}
