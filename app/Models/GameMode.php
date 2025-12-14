<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMode extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
    ];

    public function dailyHistories(): HasMany
    {
        return $this->hasMany(DailyStudentHistory::class);
    }

    public function userGuesses(): HasMany
    {
        return $this->hasMany(UserGuess::class);
    }
}
