<!-- views/system/employees/components/tabs/EmployeeLeaveTab.vue -->
<template>
  <div class="space-y-4 text-xs">
    <!-- Leave Balance Cards -->
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
      <div v-for="(balance, type) in formattedBalances" :key="type" class="rounded-xl border border-slate-200 bg-white p-3">
        <div class="text-xs text-gray-500 capitalize">{{ type }} Leave</div>
        <div class="flex items-end gap-1 mt-1">
          <span class="text-base font-semibold text-slate-900">{{ balance.used }}</span>
          <span class="text-xs text-gray-400">/ {{ balance.quota }}</span>
        </div>
        <div class="text-xs text-gray-500 mt-1">
          <span class="text-green-600">{{ balance.remaining }} remaining</span>
          <span v-if="balance.pending > 0" class="text-orange-600 ml-2">({{ balance.pending }} pending)</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-1.5 mt-2">
          <div class="bg-blue-500 h-1.5 rounded-full"
            :style="{ width: Math.min((balance.used / balance.quota) * 100, 100) + '%' }"></div>
        </div>
      </div>
    </div>

    <!-- Statistics Summary -->
    <div class="grid grid-cols-2 gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 sm:grid-cols-3 lg:grid-cols-5">
      <div class="text-center">
        <div class="text-xs text-gray-500">Total Leaves</div>
        <div class="text-base font-semibold">{{ statistics.total_leaves || 0 }}</div>
      </div>
      <div class="text-center">
        <div class="text-xs text-gray-500">Approved</div>
        <div class="text-base font-semibold text-green-600">{{ statistics.approved || 0 }}</div>
      </div>
      <div class="text-center">
        <div class="text-xs text-gray-500">Pending</div>
        <div class="text-base font-semibold text-orange-600">{{ statistics.pending || 0 }}</div>
      </div>
      <div class="text-center">
        <div class="text-xs text-gray-500">Rejected</div>
        <div class="text-base font-semibold text-red-600">{{ statistics.rejected || 0 }}</div>
      </div>
      <div class="text-center">
        <div class="text-xs text-gray-500">Total Days</div>
        <div class="text-base font-semibold text-blue-600">{{ statistics.total_days || 0 }}</div>
      </div>
    </div>

    <!-- Year Selector and Filters -->
    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
      <div class="flex flex-wrap items-center gap-2">
        <Select 
          v-model="selectedYear" 
          :options="yearOptions" 
          placeholder="Select Year" 
          size="small"
          class="w-full sm:w-28"
          @change="fetchLeaveData"
        />
        
        <IconField class="w-full sm:w-52">
          <InputIcon class="pi pi-search" />
          <InputText v-model="filters.search" placeholder="Search reason..." size="small" />
        </IconField>
        
        <Select 
          v-model="filters.status" 
          :options="statusOptions" 
          placeholder="Status" 
          size="small" 
          showClear
          class="w-full sm:w-32"
          @change="applyFilters"
        />
        
        <Select 
          v-model="filters.type" 
          :options="typeOptions" 
          placeholder="Leave Type" 
          size="small" 
          showClear
          class="w-full sm:w-36"
          @change="applyFilters"
        />
        
        <Button 
          icon="pi pi-refresh" 
          severity="secondary" 
          text 
          rounded 
          size="small"
          @click="resetFilters" 
          v-tooltip="'Reset Filters'"
        />
      </div>
    
    </div>

    <!-- Upcoming Leaves Banner (if any) -->
    <div v-if="upcomingLeaves.length > 0" class="rounded-xl border border-blue-100 bg-blue-50/70 p-3">
      <div class="flex items-center gap-2 text-blue-700 mb-2">
        <i class="pi pi-calendar"></i>
        <span class="font-medium">Upcoming Leaves</span>
      </div>
      <div class="flex gap-3 overflow-x-auto pb-1">
        <div 
          v-for="leave in upcomingLeaves" 
          :key="leave.id"
          class="min-w-48 rounded-lg border border-slate-100 bg-white p-2"
        >
          <div class="flex justify-between items-start">
            <Tag :value="leave.leave_type_label" :severity="getLeaveTypeSeverity(leave.leave_type)" size="small" />
            <span class="text-xs text-gray-500">in {{ leave.days_until }} days</span>
          </div>
          <div class="text-xs font-medium mt-1">{{ leave.start_date_formatted }} - {{ leave.end_date_formatted }}</div>
          <div class="text-xs text-gray-600">{{ leave.total_days }} day(s)</div>
        </div>
      </div>
    </div>

    <!-- Employee Info Banner -->
    <div v-if="employeeInfo.name" class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
      <i class="pi pi-user text-gray-500"></i>
      <span class="text-xs font-medium">{{ employeeInfo.name }}</span>
      <span v-if="employeeInfo.department" class="text-xs text-gray-500">• {{ employeeInfo.department }}</span>
      <span v-if="employeeInfo.position" class="text-xs text-gray-500">• {{ employeeInfo.position }}</span>
    </div>

    <!-- Leave History Table -->
    <div class="overflow-x-auto rounded-xl border border-slate-200">
    <DataTable 
      :value="filteredLeaveHistory" 
      :paginator="true" 
      :rows="10" 
      :rowsPerPageOptions="[10, 20, 50]"
      class="p-datatable-sm text-xs"
      :loading="loading"
      stripedRows
      showGridlines
      sortField="created_at"
      :sortOrder="-1"
    >
      <Column field="created_at_formatted" header="Date Filed" :sortable="true" style="width: 120px">
        <template #body="{ data }">
          <div>{{ data.created_at_formatted }}</div>
          <div class="text-xs text-gray-500">{{ formatTime(data.created_at) }}</div>
        </template>
      </Column>
      
      <Column field="leave_type_label" header="Leave Type" :sortable="true" style="width: 120px">
        <template #body="{ data }">
          <Tag :value="data.leave_type_label" :severity="getLeaveTypeSeverity(data.leave_type)" rounded />
          <div v-if="data.is_paid" class="text-xs text-green-600 mt-1">Paid</div>
          <div v-else class="text-xs text-gray-500 mt-1">Unpaid</div>
        </template>
      </Column>
      
      <Column header="Date Range" style="width: 200px">
        <template #body="{ data }">
          <div>{{ data.start_date_formatted }} - {{ data.end_date_formatted }}</div>
          <div class="text-xs text-gray-500">{{ data.duration }}</div>
        </template>
      </Column>
      
      <Column field="total_days" header="Days" :sortable="true" style="width: 70px">
        <template #body="{ data }">
          <span class="font-medium">{{ data.total_days }}</span>
        </template>
      </Column>
      
      <Column field="reason" header="Reason" style="min-width: 200px">
        <template #body="{ data }">
          <span class="truncate block" :title="data.reason">
            {{ data.reason || '—' }}
          </span>
        </template>
      </Column>
      
      <Column field="status_label" header="Status" :sortable="true" style="width: 100px">
        <template #body="{ data }">
          <Tag 
            :value="data.status_label" 
            :severity="getLeaveStatusSeverity(data.status)" 
            rounded 
          />
          <div v-if="data.approved_by" class="text-xs text-gray-500 mt-1">
            by {{ data.approved_by.name }}
          </div>
        </template>
      </Column>
      
      <Column field="handover_to" header="Handover" style="width: 120px">
        <template #body="{ data }">
          <div v-if="data.handover_to">
            <span class="text-xs">{{ data.handover_to.name }}</span>
            <div v-if="data.handover_notes" class="text-xs text-gray-500">
              Has notes
            </div>
          </div>
          <span v-else class="text-gray-400">—</span>
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
              @click="viewLeaveDetails(data)" 
            />
            <Button 
              v-if="canCancelLeave(data)"
              icon="pi pi-times" 
              text 
              rounded 
              size="small"
              severity="danger"
              v-tooltip="'Cancel Request'"
              @click="cancelLeave(data)"
            />
          </div>
        </template>
      </Column>

      <template #loading>
        <div class="space-y-2 p-3">
          <Skeleton v-for="row in 5" :key="row" height="1.75rem" />
        </div>
      </template>
      <template #empty>
        <div class="py-8 text-center text-xs text-slate-500">
          <i class="pi pi-calendar-times mb-2 block text-xl text-slate-300"></i>
          <p class="font-medium text-slate-600">No leave requests found</p>
          <p class="mt-1">Adjust the filters or submit a leave request.</p>
        </div>
      </template>
    </DataTable>
    </div>

    <!-- Leave Details Dialog -->
    <Dialog 
      v-model:visible="showDetailsDialog" 
      :header="'Leave Request Details'" 
      :style="{ width: '500px' }"
      :modal="true"
      class="leave-details-dialog"
    >
      <div v-if="selectedLeave" class="space-y-4">
        <!-- Status Banner -->
        <div class="flex justify-between items-center p-3 rounded-lg" 
          :class="{
            'bg-green-50': selectedLeave.status === 'approved',
            'bg-orange-50': selectedLeave.status === 'pending',
            'bg-red-50': selectedLeave.status === 'rejected',
            'bg-gray-50': selectedLeave.status === 'cancelled'
          }"
        >
          <div>
            <div class="text-xs text-gray-600">Status</div>
            <Tag 
              :value="selectedLeave.status_label" 
              :severity="getLeaveStatusSeverity(selectedLeave.status)" 
              rounded 
              class="mt-1"
            />
          </div>
          <div class="text-right">
            <div class="text-xs text-gray-600">Leave Type</div>
            <div class="font-medium">{{ selectedLeave.leave_type_label }}</div>
          </div>
        </div>

        <!-- Leave Details Grid -->
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-gray-50 p-2 rounded">
            <div class="text-xs text-gray-500">Date Filed</div>
            <div class="font-medium">{{ selectedLeave.created_at_formatted }}</div>
            <div class="text-xs text-gray-600">{{ formatTime(selectedLeave.created_at) }}</div>
          </div>
          
          <div class="bg-gray-50 p-2 rounded">
            <div class="text-xs text-gray-500">Total Days</div>
            <div class="font-medium">{{ selectedLeave.total_days }} days</div>
            <div class="text-xs text-gray-600">{{ selectedLeave.duration }}</div>
          </div>
        </div>

        <!-- Date Range -->
        <div class="bg-gray-50 p-3 rounded">
          <div class="text-xs text-gray-500 mb-1">Date Range</div>
          <div class="flex items-center gap-2">
            <div class="flex-1 text-center p-2 bg-white rounded border">
              <div class="text-xs text-gray-500">From</div>
              <div class="font-medium">{{ selectedLeave.start_date_formatted }}</div>
            </div>
            <i class="pi pi-arrow-right text-gray-400"></i>
            <div class="flex-1 text-center p-2 bg-white rounded border">
              <div class="text-xs text-gray-500">To</div>
              <div class="font-medium">{{ selectedLeave.end_date_formatted }}</div>
            </div>
          </div>
        </div>

        <!-- Reason -->
        <div class="bg-gray-50 p-3 rounded">
          <div class="text-xs text-gray-500 mb-1">Reason</div>
          <div class="text-xs bg-white p-2 rounded border">{{ selectedLeave.reason || '—' }}</div>
        </div>

        <!-- Payment Info -->
        <div class="grid grid-cols-2 gap-3">
          <div class="bg-gray-50 p-2 rounded">
            <div class="text-xs text-gray-500">Payment</div>
            <div>
              <span v-if="selectedLeave.is_paid" class="text-green-600 font-medium">Paid Leave</span>
              <span v-else class="text-gray-600">Unpaid Leave</span>
            </div>
          </div>
          
          <div class="bg-gray-50 p-2 rounded">
            <div class="text-xs text-gray-500">Deduct from Balance</div>
            <div>
              <span v-if="selectedLeave.deduct_from_balance" class="text-blue-600">Yes</span>
              <span v-else class="text-gray-600">No</span>
            </div>
          </div>
        </div>

        <!-- Handover Information -->
        <div v-if="selectedLeave.handover_to" class="bg-gray-50 p-3 rounded">
          <div class="text-xs text-gray-500 mb-1">Handover To</div>
          <div class="font-medium">{{ selectedLeave.handover_to.name }}</div>
          <div v-if="selectedLeave.handover_to.position" class="text-xs text-gray-600">
            {{ selectedLeave.handover_to.position }}
          </div>
          <div v-if="selectedLeave.handover_notes" class="mt-2 text-xs bg-white p-2 rounded border">
            {{ JSON.stringify(selectedLeave.handover_notes) }}
          </div>
        </div>

        <!-- Approval Information -->
        <div v-if="selectedLeave.approved_by" class="bg-gray-50 p-3 rounded">
          <div class="text-xs text-gray-500 mb-1">Approval Details</div>
          <div class="flex justify-between">
            <div>
              <div class="font-medium">{{ selectedLeave.approved_by.name }}</div>
              <div class="text-xs text-gray-600">Approver</div>
            </div>
            <div class="text-right">
              <div class="font-medium">{{ selectedLeave.approved_at_formatted }}</div>
              <div class="text-xs text-gray-600">Approved At</div>
            </div>
          </div>
        </div>

        <!-- Rejection Reason -->
        <div v-if="selectedLeave.rejected_reason" class="bg-red-50 p-3 rounded">
          <div class="text-xs text-red-600 mb-1">Rejection Reason</div>
          <div class="text-xs">{{ selectedLeave.rejected_reason }}</div>
        </div>

        <!-- Attachment -->
        <div v-if="selectedLeave.attachment_path" class="bg-gray-50 p-3 rounded">
          <div class="text-xs text-gray-500 mb-1">Attachment</div>
          <a :href="selectedLeave.attachment_path" target="_blank" class="text-blue-600 flex items-center gap-1">
            <i class="pi pi-file-pdf"></i>
            View Attachment
          </a>
        </div>
      </div>
      
      <template #footer>
        <Button label="Close" icon="pi pi-times" @click="showDetailsDialog = false" text />
        <Button 
          v-if="selectedLeave && canCancelLeave(selectedLeave)"
          label="Cancel Request" 
          icon="pi pi-times" 
          severity="danger" 
          @click="confirmCancelLeave" 
        />
      </template>
    </Dialog>

    <!-- Cancel Confirmation Dialog -->
    <Dialog 
      v-model:visible="showCancelDialog" 
      header="Confirm Cancellation" 
      :style="{ width: '400px' }"
      :modal="true"
    >
      <div class="space-y-3">
        <p>Are you sure you want to cancel this leave request?</p>
        <p class="text-xs text-gray-600">This action cannot be undone.</p>
      </div>
      <template #footer>
        <Button label="No, Keep" icon="pi pi-times" @click="showCancelDialog = false" text />
        <Button label="Yes, Cancel" icon="pi pi-check" severity="danger" @click="executeCancelLeave" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import hrService from '@/services/hr.services'
