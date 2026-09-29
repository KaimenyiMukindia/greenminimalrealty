<script setup lang="ts">
definePageMeta({ layout: 'dashboard' })
const { data, error } = await useFetch<{ data: { id: number; name: string; email: string; role: string } }>('/api/auth/me', { key: 'auth-me', server: false })
const user = computed(() => data.value?.data)
watch(error, async value => {
  if (value && (value as any).statusCode === 401) {
    const config = useRuntimeConfig()
    useCookie(config.public.sessionCookieName as string).value = null
    await navigateTo('/dashboard/login')
  }
})
</script>
<template><div><div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="text-sm font-medium text-emerald-700">Tuesday, welcome back</p><h1 class="mt-2 text-3xl font-semibold tracking-tight">Your workspace</h1><p class="mt-2 text-stone-500">A clear view of what matters in your business.</p></div><div class="rounded-2xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-600">{{ user?.name || 'Loading your profile…' }}</div></div><section class="mt-9 grid gap-5 lg:grid-cols-3"><article v-for="card in [{ label: 'Properties', value: 'Ready when you are', icon: '⌂' }, { label: 'Inquiries', value: 'Your next opportunity', icon: '✉' }, { label: 'Workspace', value: 'Built around you', icon: '✦' }]" :key="card.label" class="rounded-3xl border border-stone-200 bg-white p-6"><div class="grid size-11 place-items-center rounded-2xl bg-emerald-50 text-xl text-emerald-800">{{ card.icon }}</div><p class="mt-6 text-sm text-stone-500">{{ card.label }}</p><p class="mt-1 font-semibold">{{ card.value }}</p></article></section><section class="mt-8 rounded-3xl bg-emerald-950 p-8 text-white sm:p-10"><p class="text-xs font-semibold uppercase tracking-[.2em] text-emerald-200">Getting started</p><h2 class="mt-3 max-w-lg text-2xl font-semibold">A thoughtful foundation for your real estate work.</h2><p class="mt-3 max-w-xl text-sm leading-6 text-emerald-100/75">Your dashboard shell is ready. The next phase can bring your properties, inquiries, and content into this space.</p></section><p v-if="user" class="mt-6 text-xs text-stone-400">Signed in as {{ user.email }} · {{ user.role }}</p></div></template>
