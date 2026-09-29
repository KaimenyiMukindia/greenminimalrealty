<?php

namespace App\Observers;

use App\Actions\Auth\AuditAuthEvent;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;

class AuditLogObserver
{
    public function __construct(private readonly AuditAuthEvent $audit) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Registered::class, [self::class, 'registered']);
        $events->listen(Login::class, [self::class, 'login']);
        $events->listen(Logout::class, [self::class, 'logout']);
        $events->listen(PasswordReset::class, [self::class, 'passwordReset']);
        $events->listen(Failed::class, [self::class, 'failed']);
    }

    public function registered(Registered $event): void
    {
        $this->audit->log(request(), 'auth.registered', $event->user, ['email' => $event->user->email]);
    }

    public function login(Login $event): void
    {
        $this->audit->log(request(), 'auth.login_succeeded', $event->user, ['email' => $event->user->email]);
    }

    public function logout(Logout $event): void
    {
        if ($event->user) {
            $action = request()->routeIs('api.v1.auth.logout-all') ? 'auth.logout_all' : 'auth.logout';
            $this->audit->log(request(), $action, $event->user, ['email' => $event->user->email]);
        }
    }

    public function passwordReset(PasswordReset $event): void
    {
        $this->audit->log(request(), 'auth.password_reset', $event->user, ['email' => $event->user->email]);
    }

    public function failed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;
        $this->audit->log(request(), 'auth.login_failed', $event->user, [
            'email' => is_string($email) ? mb_substr($email, 0, 255) : null,
        ]);
    }
}
