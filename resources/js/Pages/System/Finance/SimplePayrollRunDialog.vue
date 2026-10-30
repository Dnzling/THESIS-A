<template>
  <Dialog :visible="visible" modal :style="{ width: 'min(94rem, 98vw)' }" :breakpoints="{ '960px': '98vw' }" :closable="!generating" :draggable="false" @update:visible="emit('update:visible', $event)">
    <template #header>
      <div class="flex items-center gap-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-600"><i class="pi pi-calculator text-lg" /></span>
        <div><h2 class="text-lg font-semibold text-slate-900">Generate Payroll</h2><p class="text-xs text-slate-500">Preview attendance, review adjustments, and save drafts.</p></div>
      </div>
    </template>
    <div class="space-y-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="mb-3 flex items-center justify-between gap-3"><div><p class="text-sm font-semibold text-slate-900">1. Pay period</p><p class="mt-0.5 text-xs text-slate-500">Choose completed dates to include in this payroll run.</p></div><Badge value="Attendance-based" severity="info" /></div>
        <div class="grid gap-3 md:grid-cols-[minmax(16rem,1fr)_minmax(13rem,0.75fr)_auto] md:items-end">
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Completed date range *</label>
          <DatePicker v-model="dateRange" selectionMode="range" :maxDate="yesterday" :manualInput="false" dateFormat="M d, yy" showIcon fluid />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Branch</label>
          <Select v-model="branchId" :options="branches" optionLabel="name" optionValue="id" showClear placeholder="All branches" :loading="branchesLoading" fluid />
        </div>
        <Button label="Preview Attendance" icon="pi pi-search" severity="warn" :loading="previewLoading" :disabled="!canPreview" @click="previewPayroll" />
        </div>
      </div>

      <div class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 md:grid-cols-[minmax(15rem,1fr)_auto] md:items-center">
        <div>
          <p class="text-sm font-semibold text-slate-800">Standard paid hours per workday</p>
          <p class="mt-1 text-xs leading-5 text-slate-500">Used to convert daily rates to hourly equivalents. Unpaid breaks are excluded.</p>
        </div>
        <div class="flex items-center gap-2">
          <InputNumber v-model="standardPaidHours" :min="1" :max="24" :maxFractionDigits="2" size="small" fluid suffix=" hrs" />
          <Button label="Save hours" icon="pi pi-save" severity="secondary" outlined size="small" :loading="savingHours" :disabled="!settingsLoaded || !standardPaidHours || standardPaidHours < 1 || standardPaidHours > 24" @click="savePaidHours" fluid />
        </div>
        <p v-if="hoursMessage" class="text-xs md:col-span-2" :class="hoursMessageIsError ? 'text-red-600' : 'text-emerald-700'">{{ hoursMessage }}</p>
      </div>

      <div class="flex gap-2 rounded-lg border border-orange-100 bg-orange-50/70 px-3 py-2.5 text-xs leading-5 text-orange-900"><i class="pi pi-info-circle mt-0.5 shrink-0 text-orange-600" /><p>Missing clock-outs are flagged and excluded from completed shifts. Absences are not inferred from missed punches; enter confirmed absence or leave days. Statutory and other deductions require manual review.</p></div>

      <Message v-if="errorMessage" severity="error" :closable="false">{{ errorMessage }}</Message>

      <template v-if="previewed">
        <div class="grid gap-3 sm:grid-cols-3">
          <div class="rounded-xl border border-slate-200 bg-white p-3"><p class="text-xs text-slate-500">Employees included</p><p class="mt-1 text-lg font-semibold tabular-nums text-slate-900">{{ previewRows.length }}</p></div>
          <div class="rounded-xl border border-slate-200 bg-white p-3"><p class="text-xs text-slate-500">Total recorded hours</p><p class="mt-1 text-lg font-semibold tabular-nums text-slate-900">{{ totalWorkedHours.toFixed(2) }} <span class="text-xs font-normal text-slate-500">hrs</span></p></div>
          <div class="rounded-xl border border-orange-200 bg-orange-50/60 p-3"><p class="text-xs text-orange-800">Estimated net payroll</p><p class="mt-1 text-lg font-semibold tabular-nums text-orange-900">{{ money(totalEstimatedNet) }}</p></div>
        </div>
        <p class="text-xs text-slate-500">Estimates only. Review attendance and adjustments before generating drafts.</p>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
          <DataTable :value="previewRows" dataKey="employee_id" rowHover size="small" class="text-xs" scrollable scrollDirection="horizontal" :tableStyle="{ minWidth: '1120px' }">
            <Column header="Employee" frozen style="min-width: 14rem">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <Avatar :image="data.avatar_url || undefined" :label="initials(data.employee_name)" shape="circle" size="small" class="bg-orange-100 text-orange-700" />
                  <div><p class="font-medium text-slate-900">{{ data.employee_name }}</p><p class="text-xs text-slate-500">{{ data.employee_number }} · {{ capitalize(data.pay_type) }}</p></div>
                </div>
              </template>
            </Column>
            <Column header="Attendance" style="min-width: 9rem">
              <template #body="{ data }">
                <p>{{ data.completed_workdays }} workday(s)</p>
                <p class="text-xs text-slate-500">{{ Number(data.worked_hours).toFixed(2) }} hrs · {{ data.late_minutes }} late min</p>
                <p v-if="data.missing_clock_outs" class="mt-1 text-xs font-medium text-amber-700">{{ data.missing_clock_outs }} missing clock-out(s)</p>
              </template>
            </Column>
            <Column header="Base Pay" style="min-width: 8rem"><template #body="{ data }">{{ money(data.base_salary) }}</template></Column>
            <Column header="Unpaid Absence / Leave" style="min-width: 9rem">
              <template #body="{ data }"><InputNumber v-model="data.unpaid_absence_days" :min="0" :maxFractionDigits="2" size="small" fluid :disabled="data.pay_type === 'daily'" /></template>
            </Column>
            <Column header="Paid Leave" style="min-width: 8rem">
              <template #body="{ data }"><InputNumber v-model="data.paid_leave_days" :min="0" :maxFractionDigits="2" size="small" fluid :disabled="data.pay_type !== 'daily'" /></template>
            </Column>
            <Column header="Allowance" style="min-width: 8rem"><template #body="{ data }"><InputNumber v-model="data.allowances" :min="0" mode="currency" currency="PHP" locale="en-PH" size="small" fluid /></template></Column>
            <Column header="Incentive" style="min-width: 8rem"><template #body="{ data }"><InputNumber v-model="data.incentives" :min="0" mode="currency" currency="PHP" locale="en-PH" size="small" fluid /></template></Column>
            <Column header="Other Deductions" style="min-width: 9rem"><template #body="{ data }"><InputNumber v-model="data.other_deductions" :min="0" mode="currency" currency="PHP" locale="en-PH" size="small" fluid /></template></Column>
            <Column header="Est. Net Pay" style="min-width: 9rem">
              <template #body="{ data }"><span class="font-semibold text-slate-900">{{ money(estimatedNet(data)) }}</span></template>
            </Column>
            <template #empty><div class="py-8 text-center text-sm text-slate-500">No active employees found for this selection.</div></template>
          </DataTable>
        </div>
      </template>
      <div v-else-if="previewLoading" class="space-y-3 rounded-xl border border-slate-200 p-4">
        <Skeleton width="14rem" height="1rem" />
        <Skeleton width="100%" height="2.5rem" />
        <Skeleton width="100%" height="2.5rem" />
      </div>
      <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
        <i class="pi pi-calendar-clock text-2xl text-slate-400" />
        <p class="mt-2 text-sm font-medium text-slate-700">Preview attendance to start</p>
        <p class="mt-1 text-xs text-slate-500">Choose a completed date range, then review each employee’s attendance and estimated pay.</p>
      </div>
    </div>

    <template #footer>
      <Button label="Close" severity="secondary" outlined :disabled="generating" @click="emit('update:visible', false)" />
      <Button v-if="previewed" label="Generate Draft Payroll" icon="pi pi-save" severity="warn" :loading="generating" :disabled="!previewRows.length" @click="generateDrafts" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import axios from '@/axios'
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'

