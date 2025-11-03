<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeekSeeder extends Seeder
{
    public function run(): void
    {
        // 🔒 Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 🧹 Truncate the table safely
        DB::table('weeks')->truncate();

        // 🔓 Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 🗓️ Insert week days
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach ($days as $i => $name) {
            DB::table('weeks')->insert([
                'name' => $name,
                'sort' => $i, // 0..6
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
