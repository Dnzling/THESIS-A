<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Goods Receipts</h1>
        <p class="text-gray-600">Track receiving confirmations and quality checks</p>
      </div>
      <Button label="New Receipt" icon="pi pi-plus" class="bg-blue-600 text-white" @click="$router.push('/procurement/goods-receipts/create')" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Receipts</p>
          <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.total }}</p>
          <p class="text-sm text-gray-500 mt-1">Latest compared to total history</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Full Receipts</p>
          <p class="text-3xl font-bold text-green-600 mt-2">{{ stats.full }}</p>
          <p class="text-sm text-gray-500 mt-1">No discrepancies</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Partial</p>
          <p class="text-3xl font-bold text-amber-600 mt-2">{{ stats.partial }}</p>
          <p class="text-sm text-gray-500 mt-1">Short receives</p>
        </template>
      </Card>
      <Card>
        <template #content>
          <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Damaged / Rejected</p>
          <p class="text-3xl font-bold text-red-600 mt-2">{{ stats.damaged }}</p>
          <p class="text-sm text-gray-500 mt-1">Needs attention</p>
        </template>
      </Card>
    </div>

    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <InputText v-model="searchTerm" placeholder="Search by GRN, PO, supplier" class="w-full" />
          <Select v-model="statusFilter" :options="statusOptions" option-label="label" option-value="value" class="w-full" />
          <Button label="Refresh" icon="pi pi-sync" text @click="loadReceipts" />
        </div>

        <DataTable :value="filteredReceipts" :loading="loading" class="p-datatable-sm" stripedRows responsive-layout="scroll">
          <Column field="grn_number" header="GRN" />
          <Column
            field="purchase_order.po_number"
            header="Purchase Order"
            :body="({ data }) => data.purchase_order?.po_number || data.po_number"
          />
          <Column field="supplier_name" header="Supplier" />
          <Column field="receipt_date" header="Receipt Date" />
          <Column header="Receipt Status">
            <template #body="{ data }">
              <Tag :value="data.receipt_status" :severity="statusSeverity(data.receipt_status)" />
            </template>
          </Column>
          <Column header="Qty Items">
            <template #body="{ data }">
              {{ data.items?.length || 0 }}
            </template>
          </Column>
          <Column header="Actions" style="width: 180px">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-eye" severity="info" text rounded size="small" @click="goToDetail(data.id)" v-tooltip="'View detail'" />
                <Button icon="pi pi-file-pdf" severity="secondary" text rounded size="small" @click="printPdf(data.id)" v-tooltip="'Print PDF receipt'" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'
import InputText from 'primevue/inputtext'
const loading = ref(false)

const receipts = ref<any[]>([])
const searchTerm = ref('')
const statusFilter = ref<'all' | 'full' | 'partial' | 'damaged' | 'rejected'>('all')

const statusOptions = [
  { label: 'All', value: 'all' },
  { label: 'Full', value: 'full' },
  { label: 'Partial', value: 'partial' },
  { label: 'Damaged', value: 'damaged' },
  { label: 'Rejected', value: 'rejected' },
]

const stats = computed(() => ({
  total: receipts.value.length,
  full: receipts.value.filter((r) => r.receipt_status === 'full').length,
  partial: receipts.value.filter((r) => r.receipt_status === 'partial').length,
  damaged: receipts.value.filter((r) => ['damaged', 'rejected'].includes(r.receipt_status)).length,
}))

const filteredReceipts = computed(() => {
  return receipts.value.filter((receipt) => {
    const matchesStatus = statusFilter.value === 'all' || receipt.receipt_status === statusFilter.value
    const term = searchTerm.value.toLowerCase()
    const matchesSearch =
      !term ||
      receipt.grn_number?.toLowerCase().includes(term) ||
      receipt.purchase_order?.po_number?.toLowerCase().includes(term) ||
      receipt.supplier_name?.toLowerCase().includes(term)
    return matchesStatus && matchesSearch
  })
})

const loadReceipts = async () => {
  loading.value = true
  try {
    const response = await procurementService.getGoodsReceipts()
    receipts.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to load goods receipts', error)
    receipts.value = []
  } finally {
    loading.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'full') return 'success'
  if (status === 'partial') return 'warning'
  if (['damaged', 'rejected'].includes(status)) return 'danger'
  return 'secondary'
}

const router = useRouter()
const goToDetail = (id: number) => {
  router.push({ name: 'procurement.goods-receipts.detail', params: { id } })
}

const printPdf = async (id: number) => {
  try {
    const response = await procurementService.generateGRPdf(id)
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
  } catch (error) {
    console.error('Failed to download PDF', error)
  }
}

onMounted(() => {
  loadReceipts()
})
</script>
