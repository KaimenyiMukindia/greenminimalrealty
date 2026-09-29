# Immutable Rules

Laravel is the sole logic layer. All auth, validation, business rules, persistence, email, and authorization live in Laravel.
Nuxt is the sole view layer. It consumes Laravel APIs and renders. No business logic. No auth logic beyond cookie presence checks.
Zero Blade webpages. Laravel returns JSON only for /api/*. Transactional emails use Laravel Markdown mailables (not Blade views).
Tailwind CSS everywhere on Nuxt — public pages and dashboard.
Auth driver: Laravel Sanctum Bearer tokens. Token lives only in an httpOnly, Secure, SameSite=Lax cookie set by a Nuxt server route.
Cross-subdomain deployment: Laravel API on api.*, Nuxt on www.* or app.*. CORS allowlist is env-driven, never wildcard.
All endpoints versioned: /api/v1/....
All responses via API Resources. Never return raw Eloquent models.
All input via Form Requests. No inline validation in controllers.
Registration is open (self-serve by email).
Email verification is required before any authenticated dashboard access.
Password reset is required, tokenized, single-use, 60-minute expiry.
Nuxt API calls must go through Nuxt server routes (server/api/*) acting as a thin proxy. The browser must never call the Laravel origin directly.
Scalability is a first-class requirement for both APIs (paginated, reorderable, filterable) and Nuxt pages (dynamic routes, config-driven nav, no per-item hardcoding).
MASTER.md is the single source of truth for state. Every task reads it first and appends to it last. Never overwrite prior entries.
No secrets in Nuxt client bundle. No secrets committed to git. .env.example documents every key.

# Security Protocol

### Laravel
LaravelPassword hashing: bcrypt, cost 12. Never store plaintext. Never log passwords.
Password policy: min 10 chars, uppercase, lowercase, digit, symbol. Enforced via Password::defaults().
Rate limits: register throttle:3,1; login throttle:6,1; forgot-password throttle:3,1; reset-password throttle:6,1; resend verification throttle:3,1.
Email verification links: signed URLs, 60-minute expiry.
Password reset tokens: hashed at rest, 60-minute expiry, single-use. Delete on successful reset.
Token expiry: Sanctum tokens expire in 14 days. Idle timeout of 2 hours via personal_access_tokens.last_used_at and custom middleware.
Failed login logging: every failed attempt writes to audit_logs with IP, user agent, submitted email. Never the password.
No user enumeration: forgot-password always returns 200 with identical body regardless of whether the email exists.
CORS: explicit allowed_origins from FRONTEND_URL env. supports_credentials: false. allowed_methods: [GET, POST, PUT, PATCH, DELETE, OPTIONS]. allowed_headers: [Content-Type, Authorization, Accept, X-Requested-With].
Exception handler: all /api/* errors return JSON { message, errors?, code }. Never HTML. Never stack traces in production.
.env never committed. .env.example lists all keys with safe placeholders.
### Nuxt
NuxtSession token in httpOnly + Secure + SameSite=Lax cookie set by Nuxt server route.All authenticated requests forward the cookie to Laravel via Nuxt server proxy. Laravel is the authority.Nuxt middleware only checks presence of the cookie. It never validates the token. On any 401 from Laravel, Nuxt clears the cookie and redirects to login.No secrets in runtimeConfig.public. Server-only secrets in runtimeConfig (private).

# Environment

- Workspace was empty at task start; framework installs, configuration and runtime commands have not yet been verified.
- Laravel version, Sanctum migration, queue driver, mail driver, Nuxt version, Tailwind, and SSR status: pending pre-flight verification.
- Required Laravel environment keys: FRONTEND_URL, SANCTUM_STATEFUL_DOMAINS, SESSION_DOMAIN, SESSION_SECURE_COOKIE, SESSION_SAME_SITE, mail keys, queue keys, ADMIN_EMAIL, ADMIN_PASSWORD, SEED_ADMIN.
- Required Nuxt environment keys: LARAVEL_API_URL and NUXT_SESSION_COOKIE_NAME.
- Environment files must use safe placeholders and remain uncommitted.
- Verified toolchain: PHP 8.2.12, Composer 2.9.7, Node 22.22.3, npm 10.9.8. Laravel 13.17 requires PHP ^8.3; its 13.10.1 skeleton was fetched, but Composer dependencies, Artisan, migrations, Sanctum installation, and Laravel tests are blocked until PHP 8.3+ is available.
- Nuxt 4.5.2, SSR enabled by default, Tailwind CSS 4.1, and the Node server build are configured. Frontend dependencies are installed. Nuxt server proxy uses private runtimeConfig laravelApiUrl; the browser only receives the non-secret cookie name.
- Laravel queue choice: database (jobs migration is present; production must run a queue worker). Local mail driver: log. Production mail plan: configure SMTP or a transactional mail provider and run the database queue worker.
- Laravel .env.example documents frontend/CORS, Sanctum, session, database, queue, mail, hashing, and guarded admin-seeder keys. Nuxt .env and .env.example document LARAVEL_API_URL, NUXT_SESSION_COOKIE_NAME, and cookie security. Local .env files are ignored by the respective project gitignore files.
- Laravel database migration status: unverified because Artisan is unavailable under PHP 8.2. Nuxt SSR production build: passed.
- Resolution (2026-09-29): changed backend composer constraints from Laravel 13 to Laravel 12, ran Composer successfully on PHP 8.2.12, and pinned the lockfile to laravel/framework 12.69.3, laravel/sanctum 4.3.3, and PHPUnit 11.5.56. APP_KEY is configured in ignored backend/.env. `php artisan migrate:fresh --force` succeeded and `php artisan migrate:status` reports all six migrations Ran. Laravel tests now pass: 26 tests, 93 assertions. Nuxt build remains passing.
- Laravel 12 release support: PHP 8.2–8.5; Laravel 11 also supports PHP 8.2 but is no longer security-supported after 2026-03-12. Laravel 12 security fixes run through 2027-02-24. Laravel 13 requires PHP 8.3+. Official references are listed under # Decisions.
- Root workspace is not a Git repository. Both Laravel and Nuxt .gitignore files exclude their local .env; no secrets are in .env.example.

# Decisions

- Pending framework inspection. Record package, test-runner, queue, mail, and architecture decisions here.
- Version decision (2026-09-29): Laravel 12.x is the highest Laravel major compatible with PHP 8.2.12; Laravel 12's official release table lists PHP 8.2–8.5 and its official application skeleton composer.json requires PHP ^8.2 / laravel/framework ^12.0. Laravel 13 requires PHP ^8.3 (its skeleton requires PHP ^8.3 and laravel/framework ^13.17). The prompt's Laravel 11 ceiling was conservative/outdated; Laravel 12 is selected. Sources: https://laravel.com/docs/12.x/releases, https://laravel.com/docs/11.x/releases, https://raw.githubusercontent.com/laravel/laravel/12.x/composer.json, https://raw.githubusercontent.com/laravel/laravel/11.x/composer.json, https://raw.githubusercontent.com/laravel/laravel/13.x/composer.json.
- Test runner: PHPUnit, selected because the Laravel skeleton includes PHPUnit and its Composer manifest does not include Pest.
- Architecture: Laravel 13 API in backend/ and Nuxt 4 SSR shell in frontend/. Sanctum personal access bearer tokens are stored only in an HTTP-only, Secure, SameSite=Lax Nuxt cookie; the login proxy removes the token from its browser-visible response.
- Nuxt authentication middleware checks only cookie presence. Since an HTTP-only cookie is unreadable in browser JavaScript, client-side route transitions query a same-origin Nuxt session-presence route; Laravel remains the token authority.
- Queue: database for local and initial deployment configuration. Mail: log locally; production SMTP/transactional provider required.
- Deviation: verification resend accepts an email without auth:sanctum (with throttle and a generic response) so a user who cannot sign in before verifying can still request another verification email. The brief's route table says auth:sanctum, which conflicts with that lifecycle.
- Blocker: the machine only has PHP 8.2.12, while Laravel 13 requires PHP ^8.3. Sanctum is declared in composer.json but not installed and no Laravel Artisan/test/migration command could be verified.
- Resolution: replaced the Laravel 13 scaffold dependency baseline with the official Laravel 12 PHP ^8.2 / framework ^12 skeleton constraints; PHPUnit is version 11.5.56. Laravel 12 docs confirm the used APIs: bootstrap exception response customization, middleware ordering, Sanctum bearer token issuing and expiration, email verification events, Markdown queued mailables, password broker reset, and API resources. Sources: https://laravel.com/docs/12.x/errors, https://laravel.com/docs/12.x/middleware, https://laravel.com/docs/12.x/sanctum, https://laravel.com/docs/12.x/verification, https://laravel.com/docs/12.x/passwords, https://laravel.com/docs/12.x/events, https://laravel.com/docs/12.x/mail, https://laravel.com/docs/12.x/eloquent-resources.
- No Laravel 13-only application APIs needed replacement; Laravel 12 documentation confirms the current bootstrap middleware/exception hooks, resource classes, event subscriber, token expiration, verification, reset broker, and Markdown mailable interfaces. The scaffold Composer constraints and PHPUnit/Pail/Pint/Tinker versions were downgraded to Laravel 12 compatible constraints.
- Temporary user-directed override (2026-09-29): until a mailer is configured, email verification is not required for registration, login, or authenticated routes. No application code sends or queues email. Verification routes/pages, mailables, Markdown templates, and their tests have been removed. Legacy email_verified_at schema data is retained but ignored by auth and omitted from UserResource.
- Password recovery is the sole intentionally paused auth feature: POST /api/v1/auth/forgot-password returns the same 503 `password_reset_unavailable` JSON regardless of account existence and sends no email. POST /api/v1/auth/reset-password still validates and consumes an already available valid single-use token; no token delivery mechanism is active until mail is configured. Other auth flows remain operational.

# Task Log

- Initialized this source-of-truth document with immutable and security rules from the Phase 1 brief. Framework pre-flight and implementation pending.
- Created Laravel 13 skeleton and implemented auth resources, Form Requests, actions, controllers, signed verification, single-use reset flow, rate limits, audit logging, migrations, CORS/Sanctum/password config, queued Markdown mailables, guarded admin seeder, and PHPUnit endpoint tests.
- Implemented Nuxt 4 auth pages, server proxy routes, secure token cookie, session-presence middleware, dashboard shell/navigation, reusable form/dashboard components, and Tailwind styling. Nuxt production build passes.
- Verification email link now targets /dashboard/verify-email with the signed Laravel query preserved; that page proxies verification and also supports resend.
- Backend PHP syntax lint passes for app, routes, migrations, and tests. Laravel feature tests and manual cURL/browser walkthrough are not run because PHP 8.3+ and Composer-installed dependencies are unavailable.
- Final Nuxt production build passed after verification email links and client middleware were aligned; VS Code reports no frontend errors. Final PHP lint passed on the touched verification, audit, route, controller, action, migration, and test files.
- Environment completion: Laravel Framework 12.69.3 and Sanctum 4.3.3 installed with PHP 8.2.12; Composer lockfile generated. Database migrations all ran successfully against local SQLite. Full PHPUnit result: 26 passed, 0 failed, 93 assertions. The production Nuxt build passed in the previous task; no frontend code changed in this downgrade follow-up.
- Email-free auth update: verification enforcement/endpoints/UI and all mailable dispatch paths removed; registration and login require no email. Forgot-password now explicitly reports recovery unavailable without leaking account existence. Reset-by-token endpoint remains. Sidebar only lists implemented Overview to avoid links to deferred pages.
- Verification after email-free update: Laravel PHPUnit 21 passed / 0 failed (82 assertions); 66 backend PHP files lint clean; Composer manifest valid; Nuxt production build passed; no frontend diagnostics errors. Browser walkthrough at http://127.0.0.1:3000/dashboard/login: created a user without email, signed in without verification, loaded dashboard profile, signed out to login, and confirmed forgot-password page displays the paused recovery notice. Local servers are running at http://127.0.0.1:3000 and http://127.0.0.1:8000.
- Migration status: users/password reset/sessions, cache, jobs, audit_logs, personal_access_tokens, and users role/soft-delete migrations are applied in the local SQLite database. Artisan route listing confirms RequireVerifiedAndActiveToken runs before Authenticate:sanctum for `/me`.
- Initial Laravel 12 test run found two failures: stale-token test hit Sanctum authentication before idle middleware, and the Laravel starter root-page test expected a public webpage in an API-only app. Corrected middleware priority using Laravel 12's documented AuthenticatesRequests priority marker, changed the test to advance time, and removed the incompatible starter web-page test. Final suite is green.
- Final verification after those fixes: composer.json valid; 75 backend PHP files lint clean; six migrations report Ran; 26 PHPUnit tests pass with 93 assertions; no frontend errors. Manual cURL and browser E2E walkthrough remain pending.
- Latest auth mode supersedes prior email-verification assumptions in the original Phase 1 checklist by direct user instruction: no verification links or outbound mail; immediate sign-in works. Manual email-dependent reset completion is unavailable until a mailer is configured.
- Endpoint test files cover each auth endpoint with happy paths and selected validation, authentication, duplicate/invalid-token, enumeration, and rate-limit cases. Full per-field validation-rule matrix and all endpoint test runs remain unverified.
- Endpoints implemented: POST /api/v1/auth/register (throttle:3,1), POST /api/v1/auth/login (throttle:6,1), POST /api/v1/auth/logout (verified Sanctum token + 2h idle middleware), POST /api/v1/auth/logout-all (same), GET /api/v1/auth/me (same), POST /api/v1/auth/email/verification-notification (throttle:3,1; see resend deviation), GET /api/v1/auth/email/verify/{id}/{hash} (signed + throttle:6,1), POST /api/v1/auth/forgot-password (throttle:3,1), POST /api/v1/auth/reset-password (throttle:6,1).
- Manual cURL protocol (not yet executed): use `curl -i -H "Accept: application/json" -H "Content-Type: application/json" -d '{...}' http://127.0.0.1:8000/api/v1/auth/{register,login,forgot-password,reset-password,email/verification-notification}`; use `curl -i -H "Accept: application/json" -H "Authorization: Bearer <token>" http://127.0.0.1:8000/api/v1/auth/me`, and the same bearer header with `-X POST` for `/logout` and `/logout-all`. Capture real status, headers, and response bodies after PHP 8.3+ dependencies are installed; cover validation (422), unauthorized/forbidden (401/403), throttling (429), signed-link tampering (403), and success (200/201).
- Browser end-to-end walkthrough (documented, not executed): register at /dashboard/register; confirm log mail/queue; sign-in before verification and confirm it is blocked; open signed email link and verify; sign in and land on /dashboard; refresh and confirm auth; sign out and confirm login redirect; request reset, consume reset link, sign in with new password, reuse reset link and confirm rejection; try duplicate registration and weak password; submit seven wrong logins and confirm throttle; clear the HTTP-only session cookie and confirm dashboard redirect. Inspect network and cookie settings for Secure, HttpOnly, SameSite=Lax and ensure browser requests stay on the Nuxt origin.

# Bugs Debugged

- None recorded.
- The preceding line records initial state only; bugs found and fixed during this task follow.
- Laravel 13 -> 12 downgrade: Composer constraints referenced PHP ^8.3, Laravel 13, PHPUnit 12 and a Laravel 13-only Pail/Pao set. Updated backend/composer.json to the Laravel 12 PHP ^8.2 baseline, removed Pao, selected PHPUnit 11 and compatible Tinker/Pail/Pint constraints; Composer resolved Laravel 12.69.3 successfully. Regression risk: maintain composer.lock with composer.json and verify PHP platform whenever changing Laravel major versions.
- Idle-token test initially passed unexpectedly because middleware priority placed auth:sanctum first and Sanctum touched last_used_at before idle validation. Added documented Laravel 12 priority ordering and verified actual route:list order; regression risk: retain the custom gate before auth:sanctum.
- Removed the generated example feature test for GET / because this API-only Laravel app intentionally has no Blade/web landing page; regression risk: keep the Laravel web route file page-free.
- Fixed registration throttle test expecting 200 instead of the endpoint's 201; regression risk: future response-status changes must update both contract and test.
- Fixed Nuxt login proxy returning Laravel's bearer token in browser-visible JSON after it had been copied into the cookie; regression risk: never pass access_token through a client response.
- Fixed user-role migration initially omitting editor, audit_logs schema initially using non-contract column names, and moved registration/login/verification/logout/reset auditing to Laravel lifecycle events; regression risk: keep migrations aligned with the role enum and action/ip/meta/timestamps contract and avoid duplicate event+action audit writes.
- Initial Nuxt dependency install ran from the repository root and failed; reran inside frontend/ successfully. Regression risk: frontend npm commands must execute from frontend/.
- Removing the user notification methods briefly left User.php missing its class-closing brace; PHP lint caught it, the brace was restored, and all final tests pass. Regression risk: rerun syntax lint and PHPUnit after notification lifecycle changes.

# Future Tasks

- Phase 2: to be specified after Phase 1; do not implement in this task.
- Phase 3: content models and management screens, public-facing rebuild, media uploads, SEO/resources, integrations, and role/permission system beyond the existing user role are explicitly deferred.
- Phase 2+ backlog from the explicit exclusions: public-facing rebuild; Property, Service, Testimonial, Stat and other content models/CRUD; uploads; SEO/meta resources; integrations; expanded role/permission system.
- Phase 3 dashboard work: use the delivered DataTable, Pagination, EmptyState, and LoadingSkeleton stubs on scalable section and detail routes; no such data views are implemented in Phase 1.
- Before considering Phase 1 complete, install PHP 8.3+, run Composer install, install/migrate Sanctum, generate APP_KEY, run database migrations and PHPUnit tests, start queue/mail services, and complete the documented cURL and browser walkthrough.
- Environment-constraint resolution: PHP 8.3+ is not needed for this workspace after selecting Laravel 12.69.3. Remaining Phase 1 verification is the manual cURL/browser walkthrough and exercising queued emails with a worker; automated migrations and PHPUnit have passed.
- Configure a transactional mailer before re-enabling verification emails or email-driven password recovery. Until then, keep the no-verification/no-outbound-email mode and explicit 503 recovery response.
