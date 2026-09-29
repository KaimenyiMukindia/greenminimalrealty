<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment() !== 'local' && ! config('services.admin_seed.enabled')) {
            return;
        }

        $email = config('services.admin_seed.email');
        $password = config('services.admin_seed.password');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            return;
        }

        User::updateOrCreate(
            ['email' => mb_strtolower($email)],
            [
                'name' => config('services.admin_seed.name'),
                'password' => Hash::make($password),
                'role' => UserRole::Admin,
            ],
        );
    }
}