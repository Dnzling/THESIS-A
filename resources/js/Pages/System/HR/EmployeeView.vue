<!-- views/system/employees/EmployeeInformation.vue -->
<template>
  <div class="min-h-screen">
    <ConfirmDialog />
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
          <Button label="Change Role" icon="pi pi-id-card" severity="warning" outlined @click="openRoleDialog" />
          <Button label="Edit" icon="pi pi-pencil" severity="info" outlined @click="openEditDialog" />
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
              <span>{{ formatLabel(employeeInfo.employment_details?.role) }}</span>
              <span class="text-slate-300">|</span>
              <span>{{ employeeInfo.basic_info?.employee_number || '-' }}</span>
              <span class="text-slate-300">|</span>
              <span>{{ formatLabel(employeeInfo.employment_details?.department) }}</span>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-5">
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Join Date</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ formatDate(employeeInfo.employment_details?.hire_date) }}</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Employment</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ formatLabel(employeeInfo.employment_details?.type) }}</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Leave Balance</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ leaveBalance }} days</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Attendance Rate</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ attendanceRate }}%</div>
              </div>
              <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4">
                <div class="text-xs text-slate-500">Current Shift</div>
                <div class="mt-1 text-sm font-semibold text-slate-900">{{ employeeInfo.current_shift?.shift_name || '-' }}</div>
                <div v-if="employeeInfo.current_shift?.time_range" class="text-xs text-slate-500">
                  {{ employeeInfo.current_shift.time_range }}
                </div>
                <div v-if="employeeInfo.current_shift?.covers_days_label" class="text-xs text-slate-500">
                  {{ employeeInfo.current_shift.covers_days_label }}
                </div>
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
            <Tab value="overtime">
              <div class="flex items-center gap-2">
                <span>Overtime History</span>
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
            <Tab value="deductions">
              <div class="flex items-center gap-2">
                <span>Deductions</span>
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

            <TabPanel value="overtime">
              <EmployeeOvertimeTab :employee-id="employeeId" ref="overtimeTabRef" />
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

            <TabPanel value="deductions">
              <EmployeeDeductionsTab :deductions="employeeInfo.deductions" />
            </TabPanel>
          </TabPanels>
        </Tabs>
      </div>

      <Dialog v-model:visible="showPayslipDialog" header="Payslip Details" :style="{ width: '760px' }" modal>
        <div v-if="selectedPayslip" class="space-y-4 text-sm">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
              <p><span class="font-semibold text-slate-700">Employee:</span> {{ employeeInfo.basic_info?.name || '-' }}</p>
              <p><span class="font-semibold text-slate-700">Employee #:</span> {{ employeeInfo.basic_info?.employee_number || '-' }}</p>
              <p><span class="font-semibold text-slate-700">Pay Period:</span> {{ getPayPeriodLabel(selectedPayslip) }}</p>
              <p><span class="font-semibold text-slate-700">Status:</span> {{ String(selectedPayslip.status || '-').toUpperCase() }}</p>
              <p><span class="font-semibold text-slate-700">Payment Date:</span> {{ formatDate(selectedPayslip.payment_date || selectedPayslip.paid_at || null) }}</p>
              <p><span class="font-semibold text-slate-700">Payment Method:</span> {{ selectedPayslip.payment_method || '-' }}</p>
            </div>
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4">
              <p class="mb-2 font-semibold text-emerald-800">Earnings</p>
              <div class="space-y-1">
                <p class="flex justify-between"><span>Base Salary</span><span>{{ formatCurrency(selectedPayslip.base_salary) }}</span></p>
                <p class="flex justify-between"><span>Overtime</span><span>{{ formatCurrency(selectedPayslip.overtime_amount) }}</span></p>
                <p class="flex justify-between"><span>Bonuses</span><span>{{ formatCurrency(selectedPayslip.bonuses_total) }}</span></p>
                <p class="flex justify-between"><span>Allowances</span><span>{{ formatCurrency(selectedPayslip.allowances_total) }}</span></p>
                <p class="flex justify-between border-t border-emerald-300 pt-1 font-semibold">
                  <span>Gross Pay</span>
                  <span>{{ formatCurrency(calculateGrossPay(selectedPayslip)) }}</span>
                </p>
              </div>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4">
              <p class="mb-2 font-semibold text-rose-800">Deductions</p>
              <div v-if="getDeductionItems(selectedPayslip).length" class="space-y-1">
                <p v-for="item in getDeductionItems(selectedPayslip)" :key="`${selectedPayslip.id}-${item.id || item.name}`" class="flex justify-between">
                  <span>{{ item.name }}</span>
                  <span>-{{ formatCurrency(item.amount) }}</span>
                </p>
              </div>
              <p v-else class="text-slate-500">No itemized deductions</p>
              <p class="mt-1 flex justify-between"><span>Tax</span><span>-{{ formatCurrency(selectedPayslip.tax_amount) }}</span></p>
              <p class="flex justify-between border-t border-rose-300 pt-1 font-semibold">
                <span>Total Deductions</span>
                <span>-{{ formatCurrency(Number(selectedPayslip.deductions_total || 0) + Number(selectedPayslip.tax_amount || 0)) }}</span>
              </p>
            </div>
          </div>

          <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <p class="flex items-center justify-between text-base font-semibold text-blue-900">
              <span>Net Pay</span>
              <span>{{ formatCurrency(selectedPayslip.net_salary) }}</span>
            </p>
          </div>
        </div>

        <template #footer>
          <Button label="Close" text @click="showPayslipDialog = false" />
          <Button label="Download PDF" icon="pi pi-download" severity="secondary" @click="handleDownloadPayslip(selectedPayslip)" />
          <Button label="Print" icon="pi pi-print" severity="info" @click="handlePrintPayslip(selectedPayslip)" />
        </template>
      </Dialog>

      <Dialog v-model:visible="showRoleDialog" header="Change Employee Role" :style="{ width: '520px' }" modal>
        <div class="space-y-4">
          <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Changing a role updates access immediately. Make sure this is intentional.
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Current Role</label>
            <InputText :model-value="formatLabel(employeeInfo.employment_details?.role)" disabled class="w-full" />
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">New Role *</label>
            <Select v-model="selectedRoleId" :options="roleOptions" optionLabel="label" optionValue="value"
              placeholder="Select role" filter class="w-full" />
          </div>
        </div>

        <template #footer>
          <Button label="Cancel" text @click="showRoleDialog = false" />
          <Button label="Update Role" icon="pi pi-check" severity="warning"
            :loading="savingRole" :disabled="!selectedRoleId"
            @click="confirmRoleChange" />
        </template>
      </Dialog>

      <Dialog v-model:visible="showEditDialog" header="Edit Employee Profile" :style="{ width: '760px' }" modal>
        <div class="space-y-5">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Personal Details</p>
            <div class="grid gap-3 md:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">First Name</label>
                <InputText v-model="editForm.fname" class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Last Name</label>
                <InputText v-model="editForm.lname" class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Phone</label>
                <InputText v-model="editForm.phone" class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Address</label>
                <InputText v-model="editForm.address" class="w-full" />
              </div>
            </div>
          </div>

          <div class="border-t border-slate-200 pt-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Shift Change</p>
            <div class="grid gap-3 md:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Current Shift</label>
                <InputText :model-value="employeeInfo.current_shift?.shift_name || '-'" disabled class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">New Shift</label>
                <Select
                  v-model="editForm.shift_id"
                  :options="shiftOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select shift"
                  class="w-full"
                  showClear
                />
                <small v-if="selectedShiftOption?.daysLabel" class="text-slate-500">
                  Covers {{ selectedShiftOption.daysLabel }}
                </small>
              </div>
              <div class="space-y-2">
                <label class="text-sm font-semibold text-slate-700">Effective Date</label>
                <DatePicker v-model="editForm.shift_effective_date" class="w-full" />
              </div>
            </div>
            <div class="space-y-2 mt-3" v-if="isShiftChangePending">
              <label class="text-sm font-semibold text-slate-700">Reason for Shift Change</label>
              <Textarea v-model="editForm.shift_change_reason" rows="3" class="w-full" />
            </div>
            <Message v-if="isShiftChangePending" severity="info" :closable="false" class="mt-3">
              Employee will receive an email notification for this shift change.
            </Message>
          </div>
        </div>

        <template #footer>
          <Button label="Cancel" text @click="showEditDialog = false" />
          <Button label="Save Changes" icon="pi pi-save" severity="info" :loading="savingEdit" @click="submitEditEmployee" />
        </template>
      </Dialog>
    </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import hrService from '../../../services/hr.services'
