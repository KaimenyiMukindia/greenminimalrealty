const guestPages = new Set([
  '/dashboard/login',
  '/dashboard/register',
  '/dashboard/forgot-password',
  '/dashboard/reset-password',
])

export default defineNuxtRouteMiddleware((to) => {
  if (!to.path.startsWith('/dashboard')) return

  const config = useRuntimeConfig()
  const isGuestPage = guestPages.has(to.path)
  const token = useCookie<string | null>(config.public.sessionCookieName as string)
  const present = import.meta.server
    ? Boolean(token.value)
    : undefined

  return resolveSession(present, isGuestPage, to.path)
})

async function resolveSession(serverPresence: boolean | undefined, isGuestPage: boolean, path: string) {
  const session = serverPresence === undefined
    ? await $fetch<{ present: boolean }>('/api/auth/session').catch(() => ({ present: false }))
    : { present: serverPresence }

  if (!isGuestPage && !session.present) {
    return navigateTo('/dashboard/login')
  }

  if (isGuestPage && session.present) {
    return navigateTo('/dashboard')
  }
}
