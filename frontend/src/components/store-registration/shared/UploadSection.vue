<template>
  <div class="border border-gray-200 rounded-lg p-4">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h4 class="text-lg font-medium text-gray-800">
          {{ title }}
          <span v-if="required" class="text-red-500">*</span>
        </h4>
        <p class="text-sm text-gray-600 mt-1">
          {{ description }}
        </p>
      </div>
      <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
        Max 5MB
      </span>
    </div>
    
    <div
      @click="$refs.fileInput.click()"
      class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 transition"
      :class="{ 'border-green-500 bg-green-50': file }"
    >
      <slot :file="file">
        <div v-if="!file">
          <i class="pi pi-cloud-upload text-4xl text-gray-400 mb-3"></i>
          <p class="text-sm text-gray-600">
            <span class="font-medium text-blue-600">Click to upload</span> or drag and drop
          </p>
          <p class="text-xs text-gray-500 mt-1">
            {{ acceptText || 'PNG, JPG, PDF up to 5MB' }}
          </p>
        </div>
        <div v-else class="flex items-center justify-center space-x-4">
          <div class="bg-blue-100 p-3 rounded-lg">
            <i class="pi pi-file text-2xl text-blue-600"></i>
          </div>
          <div class="text-left">
            <p class="font-medium text-gray-800">{{ file.name }}</p>
            <p class="text-sm text-gray-600">{{ formatFileSize(file.size) }}</p>
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
        class="text-red-500 hover:text-red-700 text-sm"
      >
        <i class="pi pi-times mr-1"></i>
        Remove
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import {computed} from 'vue'

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

const emit = defineEmits<{
  upload: [file: File]
  remove: []
}>()

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
    return 'PNG, JPG, PDF up to 5MB'
  }
  return 'PNG, JPG up to 5MB'
})
</script>