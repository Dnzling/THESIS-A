<!-- views/system/HREmployees.vue -->
<template>
  <div class="min-h-screen p-4 text-xs">
    <div class="mb-4 flex items-center justify-between">
      <div><h1 class="text-lg font-bold text-gray-800">Employees</h1></div>
      <div class="flex items-center gap-2">
        <Button label="Add Employee"  @click="openAddDialog" severity="warn" size="small" />
      </div>
    </div>

    <Card>
      <template #content>
        <div class="mb-5 grid grid-cols-1 items-center gap-3 md:grid-cols-5">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="searchQuery" placeholder="Search employees" fluid size="small"/>
          </IconField>
          <Select v-model="filterDepartment" :options="departments" showClear optionLabel="name" optionValue="value" size="small" fluid
            placeholder="Department"  />
  
          <Select v-model="filterStatus" :options="statuses" showClear optionLabel="label" optionValue="value"
            placeholder="All Status" fluid size="small" />
          <div v-if="hasActiveFilters" class="flex justify-start md:justify-end">
            <Button label="Clear" severity="secondary" outlined size="small" @click="resetFilters" />
          </div>
        </div>
      <!-- Employee Table -->
      <div class="border-t border-gray-100 p-3">
        <DataTable :value="filteredEmployees" class="w-full text-xs" :loading="loading" paginator :rows="10" @row-click="({ data }) => viewEmployee(data)"
          :rowsPerPageOptions="[5, 10, 20, 50]"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} employees" 
          removableSort sortMode="multiple" tableStyle="min-width: 58rem" size="small" rowHover>
          <!-- ID Column -->
          <Column field="employee_number" header="ID" style="width: 150px">
            <template #body="slotProps">
              <span class="font-semibold text-sm underline text-orange-600">{{ slotProps.data.employee_number }}</span>
            </template>
          </Column>
  
          <!-- Employee Column -->
          <Column field="name" header="Employee" style="min-width: 200px">
            <template #body="slotProps">
              <div class="flex items-center">
                <Avatar :label="getInitials(slotProps.data.fname, slotProps.data.lname)" size="normal" shape="circle"
                  class="mr-3 bg-blue-100 text-blue-800" />
                <div>
              <p class="font-medium text-xs">{{ slotProps.data.fname }} {{ slotProps.data.lname }}</p>
              <p class="text-xs text-gray-500">{{ slotProps.data.role_name || 'No Role' }}</p>
                </div>
              </div>
            </template>
          </Column>

          <Column field="monthly_salary" header="Monthly Salary" sortable>
            <template #body="slotProps"><span class="text-xs">₱{{ Number(slotProps.data.monthly_salary || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span></template>
          </Column>
          <Column field="hireDate" header="Hired Date" sortable>
            <template #body="slotProps"><span class="text-xs">{{ slotProps.data.hireDate ? formatDate(slotProps.data.hireDate) : 'Not set' }}</span></template>
          </Column>
  
          <!-- Department Column -->
          <Column field="department" header="Department" style="width: 150px">
            <template #body="slotProps">
              <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">
                {{ slotProps.data.department || 'N/A' }}
              </span>
            </template>
          </Column>
  
          <!-- Branch Column -->
          <Column field="branch" header="Branch" style="width: 150px">
            <template #body="slotProps">
              <span class="text-xs">
                {{ slotProps.data.branch || 'N/A' }}
              </span>
            </template>
          </Column>
  
          <!-- Email Column -->
          <Column field="email" header="Email" style="min-width: 180px">
            <template #body="slotProps">
              <p class="text-xs truncate" :title="slotProps.data.email">
                {{ slotProps.data.email || 'No email' }}
              </p>
            </template>
          </Column>
  
          <!-- Status Column -->
          <Column field="status" header="Status" style="width: 100px">
            <template #body="slotProps">
              <span :class="`px-2 py-1 rounded text-xs font-medium ${getStatusClass(slotProps.data.status)}`">
                {{ slotProps.data.status }}
              </span>
            </template>
          </Column>
  
        
          <!-- Empty State Template -->
          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-users text-4xl text-gray-400 mb-3"></i>
              <p class="text-gray-500 text-lg">No employees found</p>
              <p class="text-gray-400 text-sm mb-4">Try adjusting your search or filter criteria</p>
              <Button label="Add New Employee" icon="pi pi-plus" severity="warn" @click="openAddDialog" />
            </div>
          </template>
        </DataTable>
      </div>
      </template>
    </Card>
  
    <!-- Add/Edit Dialog -->
    <Dialog v-model:visible="showAddDialog" modal :header="dialogHeader" :style="{ width: 'min(58rem, 96vw)' }" appendTo="body">
      <div class="space-y-5">
        <div class="rounded-2xl border border-orange-100 bg-orange-50/70 p-4">

          <p class="mt-1 text-sm text-slate-600">Create the account, assign department, compensation, and starting weekly schedule.</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium mb-1">First Name *</label>
            <InputText v-model="employeeForm.firstName" class="w-full" />
          </div>
  
          <div>
            <label class="block text-sm font-medium mb-1">Last Name *</label>
            <InputText v-model="employeeForm.lastName" class="w-full" />
          </div>
        </div>
  
        <div class="grid gap-4 md:grid-cols-4 md:items-end">
          <div class="col-span-3">
            <label class="block text-sm font-medium mb-1">Position (Role) *</label>
            <Select v-model="employeeForm.role" :options="roles" optionLabel="display_name" class="w-full" placeholder="Select position" />
          </div>
          <Button label="Role" icon="pi pi-plus" severity="warn" outlined @click="openRoleDialog" />
        </div>

        <div class="grid gap-4 md:grid-cols-4 md:items-end">
          <div class="col-span-3">
            <label class="block text-sm font-medium mb-1">Department</label>
            <Select v-model="employeeForm.department" rounded :options="departmentOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="Select department (optional)" showClear />
          </div>
          <Button label="Department" icon="pi pi-plus" severity="warn" outlined @click="openDepartmentDialog" />
        </div>

        <div>
          <label class="block text-sm font-medium mb-1">Branch *</label>
          <Select v-model="employeeForm.branchId" :options="branches" optionLabel="name" optionValue="id" class="w-full" placeholder="Select branch" :loading="loadingBranches" />
        </div>

        <div class="grid gap-4 md:grid-cols-4 md:items-end">
          <div class="col-span-3">
            <label class="block text-sm font-medium mb-1">Salary / Rate *</label>
            <p class="mb-1 text-xs text-slate-500">Pay computation follows the selected monthly or hourly type.</p>
            <InputNumber v-model="employeeForm.salary" class="w-full" mode="currency" currency="PHP" locale="en-PH" :min="0" />
       
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Pay Type</label>
            <SelectButton v-model="employeeForm.payType" :options="payTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
          </div>
        </div>
  
        <div>
          <label class="block text-sm font-medium mb-1">Email *</label>
          <InputText v-model="employeeForm.email" class="w-full" />
          <p class="mt-1 text-xs text-gray-500">A temporary password will be generated and emailed to this address.</p>
        </div>

        <div class="rounded-2xl border border-slate-200 p-4">
          <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
              <h3 class="font-semibold text-slate-900">Shift Schedule</h3>
              <p class="text-xs text-slate-500">Set the initial weekly schedule. Disabled days are saved as off days.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <Tag :value="`${workingDaysCount}/6 days`" severity="warn" />
              <Tag :value="`${totalWeeklyHours.toFixed(2)} hrs/week`" severity="warn" />
            </div>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0">
              <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                  <th class="border-b border-slate-200 px-3 py-2">Day</th>
                  <th class="border-b border-slate-200 px-3 py-2">Working</th>
                  <th class="border-b border-slate-200 px-3 py-2">Start</th>
                  <th class="border-b border-slate-200 px-3 py-2">End</th>
                  <th class="border-b border-slate-200 px-3 py-2">Hours</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in weeklyPlanner" :key="row.day" class="align-top">
                  <td class="border-b border-slate-100 px-3 py-3 font-medium text-slate-800">{{ formatDayLabel(row.day) }}</td>
                  <td class="border-b border-slate-100 px-3 py-3">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                      <input :checked="row.is_working" type="checkbox" class="h-4 w-4 accent-orange-500" @change="(event) => onPlannerWorkingToggle(row, getCheckedValue(event))" />
                      Working
                    </label>
                  </td>
                  <td class="border-b border-slate-100 px-3 py-3">
                    <InputText :model-value="row.start_time" placeholder="08:00 AM" class="w-full" :disabled="!row.is_working" @update:model-value="(value) => onPlannerStartTimeChange(row, String(value || ''))" />
                  </td>
                  <td class="border-b border-slate-100 px-3 py-3">
                    <InputText :model-value="row.end_time" placeholder="04:00 PM" class="w-full" :disabled="!row.is_working" @update:model-value="(value) => onPlannerEndTimeChange(row, String(value || ''))" />
                  </td>
                  <td class="border-b border-slate-100 px-3 py-3 text-sm text-slate-700">
                    {{ row.is_working ? `${Number(row.hours || 0).toFixed(2)} hrs` : 'Off' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
  
      <template #footer>
        <Button label="Cancel" severity="secondary" @click="cancelDialog" />
        <Button :label="isEditMode ? 'Update' : 'Add Employee'" severity="warn" :loading="savingEmployee" @click="saveEmployee" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showDepartmentDialog" modal header="Add Department" :style="{ width: 'min(30rem, 95vw)' }" appendTo="body">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Department Name *</label>
          <InputText v-model="departmentForm.name" class="w-full" placeholder="Example: Store Operations" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Description</label>
          <Textarea v-model="departmentForm.description" rows="3" class="w-full" placeholder="Optional department notes" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showDepartmentDialog = false" />
        <Button label="Create Department" severity="warn" :loading="savingDepartment" @click="createDepartment" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showRoleDialog" modal header="Add Role" :style="{ width: 'min(30rem, 95vw)' }" appendTo="body">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium mb-1">Role Name *</label>
          <InputText v-model="roleForm.displayName" class="w-full" placeholder="Example: Sales Associate" />
          <p class="mt-1 text-xs text-slate-500">This will be available immediately in the Position dropdown.</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Description</label>
          <Textarea v-model="roleForm.description" rows="3" class="w-full" placeholder="Optional role notes" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showRoleDialog = false" />
        <Button label="Create Role" severity="warn" :loading="savingRole" @click="createRole" />
      </template>
    </Dialog>

    <!-- View Details Dialog -->
    <Dialog modal v-model:visible="showViewDialog" header="Employee Details" :style="{ width: '500px' }">
      <div v-if="selectedEmployee" class="space-y-4">
        <div class="flex items-center space-x-4">
          <Avatar :label="getInitials(selectedEmployee.fname)" size="xlarge" shape="circle"
            class="bg-blue-100 text-blue-800 text-2xl" />
          <div>
            <h3 class="text-xl font-bold">{{ selectedEmployee.fname + selectedEmployee.lname }}</h3>
            <p class="text-gray-600">{{ selectedEmployee.role_name }}</p>
          </div>
        </div>
  
        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm text-gray-500">Employee ID</p>
            <p class="font-medium">{{ selectedEmployee.employee_number }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Department</p>
            <p class="font-medium">{{ selectedEmployee.department }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Email</p>
            <p class="font-medium">{{ selectedEmployee.email }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Phone</p>
            <p class="font-medium">{{ selectedEmployee.phone || 'N/A' }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Status</p>
            <p :class="`font-medium ${getStatusClass(selectedEmployee.status)}`">
              {{ selectedEmployee.status }}
            </p>
          </div>
          <div>
            <p class="text-sm text-gray-500">Hire Date</p>
            <p class="font-medium">{{ formatDate(selectedEmployee.hireDate) }}</p>
          </div>
        </div>
      </div>
    </Dialog>
  </div>

</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../../stores/auth'
import hrService from '../../../services/hr.services'
import { useRouter } from 'vue-router'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import SelectButton from 'primevue/selectbutton'
import { showResponseDialog } from '@/utils/responseDialogBus'

interface Department {
  name: string
  value: string
}

interface Status {
  label: string
  value: string
}

interface EmployeeFormState {
  id: number | null
  firstName: string
  lastName: string
  role: RoleOption | null
  email: string
  department: string
  branchId: number | null
  payType: 'monthly' | 'hourly'
  salary: number
}

interface Employee {
  id: number
  fname: string
  lname: string
  employee_number: string
  role_name: string
  department: string
  status: string
  hireDate: string
  email: string
  phone: string
  branch: string
}

interface RoleOption {
  id: number
  name: string
  display_name: string
}

interface StatCard {
  label: string
  value: number | string
  icon: string
}

// State
const authStore = useAuthStore()
const router = useRouter()
const searchQuery = ref<string>('')
const filterDepartment = ref<string>('')
const filterStatus = ref<string>('')
const hasActiveFilters = computed(() => Boolean(searchQuery.value || filterDepartment.value || filterStatus.value))
const showAddDialog = ref<boolean>(false)
const showViewDialog = ref<boolean>(false)
const showDepartmentDialog = ref(false)
const showRoleDialog = ref(false)
const isEditMode = ref(false)
const selectedEmployee = ref<Employee | null>(null)
const employees = ref<Employee[]>([])
const loading = ref(false)
const savingEmployee = ref(false)
const savingDepartment = ref(false)
const savingRole = ref(false)
const roles = ref<RoleOption[]>([])
const branches = ref<{ id: number; name: string }[]>([])
const loadingBranches = ref(false)
const payTypeOptions = [
  { label: 'Monthly', value: 'monthly' },
  { label: 'Hourly', value: 'hourly' },
]





// Form data
const employeeForm = ref<EmployeeFormState>({
  id: null,
  firstName: '',
  lastName: '',
  role: null,
  email: '',
  department: '',
  branchId: null,
  payType: 'monthly',
  salary: 0
})

const departmentForm = ref({
  name: '',
  description: ''
})

const roleForm = ref({
  displayName: '',
  description: ''
})

// Departments are loaded from the current store only through /api/departments.
const departments = ref<Department[]>([])
const departmentOptions = computed(() => departments.value.map(department => ({
  label: department.name,
  value: department.value || department.name
})))

const weeklyPlanner = ref([
  { day: 'monday', is_working: true, shift_id: null as number | null, start_time: '08:00 AM', end_time: '04:00 PM', hours: 8 },
  { day: 'tuesday', is_working: true, shift_id: null as number | null, start_time: '08:00 AM', end_time: '04:00 PM', hours: 8 },
  { day: 'wednesday', is_working: true, shift_id: null as number | null, start_time: '08:00 AM', end_time: '04:00 PM', hours: 8 },
  { day: 'thursday', is_working: true, shift_id: null as number | null, start_time: '08:00 AM', end_time: '04:00 PM', hours: 8 },
  { day: 'friday', is_working: true, shift_id: null as number | null, start_time: '08:00 AM', end_time: '04:00 PM', hours: 8 },
  { day: 'saturday', is_working: false, shift_id: null as number | null, start_time: '08:00 AM', end_time: '04:00 PM', hours: 0 },
  { day: 'sunday', is_working: false, shift_id: null as number | null, start_time: '08:00 AM', end_time: '04:00 PM', hours: 0 },
])

const totalWeeklyHours = computed(() => weeklyPlanner.value.reduce((sum, row) => sum + Number(row.hours || 0), 0))
const workingDaysCount = computed(() => weeklyPlanner.value.filter(row => row.is_working).length)

// Status options
const statuses = ref<Status[]>([
  { label: 'Active', value: 'Active' },
  { label: 'On Leave', value: 'On-Leave' },
  { label: 'Inactive', value: 'Inactive' }
])

const fetchRoles = async () => {
  try {
    const response = await hrService.api.get('/api/store/roles/scoped', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      }
    })
    const data = response.data?.data || response.data || []
    roles.value = data.map((role: any) => ({
      id: role.id,
      name: role.name,
      display_name: role.display_name || role.name
    }))
  } catch (error) {
    console.error('Failed to fetch roles:', error)
  }
}

const fetchEmployeesAxios = async () => {
  loading.value = true
  try {
    const response = await hrService.api.get('/api/employees', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      }
    })
    const data = response.data?.data || response.data || []
    employees.value = Array.isArray(data) ? data : []
  } catch (error) {
    console.error('Failed to fetch employees:', error)
    employees.value = []
  } finally {
    loading.value = false
  }
}

const fetchDepartments = async () => {
  try {
    const response = await hrService.api.get('/api/departments', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      }
    })
    const data = response.data?.data || response.data || []
    const items = Array.isArray(data?.data) ? data.data : data
    if (Array.isArray(items) && items.length) {
      departments.value = items.map((department: any) => ({
        name: department.name || department.label || 'Department',
        value: department.name || department.value || department.id
      }))
    }
  } catch (error) {
    console.error('Failed to fetch departments:', error)
  }
}

