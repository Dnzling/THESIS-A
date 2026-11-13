<template>
  <div class="mx-auto max-w-6xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
    <ConfirmDialog />
    <Card class="rounded-2xl border border-slate-200 shadow-sm">
      <template #content>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-orange-600">Voucher Details</p>
            <h1 class="mt-1 break-words text-xl font-semibold text-slate-900">{{ voucher.voucher_name || voucher.code || 'Loading voucher...' }}</h1>
            <p class="mt-1 text-sm text-slate-500">Usage updates automatically every 10 seconds.</p>
          </div>
          <div class="flex items-center gap-2">
            <Button icon="pi pi-pencil" label="Edit" severity="warn" size="small" @click="openEdit" />

          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-slate-200 shadow-sm">
      <template #title>Voucher Info</template>
      <template #content>
        <div class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
          <div><p class="text-xs text-slate-500">Voucher Code</p><p class="mt-1 break-all font-semibold text-slate-900">{{ voucher.code || '-' }}</p></div>
          <div><p class="text-xs text-slate-500">Discount</p><p class="mt-1 font-semibold text-slate-900">{{ discountLabel(voucher) }}</p></div>
          <div><p class="text-xs text-slate-500">Status</p><div class="mt-1"><Badge :severity="statusSeverity(voucher)" :value="statusLabel(voucher)" /></div></div>
          <div><p class="text-xs text-slate-500">Minimum Order</p><p class="mt-1 font-semibold text-slate-900">{{ money(voucher.min_order_amount) }}</p></div>
          <div><p class="text-xs text-slate-500">Maximum Discount</p><p class="mt-1 font-semibold text-slate-900">{{ voucher.max_discount_amount ? money(voucher.max_discount_amount) : 'No limit' }}</p></div>
          <div><p class="text-xs text-slate-500">Valid From</p><p class="mt-1 font-semibold text-slate-900">{{ dt(voucher.starts_at) }}</p></div>
          <div><p class="text-xs text-slate-500">Valid Until</p><p class="mt-1 font-semibold text-slate-900">{{ dt(voucher.ends_at) }}</p></div>
          <div><p class="text-xs text-slate-500">Created By</p><p class="mt-1 font-semibold text-slate-900">{{ voucher.created_by_name || 'Not recorded' }}</p></div>
          <div><p class="text-xs text-slate-500">Created At</p><p class="mt-1 font-semibold text-slate-900">{{ dateTime(voucher.created_at) }}</p></div>
          <div><p class="text-xs text-slate-500">Usage Limit</p><p class="mt-1 font-semibold text-slate-900">{{ Number(voucher.voucher_slots || 0) > 0 ? Number(voucher.voucher_slots) : 'Unlimited' }}</p></div>
        </div>
      </template>
    </Card>

    <Card class="rounded-2xl border border-slate-200 shadow-sm">
      <template #title>
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span>Voucher Usage</span>
          <span class="text-xs font-normal text-slate-500">{{ refreshing ? 'Updating…' : `Updated ${lastUpdatedLabel}` }}</span>
        </div>
      </template>
      <template #content>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Times Used</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ Number(voucher.used_order_count || 0).toLocaleString() }}</p>
            <p class="mt-1 text-xs text-slate-500">Orders checked out with this code</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Customers</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ Number(voucher.used_customer_count || 0).toLocaleString() }}</p>
            <p class="mt-1 text-xs text-slate-500">Unique signed-in customers</p>
          </div>
          <div class="rounded-xl border border-orange-200 bg-orange-50/60 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-orange-800">Uses Remaining</p>
            <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ voucher.remaining_slots === null ? 'Unlimited' : Number(voucher.remaining_slots || 0).toLocaleString() }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ Number(voucher.voucher_slots || 0) > 0 ? `From ${Number(voucher.voucher_slots).toLocaleString()} total uses` : 'No usage limit set' }}</p>
          </div>
        </div>
        <p class="mt-4 rounded-lg bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-600">
          A use is recorded when checkout creates the order. Cancelled, failed, or refunded orders release the use.
        </p>
        <p v-if="loadError" class="mt-3 text-xs text-red-600">Could not refresh usage right now. The last loaded count is still shown.</p>
      </template>
    </Card>

    <Dialog v-model:visible="editDialogVisible" modal header="Edit Voucher" :style="{ width: '48rem' }">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm text-gray-600">Voucher Name</label>
          <InputText v-model="editForm.voucher_name" class="w-full" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Maximum Uses (0 = unlimited)</label>
          <InputNumber v-model="editForm.voucher_slots" :min="0" :useGrouping="false" class="w-full" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Code</label>
          <InputText v-model="editForm.code" class="w-full" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Discount Type</label>
          <Dropdown v-model="editForm.discount_type" :options="discountTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div>
          <label class="text-sm text-gray-600">{{ editForm.discount_type === 'percent' ? 'Discount Percentage' : 'Discount Amount' }}</label>
          <InputNumber
            v-model="editForm.discount_value"
            :mode="editForm.discount_type === 'percent' ? 'decimal' : 'currency'"
            :currency="editForm.discount_type === 'fixed' ? 'PHP' : undefined"
            :suffix="editForm.discount_type === 'percent' ? '%' : undefined"
            :min="0.01"
            :max="editForm.discount_type === 'percent' ? 100 : undefined"
            :minFractionDigits="editForm.discount_type === 'percent' ? 0 : 2"
            :maxFractionDigits="2"
            :invalid="Boolean(editDiscountValueError)"
            class="w-full"
          />
          <small v-if="editDiscountValueError" class="mt-1 block text-xs text-red-600">{{ editDiscountValueError }}</small>
          <small v-else-if="editForm.discount_type === 'percent'" class="mt-1 block text-xs text-slate-500">Enter a discount from 0.01% to 100%.</small>
        </div>
        <div>
          <label class="text-sm text-gray-600">Minimum Order Amount</label>
          <InputNumber v-model="editForm.min_order_amount" :min="0" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Max Discount Amount</label>
          <InputNumber v-model="editForm.max_discount_amount" :min="0" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
        </div>
        <div class="flex items-center gap-2 pt-6">
          <Checkbox v-model="editForm.is_active" binary inputId="edit_is_active" />
          <label for="edit_is_active" class="text-sm text-gray-700">Active</label>
        </div>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <div>
          <label class="text-sm text-gray-600">Start Date</label>
          <Calendar v-model="editForm.starts_at" showTime hourFormat="12" class="w-full" />
        </div>
        <div>
          <label class="text-sm text-gray-600">End Date</label>
          <Calendar v-model="editForm.ends_at" showTime hourFormat="12" class="w-full" />
        </div>
      </div>
      <p v-if="editErrorMessage" class="mt-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ editErrorMessage }}</p>
      <template #footer>
        <Button text label="Cancel" @click="editDialogVisible = false" />
        <Button label="Save Changes" icon="pi pi-save" :loading="saving" @click="saveEdit" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import crmService from '@/services/crm.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'
