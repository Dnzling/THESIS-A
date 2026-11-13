<template>
  <div class="w-full min-w-0 max-w-full space-y-4 overflow-x-hidden text-sm">
    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-orange-600">Platform billing</p>
            <h1 class="mt-1 text-xl font-semibold text-slate-950">Subscriptions</h1>
            <p class="mt-1 text-xs text-slate-500">Manage plans, store subscriptions, and renewal timelines.</p>
          </div>
          <Button icon="pi pi-refresh" label="Refresh" severity="secondary" size="small" outlined @click="loadAll" />
        </div>
      </template>
    </Card>

    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-sm font-semibold text-slate-950">Subscription plans</h2>
            <p class="mt-1 text-xs text-slate-500">Pricing and features shown on the marketing pricing page.</p>
          </div>
          <Button label="Add Plan" icon="pi pi-plus" severity="warn" size="small" @click="openCreatePlanDialog" />
        </div>
        <div v-if="plansLoading" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <Skeleton v-for="item in 3" :key="item" height="220px" />
        </div>
        <div v-else-if="plans.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <div v-for="(plan, index) in plans" :key="plan.id"
            class="flex min-h-56 flex-col justify-between rounded-2xl p-5 text-white shadow-sm" :class="planGradient(index)">
            <div>
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-widest text-white/70">{{ plan.plan_key }}</p>
                  <h3 class="mt-2 text-xl font-bold">{{ plan.name }}</h3>
                </div>
                <span class="rounded-full bg-white/15 px-2.5 py-1 text-xs">{{ plan.is_active ? 'Active' : 'Inactive' }}</span>
              </div>
              <p class="mt-3 text-sm text-white/80">{{ plan.description || 'Subscription plan for FurniSync stores.' }}</p>
              <p class="mt-4 text-2xl font-semibold">{{ formatMoney(plan.monthly_price) }}<span class="text-sm font-normal text-white/70"> / month</span></p>
              <p class="text-xs text-white/70">{{ formatMoney(plan.yearly_price) }} yearly</p>
            </div>
            <div class="mt-5 flex items-center justify-between gap-3 border-t border-white/20 pt-4">
              <span class="text-sm font-medium">{{ Number(plan.active_stores_count || 0) }} active stores</span>
              <div class="flex items-center gap-2">
                <Button label="View" size="small" outlined class="!border-white/60 !text-white" @click="goToPlan(plan)" />
                <Button icon="pi pi-trash" size="small" text class="!text-white" aria-label="Remove plan" @click="confirmRemovePlan(plan)" />
              </div>
            </div>
          </div>
        </div>
        <p v-else class="py-8 text-center text-sm text-slate-500">No subscription plans found.</p>
      </template>
    </Card>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
      <Card class="border border-slate-200 shadow-sm">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Total Stores</p>
          <p class="mt-2 text-2xl font-semibold text-slate-900">{{ stats.total_stores }}</p>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-sm">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Paid</p>
          <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ stats.paid }}</p>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-sm">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Overdue</p>
          <p class="mt-2 text-2xl font-semibold text-rose-600">{{ stats.overdue }}</p>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-sm">
        <template #content>
          <p class="text-xs uppercase tracking-wide text-slate-500">Unpaid</p>
          <p class="mt-2 text-2xl font-semibold text-amber-600">{{ stats.unpaid }}</p>
        </template>
      </Card>
    </div>

    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="mb-4 flex flex-wrap items-end gap-3">
          <div class="w-full sm:w-72">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="filters.search" placeholder="Search store, owner, email..." size="small" fluid />
            </IconField>
          </div>
          <div class="w-full sm:w-48">
            <Select
              v-model="filters.tier"
              :options="tierOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Plan Tier"
              showClear
              size="small"
              fluid
            />
          </div>
          <div class="w-full sm:w-48">
            <Select
              v-model="filters.status"
              :options="statusOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Store Status"
              showClear
              size="small"
              fluid
            />
          </div>
          <Button v-if="hasActiveFilters" label="Clear Filters" icon="pi pi-filter-slash" severity="danger" outlined size="small" @click="clearFilters" />
        </div>

        <DataTable :value="stores" :loading="loading" rowHover size="small" dataKey="id" paginator lazy
          :first="(storePage - 1) * storePerPage" :totalRecords="storeTotal" :rows="storePerPage"
          :rowsPerPageOptions="[10, 20, 50]" @page="onStorePage" class="text-sm" tableStyle="min-width: 76rem">
          <Column field="store_name" header="Store" sortable style="min-width: 13rem">
            <template #body="{ data }">
              <div class="min-w-0 py-1">
                <p class="font-semibold text-slate-900">{{ data.store_name }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ data.contact_person || 'Contact not provided' }}</p>
              </div>
            </template>
          </Column>
          <Column field="email" header="Contact" sortable style="min-width: 14rem">
            <template #body="{ data }">
              <div>
                <p class="text-sm text-slate-800">{{ data.email || '-' }}</p>
                <p class="text-xs text-slate-500">{{ data.contact_number || '-' }}</p>
              </div>
            </template>
          </Column>
          <Column field="subscription_tier" header="Plan" sortable style="min-width: 9rem">
            <template #body="{ data }">
              <Tag :value="toTitle(data.subscription_tier)" :severity="tierSeverity(data.subscription_tier)" rounded />
            </template>
          </Column>
          <Column field="subscription_ends_at" header="Ends At" sortable style="min-width: 12rem">
            <template #body="{ data }">
              <div>
                <p class="text-sm text-slate-800">{{ data.subscription_tier ? formatDate(data.subscription_ends_at) : '—' }}</p>
                <p class="mt-0.5 text-xs" :class="daysClass(data.days_remaining)">
                  {{ data.subscription_tier ? subscriptionStatusLabel(data.subscription_status, data.days_remaining, data.subscription_tier) : 'No plan assigned' }}
                </p>
              </div>
            </template>
          </Column>
          <Column field="subscription_status" header="Billing Status" sortable style="min-width: 10rem">
            <template #body="{ data }">
              <Tag :value="billingStatusLabel(data.subscription_status)" :severity="subscriptionStatusSeverity(data.subscription_status)" rounded />
            </template>
          </Column>
          <Column field="status" header="Store Status" sortable style="min-width: 10rem">
            <template #body="{ data }">
              <div class="flex items-center gap-1.5">
                <Tag :value="toTitle(data.status)" :severity="storeStatusSeverity(data.status)" rounded />
                <Button
                  v-if="showStatusInfoButton(data)"
                  icon="pi pi-info-circle"
                  text
                  rounded
                  severity="secondary"
                  @click="openStatusInfoDialog(data)"
                />
              </div>
            </template>
          </Column>
          <Column header="Usage" style="min-width: 11rem">
            <template #body="{ data }">
              <p class="text-xs text-slate-500">{{ data.users_count }} users / {{ data.products_count }} products</p>
            </template>
          </Column>
          <Column header="Actions" style="width: 8rem">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Button icon="pi pi-pencil" severity="info" outlined rounded size="small" v-tooltip="'Manage subscription'" aria-label="Manage subscription" @click="openManageDialog(data)" />
                <Button icon="pi pi-calendar-plus" severity="success" outlined rounded size="small" v-tooltip="'Extend by 30 days'" aria-label="Extend subscription 30 days" @click="quickExtend(data.id, 30)" />
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

    <Dialog v-model:visible="planDialog" :header="creatingPlan ? 'Add Plan' : 'Edit Plan'" :style="{ width: '600px' }" modal>
      <div class="space-y-4" v-if="activePlan || creatingPlan">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Plan Name</label>
            <InputText v-model="planForm.name" fluid />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Plan Key</label>
            <InputText v-model="planForm.plan_key" fluid :disabled="!creatingPlan" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Monthly Price</label>
            <InputNumber v-model="planForm.monthly_price" mode="currency" currency="PHP" locale="en-PH" fluid />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Yearly Price</label>
            <InputNumber v-model="planForm.yearly_price" mode="currency" currency="PHP" locale="en-PH" fluid />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Sales Commission Rate (optional)</label>
            <InputNumber v-model="planForm.commission_rate" suffix="%" :min="0" :max="5" :minFractionDigits="0" :maxFractionDigits="2" fluid />
            <p class="mt-1 text-xs text-slate-500">Maximum commission rate is 5%. Leave empty or set to 0 when this plan does not charge a sales commission.</p>
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
            <InputText v-model="planForm.description" fluid />
          </div>
          <div class="md:col-span-2">
            <label class="mb-2 block text-sm font-medium text-slate-700">Plan Limits (leave blank for unlimited)</label>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
              <div>
                <label class="mb-1 block text-xs text-slate-600">User Accounts</label>
                <InputNumber v-model="planForm.max_user_accounts" :min="1" placeholder="Unlimited" fluid />
              </div>
              <div>
                <label class="mb-1 block text-xs text-slate-600">Store Branches</label>
                <InputNumber v-model="planForm.max_branches" :min="1" placeholder="Unlimited" fluid />
              </div>
              <div>
                <label class="mb-1 block text-xs text-slate-600">Products</label>
                <InputNumber v-model="planForm.max_products" :min="1" placeholder="Unlimited" fluid />
              </div>
              <div>
                <label class="text-sm font-medium text-slate-700">3D Model Requests per Month</label>
                <InputNumber v-model="planForm.max_3d_model_requests_per_month" :min="0" fluid />
                <small class="text-xs text-slate-500">Maximum 3D model requests allowed each month. 0 disables requests.</small>
              </div>
            </div>
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Features (one per line)</label>
            <Textarea v-model="planForm.features" rows="5" autoResize fluid />
          </div>
          <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-slate-700">Modules Included</label>
            <MultiSelect
              v-model="planForm.modules"
              :options="modulesOptions"
              optionLabel="label"
              optionValue="value"
              display="chip"
              :loading="modulesLoading"
              placeholder="Select modules"
              class="w-full"
            />
            <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">
              <div
                v-for="mod in modulesOptions"
                :key="mod.value"
                class="flex items-center justify-between rounded border border-slate-200 px-3 py-2"
              >
                <div>
                  <p class="font-semibold text-sm text-slate-900">{{ mod.label }}</p>
                  <p class="text-xs text-slate-500 truncate">{{ mod.description }}</p>
                </div>
                <InputSwitch
                  :modelValue="planForm.modules.includes(mod.value)"
                  @update:modelValue="(val:boolean)=>toggleModule(mod.value,val)"
                />
              </div>
            </div>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Sort Order</label>
            <InputNumber v-model="planForm.sort_order" :min="0" :max="999" fluid />
          </div>
          <div class="flex items-center gap-2">
            <Checkbox v-model="planForm.is_featured" binary />
            <span class="text-sm text-slate-700">Featured</span>
          </div>
          <div class="flex items-center gap-2">
            <Checkbox v-model="planForm.is_active" binary />
            <span class="text-sm text-slate-700">Active</span>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" text @click="planDialog = false" />
        <Button label="Save" icon="pi pi-check" severity="info" :loading="savingPlan" @click="savePlan" />
      </template>
    </Dialog>

    <Dialog v-model:visible="statusInfoDialog" header="Store Status Details" :style="{ width: '520px' }" modal>
      <div v-if="statusInfoStore" class="space-y-3">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
          <p class="font-semibold text-slate-900">{{ statusInfoStore.store_name }}</p>
          <p class="text-xs text-slate-500">{{ statusInfoStore.email || 'No email' }}</p>
        </div>
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div>
            <p class="text-xs uppercase tracking-wide text-slate-500">Current Status</p>
            <Tag :value="toTitle(statusInfoStore.status)" :severity="storeStatusSeverity(statusInfoStore.status)" />
          </div>
          <div v-if="statusInfoStore?.status_details?.actioned_at">
            <p class="text-xs uppercase tracking-wide text-slate-500">Action Date</p>
            <p class="text-sm text-slate-800">{{ formatDateTime(statusInfoStore.status_details.actioned_at) }}</p>
          </div>
          <div v-if="statusInfoStore.status === 'suspended'" class="md:col-span-2">
            <p class="text-xs uppercase tracking-wide text-slate-500">Suspension Remaining</p>
            <p class="text-sm font-semibold text-amber-600">{{ suspensionRemainingLabel(statusInfoStore?.status_details) }}</p>
          </div>
          <div v-if="statusInfoStore.status === 'banned'" class="md:col-span-2">
            <p class="text-xs uppercase tracking-wide text-slate-500">Ban Duration</p>
            <p class="text-sm font-semibold text-rose-600">Permanent until admin reinstates this store.</p>
          </div>
          <div class="md:col-span-2">
            <p class="text-xs uppercase tracking-wide text-slate-500">Reason</p>
            <p class="text-sm text-slate-800 whitespace-pre-wrap">
              {{ statusInfoStore?.status_details?.action_reason || 'No reason was recorded.' }}
            </p>
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Close" text @click="statusInfoDialog = false" />
      </template>
    </Dialog>
  </div>

  <ConfirmDialog />
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axiosClient from '@/axios'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
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
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'
import MultiSelect from 'primevue/multiselect'
import InputSwitch from 'primevue/inputswitch'
import ConfirmDialog from 'primevue/confirmdialog'
import Skeleton from 'primevue/skeleton'

