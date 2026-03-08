<!-- views/system/employees/components/tabs/EmployeeAttendanceTab.vue -->
<template>
  <div class="space-y-4">
    <!-- Summary Cards -->
    <div class="grid grid-cols-5 gap-4">
      <div class="bg-gray-50 rounded-lg p-3">
        <div class="text-xs text-gray-500">Total Days</div>
        <div class="text-xl font-semibold text-gray-700">{{ summary.total_days || 0 }}</div>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <div class="text-xs text-gray-500">Present</div>
        <div class="text-xl font-semibold text-green-600">{{ summary.present || 0 }}</div>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <div class="text-xs text-gray-500">Absent</div>
        <div class="text-xl font-semibold text-red-600">{{ summary.absent || 0 }}</div>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <div class="text-xs text-gray-500">Late</div>
        <div class="text-xl font-semibold text-orange-600">{{ summary.late || 0 }}</div>
      </div>
      <div class="bg-gray-50 rounded-lg p-3">
        <div class="text-xs text-gray-500">Total Hours</div>
        <div class="text-xl font-semibold text-blue-600">{{ summary.total_worked_hours || 0 }}</div>
      </div>
    </div>

    <!-- Additional Summary Row -->
    <div class="grid grid-cols-4 gap-4">
      <div class="bg-gray-50/50 rounded-lg p-2">
        <div class="text-xs text-gray-500">Half Day</div>
        <div class="text-lg font-semibold text-amber-600">{{ summary.half_day || 0 }}</div>
      </div>
      <div class="bg-gray-50/50 rounded-lg p-2">
        <div class="text-xs text-gray-500">On Leave</div>
        <div class="text-lg font-semibold text-purple-600">{{ summary.on_leave || 0 }}</div>
      </div>
      <div class="bg-gray-50/50 rounded-lg p-2">
        <div class="text-xs text-gray-500">Holiday</div>
        <div class="text-lg font-semibold text-indigo-600">{{ summary.holiday || 0 }}</div>
      </div>
      <div class="bg-gray-50/50 rounded-lg p-2">
        <div class="text-xs text-gray-500">OT Hours</div>
        <div class="text-lg font-semibold text-blue-600">
          {{ (summary.total_overtime_minutes / 60).toFixed(1) || 0 }}
        </div>
      </div>
    </div>

    <!-- Date Range Selector -->
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">From:</span>
          <DatePicker 
            v-model="dateRange.startDate" 
            dateFormat="yy-mm-dd"
            :maxDate="dateRange.endDate || new Date()"
            placeholder="Start Date"
            class="w-36"
            size="small"
            @date-select="handleDateSelect"
          />
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm text-gray-600">To:</span>
          <DatePicker 
            v-model="dateRange.endDate" 
            dateFormat="yy-mm-dd"
            :minDate="dateRange.startDate"
            :maxDate="new Date()"
            placeholder="End Date"
            class="w-36"
            size="small"
            @date-select="handleDateSelect"
          />
        </div>
        <Button 
          label="Apply Filter" 
          icon="pi pi-filter" 
          size="small"
          @click="applyDateFilter" 
          :disabled="!isDateRangeValid"
        />
        <Button 
          label="Reset" 
          icon="pi pi-refresh" 
          severity="secondary" 
          text 
          size="small"
          @click="resetDateFilter" 
        />
      </div>
      <div class="flex items-center gap-2">
        <Button 
          label="Export" 
          icon="pi pi-download" 
          severity="secondary" 
          outlined 
          size="small"
          @click="exportAttendance" 
        />
        <Button 
          label="Refresh" 
          icon="pi pi-refresh" 
          severity="secondary" 
          text 
          size="small"
          @click="fetchAttendanceData" 
        />
      </div>
    </div>

    <!-- Quick Month Selector -->
    <div class="flex gap-2 pb-2 overflow-x-auto">
      <Button 
        v-for="month in quickMonths" 
        :key="month.value"
        :label="month.label"
        size="small"
        :severity="selectedQuickMonth === month.value ? 'primary' : 'secondary'"
        :outlined="selectedQuickMonth !== month.value"
        @click="selectQuickMonth(month.value)"
      />
    </div>

    <!-- Employee Info Banner -->
    <div v-if="employeeInfo.name" class="bg-blue-50 border border-blue-200 rounded-lg p-3 flex items-center gap-3">
      <i class="pi pi-user text-blue-500"></i>
      <div>
        <span class="font-medium text-blue-700">{{ employeeInfo.name }}</span>
        <span v-if="employeeInfo.employee_id" class="text-sm text-blue-600 ml-2">(ID: {{ employeeInfo.employee_id }})</span>
      </div>
      <div class="ml-auto text-sm text-gray-600">
        {{ dateRangeDisplay }}
      </div>
    </div>

    <!-- Attendance Table -->
    <DataTable 
      :value="attendanceHistory" 
      :paginator="true" 
      :rows="15" 
      :rowsPerPageOptions="[10, 15, 20, 50]"
      class="p-datatable-sm"
      :loading="loading"
      stripedRows
      showGridlines
      sortField="date"
      :sortOrder="-1"
    >
      <Column field="date_formatted" header="Date" :sortable="true" style="width: 120px">
        <template #body="{ data }">
          <div class="font-medium">{{ data.date_formatted }}</div>
          <div class="text-xs text-gray-500">{{ data.day }}</div>
        </template>
      </Column>
      
      <Column field="clock_in" header="Time In" :sortable="true" style="width: 100px">
        <template #body="{ data }">
          <div :class="{ 'text-orange-600 font-semibold': data.is_late }">
            {{ data.clock_in || '—' }}
          </div>
          <div v-if="data.late_minutes > 0" class="text-xs text-orange-600">
            Late: {{ formatMinutes(data.late_minutes) }}
          </div>
        </template>
      </Column>
      
      <Column field="clock_out" header="Time Out" :sortable="true" style="width: 100px">
        <template #body="{ data }">
          <div>{{ data.clock_out || '—' }}</div>
        </template>
      </Column>
      
      <Column field="status" header="Status" :sortable="true" style="width: 100px">
        <template #body="{ data }">
          <Tag :value="data.status_label" :severity="getAttendanceSeverity(data.status)" rounded />
        </template>
      </Column>
      
      <Column field="shift_name" header="Shift" :sortable="true" style="width: 120px">
        <template #body="{ data }">
          <div>{{ data.shift_name || 'No Shift' }}</div>
          <div v-if="data.shift_start && data.shift_end" class="text-xs text-gray-500">
            {{ formatShiftTime(data.shift_start) }} - {{ formatShiftTime(data.shift_end) }}
          </div>
        </template>
      </Column>
      
      <Column field="total_worked_hours" header="Worked" :sortable="true" style="width: 80px">
        <template #body="{ data }">
          <div class="font-medium">{{ data.total_worked_hours.toFixed(2) }} hrs</div>
          <div v-if="data.break_minutes > 0" class="text-xs text-gray-500">
            Break: {{ formatMinutes(data.break_minutes) }}
          </div>
        </template>
      </Column>
      
      <Column field="overtime_minutes" header="OT" :sortable="true" style="width: 60px">
        <template #body="{ data }">
          <div v-if="data.overtime_minutes > 0" class="text-blue-600 font-medium">
            {{ (data.overtime_minutes / 60).toFixed(1) }} hrs
          </div>
          <div v-else class="text-gray-400">—</div>
        </template>
      </Column>
      
      <Column field="night_differential_minutes" header="Night Diff" :sortable="true" style="width: 80px">
        <template #body="{ data }">
          <div v-if="data.night_differential_minutes > 0" class="text-purple-600 font-medium">
            {{ (data.night_differential_minutes / 60).toFixed(1) }} hrs
          </div>
          <div v-else class="text-gray-400">—</div>
        </template>
      </Column>
      
      <Column field="clock_in_method" header="Method" style="width: 80px">
        <template #body="{ data }">
          <div class="text-xs">
            <span class="capitalize">{{ data.clock_in_method || '—' }}</span>
            <span v-if="data.clock_out_method" class="text-gray-500 block text-xs">
              out: {{ data.clock_out_method }}
            </span>
          </div>
        </template>
      </Column>
      
      <Column field="notes" header="Notes" style="min-width: 150px">
        <template #body="{ data }">
          <span class="text-xs text-gray-600">{{ data.notes || '—' }}</span>
        </template>
      </Column>
      
      <Column header="Actions" style="width: 80px">
        <template #body="{ data }">
          <div class="flex gap-1">
            <Button 
              icon="pi pi-eye" 
              text 
              rounded 
              size="small" 
              v-tooltip="'View Details'"
              @click="viewAttendanceDetails(data)"
            />
            <Button 
              v-if="canEditAttendance(data)"
              icon="pi pi-pencil" 
              text 
              rounded 
              size="small"
              severity="info"
              v-tooltip="'Edit'"
              @click="editAttendance(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <!-- No Data Message -->
    <div v-if="!loading && attendanceHistory.length === 0" class="text-center py-8 text-gray-500">
      <i class="pi pi-calendar text-4xl mb-2 block text-gray-300"></i>
      <p>No attendance records found for the selected period</p>
      <p class="text-sm mt-1">Try adjusting your date range or check back later</p>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="space-y-2">
      <Skeleton v-for="i in 5" :key="i" height="3rem"></Skeleton>
    </div>

    <!-- Attendance Details Dialog -->
    <Dialog 
      v-model:visible="showDetailsDialog" 
      header="Attendance Details" 
      :style="{ width: '450px' }"
      :modal="true"
    >
      <div v-if="selectedAttendance" class="space-y-3">
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-gray-50 p-2 rounded">
            <div class="text-xs text-gray-500">Date</div>
            <div class="font-medium">{{ selectedAttendance.date_formatted }}</div>
            <div class="text-xs text-gray-600">{{ selectedAttendance.day }}</div>
          </div>
          <div class="bg-gray-50 p-2 rounded">
            <div class="text-xs text-gray-500">Status</div>
            <Tag :value="selectedAttendance.status_label" :severity="getAttendanceSeverity(selectedAttendance.status)" />
          </div>
        </div>
        
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-gray-50 p-2 rounded">
            <div class="text-xs text-gray-500">Clock In</div>
            <div class="font-medium" :class="{ 'text-orange-600': selectedAttendance.is_late }">
              {{ selectedAttendance.clock_in || '—' }}
            </div>
            <div class="text-xs text-gray-600">Method: {{ selectedAttendance.clock_in_method || '—' }}</div>
          </div>
          <div class="bg-gray-50 p-2 rounded">
            <div class="text-xs text-gray-500">Clock Out</div>
            <div class="font-medium">{{ selectedAttendance.clock_out || '—' }}</div>
            <div class="text-xs text-gray-600">Method: {{ selectedAttendance.clock_out_method || '—' }}</div>
          </div>
        </div>
        
        <div class="grid grid-cols-3 gap-2">
          <div class="bg-gray-50 p-2 rounded text-center">
            <div class="text-xs text-gray-500">Late</div>
            <div class="font-medium text-orange-600">{{ formatMinutes(selectedAttendance.late_minutes) }}</div>
          </div>
          <div class="bg-gray-50 p-2 rounded text-center">
            <div class="text-xs text-gray-500">Break</div>
            <div class="font-medium">{{ formatMinutes(selectedAttendance.break_minutes) }}</div>
          </div>
          <div class="bg-gray-50 p-2 rounded text-center">
            <div class="text-xs text-gray-500">OT</div>
            <div class="font-medium text-blue-600">{{ formatMinutes(selectedAttendance.overtime_minutes) }}</div>
          </div>
        </div>
        
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-gray-50 p-2 rounded">
            <div class="text-xs text-gray-500">Shift</div>
            <div class="font-medium">{{ selectedAttendance.shift_name || 'No Shift' }}</div>
            <div v-if="selectedAttendance.shift_start" class="text-xs text-gray-600">
              {{ formatShiftTime(selectedAttendance.shift_start) }} - {{ formatShiftTime(selectedAttendance.shift_end) }}
            </div>
          </div>
          <div class="bg-gray-50 p-2 rounded">
            <div class="text-xs text-gray-500">Total Worked</div>
            <div class="font-medium text-green-600">{{ selectedAttendance.total_worked_hours.toFixed(2) }} hours</div>
            <div class="text-xs text-gray-600">{{ selectedAttendance.total_worked_minutes }} minutes</div>
          </div>
        </div>
        
        <div v-if="selectedAttendance.notes" class="bg-gray-50 p-2 rounded">
          <div class="text-xs text-gray-500">Notes</div>
          <div class="text-sm">{{ selectedAttendance.notes }}</div>
        </div>
        
        <div class="text-xs text-gray-400 pt-2 border-t">
          Record ID: {{ selectedAttendance.id }}
        </div>
      </div>
      
      <template #footer>
        <Button label="Close" icon="pi pi-times" @click="showDetailsDialog = false" text />
        <Button 
          v-if="canEditAttendance(selectedAttendance)"
          label="Edit" 
          icon="pi pi-pencil" 
          @click="editAttendance(selectedAttendance)" 
          autofocus 
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../../stores/auth'

