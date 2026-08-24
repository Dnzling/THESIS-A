<template>
  <div class="min-h-screen p-4 sm:p-6">
    <div class="mb-4 flex items-center justify-between gap-3">
      <div>
        <h1 class="text-lg font-bold text-gray-800">Stock Movements</h1>
        <p class="text-xs text-gray-500">Review adjustments, returns, and branch transfers.</p>
      </div>
      <Button
        v-if="activeTab !== 'returns' && canCreateActiveMovement"
        :label="activeTab === 'adjustments' ? 'Create Adjustment' : 'Create Transfer'"
        icon="pi pi-plus"
        severity="warn"
        size="small"
        class="text-xs"
        @click="createActiveMovement"
      />
    </div>

    <Card>
      <template #content>
        <Tabs v-model:value="activeTab" class="stock-movement-tabs">
          <TabList>
            <Tab value="adjustments">Adjustments</Tab>
            <Tab value="returns">Returns</Tab>
            <Tab value="transfers">Transfers</Tab>
          </TabList>

          <TabPanels>
            <TabPanel value="adjustments">
              <MovementFilters
                v-model:search="adjustmentFilters.search"
                v-model:status="adjustmentFilters.status"
                :status-options="adjustmentStatuses"
                search-placeholder="Search adjustment number..."
                @reset="resetFilters('adjustments')"
              />
              <MovementTable
                :loading="adjustments.loading"
                :rows="adjustments.rows"
                :pagination="adjustments.pagination"
                empty-label="No adjustments found"
                kind="adjustment"
                @page="(event) => onPage('adjustments', event)"
                @row-click="openRow('adjustments', $event)"
              />
            </TabPanel>

            <TabPanel value="returns">
              <MovementFilters
                v-model:search="returnFilters.search"
                v-model:status="returnFilters.status"
                :status-options="returnStatuses"
                search-placeholder="Search return number..."
                @reset="resetFilters('returns')"
              />
              <p class="mb-3 text-xs text-gray-500">Returns are created from customer return requests and processed here.</p>
              <MovementTable
                :loading="returns.loading"
                :rows="returns.rows"
                :pagination="returns.pagination"
                empty-label="No returns found"
                kind="return"
                @page="(event) => onPage('returns', event)"
                @row-click="openRow('returns', $event)"
              />
            </TabPanel>

            <TabPanel value="transfers">
              <MovementFilters
                v-model:search="transferFilters.search"
                v-model:status="transferFilters.status"
                :status-options="transferStatuses"
                search-placeholder="Search transfer number..."
                @reset="resetFilters('transfers')"
              />
              <MovementTable
                :loading="transfers.loading"
                :rows="transfers.rows"
                :pagination="transfers.pagination"
                empty-label="No transfers found"
                kind="transfer"
                @page="(event) => onPage('transfers', event)"
                @row-click="openRow('transfers', $event)"
              />
            </TabPanel>
          </TabPanels>
        </Tabs>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Calendar from 'primevue/calendar'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Tab from 'primevue/tab'
import TabList from 'primevue/tablist'
import TabPanel from 'primevue/tabpanel'
import TabPanels from 'primevue/tabpanels'
import Tabs from 'primevue/tabs'
import Tag from 'primevue/tag'
import { useAuthStore } from '../../../../stores/auth'

type MovementKind = 'adjustments' | 'returns' | 'transfers'
type Pagination = { current_page: number; per_page: number; total: number }

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const activeTab = ref<MovementKind>('adjustments')

