<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Accounts Receivable</h1>
        <p class="mt-0.5 text-sm text-gray-500">Customer invoices and collections from Sales and Ecommerce.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Pending</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100">
                <i class="pi pi-clock text-sm text-orange-600"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.pendingCount }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Paid</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                <i class="pi pi-check-circle text-sm text-green-600"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.paidCount }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Overdue</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                <i class="pi pi-exclamation-circle text-sm text-red-600"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.overdueCount }}</p>
          </div>
        </template>
      </Card>

      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm bg-linear-to-br">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider">Outstanding Amount</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/20">
                <i class="pi pi-credit-card text-sm"></i>
              </div>
            </div>
            <p class="text-xl font-bold">₱{{ formatMoney(stats.outstandingAmount) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6">
          <h2 class="text-lg font-semibold text-gray-900">Filter Receivables</h2>
        </div>
      </template>

      <template #content>
        <div class="p-6 pt-2">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-medium uppercase tracking-wider text-gray-500">Search</label>
              <InputText
                v-model="filters.search"
                placeholder="Search invoice or customer"
                class="w-full"
                @keyup.enter="loadReceivables"
              />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-medium uppercase tracking-wider text-gray-500">Source</label>
              <Select
                v-model="filters.source"
                :options="sourceOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                fluid
              />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-medium uppercase tracking-wider text-gray-500">Status</label>
              <Select
                v-model="filters.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
                fluid
              />
            </div>
          </div>

          <div class="mt-4 flex justify-end">
            <Button icon="pi pi-refresh" label="Apply Filters" :loading="loading" @click="loadReceivables" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6">
          <h2 class="text-lg font-semibold text-gray-900">Customer Invoices</h2>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-2">
          <DataTable
            :value="receivables"
            :loading="loading"
            stripedRows
            responsiveLayout="scroll"
            class="p-datatable-sm"
            paginator
            :rows="10"
            :rowsPerPageOptions="[5, 10, 20, 50]"
          >
            <Column field="reference" header="Invoice #" style="min-width: 140px">
              <template #body="{ data }">
                <button class="text-sm font-medium text-blue-600 hover:underline" @click="viewDetail(data)">{{ data.reference }}</button>
              </template>
            </Column>

            <Column field="source_type" header="Source" style="width: 120px">
              <template #body="{ data }">
                <Tag
                  :value="data.source_type === 'ecommerce' ? 'Ecommerce' : 'In Store'"
                  :severity="data.source_type === 'ecommerce' ? 'info' : 'secondary'"
                  size="small"
                />
              </template>
            </Column>

            <Column field="customer" header="Customer" style="min-width: 180px" />

            <Column field="amount" header="Amount" style="width: 140px">
              <template #body="{ data }">
                <span class="text-sm font-semibold text-green-600">₱ {{ formatMoney(data.amount) }}</span>
              </template>
            </Column>

            <Column field="due_date" header="Created" style="width: 140px">
              <template #body="{ data }">
                <span class="text-sm">{{ formatDate(data.created_at) }}</span>
              </template>
            </Column>

            <Column field="status" header="Status" style="width: 130px">
              <template #body="{ data }">
                <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" size="small" />
              </template>
            </Column>

            <Column header="Actions" style="width: 100px" headerStyle="text-align:center">
              <template #body="{ data }">
                <div class="flex justify-center">
                  <Button icon="pi pi-eye" text rounded size="small" @click="viewDetail(data)" />
                </div>
              </template>
            </Column>

            <template #empty>
              <div class="py-12 text-center text-sm text-gray-500">No customer invoices found.</div>
            </template>
          </DataTable>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import financeService from '../../../services/finance.service'

const loading = ref(false)
const receivables = ref<any[]>([])
const router = useRouter()
const filters = ref({
  search: '',
  source: 'all',
  status: '',
})

const sourceOptions = [
  { label: 'All Sources', value: 'all' },
  { label: 'In Store', value: 'sales' },
  { label: 'Ecommerce', value: 'ecommerce' },
]

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Partial', value: 'partial' },
  { label: 'Paid', value: 'paid' },
  { label: 'Cancelled', value: 'cancelled' },
]

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const statusSeverity = (status: string) => {
  if (status === 'paid') return 'success'
  if (status === 'partial') return 'info'
  if (status === 'pending') return 'warn'
  if (status === 'cancelled') return 'danger'
  return 'secondary'
}

const stats = computed(() => {
  const now = new Date()
  const pendingCount = receivables.value.filter((row) => row.status === 'pending').length
  const paidCount = receivables.value.filter((row) => row.status === 'paid').length
  const overdueCount = receivables.value.filter((row) => {
    if (row.status === 'paid' || !row.due_date) return false
    const due = new Date(row.due_date)
    return !Number.isNaN(due.getTime()) && due < now
  }).length

  const outstandingAmount = receivables.value
    .filter((row) => row.status !== 'paid')
    .reduce((sum, row) => sum + Number(row.amount || 0), 0)

  return {
    pendingCount,
    paidCount,
    overdueCount,
    outstandingAmount,
  }
})

const loadReceivables = async () => {
  loading.value = true
  try {
    const params: any = {
      source: filters.value.source,
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.status) params.status = filters.value.status

    const res = await financeService.getReceivables(params)
    receivables.value = res.data || []
  } finally {
    loading.value = false
  }
}

const viewDetail = (row: any) => {
  if (!row?.id || !row?.source_type) return
  router.push({
    name: 'finance.receivables.detail',
    params: {
      source: row.source_type,
      id: String(row.id),
    },
  })
}

onMounted(loadReceivables)
</script>

<style scoped>
* {
  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

:deep(.p-card) {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
}

:deep(.p-card .p-card-content) {
  padding: 0;
}

:deep(.p-card .p-card-body) {
  padding: 0;
}

:deep(.p-datatable) {
  border-radius: 16px;
  overflow: hidden;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
  background-color: #f9fafb;
  color: #6b7280;
  font-weight: 500;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 1rem 1rem;
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
  padding: 1rem 1rem;
  border-bottom: 1px solid #f3f4f6;
}

:deep(.p-datatable .p-datatable-tbody > tr:hover) {
  background-color: #f9fafb;
}
</style>
