# Nuxt Minimal Starter

Look at the [Nuxt documentation](https://nuxt.com/docs/getting-started/introduction) to learn more.

## Setup

Make sure to install dependencies:

```bash
# npm
npm install

# pnpm
pnpm install

# yarn
yarn install

# bun
bun install
```

## Development Server

Start the development server on `http://localhost:3000`:

```bash
# npm
npm run dev

# pnpm
pnpm dev

# yarn
yarn dev

# bun
bun run dev
```

## Production

Build the application for production:

```bash
# npm
npm run build

# pnpm
pnpm build

# yarn
yarn build

# bun
bun run build
```

Locally preview production build:

```bash
# npm
npm run preview

# pnpm
pnpm preview

# yarn
yarn preview

# bun
bun run preview
```

Check out the [deployment documentation](https://nuxt.com/docs/getting-started/deployment) for more information.

## Authentication

Laravel owns validation, credentials, verification, reset tokens, Google OAuth, token expiry/revocation, roles, and deletion. The [auth service](app/services/auth.ts) and [Pinia store](app/stores/auth.ts) only call the same-origin Nuxt proxies and display the resulting session/user state. Sanctum tokens live only in an HTTP-only, Secure, SameSite=Lax cookie; they are not stored in Pinia, localStorage, or client-visible API responses.

Existing login, registration, recovery, reset, and account screens are retained. Additional pages handle email verification and Google's one-use exchange code. Laravel rejects unverified dashboard requests; Nuxt presents the verification screen. API 401 responses clear the cookie/session and return to sign-in. Sign-out errors are displayed, not silently treated as successful token revocations.

Both **Create account with Google** on registration and **Continue with Google** on login start the same Laravel-owned flow. A new verified Google identity creates a verified ordinary account without requiring an email/password form or a separate verification email. Returning identities reuse their account; Google does not grant CMS roles. Existing verified email accounts retain their password/role when linked, while unverified/conflicting matches must resolve their existing account first. Successful exchange redirects to Laravel's dashboard destination; callback failures offer retry, email sign-in, and registration links. Live Google testing requires configured credentials and matching public frontend/callback URLs; simulated-provider tests are not proof of live Google consent.

Set `NUXT_LARAVEL_API_URL` (or the existing `LARAVEL_API_URL` fallback), `NUXT_FRONTEND_URL` (public browser origin when behind a proxy), `NUXT_SESSION_COOKIE_NAME`, and `NUXT_SESSION_COOKIE_SECURE`. `NUXT_TRUSTED_PROXY_IPS` is an explicit comma-separated allowlist for trusted upstream proxy IPs; leave it empty unless your proxy topology is known. Google/Resend keys belong only in Laravel's ignored environment file, never Nuxt runtimeConfig.public.

For ngrok testing against deployed Laravel, tunnel port 3000/the Nuxt listener, configure its public HTTPS origin as Laravel's `FRONTEND_URL`, and register the deployed **Laravel** `/auth/google/callback` URL with Google. The current Laravel-only tunnel does not serve Nuxt pages. Full production email/queue/environment setup is in the [cPanel guide](../backend/deploy/CPANEL-ENV.md).

Validation: `npm run build`; `npm exec nuxt typecheck`. Type-check dependencies are installed, but pre-existing content/media/property-editor type errors are tracked in [MASTER.md](../MASTER.md); auth files have no reported type errors.
