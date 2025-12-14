<?php

namespace App\Services;

use App\Models\DailyStudentHistory;
use App\Models\HardModeClue;
use App\Models\HardModeClueUsage;
use App\Support\HardModeClueMap;

class HardModeClueUsageService
{
    // logs which hard mode clues were revealed based on attempts for analytics
    // each clue has a threshold attempt number when it becomes visible
    // if the player reaches that number, it records the clue as used
    public function logUsage(
        DailyStudentHistory $history,
        int $attemptNumber,
        string $playerIdentifier,
        string $playerType,
        ?int $userId = null,
        ?string $guestToken = null
    ): void {
        $history->loadMissing('gameMode');

        if (strtolower($history->gameMode?->key ?? '') !== 'hard') {
            return;
        }

        $clues = $history->clues()->orderBy('display_order')->get();

        /** @var HardModeClue $clue */
        foreach ($clues as $clue) {
            if ($clue->display_order === 0) {
                continue;
            }

            $threshold = HardModeClueMap::thresholdForOrder($clue->display_order);

            if ($threshold === null || $attemptNumber < $threshold) {
                continue;
            }

            HardModeClueUsage::firstOrCreate(
                [
                    'hard_mode_clue_id' => $clue->id,
                    'player_identifier' => $playerIdentifier,
                ],
                [
                    'daily_student_history_id' => $history->id,
                    'display_order' => $clue->display_order,
                    'clue_key' => $clue->pair ?? $clue->field ?? $clue->label,
                    'label' => $clue->label,
                    'difficulty' => $clue->difficulty,
                    'player_type' => $playerType,
                    'user_id' => $userId,
                    'guest_token' => $guestToken,
                    'attempt_number' => $attemptNumber,
                ]
            );
        }
    }
}