const toast = useToast()
const confirm = useConfirm()

const loading = ref(false)
const saving = ref(false)
const manageDialog = ref(false)
const statusInfoDialog = ref(false)
const statusInfoStore = ref<any | null>(null)
const stores = ref<any[]>([])
const storePage = ref(1)
const storePerPage = ref(10)
const storeTotal = ref(0)
const planGradient = (index: number) => [
  'bg-gradient-to-br from-slate-900 to-blue-900',
  'bg-gradient-to-br from-rose-900 to-blue-950',
  'bg-gradient-to-br from-orange-600 to-red-800',
][index % 3]
const formatMoney = (value: number | string | null) => new Intl.NumberFormat('en-PH', {
  style: 'currency', currency: 'PHP',
}).format(Number(value || 0))
const selectedStore = ref<any | null>(null)
const stats = reactive({
  total_stores: 0,
  paid: 0,
  overdue: 0,
  unpaid: 0,
})
const plans = ref<any[]>([])
const plansLoading = ref(false)
const planDialog = ref(false)
const creatingPlan = ref(false)
const savingPlan = ref(false)
const activePlan = ref<any | null>(null)
const planForm = reactive({
  id: 0,
  plan_key: '',
  name: '',
  description: '',
  monthly_price: 0,
  yearly_price: 0,
  commission_rate: null as number | null,
  max_user_accounts: null as number | null,
  max_branches: null as number | null,
  max_products: null as number | null,
  max_3d_model_requests_per_month: 0,
  features: '',
  is_featured: false,
  is_active: true,
  sort_order: 0,
  modules: [] as string[],
})
const modulesOptions = ref<{ label: string; value: string; description?: string }[]>([])
const modulesLoading = ref(false)