const adjustmentStatuses = [
  { label: 'Draft', value: 'draft' },
  { label: 'Pending Approval', value: 'pending_approval' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
]
const returnStatuses = [
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Received', value: 'received' },
  { label: 'Completed', value: 'completed' },
  { label: 'Rejected', value: 'rejected' },
]
const transferStatuses = [
  { label: 'Draft', value: 'draft' },
  { label: 'Pending Approval', value: 'requested' },
  { label: 'Approved', value: 'approved' },
  { label: 'Shipped', value: 'shipped' },
  { label: 'Received', value: 'received' },
  { label: 'Cancelled', value: 'cancelled' },
]

const newFilters = () => reactive({ search: '', status: null as string | null, page: 1, per_page: 15 })
const adjustmentFilters = newFilters()
const returnFilters = newFilters()
const transferFilters = newFilters()

const newMovementState = () => reactive({
  loading: false,
  rows: [] as any[],
  pagination: { current_page: 1, per_page: 15, total: 0 } as Pagination,
})
const adjustments = newMovementState()
const returns = newMovementState()
const transfers = newMovementState()

const canCreateActiveMovement = computed(() => activeTab.value === 'adjustments'
  ? authStore.hasPermission('inventory.adjustments.manage')
  : authStore.hasPermission('inventory.transfers.manage'))

const stateFor = (kind: MovementKind) => kind === 'adjustments' ? adjustments : kind === 'returns' ? returns : transfers
const filtersFor = (kind: MovementKind) => kind === 'adjustments' ? adjustmentFilters : kind === 'returns' ? returnFilters : transferFilters

const unwrapPagination = (response: any) => {
  if (Array.isArray(response?.data?.data)) return { rows: response.data.data, meta: response.data }
  if (Array.isArray(response?.data) && response?.meta) return { rows: response.data, meta: response.meta }
  if (Array.isArray(response?.data)) return { rows: response.data, meta: {} }
  if (Array.isArray(response)) return { rows: response, meta: {} }
  return { rows: [], meta: {} }
}

const loadMovement = async (kind: MovementKind) => {
  const state = stateFor(kind)
  const filters = filtersFor(kind)
  state.loading = true
  try {
    const endpoint = kind === 'adjustments' ? '/api/inventory/adjustments' : kind === 'returns' ? '/api/inventory/returns' : '/api/inventory/transfers'
    const params: Record<string, any> = { page: filters.page, per_page: filters.per_page }
    if (filters.search.trim()) params.search = filters.search.trim()
    if (filters.status) params.status = filters.status

    const response = await axios.get(endpoint, { params })
    const { rows, meta } = unwrapPagination(response.data)
    state.rows = rows
    state.pagination.current_page = Number(meta.current_page || filters.page)
    state.pagination.per_page = Number(meta.per_page || filters.per_page)
    state.pagination.total = Number(meta.total || 0)
  } catch (error: any) {
    state.rows = []
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || `Failed to load ${kind}`, life: 3000 })
  } finally {
    state.loading = false
  }
}

const resetFilters = (kind: MovementKind) => {
  const filters = filtersFor(kind)
  filters.search = ''
  filters.status = null
  filters.page = 1
  loadMovement(kind)
}

const onPage = (kind: MovementKind, event: any) => {
  const filters = filtersFor(kind)
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadMovement(kind)
}

const openRow = (kind: MovementKind, event: any) => {
  const id = event?.data?.id
  if (!id) return
  const routeName = kind === 'adjustments' ? 'inventory.adjustments.detail' : kind === 'returns' ? 'inventory.stock-returns.detail' : 'inventory.transfers.detail'
  router.push({ name: routeName, params: { id } })
}

const createActiveMovement = () => {
  router.push({ name: activeTab.value === 'adjustments' ? 'inventory.adjustments.create' : 'inventory.transfers.create' })
}

const scheduleFilterLoad = (kind: MovementKind) => {
  const filters = filtersFor(kind)
  filters.page = 1
  window.setTimeout(() => loadMovement(kind), 250)
}

watch([
  () => adjustmentFilters.search,
  () => adjustmentFilters.status,
], () => scheduleFilterLoad('adjustments'))
watch([
  () => returnFilters.search,
  () => returnFilters.status,
], () => scheduleFilterLoad('returns'))
watch([
  () => transferFilters.search,
  () => transferFilters.status,
], () => scheduleFilterLoad('transfers'))

watch(activeTab, (kind) => {
  const state = stateFor(kind)
  if (!state.rows.length && !state.loading) loadMovement(kind)
})

onMounted(() => loadMovement('adjustments'))