const props = defineProps<{ visible: boolean }>()
const emit = defineEmits<{ (event: 'update:visible', value: boolean): void; (event: 'generated'): void }>()
const yesterday = new Date(new Date().setDate(new Date().getDate() - 1))
const dateRange = ref<Date[] | null>([new Date(yesterday.getFullYear(), yesterday.getMonth(), 1), yesterday])
const branchId = ref<number | null>(null)
const branches = ref<Array<{ id: number; name: string }>>([])
const branchesLoading = ref(false)
const standardPaidHours = ref(8)
const payrollSettings = ref<Record<string, any>>({})
const settingsLoaded = ref(false)
const savingHours = ref(false)
const hoursMessage = ref('')
const hoursMessageIsError = ref(false)
const previewLoading = ref(false)
const generating = ref(false)
const previewed = ref(false)
const previewRows = ref<any[]>([])
const errorMessage = ref('')
const canPreview = computed(() => Boolean(dateRange.value?.length === 2 && dateRange.value[0] && dateRange.value[1] && !previewLoading.value && !generating.value))

const dateString = (date: Date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const money = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const initials = (name: string) => String(name || '').split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]?.toUpperCase()).join('') || 'E'
const capitalize = (value: string) => value ? value.charAt(0).toUpperCase() + value.slice(1) : 'Monthly'
const estimatedNet = (row: any) => {
  const absenceDeduction = row.pay_type === 'monthly' ? Number(row.unpaid_absence_days || 0) * Number(row.day_rate || 0) : 0
  const paidLeavePay = row.pay_type === 'daily' ? Number(row.paid_leave_days || 0) * Number(row.day_rate || 0) : 0
  return Number(row.base_salary || 0) + paidLeavePay + Number(row.overtime_amount || 0) + Number(row.allowances || 0) + Number(row.incentives || 0)
    - Number(row.late_deduction || 0) - absenceDeduction - Number(row.other_deductions || 0)
}
const totalWorkedHours = computed(() => previewRows.value.reduce((sum, row) => sum + Number(row.worked_hours || 0), 0))
const totalEstimatedNet = computed(() => previewRows.value.reduce((sum, row) => sum + estimatedNet(row), 0))

