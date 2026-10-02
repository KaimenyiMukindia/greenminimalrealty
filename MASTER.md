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
- User-directed temporary mode (2026-09-29; supersedes earlier pending mail/verification statements and applicable email-related immutable/security bullets above until mailer configuration): email verification is disabled. Registration automatically enables sign-in, login and authenticated routes ignore legacy email_verified_at values, and verification endpoints/UI/mailables/templates/tests are removed. No application code under backend/app sends or queues email.
- Password recovery cannot email reset tokens while mail is unavailable. POST /api/v1/auth/forgot-password validates and audits but sends no mail and returns stable JSON status 503 with code password_reset_unavailable for every valid email, avoiding enumeration. POST /api/v1/auth/reset-password remains functional for a valid existing token; token issuance/delivery is paused. The Nuxt forgot-password page explains this clearly.
- Live browser check with Laravel on 127.0.0.1:8000 and Nuxt on 127.0.0.1:3000: opened login; registered Browser Demo User at browser-demo@example.test without mail delivery; signed in immediately without verification; dashboard loaded the profile; sign-out redirected to login; forgot-password displayed the paused recovery message. Do not treat this temporary test account as a production seed.
- Updated backend tests to assert zero outbound mail during registration and recovery. Final PHPUnit: 21 passed, 0 failed, 82 assertions. Composer validate passed; 66 Laravel PHP files passed syntax lint; route:list shows seven remaining API endpoints; Nuxt production build passed and VS Code reports no frontend errors.
- Remaining routes: POST /api/v1/auth/register (throttle 3/min), POST /api/v1/auth/login (6/min), GET /api/v1/auth/me (active Sanctum bearer token, 14-day expiry, 2-hour idle), POST /api/v1/auth/logout and POST /api/v1/auth/logout-all (same auth gate), POST /api/v1/auth/forgot-password (3/min, stable 503 while mail disabled), POST /api/v1/auth/reset-password (6/min; usable when a valid token already exists). Email verify/resend endpoints are intentionally removed.
- Browser-serving status: development servers were launched for Laravel and Nuxt and the running browser page is available at http://127.0.0.1:3000/dashboard/login. Re-run `php artisan serve --host 127.0.0.1 --port 8000` from backend/ and `npm run dev -- --host 127.0.0.1 --port 3000` from frontend/ if those terminals have been closed.
- Deferred until mailer setup: restore MustVerifyEmail, verification routes/UI/mails, and email-driven reset-link delivery only after configuring SMTP or a transactional provider. Update this temporary decision and rerun auth, mail, and browser tests then.
- Configure a transactional mailer before re-enabling verification emails or email-driven password recovery. Until then, keep the no-verification/no-outbound-email mode and explicit 503 recovery response.

# Phase 2 Task Log (2026-09-29)

- Added the Phase 2 content schema migration for site settings, page metadata, media, stats, values, services/items, properties/tags/images, sustainability pillars, testimonials, and contact submissions. Content tables use ordering/publication indexes and soft deletes where applicable.
- Added Eloquent models with casts, fillable fields, soft deletes, and eager-loadable nested relationships for services and properties.
- Added published public API resources for settings, page metadata, stats, values, services, properties, sustainability pillars, and testimonials. Public lists cap `per_page` at 100; properties support featured, beds, and baths filters. Added validated/rate-limited public contact intake.
- Added `ContentSeeder`, wired into `DatabaseSeeder`, copying every local image asset from `GreenMinimal/*_files/` into the public disk and seeding saved page metadata, site settings, properties, services, stats, values, pillars, and testimonials.
- Added Nuxt public proxy routes and a settings-driven public layout plus API-driven SSR homepage using stable `useAsyncData` keys and `useSeoMeta`.
- Evidence: clean `php artisan migrate:fresh --seed --force` completed; focused public API tests pass; full Laravel suite passes 24 tests / 95 assertions; Nuxt production build passes.

# Phase 2 Current Gaps

- The requested Phase 2 is not complete: the remaining six public routes, full verbatim section parity, dashboard CRUD/reorder/publish/media screens, admin controllers/policies/form requests, cache observers, media upload endpoint, complete feature-test matrix, and visual/browser E2E evidence remain to be implemented.
- Seeded media URLs assume Laravel's public storage link is created during deployment; the copied files are present under `storage/app/public/uploads/`.