import Skeleton from 'primevue/skeleton'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '../../../../../stores/auth'

const props = defineProps<{
  employeeId: string | number
  initialData?: any
}>()

const emit = defineEmits(['update:leave', 'view-details', 'edit', 'export'])

const authStore = useAuthStore()
const toast = useToast()

// State
const loading = ref(false)
const leaveHistory = ref<any[]>([])
const employeeInfo = ref({
  id: null,
  name: '',
  employee_number: null,
  department: null,
  position: null
})
const statistics = ref({
  total_leaves: 0,
  pending: 0,
  approved: 0,
  rejected: 0,
  cancelled: 0,
  total_days: 0,
  sick_leaves: 0,
  vacation_leaves: 0,
  personal_leaves: 0
})
const leaveBalances = ref<any>({})
const upcomingLeaves = ref<any[]>([])

// Filters
const selectedYear = ref(new Date().getFullYear())
const filters = ref({
  search: '',
  status: null,
  type: null
})

// Dialog states
const showDetailsDialog = ref(false)
const showCancelDialog = ref(false)
const selectedLeave = ref<any>(null)
const leaveToCancel = ref<any>(null)

// Options
const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear()
  return [currentYear - 1, currentYear, currentYear + 1].map(year => ({
    label: year.toString(),
    value: year
  }))
})

