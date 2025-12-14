<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $modes = [
            ['key' => 'classic', 'name' => 'Classic', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'hard', 'name' => 'Hard', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'image', 'name' => 'Image', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('game_modes')->upsert($modes, ['key'], ['name', 'updated_at']);
    }
}
