<!-- views/system/shifts/EmployeeShifts.vue -->
<template>
    <div class="p-6 max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <Button label="Back to Schedule" icon="pi pi-arrow-left" severity="info" @click="goBack" />
        </div>
    
        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex flex-wrap gap-3 items-center justify-between">
                <div class="flex gap-3 flex-wrap">
                    <div class="relative">
                        <IconField> <i
                                class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <InputText v-model="filters.search" placeholder="Search employee..."
                                class="pl-8 rounded-lg w-64" />
                        </IconField>
    
                    </div>
    
                    <Select v-model="filters.branch" :options="branches" optionLabel="label" optionValue="value"
                        placeholder="All Branches" class="rounded-lg w-48" showClear />
    
                    <Select v-model="filters.department" :options="departments" optionLabel="label" optionValue="value"
                        placeholder="All Departments" class="rounded-lg w-48" showClear />
    
                    <Select v-model="filters.shiftType" :options="shiftTypeFilters" optionLabel="label" optionValue="value"
                        placeholder="Shift Type" class="rounded-lg w-48" showClear />
                </div>
    
                <Button label="Reset" icon="pi pi-filter-slash" severity="info" outlined @click="resetFilters" />
            </div>
        </div>
    
        <!-- Tabs -->
        <div class="mb-6">
            <Tabs v-model:value="activeTab">
                <TabList>
                    <Tab value="branches">By Branch</Tab>
                    <Tab value="departments">By Department</Tab>
                    <Tab value="summary">Summary</Tab>
                </TabList>
    
                <TabPanels>
                    <!-- By Branch Panel -->
                    <TabPanel value="branches">
                        <div class="space-y-6">
                            <div v-for="branch in filteredBranches" :key="branch.value"
                                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="p-4 bg-gray-50/50 border-b border-gray-100">
                                    <h2 class="font-semibold text-gray-700">{{ branch.label }}</h2>
                                </div>
    
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="bg-gray-50/50">
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Employee</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Department</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Shift Type</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Schedule</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Days</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Status</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="emp in getEmployeesByBranch(branch.value)" :key="emp.id"
                                                class="hover:bg-gray-50/50">
                                                <td class="p-3">
                                                    <div class="flex items-center gap-2">
                                                        <Avatar :label="getInitials(emp.name)" size="normal"
                                                            class="bg-blue-100 text-blue-600" />
                                                        <div>
                                                            <div class="font-medium">{{ emp.name }}</div>
                                                            <div class="text-xs text-gray-500">{{ emp.employeeId }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-3 text-sm">{{ emp.department }}</td>
                                                <td class="p-3">
                                                    <Tag :value="emp.shiftType" :severity="getShiftSeverity(emp.shiftType)"
                                                        rounded />
                                                </td>
                                                <td class="p-3 text-sm">
                                                    {{ emp.shiftStart }} - {{ emp.shiftEnd }}
                                                </td>
                                                <td class="p-3">
                                                    <div class="flex gap-1">
                                                        <span v-for="day in emp.workingDays" :key="day"
                                                            class="w-6 h-6 text-xs flex items-center justify-center bg-gray-100 rounded">
                                                            {{ day }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="p-3">
                                                    <Tag :value="emp.status" :severity="getStatusSeverity(emp.status)"
                                                        rounded />
                                                </td>
                                                <td class="p-3">
                                                    <div class="flex gap-1">
                                                        <Button icon="pi pi-eye" text rounded severity="info" size="small"
                                                            @click="viewEmployeeShifts(emp)" />
                                                        <Button icon="pi pi-pencil" text rounded severity="info"
                                                            size="small" @click="editEmployeeShift(emp)" />
                                                        <Button icon="pi pi-history" text rounded severity="info"
                                                            size="small" @click="viewShiftHistory(emp)" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="getEmployeesByBranch(branch.value).length === 0">
                                                <td colspan="7" class="p-8 text-center text-gray-400">
                                                    No employees found in this branch
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
    
                    <!-- By Department Panel -->
                    <TabPanel value="departments">
                        <div class="space-y-6">
                            <div v-for="dept in filteredDepartments" :key="dept.value"
                                class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="p-4 bg-gray-50/50 border-b border-gray-100">
                                    <h2 class="font-semibold text-gray-700">{{ dept.label }}</h2>
                                </div>
    
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="bg-gray-50/50">
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Employee</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Branch</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Shift Type</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Schedule</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Days</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Status</th>
                                                <th class="text-left p-3 text-sm font-medium text-gray-600">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr v-for="emp in getEmployeesByDepartment(dept.value)" :key="emp.id"
                                                class="hover:bg-gray-50/50">
                                                <td class="p-3">
                                                    <div class="flex items-center gap-2">
                                                        <Avatar :label="getInitials(emp.name)" size="normal"
                                                            class="bg-blue-100 text-blue-600" />
                                                        <div>
                                                            <div class="font-medium">{{ emp.name }}</div>
                                                            <div class="text-xs text-gray-500">{{ emp.employeeId }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="p-3 text-sm">{{ getBranchLabel(emp.branch) }}</td>
                                                <td class="p-3">
                                                    <Tag :value="emp.shiftType" :severity="getShiftSeverity(emp.shiftType)"
                                                        rounded />
                                                </td>
                                                <td class="p-3 text-sm">
                                                    {{ emp.shiftStart }} - {{ emp.shiftEnd }}
                                                </td>
                                                <td class="p-3">
                                                    <div class="flex gap-1">
                                                        <span v-for="day in emp.workingDays" :key="day"
                                                            class="w-6 h-6 text-xs flex items-center justify-center bg-gray-100 rounded">
                                                            {{ day }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="p-3">
                                                    <Tag :value="emp.status" :severity="getStatusSeverity(emp.status)"
                                                        rounded />
                                                </td>
                                                <td class="p-3">
                                                    <div class="flex gap-1">
                                                        <Button icon="pi pi-eye" text rounded severity="info" size="small"
                                                            @click="viewEmployeeShifts(emp)" />
                                                        <Button icon="pi pi-pencil" text rounded severity="info"
                                                            size="small" @click="editEmployeeShift(emp)" />
                                                        <Button icon="pi pi-history" text rounded severity="info"
                                                            size="small" @click="viewShiftHistory(emp)" />
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr v-if="getEmployeesByDepartment(dept.value).length === 0">
                                                <td colspan="7" class="p-8 text-center text-gray-400">
                                                    No employees found in this department
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
    
                    <!-- Summary Panel -->
                    <TabPanel value="summary">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Shift Distribution -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="font-semibold text-gray-700 mb-4">Shift Distribution</h3>
                                <div class="space-y-4">
                                    <div v-for="type in shiftTypes" :key="type.name" class="space-y-2">
                                        <div class="flex justify-between text-sm">
                                            <span>{{ type.name }} Shift</span>
                                            <span class="font-medium">{{ getShiftCount(type.name) }} employees</span>
                                        </div>
                                        <ProgressBar :value="getShiftPercentage(type.name)" :showValue="false"
                                            class="h-2" />
                                    </div>
                                </div>
                            </div>
    
                            <!-- Department Summary -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="font-semibold text-gray-700 mb-4">Department Summary</h3>
                                <div class="space-y-3">
                                    <div v-for="dept in departments" :key="dept.value"
                                        class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                                        <span class="text-sm">{{ dept.label }}</span>
                                        <div class="flex items-center gap-3">
                                            <span class="text-sm font-medium">{{ getDepartmentCount(dept.value) }}</span>
                                            <Tag :value="getDepartmentShiftType(dept.value)" severity="info" rounded
                                                size="small" />
                                        </div>
                                    </div>
                                </div>
                            </div>
    
                            <!-- Branch Summary -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="font-semibold text-gray-700 mb-4">Branch Summary</h3>
                                <div class="space-y-3">
                                    <div v-for="branch in branches" :key="branch.value"
                                        class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                                        <span class="text-sm">{{ branch.label }}</span>
                                        <div>
                                            <span class="text-sm font-medium">{{ getBranchCount(branch.value) }}</span>
                                            <span class="text-xs text-gray-400 ml-1">employees</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
    
                            <!-- Quick Stats -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                                <h3 class="font-semibold text-gray-700 mb-4">Quick Stats</h3>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                                        <div class="text-2xl font-semibold text-blue-600">{{ totalEmployees }}</div>
                                        <div class="text-xs text-gray-500">Total Employees</div>
                                    </div>
                                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                                        <div class="text-2xl font-semibold text-green-600">{{ activeShifts }}</div>
                                        <div class="text-xs text-gray-500">Active Shifts</div>
                                    </div>
                                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                                        <div class="text-2xl font-semibold text-orange-600">{{ nightShiftCount }}</div>
                                        <div class="text-xs text-gray-500">Night Shift</div>
                                    </div>
                                    <div class="text-center p-3 bg-gray-50 rounded-lg">
                                        <div class="text-2xl font-semibold text-purple-600">{{ partTimeCount }}</div>
                                        <div class="text-xs text-gray-500">Part Time</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    
        <!-- Employee Shift Details Modal -->
        <Dialog v-model:visible="showEmployeeModal" modal :style="{ width: '600px' }" class="rounded-xl">
            <template #header>
                <div class="flex items-center gap-3">
                    <Avatar :label="getInitials(selectedEmployee?.name || '')" size="large"
                        class="bg-blue-100 text-blue-600" />
                    <div>
                        <div class="font-semibold text-gray-800">{{ selectedEmployee?.name }}</div>
                        <div class="text-sm text-gray-500">{{ selectedEmployee?.department }} • {{
                            selectedEmployee?.employeeId }}</div>
                    </div>
                </div>
            </template>
    
            <div v-if="selectedEmployee" class="space-y-4">
                <!-- Current Shift -->
                <div class="border border-gray-100 rounded-lg p-4">
                    <h3 class="font-medium text-gray-700 mb-3">Current Assignment</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs text-gray-500">Shift Type</div>
                            <Tag :value="selectedEmployee.shiftType"
                                :severity="getShiftSeverity(selectedEmployee.shiftType)" class="mt-1" />
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Schedule</div>
                            <div class="text-sm font-medium">{{ selectedEmployee.shiftStart }} - {{
                                selectedEmployee.shiftEnd }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Working Days</div>
                            <div class="flex gap-1 mt-1">
                                <span v-for="day in selectedEmployee.workingDays" :key="day"
                                    class="w-6 h-6 text-xs flex items-center justify-center bg-blue-50 text-blue-600 rounded">
                                    {{ day }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500">Status</div>
                            <Tag :value="selectedEmployee.status" :severity="getStatusSeverity(selectedEmployee.status)"
                                class="mt-1" />
                        </div>
                    </div>
                </div>
    
                <!-- Upcoming Shifts -->
                <div>
                    <h3 class="font-medium text-gray-700 mb-3">Upcoming Shifts</h3>
                    <div class="space-y-2">
                        <div v-for="shift in upcomingShifts" :key="shift.id"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <div class="text-sm font-medium">{{ shift.date }}</div>
                                <div class="text-xs text-gray-500">{{ shift.type }} • {{ shift.start }} - {{ shift.end }}
                                </div>
                            </div>
                            <Button icon="pi pi-pencil" text rounded severity="info" size="small" />
                        </div>
                        <div v-if="upcomingShifts.length === 0" class="text-center text-gray-400 py-4">
                            No upcoming shifts
                        </div>
                    </div>
                </div>
    
                <!-- Shift History Summary -->
                <div class="border-t border-gray-100 pt-4">
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-lg font-semibold text-gray-800">12</div>
                            <div class="text-xs text-gray-500">This Month</div>
                        </div>
                        <div>
                            <div class="text-lg font-semibold text-gray-800">48</div>
                            <div class="text-xs text-gray-500">Total Shifts</div>
                        </div>
                        <div>
                            <div class="text-lg font-semibold text-gray-800">2</div>
                            <div class="text-xs text-gray-500">OT Hours</div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import ProgressBar from 'primevue/progressbar'

const router = useRouter()
const activeTab = ref('branches')
const showEmployeeModal = ref(false)
const selectedEmployee = ref<any>(null)

// Filters
const filters = ref({
  search: '',
  branch: null,
  department: null,
  shiftType: null
})

// Mock Data
const branches = ref([
  { label: 'Main Branch', value: 'main' },
  { label: 'North Branch', value: 'north' },
  { label: 'South Branch', value: 'south' },
  { label: 'East Branch', value: 'east' }
])

const departments = ref([
  { label: 'Production', value: 'production' },
  { label: 'Warehouse', value: 'warehouse' },
  { label: 'Sales', value: 'sales' },
  { label: 'Finance', value: 'finance' },
  { label: 'HR', value: 'hr' },
  { label: 'IT', value: 'it' }
])

const shiftTypeFilters = ref([
  { label: 'Morning Shift', value: 'Morning' },
  { label: 'Mid Shift', value: 'Mid' },
  { label: 'Evening Shift', value: 'Evening' },
  { label: 'Night Shift', value: 'Night' }
])

const shiftTypes = ref([
  { name: 'Morning', color: 'blue', count: 8 },
  { name: 'Mid', color: 'orange', count: 5 },
  { name: 'Evening', color: 'purple', count: 4 },
  { name: 'Night', color: 'indigo', count: 3 }
])

const employees = ref([
  {
    id: 1,
    employeeId: 'EMP-001',
    name: 'John Smith',
    department: 'production',
    departmentLabel: 'Production',
    branch: 'main',
    shiftType: 'Morning',
    shiftStart: '08:00',
    shiftEnd: '17:00',
    workingDays: ['M', 'T', 'W', 'T', 'F'],
    status: 'Active'
  },
  {
    id: 2,
    employeeId: 'EMP-002',
    name: 'Sarah Johnson',
    department: 'warehouse',
    departmentLabel: 'Warehouse',
    branch: 'north',
    shiftType: 'Mid',
    shiftStart: '12:00',
    shiftEnd: '21:00',
    workingDays: ['M', 'T', 'W', 'T', 'F'],
    status: 'Active'
  },
  {
    id: 3,
    employeeId: 'EMP-003',
    name: 'Michael Chen',
    department: 'sales',
    departmentLabel: 'Sales',
    branch: 'south',
    shiftType: 'Evening',
    shiftStart: '15:00',
    shiftEnd: '00:00',
    workingDays: ['T', 'W', 'T', 'F', 'S'],
    status: 'Active'
  },
  {
    id: 4,
    employeeId: 'EMP-004',
    name: 'Emily Davis',
    department: 'finance',
    departmentLabel: 'Finance',
    branch: 'main',
    shiftType: 'Morning',
    shiftStart: '09:00',
    shiftEnd: '18:00',
    workingDays: ['M', 'T', 'W', 'T', 'F'],
    status: 'Active'
  },
  {
    id: 5,
    employeeId: 'EMP-005',
    name: 'James Wilson',
    department: 'it',
    departmentLabel: 'IT',
    branch: 'east',
    shiftType: 'Night',
    shiftStart: '22:00',
    shiftEnd: '07:00',
    workingDays: ['M', 'T', 'W', 'T', 'F'],
    status: 'Active'
  }
])

const upcomingShifts = ref([
  { id: 1, date: '2024-12-18', type: 'Morning', start: '08:00', end: '17:00' },
  { id: 2, date: '2024-12-19', type: 'Morning', start: '08:00', end: '17:00' },
  { id: 3, date: '2024-12-20', type: 'Morning', start: '08:00', end: '17:00' }
])

// Computed
const filteredBranches = computed(() => {
  return branches.value.filter(b => {
    if (filters.value.branch && b.value !== filters.value.branch) return false
    return true
  })
})

const filteredDepartments = computed(() => {
  return departments.value.filter(d => {
    if (filters.value.department && d.value !== filters.value.department) return false
    return true
  })
})

const totalEmployees = computed(() => employees.value.length)

const activeShifts = computed(() => {
  return employees.value.filter(e => e.status === 'Active').length
})

const nightShiftCount = computed(() => {
  return employees.value.filter(e => e.shiftType === 'Night').length
})

const partTimeCount = computed(() => {
  return employees.value.filter(e => e.shiftType === 'Evening').length
})

// Methods
const getInitials = (name: string) => {
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getBranchLabel = (branchValue: string) => {
  const branch = branches.value.find(b => b.value === branchValue)
  return branch?.label || branchValue
}

const getEmployeesByBranch = (branchValue: string) => {
  let filtered = employees.value.filter(e => e.branch === branchValue)

  // Apply search filter
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(e =>
      e.name.toLowerCase().includes(search) ||
      e.employeeId.toLowerCase().includes(search)
    )
  }

  // Apply department filter
  if (filters.value.department) {
    filtered = filtered.filter(e => e.department === filters.value.department)
  }

  // Apply shift type filter
  if (filters.value.shiftType) {
    filtered = filtered.filter(e => e.shiftType === filters.value.shiftType)
  }

  return filtered
}

const getEmployeesByDepartment = (deptValue: string) => {
  let filtered = employees.value.filter(e => e.department === deptValue)

  // Apply search filter
  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(e =>
      e.name.toLowerCase().includes(search) ||
      e.employeeId.toLowerCase().includes(search)
    )
  }

  // Apply branch filter
  if (filters.value.branch) {
    filtered = filtered.filter(e => e.branch === filters.value.branch)
  }

  // Apply shift type filter
  if (filters.value.shiftType) {
    filtered = filtered.filter(e => e.shiftType === filters.value.shiftType)
  }

  return filtered
}

const getShiftSeverity = (type: string) => {
  const map: Record<string, string> = {
    'Morning': 'info',
    'Mid': 'warning',
    'Evening': 'help',
    'Night': 'secondary'
  }
  return map[type] || 'info'
}

const getStatusSeverity = (status: string) => {
  return status === 'Active' ? 'success' : 'danger'
}

const getShiftCount = (type: string) => {
  return employees.value.filter(e => e.shiftType === type).length
}

const getShiftPercentage = (type: string) => {
  const count = getShiftCount(type)
  return (count / employees.value.length) * 100
}

const getDepartmentCount = (deptValue: string) => {
  return employees.value.filter(e => e.department === deptValue).length
}

const getDepartmentShiftType = (deptValue: string) => {
  const deptEmployees = employees.value.filter(e => e.department === deptValue)
  if (deptEmployees.length === 0) return 'No shifts'

  const shiftCounts = deptEmployees.reduce((acc, e) => {
    acc[e.shiftType] = (acc[e.shiftType] || 0) + 1
    return acc
  }, {} as Record<string, number>)

  const mostCommon = Object.entries(shiftCounts).sort((a, b) => b[1] - a[1])[0]
  return mostCommon ? mostCommon[0] : 'Mixed'
}

const getBranchCount = (branchValue: string) => {
  return employees.value.filter(e => e.branch === branchValue).length
}

const resetFilters = () => {
  filters.value = { search: '', branch: null, department: null, shiftType: null }
}

const viewEmployeeShifts = (emp: any) => {
  selectedEmployee.value = emp
  showEmployeeModal.value = true
}

const editEmployeeShift = (emp: any) => {
  router.push(`/hr/shifts/edit/${emp.id}`)
}

const viewShiftHistory = (emp: any) => {
  router.push(`/hr/shifts/history/${emp.id}`)
}

const goBack = () => {
  router.push('/hr/shifts')
}
</script>