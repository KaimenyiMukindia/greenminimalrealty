<script setup lang="ts">
defineProps<{ meta: { hero_eyebrow: string; hero_headline: string; hero_subheadline: string } }>()
</script>
<template><section class="border-b border-stone-200 bg-stone-100/70 px-5 py-28 text-center sm:px-8"><div class="mx-auto max-w-5xl"><p class="text-sm uppercase tracking-[.2em] text-emerald-700">{{ meta.hero_eyebrow }}</p><h1 class="mt-4 text-5xl font-semibold tracking-tight text-emerald-950 sm:text-6xl">{{ meta.hero_headline }}</h1><p class="mx-auto mt-5 max-w-2xl text-lg text-stone-500">{{ meta.hero_subheadline }}</p></div></section></template>