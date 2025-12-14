<?php

namespace App\Http\Controllers;

use App\Services\HardModeService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class QuizController extends Controller
{
    public function __construct(
        protected HardModeService $hardModeService
    ) {}

    public function getHardModeClues(Request $request)
    {
        // returns today's hard mode clues
        $shouldRegenerate = $request->boolean('force', false);
        $clues = $this->hardModeService->getDailyHardModeClues($shouldRegenerate);
        // only sends the the clues that have been requested by random
        $safeClues = array_map(
            fn(array $clue) => Arr::only($clue, ['label', 'value', 'difficulty']),
            $clues
        );

        return response()->json($safeClues);
    }
}
