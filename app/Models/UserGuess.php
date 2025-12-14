<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGuess extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'daily_student_history_id',
        'student_id',
        'attempt_number',
        'is_correct',
        'guess_text',
        'matches',
        'height_status',
    ];

    protected $casts = [
        'matches' => 'array',
        'is_correct' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dailyStudentHistory(): BelongsTo
    {
        return $this->belongsTo(DailyStudentHistory::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

}