const statusOptions = ref([
  { label: 'Approved', value: 'approved' },
  { label: 'Pending', value: 'pending' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' }
])

const typeOptions = ref([
  { label: 'Vacation', value: 'vacation' },
  { label: 'Sick', value: 'sick' },
  { label: 'Personal', value: 'personal' },
  { label: 'Maternity', value: 'maternity' },
  { label: 'Paternity', value: 'paternity' },
  { label: 'Bereavement', value: 'bereavement' },
  { label: 'Others', value: 'others' }
])

// Computed
const formattedBalances = computed(() => {
  const formatted: Record<string, any> = {}
  
  Object.keys(leaveBalances.value).forEach(type => {
    const balance = leaveBalances.value[type]
    formatted[type] = {
      used: balance.used_days || 0,
      quota: balance.yearly_quota || 0,
      remaining: balance.remaining_days || 0,
      pending: balance.pending_days || 0,
      carried_over: balance.carried_over || 0
    }
  })
  
  return formatted
})

const filteredLeaveHistory = computed(() => {
  let filtered = [...leaveHistory.value]
  
  if (filters.value.search) {
    const searchTerm = filters.value.search.toLowerCase()
    filtered = filtered.filter(item => 
      item.reason?.toLowerCase().includes(searchTerm) ||
      item.leave_type?.toLowerCase().includes(searchTerm)
    )
  }
  
  if (filters.value.status) {
    filtered = filtered.filter(item => item.status === filters.value.status)
  }
  
  if (filters.value.type) {
    filtered = filtered.filter(item => item.leave_type === filters.value.type)
  }
  
  return filtered
})

// Helper Functions
const formatDate = (date: string) => {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const formatTime = (date: string) => {
  if (!date) return '—'
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getLeaveTypeSeverity = (type: string) => {
  const map: Record<string, string> = {
    'vacation': 'info',
    'sick': 'success',
    'personal': 'warning',
    'maternity': 'warning',
    'paternity': 'warning',
    'bereavement': 'secondary',
    'others': 'secondary'
  }
  return map[type?.toLowerCase()] || 'secondary'
}

const getLeaveStatusSeverity = (status: string) => {
  const map: Record<string, string> = {
    'approved': 'success',
    'pending': 'warning',
    'rejected': 'danger',
    'cancelled': 'secondary'
  }
  return map[status?.toLowerCase()] || 'info'
}

const canCancelLeave = (leave: any) => {
  return leave && (leave.status === 'pending' || leave.status === 'approved')
}

// Actions
const resetFilters = () => {
  filters.value = {
    search: '',
    status: null,
    type: null
  }
}

const applyFilters = () => {
  // Filters are applied automatically via computed property
  // This method can be used if you need to trigger any side effects
}

const viewLeaveDetails = (leave: any) => {
  selectedLeave.value = leave
  showDetailsDialog.value = true
  emit('view-details', leave)
}

const showNewLeaveDialog = () => {
  emit('edit', { employeeId: props.employeeId, action: 'new' })
}

const cancelLeave = (leave: any) => {
  leaveToCancel.value = leave
  showCancelDialog.value = true
}

const confirmCancelLeave = () => {
  showDetailsDialog.value = false
  leaveToCancel.value = selectedLeave.value
  showCancelDialog.value = true
}

const executeCancelLeave = async () => {
  if (!leaveToCancel.value) return
  
  try {
    const response = await hrService.api.put(`/api/leaves/${leaveToCancel.value.id}/cancel`, {}, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      }
    })

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Leave request cancelled successfully',
        life: 3000
      })
      
      // Refresh data
      await fetchLeaveData()
      await fetchUpcomingLeaves()
      
      showCancelDialog.value = false
      showDetailsDialog.value = false
      leaveToCancel.value = null
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to cancel leave request',
      life: 3000
    })
  }
}

