<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;

class SchoolBackfillSeeder extends Seeder
{
    public function run(): void
    {
        School::firstOrCreate(
            ['short_name' => 'DEFAULT'],
            ['name' => 'BARABD School', 'status' => 1]
        );
    }
}
