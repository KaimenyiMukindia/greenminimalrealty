<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: ['auth'] })
const { data, error, refresh } = await useFetch<{ data: any[] }>('/api/admin/settings?per_page=1')
const { data: mediaResponse } = await useFetch<{ data: any[] }>('/api/admin/media?per_page=100')
const form = reactive<Record<string, any>>({ ...(data.value?.data?.[0] || {}) })
const homeContentText = ref(JSON.stringify(form.home_content || {}, null, 2))
const contactLabelsText = ref(JSON.stringify(form.contact_form_labels || {}, null, 2))
const saving = ref(false)
const message = ref('')
const failed = ref('')
const media = computed(() => mediaResponse.value?.data || [])
async function save() {
	saving.value = true; message.value = ''; failed.value = ''
	try {
		form.home_content = JSON.parse(homeContentText.value)
		form.contact_form_labels = JSON.parse(contactLabelsText.value)
		const id = form.id || data.value?.data?.[0]?.id
		if (id) await $fetch(`/api/admin/settings/${id}`, { method: 'PATCH', body: form })
		else await $fetch('/api/admin/settings', { method: 'POST', body: form })
		message.value = 'Settings saved.'
		await refresh()
	} catch (requestError: any) { failed.value = requestError?.data?.errors ? Object.values(requestError.data.errors).flat().join(' ') : requestError?.data?.message || 'Unable to save settings.' }
	finally { saving.value = false }
}
</script>

<template>
	<section class="space-y-6">
		<div><p class="text-xs font-semibold uppercase tracking-[.16em] text-emerald-800">Global content</p><h2 class="mt-2 text-2xl font-semibold">Site settings</h2><p class="mt-1 text-sm text-slate-500">These values are stored in Laravel and consumed by the public site.</p></div>
		<Alert v-if="error" variant="error" :message="(error as any).data?.message || 'Unable to load settings.'" />
		<form v-else class="space-y-6" @submit.prevent="save">
			<Alert v-if="message" variant="success" :message="message" /><Alert v-if="failed" variant="error" :message="failed" />
			<section class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-7">
				<h3 class="sm:col-span-2 text-sm font-semibold uppercase tracking-wide text-slate-400">Brand and contact</h3>
				<FormField label="Public site name"><input v-model="form.site_name" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Logo"><select v-model="form.logo" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"><option value="">No logo selected</option><option v-for="asset in media" :key="asset.id" :value="asset.url">{{ asset.alt || asset.path }}</option></select></FormField>
				<FormField label="Address"><input v-model="form.address" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Phone"><input v-model="form.phone" type="tel" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Email"><input v-model="form.email" type="email" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="WhatsApp number"><input v-model="form.whatsapp_number" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="WhatsApp URL"><input v-model="form.whatsapp_url" type="url" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Homepage background image"><select v-model="form.home_background_image" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"><option value="">No image selected</option><option v-for="asset in media" :key="asset.id" :value="asset.url">{{ asset.alt || asset.path }}</option></select></FormField>
				<FormField label="Contact section heading"><input v-model="form.contact_heading" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Map embed URL"><input v-model="form.map_url" type="url" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
			</section>
			<section class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-7">
				<h3 class="sm:col-span-2 text-sm font-semibold uppercase tracking-wide text-slate-400">Public action labels and destinations</h3>
				<FormField label="Navigation CTA label"><input v-model="form.nav_cta_label" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Navigation CTA destination"><input v-model="form.nav_cta_url" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Homepage hero CTA label"><input v-model="form.home_hero_cta_label" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Homepage hero CTA destination"><input v-model="form.home_hero_cta_url" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Homepage final CTA label"><input v-model="form.home_footer_cta_label" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Homepage final CTA destination"><input v-model="form.home_footer_cta_url" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="WhatsApp action label"><input v-model="form.whatsapp_label" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
			</section>
			<section class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2 sm:p-7">
				<h3 class="sm:col-span-2 text-sm font-semibold uppercase tracking-wide text-slate-400">Footer and social links</h3>
				<FormField label="Footer tagline"><textarea v-model="form.footer_tagline" rows="3" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm" /></FormField>
				<FormField label="Copyright line"><input v-model="form.footer_copyright" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Instagram URL"><input v-model="form.instagram_url" type="url" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="Facebook URL"><input v-model="form.facebook_url" type="url" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
				<FormField label="LinkedIn URL"><input v-model="form.linkedin_url" type="url" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm"></FormField>
			</section>
			<section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7"><h3 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Dynamic homepage sections</h3><p class="mt-2 text-xs text-slate-500">Content stays database-backed; valid JSON is required.</p><textarea v-model="homeContentText" rows="16" class="mt-4 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-xs leading-5" /></section>
			<section class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-7"><h3 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Contact form copy</h3><p class="mt-2 text-xs text-slate-500">The public contact form reads labels from this database-backed object.</p><textarea v-model="contactLabelsText" rows="9" class="mt-4 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-mono text-xs leading-5" /></section>
			<div class="flex justify-end"><button class="rounded-xl bg-emerald-950 px-6 py-3 text-sm font-semibold text-white disabled:opacity-50" :disabled="saving">{{ saving ? 'Saving…' : 'Save settings' }}</button></div>
		</form>
	</section>
</template>