<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Student;
use App\Models\DailyStudentHistory;

class GuestGuess extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_token',
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function history(): BelongsTo
    {
        return $this->belongsTo(DailyStudentHistory::class, 'daily_student_history_id');
    }
}
