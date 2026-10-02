export type PropertyFilterChip = {
  kind: 'tag' | 'query'
  value: string
}

type PropertyResult = {
  data: any[]
  meta: { current_page: number; last_page: number; per_page: number; total: number }
}

type FilterState = { q: string; tags: string[]; page: number }

function routeTags(value: unknown): string[] {
  if (Array.isArray(value)) return value.map(String).filter(Boolean)
  return typeof value === 'string' && value ? [value] : []
}

function routeState(route: ReturnType<typeof useRoute>): FilterState {
  const query = typeof route.query.q === 'string' ? route.query.q : ''
  const tags = routeTags(route.query['tags[]'] ?? route.query.tags)
  return { q: query, tags, page: Math.max(1, Number(route.query.page || 1)) }
}

function stateSignature(state: FilterState): string {
  return JSON.stringify({ q: state.q, tags: [...state.tags].sort(), page: state.page })
}

export async function usePropertyFilter() {
  const route = useRoute()
  const router = useRouter()
  const initial = routeState(route)
  const chips = ref<PropertyFilterChip[]>([
    ...(initial.q ? [{ kind: 'query' as const, value: initial.q }] : []),
    ...initial.tags.map(value => ({ kind: 'tag' as const, value })),
  ])
  const input = ref('')
  const page = ref(initial.page)
  const applied = shallowRef<FilterState>(initial)
  const restoring = ref(false)
  let debounceTimer: ReturnType<typeof setTimeout> | undefined
  let nextHistoryMode: 'push' | 'replace' = 'replace'

  const queryText = computed(() => chips.value.filter(chip => chip.kind === 'query').map(chip => chip.value).join(' ').trim())
  const selectedTags = computed(() => chips.value.filter(chip => chip.kind === 'tag').map(chip => chip.value))
  const liveQuery = computed(() => [queryText.value, input.value.trim()].filter(Boolean).join(' ').trim())

  function targetState(nextPage = 1): FilterState {
    return { q: liveQuery.value, tags: [...selectedTags.value], page: nextPage }
  }

  function queryObject(state: FilterState): Record<string, string | string[] | undefined> {
    return {
      q: state.q || undefined,
      'tags[]': state.tags.length ? state.tags : undefined,
      page: state.page > 1 ? String(state.page) : undefined,
    }
  }

  function scheduleSync(mode: 'push' | 'replace' = nextHistoryMode): void {
    nextHistoryMode = 'replace'
    if (debounceTimer) clearTimeout(debounceTimer)
    page.value = 1
    debounceTimer = setTimeout(() => {
      const state = targetState(1)
      applied.value = state
      const current = routeState(route)
      if (stateSignature(current) !== stateSignature(state)) {
        void router[mode]({ path: route.path, query: queryObject(state) })
      }
    }, 250)
  }

  watch([chips, input], () => {
    if (!restoring.value) scheduleSync()
  }, { deep: true })

  watch(() => route.fullPath, () => {
    const fromUrl = routeState(route)
    if (stateSignature(fromUrl) === stateSignature(applied.value)) return

    if (debounceTimer) clearTimeout(debounceTimer)
    restoring.value = true
    input.value = ''
    chips.value = [
      ...(fromUrl.q ? [{ kind: 'query' as const, value: fromUrl.q }] : []),
      ...fromUrl.tags.map(value => ({ kind: 'tag' as const, value })),
    ]
    page.value = fromUrl.page
    applied.value = fromUrl
    void nextTick(() => { restoring.value = false })
  })

  onBeforeUnmount(() => {
    if (debounceTimer) clearTimeout(debounceTimer)
  })

  const { data, pending, error, refresh } = await useAsyncData<PropertyResult>(
    'public-properties-filter',
    () => $fetch<PropertyResult>('/api/public/properties', {
      query: {
        q: applied.value.q || undefined,
        'tags[]': applied.value.tags.length ? applied.value.tags : undefined,
        page: applied.value.page,
        per_page: 12,
      },
    }),
    { watch: [applied] },
  )

  function addChip(chip: PropertyFilterChip): void {
    const exists = chips.value.some(item => item.kind === chip.kind && item.value.toLocaleLowerCase() === chip.value.toLocaleLowerCase())
    if (exists) {
      input.value = ''
      return
    }
    nextHistoryMode = 'push'
    chips.value = [...chips.value, chip]
    input.value = ''
  }

  function removeChip(index: number): void {
    nextHistoryMode = 'push'
    chips.value = chips.value.filter((_, chipIndex) => chipIndex !== index)
  }

  function clearFilters(): void {
    nextHistoryMode = 'push'
    chips.value = []
    input.value = ''
  }

  function goToPage(nextPage: number): void {
    if (nextPage < 1 || nextPage > (data.value?.meta.last_page || 1)) return
    if (debounceTimer) clearTimeout(debounceTimer)
    page.value = nextPage
    const state = targetState(nextPage)
    applied.value = state
    void router.push({ path: route.path, query: queryObject(state) })
  }

  return {
    chips,
    input,
    queryText,
    selectedTags,
    properties: computed(() => data.value?.data || []),
    meta: computed(() => data.value?.meta),
    pending,
    error,
    page,
    addChip,
    removeChip,
    clearFilters,
    goToPage,
    refresh,
  }
}
