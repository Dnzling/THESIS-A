<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-800">{{ unit?.name || 'Unit Details' }}</h1>
            <p class="text-gray-600 mt-1">Unit information and associated products</p>
          </div>
          <div class="flex gap-3">
            <Button
              label="Edit"
              severity="secondary"
              @click="editUnit"
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

      <div v-else-if="unit" class="space-y-6">
        <!-- Unit Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2">
            <Card>
              <template #title>Unit Information</template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <p class="text-gray-900">{{ unit.name }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Abbreviation</label>
                    <p class="text-gray-900 font-mono">{{ unit.abbreviation }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <Tag
                      :value="unit.type"
                      :severity="getTypeSeverity(unit.type)"
                      class="capitalize"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Conversion Factor</label>
                    <p class="text-gray-900">{{ unit.conversion_factor || 'N/A' }}</p>
                  </div>
                  <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <p class="text-gray-900">{{ unit.description || 'No description provided' }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <Tag
                      :value="unit.status"
                      :severity="getStatusSeverity(unit.status)"
                      class="capitalize"
                    />
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                    <p class="text-gray-900">{{ unit.display_order || 0 }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                    <p class="text-gray-900">{{ formatDate(unit.created_at) }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Updated</label>
                    <p class="text-gray-900">{{ formatDate(unit.updated_at) }}</p>
                  </div>
                </div>
              </template>
            </Card>
          </div>

          <div>
            <Card>
              <template #title>Statistics</template>
              <template #content>
                <div class="space-y-4">
                  <div>
                    <div class="text-2xl font-bold text-blue-600">{{ unit.products_count || 0 }}</div>
                    <div class="text-sm text-gray-600">Total Products</div>
                  </div>
                  <div>
                    <div class="text-2xl font-bold text-green-600">{{ unit.active_products_count || 0 }}</div>
                    <div class="text-sm text-gray-600">Active Products</div>
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>

        <!-- Associated Products -->
        <Card>
          <template #title>Associated Products</template>
          <template #content>
            <DataTable
              :value="products"
              :loading="productsLoading"
              paginator
              :rows="10"
              :rowsPerPageOptions="[5, 10, 25]"
              tableStyle="min-width: 50rem"
              class="p-datatable-sm"
            >
              <Column field="sku" header="SKU" style="width: 120px" />
              <Column field="name" header="Product Name" style="min-width: 200px" />
              <Column field="category.name" header="Category" style="min-width: 150px" />
              <Column field="base_price" header="Price" style="width: 120px">
                <template #body="slotProps">
                  ${{ slotProps.data.base_price?.toFixed(2) || '0.00' }}
                </template>
              </Column>
              <Column field="current_stock" header="Stock" style="width: 100px">
                <template #body="slotProps">
                  <span :class="getStockClass(slotProps.data)">
                    {{ slotProps.data.current_stock || 0 }}
                  </span>
                </template>
              </Column>
              <Column field="status" header="Status" style="width: 120px">
                <template #body="slotProps">
                  <Tag
                    :value="slotProps.data.status"
                    :severity="getStatusSeverity(slotProps.data.status)"
                    class="capitalize"
                  />
                </template>
              </Column>
              <Column header="Actions" style="width: 100px">
                <template #body="slotProps">
                  <Button
                    icon="pi pi-eye"
                    severity="info"
                    outlined
                    @click="viewProduct(slotProps.data)"
                    v-tooltip.top="'View Product'"
                  />
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>
      </div>

      <div v-else class="text-center py-12">
        <p class="text-gray-500">Unit not found</p>
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
const productsLoading = ref(false)
const unit = ref<any>(null)
const products = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const loadUnit = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getUnit(route.params.id as string)
    if (response.success) {
      unit.value = response.data
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load unit details',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load unit details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadProducts = async () => {
  if (!unit.value) return

  productsLoading.value = true
  try {
    const response = await inventoryService.getProducts({
      unit_id: unit.value.id
    })
    if (response.success) {
      products.value = response.data || []
    }
  } catch (error) {
    console.error('Failed to load products', error)
  } finally {
    productsLoading.value = false
  }
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'warning'
    default: return 'secondary'
  }
}

const getTypeSeverity = (type: string) => {
  switch (type) {
    case 'weight': return 'primary'
    case 'volume': return 'info'
    case 'length': return 'success'
    case 'area': return 'warning'
    case 'count': return 'danger'
    default: return 'secondary'
  }
}

const getStockClass = (product: any) => {
  const stock = product.current_stock || 0
  const minStock = product.min_stock_level || 0

  if (stock <= minStock) return 'text-red-600 font-medium'
  if (stock <= minStock * 1.5) return 'text-orange-600 font-medium'
  return 'text-green-600'
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

const editUnit = () => {
  router.push({ name: 'inventory.units.edit', params: { id: unit.value.id } })
}

const goBack = () => {
  router.push({ name: 'inventory.units' })
}

const viewProduct = (product: any) => {
  router.push({ name: 'inventory.products.detail', params: { id: product.id } })
}

onMounted(async () => {
  await loadUnit()
  if (unit.value) {
    loadProducts()
  }
})
</script>