const filters = reactive({
  search: '',
  tier: null as string | null,
  status: null as string | null,
})
const hasActiveFilters = computed(() => Boolean(filters.search.trim() || filters.tier || filters.status))

const editForm = reactive({
  subscription_tier: 'free',
  subscription_ends_at: null as Date | null,
  status: 'active',
})

const tierOptions = computed(() => plans.value.map((plan: any) => ({ label: plan.name, value: plan.plan_key })))

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Pending', value: 'pending' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
  { label: 'Banned', value: 'banned' },
]

const queryParams = computed(() => ({
  search: filters.search || undefined,
  tier: filters.tier || undefined,
  status: filters.status || undefined,
  page: storePage.value,
  per_page: storePerPage.value,
}))
const onStorePage = (event: { page: number; rows: number }) => {
  storePage.value = event.page + 1
  storePerPage.value = event.rows
  loadStores()
}

const toTitle = (value: string | null | undefined) =>
  String(value || 'no plan')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

const formatDate = (value: string | null | undefined) => {
  if (!value) return 'Unlimited'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (value: string | null | undefined) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value)
  return date.toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const tierSeverity = (tier: string) => {
  switch (tier) {
    case 'enterprise': return 'help'
    case 'premium': return 'success'
    case 'basic': return 'info'
    default: return 'secondary'
  }
}