const props = defineProps<{
  employeeId: string | number
  initialData?: any
}>()

const emit = defineEmits(['update:attendance', 'export', 'edit'])

const authStore = useAuthStore()
const toast = useToast()

// State
const loading = ref(false)
const attendanceHistory = ref<any[]>([])
const employeeInfo = ref({
  id: null,
  employee_id: null,
  name: '',
  department: null,
  position: null
})

const summary = ref({
  total_days: 0,
  present: 0,
  absent: 0,
  late: 0,
  half_day: 0,
  on_leave: 0,
  holiday: 0,
  total_worked_minutes: 0,
  total_worked_hours: 0,
  total_late_minutes: 0,
  total_overtime_minutes: 0,
  total_night_differential_minutes: 0
})

// Date range state
const dateRange = ref({
  startDate: null as Date | null,
  endDate: null as Date | null
})

// Quick month selection
const selectedQuickMonth = ref('current')
const quickMonths = [
  { label: 'Current Month', value: 'current' },
  { label: 'Last Month', value: 'last' },
  { label: 'Last 3 Months', value: 'last3' },
  { label: 'Year to Date', value: 'ytd' },
  { label: 'All Time', value: 'all' }
]

// Details dialog
const showDetailsDialog = ref(false)
const selectedAttendance = ref<any>(null)