# Phase 2 Continuation Task Log (2026-09-29)

- Added authenticated admin CRUD/reorder routes for settings, page metadata, stats, values, services, properties, sustainability pillars, testimonials, and submissions, with paginated resources and editor/admin gates. Editor users can create/update; only admins can delete.
- Added admin content Form Requests, reorder validation, automatic service slugs/order defaults, transaction-based reorder, and `content.created`, `content.updated`, `content.deleted`, and `content.reordered` audit rows.
- Added Nuxt admin proxies and generic dashboard list/create/edit routes, expanded dashboard navigation, and media upload/library/delete support. Multipart proxying preserves the upload boundary.
- Added shared public components and API-driven `/about`, `/services`, `/properties`, `/sustainability`, `/airbnb-management`, and `/contact` routes. Properties support server-side beds, baths, min/max price filters and 12-item pagination.
- Added public API aliases for `page-meta`, `site-settings`, and `sustainability-pillars`.
- Evidence: admin/public focused tests pass (4 tests / 18 assertions); full Laravel suite passes 25 tests / 100 assertions; Nuxt production build passes after dashboard and public route additions; all touched-file diagnostics are clean.

# Phase 2 Continuation Gaps

- The implementation is functional but not yet a verified verbatim reproduction of all saved HTML: the public pages need visual screenshot comparison and further section-level parity work, especially the homepage sections beyond the current API-driven blocks.
- Per-model Form Request/controller/policy classes were consolidated into shared allowlisted admin handlers; nested bulk editing, drag-and-drop reorder UI, full dashboard field schemas, media pickers, and submission/settings-specific forms remain simplified.
- Cache tags/observer-driven invalidation, complete no-N+1 assertions, exhaustive CRUD endpoint matrix, cURL capture, and browser dashboard/public E2E evidence remain pending.

# Phase 2 Audit Addendum (2026-09-29)

## Asset Checklist

- [x] `logo-BYadtnuV.png` copied from saved page asset folders into `storage/app/public/uploads/` and seeded as `Media`.
- [x] `hero-CXYyLE1-.jpg`, `sustainability-C0LQ9yuw.jpg`, `airbnb-DBi_lVg-.jpg`, `links-lg0006.jpg`, `juniors-23-39-47_1.jpg`, `georgia-dsc0803.jpg`, and `property-6-u5zdNFs8.jpg` copied and seeded as `Media`.
- [x] Duplicate copies across the seven asset folders are de-duplicated by media path during seeding.
- [ ] Saved CSS/font/map support files are not content images and remain source-reference assets; visual parity and local font/map verification are pending.

## Page -> Model -> Dashboard Map

- `/`: `PageMeta`, `Stat`, `Service`/`ServiceItem`, `Property`/`PropertyTag`, `SustainabilityPillar`, `Value`, `Testimonial`, `SiteSetting` -> `/dashboard/page-meta`, `/dashboard/stats`, `/dashboard/services`, `/dashboard/properties`, `/dashboard/pillars`, `/dashboard/values`, `/dashboard/testimonials`, `/dashboard/settings`.
- `/about`: `PageMeta`, `Value`, `SiteSetting` -> corresponding dashboard routes above.
- `/services`: `PageMeta`, `Service`/`ServiceItem`, `SiteSetting` -> `/dashboard/page-meta`, `/dashboard/services`, `/dashboard/settings`.
- `/properties`: `PageMeta`, `Property`/`PropertyTag`/`PropertyImage`, `Media`, `SiteSetting` -> `/dashboard/page-meta`, `/dashboard/properties`, `/dashboard/media`, `/dashboard/settings`.
- `/sustainability`: `PageMeta`, `SustainabilityPillar`, `SiteSetting` -> `/dashboard/page-meta`, `/dashboard/pillars`, `/dashboard/settings`.
- `/airbnb-management`: `PageMeta`, `Stat`, `Service`/`ServiceItem`, `SiteSetting` -> `/dashboard/page-meta`, `/dashboard/stats`, `/dashboard/services`, `/dashboard/settings`.
- `/contact`: `PageMeta`, `SiteSetting`, `ContactSubmission` -> `/dashboard/page-meta`, `/dashboard/settings`, `/dashboard/submissions`.

