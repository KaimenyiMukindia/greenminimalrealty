<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['guest'] })
const route = useRoute(); const email = ref(String(route.query.email || '')); const token = ref(String(route.query.token || '')); const password = ref(''); const confirmation = ref(''); const pending = ref(false); const message = ref(''); const success = ref(false)
async function submit() {
  pending.value = true; message.value = ''; success.value = false
  try { const result: any = await $fetch('/api/auth/reset-password', { method: 'POST', body: { email: email.value, token: token.value, password: password.value, password_confirmation: confirmation.value } }); message.value = result?.data?.message || 'Password reset successfully.'; success.value = true }
  catch (error: any) { message.value = error?.data?.errors ? Object.values(error.data.errors).flat().join(' ') : error?.data?.message || 'This reset link is invalid or expired.' }
  finally { pending.value = false }
}
</script>
<template><main class="grid min-h-screen place-items-center bg-[#f6f6f1] px-4 py-12"><AuthCard><template #title>Choose a new password</template><template #subtitle>Password recovery emails are paused. This form works only if you already have a valid reset token.</template><form class="space-y-5" @submit.prevent="submit"><Alert v-if="message" :variant="success ? 'success' : 'error'" :message="message"/><FormInput v-model="email" label="Email address" type="email" autocomplete="email"/><FormInput v-model="password" label="New password" type="password" autocomplete="new-password" placeholder="At least 10 characters"/><FormInput v-model="confirmation" label="Confirm password" type="password" autocomplete="new-password"/><FormButton :loading="pending">Update password</FormButton><p v-if="success" class="text-center text-sm"><NuxtLink to="/dashboard/login" class="font-semibold text-emerald-800">Continue to sign in</NuxtLink></p></form></AuthCard></main></template>
