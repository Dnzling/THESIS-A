<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Media & Assets</h2>
        <p class="text-sm text-gray-500 mt-1">Manage images, videos, and documents</p>
      </div>
      <div class="flex gap-2">
        <Button 
          label="3D Models Gallery" 
          icon="pi pi-cube" 
          severity="info"
          outlined
          @click="router.push({ name: 'merchandising.3d-gallery' })"
        />
        <Button 
          v-if="authStore.hasPermission('merchandising.assets.upload')"
          label="Upload Assets" 
          icon="pi pi-cloud-upload" 
          @click="router.push({ name: 'merchandising.assets.upload' })"
        />
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card v-for="stat in assetStats" :key="stat.type" class="hover:shadow-lg transition-shadow">
        <template #content>
          <div class="text-center">
            <div :class="[stat.bgColor, 'inline-flex p-4 rounded-full mb-3']">
              <i :class="[stat.icon, stat.iconColor, 'text-2xl']"></i>
            </div>
            <p class="text-sm text-gray-600">{{ stat.label }}</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ stat.count }}</h3>
            <p class="text-xs text-gray-500 mt-1">{{ formatFileSize(stat.totalSize) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="searchQuery" placeholder="Search assets..." class="w-full" @input="onSearch" />
          </IconField>

          <Select 
            v-model="filters.asset_type" 
            :options="assetTypes" 
            optionLabel="label"
            optionValue="value"
            placeholder="All Types" 
            showClear 
            @change="loadAssets"
          />

          <Select 
            v-model="filters.product_id" 
            :options="products" 
            optionLabel="product_name" 
            optionValue="id"
            placeholder="All Products" 
            showClear 
            filter
            @change="loadAssets"
          />

          <div class="flex gap-2">
            <Button 
              :label="viewMode === 'grid' ? 'Grid' : 'List'" 
              :icon="viewMode === 'grid' ? 'pi pi-th-large' : 'pi pi-list'"
              :severity="viewMode === 'grid' ? 'primary' : 'secondary'"
              outlined
              @click="toggleViewMode"
              class="flex-1"
            />
            <Button 
              label="Bulk Delete" 
              icon="pi pi-trash" 
              severity="danger"
              outlined
              @click="bulkDeleteAssets"
              :disabled="selectedAssets.length === 0"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <Skeleton v-for="i in 8" :key="i" height="250px" class="rounded-lg" />
    </div>

    <!-- Grid View -->
    <div v-else-if="viewMode === 'grid' && assets.length > 0" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
      <Card 
        v-for="asset in assets" 
        :key="asset.id"
        class="hover:shadow-lg transition-shadow cursor-pointer"
        @click="viewAsset(asset)"
      >
        <template #content>
          <div class="space-y-3">
            <!-- Asset Preview -->
            <div class="relative aspect-video bg-gray-100 rounded-lg overflow-hidden group">
              
              <!-- Image Preview -->
              <img 
                v-if="asset.asset_type.includes('Image')"
                :src="asset.auth_url || asset.thumbnail_url || asset.url" 
                :alt="asset.file_name"
                class="w-full h-full object-cover"
                @error="handleImageError"
              />

              <!-- Video Preview -->
              <div v-else-if="asset.asset_type === 'Video_Product'" class="w-full h-full flex items-center justify-center bg-gradient-to-br from-purple-50 to-pink-100">
                <i class="pi pi-video text-6xl text-purple-600"></i>
              </div>

              <!-- PDF Preview -->
              <div v-else-if="asset.asset_type === 'Manual_PDF'" class="w-full h-full flex items-center justify-center bg-gradient-to-br from-red-50 to-orange-100">
                <i class="pi pi-file-pdf text-6xl text-red-600"></i>
              </div>

              <!-- Hover Overlay -->
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                <div class="flex gap-2">
                  <Button 
                    icon="pi pi-eye" 
                    rounded 
                    @click.stop="viewAsset(asset)"
                  />
                  <Button 
                    icon="pi pi-download" 
                    rounded 
                    severity="secondary"
                    @click.stop="downloadAsset(asset)"
                  />
                  <Button 
                    v-if="authStore.hasPermission('merchandising.assets.delete')"
                    icon="pi pi-trash" 
                    rounded 
                    severity="danger"
                    @click.stop="confirmDelete(asset)"
                  />
                </div>
              </div>

              <!-- Primary Badge -->
              <Badge 
                v-if="asset.is_primary" 
                value="Primary" 
                severity="success" 
                class="absolute top-2 left-2"
              />

              <!-- Checkbox for selection -->
              <div class="absolute top-2 right-2" @click.stop>
                <Checkbox 
                  v-model="selectedAssets" 
                  :value="asset.id" 
                  :binary="false"
                />
              </div>
            </div>

            <!-- Asset Info -->
            <div>
              <p class="text-sm font-semibold text-gray-900 truncate">{{ asset.file_name }}</p>
              <div class="flex items-center gap-2 mt-2">
                <Tag :value="getAssetTypeLabel(asset.asset_type)" :severity="getAssetTypeSeverity(asset.asset_type)" size="small" />
                <span class="text-xs text-gray-500">{{ formatFileSize(asset.file_size_kb * 1024) }}</span>
              </div>
              <p v-if="asset.product" class="text-xs text-gray-600 mt-2 truncate">
                {{ asset.product.product_name }}
              </p>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- List View -->
    <Card v-else-if="viewMode === 'list' && assets.length > 0">
      <template #content>
        <DataTable 
          :value="assets" 
          :paginator="true" 
          :rows="15"
          :rowsPerPageOptions="[15, 30, 50]"
          dataKey="id"
          v-model:selection="selectedAssets"
          stripedRows
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-folder-open text-6xl text-gray-300"></i>
              <p class="text-gray-600 mt-4">No assets found</p>
            </div>
          </template>

          <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>

          <Column header="Preview" style="width: 100px">
            <template #body="{ data }">
              <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center overflow-hidden">
                <img 
                  v-if="data.asset_type.includes('Image')"
                  :src="data.auth_url || data.thumbnail_url || data.url" 
                  class="w-full h-full object-cover rounded"
                  @error="handleImageError"
                />
                <i v-else :class="getAssetIcon(data.asset_type)" class="text-2xl text-gray-600"></i>
              </div>
            </template>
          </Column>

          <Column field="file_name" header="File Name" sortable>
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-gray-900">{{ data.file_name }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ data.alt_text || 'No description' }}</p>
              </div>
            </template>
          </Column>

          <Column field="asset_type" header="Type" sortable>
            <template #body="{ data }">
              <Tag :value="getAssetTypeLabel(data.asset_type)" :severity="getAssetTypeSeverity(data.asset_type)" />
            </template>
          </Column>

          <Column field="product.product_name" header="Product" sortable>
            <template #body="{ data }">
              <span v-if="data.product" class="text-sm">{{ data.product.product_name }}</span>
              <span v-else class="text-sm text-gray-400 italic">Unassigned</span>
            </template>
          </Column>

          <Column field="file_size_kb" header="Size" sortable>
            <template #body="{ data }">
              <span class="text-sm">{{ formatFileSize(data.file_size_kb * 1024) }}</span>
            </template>
          </Column>

          <Column field="is_primary" header="Primary">
            <template #body="{ data }">
              <Tag v-if="data.is_primary" value="Yes" severity="success" size="small" />
              <span v-else class="text-sm text-gray-400">No</span>
            </template>
          </Column>

          <Column field="created_at" header="Uploaded" sortable>
            <template #body="{ data }">
              <span class="text-sm">{{ formatDate(data.created_at) }}</span>
            </template>
          </Column>

          <Column header="Actions" :frozen="true" alignFrozen="right">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button 
                  icon="pi pi-eye" 
                  severity="info"
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'View'"
                  @click="viewAsset(data)"
                />
                <Button 
                  icon="pi pi-download" 
                  severity="secondary"
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Download'"
                  @click="downloadAsset(data)"
                />
                <Button 
                  v-if="authStore.hasPermission('merchandising.assets.delete')"
                  icon="pi pi-trash" 
                  severity="danger"
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Delete'"
                  @click="confirmDelete(data)"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Empty State -->
    <Card v-else>
      <template #content>
        <div class="text-center py-12">
          <i class="pi pi-cloud-upload text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-4 text-lg">No media assets uploaded yet</p>
          <p class="text-gray-500 text-sm mt-2">Upload images, videos, and documents for your products</p>
          <div class="flex gap-3 justify-center mt-6">
            <Button 
              label="Upload Media Assets" 
              icon="pi pi-cloud-upload" 
              @click="$router.push({ name: 'merchandising.assets.upload' })"
            />
            <Button 
              label="View 3D Models" 
              icon="pi pi-cube" 
              severity="info"
              outlined
              @click="$router.push({ name: 'merchandising.3d-gallery' })"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- View Asset Dialog -->
    <Dialog 
      v-model:visible="viewDialogVisible" 
      :header="currentAsset?.file_name" 
      :modal="true" 
      class="w-full max-w-4xl"
    >
      <div v-if="currentAsset" class="space-y-4">
        <!-- Asset Preview -->
        <div class="bg-gray-100 rounded-lg p-8 flex items-center justify-center" style="min-height: 500px;">
          
          <!-- Image Preview -->
          <img 
            v-if="currentAsset.asset_type.includes('Image')"
            :src="currentAsset.auth_url || currentAsset.url" 
            :alt="currentAsset.file_name"
            class="max-w-full max-h-96 object-contain"
          />

          <!-- Video Preview -->
          <video 
            v-else-if="currentAsset.asset_type === 'Video_Product'"
            :src="currentAsset.url"
            controls
            class="max-w-full max-h-96"
          ></video>

          <!-- PDF Preview -->
          <div v-else-if="currentAsset.asset_type === 'Manual_PDF'" class="text-center">
            <i class="pi pi-file-pdf text-8xl text-red-600 mb-4"></i>
            <p class="text-gray-600 text-lg font-semibold">{{ currentAsset.file_name }}</p>
            <p class="text-gray-500 text-sm mt-2">PDF Document</p>
          </div>

          <!-- Other file types -->
          <i v-else :class="getAssetIcon(currentAsset.asset_type)" class="text-8xl text-gray-400"></i>
        </div>

        <!-- Asset Details -->
        <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
          <div>
            <p class="text-xs text-gray-600 mb-1">Type</p>
            <Tag :value="getAssetTypeLabel(currentAsset.asset_type)" :severity="getAssetTypeSeverity(currentAsset.asset_type)" />
          </div>
          <div>
            <p class="text-xs text-gray-600 mb-1">File Size</p>
            <p class="text-sm font-semibold">{{ formatFileSize(currentAsset.file_size_kb * 1024) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-600 mb-1">Product</p>
            <p class="text-sm font-semibold">{{ currentAsset.product?.product_name || 'N/A' }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-600 mb-1">Uploaded</p>
            <p class="text-sm font-semibold">{{ formatDate(currentAsset.created_at) }}</p>
          </div>
          <div class="col-span-2" v-if="currentAsset.alt_text">
            <p class="text-xs text-gray-600 mb-1">Description</p>
            <p class="text-sm">{{ currentAsset.alt_text }}</p>
          </div>
          <div class="col-span-2">
            <p class="text-xs text-gray-600 mb-1">URL</p>
            <p class="text-xs font-mono text-blue-600 break-all">{{ currentAsset.url }}</p>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Download" icon="pi pi-download" @click="downloadAsset(currentAsset)" />
        <Button label="Close" severity="secondary" outlined @click="viewDialogVisible = false" />
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirm Delete" :modal="true" class="w-96">
      <div class="flex items-center gap-3">
        <i class="pi pi-exclamation-triangle text-4xl text-red-600"></i>
        <div>
          <p class="font-semibold">Are you sure you want to delete this asset?</p>
          <p class="text-sm text-gray-600 mt-1">This action cannot be undone.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="deleteDialogVisible = false" />
        <Button label="Delete" severity="danger" @click="deleteAsset" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../stores/auth'
import merchandisingService from '../../../../services/merchandising.service'
import { useRouter } from 'vue-router'


const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

// State
const assets = ref([])
const products = ref([])
const loading = ref(false)
const deleting = ref(false)
const viewDialogVisible = ref(false)
const deleteDialogVisible = ref(false)
const currentAsset = ref(null)
const selectedAssets = ref([])
const searchQuery = ref('')
const viewMode = ref('grid')

const filters = reactive({
  asset_type: null,
  product_id: null
})

// ✅ Only non-3D asset types
const assetTypes = [
  { label: 'Main Images', value: 'Image_Main' },
  { label: 'Gallery Images', value: 'Image_Gallery' },
  { label: 'Videos', value: 'Video_Product' },
  { label: 'PDFs/Manuals', value: 'Manual_PDF' }
]

// Computed
const assetStats = computed(() => {
  const stats = [
    {
      type: 'Image',
      label: 'Images',
      icon: 'pi pi-image',
      iconColor: 'text-blue-600',
      bgColor: 'bg-blue-100',
      count: 0,
      totalSize: 0
    },
    {
      type: 'Video',
      label: 'Videos',
      icon: 'pi pi-video',
      iconColor: 'text-purple-600',
      bgColor: 'bg-purple-100',
      count: 0,
      totalSize: 0
    },
    {
      type: 'PDF',
      label: 'PDFs',
      icon: 'pi pi-file-pdf',
      iconColor: 'text-red-600',
      bgColor: 'bg-red-100',
      count: 0,
      totalSize: 0
    },
    {
      type: 'Total',
      label: 'Total Assets',
      icon: 'pi pi-folder',
      iconColor: 'text-gray-600',
      bgColor: 'bg-gray-100',
      count: assets.value.length,
      totalSize: assets.value.reduce((sum: number, asset: any) => sum + (asset.file_size_kb * 1024), 0)
    }
  ]

  assets.value.forEach((asset: any) => {
    if (asset.asset_type.includes('Image')) {
      stats[0].count++
      stats[0].totalSize += asset.file_size_kb * 1024
    } else if (asset.asset_type === 'Video_Product') {
      stats[1].count++
      stats[1].totalSize += asset.file_size_kb * 1024
    } else if (asset.asset_type === 'Manual_PDF') {
      stats[2].count++
      stats[2].totalSize += asset.file_size_kb * 1024
    }
  })

  return stats
})

// ✅ Load images with authentication
const loadImageWithAuth = async (asset: any) => {
  if (!asset.url) return null

  try {
    const token = authStore.token || localStorage.getItem('auth_token')
    
    const response = await fetch(asset.url, {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'image/*'
      }
    })

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`)
    }

    const blob = await response.blob()
    return URL.createObjectURL(blob)
  } catch (error) {
    console.error('Failed to load image:', error)
    return null
  }
}

// Methods
const loadAssets = async () => {
  loading.value = true
  try {
    const params: any = { 
      ...filters,
      // ✅ Exclude 3D models from this list
      exclude_type: '3D_Model'
    }
    if (searchQuery.value) params.search = searchQuery.value

    const response = await merchandisingService.getAssets(params)
    
    // ✅ Filter out 3D models on frontend as backup
    assets.value = response.data.data.filter((asset: any) => asset.asset_type !== '3D_Model')
    
    // ✅ Load images with auth
    for (const asset of assets.value) {
      if (asset.asset_type.includes('Image')) {
        asset.auth_url = await loadImageWithAuth(asset)
      }
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load assets',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadProducts = async () => {
  try {
    const response = await merchandisingService.getProducts({ per_page: 1000 })
    products.value = response.data.data
  } catch (error) {
    console.error('Failed to load products:', error)
  }
}

const onSearch = () => {
  loadAssets()
}

const toggleViewMode = () => {
  viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid'
}

const viewAsset = async (asset: any) => {
  currentAsset.value = { ...asset }
  
  // ✅ Load auth URL for dialog if not already loaded
  if (asset.asset_type.includes('Image') && !asset.auth_url) {
    currentAsset.value.auth_url = await loadImageWithAuth(asset)
  }
  
  viewDialogVisible.value = true
}

const downloadAsset = (asset: any) => {
  window.open(asset.url, '_blank')
  toast.add({
    severity: 'success',
    summary: 'Download Started',
    detail: `Downloading ${asset.file_name}`,
    life: 2000
  })
}

const confirmDelete = (asset: any) => {
  currentAsset.value = asset
  deleteDialogVisible.value = true
}

const deleteAsset = async () => {
  deleting.value = true
  try {
    await merchandisingService.deleteAsset(currentAsset.value.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Asset deleted successfully',
      life: 3000
    })
    deleteDialogVisible.value = false
    loadAssets()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete asset',
      life: 3000
    })
  } finally {
    deleting.value = false
  }
}

const bulkDeleteAssets = async () => {
  if (selectedAssets.value.length === 0) return

  try {
    for (const assetId of selectedAssets.value) {
      await merchandisingService.deleteAsset(assetId)
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `${selectedAssets.value.length} assets deleted`,
      life: 3000
    })

    selectedAssets.value = []
    loadAssets()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete some assets',
      life: 3000
    })
  }
}

const handleImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.src = '/placeholder-image.png'
}

const getAssetTypeLabel = (type: string) => {
  const labels: Record<string, string> = {
    'Image_Main': 'Main Image',
    'Image_Gallery': 'Gallery',
    'Video_Product': 'Video',
    'Manual_PDF': 'PDF'
  }
  return labels[type] || type
}

const getAssetTypeSeverity = (type: string) => {
  const severities: Record<string, string> = {
    'Image_Main': 'success',
    'Image_Gallery': 'primary',
    'Video_Product': 'warning',
    'Manual_PDF': 'danger'
  }
  return severities[type] || 'secondary'
}

const getAssetIcon = (type: string) => {
  const icons: Record<string, string> = {
    'Image_Main': 'pi pi-image',
    'Image_Gallery': 'pi pi-images',
    'Video_Product': 'pi pi-video',
    'Manual_PDF': 'pi pi-file-pdf'
  }
  return icons[type] || 'pi pi-file'
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

onMounted(() => {
  loadProducts()
  loadAssets()
})
</script>

<style scoped>
.aspect-video {
  aspect-ratio: 16 / 9;
}
</style>