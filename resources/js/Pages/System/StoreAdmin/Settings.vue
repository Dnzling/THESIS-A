<template>
  <Toast />
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Store Settings</h1>
        <p class="text-sm text-slate-600">Manage your store profile, plan, and system configuration.</p>
      </div>
    </div>

    <div :class="isActiveSubscription ? 'grid gap-6 lg:grid-cols-2' : 'grid gap-6 lg:grid-cols-2'">
      

      <Card>
        <template #title>Plan Status</template>
        <template #content>
          <div class="space-y-3 text-sm text-slate-700">
            <div class="flex items-center justify-between">
              <span>Store Status</span>
              <div class="flex items-center gap-2">
                <Tag :value="storeStatusLabel" :severity="storeStatusSeverity" />
                <Button
                  v-if="showStoreStatusInfo"
                  icon="pi pi-info-circle"
                  text
                  rounded
                  severity="secondary"
                  @click="storeStatusDialogVisible = true"
                />
              </div>
            </div>
            <div class="flex items-center justify-between">
              <span>Plan</span>
              <span class="font-semibold">{{ currentPlanLabel }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span>Ends On</span>
              <span class="font-semibold">{{ subscriptionEndsAtLabel }}</span>
            </div>
          </div>
          <div class="mt-6 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
            Upgrade your plan anytime to unlock all modules and remove trial limits.
          </div>
          <Button class="mt-4 w-full" label="Upgrade Plan" severity="info" :loading="upgrading" @click="goToUpgrade" />
        </template>
      </Card>

      <!-- Online Payment methods moved to Upgrade flow; card removed -->

      
    </div>

    <Dialog v-model:visible="planDialogVisible" modal header="Available Plans" :style="{ width: '36rem' }">
      <div class="space-y-3">
        <button
          v-for="plan in availablePlans"
          :key="plan.key"
          type="button"
          class="w-full rounded-lg border p-3 text-left transition"
          :class="selectedPlan.key === plan.key ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:border-slate-300'"
          @click="selectPlan(plan.key)"
        >
          <div class="flex items-center justify-between">
            <div class="font-semibold text-slate-900">{{ plan.label }}</div>
            <div class="text-sm font-semibold text-slate-700">PHP {{ plan.amountPhp.toLocaleString() }}</div>
          </div>
          <p class="mt-1 text-xs text-slate-600">{{ plan.description }}</p>
        </button>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="planDialogVisible = false" />
        <Button label="Continue" @click="openPaymentMethodDialogForSelectedPlan" />
      </template>
    </Dialog>

    <Dialog v-model:visible="gcashDialogVisible" modal header="Payment Details" :style="{ width: '28rem' }">
      <div class="space-y-4">
        <div>
          <label class="text-sm font-medium text-slate-700 block mb-1">Payment Method</label>
          <Select v-model="selectedWalletType" :options="walletTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
          <p class="mt-1 text-xs text-slate-500">This selection affects the wallet you will be redirected to.</p>
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700 block mb-1">Account Name</label>
          <InputText v-model="gcashForm.name" class="w-full" placeholder="Juan Dela Cruz" />
        </div>
        <div>
          <label class="text-sm font-medium text-slate-700 block mb-1">Mobile Number</label>
          <InputMask v-model="gcashForm.phone" class="w-full" mask="0999 999 9999" placeholder="09__ ___ ____" />
          <p class="mt-1 text-xs text-slate-500">Use your active Philippine mobile number linked to the selected wallet.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="gcashDialogVisible = false" />
        <Button :label="`Continue to ${selectedWalletLabel}`" :loading="upgrading" @click="submitUpgradeCheckout" />
      </template>
    </Dialog>

    <Dialog v-model:visible="paymentMethodDialogVisible" modal header="Choose Payment Method" :style="{ width: '28rem' }">
      <div class="space-y-3">
        <p class="text-sm text-slate-600">Select which payment method you'd like to use for this upgrade.</p>
        <div class="grid gap-3">
          <label class="flex items-center gap-3">
            <input type="radio" v-model="selectedPaymentMethod" value="card" />
            <span>Credit / Debit Card</span>
          </label>
          <label class="flex items-center gap-3">
            <input type="radio" v-model="selectedPaymentMethod" value="gcash" />
            <span>GCash / E-Wallet</span>
          </label>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="paymentMethodDialogVisible = false" />
        <Button :disabled="!selectedPaymentMethod" @click="continueToPaymentCredentials" />
      </template>
    </Dialog>

    <Dialog v-model:visible="storeStatusDialogVisible" modal header="Store Status Details" :style="{ width: '34rem' }">
      <div class="space-y-3 text-sm">
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
          <div class="font-semibold text-slate-900">{{ store.name || 'Store' }}</div>
          <div class="text-xs text-slate-500">{{ store.email || '-' }}</div>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-600">Current Status</span>
          <Tag :value="storeStatusLabel" :severity="storeStatusSeverity" />
        </div>
        <div v-if="store.status === 'suspended'" class="flex items-center justify-between">
          <span class="text-slate-600">Suspension Remaining</span>
          <span class="font-semibold text-amber-600">{{ formatRemainingDays(store.status_details?.suspension_days_remaining) }}</span>
        </div>
        <div v-if="store.status_details?.actioned_at" class="flex items-center justify-between">
          <span class="text-slate-600">Action Date</span>
          <span class="font-semibold text-slate-800">{{ formatDateTime(store.status_details.actioned_at) }}</span>
        </div>
        <div>
          <div class="text-slate-600 mb-1">Reason</div>
          <div class="rounded-lg border border-slate-200 p-3 text-slate-800 whitespace-pre-wrap">
            {{ store.status_details?.action_reason || 'No reason was recorded.' }}
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Close" text @click="storeStatusDialogVisible = false" />
      </template>
    </Dialog>

  

    <Dialog v-model:visible="verificationDocumentsDialogVisible" modal header="Submitted Verification Documents" :style="{ width: '48rem' }">
      <div class="space-y-3">
        <div class="text-xs text-slate-500">
          Review submitted attachments while your verification is under review.
        </div>

        <DataTable :value="verificationDocuments" responsiveLayout="scroll" class="text-sm" stripedRows>
          <Column field="label" header="Document"></Column>
          <Column header="Status">
            <template #body="slotProps">
              <Tag
                :value="slotProps.data.submitted ? (slotProps.data.is_valid ? 'Submitted' : 'Needs Review') : 'Missing'"
                :severity="slotProps.data.submitted ? (slotProps.data.is_valid ? 'success' : 'warn') : 'secondary'"
              />
            </template>
          </Column>
          <Column field="size_kb" header="Size (KB)">
            <template #body="slotProps">
              <span>{{ slotProps.data.size_kb ?? '—' }}</span>
            </template>
          </Column>
          <Column header="Action">
            <template #body="slotProps">
              <Button
                v-if="slotProps.data.download_url"
                label="View"
                icon="pi pi-external-link"
                size="small"
                outlined
                @click="openDocument(slotProps.data.download_url)"
              />
              <span v-else class="text-slate-400">—</span>
            </template>
          </Column>
        </DataTable>

        <div v-if="verificationDocuments.length === 0" class="text-sm text-slate-500">
          No verification attachments found.
        </div>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="verificationDocumentsDialogVisible = false" />
      </template>
    </Dialog>


    <Card>
      <template #title>Store Profile</template>
      <template #content>
        <div class="grid gap-4 md:grid-cols-2 text-sm text-slate-700">
          <div>
            <label class="text-xs uppercase text-slate-400 block mb-1">Store Name</label>
            <InputText v-model="store.name" class="w-full" placeholder="Store name" readonly />
          </div>
          <div>
            <label class="text-xs uppercase text-slate-400 block mb-1">Contact Person</label>
            <InputText v-model="store.contact_person" class="w-full" placeholder="Contact person" />
          </div>
          <div>
            <label class="text-xs uppercase text-slate-400 block mb-1">Email</label>
            <InputText v-model="store.email" class="w-full" placeholder="Store email" readonly />
          </div>
          <div>
            <label class="text-xs uppercase text-slate-400 block mb-1">Phone</label>
            <InputText v-model="store.phone" class="w-full" placeholder="Store phone" />
          </div>
          <div>
            <label class="text-xs uppercase text-slate-400 block mb-1">Address</label>
            <InputText v-model="store.address" class="w-full" placeholder="Store address" />
          </div>
          <div>
            <label class="text-xs uppercase text-slate-400 block mb-1">City</label>
            <InputText v-model="store.city" class="w-full" placeholder="City" />
          </div>
          <div>
            <label class="text-xs uppercase text-slate-400 block mb-1">Province</label>
            <InputText v-model="store.province" class="w-full" placeholder="Province" readonly/>
          </div>
          <div>
            <label class="text-xs uppercase text-slate-400 block mb-1">Store Type</label>
            <InputText v-model="store.type" class="w-full" placeholder="Retail, Warehouse, etc." />
          </div>
        </div>
        <div class="mt-4 flex items-center justify-end gap-2">
          <Button
            label="Save Store Profile"
            size="small"
            :loading="savingProfile"
            @click="saveStoreProfile"
          />
        </div>
      </template>
    </Card>

    <Card>
      <template #title>Attendance Geolocation</template>
      <template #content>
        <p class="text-sm text-slate-600 mb-4">
          Set the main branch location for attendance login/clock-in. Employees must be within the radius to log in.
        </p>
        <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
          <div>
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <InputText v-model="searchQuery" class="flex-1 min-w-[220px]" placeholder="Search address..." />
              <Button label="Search" icon="pi pi-search" severity="secondary" @click="searchAddress" />
            </div>
            <div ref="mapEl" class="h-72 w-full rounded-xl border border-slate-200"></div>
            <p class="mt-2 text-xs text-slate-500">Drag the pin to adjust the exact location.</p>
          </div>
          <div class="space-y-3 text-sm text-slate-700">
            <div>
              <div class="text-xs uppercase text-slate-400">Address</div>
              <div class="font-semibold">{{ attendance.address || 'Not set' }}</div>
            </div>
            <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
              <div>
                <div class="text-xs uppercase text-slate-400">Geofence</div>
                <div class="text-sm font-semibold">{{ attendance.geofence_enabled ? 'Enabled' : 'Disabled' }}</div>
              </div>
              <InputSwitch v-model="attendance.geofence_enabled" />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <div class="text-xs uppercase text-slate-400">Barangay</div>
                <div class="font-semibold">{{ attendance.barangay || 'Not set' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase text-slate-400">City</div>
                <div class="font-semibold">{{ attendance.city || 'Not set' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase text-slate-400">Province</div>
                <div class="font-semibold">{{ attendance.province || 'Not set' }}</div>
              </div>
              <div>
                <div class="text-xs uppercase text-slate-400">Radius (meters)</div>
                <div class="mt-2 space-y-2">
                  <Slider v-model="attendance.geofence_radius_m" :min="0" :max="100" :step="1" class="w-full" />
                  <div class="text-xs text-slate-500">{{ attendance.geofence_radius_m }} meters</div>
                </div>
              </div>
            </div>
            <div class="text-xs text-slate-500">
              Coordinates: {{ attendance.latitude ?? '—' }}, {{ attendance.longitude ?? '—' }}
            </div>
            <Button
              label="Save Attendance Location"
              icon="pi pi-check"
              :loading="savingAttendance"
              @click="saveAttendance"
            />
          </div>
        </div>
      </template>
    </Card>


  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import axiosClient from '@/axios'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import MultiSelect from 'primevue/multiselect'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import InputSwitch from 'primevue/inputswitch'
import Slider from 'primevue/slider'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'
import { usePermissions } from '@/composables/usePermissions'
import paymongoService from '@/services/paymongo.service'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

type UpgradePlan = {
  key: string
  label: string
  amountPhp: number
  months: number
  tier: string
  description: string
  isFeatured?: boolean
}

const props = defineProps<{
  store: any
  payments: any
  branches: any[]
  attendance: any
  subscription: any
  available_plans: any[]
  verification: any
  onboarding: any
}>()

const savingAttendance = ref(false)
const savingProfile = ref(false)
const upgrading = ref(false)
const planDialogVisible = ref(false)
const gcashDialogVisible = ref(false)
const paymentMethodDialogVisible = ref(false)
const selectedPaymentMethod = ref<'card' | 'gcash' | ''>('')
const savingPayments = ref(false)
const paymongoMethodOptions = [
  { label: 'Credit/Debit Card', value: 'card' },
  { label: 'GCash', value: 'gcash' },
  { label: 'GrabPay', value: 'grab_pay' },
  { label: 'PayMaya', value: 'paymaya' },
]
const paymongoPaymentMethods = ref<string[]>(['gcash'])
const selectedWalletType = ref<'card' | 'gcash' | 'grab_pay' | 'paymaya'>('gcash')
const walletTypeOptions = computed(() => {
  const allowed = Array.isArray(paymongoPaymentMethods.value) ? paymongoPaymentMethods.value : ['gcash']
  const map: Record<string, string> = { card: 'Card', gcash: 'GCash', grab_pay: 'GrabPay', paymaya: 'PayMaya' }
  return allowed
    .filter((v) => ['card', 'gcash', 'grab_pay', 'paymaya'].includes(String(v)))
    .map((v) => ({ label: map[String(v)] || String(v), value: v as any }))
})
const selectedWalletLabel = computed(() => {
  const opt = walletTypeOptions.value.find((o) => o.value === selectedWalletType.value)
  return opt?.label || 'Wallet'
})
const storeStatusDialogVisible = ref(false)
const loading = ref(false)
const mapEl = ref<HTMLElement | null>(null)
const mapReady = ref(false)
const mapRef = ref<any>(null)
const markerRef = ref<any>(null)
const circleRef = ref<any>(null)
const searchQuery = ref('')
const toast = useToast()

const fallbackPlans: UpgradePlan[] = [
  {
    key: 'simple',
    label: 'Simple',
    amountPhp: 1490,
    months: 1,
    tier: 'simple',
    description: 'For single stores and single locations.',
  },
  {
    key: 'unlimited',
    label: 'Unlimited',
    amountPhp: 3500,
    months: 1,
    tier: 'unlimited',
    description: 'For multi-store operations and fast growth.',
    isFeatured: true,
  },
]

const availablePlans = ref<UpgradePlan[]>(
  Array.isArray(props.available_plans) && props.available_plans.length
    ? props.available_plans.map((plan: any) => ({
        key: String(plan?.key || ''),
        label: String(plan?.label || ''),
        amountPhp: Number(plan?.amount_php ?? plan?.amountPhp ?? 0),
        months: Number(plan?.months ?? 1),
        tier: String(plan?.tier || plan?.key || ''),
        description: String(plan?.description || ''),
        isFeatured: Boolean(plan?.is_featured ?? plan?.isFeatured),
      }))
    : [...fallbackPlans]
)

const store = reactive({
  name: '',
  contact_person: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  province: '',
  type: '',
  store_code: '',
  status: '',
  status_details: null as any,
})

const attendance = reactive({
  branch_id: null as number | null,
  address: '',
  barangay: '',
  city: '',
  province: '',
  latitude: null as number | null,
  longitude: null as number | null,
  geofence_radius_m: 5,
  geofence_enabled: true,
})

const subscription = reactive({
  store_id: null as number | null,
  tier: 'free',
  plan_label: 'Free',
  ends_at: '',
  modules: [] as { key: string; name: string }[],
})

const verification = reactive({
  store_status: 'pending',
  submitted_at: '' as string | null,
  reviewed_at: '' as string | null,
  rejection_reason: '' as string | null,
  documents_submitted: false,
})

const verificationDocumentsDialogVisible = ref(false)
const verificationDocuments = ref<any[]>([])
const loadingVerificationDocuments = ref(false)

const branches = ref<any[]>(Array.isArray(props.branches) ? props.branches : [])

const onboarding = reactive({
  plan: 'simple',
})

const gcashForm = reactive({
  name: '',
  phone: '',
})

const selectedPlan = reactive({
  key: fallbackPlans[0].key,
  amountPhp: fallbackPlans[0].amountPhp,
  months: fallbackPlans[0].months,
  tier: fallbackPlans[0].tier,
  label: fallbackPlans[0].label,
})

usePermissions()

// Initialize state from Inertia props (classic Inertia pattern: server provides the page data).
store.name = props.store?.name || ''
store.contact_person = props.store?.contact_person || ''
store.email = props.store?.email || ''
store.phone = props.store?.phone || ''
store.address = props.store?.address || ''
store.city = props.store?.city || ''
store.province = props.store?.province || ''
store.type = props.store?.type || ''
store.store_code = props.store?.store_code || ''
store.status = props.store?.status || ''
store.status_details = props.store?.status_details || null

subscription.store_id = props.store?.id ?? null
subscription.tier = props.subscription?.tier || 'free'
subscription.plan_label = props.subscription?.plan_label || 'Free'
subscription.ends_at = props.subscription?.ends_at || ''
subscription.modules = Array.isArray(props.subscription?.modules) ? props.subscription.modules : []

verification.store_status = props.verification?.store_status || 'pending'
verification.submitted_at = props.verification?.submitted_at || null
verification.reviewed_at = props.verification?.reviewed_at || null
verification.rejection_reason = props.verification?.rejection_reason || null
verification.documents_submitted = Boolean(props.verification?.documents_submitted)

attendance.branch_id = props.attendance?.branch_id ?? null
attendance.address = props.attendance?.address || ''
attendance.barangay = props.attendance?.barangay || ''
attendance.city = props.attendance?.city || ''
attendance.province = props.attendance?.province || ''
attendance.latitude = props.attendance?.latitude ?? null
attendance.longitude = props.attendance?.longitude ?? null
attendance.geofence_radius_m = props.attendance?.geofence_radius_m ?? 5
attendance.geofence_enabled = props.attendance?.geofence_enabled ?? true

const allowedMethods = props.payments?.paymongo?.payment_method_allowed
paymongoPaymentMethods.value = Array.isArray(allowedMethods) && allowedMethods.length ? allowedMethods : ['gcash']

const isActiveSubscription = computed(() => Boolean(subscription.ends_at))
const canShowVerificationCard = computed(() => {
  const tier = String(subscription.tier || '').toLowerCase()
  return tier === 'unlimited' || tier === 'simple'
})
const currentPlanLabel = computed(() => {
  const planLabel = String(subscription.plan_label || '').trim()
  if (planLabel) return planLabel
  return String(subscription.tier || (isActiveSubscription.value ? 'paid' : 'free')).toUpperCase()
})

const storeStatusLabel = computed(() => {
  const raw = String(store.status || 'unknown').toLowerCase()
  if (raw === 'pending') return 'Unverified'
  return raw.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
})

const storeStatusSeverity = computed(() => {
  const status = String(store.status || '').toLowerCase()
  if (status === 'active' || status === 'approved' || status === 'verified') return 'success'
  if (status === 'pending' || status === 'unverified') return 'warning'
  if (status === 'suspended' || status === 'banned') return 'danger'
  return 'secondary'
})

const showStoreStatusInfo = computed(() => {
  const status = String(store.status || '').toLowerCase()
  return status === 'suspended' || status === 'banned'
})

const formatRemainingDays = (value: number) => {
  if (!Number.isFinite(value)) return 'Not available'
  if (value <= 0) return 'Suspension period ended'
  return `${Math.round(value)} day(s) remaining`
}


const subscriptionEndsAtLabel = computed(() => subscription.ends_at || 'Not set')

const approvalMatrixRows = [
  {
    source_module: 'Procurement',
    action: 'Purchase Order approval flow',
    approver_module: 'Finance',
    approver_action: 'Approve PO (final approval)',
    notes: 'PO can move to approved/sent only after finance approval step.',
  },
  {
    source_module: 'HR Payroll',
    action: 'Payroll processing and release',
    approver_module: 'Finance',
    approver_action: 'Finance approve / mark paid',
    notes: 'Payroll must be finance-approved before payment/release.',
  },
  {
    source_module: 'Merchandising',
    action: 'Price change request',
    approver_module: 'Finance',
    approver_action: 'Approve/reject pending price',
    notes: 'Live price stays unchanged until finance decision.',
  },
  {
    source_module: 'Procurement',
    action: 'Supplier payment above threshold',
    approver_module: 'Finance',
    approver_action: 'Expense approval workflow',
    notes: 'High-value payments trigger finance expense approval.',
  },
  {
    source_module: 'Inventory',
    action: 'High-impact stock adjustment',
    approver_module: 'Finance',
    approver_action: 'Adjustment approval',
    notes: 'Certain adjustments require finance-level approval.',
  },
]

const approvalSourceCount = computed(() => new Set(approvalMatrixRows.map(row => row.source_module)).size)
const approvalApproverCount = computed(() => new Set(approvalMatrixRows.map(row => row.approver_module)).size)

const verificationStatusLabel = computed(() => {
  if (verification.store_status === 'approved') return 'Approved'
  if (verification.store_status === 'reviewing') return 'Under Review'
  if (verification.store_status === 'rejected') return 'Rejected'
  return 'Pending'
})

const verificationSeverity = computed(() => {
  if (verification.store_status === 'approved') return 'success'
  if (verification.store_status === 'reviewing') return 'info'
  if (verification.store_status === 'rejected') return 'danger'
  return 'warn'
})

const shouldShowVerificationAttachments = computed(() =>
  verification.store_status === 'reviewing' || verification.store_status === 'approved'
)

const verificationActionLabel = computed(() =>
  shouldShowVerificationAttachments.value ? 'View Submitted Attachments' : 'Open Verification Page'
)

const formatDateTime = (value?: string | null) => {
  if (!value) return '—'
  const parsed = new Date(value)
  if (Number.isNaN(parsed.getTime())) return value
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  }).format(parsed)
}

const savePaymentSettings = async () => {
  if (paymongoPaymentMethods.value.length === 0) {
    toast.add({ severity: 'warn', summary: 'Select a method', detail: 'Choose at least one payment method.', life: 3000 })
    return
  }
  savingPayments.value = true
  try {
    router.put(
      '/store/settings/payments',
      { paymongo_payment_methods: paymongoPaymentMethods.value },
      { preserveScroll: true }
    )
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Payment methods updated.', life: 2500 })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Save failed',
      detail: error?.response?.data?.message || 'Unable to update payment settings.',
      life: 3500,
    })
  } finally {
    savingPayments.value = false
  }
}

const saveAttendance = async () => {
  if (attendance.latitude === null || attendance.longitude === null) return
  savingAttendance.value = true
  try {
    router.put(
      '/store/settings/attendance',
      {
        branch_id: attendance.branch_id,
        address: attendance.address || null,
        barangay: attendance.barangay || null,
        city: attendance.city || null,
        province: attendance.province || null,
        latitude: attendance.latitude,
        longitude: attendance.longitude,
        geofence_radius_m: attendance.geofence_radius_m || 5,
        geofence_enabled: attendance.geofence_enabled,
      },
      {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Saved', detail: 'Attendance location updated.', life: 2500 }),
      }
    )
  } catch (error) {
    console.error('Failed to update attendance location', error)
  } finally {
    savingAttendance.value = false
  }
}

const saveStoreProfile = async () => {
  savingProfile.value = true
  try {
    router.put(
      '/store/settings/profile',
      {
        contact_person: store.contact_person || null,
        phone: store.phone || null,
        address: store.address || null,
        city: store.city || null,
        province: store.province || null,
        type: store.type || null,
      },
      {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: 'success', summary: 'Saved', detail: 'Store profile updated.', life: 3000 }),
      }
    )
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Update failed',
      detail: error?.response?.data?.message || 'Failed to update store profile.',
      life: 3000,
    })
  } finally {
    savingProfile.value = false
  }
}

