<template>
  <div class="max-w-4xl mx-auto space-y-6 pb-6">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.items' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Item Details</h2>
          <p class="text-sm text-gray-500 mt-1">Inventory item information</p>
        </div>
      </div>
      <div class="flex gap-2">
        <Button
          icon="pi pi-pencil"
          label="Edit"
          severity="warning"
          outlined
          @click="router.push({ name: 'inventory.items.edit', params: { id: itemId } })"
        />
      </div>
    </div>

    <div v-if="loading">
      <Skeleton height="300px" class="rounded-lg" />
    </div>

    <div v-else-if="item" class="space-y-6">
      <!-- Stock Status Badge -->
      <div class="flex justify-end">
        <Tag :value="getStockLabel(item)" :severity="getStockSeverity(item)" class="text-sm" />
      </div>

      <!-- Product Info -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-box text-blue-600"></i>
            <span>Product Information</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-600">Product Name</p>
              <p class="font-semibold text-gray-900">{{ item.product?.product_name || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">SKU</p>
              <p class="font-semibold text-gray-900">{{ item.product?.sku || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Branch</p>
              <p class="font-semibold text-gray-900">{{ item.branch?.name || 'N/A' }}</p>
            </div>
            <div v-if="item.variation">
              <p class="text-xs text-gray-600">Variation</p>
              <p class="font-semibold text-gray-900">{{ item.variation?.name || 'N/A' }}</p>
            </div>
          </div>
        </template>
      </Card>

      <!-- Stock Quantities -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-chart-bar text-emerald-600"></i>
            <span>Stock Quantities</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg text-center">
              <p class="text-xs text-gray-600 mb-1">On Hand</p>
              <p class="text-2xl font-bold text-blue-700">{{ item.quantity_on_hand }}</p>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg text-center">
              <p class="text-xs text-gray-600 mb-1">Reserved</p>
              <p class="text-2xl font-bold text-yellow-700">{{ item.quantity_reserved }}</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg text-center">
              <p class="text-xs text-gray-600 mb-1">Available</p>
              <p class="text-2xl font-bold text-green-700">{{ item.quantity_available }}</p>
            </div>
            <div class="bg-red-50 p-4 rounded-lg text-center">
              <p class="text-xs text-gray-600 mb-1">Damaged</p>
              <p class="text-2xl font-bold text-red-700">{{ item.quantity_damaged }}</p>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg text-center">
              <p class="text-xs text-gray-600 mb-1">Incoming</p>
              <p class="text-2xl font-bold text-purple-700">{{ item.quantity_incoming }}</p>
            </div>
          </div>
        </template>
      </Card>

      <!-- Stock Levels & Costs -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-sliders-h text-orange-600"></i>
            <span>Stock Levels &amp; Valuation</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-600">Reorder Point</p>
              <p class="font-semibold text-gray-900">{{ item.reorder_point }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Reorder Quantity</p>
              <p class="font-semibold text-gray-900">{{ item.reorder_quantity }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Maximum Stock</p>
              <p class="font-semibold text-gray-900">{{ item.maximum_stock }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Safety Stock</p>
              <p class="font-semibold text-gray-900">{{ item.safety_stock }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Unit Cost</p>
              <p class="font-semibold text-gray-900">{{ item.unit_cost ? `₱${Number(item.unit_cost).toFixed(2)}` : 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-600">Total Value</p>
              <p class="font-semibold text-gray-900">{{ item.total_value ? `₱${Number(item.total_value).toFixed(2)}` : 'N/A' }}</p>
            </div>
          </div>
        </template>
      </Card>

      <!-- Location -->
      <Card v-if="item.warehouse_section || item.aisle || item.rack || item.shelf || item.bin_code">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-map-marker text-gray-600"></i>
            <span>Storage Location</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div v-if="item.warehouse_section">
              <p class="text-xs text-gray-600">Section</p>
              <p class="font-semibold text-gray-900">{{ item.warehouse_section }}</p>
            </div>
            <div v-if="item.aisle">
              <p class="text-xs text-gray-600">Aisle</p>
              <p class="font-semibold text-gray-900">{{ item.aisle }}</p>
            </div>
            <div v-if="item.rack">
              <p class="text-xs text-gray-600">Rack</p>
              <p class="font-semibold text-gray-900">{{ item.rack }}</p>
            </div>
            <div v-if="item.shelf">
              <p class="text-xs text-gray-600">Shelf</p>
              <p class="font-semibold text-gray-900">{{ item.shelf }}</p>
            </div>
            <div v-if="item.bin_code">
              <p class="text-xs text-gray-600">Bin Code</p>
              <p class="font-semibold text-gray-900">{{ item.bin_code }}</p>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <!-- Not Found -->
    <div v-else class="text-center py-12">
      <i class="pi pi-exclamation-triangle text-4xl text-gray-400"></i>
      <p class="text-gray-600 mt-2">Item not found</p>
      <Button label="Back to List" icon="pi pi-arrow-left" class="mt-4" @click="router.push({ name: 'inventory.items' })" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const item = ref<any>(null)
const itemId = computed(() => Number(route.params.id))

const getStockLabel = (data: any) => {
  const qty = data.quantity_on_hand ?? 0
  const reorder = data.reorder_point ?? 0
  if (qty <= 0) return 'Out of Stock'
  if (qty <= reorder) return 'Low Stock'
  return 'In Stock'
}

const getStockSeverity = (data: any) => {
  const qty = data.quantity_on_hand ?? 0
  const reorder = data.reorder_point ?? 0
  if (qty <= 0) return 'danger'
  if (qty <= reorder) return 'warning'
  return 'success'
}

const loadItem = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getInventoryItem(itemId.value)
    item.value = response.data?.data || response.data || null

    if (!item.value) {
      toast.add({ severity: 'error', summary: 'Error', detail: 'Item not found', life: 3000 })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load item',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadItem()
})
</script>
