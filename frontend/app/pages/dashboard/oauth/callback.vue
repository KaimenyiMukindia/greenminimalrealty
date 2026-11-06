<script setup lang="ts">
import { authErrorMessage } from '~/services/auth'
definePageMeta({ layout: false })
useHead({ meta: [{ name: 'referrer', content: 'no-referrer' }] })
const route = useRoute()
const auth = useAuthStore()
const message = ref('')
const pending = ref(true)
onMounted(async () => {
  const code = route.query.code
  const error = route.query.error
  await navigateTo('/dashboard/oauth/callback', { replace: true })
  try {
    if (error || typeof code !== 'string') {
      message.value = error === 'google_unavailable'
        ? 'Google sign-in is not configured yet. Please use email and password.'
        : 'Google sign-in was cancelled or could not be completed. Please try again.'
      return
    }
    await auth.exchangeGoogleCode(code)
    await navigateTo('/dashboard', { replace: true })
  } catch (error: unknown) {
    message.value = authErrorMessage(error, 'This sign-in link is invalid or expired. Please start again.')
  } finally { pending.value = false }
})
</script>
<template><main class="grid min-h-screen place-items-center bg-[#f6f6f1] px-4 py-12"><AuthCard><template #title>Google sign-in</template><template #subtitle>{{ pending ? 'Completing your sign-in...' : 'Continue with your account.' }}</template><Alert v-if="message" variant="error" :message="message" /><div v-if="!pending" class="mt-5 space-y-4"><a href="/api/auth/google" class="block rounded-full border border-stone-300 px-5 py-3 text-center text-sm font-semibold text-emerald-900">Try Google again</a><NuxtLink to="/dashboard/login" class="block text-center text-sm font-semibold text-emerald-800">Back to sign in</NuxtLink><NuxtLink to="/dashboard/register" class="block text-center text-sm font-semibold text-emerald-800">Create an account</NuxtLink></div></AuthCard></main></template>
