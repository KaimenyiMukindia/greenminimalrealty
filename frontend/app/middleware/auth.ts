export default defineNuxtRouteMiddleware(async () => {
  const config = useRuntimeConfig()
  const token = useCookie<string | null>(config.public.sessionCookieName as string)
  const present = import.meta.server ? Boolean(token.value) : (await $fetch<{ present: boolean }>('/api/auth/session').catch(() => ({ present: false }))).present

  if (!present) return navigateTo('/dashboard/login')
})