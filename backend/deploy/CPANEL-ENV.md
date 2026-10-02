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

## Deployment logs

The cPanel task invokes `./backend/deploy/cpanel-deploy.sh` relative to the Git checkout root. It logs each named deployment stage, command start/completion, and failing line/status to:

`/home/lrnzwljz/logs/gmr-deploy.log`

The log is outside the web root and is created with owner-only permissions. No SSH is needed: open this file in cPanel File Manager to inspect failures. If cPanel reports only "Deployment task completed" or a task failure, check the log for the failed command/stage. If the log file was not created, cPanel failed while reading/parsing `.cpanel.yml` or before it could start the wrapper. The script does not log the private environment-file contents.

## Deploy without SSH

1. In cPanel **Git Version Control**, select this repository and branch `main`, click **Update from Remote**, then **Deploy HEAD Commit**.
2. The task syncs runtime code (not Markdown/docs/tests/secrets), generates Laravel and Nuxt `.env` files from `/home/lrnzwljz/.gmr-production.env`, installs dependencies, runs database migrations, seeds initial content once, and builds Nuxt.
3. In cPanel **Setup Node.js App**, set the application root to `gmr.nyimuki.com/frontend`, startup file/command to `npm start`, and ensure the app's Node version is at least 20.6. Start/restart the Node app using the cPanel UI after deployment if cPanel does not auto-restart it.
4. Set Laravel's domain document root to `gmr.nyimuki.com/backend/public` using cPanel's domain/document-root settings. Do not make the project parent directory web-accessible.
5. Read `/home/lrnzwljz/logs/gmr-deploy.log` in File Manager for exact task output. A cPanel task marked completed is not proof that the live Node app restarted or that DNS/document-root routing is correct.
