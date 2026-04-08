<template>
  <div class="p-4 min-h-screen">
    <div class="mb-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" text severity="secondary" @click="goBack" />
        <div>
          <h1 class="text-xl font-bold text-gray-800">Transaction Detail</h1>
          <p class="text-xs text-gray-500 mt-0.5">Reference: {{ transaction?.transaction_number || '-' }}</p>
        </div>
      </div>
      <Button label="Print" icon="pi pi-print" size="small" severity="info" @click="printPage" :disabled="loading || !transaction" />
    </div>

    <div v-if="loading" class="py-10 flex justify-center">
      <ProgressSpinner style="width: 36px; height: 36px" strokeWidth="6" />
    </div>

    <div v-else-if="transaction" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Type</p>
            <Tag :value="formatText(transaction.transaction_type)" :severity="transactionSeverity(transaction.transaction_type)" />
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Date</p>
            <p class="font-medium">{{ formatDateTime(transaction.transaction_date) }}</p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Quantity Change</p>
            <p :class="Number(transaction.quantity_change) >= 0 ? 'text-green-600' : 'text-red-600'" class="font-bold text-lg">
              {{ Number(transaction.quantity_change) >= 0 ? '+' : '' }}{{ transaction.quantity_change ?? 0 }}
            </p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Total Value</p>
            <p class="font-bold text-lg">{{ formatCurrency(transaction.total_value) }}</p>
          </template>
        </Card>
      </div>

      <Card>
        <template #title>Core Information</template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-xs text-gray-500">Transaction Number</p>
              <p class="font-mono">{{ transaction.transaction_number || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Branch</p>
              <p>{{ transaction.branch?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Product</p>
              <p>{{ transaction.product?.product_name || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Variation</p>
              <p>{{ transaction.variation?.variation_name || 'N/A' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Before / After</p>
              <p>{{ transaction.quantity_before ?? 0 }} -> {{ transaction.quantity_after ?? 0 }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Unit Cost</p>
              <p>{{ formatCurrency(transaction.unit_cost) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Source</p>
              <p>{{ formatText(transaction.reference_type) }} (ID: {{ transaction.reference_id || '-' }})</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Related Branch</p>
              <p>{{ transaction.related_branch?.name || '-' }}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-xs text-gray-500">Notes</p>
              <p>{{ transaction.notes || 'No notes provided.' }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #title>Audit</template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
              <p class="text-xs text-gray-500">Created By</p>
              <p>{{ transaction.created_by ? `${transaction.created_by.fname} ${transaction.created_by.lname}` : '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Employee No.</p>
              <p>{{ transaction.created_by?.employee_number || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Created At</p>
              <p>{{ formatDateTime(transaction.created_at) }}</p>
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
  window.print()
}

const formatText = (value?: string | null) => {
  if (!value) return '-'
  return value.replace(/_/g, ' ')
}

const formatDateTime = (value?: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
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
