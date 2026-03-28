<template>
  <div class="p-4 min-h-screen">
    <div class="mb-4 flex justify-between items-center">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Reorder Suggestions</h1>
        <p class="text-xs text-gray-500 mt-0.5">
          Branch-aware decision support for low stock replenishment.
        </p>
      </div>
      <Button
        label="Generate Suggestions"
        icon="pi pi-bolt"
        size="small"
        severity="info"
        :loading="generating"
        @click="generateSuggestions"
      />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Pending</p>
              <p class="text-2xl font-bold text-amber-600">{{ stats.pending_count || 0 }}</p>
            </div>
            <i class="pi pi-clock text-3xl text-amber-500 opacity-20" />
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Critical</p>
              <p class="text-2xl font-bold text-red-600">{{ stats.critical_pending || 0 }}</p>
            </div>
            <i class="pi pi-exclamation-triangle text-3xl text-red-500 opacity-20" />
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Approved</p>
              <p class="text-2xl font-bold text-green-600">{{ stats.approved_count || 0 }}</p>
            </div>
            <i class="pi pi-check-circle text-3xl text-green-500 opacity-20" />
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Estimated Cost</p>
              <p class="text-lg font-bold text-gray-900">{{ formatCurrency(stats.total_estimated_cost || 0) }}</p>
            </div>
            <i class="pi pi-money-bill text-3xl text-blue-500 opacity-20" />
          </div>
        </template>
      </Card>
    </div>

    <Card class="mb-4">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
          <div class="flex flex-col gap-2 md:col-span-2">
            <label class="text-sm font-semibold">Search</label>
            <InputText v-model="filters.search" placeholder="Product name or SKU" />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold">Status</label>
            <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value" showClear />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold">Priority</label>
            <Select v-model="filters.priority" :options="priorityOptions" optionLabel="label" optionValue="value" showClear />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold">Type</label>
            <Select v-model="filters.type" :options="typeOptions" optionLabel="label" optionValue="value" showClear />
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable
          :value="suggestions"
          :loading="loading"
          stripedRows
          class="p-datatable-sm"
          paginator
          :rows="filters.per_page"
          :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * filters.per_page"
          @page="onPage"
        >
          <Column field="suggested_at" header="Date" style="width: 140px">
            <template #body="{ data }">{{ formatDate(data.suggested_at || data.created_at) }}</template>
          </Column>
          <Column header="Product" style="min-width: 220px">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium">{{ data.product_name || '-' }}</div>
                <div class="text-gray-500">{{ data.product_sku || '-' }}</div>
              </div>
            </template>
          </Column>
          <Column header="Branch" style="min-width: 160px">
            <template #body="{ data }">{{ data.branch_name || '-' }}</template>
          </Column>
          <Column field="current_stock" header="Current" style="width: 110px" />
          <Column field="suggested_quantity" header="Suggested" style="width: 110px" />
          <Column field="priority" header="Priority" style="width: 120px">
            <template #body="{ data }">
              <Tag :value="capitalize(data.priority)" :severity="prioritySeverity(data.priority)" />
            </template>
          </Column>
          <Column field="status" header="Status" style="width: 120px">
            <template #body="{ data }">
              <Tag :value="capitalize(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions" style="width: 200px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button icon="pi pi-eye" rounded text severity="info" @click="openDetail(data.id)" />
                <Button
                  v-if="data.status === 'pending'"
                  icon="pi pi-check"
                  rounded
                  text
                  severity="success"
                  @click="quickApprove(data.id)"
                />
                <Button
                  v-if="data.status === 'pending'"
                  icon="pi pi-times"
                  rounded
                  text
                  severity="danger"
                  @click="quickReject(data.id)"
                />
                <Button
                  v-if="data.status === 'approved'"
                  icon="pi pi-send"
                  rounded
                  text
                  severity="warn"
                  @click="quickImplement(data.id)"
                />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-gray-500">No reorder suggestions found.</div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '@/services/inventory.service'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const loading = ref(false)
const generating = ref(false)
const suggestions = ref<any[]>([])
const stats = ref<any>({})
const pagination = reactive({
  total: 0,
  current_page: 1,
})

const filters = reactive({
  search: '',
  status: null as string | null,
  priority: null as string | null,
  type: null as string | null,
  page: 1,
  per_page: 15,
})

const userBranchId = Number((authStore.user as any)?.branch_id || 0)

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Implemented', value: 'implemented' },
  { label: 'Cancelled', value: 'cancelled' },
]