const setupLeafletDefaults = () => {
  const icon = L.icon({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41],
  })
  L.Marker.prototype.options.icon = icon
}

const reverseGeocode = async (lat: number, lng: number) => {
  try {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=en`
    const response = await fetch(url, {
      headers: { 'Accept': 'application/json' },
    })
    if (!response.ok) return
    const data = await response.json()
    const address = data?.address || {}
    attendance.address = data?.display_name || attendance.address
    attendance.barangay =
      address.suburb ||
      address.neighbourhood ||
      address.village ||
      address.quarter ||
      address.hamlet ||
      attendance.barangay
    attendance.city = address.city || address.town || address.municipality || address.county || attendance.city
    attendance.province = address.state || address.region || attendance.province
  } catch (error) {
    console.error('Reverse geocode failed', error)
  }
}

const forwardGeocode = async (query: string) => {
  const trimmed = query.trim()
  if (!trimmed) return null
  const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(trimmed)}&limit=1&addressdetails=1`
  const response = await fetch(url, {
    headers: { 'Accept': 'application/json' },
  })
  if (!response.ok) return null
  const results = await response.json()
  if (!Array.isArray(results) || results.length === 0) return null
  const hit = results[0]
  return {
    lat: parseFloat(hit.lat),
    lng: parseFloat(hit.lon),
  }
}

const syncLocation = async (lat: number, lng: number) => {
  attendance.latitude = lat
  attendance.longitude = lng
  await reverseGeocode(lat, lng)
}

const initMap = async () => {
  if (!mapEl.value || mapReady.value) return
  setupLeafletDefaults()

  const defaultCenter: [number, number] = [
    attendance.latitude ?? 14.5995,
    attendance.longitude ?? 120.9842,
  ]

  const map = L.map(mapEl.value).setView(defaultCenter, attendance.latitude ? 16 : 12)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
  }).addTo(map)

  const marker = L.marker(defaultCenter, { draggable: true }).addTo(map)
  const circle = L.circle(defaultCenter, {
    radius: attendance.geofence_radius_m || 5,
    color: '#16a34a',
    fillColor: '#22c55e',
    fillOpacity: 0.15,
    weight: 1,
  }).addTo(map)

  marker.on('dragend', async () => {
    const pos = marker.getLatLng()
    circle.setLatLng(pos)
    await syncLocation(pos.lat, pos.lng)
  })

  map.on('click', async (event: any) => {
    marker.setLatLng(event.latlng)
    circle.setLatLng(event.latlng)
    await syncLocation(event.latlng.lat, event.latlng.lng)
  })

  if (!attendance.address && attendance.latitude && attendance.longitude) {
    await syncLocation(attendance.latitude, attendance.longitude)
  }

  mapRef.value = map
  markerRef.value = marker
  circleRef.value = circle
  mapReady.value = true

  const updateRadius = () => {
    circle.setRadius(attendance.geofence_radius_m || 5)
  }

  return { updateRadius }
}

