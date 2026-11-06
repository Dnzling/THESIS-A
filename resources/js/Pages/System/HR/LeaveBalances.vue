<template>
  <div class="mx-auto max-w-7xl space-y-5 p-4 sm:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Leave Balances</h1>
        <p class="mt-1 text-sm text-slate-500">Review employee leave usage and remaining days for {{ currentYear }}.</p>
      </div>
      <Button label="Export" icon="pi pi-download" severity="warn" outlined size="small" @click="exportBalances" />
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm text-gray-500">Total Employees</span>
          <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-users text-blue-500 text-sm"></i>
          </div>
        </div>
        <div class="text-2xl font-semibold text-gray-800">{{ employeeCount }}</div>
        <div class="text-xs text-gray-400 mt-1">Active employees</div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm text-gray-500">Avg. Remaining</span>
          <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-chart-line text-green-500 text-sm"></i>
          </div>
        </div>
        <div class="text-2xl font-semibold text-gray-800">{{ averageRemaining }} days</div>
        <div class="text-xs text-gray-400 mt-1">Per employee</div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm text-gray-500">Low Balance</span>
          <div class="w-8 h-8 bg-yellow-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-exclamation-triangle text-yellow-500 text-sm"></i>
          </div>
        </div>
        <div class="text-2xl font-semibold text-gray-800">{{ lowBalanceCount }}</div>
        <div class="text-xs text-gray-400 mt-1">Less than 5 days left</div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm text-gray-500">Used This Year</span>
          <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-calendar text-purple-500 text-sm"></i>
          </div>
        </div>
        <div class="text-2xl font-semibold text-gray-800">{{ totalUsed }} days</div>
        <div class="text-xs text-gray-400 mt-1">Across all employees</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:w-auto lg:grid-cols-3">
          <div class="w-full sm:min-w-56">
            <IconField>
              <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
              <InputText v-model="filters.search" placeholder="Search employee" class="w-full pl-8" />
            </IconField>
          </div>

          <Select v-model="filters.department" :options="departmentOptions" showClear placeholder="All Departments"
            class="w-full sm:min-w-48" />

          <Select v-model="filters.balance" :options="balanceFilters" showClear placeholder="Balance Range"
            class="w-full sm:min-w-48" />
        </div>

        <Button label="Clear Filters" icon="pi pi-filter-slash" severity="danger" outlined size="small"
          :disabled="!hasActiveFilters" @click="resetFilters" />
      </div>
    </div>

    <!-- Balances Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div v-if="loading" class="space-y-2 p-4" aria-label="Loading leave balances">
        <Skeleton v-for="i in 6" :key="i" height="2.75rem" />
      </div>
      <div v-else-if="filteredBalances.length === 0" class="px-4 py-12 text-center">
        <i class="pi pi-calendar-minus text-2xl text-slate-300" />
        <p class="mt-2 text-sm font-medium text-slate-700">No leave balances found</p>
        <p class="mt-1 text-xs text-slate-500">Try changing or clearing the filters.</p>
      </div>
      <div v-else class="overflow-x-auto">
        <table class="w-full min-w-max text-sm">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-100">
              <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
              <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
              <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500" v-for="type in leaveTypeKeys" :key="type">
                {{ leaveTypeLabels[type] || type }}
              </th>
              <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Total Used</th>
              <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
              <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="emp in paginatedBalances" :key="emp.id" class="transition-colors hover:bg-orange-50/40">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <Avatar :label="getInitials(emp.name)" size="normal" class="bg-slate-100 text-slate-700 font-medium" />
                  <div>
                    <div class="font-medium text-slate-800">{{ emp.name }}</div>
                    <div class="text-xs text-gray-500">{{ emp.employeeId }}</div>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-slate-600">{{ emp.department }}</td>
              <td v-for="type in leaveTypeKeys" :key="type" class="px-4 py-3">
                <div class="flex flex-col">
                  <div class="flex items-center gap-2">
                    <span class="font-medium text-slate-800">{{ getBalance(emp, type) }} <span class="text-xs font-normal text-slate-500">days</span></span>
                    <Badge v-if="getBalance(emp, type) < 5" value="Low" severity="warn" />
                  </div>
                  <ProgressBar :value="getUsagePercentage(emp, type)" :showValue="false" class="mt-1 h-1 w-20" />
                </div>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <span class="font-medium text-slate-800">{{ emp.totalUsed }} days</span>
                  <span class="text-xs text-slate-500">({{ usagePercent(emp) }}%)</span>
                </div>
              </td>
              <td class="px-4 py-3">
                <Badge :value="getBalanceStatus(emp)" :severity="getBalanceSeverity(emp)" />
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-1">
                  <Button label="View" icon="pi pi-eye" size="small" outlined rounded @click="viewDetails(emp)" />
                  <Button icon="pi pi-history" text rounded severity="secondary" aria-label="View leave history" @click="viewHistory(emp)" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="!loading && filteredBalances.length" class="flex flex-col gap-2 border-t border-slate-200 p-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-xs text-slate-500">
          Showing {{ paginationStart }} to {{ paginationEnd }} of {{ filteredBalances.length }} entries
        </div>
        <Paginator v-model:first="paginationOffset" :rows="pageSize" :totalRecords="filteredBalances.length"
          @page="onPageChange" template="PrevPageLink PageLinks NextPageLink" class="bg-transparent border-0" />
      </div>
    </div>

    <!-- Employee Details Modal -->
    <Dialog v-model:visible="showDetailsModal" modal :style="{ width: '500px' }" class="rounded-xl">
      <template #header>
        <div class="flex items-center gap-3">
          <Avatar :label="getInitials(selectedEmployee?.name || '')" size="large"
            class="bg-blue-100 text-blue-600" />
          <div>
            <div class="font-semibold text-gray-800">{{ selectedEmployee?.name }}</div>
            <div class="text-sm text-gray-500">{{ selectedEmployee?.department }}</div>
          </div>
        </div>
      </template>

      <div v-if="selectedEmployee" class="space-y-5">
        <div class="grid grid-cols-2 gap-3">
          <div v-for="type in leaveTypeKeys" :key="type" class="bg-gray-50 rounded-lg p-3">
            <div class="text-xs text-gray-500 mb-2">{{ leaveTypeLabels[type] || type }} Leave</div>
            <div class="flex items-center justify-between mb-2">
              <span class="text-xl font-semibold">{{ getBalance(selectedEmployee, type) }}</span>
              <span class="text-xs text-gray-400">/ {{ getQuota(selectedEmployee, type) }} days</span>
            </div>
            <ProgressBar :value="getUsagePercentage(selectedEmployee, type)" :showValue="false" class="h-1.5" />
          </div>
        </div>

        <div class="border-t border-gray-100 pt-4">
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-gray-500">Total Allocated</span>
              <div class="font-medium">{{ selectedEmployee.totalQuota }} days</div>
            </div>
            <div>
              <span class="text-gray-500">Total Used</span>
              <div class="font-medium">{{ selectedEmployee.totalUsed }} days</div>
            </div>
            <div>
              <span class="text-gray-500">Remaining</span>
              <div class="font-medium text-blue-600">{{ selectedEmployee.totalRemaining }} days</div>
            </div>
            <div>
              <span class="text-gray-500">Pending</span>
              <div class="font-medium">{{ selectedEmployee.totalPending }} days</div>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="showDetailsModal = false" />
      </template>
    </Dialog>

    <!-- History Modal -->
    <Dialog v-model:visible="showHistoryModal" modal :style="{ width: 'min(560px, calc(100vw - 2rem))' }" class="rounded-xl"
      :header="`Leave History - ${selectedEmployee?.name || ''}`">
      <div v-if="historyLoading" class="space-y-2">
        <Skeleton v-for="i in 4" :key="i" height="2.5rem" />
      </div>
      <div v-else class="space-y-3 max-h-96 overflow-y-auto">
        <div v-for="item in historyData" :key="item.id" class="rounded-lg border border-slate-200 p-3 transition-colors hover:bg-orange-50/40">
          <div class="flex justify-between items-start mb-2">
            <div>
              <span class="text-sm font-medium">{{ item.type }}</span>
              <Badge :value="item.status" :severity="historyStatusSeverity(item.status)" class="ml-2" />
            </div>
            <span class="text-xs text-gray-400">{{ item.date }}</span>
          </div>
          <div class="text-sm text-gray-600">{{ item.details }}</div>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import hrService from '@/services/hr.services'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import ProgressBar from 'primevue/progressbar'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import Paginator from 'primevue/paginator'
