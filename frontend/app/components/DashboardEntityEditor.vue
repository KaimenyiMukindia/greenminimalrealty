<script setup lang="ts">
type Field = { key: string; label: string; kind?: 'text' | 'textarea' | 'number' | 'toggle' | 'rich' | 'media'; required?: boolean; full?: boolean }
const props = defineProps<{ section: string; id?: string }>()
const route = useRoute()
const router = useRouter()
const saving = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const galleryIds = ref<number[]>([])
const tagsText = ref('')
const itemText = ref('')
const featureText = ref('')
const occupancyText = ref('[]')
const media = ref<any[]>([])
const galleryFile = ref<File | null>(null)
const galleryAlt = ref('')
const uploadingGallery = ref(false)
const loaded = ref(false)
const form = reactive<Record<string, any>>({})

const definitions: Record<string, Field[]> = {
  properties: [
    { key: 'title', label: 'Property title', required: true }, { key: 'location', label: 'Location', required: true },
    { key: 'price_label', label: 'Price label', required: true }, { key: 'price_value', label: 'Numeric price', kind: 'number' },
    { key: 'beds', label: 'Bedrooms', kind: 'number' }, { key: 'baths', label: 'Bathrooms', kind: 'number' },
    { key: 'description', label: 'Description', kind: 'rich', full: true }, { key: 'hero_image', label: 'Cover image', kind: 'media', full: true },
    { key: 'is_featured', label: 'Featured property', kind: 'toggle' }, { key: 'is_published', label: 'Published', kind: 'toggle' }, { key: 'order', label: 'Display order', kind: 'number' },
  ],
  services: [
    { key: 'title', label: 'Service title', required: true }, { key: 'short_description', label: 'Short description', kind: 'textarea', required: true, full: true },
    { key: 'icon', label: 'Icon identifier' }, { key: 'hero_image', label: 'Image', kind: 'media' },
    { key: 'published', label: 'Published', kind: 'toggle' }, { key: 'order', label: 'Display order', kind: 'number' },
  ],
  testimonials: [
    { key: 'quote', label: 'Testimonial', kind: 'textarea', required: true, full: true }, { key: 'author_name', label: 'Author name', required: true },
    { key: 'author_role', label: 'Author role' }, { key: 'published', label: 'Published', kind: 'toggle' }, { key: 'order', label: 'Display order', kind: 'number' },
  ],
  'page-meta': [
    { key: 'route', label: 'Public route (read-only)' }, { key: 'title', label: 'Page title', required: true },
    { key: 'meta_title', label: 'SEO title' }, { key: 'meta_description', label: 'SEO description', kind: 'textarea', full: true },
    { key: 'hero_eyebrow', label: 'Hero eyebrow' }, { key: 'hero_headline', label: 'Hero headline' },
    { key: 'hero_subheadline', label: 'Hero supporting copy', kind: 'textarea', full: true }, { key: 'hero_image', label: 'Hero image', kind: 'media', full: true },
  ],
  'airbnb-listings': [
    { key: 'title', label: 'Listing title', required: true }, { key: 'location', label: 'Location' },
    { key: 'description', label: 'Description', kind: 'rich', full: true }, { key: 'hero_image', label: 'Cover image', kind: 'media', full: true },
    { key: 'published', label: 'Published', kind: 'toggle' }, { key: 'order', label: 'Display order', kind: 'number' },
  ],
  stats: [
    { key: 'value', label: 'Value', required: true }, { key: 'label', label: 'Label', required: true },
    { key: 'section', label: 'Section', required: true }, { key: 'order', label: 'Display order', kind: 'number' },
  ],
  values: [
    { key: 'title', label: 'Title', required: true }, { key: 'icon', label: 'Icon identifier' },
    { key: 'description', label: 'Description', kind: 'textarea', required: true, full: true }, { key: 'published', label: 'Published', kind: 'toggle' }, { key: 'order', label: 'Display order', kind: 'number' },
  ],
  pillars: [
    { key: 'title', label: 'Title', required: true }, { key: 'icon', label: 'Icon identifier' },
    { key: 'description', label: 'Description', kind: 'textarea', required: true, full: true }, { key: 'published', label: 'Published', kind: 'toggle' }, { key: 'order', label: 'Display order', kind: 'number' },
  ],
}
const fields = computed(() => definitions[props.section] || definitions.values)
const title = computed(() => props.section.replaceAll('-', ' ').replace(/\b\w/g, letter => letter.toUpperCase()))
const singularTitle = computed(() => ({ properties: 'Property', services: 'Service', testimonials: 'Testimonial', 'page-meta': 'Page', 'airbnb-listings': 'Airbnb listing', stats: 'Statistic', values: 'Value', pillars: 'Pillar' }[props.section] || title.value))
const isPage = computed(() => props.section === 'page-meta')
const isProperty = computed(() => props.section === 'properties')
const isService = computed(() => props.section === 'services')
const isAirbnb = computed(() => props.section === 'airbnb-listings')
const readonlyField = (field: Field) => field.key === 'route'

