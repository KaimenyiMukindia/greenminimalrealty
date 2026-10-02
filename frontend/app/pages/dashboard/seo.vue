<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: ['auth'] })
const search = ref('')
const appliedSearch = ref('')
const { data, pending, error, refresh } = await useFetch<{ data: any[] }>('/api/admin/page-meta?per_page=100&sort=route&direction=asc')
const drafts = reactive<Record<number, { meta_title: string; meta_description: string }>>({})
const saving = ref<number | null>(null)
const notices = reactive<Record<number, string>>({})
const pages = computed(() => (data.value?.data || []).filter((page: any) => `${page.title} ${page.route} ${page.meta_title || ''}`.toLowerCase().includes(appliedSearch.value.toLowerCase())))
watch(() => data.value?.data, (items) => {
  for (const page of items || []) drafts[page.id] = { meta_title: page.meta_title || '', meta_description: page.meta_description || '' }
}, { immediate: true })
function applySearch() { appliedSearch.value = search.value.trim() }
async function save(page: any) {
  saving.value = page.id
  notices[page.id] = ''
  try {
    await $fetch(`/api/admin/page-meta/${page.id}`, { method: 'PATCH', body: drafts[page.id] })
    notices[page.id] = 'Saved'
    await refresh()
  } catch (requestError: any) { notices[page.id] = requestError?.data?.message || 'Unable to save SEO fields.' }
  finally { saving.value = null }
}
</script>

<template>
  <section class="space-y-6">
    <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-emerald-800">Search appearance</p><h2 class="mt-2 text-2xl font-semibold">SEO for every page</h2><p class="mt-1 text-sm text-slate-500">Edit page titles and descriptions in place. Page routes are managed by the backend.</p></div>
    <form class="flex gap-2 rounded-2xl border border-slate-200 bg-white p-3" @submit.prevent="applySearch"><input v-model="search" type="search" placeholder="Find a page by title or route" class="min-w-0 flex-1 rounded-xl bg-slate-50 px-3 py-2 text-sm outline-none"><button class="rounded-xl bg-emerald-950 px-4 py-2 text-sm font-semibold text-white">Search</button></form>
    <Alert v-if="error" variant="error" :message="(error as any).data?.message || 'Unable to load page SEO.'" />
    <LoadingSkeleton v-else-if="pending && !data" />
    <EmptyState v-else-if="!pages.length" title="No pages found" description="Create a page record or change your search." />
    <div v-else class="space-y-4">
      <article v-for="page in pages" :key="page.id" class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2"><div><h3 class="font-semibold">{{ page.title }}</h3><p class="mt-1 text-xs text-slate-400">{{ page.route }}</p></div><NuxtLink :to="`/dashboard/page-meta/${page.id}`" class="text-xs font-semibold text-emerald-800">Edit page content</NuxtLink></div>
        <div class="grid gap-4 lg:grid-cols-2"><FormField label="Meta title"><input v-model="drafts[page.id].meta_title" maxlength="255" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></FormField><FormField label="Meta description"><textarea v-model="drafts[page.id].meta_description" maxlength="1000" rows="2" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm" /></FormField></div>
        <div class="mt-4 flex items-center justify-between"><span class="text-xs text-slate-400">{{ notices[page.id] }}</span><button class="rounded-lg bg-emerald-950 px-4 py-2 text-xs font-semibold text-white disabled:opacity-50" :disabled="saving === page.id" @click="save(page)">{{ saving === page.id ? 'Saving…' : 'Save SEO' }}</button></div>
      </article>
    </div>
  </section>
</template>