const searchAddress = async () => {
  const result = await forwardGeocode(searchQuery.value)
  if (!result) return
  if (mapRef.value) {
    mapRef.value.setView([result.lat, result.lng], 16)
  }
  if (markerRef.value) {
    markerRef.value.setLatLng([result.lat, result.lng])
  }
  if (circleRef.value) {
    circleRef.value.setLatLng([result.lat, result.lng])
  }
  await syncLocation(result.lat, result.lng)
}

const goToUpgrade = () => {
  planDialogVisible.value = true
}

const selectPlan = (key: string) => {
  const plan = availablePlans.value.find(item => item.key === key)
  if (!plan) return
  selectedPlan.key = plan.key
  selectedPlan.amountPhp = plan.amountPhp
  selectedPlan.months = plan.months
  selectedPlan.tier = plan.tier
  selectedPlan.label = plan.label
}

const openGcashDialogForSelectedPlan = () => {
  planDialogVisible.value = false
  gcashForm.name = ''
  gcashForm.phone = ''
  const first = walletTypeOptions.value[0]?.value
  selectedWalletType.value = (first as any) || 'gcash'
  gcashDialogVisible.value = true
}

const openPaymentMethodDialogForSelectedPlan = () => {
  planDialogVisible.value = false
  selectedPaymentMethod.value = ''
  paymentMethodDialogVisible.value = true
}

