<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $username = env('ADMIN_USERNAME', 'admin');
        $email = env('ADMIN_EMAIL', 'admin@cdn.conzex.com');
        $password = env('ADMIN_PASSWORD', 'Adm1n@123');

        User::updateOrCreate(
            ['username' => $username],
            [
                'name' => ucfirst($username) . ' User',
                'email' => $email,
                'password' => Hash::make($password),
                'theme' => 'light',
            ]
        );
    }
}
