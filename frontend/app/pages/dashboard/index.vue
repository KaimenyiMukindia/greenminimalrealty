<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: ['auth'] })
import { authErrorMessage } from '~/services/auth'
const message = ref('')
onMounted(async () => {
  try {
    const result = await $fetch<{ data: { dashboard_path: string } }>('/api/auth/dashboard')
    await navigateTo(result.data.dashboard_path, { replace: true })
  } catch (error: unknown) { message.value = authErrorMessage(error, 'Unable to open your dashboard.') }
})
</script>
<template><Alert v-if="message" variant="error" :message="message" /><LoadingSkeleton v-else /></template>
