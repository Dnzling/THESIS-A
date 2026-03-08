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

const loadSupplier = async () => {
  loading.value = true
  try {
    const response = await procurementService.getSupplier(supplierId)
    supplier.value = response.data || null
  } catch (error) {
    console.error('Failed to load supplier details', error)
    supplier.value = null
  } finally {
    loading.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'active') return 'success'
  if (status === 'blacklisted') return 'danger'
  return 'secondary'
}

onMounted(() => {
  loadSupplier()
})
</script>
