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
              <span class="text-xs text-slate-500">Bank Account</span>
              <span class="text-sm text-slate-700">{{ employeeInfo.employment_details?.bank_account || '-' }}</span>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 bg-white/90 shadow-sm">
        <template #title>Government IDs</template>
        <template #content>
          <div class="flex items-start justify-between gap-4">
            <span class="text-xs text-slate-500">TIN</span>
            <span class="text-sm font-mono text-slate-700">{{ employeeInfo.employment_details?.tax_id || '-' }}</span>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  employeeInfo: any
}>()

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
</script>
