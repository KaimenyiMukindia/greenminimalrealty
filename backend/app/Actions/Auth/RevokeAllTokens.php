<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevokeAllTokens
{
    public function handle(Request $request, User $user): void
    {
        $user->tokens()->delete();
        DB::table('oauth_exchange_codes')->where('user_id', $user->id)->delete();
        event(new Logout('sanctum', $user));
    }
}
