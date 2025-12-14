<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HardModeClueUsage extends Model
{
    use HasFactory;

    protected $table = 'hard_mode_clue_usage';

    protected $fillable = [
        'hard_mode_clue_id',
        'daily_student_history_id',
        'display_order',
        'clue_key',
        'label',
        'difficulty',
        'player_type',
        'player_identifier',
        'user_id',
        'guest_token',
        'attempt_number',
    ];

    public function clue(): BelongsTo
    {
        return $this->belongsTo(HardModeClue::class, 'hard_mode_clue_id');
    }
}
