<template>
  <div class="space-y-6">
    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 class="text-2xl font-semibold text-slate-900">Stores</h1>
            <p class="text-sm text-slate-500">Browse all stores with quick performance visibility.</p>
          </div>
          <Button icon="pi pi-refresh" label="Refresh" severity="info" outlined @click="loadStores" />
        </div>
      </template>
    </Card>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
          <div class="md:col-span-7">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search by store, owner, email, city..." fluid />
            </IconField>
          </div>
          <div class="md:col-span-4">
            <Select
              v-model="filters.status"
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
        <DataTable :value="stores" :loading="loading" dataKey="id" stripedRows paginator :rows="10" :rowsPerPageOptions="[10, 20, 50]">
          <Column field="store_name" header="Store" sortable>
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-slate-900">{{ data.store_name }}</p>
                <p class="text-xs text-slate-500">{{ data.contact_person || 'No contact person' }}</p>
              </div>
            </template>
          </Column>
          <Column field="email" header="Contact">
            <template #body="{ data }">
              <div>
                <p class="text-sm text-slate-800">{{ data.email || '-' }}</p>
                <p class="text-xs text-slate-500">{{ data.contact_number || '-' }}</p>
              </div>
            </template>
          </Column>
          <Column field="city" header="Location" sortable>
            <template #body="{ data }">
              <p class="text-sm text-slate-700">{{ data.city || '-' }}</p>
            </template>
          </Column>
          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <Tag :value="toTitle(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column field="subscription_tier" header="Subscription" sortable>
            <template #body="{ data }">
              <Tag :value="toTitle(data.subscription_tier)" :severity="tierSeverity(data.subscription_tier)" />
            </template>
          </Column>
          <Column header="Users/Products">
            <template #body="{ data }">
              <p class="text-xs text-slate-500">{{ data.users_count }} / {{ data.products_count }}</p>
            </template>
          </Column>
          <Column header="Actions" style="width: 120px">
            <template #body="{ data }">
              <Button icon="pi pi-arrow-right" text severity="info" rounded @click="openDetail(data.id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
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

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const stores = ref<any[]>([])

const filters = reactive({
  search: '',
  status: null as string | null,
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Pending', value: 'pending' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Verified', value: 'verified' },
  { label: 'Rejected', value: 'rejected' },
]

const toTitle = (value: string | null | undefined) =>
  String(value || 'unknown')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

const tierSeverity = (tier: string) => {
  switch (tier) {
    case 'enterprise': return 'help'
    case 'premium': return 'success'
    case 'basic': return 'info'
    default: return 'secondary'
  }
}

const statusSeverity = (status: string) => {
  switch (status) {
    case 'active':
    case 'verified':
      return 'success'
    case 'pending':
      return 'warning'
    case 'rejected':
    case 'suspended':
      return 'danger'
    default:
      return 'secondary'
  }
}

const loadStores = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/admin/stores', {
      params: {
        search: filters.search || undefined,
        status: filters.status || undefined,
        per_page: 100,
      },
    })
    stores.value = response.data?.data?.data || response.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load stores', life: 3000 })
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.status = null
}

const openDetail = (id: number) => {
  router.push({ name: 'admin.stores.detail', params: { id } })
}

watch(() => [filters.search, filters.status], () => {
  loadStores()
})

onMounted(loadStores)
</script>

