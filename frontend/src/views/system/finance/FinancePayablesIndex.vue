<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-gray-900">Accounts Payable</h1>
        <p class="mt-0.5 text-sm text-gray-500">Track invoices and supplier payment processing.</p>
      </div>
    </div>
  
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Pending Invoices</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100">
                <i class="pi pi-clock text-sm text-orange-600"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.pendingInvoices }}</p>
          </div>
        </template>
      </Card>
  
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Approved Invoices</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                <i class="pi pi-check-circle text-sm text-green-600"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.approvedInvoices }}</p>
          </div>
        </template>
      </Card>
  
      <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
        <template #content>
          <div class="p-5">
            <div class="mb-3 flex items-center justify-between">
              <span class="text-xs font-medium uppercase tracking-wider text-gray-500">Pending Payments</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100">
                <i class="pi pi-wallet text-sm text-blue-600"></i>
              </div>
            </div>
            <p class="text-2xl font-semibold text-gray-900">{{ stats.pendingPayments }}</p>
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
          <h2 class="text-lg font-semibold text-gray-900">Filter Records</h2>
        </div>
      </template>
  
      <template #content>
        <div class="p-6 pt-2">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
            <div class="space-y-2 md:col-span-2">
              <label class="text-xs font-medium uppercase tracking-wider text-gray-500">Search</label>
              <InputText v-model="filters.search" placeholder="Search invoice/payment/supplier" fluid size="small"
                @keyup.enter="reloadAll" />
            </div>
  
            <div class="space-y-2">
              <label class="text-xs font-medium uppercase tracking-wider text-gray-500">Status</label>
              <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
                placeholder="All Statuses" fluid size="small" />
            </div>
  
            <div class="flex items-end">
              <Button fluid icon="pi pi-refresh" label="Refresh" size="small" :loading="loading || loadingPayments"
                @click="reloadAll" />
            </div>
          </div>
        </div>
      </template>
    </Card>
  
    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6">
          <h2 class="text-lg font-semibold text-gray-900">Payables & Supplier Payments</h2>
        </div>
      </template>
  
      <template #content>
        <div class="p-6 pt-2">
          <TabView>
            <TabPanel header="Payables" value="0">
              <DataTable :value="filteredPayables" :loading="loading" stripedRows responsiveLayout="scroll"
                class="p-datatable-sm" paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]">
                <Column field="reference" header="Invoice #" style="min-width: 130px">
                  <template #body="{ data }">
                    <span class="text-sm font-medium text-blue-600">{{ data.reference }}</span>
                  </template>
                </Column>
                <Column field="supplier" header="Supplier" style="min-width: 170px">
                  <template #body="{ data }">
                    <span class="text-sm text-gray-800">{{ data.supplier }}</span>
                  </template>
                </Column>
                <Column field="amount" header="Amount" style="width: 130px">
                  <template #body="{ data }">
                    <span class="text-sm font-semibold text-green-600">₱ {{ formatMoney(data.amount) }}</span>
                  </template>
                </Column>
                <Column field="due_date" header="Due Date" style="width: 120px">
                  <template #body="{ data }">
                    <span class="text-sm">{{ formatDate(data.due_date) }}</span>
                  </template>
                </Column>
                <Column field="status" header="Status" style="width: 130px">
                  <template #body="{ data }">
                    <Tag :value="formatStatus(data.status)" :severity="paymentSeverity(data.status)" size="small" />
                  </template>
                </Column>
                <Column header="Actions" style="width: 110px" headerStyle="text-align: center">
                  <template #body="{ data }">
                    <div class="flex justify-center gap-1">
                      <Button icon="pi pi-eye" text rounded size="small" @click="viewInvoice(data)" />
                      <Button v-if="data.status === 'pending_approval'" icon="pi pi-check" text rounded severity="success"
                        size="small" @click="approveInvoice(data.id)" />
                    </div>
                  </template>
                </Column>
                <template #empty>
                  <div class="py-12 text-center text-sm text-gray-500">No payables found</div>
                </template>
              </DataTable>
            </TabPanel>
  
            <TabPanel header="Supplier Payments" value="1">
              <DataTable :value="filteredSupplierPayments" :loading="loadingPayments" stripedRows
                responsiveLayout="scroll" class="p-datatable-sm" paginator :rows="10"
                :rowsPerPageOptions="[5, 10, 20, 50]">
                <Column field="payment_number" header="Payment #" style="min-width: 130px">
                  <template #body="{ data }">
                    <span class="text-sm font-medium text-blue-600">{{ data.payment_number }}</span>
                  </template>
                </Column>
                <Column field="supplier.supplier_name" header="Supplier" style="min-width: 170px">
                  <template #body="{ data }">
                    <span class="text-sm">{{ data.supplier?.supplier_name || '-' }}</span>
                  </template>
                </Column>
                <Column field="payment_amount" header="Amount" style="width: 130px">
                  <template #body="{ data }">
                    <span class="text-sm font-semibold text-green-600">₱ {{ formatMoney(data.payment_amount) }}</span>
                  </template>
                </Column>
                <Column field="payment_date" header="Date" style="width: 120px">
                  <template #body="{ data }">
                    <span class="text-sm">{{ formatDate(data.payment_date) }}</span>
                  </template>
                </Column>
                <Column field="status" header="Status" style="width: 130px">
                  <template #body="{ data }">
                    <Tag :value="formatStatus(data.status)" :severity="paymentSeverity(data.status)" size="small" />
                  </template>
                </Column>
                <Column header="Actions" style="width: 110px" headerStyle="text-align: center">
                  <template #body="{ data }">
                    <div class="flex justify-center gap-1">
                      <Button v-if="data.status === 'pending_approval'" icon="pi pi-check" text rounded severity="success"
                        size="small" @click="approvePayment(data.id)" />
                      <Button v-if="data.status === 'approved'" icon="pi pi-wallet" text rounded severity="info"
                        size="small" @click="processPayment(data.id)" />
                    </div>
                  </template>
                </Column>
                <template #empty>
                  <div class="py-12 text-center text-sm text-gray-500">No supplier payments found</div>
                </template>
              </DataTable>
            </TabPanel>
          </TabView>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import financeService from '../../../services/finance.service'