## E2E Evidence Status

- [x] Editor create/update is covered by `AdminContentTest`; editor delete returns 403.
- [x] Public cache update is covered by `PublicContentTest`.
- [x] Nuxt production build confirms all seven route files and same-origin proxy routes bundle.
- [ ] Browser screenshot side-by-side comparison, dashboard edit-to-public walkthrough, 50-property browser pagination, view-source SEO inspection, and network-console capture remain pending in this environment.

- Final executable evidence (2026-09-29): `php artisan migrate:fresh --seed --force` succeeded; full Laravel suite passed 26 tests / 102 assertions; public route list shows 12 routes; admin route list shows 9 routes; Nuxt production build passed; final touched-file diagnostics are clean.

# Live Asset Audit (2026-09-29)

- Live homepage inspection found three image assets absent from the saved export: `watamu-3bed-exterior.jpg`, `watamu-0876.jpg`, and `leaves-DnSaEzhY.jpg`.
- Downloaded those assets into `GreenMinimal/live-assets/`; `ContentSeeder` copies them into `storage/app/public/uploads/`, creates `Media` rows, and assigns the Watamu images to seeded `Property` records. The leaves image is assigned to `SiteSetting.home_background_image`; the existing hero image is assigned to `/` `PageMeta.hero_image`.
- Live asset references also confirmed `Fraunces`, `Inter`, and `CameraPlainVariable` fonts plus bundled Lucide icons. The current Nuxt build still uses its existing CSS/font setup; local font mirroring and icon-by-icon visual comparison remain a parity task rather than being hardcoded into page content.
- Fresh seed after the live import passed; the three new binaries are present in Laravel public storage; PHPUnit remains green at 26 tests / 102 assertions; Nuxt production build passed.
- Added `font_assets` to `SiteSetting`; mirrored four Fraunces weights, five Inter weights, and the CameraPlainVariable font into Laravel storage. The public Nuxt layout emits their `@font-face` rules from the API response during SSR. Final asset check found 10 font files plus the three live-only images in `storage/app/public/uploads/`.

# Local/Live Mismatch Fix (2026-09-29)

- Root cause: `public/storage` was not linked, so seeded image/font URLs returned 404; created the Laravel storage link and verified live-only images serve from storage.
- Root cause: public response caching serialized Eloquent models/paginators into the database cache; long-running Laravel requests later failed with incomplete-object errors, causing Nuxt public proxies to return 500. Public reads now query fresh resources while observer invalidation remains available for future scalar/JSON cache entries.
- Root cause: local dashboard cookies were always marked `Secure`, so HTTP development sessions were discarded after login. `sessionCookieSecure` is now environment-aware; local login uses a non-Secure cookie, production remains Secure.
- Added Nuxt same-origin asset proxying. DB media/font URLs are rewritten to `/api/public/assets`, and binary assets are streamed without exposing the Laravel origin to the browser.
- Added DB-backed `SiteSetting.home_content` for the live new-listing banner, About, Sustainability, Airbnb, Why Choose Us, and CTA blocks. The settings dashboard exposes this JSON for editing.
- Dashboard edit forms now render existing scalar fields dynamically and surface 401/403/API errors instead of appearing empty.
- Runtime evidence: fresh Nuxt instance at `http://localhost:3002` returned settings/properties with same-origin asset URLs; admin login returned 200; dashboard properties returned 200 with Watamu records; a dashboard PATCH returned 200 and the updated value appeared in the public API.
- Final backend suite remains green at 26 tests / 102 assertions; frontend diagnostics are clean and the Nuxt production build passes.
- Browser verification: local admin login at `http://localhost:3000/dashboard/login` succeeded; navigation to `/dashboard/properties` succeeded and rendered six existing seeded records. Root cause was a missing named `auth` middleware referenced by dashboard pages while only `auth.global.ts` existed; added `app/middleware/auth.ts`.
- Live page audit covered `/about`, `/services`, `/properties`, `/sustainability`, `/airbnb-management`, and `/contact`; identified the live Airbnb “Stay in a home we manage” section and properties page title/copy as remaining visual/data parity work beyond the dashboard routing fix.

# Dashboard UX Rebuild — Backend Only (2026-10-02)

