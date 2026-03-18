<template>
  <div class="space-y-6 pb-6">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-4xl font-bold text-gray-900 mb-2">Procurement Dashboard</h1>
      <p class="text-gray-600">Real-time overview of your procurement operations</p>
    </div>

    <div v-if="loading" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Skeleton v-for="i in 4" :key="i" height="140px" class="rounded-lg" />
      </div>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <Skeleton height="320px" class="rounded-lg lg:col-span-2" />
        <Skeleton height="320px" class="rounded-lg" />
      </div>
    </div>

    <div v-else>
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Active Suppliers -->
        <div class="bg-linear-to-br from-blue-50 to-blue-100 rounded-lg border border-blue-200 p-6 hover:shadow-lg transition cursor-pointer"
          @click="router.push({ name: 'procurement.suppliers' })">
          <div class="flex items-start justify-between mb-4">
            <div>
              <p class="text-sm font-medium text-blue-600 uppercase mb-1">Active Suppliers</p>
              <h3 class="text-4xl font-bold text-gray-900">{{ stats.summary?.active_suppliers?.count || 0 }}</h3>
              <p class="text-xs text-blue-700 mt-2">of {{ stats.summary?.active_suppliers?.total || 0 }} total</p>
            </div>
            <div class="bg-blue-600 p-3 rounded-lg">
              <i class="pi pi-users text-2xl text-white"></i>
            </div>
          </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-linear-to-br from-amber-50 to-amber-100 rounded-lg border border-amber-200 p-6 hover:shadow-lg transition cursor-pointer"
          @click="router.push({ name: 'procurement.purchase-requisitions' })">
          <div class="flex items-start justify-between mb-4">
            <div>
              <p class="text-sm font-medium text-amber-600 uppercase mb-1">Pending Approvals</p>
              <h3 class="text-4xl font-bold text-gray-900">{{ stats.summary?.pending_approvals?.total || 0 }}</h3>
              <p class="text-xs text-amber-700 mt-2">{{ stats.summary?.pending_approvals?.pr_count }} PRs, {{ stats.summary?.pending_approvals?.po_count }} POs</p>
            </div>
            <div class="bg-amber-600 p-3 rounded-lg">
              <i class="pi pi-clock text-2xl text-white"></i>
            </div>
          </div>
        </div>

        <!-- Active POs -->
        <div class="bg-linear-to-br from-indigo-50 to-indigo-100 rounded-lg border border-indigo-200 p-6 hover:shadow-lg transition cursor-pointer"
          @click="router.push({ name: 'procurement.purchase-orders' })">
          <div class="flex items-start justify-between mb-4">
            <div>
              <p class="text-sm font-medium text-indigo-600 uppercase mb-1">Active POs</p>
              <h3 class="text-4xl font-bold text-gray-900">{{ stats.summary?.active_pos?.count || 0 }}</h3>
              <p class="text-xs text-indigo-700 mt-2">₱{{ formatCurrency(stats.summary?.active_pos?.total_value || 0) }}</p>
            </div>
            <div class="bg-indigo-600 p-3 rounded-lg">
              <i class="pi pi-shopping-cart text-2xl text-white"></i>
            </div>
          </div>
        </div>

        <!-- Pending Payments -->
        <div class="bg-linear-to-br from-green-50 to-green-100 rounded-lg border border-green-200 p-6 hover:shadow-lg transition cursor-pointer"
          @click="router.push({ name: 'procurement.payments' })">
          <div class="flex items-start justify-between mb-4">
            <div>
              <p class="text-sm font-medium text-green-600 uppercase mb-1">Pending Payments</p>
              <h3 class="text-4xl font-bold text-gray-900">{{ stats.summary?.pending_payments?.count || 0 }}</h3>
              <p class="text-xs text-green-700 mt-2">₱{{ formatCurrency(stats.summary?.pending_payments?.total_amount || 0) }}</p>
            </div>
            <div class="bg-green-600 p-3 rounded-lg">
              <i class="pi pi-wallet text-2xl text-white"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column (2/3) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Top Suppliers -->
          <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900">Top Suppliers</h2>
              <Button label="View All" severity="secondary" text size="small" @click="router.push({ name: 'procurement.suppliers' })" />
            </div>
            <div class="space-y-3" v-if="stats.top_suppliers?.length">
              <div v-for="supplier in stats.top_suppliers" :key="supplier.id"
                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition cursor-pointer"
                @click="router.push({ name: 'procurement.suppliers.detail', params: { id: supplier.id } })">
                <div class="flex-1">
                  <p class="font-semibold text-gray-900">{{ supplier.name }}</p>
                  <p class="text-xs text-gray-600 mt-1">{{ supplier.total_orders }} orders • Rating: <Tag :value="`${supplier.rating}★`" severity="info" /></p>
                </div>
                <div class="text-right">
                  <p class="font-bold text-gray-900">₱{{ formatCurrency(supplier.total_spent) }}</p>
                  <p class="text-xs text-gray-600">Total spent</p>
                </div>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              <i class="pi pi-inbox text-3xl mb-2"></i>
              <p>No suppliers found</p>
            </div>
          </div>

          <!-- Recent POs -->
          <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-xl font-bold text-gray-900">Recent Purchase Orders</h2>
              <Button label="View All" severity="secondary" text size="small" @click="router.push({ name: 'procurement.purchase-orders' })" />
            </div>
            <div class="overflow-x-auto" v-if="stats.recent_pos?.length">
              <table class="w-full text-sm">
                <thead>
                  <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">PO Number</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Supplier</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Amount</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Date</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="po in stats.recent_pos" :key="po.id" class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4 font-mono text-blue-600 cursor-pointer hover:underline">{{ po.number }}</td>
                    <td class="py-3 px-4">{{ po.supplier }}</td>
                    <td class="py-3 px-4 font-semibold">₱{{ formatCurrency(po.amount) }}</td>
                    <td class="py-3 px-4">
                      <Tag :value="po.status" :severity="getPoStatusSeverity(po.status)" />
                    </td>
                    <td class="py-3 px-4 text-gray-600">{{ po.date }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              <i class="pi pi-inbox text-3xl mb-2"></i>
              <p>No purchase orders found</p>
            </div>
          </div>
        </div>

        <!-- Right Column (1/3) -->
        <div class="space-y-6">
          <!-- Key Metrics -->
          <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-6">Key Metrics</h2>
            <div class="space-y-5">
              <div class="flex items-center justify-between p-4 bg-linear-to-r from-purple-50 to-purple-100 rounded-lg border border-purple-200">
                <div>
                  <p class="text-sm text-purple-700 font-medium">Total PO Value</p>
                  <p class="text-2xl font-bold text-purple-900 mt-1">₱{{ formatCurrency(stats.metrics?.total_po_value || 0, 0) }}</p>
                </div>
              </div>

              <div class="flex items-center justify-between p-4 bg-linear-to-r from-green-50 to-green-100 rounded-lg border border-green-200">
                <div>
                  <p class="text-sm text-green-700 font-medium">Completed POs</p>
                  <p class="text-2xl font-bold text-green-900 mt-1">{{ stats.metrics?.completed_pos || 0 }}</p>
                </div>
              </div>

              <div class="flex items-center justify-between p-4 bg-linear-to-r from-yellow-50 to-yellow-100 rounded-lg border border-yellow-200">
                <div>
                  <p class="text-sm text-yellow-700 font-medium">Avg Supplier Rating</p>
                  <p class="text-2xl font-bold text-yellow-900 mt-1">{{ stats.metrics?.avg_supplier_rating || 0 }}★</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-6">Quick Actions</h2>
            <div class="space-y-3">
              <Button label="➕ Add Supplier" class="w-full justify-start" @click="router.push({ name: 'procurement.suppliers.create' })" severity="info" text />
              <Button label="📋 Create PR" class="w-full justify-start" @click="router.push({ name: 'procurement.purchase-requisitions.create' })" severity="secondary" text />
              <Button label="📨 Create RFQ" class="w-full justify-start" @click="router.push({ name: 'procurement.rfqs.create' })" severity="warning" text />
              <Button label="🛒 Create PO" class="w-full justify-start" @click="router.push({ name: 'procurement.purchase-orders.create' })" severity="success" text />
            </div>
          </div>

          <!-- PO Status Breakdown -->
          <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6" v-if="stats.po_status_breakdown?.length">
            <h2 class="text-lg font-bold text-gray-900 mb-6">PO Status</h2>
            <div class="space-y-4">
              <div v-for="status in stats.po_status_breakdown" :key="status.status" class="border-b border-gray-100 last:border-0 pb-4 last:pb-0">
                <div class="flex justify-between items-center mb-2">
                  <span class="text-sm font-medium text-gray-700 capitalize">{{ status.status }}</span>
                  <span class="text-sm font-bold text-gray-900">{{ status.count }}</span>
                </div>
                <ProgressBar :value="status.total / (stats.metrics?.total_po_value || 1) * 100" class="h-2" />
                <p class="text-xs text-gray-500 mt-1">₱{{ formatCurrency(status.total) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import procurementService from '../../../services/procurement.service'

const router = useRouter()
const loading = ref(true)
const stats = ref<any>({})

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

const formatCurrency = (value: number, decimals: number = 2) => {
  if (value >= 1000000) {
    return new Intl.NumberFormat('en-PH', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(value / 1000000) + 'M'
  }
  if (value >= 1000) {
    return new Intl.NumberFormat('en-PH', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(value / 1000) + 'K'
  }
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(value)
}

const getPoStatusSeverity = (status: string): string => {
  const map: Record<string, string> = {
    draft: 'secondary',
    pending_finance_approval: 'warning',
    approved: 'info',
    sent_to_supplier: 'info',
    supplier_accepted: 'success',
    in_transit: 'warning',
    delivered: 'success',
    rejected_finance: 'danger',
    declined_supplier: 'danger',
    cancelled: 'danger',
    revision_requested: 'warning',
  }
  return map[status] || 'secondary'
}

onMounted(() => {
  loadDashboard()
})
</script>

