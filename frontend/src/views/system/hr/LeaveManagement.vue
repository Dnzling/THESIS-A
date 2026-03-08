<!-- views/system/LeaveManagement.vue -->
<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Stats Row - Modern cards -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500 mb-1">Pending</div>
            <div class="text-2xl font-semibold text-blue-600">{{ pendingCount }}</div>
          </div>
          <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-clock text-blue-500"></i>
          </div>
        </div>
      </div>
  
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500 mb-1">On Leave Today</div>
            <div class="text-2xl font-semibold text-blue-600">{{ onLeaveToday }}</div>
          </div>
          <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-users text-blue-500"></i>
          </div>
        </div>
      </div>
  
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 hover:shadow-md transition-shadow">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500 mb-1">Approved This Month</div>
            <div class="text-2xl font-semibold text-blue-600">{{ approvedThisMonth }}</div>
          </div>
          <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-check-circle text-blue-500"></i>
          </div>
        </div>
      </div>
    </div>
  
    <!-- Main Content: 2 columns -->
    <div class="grid grid-cols-3 gap-6">
      <!-- Left Column: Pending Requests (2/3 width) -->
      <div class="col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center justify-between">
              <h2 class="font-semibold text-gray-700">Pending Requests</h2>
              <span class="bg-blue-100 text-blue-600 text-xs font-medium px-2 py-1 rounded-full">
                {{ pendingRequests.length }} pending
              </span>
            </div>
          </div>
  
          <div v-if="pendingRequests.length === 0" class="p-12 text-center">
            <i class="pi pi-check-circle text-4xl text-gray-300 mb-2"></i>
            <p class="text-gray-400">No pending requests</p>
          </div>
  
          <div v-else class="divide-y divide-gray-100">
            <div v-for="req in pendingRequests" :key="req.id" class="p-4 hover:bg-gray-50/50 transition-colors">
              <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                  <Avatar :label="getInitials(req.employeeName)" size="large"
                    class="bg-blue-100 text-blue-600 font-medium" />
                  <div>
                    <div class="font-medium text-gray-800">{{ req.employeeName }}</div>
                    <div class="text-xs text-gray-500">{{ req.department }}</div>
                  </div>
                </div>
                <Tag :value="req.leaveType" severity="info" rounded />
              </div>
  
              <div class="flex items-center gap-4 text-sm mb-3 ml-14">
                <div class="flex items-center gap-1 text-gray-600">
                  <i class="pi pi-calendar text-gray-400"></i>
                  <span>{{ formatDateRange(req.startDate, req.endDate) }}</span>
                </div>
                <div class="flex items-center gap-1 text-gray-600">
                  <i class="pi pi-clock text-gray-400"></i>
                  <span>{{ req.duration }} days</span>
                </div>
              </div>
  
              <div class="ml-14">
                <Button label="View Details" icon="pi pi-eye" size="small" severity="info" outlined
                  @click="viewRequest(req)" />
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Right Column: Side Info (1/3 width) -->
      <div class="space-y-6">
        <!-- On Leave Today -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <h2 class="font-semibold text-gray-700">On Leave Today</h2>
          </div>
          <div class="p-4">
            <div v-if="todayLeaves.length === 0" class="text-center py-6">
              <i class="pi pi-sun text-3xl text-gray-300 mb-2"></i>
              <p class="text-sm text-gray-400">No one on leave</p>
            </div>
            <div v-else class="space-y-3">
              <div v-for="leave in todayLeaves" :key="leave.id" class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                <Avatar :label="getInitials(leave.employeeName)" size="normal" class="bg-blue-100 text-blue-600" />
                <div class="flex-1 min-w-0">
                  <div class="text-sm font-medium text-gray-800 truncate">{{ leave.employeeName }}</div>
                  <div class="text-xs text-gray-500">{{ leave.department }}</div>
                </div>
                <Tag :value="leave.leaveType" severity="info" size="small" rounded />
              </div>
            </div>
          </div>
        </div>
  
        <!-- Leave Balances (Lowest remaining) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <h2 class="font-semibold text-gray-700">Low Leave Balance</h2>
          </div>
          <div class="p-4 space-y-4">
            <div v-for="emp in lowBalanceEmployees" :key="emp.id" class="space-y-2">
              <div class="flex items-center justify-between text-sm">
                <div class="flex items-center gap-2">
                  <Avatar :label="getInitials(emp.name)" size="normal" class="bg-blue-100 text-blue-600" />
                  <span class="font-medium text-gray-700">{{ emp.name }}</span>
                </div>
                <span class="text-blue-600 font-medium">{{ emp.remaining }}/{{ emp.total }} days</span>
              </div>
              <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="bg-blue-500 h-1.5 rounded-full transition-all" :style="{ width: emp.usedPercentage + '%' }">
                </div>
              </div>
            </div>
  
            <Button label="View All Balances" link size="small" class="w-full mt-2 text-blue-600"
              @click="viewAllBalances" />
          </div>
        </div>
  
        <!-- Modern Calendar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <div class="flex items-center justify-between">
              <h2 class="font-semibold text-gray-700">Calendar</h2>
              <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                <Button icon="pi pi-chevron-left" text size="small" @click="previousMonth" class="p-1 w-6 h-6" />
                <span class="text-sm font-medium px-2">{{ currentMonthLabel }}</span>
                <Button icon="pi pi-chevron-right" text size="small" @click="nextMonth" class="p-1 w-6 h-6" />
              </div>
            </div>
          </div>
          <div class="p-4">
            <!-- Weekday headers -->
            <div class="grid grid-cols-7 gap-1 mb-2">
              <div v-for="day in weekDays" :key="day" class="text-center text-xs font-medium text-gray-500 py-1">
                {{ day }}
              </div>
            </div>
  
            <!-- Calendar days -->
            <div class="grid grid-cols-7 gap-1">
              <div v-for="(day, index) in calendarDays" :key="index" class="relative group"
                @mouseenter="showDayTooltip(day)" @mouseleave="hideTooltip" @click="viewDayDetails(day)">
                <div
                  class="aspect-square flex items-center justify-center text-sm rounded-lg cursor-pointer transition-all"
                  :class="[
                                             day.isCurrentMonth ? 'text-gray-700' : 'text-gray-300',
                                             day.hasLeave ? 'bg-blue-50 text-blue-600 font-medium hover:bg-blue-100' : 'hover:bg-gray-50'
                                           ]">
                  {{ day.date.getDate() }}
                </div>
  
                <!-- Modern Tooltip -->
                <div v-if="activeTooltip === index && day.hasLeave"
                  class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 z-10 min-w-50">
                  <div class="bg-white rounded-lg shadow-lg border border-gray-100 p-2 text-xs">
                    <div class="font-medium text-gray-700 mb-1 px-2">On Leave</div>
                    <div v-for="leave in day.leaves" :key="leave.id"
                      class="flex items-center justify-between gap-2 px-2 py-1 hover:bg-gray-50 rounded">
                      <span class="font-medium">{{ leave.employeeName }}</span>
                      <Tag :value="leave.leaveType" severity="info" size="small" rounded />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  
    <!-- Modern View Request Dialog -->
    <Dialog v-model:visible="showViewDialog" modal :style="{ width: '480px' }" class="rounded-xl" :closable="true">
      <template #header>
        <div class="flex items-center gap-2 text-gray-700">
          <i class="pi pi-file-text text-blue-500"></i>
          <span class="font-semibold">Leave Request Details</span>
        </div>
      </template>
  
      <div v-if="selectedRequest" class="space-y-5">
        <!-- Employee Info -->
        <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
          <Avatar :label="getInitials(selectedRequest.employeeName)" size="xlarge"
            class="bg-blue-100 text-blue-600 font-medium text-xl" />
          <div>
            <div class="font-semibold text-lg text-gray-800">{{ selectedRequest.employeeName }}</div>
            <div class="text-sm text-gray-500">{{ selectedRequest.department }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ selectedRequest.employeeId }}</div>
          </div>
        </div>
  
        <!-- Leave Details Grid -->
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500 mb-1">Leave Type</div>
            <Tag :value="selectedRequest.leaveType" severity="info" rounded />
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500 mb-1">Duration</div>
            <div class="font-medium text-gray-800">{{ selectedRequest.duration }} days</div>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500 mb-1">Start Date</div>
            <div class="font-medium text-gray-800">{{ formatDate(selectedRequest.startDate) }}</div>
          </div>
          <div class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500 mb-1">End Date</div>
            <div class="font-medium text-gray-800">{{ formatDate(selectedRequest.endDate) }}</div>
          </div>
        </div>
  
        <!-- Reason -->
        <div class="bg-gray-50 rounded-lg p-3">
          <div class="text-xs text-gray-500 mb-2">Reason</div>
          <div class="text-sm text-gray-700">{{ selectedRequest.reason }}</div>
        </div>
  
        <!-- Submitted Date -->
        <div class="flex items-center gap-2 text-xs text-gray-400">
          <i class="pi pi-clock"></i>
          <span>Submitted {{ formatDateTime(selectedRequest.submittedDate) }}</span>
        </div>
      </div>
  
      <template #footer>
        <div class="flex gap-2 justify-end">
          <Button label="Reject" icon="pi pi-times" severity="danger" outlined @click="openRejectDialog" class="px-4" />
          <Button label="Approve" icon="pi pi-check" severity="info" @click="approveRequest" class="px-4" />
        </div>
      </template>
    </Dialog>
  
    <!-- Modern Reject Dialog -->
    <Dialog v-model:visible="showRejectDialog" modal :style="{ width: '380px' }" class="rounded-xl" :closable="false">
      <template #header>
        <div class="flex items-center gap-2 text-red-600">
          <i class="pi pi-exclamation-triangle"></i>
          <span class="font-semibold">Reject Request</span>
        </div>
      </template>
  
      <div class="space-y-4">
        <div class="flex items-center gap-3 p-3 bg-red-50 rounded-lg">
          <i class="pi pi-info-circle text-red-400"></i>
          <p class="text-sm text-red-700">
            Rejecting leave for <span class="font-medium">{{ selectedRequest?.employeeName }}</span>
          </p>
        </div>
  
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-2">Reason for rejection *</label>
          <Textarea v-model="rejectReason" rows="3" class="w-full" placeholder="Please provide a reason..." />
        </div>
      </div>
  
      <template #footer>
        <div class="flex gap-2 justify-end">
          <Button label="Cancel" severity="secondary" text @click="showRejectDialog = false" class="px-4" />
          <Button label="Reject Request" severity="danger" @click="rejectRequest" class="px-4" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import { useAuthStore } from '../../../stores/auth'