import type { EmployeeDetails } from '../../../types/hr'
import ConfirmDialog from 'primevue/confirmdialog'

// Import tab components
import EmployeeInfoTab from './components/tabs/EmployeeInfoTab.vue'
import EmployeeAttendanceTab from './components/tabs/EmployeeAttendanceTab.vue'
import EmployeeLeaveTab from './components/tabs/EmployeeLeaveTab.vue'
import PayslipHistory from './components/tabs/EmployeePayrollTab.vue'
import EmployeeDeductionsTab from './components/tabs/EmployeeDeductionsTab.vue'
import EmployeeOvertimeTab from './components/tabs/EmployeeOvertimeTab.vue'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const employeeId = route.params.id as string

// Refs for child components
const attendanceTabRef = ref<InstanceType<typeof EmployeeAttendanceTab> | null>(null)
const overtimeTabRef = ref<InstanceType<typeof EmployeeOvertimeTab> | null>(null)
const leaveTabRef = ref<InstanceType<typeof EmployeeLeaveTab> | null>(null)
const payslipHistoryRef = ref<InstanceType<typeof PayslipHistory> | null>(null)

// Loading states
const loading = ref(false)
const error = ref('')
const showPayslipDialog = ref(false)
const selectedPayslip = ref<any | null>(null)
const showRoleDialog = ref(false)
const savingRole = ref(false)
const roleOptions = ref<{ label: string; value: number }[]>([])
const selectedRoleId = ref<number | null>(null)
const showEditDialog = ref(false)
const savingEdit = ref(false)
const shiftOptions = ref<{ label: string; value: number; daysLabel: string }[]>([])

