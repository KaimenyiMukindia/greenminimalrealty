<script setup lang="ts">
import { createAuthService, authErrorMessage } from '~/services/auth'
definePageMeta({ layout: false })
useHead({ meta: [{ name: 'referrer', content: 'no-referrer' }] })
const route = useRoute()
const auth = useAuthStore()
const service = createAuthService(useRequestFetch())
const message = ref('')
const pending = ref(false)
const success = ref(false)
onMounted(async () => {
  const { id, hash, expires, signature } = route.query
  if ([id, hash, expires, signature].every(value => typeof value === 'string')) {
    pending.value = true
    try {
      message.value = (await service.verifyEmail({ id: String(id), hash: String(hash), expires: String(expires), signature: String(signature) })).data.message
      success.value = true
      await navigateTo('/dashboard/verify-email', { replace: true })
    } catch (error: unknown) { message.value = authErrorMessage(error, 'This verification link is invalid or expired.') }
    finally { pending.value = false }
  }
  try { await auth.initialize() }
  catch (error: unknown) { message.value = authErrorMessage(error, 'Unable to load your session.') }
})
async function resend() {
  pending.value = true
  try { message.value = (await service.resendVerification()).data.message; success.value = true }
  catch (error: unknown) { message.value = authErrorMessage(error, 'Unable to resend verification. Please try again.') }
  finally { pending.value = false }
}
async function logout() {
  try { await auth.logout(); await navigateTo('/dashboard/login') }
  catch (error: unknown) { message.value = authErrorMessage(error, 'Unable to revoke your session. Please try again.'); success.value = false }
}
</script>
<template><main class="grid min-h-screen place-items-center bg-[#f6f6f1] px-4 py-12"><AuthCard><template #title>Verify your email</template><template #subtitle>Check your inbox and follow the verification link before opening your dashboard.</template><div class="space-y-5"><Alert v-if="message" :variant="success ? 'success' : 'error'" :message="message" /><FormButton v-if="auth.present" :loading="pending" @click="resend">Resend verification email</FormButton><NuxtLink :to="auth.present ? '/dashboard' : '/dashboard/login'" class="block text-center text-sm font-semibold text-emerald-800">{{ auth.present ? 'Continue to dashboard' : 'Continue to sign in' }}</NuxtLink><button v-if="auth.present" class="block w-full text-center text-sm text-stone-500" @click="logout">Sign out</button></div></AuthCard></main></template>