async function loadMedia() {
  try {
    const result = await $fetch<{ data: any[] }>('/api/admin/media?per_page=100')
    media.value = result.data || []
  } catch { media.value = [] }
}

async function load() {
  loaded.value = false
  errorMessage.value = ''
  Object.keys(form).forEach(key => delete form[key])
  if (props.id) {
    try {
      const result = await $fetch<{ data: Record<string, any> }>(`/api/admin/${props.section}/${props.id}`)
      Object.assign(form, result.data)
      if (isProperty.value) {
        tagsText.value = (result.data.tags || []).map((tag: any) => tag.label).join('\n')
        galleryIds.value = (result.data.images || []).map((image: any) => Number(image.media_id))
      }
      if (isService.value) itemText.value = (result.data.items || []).map((item: any) => item.text).join('\n')
      if (isPage.value) form.blocks = result.data.blocks || []
      if (isAirbnb.value) {
        featureText.value = (result.data.features || []).join('\n')
        occupancyText.value = JSON.stringify(result.data.occupancy_stats || [], null, 2)
      }
    } catch (error: any) {
      errorMessage.value = error?.data?.message || `Unable to load ${title.value.toLowerCase()}.`
    }
  } else {
    for (const field of fields.value) {
      if (field.kind === 'toggle') form[field.key] = field.key === 'published' || field.key === 'is_published'
      else if (field.kind === 'number') form[field.key] = field.key === 'order' ? 0 : null
      else form[field.key] = ''
    }
    form.items = []
    form.tags = []
    form.media_ids = []
    galleryIds.value = []
    form.blocks = []
    form.features = []
    form.occupancy_stats = []
  }
  loaded.value = true
}

function addBlock() {
  form.blocks ||= []
  form.blocks.push({ key: `section-${form.blocks.length + 1}`, type: 'rich_text', content: '', order: form.blocks.length, published: true })
}
function removeBlock(index: number) { form.blocks.splice(index, 1) }
function moveBlock(index: number, delta: number) {
  const next = index + delta
  if (next < 0 || next >= form.blocks.length) return
  ;[form.blocks[index], form.blocks[next]] = [form.blocks[next], form.blocks[index]]
  form.blocks.forEach((block: any, order: number) => { block.order = order })
}
function toggleGalleryMedia(id: number) {
  galleryIds.value = galleryIds.value.includes(id) ? galleryIds.value.filter(value => value !== id) : [...galleryIds.value, id]
}
function moveGalleryMedia(index: number, delta: number) {
  const target = index + delta
  if (target < 0 || target >= galleryIds.value.length) return
  ;[galleryIds.value[index], galleryIds.value[target]] = [galleryIds.value[target], galleryIds.value[index]]
}
function chooseGalleryFile(event: Event) {
  galleryFile.value = (event.target as HTMLInputElement).files?.[0] || null
}
async function uploadGalleryImage() {
  if (!galleryFile.value) return
  uploadingGallery.value = true
  errorMessage.value = ''
  const body = new FormData()
  body.append('file', galleryFile.value)
  body.append('alt', galleryAlt.value)
  try {
    const result = await $fetch<{ data: any }>('/api/admin/media/upload', { method: 'POST', body })
    media.value = [result.data, ...media.value]
    galleryIds.value = [...galleryIds.value, result.data.id]
    galleryFile.value = null
    galleryAlt.value = ''
  } catch (error: any) {
    errorMessage.value = error?.data?.message || 'Unable to upload this gallery image.'
  } finally {
    uploadingGallery.value = false
  }
}

