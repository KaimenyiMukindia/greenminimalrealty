<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RecordUnavailablePasswordRecovery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Resources\StatusResource;
use Illuminate\Http\JsonResponse;

class ForgotPasswordController extends Controller
{
    public function store(ForgotPasswordRequest $request, RecordUnavailablePasswordRecovery $action): JsonResponse
    {
        $action->handle($request);

        return (new StatusResource([
            'message' => 'Password recovery is temporarily unavailable while outbound email is disabled.',
            'code' => 'password_reset_unavailable',
        ]))->response()->setStatusCode(503);
    }
}
