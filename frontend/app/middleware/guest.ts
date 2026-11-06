export default defineNuxtRouteMiddleware(async (to) => {
  const guestPages = ['/dashboard/login', '/dashboard/register']
  if (!guestPages.includes(to.path)) return

  const session = await useRequestFetch()<{ present: boolean }>('/api/auth/session')
  if (session.present) return navigateTo('/dashboard')
})
