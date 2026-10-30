<template>
  <div class="space-y-6 p-4 text-sm md:p-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-xl font-semibold text-slate-900">Customer Refunds</h1>
      <Button v-if="canManage" label="Create Refund" icon="pi pi-plus" severity="warn" size="small" @click="router.push({ name: 'finance.refunds.create' })" />
    </header>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
      <Card v-for="item in summary" :key="item.label" class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ item.label }}</p>
          <Skeleton v-if="loading" class="mt-2" :width="item.money ? '7rem' : '3rem'" height="1.75rem" />
          <p v-else class="mt-2 text-2xl font-semibold text-slate-900">{{ item.money ? currency(item.value) : item.value }}</p>
        </template>
      </Card>
    </div>

    <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
      <template #header>
        <div class="m-4 mt-6 flex flex-col gap-3 md:flex-row md:flex-wrap md:items-end">
          <IconField class="w-full md:w-80"><InputIcon class="pi pi-search" /><InputText v-model="filters.search" placeholder="Search order or customer" fluid size="small" @keyup.enter="load" /></IconField>
          <Select v-model="filters.source" :options="sources" optionLabel="label" optionValue="value" class="w-full md:w-44" size="small" @change="load" />
          <Select v-model="filters.status" :options="statuses" optionLabel="label" optionValue="value" class="w-full md:w-52" size="small" @change="load" />
          <div class="flex items-center gap-2">
            <Button v-if="filters.search || filters.source !== 'all' || filters.status" label="Clear Filter" severity="danger" size="small" @click="clear" />
            <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" :loading="loading" @click="load" />
          </div>
        </div>
      </template>
      <template #content>
        <div v-if="loading" class="space-y-3 px-4 pb-4">
          <div class="grid grid-cols-7 gap-4 border-b border-slate-100 px-3 py-3"><Skeleton v-for="cell in 7" :key="`head-${cell}`" height="0.75rem" /></div>
          <div v-for="row in 6" :key="`refund-${row}`" class="grid grid-cols-7 gap-4 border-b border-slate-50 px-3 py-3"><Skeleton v-for="cell in 7" :key="`cell-${row}-${cell}`" height="1.25rem" /></div>
        </div>
        <DataTable v-else :value="refunds" rowHover paginator :rows="10" :rowsPerPageOptions="[10, 20, 50]" responsiveLayout="scroll" class="p-datatable-sm text-xs">
          <Column field="created_at" header="Date" sortable style="width: 150px"><template #body="{ data }">{{ date(data.created_at) }}</template></Column>
          <Column header="Reference" style="min-width: 170px"><template #body="{ data }"><span class="font-medium text-slate-800">{{ data.order_number || `Refund #${data.id}` }}</span><small class="block text-slate-500">{{ sourceLabel(data.order_type) }}</small></template></Column>
          <Column field="customer_name" header="Customer" />
          <Column header="Amount"><template #body="{ data }"><span class="font-semibold text-rose-600">{{ currency(data.amount) }}</span></template></Column>
          <Column field="reason" header="Reason" />
          <Column header="Status"><template #body="{ data }"><Badge :value="displayStatus(data)" :severity="severity(displayStatusValue(data))" /></template></Column>
          <Column header="Actions" style="width: 110px" headerStyle="text-align:center"><template #body="{ data }"><div class="flex justify-center"><Button icon="pi pi-eye" label="View" outlined rounded size="small" @click="open(data)" /></div></template></Column>
          <template #empty><div class="py-10 text-center text-slate-500">No refund requests found.</div></template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import financeService from '@/services/finance.service'
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

const router = useRouter()
const toast = useToast()
const auth = useAuthStore()
const canManage = auth.hasPermission('finance.refunds.manage')
const loading = ref(true)
const refunds = ref<any[]>([])
const filters = reactive({ search: '', source: 'all', status: '' })
const sources = [{ label: 'All Sources', value: 'all' }, { label: 'Ecommerce Returns', value: 'ecommerce_return' }, { label: 'Ecommerce', value: 'ecommerce' }, { label: 'In Store', value: 'sales' }, { label: 'Manual', value: 'manual' }]
const statuses = [{ label: 'All Statuses', value: '' }, { label: 'For Inspection', value: 'pending_inspection' }, { label: 'Pending Finance Approval', value: 'pending' }, { label: 'Approved - Awaiting Send', value: 'approved' }, { label: 'Awaiting Payout', value: 'awaiting_payout' }, { label: 'Transfer Processing', value: 'processing' }, { label: 'Transfer Failed', value: 'transfer_failed' }, { label: 'Refunded', value: 'sent' }, { label: 'Rejected', value: 'rejected' }]
const currency = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const date = (value: any) => value ? new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '-'
const label = (value: any) => value === 'pending_inspection' ? 'For Inspection' : value === 'awaiting_payout' ? 'Awaiting Payout' : value === 'sent' ? 'Refunded' : String(value || 'pending').replaceAll('_', ' ').replace(/\b\w/g, character => character.toUpperCase())
const sourceLabel = (value: string) => ({ ecommerce_return: 'Ecommerce Return', ecommerce: 'Ecommerce', sales: 'In Store', manual: 'Manual' } as any)[value] || label(value)
const severity = (value: string) => ['approved', 'sent'].includes(value) ? 'success' : ['rejected', 'transfer_failed'].includes(value) ? 'danger' : value === 'processing' ? 'info' : 'warn'
const isLegacyUnpaid = (item: any) => ['simulation', 'paymongo_wallet_test'].includes(item.payout_provider) && item.status === 'sent'
const displayStatusValue = (item: any) => isLegacyUnpaid(item) ? 'awaiting_payout' : item.status
const displayStatus = (item: any) => label(displayStatusValue(item))
const summary = computed(() => [
  { label: 'For Inspection', value: refunds.value.filter(item => item.status === 'pending_inspection').length },
  { label: 'Pending Approval', value: refunds.value.filter(item => item.status === 'pending').length },
  { label: 'Awaiting Payout', value: refunds.value.filter(item => ['approved', 'awaiting_payout'].includes(item.status) || isLegacyUnpaid(item)).length },
  { label: 'Amount To Send', value: refunds.value.filter(item => ['pending', 'approved', 'awaiting_payout'].includes(item.status) || isLegacyUnpaid(item)).reduce((sum, item) => sum + Number(item.amount || 0), 0), money: true },
])
const load = async () => { loading.value = true; try { const response = await financeService.getRefunds({ ...filters }); refunds.value = response?.data || [] } catch (error: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Unable to load refunds.', life: 3000 }) } finally { loading.value = false } }
const clear = () => { filters.search = ''; filters.source = 'all'; filters.status = ''; load() }
const open = (row: any) => router.push({ name: 'finance.refunds.detail', params: { id: row.id } })
onMounted(load)
</script>
