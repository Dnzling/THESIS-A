<template>
    <div class="max-w-7xl mx-auto space-y-6 pb-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">3D Models Gallery</h2>
                <p class="text-sm text-gray-500 mt-1">All 3D models from Store 1</p>
            </div>
            <div class="flex gap-2">
                <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined @click="loadModels"
                    :loading="loading" />
                <Button label="Upload New" icon="pi pi-cloud-upload"
                    @click="router.push({ name: 'merchandising.assets.upload' })" />
            </div>
        </div>
    
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Card>
                <template #content>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total 3D Models</p>
                            <h3 class="text-3xl font-bold text-indigo-600 mt-1">{{ models.length }}</h3>
                        </div>
                        <div class="bg-indigo-100 p-4 rounded-full">
                            <i class="pi pi-cube text-indigo-600 text-3xl"></i>
                        </div>
                    </div>
                </template>
            </Card>
    
            <Card>
                <template #content>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">GLB Models</p>
                            <h3 class="text-3xl font-bold text-blue-600 mt-1">{{ glbCount }}</h3>
                        </div>
                        <div class="bg-blue-100 p-4 rounded-full">
                            <i class="pi pi-box text-blue-600 text-3xl"></i>
                        </div>
                    </div>
                </template>
            </Card>
    
            <Card>
                <template #content>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Size</p>
                            <h3 class="text-3xl font-bold text-green-600 mt-1">{{ totalSizeFormatted }}</h3>
                        </div>
                        <div class="bg-green-100 p-4 rounded-full">
                            <i class="pi pi-database text-green-600 text-3xl"></i>
                        </div>
                    </div>
                </template>
            </Card>
        </div>
    
        <!-- Loading State -->
        <div v-if="loading && models.length === 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <Skeleton v-for="i in 6" :key="i" height="400px" class="rounded-lg" />
        </div>
    
        <!-- 3D Models List -->
        <div v-else-if="models.length > 0" class="space-y-3">
            <Card>
                <template #content>
                    <div class="hidden md:grid grid-cols-12 gap-3 text-xs font-semibold text-gray-500 uppercase mb-3">
                        <div class="col-span-4">File</div>
                        <div class="col-span-3">Product</div>
                        <div class="col-span-2">Format</div>
                        <div class="col-span-2">Size</div>
                        <div class="col-span-1 text-right">Actions</div>
                    </div>

                    <div v-for="model in models" :key="model.id"
                        class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center py-3 border-t border-gray-100 first:border-t-0">
                        <div class="md:col-span-4">
                            <div class="font-semibold text-gray-900 truncate">{{ model.file_name }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                Uploaded {{ formatDate(model.created_at) }}
                            </div>
                        </div>
                        <div class="md:col-span-3 text-sm text-gray-700 truncate">
                            {{ model.product?.product_name || 'N/A' }}
                        </div>
                        <div class="md:col-span-2">
                            <div class="flex items-center gap-2">
                                <Tag :value="model.model_format?.toUpperCase()" severity="info" size="small" />
                                <Badge v-if="model.is_primary" value="Primary" severity="success" />
                            </div>
                        </div>
                        <div class="md:col-span-2 text-sm text-gray-700">
                            {{ formatFileSize(model.file_size_kb * 1024) }}
                        </div>
                        <div class="md:col-span-1 flex md:justify-end gap-2">
                            <Button label="View" icon="pi pi-eye" size="small" @click="viewModel(model)" />
                            <Button icon="pi pi-download" severity="secondary" size="small" @click="downloadModel(model)" />
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Empty State -->
        <Card v-else>
            <template #content>
                <div class="text-center py-12">
                    <i class="pi pi-cube text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No 3D Models Found</h3>
                    <p class="text-gray-600 mb-4">Upload your first 3D model to get started</p>
                    <Button label="Upload 3D Model" icon="pi pi-cloud-upload"
                        @click="router.push({ name: 'merchandising.assets.upload' })" />
                </div>
            </template>
        </Card>
    
        <!-- View Dialog -->
        <Dialog v-model:visible="viewDialogVisible" :header="currentModel?.file_name" :modal="true"
            class="w-full max-w-4xl">
            <div v-if="currentModel" class="space-y-4">
                <!-- Fullscreen 3D Viewer -->
                <Model3DPreview
                    :model-url="currentModel.url"
                    :model-format="currentModel.model_format"
                    :camera-x="currentModel?.camera_settings?.angle_x ?? 0"
                    :camera-y="currentModel?.camera_settings?.angle_y ?? 15"
                    :zoom="currentModel?.camera_settings?.zoom ?? 1.5"
                    height="500px"
                />
    
                <!-- Model Details -->
                <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Format</p>
                        <Tag :value="currentModel.model_format?.toUpperCase()" severity="info" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">File Size</p>
                        <p class="text-sm font-semibold">{{ formatFileSize(currentModel.file_size_kb * 1024) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Product</p>
                        <p class="text-sm font-semibold">{{ currentModel.product?.product_name || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Uploaded</p>
                        <p class="text-sm font-semibold">{{ formatDate(currentModel.created_at) }}</p>
                    </div>
                </div>
            </div>
    
            <template #footer>
                <Button label="Download" icon="pi pi-download" @click="downloadModel(currentModel)" />
                <Button label="Close" severity="secondary" outlined @click="closeViewDialog" />
            </template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from '@/axios'

import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'

const router = useRouter()
const toast = useToast()

const models = ref<any[]>([])
const loading = ref(false)
const viewDialogVisible = ref(false)
const currentModel = ref(null)

// Computed
const glbCount = computed(() => {
  return models.value.filter(m => m.model_format?.toLowerCase() === 'glb').length
})

const totalSizeFormatted = computed(() => {
  const totalBytes = models.value.reduce((sum, m) => sum + (m.file_size_kb * 1024), 0)
  return formatFileSize(totalBytes)
})

// Load Models from API
const loadModels = async () => {
  loading.value = true
  try {
    // Fetch all assets filtered by 3D_Model type
    const response = await axios.get('/api/product-catalog/assets', {
      params: {
        asset_type: '3D_Model'
      }
    })

    models.value = response.data.data.data || response.data.data || []

    console.log(`Loaded ${models.value.length} 3D models:`, models.value)

  } catch (error: any) {
    console.error('Failed to load 3D models:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load 3D models',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

const viewModel = (model: any) => {
  currentModel.value = model
  viewDialogVisible.value = true
}

const closeViewDialog = () => {
  viewDialogVisible.value = false
  currentModel.value = null
}

const downloadModel = (model: any) => {
  window.open(model.url, '_blank')
  toast.add({
    severity: 'success',
    summary: 'Download Started',
    detail: `Downloading ${model.file_name}`,
    life: 2000
  })
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Cleanup on unmount
onMounted(() => {
  loadModels()
})
</script>

<style scoped>
:deep(canvas) {
    display: block;
    max-width: 100%;
}
</style>
