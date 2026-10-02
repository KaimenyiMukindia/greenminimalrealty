<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: ['auth'] })
const { data, pending, error, refresh } = await useFetch<{ data: any[] }>('/api/admin/menus?per_page=100')
const { data: profile } = await useFetch<{ data: { role: string } }>('/api/auth/me', { key: 'dashboard-user', server: false })
const emptyMenu = () => ({ name: '', location: 'primary', published: true, items: [] as any[] })
const form = reactive<any>(emptyMenu())
const selectedId = ref<number | null>(null)
const busy = ref(false)
const message = ref('')
const errorMessage = ref('')
const canDelete = computed(() => profile.value?.data.role === 'admin')
const menus = computed(() => data.value?.data || [])

function selectMenu(menu?: any) {
  selectedId.value = menu?.id ?? null
  Object.assign(form, menu ? { name: menu.name, location: menu.location, published: menu.published, items: (menu.items || []).map((item: any) => ({ ...item, children: item.children || [] })) } : emptyMenu())
  message.value = ''
  errorMessage.value = ''
}
function addItem(parentIndex?: number) {
  const item = { label: '', url: '/', order: form.items.length, published: true, children: [] as any[] }
  if (parentIndex === undefined) form.items.push(item)
  else form.items[parentIndex].children.push(item)
}
function removeItem(index: number, parentIndex?: number) {
  if (parentIndex === undefined) form.items.splice(index, 1)
  else form.items[parentIndex].children.splice(index, 1)
}
function moveItem(index: number, delta: number, parentIndex?: number) {
  const items = parentIndex === undefined ? form.items : form.items[parentIndex].children
  const target = index + delta
  if (target < 0 || target >= items.length) return
  ;[items[index], items[target]] = [items[target], items[index]]
  items.forEach((item: any, order: number) => { item.order = order })
}
function flatten(items: any[], parent?: any): any[] {
  return items.flatMap((item, order) => {
    if (!item.id) item.client_key ||= `new-${crypto.randomUUID()}`
    const current = {
      id: item.id,
      client_key: item.client_key,
      parent_id: parent?.id || null,
      parent_client_key: !parent?.id ? parent?.client_key : undefined,
      label: item.label,
      url: item.url,
      published: item.published,
      order,
    }
    return [current, ...flatten(item.children || [], item)]
  })
}
async function saveMenu() {
  busy.value = true
  errorMessage.value = ''
  message.value = ''
  try {
    const payload = { name: form.name, location: form.location, published: form.published, items: flatten(form.items) }
    const result: any = selectedId.value
      ? await $fetch(`/api/admin/menus/${selectedId.value}`, { method: 'PATCH', body: payload })
      : await $fetch('/api/admin/menus', { method: 'POST', body: payload })
    selectedId.value = result.data.id
    message.value = 'Menu saved. Public navigation updates immediately.'
    await refresh()
    selectMenu(menus.value.find(menu => menu.id === selectedId.value) || result.data)
  } catch (requestError: any) {
    errorMessage.value = requestError?.data?.errors ? Object.values(requestError.data.errors).flat().join(' ') : requestError?.data?.message || 'Unable to save this menu.'
  } finally { busy.value = false }
}
async function deleteMenu() {
  if (!selectedId.value || !canDelete.value || !confirm('Delete this menu?')) return
  busy.value = true
  try { await $fetch(`/api/admin/menus/${selectedId.value}`, { method: 'DELETE' }); selectMenu(); await refresh() }
  catch (requestError: any) { errorMessage.value = requestError?.data?.message || 'Unable to delete this menu.' }
  finally { busy.value = false }
}
</script>

