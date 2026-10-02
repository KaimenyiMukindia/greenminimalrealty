<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: ['auth'] })
const route = useRoute()
const router = useRouter()
const { data: profile } = await useFetch<{ data: { id: number; role: string } }>('/api/auth/me', { key: 'dashboard-user', server: false })
const search = ref(String(route.query.search || ''))
const role = ref(String(route.query.role || ''))
const query = computed(() => new URLSearchParams({ per_page: '50', search: search.value, role: role.value }).toString())
const { data, error, pending, refresh } = await useFetch<{ data: any[]; meta: any }>(() => `/api/admin/users?${query.value}`, { watch: [query] })
const busyId = ref<number | null>(null)
const notice = ref('')
const isAdmin = computed(() => profile.value?.data.role === 'admin')
let debounce: ReturnType<typeof setTimeout> | undefined
watch([search, role], () => {
  clearTimeout(debounce)
  debounce = setTimeout(() => router.replace({ query: { ...route.query, search: search.value || undefined, role: role.value || undefined } }), 200)
})
async function updateRole(user: any) {
  busyId.value = user.id; notice.value = ''
  try { await $fetch(`/api/admin/users/${user.id}`, { method: 'PATCH', body: { role: user.role } }); notice.value = `${user.name} updated.`; await refresh() }
  catch (requestError: any) { notice.value = requestError?.data?.message || 'Unable to update user role.'; await refresh() }
  finally { busyId.value = null }
}
async function remove(user: any) {
  if (!confirm(`Remove ${user.name}'s access?`)) return
  busyId.value = user.id; notice.value = ''
  try { await $fetch(`/api/admin/users/${user.id}`, { method: 'DELETE' }); notice.value = 'User removed.'; await refresh() }
  catch (requestError: any) { notice.value = requestError?.data?.message || 'Unable to remove user.' }
  finally { busyId.value = null }
}
</script>

<template>
  <section class="space-y-6">
    <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-emerald-800">Team access</p><h2 class="mt-2 text-2xl font-semibold">Users</h2><p class="mt-1 text-sm text-slate-500">Only administrators can change roles or revoke accounts.</p></div>
    <Alert v-if="!isAdmin" variant="error" message="Administrator access is required to manage users." />
    <Alert v-if="notice" :message="notice" variant="info" />
    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-[1fr_13rem]"><input v-model="search" type="search" placeholder="Search name or email" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><select v-model="role" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><option value="">All roles</option><option value="admin">Admin</option><option value="editor">Editor</option><option value="user">User</option></select></div>
    <Alert v-if="error && isAdmin" variant="error" :message="(error as any).data?.message || 'Unable to load users.'" />
    <LoadingSkeleton v-else-if="pending && !data" />
    <EmptyState v-else-if="isAdmin && !data?.data?.length" title="No users found" description="Try another search or role filter." />
    <div v-else-if="isAdmin && data?.data?.length" class="overflow-x-auto rounded-2xl border border-slate-200 bg-white"><table class="w-full min-w-[650px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="px-5 py-4">User</th><th class="px-5 py-4">Role</th><th class="px-5 py-4 text-right">Access</th></tr></thead><tbody><tr v-for="user in data.data" :key="user.id" class="border-t border-slate-100"><td class="px-5 py-4"><span class="block font-semibold">{{ user.name }}</span><span class="text-xs text-slate-500">{{ user.email }}</span></td><td class="px-5 py-4"><select v-model="user.role" :disabled="busyId === user.id || user.id === profile?.data.id" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm capitalize"><option value="user">User</option><option value="editor">Editor</option><option value="admin">Admin</option></select><button v-if="user.id !== profile?.data.id" class="ml-2 text-xs font-semibold text-emerald-800" :disabled="busyId === user.id" @click="updateRole(user)">Save role</button></td><td class="px-5 py-4 text-right"><button v-if="user.id !== profile?.data.id" class="text-xs font-semibold text-rose-700 disabled:opacity-50" :disabled="busyId === user.id" @click="remove(user)">Remove</button><span v-else class="text-xs text-slate-400">Current account</span></td></tr></tbody></table></div>
    <p v-if="data?.meta" class="text-xs text-slate-400">{{ data.meta.total }} total accounts</p>
  </section>
</template>
