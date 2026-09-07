<template>
  <div class="p-6 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Suppliers</h1>
        <p class="text-xs text-gray-600 mt-1">Manage supplier relationships and purchasing partners</p>
      </div>
      <Button v-if="canManageSuppliers" label="Add Supplier" icon="pi pi-plus" size="small"
        @click="router.push({ name: 'procurement.suppliers.create' })" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <Card v-for="metric in summaryCards" :key="metric.label">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold  uppercase tracking-wide">{{ metric.label }}</p>
              <p class="text-xl font-bold text-gray-900">{{ metric.value }}</p>
            </div>
            <i :class="[metric.icon, metric.color, 'text-3xl']" aria-hidden="true" />
          </div>
        </template>
      </Card>
    </div>
  
    <Card>
      <template #header>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end m-4 mt-6">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" size="small" placeholder="Search supplier" class="w-full"
              @input="onFilterChange" />
          </IconField>
          <Select v-model="filters.status" :options="statuses" optionLabel="label" optionValue="value"
            placeholder="Status" size="small" showClear class="w-full" @change="onFilterChange" />
          <Select v-model="filters.supplier_type" :options="supplierTypes" optionLabel="label" optionValue="value"
            placeholder="Supplier Type" size="small" showClear class="w-full" @change="onFilterChange" />
          <div></div>
          <Button label="Clear Filters" size="small" severity="secondary" outlined @click="resetFilters" />
        </div>
      </template>
      <template #content>
        <DataTable v-if="!loading" :value="suppliers" :loading="loading" class="p-datatable-sm" rowHover
          responsiveLayout="scroll" paginator lazy :rows="filters.per_page" :totalRecords="total"
          :first="(currentPage - 1) * filters.per_page" :rowsPerPageOptions="[10, 15, 25, 50]" @page="onPageChange">
          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-users text-5xl text-gray-300 mb-4"></i>
              <p class="text-lg text-gray-600">No suppliers found</p>
              <p class="text-sm text-gray-500 mt-1">Try adjusting your search or filters.</p>
            </div>
          </template>
  
          <Column field="supplier_name" header="Supplier" style="min-width: 220px">
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-gray-900">{{ data.supplier_name || data.company_name || '-' }}</p>
                <p class="text-xs text-gray-500">{{ data.supplier_code || 'No supplier code' }}</p>
              </div>
            </template>
          </Column>
          <Column header="Contact" style="min-width: 180px">
            <template #body="{ data }">
              <div>
                <p class="text-sm text-gray-800">{{ data.contact_person || '-' }}</p>
                <p class="text-xs text-gray-500">{{ data.phone || data.mobile || 'No phone' }}</p>
              </div>
            </template>
          </Column>
          <Column field="email" header="Email" style="min-width: 220px">
            <template #body="{ data }"><span class="text-sm text-gray-700">{{ data.email || '-' }}</span></template>
          </Column>
          <Column field="supplier_type" header="Type" style="min-width: 140px">
            <template #body="{ data }"><span class="text-sm text-gray-700">{{ humanize(data.supplier_type) }}</span></template>
          </Column>
          <Column field="status" header="Status" style="min-width: 130px">
            <template #body="{ data }">
              <Tag :value="humanize(data.status || 'active')" :severity="statusSeverity(data.status || 'active')" />
            </template>
          </Column>
          <Column header="Actions" :frozen="true" alignFrozen="right" style="width: 90px">
            <template #body="{ data }">
              <Button icon="pi pi-eye" outlined rounded
                @click="router.push({ name: 'procurement.suppliers.detail', params: { id: data.id } })"
                v-tooltip="'View Supplier'" />
            </template>
          </Column>
        </DataTable>
        <div v-else class="space-y-2 p-4">
          <Skeleton v-for="row in 6" :key="row" height="32px" />
        </div>
      </template>
  </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import procurementService, { type Supplier } from '../../../../services/procurement.service'
import { useAuthStore } from '../../../../stores/auth'

const router = useRouter()
const authStore = useAuthStore()
const canManageSuppliers = computed(() => authStore.hasPermission('procurement.suppliers.manage'))
const loading = ref(false)
const suppliers = ref<Supplier[]>([])
const total = ref(0)
const currentPage = ref(1)

const filters = reactive({
  search: '',
  status: null as string | null,
  supplier_type: null as string | null,
  page: 1,
  per_page: 15,
})

const statuses = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Blacklisted', value: 'blacklisted' }
]

const supplierTypes = [
  { label: 'Manufacturer', value: 'manufacturer' },
  { label: 'Distributor', value: 'distributor' },
  { label: 'Importer', value: 'importer' },
  { label: 'Wholesaler', value: 'wholesaler' },
  { label: 'Service Provider', value: 'service_provider' },
]

const summaryCards = computed(() => [
  { label: 'Total Suppliers', value: total.value, icon: 'pi pi-users', color: 'text-blue-500' },
  { label: 'Active', value: suppliers.value.filter((supplier: any) => supplier.status === 'active').length, icon: 'pi pi-check-circle', color: 'text-green-500' },
  { label: 'Inactive', value: suppliers.value.filter((supplier: any) => supplier.status === 'inactive').length, icon: 'pi pi-pause-circle', color: 'text-gray-500' },
  { label: 'Blacklisted', value: suppliers.value.filter((supplier: any) => supplier.status === 'blacklisted').length, icon: 'pi pi-ban', color: 'text-red-500' },
])

const loadSuppliers = async () => {
  loading.value = true
  try {
    const response = await procurementService.getSuppliers(filters)
    suppliers.value = response.data?.data || []
    total.value = Number(response.data?.total || suppliers.value.length)
    currentPage.value = currentPage.value || 1
  } catch (error) {
    console.error('Failed to load suppliers', error)
    suppliers.value = []
  } finally {
    loading.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'active') return 'success'
  if (status === 'blacklisted') return 'danger'
  return 'secondary'
}

const onFilterChange = () => {
  filters.page = 1
  currentPage.value = 1
  loadSuppliers()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = null
  filters.supplier_type = null
  filters.page = 1
  currentPage.value = 1
  loadSuppliers()
}

const onPageChange = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  currentPage.value = filters.page
  loadSuppliers()
}

const humanize = (value: string | null | undefined) => {
  if (!value) return 'N/A'
  return value.replace(/_/g, ' ').replace(/\b\w/g, (character) => character.toUpperCase())
}

onMounted(() => {
  loadSuppliers()
})
</script>