import Checkbox from 'primevue/checkbox'
import Calendar from 'primevue/calendar'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'

const voucher = reactive<any>({})
const confirm = useConfirm()
const refreshing = ref(false)
const loadError = ref(false)
const lastUpdatedAt = ref<Date | null>(null)
let refreshTimer: ReturnType<typeof setInterval> | null = null
const lastUpdatedLabel = computed(() => lastUpdatedAt.value
  ? lastUpdatedAt.value.toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit', second: '2-digit' })
  : '—')
const editDialogVisible = ref(false)
const saving = ref(false)
const editErrorMessage = ref('')
const editForm = reactive<any>({
  voucher_name: '',
  voucher_slots: 0,
  code: '',
  discount_type: 'fixed',
  discount_value: 0,
  min_order_amount: 0,
  max_discount_amount: null,
  starts_at: null,
  ends_at: null,
  is_active: false,
})
const discountTypeOptions = [
  { label: 'Fixed Amount', value: 'fixed' },
  { label: 'Percent', value: 'percent' },
]
const editDiscountValueError = computed(() => editForm.discount_type === 'percent' && Number(editForm.discount_value) > 100
  ? 'Percentage discount cannot exceed 100%.'
  : '')

const getIdFromPath = () => {
  const parts = window.location.pathname.split('/').filter(Boolean)
  return parts[parts.length - 1]
}

