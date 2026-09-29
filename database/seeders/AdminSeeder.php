<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin User',
                'email' => 'admin@cdn.conzex.com',
                'password' => Hash::make('Adm1n@123'),
                'theme' => 'light',
            ]
        );
    }
}
