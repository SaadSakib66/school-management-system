<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WeekSeeder extends Seeder {
    public function run(): void {
        $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        DB::table('weeks')->truncate();
        foreach ($days as $i => $name) {
            DB::table('weeks')->insert([
                'name' => $name,
                'sort' => $i, // 0..6
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
