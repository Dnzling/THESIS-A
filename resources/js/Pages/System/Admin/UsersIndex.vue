<template>
  <div class="space-y-6">
    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 class="text-2xl font-semibold text-slate-900">Users</h1>
            <p class="text-sm text-slate-500">All users across stores.</p>
          </div>
          <Button icon="pi pi-refresh" label="Refresh" severity="info" outlined @click="loadUsers" />
        </div>
      </template>
    </Card>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
          <div class="md:col-span-8">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search user by name or email..." fluid />
            </IconField>
          </div>
          <div class="md:col-span-3">
            <Select
              v-model="filters.is_active"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Filter status"
              showClear
              fluid
            />
          </div>
          <div class="md:col-span-1 flex justify-end">
            <Button icon="pi pi-filter-slash" text severity="secondary" @click="clearFilters" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <DataTable
          :value="users"
          :loading="loading"
          dataKey="id"
          stripedRows
          paginator
          lazy
          :rows="pageState.rows"
          :totalRecords="pageState.total"
          :first="(pageState.page - 1) * pageState.rows"
          :rowsPerPageOptions="[10, 20, 50]"
          @page="onPage"
        >
          <Column field="full_name" header="User">
            <template #body="{ data }">
              <div>
                <p class="font-medium text-slate-900">{{ data.full_name || '-' }}</p>
                <p class="text-xs text-slate-500">{{ data.email || '-' }}</p>
              </div>
            </template>
          </Column>

          <Column field="display_role" header="Role">
            <template #body="{ data }">
              <Tag :value="data.display_role || data.role || 'N/A'" severity="info" />
            </template>
          </Column>

          <Column header="Store">
            <template #body="{ data }">
              <p class="text-sm text-slate-700">{{ data.store?.name || 'No Store' }}</p>
            </template>
          </Column>

          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>

          <Column field="created_at" header="Created">
            <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const toast = useToast()
const loading = ref(false)
const users = ref<any[]>([])

const filters = reactive({
  search: '',
  is_active: null as boolean | null,
})

const pageState = reactive({
  page: 1,
  rows: 10,
  total: 0,
})

const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false },
]

const formatDateTime = (value: string | null | undefined) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'
  return new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const loadUsers = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/admin/users', {
      params: {
        search: filters.search || undefined,
        is_active: filters.is_active === null ? undefined : filters.is_active,
        page: pageState.page,
        per_page: pageState.rows,
      },
    })

    const payload = response.data?.data
    const pageData = payload?.data || []
    users.value = pageData
    pageState.total = Number(payload?.meta?.total || payload?.total || pageData.length || 0)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load users',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  pageState.page = Number(event.page || 0) + 1
  pageState.rows = Number(event.rows || 10)
  loadUsers()
}

const clearFilters = () => {
  filters.search = ''
  filters.is_active = null
  pageState.page = 1
}

watch(() => [filters.search, filters.is_active], () => {
  pageState.page = 1
  loadUsers()
})

onMounted(loadUsers)
</script>
