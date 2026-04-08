<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.suppliers' })" />
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Supplier Details</h2>
          <p class="text-sm text-gray-500 mt-1">View supplier profile and metrics</p>
        </div>
      </div>
      <Button label="Edit" icon="pi pi-pencil" severity="warning" @click="router.push({ name: 'procurement.suppliers.edit', params: { id: supplierId } })" />
    </div>

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Skeleton height="280px" class="rounded-lg" />
      </div>
      <div class="lg:col-span-1">
        <Skeleton height="280px" class="rounded-lg" />
      </div>
    </div>

    <div v-else-if="supplier" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card>
          <template #content>
            <div class="space-y-4">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <h1 class="text-3xl font-bold text-gray-900">{{ supplier.supplier_name }}</h1>
                  <p class="text-sm text-gray-500 mt-1">{{ supplier.company_name || 'No company name' }}</p>
                </div>
                <Tag :value="supplier.status || 'active'" :severity="statusSeverity(supplier.status || 'active')" />
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 rounded-lg">
                <div>
                  <p class="text-xs text-gray-600 mb-1">Contact Person</p>
                  <p class="text-sm font-semibold text-gray-900">{{ supplier.contact_person || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600 mb-1">Phone</p>
                  <p class="text-sm font-semibold text-gray-900">{{ supplier.phone || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600 mb-1">Email</p>
                  <p class="text-sm font-semibold text-gray-900">{{ supplier.email || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-xs text-gray-600 mb-1">Address</p>
                  <p class="text-sm font-semibold text-gray-900">{{ supplier.address || 'N/A' }}</p>
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <div class="lg:col-span-1 space-y-6">
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-chart-line text-blue-600"></i>
              <span>Quick Stats</span>
            </div>
          </template>
          <template #content>
            <div class="space-y-3">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Total Orders</span>
                <span class="font-semibold">{{ supplier.total_orders || 0 }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Rating</span>
                <span class="font-semibold">{{ supplier.rating || 0 }}/5</span>
              </div>
            </div>
          </template>
        </Card>

        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-chart-bar text-emerald-600"></i>
              <span>Performance</span>
            </div>
          </template>
          <template #content>
            <div v-if="performance" class="space-y-3">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">On-Time Rate</span>
                <span class="font-semibold">{{ performance.on_time_delivery_rate || 0 }}%</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Total Spend</span>
                <span class="font-semibold">₱{{ formatCurrency(performance.total_amount_purchased) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Avg Order</span>
                <span class="font-semibold">₱{{ formatCurrency(performance.average_order_value) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Late Deliveries</span>
                <span class="font-semibold">{{ performance.late_deliveries || 0 }}</span>
              </div>
            </div>
            <div v-else class="text-sm text-gray-500">
              No performance metrics available yet.
            </div>
          </template>
        </Card>
      </div>

      <!-- Supplier Contracts Section -->
      <div v-if="contracts && contracts.length > 0" class="lg:col-span-3">
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-file-pdf text-red-600"></i>
              <span>Contracts</span>
              <Tag value="+1" :value="`${contracts.length}`" severity="info" />
            </div>
          </template>
          <template #content>
            <div class="space-y-4">
              <div v-for="contract in contracts" :key="contract.id" class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between gap-3">
                  <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">{{ contract.contract_title }}</h3>
                    <p class="text-sm text-gray-600 mt-1">{{ contract.contract_number }}</p>
                  </div>
                  <Tag :value="contract.status || 'draft'" :severity="contractStatusSeverity(contract.status || 'draft')" />
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 p-3 bg-gray-50 rounded">
                  <div>
                    <p class="text-xs text-gray-600">Contract Type</p>
                    <p class="text-sm font-semibold text-gray-900 capitalize">{{ contract.contract_type?.replace('_', ' ') }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-600">Min Order Value</p>
                    <p class="text-sm font-semibold text-gray-900">₱{{ formatCurrency(contract.minimum_order_value) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-600">Payment Terms</p>
                    <p class="text-sm font-semibold text-gray-900">{{ contract.payment_terms_days }} days</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-600">Discount</p>
                    <p class="text-sm font-semibold text-gray-900">{{ contract.discount_percentage }}%</p>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mt-3">
                  <div>
                    <p class="text-xs text-gray-600">Start Date</p>
                    <p class="text-sm text-gray-900">{{ formatDate(contract.start_date) }}</p>
                  </div>
                  <div>
                    <p class="text-xs text-gray-600">End Date</p>
                    <p class="text-sm text-gray-900">{{ formatDate(contract.end_date) }}</p>
                  </div>
                </div>

                <div v-if="contract.terms_conditions" class="mt-3 p-3 bg-blue-50 rounded border border-blue-200">
                  <p class="text-xs text-blue-700 font-semibold mb-1">Terms & Conditions</p>
                  <p class="text-xs text-blue-700 line-clamp-2">{{ contract.terms_conditions }}</p>
                </div>

                <div class="flex gap-2 justify-end mt-3">
                  <Button 
                    label="View" 
                    icon="pi pi-eye" 
                    text 
                    size="small"
                    @click="router.push({ name: 'procurement.supplier-contracts.detail', params: { id: contract.id } })" />
                  <Button 
                    label="Edit" 
                    icon="pi pi-pencil" 
                    text 
                    size="small"
                    severity="warning"
                    @click="router.push({ name: 'procurement.supplier-contracts.edit', params: { id: contract.id } })" />
                </div>
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()
const supplierId = Number(route.params.id)

const loading = ref(false)
const supplier = ref<any>(null)
const contracts = ref<any[]>([])
const performance = ref<any>(null)

const loadSupplierData = async () => {
  loading.value = true
  try {
    // Fetch supplier details
    const supplierResponse = await procurementService.getSupplier(supplierId)
    supplier.value = supplierResponse.data || null

    // Fetch contracts for this supplier
    try {
      const contractsResponse = await procurementService.getSupplierContracts({
        supplier_id: supplierId,
        per_page: 100
      })
      contracts.value = contractsResponse.data?.data || []
    } catch (error) {
      console.warn('Failed to load contracts', error)
      contracts.value = []
    }

    try {
      const performanceResponse = await procurementService.getSupplierPerformance(supplierId)
      performance.value = performanceResponse.data || null
    } catch (error) {
      console.warn('Failed to load performance metrics', error)
      performance.value = null
    }
  } catch (error) {
    console.error('Failed to load supplier details', error)
    supplier.value = null
    contracts.value = []
    performance.value = null
  } finally {
    loading.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'active') return 'success'
  if (status === 'blacklisted') return 'danger'
  return 'secondary'
}

const contractStatusSeverity = (status: string) => {
  if (status === 'active') return 'success'
  if (status === 'terminated') return 'danger'
  if (status === 'draft') return 'warning'
  if (status === 'expiring') return 'warning'
  return 'secondary'
}

const formatCurrency = (value: any): string => {
  if (!value) return '0.00'
  return Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateString: string): string => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

onMounted(() => {
  loadSupplierData()
})
</script>
