<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Resources\StatusResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function store(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->validated());

        return (new StatusResource([
            'message' => 'If an account exists for this email, a password reset link has been sent.',
            'code' => 'password_reset_requested',
        ]))->response();
    }
}
