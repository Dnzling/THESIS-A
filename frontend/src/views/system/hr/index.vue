<!-- views/system/HRIndex.vue -->
<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 bg-white p-4 rounded-xl box-shadow">
      <div>
        <h1 class="text-2xl font-semibold">Welcome back, {{firstname}}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ currentDate }}</p>
      </div>
      <div class="flex gap-2 w-full sm:w-auto">
        <Button label="Reports" icon="pi pi-download" severity="info" size="small" class="flex-1 sm:flex-none" @click="generateReports" />
      </div>
    </div>
  
    <!-- Stats Cards - More Compact -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
      <!-- Total Employees -->
      <div class="bg-white p-4 rounded-xl box-shadow">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total</span>
          <i class="pi pi-users text-blue-500 text-sm"></i>
        </div>
        <p class="text-2xl font-bold">{{ hrStats.totalEmployees }}</p>
        <p class="text-xs text-green-600 mt-1">↑ {{ hrStats.activeEmployees }} active</p>
      </div>

      <!-- Present Today -->
      <div class="bg-white p-4 rounded-xl box-shadow">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Present</span>
          <i class="pi pi-user-check text-green-500 text-sm"></i>
        </div>
        <p class="text-2xl font-bold">{{ attendanceStats.present }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ attendanceStats.presentPercentage }}% attendance</p>
      </div>

      <!-- On Leave -->
      <div class="bg-white p-4 rounded-xl box-shadow">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">On Leave</span>
          <i class="pi pi-calendar-times text-yellow-500 text-sm"></i>
        </div>
        <p class="text-2xl font-bold">{{ hrStats.dayOff }}</p>
        <p class="text-xs text-gray-500 mt-1">Sick: {{ hrStats.sickLeave }}</p>
      </div>

      <!-- Pending -->
      <div class="bg-white p-4 rounded-xl box-shadow">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pending</span>
          <i class="pi pi-clock text-orange-500 text-sm"></i>
        </div>
        <p class="text-2xl font-bold">{{ pendingRequests.length }}</p>
        <p class="text-xs text-gray-500 mt-1">awaiting approval</p>
      </div>
    </div>
  
  
    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Attendance Meter & Leave Summary -->
      <div class="space-y-4">
        <!-- Attendance Rate Card -->
        <div class="bg-white p-4 rounded-xl box-shadow">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold">Attendance Rate</h3>
            <Tag :value="`${Math.round((hrStats.onTime / hrStats.totalEmployees)*100)}%`" severity="success" size="small" />
          </div>
          <MeterGroup :value="meterValues" :max="100" />
          <div class="grid grid-cols-3 gap-2 mt-3 text-center text-xs">
            <div>
              <p class="font-medium text-gray-900">{{ hrStats.onTime }}</p>
              <p class="text-gray-500">On Time</p>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ hrStats.dayOff }}</p>
              <p class="text-gray-500">Day Off</p>
            </div>
            <div>
              <p class="font-medium text-gray-900">{{ hrStats.sickLeave }}</p>
              <p class="text-gray-500">Sick</p>
            </div>
          </div>
        </div>

        <!-- Leave Balance Compact -->
        <div class="bg-white p-4 rounded-xl box-shadow">
          <h3 class="text-sm font-semibold mb-3">Leave Balances</h3>
          <div class="space-y-2">
            <div v-for="leave in leaveBalancesCompact" :key="leave.name" class="flex items-center gap-2">
              <i :class="leave.icon" :style="{ color: leave.color }" class="text-sm"></i>
              <div class="flex-1">
                <div class="flex justify-between text-xs mb-1">
                  <span>{{ leave.name }}</span>
                  <span class="font-medium">{{ leave.used }}/{{ leave.total }}</span>
                </div>
                <ProgressBar :value="(leave.used/leave.total)*100" :showValue="false" class="h-1.5" />
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Income Chart - Simplified -->
      <div class="bg-white p-4 rounded-xl box-shadow lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
          <div>
            <h3 class="text-sm font-semibold">Income Trend</h3>
            <p class="text-xs text-gray-500">2024 vs 2025</p>
          </div>
          <div class="flex gap-3">
            <div class="flex items-center gap-1">
              <span class="w-2 h-2 rounded-full bg-blue-500"></span>
              <span class="text-xs">2024</span>
            </div>
            <div class="flex items-center gap-1">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <span class="text-xs">2025</span>
            </div>
          </div>
        </div>
        <Chart type="line" :data="chartData" :options="chartOptions" class="h-65" />
        <div class="grid grid-cols-3 gap-2 mt-3 text-center">
          <div class="bg-blue-50 p-2 rounded">
            <p class="text-xs text-gray-500">2024</p>
            <p class="text-sm font-bold">${{ formatAbbr(total2024) }}</p>
          </div>
          <div class="bg-emerald-50 p-2 rounded">
            <p class="text-xs text-gray-500">2025</p>
            <p class="text-sm font-bold">${{ formatAbbr(total2025) }}</p>
          </div>
          <div class="bg-gray-50 p-2 rounded">
            <p class="text-xs text-gray-500">Growth</p>
            <p class="text-sm font-bold text-emerald-600">+{{ growthPercentage }}%</p>
          </div>
        </div>
      </div>
    </div>
  
    <!-- Second Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Pending Approvals -->
      <div class="bg-white p-4 rounded-xl box-shadow">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-semibold">Pending Approvals</h3>
          <Badge :value="pendingRequests.length" severity="warning" size="small" />
        </div>
        <div class="space-y-2">
          <div v-for="request in pendingRequests.slice(0, 3)" :key="request.id" class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
            <div class="flex items-center gap-2">
              <Avatar :label="getInitials(request.employee)" size="small" shape="circle" class="bg-blue-100 text-blue-800 text-xs" />
              <div>
                <p class="text-xs font-medium">{{ request.employee }}</p>
                <p class="text-xs text-gray-500">{{ request.type }}</p>
              </div>
            </div>
            <div class="flex gap-1">
              <Button icon="pi pi-check" size="small" rounded text severity="success" @click="approveRequest(request)" />
              <Button icon="pi pi-times" size="small" rounded text severity="danger" @click="rejectRequest(request)" />
            </div>
          </div>
          <Button label="View all" link size="small" class="w-full text-xs" @click="viewAllActivities" />
        </div>
      </div>

      <!-- Recent Activities -->
      <div class="bg-white p-4 rounded-xl box-shadow">
        <h3 class="text-sm font-semibold mb-3">Recent Activity</h3>
        <div class="space-y-2">
          <div v-for="activity in recentActivities.slice(0, 3)" :key="activity.id" class="flex items-start gap-2 p-2 hover:bg-gray-50 rounded">
            <div :class="`w-6 h-6 rounded-full flex items-center justify-center ${activity.iconBg}`">
              <i :class="`${activity.icon} text-xs ${activity.iconColor}`"></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium truncate">{{ activity.description }}</p>
              <p class="text-xs text-gray-500">{{ activity.time }}</p>
            </div>
            <Tag :value="activity.status" :severity="getActivityStatusSeverity(activity.status)" size="small" class="text-xs" />
          </div>
        </div>
      </div>

      <!-- Upcoming Birthdays -->
      <div class="bg-white p-4 rounded-xl box-shadow">
        <h3 class="text-sm font-semibold mb-3">🎂 Upcoming Birthdays</h3>
        <div class="space-y-2">
          <div v-for="emp in upcomingBirthdays.slice(0, 3)" :key="emp.id" class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded">
            <Avatar :label="getInitials(emp.name)" size="small" shape="circle" class="bg-pink-100 text-pink-800 text-xs" />
            <div class="flex-1">
              <p class="text-xs font-medium">{{ emp.name }}</p>
              <p class="text-xs text-gray-500">{{ formatBirthdayDate(emp.birthday) }}</p>
            </div>
            <Tag :value="`${emp.daysUntil}d`" severity="info" size="small" class="text-xs" />
          </div>
        </div>
      </div>
    </div>
  
    <!-- Employee Table - Compact -->
    <div class="bg-white rounded-xl box-shadow overflow-hidden">
      <div class="p-4 border-b">
        <div class="flex flex-col sm:flex-row gap-2 justify-between items-start sm:items-center">
          <h3 class="text-sm font-semibold">Employee Directory</h3>
          <div class="flex gap-2 w-full sm:w-auto">
            <IconField iconPosition="left" class="w-full sm:w-auto">
              <InputIcon class="pi pi-search text-xs" />
              <InputText v-model="employeeSearch" placeholder="Search..." class="w-full sm:w-48 h-8 text-sm" />
            </IconField>
            <Select v-model="employeeFilter" :options="employeeFilterOptions" optionLabel="name" placeholder="Filter" class="w-24 h-8 text-sm" />
          </div>
        </div>
      </div>

      <!-- Mobile Card View (visible on small screens) -->
      <div class="block sm:hidden p-2">
        <div v-for="emp in filteredEmployees.slice(0, 3)" :key="emp.id" class="border-b last:border-0 py-2">
          <div class="flex items-center gap-2">
            <Avatar :label="getInitials(emp.name)" size="normal" shape="circle" class="bg-blue-100 text-blue-800" />
            <div class="flex-1">
              <div class="flex items-center justify-between">
                <p class="text-sm font-medium">{{ emp.name }}</p>
                <Tag :value="emp.status" :severity="getEmployeeStatusSeverity(emp.status)" size="small" class="text-xs" />
              </div>
              <p class="text-xs text-gray-500">{{ emp.position }}</p>
              <p class="text-xs text-gray-400">{{ emp.employeeId }}</p>
            </div>
          </div>
        </div>
        <Button label="View all employees" link size="small" class="w-full mt-2 text-xs" @click="router.push('/hr/employees')" />
      </div>

      <!-- Desktop Table (hidden on small screens) -->
      <DataTable :value="filteredEmployees" dataKey="id" :rows="3" :paginator="false" class="hidden sm:block text-sm">
        <Column field="id" header="ID" sortable style="width: 5%">
          <template #body="slotProps">
            <span class="text-xs font-medium">{{ slotProps.data.id }}</span>
          </template>
        </Column>
        <Column field="name" header="Employee" sortable style="width: 25%">
          <template #body="slotProps">
            <div class="flex items-center gap-2">
              <Avatar :label="getInitials(slotProps.data.name)" size="small" shape="circle" class="bg-blue-100 text-blue-800 text-xs" />
              <div>
                <p class="text-xs font-medium">{{ slotProps.data.name }}</p>
                <p class="text-xs text-gray-500">{{ slotProps.data.position }}</p>
              </div>
            </div>
          </template>
        </Column>
        <Column field="department" header="Department" sortable style="width: 20%">
          <template #body="slotProps">
            <Tag :value="slotProps.data.department" severity="info" size="small" class="text-xs" />
          </template>
        </Column>
        <Column field="status" header="Status" sortable style="width: 15%">
          <template #body="slotProps">
            <Tag :value="slotProps.data.status" :severity="getEmployeeStatusSeverity(slotProps.data.status)" size="small" class="text-xs" />
          </template>
        </Column>
        <Column header="Actions" style="width: 15%">
          <template #body="slotProps">
            <div class="flex gap-1">
              <Button icon="pi pi-eye" size="small" text rounded severity="info" @click="viewEmployee(slotProps.data)" />
              <Button icon="pi pi-pencil" size="small" text rounded severity="secondary" @click="editEmployee(slotProps.data)" />
            </div>
          </template>
        </Column>
      </DataTable>
      
      <div class="p-2 border-t text-center sm:hidden">
        <span class="text-xs text-gray-500">{{ filteredEmployees.length }} total employees</span>
      </div>
    </div>
  
    <!-- Add Employee Dialog - Compact -->
    <Dialog v-model:visible="showAddEmployeeDialog" header="Add Employee" :style="{ width: '90%', maxWidth: '500px' }" :modal="true" class="p-fluid">
      <div class="space-y-3">
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-medium mb-1">First Name *</label>
            <InputText v-model="newEmployee.firstName" placeholder="First name" class="text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium mb-1">Last Name *</label>
            <InputText v-model="newEmployee.lastName" placeholder="Last name" class="text-sm" />
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium mb-1">Email *</label>
          <InputText v-model="newEmployee.email" placeholder="Email" class="text-sm" />
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-medium mb-1">Position</label>
            <Select v-model="newEmployee.position" :options="positionOptions" optionLabel="name" placeholder="Select" class="text-sm" />
          </div>
          <div>
            <label class="block text-xs font-medium mb-1">Department</label>
            <Select v-model="newEmployee.department" :options="departmentOptions" optionLabel="name" placeholder="Select" class="text-sm" />
          </div>
        </div>
      </div>
      <template #footer>
        <div class="flex gap-2 justify-end">
          <Button label="Cancel" severity="secondary" size="small" @click="showAddEmployeeDialog = false" />
          <Button label="Add" size="small" @click="addEmployee" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../../../stores/auth'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import Avatar from 'primevue/avatar'
