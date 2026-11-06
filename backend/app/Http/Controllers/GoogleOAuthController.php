<?php

namespace App\Http\Controllers;

use App\Http\Requests\GoogleRedirectRequest;
use App\Services\AuthenticationService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GoogleOAuthController extends Controller
{
    public function redirect(GoogleRedirectRequest $request): RedirectResponse
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret') || ! config('services.google.redirect')) {
            return $this->failure('google_unavailable');
        }
        $request->session()->regenerate();
        $request->session()->put('google_challenge', $request->validated('challenge'));
        $request->session()->put('google_started_at', now()->timestamp);

        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request, AuthenticationService $service): RedirectResponse
    {
        $challenge = $request->session()->pull('google_challenge');
        $started = $request->session()->pull('google_started_at');
        if ($request->has('error') || ! is_string($challenge) || ! is_int($started) || $started < now()->subMinutes(10)->timestamp) {
            $request->session()->invalidate();

            return $this->failure('google_failed');
        }
        try {
            $provider = Socialite::driver('google');
            $google = $provider->user();
            $code = $service->createGoogleExchange($google, $challenge);
            $request->session()->invalidate();

            return redirect()->away(rtrim(config('app.frontend_url'), '/').'/dashboard/oauth/callback?'.http_build_query(['code' => $code]))
                ->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
        } catch (InvalidStateException|GuzzleException|HttpException $exception) {
            Log::warning('Google sign-in failed.', ['reason' => $exception::class]);
            $request->session()->invalidate();

            return $this->failure('google_failed');
        }
    }

    private function failure(string $error): RedirectResponse
    {
        return redirect()->away(rtrim(config('app.frontend_url'), '/').'/dashboard/oauth/callback?error='.$error)
            ->withHeaders(['Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer']);
    }
}
