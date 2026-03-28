<template>
  <div class="space-y-6">
    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <Button icon="pi pi-arrow-left" text severity="secondary" @click="goBack" />
              <h1 class="text-2xl font-semibold text-slate-900">Store Details</h1>
            </div>
            <p class="ml-10 text-sm text-slate-500">Users, products, and performance summary.</p>
          </div>
          <Button icon="pi pi-refresh" label="Refresh" severity="info" outlined @click="loadStoreDetail" />
        </div>
      </template>
    </Card>

    <Skeleton v-if="loading" height="12rem" borderRadius="12px" />

    <Card v-else class="border border-slate-200 shadow-none">
      <template #content>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Store</p>
            <p class="text-base font-semibold text-slate-900">{{ store.store_name || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Status</p>
            <Tag :value="toTitle(store.status)" :severity="statusSeverity(store.status)" />
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Contact Person</p>
            <p class="text-sm text-slate-700">{{ store.contact_person || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Subscription</p>
            <Tag :value="toTitle(store.subscription_tier)" :severity="tierSeverity(store.subscription_tier)" />
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Email</p>
            <p class="text-sm text-slate-700">{{ store.email || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Phone</p>
            <p class="text-sm text-slate-700">{{ store.contact_number || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">City</p>
            <p class="text-sm text-slate-700">{{ store.city || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Address</p>
            <p class="text-sm text-slate-700">{{ store.address || '-' }}</p>
          </div>
        </div>
      </template>
    </Card>

    <div v-if="!loading" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Total Users</p>
          <p class="text-2xl font-semibold text-slate-900">{{ performance.users_total }}</p>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Active Users</p>
          <p class="text-2xl font-semibold text-slate-900">{{ performance.users_active }}</p>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Total Products</p>
          <p class="text-2xl font-semibold text-slate-900">{{ performance.products_total }}</p>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Active Products</p>
          <p class="text-2xl font-semibold text-slate-900">{{ performance.products_active }}</p>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Store Age</p>
          <p class="text-2xl font-semibold text-slate-900">{{ performance.age_days }}</p>
          <p class="text-xs text-slate-500">days</p>
        </template>
      </Card>
    </div>

    <Card class="border border-slate-200 shadow-none">
      <template #title>Users</template>
      <template #content>
        <DataTable :value="users" :loading="loading" dataKey="id" stripedRows paginator :rows="10" :rowsPerPageOptions="[10, 20, 50]">
          <Column field="name" header="Name" />
          <Column field="email" header="Email" />
          <Column field="role" header="Role" />
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

    <Card class="border border-slate-200 shadow-none">
      <template #title>Products</template>
      <template #content>
        <DataTable :value="products" :loading="loading" dataKey="id" stripedRows paginator :rows="10" :rowsPerPageOptions="[10, 20, 50]">
          <Column field="sku" header="SKU" />
          <Column field="product_name" header="Product" />
          <Column field="is_active" header="Status">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column field="cost_price" header="Cost Price">
            <template #body="{ data }">{{ formatMoney(data.cost_price) }}</template>
          </Column>
          <Column field="tax_rate" header="Tax Rate">
            <template #body="{ data }">{{ Number(data.tax_rate || 0).toFixed(2) }}%</template>
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
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const loading = ref(false)

const store = ref<any>({})
const users = ref<any[]>([])
const products = ref<any[]>([])
const performance = ref({
  users_total: 0,
  users_active: 0,
  products_total: 0,
  products_active: 0,
  age_days: 0,
})

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

const formatMoney = (value: number | string | null | undefined) => {
  const num = Number(value || 0)
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(num)
}

const loadStoreDetail = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get(`/api/admin/stores/${route.params.id}`)
    const payload = response.data?.data || {}
    store.value = payload.store || {}
    users.value = payload.users || []
    products.value = payload.products || []
    performance.value = payload.performance || performance.value
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load store detail',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'admin.stores' })
}

onMounted(loadStoreDetail)
</script>