import ProgressBar from 'primevue/progressbar'
import Dialog from 'primevue/dialog'
import Badge from 'primevue/badge'
import MeterGroup from 'primevue/metergroup'
import Chart from 'primevue/chart'

const router = useRouter()

// State
const showAddEmployeeDialog = ref(false)
const employeeSearch = ref('')
const employeeFilter = ref(null)
const authStore = useAuthStore()

const firstname = ref(authStore.user.first_name)

const hrStats = ref({
  totalEmployees: 150,
  activeEmployees: 142,
  onTime: 120,
  dayOff: 15,
  sickLeave: 7
});

const currentDate = computed(() => {
  return new Date().toLocaleDateString('en-US', { 
    weekday: 'long', 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric' 
  })
})

// Compact leave balances
const leaveBalancesCompact = computed(() => [
  { name: 'Vacation', used: leaveBalance.value.vacation.used, total: leaveBalance.value.vacation.total, icon: 'pi pi-sun', color: '#3b82f6' },
  { name: 'Sick', used: leaveBalance.value.sick.used, total: leaveBalance.value.sick.total, icon: 'pi pi-heart', color: '#ef4444' },
  { name: 'Emergency', used: leaveBalance.value.emergency.used, total: leaveBalance.value.emergency.total, icon: 'pi pi-exclamation-triangle', color: '#f59e0b' }
])

