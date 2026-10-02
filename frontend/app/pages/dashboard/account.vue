<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: ['auth'] })
const { data, error, refresh } = await useFetch<{ data: { id: number; name: string; email: string; role: string } }>('/api/auth/me', { key: 'dashboard-account', server: false })
const profile = reactive({ name: data.value?.data.name || '', email: data.value?.data.email || '' })
const password = reactive({ current_password: '', password: '', password_confirmation: '' })
const profileSaving = ref(false)
const passwordSaving = ref(false)
const profileMessage = ref('')
const profileError = ref('')
const passwordMessage = ref('')
const passwordError = ref('')
watch(() => data.value?.data, user => { if (user) { profile.name = user.name; profile.email = user.email } }, { immediate: true })
async function saveProfile() {
  profileSaving.value = true; profileMessage.value = ''; profileError.value = ''
  try { await $fetch('/api/auth/me', { method: 'PATCH', body: profile }); profileMessage.value = 'Profile updated.'; await refresh() }
  catch (requestError: any) { profileError.value = requestError?.data?.errors ? Object.values(requestError.data.errors).flat().join(' ') : requestError?.data?.message || 'Unable to update profile.' }
  finally { profileSaving.value = false }
}
async function savePassword() {
  passwordSaving.value = true; passwordMessage.value = ''; passwordError.value = ''
  try { await $fetch('/api/auth/password', { method: 'PUT', body: password }); passwordMessage.value = 'Password updated. Other sessions have been signed out.'; Object.assign(password, { current_password: '', password: '', password_confirmation: '' }) }
  catch (requestError: any) { passwordError.value = requestError?.data?.errors ? Object.values(requestError.data.errors).flat().join(' ') : requestError?.data?.message || 'Unable to change password.' }
  finally { passwordSaving.value = false }
}
</script>

<template>
  <section class="mx-auto max-w-4xl space-y-6">
    <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-emerald-800">Your account</p><h2 class="mt-2 text-2xl font-semibold">Profile and security</h2><p class="mt-1 text-sm text-slate-500">Update your account details and keep your sign-in secure.</p></div>
    <Alert v-if="error" variant="error" :message="(error as any).data?.message || 'Unable to load your account.'" />
    <form class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-7" @submit.prevent="saveProfile"><div><h3 class="font-semibold">Personal details</h3><p class="mt-1 text-xs text-slate-500">Role: <span class="capitalize">{{ data?.data.role }}</span></p></div><Alert v-if="profileMessage" variant="success" :message="profileMessage" /><Alert v-if="profileError" variant="error" :message="profileError" /><div class="grid gap-4 sm:grid-cols-2"><FormField label="Name"><input v-model="profile.name" required maxlength="120" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField><FormField label="Email"><input v-model="profile.email" required type="email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField></div><div class="flex justify-end"><button class="rounded-xl bg-emerald-950 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50" :disabled="profileSaving">{{ profileSaving ? 'Saving…' : 'Save profile' }}</button></div></form>
    <form class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-7" @submit.prevent="savePassword"><div><h3 class="font-semibold">Change password</h3><p class="mt-1 text-xs text-slate-500">Use at least 10 characters including uppercase, lowercase, a number, and a symbol.</p></div><Alert v-if="passwordMessage" variant="success" :message="passwordMessage" /><Alert v-if="passwordError" variant="error" :message="passwordError" /><div class="grid gap-4 sm:grid-cols-2"><FormField label="Current password"><input v-model="password.current_password" required type="password" autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField><span /><FormField label="New password"><input v-model="password.password" required type="password" autocomplete="new-password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField><FormField label="Confirm new password"><input v-model="password.password_confirmation" required type="password" autocomplete="new-password" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField></div><div class="flex justify-end"><button class="rounded-xl bg-emerald-950 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50" :disabled="passwordSaving">{{ passwordSaving ? 'Updating…' : 'Update password' }}</button></div></form>
  </section>
</template>