// Computed properties
const isDateRangeValid = computed(() => {
  return dateRange.value.startDate && dateRange.value.endDate
})

const dateRangeDisplay = computed(() => {
  if (dateRange.value.startDate && dateRange.value.endDate) {
    const start = formatDateForDisplay(dateRange.value.startDate)
    const end = formatDateForDisplay(dateRange.value.endDate)
    return `${start} - ${end}`
  }
  return 'Select date range'
})

// Helper Functions
const formatDateForDisplay = (date: Date) => {
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatMinutes = (minutes: number) => {
  if (!minutes || minutes === 0) return '0 min'
  const hrs = Math.floor(minutes / 60)
  const mins = minutes % 60
  return hrs > 0 ? `${hrs}h ${mins}m` : `${mins}m`
}

const formatShiftTime = (timeString: string) => {
  if (!timeString) return '—'
  const date = new Date(timeString)
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const getAttendanceSeverity = (status: string) => {
  const map: Record<string, string> = {
    'present': 'success',
    'late': 'warning',
    'absent': 'danger',
    'half_day': 'warn',
    'on_leave': 'info',
    'holiday': 'secondary'
  }
  return map[status?.toLowerCase()] || 'secondary'
}

const canEditAttendance = (attendance: any) => {
  if (!attendance) return false
  // Add your edit permission logic here
  // For example, only allow editing if not clocked out or if user has admin role
  return true // or implement your logic
}

// Date calculation functions
const getDateRangeFromQuickMonth = (type: string) => {
  const now = new Date()
  const start = new Date()
  const end = new Date()

  switch(type) {
    case 'current':
      start.setDate(1)
      end.setMonth(now.getMonth() + 1, 0)
      break
    case 'last':
      start.setMonth(now.getMonth() - 1, 1)
      end.setMonth(now.getMonth(), 0)
      break
    case 'last3':
      start.setMonth(now.getMonth() - 3, 1)
      end.setDate(now.getDate())
      break
    case 'ytd':
      start.setMonth(0, 1)
      end.setDate(now.getDate())
      break
    case 'all':
      return { startDate: null, endDate: null }
  }

  return {
    startDate: start,
    endDate: end
  }
}

// API Functions
const fetchAttendanceData = async () => {
  if (!props.employeeId) return
  
  loading.value = true
  try {
    const params: any = { 
      employee_id: props.employeeId 
    }

    // Add date filters
    if (dateRange.value.startDate) {
      params.start_date = formatDateForAPI(dateRange.value.startDate)
    }
    if (dateRange.value.endDate) {
      params.end_date = formatDateForAPI(dateRange.value.endDate)
    }

    const response = await axios.get('/api/attendance/by-employee-number', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      },
      params: params
    })

    if (response.data.success) {
      // Update employee info
      employeeInfo.value = response.data.data.employee
      
      // Update summary
      summary.value = response.data.data.summary
      
      // Update attendance history
      attendanceHistory.value = response.data.data.attendances || []
      
      // Update date range display
      if (response.data.data.date_range) {
        dateRange.value.startDate = response.data.data.date_range.start_date 
          ? new Date(response.data.data.date_range.start_date) 
          : null
        dateRange.value.endDate = response.data.data.date_range.end_date 
          ? new Date(response.data.data.date_range.end_date) 
          : null
      }
      
      emit('update:attendance', response.data.data)
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to fetch attendance data',
      life: 3000
    })
    console.error('Attendance fetch error:', err)
  } finally {
    loading.value = false
  }
}

