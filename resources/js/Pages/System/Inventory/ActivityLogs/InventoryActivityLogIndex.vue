<template>
  <div class="p-4 min-h-screen space-y-4">
    <div>
      <h1 class="text-xl font-bold text-gray-800">Inventory Activity Logs</h1>
      <p class="text-xs text-gray-500 mt-0.5">Tracks create/update/delete/approve actions across inventory modules.</p>
    </div>

    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
          <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-gray-700 mb-1">Search</label>
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Action, description, entity" class="w-full p-inputtext-sm" />
            </IconField>
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Entity</label>
            <Select
              v-model="filters.entity_type"
              :options="entityTypes"
              optionLabel="label"
              optionValue="value"
              placeholder="All"
              showClear
              class="w-full"
            />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">From</label>
            <DatePicker v-model="filters.from_date" dateFormat="yy-mm-dd" class="w-full" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">To</label>
            <DatePicker v-model="filters.to_date" dateFormat="yy-mm-dd" class="w-full" />
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable
          :value="logs"
          :loading="loading"
          paginator
          :rows="filters.per_page"
          :totalRecords="totalRecords"
          :lazy="true"
          :first="(filters.page - 1) * filters.per_page"
          class="p-datatable-sm"
          stripedRows
          @page="onPage"
        >
          <Column field="created_at" header="Date" style="width: 170px">
            <template #body="{ data }">
              <div class="text-xs">
                <div>{{ formatDate(data.created_at) }}</div>
                <div class="text-gray-500">{{ formatTime(data.created_at) }}</div>
              </div>
            </template>
          </Column>
          <Column field="action" header="Action" style="min-width: 200px">
            <template #body="{ data }">
              <Tag :value="formatKey(data.action)" severity="info" />
            </template>
          </Column>
          <Column field="entity_type" header="Entity" style="min-width: 170px">
            <template #body="{ data }">
              {{ formatKey(data.entity_type) }}
            </template>
          </Column>
          <Column field="description" header="Description" style="min-width: 240px" />
          <Column header="Source ID" style="width: 90px">
            <template #body="{ data }">
              {{ data.entity_id || '-' }}
            </template>
          </Column>
          <Column header="By" style="min-width: 160px">
            <template #body="{ data }">
              {{ data.user ? `${data.user.fname || ''} ${data.user.lname || ''}`.trim() : '-' }}
            </template>
          </Column>
          <Column header="Action" style="width: 80px">
            <template #body="{ data }">
              <Button
                icon="pi pi-eye"
                severity="info"
                text
                rounded
                size="small"
                @click="openDetail(data.id)"
              />
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-gray-500">No inventory activity logs found.</div>
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

const router = useRouter()
const toast = useToast()

const loading = ref(false)
const logs = ref<any[]>([])
const totalRecords = ref(0)

const filters = reactive({
  search: '',
  entity_type: null as string | null,
  from_date: null as Date | null,
  to_date: null as Date | null,
  page: 1,
  per_page: 15,
})

const entityTypes = [
  { label: 'Stock Transfer', value: 'inventory.stock_transfer' },
  { label: 'Stock Adjustment', value: 'inventory.stock_adjustment' },
  { label: 'Stock Count', value: 'inventory.stock_count' },
  { label: 'Branch Inventory', value: 'inventory.branch_inventory' },
]

const formatDateParam = (date: Date) => date.toISOString().split('T')[0]

const loadLogs = async () => {
  loading.value = true
  try {
    const params: any = {
      page: filters.page,
      per_page: filters.per_page,
    }
    if (filters.search) params.search = filters.search
    if (filters.entity_type) params.entity_type = filters.entity_type
    if (filters.from_date) params.from_date = formatDateParam(filters.from_date)
    if (filters.to_date) params.to_date = formatDateParam(filters.to_date)

    const response = await inventoryService.getActivityLogs(params)
    if (response.success) {
      logs.value = Array.isArray(response.data?.data) ? response.data.data : []
      totalRecords.value = Number(response.data?.total || 0)
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load inventory activity logs',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadLogs()
}

const openDetail = (id: number) => {
  router.push({ name: 'inventory.activity-logs.detail', params: { id } })
}

const formatDate = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? '-' : d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
}

const formatTime = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? '-' : d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const formatKey = (value?: string) => (value ? value.replace(/^inventory\./, '').replaceAll('.', ' ') : 'N/A')

let timer: ReturnType<typeof setTimeout> | null = null
watch(
  () => [filters.search, filters.entity_type, filters.from_date, filters.to_date],
  () => {
    filters.page = 1
    if (timer) clearTimeout(timer)
    timer = setTimeout(loadLogs, 250)
  },
)

onMounted(loadLogs)
</script>

