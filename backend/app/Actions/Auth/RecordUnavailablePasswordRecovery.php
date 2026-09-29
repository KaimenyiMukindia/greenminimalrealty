<?php

namespace App\Actions\Auth;

use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Models\User;

class RecordUnavailablePasswordRecovery
{
    public function __construct(private readonly AuditAuthEvent $audit) {}

    public function handle(ForgotPasswordRequest $request): void
    {
        $email = $request->validated('email');
        $user = User::where('email', $email)->first();

        $this->audit->log($request, 'auth.password_reset_requested', $user, ['email' => $email]);
    }
}