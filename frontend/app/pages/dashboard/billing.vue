<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: ['auth'] })
</script>

<template>
  <section class="mx-auto max-w-3xl rounded-3xl border border-slate-200 bg-white px-6 py-14 text-center sm:px-12">
    <div class="mx-auto grid size-14 place-items-center rounded-2xl bg-emerald-50 text-2xl text-emerald-900">◇</div>
    <p class="mt-6 text-xs font-semibold uppercase tracking-[.16em] text-emerald-800">Workspace</p>
    <h2 class="mt-2 text-2xl font-semibold">Billing</h2>
    <p class="mx-auto mt-3 max-w-lg text-sm leading-6 text-slate-500">Billing management is not enabled yet. This section is reserved for future subscription and invoice tools.</p>
    <span class="mt-6 inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-500">Coming later</span>
  </section>
</template>
