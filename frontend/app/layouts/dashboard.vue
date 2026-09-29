<script setup lang="ts">
import { dashboardNav } from '~/config/dashboard-nav'
const groups = computed(() => [...new Set(dashboardNav.map(item => item.section))])
const pending = ref(false)
async function signOut() {
  pending.value = true
  try { await $fetch('/api/auth/logout', { method: 'POST' }) } catch { /* Cookie is cleared by the server route on every outcome. */ }
  await navigateTo('/dashboard/login')
}
</script>
<template><div class="min-h-screen bg-[#f6f6f1] text-emerald-950 md:flex"><aside class="hidden w-72 shrink-0 border-r border-stone-200 bg-white p-7 md:flex md:flex-col"><AppLogo /><p class="mt-12 text-[11px] font-semibold uppercase tracking-[.2em] text-stone-400">Navigation</p><div v-for="section in groups" :key="section" class="mt-6"><p class="mb-2 text-xs font-semibold text-stone-400">{{ section }}</p><nav class="space-y-1"><NuxtLink v-for="item in dashboardNav.filter(link => link.section === section)" :key="item.to" :to="item.to" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-stone-600 transition hover:bg-emerald-50 hover:text-emerald-950"><span class="w-5 text-center">{{ item.icon }}</span>{{ item.label }}</NuxtLink></nav></div><div class="mt-auto rounded-2xl bg-emerald-50 p-4"><p class="text-sm font-semibold">A calmer way to work</p><p class="mt-1 text-xs leading-5 text-stone-500">Your real estate workspace, kept simple.</p></div></aside><div class="min-w-0 flex-1"><header class="flex h-20 items-center justify-between border-b border-stone-200 bg-white/80 px-5 sm:px-9"><div class="md:hidden"><AppLogo /></div><p class="hidden text-sm text-stone-500 md:block">Good to see you again</p><button class="rounded-full border border-stone-200 px-4 py-2 text-sm font-medium hover:bg-stone-50" :disabled="pending" @click="signOut">Sign out</button></header><main class="mx-auto max-w-7xl p-5 sm:p-9"><slot /></main></div></div></template>
