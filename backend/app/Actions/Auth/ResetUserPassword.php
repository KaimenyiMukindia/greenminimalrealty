<?php

namespace App\Actions\Auth;

use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetUserPassword
{
    public function __construct(private readonly AuditAuthEvent $audit) {}

    public function handle(ResetPasswordRequest $request): bool
    {
        $data = $request->validated();
        $status = Password::reset(
            ['email' => $data['email'], 'token' => $data['token'], 'password' => $data['password'], 'password_confirmation' => $data['password_confirmation']],
            function (User $user, string $password) use ($request): void {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                $user->tokens()->delete();
                event(new PasswordResetEvent($user));
            },
        );

        $success = $status === Password::PASSWORD_RESET;

        if (! $success) {
            $this->audit->log($request, 'auth.password_reset_failed', null, ['email' => $data['email']]);
        }

        return $success;
    }
}