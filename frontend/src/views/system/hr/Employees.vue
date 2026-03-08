<!-- views/system/HREmployees.vue -->
<template>
  <div class="space-y-6 text-sm">
    <!-- Employee Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-4">
      <div v-for="stat in employeeStats" :key="stat.label"
        class="bg-white rounded-lg border border-info-200 p-4 shadow-sm">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm text-gray-500">{{ stat.label }}</p>
            <p class="text-2xl font-bold text-info-700 mt-1">{{ stat.value }}</p>
          </div>
          <div class="w-10 h-10 rounded-full bg-info-100 flex items-center justify-center">
            <i :class="[stat.icon, 'text-info-600']"></i>
          </div>
        </div>
      </div>
    </div>
  
    <!-- Main Content -->
    <div class="bg-white rounded-lg">
      <!-- Toolbar -->
      <div class="p-4 border-b">
        <div class="flex flex-col md:flex-row md:items-center justify-between space-y-3 md:space-y-0">
          <div class="flex items-center space-x-2">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="searchQuery" placeholder="Search" class="w-full md:w-64" size="small"/>
            </IconField>
            <Select v-model="filterDepartment" :options="departments" showClear optionLabel="name" optionValue="value"
              placeholder="Department" class="w-full md:w-60" size="small" />
  
            <Select v-model="filterStatus" :options="statuses" showClear optionLabel="label" optionValue="value"
              placeholder="All Status" class="w-full md:w-40" size="small"/>
          </div>
          <div class="flex space-x-2">
            <Button label="Add Employee" icon="pi pi-user-plus" @click="showAddDialog = true" severity="info"
              class="ml-auto" size="small" />
          </div>
        </div>
  
      </div>
  
      <!-- Employee Table -->
      <div class="p-4">
        <DataTable :value="filteredEmployees" class="w-full" :loading="loading" paginator :rows="10"
          :rowsPerPageOptions="[5, 10, 20, 50]"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} employees" rowHover showGridlines
          removableSort sortMode="multiple" tableStyle="min-width: 50rem">
          <!-- ID Column -->
          <Column field="employee_number" header="ID" style="width: 150px">
            <template #body="slotProps">
              <span class="font-semibold text-sm">{{ slotProps.data.employee_number }}</span>
            </template>
          </Column>
  
          <!-- Employee Column -->
          <Column field="name" header="Employee" style="min-width: 200px">
            <template #body="slotProps">
              <div class="flex items-center">
                <Avatar :label="getInitials(slotProps.data.fname, slotProps.data.lname)" size="normal" shape="circle"
                  class="mr-3 bg-blue-100 text-blue-800" />
                <div>
                  <p class="font-medium">{{ slotProps.data.fname }} {{ slotProps.data.lname }}</p>
                  <p class="text-sm text-gray-500">{{ slotProps.data.role_name || 'No Role' }}</p>
                </div>
              </div>
            </template>
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
              <span class="text-sm">
                {{ slotProps.data.branch || 'N/A' }}
              </span>
            </template>
          </Column>
  
          <!-- Email Column -->
          <Column field="email" header="Email" style="min-width: 180px">
            <template #body="slotProps">
              <p class="text-sm truncate" :title="slotProps.data.email">
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
  
          <!-- Actions Column -->
          <Column header="Actions" style="width: 100px">
            <template #body="slotProps">
              <div class="flex space-x-1">
                <Button icon="pi pi-eye" size="small" text rounded @click="viewEmployee(slotProps.data)"
                  v-tooltip.top="'View Details'" />
                <Button icon="pi pi-pencil" size="small" text rounded severity="secondary"
                  @click="editEmployee(slotProps.data)" v-tooltip.top="'Edit Employee'" />
              </div>
            </template>
          </Column>
  
          <!-- Empty State Template -->
          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-users text-4xl text-gray-400 mb-3"></i>
              <p class="text-gray-500 text-lg">No employees found</p>
              <p class="text-gray-400 text-sm mb-4">Try adjusting your search or filter criteria</p>
              <Button label="Add New Employee" icon="pi pi-plus" severity="info" @click="showAddDialog = true" />
            </div>
          </template>
        </DataTable>
      </div>
    </div>
  
    <!-- Add/Edit Dialog -->
    <Dialog modal v-model:visible="showAddDialog" :header="dialogHeader" :style="{ width: '500px' }">
      <div class="space-y-4">
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
  
        <div>
          <label class="block text-sm font-medium mb-1">Position *</label>
          <InputText v-model="employeeForm.position" class="w-full" />
        </div>
  
        <div>
          <label class="block text-sm font-medium mb-1">Department *</label>
          <Select v-model="employeeForm.department" :options="departments" optionLabel="name" class="w-full" />
        </div>
  
        <div>
          <label class="block text-sm font-medium mb-1">Email *</label>
          <InputText v-model="employeeForm.email" class="w-full" />
        </div>
  
        <div>
          <label class="block text-sm font-medium mb-1">Phone</label>
          <InputText v-model="employeeForm.phone" class="w-full" />
        </div>
  
        <div v-if="isEditMode">
          <label class="block text-sm font-medium mb-1">Status</label>
          <Select v-model="employeeForm.status" :options="statuses" optionLabel="label" class="w-full" />
        </div>
      </div>
  
      <template #footer>
        <Button label="Cancel" severity="secondary" @click="cancelDialog" />
        <Button :label="isEditMode ? 'Update' : 'Add Employee'" @click="saveEmployee" />
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
import axios from 'axios'
import { useRouter } from 'vue-router'

