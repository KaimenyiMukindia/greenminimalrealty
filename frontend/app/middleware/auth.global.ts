const publicAuthPages = new Set([
  '/dashboard/login',
  '/dashboard/register',
  '/dashboard/forgot-password',
  '/dashboard/reset-password',
  '/dashboard/verify-email',
  '/dashboard/oauth/callback',
])

export default defineNuxtRouteMiddleware(async (to) => {
  if (!to.path.startsWith('/dashboard')) return
  const auth = useAuthStore()
  const fetcher = useRequestFetch()
  const session = await fetcher<{ present: boolean }>('/api/auth/session')
  if (!publicAuthPages.has(to.path) && !session.present) {
    auth.clear()
    return navigateTo({ path: '/dashboard/login', query: { redirect: to.fullPath } })
  }
  if (!publicAuthPages.has(to.path) && session.present && import.meta.client) {
    try {
      await fetcher('/api/auth/dashboard')
    } catch (error: unknown) {
      if (error && typeof error === 'object' && 'statusCode' in error) {
        if (error.statusCode === 401) return navigateTo('/dashboard/login')
        if (error.statusCode === 403) return navigateTo('/dashboard/verify-email')
      }
      throw error
    }
  }
})