// Types
interface LeaveRequest {
  id: number
  employee_id: number
  employeeName: string
  department: string
  employeeId: string
  leave_type: string
  leaveType: string
  start_date: string
  startDate: string
  end_date: string
  endDate: string
  total_days: number
  duration: number
  reason: string
  status: string
  attachment_path: string | null
  is_paid: boolean
  approved_by: number | null
  approved_at: string | null
  rejected_reason: string | null
  created_at: string
  submittedDate: string
  handover_notes: {
    tasks: string[]
    documents: string[]
  } | null
  handover_to: any
  employee: {
    id: number
    fname: string
    lname: string
    employee_number: string
    department: string
  }
  approver: {
    id: number
    fname: string
    lname: string
    full_name: string
  } | null
}

interface LeaveBalance {
  quota: number
  used: number
  remaining: number
  pending: number
}

interface Employee {
  id: number
  fname: string
  lname: string
  name: string
  department: string
  employee_number: string
  leaveBalance: number
  leave_balances?: Record<string, LeaveBalance>
}

interface LeaveCounts {
  pending: number
  approved: number
  rejected: number
  cancelled: number
  total: number
}

const router = useRouter()
const toast = useToast()
const weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']

// State
const loading = ref(false)
const showViewDialog = ref(false)
const showRejectDialog = ref(false)
const selectedRequest = ref<LeaveRequest | null>(null)
const rejectReason = ref('')
const currentMonth = ref(new Date())
const activeTooltip = ref<number | null>(null)
const storeId = ref<number | null>(null)
const authStore = useAuthStore()

