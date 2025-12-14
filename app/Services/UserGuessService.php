<?php

namespace App\Services;

use App\Models\DailyStudentHistory;
use App\Models\User;
use App\Models\UserGuess;
use Illuminate\Support\Facades\Cache;

class UserGuessService
{
    // handles all guess related operations
    // checking if user has completed todays quiz, counting attempts, recording guesses, retrieving history
    // tracking guessed student ids, last guess result
    // caching for performance
    private const HISTORY_COLUMNS = [
        'id',
        'first_name',
        'second_name',
        'image',
        'age',
        'birthday',
        'height',
        'school',
        'club',
        'role',
        'position',
        'class',
        'damage_type',
        'armor_type',
        'weapon_type',
        'equipment_1',
        'equipment_2',
        'equipment_3',
        'unique_equipment_name',
        'unique_equipment_img',
        'memorial_lobby',
    ];

    public function userHasCompleted(User $user, DailyStudentHistory $history): bool
    {
        return UserGuess::where('user_id', $user->id)
            ->where('daily_student_history_id', $history->id)
            ->where('is_correct', true)
            ->exists();
    }

    public function attemptCount(User $user, DailyStudentHistory $history): int
    {
        return UserGuess::where('user_id', $user->id)
            ->where('daily_student_history_id', $history->id)
            ->count();
    }

    public function guessedStudentIds(User $user, DailyStudentHistory $history): array
    {
        return UserGuess::where('user_id', $user->id)
            ->where('daily_student_history_id', $history->id)
            ->pluck('student_id')
            ->all();
    }

    public function lastGuessResult(User $user, DailyStudentHistory $history): ?bool
    {
        $guess = UserGuess::where('user_id', $user->id)
            ->where('daily_student_history_id', $history->id)
            ->orderByDesc('attempt_number')
            ->first();

        return $guess?->is_correct;
    }

    public function recordGuess(
        User $user,
        DailyStudentHistory $history,
        int $studentId,
        array $result,
        string $guessText
    ): ?UserGuess {
        $existing = UserGuess::where('user_id', $user->id)
            ->where('daily_student_history_id', $history->id)
            ->where('student_id', $studentId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $attemptNumber = $this->attemptCount($user, $history) + 1;

        $guess = UserGuess::create([
            'user_id' => $user->id,
            'daily_student_history_id' => $history->id,
            'student_id' => $studentId,
            'attempt_number' => $attemptNumber,
            'is_correct' => (bool)($result['match'] ?? false),
            'guess_text' => $guessText,
            'matches' => $result['fields'] ?? [],
            'height_status' => $result['heightStatus'] ?? null,
        ]);

        if ($this->shouldCacheHistory()) {
            Cache::forget($this->historyCacheKey($user, $history));
        }

        return $guess;
    }

    public function latestGuess(User $user): ?UserGuess
    {
        return UserGuess::with('student')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->first();
    }

    public function latestGuessForHistory(User $user, DailyStudentHistory $history): ?UserGuess
    {
        return UserGuess::with('student')
            ->where('user_id', $user->id)
            ->where('daily_student_history_id', $history->id)
            ->orderByDesc('attempt_number')
            ->first();
    }

    public function latestGuessPayload(User $user): ?array
    {
        $guess = $this->latestGuess($user);
        return $guess ? $this->transformGuess($guess) : null;
    }

    public function historyPayload(User $user, DailyStudentHistory $history): array
    {
        $ttl = $this->historyCacheTtlSeconds();
        if (!$this->shouldCacheHistory()) {
            return $this->historyQuery($user, $history)
                ->get()
                ->map(fn(UserGuess $guess) => $this->transformGuess($guess))
                ->values()
                ->all();
        }

        $cacheKey = $this->historyCacheKey($user, $history);

        return Cache::remember($cacheKey, $ttl, function () use ($user, $history) {
            return $this->historyQuery($user, $history)
                ->get()
                ->map(fn(UserGuess $guess) => $this->transformGuess($guess))
                ->values()
                ->all();
        });
    }

    protected function historyQuery(User $user, DailyStudentHistory $history)
    {
        return UserGuess::with('student')
            ->where('user_id', $user->id)
            ->where('daily_student_history_id', $history->id)
            ->orderBy('attempt_number');
    }

    public function transformGuess(UserGuess $guess): array
    {
        $student = $guess->student;
        $base = $student
            ? $student->only(self::HISTORY_COLUMNS)
            : [];

        return array_merge($base, [
            'correct' => (bool)$guess->is_correct,
            'matches' => $guess->matches ?? [],
            'heightStatus' => $guess->height_status,
        ]);
    }

    protected function historyCacheKey(User $user, DailyStudentHistory $history): string
    {
        return sprintf('user_history:%d:%d', $user->id, $history->id);
    }

    protected function historyCacheTtlSeconds(): int
    {
        $ttl = (int) config('statistics.history_cache_ttl_seconds', 0);
        return max($ttl, 0);
    }

    protected function shouldCacheHistory(): bool
    {
        return $this->historyCacheTtlSeconds() > 0;
    }
}
