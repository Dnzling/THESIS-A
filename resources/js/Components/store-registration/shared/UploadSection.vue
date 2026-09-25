<template>
  <div class="rounded-2xl border border-orange-100 bg-white p-4 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h4 class="text-sm font-semibold text-slate-900">
          {{ title }}
          <span v-if="required" class="text-red-500">*</span>
        </h4>
        <p class="mt-1 text-xs leading-5 text-slate-500">
          {{ description }}
        </p>
      </div>
      <span class="rounded-full bg-orange-100 px-2 py-1 text-xs font-medium text-orange-700">
        Max {{ maxMb }}MB
      </span>
    </div>
    
    <div
      @click="$refs.fileInput.click()"
      class="cursor-pointer rounded-2xl border-2 border-dashed border-orange-200 bg-orange-50/30 p-6 text-center transition hover:border-orange-400 hover:bg-orange-50"
      :class="{ 'border-emerald-400 bg-emerald-50/70': file }"
    >
      <slot :file="file">
        <div v-if="!file">
          <i class="pi pi-cloud-upload mb-3 text-3xl text-orange-400"></i>
          <p class="text-sm text-slate-600">
            <span class="font-semibold text-orange-600">Click to upload</span> or drag and drop
          </p>
          <p class="text-xs text-gray-500 mt-1">
            {{ acceptText || 'PNG or JPG up to 5MB' }}
          </p>
        </div>
        <div v-else class="flex items-center justify-center space-x-4">
          <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-orange-100 bg-white">
            <img v-if="previewUrl && file.type.startsWith('image/')" :src="previewUrl" :alt="title" class="h-full w-full object-cover" />
            <iframe v-else-if="previewUrl && file.type === 'application/pdf'" :src="`${previewUrl}#toolbar=0&navpanes=0`" :title="title" tabindex="-1" class="pointer-events-none h-full w-full border-0" />
          </div>
          <div class="text-left">
            <p class="font-medium text-slate-800">{{ file.name }}</p>
            <p class="text-sm text-slate-500">{{ formatFileSize(file.size) }}</p>
          </div>
        </div>
      </slot>
    </div>
    
    <input
      ref="fileInput"
      type="file"
      :accept="accept"
      class="hidden"
      @change="handleFileChange"
    />
    
    <div v-if="file" class="flex justify-end mt-3">
      <button
        type="button"
        @click.stop="$emit('remove')"
        class="text-sm font-medium text-red-500 hover:text-red-700"
      >
        <i class="pi pi-times mr-1"></i>
        Remove
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import {computed, onBeforeUnmount, ref, watch} from 'vue'

interface Props {
  title: string
  description: string
  file: File | null
  accept?: string
  required?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  accept: '.jpg,.jpeg,.png',
  required: false
})
const previewUrl = ref('')
watch(() => props.file, (file) => {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
  previewUrl.value = file ? URL.createObjectURL(file) : ''
}, { immediate: true })
onBeforeUnmount(() => { if (previewUrl.value) URL.revokeObjectURL(previewUrl.value) })

const emit = defineEmits<{
  upload: [file: File]
  remove: []
}>()
const maxMb = computed(() => props.accept === '.pdf' ? 10 : 5)

const handleFileChange = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files && input.files[0]) {
    emit('upload', input.files[0])
  }
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const acceptText = computed(() => {
  if (props.accept.includes('.pdf')) {
    return props.accept === '.pdf' ? 'PDF up to 10MB' : 'PNG, JPG, PDF up to 5MB'
  }
  return 'PNG, JPG up to 5MB'
})
</script>