- User-directed scope constraint: do not change the front-end UI. No Nuxt UI, layout, page, component, CSS, or navigation files were edited; work in this task is limited to Laravel backend, backend tests, migrations/seeding, and this log.
- Added persisted page content blocks and per-page `meta_title` / `meta_description`; page editor writes fields and HTML blocks in one transaction. Public page metadata includes published blocks and maps SEO metadata to the existing `title` / `description` response fields for compatibility with the unchanged Nuxt renderer.
- Added database-backed menus and menu items, reorder validation, primary-menu synchronization to existing `SiteSetting.nav_items`, and a migration/seeder path to import existing navigation into menu rows.
- Added Airbnb listing storage and authenticated CRUD at `/api/v1/admin/airbnb-listings`, with public published listing reads.
- Property and service create/update now support nested fields in the same save; property tags/gallery and service items are synchronized transactionally. Gallery unlinking retains shared media files. Page blocks remain nested under their page, not a separate editing endpoint.
- Admin lists now support server-side publication, section, search, and ordering filters; menu and media lists support their relevant filters. Reorder validates resource membership. Slugs are ignored from request payloads and generated server-side.
- Admin reads are limited to admin/editor roles; editors can create/update but remain blocked from delete endpoints. Menu CRUD and settings navigation synchronization are atomic.
- Local verification: both dashboard migrations applied successfully; authenticated admin route listing shows 15 routes; full Laravel PHPUnit suite passes (31 tests, 0 failures); workspace PHP diagnostics report no errors. Frontend build/browser/UI checks were not run because the user explicitly prohibited front-end UI changes.

## Bugs Debugged — Dashboard Backend

- Page metadata read failed with `no such table: content_blocks`; the model relation had no backing table because the pending dashboard migration was empty. Added the page block and SEO columns plus menu/Airbnb schema and applied the migration. Regression risk: run migrations in each environment before serving page metadata.
- Content create requests returned 200 after reloading the newly-created model to include nested relationships; the reload cleared Laravel's `wasRecentlyCreated` signal. Set the create response status explicitly to 201 and added regression tests. Regression risk: preserve the API's 201 create contract when changing response/resource loading.
- The primary navigation initially synchronized as an empty array because the newly-created Menu instance did not contain the database-default `published=true` attribute in memory. Synchronization now reads the persisted published primary menu; existing settings are backfilled into editable menu rows. Regression risk: do not infer database defaults from an unrefreshed model after insert.
- Removing a property image association previously caused `ContentObserver` to delete the underlying media file, making reusable media disappear from the library. Gallery synchronization now retains media and restores prior association rows where needed. Regression risk: unlinking media from one entity must not delete the shared asset.

# Dashboard UX Rebuild — Full Implementation (2026-10-02)

## Immutable rules restated

- The existing public site keeps its current visual layout and styling. Site labels that had been literal in Nuxt were moved to database-backed settings with identical migrated/seeded values; optional page blocks render only when an editor adds them.
- Laravel remains the logic, validation, authorization, and persistence layer; Nuxt remains the view layer. APIs return JSON. All browser calls use same-origin Nuxt server proxies; Laravel origin stays private.
- All editable content is database-backed, including page sections and navigation. Page/property/service/Airbnb slugs are generated in Laravel and are not shown or submitted by dashboard forms.
- Related entity fields are saved in one dashboard screen and one backend transaction (page blocks, service items, property tags/gallery, menu items, Airbnb stats/features).
- Admin/editor role separation remains enforced: editors may read/create/update; destructive actions and user/role administration are administrator-only.
- List screens are paginated and URL-query-driven; server endpoints apply supported search, publication, section, role, sort, and direction filters. Reordering is persisted server-side.

## Implementation and decisions