import Skeleton from 'primevue/skeleton'

interface EmployeeBalanceRow {
  id: number
  employeeId: string
  name: string
  department: string
  balances: Record<string, any>
  totalUsed: number
  totalQuota: number
  totalRemaining: number
  totalPending: number
}

const showDetailsModal = ref(false)
const showHistoryModal = ref(false)
const selectedEmployee = ref<EmployeeBalanceRow | null>(null)
const paginationOffset = ref(0)
const pageSize = ref(10)
const loading = ref(false)
const historyLoading = ref(false)

const filters = ref({
  search: '',
  department: null as string | null,
  balance: null as string | null
})

const employees = ref<EmployeeBalanceRow[]>([])
const historyData = ref<any[]>([])

const leaveTypeLabels: Record<string, string> = {
  vacation: 'Vacation',
  sick: 'Sick',
  personal: 'Personal',
  maternity: 'Maternity',
  paternity: 'Paternity',
  bereavement: 'Bereavement',
  others: 'Others',
}

const leaveTypeKeys = ref<string[]>(Object.keys(leaveTypeLabels))
const departmentOptions = ref<string[]>([])
const currentYear = new Date().getFullYear()
const hasActiveFilters = computed(() => Boolean(filters.value.search || filters.value.department || filters.value.balance))

