<script setup lang="ts">
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'

const props = defineProps<{ modelValue: string; label: string }>()
const emit = defineEmits<{ 'update:modelValue': [value: string] }>()
const editor = useEditor({
  content: props.modelValue || '',
  extensions: [StarterKit],
  immediatelyRender: false,
  onUpdate: ({ editor: current }) => emit('update:modelValue', current.getHTML()),
})

watch(() => props.modelValue, (value) => {
  if (editor.value && value !== editor.value.getHTML()) editor.value.commands.setContent(value || '', { emitUpdate: false })
})
</script>

<template>
  <div class="space-y-2">
    <p class="text-sm font-medium text-slate-700">{{ label }}</p>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white focus-within:border-emerald-700 focus-within:ring-4 focus-within:ring-emerald-900/5">
      <div class="flex flex-wrap gap-1 border-b border-slate-100 bg-slate-50 p-2">
        <button v-for="action in [
          { label: 'B', run: () => editor?.chain().focus().toggleBold().run(), active: 'bold' },
          { label: 'I', run: () => editor?.chain().focus().toggleItalic().run(), active: 'italic' },
          { label: 'H2', run: () => editor?.chain().focus().toggleHeading({ level: 2 }).run(), active: 'heading' },
          { label: '• List', run: () => editor?.chain().focus().toggleBulletList().run(), active: 'bulletList' },
          { label: '1. List', run: () => editor?.chain().focus().toggleOrderedList().run(), active: 'orderedList' },
          { label: 'Quote', run: () => editor?.chain().focus().toggleBlockquote().run(), active: 'blockquote' },
        ]" :key="action.label" type="button" class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-white" :class="editor?.isActive(action.active) ? 'bg-white text-emerald-900 shadow-sm' : ''" @click="action.run">{{ action.label }}</button>
      </div>
      <EditorContent :editor="editor" class="rich-text-editor min-h-44 px-4 py-3 text-sm leading-6" />
    </div>
    <p class="text-xs text-slate-400">Formatting is saved as HTML content.</p>
  </div>
</template>

<style>
.rich-text-editor .ProseMirror { min-height: 10rem; outline: none; }
.rich-text-editor .ProseMirror p { margin: .55rem 0; }
.rich-text-editor .ProseMirror h2 { margin: 1rem 0 .5rem; font-size: 1.25rem; font-weight: 600; }
.rich-text-editor .ProseMirror ul, .rich-text-editor .ProseMirror ol { margin: .5rem 0; padding-left: 1.5rem; }
.rich-text-editor .ProseMirror ul { list-style: disc; }
.rich-text-editor .ProseMirror ol { list-style: decimal; }
.rich-text-editor .ProseMirror blockquote { border-left: 3px solid #a7f3d0; margin: .75rem 0; padding-left: .75rem; color: #64748b; }
</style>