// Format numbers in abbreviated form (e.g., 45K instead of 45,000)
const formatAbbr = (num) => {
  if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M'
  if (num >= 1000) return (num / 1000).toFixed(1) + 'K'
  return num.toString()
}

const meterValues = computed(() => {
  const total = hrStats.value.onTime + hrStats.value.dayOff + hrStats.value.sickLeave;

  return [
    { label: 'On Time', value: Math.round((hrStats.value.onTime / total) * 100), color: '#462FA1' },
    { label: 'Day Off', value: Math.round((hrStats.value.dayOff / total) * 100), color: '#eab308' },
    { label: 'Sick Leave', value: Math.round((hrStats.value.sickLeave / total) * 100), color: '#ef4444' }
  ];
});

// Attendance Stats
const attendanceStats = ref({
  present: 42,
  late: 3,
  absent: 3,
  presentPercentage: 87.5,
  latePercentage: 6.25,
  absentPercentage: 6.25
})

// Employee Data
const employees = ref([
  {
    id: 1,
    name: 'John Smith',
    employeeId: 'EMP-001',
    position: 'Store Manager',
    department: 'Operations',
    status: 'Active',
    hireDate: '2022-03-15',
    email: 'john.smith@company.com',
    phone: '+639123456789'
  },
  {
    id: 2,
    name: 'Sarah Johnson',
    employeeId: 'EMP-002',
    position: 'Sales Supervisor',
    department: 'Sales',
    status: 'Active',
    hireDate: '2022-06-20',
    email: 'sarah.j@company.com',
    phone: '+639234567890'
  },
  {
    id: 3,
    name: 'Michael Chen',
    employeeId: 'EMP-003',
    position: 'Inventory Specialist',
    department: 'Warehouse',
    status: 'Active',
    hireDate: '2023-01-10',
    email: 'michael.c@company.com',
    phone: '+639345678901'
  },
  {
    id: 4,
    name: 'Lisa Rodriguez',
    employeeId: 'EMP-004',
    position: 'HR Manager',
    department: 'Human Resources',
    status: 'Active',
    hireDate: '2021-08-05',
    email: 'lisa.r@company.com',
    phone: '+639456789012'
  },
  {
    id: 5,
    name: 'David Wilson',
    employeeId: 'EMP-005',
    position: 'Accountant',
    department: 'Finance',
    status: 'On Leave',
    hireDate: '2022-11-30',
    email: 'david.w@company.com',
    phone: '+639567890123'
  },
  {
    id: 6,
    name: 'Emma Garcia',
    employeeId: 'EMP-006',
    position: 'Marketing Officer',
    department: 'Marketing',
    status: 'Active',
    hireDate: '2023-02-14',
    email: 'emma.g@company.com',
    phone: '+639678901234'
  },
  {
    id: 7,
    name: 'Robert Kim',
    employeeId: 'EMP-007',
    position: 'IT Support',
    department: 'IT',
    status: 'Active',
    hireDate: '2022-09-25',
    email: 'robert.k@company.com',
    phone: '+639789012345'
  },
  {
    id: 8,
    name: 'Maria Santos',
    employeeId: 'EMP-008',
    position: 'Purchasing Officer',
    department: 'Procurement',
    status: 'Active',
    hireDate: '2021-12-01',
    email: 'maria.s@company.com',
    phone: '+639890123456'
  }
])

