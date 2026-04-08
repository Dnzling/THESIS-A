<template>
  <div class="p-6 max-w-7xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Leave History</h2>
        <p class="text-sm text-gray-500">Track all filed leave requests across the store.</p>
      </div>
      <Button label="Back to Management" icon="pi pi-arrow-left" severity="secondary" outlined
        @click="router.push({ name: 'hr.leave' })" />
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
      <div class="flex flex-wrap gap-3 items-center justify-between">
        <div class="flex gap-3 flex-wrap">
          <IconField>
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <InputText v-model="filters.search" placeholder="Search employee" class="pl-8 rounded-lg w-64" />
          </IconField>

          <Select v-model="filters.status" :options="statusOptions" showClear placeholder="Status"
            class="rounded-lg w-40" />

          <Select v-model="filters.leaveType" :options="leaveTypeOptions" showClear placeholder="Leave Type"
            class="rounded-lg w-44" />
        </div>

        <div class="flex gap-2">
          <Button label="Apply" icon="pi pi-filter" severity="info" outlined @click="fetchLeaves" />
          <Button label="Reset" icon="pi pi-filter-slash" severity="secondary" outlined @click="resetFilters" />
        </div>
      </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-100 text-left">
              <th class="p-4 text-sm font-medium text-gray-600">Employee</th>
              <th class="p-4 text-sm font-medium text-gray-600">Leave Type</th>
              <th class="p-4 text-sm font-medium text-gray-600">Date Range</th>
              <th class="p-4 text-sm font-medium text-gray-600">Days</th>
              <th class="p-4 text-sm font-medium text-gray-600">Status</th>
              <th class="p-4 text-sm font-medium text-gray-600">Filed</th>
              <th class="p-4 text-sm font-medium text-gray-600">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="leave in filteredLeaves" :key="leave.id" class="hover:bg-gray-50/50 transition-colors">
              <td class="p-4">
                <div class="flex items-center gap-3">
                  <Avatar :label="getInitials(leave.employeeName)" size="large"
                    class="bg-blue-100 text-blue-600 font-medium" />
                  <div>
                    <div class="font-medium text-gray-800">{{ leave.employeeName }}</div>
                    <div class="text-xs text-gray-500">{{ leave.department }}</div>
                  </div>
                </div>
              </td>
              <td class="p-4">
                <Tag :value="leave.leaveType" severity="info" rounded />
              </td>
              <td class="p-4 text-sm text-gray-600">
                {{ formatDateRange(leave.startDate, leave.endDate) }}
              </td>
              <td class="p-4 text-sm font-medium text-gray-700">{{ leave.duration }}</td>
              <td class="p-4">
                <Tag :value="leave.statusLabel" :severity="statusSeverity(leave.status)" rounded />
              </td>
              <td class="p-4 text-sm text-gray-500">{{ formatDate(leave.submittedDate) }}</td>
              <td class="p-4">
                <Button icon="pi pi-eye" text rounded severity="info" @click="goToDetail(leave.id)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="p-4 border-t border-gray-100 flex items-center justify-between">
        <div class="text-sm text-gray-500">
          Showing {{ paginationStart }} to {{ paginationEnd }} of {{ totalRecords }} entries
        </div>
        <Paginator v-model:first="paginationOffset" :rows="perPage" :totalRecords="totalRecords" @page="onPageChange"
          template="PrevPageLink PageLinks NextPageLink" class="bg-transparent border-0" />
      </div>
    </div>

    <div v-if="loading" class="mt-4">
      <Skeleton v-for="i in 5" :key="i" height="3rem" class="mb-2" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import hrService from '@/services/hr.services'
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Paginator from 'primevue/paginator'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'

interface LeaveRequest {
  id: number
  employeeName: string
  department: string
  leaveType: string
  startDate: string
  endDate: string
  duration: number
  status: string
  statusLabel: string
  submittedDate: string
}

const router = useRouter()
const loading = ref(false)
const leaveRequests = ref<LeaveRequest[]>([])
const totalRecords = ref(0)
const perPage = ref(15)
const currentPage = ref(1)
const paginationOffset = ref(0)

const filters = ref({
  search: '',
  status: null as string | null,
  leaveType: null as string | null,
})

const statusOptions = [
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' },
]

const leaveTypeOptions = [
  { label: 'Vacation', value: 'vacation' },
  { label: 'Sick', value: 'sick' },
  { label: 'Personal', value: 'personal' },
  { label: 'Maternity', value: 'maternity' },
  { label: 'Paternity', value: 'paternity' },
  { label: 'Bereavement', value: 'bereavement' },
  { label: 'Others', value: 'others' },
]

const getInitials = (name: string): string => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

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

const statusSeverity = (status: string) => {
  const map: Record<string, string> = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'secondary',
  }
  return map[status] || 'info'
}

const transformLeaveData = (records: any[]): LeaveRequest[] => {
  return records.map((item: any) => {
    const employee = item.employee || {}
    return {
      id: item.id,
      employeeName: employee.fname && employee.lname
        ? `${employee.fname} ${employee.lname}`.trim()
        : 'Unknown',
      department: employee.department || 'N/A',
      leaveType: item.leave_type
        ? item.leave_type.charAt(0).toUpperCase() + item.leave_type.slice(1).replace(/_/g, ' ')
        : 'Unknown',
      startDate: item.start_date,
      endDate: item.end_date,
      duration: item.total_days || 0,
      status: item.status || 'pending',
      statusLabel: item.status ? item.status.charAt(0).toUpperCase() + item.status.slice(1) : 'Pending',
      submittedDate: item.created_at,
    }
  })
}

const fetchLeaves = async () => {
  loading.value = true
  try {
    const response = await hrService.api.get('/api/leaves', {
      params: {
        page: currentPage.value,
        per_page: perPage.value,
        status: filters.value.status || undefined,
        leave_type: filters.value.leaveType || undefined,
      }
    })
    if (response.data.success) {
      const records = response.data.data?.data || response.data.data || []
      leaveRequests.value = transformLeaveData(records)
      totalRecords.value = response.data.data?.total || records.length
      perPage.value = response.data.data?.per_page || perPage.value
    }
  } catch (error) {
    console.error('Failed to load leaves', error)
  } finally {
    loading.value = false
  }
}

const filteredLeaves = computed(() => {
  if (!filters.value.search) return leaveRequests.value
  const search = filters.value.search.toLowerCase()
  return leaveRequests.value.filter(leave =>
    leave.employeeName.toLowerCase().includes(search) ||
    leave.department.toLowerCase().includes(search)
  )
})

const onPageChange = (event: any) => {
  paginationOffset.value = event.first
  currentPage.value = Math.floor(event.first / perPage.value) + 1
  fetchLeaves()
}

const paginationStart = computed(() => (totalRecords.value === 0 ? 0 : paginationOffset.value + 1))
const paginationEnd = computed(() => Math.min(paginationOffset.value + perPage.value, totalRecords.value))

const resetFilters = () => {
  filters.value = { search: '', status: null, leaveType: null }
  currentPage.value = 1
  paginationOffset.value = 0
  fetchLeaves()
}

const goToDetail = (id: number) => {
  router.push({ name: 'hr.leaves.detail', params: { id } })
}

onMounted(fetchLeaves)
</script>