const load = async () => {
  if (refreshing.value) return
  refreshing.value = true
  const id = getIdFromPath()
  try {
    const res = await crmService.getVoucher(id)
    Object.assign(voucher, res?.data || {})
    lastUpdatedAt.value = new Date()
    loadError.value = false
  } catch {
    loadError.value = true
  } finally {
    refreshing.value = false
  }
}

const openEdit = () => {
  editErrorMessage.value = ''
  Object.assign(editForm, {
    voucher_name: voucher.voucher_name || '',
    voucher_slots: Number(voucher.voucher_slots || 0),
    code: voucher.code || '',
    discount_type: voucher.discount_type || 'fixed',
    discount_value: Number(voucher.discount_value || 0),
    min_order_amount: Number(voucher.min_order_amount || 0),
    max_discount_amount: voucher.max_discount_amount ? Number(voucher.max_discount_amount) : null,
    starts_at: voucher.starts_at ? new Date(voucher.starts_at) : null,
    ends_at: voucher.ends_at ? new Date(voucher.ends_at) : null,
    is_active: Boolean(voucher.is_active),
  })
  editDialogVisible.value = true
}

const isoOrNull = (value: Date | null) => (value ? new Date(value).toISOString() : null)
const confirmSaveEdit = () =>
  new Promise<boolean>((resolve) => {
    confirm.require({
      message: 'Save changes to this voucher?',
      header: 'Confirm Save',
      icon: 'pi pi-exclamation-triangle',
      acceptLabel: 'Save',
      rejectLabel: 'Cancel',
      accept: () => resolve(true),
      reject: () => resolve(false),
    })
  })

const saveEdit = async () => {
  if (editDiscountValueError.value) {
    editErrorMessage.value = editDiscountValueError.value
    return
  }

  const confirmed = await confirmSaveEdit()
  if (!confirmed) {
    return
  }

  saving.value = true
  try {
    const id = getIdFromPath()
    await crmService.updateVoucher(id, {
      ...editForm,
      starts_at: isoOrNull(editForm.starts_at),
      ends_at: isoOrNull(editForm.ends_at),
    })
    editDialogVisible.value = false
    await load()
  } catch (error: any) {
    editErrorMessage.value = error?.response?.data?.message || 'Unable to save voucher changes.'
  } finally {
    saving.value = false
  }
}

const money = (v: number | string | null | undefined) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const dt = (v: string | null | undefined) => (v ? new Date(v).toLocaleString('en-PH') : '-')
const dateTime = (v: string | null | undefined) => (v ? new Date(v).toLocaleString('en-PH', { timeZone: 'Asia/Manila', month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : '-')
const isExpired = (row: any) => !!row?.ends_at && new Date(row.ends_at).getTime() < Date.now()
const statusLabel = (row: any) => (isExpired(row) ? 'Expired' : row?.is_active ? 'Active' : 'Draft')
const statusSeverity = (row: any) => (isExpired(row) ? 'danger' : row?.is_active ? 'success' : 'warn')
const discountLabel = (row: any) => row?.discount_type === 'percent' ? `${Number(row?.discount_value || 0)}%` : money(row?.discount_value)

onMounted(() => {
  void load()
  refreshTimer = setInterval(() => void load(), 10000)
})

onUnmounted(() => {
  if (refreshTimer) clearInterval(refreshTimer)
})
</script>
