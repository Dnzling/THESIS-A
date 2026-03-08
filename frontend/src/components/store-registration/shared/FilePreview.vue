<template>
  <div class="border border-gray-200 rounded-lg p-4">
    <div class="flex items-center space-x-4">
      <div class="shrink-0">
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
          <i class="pi pi-file text-xl text-blue-600"></i>
        </div>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-900 truncate">
          {{ label }}
        </p>
        <div v-if="file" class="mt-1">
          <p class="text-xs text-gray-500">
            {{ file.name }}
          </p>
          <p class="text-xs text-gray-400">
            {{ formatFileSize(file.size) }}
          </p>
        </div>
        <div v-else class="mt-1">
          <p class="text-xs text-gray-400 italic">
            No file uploaded
          </p>
        </div>
      </div>
      <div v-if="file" class="shrink-0">
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
          <i class="pi pi-check mr-1"></i>
          Uploaded
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  file: File | null
  label: string
}

defineProps<Props>()

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
defineExpose({ formatFileSize })
</script>