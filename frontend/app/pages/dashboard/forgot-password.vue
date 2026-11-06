<script setup lang="ts">
import { createAuthService, authErrorMessage } from '~/services/auth'
definePageMeta({ layout: false })
const service = createAuthService(useRequestFetch())
const email = ref('')
const pending = ref(false)
const message = ref('')
const success = ref(false)
async function submit() {
  pending.value = true; message.value = ''; success.value = false
  try { message.value = (await service.forgotPassword(email.value)).data.message; success.value = true }
  catch (error: unknown) { message.value = authErrorMessage(error, 'Unable to request a password reset. Please try again.') }
  finally { pending.value = false }
}
</script>
<template><main class="grid min-h-screen place-items-center bg-[#f6f6f1] px-4 py-12"><AuthCard><template #title>Reset your password</template><template #subtitle>Enter your email to request a password reset link.</template><form class="space-y-5" @submit.prevent="submit"><Alert v-if="message" :variant="success ? 'success' : 'error'" :message="message" /><FormInput v-model="email" label="Email address" type="email" autocomplete="email" /><FormButton :loading="pending">Send reset link</FormButton><NuxtLink to="/dashboard/login" class="block text-center text-sm font-semibold text-emerald-800">Back to sign in</NuxtLink></form></AuthCard></main></template>
