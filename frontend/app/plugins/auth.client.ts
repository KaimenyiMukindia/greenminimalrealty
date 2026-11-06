export default defineNuxtPlugin((nuxtApp) => {
  globalThis.$fetch = $fetch.create({
    async onResponseError({ request, response }) {
      if (response.status === 401 && !String(request).includes('/api/auth/login')) {
        useAuthStore(nuxtApp.$pinia).clear()
        await nuxtApp.runWithContext(() => navigateTo('/dashboard/login'))
      } else if (response.status === 403 && response._data?.message === 'Your email address is not verified.') {
        await nuxtApp.runWithContext(() => navigateTo('/dashboard/verify-email'))
      }
    },
  })
})