const exportLeaves = () => {
  const params = new URLSearchParams()
  params.append('employee_id', props.employeeId.toString())
  params.append('year', selectedYear.value.toString())
  
  if (filters.value.status) {
    params.append('status', filters.value.status)
  }
  if (filters.value.type) {
    params.append('leave_type', filters.value.type)
  }
  
  const url = `/api/leaves/export?${params.toString()}`
  window.open(url, '_blank')
  emit('export', { 
    employeeId: props.employeeId,
    year: selectedYear.value,
    filters: filters.value
  })
}

// API Functions
const fetchLeaveData = async () => {
  if (!props.employeeId) return
  
  loading.value = true
  try {
    const response = await hrService.api.get(`/api/users/${props.employeeId}/leaves`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      },
      params: {
        year: selectedYear.value
      }
    })

    if (response.data.success) {
      const data = response.data.data
      
      // Update all data from the response
      employeeInfo.value = data.employee || employeeInfo.value
      statistics.value = data.statistics || statistics.value
      leaveBalances.value = data.balances || {}
      
      // The leaves are paginated, so we need to extract the data array
      if (data.leaves && data.leaves.data) {
        leaveHistory.value = data.leaves.data
      } else {
        leaveHistory.value = []
      }
      
      emit('update:leave', data)
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to fetch leave data',
      life: 3000
    })
    console.error('Leave fetch error:', err)
  } finally {
    loading.value = false
  }
}

