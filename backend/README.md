<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

### Project authentication

This is the existing Laravel API backend, not a second auth application. Authentication remains bearer-only Sanctum under `/api/v1/auth`; Google uses Laravel session state only for its browser OAuth handshake. See [API routes](routes/api.php), [AuthenticationService](app/Services/AuthenticationService.php), and [auth actions](app/Actions/Auth).

- Public: `POST register`, `login`, `forgot-password`, `reset-password`, `google/exchange`; signed `GET email/verify/{id}/{hash}`.
- Authenticated: `GET/PATCH/DELETE me`, `PUT me/password`, `POST logout`, `logout-all`, `email/verification-notification`.
- Verified bearer session: `GET dashboard` and all existing `/api/v1/admin/*` routes. Account/verification/logout operations remain available before verification.
- Google browser navigation: `GET /auth/google`, `GET /auth/google/callback`. Google access tokens are never persisted or returned to Nuxt; only a 60-second, browser-bound, hashed-at-rest exchange code is redirected.
- Logout removes the current token. Logout-all, reset, and deletion revoke every token and pending Google exchange; reset tokens are hashed, configured-expiry, and consumed on success. Deletion anonymizes and soft-deletes the account consistent with the existing schema, and protects the last administrator.

Verification and reset emails use queued Laravel Markdown mailables through the official Resend transport. Google/Resend keys are intentionally empty in `.env.example`. `MAIL_MAILER=log` is local/setup-only; configure a verified Resend sender and a queue worker before public release. The [deployment guide](deploy/CPANEL-ENV.md) covers cPanel, ngrok, secure cookies, trusted proxies, config caching, and environment setup.

Run `php artisan migrate --no-interaction`, then `php artisan test tests/Feature/Api/V1/Auth tests/Feature/Deployment/ProductionEnvGeneratorTest.php`. [AuthFlowTest](tests/Feature/Api/V1/Auth/AuthFlowTest.php) extends the existing auth tests with Google state/callback/exchange, verification, reset expiry/revocation, profile reverification, and deletion cases. Full-suite verification is recorded in [MASTER.md](../MASTER.md).

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