// State
const activeTab = ref('info')
const employeeInfo = ref<EmployeeDetails | any>({
  basic_info: {},
  employment_details: {},
  contact_info: {},
  leave_info: {},
  attendance: {},
  payroll: {},
  deductions: {},
  quick_stats: {}
})

const editForm = ref({
  fname: '',
  lname: '',
  phone: '',
  address: '',
  shift_id: null as number | null,
  shift_effective_date: new Date(),
  shift_change_reason: '',
})

// Computed Properties
const leaveBalance = computed(() => {
  return employeeInfo.value.leave_info?.summary?.total_remaining || 0
})

const attendanceRate = computed(() => {
  return employeeInfo.value.quick_stats?.attendance_rate || 0
})

const currentRoleId = computed(() => {
  return employeeInfo.value?.employment_details?.role_id || null
})

const currentShiftId = computed(() => {
  return employeeInfo.value?.current_shift?.shift_id || null
})

const isShiftChangePending = computed(() => {
  return !!editForm.value.shift_id && Number(editForm.value.shift_id) !== Number(currentShiftId.value || 0)
})

const selectedShiftOption = computed(() => {
  return shiftOptions.value.find((option) => Number(option.value) === Number(editForm.value.shift_id || 0)) || null
})

// API Functions
const fetchEmployeeData = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await hrService.getEmployeeDetails(employeeId)

    if (response.success) {
      employeeInfo.value = response.data
      selectedRoleId.value = currentRoleId.value
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

const loadRoles = async () => {
  try {
    const response = await hrService.api.get('/api/store/roles/store-specific')
    const roles = response?.data?.data || response?.data || response || []
    roleOptions.value = roles.map((role: any) => ({
      label: role.display_name || role.name || `Role ${role.id}`,
      value: role.id
    }))
  } catch (err) {
    console.error('Failed to load roles', err)
  }
}

const loadShifts = async () => {
  try {
    const response = await hrService.getShifts({ per_page: 200 })
    const payload = response?.data?.data || response?.data || response || []
    shiftOptions.value = payload.map((shift: any) => ({
      label: `${shift.name} (${formatShiftTime(shift.start_time)} - ${formatShiftTime(shift.end_time)})`,
      value: Number(shift.id),
      daysLabel: formatShiftDays(shift.week_days),
    }))
  } catch (err) {
    console.error('Failed to load shifts', err)
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

const formatLabel = (value: string | null | undefined) => {
  if (!value) return '-'
  return value
    .toString()
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const formatCurrency = (value: number | string | null | undefined) => {
  const amount = typeof value === 'string' ? parseFloat(value) : Number(value || 0)
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number.isFinite(amount) ? amount : 0)
}

const formatShiftTime = (value: string | null | undefined) => {
  if (!value) return '-'
  const date = new Date(value)
  if (!Number.isNaN(date.getTime())) {
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true })
  }

  if (/^\d{2}:\d{2}/.test(value)) {
    const [hoursRaw, minutes] = value.split(':')
    const hours = Number(hoursRaw)
    if (!Number.isNaN(hours)) {
      const period = hours >= 12 ? 'PM' : 'AM'
      const normalized = ((hours + 11) % 12) + 1
      return `${String(normalized).padStart(2, '0')}:${minutes} ${period}`
    }
  }

  return value
}

const formatShiftDays = (days: string[] | string | null | undefined) => {
  let dayList: string[] = []
  if (Array.isArray(days)) {
    dayList = days
  } else if (typeof days === 'string' && days.trim()) {
    try {
      const parsed = JSON.parse(days)
      dayList = Array.isArray(parsed) ? parsed : []
    } catch {
      dayList = []
    }
  }

  if (!dayList.length) return 'No days set'
  return dayList
    .map((day) => day.charAt(0).toUpperCase() + day.slice(1))
    .join(', ')
}

const calculateGrossPay = (payslip: any) => {
  return Number(payslip?.base_salary || 0)
    + Number(payslip?.overtime_amount || 0)
    + Number(payslip?.bonuses_total || 0)
    + Number(payslip?.allowances_total || 0)
}

const getDeductionItems = (payslip: any) => {
  const deductionItems = Array.isArray(payslip?.items?.deductions) ? payslip.items.deductions : []
  return deductionItems
}

const getPayPeriodLabel = (payslip: any) => {
  const name = payslip?.pay_period?.name
  const start = payslip?.pay_period?.start_date
  const end = payslip?.pay_period?.end_date
  if (name) return name
  if (start && end) return `${formatDate(start)} - ${formatDate(end)}`
  return '-'
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

const openEditDialog = () => {
  editForm.value = {
    fname: employeeInfo.value?.basic_info?.first_name || '',
    lname: employeeInfo.value?.basic_info?.last_name || '',
    phone: employeeInfo.value?.contact_info?.phone || '',
    address: employeeInfo.value?.contact_info?.address || '',
    shift_id: employeeInfo.value?.current_shift?.shift_id || null,
    shift_effective_date: new Date(),
    shift_change_reason: '',
  }
  showEditDialog.value = true
}

const submitEditEmployee = async () => {
  if (isShiftChangePending.value && !editForm.value.shift_change_reason.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Reason Required',
      detail: 'Please provide a reason for changing the shift.',
      life: 2800,
    })
    return
  }

  savingEdit.value = true
  try {
    const payload: any = {
      fname: editForm.value.fname,
      lname: editForm.value.lname,
      phone: editForm.value.phone || null,
      address: editForm.value.address || null,
    }

    if (isShiftChangePending.value) {
      payload.shift_id = editForm.value.shift_id
      payload.shift_effective_date = new Date(editForm.value.shift_effective_date).toISOString().slice(0, 10)
      payload.shift_change_reason = editForm.value.shift_change_reason.trim()
    }

    const response = await hrService.api.put(`/api/employees/${employeeId}`, payload)
    if (response?.data?.success) {
      toast.add({
        severity: 'success',
        summary: 'Saved',
        detail: isShiftChangePending.value
          ? 'Employee details and shift updated. Email notification sent.'
          : 'Employee details updated successfully.',
        life: 2800,
      })
      showEditDialog.value = false
      await fetchEmployeeData()
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: err?.response?.data?.message || 'Unable to save employee changes.',
      life: 3000,
    })
  } finally {
    savingEdit.value = false
  }
}

