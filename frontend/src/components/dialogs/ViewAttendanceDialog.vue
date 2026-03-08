<!-- components/dialogs/ViewAttendanceDialog.vue -->
<template>
  <Dialog :visible="visible" @update:visible="emit('update:visible', $event)" modal
    :header="`Attendance Details - ${selectedAttendance?.employee?.full_name || ''}`" :style="{ width: '700px' }"
    :draggable="false" class="attendance-details-dialog" @hide="onHide">
    <!-- Loading State -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
      <i class="pi pi-spin pi-spinner text-4xl text-primary-500 mb-4"></i>
      <p class="text-gray-600">Loading attendance details...</p>
    </div>
  
    <!-- Content -->
    <div v-else-if="selectedAttendance" class="space-y-6">
      <!-- Employee Summary -->
      <div class="flex items-center gap-4 p-4 bg-white border border-gray-200 rounded-lg">
        <!-- Avatar -->
        <Avatar :label="getInitials(selectedAttendance.employee?.full_name || '')" size="large" shape="circle"
          class="bg-gray-100 text-gray-700 font-medium" />
  
        <!-- Employee Info -->
        <div class="flex-1">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold text-gray-800">
                {{ selectedAttendance.employee?.full_name || 'Unknown' }}
              </h3>
              <p class="text-sm text-gray-500">
                {{ selectedAttendance.employee?.department || 'N/A' }} • {{ selectedAttendance.employee?.branch || 'N/A'
                }}
              </p>
            </div>
  
            <!-- Status & Date -->
            <div class="text-right">
              <div class="flex items-center gap-2">
                <Tag :value="selectedAttendance.status_badge?.label || 'UNKNOWN'"
                  :severity="selectedAttendance.status_badge?.color || 'secondary'" />
                <span class="text-sm text-gray-500">{{ selectedAttendance.attendance_date }}</span>
              </div>
              <Badge v-if="selectedAttendance.time_summary?.late_minutes > 0"
                :value="`Late: ${selectedAttendance.time_summary?.late_minutes}m`" severity="danger" />
            </div>
          </div>
        </div>
      </div>
  
      <!-- Shift Details - Add safe formatting -->
      <div class="grid grid-cols-2 gap-4">
        <div>
          <span class="text-gray-600 text-sm">Shift:</span>
          <p class="font-medium">{{ selectedAttendance.shift_details?.name || 'N/A' }}</p>
          <p class="text-xs text-gray-500">{{ selectedAttendance.shift_details?.code || '' }}</p>
        </div>
        <div>
          <span class="text-gray-600 text-sm">Schedule:</span>
          <p class="font-medium">
            {{ selectedAttendance.shift_details?.start_time || '--:--' }} -
            {{ selectedAttendance.shift_details?.end_time || '--:--' }}
          </p>
        </div>
        <div>
          <span class="text-gray-600 text-sm">Break Schedule:</span>
          <p class="font-medium">
            {{ selectedAttendance.shift_details?.break_start || '--:--' }} -
            {{ selectedAttendance.shift_details?.break_end || '--:--' }}
          </p>
        </div>
        <div>
          <span class="text-gray-600 text-sm">Total Hours:</span>
          <p class="font-medium">{{ selectedAttendance.shift_details?.total_hours || '0' }} hrs</p>
        </div>
      </div>
  
      <!-- Time Details -->
      <div class="grid grid-cols-2 gap-4">
        <div class="border rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-500 mb-3 flex items-center gap-2">
            Check In Details
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-gray-600">Time:</span>
              <span class="font-medium">{{ selectedAttendance.clock_in_details?.time || '--:--' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">IP Address:</span>
              <span class="font-mono text-sm">{{ selectedAttendance.clock_in_details?.ip || 'N/A' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Location:</span>
              <span>{{ selectedAttendance.clock_in_details?.location || 'N/A' }}</span>
            </div>
          </div>
        </div>
  
        <div class="border rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-500 mb-3 flex items-center gap-2">
            Check Out Details
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-gray-600">Time:</span>
              <span class="font-medium">{{ selectedAttendance.clock_out_details?.time || '--:--' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">IP Address:</span>
              <span class="font-mono text-sm">{{ selectedAttendance.clock_out_details?.ip || 'N/A' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-600">Location:</span>
              <span>{{ selectedAttendance.clock_out_details?.location || 'N/A' }}</span>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Break Details -->
      <div class="border rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-500 mb-3 flex items-center gap-2">
          <i class="pi pi-stopwatch text-primary-500"></i>
          Break Time
        </h4>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <span class="text-gray-600 text-sm">Break Start:</span>
            <p class="font-medium">{{ selectedAttendance.break_details?.break_start || '--:--' }}</p>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Break End:</span>
            <p class="font-medium">{{ selectedAttendance.break_details?.break_end || '--:--' }}</p>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Total Break:</span>
            <p class="font-medium">{{ selectedAttendance.break_details?.break_hours || '0h 0m' }}</p>
          </div>
        </div>
      </div>
  
      <!-- Time Summary -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-blue-50 rounded-lg p-3 text-center">
          <span class="text-xs text-gray-600">Total Worked</span>
          <p class="text-lg font-semibold text-blue-700">{{ selectedAttendance.time_summary?.total_worked_hours || '0h 0m'
            }}</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-3 text-center">
          <span class="text-xs text-gray-600">Late</span>
          <p class="text-lg font-semibold text-yellow-700">{{ selectedAttendance.time_summary?.late_hours || '0h 0m' }}
          </p>
        </div>
        <div class="bg-orange-50 rounded-lg p-3 text-center">
          <span class="text-xs text-gray-600">Early Departure</span>
          <p class="text-lg font-semibold text-orange-700">{{ selectedAttendance.time_summary?.early_departure_hours ||
            '0h 0m' }}</p>
        </div>
        <div class="bg-green-50 rounded-lg p-3 text-center">
          <span class="text-xs text-gray-600">Overtime</span>
          <p class="text-lg font-semibold text-green-700">{{ selectedAttendance.time_summary?.overtime_hours || '0h 0m' }}
          </p>
        </div>
      </div>
  
      <!-- Approval Information -->
      <div v-if="selectedAttendance.approval?.approved_by" class="border rounded-lg p-4 bg-green-50">
        <h4 class="text-sm font-medium text-gray-600 mb-3 flex items-center gap-2">
          <i class="pi pi-check-circle text-green-600"></i>
          Approval Information
        </h4>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <span class="text-gray-600 text-sm">Approved By:</span>
            <p class="font-medium">{{ selectedAttendance.approval?.approved_by?.name }}</p>
            <p class="text-xs text-gray-500">{{ selectedAttendance.approval?.approved_by?.role }}</p>
          </div>
          <div>
            <span class="text-gray-600 text-sm">Approved At:</span>
            <p class="font-medium">{{ selectedAttendance.approval?.approved_at }}</p>
          </div>
        </div>
      </div>
  
      <!-- Notes -->
      <div v-if="selectedAttendance.notes" class="border rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-500 mb-2">Notes</h4>
        <p class="text-gray-700">{{ selectedAttendance.notes }}</p>
      </div>
  
      <!-- Timestamps -->
      <div class="text-xs text-gray-400 flex justify-between border-t pt-4">
        <span>Created: {{ selectedAttendance.timestamps?.created_at || 'N/A' }}</span>
        <span>Updated: {{ selectedAttendance.timestamps?.updated_at || 'N/A' }}</span>
      </div>
    </div>
  
    <template #footer>
      <Button label="Close" icon="pi pi-times" severity="secondary" @click="closeDialog" />
      <Button v-if="selectedAttendance && !loading" label="Edit Attendance" icon="pi pi-pencil" severity="contrast"
        @click="editFromDetails" />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import { useAuthStore } from '../../stores/auth'

const authStore = useAuthStore()

const props = defineProps<{
  visible: boolean
  attendanceId: number | null
}>()

const emit = defineEmits<{
  (e: 'update:visible', value: boolean): void
  (e: 'edit', attendance: any): void
}>()

const toast = useToast()
const loading = ref(false)
const selectedAttendance = ref<any>(null)

// Helper: Format datetime to time string (HH:MM AM/PM)
// Helper: Format datetime to time string (HH:MM AM/PM)
const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTime = (datetime: string) => {
  if (!datetime) return 'N/A'
  return new Date(datetime).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const formatDateTime = (datetime: string) => {
  if (!datetime) return 'N/A'
  return new Date(datetime).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  })
}

const formatStatus = (status: string) => {
  const map: Record<string, string> = {
    'present': 'Present',
    'late': 'Late',
    'absent': 'Absent',
    'on_leave': 'On Leave',
    'half_day': 'Half Day',
    'holiday': 'Holiday',
    'rest_day': 'Rest Day'
  }
  return map[status] || status || 'Unknown'
}

const getStatusSeverity = (status: string) => {
  const map: Record<string, string> = {
    'present': 'success',
    'late': 'warning',
    'absent': 'danger',
    'on_leave': 'info',
    'half_day': 'warn',
    'holiday': 'secondary',
    'rest_day': 'contrast'
  }
  return map[status] || 'secondary'
}

// Helper: Convert minutes to hours and minutes string
const minutesToHours = (minutes: number | null): string => {
  if (!minutes || minutes <= 0) return '0h 0m'
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return `${hours}h ${mins}m`
}

// Helper: Transform API response to UI format
const transformAttendance = (data: any) => {
  const employee = data.employee || {}
  const shift = data.shift || {}
  const schedule = data.schedule || {}

  return {
    // Attendance Date
    attendance_date: formatDate(data.attendance_date),
    attendance_date_raw: data.attendance_date,

    // Employee basic info
    employee: {
      ...employee,
      full_name: `${employee.fname || ''} ${employee.lname || ''}`.trim() || 'Unknown',
      department: employee.department || 'N/A',
      branch: 'Main Branch' // Add branch if available in API
    },

    // Status badge
    status_badge: {
      label: data.status ? data.status.toUpperCase() : 'UNKNOWN',
      color: getStatusSeverity(data.status)
    },

    // Clock in details
    clock_in_details: {
      time: formatTime(data.clock_in),
      raw: data.clock_in,
      ip: data.clock_in_ip || 'N/A',
      location: data.clock_in_location || 'N/A',
      method: data.clock_in_method || 'N/A'
    },

    // Clock out details
    clock_out_details: {
      time: formatTime(data.clock_out),
      raw: data.clock_out,
      ip: data.clock_out_ip || 'N/A',
      location: data.clock_out_location || 'N/A',
      method: data.clock_out_method || 'N/A'
    },

    // Break details
    break_details: {
      break_start: formatTime(data.break_start),
      break_end: formatTime(data.break_end),
      break_hours: minutesToHours(data.break_minutes),
      break_minutes: data.break_minutes || 0
    },

    // Time summary
    time_summary: {
      total_worked_hours: minutesToHours(data.total_worked_minutes),
      total_worked_minutes: data.total_worked_minutes || 0,
      late_hours: minutesToHours(data.late_minutes),
      late_minutes: data.late_minutes || 0,
      early_departure_hours: minutesToHours(data.early_departure_minutes),
      early_departure_minutes: data.early_departure_minutes || 0,
      overtime_hours: minutesToHours(data.overtime_minutes),
      overtime_minutes: data.overtime_minutes || 0,
      night_differential_hours: minutesToHours(data.night_differential_minutes),
      night_differential_minutes: data.night_differential_minutes || 0
    },

    // Shift details
    shift_details: {
      id: shift.id,
      name: shift.name || 'N/A',
      code: shift.code || '',
      start_time: shift.start_time,
      end_time: shift.end_time,
      break_start: shift.break_start,
      break_end: shift.break_end,
      total_hours: shift.total_hours || '0',
      shift_type: shift.shift_type,
      week_days: shift.week_days || [],
      grace_period_minutes: shift.grace_period_minutes
    },

    // Schedule details
    schedule_details: {
      id: schedule.id,
      schedule_date: formatDate(schedule.schedule_date),
      generation_method: schedule.generation_method,
      status: schedule.status
    },

    // Approval info
    approval: {
      approved_by: data.approver ? {
        name: `${data.approver.fname || ''} ${data.approver.lname || ''}`.trim(),
        role: data.approver.role?.name || 'N/A'
      } : null,
      approved_at: data.approved_at ? formatDate(data.approved_at) : null
    },

    // Overtime request
    overtime_request: data.overtime_request ? {
      id: data.overtime_request.id,
      ot_start: formatTime(data.overtime_request.ot_start),
      ot_end: formatTime(data.overtime_request.ot_end),
      ot_minutes: data.overtime_request.ot_minutes,
      ot_type: data.overtime_request.ot_type,
      reason: data.overtime_request.reason,
      status: data.overtime_request.status
    } : null,

    // Employee details
    employee_details: {
      employee_number: employee.employee_number || 'N/A',
      email: employee.user?.email || 'N/A',
      phone: employee.phone || 'N/A',
      hire_date: formatDate(employee.hire_date),
      employment_type: employee.employment_type || 'N/A',
      gender: employee.gender || 'N/A',
      date_of_birth: formatDate(employee.date_of_birth),
      address: employee.address || 'N/A'
    },

    // Notes
    notes: data.notes || null,

    // Timestamps
    timestamps: {
      created_at: formatDate(data.created_at),
      updated_at: formatDate(data.updated_at),
      raw_created_at: data.created_at,
      raw_updated_at: data.updated_at
    },

    // Raw data for editing
    raw: data,

    // Additional fields
    id: data.id,
    status: data.status,
    is_ot_approved: data.is_ot_approved,
    is_restday_work: data.is_restday_work
  }
}
// Fetch when ID changes or dialog opens
watch([() => props.attendanceId, () => props.visible], async ([newId, newVisible]) => {
  if (newId && newVisible) {
    await fetchAttendanceDetails(newId)
  } else if (!newVisible) {
    selectedAttendance.value = null
  }
})

const fetchAttendanceDetails = async (id: number) => {
  loading.value = true
  try {
    const response = await axios.get(`api/attendances/${id}`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      },
      params: { with_details: true }
    })

    if (response.data.success) {
      // Transform the raw API response to UI format
      selectedAttendance.value = transformAttendance(response.data.data)
    }
  } catch (err: any) {
    console.error('Fetch error:', err.response?.data || err)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to fetch attendance details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const closeDialog = () => {
  emit('update:visible', false)
}

const editFromDetails = () => {
  // Pass the raw data for editing
  if (selectedAttendance.value?.raw) {
    emit('edit', selectedAttendance.value.raw)
  } else {
    emit('edit', selectedAttendance.value)
  }
  closeDialog()
}

const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ').map((n: string) => n[0]).join('').toUpperCase()
}

const onHide = () => {
  // Optional cleanup
}
</script>