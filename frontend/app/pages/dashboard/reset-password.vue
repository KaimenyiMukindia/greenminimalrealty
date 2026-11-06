<script setup lang="ts">
definePageMeta({ layout: false })
useHead({ meta: [{ name: 'referrer', content: 'no-referrer' }] })
import { createAuthService, authErrorMessage } from '~/services/auth'
const service = createAuthService(useRequestFetch())
const route = useRoute(); const email = ref(String(route.query.email || '')); const token = ref(String(route.query.token || '')); const password = ref(''); const confirmation = ref(''); const pending = ref(false); const message = ref(''); const success = ref(false)
async function submit() {
  pending.value = true; message.value = ''; success.value = false
  try { const result = await service.resetPassword({ email: email.value, token: token.value, password: password.value, password_confirmation: confirmation.value }); message.value = result.data.message; success.value = true; useAuthStore().clear(); await navigateTo('/dashboard/reset-password', { replace: true }) }
  catch (error: unknown) { message.value = authErrorMessage(error, 'This reset link is invalid or expired.') }
  finally { pending.value = false }
}
</script>
<template><main class="grid min-h-screen place-items-center bg-[#f6f6f1] px-4 py-12"><AuthCard><template #title>Choose a new password</template><template #subtitle>Use the reset link from your email. Links expire after 60 minutes and can be used once.</template><form class="space-y-5" @submit.prevent="submit"><Alert v-if="message" :variant="success ? 'success' : 'error'" :message="message"/><FormInput v-model="email" label="Email address" type="email" autocomplete="email"/><FormInput v-model="password" label="New password" type="password" autocomplete="new-password" placeholder="At least 10 characters"/><FormInput v-model="confirmation" label="Confirm password" type="password" autocomplete="new-password"/><FormButton :loading="pending">Update password</FormButton><p v-if="success" class="text-center text-sm"><NuxtLink to="/dashboard/login" class="font-semibold text-emerald-800">Continue to sign in</NuxtLink></p></form></AuthCard></main></template>