- Rebuilt `frontend/app/layouts/dashboard.vue` as a persistent vertical icon rail and route-backed section tabs. `frontend/app/config/dashboard-nav.ts` is the rail/tab source. The global Settings rail and Content Management → Site Settings tab use distinct `rail` query values. Mobile keeps a 72px icon rail and horizontally scrollable tabs. The header displays the signed-in user and logout action.
- Replaced placeholder generic edit screens with schema-driven single-location editors for Page, Property, Service, Testimonial, Statistics, Values, Sustainability Pillar, and Airbnb listing. Slug inputs were removed. Existing page routes are read-only; Laravel generates unique page routes from the title on create. A Nuxt catch-all public renderer serves newly created page routes.
- Added Tiptap rich-text editing for page blocks; HTML is persisted with the page and exposed to existing public pages and newly generated public routes. Laravel sanitizes block HTML before persistence (safe formatting tags only; script/style/embedded objects and unsafe link schemes are removed).
- Property forms include core fields, tags, publish/order/featured toggles, ordered gallery selection, and inline image upload; updates commit parent and relationships together. Service items and Airbnb features/occupancy data use the same one-save editor.
- Added dedicated menu CRUD with navigation slots, root and nested items, order controls, publication state, and public primary-menu synchronization. Existing `SiteSetting.nav_items` were imported into menu rows. SEO has a bulk inline editor for each page.
- Completed Site Settings for brand/logo, contact, footer, socials, WhatsApp, page/home CTA labels and destinations, contact-form labels, map URL, and dynamic homepage content. Added Users and Account screens with admin-only role/access management, own-profile edits, and password change. Billing is a scaffold placeholder.
- Added explicit Nuxt media-list proxy because the nested media upload route shadowed the prior catch-all in development; admin media URLs are rewritten to Nuxt’s same-origin asset proxy. Browser network inspection observed no requests to Laravel origin.
- Tiptap Vue 3 Starter Kit selected for the WYSIWYG editor. Installation reported 7 high-severity npm audit findings across the dependency tree; dependency remediation was not included in this task.

## Bugs Debugged — Dashboard UX

- Edit screens showed 401/“Unauthenticated” on direct route loads while list pages worked. Root cause: direct `$fetch` during SSR did not forward the incoming HTTP-only session cookie. Edit data/media loading now starts after mount through the same-origin proxy; existing `useFetch` routes retain Nuxt request-cookie forwarding. Regression risk: authenticated SSR calls must use `useFetch`/request fetch or run after hydration.
- The media library and editor media selectors received Nuxt 404 responses at `/api/admin/media` due route resolution with the nested upload route. Added an explicit static media GET proxy with URL rewriting and verified a 200 response with same-origin asset URLs. Regression risk: preserve an explicit route when adding sibling nested media proxy paths.
- Page block output could execute authored HTML if rendered raw. Added backend HTML sanitization and regression coverage for event attributes, script tags, and `javascript:` links. Regression risk: keep public `v-html` fed only sanitized persisted block content.
- Newly created menus with new child items initially lacked a stable parent database ID. Added temporary client keys resolved by Laravel inside the menu transaction and validated parent ownership/depth; recursive resource serialization returns children to the editor. Regression risk: parent creation must precede child resolution and all referenced parents must remain in the submitted menu.
- New pages previously required the client to submit a route, effectively exposing slug editing. Routes are now generated uniquely from the title in Laravel; the create form omits the route from the payload, and the public catch-all fetches page metadata by generated route. Regression risk: keep route generation centralized and add tests if its format changes.

## Verification evidence

- Local Laravel migrations are applied, including page blocks/meta, menus, Airbnb listings, imported primary navigation, public labels, and contact-page settings.
- Full backend suite: `php artisan test` — 34 passed, 0 failed, 155 assertions. Covers CRUD/persistence, generated route/slug behavior, filters, nested menu items, editor destructive-action denial, admin-only user operations, password/profile updates, and HTML sanitization.
- Nuxt production build: `npm run build` completed successfully. PHP/TypeScript workspace diagnostics report no errors.
- Browser checks at local Nuxt/Laravel: logged in using a disposable editor QA account; property list rendered six rows; page editor loaded DB fields and same-origin media choices; primary menu editor loaded seven existing links; Site Settings rendered DB values; public contact page rendered its saved text/map; public home produced no console errors.
- Responsive check at a narrow viewport: rail labels were hidden (72px icon rail); Content Management tabs exceeded viewport width and used horizontal overflow.
- Same-origin network check while loading the dashboard recorded no external request origins. Final grep checks returned zero matches for the brand name, existing public CTA/contact/hero copy, slug-input references, or Laravel origin/runtime API URL references in client-side `frontend/app/**`; brand/CTA/form labels now come from Laravel settings.
- Public appearance preservation: database values were backfilled to the exact current visible CTA/contact labels and map URL. The public layout/styles are unchanged; the optional block component emits nothing when a page has no saved blocks.

