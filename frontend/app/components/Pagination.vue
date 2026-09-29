<script setup lang="ts">
defineProps<{ currentPage: number; lastPage: number }>()
defineEmits<{ change: [page: number] }>()
</script>
<template><nav class="flex items-center justify-between text-sm text-stone-500"><span>Page {{ currentPage }} of {{ lastPage }}</span><div class="flex gap-2"><button class="rounded-full border border-stone-200 px-4 py-2 hover:bg-stone-50 disabled:opacity-40" :disabled="currentPage <= 1" @click="$emit('change', currentPage - 1)">Previous</button><button class="rounded-full border border-stone-200 px-4 py-2 hover:bg-stone-50 disabled:opacity-40" :disabled="currentPage >= lastPage" @click="$emit('change', currentPage + 1)">Next</button></div></nav></template>