const fetchBranches = async () => {
  loadingBranches.value = true
  try {
    const response = await hrService.api.get('/api/branches', {
      headers: { Authorization: `Bearer ${authStore.token}` },
    })
    const data = response.data?.data || []
    branches.value = Array.isArray(data)
      ? data.map((branch: any) => ({ id: Number(branch.id), name: branch.name || `Branch ${branch.id}` }))
      : []

    const currentBranchId = Number((authStore.currentUser as any)?.branch_id || 0)
    if (!employeeForm.value.branchId && branches.value.some(branch => branch.id === currentBranchId)) {
      employeeForm.value.branchId = currentBranchId
    } else if (!employeeForm.value.branchId && branches.value.length === 1) {
      employeeForm.value.branchId = branches.value[0].id
    }
  } catch (error) {
    console.error('Failed to fetch branches:', error)
    branches.value = []
  } finally {
    loadingBranches.value = false
  }
}

const toRoleSlug = (value: string) => String(value || '')
  .trim()
  .toLowerCase()
  .replace(/[^a-z0-9]+/g, '_')
  .replace(/^_+|_+$/g, '')

// Computed property for filtered employees

const filteredEmployees = computed(() => {
  if (!employees.value) return []

  let filtered = [...employees.value]

  // Search filter
  if (searchQuery.value) {
    const term = searchQuery.value.toLowerCase()
    filtered = filtered.filter(emp =>
      (emp.fname?.toLowerCase() || '').includes(term) ||
      (emp.lname?.toLowerCase() || '').includes(term) ||
      `${emp.fname} ${emp.lname}`.toLowerCase().includes(term) ||
      (emp.email?.toLowerCase() || '').includes(term) ||
      (emp.role_name?.toLowerCase() || '').includes(term) ||
      (emp.employee_number?.toLowerCase() || '').includes(term) ||
      (emp.department?.toLowerCase() || '').includes(term) ||
      (emp.branch?.toLowerCase() || '').includes(term)
    )
  }

  // Department filter
  if (filterDepartment.value) {
    filtered = filtered.filter(emp =>
      emp.department?.toLowerCase() === filterDepartment.value?.toLowerCase()
    )
  }

  // Status filter
  if (filterStatus.value) {
    filtered = filtered.filter(emp =>
      emp.status === filterStatus.value
    )
  }

  return filtered
})

