<template>
  <div class="min-h-screen space-y-6 p-6">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Suppliers</h1>
        <p class="mt-1 text-xs text-gray-600">View the suppliers connected to your store and inventory products.</p>
      </div>
      <Button label="Add Supplier" icon="pi pi-plus" size="small"
        @click="router.push({ name: 'inventory.suppliers.create' })" />
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <Card v-for="metric in summaryCards" :key="metric.label">
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ metric.label }}</p>
              <p class="text-xl font-bold text-gray-900">{{ metric.value }}</p>
            </div>
            <i :class="[metric.icon, metric.color, 'text-3xl']" aria-hidden="true" />
          </div>
        </template>
      </Card>
    </div>

    <Card>
      <template #header>
        <div class="grid grid-cols-1 items-end gap-4 m-4 mt-6 md:grid-cols-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText
              v-model="filters.search"
              class="w-full"
              size="small"
              placeholder="Search supplier"
              @input="onFilterChange"
            />
          </IconField>
          <Select
            v-model="filters.status"
            :options="statuses"
            optionLabel="label"
            optionValue="value"
            placeholder="Status"
            size="small"
            showClear
            class="w-full"
            @change="onFilterChange"
          />
          <Select
            v-model="filters.supplier_type"
            :options="supplierTypes"
            optionLabel="label"
            optionValue="value"
            placeholder="Supplier Type"
            size="small"
            showClear
            class="w-full"
            @change="onFilterChange"
          />
          <Button label="Clear Filters" size="small" severity="secondary" outlined @click="resetFilters" />
        </div>
      </template>

      <template #content>
        <DataTable
          v-if="!loading"
          :value="suppliers"
          class="p-datatable-sm"
          rowHover
          responsiveLayout="scroll"
          paginator
          lazy
          :rows="filters.per_page"
          :totalRecords="total"
          :first="(currentPage - 1) * filters.per_page"
          :rowsPerPageOptions="[10, 15, 25, 50]"
          @page="onPageChange"
        >
          <template #empty>
            <div class="py-12 text-center">
              <i class="pi pi-truck mb-4 text-5xl text-gray-300" />
              <p class="text-lg text-gray-600">No suppliers found</p>
              <p class="mt-1 text-sm text-gray-500">Suppliers linked to this store will appear here.</p>
            </div>
          </template>

          <Column field="supplier_name" header="Supplier" style="min-width: 220px">
            <template #body="{ data }">
              <div class="flex items-center gap-3">
                <img v-if="data.logo_url" :src="data.logo_url" :alt="`${data.supplier_name || 'Supplier'} logo`"
                  class="h-10 w-10 rounded-lg border border-gray-200 object-cover" />
                <div v-else class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-400">
                  <i class="pi pi-building" />
                </div>
                <div>
                  <p class="font-semibold text-gray-900">{{ data.supplier_name || data.company_name || '-' }}</p>
                  <p class="text-xs text-gray-500">{{ data.supplier_code || 'No supplier code' }}</p>
                </div>
              </div>
            </template>
          </Column>
          <Column header="Contact" style="min-width: 180px">
            <template #body="{ data }">
              <p class="text-sm text-gray-800">{{ data.contact_person || '-' }}</p>
              <p class="text-xs text-gray-500">{{ data.phone || data.mobile || 'No phone' }}</p>
            </template>
          </Column>
          <Column field="email" header="Email" style="min-width: 220px">
            <template #body="{ data }">
              <span class="text-sm text-gray-700">{{ data.email || '-' }}</span>
            </template>
          </Column>
          <Column field="supplier_type" header="Type" style="min-width: 140px">
            <template #body="{ data }">
              <span class="text-sm text-gray-700">{{ humanize(data.supplier_type) }}</span>
            </template>
          </Column>
          <Column field="status" header="Status" style="min-width: 110px">
            <template #body="{ data }">
              <Tag :value="humanize(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions" style="width: 90px">
            <template #body="{ data }">
              <Button icon="pi pi-eye" label="View" outlined rounded size="small" v-tooltip="'View Supplier'"
                @click="router.push({ name: 'inventory.suppliers.detail', params: { id: data.id } })" />
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
import inventoryService from '../../../../services/inventory.service'

type Supplier = {
  id: number
  supplier_name?: string
  company_name?: string
  supplier_code?: string
  contact_person?: string
  phone?: string
  mobile?: string
  email?: string
  supplier_type?: string
  rating?: number | string | null
  status?: string
  logo_url?: string | null
}

const router = useRouter()
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
  { label: 'Blacklisted', value: 'blacklisted' },
]

const supplierTypes = [
  { label: 'Manufacturer', value: 'manufacturer' },
  { label: 'Wholesaler', value: 'wholesaler' },
  { label: 'Distributor', value: 'distributor' },
  { label: 'Importer', value: 'importer' },
  { label: 'Local Artisan', value: 'local_artisan' },
]

const summaryCards = computed(() => [
  { label: 'Total Suppliers', value: total.value, icon: 'pi pi-users', color: 'text-blue-500' },
  { label: 'Active on Page', value: suppliers.value.filter(supplier => supplier.status === 'active').length, icon: 'pi pi-check-circle', color: 'text-green-500' },
  { label: 'Inactive on Page', value: suppliers.value.filter(supplier => supplier.status !== 'active').length, icon: 'pi pi-pause-circle', color: 'text-gray-500' },
])

const loadSuppliers = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getSuppliers(filters)
    const paginator = response.data || response
    suppliers.value = Array.isArray(paginator.data) ? paginator.data : []
    total.value = Number(paginator.total || suppliers.value.length)
    currentPage.value = Number(paginator.current_page || filters.page)
  } catch (error) {
    console.error('Failed to load inventory suppliers', error)
    suppliers.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
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

const humanize = (value?: string | null) => {
  if (!value) return 'N/A'
  return value.replace(/_/g, ' ').replace(/\b\w/g, character => character.toUpperCase())
}

const formatRating = (rating?: number | string | null) => {
  const value = Number(rating)
  return Number.isFinite(value) ? `${value.toFixed(1)} / 5` : 'N/A'
}

const statusSeverity = (status?: string) => {
  if (status === 'active') return 'success'
  if (status === 'blacklisted') return 'danger'
  return 'secondary'
}

onMounted(loadSuppliers)
</script>