const loading = ref(false)
const payables = ref<any[]>([])
const supplierPayments = ref<any[]>([])
const loadingPayments = ref(false)
const router = useRouter()
const filters = ref({
  search: '',
  status: '',
})

const statusOptions = [
  { label: 'All Statuses', value: '' },
  { label: 'Pending Approval', value: 'pending_approval' },
  { label: 'Approved', value: 'approved' },
  { label: 'Completed', value: 'completed' },
]

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH').format(amount)
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const normalize = (value: unknown) => String(value || '').toLowerCase()

const matchesSearch = (row: any, text: string) => {
  if (!text) return true
  const search = normalize(text)
  return [
    row?.reference,
    row?.supplier,
    row?.payment_number,
    row?.supplier?.supplier_name,
  ].some((field) => normalize(field).includes(search))
}

const filteredPayables = computed(() => {
  return payables.value.filter((row) => {
    const statusOk = !filters.value.status || normalize(row?.status) === normalize(filters.value.status)
    const searchOk = matchesSearch(row, filters.value.search)
    return statusOk && searchOk
  })
})

const filteredSupplierPayments = computed(() => {
  return supplierPayments.value.filter((row) => {
    const statusOk = !filters.value.status || normalize(row?.status) === normalize(filters.value.status)
    const searchOk = matchesSearch(row, filters.value.search)
    return statusOk && searchOk
  })
})

const stats = computed(() => {
  const pendingInvoices = payables.value.filter((row) => normalize(row?.status) === 'pending_approval').length
  const approvedInvoices = payables.value.filter((row) => normalize(row?.status) === 'approved').length
  const pendingPayments = supplierPayments.value.filter((row) => normalize(row?.status) === 'pending_approval').length
  const outstandingAmount = payables.value
    .filter((row) => normalize(row?.status) !== 'completed')
    .reduce((sum, row) => sum + Number(row?.amount || 0), 0)

  return {
    pendingInvoices,
    approvedInvoices,
    pendingPayments,
    outstandingAmount,
  }
})

const loadPayables = async () => {
  loading.value = true
  try {
    const res = await financeService.getPayables()
    payables.value = res.data || []
  } finally {
    loading.value = false
  }
}

const loadSupplierPayments = async () => {
  loadingPayments.value = true
  try {
    const res = await financeService.getSupplierPayments({ per_page: 50 })
    supplierPayments.value = res.data?.data || res.data || []
  } finally {
    loadingPayments.value = false
  }
}

const paymentSeverity = (status: string) => {
  if (status === 'approved') return 'success'
  if (status === 'completed') return 'info'
  if (status === 'pending_approval') return 'warning'
  return 'secondary'
}

const approvePayment = async (id: number) => {
  await financeService.approveSupplierPayment(id)
  loadSupplierPayments()
}

const processPayment = async (id: number) => {
  await financeService.processSupplierPayment(id)
  loadSupplierPayments()
}

const approveInvoice = async (id: number) => {
  await financeService.approveInvoice(id)
  loadPayables()
}

const viewInvoice = (row: any) => {
  if (!row?.id) return
  router.push({ name: 'finance.invoices.detail', params: { id: row.id } })
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  if (status === 'pending_approval') return 'Pending'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const reloadAll = () => {
  loadPayables()
  loadSupplierPayments()
}

onMounted(() => {
  reloadAll()
})
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

:deep(.p-tag) {
  border-radius: 9999px;
  font-weight: 500;
}
</style>