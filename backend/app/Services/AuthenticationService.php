<?php

namespace App\Services;

use App\Actions\Auth\AuditAuthEvent;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as GoogleUser;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthenticationService
{
    public function issueToken(User $user): string
    {
        return $user->createToken('frontend', ['*'], now()->addMinutes((int) config('sanctum.expiration')))->plainTextToken;
    }

    public function createGoogleExchange(GoogleUser $google, string $challenge): string
    {
        $email = mb_strtolower(trim((string) $google->getEmail()));
        $googleId = (string) $google->getId();
        if (($google->user['email_verified'] ?? false) !== true || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 255 || $googleId === '' || strlen($googleId) > 255) {
            throw new HttpException(403, 'Google must provide a verified email address.');
        }

        return DB::transaction(function () use ($google, $googleId, $email, $challenge): string {
            $user = User::withTrashed()->where('google_id', $googleId)->orWhere('email', $email)->lockForUpdate()->first();
            if ($user && ($user->trashed() || ! $user->hasVerifiedEmail() || $user->email !== $email
                || ($user->google_id !== null && $user->google_id !== $googleId))) {
                throw new HttpException(403, 'This account cannot be linked to Google. Sign in with your existing credentials.');
            }
            if (! $user) {
                $user = new User;
                $user->forceFill([
                    'name' => mb_substr($google->getName() ?: 'Google user', 0, 120),
                    'email' => $email, 'password' => Str::random(64), 'password_set' => false,
                    'role' => UserRole::User, 'email_verified_at' => now(),
                ]);
                $user->save();
                event(new Registered($user));
            }
            $user->forceFill(['google_id' => $googleId])->save();
            $code = Str::random(64);
            DB::table('oauth_exchange_codes')->where('expires_at', '<=', now())->delete();
            DB::table('oauth_exchange_codes')->insert([
                'code_hash' => hash('sha256', $code), 'user_id' => $user->id,
                'challenge' => $challenge, 'expires_at' => now()->addSeconds(60),
            ]);

            return $code;
        }, 3);
    }

    /** @return array{user: User, token: string} */
    public function exchangeGoogleCode(string $code, string $verifier): array
    {
        return DB::transaction(function () use ($code, $verifier): array {
            $hash = hash('sha256', $code);
            $exchange = DB::table('oauth_exchange_codes')->where('code_hash', $hash)->where('expires_at', '>', now())->first();
            if (! $exchange || ! hash_equals($exchange->challenge, hash('sha256', $verifier))) {
                throw new HttpException(422, 'The Google exchange code is invalid or expired.');
            }
            $user = User::whereKey($exchange->user_id)->lockForUpdate()->first();
            if (! $user || ! $user->hasVerifiedEmail()
                || DB::table('oauth_exchange_codes')->where('code_hash', $hash)->where('expires_at', '>', now())->delete() !== 1) {
                throw new HttpException(422, 'The Google exchange code is invalid or expired.');
            }
            $token = $this->issueToken($user);
            event(new Login('sanctum', $user, false));

            return ['user' => $user, 'token' => $token];
        }, 3);
    }

    public function deleteAccount(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($user->role === UserRole::Admin && User::where('role', UserRole::Admin)->count() <= 1) {
                throw new HttpException(422, 'The last administrator cannot be removed.');
            }
            app(AuditAuthEvent::class)->log(request(), 'auth.account_deleted', $user);
            $user->tokens()->delete();
            DB::table('oauth_exchange_codes')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->forceFill([
                'name' => 'Deleted account', 'email' => 'deleted-'.$user->id.'-'.Str::uuid().'@example.invalid',
                'google_id' => null, 'email_verified_at' => null, 'remember_token' => null,
                'password' => Str::random(64), 'password_set' => false,
            ])->save();
            $user->delete();
        }, 3);
    }
}
