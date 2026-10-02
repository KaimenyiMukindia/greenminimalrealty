<script setup lang="ts">
import type { PropertyFilterChip } from '~/composables/usePropertyFilter'

type Suggestion = { value: string; kind: PropertyFilterChip['kind']; category: string }
type SuggestionResponse = { data: { tags: string[]; locations: string[]; titles: string[] } }

const props = defineProps<{ modelValue: string; chips: PropertyFilterChip[] }>()
const emit = defineEmits<{
  'update:modelValue': [value: string]
  add: [chip: PropertyFilterChip]
  remove: [index: number]
  clear: []
}>()

const suggestions = ref<Suggestion[]>([])
const activeIndex = ref(-1)
const open = ref(false)
const loading = ref(false)
const inputElement = ref<HTMLInputElement | null>(null)
let debounceTimer: ReturnType<typeof setTimeout> | undefined
let blurTimer: ReturnType<typeof setTimeout> | undefined
let requestSequence = 0

watch(() => props.modelValue, (value) => {
  if (debounceTimer) clearTimeout(debounceTimer)
  const term = value.trim()
  activeIndex.value = -1
  if (term.length < 1) {
    suggestions.value = []
    open.value = false
    loading.value = false
    return
  }

  open.value = true
  suggestions.value = []
  loading.value = true
  const sequence = ++requestSequence
  debounceTimer = setTimeout(async () => {
    try {
      const response = await $fetch<SuggestionResponse>('/api/public/properties/suggestions', { query: { q: term } })
      if (sequence !== requestSequence) return
      suggestions.value = [
        ...(response.data.tags || []).map(value => ({ value, kind: 'tag' as const, category: 'Amenity' })),
        ...(response.data.locations || []).map(value => ({ value, kind: 'query' as const, category: 'Location' })),
        ...(response.data.titles || []).map(value => ({ value, kind: 'query' as const, category: 'Property' })),
      ].slice(0, 30)
    } catch {
      if (sequence === requestSequence) suggestions.value = []
    } finally {
      if (sequence === requestSequence) loading.value = false
    }
  }, 250)
})

function selectSuggestion(suggestion: Suggestion): void {
  emit('add', { kind: suggestion.kind, value: suggestion.value })
  suggestions.value = []
  open.value = false
  activeIndex.value = -1
}

function addFreeText(): void {
  const value = props.modelValue.trim()
  if (!value) return
  emit('add', { kind: 'query', value })
  suggestions.value = []
  open.value = false
  activeIndex.value = -1
}

function onKeydown(event: KeyboardEvent): void {
  if (event.key === 'ArrowDown' && open.value && suggestions.value.length) {
    event.preventDefault()
    activeIndex.value = (activeIndex.value + 1) % suggestions.value.length
  } else if (event.key === 'ArrowUp' && open.value && suggestions.value.length) {
    event.preventDefault()
    activeIndex.value = activeIndex.value <= 0 ? suggestions.value.length - 1 : activeIndex.value - 1
  } else if (event.key === 'Enter') {
    event.preventDefault()
    if (open.value && activeIndex.value >= 0 && suggestions.value[activeIndex.value]) selectSuggestion(suggestions.value[activeIndex.value])
    else addFreeText()
  } else if (event.key === 'Escape') {
    open.value = false
    activeIndex.value = -1
  } else if (event.key === 'Backspace' && !props.modelValue && props.chips.length) {
    emit('remove', props.chips.length - 1)
  }
}

function deferCloseSuggestions(): void {
  if (blurTimer) clearTimeout(blurTimer)
  blurTimer = setTimeout(() => { open.value = false }, 120)
}

onBeforeUnmount(() => {
  if (debounceTimer) clearTimeout(debounceTimer)
  if (blurTimer) clearTimeout(blurTimer)
})
</script>

<template>
  <div class="relative">
    <div class="flex min-h-14 w-full flex-wrap items-center gap-2 rounded-2xl border border-stone-200 bg-white px-4 py-2.5 shadow-sm transition focus-within:border-emerald-700 focus-within:ring-4 focus-within:ring-emerald-900/5">
      <span aria-hidden="true" class="shrink-0 text-slate-400">⌕</span>
      <span v-for="(chip, index) in chips" :key="`${chip.kind}-${chip.value}-${index}`" class="inline-flex max-w-full items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium" :class="chip.kind === 'tag' ? 'bg-emerald-50 text-emerald-900' : 'bg-slate-100 text-slate-700'">
        <span class="truncate">{{ chip.value }}</span>
        <button type="button" class="grid size-4 shrink-0 place-items-center rounded-full text-current/70 hover:bg-black/5" :aria-label="`Remove ${chip.value} filter`" @click="emit('remove', index)">×</button>
      </span>
      <input
        ref="inputElement"
        :value="modelValue"
        type="search"
        role="combobox"
        aria-label="Search properties by keyword, location, beds, baths, price, or amenity"
        aria-autocomplete="list"
        :aria-expanded="open"
        :aria-activedescendant="activeIndex >= 0 ? `property-suggestion-${activeIndex}` : undefined"
        autocomplete="off"
        class="min-w-[8rem] flex-1 border-0 bg-transparent py-1 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0"
        @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
        @keydown="onKeydown"
        @focus="modelValue.trim() && (open = true)"
        @blur="deferCloseSuggestions"
      >
      <button v-if="chips.length" type="button" class="shrink-0 text-xs font-medium text-slate-400 transition hover:text-slate-700" @click="emit('clear')">Clear</button>
    </div>

    <ul v-if="open && (loading || suggestions.length || modelValue.trim())" role="listbox" class="absolute inset-x-0 top-full z-20 mt-2 max-h-80 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
      <li v-if="loading" class="px-3 py-2 text-sm text-slate-500" role="status">Searching property data…</li>
      <li v-for="(suggestion, index) in suggestions" :id="`property-suggestion-${index}`" :key="`${suggestion.category}-${suggestion.value}`" role="option" :aria-selected="activeIndex === index">
        <button type="button" class="flex w-full items-center justify-between gap-4 rounded-xl px-3 py-2.5 text-left text-sm transition" :class="activeIndex === index ? 'bg-emerald-50 text-emerald-950' : 'hover:bg-slate-50'" @mousedown.prevent="selectSuggestion(suggestion)">
          <span class="truncate">{{ suggestion.value }}</span><span class="shrink-0 text-[10px] font-medium uppercase tracking-wide text-slate-400">{{ suggestion.category }}</span>
        </button>
      </li>
      <li v-if="!loading && !suggestions.length" class="px-3 py-2 text-sm text-slate-500">No matching suggestions. Press Enter to search this text.</li>
    </ul>
  </div>
</template>
