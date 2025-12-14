<?php

namespace App\Services;

use App\Models\HardModeClue;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;

class HardModeService
{
    public function __construct(
        protected DailyStudentService $dailyStudentService,
    ) {}

    public function getDailyHardModeClues(bool $forceRegenerate = false): array
    {
        // retrieves todays hard mode clues
        $history = $this->dailyStudentService->getDailyHistoryRecord('hard');
        $cacheKey = $this->clueCacheKey($history->id);
        if (!$forceRegenerate && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $query = $history->clues();

        //debug
        if ($forceRegenerate) {
            Cache::forget($cacheKey);
            $query->delete();
        }

        // gets current clues if they exist and caches them
        $existing = $query->get();
        if ($existing->isNotEmpty()) {
            $mapped = $existing->map(fn(HardModeClue $clue) => $this->clueModelToArray($clue))->all();
            Cache::put($cacheKey, $mapped, $this->clueCacheTtl());
            return $mapped;
        }

        $student = $history->student;
        if (!$student) {
            Cache::put($cacheKey, [], $this->clueCacheTtl());
            return [];
        }

        $clues = $this->generateClues($student, $forceRegenerate);
        foreach ($clues as $index => $clue) {
            $history->clues()->create([
                'display_order' => $index,
                'label' => $clue['label'],
                'value' => $clue['value'],
                'difficulty' => $clue['difficulty'],
                'field' => $clue['field'] ?? null,
                'pair' => $clue['pair'] ?? null,
            ]);
        }

        Cache::put($cacheKey, $clues, $this->clueCacheTtl());
        return $clues;
    }

    protected function clueModelToArray(HardModeClue $clue): array
    {
        // maps the model to an array
        return [
            'label' => $clue->label,
            'value' => $clue->value,
            'difficulty' => $clue->difficulty,
            'field' => $clue->field,
            'pair' => $clue->pair,
        ];
    }

    protected function generateClues(Student $student, bool $forceRegenerate = false): array
    {
        // attributes by difficulty
        $hardAttributes = [
            'Position' => 'position',
            'Weapon Type' => 'weapon_type',
            'Equipment' => ['equipment_1', 'equipment_2', 'equipment_3'],
            'Armor Type' => 'armor_type',
            'Height' => 'height',
        ];

        $mediumAttributes = [
            'Age' => 'age',
            'Role' => 'role',
            'Unique Equipment' => 'unique_equipment_name',
            'Damage Type' => 'damage_type',
        ];

        $easyAttributes = [
            'School' => 'school',
            'Class' => 'class',
            'Club' => 'club',
        ];

        // image is always included
        $alwaysAttributes = [
            'Image' => 'image',
        ];

        // create a daily seed so clues dont change during the day
        $seed = now()->toDateString() . '|hard|' . $student->id;
        if ($forceRegenerate) {
            $seed .= '|regen|' . microtime(true);
        }

        // pick attributes based on the daily seed
        $randomHard   = $this->pluckDeterministic($hardAttributes, 3, $seed . '|hard');
        $randomMedium = $this->pluckDeterministic($mediumAttributes, 2, $seed . '|medium');
        $randomEasy   = $this->pluckDeterministic($easyAttributes, 1, $seed . '|easy');

        $clues = array_merge(
            $this->toClueArray($alwaysAttributes, $student, 'always'),
            $this->toClueArray($randomHard, $student, 'hard'),
            $this->toClueArray($randomMedium, $student, 'medium'),
            $this->toClueArray($randomEasy, $student, 'easy'),
        );

        return $clues;
    }

    private function pluckDeterministic(array $source, int $count, string $seed): array
    {
        // Picks N items from an array deterministically based on a seed.
        // Selects a fixed set of attributes based on a seed.
        // This ensures the same attributes are chosen every day for the same student,
        // while still feeling random. Changing the seed produces a different selection.
        if ($count >= count($source)) return $source;

        $keys = array_keys($source);

        $ranked = [];
        foreach ($keys as $k) {
            $ranked[$k] = crc32($seed . '|' . $k);
        }

        uasort($ranked, function ($a, $b) {
            if ($a === $b) return 0;
            return ($a < $b) ? -1 : 1;
        });

        $selectedKeys = array_slice(array_keys($ranked), 0, $count);
        return array_intersect_key($source, array_flip($selectedKeys));
    }

    private function toClueArray(array $attributes, Student $student, string $difficulty): array
    {
        // converts attributes to clue array format
        // handles the equipment fields and label, value, difficulty information
        $result = [];

        foreach ($attributes as $label => $fieldOrFields) {
            if (is_array($fieldOrFields)) {
                $value = implode(', ', array_filter(array_map(fn($f) => $student->$f, $fieldOrFields)));
            } else {
                $value = $student->$fieldOrFields;
                $pair = $fieldOrFields;
            }

            $result[] = [
                'label' => $label,
                'value' => $value,
                'difficulty' => $difficulty,
                'field' => $label, // used for matchHandler
                'pair' => $pair,   // used to access the student property
            ];
        }

        return $result;
    }

    protected function clueCacheKey(int $historyId): string
    {
        return sprintf('hard_mode_clues:%d', $historyId);
    }

    protected function clueCacheTtl(): int
    {
        // returns how long clue data should be in cache
        $configTtl = (int) config('statistics.clue_cache_ttl_seconds', 0);
        if ($configTtl > 0) {
            return $configTtl;
        }

        $secondsUntilEndOfDay = now()->endOfDay()->diffInSeconds(now());
        return max($secondsUntilEndOfDay, 60);
    }
}
