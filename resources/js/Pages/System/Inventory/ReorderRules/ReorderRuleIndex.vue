<template>
  <div class="min-h-screen space-y-4 p-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900">Reorder Rules</h1>
        <p class="mt-0.5 text-xs text-slate-500">Configure when and how much each branch should replenish.</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <Button label="Create Rule" icon="pi pi-plus" size="small" severity="warn" @click="router.push({ name: 'inventory.reorder-rules.create' })" />
      </div>
    </div>

    <Card>
      <template #content>
        <div class="flex flex-col gap-2 md:flex-row md:flex-wrap md:items-center">
          <IconField class="w-full md:w-72"><InputIcon class="pi pi-search" /><InputText v-model="filters.search" placeholder="Search product or SKU" size="small" fluid /></IconField>
          <Select v-model="filters.priority" :options="priorityOptions" optionLabel="label" optionValue="value" placeholder="All priorities" showClear size="small" class="w-full md:w-40" />
          <Select v-model="filters.is_active" :options="activeOptions" optionLabel="label" optionValue="value" placeholder="All statuses" showClear size="small" class="w-full md:w-40" />
          <Button v-if="hasActiveFilters" label="Clear Filter" icon="pi pi-filter-slash" severity="danger" size="small" @click="resetFilters" />
          <span class="text-xs text-slate-500 md:ml-auto">{{ pagination.total }} rules</span>
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <div v-if="loading" class="space-y-3">
          <div class="grid grid-cols-6 gap-3 text-xs text-gray-400">
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
            <Skeleton height="24px" class="col-span-1" />
          </div>
          <div v-for="i in 8" :key="i" class="grid grid-cols-6 gap-3">
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
            <Skeleton height="20px" class="col-span-1" />
          </div>
        </div>

        <DataTable
          v-else
          :value="rules"
          dataKey="id"
          rowHover
          scrollable
          :tableStyle="{ minWidth: '70rem' }"
          class="p-datatable-sm text-sm"
          paginator
          :rows="filters.per_page"
          :rowsPerPageOptions="[15, 25, 50]"
          :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * filters.per_page"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect CurrentPageReport"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} rules"
          @page="onPage"
        >
          <Column header="Product" style="min-width: 220px">
            <template #body="{ data }">
              <div class="min-w-0">
                <p class="truncate font-medium text-slate-900">{{ data.product_name || '—' }}</p>
                <p class="mt-0.5 truncate font-mono text-xs text-slate-500">{{ data.product_sku || 'No SKU' }}</p>
              </div>
            </template>
          </Column>
          <Column header="Branch" style="min-width: 140px"><template #body="{ data }"><span class="text-slate-700 text-xs">{{ data.branch?.name || '—' }}</span></template></Column>
          <Column field="basis_type" header="Basis" style="min-width: 150px" class="text-xs">
            <template #body="{ data }"><Badge :value="toLabel(data.basis_type || 'reorder_point')" severity="secondary" /></template>
          </Column>
          <Column field="reorder_point" header="Reorder Point" style="width: 120px"><template #body="{ data }">{{ formatQuantity(data.reorder_point) }}</template></Column>
          <Column field="reorder_quantity" header="Reorder Qty" style="width: 115px"><template #body="{ data }">{{ formatQuantity(data.reorder_quantity) }}</template></Column>
          <Column field="safety_stock" header="Safety Stock" style="width: 115px"><template #body="{ data }">{{ formatQuantity(data.safety_stock) }}</template></Column>
          <Column field="maximum_stock" header="Max Stock" style="width: 110px"><template #body="{ data }">{{ formatQuantity(data.maximum_stock) }}</template></Column>
          <Column field="priority" header="Priority" style="width: 110px">
            <template #body="{ data }">
              <Badge :value="toLabel(data.priority)" :severity="prioritySeverity(data.priority)" />
            </template>
          </Column>
          <Column field="is_active" header="Status" style="width: 110px">
            <template #body="{ data }">
              <Badge :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column header="Actions" style="width: 100px">
            <template #body="{ data }">
              <Button label="View" icon="pi pi-eye" outlined rounded size="small" class="text-xs" @click="openDetail(data.id)" />
            </template>
          </Column>
          <template #empty>
            <div class="flex flex-col items-center py-10 text-center">
              <i class="pi pi-sliders-h text-3xl text-slate-300" />
              <p class="mt-3 text-sm font-medium text-slate-700">No reorder rules found</p>
              <p class="mt-1 text-xs text-slate-500">Create a rule to set replenishment thresholds for your products.</p>
              <Button label="Create Rule" icon="pi pi-plus" severity="warn" size="small" class="mt-4" @click="router.push({ name: 'inventory.reorder-rules.create' })" />
            </div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '@/services/inventory.service'
