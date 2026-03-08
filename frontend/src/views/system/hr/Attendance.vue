<template>
  <div class="space-y-6">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="stat in stats" :key="stat.label" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">{{ stat.label }}</div>
            <div class="text-2xl font-semibold" :class="stat.colorClass">{{ stat.value }}</div>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="stat.bgClass">
            <i :class="[stat.icon, stat.iconClass]"></i>
          </div>
        </div>
      </div>
    </div>
  
    <!-- Header with Search and Filters -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div class="flex flex-col sm:flex-row gap-4 w-full md:w-auto">
        <!-- Search Input -->
        <span class="p-input-icon-left w-full sm:w-64">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search employee..." class="w-full" size="small" />
          </IconField>
        </span>
  
        <!-- Status Filter -->
        <Select v-model="filters.status" :options="statusFilterOptions" optionLabel="label" size="small"
          optionValue="value" placeholder="All Status" showClear class="w-full sm:w-40">
          <template #option="slotProps">
            <Tag :value="slotProps.option.label" :severity="getStatusSeverity(slotProps.option.value)" />
          </template>
        </Select>
  
        <!-- Date Picker -->
        <DatePicker v-model="filters.dateRange" selectionMode="range" showIcon showClear size="small"
          placeholder="Date Range" class="w-full sm:w-60" dateFormat="M dd, yy" />
      </div>
  
      <!-- Clear Filters & Refresh -->
      <div class="flex gap-2">
        <Button v-if="hasActiveFilters" label="Clear" icon="pi pi-filter-slash" severity="secondary" text size="small"
          @click="clearFilters" />
        <Button icon="pi pi-refresh" severity="info" text size="small" @click="fetchAttendance" />
      </div>
    </div>
  
    <!-- Attendance Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <DataTable :value="attendanceData" class="w-full text-sm" :loading="loading" paginator :rows="10"
        :rowsPerPageOptions="[5, 10, 20, 50]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries" rowHover showGridlines
        responsiveLayout="scroll">
        <Column field="attendanceDateRaw" header="Date" sortable style="width: 120px">
          <template #body="{ data }">
            <div class="flex flex-col">
              <span class="font-medium">{{ data.attendanceDate }}</span>
              <span class="text-xs text-gray-400">{{ data.dayOfWeek }}</span>
            </div>
          </template>
        </Column>
  
        <Column field="employee.name" header="Employee" sortable style="min-width: 200px">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <Avatar :label="getInitials(data.employee.name)" size="small" shape="circle"
                class="bg-blue-100 text-blue-600" />
              <div>
                <p class="font-medium">{{ data.employee.name }}</p>
                <p class="text-xs text-gray-500">{{ data.employee.department }}</p>
              </div>
            </div>
          </template>
        </Column>
  
        <Column header="Check In" style="width: 140px">
          <template #body="{ data }">
            <div v-if="data.clockInRaw">
              <span>{{ data.clockInTime }}</span>
              <Badge v-if="data.isLate" value="Late" severity="danger" class="ml-2" />
            </div>
            <span v-else class="text-gray-400">--:--</span>
          </template>
        </Column>
  
        <Column header="Check Out" style="width: 140px">
          <template #body="{ data }">
            <span>{{ data.clockOut || '--:--' }}</span>
          </template>
        </Column>
  
        <Column header="Hours" style="width: 100px">
          <template #body="{ data }">
            <div class="flex flex-col">
              <span class="font-medium">{{ data.totalHours }}</span>
              <span v-if="data.overtimeMinutes > 0" class="text-xs text-orange-500">
                +{{ data.overtimeHours }} OT
              </span>
            </div>
          </template>
        </Column>
  
        <Column field="status" header="Status" sortable style="width: 120px">
          <template #body="{ data }">
            <Tag :value="formatStatus(data.status)" :severity="getStatusSeverity(data.status)" />
          </template>
        </Column>
  
        <Column header="Actions" :exportable="false" style="width: 100px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-eye" severity="info" text rounded size="small" @click="viewDetails(data)"
                v-tooltip.top="'View Details'" />
              <Button icon="pi pi-pencil" severity="contrast" text rounded size="small" @click="editAttendance(data)"
                v-tooltip.top="'Edit'" />
            </div>
          </template>
        </Column>
  
        <template #empty>
          <div class="text-center py-12">
            <i class="pi pi-calendar text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No attendance records found</p>
          </div>
        </template>
      </DataTable>
    </div>
  
    <!-- Edit Attendance Dialog -->
    <Dialog v-model:visible="showEditDialog" modal header="Edit Attendance" :style="{ width: '500px' }"
      :draggable="false">
      <div v-if="editingAttendance" class="space-y-4">
        <!-- Employee Info -->
        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
          <Avatar :label="getInitials(editingAttendance.employee?.name)" size="large" shape="circle"
            class="bg-blue-100 text-blue-600" />
          <div>
            <div class="font-medium">{{ editingAttendance.employee?.name }}</div>
            <div class="text-sm text-gray-500">{{ editingAttendance.employee?.department }}</div>
          </div>
        </div>
  
        <!-- Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
          <InputText v-model="editingAttendance.attendanceDate" disabled class="w-full" />
        </div>
  
        <!-- Time Inputs -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Clock In</label>
            <InputText v-model="editingAttendance.clockInTime" type="time" class="w-full" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Clock Out</label>
            <InputText v-model="editingAttendance.clockOutTime" type="time" class="w-full" />
          </div>
        </div>
  
        <!-- Break Minutes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Break (minutes)</label>
          <InputNumber v-model="editingAttendance.breakMinutes" :min="0" class="w-full" />
        </div>
  
        <!-- Status -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <Select v-model="editingAttendance.status" :options="statusOptions" optionLabel="label" optionValue="value"
            class="w-full">
            <template #option="slotProps">
              <Tag :value="slotProps.option.label" :severity="getStatusSeverity(slotProps.option.value)" />
            </template>
          </Select>
        </div>
  
        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
          <Textarea v-model="editingAttendance.notes" rows="2" class="w-full" placeholder="Optional notes..." />
        </div>
      </div>
  
      <template #footer>
        <Button label="Cancel" severity="secondary" @click="showEditDialog = false" />
        <Button label="Save Changes" severity="info" @click="saveAttendance" :loading="saving" />
      </template>
    </Dialog>
  
    <!-- View Details Dialog -->
    <Dialog v-model:visible="showDetailsDialog" modal
      :header="`Attendance Details - ${selectedAttendance?.employee?.name || ''}`" :style="{ width: '600px' }"
      :draggable="false">
      <div v-if="selectedAttendance" class="space-y-4">
        <!-- Employee Summary -->
        <div class="flex items-center gap-4 p-4 bg-blue-50 rounded-lg">
          <Avatar :label="getInitials(selectedAttendance.employee?.name)" size="xlarge" shape="circle"
            class="bg-blue-100 text-blue-600" />
          <div>
            <h3 class="text-lg font-semibold">{{ selectedAttendance.employee?.name }}</h3>
            <p class="text-sm text-gray-600">{{ selectedAttendance.employee?.department }}</p>
            <div class="flex gap-2 mt-2">
              <Tag :value="formatStatus(selectedAttendance.status)"
                :severity="getStatusSeverity(selectedAttendance.status)" />
              <Tag v-if="selectedAttendance.isLate" value="Late" severity="danger" />
            </div>
          </div>
        </div>
  
        <!-- Date and Time -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500 mb-1">Date</div>
            <div class="font-medium">{{ selectedAttendance.attendanceDate }}</div>
          </div>
          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500 mb-1">Day</div>
            <div class="font-medium">{{ selectedAttendance.dayOfWeek }}</div>
          </div>
        </div>
  
        <!-- Clock In/Out -->
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500 mb-1">Clock In</div>
            <div class="font-medium text-lg">{{ selectedAttendance.clockIn || '--:--' }}</div>
            <div v-if="selectedAttendance.isLate" class="text-xs text-red-500 mt-1">
              Late: {{ selectedAttendance.lateMinutes }} mins
            </div>
          </div>
          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500 mb-1">Clock Out</div>
            <div class="font-medium text-lg">{{ selectedAttendance.clockOut || '--:--' }}</div>
          </div>
        </div>
  
        <!-- Time Summary -->
        <div class="bg-gray-50 p-3 rounded-lg">
          <div class="text-xs text-gray-500 mb-2">Time Summary</div>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <div class="text-sm text-gray-600">Worked</div>
              <div class="font-semibold text-lg">{{ selectedAttendance.totalHours }} hrs</div>
            </div>
            <div>
              <div class="text-sm text-gray-600">Break</div>
              <div class="font-semibold">{{ selectedAttendance.breakMinutes }} mins</div>
            </div>
            <div>
              <div class="text-sm text-gray-600">Overtime</div>
              <div class="font-semibold text-orange-500">{{ selectedAttendance.overtimeHours }} hrs</div>
            </div>
          </div>
        </div>
  
        <!-- Shift Info -->
        <div v-if="selectedAttendance.shift" class="bg-gray-50 p-3 rounded-lg">
          <div class="text-xs text-gray-500 mb-2">Shift Details</div>
          <div class="flex items-center gap-2">
            <Tag :value="selectedAttendance.shift.name" severity="info" />
            <span class="text-sm">{{ selectedAttendance.shift.startTime }} - {{ selectedAttendance.shift.endTime }}</span>
          </div>
        </div>
  
        <!-- Notes -->
        <div v-if="selectedAttendance.notes" class="bg-yellow-50 p-3 rounded-lg border border-yellow-200">
          <div class="text-xs text-yellow-700 mb-1">Notes</div>
          <div class="text-sm">{{ selectedAttendance.notes }}</div>
        </div>
      </div>
  
      <template #footer>
        <Button label="Close" severity="secondary" @click="showDetailsDialog = false" />
        <Button label="Edit" icon="pi pi-pencil" severity="info" @click="editFromDetails" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import { useAuthStore } from '../../../stores/auth'