// New Employee
const newEmployee = ref({
  firstName: '',
  lastName: '',
  position: null,
  department: null,
  email: '',
  phone: '',
  hireDate: null,
  salary: 0,
  address: ''
})

// Filter Options
const employeeFilterOptions = ref([
  { name: 'All Employees', value: 'all' },
  { name: 'Active Only', value: 'active' },
  { name: 'On Leave', value: 'leave' },
  { name: 'By Department', value: 'department' }
])

const positionOptions = ref([
  { name: 'Store Manager', value: 'store-manager' },
  { name: 'Sales Supervisor', value: 'sales-supervisor' },
  { name: 'Sales Associate', value: 'sales-associate' },
  { name: 'Inventory Specialist', value: 'inventory-specialist' },
  { name: 'Warehouse Staff', value: 'warehouse-staff' },
  { name: 'HR Manager', value: 'hr-manager' },
  { name: 'Accountant', value: 'accountant' },
  { name: 'Marketing Officer', value: 'marketing-officer' },
  { name: 'IT Support', value: 'it-support' },
  { name: 'Purchasing Officer', value: 'purchasing-officer' }
])

const departmentOptions = ref([
  { name: 'Operations', value: 'operations' },
  { name: 'Sales', value: 'sales' },
  { name: 'Warehouse', value: 'warehouse' },
  { name: 'Human Resources', value: 'hr' },
  { name: 'Finance', value: 'finance' },
  { name: 'Marketing', value: 'marketing' },
  { name: 'IT', value: 'it' },
  { name: 'Procurement', value: 'procurement' }
])