async function save() {
  saving.value = true
  errorMessage.value = ''
  successMessage.value = ''
  try {
    const payload = { ...form }
    delete payload.id
    delete payload.slug
    if (isPage.value) delete payload.route
    if (isProperty.value) {
      payload.tags = tagsText.value.split('\n').map((tag: string) => tag.trim()).filter(Boolean)
      payload.media_ids = galleryIds.value
    }
    if (isService.value) payload.items = itemText.value.split('\n').map((text: string, order: number) => ({ text: text.trim(), order })).filter((item: any) => item.text)
    if (isAirbnb.value) {
      payload.features = featureText.value.split('\n').map((feature: string) => feature.trim()).filter(Boolean)
      payload.occupancy_stats = JSON.parse(occupancyText.value || '[]')
    }
    if (isPage.value) payload.blocks = (form.blocks || []).map((block: any, order: number) => ({ ...block, order }))

    if (props.id) await $fetch(`/api/admin/${props.section}/${props.id}`, { method: 'PATCH', body: payload })
    else await $fetch(`/api/admin/${props.section}`, { method: 'POST', body: payload })
    successMessage.value = 'Saved successfully.'
    await router.push(`/dashboard/${props.section}`)
  } catch (error: any) {
    const validation = error?.data?.errors
    errorMessage.value = validation ? Object.values(validation).flat().join(' ') : error?.data?.message || 'Unable to save these changes.'
  } finally {
    saving.value = false
  }
}

onMounted(() => { void Promise.all([load(), loadMedia()]) })
watch(() => [props.id, props.section], () => load())
</script>