<template>
  <section class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.16em] text-emerald-800">Navigation</p><h2 class="mt-2 text-2xl font-semibold">Menus</h2><p class="mt-1 text-sm text-slate-500">Manage menu slots and nested links. Primary navigation is reflected on the public site.</p></div><button class="rounded-xl bg-emerald-950 px-4 py-3 text-sm font-semibold text-white" @click="selectMenu()">New menu</button></div>
    <Alert v-if="error" variant="error" :message="(error as any).data?.message || 'Unable to load menus.'" />
    <div class="grid gap-6 xl:grid-cols-[minmax(15rem,.75fr)_minmax(0,1.6fr)]">
      <aside class="rounded-2xl border border-slate-200 bg-white p-4"><div class="mb-3 flex items-center justify-between"><h3 class="text-sm font-semibold">Saved menus</h3><span class="text-xs text-slate-400">{{ menus.length }}</span></div><LoadingSkeleton v-if="pending && !data" /><EmptyState v-else-if="!menus.length" title="No menus yet" description="Create a menu and add links." /><button v-for="menu in menus" :key="menu.id" class="mb-2 flex w-full items-center justify-between rounded-xl border px-3 py-3 text-left transition" :class="selectedId === menu.id ? 'border-emerald-300 bg-emerald-50' : 'border-slate-100 hover:bg-slate-50'" @click="selectMenu(menu)"><span><span class="block text-sm font-semibold">{{ menu.name }}</span><span class="text-xs text-slate-500">{{ menu.location }}</span></span><span class="text-xs text-slate-400">{{ menu.items?.length || 0 }} links</span></button></aside>
      <form class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-7" @submit.prevent="saveMenu">
        <Alert v-if="message" variant="success" :message="message" /><Alert v-if="errorMessage" variant="error" :message="errorMessage" />
        <div class="grid gap-4 sm:grid-cols-2"><FormField label="Menu name"><input v-model="form.name" required class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField><FormField label="Navigation slot"><select v-model="form.location" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"><option value="primary">Primary navigation</option><option value="footer">Footer navigation</option><option value="secondary">Secondary navigation</option></select></FormField></div>
        <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="form.published" type="checkbox" class="accent-emerald-800"> Menu is published</label>
        <div class="flex items-center justify-between border-t border-slate-100 pt-4"><div><h3 class="text-sm font-semibold">Menu items</h3><p class="mt-1 text-xs text-slate-500">Reorder, edit, or add nested links.</p></div><button type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold hover:bg-slate-50" @click="addItem()">Add link</button></div>
        <EmptyState v-if="!form.items.length" title="This menu has no links" description="Add links to show them in the selected navigation slot." />
        <div v-for="(item, index) in form.items" :key="item.id || `root-${index}`" class="space-y-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3">
          <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto]"><input v-model="item.label" required aria-label="Link label" placeholder="Link label" class="min-w-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><input v-model="item.url" required aria-label="Link URL" placeholder="/page or https://…" class="min-w-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><div class="flex gap-1"><button type="button" class="rounded-lg px-2 text-sm hover:bg-white" :disabled="index === 0" @click="moveItem(Number(index), -1)">↑</button><button type="button" class="rounded-lg px-2 text-sm hover:bg-white" :disabled="Number(index) === form.items.length - 1" @click="moveItem(Number(index), 1)">↓</button><button type="button" class="px-2 text-xs text-rose-700" @click="removeItem(Number(index))">Remove</button></div></div>
          <div class="flex items-center justify-between pl-3"><label class="flex items-center gap-2 text-xs text-slate-500"><input v-model="item.published" type="checkbox" class="accent-emerald-800"> Visible</label><button type="button" class="text-xs font-semibold text-emerald-800" @click="addItem(Number(index))">Add submenu item</button></div>
          <div v-for="(child, childIndex) in item.children" :key="child.id || `child-${childIndex}`" class="ml-4 grid gap-2 border-l-2 border-emerald-200 pl-3 sm:grid-cols-[1fr_1fr_auto]"><input v-model="child.label" required aria-label="Nested link label" placeholder="Nested label" class="min-w-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><input v-model="child.url" required aria-label="Nested link URL" placeholder="/nested-page" class="min-w-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"><div class="flex items-center gap-2"><label class="flex items-center gap-1 text-xs text-slate-500"><input v-model="child.published" type="checkbox" class="accent-emerald-800"> Visible</label><button type="button" class="text-xs text-rose-700" @click="removeItem(Number(childIndex), Number(index))">Remove</button></div></div>
        </div>
        <div class="flex flex-wrap justify-between gap-3 border-t border-slate-100 pt-4"><button v-if="selectedId && canDelete" type="button" class="rounded-xl border border-rose-200 px-4 py-2.5 text-sm font-medium text-rose-700" @click="deleteMenu">Delete menu</button><span v-else /><button class="rounded-xl bg-emerald-950 px-5 py-3 text-sm font-semibold text-white disabled:opacity-50" :disabled="busy">{{ busy ? 'Saving…' : 'Save menu' }}</button></div>
      </form>
    </div>
  </section>
</template>