const subscriptionStatusSeverity = (status: string) => {
  switch (String(status || '').toLowerCase()) {
    case 'paid': return 'success'
    case 'overdue': return 'danger'
    case 'unpaid': return 'warning'
    case 'unassigned': return 'secondary'
    default: return 'secondary'
  }
}

const billingStatusLabel = (status: string | null | undefined) => {
  if (!status || status === 'unassigned') return 'No Plan'
  if (status === 'unpaid') return 'Free'
  return toTitle(status)
}

const storeStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'pending': return 'warning'
    case 'suspended': return 'danger'
    case 'banned': return 'danger'
    default: return 'secondary'
  }
}

const showStatusInfoButton = (store: any) => {
  const status = String(store?.status || '').toLowerCase()
  return status === 'suspended' || status === 'banned'
}

const suspensionRemainingLabel = (details: any) => {
  const days = Number(details?.suspension_days_remaining)
  if (Number.isNaN(days)) return 'Not available'
  if (days <= 0) return 'Suspension period ended (awaiting admin update).'
  return `${days} day(s) remaining`
}

const openStatusInfoDialog = (store: any) => {
  statusInfoStore.value = store
  statusInfoDialog.value = true
}

const subscriptionStatusLabel = (subscriptionStatus: string, daysRemaining: number | null, tier: string) => {
  const normalized = String(subscriptionStatus || '').toLowerCase()
  if (normalized === 'unassigned') return 'No plan assigned'
  if (normalized === 'paid') {
    if (tier === 'free') return 'Free plan'
    if (daysRemaining === null || daysRemaining === undefined) return 'Paid'
    if (daysRemaining <= 14) return `${daysRemaining === 0 ? 'Expires today' : `${daysRemaining} day(s) left`}`
    return `${daysRemaining} day(s) left`
  }
  if (normalized === 'overdue') {
    if (daysRemaining === null || daysRemaining === undefined) return 'Overdue'
    return `Overdue ${Math.abs(daysRemaining)} day(s)`
  }
  if (normalized === 'unpaid') return 'Unpaid / Free'
  if (tier === 'free') return 'Free plan'
  if (daysRemaining === null || daysRemaining === undefined) return 'Unlimited'
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
    const paginator = response.data?.data || {}
    stores.value = Array.isArray(paginator.data) ? paginator.data : []
    storeTotal.value = Number(paginator.total || 0)
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
    stats.paid = Number(data.paid || 0)
    stats.overdue = Number(data.overdue || 0)
    stats.unpaid = Number(data.unpaid || 0)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load stats', life: 3000 })
  }
}

