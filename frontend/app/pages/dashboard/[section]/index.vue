<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: ['auth'] })
const route = useRoute()
const router = useRouter()
const section = computed(() => String(route.params.section))
const title = computed(() => ({ 'page-meta': 'Pages', properties: 'Property Listings', 'airbnb-listings': 'Airbnb Management', services: 'Services', testimonials: 'Testimonials', pillars: 'Sustainability', values: 'Values', stats: 'Statistics', submissions: 'Contact Submissions' }[section.value] || section.value.replaceAll('-', ' ').replace(/\b\w/g, letter => letter.toUpperCase())))
const filters = reactive({ search: String(route.query.search || ''), published: String(route.query.published || ''), section: String(route.query.section || ''), sort: String(route.query.sort || 'order'), direction: String(route.query.direction || 'asc'), page: Number(route.query.page || 1) })
const queryString = computed(() => {
  const query = new URLSearchParams({ per_page: '20', sort: filters.sort, direction: filters.direction, page: String(filters.page) })
  if (filters.search.trim()) query.set('search', filters.search.trim())
  if (filters.published) query.set('published', filters.published)
  if (filters.section) query.set('section', filters.section)
  return query.toString()
})
const { data, error, pending, refresh } = await useFetch<{ data: any[]; meta: { current_page: number; last_page: number; total: number } }>(() => `/api/admin/${section.value}?${queryString.value}`, { watch: [section, queryString] })
const { data: profile } = await useFetch<{ data: { role: string } }>('/api/auth/me', { key: 'dashboard-user', server: false })
const canDelete = computed(() => profile.value?.data.role === 'admin')
const orderedResources = ['properties', 'services', 'testimonials', 'pillars', 'values', 'stats', 'airbnb-listings']
let debounce: ReturnType<typeof setTimeout> | undefined
watch(() => [filters.search, filters.published, filters.section, filters.sort, filters.direction], () => {
  clearTimeout(debounce)
  debounce = setTimeout(() => router.replace({ query: { ...route.query, search: filters.search || undefined, published: filters.published || undefined, section: filters.section || undefined, sort: filters.sort, direction: filters.direction, page: undefined } }), 250)
})
watch(() => route.query, (query) => {
  filters.search = String(query.search || '')
  filters.published = String(query.published || '')
  filters.section = String(query.section || '')
  filters.sort = String(query.sort || 'order')
  filters.direction = String(query.direction || 'asc')
  filters.page = Number(query.page || 1)
}, { deep: true })
async function remove(id: number) {
  if (!canDelete.value || !confirm('Delete this record? This action cannot be undone.')) return
  try { await $fetch(`/api/admin/${section.value}/${id}`, { method: 'DELETE' }); await refresh() } catch (requestError: any) { alert(requestError?.data?.message || 'Delete failed.') }
}
async function setPage(page: number) {
  filters.page = page
  await router.push({ query: { ...route.query, page: String(page) } })
}
</script>

<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-emerald-800">{{ title }}</p><h2 class="mt-2 text-2xl font-semibold tracking-tight">Manage {{ title.toLowerCase() }}</h2><p class="mt-1 text-sm text-slate-500">{{ data?.meta?.total ?? 0 }} records · Changes are saved to the content database.</p></div>
      <NuxtLink v-if="section !== 'submissions'" :to="`/dashboard/${section}/create`" class="rounded-xl bg-emerald-950 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800">Add {{ title.slice(0, -1) }}</NuxtLink>
    </div>

    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 xl:grid-cols-5">
      <label class="sm:col-span-2"><span class="mb-1 block text-xs font-semibold text-slate-500">Search</span><input v-model="filters.search" type="search" placeholder="Search records…" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></label>
      <label v-if="section === 'stats'"><span class="mb-1 block text-xs font-semibold text-slate-500">Section</span><select v-model="filters.section" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><option value="">All sections</option><option value="hero">Hero</option><option value="airbnb">Airbnb</option></select></label>
      <label v-if="['properties', 'airbnb-listings', 'services', 'testimonials', 'pillars', 'values'].includes(section)"><span class="mb-1 block text-xs font-semibold text-slate-500">Publication</span><select v-model="filters.published" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><option value="">All</option><option value="1">Published</option><option value="0">Draft</option></select></label>
      <label><span class="mb-1 block text-xs font-semibold text-slate-500">Sort by</span><select v-model="filters.sort" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><option value="order">Display order</option><option value="created_at">Recently added</option><option value="title">Title</option></select></label>
      <label><span class="mb-1 block text-xs font-semibold text-slate-500">Direction</span><select v-model="filters.direction" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><option value="asc">Ascending</option><option value="desc">Descending</option></select></label>
    </div>

    <Alert v-if="error" variant="error" :message="(error as any).data?.message || 'Unable to load this list.'" />
    <LoadingSkeleton v-else-if="pending && !data" />
    <EmptyState v-else-if="!data?.data?.length" :title="filters.search ? 'No matching records' : `No ${title.toLowerCase()} yet`" description="Try adjusting the filters or add a new record." />
    <template v-else>
      <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="overflow-x-auto"><table class="w-full min-w-[600px] text-left text-sm">
          <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-3.5">Record</th><th class="px-5 py-3.5">Status</th><th class="px-5 py-3.5">Last updated</th><th class="px-5 py-3.5 text-right">Actions</th></tr></thead>
          <tbody><tr v-for="item in data.data" :key="item.id" class="border-t border-slate-100 hover:bg-slate-50/70"><td class="px-5 py-4"><span class="block font-semibold text-slate-800">{{ item.title || item.author_name || item.route || item.value || item.label || item.name || item.email }}</span><span v-if="section === 'page-meta'" class="mt-1 block text-xs text-slate-400">{{ item.route }}</span><span v-else-if="section === 'properties' || section === 'airbnb-listings'" class="mt-1 block text-xs text-slate-400">{{ item.location }}</span></td><td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="item.published === false || item.is_published === false ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800'">{{ item.published === false || item.is_published === false ? 'Draft' : 'Published' }}</span></td><td class="px-5 py-4 text-xs text-slate-500">{{ item.updated_at ? new Date(item.updated_at).toLocaleDateString() : '—' }}</td><td class="px-5 py-4"><div class="flex justify-end gap-3"><NuxtLink v-if="section !== 'submissions'" :to="`/dashboard/${section}/${item.id}`" class="font-medium text-emerald-800 hover:text-emerald-950">Edit</NuxtLink><button v-if="canDelete" class="font-medium text-rose-700 hover:text-rose-900" @click="remove(item.id)">Delete</button></div></td></tr></tbody>
        </table></div>
      </div>
      <div v-if="orderedResources.includes(section)" class="rounded-2xl border border-slate-200 bg-white p-4"><p class="mb-3 text-xs font-medium text-slate-500">Adjust the displayed order using the arrow controls.</p><SortableList :items="data.data" :resource="section" @saved="refresh" /></div>
      <div class="flex items-center justify-between text-sm text-slate-500"><span>Page {{ data.meta.current_page }} of {{ data.meta.last_page }} · {{ data.meta.total }} total</span><div class="flex gap-2"><button class="rounded-lg border px-3 py-2 disabled:opacity-40" :disabled="data.meta.current_page <= 1" @click="setPage(data.meta.current_page - 1)">Previous</button><button class="rounded-lg border px-3 py-2 disabled:opacity-40" :disabled="data.meta.current_page >= data.meta.last_page" @click="setPage(data.meta.current_page + 1)">Next</button></div></div>
    </template>
  </section>
</template>
