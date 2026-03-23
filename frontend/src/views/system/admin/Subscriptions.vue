<template>
  <div class="space-y-6">
    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Platform Billing</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Subscription Management</h1>
            <p class="text-sm text-slate-500">Manage store plans, renewals, and expiration timeline.</p>
          </div>
          <Button icon="pi pi-refresh" label="Refresh" severity="info" outlined @click="loadAll" />
        </div>
      </template>
    </Card>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Total Stores</p>
          <p class="mt-2 text-3xl font-semibold text-slate-900">{{ stats.total_stores }}</p>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Active Paid</p>
          <p class="mt-2 text-3xl font-semibold text-emerald-600">{{ stats.active_paid }}</p>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Expiring in 14 days</p>
          <p class="mt-2 text-3xl font-semibold text-amber-600">{{ stats.expiring_soon }}</p>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Expired</p>
          <p class="mt-2 text-3xl font-semibold text-rose-600">{{ stats.expired }}</p>
        </template>
      </Card>
    </div>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-12">
          <div class="md:col-span-5">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search store, owner, email..." fluid />
            </IconField>
          </div>
          <div class="md:col-span-3">
            <Select
              v-model="filters.tier"
              :options="tierOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Plan Tier"
              showClear
              fluid
            />
          </div>
          <div class="md:col-span-3">
            <Select
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Store Status"
              showClear
              fluid
            />
          </div>
          <div class="md:col-span-1 flex items-center justify-end">
            <Button icon="pi pi-filter-slash" text severity="secondary" @click="clearFilters" />
          </div>
        </div>

        <DataTable :value="stores" :loading="loading" stripedRows dataKey="id" paginator :rows="10" :rowsPerPageOptions="[10, 20, 50]">
          <Column field="store_name" header="Store" sortable>
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-slate-900">{{ data.store_name }}</p>
                <p class="text-xs text-slate-500">{{ data.contact_person || 'No contact person' }}</p>
              </div>
            </template>
          </Column>
          <Column field="email" header="Contact" sortable>
            <template #body="{ data }">
              <div>
                <p class="text-sm text-slate-800">{{ data.email || '-' }}</p>
                <p class="text-xs text-slate-500">{{ data.contact_number || '-' }}</p>
              </div>
            </template>
          </Column>
          <Column field="subscription_tier" header="Plan" sortable>
            <template #body="{ data }">
              <Tag :value="toTitle(data.subscription_tier)" :severity="tierSeverity(data.subscription_tier)" />
            </template>
          </Column>
          <Column field="subscription_ends_at" header="Ends At" sortable>
            <template #body="{ data }">
              <div>
                <p class="text-sm text-slate-800">{{ formatDate(data.subscription_ends_at) }}</p>
                <p class="text-xs" :class="daysClass(data.days_remaining)">
                  {{ daysLabel(data.days_remaining, data.subscription_tier) }}
                </p>
              </div>
            </template>
          </Column>
          <Column field="status" header="Store Status" sortable>
            <template #body="{ data }">
              <Tag :value="toTitle(data.status)" :severity="storeStatusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Usage">
            <template #body="{ data }">
              <p class="text-xs text-slate-500">{{ data.users_count }} users / {{ data.products_count }} products</p>
            </template>
          </Column>
          <Column header="Actions" style="width: 180px">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Button icon="pi pi-pencil" severity="info" text rounded @click="openManageDialog(data)" />
                <Button icon="pi pi-calendar-plus" severity="success" text rounded @click="quickExtend(data.id, 30)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="manageDialog" header="Manage Subscription" :style="{ width: '560px' }" modal>
      <div class="space-y-4" v-if="selectedStore">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
          <p class="font-semibold text-slate-900">{{ selectedStore.store_name }}</p>
          <p class="text-xs text-slate-500">{{ selectedStore.email || 'No email' }}</p>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Plan Tier</label>
            <Select
              v-model="editForm.subscription_tier"
              :options="tierOptions"
              optionLabel="label"
              optionValue="value"
              fluid
            />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Store Status</label>
            <Select
              v-model="editForm.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              fluid
            />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Subscription End Date</label>
            <DatePicker v-model="editForm.subscription_ends_at" dateFormat="yy-mm-dd" showIcon fluid />
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button label="Extend 30 days" icon="pi pi-calendar-plus" severity="secondary" outlined @click="extendInDialog(30)" />
          <Button label="Extend 365 days" icon="pi pi-calendar-plus" severity="secondary" outlined @click="extendInDialog(365)" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" text @click="manageDialog = false" />
        <Button label="Save" icon="pi pi-check" severity="info" :loading="saving" @click="saveSubscription" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axiosClient from '@/axios'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import DatePicker from 'primevue/datepicker'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'

const toast = useToast()

