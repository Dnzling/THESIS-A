<template>
  <div class="mx-auto max-w-7xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Supplier portal</p>
        <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Product Listings</h1>
        <p class="mt-1 text-sm text-slate-500">Products assigned to your supplier account, with tax-inclusive cost pricing.</p>
      </div>
      <div class="flex gap-2">
        <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadListings(1)" />
        <Button icon="pi pi-plus" label="Create Product" severity="warn" size="small" @click="router.push('/supplier-portal/products/create')" />
      </div>
    </header>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 lg:flex-row lg:items-center">
          <div class="w-full lg:w-72">
            <IconField fluid>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search product or SKU" size="small" fluid @input="scheduleSearch" />
            </IconField>
          </div>
          <div class="w-full lg:w-56">
            <Select v-model="filters.store_id" :options="stores" optionLabel="store_name" optionValue="id" placeholder="All linked stores" showClear fluid size="small" @change="loadListings(1)" />
          </div>
          <div class="w-full lg:w-40">
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="All statuses" showClear fluid size="small" @change="loadListings(1)" />
          </div>
          <Button v-if="hasFilters" label="Reset" icon="pi pi-filter-slash" severity="secondary" text size="small" @click="resetFilters" />
        </div>

        <div v-if="loading && !rows.length" class="space-y-2 py-4">
          <Skeleton v-for="item in 6" :key="item" height="3.5rem" borderRadius="0.5rem" />
        </div>

        <DataTable
          v-else
          :value="rows"
          dataKey="id"
          rowHover
          responsiveLayout="scroll"
          class="mt-3 text-sm"
          paginator
          lazy
          :first="(page - 1) * perPage"
          :rows="perPage"
          :totalRecords="total"
          :rowsPerPageOptions="[10, 25, 50]"
          :loading="loading"
          @page="onPage"
        >
          <Column header="Product" style="min-width: 17rem">
            <template #body="{ data }">
              <div class="flex items-center gap-3">
                <img v-if="data.image_url" :src="data.image_url" :alt="data.product_name" class="h-10 w-10 rounded-lg border border-slate-200 object-cover" />
                <div v-else class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-400">
                  <i class="pi pi-box" />
                </div>
                <div class="min-w-0">
                  <p class="truncate font-medium text-slate-900">{{ data.product_name }}</p>
                  <p class="mt-0.5 text-xs text-slate-500">{{ data.category_name || formatType(data.product_type) }}</p>
                </div>
              </div>
            </template>
          </Column>
          <Column header="SKU" style="min-width: 10rem">
            <template #body="{ data }">
              <p class="font-medium text-slate-700">{{ data.supplier_sku || data.product_sku || '—' }}</p>
              <p v-if="data.supplier_sku && data.product_sku" class="text-xs text-slate-400">Catalog: {{ data.product_sku }}</p>
            </template>
          </Column>
          <Column field="store_name" header="Store" style="min-width: 11rem" />
          <Column header="Order Terms" style="min-width: 10rem">
            <template #body="{ data }">
              <p>{{ data.minimum_order_quantity }} {{ data.unit_of_measurement || 'unit' }} minimum</p>
              <p class="text-xs text-slate-500">{{ data.lead_time_days }} lead day{{ data.lead_time_days === 1 ? '' : 's' }}</p>
            </template>
          </Column>
          <Column header="Cost (Tax Included)" style="min-width: 12rem">
            <template #body="{ data }">
              <p class="font-semibold text-slate-900">{{ money(data.cost_including_tax) }}</p>
              <p class="text-xs text-slate-500">Base {{ money(data.base_cost) }} · {{ data.is_tax_exempt ? 'Tax exempt' : `${number(data.tax_rate)}% tax` }}</p>
            </template>
          </Column>
          <Column header="Status" style="width: 8rem">
            <template #body="{ data }"><Badge :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'info' : 'secondary'" /></template>
          </Column>
          <Column header="Actions" style="width: 7rem">
            <template #body="{ data }"><Button label="View" icon="pi pi-eye" outlined rounded size="small" @click="router.push(`/supplier-portal/products/${data.id}`)" /></template>
          </Column>
          <template #empty>
            <div class="py-12 text-center">
              <i class="pi pi-box text-3xl text-slate-300" />
              <p class="mt-3 font-medium text-slate-700">No product listings found</p>
              <p class="mt-1 text-xs text-slate-500">Products linked to your supplier account will appear here.</p>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import { useToast } from 'primevue/usetoast'
import supplierService from '../../../services/supplier.service'

const toast = useToast()
const router = useRouter()
const loading = ref(false)
const rows = ref<any[]>([])
const stores = ref<any[]>([])
const page = ref(1)
const perPage = ref(25)
const total = ref(0)
let searchTimer: ReturnType<typeof setTimeout> | null = null

const filters = reactive({ search: '', store_id: null as number | null, status: null as 'active' | 'inactive' | null })
const statusOptions = [{ label: 'Active', value: 'active' }, { label: 'Inactive', value: 'inactive' }]
const hasFilters = computed(() => Boolean(filters.search || filters.store_id || filters.status))

const loadListings = async (requestedPage = page.value) => {
  loading.value = true
  try {
    const response = await supplierService.getProductListings({
      page: requestedPage,
      per_page: perPage.value,
      search: filters.search || undefined,
      store_id: filters.store_id || undefined,
      status: filters.status || undefined,
    })
    const pagination = response.data || {}
    rows.value = pagination.data || []
    total.value = Number(pagination.total || 0)
    page.value = Number(pagination.current_page || requestedPage)
    stores.value = response.stores || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to load products', detail: error?.response?.data?.message || 'Product listings could not be loaded.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const scheduleSearch = () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => loadListings(1), 350)
}

const onPage = (event: any) => {
  perPage.value = Number(event.rows || 25)
  loadListings(Number(event.page || 0) + 1)
}

const resetFilters = () => {
  filters.search = ''
  filters.store_id = null
  filters.status = null
  loadListings(1)
}

const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const number = (value: unknown) => new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number(value || 0))
const formatType = (value: string) => String(value || 'Product').replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase())

onMounted(() => loadListings(1))
onBeforeUnmount(() => { if (searchTimer) clearTimeout(searchTimer) })
</script>
