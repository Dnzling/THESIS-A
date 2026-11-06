<template>
  <div v-if="!selectedType" class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded aria-label="Go back to stock requests" @click="router.push({ name: 'inventory.stock-requests' })" />
      <div>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Create Stock Request</h1>
        <p class="mt-1 text-sm text-slate-500">Choose where the stock should come from. The request will continue through that team's workflow.</p>
      </div>
    </header>

    <div v-if="checkingWarehouse" class="grid gap-4 md:grid-cols-2">
      <Skeleton height="11rem" class="rounded-2xl" />
      <Skeleton height="11rem" class="rounded-2xl" />
    </div>
    <div v-else class="grid gap-4 md:grid-cols-2">
      <button v-if="canCreatePurchase" type="button"
        class="group rounded-2xl border border-slate-200 bg-white p-5 text-left transition hover:-translate-y-0.5 hover:border-orange-300 hover:shadow-lg"
        @click="goToPurchaseOrder">
        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-50 text-orange-600"><i class="pi pi-shopping-bag text-lg" /></span>
        <h2 class="mt-4 text-base font-semibold text-slate-900">Create Purchase Order</h2>
        <p class="mt-1 text-sm leading-6 text-slate-600">Create a direct order from a supplier in Inventory. It will not be routed to Procurement.</p>
        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-orange-700">Continue <i class="pi pi-arrow-right text-xs" /></span>
      </button>
      <button v-if="canCreateTransfer" type="button"
        class="group rounded-2xl border border-slate-200 bg-white p-5 text-left transition hover:-translate-y-0.5 hover:border-orange-300 hover:shadow-lg"
        @click="selectedType = 'transfer'">
        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-50 text-sky-700"><i class="pi pi-warehouse text-lg" /></span>
        <h2 class="mt-4 text-base font-semibold text-slate-900">Request from Warehouse</h2>
        <p class="mt-1 text-sm leading-6 text-slate-600">Request stock already held by a warehouse or another eligible branch.</p>
        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-orange-700">Continue <i class="pi pi-arrow-right text-xs" /></span>
      </button>
      <div v-if="!canCreatePurchase && !canCreateTransfer" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 md:col-span-2">
        You do not have permission to create an Inventory purchase order or a warehouse transfer request.
      </div>
    </div>
  </div>

  <div v-else class="space-y-3">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
      <p class="text-xs font-medium text-slate-500">Stock Request <span class="mx-1">/</span> Warehouse Transfer</p>
    </div>
    <PurchaseRequisitionCreate v-if="isLegacyPrEdit" :key="`legacy-purchase-${route.params.id}`" />
    <TransferCreate v-else :key="`transfer-${route.params.id || 'new'}`" />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { router as inertiaRouter } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import inventoryService from '@/services/inventory.service'
import PurchaseRequisitionCreate from '@/Pages/System/Inventory/PurchaseRequisitions/PurchaseRequisitionCreate.vue'
import TransferCreate from '@/Pages/System/Inventory/Transfers/TransferCreate.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const checkingWarehouse = ref(true)
const hasWarehouseBranch = ref(false)
const canCreatePurchase = computed(() => authStore.hasPermission('inventory.purchase_orders.manage'))
const canCreateTransfer = computed(() => hasWarehouseBranch.value && (authStore.hasPermission('inventory.transfers.manage') || authStore.hasPermission('inventory.transfers.create')))
const isLegacyPrEdit = computed(() => route.name === 'inventory.stock-requests.edit' && Boolean(route.params.id))
const availableType = (type: unknown) => {
  if (type === 'purchase' && canCreatePurchase.value) return 'purchase'
  if (type === 'transfer' && canCreateTransfer.value) return 'transfer'
  return ''
}
const routeRequestType = () => route.name === 'inventory.stock-requests.edit' ? 'purchase' : route.params.type || route.query.type
const selectedType = ref(routeRequestType() === 'transfer' ? availableType('transfer') : '')

const goToPurchaseOrder = () => {
  if (canCreatePurchase.value) inertiaRouter.visit('/inventory/purchase-orders/create')
}

watch(() => [route.name, route.params.type, route.query.type], () => {
  if (checkingWarehouse.value) return
  if (!isLegacyPrEdit.value && routeRequestType() === 'purchase' && canCreatePurchase.value) {
    goToPurchaseOrder()
    return
  }
  selectedType.value = isLegacyPrEdit.value ? 'purchase' : availableType(routeRequestType() === 'transfer' ? 'transfer' : '')
})

onMounted(async () => {
  try {
    const response = await inventoryService.getBranches()
    const payload = response?.data ?? response
    const branches = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : []
    hasWarehouseBranch.value = branches.some((branch: any) => String(branch.branch_type || '').toLowerCase() === 'warehouse')
  } catch {
    hasWarehouseBranch.value = false
  } finally {
    checkingWarehouse.value = false
  }

  const requestedType = routeRequestType()
  if (!isLegacyPrEdit.value && requestedType === 'purchase' && canCreatePurchase.value) {
    goToPurchaseOrder()
    return
  }
  selectedType.value = isLegacyPrEdit.value ? 'purchase' : availableType(requestedType)
  if (!hasWarehouseBranch.value && canCreatePurchase.value && !selectedType.value) goToPurchaseOrder()
})
</script>
