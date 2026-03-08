<!-- views/system/employees/components/tabs/EmployeeInfoTab.vue -->
<template>
  <div class="grid grid-cols-3 gap-6">
    <!-- Personal Information -->
    <div class="col-span-1 space-y-4">
      <div class="border border-gray-100 rounded-lg p-4">
        <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <i class="pi pi-user text-blue-500"></i>
          Personal Information
        </h3>
        <div class="space-y-3">
          <div>
            <div class="text-xs text-gray-500">Full Name</div>
            <div class="text-sm font-medium">{{ employeeInfo.basic_info?.name }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Birth Date</div>
            <div class="text-sm">{{ formatDate(employeeInfo.basic_info?.birthday) || '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Gender</div>
            <div class="text-sm capitalize">{{ employeeInfo.basic_info?.gender || '—' }}</div>
          </div>
        </div>
      </div>

      <div class="border border-gray-100 rounded-lg p-4">
        <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <i class="pi pi-phone text-blue-500"></i>
          Contact Information
        </h3>
        <div class="space-y-3">
          <div>
            <div class="text-xs text-gray-500">Phone Number</div>
            <div class="text-sm">{{ employeeInfo.contact_info?.phone || '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Address</div>
            <div class="text-sm">{{ employeeInfo.contact_info?.address || '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Emergency Contact</div>
            <div class="text-sm">{{ employeeInfo.contact_info?.emergency_contact?.name || '—' }}</div>
            <div class="text-xs text-gray-500">
              {{ employeeInfo.contact_info?.emergency_contact?.relationship }}: 
              {{ employeeInfo.contact_info?.emergency_contact?.phone }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Employment Details -->
    <div class="col-span-1 space-y-4">
      <div class="border border-gray-100 rounded-lg p-4">
        <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <i class="pi pi-briefcase text-blue-500"></i>
          Employment Details
        </h3>
        <div class="space-y-3">
          <div>
            <div class="text-xs text-gray-500">Employee ID</div>
            <div class="text-sm font-mono">{{ employeeInfo.basic_info?.employee_number }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Department</div>
            <div class="text-sm">{{ employeeInfo.employment_details?.department || '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Position / Role</div>
            <div class="text-sm">{{ employeeInfo.employment_details?.role || '—' }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Employment Type</div>
            <div class="text-sm">
              <Tag :value="employeeInfo.employment_details?.type || 'Regular'"
                :severity="getEmploymentTypeSeverity(employeeInfo.employment_details?.type)" rounded />
            </div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Hire Date</div>
            <div class="text-sm">{{ formatDate(employeeInfo.employment_details?.hire_date) }}</div>
          </div>
        </div>
      </div>

      <div class="border border-gray-100 rounded-lg p-4">
        <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <i class="pi pi-building text-blue-500"></i>
          Branch / Location
        </h3>
        <div class="space-y-3">
          <div>
            <div class="text-xs text-gray-500">Branch</div>
            <div class="text-sm">{{ employeeInfo.employment_details?.branch || '—' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Government IDs & Compensation -->
    <div class="col-span-1 space-y-4">
      <div class="border border-gray-100 rounded-lg p-4">
        <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <i class="pi pi-id-card text-blue-500"></i>
          Government IDs
        </h3>
        <div class="space-y-3">
          <div>
            <div class="text-xs text-gray-500">TIN Number</div>
            <div class="text-sm font-mono">{{ employeeInfo.employment_details?.tax_id || '—' }}</div>
          </div>
        </div>
      </div>

      <div class="border border-gray-100 rounded-lg p-4">
        <h3 class="font-semibold text-gray-700 mb-3 flex items-center gap-2">
          <i class="pi pi-money-bill text-blue-500"></i>
          Compensation
        </h3>
        <div class="space-y-3">
          <div>
            <div class="text-xs text-gray-500">Monthly Salary</div>
            <div class="text-lg font-semibold text-blue-600">₱{{ formatNumber(employeeInfo.employment_details?.monthly_salary || 0) }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Bank Account</div>
            <div class="text-sm">{{ employeeInfo.employment_details?.bank_account || '—' }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  employeeInfo: any
}>()

const formatDate = (date: string) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatNumber = (num: number) => {
  return num?.toLocaleString() || '0'
}

const getEmploymentTypeSeverity = (type: string) => {
  const map: Record<string, string> = {
    'full_time': 'success',
    'part_time': 'warning',
    'contract': 'info',
    'intern': 'secondary'
  }
  return map[type?.toLowerCase()] || 'info'
}
</script>