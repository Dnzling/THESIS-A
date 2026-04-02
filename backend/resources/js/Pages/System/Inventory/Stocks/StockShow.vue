<template>
  <div class="max-w-6xl mx-auto space-y-6 pb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.items' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Stock Details</h2>
          <p class="text-sm text-gray-500 mt-1">View stock levels and take quick actions</p>
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <Button
          v-if="primary3DModel"
          label="View 3D"
          icon="pi pi-cube"
          severity="info"
          @click="openView3DModal"
        />
        <Button
          label="Manage Reorder Rule"
          icon="pi pi-sliders-h"
          severity="info"
          outlined
          @click="goToReorderRule"
        />
        <Button
          v-if="['low_stock', 'out_of_stock'].includes(item?.stock_status)"
          label="Create PR"
          icon="pi pi-plus-circle"
          severity="help"
          outlined
          @click="createPurchaseRequisition"
        />
        <Button
          v-if="canUpdateItems"
          label="Edit"
          icon="pi pi-pencil"
          severity="warning"
          @click="router.push({ name: 'inventory.items.edit', params: { id: itemId } })"
        />
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-12">
      <ProgressSpinner />
    </div>

    <div v-else-if="item" class="space-y-6">
      <Card>
        <template #content>
          <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
            <div>
              <h3 class="text-2xl font-bold text-gray-900">{{ item.product?.product_name || 'Unknown Product' }}</h3>
              <div class="flex flex-wrap gap-2 mt-2">
                <Tag :value="item.variation?.variation_sku || item.product?.sku || 'N/A'" severity="secondary" class="font-mono" />
                <Tag :value="getStockLabel(item.stock_status)" :severity="getStockSeverity(item.stock_status)" />
                <Tag v-if="item.variation_id" value="Variant" severity="info" />
                <Tag v-else value="Standard" severity="secondary" />
              </div>
              <p class="text-sm text-gray-500 mt-2">
                Branch: {{ item.branch?.name || 'N/A' }}
              </p>
            </div>
            <div class="text-right">
              <p class="text-sm text-gray-500">Available</p>
              <p class="text-3xl font-bold text-gray-900">{{ item.quantity_available ?? 0 }}</p>
              <p class="text-xs text-gray-500 mt-1">On hand: {{ item.quantity_on_hand ?? 0 }}</p>
            </div>
          </div>
        </template>
      </Card>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <template #content>
            <p class="text-xs text-gray-500 mb-1">Reorder Point</p>
            <p class="text-xl font-semibold text-gray-900">{{ item.reorder_point ?? 0 }}</p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500 mb-1">Reorder Quantity</p>
            <p class="text-xl font-semibold text-gray-900">{{ item.reorder_quantity ?? 0 }}</p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500 mb-1">Safety Stock</p>
            <p class="text-xl font-semibold text-gray-900">{{ item.safety_stock ?? 0 }}</p>
          </template>
        </Card>
      </div>

      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-warehouse text-indigo-600"></i>
            <span>Warehouse Location</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <p class="text-xs text-gray-500">Section</p>
              <p class="text-sm font-semibold text-gray-900">{{ item.warehouse_section || '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Aisle</p>
              <p class="text-sm font-semibold text-gray-900">{{ item.aisle || '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Rack</p>
              <p class="text-sm font-semibold text-gray-900">{{ item.rack || '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Shelf</p>
              <p class="text-sm font-semibold text-gray-900">{{ item.shelf || '—' }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-calculator text-emerald-600"></i>
            <span>Costing</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p class="text-xs text-gray-500">Unit Cost</p>
              <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(item.unit_cost) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Average Cost</p>
              <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(item.average_cost) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Total Value</p>
              <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(item.total_value) }}</p>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <Dialog
      v-model:visible="view3DModalVisible"
      :header="primary3DModel?.file_name || '3D Model'"
      :modal="true"
      class="w-full max-w-4xl"
    >
      <div v-if="primary3DModel" class="space-y-4">
        <Model3DPreview
          :model-url="primary3DModel.url"
          :model-format="primary3DModel.model_format"
          :camera-x="primary3DModel?.camera_settings?.angle_x ?? 0"
          :camera-y="primary3DModel?.camera_settings?.angle_y ?? 15"
          :zoom="primary3DModel?.camera_settings?.zoom ?? 1.5"
          height="500px"
        />
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="view3DModalVisible = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'
import merchandisingService from '../../../../services/merchandising.service'
import { useAuthStore } from '../../../../stores/auth'
import Model3DPreview from '@/Components/merchandising/Model3DPreview.vue'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const itemId = Number(route.params.id)
const item = ref<any>(null)
const loading = ref(false)
const reorderRuleId = ref<number | null>(null)

const view3DModalVisible = ref(false)
const primary3DModel = ref<any>(null)

const canUpdateItems = computed(() => authStore.hasPermission('inventory.branch_inventory.manage'))

const formatCurrency = (value: any) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount)
}

const getStockLabel = (status: string) => {
  const labels: Record<string, string> = {
    in_stock: 'In Stock',
    low_stock: 'Low Stock',
    out_of_stock: 'Out of Stock',
    overstock: 'Overstock'
  }
  return labels[status] ?? 'Unknown'
}

const getStockSeverity = (status: string) => {
  const severities: Record<string, string> = {
    in_stock: 'success',
    low_stock: 'warning',
    out_of_stock: 'danger',
    overstock: 'info'
  }
  return severities[status] ?? 'secondary'
}

const extractRows = (payload: any): any[] => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  return []
}

const loadReorderRule = async () => {
  const productId = Number(item.value?.product_id || 0)
  const branchId = Number(item.value?.branch_id || 0)
  if (!productId || !branchId) {
    reorderRuleId.value = null
    return
  }

  try {
    const response = await inventoryService.getReorderRules({
      product_id: productId,
      branch_id: branchId,
      per_page: 1
    })
    const rows = extractRows(response?.data)
    const rule = rows[0]
    reorderRuleId.value = rule?.id ? Number(rule.id) : null
  } catch {
    reorderRuleId.value = null
  }
}

const load3DAssets = async () => {
  const productId = Number(item.value?.product_id || 0)
  if (!productId) return

  try {
    const response = await merchandisingService.get3DData(productId)
    const payload = response?.data ?? response
    const models = payload?.assets_by_type?.['3D_Model'] || []
    primary3DModel.value = models.find((model: any) => model.is_primary) || models[0] || null
  } catch {
    primary3DModel.value = null
  }
}

const loadItem = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getInventoryItem(itemId)
    item.value = response?.data ?? response
    await Promise.all([loadReorderRule(), load3DAssets()])
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to load stock details',
      life: 3000
    })
    router.push({ name: 'inventory.items' })
  } finally {
    loading.value = false
  }
}

