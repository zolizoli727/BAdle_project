<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Student;
use Illuminate\Support\Carbon;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = file_get_contents(database_path('bluearchive_characters.json'));
        $datas = json_decode($json);
        if (!is_array($datas)) {
            $datas = [$datas];
        }

        foreach ($datas as $data => $value) {
            Student::create([
                'first_name' => $value->first_name,
                'second_name' => $value->second_name,
                'age' => $value->age,
                'birthday' => $value->birthday,
                'height' => $value->height,
                'designer' => $value->designer,
                'illustrator' => $value->illustrator,
                'voice' => $value->voice,
                'release_date_jp' => $this->normalizeDate($value->release_date_jp ?? null),
                'release_date_gl' => $this->normalizeDate($value->release_date_gl ?? null),
                'school' => $value->school,
                'club' => $value->club,
                'role' => $value->role,
                'position' => $value->position,
                'class' => $value->class,
                'damage_type' => $value->damage_type,
                'armor_type' => $value->armor_type,
                'weapon_type' => $value->weapon_type,
                'equipment_1' => $value->equipment_1,
                'equipment_2' => $value->equipment_2,
                'equipment_3' => $value->equipment_3,
                'unique_equipment_name' => $value->unique_equipment_name,
                'unique_equipment_img' => $value->unique_equipment_img,
                'memorial_lobby' => $value->memorial_lobby,
                'image' => $value->image,
                'updated_at' => now(),
                'created_at' => now()
            ]);
        }
    }

    private function normalizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);
        if ($value === '' || $this->isNonReleasedDate($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function isNonReleasedDate(string $value): bool
    {
        $normalized = strtolower($value);
        return str_contains($normalized, 'not released')
            || str_contains($normalized, 'tbd')
            || str_contains($normalized, 'tba')
            || $normalized === 'unknown';
    }
}
