<?php

namespace App\Actions\Auth;

use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoginUser
{
    public function __construct(private readonly AuthenticationService $service) {}

    /** @return array{user: User, token: string} */
    public function handle(LoginRequest $request): array
    {
        $email = $request->validated('email');
        $user = User::where('email', $email)->first();

        if (! $user || ! $user->password_set || ! Hash::check($request->validated('password'), $user->password)) {
            event(new Failed('sanctum', $user, ['email' => $email]));
            throw new HttpException(401, 'The supplied credentials are invalid.');
        }

        $token = $this->service->issueToken($user);
        event(new Login('sanctum', $user, false));

        return ['user' => $user, 'token' => $token];
    }
}
