<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Toast />

    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-semibold text-gray-900 tracking-tight">Price Approvals</h1>
        <p class="text-sm text-gray-500 mt-1">Review merchandising price changes before they go live</p>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Pending</p>
            <p class="text-3xl font-semibold text-gray-900">{{ stats.pending }}</p>
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Approved</p>
            <p class="text-3xl font-semibold text-gray-900">{{ stats.approved }}</p>
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Rejected</p>
            <p class="text-3xl font-semibold text-gray-900">{{ stats.rejected }}</p>
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden bg-white">
        <template #content>
          <div class="p-5">
            <p class="text-xs font-medium text-blue-600 uppercase tracking-wider mb-2">Pending Value</p>
            <p class="text-xl font-bold text-gray-900">PHP {{ formatNumber(stats.pendingAmount) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="md:col-span-2 space-y-2">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Search</label>
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search by product name or SKU" fluid @keyup.enter="loadRows" />
            </IconField>
          </div>
          <div class="space-y-2">
            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</label>
            <Select
              v-model="filters.price_approval_status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              fluid
              @change="loadRows"
            />
          </div>
          <div class="flex items-end">
            <Button label="Refresh" icon="pi pi-refresh" severity="info" fluid @click="loadRows" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton v-for="i in 4" :key="i" height="56px" />
        </div>
        <DataTable v-else :value="rows" stripedRows paginator :rows="10" responsiveLayout="scroll">
          <Column field="sku" header="SKU" style="min-width: 130px" />
          <Column field="product_name" header="Product" style="min-width: 220px" />
          <Column header="Current Price" style="min-width: 140px">
            <template #body="{ data }">
              <span class="font-semibold">PHP {{ formatNumber(data.base_price || 0) }}</span>
            </template>
          </Column>
          <Column header="Requested Price" style="min-width: 160px">
            <template #body="{ data }">
              <span class="font-semibold text-blue-700">PHP {{ formatNumber(data.pending_base_price ?? data.base_price ?? 0) }}</span>
            </template>
          </Column>
          <Column header="Status" style="min-width: 140px">
            <template #body="{ data }">
              <Tag :value="labelStatus(data.price_approval_status)" :severity="severityStatus(data.price_approval_status)" />
            </template>
          </Column>
          <Column header="Requested At" style="min-width: 160px">
            <template #body="{ data }">
              {{ formatDate(data.price_proposed_at || data.updated_at) }}
            </template>
          </Column>
          <Column header="Actions" style="width: 100px">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded severity="info" @click="openDetail(data.id)" />
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-10 text-gray-500">No price approvals found.</div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '@/services/merchandising.service'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import Toast from 'primevue/toast'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const rows = ref<any[]>([])
const filters = ref({
  search: '',
  price_approval_status: 'pending',
})

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
]

const stats = computed(() => ({
  pending: rows.value.filter(r => r.price_approval_status === 'pending').length,
  approved: rows.value.filter(r => r.price_approval_status === 'approved').length,
  rejected: rows.value.filter(r => r.price_approval_status === 'rejected').length,
  pendingAmount: rows.value
    .filter(r => r.price_approval_status === 'pending')
    .reduce((sum, r) => sum + Number(r.pending_base_price ?? 0), 0),
}))

const loadRows = async () => {
  loading.value = true
  try {
    const response = await merchandisingService.getProducts({
      per_page: 100,
      search: filters.value.search || undefined,
      price_approval_status: filters.value.price_approval_status || undefined,
    })
    rows.value = response?.data?.data || response?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load approvals', life: 3000 })
    rows.value = []
  } finally {
    loading.value = false
  }
}

const openDetail = (id: number) => {
  router.push({ name: 'finance.price-approvals.detail', params: { id } })
}

const labelStatus = (status?: string) => {
  if (status === 'pending') return 'Pending'
  if (status === 'rejected') return 'Rejected'
  return 'Approved'
}

const severityStatus = (status?: string): 'success' | 'warn' | 'danger' | 'info' | 'secondary' | 'contrast' => {
  if (status === 'pending') return 'warn'
  if (status === 'rejected') return 'danger'
  return 'success'
}

const formatDate = (date?: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatNumber = (value: number) => {
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0)
}

onMounted(loadRows)
</script>
