<template>
  <div class="min-h-screen p-4">
    <div class=" mx-auto">
      <div class="mb-4 flex items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Purchase Requisitions</h1>
        </div>
        <div class="flex items-center gap-2">
          <Button
            v-if="!props.warehouseMode && canManage"
            label="Receipts"
            severity="info"
            size="small"
            @click="router.push({ name: 'inventory.goods-receipts' })"
          />
          <Button
            v-if="canManage"
            label="Create PR" 
            severity="warn"
            size="small"
            @click="router.push({ name: props.warehouseMode ? 'warehouse.purchase-requisitions.create' : 'inventory.requisites.create' })"
          />
        </div>
      </div>

      <Card>
        <template #content>
          <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-4 mb-4">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search requisition, product, or SKU" fluid  size="small" />
            </IconField>
            <Select
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Statuses"
              showClear
              fluid
              size="small"
            />
            <DatePicker
              v-model="filters.date_range"
              selectionMode="range"
              dateFormat="M dd, yy"
              placeholder="Date range"
              showIcon
              :maxDate="new Date()"
              showButtonBar
              fluid
              class="w-full"
              size="small"
              @date-select="onDateRangeChange"
              @clear="onDateRangeChange"
            />
            <div v-if="hasActiveFilters">
              <Button label="Clear All" severity="danger" size="small" @click="resetFilters" />
            </div>
          </div>
          <!-- Requisition Table -->
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
            :value="rows"
            class="p-datatable-sm text-sm p-datatable-fluid"
            responsiveLayout="scroll"
            paginator
            :rows="perPage"
            :totalRecords="total"
            :lazy="true"
            :first="(page - 1) * perPage"
            @page="onPageChange"
            :sortField="sortField"
            :sortOrder="sortOrder"
            @sort="onSort"
            rowHover
            :rowsPerPageOptions="[15, 25, 50]"
          >
            <template #empty>
              <div class="py-8 text-center">
                <i class="pi pi-inbox text-3xl text-gray-400"></i>
                <p class="mt-2 text-xs text-gray-600">No purchase requisitions found</p>
              </div>
            </template>
            <Column field="created_at" header="Date" sortable style="width: 130px">
              <template #body="{ data }">
                <div class="text-xs">
                  <div>{{ formatDate(data.created_at) }}</div>
                  <div class="text-gray-500 text-xs">{{ formatTime(data.created_at) }}</div>
                </div>
              </template>
            </Column>

            <Column field="pr_number" header="PR No." style="width: 200px">
              <template #body="{ data }">
                <span class="font-semibold text-gray-900">{{ data.pr_number || `PR #${data.id}` }}</span>
              </template>
            </Column>

        <Column header="Requested By" style="min-width: 180px">
          <template #body="{ data }">
            <span class="text-gray-700">
              {{ data.requestedBy?.user?.full_name || data.requested_by_name || '—' }}
            </span>
          </template>
        </Column>

            <Column header="Status" style="width: 230px">
              <template #body="{ data }">
                <Badge :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
              </template>
            </Column>

            <Column header="Items" style="width: 90px">
              <template #body="{ data }">
                <span class="font-semibold text-gray-900">{{ (data.items || []).length }}</span>
              </template>
            </Column>

            <Column field="estimated_amount" header="Request Amount" style="width: 150px">
              <template #body="{ data }">
                <span class="font-semibold text-green-600 justify-end flex">{{
                  formatCurrency(data.estimated_amount)
                }}</span>
              </template>
            </Column>

            <Column header="Reason" style="min-width: 230px">
              <template #body="{ data }">
                <span class="text-gray-700">{{ String(data.reason || '—') }}</span>
              </template>
            </Column>
            <Column header="Action" style="width: 90px">
              <template #body="{ data }">
                <Button
                  icon="pi pi-eye"
                  size="small"
                  outlined
                  label="View"
                  rounded
                  @click.stop="router.push({ name: props.warehouseMode ? 'warehouse.purchase-requisitions.view' : 'inventory.requisites.detail', params: { id: data.id } })"
                />
              </template>
            </Column>
          </DataTable>
          </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import inventoryService from '@/services/inventory.service'
