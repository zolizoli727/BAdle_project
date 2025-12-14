<?php

namespace App\Http\Controllers;

use App\Services\DailyStudentService;
use App\Services\StudentGuessService;
use App\Services\StatisticService;
use App\Services\UserGuessService;
use App\Services\GuestGuessService;
use App\Services\GuestStatisticService;
use App\Services\HardModeClueUsageService;
use App\Models\Student;
use Illuminate\Http\Request;


class FormController extends Controller
{
    public function __construct(
        protected DailyStudentService $dailyStudentService,
        protected StudentGuessService $studentGuessService,
        protected StatisticService $statisticService,
        protected UserGuessService $userGuessService,
        protected GuestGuessService $guestGuessService,
        protected GuestStatisticService $guestStatisticService,
        protected HardModeClueUsageService $hardModeClueUsageService
    ) {}

    public function guessStudent(Request $request)
    {
        // handle guess submissions
        // get mode from request, default to classic
        $mode = $request->input('mode', 'classic');
        $modeKey = ucfirst($mode);
        $user = $request->user();
        $guestToken = $request->attributes->get('guest_token');

        $request->validate([
            'student_name' => 'required|string'
        ]);

        // fetching today's student for the mode
        $dailyHistory = $this->dailyStudentService->getDailyHistoryRecord($mode);
        $todayStudent = $dailyHistory->student;

        // check if quiz already completed
        if (!$user && $guestToken && $this->guestGuessService->guestHasCompleted($guestToken, $dailyHistory)) {
            return back();
        }

        if ($user && $this->userGuessService->userHasCompleted($user, $dailyHistory)) {
            return back();
        }

        // validate user guess
        $guess = trim($request->input('student_name'));
        $guessedStudent = $this->studentGuessService->findGuessedStudent($guess);
        if (!$guessedStudent) {
            return back()->withInput();
        }
        $results = $this->studentGuessService->isGuessCorrect($guess, $todayStudent);
        $isCorrect = $results['match'];

        // record guess into db if user and update statistics if correct
        if ($user) {
            $this->userGuessService->recordGuess($user, $dailyHistory, $guessedStudent->id, $results, $guess);
            $attemptsCount = $this->userGuessService->attemptCount($user, $dailyHistory);
            
            //hard mode clue logging
            if (strtolower($mode) === 'hard') {
                $this->hardModeClueUsageService->logUsage(
                    $dailyHistory,
                    $attemptsCount,
                    "user:{$user->id}",
                    'user',
                    $user->id
                );
            }

            if ($isCorrect) {
                $this->statisticService->statHandler($dailyHistory, $user, $attemptsCount);
            }
        } else {
            if (!$guestToken) {
                return back();
            }
            //------------------------------------------------------
            // if not user use guest token to record guesses into db
            $this->guestGuessService->recordGuess($guestToken, $dailyHistory, $guessedStudent->id, $results, $guess);
            $attemptsCount = $this->guestGuessService->attemptCount($guestToken, $dailyHistory);

            if (strtolower($mode) === 'hard') {
                $this->hardModeClueUsageService->logUsage(
                    $dailyHistory,
                    $attemptsCount,
                    "guest:{$guestToken}",
                    'guest',
                    null,
                    $guestToken
                );
            }

            if ($isCorrect) {
                $this->guestStatisticService->statHandler($dailyHistory, $guestToken, $attemptsCount);
            }
        }

        return back();
    }

    public function search(Request $request)
    {
        // autocomplete search for student names,
        // excluding already guessed students,
        // matches first and last names in any order
        $mode = $request->query('mode', 'classic');
        $user = $request->user();
        $guestToken = $request->attributes->get('guest_token');
        $dailyHistory = $this->dailyStudentService->getDailyHistoryRecord($mode);

        $term = $request->query('term');
        $terms = explode(' ', strtolower(trim($term)));
        $query = Student::query();

        $guessedIds = $user
            ? $this->userGuessService->guessedStudentIds($user, $dailyHistory)
            : ($guestToken
                ? $this->guestGuessService->guessedStudentIds($guestToken, $dailyHistory)
                : []);

        if (count($terms) > 1) {
            $query->where(function ($q) use ($terms) {
                $q->whereRaw('LOWER(first_name) LIKE ?', ["{$terms[0]}%"])
                    ->whereRaw('LOWER(second_name) LIKE ?', ["{$terms[1]}%"]);
            })->orWhere(function ($q) use ($terms) {
                $q->whereRaw('LOWER(first_name) LIKE ?', ["{$terms[1]}%"])
                    ->whereRaw('LOWER(second_name) LIKE ?', ["{$terms[0]}%"]);
            });
        } else {
            $query->where(function ($q) use ($terms) {
                $q->whereRaw('LOWER(first_name) LIKE ?', ["{$terms[0]}%"])
                    ->orWhereRaw('LOWER(second_name) LIKE ?', ["{$terms[0]}%"]);
            });
        }

        if (!empty($guessedIds)) {
            $query->whereNotIn('id', $guessedIds);
        }

        return $query->limit(10)->get(['id', 'first_name', 'second_name']);
    }
}
