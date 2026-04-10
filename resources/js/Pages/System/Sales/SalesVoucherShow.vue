<template>
  <div class="max-w-6xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <ConfirmDialog />
    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Voucher Details</h1>
            <p class="text-sm text-gray-500">Includes draft voucher stats for quick monitoring.</p>
          </div>
          <div class="flex items-center gap-2">
            <Button icon="pi pi-pencil" label="Edit" @click="openEdit" />
            <Button text icon="pi pi-arrow-left" label="Back to Vouchers" @click="router.visit('/sales/vouchers')" />
          </div>
        </div>
      </template>
    </Card>

    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #title>Voucher Info</template>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div><p class="text-gray-500">Voucher Name</p><p class="font-semibold text-gray-900">{{ voucher.voucher_name || '-' }}</p></div>
          <div><p class="text-gray-500">Code</p><p class="font-semibold text-gray-900">{{ voucher.code || '-' }}</p></div>
          <div><p class="text-gray-500">Voucher Slots</p><p class="font-semibold text-gray-900">{{ Number(voucher.voucher_slots || 0) }}</p></div>
          <div><p class="text-gray-500">Discount</p><p class="font-semibold text-gray-900">{{ discountLabel(voucher) }}</p></div>
          <div><p class="text-gray-500">Status</p><Tag :severity="statusSeverity(voucher)">{{ statusLabel(voucher) }}</Tag></div>
          <div><p class="text-gray-500">Minimum Order</p><p class="font-semibold text-gray-900">{{ money(voucher.min_order_amount) }}</p></div>
          <div><p class="text-gray-500">Max Discount</p><p class="font-semibold text-gray-900">{{ voucher.max_discount_amount ? money(voucher.max_discount_amount) : '-' }}</p></div>
          <div><p class="text-gray-500">Start / End</p><p class="font-semibold text-gray-900">{{ dt(voucher.starts_at) }} - {{ dt(voucher.ends_at) }}</p></div>
        </div>
      </template>
    </Card>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Drafts Total</p><p class="text-2xl font-semibold">{{ draftStats.total_drafts }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Draft Slots</p><p class="text-2xl font-semibold">{{ draftStats.total_draft_slots }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Drafts Started</p><p class="text-2xl font-semibold">{{ draftStats.drafts_started }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">With End Date</p><p class="text-2xl font-semibold">{{ draftStats.drafts_with_end_date }}</p></template></Card>
      <Card class="border border-gray-100 shadow-sm rounded-2xl"><template #content><p class="text-xs text-gray-500 uppercase">Future Start</p><p class="text-2xl font-semibold">{{ draftStats.drafts_future_start }}</p></template></Card>
    </div>

    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #title>Draft Vouchers</template>
      <template #content>
        <DataTable :value="drafts" stripedRows>
          <Column field="code" header="Code" />
          <Column field="voucher_slots" header="Slots" />
          <Column field="discount_type" header="Type" />
          <Column field="discount_value" header="Discount">
            <template #body="{ data }">{{ data.discount_type === 'percent' ? `${Number(data.discount_value || 0)}%` : money(data.discount_value) }}</template>
          </Column>
          <Column field="starts_at" header="Start"><template #body="{ data }">{{ dt(data.starts_at) }}</template></Column>
          <Column field="ends_at" header="End"><template #body="{ data }">{{ dt(data.ends_at) }}</template></Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="editDialogVisible" modal header="Edit Voucher" :style="{ width: '48rem' }">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm text-gray-600">Voucher Name</label>
          <InputText v-model="editForm.voucher_name" class="w-full" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Voucher Slots</label>
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
          <label class="text-sm text-gray-600">Discount Value</label>
          <InputNumber v-model="editForm.discount_value" :min="0.01" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
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
      <template #footer>
        <Button text label="Cancel" @click="editDialogVisible = false" />
        <Button label="Save Changes" icon="pi pi-save" :loading="saving" @click="saveEdit" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Dropdown from 'primevue/dropdown'
import Checkbox from 'primevue/checkbox'
import Calendar from 'primevue/calendar'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'

const voucher = reactive<any>({})
const draftStats = reactive<any>({
  total_drafts: 0,
  total_draft_slots: 0,
  drafts_started: 0,
  drafts_with_end_date: 0,
  drafts_future_start: 0,
})
const drafts = ref<any[]>([])
const confirm = useConfirm()
const editDialogVisible = ref(false)
const saving = ref(false)
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

const getIdFromPath = () => {
  const parts = window.location.pathname.split('/').filter(Boolean)
  return parts[parts.length - 1]
}

const load = async () => {
  const id = getIdFromPath()
  const res = await salesService.getVoucher(id)
  Object.assign(voucher, res?.data || {})
  Object.assign(draftStats, res?.draft_stats || {})
  drafts.value = res?.drafts || []
}

const openEdit = () => {
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
  const confirmed = await confirmSaveEdit()
  if (!confirmed) {
    return
  }

  saving.value = true
  try {
    const id = getIdFromPath()
    await salesService.updateVoucher(id, {
      ...editForm,
      starts_at: isoOrNull(editForm.starts_at),
      ends_at: isoOrNull(editForm.ends_at),
    })
    editDialogVisible.value = false
    await load()
  } finally {
    saving.value = false
  }
}

const money = (v: number | string | null | undefined) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))
const dt = (v: string | null | undefined) => (v ? new Date(v).toLocaleString('en-PH') : '-')
const isExpired = (row: any) => !!row?.ends_at && new Date(row.ends_at).getTime() < Date.now()
const statusLabel = (row: any) => (isExpired(row) ? 'Expired' : row?.is_active ? 'Active' : 'Draft')
const statusSeverity = (row: any) => (isExpired(row) ? 'danger' : row?.is_active ? 'success' : 'warn')
const discountLabel = (row: any) => row?.discount_type === 'percent' ? `${Number(row?.discount_value || 0)}%` : money(row?.discount_value)

onMounted(load)
</script>
