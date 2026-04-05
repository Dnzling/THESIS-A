<template>
  <div class="max-w-6xl mx-auto space-y-6 pb-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">3D Reconstruction</h2>
        <p class="text-sm text-gray-500 mt-1">Generate 3D models from photos or upload existing 3D files.</p>
      </div>
      <div class="flex gap-2">
        <Button label="3D Gallery" icon="pi pi-cube" severity="info" outlined
          @click="router.push({ name: 'merchandising.3d-gallery' })" />
        <Button label="Upload 3D Model" icon="pi pi-cloud-upload"
          @click="router.push({ name: 'merchandising.assets.upload' })" />
      </div>
    </div>
  
    <!-- Choose Path -->
  
  
    <!-- Reconstruction Form -->
    <Card ref="formCard">
      <template #title>
        <div class="flex items-center gap-2">
          <span>New 3D Reconstruction</span>
        </div>
      </template>
      <template #content>
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Product <span class="text-red-500">*</span></label>
              <Select v-model="form.product_id" :options="products" optionLabel="product_name" optionValue="id"
                placeholder="Select a product" filter />
            </div>
  
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Recommended</label>
              <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                Upload 30-45 photos for best results.
              </div>
            </div>
          </div>
  
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Upload Photos <span class="text-red-500">*</span></label>
            <div
              class="border-2 border-dashed border-gray-300 rounded-lg p-10 text-center hover:border-green-400 transition-colors cursor-pointer"
              @click="fileInput?.click()" @dragover.prevent @drop.prevent="handleDrop">
              <i class="pi pi-cloud-upload text-5xl text-gray-400 mb-3 block"></i>
              <p class="text-base font-medium text-gray-700">Drop 30-45 images here</p>
              <p class="text-xs text-gray-500 mt-2">Accepted: JPG, PNG, WebP (max 10MB each)</p>
            </div>
  
            <input ref="fileInput" type="file" multiple accept="image/jpeg,image/png,image/webp" class="hidden"
              @change="handleFileSelect" />
  
            <div class="flex items-center justify-between text-xs text-gray-600">
              <span>Selected: {{ form.images.length }} files</span>
              <span>Recommended: 30-45 images</span>
            </div>
  
            <div v-if="form.images.length" class="max-h-40 overflow-auto border rounded-lg p-3 text-xs text-gray-700">
              <div v-for="(file, index) in form.images" :key="index" class="flex items-center justify-between py-1">
                <span class="truncate">{{ file.name }}</span>
                <span class="text-gray-500">{{ formatFileSize(file.size) }}</span>
              </div>
            </div>
          </div>
  
          <div class="flex items-center gap-2">
            <Button label="Start Reconstruction" icon="pi pi-play" severity="success" :loading="submitting"
              :disabled="!canSubmit" @click="handleSubmit" />
            <Button label="Clear" severity="secondary" outlined type="button" @click="resetForm" />
          </div>
  
        
        </form>
      </template>
    </Card>
  
    <!-- Current Status -->
    <Card v-if="currentRecon">
      <template #title>
        <div class="flex items-center justify-between">
          <span>Current Reconstruction</span>
          <Tag :value="currentRecon.status.toUpperCase()" :severity="statusSeverity(currentRecon.status)" />
        </div>
      </template>
      <template #content>
        <div class="space-y-3">
          <ProgressBar :value="currentRecon.progress || 0" />
          <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm text-gray-700">
            <div>
              <div class="text-xs text-gray-500">ID</div>
              <div class="font-semibold">{{ currentRecon.id }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Images</div>
              <div class="font-semibold">{{ currentRecon.input_count }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Started</div>
              <div class="font-semibold">{{ formatDateTime(currentRecon.started_at) }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Finished</div>
              <div class="font-semibold">{{ formatDateTime(currentRecon.finished_at) }}</div>
            </div>
          </div>
  
          <div v-if="currentRecon.error_message" class="p-3 bg-red-50 border border-red-200 rounded text-sm text-red-700">
            {{ currentRecon.error_message }}
          </div>
  
          <div class="flex gap-2">
            <Button v-if="currentRecon.status === 'ready'" label="Open Result" icon="pi pi-eye" severity="info"
              @click="openResult(currentRecon.id)" />
            <Button v-if="['queued', 'processing'].includes(currentRecon.status)" label="Cancel" icon="pi pi-times"
              severity="danger" outlined @click="cancelCurrent" />
          </div>
        </div>
      </template>
    </Card>
  
    <!-- History -->
    <Card>
      <template #title>
        <div class="flex items-center justify-between">
          <span>Reconstruction History</span>
          <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined @click="loadReconstructions" />
        </div>
      </template>
      <template #content>
        <DataTable :value="reconstructions" dataKey="id" :paginator="true" :rows="10" :rowsPerPageOptions="[10, 20, 50]">
          <template #empty>
            <div class="text-center py-8 text-gray-500">No reconstructions yet.</div>
          </template>
          <Column field="product.product_name" header="Product">
            <template #body="{ data }">
              <span>{{ data.product?.product_name || 'N/A' }}</span>
            </template>
          </Column>
          <Column field="input_count" header="Images" style="width: 120px" />
          <Column field="status" header="Status" style="width: 140px">
            <template #body="{ data }">
              <Tag :value="data.status.toUpperCase()" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column field="created_at" header="Created">
            <template #body="{ data }">
              <span>{{ formatDateTime(data.created_at) }}</span>
            </template>
          </Column>
          <Column header="Actions" style="width: 200px">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-eye" size="small" severity="info" text rounded @click="openResult(data.id)"
                  :disabled="data.status !== 'ready'" />
                <Button icon="pi pi-times" size="small" severity="danger" text rounded @click="cancelById(data.id)"
                  :disabled="!['queued', 'processing'].includes(data.status)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  
    <Dialog  :visible="isBusy" :modal="true" :closable="false" :draggable="false"
      :style="{ width: '420px', maxWidth: '92vw' }">
      <template #header>
        <div class="flex items-center gap-2">
          <i class="pi pi-spin pi-spinner text-blue-600"></i>
          <span class="font-semibold">{{ busyTitle }}</span>
        </div>
      </template>
      <div class="space-y-3">
        <ProgressBar v-if="showBusyPercent" :value="busyProgress" :showValue="false" />
        <ProgressBar v-else mode="indeterminate" />
        <div class="flex items-center justify-between text-xs text-gray-600">
          <span>Please keep this tab open.</span>
          <span v-if="showBusyPercent">{{ busyProgress }}%</span>
          <span v-else>Processing...</span>
        </div>
        
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted, onBeforeUnmount, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'

import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

const router = useRouter()
const toast = useToast()

const products = ref<any[]>([])
const reconstructions = ref<any[]>([])
const currentRecon = ref<any | null>(null)
const submitting = ref(false)
const uploadProgress = ref(0)
const isBusy = computed(() => {
  if (submitting.value) return true
  if (!currentRecon.value) return false
  return ['queued', 'processing'].includes(String(currentRecon.value.status || '').toLowerCase())
})
const busyTitle = computed(() => {
  if (submitting.value) return 'Uploading photos'
  const status = String(currentRecon.value?.status || '').toLowerCase()
  if (status === 'processing') return 'Reconstruction in progress'
  return 'Queued for reconstruction'
})
const busyProgress = computed(() => {
  if (submitting.value) return uploadProgress.value
  const progress = Number(currentRecon.value?.progress || 0)
  return Number.isFinite(progress) ? progress : 0
})
const showBusyPercent = computed(() => submitting.value && uploadProgress.value > 0)
const pollingId = ref<any>(null)

const formCard = ref()
const fileInput = ref<HTMLInputElement | null>(null)

const form = reactive({
  product_id: null as number | null,
  images: [] as File[],
})

const canSubmit = computed(() => {
  return !!form.product_id && form.images.length >= 8 && !submitting.value
})

const scrollToForm = async () => {
  await nextTick()
  formCard.value?.$el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  if (!files.length) return
  form.images = files
}

const handleDrop = (event: DragEvent) => {
  const files = Array.from(event.dataTransfer?.files || [])
  if (!files.length) return
  form.images = files
}

const resetForm = () => {
  form.product_id = null
  form.images = []
  if (fileInput.value) fileInput.value.value = ''
}

const handleSubmit = async () => {
  if (!canSubmit.value) {
    toast.add({
      severity: 'warn',
      summary: 'Missing Info',
      detail: 'Select a product and upload at least 8 images.',
      life: 3000
    })
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    formData.append('product_id', String(form.product_id))
    formData.append('options[engine]', 'colmap')
    form.images.forEach((file) => {
      formData.append('images[]', file)
    })

    const response = await merchandisingService.create3DReconstruction(formData, {
      onUploadProgress: (event: any) => {
        if (!event.total) return
        uploadProgress.value = Math.round((event.loaded / event.total) * 100)
      }
    })
    currentRecon.value = response.data

    toast.add({
      severity: 'success',
      summary: 'Queued',
      detail: '3D reconstruction has been queued.',
      life: 3000
    })

    resetForm()
    startPolling()
    loadReconstructions()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: error.response?.data?.message || 'Failed to queue reconstruction',
      life: 5000
    })
  } finally {
    submitting.value = false
    uploadProgress.value = 0
  }
}

const loadProducts = async () => {
  const response = await merchandisingService.getProducts({ per_page: 1000, product_type: 'finished_good' })
  products.value = response.data.data || []
}

const loadReconstructions = async () => {
  const response = await merchandisingService.get3DReconstructions({ per_page: 20 })
  const data = response.data.data || response.data
  reconstructions.value = data.data || data
}

const startPolling = () => {
  stopPolling()
  if (!currentRecon.value?.id) return

  pollingId.value = setInterval(async () => {
    try {
      const res = await merchandisingService.get3DReconstructionStatus(currentRecon.value.id)
      currentRecon.value = res.data
      if (['ready', 'failed', 'canceled'].includes(currentRecon.value.status)) {
        stopPolling()
      }
    } catch (error) {
      stopPolling()
    }
  }, 4000)
}

const stopPolling = () => {
  if (pollingId.value) {
    clearInterval(pollingId.value)
    pollingId.value = null
  }
}

const openResult = (id: number) => {
  window.open(`/api/product-catalog/3d-reconstructions/${id}/result`, '_blank')
}

const cancelCurrent = async () => {
  if (!currentRecon.value) return
  await cancelById(currentRecon.value.id)
}

const cancelById = async (id: number) => {
  try {
    const res = await merchandisingService.cancel3DReconstruction(id)
    if (currentRecon.value?.id === id) {
      currentRecon.value = res.data
      stopPolling()
    }
    loadReconstructions()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: error.response?.data?.message || 'Failed to cancel reconstruction',
      life: 4000
    })
  }
}

const statusSeverity = (status: string) => {
  switch (status) {
    case 'ready':
      return 'success'
    case 'failed':
      return 'danger'
    case 'processing':
      return 'info'
    case 'queued':
      return 'warning'
    case 'canceled':
      return 'secondary'
    default:
      return 'secondary'
  }
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const formatDateTime = (value: string | null) => {
  if (!value) return 'N/A'
  return new Date(value).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(async () => {
  await loadProducts()
  await loadReconstructions()
})

onBeforeUnmount(() => {
  stopPolling()
})

watch(isBusy, (busy) => {
  if (busy) {
    window.onbeforeunload = () => 'A reconstruction is in progress. Are you sure you want to leave?'
  } else {
    window.onbeforeunload = null
  }
})
</script>