const resetFilters = () => {
  searchQuery.value = ''
  filterDepartment.value = ''
  filterStatus.value = ''
}

// Computed properties
const dialogHeader = computed(() => {
  return isEditMode.value ? 'Edit Employee' : 'Add New Employee'
})

// Helper functions
const getInitials = (firstName: string, lastName = '') => {
  const first = String(firstName || '').trim()
  const last = String(lastName || '').trim()
  if (!first && !last) return '?'
  return `${first.charAt(0)}${last.charAt(0)}`.toUpperCase()
}

const getStatusClass = (status: string) => {
  switch (status) {
    case 'Active': return 'bg-green-100 text-green-800'
    case 'On Leave': return 'bg-yellow-100 text-yellow-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const formatDate = (dateString: string) => {
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  } catch {
    return dateString
  }
}

const dayLabelMap: Record<string, string> = {
  monday: 'Monday',
  tuesday: 'Tuesday',
  wednesday: 'Wednesday',
  thursday: 'Thursday',
  friday: 'Friday',
  saturday: 'Saturday',
  sunday: 'Sunday',
}

const formatDayLabel = (value?: string) => value ? (dayLabelMap[value.toLowerCase()] || value) : 'Day'

const parseTimeToMinutes = (value?: string) => {
  if (!value) return null
  const raw = String(value).trim().toUpperCase()
  const match = raw.match(/^(\d{1,2}):(\d{2})\s*([AP]M)?$/)
  if (!match) return null
  let hours = Number(match[1])
  const minutes = Number(match[2])
  const meridiem = match[3]
  if (Number.isNaN(hours) || Number.isNaN(minutes) || minutes > 59) return null

  if (meridiem) {
    if (hours < 1 || hours > 12) return null
    if (meridiem === 'AM') hours = hours === 12 ? 0 : hours
    if (meridiem === 'PM') hours = hours === 12 ? 12 : hours + 12
  } else if (hours > 23) {
    return null
  }

  return hours * 60 + minutes
}

