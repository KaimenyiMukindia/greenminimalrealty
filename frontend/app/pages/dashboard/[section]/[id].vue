<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: ['auth'] })
const route = useRoute()
</script>

<template><DashboardEntityEditor :section="String(route.params.section)" :id="String(route.params.id)" /></template>