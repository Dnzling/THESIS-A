<template>
  <div class="space-y-6 p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">Product Activity Logs</h1>
        <p class="text-sm text-gray-500">Track create, update, delete, upload, and pricing changes.</p>
      </div>
      <Button label="Back to Products" icon="pi pi-arrow-left" severity="secondary" outlined @click="goProducts" />
    </div>

    <Card>
      <template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
          <InputText v-model="filters.search" placeholder="Search logs" @input="reloadFromStart" />
          <InputText v-model="filters.action" placeholder="Action contains" @input="reloadFromStart" />
          <InputNumber v-model="filters.entity_id" :min="1" placeholder="Product ID" fluid @input="reloadFromStart" />
          <DatePicker v-model="filters.from" dateFormat="yy-mm-dd" placeholder="From date" showIcon fluid @date-select="reloadFromStart" />
          <DatePicker v-model="filters.to" dateFormat="yy-mm-dd" placeholder="To date" showIcon fluid @date-select="reloadFromStart" />
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable
          :value="logs"
          :loading="loading"
          paginator
          :rows="pagination.per_page"
          :totalRecords="pagination.total"
          :first="(pagination.page - 1) * pagination.per_page"
          @page="onPage"
          responsiveLayout="scroll"
          stripedRows
          class="p-datatable-sm"
        >
          <template #empty>
            <div class="py-8 text-center text-gray-500">No product logs found.</div>
          </template>

          <Column field="created_at" header="Date">
            <template #body="{ data }">
              {{ formatDate(data.created_at) }}
            </template>
          </Column>
          <Column field="user" header="User" />
          <Column field="action" header="Action">
            <template #body="{ data }">
              <Tag :value="data.action" severity="info" />
            </template>
          </Column>
          <Column field="description" header="Description" />
          <Column field="entity_id" header="Entity ID" />
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import merchandisingService from '@/services/merchandising.service'

const router = useRouter()
const logs = ref<any[]>([])
const loading = ref(false)
const pagination = reactive({
  page: 1,
  per_page: 20,
  total: 0,
})

const filters = reactive({
  search: '',
  action: '',
  entity_id: null as number | null,
  from: null as Date | null,
  to: null as Date | null,
})

function fmt(date: Date | null) {
  if (!date) return undefined
  return date.toISOString().slice(0, 10)
}

function formatDate(value: string) {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function loadLogs() {
  loading.value = true
  try {
    const response = await merchandisingService.getActivityLog({
      page: pagination.page,
      per_page: pagination.per_page,
      search: filters.search || undefined,
      action: filters.action || undefined,
      entity_id: filters.entity_id || undefined,
      from: fmt(filters.from),
      to: fmt(filters.to),
    })

    const payload = response.data || {}
    logs.value = payload?.data || []
    pagination.total = Number(payload?.total || logs.value.length)
    pagination.per_page = Number(payload?.per_page || pagination.per_page)
    pagination.page = Number(payload?.current_page || pagination.page)
  } finally {
    loading.value = false
  }
}

function onPage(event: any) {
  pagination.page = event.page + 1
  pagination.per_page = event.rows
  loadLogs()
}

function reloadFromStart() {
  pagination.page = 1
  loadLogs()
}

function goProducts() {
  router.push({ name: 'merchandising.products' })
}

onMounted(loadLogs)
</script>

