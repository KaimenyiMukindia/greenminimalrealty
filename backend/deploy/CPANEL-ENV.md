# cPanel production environment setup

The deployment task generates both `backend/.env` and `frontend/.env` on the server. Do not commit either generated file or put credentials in GitHub.

Before the first deployment, create this **server-only** shell environment file at:

`/home/lrnzwljz/.gmr-production.env`

This can be done in cPanel **File Manager** (no SSH required): open the account home directory `/home/lrnzwljz` (one directory above `public_html`), enable **Show Hidden Files**, create `.gmr-production.env`, paste the shell assignments below, save, then use **Change Permissions** to set `0600`. This file must not be placed under `public_html`, the Git repository, or the deploy directory.

Set restrictive permissions (`chmod 600`) and populate these values using the cPanel MySQL database/user and your production domains:

- `APP_URL` — HTTPS Laravel API origin, e.g. `https://api.example.com`
- `FRONTEND_URL` — HTTPS Nuxt site origin, e.g. `https://www.example.com`
- `SANCTUM_STATEFUL_DOMAINS` — Nuxt hostname(s), no scheme or path
- `SESSION_DOMAIN` — cookie domain appropriate for the deployment, such as `.example.com`
- `DB_CONNECTION` — `mysql` or `mariadb`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Optional `APP_NAME`, `SANCTUM_TOKEN_EXPIRATION` (minutes), `NUXT_SESSION_COOKIE_NAME` (defaults to `gmr_session`)
- Optional initial administrator: `SEED_ADMIN=true`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_NAME`

Use shell assignments such as `DB_PASSWORD='your-server-password'`; keep secrets only in the cPanel home-directory file. With `SEED_ADMIN` unset or false, no administrator is created by the seeder. Do not send credentials to chat or commit them.

The deploy script sources this file, writes both environment files atomically with mode `0600`, sets Laravel production mode, disables debug, forces secure cookies, and generates a random `APP_KEY` on first deployment. On later deployments it retains the existing `APP_KEY` while refreshing the settings from the private source file. The Nuxt environment contains the API origin, secure-cookie flag and cookie name; its `npm start` command loads that file with Node's `--env-file` option. Back up the generated files and the private source file securely.

Set the Nuxt Node application root to `frontend` and startup command to `npm start`. The deployment generates its `.env`; cPanel may also define/override the same runtime variables. Map Laravel's document root to `backend/public`; do not expose the project parent directory as a document root.

On the first successful deployment, migrations run and the database is seeded once. An administrator is seeded only if the optional admin values are supplied. Later deployments run outstanding migrations but do not reseed content or overwrite dashboard edits.

## Authentication, Google, and Resend

Leave `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, and `RESEND_API_KEY` empty until setup is complete. `MAIL_MAILER=log` is an explicit setup mode: links are logged, not delivered. Do not launch public registration/recovery with log mail; restrict access during setup. Existing unverified administrators must verify their email too; deployment does not silently mark accounts verified.

