<script setup lang="ts">
defineProps<{ columns: string[] }>()
</script>
<template><div class="overflow-hidden rounded-3xl border border-stone-200 bg-white"><table class="w-full text-left text-sm"><thead class="bg-stone-50 text-xs uppercase tracking-wider text-stone-500"><tr><th v-for="column in columns" :key="column" class="px-5 py-4 font-semibold">{{ column }}</th></tr></thead><tbody><slot /></tbody></table></div></template>
