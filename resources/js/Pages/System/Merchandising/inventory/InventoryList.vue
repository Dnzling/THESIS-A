<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Inventory Status</h2>
        <p class="text-sm text-gray-500 mt-1">Monitor stock levels across all product variations</p>
      </div>
      <div class="flex gap-2">
        <Button 
          label="Export Report" 
          icon="pi pi-download" 
          severity="secondary"
          outlined
          @click="exportInventory"
          :loading="exporting"
        />
        <Button 
          label="Bulk Stock Update" 
          icon="pi pi-upload" 
          @click="openBulkUpdateDialog"
        />
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Products</p>
              <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ stats.totalProducts }}</h3>
            </div>
            <div class="bg-blue-100 p-3 rounded-full">
              <i class="pi pi-box text-blue-600 text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">In Stock</p>
              <h3 class="text-2xl font-bold text-green-600 mt-1">{{ stats.inStock }}</h3>
            </div>
            <div class="bg-green-100 p-3 rounded-full">
              <i class="pi pi-check-circle text-green-600 text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Low Stock</p>
              <h3 class="text-2xl font-bold text-orange-600 mt-1">{{ stats.lowStock }}</h3>
            </div>
            <div class="bg-orange-100 p-3 rounded-full">
              <i class="pi pi-exclamation-triangle text-orange-600 text-2xl"></i>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Out of Stock</p>
              <h3 class="text-2xl font-bold text-red-600 mt-1">{{ stats.outOfStock }}</h3>
            </div>
            <div class="bg-red-100 p-3 rounded-full">
              <i class="pi pi-times-circle text-red-600 text-2xl"></i>
            </div>
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
            <InputText v-model="searchQuery" placeholder="Search products..." class="w-full" @input="onSearch" />
          </IconField>

          <Select 
            v-model="filters.category_id" 
            :options="categories" 
            optionLabel="category_name" 
            optionValue="id"
            placeholder="All Categories" 
            showClear 
            filter
            @change="loadInventory"
          />

          <Select 
            v-model="filters.stock_status" 
            :options="stockStatusOptions" 
            placeholder="All Stock Status" 
            showClear 
            @change="loadInventory"
          />

          <Select 
            v-model="filters.sort_by" 
            :options="sortOptions" 
            optionLabel="label" 
            optionValue="value"
            placeholder="Sort by" 
            @change="loadInventory"
          />
        </div>
      </template>
    </Card>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <Skeleton v-for="i in 10" :key="i" height="80px" class="rounded-lg" />
    </div>

    <!-- Inventory Table -->
    <Card v-else-if="inventory.length > 0">
      <template #content>
        <DataTable 
          :value="inventory" 
          :paginator="true" 
          :rows="20"
          :rowsPerPageOptions="[20, 50, 100]"
          dataKey="id"
          stripedRows
          class="p-datatable-sm"
          responsiveLayout="scroll"
        >
          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-inbox text-6xl text-gray-300"></i>
              <p class="text-gray-600 mt-4">No inventory found</p>
            </div>
          </template>

          <Column field="product.sku" header="SKU" frozen>
            <template #body="{ data }">
              <span class="font-mono text-sm font-semibold">{{ data.variation_sku || data.product?.sku }}</span>
            </template>
          </Column>

          <Column field="product.product_name" header="Product" sortable>
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-gray-900">{{ data.product?.product_name }}</p>
                <p v-if="data.variation_name" class="text-xs text-gray-500 mt-1">{{ data.variation_name }}</p>
              </div>
            </template>
          </Column>

          <Column field="category" header="Category">
            <template #body="{ data }">
              <span class="text-sm text-gray-700">{{ data.product?.category?.category_name || 'N/A' }}</span>
            </template>
          </Column>

          <Column field="stock_quantity" header="Stock Quantity" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Badge :value="data.stock_quantity" :severity="getStockSeverity(data.stock_quantity)" />
                <ProgressBar 
                  :value="getStockPercentage(data.stock_quantity)" 
                  :showValue="false"
                  :style="{ height: '6px', width: '80px' }"
                  :pt="{
                    value: { style: { background: getStockColor(data.stock_quantity) } }
                  }"
                />
              </div>
            </template>
          </Column>

          <Column field="reorder_level" header="Reorder Level">
            <template #body="{ data }">
              <span class="text-sm">{{ data.reorder_level || 10 }}</span>
            </template>
          </Column>

          <Column field="price" header="Price">
            <template #body="{ data }">
              <span class="font-semibold">₱{{ formatPrice(data.final_price || data.product?.base_price || 0) }}</span>
            </template>
          </Column>

          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="getStockStatus(data.stock_quantity)" :severity="getStockSeverity(data.stock_quantity)" />
            </template>
          </Column>

          <Column header="Actions" :frozen="true" alignFrozen="right">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button 
                  icon="pi pi-pencil" 
                  severity="info"
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Update Stock'"
                  @click="openStockUpdateDialog(data)"
                />
                <Button 
                  icon="pi pi-chart-line" 
                  severity="secondary"
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'View History'"
                  @click="viewStockHistory(data)"
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
          <i class="pi pi-inbox text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-4 text-lg">No inventory data found</p>
          <p class="text-gray-500 text-sm mt-2">Add products and variations to track inventory</p>
        </div>
      </template>
    </Card>

    <!-- Update Stock Dialog -->
    <Dialog 
      v-model:visible="stockUpdateDialogVisible" 
      header="Update Stock Quantity" 
      :modal="true" 
      class="w-full max-w-md"
    >
      <div v-if="currentItem" class="space-y-4 mt-4">
        <div class="bg-gray-50 p-4 rounded-lg">
          <p class="text-sm font-semibold text-gray-900">{{ currentItem.product?.product_name }}</p>
          <p v-if="currentItem.variation_name" class="text-xs text-gray-600 mt-1">{{ currentItem.variation_name }}</p>
          <p class="text-xs text-gray-500 mt-2">SKU: {{ currentItem.variation_sku || currentItem.product?.sku }}</p>
        </div>

        <div class="flex flex-col gap-2">
          <label class="text-sm font-semibold text-gray-700">Current Stock</label>
          <p class="text-2xl font-bold text-gray-900">{{ currentItem.stock_quantity }}</p>
        </div>

        <div class="flex flex-col gap-2">
          <label for="new_stock" class="text-sm font-semibold text-gray-700">
            New Stock Quantity <span class="text-red-500">*</span>
          </label>
          <InputNumber 
            id="new_stock"
            v-model="stockUpdateData.new_quantity" 
            :min="0"
            showButtons
            buttonLayout="horizontal"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="reason" class="text-sm font-semibold text-gray-700">
            Reason for Update
          </label>
          <Textarea 
            id="reason"
            v-model="stockUpdateData.reason" 
            rows="3" 
            placeholder="e.g., Stock received, Damaged items, etc."
          />
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="stockUpdateDialogVisible = false" />
        <Button label="Update Stock" icon="pi pi-check" @click="updateStock" :loading="updating" />
      </template>
    </Dialog>

    <!-- Bulk Update Dialog -->
    <Dialog 
      v-model:visible="bulkUpdateDialogVisible" 
      header="Bulk Stock Update" 
      :modal="true" 
      class="w-full max-w-2xl"
    >
      <div class="space-y-4 mt-4">
        <p class="text-sm text-gray-600">Upload a CSV file to update multiple stock quantities at once</p>
        
        <FileUpload 
          mode="basic" 
          accept=".csv" 
          :maxFileSize="1000000"
          chooseLabel="Choose CSV File"
          class="w-full"
          @select="handleCSVUpload"
        />

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <p class="text-sm font-semibold text-blue-900 mb-2">CSV Format:</p>
          <code class="text-xs text-blue-800 block">SKU,Quantity,Reason</code>
          <code class="text-xs text-blue-800 block mt-1">SOFA-001,50,Stock received</code>
          <code class="text-xs text-blue-800 block">CHAIR-002,25,Restock</code>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="bulkUpdateDialogVisible = false" />
        <Button label="Upload & Update" icon="pi pi-upload" @click="processBulkUpdate" :loading="bulkUpdating" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../stores/auth'
