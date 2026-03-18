<template>
  <div class="p-6 space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">PO Finance Approvals</h1>
        <p class="text-gray-500 mt-1">Review and approve purchase orders from procurement</p>
      </div>
    </div>

    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
          <span class="p-input-icon-left md:col-span-2">
            <i class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search PO number" class="w-full" @keyup.enter="loadPOs" />
          </span>
          <Dropdown v-model="filters.status" :options="statusOptions" placeholder="All Status" showClear @change="loadPOs" />
          <Button icon="pi pi-refresh" class="p-button-outlined p-button-rounded" @click="loadPOs" />
        </div>

        <DataTable
          :value="purchaseOrders"
          :loading="loading"
          stripedRows
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <Column field="po_number" header="PO Number" style="width: 15%">
            <template #body="{ data }">
              <span class="font-semibold text-blue-600">{{ data.po_number }}</span>
            </template>
          </Column>
          <Column header="Supplier" style="width: 20%">
            <template #body="{ data }">
              <div>
                <p class="font-semibold">{{ data.supplier?.supplier_name || '-' }}</p>
                <p class="text-xs text-gray-500">{{ data.supplier?.supplier_code || '' }}</p>
              </div>
            </template>
          </Column>
          <Column header="Branch" style="width: 16%">
            <template #body="{ data }">
              <div>
                <p class="font-semibold">{{ data.branch?.name || '-' }}</p>
                <p class="text-xs text-gray-500">{{ data.branch?.branch_code || '' }}</p>
              </div>
            </template>
          </Column>
          <Column header="Order Date" style="width: 14%">
            <template #body="{ data }">
              {{ formatDate(data.order_date) }}
            </template>
          </Column>
          <Column header="Total" style="width: 12%">
            <template #body="{ data }">
              <span class="font-semibold text-green-600">₱ {{ formatNumber(data.total_amount || data.subtotal || 0) }}</span>
            </template>
          </Column>
          <Column header="Status" style="width: 14%">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions" style="width: 10%" headerStyle="text-align: center">
            <template #body="{ data }">
              <div class="flex gap-2 justify-center">
                <Button icon="pi pi-eye" text rounded @click="viewPO(data)" v-tooltip="'View'" />
              </div>
            </template>
          </Column>

          <template #empty>
            <div class="text-center py-8 text-gray-500">No purchase orders found</div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import procurementService from '../../../services/procurement.service'

const router = useRouter()
const loading = ref(false)
const purchaseOrders = ref<any[]>([])

const filters = ref({
  search: '',
  status: 'pending_finance_approval',
})

const statusOptions = [
  { label: 'Pending Finance Approval', value: 'pending_finance_approval' },
  { label: 'Approved', value: 'approved' },
  { label: 'Sent to Supplier', value: 'sent_to_supplier' },
  { label: 'Supplier Accepted', value: 'supplier_accepted' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Rejected (Finance)', value: 'rejected_finance' },
  { label: 'Cancelled', value: 'cancelled' },
]

const loadPOs = async () => {
  loading.value = true
  try {
    const params: any = { per_page: 50 }
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.search) params.search = filters.value.search

    const response = await procurementService.getPurchaseOrders(params)
    purchaseOrders.value = response.data?.data || response.data || []
  } finally {
    loading.value = false
  }
}

const viewPO = (po: any) => {
  router.push({ name: 'finance.purchase-orders.detail', params: { id: po.id } })
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatNumber = (value: number) => {
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0)
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const statusSeverity = (status: string) => {
  if (status === 'pending_finance_approval') return 'warning'
  if (status === 'approved') return 'success'
  if (status === 'rejected_finance' || status === 'cancelled') return 'danger'
  return 'info'
}

onMounted(loadPOs)
</script>