// Helper to format date for API
const formatDateForAPI = (date: Date) => {
  return date.toISOString().split('T')[0]
}

// Date filter handlers
const handleDateSelect = () => {
  // Auto-apply if both dates are selected
  if (dateRange.value.startDate && dateRange.value.endDate) {
    applyDateFilter()
  }
}

const applyDateFilter = () => {
  if (dateRange.value.startDate && dateRange.value.endDate) {
    selectedQuickMonth.value = ''
    fetchAttendanceData()
  } else {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please select both start and end dates',
      life: 3000
    })
  }
}

const resetDateFilter = () => {
  // Reset to current month
  const now = new Date()
  dateRange.value = {
    startDate: new Date(now.getFullYear(), now.getMonth(), 1),
    endDate: new Date(now.getFullYear(), now.getMonth() + 1, 0)
  }
  selectedQuickMonth.value = 'current'
  fetchAttendanceData()
}

const selectQuickMonth = (type: string) => {
  selectedQuickMonth.value = type
  const range = getDateRangeFromQuickMonth(type)
  dateRange.value.startDate = range.startDate
  dateRange.value.endDate = range.endDate
  
  if (type !== 'all' || (range.startDate && range.endDate)) {
    fetchAttendanceData()
  } else {
    // For 'all' type, fetch without date filters
    dateRange.value.startDate = null
    dateRange.value.endDate = null
    fetchAttendanceData()
  }
}

