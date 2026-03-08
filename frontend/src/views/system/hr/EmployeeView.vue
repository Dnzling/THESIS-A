<!-- views/system/employees/EmployeeInformation.vue -->
<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-96">
      <ProgressSpinner />
    </div>
  
    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-8 text-center">
      <i class="pi pi-exclamation-triangle text-4xl text-red-500 mb-3"></i>
      <h3 class="text-lg font-medium text-red-800 mb-2">Failed to Load Employee Data</h3>
      <p class="text-red-600 mb-4">{{ error }}</p>
      <Button label="Try Again" icon="pi pi-refresh" @click="fetchEmployeeData" severity="danger" />
    </div>
  
    <!-- Main Content -->
    <template v-else>
      <!-- Header with back button and employee name -->
      <div class="flex justify-between items-center mb-6">
        <div class="flex items-center gap-4">
          <Button icon="pi pi-arrow-left" text rounded severity="info" @click="goBack" />
          <div>
            <h1 class="text-2xl font-semibold text-gray-800">Employee Information</h1>
            <p class="text-sm text-gray-500">View complete employee details and history</p>
          </div>
        </div>
        <div class="flex gap-2">
          <Button label="Edit" icon="pi pi-pencil" severity="info" outlined @click="editEmployee" />
          <Button label="Export" icon="pi pi-download" severity="secondary" outlined @click="exportData" />
        </div>
      </div>
  
      <!-- Employee Profile Summary Card -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
        <div class="flex items-start gap-6">
          <!-- Avatar with employee photo/initials -->
          <div class="relative">
            <Avatar :label="getInitials(employeeInfo.basic_info?.name)" size="xlarge"
              class="bg-blue-100 text-blue-600 text-2xl font-medium" />
            <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white"></div>
          </div>
  
          <!-- Basic Info -->
          <div class="flex-1">
            <div class="flex justify-between items-start">
              <div>
                <h2 class="text-2xl font-semibold text-gray-800">{{ employeeInfo.basic_info?.name }}</h2>
                <div class="flex items-center gap-3 mt-1">
                  <Tag :value="employeeInfo.employment_details?.status || 'Active'"
                    :severity="getStatusSeverity(employeeInfo.employment_details?.status)" rounded />
                  <span class="text-sm text-gray-500">{{ employeeInfo.employment_details?.role || '—' }}</span>
                  <span class="text-sm text-gray-400">•</span>
                  <span class="text-sm text-gray-500">{{ employeeInfo.basic_info?.employee_number }}</span>
                </div>
              </div>
            </div>
  
            <!-- Quick Info Grid -->
            <div class="grid grid-cols-4 gap-4 mt-4">
              <div class="border-r border-gray-100 pr-4">
                <div class="text-xs text-gray-500">Department</div>
                <div class="text-sm font-medium mt-1">{{ employeeInfo.employment_details?.department || '—' }}</div>
              </div>
              <div class="border-r border-gray-100 pr-4">
                <div class="text-xs text-gray-500">Join Date</div>
                <div class="text-sm font-medium mt-1">{{ formatDate(employeeInfo.employment_details?.hire_date) }}</div>
              </div>
              <div class="border-r border-gray-100 pr-4">
                <div class="text-xs text-gray-500">Leave Balance</div>
                <div class="text-sm font-medium mt-1">{{ leaveBalance }} days</div>
              </div>
              <div>
                <div class="text-xs text-gray-500">Attendance Rate</div>
                <div class="text-sm font-medium mt-1">{{ attendanceRate }}%</div>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Main Tabs -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <Tabs v-model:value="activeTab">
          <TabList class="px-6 pt-2">
            <Tab value="info">
              <div class="flex items-center gap-2">
                <span>Full Information</span>
              </div>
            </Tab>
            <Tab value="attendance">
              <div class="flex items-center gap-2">
                <span>Attendance History</span>
              </div>
            </Tab>
            <Tab value="leave">
              <div class="flex items-center gap-2">
                <span>Leave History</span>
              </div>
            </Tab>
            <Tab value="payslip">
              <div class="flex items-center gap-2">
                <span>Payslip History</span>
              </div>
            </Tab>
          </TabList>
  
          <TabPanels class="p-6">
            <!-- FULL INFORMATION TAB -->
            <TabPanel value="info">
              <EmployeeInfoTab :employee-info="employeeInfo" />
            </TabPanel>
  
            <!-- ATTENDANCE HISTORY TAB -->
            <TabPanel value="attendance">
              <EmployeeAttendanceTab 
                :employee-id="employeeId" 
                :initial-data="employeeInfo.attendance"
                @update:attendance="handleAttendanceUpdate"
                @export="handleAttendanceExport"
                ref="attendanceTabRef"
              />
            </TabPanel>
  
            <!-- LEAVE HISTORY TAB -->
            <TabPanel value="leave">
              <EmployeeLeaveTab 
                :employee-id="employeeId"
                :initial-data="employeeInfo.leave_info"
                @update:leave="handleLeaveUpdate"
                @view-details="handleViewLeaveDetails"
                ref="leaveTabRef"
              />
            </TabPanel>
  
            <!-- PAYSLIP TAB -->
            <TabPanel value="payslip">
              <PayslipHistory 
                v-if="employeeInfo?.basic_info?.id" 
                :employee-id="employeeInfo.basic_info?.id"
                :employee-name="employeeInfo?.basic_info?.name" 
                @view-payslip="handleViewPayslip"
                @download-payslip="handleDownloadPayslip" 
                @print-payslip="handlePrintPayslip"
                @generate-payslip="handleGeneratePayslip" 
                @export-all="handleExportAll" 
                ref="payslipHistoryRef" 
              />
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../stores/auth'

