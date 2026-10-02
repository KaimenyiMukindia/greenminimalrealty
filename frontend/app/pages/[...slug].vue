<script setup lang="ts">
definePageMeta({ layout: 'public' })
const route = useRoute()
const { data, error } = await useAsyncData(`public-page-${route.path}`, () => $fetch<{ data: any }>('/api/public/meta', { query: { route: route.path } }))
if (error.value) throw createError({ statusCode: 404, statusMessage: 'Page not found' })
useSeoMeta({ title: () => data.value?.data.title, description: () => data.value?.data.description })
</script>

<template>
  <main v-if="data?.data">
    <PublicHero :meta="data.data" />
    <PublicContentBlocks :blocks="data.data.blocks" />
  </main>
</template>
