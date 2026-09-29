<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\AuthResource;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request, RegisterUser $action): JsonResponse
    {
        return (new AuthResource([
            'user' => $action->handle($request),
            'message' => 'Account created. You can sign in now.',
        ]))->response()->setStatusCode(201);
    }
}
