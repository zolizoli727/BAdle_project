<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\HardModeClue;

class DailyStudentHistory extends Model
{
    use HasFactory;

    protected $table = 'daily_student_history';

    protected $fillable = [
        'played_on',
        'game_mode_id',
        'student_id',
    ];

    protected $casts = [
        'played_on' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function gameMode(): BelongsTo
    {
        return $this->belongsTo(GameMode::class);
    }

    public function guesses(): HasMany
    {
        return $this->hasMany(UserGuess::class);
    }

    public function clues(): HasMany
    {
        return $this->hasMany(HardModeClue::class, 'daily_student_history_id')
            ->orderBy('display_order');
    }
}
