<script setup lang="ts">
defineProps<{ loading?: boolean; type?: 'button' | 'submit' }>()
</script>
<template><button :type="type || 'submit'" :disabled="loading" class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-900 px-5 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-950/10 transition hover:bg-emerald-800 disabled:cursor-wait disabled:opacity-60"><span v-if="loading" class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" /><slot /></button></template>
