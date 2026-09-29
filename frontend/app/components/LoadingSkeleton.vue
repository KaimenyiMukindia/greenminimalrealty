<script setup lang="ts">
defineProps<{ rows?: number }>()
</script>
<template><div class="space-y-3" aria-label="Loading" aria-busy="true"><div v-for="row in rows || 3" :key="row" class="h-14 animate-pulse rounded-2xl bg-stone-200/70" /></div></template>
