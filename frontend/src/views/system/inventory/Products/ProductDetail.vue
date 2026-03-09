<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ product?.name || 'Product Details' }}</h1>
            <p class="text-gray-600 mt-1">SKU: {{ product?.sku }}</p>
          </div>
          <div class="flex gap-3">
            <Button
              label="Edit"
              severity="secondary"
              @click="editProduct"
              :disabled="loading"
            />
            <Button
              label="Back to List"
              severity="secondary"
              @click="goBack"
              :disabled="loading"
            />
          </div>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>

      <div v-else-if="product" class="space-y-6">
        <!-- Product Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2">
            <Card>
              <template #title>Product Information</template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <p class="text-gray-900">{{ product.sku }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <p class="text-gray-900">{{ product.name }}</p>
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <p class="text-gray-900">{{ product.description || 'No description provided' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <p class="text-gray-900">{{ product.category?.name || 'N/A' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                    <p class="text-gray-900">{{ product.unit?.name || 'N/A' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <Tag
                      :value="product.status"
                      :severity="getStatusSeverity(product.status)"
                      class="capitalize"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Track Serial Numbers</label>
                    <Tag
                      :value="product.track_serial_numbers ? 'Yes' : 'No'"
                      :severity="product.track_serial_numbers ? 'success' : 'secondary'"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Track Batches</label>
                    <Tag
                      :value="product.track_batches ? 'Yes' : 'No'"
                      :severity="product.track_batches ? 'success' : 'secondary'"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <div>
            <Card>
              <template #title>Pricing</template>
              <template #content>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Base Price</label>
                    <p class="text-2xl font-bold text-green-600">
                      ${{ product.base_price?.toFixed(2) || '0.00' }}
                    </p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cost Price</label>
                    <p class="text-gray-900">
                      ${{ product.cost_price?.toFixed(2) || '0.00' }}
                    </p>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Inventory Levels -->
        <Card>
          <template #title>Inventory Levels</template>
          <template #content>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
              <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ product.current_stock || 0 }}</div>
                <div class="text-sm text-gray-600">Current Stock</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-orange-600">{{ product.min_stock_level || 0 }}</div>
                <div class="text-sm text-gray-600">Min Stock Level</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-purple-600">{{ product.max_stock_level || 0 }}</div>
                <div class="text-sm text-gray-600">Max Stock Level</div>
              </div>
              <div class="text-center">
                <div class="text-2xl font-bold text-red-600">{{ product.reorder_point || 0 }}</div>
                <div class="text-sm text-gray-600">Reorder Point</div>
              </div>
            </div>
          </template>
        </Card>

        <!-- Stock Movements -->
        <Card>
          <template #title>Recent Stock Movements</template>
          <template #content>
            <DataTable
              :value="stockMovements"
              :loading="stockMovementsLoading"
              paginator
              :rows="10"
              :rowsPerPageOptions="[5, 10, 25]"
              tableStyle="min-width: 50rem"
              class="p-datatable-sm"
            >
              <Column field="type" header="Type" style="width: 120px">
                <template #body="slotProps">
                  <Tag
                    :value="slotProps.data.type"
                    :severity="getMovementTypeSeverity(slotProps.data.type)"
                    class="capitalize"
                  />
                </template>
              </Column>
              <Column field="quantity" header="Quantity" style="width: 100px">
                <template #body="slotProps">
                  <span :class="slotProps.data.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                    {{ slotProps.data.quantity > 0 ? '+' : '' }}{{ slotProps.data.quantity }}
                  </span>
                </template>
              </Column>
              <Column field="reference" header="Reference" style="width: 150px" />
              <Column field="notes" header="Notes" />
              <Column field="created_at" header="Date" style="width: 150px">
                <template #body="slotProps">
                  {{ formatDate(slotProps.data.created_at) }}
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <!-- Serial Numbers (if tracking enabled) -->
        <Card v-if="product.track_serial_numbers">
          <template #title>Serial Numbers</template>
          <template #content>
            <DataTable
              :value="serialNumbers"
              :loading="serialNumbersLoading"
              paginator
              :rows="10"
              :rowsPerPageOptions="[5, 10, 25]"
              tableStyle="min-width: 50rem"
              class="p-datatable-sm"
            >
              <Column field="serial_number" header="Serial Number" style="width: 200px" />
              <Column field="batch_number" header="Batch" style="width: 150px" />
              <Column field="status" header="Status" style="width: 120px">
                <template #body="slotProps">
                  <Tag
                    :value="slotProps.data.status"
                    :severity="getSerialStatusSeverity(slotProps.data.status)"
                    class="capitalize"
                  />
                </template>
              </Column>
              <Column field="location" header="Location" />
              <Column field="created_at" header="Created" style="width: 150px">
                <template #body="slotProps">
                  {{ formatDate(slotProps.data.created_at) }}
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <!-- Batches (if tracking enabled) -->
        <Card v-if="product.track_batches">
          <template #title>Batches</template>
          <template #content>
            <DataTable
              :value="batches"
              :loading="batchesLoading"
              paginator
              :rows="10"
              :rowsPerPageOptions="[5, 10, 25]"
              tableStyle="min-width: 50rem"
              class="p-datatable-sm"
            >
              <Column field="batch_number" header="Batch Number" style="width: 150px" />
              <Column field="quantity" header="Quantity" style="width: 100px" />
              <Column field="expiry_date" header="Expiry Date" style="width: 120px">
                <template #body="slotProps">
                  {{ slotProps.data.expiry_date ? formatDate(slotProps.data.expiry_date) : 'N/A' }}
                </template>
              </Column>
              <Column field="manufacture_date" header="Manufacture Date" style="width: 150px">
                <template #body="slotProps">
                  {{ slotProps.data.manufacture_date ? formatDate(slotProps.data.manufacture_date) : 'N/A' }}
                </template>
              </Column>
              <Column field="status" header="Status" style="width: 120px">
                <template #body="slotProps">
                  <Tag
                    :value="slotProps.data.status"
                    :severity="getBatchStatusSeverity(slotProps.data.status)"
                    class="capitalize"
                  />
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>

      <div v-else class="text-center py-12">
        <p class="text-gray-500">Product not found</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(true)
const stockMovementsLoading = ref(false)
const serialNumbersLoading = ref(false)
const batchesLoading = ref(false)
const product = ref<any>(null)
const stockMovements = ref<any[]>([])
const serialNumbers = ref<any[]>([])
const batches = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const loadProduct = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getProduct(route.params.id as string)
    if (response.success) {
      product.value = response.data
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load product details',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load product details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadStockMovements = async () => {
  if (!product.value) return

  stockMovementsLoading.value = true
  try {
    const response = await inventoryService.getProductStockMovements(product.value.id)
    if (response.success) {
      stockMovements.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load stock movements', error)
  } finally {
    stockMovementsLoading.value = false
  }
}

const loadSerialNumbers = async () => {
  if (!product.value || !product.value.track_serial_numbers) return

  serialNumbersLoading.value = true
  try {
    const response = await inventoryService.getProductSerialNumbers(product.value.id)
    if (response.success) {
      serialNumbers.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load serial numbers', error)
  } finally {
    serialNumbersLoading.value = false
  }
}

const loadBatches = async () => {
  if (!product.value || !product.value.track_batches) return

  batchesLoading.value = true
  try {
    const response = await inventoryService.getProductBatches(product.value.id)
    if (response.success) {
      batches.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load batches', error)
  } finally {
    batchesLoading.value = false
  }
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'warning'
    case 'discontinued': return 'danger'
    default: return 'secondary'
  }
}

const getMovementTypeSeverity = (type: string) => {
  switch (type) {
    case 'in': return 'success'
    case 'out': return 'danger'
    case 'adjustment': return 'warning'
    default: return 'secondary'
  }
}

const getSerialStatusSeverity = (status: string) => {
  switch (status) {
    case 'available': return 'success'
    case 'sold': return 'info'
    case 'damaged': return 'danger'
    case 'returned': return 'warning'
    default: return 'secondary'
  }
}

const getBatchStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'expired': return 'danger'
    case 'depleted': return 'warning'
    default: return 'secondary'
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const editProduct = () => {
  router.push({ name: 'inventory.products.edit', params: { id: product.value.id } })
}

const goBack = () => {
  router.push({ name: 'inventory.products' })
}

onMounted(async () => {
  await loadProduct()
  if (product.value) {
    loadStockMovements()
    loadSerialNumbers()
    loadBatches()
  }
})
</script>