const loadPlans = async () => {
  plansLoading.value = true
  try {
    const response = await axiosClient.get('/api/admin/subscription-plans')
    plans.value = response.data?.data || []
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load plans', life: 3000 })
  } finally {
    plansLoading.value = false
  }
}

const loadAll = async () => {
  await Promise.all([loadStores(), loadStats(), loadPlans()])
}

const openManageDialog = (store: any) => {
  selectedStore.value = store
  editForm.subscription_tier = store.subscription_tier || 'free'
  editForm.status = store.status || 'active'
  editForm.subscription_ends_at = store.subscription_ends_at ? new Date(store.subscription_ends_at) : null
  manageDialog.value = true
}

const loadModulesOptions = async () => {
  if (modulesOptions.value.length) return
  modulesLoading.value = true
  try {
    const res = await axiosClient.get('/api/admin/store-modules/modules')
    modulesOptions.value = (res.data?.data ?? []).map((m: any) => ({
      label: m.name,
      value: m.key,
      description: m.description,
    }))
  } finally {
    modulesLoading.value = false
  }
}

const openPlanDialog = async (plan: any) => {
  creatingPlan.value = false
  activePlan.value = plan
  planForm.id = Number(plan.id)
  planForm.plan_key = String(plan.plan_key || '')
  planForm.name = String(plan.name || '')
  planForm.description = String(plan.description || '')
  planForm.monthly_price = Number(plan.monthly_price || 0)
  planForm.yearly_price = Number(plan.yearly_price || 0)
  planForm.commission_rate = Number(plan.commission_rate || 0) || null
  planForm.max_user_accounts = plan.max_user_accounts == null ? null : Number(plan.max_user_accounts)
  planForm.max_branches = plan.max_branches == null ? null : Number(plan.max_branches)
  planForm.max_products = plan.max_products == null ? null : Number(plan.max_products)
  planForm.max_3d_model_requests_per_month = Number(plan.max_3d_model_requests_per_month || 0)
  planForm.features = Array.isArray(plan.features) ? plan.features.join('\n') : ''
  planForm.is_featured = !!plan.is_featured
  planForm.is_active = plan.is_active !== false
  planForm.sort_order = Number(plan.sort_order || 0)
  await loadModulesOptions()
  try {
    const res = await axiosClient.get(`/api/admin/subscription-plans/${plan.id}`)
    const payload = res.data?.data || {}
    const included = (payload.modules || []).filter((m: any) => m.included).map((m: any) => m.key)
    planForm.modules = included
  } catch (e) {
    planForm.modules = []
  }
  planDialog.value = true
}

