<!-- views/system/LeaveBalances.vue -->
<template>
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex gap-2">
                <Button label="Export" icon="pi pi-download" severity="info" outlined @click="exportBalances" />
                <Button label="Adjust Balance" icon="pi pi-plus" severity="info" @click="showAdjustModal = true" />
            </div>
        </div>
    
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-500">Total Employees</span>
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                        <i class="pi pi-users text-blue-500 text-sm"></i>
                    </div>
                </div>
                <div class="text-2xl font-semibold text-gray-800">{{ employeeCount }}</div>
                <div class="text-xs text-gray-400 mt-1">Active employees</div>
            </div>
    
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-500">Avg. Remaining</span>
                    <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center">
                        <i class="pi pi-chart-line text-green-500 text-sm"></i>
                    </div>
                </div>
                <div class="text-2xl font-semibold text-gray-800">{{ averageRemaining }} days</div>
                <div class="text-xs text-gray-400 mt-1">Per employee</div>
            </div>
    
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-500">Low Balance</span>
                    <div class="w-8 h-8 bg-yellow-50 rounded-lg flex items-center justify-center">
                        <i class="pi pi-exclamation-triangle text-yellow-500 text-sm"></i>
                    </div>
                </div>
                <div class="text-2xl font-semibold text-gray-800">{{ lowBalanceCount }}</div>
                <div class="text-xs text-gray-400 mt-1">Less than 5 days left</div>
            </div>
    
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
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
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex flex-wrap gap-3 items-center justify-between">
                <div class="flex gap-3 flex-wrap">
                    <div class="relative">
                        <IconField>
                            <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <InputText v-model="filters.search" placeholder="Search employee"
                                class="pl-8 rounded-lg w-64" />
                        </IconField>
    
                    </div>
    
                    <Select v-model="filters.department" :options="departments" showClear placeholder="All Departments"
                        class="rounded-lg w-48" />
    
                    <Select v-model="filters.balance" :options="balanceFilters" showClear placeholder="Balance Range"
                        class="rounded-lg w-48" />
                </div>
    
                <Button label="Reset Filters" icon="pi pi-filter-slash" severity="info" outlined @click="resetFilters" />
            </div>
        </div>
    
        <!-- Balances Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="text-left p-4 text-sm font-medium text-gray-600">Employee</th>
                            <th class="text-left p-4 text-sm font-medium text-gray-600">Department</th>
                            <th class="text-left p-4 text-sm font-medium text-gray-600" v-for="type in leaveTypes"
                                :key="type">
                                {{ type }}
                            </th>
                            <th class="text-left p-4 text-sm font-medium text-gray-600">Total Used</th>
                            <th class="text-left p-4 text-sm font-medium text-gray-600">Status</th>
                            <th class="text-left p-4 text-sm font-medium text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="emp in filteredBalances" :key="emp.id" class="hover:bg-gray-50/50 transition-colors">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <Avatar :label="getInitials(emp.name)" size="large"
                                        class="bg-blue-100 text-blue-600 font-medium" />
                                    <div>
                                        <div class="font-medium text-gray-800">{{ emp.name }}</div>
                                        <div class="text-xs text-gray-500">{{ emp.employeeId }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-sm text-gray-600">{{ emp.department }}</td>
                            <td v-for="type in leaveTypes" :key="type" class="p-4">
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium">{{ getBalance(emp, type) }} days</span>
                                        <Tag v-if="getBalance(emp, type) < 5" value="Low" severity="warning" size="small"
                                            rounded />
                                    </div>
                                    <ProgressBar :value="getUsagePercentage(emp, type)" :showValue="false"
                                        class="h-1 w-20 mt-1" />
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium">{{ emp.totalUsed }} days</span>
                                    <span class="text-xs text-gray-400">({{ Math.round((emp.totalUsed / emp.totalQuota) *
                                        100) }}%)</span>
                                </div>
                            </td>
                            <td class="p-4">
                                <Tag :value="getBalanceStatus(emp)" :severity="getBalanceSeverity(emp)" rounded />
                            </td>
                            <td class="p-4">
                                <div class="flex gap-2">
                                    <Button icon="pi pi-eye" text rounded severity="info" @click="viewDetails(emp)" />
                                    <Button icon="pi pi-pencil" text rounded severity="info" @click="adjustBalance(emp)" />
                                    <Button icon="pi pi-history" text rounded severity="info" @click="viewHistory(emp)" />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
    
            <!-- Pagination -->
            <div class="p-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ paginationStart }} to {{ paginationEnd }} of {{ filteredBalances.length }} entries
                </div>
                <Paginator v-model:first="paginationOffset" :rows="pageSize" :totalRecords="filteredBalances.length"
                    @page="onPageChange" template="PrevPageLink PageLinks NextPageLink" class="bg-transparent border-0" />
            </div>
        </div>
    
        <!-- Adjust Balance Modal -->
        <Dialog v-model:visible="showAdjustModal" modal :style="{ width: '450px' }" class="rounded-xl">
            <template #header>
                <div class="flex items-center gap-2 text-gray-700">
                    <i class="pi pi-sliders-h text-blue-500"></i>
                    <span class="font-semibold">Adjust Leave Balance</span>
                </div>
            </template>
    
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-2">Employee</label>
                    <Select v-model="adjustForm.employeeId" :options="employees" optionLabel="name" optionValue="id"
                        placeholder="Select employee" class="w-full" />
                </div>
    
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-2">Leave Type</label>
                    <Select v-model="adjustForm.leaveType" :options="leaveTypeOptions" optionLabel="label"
                        optionValue="value" placeholder="Select leave type" class="w-full" />
                </div>
    
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-2">Adjustment Type</label>
                    <div class="flex gap-4">
                        <div class="flex items-center gap-2">
                            <RadioButton v-model="adjustForm.adjustmentType" inputId="add" value="add" />
                            <label for="add" class="text-sm">Add Days</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <RadioButton v-model="adjustForm.adjustmentType" inputId="deduct" value="deduct" />
                            <label for="deduct" class="text-sm">Deduct Days</label>
                        </div>
                        <div class="flex items-center gap-2">
                            <RadioButton v-model="adjustForm.adjustmentType" inputId="set" value="set" />
                            <label for="set" class="text-sm">Set Balance</label>
                        </div>
                    </div>
                </div>
    
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-2">Days</label>
                    <InputNumber v-model="adjustForm.days" :min="0" :max="30" class="w-full" />
                </div>
    
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-2">Reason</label>
                    <Textarea v-model="adjustForm.reason" rows="2" class="w-full" placeholder="Reason for adjustment..." />
                </div>
            </div>
    
            <template #footer>
                <div class="flex gap-2 justify-end">
                    <Button label="Cancel" severity="secondary" outlined @click="closeAdjustModal" />
                    <Button label="Apply Adjustment" severity="info" @click="applyAdjustment" />
                </div>
            </template>
        </Dialog>
    
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
                <!-- Balance Cards -->
                <div class="grid grid-cols-2 gap-3">
                    <div v-for="type in leaveTypes" :key="type" class="bg-gray-50 rounded-lg p-3">
                        <div class="text-xs text-gray-500 mb-2">{{ type }} Leave</div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xl font-semibold">{{ getBalance(selectedEmployee, type) }}</span>
                            <span class="text-xs text-gray-400">/ 20 days</span>
                        </div>
                        <ProgressBar :value="getUsagePercentage(selectedEmployee, type)" :showValue="false" class="h-1.5" />
                    </div>
                </div>
    
                <!-- Recent History -->
                <div>
                    <h3 class="font-medium text-gray-700 mb-3">Recent Activity</h3>
                    <div class="space-y-2">
                        <div v-for="history in recentHistory" :key="history.id"
                            class="flex items-center gap-3 p-2 hover:bg-gray-50 rounded-lg">
                            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                                <i :class="history.icon" class="text-blue-500 text-xs"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium">{{ history.action }}</div>
                                <div class="text-xs text-gray-500">{{ history.date }}</div>
                            </div>
                            <Tag :value="history.days" severity="info" size="small" />
                        </div>
                    </div>
                </div>
    
                <!-- Yearly Summary -->
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
                            <div class="font-medium text-blue-600">{{ selectedEmployee.totalQuota -
                                selectedEmployee.totalUsed }} days</div>
                        </div>
                        <div>
                            <span class="text-gray-500">Carry Over</span>
                            <div class="font-medium">{{ selectedEmployee.carryOver || 0 }} days</div>
                        </div>
                    </div>
                </div>
            </div>
    
            <template #footer>
                <Button label="Close" severity="secondary" outlined @click="showDetailsModal = false" />
            </template>
        </Dialog>
    
        <!-- History Modal -->
        <Dialog v-model:visible="showHistoryModal" modal :style="{ width: '500px' }" class="rounded-xl"
            :header="`Leave History - ${selectedEmployee?.name}`">
            <div class="space-y-3 max-h-96 overflow-y-auto">
                <div v-for="item in fullHistory" :key="item.id"
                    class="border border-gray-100 rounded-lg p-3 hover:bg-gray-50">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="text-sm font-medium">{{ item.action }}</span>
                            <Tag :value="item.type" severity="info" size="small" class="ml-2" />
                        </div>
                        <span class="text-xs text-gray-400">{{ item.date }}</span>
                    </div>
                    <div class="text-sm text-gray-600">{{ item.details }}</div>
                    <div class="text-xs text-gray-400 mt-2">By: {{ item.performedBy }}</div>
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import RadioButton from 'primevue/radiobutton'
import Paginator from 'primevue/paginator'

// Types
interface Employee {
  id: number
  employeeId: string
  name: string
  department: string
  balances: {
    Annual: number
    Sick: number
    Emergency: number
  }
  totalUsed: number
  totalQuota: number
  carryOver?: number
}

interface BalanceHistory {
  id: number
  employeeId: number
  action: string
  type: string
  days: number
  date: string
  details: string
  performedBy: string
  icon: string
}

// State
const showAdjustModal = ref(false)
const showDetailsModal = ref(false)
const showHistoryModal = ref(false)
const selectedEmployee = ref<Employee | null>(null)
const paginationOffset = ref(0)
const pageSize = ref(10)

// Filters
const filters = ref({
  search: '',
  department: null,
  balance: null
})

// Form
const adjustForm = ref({
  employeeId: null,
  leaveType: null,
  adjustmentType: 'add',
  days: 0,
  reason: ''
})

// Mock Data
const employees = ref<Employee[]>([
  {
    id: 1,
    employeeId: 'EMP-001',
    name: 'John Cruz',
    department: 'Production',
    balances: { Annual: 15, Sick: 8, Emergency: 3 },
    totalUsed: 4,
    totalQuota: 26,
    carryOver: 2
  },
  {
    id: 2,
    employeeId: 'EMP-002',
    name: 'Maria Santos',
    department: 'Warehouse',
    balances: { Annual: 6, Sick: 2, Emergency: 1 },
    totalUsed: 11,
    totalQuota: 20
  },
  {
    id: 3,
    employeeId: 'EMP-003',
    name: 'Carlos Lim',
    department: 'Sales',
    balances: { Annual: 2, Sick: 0, Emergency: 0 },
    totalUsed: 18,
    totalQuota: 20
  },
  {
    id: 4,
    employeeId: 'EMP-004',
    name: 'Anna Reyes',
    department: 'Finance',
    balances: { Annual: 12, Sick: 4, Emergency: 2 },
    totalUsed: 2,
    totalQuota: 18
  },
  {
    id: 5,
    employeeId: 'EMP-005',
    name: 'Michael Tan',
    department: 'IT',
    balances: { Annual: 18, Sick: 5, Emergency: 3 },
    totalUsed: 4,
    totalQuota: 26
  }
])

const historyData = ref<BalanceHistory[]>([
  {
    id: 1,
    employeeId: 1,
    action: 'Leave Approved',
    type: 'Annual',
    days: 3,
    date: '2024-12-10',
    details: 'Annual leave approved for Dec 15-17',
    performedBy: 'HR Manager',
    icon: 'pi pi-check-circle'
  },
  {
    id: 2,
    employeeId: 1,
    action: 'Balance Adjusted',
    type: 'Annual',
    days: 2,
    date: '2024-11-15',
    details: 'Carry over from previous year',
    performedBy: 'Admin',
    icon: 'pi pi-sliders-h'
  }
])

// Options
const departments = ['All Departments', 'Production', 'Warehouse', 'Sales', 'Finance', 'IT', 'HR']
const leaveTypes = ['Annual', 'Sick', 'Emergency']
const balanceFilters = [
  { label: 'All Balances', value: null },
  { label: 'Low (0-5 days)', value: 'low' },
  { label: 'Medium (6-15 days)', value: 'medium' },
  { label: 'High (16+ days)', value: 'high' }
]

const leaveTypeOptions = [
  { label: 'Annual Leave', value: 'Annual' },
  { label: 'Sick Leave', value: 'Sick' },
  { label: 'Emergency Leave', value: 'Emergency' }
]

// Computed
const employeeCount = computed(() => employees.value.length)

const averageRemaining = computed(() => {
  const total = employees.value.reduce((sum, emp) => {
    const remaining = Object.values(emp.balances).reduce((a, b) => a + b, 0)
    return sum + remaining
  }, 0)
  return Math.round(total / employees.value.length)
})

const lowBalanceCount = computed(() => {
  return employees.value.filter(emp => {
    const total = Object.values(emp.balances).reduce((a, b) => a + b, 0)
    return total < 5
  }).length
})

const totalUsed = computed(() => {
  return employees.value.reduce((sum, emp) => sum + emp.totalUsed, 0)
})

const filteredBalances = computed(() => {
  let filtered = [...employees.value]

  // Search filter
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(emp =>
      emp.name.toLowerCase().includes(search) ||
      emp.employeeId.toLowerCase().includes(search)
    )
  }

  // Department filter
  if (filters.value.department && filters.value.department !== 'All Departments') {
    filtered = filtered.filter(emp => emp.department === filters.value.department)
  }

  // Balance filter
  if (filters.value.balance) {
    filtered = filtered.filter(emp => {
      const total = Object.values(emp.balances).reduce((a, b) => a + b, 0)
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

const paginationStart = computed(() => paginationOffset.value + 1)
const paginationEnd = computed(() => Math.min(paginationOffset.value + pageSize.value, filteredBalances.value.length))

const recentHistory = computed(() => {
  return historyData.value
    .filter(h => h.employeeId === selectedEmployee.value?.id)
    .slice(0, 3)
})

const fullHistory = computed(() => {
  return historyData.value.filter(h => h.employeeId === selectedEmployee.value?.id)
})

// Helper functions
const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getBalance = (employee: Employee, type: string) => {
  return employee.balances[type as keyof typeof employee.balances] || 0
}

const getUsagePercentage = (employee: Employee, type: string) => {
  const used = 20 - getBalance(employee, type)
  return (used / 20) * 100
}

const getBalanceStatus = (employee: Employee) => {
  const total = Object.values(employee.balances).reduce((a, b) => a + b, 0)
  if (total < 5) return 'Low Balance'
  if (total < 10) return 'Moderate'
  return 'Healthy'
}

const getBalanceSeverity = (employee: Employee) => {
  const total = Object.values(employee.balances).reduce((a, b) => a + b, 0)
  if (total < 5) return 'warning'
  if (total < 10) return 'info'
  return 'success'
}

// Actions
const resetFilters = () => {
  filters.value = { search: '', department: null, balance: null }
}

const onPageChange = (event: any) => {
  paginationOffset.value = event.first
}

const viewDetails = (employee: Employee) => {
  selectedEmployee.value = employee
  showDetailsModal.value = true
}

const adjustBalance = (employee: Employee) => {
  adjustForm.value.employeeId = employee.id
  showAdjustModal.value = true
}

const viewHistory = (employee: Employee) => {
  selectedEmployee.value = employee
  showHistoryModal.value = true
}

const closeAdjustModal = () => {
  showAdjustModal.value = false
  adjustForm.value = { employeeId: null, leaveType: null, adjustmentType: 'add', days: 0, reason: '' }
}

const applyAdjustment = () => {
  // Validate form
  if (!adjustForm.value.employeeId || !adjustForm.value.leaveType || adjustForm.value.days <= 0) {
    alert('Please fill all required fields')
    return
  }

  // Find employee
  const employee = employees.value.find(e => e.id === adjustForm.value.employeeId)
  if (!employee) return

  // Apply adjustment based on type
  switch (adjustForm.value.adjustmentType) {
    case 'add':
      employee.balances[adjustForm.value.leaveType] += adjustForm.value.days
      break
    case 'deduct':
      employee.balances[adjustForm.value.leaveType] = Math.max(0, employee.balances[adjustForm.value.leaveType] - adjustForm.value.days)
      break
    case 'set':
      employee.balances[adjustForm.value.leaveType] = adjustForm.value.days
      break
  }

  // Add to history
  historyData.value.unshift({
    id: historyData.value.length + 1,
    employeeId: employee.id,
    action: 'Balance Adjusted',
    type: adjustForm.value.leaveType,
    days: adjustForm.value.days,
    date: new Date().toISOString().split('T')[0],
    details: `${adjustForm.value.adjustmentType} ${adjustForm.value.days} days. Reason: ${adjustForm.value.reason || 'No reason provided'}`,
    performedBy: 'Current User',
    icon: 'pi pi-sliders-h'
  })

  closeAdjustModal()
}

const exportBalances = () => {
  console.log('Exporting balances...')
}
</script>