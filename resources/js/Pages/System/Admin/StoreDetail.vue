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
          <div class="flex gap-2">
            <Button icon="pi pi-refresh" label="Refresh" severity="info" outlined @click="loadStoreDetail" />
            <Button
              :label="store.status === 'active' ? 'Deactivate' : 'Inactive'"
              :icon="store.status === 'active' ? 'pi pi-ban' : 'pi pi-lock'"
              :severity="store.status === 'active' ? 'warning' : 'secondary'"
              outlined
              :disabled="store.status !== 'active'"
              @click="openDeactivateDialog"
            />
            <Button icon="pi pi-trash" label="Delete" severity="danger" outlined @click="openDeleteDialog" />
          </div>
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
            <Tag :value="displayStatus(store.status)" :severity="statusSeverity(store.status)" />
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Deactivation</p>
            <Tag
              :value="store.deleted_at ? 'Deleted' : store.deactivated_at ? 'Deactivated' : 'None'"
              :severity="store.deleted_at ? 'danger' : store.deactivated_at ? 'warning' : 'secondary'"
            />
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
          <div class="md:col-span-2 xl:col-span-4" v-if="store.deactivation_reason">
            <p class="text-xs uppercase tracking-wide text-slate-500">Reason</p>
            <p class="mt-1 rounded-xl bg-amber-50 p-3 text-sm text-amber-900 whitespace-pre-line">{{ store.deactivation_reason }}</p>
          </div>
        </div>
      </template>
    </Card>

    <div v-if="!loading" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
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
          <p class="text-xs uppercase tracking-wide text-slate-500">Total Customers</p>
          <p class="text-2xl font-semibold text-slate-900">{{ performance.customers_total }}</p>
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
          <Column field="created_at" header="Created">
            <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Card class="border border-slate-200 shadow-none">
      <template #title>Customers</template>
      <template #content>
        <DataTable :value="customers" :loading="loading" dataKey="id" stripedRows paginator :rows="10" :rowsPerPageOptions="[10, 20, 50]">
          <Column field="name" header="Name" />
          <Column field="email" header="Email" />
          <Column field="verification_status" header="Verification">
            <template #body="{ data }">
              <Tag :value="toTitle(data.verification_status)" :severity="statusSeverity(data.verification_status)" />
            </template>
          </Column>
          <Column field="orders_count" header="Orders" />
          <Column field="last_order_at" header="Last Order">
            <template #body="{ data }">{{ formatDateTime(data.last_order_at) }}</template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <StoreModulesDialog
      v-model:modelValue="showModulesDialog"
      :store-id="store.id"
      :store-name="store.store_name"
    />

    <Dialog v-model:visible="actionDialog.visible" modal :header="actionDialog.title" class="w-full max-w-lg">
      <div class="space-y-4">
        <p class="text-sm text-slate-600 whitespace-pre-line">{{ actionDialog.message }}</p>
        <Textarea v-model="actionDialog.reason" autoResize rows="4" class="w-full" placeholder="Type the reason here" />
        <Message v-if="actionDialog.error" severity="error" :closable="false">
          {{ actionDialog.error }}
        </Message>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="actionDialog.visible = false" />
        <Button :label="actionDialog.confirmLabel" :severity="actionDialog.severity" :loading="actionDialog.loading" @click="submitAction" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Message from 'primevue/message'
import StoreModulesDialog from '@/Components/Admin/StoreModulesDialog.vue'
import { showResponseDialog } from '@/utils/responseDialogBus'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const showModulesDialog = ref(false)

const store = ref<any>({})
const users = ref<any[]>([])
const products = ref<any[]>([])
const customers = ref<any[]>([])
const performance = ref({
  users_total: 0,
  users_active: 0,
  products_total: 0,
  products_active: 0,
  customers_total: 0,
  age_days: 0,
})
const actionDialog = ref({
  visible: false,
  mode: '' as 'deactivate' | 'delete' | '',
  title: '',
  message: '',
  confirmLabel: 'Confirm',
  severity: 'warning' as 'warning' | 'danger',
  reason: '',
  error: '',
  loading: false,
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
  const normalized = String(status || '').toLowerCase()
  switch (normalized) {
    case 'active':
    case 'verified':
      return 'success'
    case 'pending':
    case 'unverified':
      return 'warning'
    case 'rejected':
    case 'suspended':
      return 'danger'
    default:
      return 'secondary'
  }
}

const displayStatus = (value: string | null | undefined) => {
  const normalized = String(value || '').toLowerCase()
  if (normalized === 'pending') return 'Unverified'
  return toTitle(value)
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

const openModulesDialog = async () => {
  showModulesDialog.value = true
}

const loadStoreDetail = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get(`/api/admin/stores/${route.params.id}`)
    const payload = response.data?.data || {}
    store.value = payload.store || {}
    users.value = payload.users || []
    products.value = payload.products || []
    customers.value = payload.customers || []
    performance.value = payload.performance || performance.value
  } catch (error: any) {
    showResponseDialog({
      severity: 'error',
      title: 'Failed to Load Store',
      message: error?.response?.data?.message || 'Failed to load store detail',
    })
  } finally {
    loading.value = false
  }
}

const openDeactivateDialog = () => {
  actionDialog.value = {
    ...actionDialog.value,
    visible: true,
    mode: 'deactivate',
    title: store.value?.status === 'active' ? 'Deactivate Store' : 'Reactivate Store',
    message:
      store.value?.status === 'active'
        ? 'This will mark the store as inactive. Please enter a reason so other users can understand why it was deactivated.'
        : 'This will reactivate the store. Please enter a short reason for the status change.',
    confirmLabel: store.value?.status === 'active' ? 'Deactivate' : 'Reactivate',
    severity: 'warning',
    reason: '',
    error: '',
    loading: false,
  }
}

const openDeleteDialog = () => {
  actionDialog.value = {
    ...actionDialog.value,
    visible: true,
    mode: 'delete',
    title: 'Delete Store',
    message: 'This will soft delete the store. It will be hidden from normal lists but can still be restored later if needed. Please enter a reason.',
    confirmLabel: 'Delete',
    severity: 'danger',
    reason: '',
    error: '',
    loading: false,
  }
}

const submitAction = async () => {
  if (!actionDialog.value.reason.trim()) {
    actionDialog.value.error = 'Please provide a reason before continuing.'
    return
  }

  actionDialog.value.loading = true
  actionDialog.value.error = ''

  try {
    const storeId = route.params.id
    if (actionDialog.value.mode === 'deactivate') {
      await axiosClient.patch(`/api/admin/stores/${storeId}/deactivate`, { reason: actionDialog.value.reason })
      showResponseDialog({
        severity: 'success',
        title: 'Store Updated',
        message: 'The store has been deactivated successfully.',
      })
    } else if (actionDialog.value.mode === 'delete') {
      await axiosClient.delete(`/api/admin/stores/${storeId}`, { data: { reason: actionDialog.value.reason } })
      showResponseDialog({
        severity: 'success',
        title: 'Store Deleted',
        message: 'The store has been soft deleted successfully.',
      })
    }
    actionDialog.value.visible = false
    await loadStoreDetail()
  } catch (error: any) {
    actionDialog.value.error = error?.response?.data?.message || 'Action failed. Please try again.'
  } finally {
    actionDialog.value.loading = false
  }
}

const goBack = () => {
  router.push({ name: 'admin.stores' })
}

onMounted(loadStoreDetail)
</script>