// Data from API
const leaveRequests = ref<LeaveRequest[]>([])
const employees = ref<Employee[]>([])
const leaveCounts = ref<LeaveCounts>({
  pending: 0,
  approved: 0,
  rejected: 0,
  cancelled: 0,
  total: 0
})

// Set authorization header
axios.defaults.headers.common['Authorization'] = `Bearer ${authStore.token}`

// Helper: Get initials from name
const getInitials = (name: string): string => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

// Helper: Format date
const formatDate = (date: string | null): string => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    })
  } catch {
    return date
  }
}

// Helper: Format date and time
const formatDateTime = (date: string | null): string => {
  if (!date) return 'N/A'
  try {
    return new Date(date).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return date
  }
}

// Helper: Format date range
const formatDateRange = (start: string | null, end: string | null): string => {
  if (!start || !end) return ''
  const startDate = new Date(start)
  const endDate = new Date(end)

  if (start === end) {
    return startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
  }

  if (startDate.getMonth() === endDate.getMonth()) {
    return `${startDate.getDate()}-${endDate.getDate()} ${endDate.toLocaleDateString('en-US', { month: 'short' })}`
  }

  return `${startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`
}

// Helper: Get status severity
const getStatusSeverity = (status: string | null): string => {
  const map: Record<string, string> = {
    'pending': 'warning',
    'approved': 'success',
    'rejected': 'danger',
    'cancelled': 'secondary'
  }
  return map[status?.toLowerCase()] || 'info'
}

