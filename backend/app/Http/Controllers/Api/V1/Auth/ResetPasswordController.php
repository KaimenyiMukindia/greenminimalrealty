<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ResetUserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\StatusResource;
use Illuminate\Http\JsonResponse;

class ResetPasswordController extends Controller
{
    public function store(ResetPasswordRequest $request, ResetUserPassword $action): JsonResponse
    {
        $success = $action->handle($request);

        return (new StatusResource([
            'message' => $success ? 'Password reset successfully.' : 'The password reset token is invalid or expired.',
            'code' => $success ? 'password_reset' : 'invalid_reset_token',
        ]))->response()->setStatusCode($success ? 200 : 422);
    }
}