const exportData = () => {
  window.open(`/api/employees/${employeeId}/export`, '_blank')
}

const openRoleDialog = () => {
  selectedRoleId.value = currentRoleId.value
  showRoleDialog.value = true
}

const confirmRoleChange = () => {
  if (!selectedRoleId.value || selectedRoleId.value === currentRoleId.value) {
    showRoleDialog.value = false
    return
  }

  confirm.require({
    header: 'Confirm Role Change',
    message: 'This will immediately update the employee access. Continue?',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Yes, update',
    rejectLabel: 'Cancel',
    accept: () => updateEmployeeRole()
  })
}

const updateEmployeeRole = async () => {
  if (!selectedRoleId.value) return

  savingRole.value = true
  try {
    const payload = { role_id: selectedRoleId.value }
    const response = await hrService.api.put(`/api/employees/${employeeId}`, payload)
    if (response?.data?.success) {
      toast.add({ severity: 'success', summary: 'Updated', detail: 'Role updated successfully', life: 2500 })
      showRoleDialog.value = false
      await fetchEmployeeData()
    } else {
      toast.add({ severity: 'warn', summary: 'Warning', detail: 'Role update returned no changes', life: 2500 })
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Failed',
      detail: err.response?.data?.message || 'Failed to update role',
      life: 3000
    })
  } finally {
    savingRole.value = false
  }
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

