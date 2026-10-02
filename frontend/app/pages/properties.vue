<script setup lang="ts">
definePageMeta({ layout: 'public' })
const { data: meta } = await useAsyncData('public-meta-properties', () => $fetch<any>('/api/public/meta?route=/properties'))
const filter = await usePropertyFilter()
useSeoMeta({ title: () => meta.value?.data.title, description: () => meta.value?.data.description })
</script>
<template>
	<div>
		<PublicHero v-if="meta?.data" :meta="meta.data" />
		<PublicContentBlocks :blocks="meta?.data?.blocks" />
		<section class="mx-auto max-w-7xl px-5 py-12 sm:px-8">
			<PropertyFilter
				v-model="filter.input.value"
				:chips="filter.chips.value"
				@add="filter.addChip"
				@remove="filter.removeChip"
				@clear="filter.clearFilters"
			/>
			<Alert v-if="filter.error.value" class="mt-5" variant="error" :message="(filter.error.value as any).data?.message || 'Unable to search properties right now.'" />
			<div v-if="filter.properties.value.length" class="mt-10 grid gap-7 md:grid-cols-2 lg:grid-cols-3">
				<PropertyCard v-for="property in filter.properties.value" :key="property.id" :property="property" />
			</div>
			<div v-else-if="!filter.pending.value" class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-white px-6 py-12 text-center">
				<h2 class="text-lg font-semibold text-emerald-950">No properties match these filters</h2>
				<p class="mt-2 text-sm text-stone-500">Try another search or remove a filter to see more properties.</p>
				<button v-if="filter.chips.value.length || filter.input.value" type="button" class="mt-5 rounded-full bg-emerald-900 px-5 py-2.5 text-sm font-medium text-white" @click="filter.clearFilters">Clear filters</button>
			</div>
			<nav v-if="filter.meta.value && filter.meta.value.last_page > 1" class="mt-10 flex justify-between text-sm">
				<button :disabled="filter.page.value <= 1" class="rounded-full border px-4 py-2 disabled:opacity-40" @click="filter.goToPage(filter.page.value - 1)">Previous</button>
				<span>Page {{ filter.meta.value.current_page }} of {{ filter.meta.value.last_page }}</span>
				<button :disabled="filter.page.value >= filter.meta.value.last_page" class="rounded-full border px-4 py-2 disabled:opacity-40" @click="filter.goToPage(filter.page.value + 1)">Next</button>
			</nav>
		</section>
	</div>
</template>