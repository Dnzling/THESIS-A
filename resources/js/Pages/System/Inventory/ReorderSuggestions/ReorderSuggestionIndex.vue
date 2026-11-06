<template>
  <div class="p-4 min-h-screen">
    <div class="mb-4 flex justify-between items-center">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Reorder Suggestions</h1>
        <p class="text-xs text-gray-500 mt-0.5">
          {{ reorderDestination === 'warehouse'
            ? 'Select pending items for one receiving branch to create a grouped warehouse request.'
            : 'Group pending items by supplier and receiving branch to create one purchase order per group.' }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <Button
          v-if="reorderDestination === 'warehouse' && canCreateTransfer"
          :label="`Request Selected (${selectedSuggestions.length})`"
          icon="pi pi-warehouse"
          size="small"
          severity="warn"
          :disabled="!canCreateGroupedWarehouseRequest"
          @click="createWarehouseRequest(selectedSuggestions.map(item => item.id))"
        />
        <Button
          v-else-if="reorderDestination === 'pr' && canManagePr"
          :label="`Create Grouped PRs (${selectedSuggestions.length})`"
          icon="pi pi-file-plus"
          size="small"
          severity="warn"
          :disabled="!selectedSuggestions.length || selectedSuggestions.some(item => item.status !== 'pending')"
          @click="createGroupedPrs"
        />
        <Button v-if="reorderDestination === 'po' && canCreatePo" label="Create PO by Supplier" icon="pi pi-shopping-cart" size="small" severity="warn" outlined @click="openSimplePoDialog" />
        <Button
          label="Reorder Rules"
          icon="pi pi-sliders-h"
          size="small"
          severity="secondary"
          outlined
          @click="router.push({ name: 'inventory.reorder-rules' })"
        />
        <Button
          label="Refresh"
          icon="pi pi-refresh"
          size="small"
          severity="secondary"
          outlined
          :loading="loading"
          @click="refreshData"
        />
      </div>
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
          v-model:selection="selectedSuggestions"
          :value="suggestions"
          dataKey="id"
          :rowSelectable="isPendingSuggestionSelectable"
          rowHover
          class="p-datatable-sm text-sm"
          paginator
          :rows="filters.per_page"
          :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * filters.per_page"
          @page="onPage"
        >
          <Column v-if="(reorderDestination === 'warehouse' && canCreateTransfer) || (reorderDestination === 'pr' && canManagePr)" selectionMode="multiple" headerStyle="width: 3rem" />
          <Column field="suggested_at" header="Date" style="width: 140px">
            <template #body="{ data }">{{ formatDate(data.suggested_at || data.created_at) }}</template>
          </Column>
          <Column header="Product" style="min-width: 220px">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium">{{ data.product_name || '-' }}</div>
                <div class="text-gray-500">{{ data.product_sku || '-' }}</div>
                <div v-if="data.variation_name" class="mt-0.5 text-xs font-medium text-orange-700">
                  Variant: {{ data.variation_name }}
                </div>
              </div>
            </template>
          </Column>
          <Column header="Branch" style="min-width: 160px">
            <template #body="{ data }">{{ data.branch_name || '-' }}</template>
          </Column>
          <Column field="current_stock" header="Current" style="width: 110px"><template #body="{ data }">{{ formatQuantity(data.current_stock) }}</template></Column>
          <Column field="suggested_quantity" header="Suggested" style="width: 110px"><template #body="{ data }">{{ formatQuantity(data.suggested_quantity) }}</template></Column>
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
          <Column header="Actions" style="width: 210px">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Button label="View" icon="pi pi-eye" size="small" rounded outlined @click="openDetail(data.id)" />
                <Button
                  v-if="data.metadata?.stock_transfer_id"
                  label="View Transfer"
                  icon="pi pi-arrow-right-arrow-left"
                  size="small"
                  rounded
                  outlined
                  @click="viewWarehouseRequest(data.metadata.stock_transfer_id)"
                />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-gray-500">No reorder suggestions found.</div>
          </template>
        </DataTable>
        <small v-if="reorderDestination === 'warehouse' && selectedSuggestions.length && !canCreateGroupedWarehouseRequest" class="mt-3 block text-amber-700">
          Select pending suggestions for one receiving branch, with only one suggestion per product or variant.
        </small>
      </template>
    </Card>

    <Dialog v-model:visible="simplePoDialogVisible" modal header="Create PO from Reorder Suggestions" :style="{ width: 'min(96vw, 900px)' }">
      <p class="mb-4 text-sm text-slate-600">Choose a supplier and receiving branch, then select its linked pending suggestions. Each supplier-and-branch group becomes one draft PO.</p>
      <div class="mb-4 grid gap-3 md:grid-cols-2">
        <Select v-if="canSelectAllBranches" v-model="poBranchId" :options="poBranches" optionLabel="name" optionValue="id" placeholder="Select receiving branch" class="w-full" />
        <div v-else class="flex min-h-10 items-center rounded-md border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700">Receiving branch: {{ assignedBranchName || 'No assigned branch' }}</div>
        <Select v-model="poSupplierId" :options="supplierOptionsForBranch" optionLabel="supplier_name" optionValue="id" placeholder="Select supplier" class="w-full" />
      </div>
      <div v-if="!canSelectAllBranches && !poBranchId && !simplePoLoading" class="mb-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800">Your user profile needs an assigned branch before you can create a PO from reorder suggestions.</div>
      <div v-if="simplePoLoading" class="space-y-2"><Skeleton v-for="n in 4" :key="n" height="3rem" /></div>
      <DataTable v-else v-model:selection="selectedPoSuggestions" :value="supplierSuggestions" dataKey="id" size="small" rowHover scrollable scrollHeight="380px">
        <Column selectionMode="multiple" headerStyle="width: 3rem" />
        <Column header="Item"><template #body="{ data }"><div class="font-medium">{{ data.product_name }}</div><div class="text-xs text-slate-500">{{ data.product_sku }}<span v-if="data.variation_name"> · {{ data.variation_name }}</span></div></template></Column>
        <Column header="Current"><template #body="{ data }">{{ formatQuantity(data.current_stock) }}</template></Column>
        <Column header="Suggested Qty"><template #body="{ data }">{{ formatQuantity(data.suggested_quantity) }}</template></Column>
        <Column header="Priority"><template #body="{ data }"><Tag :value="capitalize(data.priority)" :severity="prioritySeverity(data.priority)" /></template></Column>
      </DataTable>
      <div v-if="poSupplierId && !supplierSuggestions.length && !simplePoLoading" class="py-5 text-center text-sm text-slate-500">No pending suggestions for this supplier and branch.</div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="simplePoDialogVisible = false" />
        <Button :label="`Continue with ${selectedPoSuggestions.length} item(s)`" icon="pi pi-arrow-right" severity="warn" :disabled="!poSupplierId || !poBranchId || !selectedPoSuggestions.length" @click="continueToSimplePo" />
      </template>
    </Dialog>

  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { router as inertiaRouter } from '@inertiajs/vue3'
import axiosClient from '@/axios'
import inventoryService from '@/services/inventory.service'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const reorderDestination = ref('pr')
const canCreatePo = computed(() => authStore.hasPermission('inventory.purchase_orders.manage'))
const canManagePr = computed(() => authStore.hasPermission('inventory.requisites.manage'))
const canCreateTransfer = computed(() => authStore.hasPermission('inventory.transfers.create') || authStore.hasPermission('inventory.transfers.manage'))
const selectedSuggestions = ref<any[]>([])
const canCreateGroupedWarehouseRequest = computed(() => {
  if (!selectedSuggestions.value.length) return false
  if (selectedSuggestions.value.some(item => item.status !== 'pending')) return false
  const branches = new Set(selectedSuggestions.value.map(item => Number(item.branch_id)))
  const products = selectedSuggestions.value.map(item => `${Number(item.product_id)}:${Number(item.variation_id || 0)}`)
  return branches.size === 1 && new Set(products).size === products.length
})
const simplePoDialogVisible = ref(false)
const simplePoLoading = ref(false)
const poBranches = ref<any[]>([])
const canSelectAllBranches = ref(false)
const poSuppliers = ref<any[]>([])
const poProducts = ref<any[]>([])
const pendingPoSuggestions = ref<any[]>([])
const poBranchId = ref<number | null>(Number((authStore.user as any)?.branch_id || 0) || null)
const poSupplierId = ref<number | null>(null)
const selectedPoSuggestions = ref<any[]>([])
const assignedBranchName = computed(() => poBranches.value.find(branch => Number(branch.id) === Number(poBranchId.value))?.name || '')
const supplierOptionsForBranch = computed(() => poSuppliers.value.filter(supplier =>
  String(supplier?.supplier_name || '').trim().length > 0
).sort((left, right) => String(left.supplier_name).localeCompare(String(right.supplier_name))))
const supplierSuggestions = computed(() => pendingPoSuggestions.value.filter(suggestion => {
  if (Number(suggestion.branch_id) !== Number(poBranchId.value) || !poSupplierId.value) return false
  const product = poProducts.value.find(item => Number(item.id) === Number(suggestion.product_id))
  return product?.suppliers?.some((supplier: any) => Number(supplier.id) === Number(poSupplierId.value))
}))
watch([poBranchId, poSupplierId], () => { selectedPoSuggestions.value = [] })

const loading = ref(false)
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
  product_sku: row?.variation?.variation_sku || row?.product?.sku || '-',
  variation_name: row?.variation?.variation_name || null,
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

const formatQuantity = (value: unknown) => new Intl.NumberFormat('en-PH', {
  maximumFractionDigits: 0,
}).format(Math.round(Number(value) || 0))

const loadSuggestions = async () => {
  loading.value = true
  try {
    const params: any = { ...filters }
    if (userBranchId > 0) params.branch_id = userBranchId
    const response = await inventoryService.getReorderSuggestions(params)
    reorderDestination.value = response?.reorder_destination || 'pr'
    const rows = extractRows(response?.data).map(normalizeSuggestion)
    suggestions.value = rows
    const loadedIds = new Set(rows.map(row => Number(row.id)))
    selectedSuggestions.value = selectedSuggestions.value.filter(row => loadedIds.has(Number(row.id)))
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

const openSimplePoDialog = async () => {
  simplePoDialogVisible.value = true
  simplePoLoading.value = true
  selectedPoSuggestions.value = []
  try {
    const [suggestionResponse, optionResponse] = await Promise.all([
      inventoryService.getReorderSuggestions({ status: 'pending', expired: false, per_page: 200 }),
      axiosClient.get('/api/inventory/purchase-orders/options'),
    ])
    pendingPoSuggestions.value = (suggestionResponse?.data?.data || []).map(normalizeSuggestion)
    poBranches.value = optionResponse.data?.data?.branches || []
    canSelectAllBranches.value = Boolean(optionResponse.data?.data?.can_select_all_branches)
    poSuppliers.value = optionResponse.data?.data?.suppliers || []
    poProducts.value = optionResponse.data?.data?.products || []
    if (!canSelectAllBranches.value || (!poBranchId.value && poBranches.value.length === 1)) {
      poBranchId.value = poBranches.value.length === 1 ? Number(poBranches.value[0].id) : null
    }
  } catch (error: any) {
    simplePoDialogVisible.value = false
    toast.add({ severity: 'error', summary: 'Unable to load reorder items', detail: error?.response?.data?.message || 'Please try again.', life: 3500 })
  } finally {
    simplePoLoading.value = false
  }
}

const continueToSimplePo = () => {
  if (!poSupplierId.value || !poBranchId.value || !selectedPoSuggestions.value.length) return
  const ids = selectedPoSuggestions.value.map(item => item.id).join(',')
  simplePoDialogVisible.value = false
  inertiaRouter.visit(`/inventory/purchase-orders/create?reorder_suggestion_ids=${ids}&supplier_id=${poSupplierId.value}`)
}

const isPendingSuggestionSelectable = (row: any) => row?.status === 'pending'

const createGroupedPrs = async () => {
  if (!selectedSuggestions.value.length || selectedSuggestions.value.some(item => item.status !== 'pending')) return
  try {
    const response = await inventoryService.createGroupedReorderPrs(selectedSuggestions.value.map(item => Number(item.id)))
    const createdCount = Array.isArray(response?.data) ? response.data.length : 0
    selectedSuggestions.value = []
    toast.add({
      severity: 'success',
      summary: 'Draft PRs Created',
      detail: response?.message || `${createdCount} grouped draft purchase requisition(s) created.`,
      life: 3500,
    })
    await refreshData()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Could Not Create PRs', detail: error?.response?.data?.message || 'Refresh suggestions and try again.', life: 4000 })
  }
}

const createWarehouseRequest = (ids: number[]) => {
  if (!ids.length || !canCreateGroupedWarehouseRequest.value) return
  const query = new URLSearchParams({
    type: 'transfer',
    reorder_suggestion_ids: ids.join(','),
  })
  inertiaRouter.visit(`/inventory/stock-requests/create?${query.toString()}`)
}

const viewWarehouseRequest = (id: number) => {
  router.push({ name: 'inventory.stock-requests.detail', params: { type: 'transfer', id } })
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

const refreshData = () => Promise.all([loadSuggestions(), loadStats()])

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
