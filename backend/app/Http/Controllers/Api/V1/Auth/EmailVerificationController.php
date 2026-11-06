<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\StatusResource;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EmailVerificationController extends Controller
{
    public function verify(Request $request, int $id, string $hash): StatusResource
    {
        $user = User::findOrFail($id);
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            throw new HttpException(403, 'The verification link is invalid.');
        }
        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return new StatusResource(['message' => 'Your email address has been verified.', 'code' => 'email_verified']);
    }

    public function store(Request $request): StatusResource
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return new StatusResource(['message' => 'If verification is needed, a verification email has been sent.', 'code' => 'verification_requested']);
    }
}
