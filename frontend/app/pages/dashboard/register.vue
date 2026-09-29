<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['guest'] })
const name = ref(''); const email = ref(''); const password = ref(''); const pending = ref(false); const message = ref(''); const success = ref(false)
async function submit() {
  pending.value = true; message.value = ''; success.value = false
  try { const result: any = await $fetch('/api/auth/register', { method: 'POST', body: { name: name.value, email: email.value, password: password.value, password_confirmation: password.value } }); message.value = result?.data?.message || 'Account created. Check your email to verify your address.'; success.value = true }
  catch (error: any) { message.value = error?.data?.errors ? Object.values(error.data.errors).flat().join(' ') : error?.data?.message || 'Unable to create your account.' }
  finally { pending.value = false }
}
</script>
<template><main class="grid min-h-screen place-items-center bg-[#f6f6f1] px-4 py-12"><AuthCard><template #title>Create your account</template><template #subtitle>A fresh start for your real estate workspace.</template><form class="space-y-5" @submit.prevent="submit"><Alert v-if="message" :variant="success ? 'success' : 'error'" :message="message"/><NuxtLink v-if="success" to="/dashboard/login" class="block text-center text-sm font-semibold text-emerald-800">Continue to sign in</NuxtLink><FormInput v-model="name" label="Full name" autocomplete="name" placeholder="Your name"/><FormInput v-model="email" label="Email address" type="email" autocomplete="email" placeholder="you@example.com"/><FormInput v-model="password" label="Password" type="password" autocomplete="new-password" placeholder="At least 10 characters"/><p class="-mt-2 text-xs leading-5 text-stone-500">Use uppercase and lowercase letters, a number, and a symbol.</p><FormButton :loading="pending">Create account</FormButton><p class="text-center text-sm text-stone-500">Already registered? <NuxtLink to="/dashboard/login" class="font-semibold text-emerald-800">Sign in</NuxtLink></p></form></AuthCard></main></template>
