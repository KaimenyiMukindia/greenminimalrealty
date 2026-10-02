<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: ['auth'] })
await navigateTo('/dashboard/properties', { replace: true })
</script>
<template><LoadingSkeleton /></template>