import WarehouseService from '@/services/warehouse.service'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const props = defineProps<{ warehouseMode?: boolean }>()
const service: any = props.warehouseMode ? WarehouseService : inventoryService

const loading = ref(false)
const rows = ref<any[]>([])
const total = ref(0)
const page = ref(1)
const perPage = ref(15)
const sortField = ref('created_at')
const sortOrder = ref(-1)

const canManage = computed(() => authStore.hasPermission(props.warehouseMode ? 'warehouse.purchase-requisitions.manage' : 'inventory.requisites.manage'))
const hasActiveFilters = computed(() => Boolean(filters.search.trim() || filters.status || filters.date_range?.length))

const filters = reactive<{ search: string; status: string | null; date_range: Date[] | null }>({
  search: '',
  status: null,
  date_range: null,
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Pending', value: 'pending' },
  { label: 'Procurement Processing', value: 'procurement_processing' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' },
]

const statusSeverity = (status: string) => {
  const s = String(status || '').toLowerCase()
  if (s === 'procurement_processing' || s === 'approved') return 'info'
  if (s === 'rejected' || s === 'cancelled') return 'danger'
  if (s === 'draft') return 'secondary'
  if (s === 'po_created') return 'success'
  return 'warning'
}

const formatStatus = (status: any) => {
  const s = String(status || '').replace(/_/g, ' ').trim()
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : 'Unknown'
}

const formatDate = (value: any) => {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
}

const rowClass = (data: any) => ({ 'cursor-pointer hover:bg-gray-50': true })

const onRowClick = (event: any) => {
  const id = event?.data?.id
  if (id) router.push({ name: props.warehouseMode ? 'warehouse.purchase-requisitions.view' : 'inventory.requisites.detail', params: { id } })
}

const formatTime = (value: any) => {
  if (!value) return '-'
  const d = new Date(value)
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const formatCurrency = (value: any) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
}).format(Number(value || 0))

const formatDateParam = (value: Date | null | undefined) => {
  if (!value) return undefined
  const year = value.getFullYear()
  const month = String(value.getMonth() + 1).padStart(2, '0')
  const day = String(value.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const onDateRangeChange = () => {
  if (filters.date_range?.length === 2) {
    page.value = 1
    load()
  } else if (!filters.date_range?.length) {
    page.value = 1
    load()
  }
}

const load = async () => {
  loading.value = true
  try {
    const response = await service[props.warehouseMode ? 'purchaseRequisitions' : 'getPurchaseRequisitions']({
      page: page.value,
      per_page: perPage.value,
      sort_by: sortField.value,
      sort_order: sortOrder.value === 1 ? 'asc' : 'desc',
      status: filters.status || undefined,
      search: filters.search || undefined,
      date_from: formatDateParam(filters.date_range?.[0]),
      date_to: formatDateParam(filters.date_range?.[1]),
    })

    if (props.warehouseMode ? response : response?.success) {
      const payload = props.warehouseMode ? response : response.data
      rows.value = payload?.data || []
      total.value = payload?.total || 0
    } else {
      rows.value = []
      total.value = 0
    }
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: e?.response?.data?.message || 'Failed to load requisitions',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onPageChange = (e: any) => {
  page.value = e.page + 1
  perPage.value = e.rows
  load()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = null
  filters.date_range = null
  page.value = 1
  perPage.value = 15
  sortField.value = 'created_at'
  sortOrder.value = -1
  load()
}

const onSort = (e: any) => {
  sortField.value = e.sortField || 'created_at'
  sortOrder.value = e.sortOrder || -1
  load()
}

let searchTimer: ReturnType<typeof setTimeout> | null = null

watch([() => filters.status, () => perPage.value], () => {
  page.value = 1
  load()
})

watch(() => filters.search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    load()
  }, 350)
})

onMounted(async () => {
  try {
    if (!authStore.user) await authStore.fetchCurrentUser()
  } catch {}
  await load()
})
</script>
