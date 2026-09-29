<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class RevokeCurrentToken
{
    public function handle(Request $request, User $user): void
    {
        $user->currentAccessToken()?->delete();
        event(new Logout('sanctum', $user));
    }
}