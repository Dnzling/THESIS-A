<template>
  <div class="max-w-7xl mx-auto space-y-5 py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push('/supplier-portal/stores')" />
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">Store Details</h1>
          <p class="text-xs text-gray-500 mt-1">Transactions and active contracts with this store.</p>
        </div>
      </div>
      <Button
        v-if="detail?.can_create_contract"
        label="Create Contract"
        icon="pi pi-file-edit"
        size="small"
        @click="goCreateContract"
      />
    </div>

    <Card v-if="loading" class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #content>
        <div class="py-8 text-center text-sm text-gray-500">Loading store details...</div>
      </template>
    </Card>

    <template v-else-if="detail">
      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-gray-500">Store Name</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-gray-500">Store Code</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.store_code || '-' }}</p>
            </div>
            <div>
              <p class="text-gray-500">Email</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.email || '-' }}</p>
            </div>
            <div>
              <p class="text-gray-500">Phone</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.phone || '-' }}</p>
            </div>
            <div>
              <p class="text-gray-500">City</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.city || '-' }}</p>
            </div>
            <div>
              <p class="text-gray-500">Province</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.province || '-' }}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-gray-500">Address</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.address || '-' }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #header>
          <div class="px-5 pt-5 pb-1 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Active Contracts</h2>
            <Button v-if="detail.can_create_contract" label="Create Contract" icon="pi pi-plus" size="small" outlined @click="goCreateContract" />
          </div>
        </template>
        <template #content>
          <DataTable :value="detail.active_contracts || []" dataKey="id" size="small" stripedRows responsiveLayout="scroll">
            <template #empty>
              <div class="py-5 text-center text-sm text-gray-500">No active contracts for this store.</div>
            </template>
            <Column field="contract_number" header="Contract #" />
            <Column field="contract_title" header="Title" />
            <Column field="start_date" header="Start">
              <template #body="{ data }">{{ formatDate(data.start_date) }}</template>
            </Column>
            <Column field="end_date" header="End">
              <template #body="{ data }">{{ formatDate(data.end_date) }}</template>
            </Column>
            <Column field="status" header="Status" />
          </DataTable>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #header>
          <div class="px-5 pt-5 pb-1">
            <h2 class="font-semibold text-gray-900">PO Transactions</h2>
          </div>
        </template>
        <template #content>
          <DataTable :value="detail.po_transactions || []" dataKey="id" size="small" stripedRows paginator :rows="10" responsiveLayout="scroll">
            <template #empty>
              <div class="py-5 text-center text-sm text-gray-500">No PO transactions found.</div>
            </template>
            <Column header="PO #">
              <template #body="{ data }">{{ data.purchase_order?.po_number || '-' }}</template>
            </Column>
            <Column header="PO Status">
              <template #body="{ data }">{{ data.purchase_order?.status || '-' }}</template>
            </Column>
            <Column field="response" header="Response" />
            <Column field="receipt_status" header="Receipt" />
            <Column field="submitted_at" header="Submitted">
              <template #body="{ data }">{{ formatDateTime(data.submitted_at) }}</template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #header>
          <div class="px-5 pt-5 pb-1">
            <h2 class="font-semibold text-gray-900">RFQ Transactions</h2>
          </div>
        </template>
        <template #content>
          <DataTable :value="detail.rfq_transactions || []" dataKey="id" size="small" stripedRows paginator :rows="10" responsiveLayout="scroll">
            <template #empty>
              <div class="py-5 text-center text-sm text-gray-500">No RFQ transactions found.</div>
            </template>
            <Column header="RFQ #">
              <template #body="{ data }">{{ data.rfq?.rfq_number || '-' }}</template>
            </Column>
            <Column header="RFQ Title">
              <template #body="{ data }">{{ data.rfq?.title || '-' }}</template>
            </Column>
            <Column field="status" header="Feedback Status" />
            <Column field="quoted_price" header="Quoted Price">
              <template #body="{ data }">{{ money(data.quoted_price) }}</template>
            </Column>
            <Column field="submitted_at" header="Submitted">
              <template #body="{ data }">{{ formatDateTime(data.submitted_at) }}</template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </template>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const detail = ref<any>(null)

const formatDate = (value: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (value: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const money = (value: number | string | null) => {
  const n = Number(value || 0)
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(n)
}

const loadDetail = async () => {
  loading.value = true
  try {
    const storeId = Number(route.params.storeId)
    const res = await supplierService.getLinkedStoreDetail(storeId)
    detail.value = res?.data ?? null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load store details.', life: 3500 })
    router.push('/supplier-portal/stores')
  } finally {
    loading.value = false
  }
}

const goCreateContract = () => {
  const supplierId = detail.value?.supplier?.id
  const storeId = detail.value?.store?.id
  router.push({
    name: 'procurement.supplier-contracts.create',
    query: {
      supplier_id: supplierId,
      store_id: storeId,
      from_supplier_portal: '1',
    },
  })
}

onMounted(loadDetail)
</script>