const openCreatePlanDialog = () => {
  creatingPlan.value = true
  activePlan.value = null
  planForm.id = 0
  planForm.plan_key = ''
  planForm.name = ''
  planForm.description = ''
  planForm.monthly_price = 0
  planForm.yearly_price = 0
  planForm.commission_rate = null
  planForm.max_user_accounts = null
  planForm.max_branches = null
  planForm.max_products = null
  planForm.max_3d_model_requests_per_month = 0
  planForm.features = ''
  planForm.is_featured = false
  planForm.is_active = true
  planForm.sort_order = 0
  planForm.modules = modulesOptions.value.map((m) => m.value)
  planDialog.value = true
}

const savePlan = async () => {
  if (!activePlan.value && !creatingPlan.value) return
  confirm.require({
    message: 'Save changes to this plan?',
    header: 'Confirm',
    icon: 'pi pi-check',
    accept: async () => {
      savingPlan.value = true
      try {
        const features = planForm.features
          .split(/\r?\n/)
          .map((line) => line.trim())
          .filter(Boolean)
        const payload = {
          plan_key: planForm.plan_key,
          name: planForm.name,
          description: planForm.description || null,
          monthly_price: planForm.monthly_price,
          yearly_price: planForm.yearly_price,
          commission_rate: planForm.commission_rate || null,
          max_user_accounts: planForm.max_user_accounts,
          max_branches: planForm.max_branches,
          max_products: planForm.max_products,
          max_3d_model_requests_per_month: planForm.max_3d_model_requests_per_month,
          features,
          is_featured: planForm.is_featured,
          is_active: planForm.is_active,
          sort_order: planForm.sort_order,
          modules: planForm.modules,
        }
        if (creatingPlan.value) {
          const response = await axiosClient.post('/api/admin/subscription-plans', payload)
          toast.add({ severity: 'success', summary: 'Saved', detail: response?.data?.message || 'Plan created successfully', life: 2500 })
        } else {
          const response = await axiosClient.put(`/api/admin/subscription-plans/${planForm.id}`, payload)
          toast.add({ severity: 'success', summary: 'Saved', detail: response?.data?.message || 'Plan updated successfully', life: 2500 })
        }
        planDialog.value = false
        await loadPlans()
      } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to update plan', life: 3000 })
      } finally {
        savingPlan.value = false
      }
    },
  })
}

const saveSubscription = async () => {
  if (!selectedStore.value) return
  saving.value = true
  try {
    const response = await axiosClient.put(`/api/admin/subscriptions/${selectedStore.value.id}`, {
      subscription_tier: editForm.subscription_tier,
      status: editForm.status,
      subscription_ends_at: editForm.subscription_ends_at
        ? editForm.subscription_ends_at.toISOString().slice(0, 10)
        : null,
    })
    toast.add({ severity: 'success', summary: 'Saved', detail: response?.data?.message || 'Subscription updated successfully', life: 2500 })
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
    const response = await axiosClient.post(`/api/admin/subscriptions/${storeId}/extend`, { days })
    toast.add({ severity: 'success', summary: 'Extended', detail: response?.data?.message || `Subscription extended by ${days} days`, life: 2200 })
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to extend subscription', life: 3000 })
  }
}

const toggleModule = (key: string, val: boolean) => {
  if (val) {
    if (!planForm.modules.includes(key)) planForm.modules.push(key)
  } else {
    planForm.modules = planForm.modules.filter((k) => k !== key)
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

const goToPlan = (plan: any) => {
  if (!plan?.id) return
  window.location.href = `/admin/subscription-plans/${plan.id}`
}

const onPlanRowClick = (event: any) => {
  if (event?.data) {
    goToPlan(event.data)
  }
}

const confirmRemovePlan = (plan: any) => {
  confirm.require({
    header: 'Remove subscription plan?',
    message: `Remove ${plan.name}? Stores currently assigned to this plan must be reassigned first.`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Remove Plan',
    rejectLabel: 'Keep Plan',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        await axiosClient.delete(`/api/admin/subscription-plans/${plan.id}`)
        toast.add({ severity: 'success', summary: 'Plan removed', detail: `${plan.name} was removed.`, life: 3000 })
        await loadPlans()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Unable to remove plan',
          detail: error?.response?.data?.message || 'The plan could not be removed.',
          life: 5000,
        })
      }
    },
  })
}

watch(() => [filters.search, filters.tier, filters.status], () => {
  storePage.value = 1
  loadStores()
})

onMounted(async () => {
  await Promise.all([loadAll(), loadModulesOptions()])
})
</script>
