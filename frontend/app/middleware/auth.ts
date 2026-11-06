export default defineNuxtRouteMiddleware(async () => {
  const auth = useAuthStore()
  if (!auth.present) {
    const session = await useRequestFetch()<{ present: boolean }>('/api/auth/session')
    if (!session.present) return navigateTo('/dashboard/login')
  }
})