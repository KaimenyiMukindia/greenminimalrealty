<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureActiveToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainTextToken = $request->bearerToken();
        $accessToken = $plainTextToken ? PersonalAccessToken::findToken($plainTextToken) : null;

        if (! $accessToken) {
            throw new HttpException(401, 'Unauthenticated.');
        }

        $lastActivity = $accessToken->last_used_at ?? $accessToken->created_at;

        if (! $lastActivity || $lastActivity->lt(now()->subHours(2))) {
            $accessToken->delete();
            throw new HttpException(401, 'The access token has expired due to inactivity.');
        }

        Auth::shouldUse('sanctum');
        $user = Auth::guard('sanctum')->user();

        if (! $user) {
            throw new HttpException(401, 'Unauthenticated.');
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}