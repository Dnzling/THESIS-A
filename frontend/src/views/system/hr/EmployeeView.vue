<!-- views/system/employees/EmployeeInformation.vue -->
<template>
  <div class="min-h-screen bg-slate-50/70">
    <div class="mx-auto max-w-7xl px-6 py-8">
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-96">
      <ProgressSpinner />
    </div>
  
    <!-- Error State -->
    <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 p-8 text-center">
      <i class="pi pi-exclamation-triangle text-4xl text-red-500 mb-3"></i>
      <h3 class="text-lg font-medium text-red-800 mb-2">Failed to Load Employee Data</h3>
      <p class="text-red-600 mb-4">{{ error }}</p>
      <Button label="Try Again" icon="pi pi-refresh" @click="fetchEmployeeData" severity="danger" />
    </div>
  
    <!-- Main Content -->
    <template v-else>
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <Button icon="pi pi-arrow-left" text rounded severity="secondary" @click="goBack" />
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-slate-400">Employee Profile</p>
            <h1 class="text-2xl font-semibold text-slate-900">Employee Information</h1>
            <p class="text-sm text-slate-500">Complete record, activity, and payroll summary in one view.</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button label="Edit" icon="pi pi-pencil" severity="info" outlined @click="editEmployee" />
          <Button label="Export" icon="pi pi-download" severity="secondary" outlined @click="exportData" />
        </div>
      </div>
  
      <!-- Employee Profile Summary Card -->
      <div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white/90 p-6 shadow-sm">
        <div class="flex flex-wrap items-start gap-6">
          <div class="relative">
            <Avatar :label="getInitials(employeeInfo.basic_info?.name)" size="xlarge"
              class="bg-blue-100 text-blue-600 text-2xl font-semibold" />
            <div class="absolute -bottom-1 -right-1 h-4 w-4 rounded-full border-2 border-white bg-emerald-500"></div>
          </div>

          <div class="flex-1">
            <div class="flex flex-wrap items-center gap-3">
              <h2 class="text-2xl font-semibold text-slate-900">{{ employeeInfo.basic_info?.name || '-' }}</h2>
              <Tag :value="employeeInfo.employment_details?.status || 'Active'"
                :severity="getStatusSeverity(employeeInfo.employment_details?.status)" rounded />
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-3 text-sm text-slate-500">
              <span>{{ employeeInfo.employment_details?.role || '-' }}</span>
              <span class="text-slate-300">|</span>
              <span>{{ employeeInfo.basic_info?.employee_number || '-' }}</span>
              <span class="text-slate-300">|</span>
              <span>{{ employeeInfo.employment_details?.department || '-' }}</span>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-4">
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Join Date</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ formatDate(employeeInfo.employment_details?.hire_date) }}</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Employment</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ employeeInfo.employment_details?.type || '-' }}</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Leave Balance</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ leaveBalance }} days</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Attendance Rate</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ attendanceRate }}%</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Tabs -->
      <div class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white/90 shadow-sm">
        <Tabs v-model:value="activeTab">
          <TabList class="px-6 pt-3">
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
