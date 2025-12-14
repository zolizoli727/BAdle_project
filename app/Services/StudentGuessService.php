<?php

namespace App\Services;

use App\Models\Student;

class StudentGuessService
{
    public function findGuessedStudent(string $guess): ?Student
    {
        //megnézi hogy az adatbázisban lévő diák neve megegyezik-e a kitalált névvel, mivel van autocomplete és free typing ezért ez a megoldás működik csak id nem
        $cleanGuess = strtolower(trim($guess));
        $parts = explode(' ', strtolower(trim($guess)));

        return Student::where(function ($query) use ($parts, $cleanGuess) {
            $query->where(function ($q) use ($parts) {
                $q->whereRaw('LOWER(first_name) = ?', [$parts[0]])
                    ->whereRaw('LOWER(second_name) = ?', [$parts[1] ?? '']);
            })
                ->orWhere(function ($q) use ($parts) {
                    $q->whereRaw('LOWER(first_name) = ?', [$parts[1] ?? ''])
                        ->whereRaw('LOWER(second_name) = ?', [$parts[0]]);
                })
                ->orWhereRaw('LOWER(first_name) = ?', [$cleanGuess])
                ->orWhereRaw('LOWER(second_name) = ?', [$cleanGuess]);
        })->first();
    }

    public function isGuessCorrect(string $guess, Student $student): array
    {
        //megnézi hogy a kitalált név megegyezik-e a napi diákkal
        $matchedStudent = $this->findGuessedStudent($guess); //app(StudentGuessService::class)->findGuessedStudent($guess);

        if (!$matchedStudent) {
            return [
                'match' => false,
            ];
        }

        $matchableFields = [
            'first_name',
            'second_name',
            'image',
            'age',
            'birthday',
            'height',
            'release_date_gl',
            'school',
            'club',
            'role',
            'position',
            'class',
            'damage_type',
            'armor_type',
            'weapon_type',
            'unique_equipment_name',
            'unique_equipment_img',
            'memorial_lobby'
        ];

        $equipmentFields = [
            'equipment_1',
            'equipment_2',
            'equipment_3',
        ];

        $fieldsResult = collect($matchableFields)->mapWithKeys(function ($field) use ($matchedStudent, $student) {
            return [$field => $matchedStudent->$field === $student->$field];
        });

        // add equipment fields to boolean matches and determine height status
        $fieldsResult = $fieldsResult->merge(
            collect($equipmentFields)
                ->mapWithKeys(fn($field) => [$field => $matchedStudent->$field === $student->$field])
        );
        if ($matchedStudent->height === $student->height) {
            $heightStatus = 'correct';
        } elseif ($matchedStudent->height > $student->height) {
            $heightStatus = 'above';
        } else {
            $heightStatus = 'below';
        }

        return [
            'match' => $matchedStudent->id === $student->id,
            'fields' => $fieldsResult->toArray(),
            'heightStatus' => $heightStatus,
        ];
    }
}
