<?php

namespace App\Services;

use App\Models\DailyStudentHistory;
use App\Models\GuestGuess;
use Illuminate\Support\Facades\Cache;

class GuestGuessService
{
    // refer to UserGuessService.php for comments
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

    public function guestHasCompleted(string $token, DailyStudentHistory $history): bool
    {
        return GuestGuess::where('guest_token', $token)
            ->where('daily_student_history_id', $history->id)
            ->where('is_correct', true)
            ->exists();
    }

    public function attemptCount(string $token, DailyStudentHistory $history): int
    {
        return GuestGuess::where('guest_token', $token)
            ->where('daily_student_history_id', $history->id)
            ->count();
    }

    public function guessedStudentIds(string $token, DailyStudentHistory $history): array
    {
        return GuestGuess::where('guest_token', $token)
            ->where('daily_student_history_id', $history->id)
            ->pluck('student_id')
            ->all();
    }

    public function lastGuessResult(string $token, DailyStudentHistory $history): ?bool
    {
        $guess = GuestGuess::where('guest_token', $token)
            ->where('daily_student_history_id', $history->id)
            ->orderByDesc('attempt_number')
            ->first();

        return $guess?->is_correct;
    }

    public function recordGuess(
        string $token,
        DailyStudentHistory $history,
        int $studentId,
        array $result,
        string $guessText
    ): ?GuestGuess {
        $existing = GuestGuess::where('guest_token', $token)
            ->where('daily_student_history_id', $history->id)
            ->where('student_id', $studentId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $attemptNumber = $this->attemptCount($token, $history) + 1;

        $guess = GuestGuess::create([
            'guest_token' => $token,
            'daily_student_history_id' => $history->id,
            'student_id' => $studentId,
            'attempt_number' => $attemptNumber,
            'is_correct' => (bool)($result['match'] ?? false),
            'guess_text' => $guessText,
            'matches' => $result['fields'] ?? [],
            'height_status' => $result['heightStatus'] ?? null,
        ]);

        if ($this->shouldCacheHistory()) {
            Cache::forget($this->historyCacheKey($token, $history));
        }

        return $guess;
    }

    public function historyPayload(string $token, DailyStudentHistory $history): array
    {
        $ttl = $this->historyCacheTtlSeconds();
        if (!$this->shouldCacheHistory()) {
            return $this->historyQuery($token, $history)
                ->get()
                ->map(fn(GuestGuess $guess) => $this->transformGuess($guess))
                ->values()
                ->all();
        }

        $cacheKey = $this->historyCacheKey($token, $history);

        return Cache::remember($cacheKey, $ttl, function () use ($token, $history) {
            return $this->historyQuery($token, $history)
                ->get()
                ->map(fn(GuestGuess $guess) => $this->transformGuess($guess))
                ->values()
                ->all();
        });
    }

    protected function historyQuery(string $token, DailyStudentHistory $history)
    {
        return GuestGuess::with('student')
            ->where('guest_token', $token)
            ->where('daily_student_history_id', $history->id)
            ->orderBy('attempt_number');
    }

    protected function transformGuess(GuestGuess $guess): array
    {
        $student = $guess->student;
        $base = $student ? $student->only(self::HISTORY_COLUMNS) : [];

        return array_merge($base, [
            'correct' => (bool)$guess->is_correct,
            'matches' => $guess->matches ?? [],
            'heightStatus' => $guess->height_status,
        ]);
    }

    public function latestGuess(string $token): ?GuestGuess
    {
        return GuestGuess::with('student')
            ->where('guest_token', $token)
            ->orderByDesc('created_at')
            ->first();
    }

    public function latestGuessForHistory(string $token, DailyStudentHistory $history): ?GuestGuess
    {
        return GuestGuess::with('student')
            ->where('guest_token', $token)
            ->where('daily_student_history_id', $history->id)
            ->orderByDesc('attempt_number')
            ->first();
    }

    protected function historyCacheKey(string $token, DailyStudentHistory $history): string
    {
        return sprintf('guest_history:%s:%d', $token, $history->id);
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