interface Department {
  name: string
  value: string
}

interface Status {
  label: string
  value: string
}

interface EmployeeForm {
  id: number | null;
  firstName: string;
  lastName: string;
  position: string;
  department: string;
  email: string;
  phone: string;
  status: string;
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
const showAddDialog = ref<boolean>(false)
const showViewDialog = ref<boolean>(false)
const isEditMode = ref(false)
const selectedEmployee = ref<Employee | null>(null)
const employees = ref<Employee[]>([])
const loading = ref(false)

const employeeStats = ref<StatCard[]>([
  { label: 'Total Employees', value: 0, icon: 'pi pi-user' },
  { label: 'Active Employees', value: 0, icon: 'pi pi-check-circle' },
  { label: 'Departments', value: 0, icon: 'pi pi-building' }
])



// Form data
const employeeForm = ref({
  id: null,
  firstName: '',
  lastName: '',
  position: '',
  department: null,
  email: '',
  phone: '',
  status: { label: 'Active', value: 'active' }
})

// Departments for dropdown
const departments = ref<Department[]>([
  { name: 'Store Management', value: 'store management' },
  { name: 'Store Operations', value: 'Store Operations' },
  { name: 'Sales', value: 'Sales' },
  { name: 'Logistics', value: 'Logistics' },
  { name: 'Human Resources', value: 'Human Resources' },
  { name: 'Finance', value: 'Finance' },
  { name: 'Marketing', value: 'Marketing' },
  { name: 'IT', value: 'IT' },
  { name: 'Procurement', value: 'Procurement' }
])

// Status options
const statuses = ref<Status[]>([
  { label: 'Active', value: 'Active' },
  { label: 'On Leave', value: 'On-Leave' },
  { label: 'Inactive', value: 'Inactive' }
])


const fetchEmployeesAxios = async () => {
  loading.value = true
  try {
    const response = await axios.get('api/employees', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      }
    })
    employees.value = response.data.data

    // Update stats based on your API response structure
    if (response.data.counts) {
      employeeStats.value = [
        {
          label: 'Total Employees',
          value: response.data.counts.total || 0,
          icon: 'pi pi-user'
        },
        {
          label: 'Active Employees',
          value: response.data.counts.active || 0,
          icon: 'pi pi-check-circle'
        },
        {
          label: 'Departments',
          value: response.data.counts.departments || 0,
          icon: 'pi pi-building'
        }
      ]
    }
  } catch (error) {
    console.error('Failed to fetch employees:', error)
   } finally {
    loading.value = false
  }
}


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

// Computed properties
const dialogHeader = computed(() => {
  return isEditMode.value ? 'Edit Employee' : 'Add New Employee'
})

// Reset filters
const resetFilters = () => {
  searchQuery.value = ''
  filterDepartment.value = ''
  filterStatus.value = ''
}

const hasActiveFilters = computed(() => {
  return searchQuery.value !== '' || filterStatus.value !== null
})

// Helper functions
const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
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

// Action functions
const viewEmployee = (employee: Employee) => {
  // selectedEmployee.value = employee
  // showViewDialog.value = true
  router.push(`/hr/employees/view/${employee.id}`)
}

const editEmployee = (employee: Employee) => {
  isEditMode.value = true
  employeeForm.value = {
    id: employee.id,
    firstName: employee.fname,
    lastName: employee.lname,
    position: employee.role_name, // Using role_name as position
    department: departments.value.find(d => d.value === employee.department), // Find by value, not name
    email: employee.email,
    phone: employee.phone,
    status: statuses.value.find(s => s.value === employee.status) // Find by value to match your data
  }
  showAddDialog.value = true
}

const saveEmployee = () => {
  if (!employeeForm.value.firstName || !employeeForm.value.lastName ||
    !employeeForm.value.position || !employeeForm.value.department ||
    !employeeForm.value.email) {
    alert('Please fill in all required fields')
    return
  }

  const employeeData = {
    fname: employeeForm.value.firstName,
    lname: employeeForm.value.lastName,
    role_name: employeeForm.value.position,
    department: employeeForm.value.department.value, // Use department.value instead of name
    email: employeeForm.value.email,
    phone: employeeForm.value.phone,
    status: employeeForm.value.status?.value || 'Active', // Use status.value
    branch: 'Main Branch' // Add default branch or get from somewhere
  }

  if (isEditMode.value) {
    // Update existing employee
    const index = employees.value.findIndex(e => e.id === employeeForm.value.id)
    if (index !== -1) {
      employees.value[index] = {
        ...employees.value[index],
        ...employeeData,
        id: employeeForm.value.id // Preserve the ID
      }
    }
  } else {
    // Add new employee
    const newId = employees.value.length > 0
      ? Math.max(...employees.value.map(e => e.id)) + 1
      : 1

    const newEmployee: Employee = {
      id: newId,
      employee_number: `EMP-${String(newId).padStart(3, '0')}`,
      hireDate: new Date().toISOString(),
      ...employeeData
    }

    employees.value?.push(newEmployee)
  }

  cancelDialog()
}

const cancelDialog = () => {
  showAddDialog.value = false
  isEditMode.value = false
  employeeForm.value = {
    id: null,
    firstName: '',
    lastName: '',
    position: '',
    department: null,
    email: '',
    phone: '',
    status: { label: 'Active', value: 'active' }
  }
}

onMounted(() => {
  fetchEmployeesAxios(),
    console.log('Employees page loaded')
})
</script>

<style scoped>
/* Add any custom styles here */
</style>