// Toast & Auth
const toast = useToast()
const authStore = useAuthStore()

// State
const loading = ref(false)
const saving = ref(false)
const showEditDialog = ref(false)
const showDetailsDialog = ref(false)
const attendanceData = ref<any[]>([])
const selectedAttendance = ref<any>(null)
const editingAttendance = ref<any>(null)

// Filters
const filters = reactive({
  search: '',
  status: null as string | null,
  dateRange: null as Date[] | null
})

// Status Options
const statusFilterOptions = [
  { label: 'Present', value: 'present' },
  { label: 'Late', value: 'late' },
  { label: 'Absent', value: 'absent' },
  { label: 'On Leave', value: 'on_leave' },
  { label: 'Half Day', value: 'half_day' }
]

const statusOptions = [
  { label: 'Present', value: 'present' },
  { label: 'Late', value: 'late' },
  { label: 'Absent', value: 'absent' },
  { label: 'On Leave', value: 'on_leave' },
  { label: 'Half Day', value: 'half_day' }
]

// Stats
const summary = ref({
  present: 0,
  late: 0,
  absent: 0,
  on_leave: 0,
  half_day: 0,
  total: 0
})

const stats = computed(() => [
  {
    label: 'Present',
    value: summary.value.present,
    icon: 'pi pi-check',
    colorClass: 'text-green-600',
    bgClass: 'bg-green-50',
    iconClass: 'text-green-500'
  },
  {
    label: 'Late',
    value: summary.value.late,
    icon: 'pi pi-clock',
    colorClass: 'text-orange-600',
    bgClass: 'bg-orange-50',
    iconClass: 'text-orange-500'
  },
  {
    label: 'Absent',
    value: summary.value.absent,
    icon: 'pi pi-times',
    colorClass: 'text-red-600',
    bgClass: 'bg-red-50',
    iconClass: 'text-red-500'
  },
  {
    label: 'On Leave',
    value: summary.value.on_leave,
    icon: 'pi pi-calendar',
    colorClass: 'text-blue-600',
    bgClass: 'bg-blue-50',
    iconClass: 'text-blue-500'
  }
])