// Export function
const exportAttendance = () => {
  if (!props.employeeId) return
  
  const params = new URLSearchParams()
  params.append('employee_id', props.employeeId.toString())
  
  if (dateRange.value.startDate) {
    params.append('start_date', formatDateForAPI(dateRange.value.startDate))
  }
  if (dateRange.value.endDate) {
    params.append('end_date', formatDateForAPI(dateRange.value.endDate))
  }
  
  const url = `/api/attendances/export?${params.toString()}`
  window.open(url, '_blank')
  emit('export', { 
    employeeId: props.employeeId,
    startDate: dateRange.value.startDate,
    endDate: dateRange.value.endDate
  })
}

// View details
const viewAttendanceDetails = (attendance: any) => {
  selectedAttendance.value = attendance
  showDetailsDialog.value = true
}

const editAttendance = (attendance: any) => {
  showDetailsDialog.value = false
  emit('edit', attendance)
}

// Expose methods for parent component
defineExpose({
  refresh: fetchAttendanceData,
  dateRange,
  selectedQuickMonth,
  employeeInfo,
  summary
})

// Watchers
watch(() => props.employeeId, () => {
  if (props.employeeId) {
    resetDateFilter()
  }
})

// Initialize
onMounted(() => {
  // Set default date range to current month
  const now = new Date()
  dateRange.value.startDate = new Date(now.getFullYear(), now.getMonth(), 1)
  dateRange.value.endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  
  if (props.initialData) {
    // Handle initial data if provided
    if (props.initialData.attendances) {
      attendanceHistory.value = props.initialData.attendances
    }
    if (props.initialData.summary) {
      summary.value = props.initialData.summary
    }
    if (props.initialData.employee) {
      employeeInfo.value = props.initialData.employee
    }
  } else {
    fetchAttendanceData()
  }
})
</script>

<style scoped>
.p-datatable-sm :deep(.p-datatable-tbody > tr > td) {
  padding: 0.5rem 0.5rem;
}

:deep(.p-tag) {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}
</style>