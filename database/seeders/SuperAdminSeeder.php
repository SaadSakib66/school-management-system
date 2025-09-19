<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name'      => 'Super Admin',
                'password'  => '12365478', // auto-hashed by cast
                'role'      => 'super_admin',
                'school_id' => null,
                'status'    => 1,
            ]
        );
    }
}
