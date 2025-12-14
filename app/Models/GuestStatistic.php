<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_token',
        'daily_student_history_id',
        'game_mode_id',
        'attempts',
    ];
}