const formatMinutesToTime12h = (minutesTotal: number) => {
  const normalized = ((minutesTotal % 1440) + 1440) % 1440
  const hours24 = Math.floor(normalized / 60)
  const minutes = normalized % 60
  const period = hours24 >= 12 ? 'PM' : 'AM'
  const hours12 = ((hours24 + 11) % 12) + 1
  return `${String(hours12).padStart(2, '0')}:${String(minutes).padStart(2, '0')} ${period}`
}

const calculateHours = (start?: string, end?: string) => {
  const startMinutes = parseTimeToMinutes(start)
  const endMinutes = parseTimeToMinutes(end)
  if (startMinutes === null || endMinutes === null) return 0
  const diff = endMinutes - startMinutes
  return diff > 0 ? diff / 60 : 0
}

const capPlannerRowAtEightHours = (row: any) => {
  if (!row.is_working) {
    row.hours = 0
    return
  }

  const startMinutes = parseTimeToMinutes(row.start_time)
  if (startMinutes === null) {
    row.hours = 0
    return
  }

  row.end_time = formatMinutesToTime12h(startMinutes + 8 * 60)
  row.hours = 8
}

const validateScheduleRules = () => {
  if (workingDaysCount.value > 6) {
    return 'Only 6 working days are allowed per week.'
  }

  const overLimit = weeklyPlanner.value.find(row => row.is_working && Number(row.hours || 0) > 8)
  if (overLimit) {
    return `${formatDayLabel(overLimit.day)} exceeds the 8-hour daily limit.`
  }

  return ''
}

