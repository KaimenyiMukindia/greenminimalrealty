<?php

namespace App\Actions\Auth;

use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoginUser
{
    /** @return array{user: User, token: string} */
    public function handle(LoginRequest $request): array
    {
        $email = $request->validated('email');
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            event(new Failed('sanctum', $user, ['email' => $email]));
            throw new HttpException(401, 'The supplied credentials are invalid.');
        }

        $token = $user->createToken('frontend', ['*'], now()->addMinutes((int) config('sanctum.expiration', 14 * 24 * 60)))->plainTextToken;
        event(new Login('sanctum', $user, false));

        return ['user' => $user, 'token' => $token];
    }
}