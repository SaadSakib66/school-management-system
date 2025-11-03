<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name'      => 'Super Admin',
                'password'  => Hash::make('12365478'),
                'role'      => 'super_admin',
                'school_id' => null,
                'status'    => 1,
            ]
        );
    }
}