const onPlannerStartTimeChange = (row: any, value: string) => {
  row.start_time = value
  const startMinutes = parseTimeToMinutes(value)
  if (startMinutes === null) {
    row.end_time = ''
    row.hours = 0
    return
  }
  row.is_working = true
  capPlannerRowAtEightHours(row)
}

const onPlannerEndTimeChange = (row: any, value: string) => {
  row.end_time = value
  row.hours = row.is_working ? calculateHours(row.start_time, row.end_time) : 0
  if (row.hours > 8) {
    capPlannerRowAtEightHours(row)
    notifyResponse(false, 'Schedule Limit', 'Daily working hours cannot exceed 8 hours.')
  }
}

const onPlannerWorkingToggle = (row: any, checked: boolean) => {
  if (checked && workingDaysCount.value >= 6 && !row.is_working) {
    row.is_working = false
    notifyResponse(false, 'Schedule Limit', 'Only 6 working days are allowed per week.')
    return
  }

  row.is_working = checked
  if (checked) {
    row.start_time = row.start_time || '08:00 AM'
    capPlannerRowAtEightHours(row)
    return
  }
  row.hours = 0
}

const getCheckedValue = (event: Event) => Boolean((event.target as HTMLInputElement | null)?.checked)

const resetWeeklyPlanner = () => {
  weeklyPlanner.value = weeklyPlanner.value.map((row) => ({
    ...row,
    is_working: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].includes(row.day),
    start_time: '08:00 AM',
    end_time: '04:00 PM',
    hours: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'].includes(row.day) ? 8 : 0,
  }))
}

