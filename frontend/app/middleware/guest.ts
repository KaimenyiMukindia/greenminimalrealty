export default defineNuxtRouteMiddleware((to) => {
  const guestPages = ['/dashboard/login', '/dashboard/register', '/dashboard/forgot-password', '/dashboard/reset-password']
  if (!guestPages.includes(to.path)) return

  const config = useRuntimeConfig()
  const token = useCookie<string | null>(config.public.sessionCookieName as string)
  if (token.value) return navigateTo('/dashboard')
})
