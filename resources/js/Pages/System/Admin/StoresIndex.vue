<template>
  <div class="space-y-6">
    <Card class="border  shadow-none">
      <template #content>
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-semibold text-slate-900">Stores</h1>
            <p class="text-sm text-slate-500">Browse all stores, subscription details, sales, and products in one place.</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <Button label="Store Verify" icon="pi pi-shield" severity="warning" @click="goToValidation" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
          <div class="md:col-span-7">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search by store, owner, email, city..." fluid />
            </IconField>
          </div>
          <div class="md:col-span-4">
            <Select
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter status"
              showClear
              fluid
            />
          </div>
          <div class="md:col-span-1 flex justify-end">
            <Button icon="pi pi-filter-slash" text severity="secondary" @click="clearFilters" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <DataTable :value="stores" :loading="loading" dataKey="id" stripedRows paginator :rows="10" :rowsPerPageOptions="[10, 20, 50]">
          <Column field="store_name" header="Store" sortable>
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-slate-900">{{ data.store_name }}</p>
                <p class="text-xs text-slate-500">{{ data.contact_person || 'No contact person' }}</p>
              </div>
            </template>
          </Column>
          <Column field="email" header="Contact">
            <template #body="{ data }">
              <div>
                <p class="text-sm text-slate-800">{{ data.email || '-' }}</p>
                <p class="text-xs text-slate-500">{{ data.contact_number || '-' }}</p>
              </div>
            </template>
          </Column>
          <Column field="city" header="Location" sortable>
            <template #body="{ data }">
              <p class="text-sm text-slate-700">{{ data.city || '-' }}</p>
            </template>
          </Column>
          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <Tag :value="displayStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column field="subscription_tier" header="Subscription" sortable>
            <template #body="{ data }">
              <Tag :value="toTitle(data.subscription_tier)" :severity="tierSeverity(data.subscription_tier)" />
            </template>
          </Column>
          <Column header="Main Person">
            <template #body="{ data }">
              <p class="text-sm text-slate-900">{{ data.contact_person || '-' }}</p>
              <p class="text-xs text-slate-500">{{ data.email || '-' }}</p>
            </template>
          </Column>
        

          <Column header="Actions" style="width: 120px">
            <template #body="{ data }">
    
              <Button icon="pi pi-arrow-right" text severity="info" rounded @click="openDetail(data.id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="showStoreDialog" modal :style="{ width: '980px', maxWidth: '96vw' }" header="Store Overview">
      <div v-if="selectedStore" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Store</p>
            <p class="font-semibold text-slate-900">{{ selectedStore.store_name }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Subscription Duration</p>
            <p class="font-semibold text-slate-900">{{ formatSubscriptionDuration(selectedStore.subscription_ends_at) }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Sales</p>
            <p class="font-semibold text-slate-900">₱{{ formatMoney(selectedStore.total_sales || 0) }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Main Person</p>
            <p class="font-semibold text-slate-900">{{ selectedStore.contact_person || '-' }}</p>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500 mb-2">Place</p>
            <p class="text-sm text-slate-700">{{ selectedStore.address || '-' }}</p>
            <p class="text-sm text-slate-500">{{ selectedStore.city || '-' }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500 mb-2">Store Info</p>
            <p class="text-sm text-slate-700">Status: {{ displayStatus(selectedStore.status) }}</p>
            <p class="text-sm text-slate-700">Products: {{ selectedStore.products_count || 0 }}</p>
            <p class="text-sm text-slate-700">Users: {{ selectedStore.users_count || 0 }}</p>
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 p-4">
          <div class="flex items-center justify-between gap-3 mb-4">
            <div>
              <p class="text-sm font-semibold text-slate-900">Products</p>
              <p class="text-xs text-slate-500">Alphabetical list</p>
            </div>
          </div>
          <DataTable :value="sortedProducts" stripedRows dataKey="id">
            <Column field="product_name" header="Product Name" />
            <Column field="sku" header="SKU" />
            <Column field="status" header="Status">
              <template #body="{ data }">
                <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Dialog from 'primevue/dialog'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const stores = ref<any[]>([])
const showStoreDialog = ref(false)
const selectedStore = ref<any>(null)

const filters = reactive({
  search: '',
  status: null as string | null,
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Pending', value: 'pending' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Verified', value: 'verified' },
  { label: 'Rejected', value: 'rejected' },
]

const toTitle = (value: string | null | undefined) =>
  String(value || 'unknown')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

const displayStatus = (value: string | null | undefined) => {
  const normalized = String(value || '').toLowerCase()
  if (normalized === 'pending') return 'Unverified'
  return toTitle(value)
}

const tierSeverity = (tier: string) => {
  switch (tier) {
    case 'enterprise': return 'help'
    case 'premium': return 'success'
    case 'basic': return 'info'
    default: return 'secondary'
  }
}

const statusSeverity = (status: string) => {
  switch (status) {
    case 'active':
    case 'verified':
      return 'success'
    case 'pending':
    case 'unverified':
      return 'warning'
    case 'rejected':
    case 'suspended':
      return 'danger'
    default:
      return 'secondary'
  }
}

const loadStores = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/admin/stores', {
      params: {
        search: filters.search || undefined,
        status: filters.status || undefined,
        per_page: 100,
      },
    })
    stores.value = response.data?.data?.data || response.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load stores', life: 3000 })
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.status = null
}

const openDetail = (id: number) => {
  router.push({ name: 'admin.stores.detail', params: { id } })
}

const openStoreDialog = async (id: number) => {
  try {
    const response = await axiosClient.get(`/api/admin/stores/${id}`)
    selectedStore.value = response.data?.data?.store || response.data?.data?.store || response.data?.data?.data?.store || response.data?.data?.store || null
    const products = response.data?.data?.products || []
    selectedStore.value = { ...selectedStore.value, products }
    showStoreDialog.value = true
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load store details', life: 3000 })
  }
}

const sortedProducts = computed(() => {
  const products = Array.isArray(selectedStore.value?.products) ? selectedStore.value.products : []
  return [...products].sort((a, b) => String(a.product_name || '').localeCompare(String(b.product_name || '')))
})

const formatMoney = (value: number | string) => {
  const amount = Number(value || 0)
  return amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatSubscriptionDuration = (endsAt: string | null | undefined) => {
  if (!endsAt) return 'No end date'
  return endsAt
}

const goToValidation = () => {
  router.push('/admin/store-validation')
}

watch(() => [filters.search, filters.status], () => {
  loadStores()
})

onMounted(loadStores)
</script>
