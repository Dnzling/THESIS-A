<template>
  <div class="min-h-screen p-4 max-w-7xl mx-auto space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Returns</h1>
        <p class="text-sm text-gray-500">Review and track customer return requests.</p>
      </div>
      <div class="flex gap-2">
        <Button icon="pi pi-refresh" label="Refresh" severity="secondary" outlined size="small" @click="loadReturns" />
      </div>
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText
              v-model="filters.search"
              placeholder="Search order #, customer, product"
              class="w-full"
              size="small"
            />
          </IconField>

          <Select
            v-model="filters.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Status"
            class="w-full"
            size="small"
            showClear
          />

          <DatePicker
            v-model="filters.date_range"
            selectionMode="range"
            placeholder="Date range"
            class="w-full"
            size="small"
            showIcon
          />

          <div class="flex gap-2 justify-end">
            <Button icon="pi pi-filter" label="Apply" size="small" @click="applyFilters" />
            <Button icon="pi pi-filter-slash" label="Reset" severity="secondary" outlined size="small" @click="resetFilters" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <DataTable
          :value="rows"
          :loading="loading"
          dataKey="id"
          stripedRows
          paginator
          lazy
          :rows="filters.per_page"
          :totalRecords="totalRecords"
          :rowsPerPageOptions="[15, 25, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageSelect"
          class="p-datatable-sm"
          @page="onPage"
          @sort="onSort"
          :sortField="sortField"
          :sortOrder="sortOrder"
        >
          <template #empty>
            <div class="text-center py-10">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No return requests found</p>
            </div>
          </template>

          <Column field="created_at" header="Date" sortable style="width: 12%">
            <template #body="{ data }">
              {{ formatDate(data.created_at) }}
            </template>
          </Column>

          <Column header="Order" sortable field="order.order_number" style="width: 16%">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium text-gray-900">{{ data.order?.order_number || `#${data.order_id}` }}</div>
                <div class="text-xs text-gray-500">Item #{{ data.order_item_id }}</div>
              </div>
            </template>
          </Column>

          <Column header="Customer" style="width: 18%">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium text-gray-900">{{ data.user?.full_name || data.user?.email || 'N/A' }}</div>
                <div class="text-xs text-gray-500">{{ data.user?.email || '' }}</div>
              </div>
            </template>
          </Column>

          <Column header="Product" style="width: 22%">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium text-gray-900">
                  {{ data.order_item?.product?.product_name || data.order_item?.product_name || 'N/A' }}
                </div>
                <div class="text-xs text-gray-500">
                  SKU: {{ data.order_item?.product?.sku || data.order_item?.sku || 'N/A' }}
                </div>
              </div>
            </template>
          </Column>

          <Column header="Qty" style="width: 8%">
            <template #body="{ data }">
              <span class="font-medium">{{ data.requested_quantity ?? 1 }}</span>
            </template>
          </Column>

          <Column field="status" header="Status" sortable style="width: 14%">
            <template #body="{ data }">
              <Tag :value="prettyStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <Column header="Attachments" style="width: 10%">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Badge :value="(data.evidence_urls || []).length" severity="info" />
                <Button
                  icon="pi pi-images"
                  text
                  size="small"
                  severity="secondary"
                  :disabled="!(data.evidence_urls || []).length"
                  v-tooltip="'View attachments'"
                  @click="openAttachments(data)"
                />
              </div>
            </template>
          </Column>

          <Column header="Actions" style="width: 10%">
            <template #body="{ data }">
              <Button
                icon="pi pi-eye"
                label="View"
                size="small"
                text
                severity="info"
                @click="router.push({ name: 'sales.returns.detail', params: { id: data.id } })"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="attachmentsDialogVisible" header="Attachments" modal class="w-full max-w-5xl">
      <div v-if="attachments.length" class="space-y-3">
        <Galleria
          :value="attachments"
          :numVisible="6"
          :circular="true"
          :showItemNavigators="true"
          :showThumbnails="true"
          containerStyle="max-width: 100%"
        >
          <template #item="{ item }">
            <div class="flex justify-center bg-black/5 rounded-lg overflow-hidden">
              <img :src="item.url" :alt="item.name" class="max-h-[520px] w-auto object-contain" />
            </div>
          </template>
          <template #thumbnail="{ item }">
            <img :src="item.url" :alt="item.name" class="h-14 w-14 object-cover rounded-md" />
          </template>
        </Galleria>
      </div>
      <div v-else class="py-10 text-center text-sm text-gray-600">No attachments.</div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined size="small" @click="attachmentsDialogVisible = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import salesService from '@/services/sales.service'

const router = useRouter()
const toast = useToast()

const loading = ref(false)
const rows = ref<any[]>([])
const totalRecords = ref(0)

const sortField = ref('created_at')
const sortOrder = ref(-1)

const filters = reactive({
  search: '',
  status: null as null | string,
  date_range: null as any,
  page: 1,
  per_page: 15,
})

const statusOptions = computed(() => ([
  { label: 'Pending Verification', value: 'pending_verification' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Received', value: 'received' },
  { label: 'Refunded', value: 'refunded' },
]))

const toIsoDate = (date: Date) => {
  const d = new Date(date)
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

const buildParams = () => {
  const params: any = {
    search: filters.search || undefined,
    status: filters.status || undefined,
    page: filters.page,
    per_page: filters.per_page,
    sort_by: sortField.value,
    sort_order: sortOrder.value === 1 ? 'asc' : 'desc',
  }

  const range = filters.date_range as any
  if (Array.isArray(range) && range[0] && range[1]) {
    params.start_date = toIsoDate(range[0])
    params.end_date = toIsoDate(range[1])
  }

  return params
}

const loadReturns = async () => {
  loading.value = true
  try {
    const res = await salesService.getReturns(buildParams())
    rows.value = res?.data || []
    totalRecords.value = res?.meta?.total || 0
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load returns',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  filters.page = 1
  loadReturns()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = null
  filters.date_range = null
  filters.page = 1
  filters.per_page = 15
  sortField.value = 'created_at'
  sortOrder.value = -1
  loadReturns()
}

const onPage = (event: any) => {
  filters.page = (event.page || 0) + 1
  filters.per_page = event.rows || filters.per_page
  loadReturns()
}

const onSort = (event: any) => {
  sortField.value = event.sortField || 'created_at'
  sortOrder.value = event.sortOrder ?? -1
  loadReturns()
}

const formatDate = (value: any) => {
  if (!value) return '—'
  const date = new Date(value)
  return new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit' }).format(date)
}

const prettyStatus = (value: any) => {
  const raw = String(value || '')
  if (raw === 'pending_verification') return 'Return Pending'
  const v = raw.replace(/_/g, ' ')
  return v ? v.charAt(0).toUpperCase() + v.slice(1) : '—'
}

const statusSeverity = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'approved' || normalized === 'received' || normalized === 'refunded') return 'success'
  if (normalized === 'rejected') return 'danger'
  return 'warning'
}

const attachmentsDialogVisible = ref(false)
const attachments = ref<{ url: string; name: string }[]>([])

const openAttachments = (row: any) => {
  const urls: string[] = Array.isArray(row?.evidence_urls) ? row.evidence_urls : []
  attachments.value = urls.map((url, idx) => ({
    url,
    name: `Attachment ${idx + 1}`,
  }))
  attachmentsDialogVisible.value = true
}

watch(
  () => filters.search,
  () => {
    filters.page = 1
  },
)

onMounted(loadReturns)
</script>