const notifyResponse = (success: boolean, title: string, message: string) => {
  showResponseDialog({
    severity: success ? 'success' : 'error',
    title,
    message,
  })
}

// Action functions
const viewEmployee = (employee: Employee) => {
  // selectedEmployee.value = employee
  // showViewDialog.value = true
  router.push(`/hr/employees/view/${employee.id}`)
}

const editEmployee = (employee: Employee) => {
  isEditMode.value = true
  const matchedRole = roles.value.find(role =>
    role.display_name === employee.role_name || role.name === employee.role_name
  ) || null
  employeeForm.value = {
    id: employee.id,
    firstName: employee.fname,
    lastName: employee.lname,
    role: matchedRole,
    email: employee.email,
    department: employee.department || '',
    branchId: null,
    payType: 'monthly',
    salary: 0
  }
  showAddDialog.value = true
}

const resetEmployeeForm = () => {
  employeeForm.value = {
    id: null,
    firstName: '',
    lastName: '',
    role: null,
    email: '',
    department: '',
    branchId: null,
    payType: 'monthly',
    salary: 0
  }
  resetWeeklyPlanner()
}

const openAddDialog = async () => {
  isEditMode.value = false
  resetEmployeeForm()
  if (roles.value.length === 0) {
    await fetchRoles()
  }
  if (departments.value.length === 0) {
    await fetchDepartments()
  }
  await fetchBranches()
  showAddDialog.value = true
}