const balanceFilters = [
  { label: 'All Balances', value: null },
  { label: 'Low (0-5 days)', value: 'low' },
  { label: 'Medium (6-15 days)', value: 'medium' },
  { label: 'High (16+ days)', value: 'high' }
]

const fetchBalances = async () => {
  loading.value = true
  try {
    const year = new Date().getFullYear()
    const [employeesResponse, balancesResponse] = await Promise.all([
      hrService.getEmployees(),
      hrService.getLeaveBalances({ year, per_page: 500 }),
    ])

    const employeeRecords = employeesResponse?.data?.data || employeesResponse?.data || []
    const balanceRecords = balancesResponse?.data?.data || balancesResponse?.data || []

    const employeeMap = new Map<number, EmployeeBalanceRow>()
    employeeRecords.forEach((emp: any) => {
      employeeMap.set(emp.id, {
        id: emp.id,
        employeeId: emp.employee_number || `EMP-${emp.id}`,
        name: `${emp.fname} ${emp.lname}`.trim(),
        department: emp.department || 'N/A',
        balances: {},
        totalUsed: 0,
        totalQuota: 0,
        totalRemaining: 0,
        totalPending: 0,
      })
    })

    const typeSet = new Set(leaveTypeKeys.value)

    balanceRecords.forEach((balance: any) => {
      const empId = balance.employee_id
      const employee = employeeMap.get(empId) || {
        id: empId,
        employeeId: balance.employee?.employee_number || `EMP-${empId}`,
        name: balance.employee
          ? `${balance.employee.fname} ${balance.employee.lname}`.trim()
          : `Employee #${empId}`,
        department: balance.employee?.department || 'N/A',
        balances: {},
        totalUsed: 0,
        totalQuota: 0,
        totalRemaining: 0,
        totalPending: 0,
      }

      const type = balance.leave_type
      typeSet.add(type)

      const quota = Number(balance.yearly_quota || 0) + Number(balance.carried_over || 0)
      const used = Number(balance.used_days || 0)
      const pending = Number(balance.pending_days || 0)
      const remaining = Number(balance.remaining_days || 0)

      employee.balances[type] = {
        id: balance.id,
        leave_type: type,
        yearly_quota: Number(balance.yearly_quota || 0),
        carried_over: Number(balance.carried_over || 0),
        used_days: used,
        pending_days: pending,
        remaining_days: remaining,
        quota,
      }

      employee.totalUsed += used
      employee.totalQuota += quota
      employee.totalRemaining += remaining
      employee.totalPending += pending

      employeeMap.set(empId, employee)
    })

    leaveTypeKeys.value = Array.from(typeSet)
    employees.value = Array.from(employeeMap.values())
    departmentOptions.value = Array.from(new Set(employees.value.map(emp => emp.department))).filter(Boolean)
  } catch (error) {
    employees.value = []
  } finally {
    loading.value = false
  }
}