const loading = ref(false)
const saving = ref(false)
const manageDialog = ref(false)
const stores = ref<any[]>([])
const selectedStore = ref<any | null>(null)
const stats = reactive({
  total_stores: 0,
  active_paid: 0,
  expiring_soon: 0,
  expired: 0,
})

const filters = reactive({
  search: '',
  tier: null as string | null,
  status: null as string | null,
})

const editForm = reactive({
  subscription_tier: 'free',
  subscription_ends_at: null as Date | null,
  status: 'active',
})

const tierOptions = [
  { label: 'Free', value: 'free' },
  { label: 'Basic', value: 'basic' },
  { label: 'Premium', value: 'premium' },
  { label: 'Enterprise', value: 'enterprise' },
]

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Pending', value: 'pending' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
]

const queryParams = computed(() => ({
  search: filters.search || undefined,
  tier: filters.tier || undefined,
  status: filters.status || undefined,
  per_page: 100,
}))

const toTitle = (value: string | null | undefined) =>
  String(value || 'unknown')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

const formatDate = (value: string | null | undefined) => {
  if (!value) return 'No expiry'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
}

const tierSeverity = (tier: string) => {
  switch (tier) {
    case 'enterprise': return 'help'
    case 'premium': return 'success'
    case 'basic': return 'info'
    default: return 'secondary'
  }
}

const storeStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'pending': return 'warning'
    case 'suspended': return 'danger'
    default: return 'secondary'
  }
}

const daysLabel = (daysRemaining: number | null, tier: string) => {
  if (tier === 'free') return 'Free plan'
  if (daysRemaining === null || daysRemaining === undefined) return 'No end date'
  if (daysRemaining < 0) return `Expired ${Math.abs(daysRemaining)} day(s) ago`
  if (daysRemaining === 0) return 'Expires today'
  return `${daysRemaining} day(s) left`
}

const daysClass = (daysRemaining: number | null) => {
  if (daysRemaining === null || daysRemaining === undefined) return 'text-slate-500'
  if (daysRemaining < 0) return 'text-rose-600'
  if (daysRemaining <= 14) return 'text-amber-600'
  return 'text-emerald-600'
}

const loadStores = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/admin/subscriptions', { params: queryParams.value })
    stores.value = response.data?.data?.data || response.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load subscriptions', life: 3000 })
  } finally {
    loading.value = false
  }
}

const loadStats = async () => {
  try {
    const response = await axiosClient.get('/api/admin/subscriptions/stats')
    const data = response.data?.data || {}
    stats.total_stores = Number(data.total_stores || 0)
    stats.active_paid = Number(data.active_paid || 0)
    stats.expiring_soon = Number(data.expiring_soon || 0)
    stats.expired = Number(data.expired || 0)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load stats', life: 3000 })
  }
}

const loadAll = async () => {
  await Promise.all([loadStores(), loadStats()])
}

const openManageDialog = (store: any) => {
  selectedStore.value = store
  editForm.subscription_tier = store.subscription_tier || 'free'
  editForm.status = store.status || 'active'
  editForm.subscription_ends_at = store.subscription_ends_at ? new Date(store.subscription_ends_at) : null
  manageDialog.value = true
}

const saveSubscription = async () => {
  if (!selectedStore.value) return
  saving.value = true
  try {
    await axiosClient.put(`/api/admin/subscriptions/${selectedStore.value.id}`, {
      subscription_tier: editForm.subscription_tier,
      status: editForm.status,
      subscription_ends_at: editForm.subscription_ends_at
        ? editForm.subscription_ends_at.toISOString().slice(0, 10)
        : null,
    })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Subscription updated successfully', life: 2500 })
    manageDialog.value = false
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to update subscription', life: 3000 })
  } finally {
    saving.value = false
  }
}

const quickExtend = async (storeId: number, days: number) => {
  try {
    await axiosClient.post(`/api/admin/subscriptions/${storeId}/extend`, { days })
    toast.add({ severity: 'success', summary: 'Extended', detail: `Subscription extended by ${days} days`, life: 2200 })
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to extend subscription', life: 3000 })
  }
}

const extendInDialog = async (days: number) => {
  if (!selectedStore.value) return
  await quickExtend(Number(selectedStore.value.id), days)
  await loadStores()
  const updated = stores.value.find((store) => Number(store.id) === Number(selectedStore.value.id))
  if (updated) {
    selectedStore.value = updated
    editForm.subscription_ends_at = updated.subscription_ends_at ? new Date(updated.subscription_ends_at) : null
  }
}

const clearFilters = () => {
  filters.search = ''
  filters.tier = null
  filters.status = null
}

watch(() => [filters.search, filters.tier, filters.status], () => {
  loadStores()
})

onMounted(loadAll)
</script>