const handleViewPayslip = async (payslip: any) => {
  try {
    const response = await hrService.api.get(`/api/payrolls/${payslip.id}/payslip`)
    selectedPayslip.value = {
      ...payslip,
      ...(response?.data?.payslip || {}),
    }
  } catch {
    selectedPayslip.value = payslip
  }

  showPayslipDialog.value = true
}

const handleDownloadPayslip = async (payslip: any) => {
  if (!payslip?.id) return

  try {
    const response = await hrService.api.get(`/api/payrolls/${payslip.id}/payslip/pdf`, {
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const periodLabel = String(getPayPeriodLabel(payslip)).replace(/\s+/g, '_')
    link.setAttribute('download', `payslip_${periodLabel || payslip.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Download Failed',
      detail: 'Unable to download payslip PDF.',
      life: 2500,
    })
  }
}

const handlePrintPayslip = async (payslip: any) => {
  if (!payslip?.id) return

  try {
    const response = await hrService.api.get(`/api/payrolls/${payslip.id}/payslip/print`, {
      responseType: 'blob'
    })
    const file = new Blob([response.data], { type: 'application/pdf' })
    const fileURL = window.URL.createObjectURL(file)
    window.open(fileURL, '_blank')
    setTimeout(() => window.URL.revokeObjectURL(fileURL), 60_000)
  } catch {
    toast.add({
      severity: 'error',
      summary: 'Print Failed',
      detail: 'Unable to open payslip print preview.',
      life: 2500,
    })
  }
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
  } else if (newTab === 'overtime' && overtimeTabRef.value) {
    overtimeTabRef.value.refresh()
  } else if (newTab === 'leave' && leaveTabRef.value) {
    leaveTabRef.value.refresh()
  } else if (newTab === 'payslip' && payslipHistoryRef.value) {
    payslipHistoryRef.value.refresh()
  }
})

// Lifecycle
onMounted(() => {
  fetchEmployeeData()
  loadRoles()
  loadShifts()
})
</script>
