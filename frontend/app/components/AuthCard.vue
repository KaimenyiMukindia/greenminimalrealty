<template>
  <section class="w-full max-w-md rounded-[2rem] border border-stone-200/80 bg-white p-8 shadow-[0_24px_70px_-40px_rgba(19,55,40,.25)] sm:p-10">
    <div class="mb-8"><AppLogo /><p class="mt-8 text-xs font-semibold uppercase tracking-[.2em] text-emerald-700">Your workspace</p><h1 class="mt-2 text-3xl font-semibold tracking-tight text-emerald-950"><slot name="title" /></h1><p class="mt-2 text-sm leading-6 text-stone-500"><slot name="subtitle" /></p></div>
    <slot />
  </section>
</template>
