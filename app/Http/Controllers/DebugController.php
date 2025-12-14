<?php

namespace App\Http\Controllers;

use App\Models\GuestGuess;
use App\Models\GuestStatistic;
use App\Models\Statistics;
use App\Models\UserGuess;
use App\Services\DailyStudentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function __construct(
        protected DailyStudentService $dailyStudentService
    ) {}
    // on demand debug function to clear today's history for a mode
    public function clearHistory(Request $request, string $mode): RedirectResponse
    {
        $modeKey = ucfirst($mode);
        $dailyHistory = $this->dailyStudentService->getDailyHistoryRecord($mode);

        UserGuess::where('daily_student_history_id', $dailyHistory->id)->delete();
        GuestGuess::where('daily_student_history_id', $dailyHistory->id)->delete();
        Statistics::where('daily_student_history_id', $dailyHistory->id)->delete();
        GuestStatistic::where('daily_student_history_id', $dailyHistory->id)->delete();

        $request->session()->forget("guessHistory{$modeKey}");
        $request->session()->forget("guessCorrect{$modeKey}");
        $request->session()->forget("messageBoxShow{$modeKey}");
        $request->session()->forget("attempts{$modeKey}");
        $request->session()->forget("{$mode}GameState");

        return redirect()->back();
    }
}
