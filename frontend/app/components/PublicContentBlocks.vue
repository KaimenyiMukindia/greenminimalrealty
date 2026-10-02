<script setup lang="ts">
defineProps<{ blocks?: Array<{ id: number; key: string; content?: string | null }> }>()
</script>

<template>
  <section v-if="blocks?.length" class="mx-auto max-w-7xl space-y-8 px-5 py-12 sm:px-8">
    <article v-for="block in blocks" :key="block.id" class="content-block mx-auto w-full max-w-4xl text-slate-700" v-html="block.content || ''" />
  </section>
</template>

<style scoped>
.content-block :deep(p) { margin: .75rem 0; line-height: 1.8; }
.content-block :deep(h2) { margin: 1.75rem 0 .75rem; color: #183a2d; font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 600; line-height: 1.2; }
.content-block :deep(h3) { margin: 1.25rem 0 .5rem; color: #183a2d; font-size: 1.35rem; font-weight: 600; }
.content-block :deep(ul) { margin: .75rem 0; padding-left: 1.5rem; list-style: disc; }
.content-block :deep(ol) { margin: .75rem 0; padding-left: 1.5rem; list-style: decimal; }
.content-block :deep(li) { margin: .25rem 0; }
.content-block :deep(blockquote) { margin: 1rem 0; border-left: 3px solid #a7f3d0; padding-left: 1rem; color: #64748b; }
.content-block :deep(a) { color: #047857; text-decoration: underline; text-underline-offset: 3px; }
.content-block :deep(strong) { color: #183a2d; font-weight: 600; }
</style>