For [Resend](https://resend.com/), verify your sending domain, then set `MAIL_MAILER=resend`, `RESEND_API_KEY`, `MAIL_FROM_ADDRESS` (on that domain), and optionally `MAIL_FROM_NAME` in the private cPanel environment source. Redeploy to regenerate config. The generator rejects Resend without a key/real sender; it does not fall back to log on delivery failures.

Auth mail uses Laravel's database queue. Configure a supervised queue worker, or a cPanel cron using your PHP CLI and the backend absolute path: `php artisan queue:work --stop-when-empty --tries=3 --timeout=60` every minute. Prevent overlapping cron invocations using the hosting-supported locking mechanism. Monitor `jobs`, `failed_jobs`, and Laravel logs; restart long-lived workers after deployments. Signed verification/reset links expire after 60 minutes, so queue backlog must stay well below that.

In Google Cloud, configure a Web OAuth client, the consent screen, and the authorized redirect URI **exactly** `APP_URL/auth/google/callback`. Set all three Google values together, including that full HTTPS redirect URI. Nuxt starts the flow through `/api/auth/google`; Laravel uses its own state-checked session and validates Google's verified email. Laravel redirects to `FRONTEND_URL/dashboard/oauth/callback` with a hashed-at-rest, 60-second, browser-bound exchange code. Nuxt exchanges it once and sets the HTTP-only session cookie; Google access/refresh tokens and Sanctum tokens never appear in the browser response or URL.

Keep Laravel's session cookie host-only if possible (`SESSION_DOMAIN` may be the API hostname); it must not collide with Nuxt's session cookie. OAuth sessions use Secure/SameSite=Lax cookies. Preserve HTTPS/original host when configuring reverse proxies so signed verification URLs work. `NUXT_FRONTEND_URL` is generated from `FRONTEND_URL` for same-origin mutation checks behind TLS proxies. Set `TRUSTED_PROXIES` to the exact proxy addresses Laravel receives requests from, and `NUXT_TRUSTED_PROXY_IPS` to the exact upstream proxy addresses Node receives requests from. Both default to empty; no internet-supplied forwarded headers are trusted by default. Nuxt forwards the resolved client IP and user agent to Laravel; without correctly configured trusted proxies, clients behind Nuxt share its IP-based auth limits. Only trust proxies that append/overwrite forwarded headers correctly. The bearer-token API remains separate from Google callback session handling.

For browser testing against deployed Laravel, expose **Nuxt**, not just Laravel, using ngrok. Set backend `APP_URL` to the deployed Laravel origin, backend `FRONTEND_URL` to the public Nuxt/ngrok origin, and Nuxt `NUXT_LARAVEL_API_URL` to that same Laravel origin. Set `NUXT_FRONTEND_URL` to the public Nuxt/ngrok origin and `NUXT_SESSION_COOKIE_SECURE=true`. Google uses the deployed Laravel `/auth/google/callback`; its response returns to the public Nuxt callback page. Update Laravel config cache whenever these values change. A tunnel pointed at Laravel cannot serve Nuxt's `/dashboard/login`.

Ordinary registration creates the existing `user` role, not a CMS administrator. Google can link only an active, verified account with the same email; unverified or deleted matches are refused. Google-only users can use password recovery to set their first password before changing email. Deletion soft-deletes accounts consistent with the existing schema, anonymizes their profile, and revokes tokens, reset links, OAuth codes, and database sessions.

## Deployment logs

GitHub Actions builds Nuxt on Linux/Node 22 after pushes to `main` or `auth`, with the `node-server` preset. It publishes `nuxt.tar.gz` and its SHA-256 checksum in the prerelease tagged `nuxt-<full-commit-SHA>`. The archive contains a revision marker and the complete `.output` runtime, not environment files. Enable Actions and its workflow write permission in the repository. No server SSH key or production secrets are needed by the workflow.

Wait for **Build Nuxt deployment** to succeed before deploying that commit. The cPanel manifest invokes `./backend/deploy/cpanel-deploy.sh` relative to the Git checkout root. It downloads the public release for exactly `HEAD`, verifies the checksum, revision marker, and server entry point **before changing live files**. A missing, corrupt, or mismatched release fails explicitly; old manually uploaded output is never accepted. This relies on the repository/releases remaining public; private releases would require a separately designed authenticated download.

The script uses `tar` (not unavailable `rsync`) to copy runtime source while excluding secrets, dependencies, caches, tests, and local SQLite. It generates runtime env files, installs Composer dependencies, clears cached config, migrates before clearing database-backed caches, seeds only when the initial seed marker is absent, and caches config. Storage links use shell `ln -s` because this host disables PHP's `symlink()` function. The completed Nuxt build replaces live `.output`; the previous output is retained as `.output-backup-<timestamp>-<pid>` for manual rollback. Long-lived queue workers receive a restart signal.

Restart the Nuxt Node process after a successful deployment so it loads the new output and environment. The existing host startup script/cron is outside Git; do not overwrite it. Output backups consume disk space: remove only specifically identified old backup directories once a release is verified.

This host currently serves Laravel and Nuxt on the same subdomain through Apache. The tracked `site.htaccess` preserves its storage/API/Nuxt proxy rules, adds Laravel-owned `/auth/google` and `/auth/google/callback`, and rejects direct access to source directories. Deployment backs up the previous root `.htaccess` before installing these rules. If moving to separate frontend/API domains, review this host-specific template rather than copying its same-domain proxy configuration.

Deployment output is redirected directly into the private log, avoiding process-substitution requirements in the cPanel runner. Each deployment stage, command start/completion, and failing line/status is recorded in:

`/home/lrnzwljz/logs/gmr-deploy.log`

The log is outside the web root and is created with owner-only permissions. No SSH is needed: open this file in cPanel File Manager to inspect failures. If cPanel reports only "Deployment task completed" or a task failure, check the log for the failed command/stage. If the log file was not created, cPanel failed while reading/parsing `.cpanel.yml` or before it could start the wrapper. The script does not log the private environment-file contents.

## Deploy without SSH

1. In cPanel **Git Version Control**, select this repository and branch `main`, click **Update from Remote**, then **Deploy HEAD Commit**.
2. The task verifies the commit-matched Nuxt release, syncs runtime code (not Markdown/docs/tests/secrets), generates Laravel and Nuxt `.env` files from `/home/lrnzwljz/.gmr-production.env`, installs backend dependencies, runs database migrations, seeds initial content once, and publishes Nuxt output.
3. In cPanel **Setup Node.js App**, set the application root to `gmr.nyimuki.com/frontend`, startup file/command to `npm start`, and ensure the app's Node version is at least 20.6. Start/restart the Node app using the cPanel UI after deployment if cPanel does not auto-restart it.
4. Set Laravel's domain document root to `gmr.nyimuki.com/backend/public` using cPanel's domain/document-root settings. Do not make the project parent directory web-accessible.
5. Read `/home/lrnzwljz/logs/gmr-deploy.log` in File Manager for exact task output. A cPanel task marked completed is not proof that the live Node app restarted or that DNS/document-root routing is correct.