// Upcoming Holidays
const upcomingHolidays = ref([
  {
    id: 1,
    name: 'New Year\'s Day',
    date: '2024-01-01',
    type: 'Regular Holiday'
  },
  {
    id: 2,
    name: 'Chinese New Year',
    date: '2024-02-10',
    type: 'Special Holiday'
  },
  {
    id: 3,
    name: 'Good Friday',
    date: '2024-03-29',
    type: 'Regular Holiday'
  },
  {
    id: 4,
    name: 'Independence Day',
    date: '2024-06-12',
    type: 'Regular Holiday'
  }
])

// Upcoming Birthdays
const upcomingBirthdays = ref([
  {
    id: 1,
    name: 'John Smith',
    position: 'Store Manager',
    birthday: '1990-01-20',
    daysUntil: 3
  },
  {
    id: 2,
    name: 'Sarah Johnson',
    position: 'Sales Supervisor',
    birthday: '1988-01-22',
    daysUntil: 5
  },
  {
    id: 3,
    name: 'Michael Chen',
    position: 'Inventory Specialist',
    birthday: '1992-01-25',
    daysUntil: 8
  },
  {
    id: 4,
    name: 'Lisa Rodriguez',
    position: 'HR Manager',
    birthday: '1985-01-28',
    daysUntil: 11
  }
])

