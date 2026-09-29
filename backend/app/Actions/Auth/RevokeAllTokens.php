<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class RevokeAllTokens
{
    public function handle(Request $request, User $user): void
    {
        $user->tokens()->delete();
        event(new Logout('sanctum', $user));
    }
}