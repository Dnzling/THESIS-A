<template>
  <div class="mx-auto max-w-7xl space-y-5 p-4 sm:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Leave History</h1>
        <p class="mt-1 text-sm text-slate-500">Track filed leave requests across your store.</p>
      </div>
      <Button label="Back to Management" icon="pi pi-arrow-left" severity="secondary" outlined size="small"
        @click="router.push({ name: 'hr.leave' })" />
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:w-auto lg:grid-cols-3">
          <IconField class="w-full sm:min-w-56">
            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
            <InputText v-model="filters.search" placeholder="Search employee" class="w-full pl-8" />
          </IconField>

          <Select v-model="filters.status" :options="statusOptions" showClear placeholder="Status"
            class="w-full sm:min-w-40" />

          <Select v-model="filters.leaveType" :options="leaveTypeOptions" showClear placeholder="Leave Type"
            class="w-full sm:min-w-44" />
        </div>

        <div class="flex gap-2">
          <Button label="Apply" icon="pi pi-filter" severity="warn" outlined size="small" @click="applyFilters" />
          <Button v-if="hasActiveFilters" label="Clear Filters" icon="pi pi-filter-slash" severity="danger" outlined size="small" @click="resetFilters" />
        </div>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div v-if="loading" class="space-y-2 p-4" aria-label="Loading leave history">
        <Skeleton v-for="i in 6" :key="i" height="2.75rem" />
      </div>
      <div v-else-if="filteredLeaves.length === 0" class="px-4 py-12 text-center">
        <i class="pi pi-calendar-times text-2xl text-slate-300" />
        <p class="mt-2 text-sm font-medium text-slate-700">No leave history found</p>
        <p class="mt-1 text-xs text-slate-500">Try adjusting the filters or search term.</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-100 text-left">
              <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
              <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Leave Type</th>
              <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Date Range</th>
              <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Days</th>
              <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
              <th class="whitespace-nowrap px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Filed</th>
              <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="leave in filteredLeaves" :key="leave.id" class="transition-colors hover:bg-orange-50/40">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <Avatar :label="getInitials(leave.employeeName)" size="normal" class="bg-slate-100 text-slate-700 font-medium" />
                  <div>
                    <div class="font-medium text-slate-800">{{ leave.employeeName }}</div>
                    <div class="text-xs text-slate-500">{{ leave.department }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3">
                <Tag :value="leave.leaveType" severity="info" rounded />
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                {{ formatDateRange(leave.startDate, leave.endDate) }}
              </td>
              <td class="px-4 py-3 font-medium text-slate-700">{{ leave.duration }} <span class="text-xs font-normal text-slate-500">days</span></td>
              <td class="px-4 py-3">
                <Badge :value="leave.statusLabel" :severity="statusSeverity(leave.status)" />
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-slate-500">{{ formatDate(leave.submittedDate) }}</td>
              <td class="px-4 py-3 text-right">
                <Button label="View" icon="pi pi-eye" size="small" outlined rounded @click="goToDetail(leave.id)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!loading && filteredLeaves.length" class="flex flex-col gap-2 border-t border-slate-200 p-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-xs text-slate-500">
          Showing {{ paginationStart }} to {{ paginationEnd }} of {{ totalRecords }} entries
        </div>
        <Paginator v-model:first="paginationOffset" :rows="perPage" :totalRecords="totalRecords" @page="onPageChange"
          template="PrevPageLink PageLinks NextPageLink" class="bg-transparent border-0" />
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import hrService from '@/services/hr.services'
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
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
const hasActiveFilters = computed(() => Boolean(filters.value.search || filters.value.status || filters.value.leaveType))

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
    pending: 'warn',
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

const applyFilters = () => {
  currentPage.value = 1
  paginationOffset.value = 0
  fetchLeaves()
}

const goToDetail = (id: number) => {
  router.push({ name: 'hr.leaves.detail', params: { id } })
}

onMounted(fetchLeaves)
</script>