// Leave Balance
const leaveBalance = ref({
  vacation: { used: 8, total: 15 },
  sick: { used: 3, total: 10 },
  emergency: { used: 1, total: 5 }
})

// Recent Activities
const recentActivities = ref([
  {
    id: 1,
    description: 'New employee onboarding completed',
    employee: 'John Smith',
    time: 'Today, 10:30 AM',
    status: 'Completed',
    icon: 'pi pi-user-plus',
    iconColor: 'text-green-600',
    iconBg: 'bg-green-100'
  },
  {
    id: 2,
    description: 'Leave request submitted',
    employee: 'Sarah Johnson',
    time: 'Today, 9:45 AM',
    status: 'Pending',
    icon: 'pi pi-calendar',
    iconColor: 'text-yellow-600',
    iconBg: 'bg-yellow-100'
  },
  {
    id: 3,
    description: 'Performance review scheduled',
    employee: 'Michael Chen',
    time: 'Yesterday, 3:15 PM',
    status: 'Scheduled',
    icon: 'pi pi-chart-bar',
    iconColor: 'text-blue-600',
    iconBg: 'bg-blue-100'
  },
  {
    id: 4,
    description: 'Salary adjustment approved',
    employee: 'David Wilson',
    time: 'Jan 12, 2:30 PM',
    status: 'Approved',
    icon: 'pi pi-money-bill',
    iconColor: 'text-green-600',
    iconBg: 'bg-green-100'
  }
])

// Pending Requests
const pendingRequests = ref([
  {
    id: 1,
    employee: 'Robert Kim',
    type: 'Leave Request',
    date: 'Jan 15-17, 2024',
    days: 3
  },
  {
    id: 2,
    employee: 'Emma Garcia',
    type: 'Overtime Request',
    date: 'Jan 14, 2024',
    hours: 4
  },
  {
    id: 3,
    employee: 'Maria Santos',
    type: 'Salary Advance',
    date: 'Jan 13, 2024',
    amount: 10000
  },
  {
    id: 4,
    employee: 'John Smith',
    type: 'Training Request',
    date: 'Jan 20, 2024',
    course: 'Leadership'
  }
])

// Computed Properties
const filteredEmployees = computed(() => {
  let filtered = employees.value

  // Search filter
  if (employeeSearch.value) {
    const term = employeeSearch.value.toLowerCase()
    filtered = filtered.filter(emp =>
      emp.name.toLowerCase().includes(term) ||
      emp.position.toLowerCase().includes(term) ||
      emp.department.toLowerCase().includes(term) ||
      emp.employeeId.toLowerCase().includes(term)
    )
  }

  // Status filter
  if (employeeFilter.value === 'active') {
    filtered = filtered.filter(emp => emp.status === 'Active')
  } else if (employeeFilter.value === 'leave') {
    filtered = filtered.filter(emp => emp.status === 'On Leave')
  }

  return filtered
})

const today = computed(() => {
  return new Date().toISOString().split('T')[0]
})

// Helper Functions
const formatCurrency = (amount: number) => {
  return amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateString: string) => {
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    })
  } catch (e) {
    return dateString
  }
}

const formatBirthdayDate = (dateString: string) => {
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric'
    })
  } catch (e) {
    return dateString
  }
}

const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getEmployeeStatusSeverity = (status: string) => {
  switch (status.toLowerCase()) {
    case 'active': return 'success'
    case 'on leave': return 'warning'
    case 'inactive': return 'danger'
    case 'probation': return 'info'
    default: return 'secondary'
  }
}

