<script setup lang="ts">
defineProps<{ title?: string; description?: string }>()
</script>
<template><div class="rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-14 text-center"><div class="mx-auto grid size-12 place-items-center rounded-2xl bg-emerald-50 text-xl text-emerald-800">⌂</div><h3 class="mt-4 font-semibold text-emerald-950">{{ title || 'Nothing here yet' }}</h3><p class="mt-2 text-sm text-stone-500">{{ description || 'When there is something to show, it will appear here.' }}</p><slot /></div></template>