const continueToPaymentCredentials = () => {
  paymentMethodDialogVisible.value = false
  // map selected payment method to wallet/credential dialog
  if (selectedPaymentMethod.value === 'gcash') {
    const first = walletTypeOptions.value[0]?.value
    selectedWalletType.value = (first as any) || 'gcash'
    gcashForm.name = ''
    gcashForm.phone = ''
    gcashDialogVisible.value = true
    return
  }

  // card or others: reuse credential dialog (collect account name/email) and treat as card flow
  selectedWalletType.value = 'card'
  gcashForm.name = ''
  gcashForm.phone = ''
  gcashDialogVisible.value = true
}

const goToVerification = () => {
  router.visit('/system/store/verification')
}

const openDocument = (url: string) => {
  if (!url) return
  window.open(url, '_blank', 'noopener')
}

const loadVerificationDocuments = async () => {
  if (!subscription.store_id) {
    verificationDocuments.value = []
    return
  }

  loadingVerificationDocuments.value = true
  try {
    const response = await axiosClient.get(`/api/stores/${subscription.store_id}/verification/documents`)
    const payload = response?.data?.data || {}
    verificationDocuments.value = Array.isArray(payload.documents) ? payload.documents : []
  } catch (error: any) {
    verificationDocuments.value = []
    toast.add({
      severity: 'error',
      summary: 'Unable to load documents',
      detail: error?.response?.data?.message || 'Failed to load verification attachments.',
      life: 3000,
    })
  } finally {
    loadingVerificationDocuments.value = false
  }
}

