<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\LoginUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\AuthResource;

class LoginController extends Controller
{
    public function store(LoginRequest $request, LoginUser $action): AuthResource
    {
        return new AuthResource($action->handle($request) + ['message' => 'Signed in successfully.']);
    }
}
