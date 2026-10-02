<script setup lang="ts">
import { dashboardNav } from '~/config/dashboard-nav'

const route = useRoute()
const pending = ref(false)
const { data: profile } = await useFetch<{ data: { name: string; email: string; role: string } }>('/api/auth/me', { key: 'dashboard-user', server: false })
const { data: siteSettings } = await useFetch<{ data: { site_name?: string; logo?: string } }>('/api/public/settings', { key: 'dashboard-site-brand' })
const currentUser = computed(() => profile.value?.data)
const siteBrand = computed(() => siteSettings.value?.data)
const activeRail = computed(() => {
  const requestedRail = String(route.query.rail || '')
  if (requestedRail) {
    const requested = dashboardNav.find(item => item.id === requestedRail)
    if (requested) return requested
  }
  return dashboardNav.find(item => item.tabs.some(tab => route.path === tab.to.split('?')[0] || route.path.startsWith(`${tab.to.split('?')[0]}/`))) ?? dashboardNav[1]
})
const currentTab = computed(() => activeRail.value.tabs.find(tab => route.path === tab.to.split('?')[0] || route.path.startsWith(`${tab.to.split('?')[0]}/`)) ?? activeRail.value.tabs[0])
const pageTitle = computed(() => {
  const recordPage = route.params.id ? 'Edit record' : route.path.endsWith('/create') ? 'Create record' : ''
  return recordPage || currentTab.value.label
})

async function signOut() {
  pending.value = true
  try { await $fetch('/api/auth/logout', { method: 'POST' }) } catch { /* The server proxy clears the cookie on every outcome. */ }
  await navigateTo('/dashboard/login')
}
</script>

<template>
  <div class="min-h-screen bg-[#f5f6f2] text-slate-900 md:flex">
    <aside class="fixed inset-y-0 left-0 z-30 flex w-[4.5rem] flex-col border-r border-slate-200 bg-white px-2 py-4 md:w-60 md:px-4">
      <NuxtLink to="/dashboard/properties" aria-label="Dashboard home" class="mb-7 flex h-12 items-center justify-center rounded-2xl bg-emerald-950 text-xl font-semibold text-white md:justify-start md:px-3">
        <img v-if="siteBrand?.logo" :src="siteBrand.logo" :alt="siteBrand.site_name || ''" class="max-h-9 max-w-44 rounded-lg bg-white object-contain p-1">
        <span v-else aria-hidden="true">◈</span><span class="sr-only">Dashboard</span>
      </NuxtLink>
      <nav aria-label="Dashboard sections" class="flex flex-1 flex-col gap-1.5">
        <NuxtLink
          v-for="item in dashboardNav"
          :key="item.id"
          :to="item.tabs[0].to"
          :aria-label="item.label"
          :title="item.label"
          :aria-current="activeRail.id === item.id ? 'page' : undefined"
          class="group flex min-h-11 items-center justify-center gap-3 rounded-xl px-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-950 md:justify-start md:px-3"
          :class="activeRail.id === item.id ? 'bg-emerald-50 text-emerald-950 ring-1 ring-emerald-100' : ''"
        >
          <span class="w-6 shrink-0 text-center text-lg" aria-hidden="true">{{ item.icon }}</span>
          <span class="hidden truncate text-sm font-medium md:inline">{{ item.label }}</span>
        </NuxtLink>
      </nav>
      <div class="mt-4 border-t border-slate-100 pt-4">
        <NuxtLink to="/dashboard/account" class="flex items-center justify-center gap-3 rounded-xl px-2 py-2 hover:bg-slate-50 md:justify-start md:px-3">
          <span class="grid size-8 shrink-0 place-items-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-950">{{ currentUser?.name?.slice(0, 1)?.toUpperCase() || 'U' }}</span>
          <span class="hidden min-w-0 md:block"><span class="block truncate text-xs font-semibold">{{ currentUser?.name || 'Account' }}</span><span class="block truncate text-[11px] capitalize text-slate-400">{{ currentUser?.role || 'Profile' }}</span></span>
        </NuxtLink>
      </div>
    </aside>

    <div class="min-w-0 flex-1 pl-[4.5rem] md:pl-60">
      <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 backdrop-blur">
        <div class="flex min-h-[4.25rem] items-center justify-between gap-4 px-4 sm:px-7">
          <div class="min-w-0">
            <p class="truncate text-xs font-medium text-slate-400">{{ activeRail.label }}</p>
            <h1 class="truncate text-sm font-semibold text-slate-900">{{ pageTitle }}</h1>
          </div>
          <div class="flex shrink-0 items-center gap-3">
            <span class="hidden max-w-48 truncate text-sm text-slate-500 sm:inline">{{ currentUser?.name }}</span>
            <button class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 disabled:opacity-50" :disabled="pending" @click="signOut">{{ pending ? 'Signing out…' : 'Sign out' }}</button>
          </div>
        </div>
        <nav aria-label="Section tabs" class="flex gap-1 overflow-x-auto border-t border-slate-100 px-3 sm:px-6">
          <NuxtLink
            v-for="tab in activeRail.tabs"
            :key="tab.to"
            :to="tab.to"
            class="shrink-0 border-b-2 px-3 py-3 text-xs font-medium transition sm:px-4"
            :class="currentTab.to === tab.to ? 'border-emerald-800 text-emerald-950' : 'border-transparent text-slate-500 hover:text-slate-900'"
          >{{ tab.label }}</NuxtLink>
        </nav>
      </header>
      <main class="mx-auto w-full max-w-[1440px] p-4 sm:p-7 lg:p-9"><slot /></main>
    </div>
  </div>
</template>