const hasActiveFilters = computed(() => {
  return filters.search !== '' || filters.status !== null || filters.dateRange !== null
})

// Helper Functions
const getInitials = (name: string): string => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getStatusSeverity = (status: string): string => {
  const map: Record<string, string> = {
    'present': 'success',
    'absent': 'danger',
    'on_leave': 'info',
    'half_day': 'warning',
    'late': 'danger'
  }
  return map[status?.toLowerCase()] || 'secondary'
}

const formatStatus = (status: string): string => {
  const map: Record<string, string> = {
    'present': 'Present',
    'late': 'Late',
    'absent': 'Absent',
    'on_leave': 'On Leave',
    'half_day': 'Half Day',
    'holiday': 'Holiday'
  }
  return map[status] || status || 'Unknown'
}

const formatDate = (dateString: string): string => {
  if (!dateString) return '--'
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    })
  } catch {
    return dateString
  }
}


const formatTime = (dateTimeString: string | null): string => {
  if (!dateTimeString) return ''
  try {
    const date = new Date(dateTimeString)
    return date.toLocaleTimeString('en-US', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true,
      timeZone: 'Asia/Manila' // Add this
    })
  } catch {
    return ''
  }
}

const formatTime24 = (dateTimeString: string | null): string => {
  if (!dateTimeString) return ''
  try {
    const date = new Date(dateTimeString)
    // Convert to local time first
    const localDate = new Date(date.toLocaleString('en-US', { timeZone: 'Asia/Manila' }))
    return localDate.toTimeString().slice(0, 5)
  } catch {
    return ''
  }
}
const minutesToHours = (minutes: number): string => {
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`
}

const formatDayOfWeek = (dateString: string): string => {
  if (!dateString) return '--'
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', { weekday: 'short' })
  } catch {
    return '--'
  }
}

// Transform API data to UI format
const transformAttendance = (item: any): any => {
  const employee = item.employee || {}
  const shift = item.shift || {}

  return {
    id: item.id,
    attendanceDate: formatDate(item.attendance_date),
    attendanceDateRaw: item.attendance_date,
    dayOfWeek: formatDayOfWeek(item.attendance_date),
    employee: {
      id: employee.id,
      name: employee.fname && employee.lname
        ? `${employee.fname} ${employee.lname}`.trim()
        : 'Unknown',
      department: employee.department || 'N/A'
    },
    clockIn: formatTime(item.clock_in),
    clockInRaw: item.clock_in,
    clockInTime: formatTime(item.clock_in),
    clockOut: formatTime(item.clock_out),
    clockOutRaw: item.clock_out,
    clockOutTime: formatTime(item.clock_out),
    isLate: item.status === 'late',
    lateMinutes: item.late_minutes || 0,
    breakMinutes: item.break_minutes || 0,
    totalWorkedMinutes: item.total_worked_minutes || 0,
    totalHours: minutesToHours(item.total_worked_minutes || 0),
    overtimeMinutes: item.overtime_minutes || 0,
    overtimeHours: minutesToHours(item.overtime_minutes || 0),
    status: item.status,
    notes: item.notes || null,
    shift: shift.id ? {
      id: shift.id,
      name: shift.name || 'N/A',
      code: shift.code || '',
      startTime: shift.start_time || '',
      endTime: shift.end_time || ''
    } : null
  }
}

// Fetch attendance data
const fetchAttendance = async () => {
  loading.value = true

  try {
    const params: any = {}

    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    if (filters.dateRange && filters.dateRange[0] && filters.dateRange[1]) {
      params.start_date = filters.dateRange[0].toISOString().split('T')[0]
      params.end_date = filters.dateRange[1].toISOString().split('T')[0]
    }

    const response = await axios.get('api/attendances', {
      headers: { 'Authorization': `Bearer ${authStore.token}` },
      params
    })

    if (response.data.success) {
      const records = response.data.data.data || response.data.data || []
      attendanceData.value = records.map(transformAttendance)

      // Update summary
      if (response.data.summary) {
        summary.value = response.data.summary
      }
    }
  } catch (error: any) {
    console.error('Fetch attendance error:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to fetch attendance',
      life: 5000
    })
  } finally {
    loading.value = false
  }
}

// View details
const viewDetails = (data: any) => {
  selectedAttendance.value = data
  showDetailsDialog.value = true
}

// Edit from details
const editFromDetails = () => {
  if (selectedAttendance.value) {
    editAttendance(selectedAttendance.value)
    showDetailsDialog.value = false
  }
}

// Edit attendance
const editAttendance = (data: any) => {
  editingAttendance.value = {
    ...data,
    attendanceDate: data.attendanceDate
  }
  showEditDialog.value = true
}

// Save attendance
const saveAttendance = async () => {
  if (!editingAttendance.value) return

  saving.value = true

  try {
    const updateData: any = {
      status: editingAttendance.value.status,
      break_minutes: editingAttendance.value.breakMinutes,
      notes: editingAttendance.value.notes
    }

    // Handle clock_in time
    if (editingAttendance.value.clockInTime) {
      const date = new Date(editingAttendance.value.attendanceDateRaw)
      updateData.clock_in = `${date.toISOString().split('T')[0]} ${editingAttendance.value.clockInTime}:00`
    }

    // Handle clock_out time
    if (editingAttendance.value.clockOutTime) {
      const date = new Date(editingAttendance.value.attendanceDateRaw)
      updateData.clock_out = `${date.toISOString().split('T')[0]} ${editingAttendance.value.clockOutTime}:00`
    }

    const response = await axios.put(
      `api/attendances/${editingAttendance.value.id}`,
      updateData,
      { headers: { 'Authorization': `Bearer ${authStore.token}` } }
    )

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Attendance updated successfully',
        life: 3000
      })
      showEditDialog.value = false
      await fetchAttendance()
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update attendance',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

// Clear filters
const clearFilters = () => {
  filters.search = ''
  filters.status = null
  filters.dateRange = null
  fetchAttendance()
}

// Watch for filter changes
watch(
  [() => filters.search, () => filters.status, () => filters.dateRange],
  () => {
    const timeoutId = setTimeout(() => fetchAttendance(), 300)
    return () => clearTimeout(timeoutId)
  },
  { deep: true }
)

// Initial fetch
onMounted(() => {
  fetchAttendance()
})
</script>