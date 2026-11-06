<template>
  <div class="mx-auto max-w-6xl space-y-5 px-4 py-5 sm:px-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded aria-label="Back to stock returns"
          @click="router.back()" />
        <div>
          <h1 class="text-xl font-bold text-slate-900">{{ stockReturn?.return_number || 'Stock Return Details' }}</h1>
          <p class="mt-1 text-sm text-slate-500">Review returned items and destination information.</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Tag v-if="stockReturn" :value="humanize(stockReturn.status)" :severity="statusSeverity(stockReturn.status)" />
        <Button label="Print Return Note" icon="pi pi-print" severity="secondary" outlined rounded size="small"
          :disabled="loading || !stockReturn" :loading="printing" @click="printReturn" />
      </div>
    </div>

    <div v-if="loading" class="space-y-4">
      <Skeleton height="9rem" />
      <Skeleton height="18rem" />
    </div>
    <Message v-else-if="error" severity="error" :closable="false">{{ error }}</Message>
    <template v-else-if="stockReturn">
      <Card>
        <template #title><span class="text-base">Return Information</span></template>
        <template #content>
          <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div><p class="field-label">Type</p><p class="field-value">{{ humanize(stockReturn.return_type) }}</p></div>
            <div><p class="field-label">From Branch</p><p class="field-value">{{ stockReturn.from_branch?.name || '-' }}</p></div>
            <div><p class="field-label">Destination</p><p class="field-value">{{ stockReturn.supplier?.supplier_name || stockReturn.to_branch?.name || '-' }}</p></div>
            <div><p class="field-label">Requested Date</p><p class="field-value">{{ formatDate(stockReturn.requested_date) }}</p></div>
            <div><p class="field-label">Return Reason</p><p class="field-value">{{ humanize(stockReturn.return_reason) }}</p></div>
            <div><p class="field-label">Total Return Value</p><p class="field-value">{{ money(stockReturn.total_value) }}</p></div>
          </div>
          <p v-if="stockReturn.reason_details" class="mt-4 whitespace-pre-line text-sm text-slate-700">{{ stockReturn.reason_details }}</p>
          <p v-if="stockReturn.notes" class="mt-3 whitespace-pre-line text-sm text-slate-600">{{ stockReturn.notes }}</p>
        </template>
      </Card>

      <Card>
        <template #title><span class="text-base">Returned Items ({{ stockReturn.items?.length || 0 }})</span></template>
        <template #content>
          <DataTable :value="stockReturn.items || []" rowHover size="small" responsiveLayout="scroll">
            <template #empty><div class="py-8 text-center text-sm text-slate-500">No returned items recorded.</div></template>
            <Column header="Product">
              <template #body="{ data }">
                <p class="font-semibold text-slate-900">{{ data.product?.product_name || 'Unknown Product' }}</p>
                <p class="text-xs text-slate-500">SKU: {{ data.variation?.variation_sku || data.product?.sku || '-' }}</p>
                <p v-if="data.variation?.variation_name" class="text-xs text-slate-500">{{ data.variation.variation_name }}</p>
              </template>
            </Column>
            <Column field="quantity_returned" header="Quantity" />
            <Column header="Condition"><template #body="{ data }">{{ humanize(data.condition) }}</template></Column>
            <Column header="Unit Value"><template #body="{ data }">{{ money(data.unit_value ?? data.unit_cost) }}</template></Column>
            <Column header="Total Value"><template #body="{ data }">{{ money(data.total_value ?? data.total_cost) }}</template></Column>
            <Column field="notes" header="Notes"><template #body="{ data }">{{ data.notes || '-' }}</template></Column>
          </DataTable>
        </template>
      </Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'

const route = useRoute()
const router = useRouter()
const stockReturn = ref<any>(null)
const loading = ref(true)
const printing = ref(false)
const error = ref('')

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await axiosClient.get(`/api/inventory/returns/${route.params.id}`)
    stockReturn.value = response.data?.data ?? null
    if (!stockReturn.value) throw new Error('Stock return details were not returned.')
  } catch (cause: any) {
    error.value = cause?.response?.data?.message || cause?.message || 'Unable to load this stock return.'
  } finally {
    loading.value = false
  }
}

const printReturn = async () => {
  if (printing.value || !stockReturn.value) return
  printing.value = true
  try {
    const response = await axiosClient.get(`/api/inventory/returns/${route.params.id}/print`, { responseType: 'blob' })
    const url = URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const printWindow = window.open(url, '_blank')
    if (printWindow) printWindow.opener = null
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
  } catch (cause: any) {
    error.value = cause?.response?.data?.message || 'Unable to create the stock return PDF.'
  } finally {
    printing.value = false
  }
}

const humanize = (value?: string) => value ? value.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()) : '-'
const statusSeverity = (status?: string) => ({
  draft: 'secondary', requested: 'warn', approved: 'info', shipped: 'info', received: 'success',
  rejected: 'danger', cancelled: 'secondary',
}[String(status || '').toLowerCase()] || 'secondary')
const formatDate = (value?: string) => value ? new Date(`${value.slice(0, 10)}T00:00:00`).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '-'
const money = (value: unknown) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))

onMounted(load)
</script>

<style scoped>
.field-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; }
.field-value { margin-top: .25rem; font-size: .9rem; font-weight: 600; color: #0f172a; }
</style>
