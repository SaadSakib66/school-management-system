<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Admin User
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('12365478'),
            'role' => 'admin',
        ]);

        // Teacher user
        User::create([
            'name' => 'Teacher',
            'email' => 'teacher@gmail.com',
            'password' => Hash::make('12365478'),
            'role' => 'teacher',
        ]);

        // Student user
        User::create([
            'name' => 'Student',
            'email' => 'student@gmail.com',
            'password' => Hash::make('12365478'),
            'role' => 'student',
        ]);

        // Parent user
        User::create([
            'name' => 'Parent',
            'email' => 'parent@gmail.com',
            'password' => Hash::make('12365478'),
            'role' => 'parent',
        ]);
    }
}