// Helper: Transform API data to UI format
const transformLeaveData = (records: any[]): LeaveRequest[] => {
  return records.map((item: any) => {
    const employee = item.employee || {}
    const approver = item.approver
    const handoverTo = item.handover_to

    return {
      // Original fields
      id: item.id,
      employee_id: item.employee_id,
      leave_type: item.leave_type,
      start_date: item.start_date,
      end_date: item.end_date,
      total_days: item.total_days,
      reason: item.reason,
      status: item.status,
      attachment_path: item.attachment_path,
      is_paid: item.is_paid,
      approved_by: item.approved_by,
      approved_at: item.approved_at,
      rejected_reason: item.rejected_reason,
      created_at: item.created_at,
      handover_notes: item.handover_notes,
      handover_to: handoverTo,

      // Transformed fields for UI
      employeeName: employee.fname && employee.lname
        ? `${employee.fname} ${employee.lname}`.trim()
        : 'Unknown',
      department: employee.department || 'N/A',
      employeeId: employee.employee_number || `#${employee.id}`,
      leaveType: item.leave_type
        ? item.leave_type.charAt(0).toUpperCase() + item.leave_type.slice(1).replace(/_/g, ' ')
        : 'Unknown',
      startDate: item.start_date,
      endDate: item.end_date,
      duration: item.total_days || 0,
      submittedDate: item.created_at,

      // Employee object for reference
      employee: employee,

      // Approver info
      approver: approver ? {
        id: approver.id,
        fname: approver.fname,
        lname: approver.lname,
        full_name: approver.full_name || `${approver.fname} ${approver.lname}`.trim()
      } : null
    }
  })
}