const priorityOptions = [
  { label: 'Critical', value: 'critical' },
  { label: 'High', value: 'high' },
  { label: 'Medium', value: 'medium' },
  { label: 'Low', value: 'low' },
]

const typeOptions = [
  { label: 'Automatic', value: 'automatic' },
  { label: 'Manual', value: 'manual' },
  { label: 'Emergency', value: 'emergency' },
]

const extractRows = (payload: any): any[] => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  return []
}

const normalizeSuggestion = (row: any) => ({
  ...row,
  product_name: row?.product?.product_name || row?.product?.name || '-',
  product_sku: row?.product?.sku || '-',
  branch_name: row?.branch?.name || '-',
})

const statusSeverity = (status: string) => {
  const key = String(status || '').toLowerCase()
  if (key === 'approved') return 'success'
  if (key === 'rejected') return 'danger'
  if (key === 'implemented') return 'info'
  if (key === 'cancelled') return 'secondary'
  return 'warn'
}

const prioritySeverity = (priority: string) => {
  const key = String(priority || '').toLowerCase()
  if (key === 'critical') return 'danger'
  if (key === 'high') return 'warn'
  if (key === 'medium') return 'info'
  return 'secondary'
}

const capitalize = (value: string) => {
  const text = String(value || '')
  return text ? `${text.charAt(0).toUpperCase()}${text.slice(1)}` : '-'
}

const formatDate = (value?: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: '2-digit' })
}

const formatCurrency = (amount: number) => {
  const n = Number(amount || 0)
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(n)
}

const loadSuggestions = async () => {
  loading.value = true
  try {
    const params: any = { ...filters }
    if (userBranchId > 0) params.branch_id = userBranchId
    const response = await inventoryService.getReorderSuggestions(params)
    const rows = extractRows(response?.data).map(normalizeSuggestion)
    suggestions.value = rows
    pagination.total = Number(response?.data?.total || rows.length || 0)
    pagination.current_page = Number(response?.data?.current_page || filters.page || 1)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load reorder suggestions',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const params: any = {}
    if (userBranchId > 0) params.branch_id = userBranchId
    const response = await inventoryService.getReorderSuggestionStats(params)
    stats.value = response?.data || {}
  } catch {
    stats.value = {}
  }
}

const generateSuggestions = async () => {
  generating.value = true
  try {
    const payload: any = {}
    if (userBranchId > 0) payload.branch_id = userBranchId
    const response = await inventoryService.generateSuggestions(payload)
    toast.add({
      severity: 'success',
      summary: 'Generated',
      detail: response?.message || 'Reorder suggestions generated successfully.',
      life: 2500,
    })
    await Promise.all([loadSuggestions(), loadStats()])
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to generate suggestions',
      life: 3000,
    })
  } finally {
    generating.value = false
  }
}

const quickApprove = async (id: number) => {
  try {
    await inventoryService.approveReorderSuggestion(id)
    await Promise.all([loadSuggestions(), loadStats()])
    toast.add({ severity: 'success', summary: 'Approved', detail: 'Suggestion approved.', life: 2000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Approval failed', life: 3000 })
  }
}

const quickReject = async (id: number) => {
  try {
    await inventoryService.rejectReorderSuggestion(id)
    await Promise.all([loadSuggestions(), loadStats()])
    toast.add({ severity: 'info', summary: 'Rejected', detail: 'Suggestion rejected.', life: 2000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Reject failed', life: 3000 })
  }
}

const quickImplement = async (id: number) => {
  try {
    await inventoryService.implementReorderSuggestion(id)
    await Promise.all([loadSuggestions(), loadStats()])
    toast.add({ severity: 'success', summary: 'Implemented', detail: 'Suggestion implemented.', life: 2000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Implement failed', life: 3000 })
  }
}

const openDetail = (id: number) => {
  router.push({ name: 'inventory.reorder-suggestions.detail', params: { id } })
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadSuggestions()
}

let watchTimer: ReturnType<typeof setTimeout> | null = null
watch(
  () => [filters.search, filters.status, filters.priority, filters.type],
  () => {
    filters.page = 1
    if (watchTimer) clearTimeout(watchTimer)
    watchTimer = setTimeout(() => loadSuggestions(), 250)
  },
)

onMounted(async () => {
  await Promise.all([loadSuggestions(), loadStats()])
})
</script>
