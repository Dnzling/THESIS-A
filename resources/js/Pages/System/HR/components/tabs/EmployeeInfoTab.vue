<template>
  <div class="space-y-2">
    <section>
      <Divider align="left"><span class="text-sm font-semibold text-slate-800">Personal Information</span></Divider>
      <div class="grid gap-x-10 gap-y-4 sm:grid-cols-2">
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Full Name</span><span class="text-right text-sm font-medium text-slate-900">{{ employeeInfo.basic_info?.name || '-' }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Birth Date</span><span class="text-right text-sm text-slate-700">{{ formatDate(employeeInfo.basic_info?.birthday) }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Gender</span><span class="text-right text-sm text-slate-700">{{ formatLabel(employeeInfo.basic_info?.gender) }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Phone</span><span class="text-right text-sm text-slate-700">{{ employeeInfo.contact_info?.phone || '-' }}</span></div>
        <div class="flex justify-between gap-4 sm:col-span-2"><span class="text-sm text-slate-500">Address</span><span class="max-w-[70%] text-right text-sm text-slate-700">{{ employeeInfo.contact_info?.address || '-' }}</span></div>
      </div>
      <div class="mt-4 rounded-lg bg-slate-50 px-4 py-3">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Emergency Contact</p>
        <div class="grid gap-3 sm:grid-cols-3">
          <div><p class="text-xs text-slate-500">Name</p><p class="mt-1 text-sm text-slate-800">{{ employeeInfo.contact_info?.emergency_contact?.name || '-' }}</p></div>
          <div><p class="text-xs text-slate-500">Relationship</p><p class="mt-1 text-sm text-slate-800">{{ employeeInfo.contact_info?.emergency_contact?.relationship || '-' }}</p></div>
          <div><p class="text-xs text-slate-500">Phone</p><p class="mt-1 text-sm text-slate-800">{{ employeeInfo.contact_info?.emergency_contact?.phone || '-' }}</p></div>
        </div>
      </div>
    </section>

    <section>
      <Divider align="left"><span class="text-sm font-semibold text-slate-800">Job Information</span></Divider>
      <div class="grid gap-x-10 gap-y-4 sm:grid-cols-2">
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Employee Number</span><span class="text-right font-mono text-sm text-slate-700">{{ employeeInfo.basic_info?.employee_number || '-' }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Department</span><span class="text-right text-sm text-slate-700">{{ formatLabel(employeeInfo.employment_details?.department) }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Role</span><span class="text-right text-sm text-slate-700">{{ formatLabel(employeeInfo.employment_details?.role) }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Employment Type</span><Tag :value="formatLabel(employeeInfo.employment_details?.type) || 'Regular'" :severity="getEmploymentTypeSeverity(employeeInfo.employment_details?.type)" rounded /></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Hire Date</span><span class="text-right text-sm text-slate-700">{{ formatDate(employeeInfo.employment_details?.hire_date) }}</span></div>
        <div v-if="employeeInfo.employment_details?.type === 'contract'" class="flex justify-between gap-4"><span class="text-sm text-slate-500">Contract End Date</span><span class="text-right text-sm text-slate-700">{{ formatDate(employeeInfo.employment_details?.contract_end_date) }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Assigned Branch</span><span class="text-right text-sm text-slate-700">{{ employeeInfo.employment_details?.branch || '-' }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Current Shift</span><span class="text-right text-sm text-slate-700">{{ employeeInfo.current_shift?.shift_name || '-' }}<span v-if="employeeInfo.current_shift?.time_range" class="block text-xs text-slate-500">{{ employeeInfo.current_shift.time_range }}</span><span v-if="employeeInfo.current_shift?.covers_days_label" class="block text-xs text-slate-500">Covers: {{ employeeInfo.current_shift.covers_days_label }}</span></span></div>
      </div>
      <div class="mt-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Weekly Schedule</p>
        <div class="grid gap-2 sm:grid-cols-2">
          <div v-for="day in weeklyScheduleList" :key="day.day_of_week" class="flex justify-between gap-4 rounded-lg bg-slate-50 px-3 py-2"><span class="text-xs font-semibold text-slate-500">{{ day.day_label }}</span><span class="text-right text-sm text-slate-700">{{ day.label }}</span></div>
        </div>
      </div>
    </section>

    <section>
      <Divider align="left"><span class="text-sm font-semibold text-slate-800">Salary Information</span></Divider>
      <div class="grid gap-x-10 gap-y-4 sm:grid-cols-2">
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Salary / Rate</span><span class="text-right text-sm font-semibold text-slate-900">PHP {{ formatNumber(employeeInfo.employment_details?.monthly_salary || 0) }} / {{ employeeInfo.employment_details?.pay_type === 'hourly' ? 'hour' : employeeInfo.employment_details?.pay_type === 'daily' ? 'day' : 'month' }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Pay Type</span><span class="text-right text-sm text-slate-700">{{ formatLabel(employeeInfo.employment_details?.pay_type || 'monthly') }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Hourly Rate</span><span class="text-right text-sm text-slate-700">PHP {{ formatNumber(employeeInfo.employment_details?.hourly_rate || 0) }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Payroll Card</span><span class="text-right text-sm text-slate-700">{{ formatLabel(employeeInfo.credit_card?.card_type) }} · {{ employeeInfo.credit_card?.masked_card_number || 'Not assigned' }}</span></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Card Status</span><Tag :value="formatLabel(employeeInfo.credit_card?.status) || 'Not assigned'" :severity="getCardStatusSeverity(employeeInfo.credit_card?.status)" rounded /></div>
        <div class="flex justify-between gap-4"><span class="text-sm text-slate-500">Card Assigned</span><span class="text-right text-sm text-slate-700">{{ formatDate(employeeInfo.credit_card?.assigned_at) }}</span></div>
      </div>
      <div class="mt-3 flex justify-end"><Button label="Edit Payroll Card" icon="pi pi-pencil" severity="secondary" outlined size="small" @click="$emit('edit-card')" /></div>
    </section>

    <section>
      <Divider align="left"><span class="text-sm font-semibold text-slate-800">Documents</span></Divider>
      <div v-if="governmentIdList.length" class="divide-y divide-slate-100">
        <div v-for="item in governmentIdList" :key="`${item.label}-${item.number}`" class="flex flex-wrap items-center justify-between gap-3 py-3">
          <div><p class="text-sm font-medium text-slate-800">{{ item.label }}</p><p class="font-mono text-xs text-slate-500">{{ item.number || '-' }}</p></div>
          <div class="flex items-center gap-2"><Tag :value="formatLabel(item.status || 'pending')" :severity="item.status === 'verified' ? 'success' : 'warn'" rounded /><span v-if="item.code" class="text-xs text-slate-500">{{ item.code }}</span><Button v-if="item.status !== 'verified'" label="Verify" icon="pi pi-check" severity="success" text size="small" @click="$emit('verify-id', item)" /></div>
        </div>
      </div>
      <p v-else class="py-3 text-sm text-slate-500">No government ID records available.</p>
      <div class="flex justify-end"><Button label="Edit Documents" icon="pi pi-pencil" severity="secondary" outlined size="small" @click="$emit('edit-id')" /></div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  employeeInfo: any
}>()
const emit = defineEmits<{
  (e: 'updated'): void
  (e: 'edit-id'): void
  (e: 'edit-card'): void
  (e: 'verify-id', item: any): void
}>()

const governmentIdList = computed(() => {
  const records =
    props.employeeInfo?.governmentIds ||
    props.employeeInfo?.government_ids ||
    props.employeeInfo?.employee_government_ids ||
    props.employeeInfo?.government_id_records ||
    props.employeeInfo?.governmentIds?.data ||
    props.employeeInfo?.government_ids?.data ||
    []

  const normalized = Array.isArray(records) ? records : (records?.data || records?.items || [])

  return Array.isArray(normalized) ? normalized.map((record: any) => ({
    id: record.id,
    label: record.label || record.id_type || record.code || 'Government ID',
    number: record.id_number || record.number || '-',
    status: String(record.status || 'pending').toLowerCase(),
    code: record.code || '',
    filePath: record.id_file_path || record.file_path || '',
    verifiedAt: record.verified_at || record.verifiedAt || null,
  })) : []
})

const weeklyScheduleList = computed(() => {
  const records = Array.isArray(props.employeeInfo?.weekly_schedule) ? props.employeeInfo.weekly_schedule : []
  const fallback = [
    { day_of_week: 'monday', day_label: 'Monday' },
    { day_of_week: 'tuesday', day_label: 'Tuesday' },
    { day_of_week: 'wednesday', day_label: 'Wednesday' },
    { day_of_week: 'thursday', day_label: 'Thursday' },
    { day_of_week: 'friday', day_label: 'Friday' },
    { day_of_week: 'saturday', day_label: 'Saturday' },
    { day_of_week: 'sunday', day_label: 'Sunday' },
  ]

  return fallback.map((day) => {
    const record = records.find((item: any) => String(item.day_of_week).toLowerCase() === day.day_of_week)
    const label = record?.is_off
      ? 'Off'
      : (record?.shift_name || record?.shift?.name || ((record?.start_time && record?.end_time) ? `${formatTime(record.start_time)} - ${formatTime(record.end_time)}` : 'Scheduled'))

    return { ...day, label }
  })
})

const formatTime = (value?: string | null) => {
  if (!value) return '-'
  const raw = String(value).trim()
  const normalized = raw.toUpperCase()

  const twelveHourMatch = normalized.match(/^(\d{1,2}):(\d{2})\s*([AP]M)$/)
  if (twelveHourMatch) {
    const hours = Number(twelveHourMatch[1])
    const minutes = twelveHourMatch[2]
    const period = twelveHourMatch[3]
    if (Number.isFinite(hours) && hours >= 1 && hours <= 12) {
      return `${String(hours).padStart(2, '0')}:${minutes} ${period}`
    }
  }

  const twentyFourMatch = raw.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/)
  if (twentyFourMatch) {
    const hours24 = Number(twentyFourMatch[1])
    const minutes = twentyFourMatch[2]
    if (!Number.isNaN(hours24)) {
      const period = hours24 >= 12 ? 'PM' : 'AM'
      const hours12 = ((hours24 + 11) % 12) + 1
      return `${String(hours12).padStart(2, '0')}:${minutes} ${period}`
    }
  }

  return raw
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatNumber = (num: number) => {
  return num?.toLocaleString() || '0'
}

const formatLabel = (value: string | null | undefined) => {
  if (!value) return '-'
  return value
    .toString()
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const getEmploymentTypeSeverity = (type: string) => {
  const map: Record<string, string> = {
    full_time: 'success',
    part_time: 'warning',
    contract: 'info',
    intern: 'secondary'
  }
  return map[type?.toLowerCase()] || 'info'
}

const getCardStatusSeverity = (status: string) => {
  const map: Record<string, string> = {
    active: 'success',
    pending: 'warning',
    inactive: 'secondary'
  }
  return map[status?.toLowerCase()] || 'secondary'
}
</script>
