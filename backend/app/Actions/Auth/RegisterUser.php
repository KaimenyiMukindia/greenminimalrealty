<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

class RegisterUser
{
    public function handle(RegisterRequest $request): User
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => UserRole::User,
        ]);

        event(new Registered($user));

        return $user;
    }
}