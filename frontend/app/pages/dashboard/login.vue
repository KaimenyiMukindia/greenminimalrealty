<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['guest'] })
const email = ref(''); const password = ref(''); const pending = ref(false); const message = ref('')
async function submit() {
  pending.value = true; message.value = ''
  try { await $fetch('/api/auth/login', { method: 'POST', body: { email: email.value, password: password.value } }); await navigateTo('/dashboard') }
  catch (error: any) { message.value = error?.data?.message || error?.data?.errors?.email?.[0] || 'Unable to sign in. Check your details and try again.' }
  finally { pending.value = false }
}
</script>
<template><main class="grid min-h-screen place-items-center bg-[#f6f6f1] px-4 py-12"><AuthCard><template #title>Welcome back</template><template #subtitle>Sign in to pick up right where you left off.</template><form class="space-y-5" @submit.prevent="submit"><Alert v-if="message" variant="error" :message="message"/><FormInput v-model="email" label="Email address" type="email" autocomplete="email" placeholder="you@example.com"/><FormInput v-model="password" label="Password" type="password" autocomplete="current-password" placeholder="Your password"/><div class="flex justify-end text-sm"><NuxtLink to="/dashboard/forgot-password" class="font-medium text-emerald-800 hover:text-emerald-950">Forgot password?</NuxtLink></div><FormButton :loading="pending">Sign in</FormButton><p class="text-center text-sm text-stone-500">New to Greenminimal? <NuxtLink to="/dashboard/register" class="font-semibold text-emerald-800">Create account</NuxtLink></p></form></AuthCard></main></template>
