<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Mail\Auth\AuthLinkMail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = ['name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = ['password_set' => true];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'password_set' => 'boolean',
        ];
    }

    public function sendEmailVerificationNotification(): void
    {
        $signed = URL::temporarySignedRoute('api.v1.auth.verification.verify', now()->addMinutes(60), [
            'id' => $this->getKey(),
            'hash' => sha1($this->getEmailForVerification()),
        ]);
        parse_str((string) parse_url($signed, PHP_URL_QUERY), $query);
        $url = rtrim(config('app.frontend_url'), '/').'/dashboard/verify-email?'.http_build_query([
            'id' => $this->getKey(), 'hash' => sha1($this->getEmailForVerification()),
            'expires' => $query['expires'], 'signature' => $query['signature'],
        ]);
        Mail::to($this->email)->send((new AuthLinkMail('Verify your email address', $url, 'Verify email', 60))->afterCommit());
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = rtrim(config('app.frontend_url'), '/').'/dashboard/reset-password?'.http_build_query([
            'token' => $token, 'email' => $this->email,
        ]);
        Mail::to($this->email)->send((new AuthLinkMail('Reset your password', $url, 'Reset password', (int) config('auth.passwords.users.expire')))->afterCommit());
    }
}