## Remaining verification / scope notes

- Browser create/edit/refresh/public-page mutation walkthrough and a full filter-matrix UI walkthrough remain unverified end-to-end in a real browser; equivalent persistence, filter, page, menu, and role cases are covered by Laravel tests.
- Billing intentionally remains a placeholder pending product requirements. Email verification and email-based password recovery remain paused per the existing mailer decision earlier in this document.
- Public block CSS is additive only when blocks exist; final visual parity for editor-created block content should be reviewed with actual authored content before production release.
- The disposable `dashboard-qa@example.test` editor account and its session token were removed after the browser walkthrough. Final Nuxt production build passed after the brand/copy data-binding update; the backend suite remains 34/34 tests green (155 assertions).

# Properties Filter Rebuild — Single-Input Typeahead (2026-10-02)

## Rules and architecture

- Laravel remains responsible for every search/filter semantic, query, validation, suggestions source, and cache decision. Nuxt only collects chips/input, syncs URL state, renders API suggestions/results, and proxies requests; it performs no local property searching.
- Browser requests go to same-origin `/api/public/properties` and `/api/public/properties/suggestions` Nuxt routes only. Public property card markup and all unrelated pages were left unchanged.
- Suggestions are queried from published Laravel Property/PropertyTag records; no suggestion vocabulary or live property copy was added to Nuxt.

## Task log

- Replaced the four-field filter form on `/properties` with one full-width typeahead input and removable inline chips. Supports query and amenity chips, independently removable filters, clear-all, arrow-key navigation, Enter, Escape, and Backspace removal of the last chip.
- Added `usePropertyFilter` for URL-restored `q`, `tags[]`, and `page`, debounced 250ms filtering, paginated API results, push/replace history, and back/forward synchronization.
- Added explicit same-origin property search and suggestions proxies. The property proxy preserves repeated `tags[]` query parameters and rewrites media URLs through the existing asset proxy.
- Laravel property filtering now combines word-wise free text across title/location/description/price label/tags, exact numeric bed/bath/price matches, explicit bed/bath/price ranges, featured state, and selected tags with `whereHas` + `whereIn`. Responses remain paginated (default 12, maximum 100), ordered deterministically, and include loaded tags for existing cards.
- Added a validated suggestions endpoint returning up to 10 unique matching tag labels, locations, and titles for published properties. Suggestions are cached by normalized query for five minutes using `ContentCache`; existing content observers invalidate cached suggestion keys on Property/PropertyTag writes.
- Regression tests cover composed search/tag/numeric filters, tag-bearing resources, DB-backed published suggestions and per-kind caps, suggestion cache invalidation, large result pagination, and `per_page` cap.

## Bugs Debugged — Properties Filter

- Typeahead blur caused `TypeError: _ctx.setTimeout is not a function`; the inline template expression was compiled as a component-context lookup. Moved blur handling into a script function and clear its timer on unmount. Regression risk: do not invoke browser globals as unresolved inline template identifiers.
- Vue warned that `onBeforeUnmount` had no active component instance because the composable registered cleanup after awaiting `useAsyncData`. Moved hook registration before the await. Regression risk: register Vue lifecycle hooks synchronously during setup, before any await.
- Suggestions for the prior keystroke remained selectable while a new lookup was pending. Clear the old suggestions at the start of each debounced query and guard response ordering with a request sequence. Regression risk: stale asynchronous results must never be presented for newer input.
- Back/Forward URL restoration was initially susceptible to the normal chip/input watcher resetting the restored page to page 1. Added a restoration guard and discard any pending debounce before applying browser-history state. Regression risk: route hydration and user-driven filter watchers must be kept distinct.

## Verification evidence

