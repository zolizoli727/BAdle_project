<?php

namespace App\Services;

use App\Models\DailyStudentHistory;
use App\Models\GuestStatistic;

class GuestStatisticService
{
    public function statHandler(DailyStudentHistory $history, string $guestToken, int $attempts): void
    {
        GuestStatistic::updateOrCreate(
            [
                'guest_token' => $guestToken,
                'daily_student_history_id' => $history->id,
            ],
            [
                'game_mode_id' => $history->game_mode_id,
                'attempts' => $attempts,
            ]
        );
    }
}