const handleVerificationAction = async () => {
  if (!shouldShowVerificationAttachments.value) {
    goToVerification()
    return
  }

  await loadVerificationDocuments()
  verificationDocumentsDialogVisible.value = true
}

const toPlainPhone = (value: string): string => value.replace(/\D/g, '')

const submitUpgradeCheckout = async () => {
  const normalizedPhone = toPlainPhone(gcashForm.phone)
  if (!gcashForm.name.trim()) {
    toast.add({ severity: 'warn', summary: 'Missing name', detail: 'Enter the GCash account name.', life: 2800 })
    return
  }
  if (!/^09\d{9}$/.test(normalizedPhone)) {
    toast.add({ severity: 'warn', summary: 'Invalid number', detail: 'Enter a valid PH GCash number (09XXXXXXXXX).', life: 3200 })
    return
  }

  gcashDialogVisible.value = false
  await startUpgradeCheckout(gcashForm.name.trim(), normalizedPhone)
}

const startUpgradeCheckout = async (gcashName: string, gcashPhone: string) => {
  upgrading.value = true
  try {
    const effectiveStoreId = subscription.store_id ?? null
    const metadata = {
      store_id: effectiveStoreId,
      months: selectedPlan.months,
      subscription_tier: selectedPlan.tier,
      plan_key: selectedPlan.key,
      plan_label: selectedPlan.label,
    }

    const fallbackEmail = (store.email || 'owner@example.com').trim()

    // Card payments should use Online Payment hosted checkout so we never collect card data on our site.
    if (selectedWalletType.value === 'card') {
      const successUrl = `${window.location.origin}/store/settings?paymongo_checkout=1`
      const cancelUrl = `${window.location.origin}/store/settings?paymongo_checkout_cancel=1`
      const sessionRes = await paymongoService.createCheckoutSession({
        amount: selectedPlan.amountPhp * 100,
        currency: 'PHP',
        description: `Store plan upgrade (${selectedPlan.label})`,
        payment_method_allowed: paymongoPaymentMethods.value.length ? paymongoPaymentMethods.value : ['gcash'],
        metadata: { ...metadata, payer_email: fallbackEmail, payer_name: gcashName, payer_phone: gcashPhone },
        store_id: effectiveStoreId,
        payable_type: 'subscription_upgrade',
        payable_id: effectiveStoreId,
        success_url: successUrl,
        cancel_url: cancelUrl,
      })
      const checkoutUrl = sessionRes?.data?.checkout_url
      if (!checkoutUrl) throw new Error(sessionRes?.message || 'Unable to start card checkout.')
      window.location.href = checkoutUrl
      return
    }

    const intentResponse = await paymongoService.createIntent({
      amount: selectedPlan.amountPhp * 100,
      currency: 'PHP',
      description: `Store plan upgrade (${selectedPlan.label})`,
      statement_descriptor: 'Store Upgrade',
      payment_method_allowed: paymongoPaymentMethods.value.length ? paymongoPaymentMethods.value : ['gcash'],
      metadata,
      store_id: effectiveStoreId,
      payable_type: 'subscription_upgrade',
      payable_id: effectiveStoreId,
    })

    const paymentIntentId = intentResponse?.data?.data?.id
    if (!paymentIntentId) {
      throw new Error(intentResponse?.message || 'Unable to create payment intent.')
    }

    const returnUrl = `${window.location.origin}/store/settings?paymongo_intent=${encodeURIComponent(paymentIntentId)}`
    const walletResponse = await paymongoService.startWallet(paymentIntentId, selectedWalletType.value as any, {
      name: gcashName,
      email: fallbackEmail,
      phone: gcashPhone,
      return_url: returnUrl,
    })

    const redirectUrl = walletResponse?.data?.redirect_url
    if (!redirectUrl) {
      throw new Error(walletResponse?.message || 'Unable to start wallet checkout.')
    }

    window.location.href = redirectUrl
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Upgrade failed',
      detail: error?.message || 'Unable to start upgrade payment.',
      life: 4000,
    })
  } finally {
    upgrading.value = false
  }
}

