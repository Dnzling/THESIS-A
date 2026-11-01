<template>
  <div class="mx-auto max-w-6xl space-y-5 pb-8">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex min-w-0 items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded aria-label="Go back" @click="router.back()" />
        <div class="min-w-0">
          <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">Inventory item</p>
          <h1 class="truncate text-xl font-bold text-slate-900">{{ item?.product?.product_name || 'Stock details' }}</h1>
          <p class="truncate text-sm text-slate-500">
            {{ item?.variation?.variation_name || 'Standard item' }}
            <span class="mx-1">·</span>SKU {{ item?.variation?.variation_sku || item?.product?.sku || '—' }}
          </p>
        </div>
      </div>
      <div v-if="item" class="flex flex-wrap gap-2 sm:justify-end">
        <Button v-if="primary3DModel" label="View 3D" icon="pi pi-cube" severity="secondary" outlined size="small" @click="openView3DModal" />
        <Button label="Reorder rule" icon="pi pi-sliders-h" severity="secondary" outlined size="small" @click="goToReorderRule" />
        <Button v-if="['low_stock', 'out_of_stock'].includes(item.stock_status)" label="Create PR" icon="pi pi-plus" severity="warn" outlined size="small" @click="createPurchaseRequisition" />
        <Button v-if="canUpdateItems" label="Edit item" icon="pi pi-pencil" severity="warn" size="small" @click="router.push({ name: 'inventory.items.edit', params: { id: itemId } })" />
      </div>
    </header>

    <div v-if="loading" class="space-y-4">
      <Skeleton height="10rem" borderRadius="1rem" />
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><Skeleton v-for="index in 4" :key="index" height="6rem" borderRadius="1rem" /></div>
      <Skeleton height="12rem" borderRadius="1rem" />
    </div>

    <div v-else-if="item" class="space-y-5">
      <Card class="overflow-hidden border border-slate-200 shadow-sm">
        <template #content>
          <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
            <div class="grid h-32 w-full shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50 sm:h-36 sm:w-36">
              <img v-if="productImage" :src="productImage" :alt="item.product?.product_name || 'Product image'" class="h-full w-full object-contain" />
              <i v-else class="pi pi-image text-3xl text-slate-300" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <Badge :value="getStockLabel(item.stock_status)" :severity="getStockSeverity(item.stock_status)" />
                <Tag :value="item.variation_id ? 'Variant stock' : 'Standard stock'" severity="secondary" />
                <Tag v-if="item.product?.product_type" :value="getTypeLabel(item.product.product_type)" severity="info" />
              </div>
              <p class="mt-3 text-sm text-slate-600">{{ item.branch?.name || 'Branch not assigned' }}</p>
              <p v-if="item.variation" class="mt-1 text-xs text-slate-500">
                {{ [item.variation.color, item.variation.size, item.variation.material].filter(Boolean).join(' · ') }}
              </p>
              <p class="mt-1 font-mono text-xs text-slate-500">Inventory record #{{ item.id }}</p>
            </div>
          </div>
        </template>
      </Card>

      <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div v-for="metric in stockMetrics" :key="metric.label" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium text-slate-500">{{ metric.label }}</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ metric.value }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ metric.note }}</p>
        </div>
      </section>

      <div class="grid gap-4 lg:grid-cols-2">
        <Card class="border border-slate-200 shadow-sm">
          <template #title><span class="text-base">Reorder settings</span></template>
          <template #content>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-5">
              <div><dt class="text-xs text-slate-500">Reorder point</dt><dd class="mt-1 font-semibold text-slate-900">{{ item.reorder_point ?? 0 }}</dd></div>
              <div><dt class="text-xs text-slate-500">Reorder quantity</dt><dd class="mt-1 font-semibold text-slate-900">{{ item.reorder_quantity ?? 0 }}</dd></div>
              <div><dt class="text-xs text-slate-500">Safety stock</dt><dd class="mt-1 font-semibold text-slate-900">{{ item.safety_stock ?? 0 }}</dd></div>
              <div><dt class="text-xs text-slate-500">Maximum stock</dt><dd class="mt-1 font-semibold text-slate-900">{{ item.maximum_stock ?? '—' }}</dd></div>
            </dl>
          </template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #title><span class="text-base">Branch and storage location</span></template>
          <template #content>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-5">
              <div><dt class="text-xs text-slate-500">Branch</dt><dd class="mt-1 font-medium text-slate-900">{{ item.branch?.name || '—' }}</dd></div>
              <div><dt class="text-xs text-slate-500">Section</dt><dd class="mt-1 font-medium text-slate-900">{{ item.warehouse_section || '—' }}</dd></div>
              <div><dt class="text-xs text-slate-500">Aisle / Rack</dt><dd class="mt-1 font-medium text-slate-900">{{ [item.aisle, item.rack].filter(Boolean).join(' / ') || '—' }}</dd></div>
              <div><dt class="text-xs text-slate-500">Shelf / Bin</dt><dd class="mt-1 font-medium text-slate-900">{{ [item.shelf, item.bin_code].filter(Boolean).join(' / ') || '—' }}</dd></div>
            </dl>
          </template>
        </Card>
      </div>

      <Card class="border border-slate-200 shadow-sm">
        <template #title><span class="text-base">Inventory value</span></template>
        <template #content>
          <div class="grid gap-4 sm:grid-cols-2">
            <div><p class="text-xs text-slate-500">Cost per unit</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ formatCurrency(item.variation?.cost_price ?? item.product?.cost_price ?? item.product?.inventory_cost_price) }}</p></div>
            <div><p class="text-xs text-slate-500">On-hand stock value</p><p class="mt-1 text-lg font-semibold text-slate-900">{{ formatCurrency(Number(item.quantity_on_hand || 0) * Number(item.variation?.cost_price ?? item.product?.cost_price ?? item.product?.inventory_cost_price ?? 0)) }}</p></div>
          </div>
        </template>
      </Card>
    </div>
    <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-12 text-center text-sm text-slate-500">Inventory item not found.</div>

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
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const itemId = Number(route.params.id)
const item = ref<any>(null)
const loading = ref(false)
const reorderRuleId = ref<number | null>(null)
const productImage = computed(() => {
  const assets = Array.isArray(item.value?.product?.assets) ? item.value.product.assets : []
  const asset = assets
    .filter((candidate: any) => ['Image_Main', 'Image_Gallery', 'Image_360'].includes(candidate.asset_type))
    .sort((a: any, b: any) => Number(Boolean(b.is_primary)) - Number(Boolean(a.is_primary))
      || Number(a.display_order || 0) - Number(b.display_order || 0))[0]
  return asset?.thumbnail_url || asset?.url || null
})
const stockMetrics = computed(() => [
  { label: 'On hand', value: Number(item.value?.quantity_on_hand || 0), note: 'Physical quantity recorded' },
  { label: 'Reserved', value: Number(item.value?.quantity_reserved || 0), note: 'Held for current orders' },
  { label: 'Available', value: Number(item.value?.quantity_available || 0), note: 'Ready to allocate or sell' },
  { label: 'Incoming', value: Number(item.value?.quantity_incoming || 0), note: 'Expected replenishment' },
])

const view3DModalVisible = ref(false)
const primary3DModel = ref<any>(null)

const canUpdateItems = computed(() => authStore.hasPermission('inventory.branch_inventory.manage'))

const formatCurrency = (value: any) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount)
}

const getTypeLabel = (type?: string) => {
  const labels: Record<string, string> = {
    finished_good: 'Product',
    supply: 'Supplies',
    raw_material: 'Raw Material',
  }
  return labels[String(type || '').toLowerCase()] || 'Other'
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
    low_stock: 'warn',
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
    const response = await inventoryService.getInventoryItem(itemId, {
      branch_id: route.query.branch_id ? Number(route.query.branch_id) : undefined,
    })
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