<template>
  <section>
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
      <div>
        <NuxtLink :to="`/dashboard/${section}`" class="text-xs font-semibold text-slate-500 hover:text-emerald-900">← Back to {{ title }}</NuxtLink>
        <h2 class="mt-2 text-2xl font-semibold tracking-tight">{{ id ? 'Edit' : 'Create' }} {{ singularTitle }}</h2>
        <p class="mt-1 text-sm text-slate-500">All related fields are saved together.</p>
      </div>
    </div>
    <Alert v-if="errorMessage" class="mb-5" variant="error" :message="errorMessage" />
    <LoadingSkeleton v-if="!loaded" />
    <form v-else class="space-y-6" @submit.prevent="save">
      <section class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
        <h3 class="mb-5 text-sm font-semibold uppercase tracking-wide text-slate-400">Details</h3>
        <div class="grid gap-5 md:grid-cols-2">
          <div v-for="field in fields" :key="field.key" class="space-y-2" :class="field.full ? 'md:col-span-2' : ''">
            <label v-if="field.kind !== 'toggle' && field.kind !== 'rich'" class="block text-sm font-medium text-slate-700" :for="field.key">{{ field.label }}<span v-if="field.required" class="ml-1 text-rose-600">*</span></label>
            <input v-if="!field.kind || field.kind === 'text'" :id="field.key" v-model="form[field.key]" :readonly="readonlyField(field)" :required="field.required" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none transition focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-900/5 read-only:text-slate-400" :class="readonlyField(field) ? 'cursor-not-allowed' : ''">
            <input v-else-if="field.kind === 'number'" :id="field.key" v-model.number="form[field.key]" type="number" min="0" :required="field.required" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-emerald-700 focus:bg-white">
            <textarea v-else-if="field.kind === 'textarea'" :id="field.key" v-model="form[field.key]" rows="4" :required="field.required" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm outline-none focus:border-emerald-700 focus:bg-white" />
            <RichTextEditor v-else-if="field.kind === 'rich'" v-model="form[field.key]" :label="field.label" />
            <select v-else-if="field.kind === 'media'" :id="field.key" v-model="form[field.key]" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm">
              <option value="">No image selected</option>
              <option v-for="asset in media" :key="asset.id" :value="asset.url">{{ asset.alt || asset.path }}</option>
            </select>
            <label v-else-if="field.kind === 'toggle'" class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 text-sm font-medium text-slate-700"><input v-model="form[field.key]" type="checkbox" class="size-4 accent-emerald-800">{{ field.label }}</label>
          </div>
        </div>
      </section>

      <section v-if="isProperty" class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
          <h3 class="text-base font-semibold">Property tags</h3><p class="mt-1 text-xs text-slate-500">One tag per line.</p>
          <textarea v-model="tagsText" rows="7" class="mt-4 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm" />
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
          <h3 class="text-base font-semibold">Gallery</h3><p class="mt-1 text-xs text-slate-500">Select and order images or upload from this editor. Gallery changes are committed with the property.</p>
          <div class="mt-4 flex flex-wrap items-center gap-2 rounded-xl bg-slate-50 p-3"><input type="file" accept="image/jpeg,image/png,image/webp,image/gif" @change="chooseGalleryFile"><input v-model="galleryAlt" aria-label="Uploaded image alt text" placeholder="Alt text" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs"><button type="button" class="rounded-lg bg-emerald-950 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" :disabled="!galleryFile || uploadingGallery" @click="uploadGalleryImage">{{ uploadingGallery ? 'Uploading…' : 'Upload to gallery' }}</button></div>
          <div class="mt-4 grid max-h-64 grid-cols-3 gap-2 overflow-auto sm:grid-cols-4">
            <button v-for="asset in media" :key="asset.id" type="button" class="relative overflow-hidden rounded-xl border-2 text-left" :class="galleryIds.includes(asset.id) ? 'border-emerald-700' : 'border-transparent'" @click="toggleGalleryMedia(asset.id)">
              <img :src="asset.url" :alt="asset.alt || ''" class="aspect-square w-full object-cover"><span class="block truncate px-1 py-1 text-[10px]">{{ asset.alt || asset.id }}</span><span v-if="galleryIds.includes(asset.id)" class="absolute right-1 top-1 grid size-5 place-items-center rounded-full bg-emerald-900 text-[10px] text-white">{{ galleryIds.indexOf(asset.id) + 1 }}</span>
            </button>
          </div>
          <div v-if="galleryIds.length" class="mt-3 space-y-1"><div v-for="(id, index) in galleryIds" :key="id" class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-xs"><span>{{ index + 1 }}. {{ media.find(asset => asset.id === id)?.alt || `Image ${id}` }}</span><span class="flex gap-2"><button type="button" :disabled="index === 0" @click="moveGalleryMedia(Number(index), -1)">↑</button><button type="button" :disabled="Number(index) === galleryIds.length - 1" @click="moveGalleryMedia(Number(index), 1)">↓</button><button type="button" class="text-rose-700" @click="toggleGalleryMedia(id)">Remove</button></span></div></div>
        </div>
      </section>

      <section v-if="isService" class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
        <h3 class="text-base font-semibold">Included service items</h3><p class="mt-1 text-xs text-slate-500">One item per line. Drag order is top to bottom.</p>
        <textarea v-model="itemText" rows="7" class="mt-4 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm" />
      </section>

      <section v-if="isAirbnb" class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7"><h3 class="text-base font-semibold">Features</h3><p class="mt-1 text-xs text-slate-500">One feature per line.</p><textarea v-model="featureText" rows="8" class="mt-4 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm" /></div>
        <div class="rounded-3xl border border-slate-200 bg-white p-5 sm:p-7"><h3 class="text-base font-semibold">Occupancy statistics</h3><p class="mt-1 text-xs text-slate-500">JSON array of label/value pairs.</p><textarea v-model="occupancyText" rows="8" class="mt-4 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 font-mono text-xs" /></div>
      </section>

      <section v-if="isPage" class="space-y-4 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 class="text-base font-semibold">Page content blocks</h3><p class="mt-1 text-xs text-slate-500">Each block is stored on this page. Rich-text content is persisted as HTML.</p></div><button type="button" class="rounded-xl bg-emerald-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800" @click="addBlock">Add section</button></div>
        <EmptyState v-if="!form.blocks?.length" title="No content blocks yet" description="Add a section to start building this page." />
        <article v-for="(block, index) in form.blocks" :key="block.id || `${block.key}-${index}`" class="space-y-4 rounded-2xl border border-slate-200 p-4">
          <div class="flex items-center justify-between"><p class="text-sm font-semibold">Section {{ Number(index) + 1 }}</p><div class="flex gap-2"><button type="button" class="rounded-lg border px-2 py-1 text-xs" :disabled="Number(index) === 0" @click="moveBlock(Number(index), -1)">↑</button><button type="button" class="rounded-lg border px-2 py-1 text-xs" :disabled="Number(index) === form.blocks.length - 1" @click="moveBlock(Number(index), 1)">↓</button><button type="button" class="rounded-lg px-2 py-1 text-xs text-rose-700" @click="removeBlock(Number(index))">Remove</button></div></div>
          <div class="grid gap-4 md:grid-cols-2"><FormField label="Section key"><input v-model="block.key" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"></FormField><FormField label="Content type"><select v-model="block.type" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm"><option value="rich_text">Rich text</option><option value="text">Plain text</option><option value="html">HTML</option></select></FormField></div>
          <RichTextEditor v-model="block.content" label="Section content" />
          <label class="flex items-center gap-2 text-sm text-slate-600"><input v-model="block.published" type="checkbox" class="accent-emerald-800"> Published on public page</label>
        </article>
      </section>
      <div class="sticky bottom-3 flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur"><p class="hidden text-sm text-emerald-800 sm:block">{{ successMessage }}</p><button type="submit" class="ml-auto rounded-xl bg-emerald-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-50" :disabled="saving">{{ saving ? 'Saving…' : 'Save changes' }}</button></div>
    </form>
  </section>
</template>