const MovementFilters = defineComponent({
  props: {
    search: { type: String, default: '' },
    status: { type: String, default: null },
    statusOptions: { type: Array, default: () => [] },
    searchPlaceholder: { type: String, default: 'Search...' },
  },
  emits: ['update:search', 'update:status', 'reset'],
  setup(props, { emit }) {
    return () => h('div', { class: 'mb-4 grid grid-cols-1 items-end gap-3 md:grid-cols-3' }, [
      h(IconField, { class: 'w-full' }, {
        default: () => [h(InputIcon, { class: 'pi pi-search' }), h(InputText, {
          modelValue: props.search,
          'onUpdate:modelValue': (value: string) => emit('update:search', value),
          placeholder: props.searchPlaceholder,
          class: 'w-full text-xs',
          size: 'small',
          onKeyup: (event: KeyboardEvent) => event.key === 'Enter' && emit('update:search', props.search),
        })],
      }),
      h(Select, {
        modelValue: props.status,
        'onUpdate:modelValue': (value: string | null) => emit('update:status', value),
        options: props.statusOptions,
        optionLabel: 'label',
        optionValue: 'value',
        placeholder: 'All Statuses',
        showClear: true,
        class: 'w-full text-xs',
        size: 'small',
      }),
      h(Button, { label: 'Clear All', icon: 'pi pi-filter-slash', severity: 'secondary', outlined: true, size: 'small', class: 'text-xs', onClick: () => emit('reset') }),
    ])
  },
})

const MovementTable = defineComponent({
  props: {
    loading: Boolean,
    rows: { type: Array, default: () => [] },
    pagination: { type: Object, required: true },
    emptyLabel: { type: String, default: 'No records found' },
    kind: { type: String, required: true },
  },
  emits: ['page', 'row-click'],
  setup(props, { emit }) {
    const formatDate = (value: any) => value ? new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '-'
    const statusLabel = (value: any) => String(value || 'N/A').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
    const statusSeverity = (value: any) => ({ approved: 'success', completed: 'success', received: 'success', rejected: 'danger', cancelled: 'danger', pending: 'warn', pending_approval: 'warn', requested: 'warn', shipped: 'info' } as Record<string, string>)[value] || 'secondary'
    const rowClass = () => 'cursor-pointer hover:bg-orange-50'
    return () => props.loading
      ? h('div', { class: 'space-y-2' }, Array.from({ length: 7 }, (_, index) => (
          h('div', { key: index, class: 'grid grid-cols-5 gap-2' }, Array.from({ length: 5 }, (_, cell) => (
            h(Skeleton, { key: cell, height: '24px' })
          )))
        )))
      : h(DataTable, {
        value: props.rows,
        paginator: true,
        lazy: true,
        rows: (props.pagination as Pagination).per_page,
        first: ((props.pagination as Pagination).current_page - 1) * (props.pagination as Pagination).per_page,
        totalRecords: (props.pagination as Pagination).total,
        dataKey: 'id',
        class: 'p-datatable-sm p-datatable-fluid text-xs',
        stripedRows: true,
        rowClass,
        onPage: (event: any) => emit('page', event),
        onRowClick: (event: any) => emit('row-click', event),
        paginatorTemplate: 'FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageSelect',
        currentPageReportTemplate: 'Showing {first} to {last} of {totalRecords}',
        rowsPerPageOptions: [15, 25, 50],
      }, {
        empty: () => h('div', { class: 'py-8 text-center text-xs text-gray-500' }, [h('i', { class: 'pi pi-inbox mb-2 text-3xl text-gray-400' }), h('p', props.emptyLabel)]),
        default: () => {
          const columns = props.kind === 'adjustment'
            ? [
                ['adjustment_number', 'Reference'],
                ['reason', 'Reason'],
                ['adjustment_date', 'Date'],
                ['status', 'Status'],
              ]
            : props.kind === 'return'
              ? [['return_number', 'Reference'], ['return_type', 'Type'], ['requested_date', 'Date'], ['status', 'Status']]
              : [['reference_no', 'Reference'], ['from_branch', 'From'], ['to_branch', 'To'], ['transfer_date', 'Date'], ['status', 'Status']]
          return columns.map(([field, header]) => h(Column, { key: field, field, header, class: 'text-xs' }, {
            body: ({ data }: any) => field === 'status'
              ? h(Tag, { value: statusLabel(data[field]), severity: statusSeverity(data[field]), class: 'text-xs' })
              : field.includes('date')
                ? h('span', { class: 'text-xs text-gray-600' }, formatDate(data[field]))
                : field === 'from_branch'
                  ? h('span', { class: 'text-xs' }, data.from_branch?.name || 'N/A')
                  : field === 'to_branch'
                    ? h('span', { class: 'text-xs' }, data.to_branch?.name || 'N/A')
                    : h('span', { class: 'text-xs' }, String(data[field] || 'N/A').replace(/_/g, ' ')),
          }))
        },
      })
  },
})
</script>