import merchandisingService from '../../../../services/merchandising.service'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import ProgressBar from 'primevue/progressbar'
import FileUpload from 'primevue/fileupload'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const toast = useToast()
const authStore = useAuthStore()

// State
const inventory = ref([])
const categories = ref([])
const loading = ref(false)
const exporting = ref(false)
const updating = ref(false)
const bulkUpdating = ref(false)
const stockUpdateDialogVisible = ref(false)
const bulkUpdateDialogVisible = ref(false)
const currentItem = ref(null)
const searchQuery = ref('')

const stats = reactive({
  totalProducts: 0,
  inStock: 0,
  lowStock: 0,
  outOfStock: 0
})

const filters = reactive({
  category_id: null,
  stock_status: null,
  sort_by: 'stock_asc'
})

const stockUpdateData = reactive({
  new_quantity: 0,
  reason: ''
})

const stockStatusOptions = ['All', 'In Stock', 'Low Stock', 'Out of Stock']

const sortOptions = [
  { label: 'Stock: Low to High', value: 'stock_asc' },
  { label: 'Stock: High to Low', value: 'stock_desc' },
  { label: 'Product Name A-Z', value: 'name_asc' },
  { label: 'Product Name Z-A', value: 'name_desc' }
]

// Methods
const loadInventory = async () => {
  loading.value = true
  try {
    const params: any = { ...filters }
    if (searchQuery.value) params.search = searchQuery.value

    // Fetch variations with stock info
    const response = await merchandisingService.getVariations(params)
    inventory.value = response.data.data

    // Calculate stats
    calculateStats()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load inventory',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadCategories = async () => {
  try {
    const response = await merchandisingService.getCategories()
    categories.value = response.data.data
  } catch (error) {
    console.error('Failed to load categories:', error)
  }
}

const calculateStats = () => {
  stats.totalProducts = inventory.value.length
  stats.inStock = inventory.value.filter((item: any) => item.stock_quantity > 10).length
  stats.lowStock = inventory.value.filter((item: any) => item.stock_quantity > 0 && item.stock_quantity <= 10).length
  stats.outOfStock = inventory.value.filter((item: any) => item.stock_quantity === 0).length
}

const onSearch = () => {
  loadInventory()
}

const getStockStatus = (quantity: number) => {
  if (quantity === 0) return 'Out of Stock'
  if (quantity <= 10) return 'Low Stock'
  return 'In Stock'
}

const getStockSeverity = (quantity: number) => {
  if (quantity === 0) return 'danger'
  if (quantity <= 10) return 'warning'
  return 'success'
}

const getStockColor = (quantity: number) => {
  if (quantity === 0) return '#ef4444'
  if (quantity <= 10) return '#f97316'
  return '#10b981'
}

const getStockPercentage = (quantity: number) => {
  const max = 100
  return Math.min((quantity / max) * 100, 100)
}

const openStockUpdateDialog = (item: any) => {
  currentItem.value = item
  stockUpdateData.new_quantity = item.stock_quantity
  stockUpdateData.reason = ''
  stockUpdateDialogVisible.value = true
}

const updateStock = async () => {
  if (!currentItem.value) return

  updating.value = true
  try {
    await merchandisingService.updateVariation(currentItem.value.id, {
      stock_quantity: stockUpdateData.new_quantity
    })

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Stock quantity updated successfully',
      life: 3000
    })

    stockUpdateDialogVisible.value = false
    loadInventory()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update stock',
      life: 3000
    })
  } finally {
    updating.value = false
  }
}

const openBulkUpdateDialog = () => {
  bulkUpdateDialogVisible.value = true
}

const handleCSVUpload = (event: any) => {
  console.log('CSV uploaded:', event.files[0])
  // TODO: Parse CSV and prepare for bulk update
}

const processBulkUpdate = async () => {
  bulkUpdating.value = true
  try {
    // TODO: Implement bulk update logic
    toast.add({
      severity: 'info',
      summary: 'Coming Soon',
      detail: 'Bulk update feature will be available soon',
      life: 3000
    })
    bulkUpdateDialogVisible.value = false
  } finally {
    bulkUpdating.value = false
  }
}

const viewStockHistory = (item: any) => {
  toast.add({
    severity: 'info',
    summary: 'Coming Soon',
    detail: 'Stock history feature will be available soon',
    life: 3000
  })
}

const exportInventory = () => {
  exporting.value = true
  setTimeout(() => {
    toast.add({
      severity: 'success',
      summary: 'Export Started',
      detail: 'Inventory report will be downloaded shortly',
      life: 3000
    })
    exporting.value = false
  }, 1000)
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2 
  }).format(price)
}

onMounted(() => {
  loadCategories()
  loadInventory()
})
</script>