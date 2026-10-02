<script setup lang="ts">
const { data } = await useFetch<{ data: { site_name?: string; logo?: string } }>('/api/public/settings', { key: 'public-settings-logo' })
const site = computed(() => data.value?.data)
</script>

<template>
  <NuxtLink to="/dashboard" :aria-label="site?.site_name || ''" class="inline-flex items-center gap-3 font-semibold tracking-tight text-emerald-950">
    <img v-if="site?.logo" :src="site.logo" :alt="site?.site_name || ''" class="h-12 max-w-40 object-contain">
    <span v-else aria-hidden="true" class="grid size-10 place-items-center rounded-2xl bg-emerald-900 text-lg text-white">◈</span>
  </NuxtLink>
</template>