const getActivityStatusSeverity = (status: string) => {
  switch (status.toLowerCase()) {
    case 'completed':
    case 'approved': return 'success'
    case 'pending': return 'warning'
    case 'scheduled': return 'info'
    case 'rejected': return 'danger'
    default: return 'secondary'
  }
}

// Action Functions
const viewEmployee = (employee: any) => {
  router.push(`/hr/employees/${employee.id}`)
}

const editEmployee = (employee: any) => {
  console.log('Edit employee:', employee)
  // Navigate to edit page
}

const markAttendance = () => {
  console.log('Mark attendance')
  router.push('/hr/attendance')
}

const processPayroll = () => {
  console.log('Process payroll')
  router.push('/hr/payroll')
}

const approveLeaveRequests = () => {
  console.log('Approve leave requests')
  router.push('/hr/leave-requests')
}

const viewAllActivities = () => {
  router.push('/hr/activities')
}

const approveRequest = (request: any) => {
  console.log('Approve request:', request)
  // Implement approval logic
}

const rejectRequest = (request: any) => {
  console.log('Reject request:', request)
  // Implement rejection logic
}

const addEmployee = () => {
  const newId = Math.max(...employees.value.map(e => e.id)) + 1
  const employee = {
    id: newId,
    name: `${newEmployee.value.firstName} ${newEmployee.value.lastName}`,
    employeeId: `EMP-${String(newId).padStart(3, '0')}`,
    position: newEmployee.value.position?.name || '',
    department: newEmployee.value.department?.name || '',
    status: 'Active',
    hireDate: newEmployee.value.hireDate || new Date().toISOString().split('T')[0],
    email: newEmployee.value.email,
    phone: newEmployee.value.phone
  }

  employees.value.unshift(employee)
  showAddEmployeeDialog.value = false
  resetNewEmployee()
}

const resetNewEmployee = () => {
  newEmployee.value = {
    firstName: '',
    lastName: '',
    position: null,
    department: null,
    email: '',
    phone: '',
    hireDate: null,
    salary: 0,
    address: ''
  }
}

const generateReports = () => {
  console.log('Generate HR reports')
  router.push('/hr/reports')
}

// Monthly income data
const income2024 = ref([45000, 52000, 48000, 61000, 58000, 72000, 68000, 75000, 71000, 80000, 78000, 85000]);
const income2025 = ref([48000, 55000, 52000, 65000, 62000, 76000, 72000, 79000, 75000, 84000, 82000, 90000]);
const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

// Calculate totals
const total2024 = computed(() => income2024.value.reduce((a, b) => a + b, 0));
const total2025 = computed(() => income2025.value.reduce((a, b) => a + b, 0));
const growthPercentage = computed(() => {
  return (((total2025.value - total2024.value) / total2024.value) * 100).toFixed(1);
});

// Format numbers
const formatNumber = (num) => {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
};

// Chart configuration
const chartData = ref({
  labels: months,
  datasets: [
    {
      label: '2024',
      data: income2024.value,
      fill: false,
      borderColor: '#3b82f6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      tension: 0.4,
      borderWidth: 2
    },
    {
      label: '2025',
      data: income2025.value,
      fill: false,
      borderColor: '#10b981',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      tension: 0.4,
      borderWidth: 2
    }
  ]
});

const chartOptions = ref({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          return `${context.dataset.label}: $${formatNumber(context.raw)}`;
        }
      }
    }
  },
  scales: {
    y: {
      beginAtZero: false,
      ticks: {
        callback: function(value) {
          return '$' + formatNumber(value);
        }
      },
      grid: {
        color: 'rgba(0, 0, 0, 0.05)'
      }
    },
    x: {
      grid: {
        color: 'rgba(0, 0, 0, 0.05)'
      }
    }
  }
});


onMounted(() => {
  console.log('HR Index page loaded')
})
</script>

<style scoped>
.box-shadow {
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .p-datatable {
    font-size: 0.75rem;
  }
  
  .p-button.p-button-sm {
    padding: 0.25rem 0.5rem;
  }
}
</style>