const handleUpgradeReturn = async () => {
  const params = new URLSearchParams(window.location.search)
  const paymentIntentId = params.get('paymongo_intent') || params.get('payment_intent_id')
  if (!paymentIntentId) return

  try {
    const statusResponse = await paymongoService.getIntent(paymentIntentId)
    const status = String(statusResponse?.data?.data?.attributes?.status || '').toLowerCase()

    if (status === 'succeeded') {
      router.reload({ preserveScroll: true })
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Payment pending',
        detail: 'Payment is still processing. Please refresh in a few moments.',
        life: 3500,
      })
    }
  } catch (_error) {
    toast.add({
      severity: 'error',
      summary: 'Status check failed',
      detail: 'Unable to verify payment status right now.',
      life: 3500,
    })
  } finally {
    params.delete('paymongo_intent')
    params.delete('payment_intent_id')
    const query = params.toString()
    const cleanUrl = `${window.location.pathname}${query ? `?${query}` : ''}`
    window.history.replaceState({}, '', cleanUrl)
  }
}

const handleUpgradePrompt = () => {
  const params = new URLSearchParams(window.location.search)
  const shouldOpen = params.get('open_upgrade') === '1'
  const targetPlan = params.get('plan')
  if (!shouldOpen) return

  if (targetPlan) {
    selectPlan(targetPlan)
  }
  planDialogVisible.value = true

  params.delete('open_upgrade')
  params.delete('plan')
  const query = params.toString()
  const cleanUrl = `${window.location.pathname}${query ? `?${query}` : ''}`
  window.history.replaceState({}, '', cleanUrl)
}

onMounted(async () => {
  await handleUpgradeReturn()
  handleUpgradePrompt()
  try {
    const handlers = await initMap()
    if (handlers?.updateRadius) {
      watch(
        () => attendance.geofence_radius_m,
        () => handlers.updateRadius()
      )
    }
  } catch (error) {
    console.error('Map init failed', error)
  }
})
</script>
