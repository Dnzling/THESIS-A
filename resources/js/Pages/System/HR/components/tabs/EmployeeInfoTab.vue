<template>
  <div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6">
      <Card class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
        <template #title>Personal</template>
        <template #content>
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Full Name</span>
              <span class="text-sm font-semibold text-slate-900 text-right">{{ employeeInfo.basic_info?.name || '-' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Birth Date</span>
              <span class="text-sm text-slate-700">{{ formatDate(employeeInfo.basic_info?.birthday) }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Gender</span>
              <span class="text-sm text-slate-700 capitalize">{{ formatLabel(employeeInfo.basic_info?.gender) }}</span>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
        <template #title>Contact</template>
        <template #content>
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Phone</span>
              <span class="text-sm text-slate-700">{{ employeeInfo.contact_info?.phone || '-' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Address</span>
              <span class="text-sm text-slate-700 text-right">{{ employeeInfo.contact_info?.address || '-' }}</span>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
        <template #title>Emergency Contact</template>
        <template #content>
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Name</span>
              <span class="text-sm text-slate-700">{{ employeeInfo.contact_info?.emergency_contact?.name || '-' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Relationship</span>
              <span class="text-sm text-slate-700">{{ employeeInfo.contact_info?.emergency_contact?.relationship || '-' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Phone</span>
              <span class="text-sm text-slate-700">{{ employeeInfo.contact_info?.emergency_contact?.phone || '-' }}</span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <div class="space-y-6">
      <Card class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
        <template #title>Employment</template>
        <template #content>
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Employee Number</span>
              <span class="text-sm font-mono text-slate-700">{{ employeeInfo.basic_info?.employee_number || '-' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Department</span>
              <span class="text-sm text-slate-700">{{ formatLabel(employeeInfo.employment_details?.department) }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Role</span>
              <span class="text-sm text-slate-700">{{ formatLabel(employeeInfo.employment_details?.role) }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Employment Type</span>
              <Tag :value="formatLabel(employeeInfo.employment_details?.type) || 'Regular'"
                :severity="getEmploymentTypeSeverity(employeeInfo.employment_details?.type)" rounded />
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Hire Date</span>
              <span class="text-sm text-slate-700">{{ formatDate(employeeInfo.employment_details?.hire_date) }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Current Shift</span>
              <div class="text-right">
                <div class="text-sm text-slate-700">{{ employeeInfo.current_shift?.shift_name || '-' }}</div>
                <div v-if="employeeInfo.current_shift?.time_range" class="text-xs text-slate-500">
                  {{ employeeInfo.current_shift.time_range }}
                </div>
                <div v-if="employeeInfo.current_shift?.covers_days_label" class="text-xs text-slate-500">
                  Covers: {{ employeeInfo.current_shift.covers_days_label }}
                </div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
        <template #title>Branch</template>
        <template #content>
          <div class="flex items-start justify-between gap-4">
            <span class="text-xs text-slate-500">Assigned Branch</span>
            <span class="text-sm text-slate-700">{{ employeeInfo.employment_details?.branch || '-' }}</span>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
        <template #title>Weekly Schedule</template>
        <template #content>
          <div class="space-y-2">
            <div v-for="day in weeklyScheduleList" :key="day.day_of_week" class="flex items-start justify-between gap-4 rounded-lg bg-slate-50 px-3 py-2">
              <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ day.day_label }}</span>
              <span class="text-sm text-slate-700 text-right">{{ day.label }}</span>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <div class="space-y-6">
      <Card class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
        <template #title>Compensation</template>
        <template #content>
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Monthly Salary</span>
              <span class="text-base font-semibold text-blue-600">PHP {{ formatNumber(employeeInfo.employment_details?.monthly_salary || 0) }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Pay Type</span>
              <span class="text-sm text-slate-700 capitalize">{{ formatLabel(employeeInfo.employment_details?.pay_type || 'monthly') }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Hourly Rate</span>
              <span class="text-sm text-slate-700">PHP {{ formatNumber(employeeInfo.employment_details?.hourly_rate || 0) }}</span>
            </div>
        
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
        <template #title>Government IDs</template>
        <template #content>
          <div class="space-y-4">
            <div v-if="governmentIdList.length" class="space-y-3">
              <div v-for="item in governmentIdList" :key="`${item.label}-${item.number}`" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-start justify-between gap-4">
                  <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ item.label }}</span>
                  <span class="text-sm font-mono text-slate-700 text-right">{{ item.number || '-' }}</span>
                </div>
                <div class="mt-1 flex flex-wrap items-center gap-2">
                  <Tag :value="formatLabel(item.status || 'pending')" :severity="item.status === 'verified' ? 'success' : 'warning'" rounded />
                  <span class="text-xs text-slate-500" v-if="item.code">{{ item.code }}</span>
                  <Button
                    v-if="item.status !== 'verified'"
                    label="Verify ID"
                    icon="pi pi-check"
                    severity="success"
                    text
                    size="small"
                    @click="$emit('verify-id', item)"
                  />
                </div>
              </div>
            </div>
            <div v-else class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <p class="text-sm text-slate-500">No government ID records available.</p>
            </div>
            <div class="flex items-center justify-end">
              <Button label="Edit" icon="pi pi-pencil" severity="info" outlined @click="$emit('edit-id')" />
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
        <template #title>Payroll Card</template>
        <template #content>
          <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Card Type</span>
              <span class="text-sm text-slate-700">{{ formatLabel(employeeInfo.credit_card?.card_type) }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Card Number</span>
              <span class="text-sm font-mono text-slate-700">{{ employeeInfo.credit_card?.masked_card_number || '-' }}</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Status</span>
              <Tag
                :value="formatLabel(employeeInfo.credit_card?.status)"
                :severity="getCardStatusSeverity(employeeInfo.credit_card?.status)"
                rounded
              />
            </div>
            <div class="flex items-start justify-between gap-4">
              <span class="text-xs text-slate-500">Assigned</span>
              <span class="text-sm text-slate-700">{{ formatDate(employeeInfo.credit_card?.assigned_at) }}</span>
            </div>
            <div class="flex items-center justify-end pt-2">
              <Button label="Edit Card" icon="pi pi-pencil" severity="info" outlined @click="$emit('edit-card')" />
            </div>
          </div>
        </template>
      </Card>
    </div>
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