const fetchUpcomingLeaves = async () => {
  if (!props.employeeId) return
  
  try {
    const response = await hrService.api.get(`/api/users/${props.employeeId}/leaves/upcoming`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      },
      params: {
        limit: 5
      }
    })

    if (response.data.success) {
      upcomingLeaves.value = response.data.data || []
    }
  } catch (err) {
    console.error('Failed to fetch upcoming leaves:', err)
  }
}

// Expose methods for parent component
defineExpose({
  refresh: fetchLeaveData,
  filters,
  selectedYear
})

// Watchers
watch(() => props.employeeId, () => {
  if (props.employeeId) {
    fetchLeaveData()
    fetchUpcomingLeaves()
  }
})

watch(selectedYear, () => {
  fetchLeaveData()
})

// Lifecycle
onMounted(() => {
  if (props.initialData) {
    // Handle initial data if provided
    if (props.initialData.leaves) {
      leaveHistory.value = props.initialData.leaves.data || []
    }
    if (props.initialData.statistics) {
      statistics.value = props.initialData.statistics
    }
    if (props.initialData.balances) {
      leaveBalances.value = props.initialData.balances
    }
    if (props.initialData.employee) {
      employeeInfo.value = props.initialData.employee
    }
  } else {
    fetchLeaveData()
    fetchUpcomingLeaves()
  }
})
</script>

<style scoped>
.p-datatable-sm :deep(.p-datatable-tbody > tr > td) {
  padding: 0.5rem 0.5rem;
  font-size: 0.75rem;
}

.p-datatable-sm :deep(.p-datatable-thead > tr > th) {
  padding: 0.5rem 0.5rem;
  font-size: 0.75rem;
}

:deep(.p-tag) {
  font-size: 0.75rem;
  padding: 0.25rem 0.5rem;
}

.leave-details-dialog :deep(.p-dialog-content) {
  padding: 1.5rem;
}
</style>
