<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Product Variations</h2>
        <p class="text-sm text-gray-500 mt-1">Manage product variants like colors, sizes, and materials</p>
      </div>
      <div class="flex gap-2">
        <Button 
          label="Bulk Stock Update" 
          icon="pi pi-upload" 
          severity="secondary"
          outlined
          @click="openBulkUpdateDialog"
        />
        <Button 
          v-if="authStore.hasPermission('merchandising.variations.create')"
          label="Add Variation" 
          icon="pi pi-plus" 
          @click="router.push({ name: 'merchandising.variations.create' })"
        />
      </div>
    </div>

    <!-- Search & Filters -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="searchQuery" placeholder="Search variations..." class="w-full" @input="onSearch" />
          </IconField>

          <Select 
            v-model="filters.product_id" 
            :options="products" 
            optionLabel="product_name" 
            optionValue="id"
            placeholder="All Products" 
            showClear 
            filter
            @change="loadVariations"
          />

          <Select 
            v-model="filters.stock_status" 
            :options="stockStatusOptions" 
            placeholder="All Stock Status" 
            showClear 
            @change="loadVariations"
          />

          <Select 
            v-model="filters.is_active" 
            :options="statusOptions" 
            optionLabel="label" 
            optionValue="value"
            placeholder="All Status" 
            showClear 
            @change="loadVariations"
          />
        </div>
      </template>
    </Card>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-3">
      <Skeleton v-for="i in 5" :key="i" height="100px" class="rounded-lg" />
    </div>

    <!-- Variations DataTable -->
    <Card v-else-if="variations.length > 0">
      <template #content>
        <DataTable 
          :value="variations" 
          :paginator="true" 
          :rows="15"
          :rowsPerPageOptions="[15, 25, 50]"
          dataKey="id"
          stripedRows
          class="p-datatable-sm"
          v-model:selection="selectedVariations"
          @row-select="onRowSelect"
          @row-unselect="onRowUnselect"
        >
          <template #header>
            <div class="flex justify-between items-center">
              <span class="text-sm font-semibold text-gray-700">
                {{ variations.length }} variations found
                <span v-if="selectedVariations.length > 0" class="text-blue-600">
                  ({{ selectedVariations.length }} selected)
                </span>
              </span>
              <div v-if="selectedVariations.length > 0" class="flex gap-2">
                <Button 
                  label="Bulk Activate" 
                  icon="pi pi-check" 
                  severity="success"
                  size="small"
                  @click="bulkUpdateStatus(true)"
                />
                <Button 
                  label="Bulk Deactivate" 
                  icon="pi pi-times" 
                  severity="danger"
                  size="small"
                  outlined
                  @click="bulkUpdateStatus(false)"
                />
              </div>
            </div>
          </template>

          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-th-large text-6xl text-gray-300"></i>
              <p class="text-gray-600 mt-4">No variations found</p>
            </div>
          </template>

          <Column selectionMode="multiple" headerStyle="width: 3rem" :exportable="false"></Column>

          <Column field="variation_sku" header="SKU" sortable frozen>
            <template #body="{ data }">
              <span class="font-mono text-sm font-semibold">{{ data.variation_sku }}</span>
            </template>
          </Column>

          <Column field="product.product_name" header="Product" sortable>
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-gray-900">{{ data.product?.product_name }}</p>
                <p class="text-xs text-gray-500 font-mono">{{ data.product?.sku }}</p>
              </div>
            </template>
          </Column>

          <Column field="variation_name" header="Variation" sortable>
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <div 
                  v-if="data.color_hex" 
                  :style="{ backgroundColor: data.color_hex }"
                  class="w-6 h-6 rounded border-2 border-gray-300"
                ></div>
                <span class="font-medium">{{ data.variation_name }}</span>
              </div>
            </template>
          </Column>

          <Column header="Attributes">
            <template #body="{ data }">
              <div class="flex flex-wrap gap-1">
                <Tag v-if="data.color" :value="data.color" severity="info" size="small" />
                <Tag v-if="data.size" :value="data.size" severity="secondary" size="small" />
                <Tag v-if="data.material" :value="data.material" severity="success" size="small" />
                <Tag v-if="data.finish" :value="data.finish" severity="warning" size="small" />
              </div>
            </template>
          </Column>

          <Column field="final_price" header="Price" sortable>
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-gray-900">₱{{ formatPrice(data.final_price || 0) }}</p>
                <p v-if="data.price_adjustment !== 0" class="text-xs text-gray-600">
                  {{ data.price_adjustment > 0 ? '+' : '' }}₱{{ formatPrice(data.price_adjustment) }}
                </p>
              </div>
            </template>
          </Column>

          <Column field="stock_quantity" header="Stock" sortable>
            <template #body="{ data }">
              <Badge 
                :value="data.stock_quantity" 
                :severity="getStockSeverity(data.stock_quantity)"
              />
            </template>
          </Column>

          <Column field="weight_kg" header="Weight">
            <template #body="{ data }">
              <span v-if="data.weight_kg" class="text-sm">{{ data.weight_kg }} kg</span>
              <span v-else class="text-sm text-gray-400 italic">N/A</span>
            </template>
          </Column>

          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag 
                :value="data.is_active ? 'Active' : 'Inactive'" 
                :severity="data.is_active ? 'success' : 'secondary'"
              />
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
                  @click="viewVariation(data)"
                />
                <Button 
                  icon="pi pi-pencil" 
                  severity="warning"
                  text 
                  rounded 
                  size="small"
                  v-tooltip.top="'Edit'"
                  @click="router.push({ name: 'merchandising.variations.edit', params: { id: data.id } })"
                />
                <Button 
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
          <i class="pi pi-th-large text-6xl text-gray-300"></i>
          <p class="text-gray-600 mt-4 text-lg">No variations found</p>
          <p class="text-gray-500 text-sm mt-2">Create variations to offer different options for your products</p>
          <Button 
            label="Create Your First Variation" 
            icon="pi pi-plus" 
            class="mt-4" 
            @click="router.push({ name: 'merchandising.variations.create' })"
          />
        </div>
      </template>
    </Card>

    <!-- Bulk Stock Update Dialog -->
    <Dialog 
      v-model:visible="bulkUpdateDialogVisible" 
      header="Bulk Stock Update" 
      :modal="true" 
      class="w-full max-w-md"
    >
      <div class="space-y-4 mt-4">
        <p class="text-sm text-gray-600">Update stock quantities for multiple variations at once</p>
        
        <div class="flex flex-col gap-2">
          <label for="bulk_product" class="text-sm font-semibold text-gray-700">
            Select Product
          </label>
          <Select 
            id="bulk_product"
            v-model="bulkUpdateData.product_id" 
            :options="products" 
            optionLabel="product_name" 
            optionValue="id"
            placeholder="Choose a product" 
            filter
            @change="loadProductVariations"
          />
        </div>

        <div v-if="bulkUpdateData.product_id" class="space-y-3">
          <p class="text-sm font-semibold text-gray-700">Update Stock for Variations:</p>
          <div v-for="variation in productVariations" :key="variation.id" class="flex items-center gap-3 p-3 bg-gray-50 rounded">
            <div class="flex-1">
              <p class="text-sm font-medium">{{ variation.variation_name }}</p>
              <p class="text-xs text-gray-500">Current: {{ variation.stock_quantity }}</p>
            </div>
            <InputNumber 
              v-model="bulkUpdateData.stocks[variation.id]"
              :min="0"
              showButtons
              buttonLayout="horizontal"
              class="w-32"
            />
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="bulkUpdateDialogVisible = false" />
        <Button label="Update Stock" icon="pi pi-check" @click="submitBulkStockUpdate" :loading="updatingStock" />
      </template>
    </Dialog>

    <!-- View Variation Dialog -->
    <Dialog 
      v-model:visible="viewDialogVisible" 
      header="Variation Details" 
      :modal="true" 
      class="w-full max-w-2xl"
    >
      <div v-if="currentVariation" class="space-y-4 mt-4">
        <!-- Variation Info -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-xs text-gray-600 mb-1">SKU</p>
            <p class="text-sm font-semibold font-mono">{{ currentVariation.variation_sku }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-600 mb-1">Product</p>
            <p class="text-sm font-semibold">{{ currentVariation.product?.product_name }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-600 mb-1">Variation Name</p>
            <p class="text-sm font-semibold">{{ currentVariation.variation_name }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-600 mb-1">Status</p>
            <Tag 
              :value="currentVariation.is_active ? 'Active' : 'Inactive'" 
              :severity="currentVariation.is_active ? 'success' : 'secondary'"
            />
          </div>
        </div>

        <!-- Attributes -->
        <div v-if="currentVariation.color || currentVariation.size || currentVariation.material" class="border-t border-gray-200 pt-4">
          <p class="text-sm font-semibold text-gray-700 mb-3">Attributes</p>
          <div class="flex flex-wrap gap-2">
            <div v-if="currentVariation.color" class="flex items-center gap-2 px-3 py-2 bg-blue-50 rounded">
              <div 
                v-if="currentVariation.color_hex"
                :style="{ backgroundColor: currentVariation.color_hex }"
                class="w-5 h-5 rounded border border-gray-300"
              ></div>
              <span class="text-sm"><strong>Color:</strong> {{ currentVariation.color }}</span>
            </div>
            <div v-if="currentVariation.size" class="px-3 py-2 bg-purple-50 rounded">
              <span class="text-sm"><strong>Size:</strong> {{ currentVariation.size }}</span>
            </div>
            <div v-if="currentVariation.material" class="px-3 py-2 bg-green-50 rounded">
              <span class="text-sm"><strong>Material:</strong> {{ currentVariation.material }}</span>
            </div>
            <div v-if="currentVariation.finish" class="px-3 py-2 bg-orange-50 rounded">
              <span class="text-sm"><strong>Finish:</strong> {{ currentVariation.finish }}</span>
            </div>
          </div>
        </div>

        <!-- Pricing -->
        <div class="border-t border-gray-200 pt-4">
          <p class="text-sm font-semibold text-gray-700 mb-3">Pricing</p>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-xs text-gray-600 mb-1">Base Price</p>
              <p class="text-lg font-semibold text-gray-900">₱{{ formatPrice(currentVariation.product?.base_price || 0) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600 mb-1">Price Adjustment</p>
              <p class="text-lg font-semibold" :class="currentVariation.price_adjustment >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ currentVariation.price_adjustment >= 0 ? '+' : '' }}₱{{ formatPrice(currentVariation.price_adjustment || 0) }}
              </p>
            </div>
            <div class="col-span-2">
              <p class="text-xs text-gray-600 mb-1">Final Price</p>
              <p class="text-2xl font-bold text-blue-600">₱{{ formatPrice(currentVariation.final_price || 0) }}</p>
            </div>
          </div>
        </div>

        <!-- Stock & Dimensions -->
        <div class="border-t border-gray-200 pt-4">
          <p class="text-sm font-semibold text-gray-700 mb-3">Stock & Dimensions</p>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <p class="text-xs text-gray-600 mb-1">Stock</p>
              <Badge :value="currentVariation.stock_quantity" :severity="getStockSeverity(currentVariation.stock_quantity)" />
            </div>
            <div>
              <p class="text-xs text-gray-600 mb-1">Weight</p>
              <p class="text-sm font-semibold">{{ currentVariation.weight_kg || 'N/A' }} kg</p>
            </div>
            <div>
              <p class="text-xs text-gray-600 mb-1">Length</p>
              <p class="text-sm font-semibold">{{ currentVariation.length_cm || 'N/A' }} cm</p>
            </div>
            <div>
              <p class="text-xs text-gray-600 mb-1">Width</p>
              <p class="text-sm font-semibold">{{ currentVariation.width_cm || 'N/A' }} cm</p>
            </div>
          </div>
        </div>

        <!-- Additional Info -->
        <div class="border-t border-gray-200 pt-4">
          <p class="text-sm font-semibold text-gray-700 mb-3">Additional Information</p>
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-xs text-gray-600 mb-1">Created</p>
              <p>{{ formatDate(currentVariation.created_at) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600 mb-1">Last Updated</p>
              <p>{{ formatDate(currentVariation.updated_at) }}</p>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <Button 
          label="Edit" 
          icon="pi pi-pencil" 
          @click="$router.push({ name: 'merchandising.variations.edit', params: { id: currentVariation.id } })" 
        />
        <Button label="Close" severity="secondary" outlined @click="viewDialogVisible = false" />
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:visible="deleteDialogVisible" header="Confirm Delete" :modal="true" class="w-96">
      <div class="flex items-center gap-3">
        <i class="pi pi-exclamation-triangle text-4xl text-red-600"></i>
        <div>
          <p class="font-semibold">Are you sure you want to delete this variation?</p>
          <p class="text-sm text-gray-600 mt-1">This action cannot be undone.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="deleteDialogVisible = false" />
        <Button label="Delete" severity="danger" @click="deleteVariation" :loading="deleting" />
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
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import { useRouter } from 'vue-router'

const toast = useToast()
const router = useRouter()
const authStore = useAuthStore()

// State
const variations = ref([])
const products = ref([])
const loading = ref(false)
const deleting = ref(false)
const updatingStock = ref(false)
const deleteDialogVisible = ref(false)
const bulkUpdateDialogVisible = ref(false)
const viewDialogVisible = ref(false)
const currentVariation = ref(null)
const searchQuery = ref('')
const selectedVariations = ref([])
const productVariations = ref([])

const filters = reactive({
  product_id: null,
  stock_status: null,
  is_active: null
})

const bulkUpdateData = reactive({
  product_id: null,
  stocks: {}
})

const stockStatusOptions = ['In Stock', 'Low Stock', 'Out of Stock']

const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]

// Methods
const loadVariations = async () => {
  loading.value = true
  try {
    const params: any = { ...filters }
    if (searchQuery.value) params.search = searchQuery.value

    const response = await merchandisingService.getVariations(params)
    variations.value = response.data.data
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load variations',
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

const loadProductVariations = async () => {
  if (!bulkUpdateData.product_id) return
  
  try {
    const response = await merchandisingService.getVariationsByProduct(bulkUpdateData.product_id)
    productVariations.value = response.data.variations || []
    
    // Initialize stock values
    bulkUpdateData.stocks = {}
    productVariations.value.forEach(v => {
      bulkUpdateData.stocks[v.id] = v.stock_quantity
    })
  } catch (error) {
    console.error('Failed to load product variations:', error)
  }
}

const onSearch = () => {
  loadVariations()
}

const onRowSelect = () => {
  // Handle row selection
}

const onRowUnselect = () => {
  // Handle row deselection
}

const viewVariation = (variation: any) => {
  currentVariation.value = variation
  viewDialogVisible.value = true
}

const openBulkUpdateDialog = () => {
  bulkUpdateData.product_id = null
  bulkUpdateData.stocks = {}
  productVariations.value = []
  bulkUpdateDialogVisible.value = true
}

const submitBulkStockUpdate = async () => {
  if (!bulkUpdateData.product_id) return
  
  updatingStock.value = true
  try {
    const updates = Object.entries(bulkUpdateData.stocks).map(([id, stock]) => ({
      variation_id: parseInt(id),
      stock_quantity: stock
    }))

    await merchandisingService.bulkUpdateStock({ updates })
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Stock quantities updated successfully',
      life: 3000
    })
    
    bulkUpdateDialogVisible.value = false
    loadVariations()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update stock',
      life: 3000
    })
  } finally {
    updatingStock.value = false
  }
}

const bulkUpdateStatus = async (isActive: boolean) => {
  if (selectedVariations.value.length === 0) return
  
  try {
    const ids = selectedVariations.value.map((v: any) => v.id)
    
    for (const id of ids) {
      await merchandisingService.updateVariation(id, { is_active: isActive })
    }
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `${ids.length} variations ${isActive ? 'activated' : 'deactivated'}`,
      life: 3000
    })
    
    selectedVariations.value = []
    loadVariations()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update variations',
      life: 3000
    })
  }
}

const confirmDelete = (variation: any) => {
  currentVariation.value = variation
  deleteDialogVisible.value = true
}

const deleteVariation = async () => {
  deleting.value = true
  try {
    await merchandisingService.deleteVariation(currentVariation.value.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Variation deleted successfully',
      life: 3000
    })
    deleteDialogVisible.value = false
    loadVariations()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete variation',
      life: 3000
    })
  } finally {
    deleting.value = false
  }
}

const getStockSeverity = (stock: number) => {
  if (stock === 0) return 'danger'
  if (stock <= 10) return 'warning'
  return 'success'
}

const formatPrice = (price: number) => {
  return new Intl.NumberFormat('en-PH', { 
    minimumFractionDigits: 2,
    maximumFractionDigits: 2 
  }).format(price)
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
  loadVariations()
  loadProducts()
})
</script>