- Full Laravel test suite: 38 passed, 0 failed. PHP/TypeScript workspace diagnostics: no errors.
- Nuxt production build: `npm run build` completed successfully.
- Browser: typed `pool`; suggestions `Rooftop pool` and `Swimming pool` appeared from seeded DB records. ArrowDown + Enter selected a suggestion and displayed a removable chip; the resulting single-tag filter returned the matching Georgia Luxury Apartment. Typed `Watamu` alongside a tag and got two matching villas; removing the tag left the `Watamu` query intact. A nonmatching query showed the empty state and Clear filters action. Back navigation restored the previous query chip. Reload with `tags[]=Swimming pool` restored the tag chip and four matching records.
- Browser network/console check on filtered properties: no requests to Laravel origin, no console errors, no typeahead lifecycle/unhandled warnings. Public property card layout remained the existing card markup.
- Suggestions are confirmed database-driven through API feature tests and the browser values matching seeded tags/locations/titles. 55-property pagination and 100-item cap are verified through backend tests.

# cPanel Git Deployment Troubleshooting (2026-10-02)

- Confirmed the checked-in `.cpanel.yml` began with UTF-8 BOM bytes `EF-BB-BF`, before `deployment:`. Recreated it as UTF-8 without BOM and added the YAML document-start marker.
- Validation: first bytes are now `2D-2D-2D` (`---`), BOM check is false, and Symfony YAML parsed the deployment task list successfully. The deployment workflow was subsequently revised in “GitHub → cPanel Initial Deployment” below.
- The deployment directory remains `/home/lrnzwljz/gmr.nyimuki.com/`. cPanel still needs Laravel document-root and Nuxt Node app/subdomain configuration; Git deployment tasks cannot configure those hosting-level settings.
- The current workflow excludes source `.env` files and generates production runtime env files from the cPanel-only configuration below. Laravel `.env` is generated with a retained/generated `APP_KEY`; Nuxt `.env` provides the API origin and secure cookie settings. Both are excluded from Git and regenerated during deployment.

# GitHub → cPanel Initial Deployment (2026-10-02)

- Deployment contract: commit all source and Markdown documentation to GitHub `main`; `.cpanel.yml` syncs only runtime code and explicitly excludes `*.md`, `.claude/`, `.mcp.json`, `.env*`, Git metadata, dependencies, and local build caches from the live directories.
- PHPUnit tests, `phpunit.xml`, and the tracked developer SQLite file are also excluded from the live backend sync; migrations and seeders remain included.
- A cPanel-only `/home/lrnzwljz/.gmr-production.env` file is the single production configuration source; `backend/deploy/generate-production-env.php` validates it and atomically generates Laravel `backend/.env` and Nuxt `frontend/.env` with restrictive permissions. Existing Laravel `APP_KEY` is retained; an initial random key is generated when absent. The Nuxt env contains `NUXT_LARAVEL_API_URL`, secure-cookie mode, and session-cookie name; `npm start` loads it with Node `--env-file`. No secrets are committed.
- Deployment sync preserves Laravel storage/uploads and excludes source `.env`, Markdown, tests, docs, local SQLite, caches, and dependencies from the live copy. It installs Composer dependencies, clears stale config, runs `php artisan migrate --force` on every pull, links public storage if missing, seeds DB content once, caches Laravel config, installs frontend dependencies, and builds Nuxt. Node starts with `npm start`, which loads the generated Nuxt `.env`.
- Initial content seeding is guarded by `$DEPLOYPATH/.initial-content-seeded`: `db:seed --force` runs only once after a successful seed. This avoids resetting CMS edits on later deployments. Initial admin creation remains guarded by `SEED_ADMIN`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, and `ADMIN_NAME` in the production environment.
- The local `GreenMinimal` source export is absent and intentionally is not required for deployment. `ContentSeeder` now falls back to the tracked `backend/storage/app/public/uploads` assets. `ContentSeederDeploymentTest` verifies first-seed records/media with that fallback.
- Nuxt package now includes `npm start` -> `node .output/server/index.mjs` for the cPanel Node application manager.
- Required cPanel setup outside Git deployment tasks: create the private settings file documented in `backend/deploy/CPANEL-ENV.md`; use PHP CLI 8.2+ and Node 20.6+; configure Laravel document root to `backend/public`; configure Node application root `frontend`, startup `npm start`, and supported Node version; map frontend/API hostnames/reverse proxy appropriately. Never expose the project parent directory as a browsable document root.
- Earlier commits `c9ae4b7`, `4d3c3c4`, and `fce26cc` predate this generated-env follow-up. The env generator, cPanel task changes, and regression tests are pending commit and push; GitHub push still does not confirm cPanel deployment.
