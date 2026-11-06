// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: ['@pinia/nuxt'],
  typescript: {
    tsConfig: { compilerOptions: { types: ['node'] } },
  },
  routeRules: {
    '/dashboard/**': { headers: { 'cache-control': 'no-store', 'referrer-policy': 'no-referrer' } },
  },
  css: ['~/assets/css/main.css'],
  vite: {
    plugins: [
      (await import('@tailwindcss/vite')).default(),
    ],
  },
  runtimeConfig: {
    laravelApiUrl: process.env.LARAVEL_API_URL || 'http://127.0.0.1:8000',
    trustedProxyIps: '',
    frontendUrl: '',
    sessionCookieSecure: process.env.NUXT_SESSION_COOKIE_SECURE === 'true' || process.env.NODE_ENV === 'production',
    public: {
      sessionCookieName: process.env.NUXT_SESSION_COOKIE_NAME || 'gmr_session',
    },
  },
})
