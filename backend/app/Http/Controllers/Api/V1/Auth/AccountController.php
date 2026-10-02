<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\StatusResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $request->user()->update($request->validated());

        return new UserResource($request->user()->fresh());
    }

    public function updatePassword(UpdatePasswordRequest $request): StatusResource
    {
        $request->user()->update(['password' => $request->validated('password')]);
        $currentToken = $request->user()->currentAccessToken();

        if ($currentToken && method_exists($currentToken, 'getKey')) {
            $request->user()->tokens()->where('id', '!=', $currentToken->getKey())->delete();
        }

        return new StatusResource(['message' => 'Password updated. Other sessions were signed out.', 'code' => 'password_updated']);
    }
}