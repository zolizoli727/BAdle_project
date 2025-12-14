<?php

namespace App\Services;

use App\Models\DailyStudentHistory;
use App\Models\GameMode;
use App\Models\Student;
use RuntimeException;

class DailyStudentService
{
    // cache for today's history records per mode so it doesnt ask the db multiple times
    protected array $historyCache = [];

    public function __construct(
        protected HistoryMemoryService $historyMemoryService,
    ) {}

    public function getDailyClassicStudent(): ?Student
    {
        return $this->getDailyStudentByMode('classic');
    }

    public function getDailyHardStudent(): ?Student
    {
        return $this->getDailyStudentByMode('hard');
    }

    public function getDailyImageStudent(): ?Student
    {
        return $this->getDailyStudentByMode('image');
    }

    public function getDailyHistoryRecord(string $mode): DailyStudentHistory
    {
        // dynamically retrieves or creates today's history record for the specified mode
        // and avoids multiple db queries by using cache
        $this->historyMemoryService->resetHistoryMemory();

        $modeKey = strtolower($mode);

        if (isset($this->historyCache[$modeKey])) {
            return $this->historyCache[$modeKey];
        }

        $gameMode = GameMode::where('key', $modeKey)->firstOrFail();
        $today = now()->startOfDay();

        return $this->historyCache[$modeKey] = DailyStudentHistory::firstOrCreate(
            [
                'played_on' => $today,
                'game_mode_id' => $gameMode->id,
            ],
            [
                'student_id' => $this->selectDailyStudentId($modeKey),
            ]
        );
    }

    protected function getDailyStudentByMode(string $mode): ?Student
    {
        // returns today's student for the specified mode
        return $this->getDailyHistoryRecord($mode)->student;
    }

    protected function selectDailyStudentId(string $modeKey): int
    {
        // selects todays student based on a hash of the date and mode
        // making sure each mode has a different student each day
        // psuedo-random
        $today = now()->toDateString();
        $hash = crc32($today . $modeKey);
        $totalStudents = Student::count();

        $index = abs($hash) % $totalStudents;
        $studentId = Student::offset($index)->value('id');

        if (!$studentId) {
            $studentId = Student::first()->id;
        }

        return $studentId;
    }
}