// Import tab components
import EmployeeInfoTab from './components/tabs/EmployeeInfoTab.vue'
import EmployeeAttendanceTab from './components/tabs/EmployeeAttendanceTab.vue'
import EmployeeLeaveTab from './components/tabs/EmployeeLeaveTab.vue'
import PayslipHistory from './components/tabs/EmployeePayrollTab.vue'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const toast = useToast()
const employeeId = route.params.id as string

// Refs for child components
const attendanceTabRef = ref<InstanceType<typeof EmployeeAttendanceTab> | null>(null)
const leaveTabRef = ref<InstanceType<typeof EmployeeLeaveTab> | null>(null)
const payslipHistoryRef = ref<InstanceType<typeof PayslipHistory> | null>(null)

// Loading states
const loading = ref(false)
const error = ref('')

// State
const activeTab = ref('info')
const employeeInfo = ref<any>({
  basic_info: {},
  employment_details: {},
  contact_info: {},
  leave_info: {},
  attendance: {},
  payroll: {},
  deductions: {},
  quick_stats: {}
})

// Computed Properties
const leaveBalance = computed(() => {
  return employeeInfo.value.leave_info?.summary?.total_remaining || 0
})

const attendanceRate = computed(() => {
  return employeeInfo.value.quick_stats?.attendance_rate || 0
})

// API Functions
const fetchEmployeeData = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await axios.get(`api/employees/${employeeId}/details`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      }
    })

    if (response.data.success) {
      employeeInfo.value = response.data.data
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Failed to fetch employee data'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.value,
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

// Helper functions
const getInitials = (name: string) => {
  if (!name) return ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

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

const getStatusSeverity = (status: string) => {
  const map: Record<string, string> = {
    'active': 'success',
    'on_leave': 'info',
    'suspended': 'warning',
    'terminated': 'danger'
  }
  return map[status?.toLowerCase()] || 'info'
}

// Navigation
const goBack = () => {
  router.push('/hr/employees')
}

const editEmployee = () => {
  router.push(`/hr/employees/${employeeId}/edit`)
}

const exportData = () => {
  window.open(`api/employees/${employeeId}/export`, '_blank')
}

// Event Handlers
const handleAttendanceUpdate = (data: any) => {
  employeeInfo.value.attendance = data
}

const handleAttendanceExport = (params: { month: number; year: number }) => {
  toast.add({
    severity: 'info',
    summary: 'Exporting',
    detail: `Exporting attendance for ${params.month}/${params.year}`,
    life: 2000
  })
}

const handleLeaveUpdate = (data: any) => {
  employeeInfo.value.leave_info = data
}

const handleViewLeaveDetails = (leave: any) => {
  console.log('Viewing leave details:', leave)
}

const handleViewPayslip = (payslip: any) => {
  console.log('Viewing payslip:', payslip)
}

const handleDownloadPayslip = (payslip: any) => {
  toast.add({
    severity: 'info',
    summary: 'Downloading',
    detail: `Downloading payslip for ${payslip.pay_period?.name}`,
    life: 2000
  })
}

const handlePrintPayslip = (payslip: any) => {
  console.log('Printing payslip:', payslip)
}

const handleGeneratePayslip = (year: number, month: number) => {
  toast.add({
    severity: 'info',
    summary: 'Generating',
    detail: `Generating payslip for ${month}/${year}`,
    life: 2000
  })

  setTimeout(() => {
    payslipHistoryRef.value?.refresh()
  }, 1000)
}

const handleExportAll = (year: number, month: number) => {
  console.log('Exporting all payslips for:', year, month)
}

// Watchers
watch(activeTab, (newTab) => {
  // Refresh data when switching to specific tabs
  if (newTab === 'attendance' && attendanceTabRef.value) {
    attendanceTabRef.value.refresh()
  } else if (newTab === 'leave' && leaveTabRef.value) {
    leaveTabRef.value.refresh()
  } else if (newTab === 'payslip' && payslipHistoryRef.value) {
    payslipHistoryRef.value.refresh()
  }
})

// Lifecycle
onMounted(() => {
  fetchEmployeeData()
})
</script>