import { useAuthStore } from '@/stores/auth'
import Badge from 'primevue/badge'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const loading = ref(false)
const rules = ref<any[]>([])

const pagination = reactive({
  total: 0,
  current_page: 1,
})

const filters = reactive({
  search: '',
  priority: null as string | null,
  is_active: null as boolean | null,
  page: 1,
  per_page: 15,
})
const hasActiveFilters = computed(() => Boolean(filters.search.trim() || filters.priority || filters.is_active !== null))

const userBranchId = Number(
  (authStore.user as any)?.branch_id ||
  (authStore.user as any)?.branch?.id ||
  (authStore.user as any)?.employee?.branch_id ||
  0
)

const priorityOptions = [
  { label: 'Critical', value: 'critical' },
  { label: 'High', value: 'high' },
  { label: 'Medium', value: 'medium' },
  { label: 'Low', value: 'low' },
]

const activeOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false },
]

const extractRows = (payload: any): any[] => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  return []
}

const normalizeRule = (row: any) => ({
  ...row,
  product_name: row?.product?.product_name || row?.product?.name || '-',
  product_sku: row?.product?.sku || '-',
})

const toLabel = (value: string) => {
  const text = String(value || '')
  if (!text) return '-'
  if (text === 'demand_lead_time') return 'Demand + Lead Time'
  return text
    .split('_')
    .map((w) => `${w.charAt(0).toUpperCase()}${w.slice(1)}`)
    .join(' ')
}

const formatQuantity = (value: number | string | null | undefined) => {
  if (value === null || value === undefined || value === '') return '—'
  return Number(value).toLocaleString('en-PH', { maximumFractionDigits: 2 })
}

const prioritySeverity = (priority: string) => {
  const key = String(priority || '').toLowerCase()
  if (key === 'critical') return 'danger'
  if (key === 'high') return 'warn'
  if (key === 'medium') return 'info'
  return 'secondary'
}

const loadRules = async () => {
  loading.value = true
  try {
    const params: any = { ...filters }
    if (userBranchId > 0) params.branch_id = userBranchId
    const response = await inventoryService.getReorderRules(params)
    const rows = extractRows(response?.data).map(normalizeRule)
    rules.value = rows
    pagination.total = Number(response?.data?.total || rows.length || 0)
    pagination.current_page = Number(response?.data?.current_page || filters.page || 1)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load reorder rules',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const openDetail = (id: number) => {
  router.push({ name: 'inventory.reorder-rules.detail', params: { id } })
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadRules()
}

const resetFilters = () => {
  filters.search = ''
  filters.priority = null
  filters.is_active = null
  filters.page = 1
  loadRules()
}

let watchTimer: ReturnType<typeof setTimeout> | null = null
watch(
  () => [filters.search, filters.priority, filters.is_active],
  () => {
    filters.page = 1
    if (watchTimer) clearTimeout(watchTimer)
    watchTimer = setTimeout(() => loadRules(), 250)
  },
)

onMounted(() => {
  loadRules()
})
</script>
