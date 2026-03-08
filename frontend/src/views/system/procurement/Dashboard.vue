<template>
  <div class="space-y-6">
    <div v-if="loading" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Skeleton v-for="i in 4" :key="i" height="120px" class="rounded-lg" />
      </div>
      <Skeleton height="280px" class="rounded-lg" />
    </div>

    <div v-else>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="router.push({ name: 'procurement.suppliers' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Active Suppliers</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ stats.active_suppliers?.count || 0 }}</h3>
              </div>
              <div class="bg-blue-100 p-4 rounded-full">
                <i class="pi pi-users text-3xl text-blue-600"></i>
              </div>
            </div>
          </template>
        </Card>

        <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="router.push({ name: 'procurement.purchase-requisitions' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Pending PR Approvals</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ stats.pending_approvals?.pr_count || 0 }}</h3>
              </div>
              <div class="bg-amber-100 p-4 rounded-full">
                <i class="pi pi-file-edit text-3xl text-amber-600"></i>
              </div>
            </div>
          </template>
        </Card>

        <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="router.push({ name: 'procurement.purchase-orders' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Active Purchase Orders</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ stats.active_pos?.count || 0 }}</h3>
                <p class="text-xs text-gray-600 mt-1">₱{{ formatNumber(stats.active_pos?.total_value || 0) }}</p>
              </div>
              <div class="bg-indigo-100 p-4 rounded-full">
                <i class="pi pi-shopping-cart text-3xl text-indigo-600"></i>
              </div>
            </div>
          </template>
        </Card>

        <Card class="hover:shadow-lg transition-shadow cursor-pointer" @click="router.push({ name: 'procurement.payments' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Pending Payments</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ stats.pending_payments?.count || 0 }}</h3>
                <p class="text-xs text-gray-600 mt-1">₱{{ formatNumber(stats.pending_payments?.total_amount || 0) }}</p>
              </div>
              <div class="bg-green-100 p-4 rounded-full">
                <i class="pi pi-wallet text-3xl text-green-600"></i>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Card class="lg:col-span-2">
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-history text-blue-600"></i>
              <span>Pending Approvals</span>
            </div>
          </template>
          <template #content>
            <DataTable :value="pendingRows" class="p-datatable-sm" stripedRows>
              <Column field="type" header="Type" />
              <Column field="count" header="Count" />
            </DataTable>
          </template>
        </Card>

        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-bolt text-yellow-600"></i>
              <span>Quick Actions</span>
            </div>
          </template>
          <template #content>
            <div class="space-y-2">
              <Button label="Add Supplier" icon="pi pi-plus" class="w-full" @click="router.push({ name: 'procurement.suppliers.create' })" />
              <Button label="Create PR" icon="pi pi-file" severity="secondary" class="w-full" @click="router.push({ name: 'procurement.purchase-requisitions.create' })" />
              <Button label="Create RFQ" icon="pi pi-send" severity="info" class="w-full" @click="router.push({ name: 'procurement.rfqs.create' })" />
              <Button label="Create PO" icon="pi pi-shopping-cart" severity="success" class="w-full" @click="router.push({ name: 'procurement.purchase-orders.create' })" />
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import procurementService from '../../../services/procurement.service'

const router = useRouter()
const loading = ref(true)
const stats = ref<any>({})

const pendingRows = computed(() => {
  return [
    { type: 'Purchase Requisitions', count: stats.value.pending_approvals?.pr_count || 0 },
    { type: 'Purchase Orders', count: stats.value.pending_approvals?.po_count || 0 }
  ]
})

const loadDashboard = async () => {
  loading.value = true
  try {
    const response = await procurementService.getDashboardStats()
    stats.value = response.data || {}
  } catch (error) {
    console.error('Failed to load procurement dashboard', error)
    stats.value = {}
  } finally {
    loading.value = false
  }
}

const formatNumber = (value: number) => {
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value)
}

onMounted(() => {
  loadDashboard()
})
</script>