const employeeCount = computed(() => employees.value.length)

const averageRemaining = computed(() => {
  if (employees.value.length === 0) return 0
  const total = employees.value.reduce((sum, emp) => sum + emp.totalRemaining, 0)
  return Math.round(total / employees.value.length)
})

const lowBalanceCount = computed(() => {
  return employees.value.filter(emp => emp.totalRemaining < 5).length
})

const totalUsed = computed(() => {
  return employees.value.reduce((sum, emp) => sum + emp.totalUsed, 0)
})

const filteredBalances = computed(() => {
  let filtered = [...employees.value]
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(emp =>
      emp.name.toLowerCase().includes(search) ||
      emp.employeeId.toLowerCase().includes(search)
    )
  }
  if (filters.value.department) {
    filtered = filtered.filter(emp => emp.department === filters.value.department)
  }
  if (filters.value.balance) {
    filtered = filtered.filter(emp => {
      const total = emp.totalRemaining
      switch (filters.value.balance) {
        case 'low': return total < 5
        case 'medium': return total >= 5 && total <= 15
        case 'high': return total > 15
        default: return true
      }
    })
  }
  return filtered
})

const paginatedBalances = computed(() => {
  return filteredBalances.value.slice(paginationOffset.value, paginationOffset.value + pageSize.value)
})

const paginationStart = computed(() => paginationOffset.value + 1)
const paginationEnd = computed(() => Math.min(paginationOffset.value + pageSize.value, filteredBalances.value.length))

const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getBalance = (employee: EmployeeBalanceRow, type: string) => {
  return employee.balances?.[type]?.remaining_days ?? 0
}

const getQuota = (employee: EmployeeBalanceRow, type: string) => {
  return employee.balances?.[type]?.quota ?? 0
}

const getUsagePercentage = (employee: EmployeeBalanceRow, type: string) => {
  const quota = getQuota(employee, type)
  if (!quota) return 0
  const used = employee.balances?.[type]?.used_days ?? 0
  return Math.min((used / quota) * 100, 100)
}

const getBalanceStatus = (employee: EmployeeBalanceRow) => {
  const total = employee.totalRemaining
  if (total < 5) return 'Low Balance'
  if (total < 10) return 'Moderate'
  return 'Healthy'
}

const getBalanceSeverity = (employee: EmployeeBalanceRow) => {
  const total = employee.totalRemaining
  if (total < 5) return 'warn'
  if (total < 10) return 'info'
  return 'success'
}

const usagePercent = (employee: EmployeeBalanceRow) => employee.totalQuota > 0
  ? Math.round((employee.totalUsed / employee.totalQuota) * 100)
  : 0

const historyStatusSeverity = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized.includes('approv')) return 'success'
  if (normalized.includes('reject') || normalized.includes('cancel')) return 'secondary'
  if (normalized.includes('pending')) return 'warn'
  return 'info'
}

const resetFilters = () => {
  filters.value = { search: '', department: null, balance: null }
}

const onPageChange = (event: any) => {
  paginationOffset.value = event.first
}

const viewDetails = (employee: EmployeeBalanceRow) => {
  selectedEmployee.value = employee
  showDetailsModal.value = true
}

const viewHistory = async (employee: EmployeeBalanceRow) => {
  selectedEmployee.value = employee
  showHistoryModal.value = true
  historyLoading.value = true
  try {
    const response = await hrService.api.get(`/api/users/${employee.id}/leaves`, {
      params: { per_page: 50 }
    })
    const records = response.data?.data?.leaves?.data || response.data?.data?.leaves || []
    historyData.value = records.map((leave: any) => ({
      id: leave.id,
      type: leave.leave_type_label || leave.leave_type,
      status: leave.status_label || leave.status,
      date: leave.created_at_formatted || leave.created_at,
      details: `${leave.start_date_formatted || leave.start_date} - ${leave.end_date_formatted || leave.end_date}`,
    }))
  } catch (error) {
    historyData.value = []
  } finally {
    historyLoading.value = false
  }
}

const exportBalances = () => {
  console.log('Exporting balances...')
}

onMounted(fetchBalances)
</script>
