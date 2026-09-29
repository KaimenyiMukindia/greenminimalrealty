<script setup lang="ts">
withDefaults(defineProps<{ variant?: 'success' | 'error' | 'info'; message: string }>(), { variant: 'info' })
const colors = { success: 'border-emerald-200 bg-emerald-50 text-emerald-900', error: 'border-rose-200 bg-rose-50 text-rose-800', info: 'border-sky-200 bg-sky-50 text-sky-900' }
</script>
<template><div role="status" class="rounded-2xl border px-4 py-3 text-sm leading-6" :class="colors[variant]">{{ message }}</div></template>
