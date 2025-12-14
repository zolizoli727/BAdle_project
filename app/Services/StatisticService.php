<?php

namespace App\Services;

use App\Models\DailyStudentHistory;
use App\Models\GameMode;
use App\Models\GuestGuess;
use App\Models\GuestStatistic;
use App\Models\Student;
use App\Models\Statistics;
use App\Models\User;
use App\Models\UserGuess;
use App\Support\HardModeClueMap;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticService
{
    // just a bunch of db queries for analytics

    public function statHandler(DailyStudentHistory $history, User $user, int $attempts): void
    {
        Statistics::updateOrCreate(
            [
                'daily_student_history_id' => $history->id,
                'user_id' => $user->id,
            ],
            [
                'game_mode_id' => $history->game_mode_id,
                'attempts' => $attempts,
            ]
        );
    }

    public function combinedAttemptsForMode(string $mode): Collection
    {
        $gameMode = GameMode::where('key', strtolower($mode))->firstOrFail();

        $userAttempts = Statistics::where('game_mode_id', $gameMode->id)
            ->pluck('attempts');

        $guestAttempts = GuestStatistic::where('game_mode_id', $gameMode->id)
            ->pluck('attempts');

        return $userAttempts->merge($guestAttempts);
    }

    public function dbQueries(?User $user = null, array|string|null $modeFilter = null): array
    {
        $cacheKey = $this->cacheKey($user, $modeFilter);

        if ($cacheKey === null) {
            return $this->compileDbQueries($user, $modeFilter);
        }

        return Cache::remember(
            $cacheKey,
            now()->addSeconds($this->cacheTtlSeconds()),
            fn() => $this->compileDbQueries($user, $modeFilter)
        );
    }

    protected function compileDbQueries(?User $user = null, array|string|null $modeFilter = null): array
    {
        $today = now()->toDateString();
        $modes = $this->resolveModes($modeFilter);
        $modeIds = $modes->pluck('id')->all();

        $totalGuessesByUser = UserGuess::count();
        $totalGuestGuesses = GuestGuess::count();
        $totalGuesses = $totalGuessesByUser + $totalGuestGuesses;
        $totalGuessesCurrentUser = $user ? UserGuess::where('user_id', $user->id)->count() : null;

        $todayGuessesByMode = $this->guessesByModeForDate($today, false, $modeIds);
        $todayGuesses = array_sum($todayGuessesByMode);

        $todayCorrectGuessesByMode = $this->guessesByModeForDate($today, true, $modeIds);
        $todayCorrectGuesses = array_sum($todayCorrectGuessesByMode);

        $historicalCorrectGuessesByUser = UserGuess::where('is_correct', true)->count();

        $attemptTotals = $this->aggregateAttempts();
        $averageGuessPerStudent = $this->safeAverage($attemptTotals['total_attempts'], $attemptTotals['total_runs']);
        $averageGuessPerRegisteredUser = $this->safeAverage($attemptTotals['user_attempts'], $attemptTotals['user_runs']);
        $averageGuessPerStudentByMode = $this->averageAttemptsByMode($modeIds);
        $averageGuessCurrentUser = $user ? $this->averageAttemptsForUser($user) : null;

        $studentDifficulty = $this->studentDifficultyStats();
        $hardestStudent = $studentDifficulty['hardest'];
        $easiestStudent = $studentDifficulty['easiest'];
        $mostGoalStudent = $studentDifficulty['mostGoal'];

        $guessFrequencies = $this->guessFrequencyStats();
        $mostGuessedStudent = $guessFrequencies['most'];
        $leastGuessedStudent = $guessFrequencies['least'];
        $mostFirstGuessStudent = $guessFrequencies['firstGuess'];

        $streaks = $user ? $this->streakStatsForUser($user) : ['best' => null, 'current' => null];
        $userPercentile = $user ? $this->userPercentileSnapshot($user) : null;
        $modeSnapshots = $modes
            ->mapWithKeys(fn(GameMode $mode) => [
                $mode->key => $this->compileModeStats($mode, $today),
            ])
            ->all();

        $hardModeId = GameMode::where('key', 'hard')->value('id');
        $hintSuccessRates = $hardModeId ? $this->hintSuccessRates($hardModeId) : [];

        return [
            'totalGuesses' => $totalGuesses,
            'totalGuessesByUser' => $totalGuessesByUser,
            'totalGuessesCurrentUser' => $totalGuessesCurrentUser,
            'todayGuesses' => $todayGuesses,
            'todayGuessesByMode' => $todayGuessesByMode,
            'todayCorrectGuesses' => $todayCorrectGuesses,
            'todayCorrectGuessesByMode' => $todayCorrectGuessesByMode,
            'historicalCorrectGuessesByUser' => $historicalCorrectGuessesByUser,
            'bestDailyStreak' => $streaks['best'],
            'currentDailyStreak' => $streaks['current'],
            'averageGuessPerStudent' => $averageGuessPerStudent,
            'averageGuessPerStudentByMode' => $averageGuessPerStudentByMode,
            'averageGuessPerRegisteredUser' => $averageGuessPerRegisteredUser,
            'averageGuessCurrentUser' => $averageGuessCurrentUser,
            'hardestStudent' => $hardestStudent,
            'easiestStudent' => $easiestStudent,
            'mostGuessedStudent' => $mostGuessedStudent,
            'leastGuessedStudent' => $leastGuessedStudent,
            'mostFirstGuessStudent' => $mostFirstGuessStudent,
            'mostGoalStudent' => $mostGoalStudent,
            'userPercentile' => $userPercentile,
            'modes' => $modeSnapshots,
            'hardModeHintSuccessRates' => $hintSuccessRates,
        ];
    }

    protected function cacheKey(?User $user, array|string|null $modeFilter): ?string
    {
        if ($this->cacheTtlSeconds() <= 0) {
            return null;
        }

        $userPart = $user?->id ?? 'guest';

        if (is_array($modeFilter)) {
            $modePart = implode(',', array_map('strtolower', $modeFilter));
        } elseif (is_string($modeFilter) && $modeFilter !== '') {
            $modePart = strtolower($modeFilter);
        } else {
            $modePart = 'all';
        }

        return sprintf('statistics:%s:%s:%s', $userPart, $modePart, now()->toDateString());
    }

    protected function cacheTtlSeconds(): int
    {
        $ttl = (int)config('statistics.cache_ttl_seconds', 0);
        return max($ttl, 0);
    }

    protected function aggregateAttempts(?int $gameModeId = null): array
    {
        $userQuery = Statistics::query();
        $guestQuery = GuestStatistic::query();

        if ($gameModeId !== null) {
            $userQuery->where('game_mode_id', $gameModeId);
            $guestQuery->where('game_mode_id', $gameModeId);
        }

        $userTotals = $userQuery
            ->selectRaw('COALESCE(SUM(attempts), 0) as total_attempts, COUNT(*) as total_runs')
            ->first();

        $guestTotals = $guestQuery
            ->selectRaw('COALESCE(SUM(attempts), 0) as total_attempts, COUNT(*) as total_runs')
            ->first();

        $userAttempts = (int)($userTotals->total_attempts ?? 0);
        $userRuns = (int)($userTotals->total_runs ?? 0);
        $guestAttempts = (int)($guestTotals->total_attempts ?? 0);
        $guestRuns = (int)($guestTotals->total_runs ?? 0);

        return [
            'user_attempts' => $userAttempts,
            'user_runs' => $userRuns,
            'guest_attempts' => $guestAttempts,
            'guest_runs' => $guestRuns,
            'total_attempts' => $userAttempts + $guestAttempts,
            'total_runs' => $userRuns + $guestRuns,
        ];
    }

    protected function averageAttemptsByMode(?array $modeIds = null): array
    {
        $query = GameMode::query()->orderBy('id');

        if ($modeIds) {
            $query->whereIn('id', $modeIds);
        }

        $modes = $query->get();
        $averages = [];

        foreach ($modes as $mode) {
            $totals = $this->aggregateAttempts($mode->id);
            $averages[$mode->key] = $this->safeAverage($totals['total_attempts'], $totals['total_runs']);
        }

        return $averages;
    }

    protected function averageAttemptsForUser(User $user): float
    {
        $totals = Statistics::query()
            ->where('user_id', $user->id)
            ->selectRaw('COALESCE(SUM(attempts), 0) as total_attempts, COUNT(*) as total_runs')
            ->first();

        if (!$totals) {
            return 0.0;
        }

        return $this->safeAverage(
            (int)($totals->total_attempts ?? 0),
            (int)($totals->total_runs ?? 0)
        );
    }

    protected function resolveModes(array|string|null $filter): Collection
    {
        $query = GameMode::query()->orderBy('id');

        if ($filter !== null) {
            $keys = collect(is_array($filter) ? $filter : [$filter])
                ->filter()
                ->map(fn($key) => strtolower($key))
                ->unique()
                ->values();

            if ($keys->isNotEmpty()) {
                $query->whereIn('key', $keys->all());
            }
        }

        return $query->get();
    }

    protected function compileModeStats(GameMode $mode, string $today): array
    {
        $guessCounts = $this->guessCountsForMode($mode->id);
        $attemptTotals = $this->aggregateAttempts($mode->id);
        $todayGuessesMap = $this->guessesByModeForDate($today, false, [$mode->id]);
        $todayCorrectMap = $this->guessesByModeForDate($today, true, [$mode->id]);

        return [
            'key' => $mode->key,
            'name' => $mode->name,
            'totalGuesses' => $guessCounts['total'],
            'userGuesses' => $guessCounts['users'],
            'guestGuesses' => $guessCounts['guests'],
            'runs' => $attemptTotals['total_runs'],
            'averageAttempts' => $this->safeAverage($attemptTotals['total_attempts'], $attemptTotals['total_runs']),
            'todayGuesses' => $todayGuessesMap[$mode->key] ?? 0,
            'todayCorrect' => $todayCorrectMap[$mode->key] ?? 0,
        ];
    }

    protected function guessCountsForMode(int $modeId): array
    {
        $userCount = UserGuess::query()
            ->join('daily_student_history', 'user_guesses.daily_student_history_id', '=', 'daily_student_history.id')
            ->where('daily_student_history.game_mode_id', $modeId)
            ->count();

        $guestCount = GuestGuess::query()
            ->join('daily_student_history', 'guest_guesses.daily_student_history_id', '=', 'daily_student_history.id')
            ->where('daily_student_history.game_mode_id', $modeId)
            ->count();

        return [
            'users' => $userCount,
            'guests' => $guestCount,
            'total' => $userCount + $guestCount,
        ];
    }

    protected function guessesByModeForDate(string $date, bool $onlyCorrect = false, ?array $modeIds = null): array
    {
        $userCounts = UserGuess::select(
            'daily_student_history.game_mode_id as mode_id',
            DB::raw('COUNT(*) as total')
        )
            ->join('daily_student_history', 'user_guesses.daily_student_history_id', '=', 'daily_student_history.id')
            ->whereDate('daily_student_history.played_on', $date)
            ->when($onlyCorrect, fn($query) => $query->where('user_guesses.is_correct', true))
            ->when($modeIds, fn($query) => $query->whereIn('daily_student_history.game_mode_id', $modeIds))
            ->groupBy('daily_student_history.game_mode_id')
            ->pluck('total', 'mode_id')
            ->toArray();

        $guestCounts = GuestGuess::select(
            'daily_student_history.game_mode_id as mode_id',
            DB::raw('COUNT(*) as total')
        )
            ->join('daily_student_history', 'guest_guesses.daily_student_history_id', '=', 'daily_student_history.id')
            ->whereDate('daily_student_history.played_on', $date)
            ->when($onlyCorrect, fn($query) => $query->where('guest_guesses.is_correct', true))
            ->when($modeIds, fn($query) => $query->whereIn('daily_student_history.game_mode_id', $modeIds))
            ->groupBy('daily_student_history.game_mode_id')
            ->pluck('total', 'mode_id')
            ->toArray();

        $combined = $this->combineCounters([$userCounts, $guestCounts]);

        if (empty($combined)) {
            return [];
        }

        $modeKeys = GameMode::whereIn('id', array_keys($combined))
            ->pluck('key', 'id')
            ->toArray();

        $results = [];

        foreach ($combined as $modeId => $count) {
            $modeKey = $modeKeys[$modeId] ?? (string)$modeId;
            $results[$modeKey] = $count;
        }

        return $results;
    }

    protected function studentDifficultyStats(): array
    {
        $userStats = Statistics::query()
            ->select(
                'daily_student_history.student_id',
                DB::raw('SUM(game_statistics.attempts) as attempts_sum'),
                DB::raw('COUNT(*) as runs')
            )
            ->join('daily_student_history', 'game_statistics.daily_student_history_id', '=', 'daily_student_history.id')
            ->groupBy('daily_student_history.student_id')
            ->get();

        $guestStats = GuestStatistic::query()
            ->select(
                'daily_student_history.student_id',
                DB::raw('SUM(guest_statistics.attempts) as attempts_sum'),
                DB::raw('COUNT(*) as runs')
            )
            ->join('daily_student_history', 'guest_statistics.daily_student_history_id', '=', 'daily_student_history.id')
            ->groupBy('daily_student_history.student_id')
            ->get();

        $totals = [];

        foreach ($userStats as $row) {
            $studentId = (int)$row->student_id;
            $totals[$studentId]['attempts'] = (int)($totals[$studentId]['attempts'] ?? 0) + (int)$row->attempts_sum;
            $totals[$studentId]['runs'] = (int)($totals[$studentId]['runs'] ?? 0) + (int)$row->runs;
        }

        foreach ($guestStats as $row) {
            $studentId = (int)$row->student_id;
            $totals[$studentId]['attempts'] = (int)($totals[$studentId]['attempts'] ?? 0) + (int)$row->attempts_sum;
            $totals[$studentId]['runs'] = (int)($totals[$studentId]['runs'] ?? 0) + (int)$row->runs;
        }

        $hardest = $easiest = null;

        foreach ($totals as $studentId => $data) {
            if (($data['runs'] ?? 0) === 0) {
                continue;
            }

            $average = $this->safeAverage($data['attempts'], $data['runs']);

            if (!$hardest || $average > $hardest['average']) {
                $hardest = [
                    'student' => $this->studentSummary($studentId),
                    'average' => $average,
                    'runs' => $data['runs'],
                ];
            }

            if (!$easiest || $average < $easiest['average']) {
                $easiest = [
                    'student' => $this->studentSummary($studentId),
                    'average' => $average,
                    'runs' => $data['runs'],
                ];
            }
        }

        $goalData = DailyStudentHistory::select('student_id', DB::raw('COUNT(*) as appearances'))
            ->groupBy('student_id')
            ->orderByDesc('appearances')
            ->first();

        $mostGoal = $goalData
            ? [
                'student' => $this->studentSummary((int)$goalData->student_id),
                'appearances' => (int)$goalData->appearances,
            ]
            : null;

        return [
            'hardest' => $hardest,
            'easiest' => $easiest,
            'mostGoal' => $mostGoal,
        ];
    }

    protected function guessFrequencyStats(): array
    {
        $userGuessCounts = UserGuess::select('student_id', DB::raw('COUNT(*) as guesses'))
            ->groupBy('student_id')
            ->pluck('guesses', 'student_id')
            ->toArray();

        $guestGuessCounts = GuestGuess::select('student_id', DB::raw('COUNT(*) as guesses'))
            ->groupBy('student_id')
            ->pluck('guesses', 'student_id')
            ->toArray();

        $combined = $this->combineCounters([$userGuessCounts, $guestGuessCounts]);
        $most = $this->extremeGuessStat($combined, 'max');
        $least = $this->extremeGuessStat($combined, 'min');
        $firstGuess = $this->mostFrequentFirstGuess();

        return [
            'most' => $most,
            'least' => $least,
            'firstGuess' => $firstGuess,
        ];
    }

    protected function userPercentileSnapshot(User $user): ?array
    {
        $userAverages = Statistics::query()
            ->select(
                'user_id',
                DB::raw('SUM(attempts) as attempts_sum'),
                DB::raw('COUNT(*) as runs')
            )
            ->groupBy('user_id')
            ->get()
            ->map(function ($row) {
                $runs = (int)($row->runs ?? 0);
                $average = $runs > 0 ? (float)$row->attempts_sum / $runs : null;

                return [
                    'user_id' => (int)$row->user_id,
                    'runs' => $runs,
                    'average' => $average,
                ];
            });

        $target = $userAverages->firstWhere('user_id', $user->id);

        if (!$target || $target['runs'] === 0 || $target['average'] === null) {
            return null;
        }

        $totalUsers = $userAverages->count();

        if ($totalUsers === 0) {
            return null;
        }

        $usersBeaten = $userAverages
            ->filter(fn($row) => $row['average'] !== null && $row['average'] >= $target['average'])
            ->count();

        $percentile = round(($usersBeaten / $totalUsers) * 100, 2);

        return [
            'averageAttempts' => round($target['average'], 2),
            'runs' => $target['runs'],
            'percentile' => $percentile,
            'totalPlayers' => $totalUsers,
        ];
    }

    protected function hintSuccessRates(int $hardModeId): array
    {
        $attempts = $this->hardModeRunAttempts($hardModeId);

        if ($attempts->isEmpty()) {
            return [];
        }

        $bands = HardModeClueMap::hintBands();
        $totals = [];

        foreach ($bands as $key => $band) {
            $totals[$key] = $attempts->filter(function (int $attempt) use ($band) {
                $min = $band['min'];
                $max = $band['max'];

                $meetsMin = $attempt >= $min;
                $meetsMax = $max === null ? true : $attempt <= $max;

                return $meetsMin && $meetsMax;
            })->count();
        }

        $grandTotal = array_sum($totals);

        if ($grandTotal === 0) {
            return [];
        }

        $formatted = [];

        foreach ($bands as $key => $band) {
            $count = $totals[$key] ?? 0;
            $formatted[$key] = [
                'label' => $band['label'],
                'count' => $count,
                'rate' => round(($count / $grandTotal) * 100, 2),
            ];
        }

        return $formatted;
    }

    protected function hardModeRunAttempts(int $hardModeId): Collection
    {
        $userAttempts = Statistics::query()
            ->where('game_mode_id', $hardModeId)
            ->pluck('attempts');

        $guestAttempts = GuestStatistic::query()
            ->where('game_mode_id', $hardModeId)
            ->pluck('attempts');

        return $userAttempts->merge($guestAttempts)->map(fn($value) => (int)$value);
    }

    protected function combineCounters(array $sources): array
    {
        $result = [];

        foreach ($sources as $source) {
            foreach ($source as $key => $value) {
                if ($key === null) {
                    continue;
                }

                $result[(int)$key] = ($result[(int)$key] ?? 0) + (int)$value;
            }
        }

        return $result;
    }

    protected function mostFrequentFirstGuess(): ?array
    {
        $userFirst = UserGuess::where('attempt_number', 1)
            ->select('student_id', DB::raw('COUNT(*) as guesses'))
            ->groupBy('student_id')
            ->pluck('guesses', 'student_id')
            ->toArray();

        $guestFirst = GuestGuess::where('attempt_number', 1)
            ->select('student_id', DB::raw('COUNT(*) as guesses'))
            ->groupBy('student_id')
            ->pluck('guesses', 'student_id')
            ->toArray();

        $combined = $this->combineCounters([$userFirst, $guestFirst]);

        if (empty($combined)) {
            return null;
        }

        $top = null;

        foreach ($combined as $id => $count) {
            if ($top === null || $count > $top['count']) {
                $top = [
                    'student_id' => $id,
                    'count' => $count,
                ];
            }
        }

        return $top
            ? [
                'student' => $this->studentSummary($top['student_id']),
                'count' => $top['count'],
            ]
            : null;
    }

    protected function extremeGuessStat(array $counts, string $direction): ?array
    {
        if (empty($counts)) {
            return null;
        }

        $best = null;

        foreach ($counts as $studentId => $count) {
            if ($count <= 0) {
                continue;
            }

            if (!$best) {
                $best = ['student_id' => $studentId, 'count' => $count];
                continue;
            }

            if ($direction === 'max' && $count > $best['count']) {
                $best = ['student_id' => $studentId, 'count' => $count];
            }

            if ($direction === 'min' && $count < $best['count']) {
                $best = ['student_id' => $studentId, 'count' => $count];
            }
        }

        return $best
            ? [
                'student' => $this->studentSummary($best['student_id']),
                'guesses' => $best['count'],
            ]
            : null;
    }

    protected function streakStatsForUser(User $user): array
    {
        $dates = Statistics::query()
            ->select('daily_student_history.played_on')
            ->join('daily_student_history', 'game_statistics.daily_student_history_id', '=', 'daily_student_history.id')
            ->where('game_statistics.user_id', $user->id)
            ->orderBy('daily_student_history.played_on')
            ->pluck('daily_student_history.played_on')
            ->map(fn($date) => $date instanceof Carbon ? $date->copy()->startOfDay() : Carbon::parse($date)->startOfDay())
            ->unique(fn(Carbon $date) => $date->toDateString())
            ->values();

        if ($dates->isEmpty()) {
            return [
                'best' => 0,
                'current' => 0,
            ];
        }

        $best = 0;
        $running = 0;
        $previous = null;

        foreach ($dates as $date) {
            if ($previous && $previous->copy()->addDay()->isSameDay($date)) {
                $running++;
            } else {
                $running = 1;
            }

            $best = max($best, $running);
            $previous = $date;
        }

        $current = 0;
        $expected = null;

        foreach ($dates->sortDesc() as $date) {
            if (!$expected) {
                $current = 1;
                $expected = $date->copy()->subDay();
                continue;
            }

            if ($date->isSameDay($expected)) {
                $current++;
                $expected = $expected->copy()->subDay();
            } else {
                break;
            }
        }

        return [
            'best' => $best,
            'current' => $current,
        ];
    }

    protected function studentSummary(int $studentId): ?array
    {
        $student = Student::select('id', 'first_name', 'second_name', 'image')->find($studentId);

        if (!$student) {
            return null;
        }

        return [
            'id' => $student->id,
            'first_name' => $student->first_name,
            'second_name' => $student->second_name,
            'image' => $student->image,
        ];
    }

    protected function safeAverage(int $total, int $count): float
    {
        return $count > 0 ? round($total / $count, 2) : 0.0;
    }
}
