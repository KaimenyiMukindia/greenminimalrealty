<script setup lang="ts">
defineProps<{ label: string; modelValue: string; type?: string; placeholder?: string; error?: string; autocomplete?: string }>()
defineEmits<{ 'update:modelValue': [value: string] }>()
</script>
<template>
  <label class="block space-y-2"><span class="text-sm font-medium text-stone-700">{{ label }}</span><input :value="modelValue" :type="type || 'text'" :placeholder="placeholder" :autocomplete="autocomplete" class="w-full rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-900 outline-none transition placeholder:text-stone-400 focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-900/5" @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"><span v-if="error" class="block text-xs text-rose-600">{{ error }}</span></label>
</template>