async function loadBranches() {
  branchesLoading.value = true
  try {
    const response = await axios.get('/api/branches')
    branches.value = response.data?.data || response.data || []
  } catch {
    branches.value = []
  } finally {
    branchesLoading.value = false
  }
  try {
    const response = await axios.get('/api/store/settings/hr')
    const data = response.data?.data || {}
    payrollSettings.value = data.payroll_settings || {}
    const configuredHours = Number(payrollSettings.value.paidHours || data.workforce_rules?.dailyHours || 8)
    standardPaidHours.value = Math.max(1, Math.min(24, configuredHours))
    settingsLoaded.value = true
  } catch {
    hoursMessage.value = 'Could not load the saved workday hours. The payroll preview will use 8 hours until settings are available.'
    hoursMessageIsError.value = true
  }
}

async function savePaidHours() {
  if (!settingsLoaded.value || standardPaidHours.value < 1 || standardPaidHours.value > 24) return
  savingHours.value = true
  hoursMessage.value = ''
  try {
    const response = await axios.put('/api/store/settings/hr', {
      payroll_settings: { ...payrollSettings.value, paidHours: standardPaidHours.value },
    })
    payrollSettings.value = response.data?.data?.payroll_settings || { ...payrollSettings.value, paidHours: standardPaidHours.value }
    hoursMessage.value = 'Standard paid hours saved for this store.'
    hoursMessageIsError.value = false
  } catch (error: any) {
    hoursMessage.value = error?.response?.data?.message || 'Unable to save the paid-hours setting.'
    hoursMessageIsError.value = true
  } finally {
    savingHours.value = false
  }
}

async function previewPayroll() {
  if (!canPreview.value || !dateRange.value) return
  previewLoading.value = true
  previewed.value = false
  previewRows.value = []
  errorMessage.value = ''
  try {
    const response = await axios.get('/api/store/simple-staff/payroll/preview', {
      params: { start_date: dateString(dateRange.value[0]), end_date: dateString(dateRange.value[1]), branch_id: branchId.value || undefined },
    })
    previewRows.value = (response.data?.data || []).map((row: any) => ({
      ...row,
      unpaid_absence_days: 0,
      paid_leave_days: 0,
      allowances: 0,
      incentives: 0,
      other_deductions: 0,
    }))
    previewed.value = true
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || Object.values(error?.response?.data?.errors || {}).flat()[0] as string || 'Unable to preview payroll. Please try again.'
  } finally {
    previewLoading.value = false
  }
}

async function generateDrafts() {
  if (!previewRows.value.length || !dateRange.value || generating.value) return
  generating.value = true
  errorMessage.value = ''
  try {
    await axios.post('/api/store/simple-staff/payroll/generate', {
      start_date: dateString(dateRange.value[0]),
      end_date: dateString(dateRange.value[1]),
      branch_id: branchId.value || undefined,
      rows: previewRows.value.map(row => ({
        employee_id: row.employee_id,
        unpaid_absence_days: row.unpaid_absence_days,
        paid_leave_days: row.paid_leave_days,
        allowances: row.allowances,
        incentives: row.incentives,
        other_deductions: row.other_deductions,
      })),
    })
    emit('generated')
    emit('update:visible', false)
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || Object.values(error?.response?.data?.errors || {}).flat()[0] as string || 'Unable to save payroll drafts. Please review and try again.'
  } finally {
    generating.value = false
  }
}

watch(() => props.visible, value => {
  if (value && !branches.value.length) void loadBranches()
  if (!value) {
    previewed.value = false
    previewRows.value = []
    errorMessage.value = ''
    hoursMessage.value = ''
  }
})
</script>
