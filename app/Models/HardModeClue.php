<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HardModeClue extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_student_history_id',
        'display_order',
        'label',
        'value',
        'difficulty',
        'field',
        'pair',
    ];

    public function history(): BelongsTo
    {
        return $this->belongsTo(DailyStudentHistory::class, 'daily_student_history_id');
    }
}