const goToReorderRule = () => {
  const productId = Number(item.value?.product_id || 0)
  const branchId = Number(item.value?.branch_id || 0)
  if (!productId) return

  if (reorderRuleId.value) {
    router.push({ name: 'inventory.reorder-rules.edit', params: { id: reorderRuleId.value } })
    return
  }

  router.push({
    name: 'inventory.reorder-rules.create',
    query: {
      product_id: String(productId),
      branch_id: branchId ? String(branchId) : undefined
    }
  })
}

const createPurchaseRequisition = () => {
  if (!item.value) return

  const reorderQty = Number(item.value?.reorder_quantity || 0)
  const reorderPoint = Number(item.value?.reorder_point || 0)
  const available = Number(item.value?.quantity_available || 0)
  const suggestedQty = reorderQty > 0 ? reorderQty : Math.max(1, reorderPoint - available)

  router.push({
    name: 'inventory.requisites.create',
    query: {
      branch_inventory_id: item.value.id,
      branch_id: item.value.branch_id,
      product_id: item.value.product_id,
      variation_id: item.value.variation_id || undefined,
      requested_quantity: String(suggestedQty),
      notes: 'Auto-generated from Branch Inventory low stock.',
    }
  })
}

const openView3DModal = () => {
  if (!primary3DModel.value) {
    toast.add({
      severity: 'warn',
      summary: 'No 3D Model',
      detail: 'No 3D model available for this product.',
      life: 2500
    })
    return
  }
  view3DModalVisible.value = true
}

onMounted(() => {
  loadItem()
})
</script>
