<template>
  <PurchaseRequisitionDetail v-if="requestType === 'purchase'" :key="`purchase-${route.params.id}`" />
  <TransferDetail v-else-if="requestType === 'transfer'" :key="`transfer-${route.params.id}`" />
  <div v-else class="mx-auto max-w-3xl px-4 py-16 text-center">
    <i class="pi pi-exclamation-circle text-4xl text-amber-500" />
    <h1 class="mt-3 text-lg font-semibold text-slate-900">Request type is missing</h1>
    <p class="mt-1 text-sm text-slate-600">Return to Stock Requests and open the request from its list.</p>
    <Button label="Back to Stock Requests" severity="warn" size="small" class="mt-4" @click="router.push({ name: 'inventory.stock-requests' })" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PurchaseRequisitionDetail from '@/Pages/System/Inventory/PurchaseRequisitions/PurchaseRequisitionDetail.vue'
import TransferDetail from '@/Pages/System/Inventory/Transfers/TransferDetail.vue'

const route = useRoute()
const router = useRouter()
const requestType = computed(() => String(route.params.type || ''))
</script>
