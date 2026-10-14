<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <Toast />
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-chevron-left" rounded outlined severity="secondary" aria-label="Back to transactions" @click="goBack" />
        <div>
          <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Transaction Detail</h1>
          <p class="mt-1 text-sm text-gray-500">{{ transaction?.transaction_number || 'Review this stock movement' }}</p>
        </div>
      </div>
      <Button label="Print" icon="pi pi-print" size="small" severity="secondary" outlined @click="printPage" :disabled="loading || !transaction" />
    </div>

    <div v-if="loading" class="space-y-4">
      <div class="grid gap-4 md:grid-cols-4"><Skeleton v-for="row in 4" :key="row" height="7rem" class="!rounded-2xl" /></div>
      <div class="grid gap-4 md:grid-cols-2"><Skeleton height="17rem" class="!rounded-2xl" /><Skeleton height="17rem" class="!rounded-2xl" /></div>
    </div>

    <div v-else-if="transaction" class="space-y-6">
      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Card>
          <template #content>
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-500">Movement Type</p>
            <Tag :value="formatText(transaction.transaction_type)" :severity="transactionSeverity(transaction.transaction_type)" />
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Date & Time</p>
            <p class="mt-2 font-semibold text-gray-900">{{ formatDateTime(transaction.transaction_date) }}</p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Quantity Change</p>
            <p :class="Number(transaction.quantity_change) >= 0 ? 'text-green-600' : 'text-red-600'" class="font-bold text-lg">
              {{ signedQuantity(transaction.quantity_change) }}
            </p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Movement Value</p>
            <p class="mt-2 text-lg font-semibold text-gray-900">{{ formatCurrency(transaction.total_value) }}</p>
          </template>
        </Card>
      </div>

      <Card>
        <template #title><span class="flex items-center gap-2 text-lg"><i class="pi pi-box text-blue-600"></i> Movement Information</span></template>
        <template #content>
          <div class="grid grid-cols-1 gap-5 text-sm md:grid-cols-2">
            <div>
              <p class="text-xs text-gray-500">Transaction Number</p>
              <p class="mt-1 font-mono font-semibold text-gray-900">{{ transaction.transaction_number || '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Branch</p>
              <p class="mt-1 font-medium text-gray-900">{{ transaction.branch?.name || '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Product</p>
              <p class="mt-1 font-medium text-gray-900">{{ transaction.product?.product_name || 'Product unavailable' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Variation</p>
              <p class="mt-1 font-medium text-gray-900">{{ transaction.variation?.variation_name || 'No variant' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Before / After</p>
              <p class="mt-1 font-medium text-gray-900">{{ formatQuantity(transaction.quantity_before) }} → {{ formatQuantity(transaction.quantity_after) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Unit Cost</p>
              <p class="mt-1 font-medium text-gray-900">{{ formatCurrency(transaction.unit_cost) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Source</p>
              <p class="mt-1 font-medium text-gray-900">{{ formatText(transaction.reference_type) }}{{ transaction.reference_id ? ` #${transaction.reference_id}` : '' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Related Branch</p>
              <p class="mt-1 font-medium text-gray-900">{{ transaction.related_branch?.name || '—' }}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-xs text-gray-500">Notes</p>
              <p class="mt-1 whitespace-pre-wrap text-gray-800">{{ transaction.notes || 'No notes provided.' }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #title><span class="flex items-center gap-2 text-lg"><i class="pi pi-history text-green-600"></i> Audit Trail</span></template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <p class="text-xs text-gray-500">Created By</p>
              <p class="mt-1 font-medium text-gray-900">{{ employeeName(transaction.created_by) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Employee No.</p>
              <p class="mt-1 font-medium text-gray-900">{{ transaction.created_by?.employee_number || '—' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Created At</p>
              <p class="mt-1 font-medium text-gray-900">{{ formatDateTime(transaction.created_at) }}</p>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <Card v-else>
      <template #content>
        <div class="text-center py-8 text-gray-500">No transaction data found.</div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'
import Skeleton from 'primevue/skeleton'
import Toast from 'primevue/toast'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const transaction = ref<any | null>(null)

const transactionId = computed(() => Number(route.params.id || 0))

const normalizeResponseRow = (payload: any) => {
  if (!payload) return null
  if (payload.data && !Array.isArray(payload.data)) return payload.data
  return payload
}

const loadTransaction = async () => {
  if (!transactionId.value || Number.isNaN(transactionId.value)) {
    toast.add({
      severity: 'error',
      summary: 'Invalid Transaction',
      detail: 'Invalid transaction id.',
      life: 3000,
    })
    goBack()
    return
  }

  loading.value = true
  try {
    const response = await inventoryService.getTransaction(transactionId.value)
    if (!response?.success) {
      throw new Error(response?.message || 'Failed to load transaction detail')
    }
    transaction.value = normalizeResponseRow(response)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || error?.message || 'Failed to load transaction detail',
      life: 3500,
    })
    transaction.value = null
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.transactions' })
}

const printPage = () => {
  if (!transactionId.value || Number.isNaN(transactionId.value)) {
    toast.add({
      severity: 'error',
      summary: 'Invalid Transaction',
      detail: 'Cannot open print view for this transaction.',
      life: 2500,
    })
    return
  }

  window.open(`/inventory/transactions/${transactionId.value}/print`, '_blank')
}

const formatText = (value?: string | null) => {
  if (!value) return '—'
  return value.replace(/_/g, ' ').replace(/\b\w/g, letter => letter.toUpperCase())
}

const formatQuantity = (value: string | number | null | undefined) => Number(value || 0).toLocaleString('en-PH')
const signedQuantity = (value: string | number | null | undefined) => `${Number(value || 0) > 0 ? '+' : ''}${formatQuantity(value)}`
const employeeName = (employee: any) => employee ? [employee.fname || employee.user?.fname, employee.lname || employee.user?.lname].filter(Boolean).join(' ') || `Employee #${employee.id}` : '—'

const formatDateTime = (value?: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const formatCurrency = (value: string | number | null | undefined) => {
  const amount = Number(value || 0)
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount)
}

const transactionSeverity = (type?: string | null) => {
  switch (type) {
    case 'purchase':
    case 'receive':
      return 'success'
    case 'sale':
    case 'issue':
      return 'danger'
    case 'adjustment':
      return 'warning'
    case 'transfer':
    case 'transfer_in':
    case 'transfer_out':
      return 'info'
    case 'return':
      return 'secondary'
    default:
      return 'secondary'
  }
}

onMounted(loadTransaction)
</script>