const openDepartmentDialog = () => {
  departmentForm.value = { name: '', description: '' }
  showDepartmentDialog.value = true
}

const openRoleDialog = () => {
  roleForm.value = { displayName: '', description: '' }
  showRoleDialog.value = true
}

const createDepartment = async () => {
  const name = departmentForm.value.name.trim()
  if (!name) {
    notifyResponse(false, 'Department Required', 'Please enter the department name.')
    return
  }

  savingDepartment.value = true
  try {
    const response = await hrService.api.post('/api/departments', {
      name,
      description: departmentForm.value.description || '',
      status: 'active'
    }, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'X-Suppress-Dialog': '1',
      }
    })
    const department = response.data?.data || {}
    const label = department.name || name
    if (!departments.value.some(item => item.value === label)) {
      departments.value.push({ name: label, value: label })
    }
    employeeForm.value.department = label
    showDepartmentDialog.value = false
    notifyResponse(true, 'Department Created', response.data?.message || `${label} is now available.`)
  } catch (error: any) {
    const errors = error?.response?.data?.errors
    const firstError = errors && Object.values(errors)[0]
    notifyResponse(false, 'Department Failed', Array.isArray(firstError) ? firstError[0] : (error?.response?.data?.message || 'Unable to create department.'))
  } finally {
    savingDepartment.value = false
  }
}

const createRole = async () => {
  const displayName = roleForm.value.displayName.trim()
  if (!displayName) {
    notifyResponse(false, 'Role Required', 'Please enter the role name.')
    return
  }

  const baseName = toRoleSlug(displayName)
  if (!baseName) {
    notifyResponse(false, 'Role Invalid', 'Please use letters or numbers for the role name.')
    return
  }
  const name = baseName

  savingRole.value = true
  try {
    const response = await hrService.api.post('/api/store/roles', {
      name,
      display_name: displayName,
      code: baseName.toUpperCase(),
      description: roleForm.value.description || '',
      is_active: true,
    }, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'X-Suppress-Dialog': '1',
      }
    })

    const role = response.data?.data || {}
    const createdRole = {
      id: Number(role.id),
      name: role.name || name,
      display_name: role.display_name || displayName,
    }

    if (createdRole.id && !roles.value.some(item => item.id === createdRole.id)) {
      roles.value.push(createdRole)
    }

    await fetchRoles()
    employeeForm.value.role = roles.value.find(item => item.id === createdRole.id) || createdRole
    showRoleDialog.value = false
    notifyResponse(true, 'Role Created', response.data?.message || `${displayName} is now available.`)
  } catch (error: any) {
    const errors = error?.response?.data?.errors
    const firstError = errors && Object.values(errors)[0]
    notifyResponse(false, 'Role Failed', Array.isArray(firstError) ? firstError[0] : (error?.response?.data?.message || 'Unable to create role.'))
  } finally {
    savingRole.value = false
  }
}

