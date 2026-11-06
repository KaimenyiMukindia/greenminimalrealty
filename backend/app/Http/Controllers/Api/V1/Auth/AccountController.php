<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\DeleteAccountRequest;
use App\Http\Requests\Api\V1\Auth\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Resources\StatusResource;
use App\Http\Resources\UserResource;
use App\Services\AuthenticationService;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function updateProfile(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $emailChanged = $request->validated('email') !== $user->email;
        DB::transaction(function () use ($request, $user, $emailChanged): void {
            if ($emailChanged) {
                DB::table('password_reset_tokens')->where('email', $user->email)->delete();
                DB::table('oauth_exchange_codes')->where('user_id', $user->id)->delete();
                $user->forceFill(['email_verified_at' => null, 'google_id' => null]);
            }
            $user->fill($request->safe()->only(['name', 'email']))->save();
            if ($emailChanged) {
                $user->sendEmailVerificationNotification();
            }
        });

        return new UserResource($request->user()->fresh());
    }

    public function updatePassword(UpdatePasswordRequest $request): StatusResource
    {
        $request->user()->update(['password' => $request->validated('password')]);
        DB::table('oauth_exchange_codes')->where('user_id', $request->user()->id)->delete();
        $currentToken = $request->user()->currentAccessToken();

        if ($currentToken && method_exists($currentToken, 'getKey')) {
            $request->user()->tokens()->where('id', '!=', $currentToken->getKey())->delete();
        }

        return new StatusResource(['message' => 'Password updated. Other sessions were signed out.', 'code' => 'password_updated']);
    }

    public function destroy(DeleteAccountRequest $request, AuthenticationService $service): StatusResource
    {
        $service->deleteAccount($request->user());

        return new StatusResource(['message' => 'Account deleted. All sessions were signed out.', 'code' => 'account_deleted']);
    }
}