// API Functions
const fetchLeaves = async () => {
  loading.value = true
  try {
    const response = await axios.get('api/leaves')

    if (response.data.success) {
      // Get the paginated data array
      const records = response.data.data.data || response.data.data || []

      // Transform API data to match UI format
      leaveRequests.value = transformLeaveData(records)

      // Update counts if provided by API
      if (response.data.counts) {
        leaveCounts.value = response.data.counts
      } else {
        // Calculate counts from data
        const counts: LeaveCounts = {
          pending: 0,
          approved: 0,
          rejected: 0,
          cancelled: 0,
          total: records.length
        }
        records.forEach((item: any) => {
          const status = item.status?.toLowerCase()
          if (status === 'pending') counts.pending++
          else if (status === 'approved') counts.approved++
          else if (status === 'rejected') counts.rejected++
          else if (status === 'cancelled') counts.cancelled++
        })
        leaveCounts.value = counts
      }

      // Store store_id for future requests
      if (response.data.store_id) {
        storeId.value = response.data.store_id
      }
    }
  } catch (error: any) {
    console.error('Error fetching leaves:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load leave requests',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchEmployees = async () => {
  try {
    const response = await axios.get('api/employees')
    if (response.data.success) {
      const records = response.data.data.data || response.data.data || []
      employees.value = records.map((emp: any) => ({
        id: emp.id,
        fname: emp.fname,
        lname: emp.lname,
        name: `${emp.fname} ${emp.lname}`.trim(),
        department: emp.department || 'N/A',
        employee_number: emp.employee_number || '',
        leaveBalance: 15 // Default or from API
      }))
    }
  } catch (error) {
    console.error('Error fetching employees:', error)
  }
}

const fetchLeaveBalances = async (employeeId?: number) => {
  try {
    const params: any = {}
    if (employeeId) {
      params.employee_id = employeeId
    }
    params.year = new Date().getFullYear()

    const response = await axios.get('/api/leaves/balance', { params })
    if (response.data.success) {
      const balanceData = response.data.data
      const employee = employees.value.find(e => e.id === balanceData.employee_id)
      if (employee) {
        employee.leave_balances = balanceData.balance
        const totalRemaining = Object.values(balanceData.balance).reduce(
          (sum: number, type: any) => sum + (type.remaining || 0), 0
        )
        employee.leaveBalance = totalRemaining
      }
    }
  } catch (error) {
    console.error('Error fetching leave balances:', error)
  }
}

const approveLeave = async (id: number) => {
  try {
    const response = await axios.post(`/api/leaves/${id}/approve`, {
      notes: 'Approved via UI'
    })

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Leave request approved successfully',
        life: 3000
      })

      // Update local data
      const index = leaveRequests.value.findIndex(r => r.id === id)
      if (index !== -1) {
        leaveRequests.value[index].status = 'approved'
        leaveRequests.value[index].approved_at = new Date().toISOString()
      }

      // Update counts
      leaveCounts.value.pending--
      leaveCounts.value.approved++

      showViewDialog.value = false
      selectedRequest.value = null
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to approve leave',
      life: 3000
    })
  }
}

const rejectLeave = async (id: number, reason: string) => {
  try {
    const response = await axios.post(`/api/leaves/${id}/reject`, {
      reason: reason
    })

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Leave request rejected',
        life: 3000
      })

      // Update local data
      const index = leaveRequests.value.findIndex(r => r.id === id)
      if (index !== -1) {
        leaveRequests.value[index].status = 'rejected'
        leaveRequests.value[index].rejected_reason = reason
      }

      // Update counts
      leaveCounts.value.pending--
      leaveCounts.value.rejected++

      showRejectDialog.value = false
      showViewDialog.value = false
      selectedRequest.value = null
      rejectReason.value = ''
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to reject leave',
      life: 3000
    })
  }
}

const cancelLeave = async (id: number) => {
  try {
    const response = await axios.post(`/api/leaves/${id}/cancel`)

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Leave request cancelled',
        life: 3000
      })

      const index = leaveRequests.value.findIndex(r => r.id === id)
      if (index !== -1) {
        const oldStatus = leaveRequests.value[index].status
        leaveRequests.value[index].status = 'cancelled'

        if (oldStatus === 'pending') leaveCounts.value.pending--
        else if (oldStatus === 'approved') leaveCounts.value.approved--
        leaveCounts.value.cancelled++
      }
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to cancel leave',
      life: 3000
    })
  }
}

// Computed properties
const pendingRequests = computed(() =>
  leaveRequests.value.filter(r => r.status === 'pending')
)

const pendingCount = computed(() => pendingRequests.value.length)

const onLeaveToday = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const todayStr = today.toISOString()

  return leaveRequests.value.filter(r =>
    r.status === 'approved' && r.start_date <= todayStr && r.end_date >= todayStr
  ).length
})

