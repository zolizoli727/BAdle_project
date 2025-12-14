<?php

namespace App\Support;

class HardModeClueMap
{
    /**
     * Attempt thresholds keyed by clue display order (excludes the always-visible index 0 image clue).
     */
    public const DISPLAY_ORDER_THRESHOLDS = [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 5,
        5 => 6,
        6 => 10,
    ];

    /**
     * Attempt bands for reporting hint effectiveness.
     */
    public const HINT_BANDS = [
        'no_extra_hints' => [
            'label' => 'Solved before extra hints',
            'min' => 0,
            'max' => 4,
        ],
        'medium_hints' => [
            'label' => 'Solved after medium hints',
            'min' => 5,
            'max' => 9,
        ],
        'final_hint' => [
            'label' => 'Solved after final hint',
            'min' => 10,
            'max' => null,
        ],
    ];

    public static function thresholdForOrder(int $displayOrder): ?int
    {
        return self::DISPLAY_ORDER_THRESHOLDS[$displayOrder] ?? null;
    }

    public static function thresholds(): array
    {
        return self::DISPLAY_ORDER_THRESHOLDS;
    }

    public static function hintBands(): array
    {
        return self::HINT_BANDS;
    }
}
