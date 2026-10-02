<script setup lang="ts">
const { data: settings } = await useAsyncData('public-settings', () => $fetch<{ data: Record<string, any> }>('/api/public/settings'))
const site = computed(() => settings.value?.data)
const navItems = computed(() => site.value?.nav_items || [])
const fontCss = computed(() => (site.value?.font_assets || []).map((font: any) => `@font-face{font-family:'${font.family}';font-style:normal;font-weight:${font.weight};font-display:swap;src:url('${font.url}') format('${String(font.file).endsWith('.woff2') ? 'woff2' : 'truetype'}');}`).join(''))
useHead(() => ({ style: [{ children: fontCss.value }] }))
</script>

<template>
  <div class="min-h-screen bg-[#fbfaf5] text-[#183a2d]">
    <header class="sticky top-0 z-10 border-b border-stone-200/80 bg-[#fbfaf5]/95 backdrop-blur">
      <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8">
        <NuxtLink to="/" :aria-label="site?.site_name || ''" class="flex items-center">
          <img v-if="site?.logo" :src="site.logo" :alt="site?.site_name || ''" class="h-16 w-auto">
          <span v-else-if="site?.site_name" class="font-semibold">{{ site.site_name }}</span>
        </NuxtLink>
        <nav class="hidden items-center gap-6 lg:flex">
          <NuxtLink v-for="item in navItems" :key="item.to" :to="item.to" class="text-sm text-stone-600 transition hover:text-emerald-900">{{ item.label }}</NuxtLink>
          <NuxtLink v-if="site?.nav_cta_label && site?.nav_cta_url" :to="site.nav_cta_url" class="rounded-full bg-emerald-900 px-5 py-2.5 text-sm font-medium text-white">{{ site.nav_cta_label }}</NuxtLink>
        </nav>
      </div>
    </header>
    <main><slot /></main>
    <footer class="border-t border-stone-200 bg-stone-100/70">
      <div class="mx-auto grid max-w-7xl gap-8 px-5 py-14 sm:px-8 md:grid-cols-3">
        <div class="md:col-span-2"><img v-if="site?.logo" :src="site.logo" :alt="site?.site_name || ''" class="h-16 w-auto"><p class="mt-4 max-w-md text-sm leading-6 text-stone-500">{{ site?.footer_tagline }}</p></div>
        <div class="text-sm text-stone-600"><p>{{ site?.address }}</p><a class="mt-2 block" :href="`tel:${site?.phone}`">{{ site?.phone }}</a><a class="mt-2 block" :href="`mailto:${site?.email}`">{{ site?.email }}</a></div>
      </div>
      <div class="border-t border-stone-200 px-5 py-5 text-center text-xs text-stone-500">{{ site?.footer_copyright }}</div>
    </footer>
    <a v-if="site?.whatsapp_url && site?.whatsapp_label" :href="site.whatsapp_url" target="_blank" rel="noreferrer" class="fixed bottom-6 right-6 rounded-full bg-emerald-700 px-4 py-3 text-sm font-medium text-white shadow-lg">{{ site.whatsapp_label }}</a>
  </div>
</template>