<script setup lang="ts">
definePageMeta({ layout: false, middleware: ['guest'] })
</script>
<template><main class="grid min-h-screen place-items-center bg-[#f6f6f1] px-4 py-12"><AuthCard><template #title>Password recovery paused</template><template #subtitle>We have not configured outbound email yet, so password reset links cannot be delivered.</template><div class="space-y-5"><Alert variant="info" message="Email-based password recovery is temporarily unavailable. Your account and sign-in continue to work normally."/><NuxtLink to="/dashboard/login" class="inline-flex w-full items-center justify-center rounded-full bg-emerald-900 px-5 py-3.5 text-sm font-semibold text-white">Back to sign in</NuxtLink></div></AuthCard></main></template>
