<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'password' => 'password', 'role' => 'admin'],
            ['name' => 'Manager User', 'email' => 'manager@example.com', 'password' => 'password', 'role' => 'manager'],
            ['name' => 'John Doe', 'email' => 'john@example.com', 'password' => 'password', 'role' => 'user'],
            ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'password' => 'password', 'role' => 'user'],
            ['name' => 'Bob Wilson', 'email' => 'bob@example.com', 'password' => 'password', 'role' => 'user'],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make($user['password']),
                'role' => $user['role'],
            ]);
        }
    }
}