const approvedThisMonth = computed(() => {
  const thisMonth = new Date().getMonth()
  const thisYear = new Date().getFullYear()

  return leaveRequests.value.filter(r => {
    if (r.status !== 'approved') return false
    const startDate = new Date(r.start_date)
    return startDate.getMonth() === thisMonth && startDate.getFullYear() === thisYear
  }).length
})

const todayLeaves = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const todayStr = today.toISOString()

  return leaveRequests.value
    .filter(r =>
      r.status === 'approved' && r.start_date <= todayStr && r.end_date >= todayStr
    )
    .map(r => ({
      id: r.id,
      employeeName: r.employeeName,
      department: r.department,
      leaveType: r.leaveType
    }))
})

const lowBalanceEmployees = computed(() => {
  return employees.value
    .map(emp => ({
      id: emp.id,
      name: emp.name,
      department: emp.department,
      total: 20,
      remaining: emp.leaveBalance,
      usedPercentage: ((20 - emp.leaveBalance) / 20) * 100
    }))
    .filter(emp => emp.remaining < 10)
    .sort((a, b) => a.remaining - b.remaining)
    .slice(0, 3)
})

const currentMonthLabel = computed(() =>
  currentMonth.value.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
)

const calendarDays = computed(() => {
  const year = currentMonth.value.getFullYear()
  const month = currentMonth.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)

  const days = []
  const startOffset = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1

  // Previous month days
  for (let i = startOffset; i > 0; i--) {
    days.push({
      date: new Date(year, month, -i + 1),
      isCurrentMonth: false,
      hasLeave: false,
      leaves: []
    })
  }

  // Current month days
  for (let i = 1; i <= lastDay.getDate(); i++) {
    const date = new Date(year, month, i)
    date.setHours(0, 0, 0, 0)

    // Find leaves on this day
    const leavesOnDay = leaveRequests.value.filter(l => {
      if (l.status !== 'approved') return false

      const startDate = new Date(l.start_date)
      startDate.setHours(0, 0, 0, 0)

      const endDate = new Date(l.end_date)
      endDate.setHours(23, 59, 59, 999)

      return date >= startDate && date <= endDate
    }).map(l => ({
      id: l.id,
      employeeName: l.employeeName,
      leaveType: l.leaveType
    }))

    days.push({
      date,
      isCurrentMonth: true,
      hasLeave: leavesOnDay.length > 0,
      leaves: leavesOnDay
    })
  }

  // Next month days to fill the grid
  const remainingDays = 42 - days.length // 6 rows x 7 days
  for (let i = 1; i <= remainingDays; i++) {
    days.push({
      date: new Date(year, month + 1, i),
      isCurrentMonth: false,
      hasLeave: false,
      leaves: []
    })
  }

  return days
})

// Actions
const viewRequest = (request: LeaveRequest) => {
  selectedRequest.value = request
  showViewDialog.value = true
}

const approveRequest = async () => {
  if (!selectedRequest.value) return
  await approveLeave(selectedRequest.value.id)
}

const openRejectDialog = () => {
  showViewDialog.value = false
  showRejectDialog.value = true
}

const rejectRequest = async () => {
  if (!rejectReason.value.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please provide a reason for rejection',
      life: 3000
    })
    return
  }

  if (!selectedRequest.value) return
  await rejectLeave(selectedRequest.value.id, rejectReason.value)
}

const previousMonth = () => {
  const date = new Date(currentMonth.value);
  date.setMonth(date.getMonth() - 1);
  currentMonth.value = date;
}

const nextMonth = () => {
  const date = new Date(currentMonth.value);
  date.setMonth(date.getMonth() + 1);
  currentMonth.value = date;
}

const showDayTooltip = (day: any) => {
  if (day.hasLeave) {
    activeTooltip.value = calendarDays.value.indexOf(day);
  }
}

const hideTooltip = () => {
  activeTooltip.value = null;
}

const viewDayDetails = (day: any) => {
  if (day.hasLeave) {
    console.log('Leaves on this day:', day.leaves);
  }
}

const viewAllBalances = () => {
  router.push({ name: 'hr.leave.balances' });
}

// Load data on mount 
onMounted(() => {
  fetchLeaves();
  fetchEmployees();
});

</script>