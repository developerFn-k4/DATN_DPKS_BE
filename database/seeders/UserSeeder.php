<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN
        User::create([
            'name' => 'Admin Account',
            'email' => 'admin@gmail.com',
            'role' => 'admin',
            'password' => Hash::make('123456'),
        ]);

        // CUSTOMER
        User::create([
            'name' => 'Customer Account',
            'email' => 'customer@gmail.com',
            'role' => 'customer',
            'password' => Hash::make('123456'),
        ]);
    }
}