const saveEmployee = async () => {
  if (!employeeForm.value.firstName || !employeeForm.value.lastName || !employeeForm.value.role || !employeeForm.value.email || !employeeForm.value.branchId || Number(employeeForm.value.salary || 0) <= 0) {
    notifyResponse(false, 'Missing Details', 'Please complete the required employee, branch, and salary fields.')
    return
  }

  const selectedRole = employeeForm.value.role
  if (!selectedRole) {
    notifyResponse(false, 'Role Required', 'Please select a role.')
    return
  }

  const scheduleError = validateScheduleRules()
  if (scheduleError) {
    notifyResponse(false, 'Schedule Limit', scheduleError)
    return
  }

  const employeeData = {
    fname: employeeForm.value.firstName,
    lname: employeeForm.value.lastName,
    role_name: selectedRole.display_name,
    email: employeeForm.value.email,
    phone: '',
    status: 'Active',
    department: employeeForm.value.department,
    branch: 'Main Branch' // Add default branch or get from somewhere
  }

  if (isEditMode.value) {
    const formId = employeeForm.value.id
    if (formId === null) {
      notifyResponse(false, 'Missing Employee', 'Cannot update employee without a valid ID.')
      return
    }

    // Update existing employee
    const index = employees.value.findIndex(e => e.id === formId)
    if (index !== -1) {
      const existingEmployee = employees.value[index]
      if (!existingEmployee) {
        return
      }
      employees.value[index] = {
        employee_number: existingEmployee.employee_number,
        hireDate: existingEmployee.hireDate,
        ...employeeData,
        id: formId
      }
    }
  } else {
    // Persist to backend (manual invite: skip OTP and mark email verified).
    savingEmployee.value = true
    try {
      const response = await hrService.api.post('/api/employees/invite', {
        fname: employeeForm.value.firstName,
        lname: employeeForm.value.lastName,
        email: employeeForm.value.email,
        role_id: selectedRole.id,
        branch_id: employeeForm.value.branchId,
        hire_date: new Date().toISOString().slice(0, 10),
        department: employeeForm.value.department || null,
        employment_type: 'full_time',
        salary: Number(employeeForm.value.salary || 0),
        pay_type: employeeForm.value.payType,
        status: 'active'
      }, {
        headers: {
          'Authorization': `Bearer ${authStore.token}`,
          'X-Suppress-Dialog': '1',
        }
      })

      const createdEmployeeId = Number(response.data?.data?.employee?.id || 0)
      if (createdEmployeeId > 0) {
        await hrService.api.put(`/api/employees/${createdEmployeeId}/weekly-schedule`, {
          schedules: weeklyPlanner.value.map((row) => ({
            day_of_week: row.day,
            shift_id: null,
            start_time: row.is_working ? row.start_time : null,
            end_time: row.is_working ? row.end_time : null,
            is_off: !row.is_working,
            effective_from: new Date().toISOString().slice(0, 10),
            effective_to: null,
            notes: null,
          }))
        }, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`,
            'X-Suppress-Dialog': '1',
          }
        })
      }

      await fetchEmployeesAxios()
      cancelDialog()
      notifyResponse(true, 'Employee Created', response.data?.message || 'Employee account, salary, branch, and schedule were saved.')
    } catch (error: any) {
      const apiMessage = error?.response?.data?.message
      const apiErrors = error?.response?.data?.errors
      const firstValidationError = apiErrors && Object.values(apiErrors)[0]
      const firstMessage = Array.isArray(firstValidationError) ? firstValidationError[0] : null
      notifyResponse(false, 'Employee Creation Failed', firstMessage || apiMessage || 'Failed to create employee.')
      return
    } finally {
      savingEmployee.value = false
    }
  }

  if (isEditMode.value) cancelDialog()
}

const cancelDialog = () => {
  showAddDialog.value = false
  isEditMode.value = false
  resetEmployeeForm()
}

onMounted(() => {
  fetchEmployeesAxios()
  fetchRoles()
  fetchDepartments()
  fetchBranches()
  console.log('Employees page loaded')
})
</script>

<style scoped>
/* Add any custom styles here */
</style>
