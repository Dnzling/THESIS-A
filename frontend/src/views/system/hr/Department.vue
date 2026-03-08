```vue
<template>
  <div class="space-y-6">
    <!-- Header Section -->
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Department Management</h2>
        <p class="text-sm text-gray-600 mt-1">Manage company departments and organizational structure</p>
      </div>
      <Button label="Add Department" icon="pi pi-plus" severity="info" @click="openAddDialog" />
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Total Departments</div>
            <div class="text-2xl font-semibold text-blue-600">{{ stats.total }}</div>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-blue-50">
            <i class="pi pi-building text-blue-500"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Active</div>
            <div class="text-2xl font-semibold text-green-600">{{ stats.active }}</div>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-between bg-green-50">
            <i class="pi pi-check-circle text-green-500"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Total Employees</div>
            <div class="text-2xl font-semibold text-purple-600">{{ stats.totalEmployees }}</div>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-purple-50">
            <i class="pi pi-users text-purple-500"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Inactive</div>
            <div class="text-2xl font-semibold text-gray-600">{{ stats.inactive }}</div>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-50">
            <i class="pi pi-ban text-gray-500"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Filters -->
    <div class="flex gap-3 flex-wrap">
      <IconField iconPosition="left" class="flex-1">
        <InputIcon>
          <i class="pi pi-search" />
        </InputIcon>
        <InputText v-model="filters.search" placeholder="Search departments..." class="w-full" size="small" />
      </IconField>
      <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
        placeholder="All Status" showClear class="w-48" size="small" />
      <Button v-if="filters.search || filters.status" label="Clear" icon="pi pi-filter-slash" severity="secondary" text
        size="small" @click="clearFilters" />
    </div>

    <!-- Departments Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <DataTable :value="filteredDepartments" :loading="loading" paginator :rows="10"
        :rowsPerPageOptions="[5, 10, 20, 50]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} departments" rowHover showGridlines
        sortMode="multiple" class="text-sm">
        <Column field="name" header="Department Name" sortable style="min-width: 200px">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <i class="pi pi-building text-blue-500"></i>
              <div>
                <div class="font-medium">{{ data.name }}</div>
                <div v-if="data.code" class="text-xs text-gray-500">{{ data.code }}</div>
              </div>
            </div>
          </template>
        </Column>

        <Column field="description" header="Description" style="min-width: 250px">
          <template #body="{ data }">
            <span class="text-gray-600">{{ data.description || 'N/A' }}</span>
          </template>
        </Column>

        <Column field="manager_name" header="Manager" sortable style="min-width: 180px">
          <template #body="{ data }">
            <div v-if="data.manager_name" class="flex items-center gap-2">
              <Avatar :label="getInitials(data.manager_name)" size="small" shape="circle"
                class="bg-purple-100 text-purple-600" />
              <span>{{ data.manager_name }}</span>
            </div>
            <span v-else class="text-gray-400">No Manager</span>
          </template>
        </Column>

        <Column field="employee_count" header="Employees" sortable style="width: 120px">
          <template #body="{ data }">
            <Tag :value="data.employee_count || 0" severity="info" />
          </template>
        </Column>

        <Column field="status" header="Status" sortable style="width: 120px">
          <template #body="{ data }">
            <Tag :value="data.status" :severity="data.status === 'active' ? 'success' : 'secondary'" />
          </template>
        </Column>

        <Column field="created_at" header="Created Date" sortable style="width: 140px">
          <template #body="{ data }">
            {{ formatDate(data.created_at) }}
          </template>
        </Column>

        <Column header="Actions" :exportable="false" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button icon="pi pi-eye" severity="info" text rounded size="small" @click="viewDepartment(data)"
                v-tooltip.top="'View Details'" />
              <Button icon="pi pi-pencil" severity="contrast" text rounded size="small" @click="editDepartment(data)"
                v-tooltip.top="'Edit'" />
              <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="confirmDelete(data)"
                v-tooltip.top="'Delete'" />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-12">
            <i class="pi pi-building text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No departments found</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Add/Edit Department Dialog -->
    <Dialog v-model:visible="showFormDialog" modal :header="isEditMode ? 'Edit Department' : 'Add Department'"
      :style="{ width: '500px' }" :draggable="false">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Department Name *</label>
          <InputText v-model="formData.name" placeholder="Enter department name" class="w-full" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Department Code</label>
          <InputText v-model="formData.code" placeholder="e.g., HR, IT, FIN" class="w-full" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <Textarea v-model="formData.description" rows="3" placeholder="Enter department description"
            class="w-full" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Manager (Optional)</label>
          <Select v-model="formData.manager_id" :options="employees" optionLabel="full_name" optionValue="id"
            placeholder="Select manager" showClear filter class="w-full" />
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <Select v-model="formData.status" :options="statusOptions" optionLabel="label" optionValue="value"
            placeholder="Select status" class="w-full" />
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" severity="secondary" @click="showFormDialog = false" />
        <Button :label="isEditMode ? 'Update' : 'Create'" severity="info" @click="saveDepartment" :loading="saving" />
      </template>
    </Dialog>

    <!-- View Department Dialog -->
    <Dialog v-model:visible="showViewDialog" modal header="Department Details" :style="{ width: '600px' }"
      :draggable="false">
      <div v-if="selectedDepartment" class="space-y-4">
        <div class="bg-blue-50 p-4 rounded-lg">
          <h3 class="text-lg font-semibold text-blue-900">{{ selectedDepartment.name }}</h3>
          <p v-if="selectedDepartment.code" class="text-sm text-blue-600 mt-1">Code: {{ selectedDepartment.code }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500 mb-1">Status</div>
            <Tag :value="selectedDepartment.status"
              :severity="selectedDepartment.status === 'active' ? 'success' : 'secondary'" />
          </div>
          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500 mb-1">Total Employees</div>
            <div class="font-medium text-lg">{{ selectedDepartment.employee_count || 0 }}</div>
          </div>
        </div>

        <div class="bg-gray-50 p-3 rounded-lg">
          <div class="text-xs text-gray-500 mb-1">Manager</div>
          <div v-if="selectedDepartment.manager_name" class="flex items-center gap-2 mt-2">
            <Avatar :label="getInitials(selectedDepartment.manager_name)" size="normal" shape="circle"
              class="bg-purple-100 text-purple-600" />
            <span class="font-medium">{{ selectedDepartment.manager_name }}</span>
          </div>
          <span v-else class="text-gray-400">No manager assigned</span>
        </div>

        <div v-if="selectedDepartment.description" class="bg-gray-50 p-3 rounded-lg">
          <div class="text-xs text-gray-500 mb-1">Description</div>
          <p class="text-sm">{{ selectedDepartment.description }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500 mb-1">Created Date</div>
            <div class="font-medium">{{ formatDate(selectedDepartment.created_at) }}</div>
          </div>
          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500 mb-1">Last Updated</div>
            <div class="font-medium">{{ formatDate(selectedDepartment.updated_at) }}</div>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Close" severity="secondary" @click="showViewDialog = false" />
        <Button label="Edit" icon="pi pi-pencil" severity="info" @click="editFromView" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from 'axios'
import { useAuthStore } from '../../../stores/auth'

// Interfaces
interface Department {
  id: number
  name: string
  code?: string
  description?: string
  manager_id?: number
  manager_name?: string
  employee_count?: number
  status: 'active' | 'inactive'
  created_at: string
  updated_at: string
}

interface Employee {
  id: number
  full_name: string
}

// State
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const showFormDialog = ref(false)
const showViewDialog = ref(false)
const isEditMode = ref(false)
const departments = ref<Department[]>([])
const employees = ref<Employee[]>([])
const selectedDepartment = ref<Department | null>(null)

// Filters
const filters = ref({
  search: '',
  status: null as string | null
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

// Form Data
const formData = ref({
  name: '',
  code: '',
  description: '',
  manager_id: null as number | null,
  status: 'active'
})

// Computed
const filteredDepartments = computed(() => {
  return departments.value.filter(dept => {
    const matchesSearch = !filters.value.search ||
      dept.name.toLowerCase().includes(filters.value.search.toLowerCase()) ||
      dept.code?.toLowerCase().includes(filters.value.search.toLowerCase())
    const matchesStatus = !filters.value.status || dept.status === filters.value.status
    return matchesSearch && matchesStatus
  })
})

const stats = computed(() => {
  return {
    total: departments.value.length,
    active: departments.value.filter(d => d.status === 'active').length,
    inactive: departments.value.filter(d => d.status === 'inactive').length,
    totalEmployees: departments.value.reduce((sum, d) => sum + (d.employee_count || 0), 0)
  }
})

// Methods
const fetchDepartments = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/departments', {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    })
    if (response.data.success) {
      departments.value = response.data.data
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to fetch departments',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchEmployees = async () => {
  try {
    const response = await axios.get('/api/employees', {
      headers: { 'Authorization': `Bearer ${authStore.token}` },
      params: { per_page: 1000 }
    })
    if (response.data.success) {
      employees.value = (response.data.data.data || response.data.data).map((emp: any) => ({
        id: emp.id,
        full_name: `${emp.fname} ${emp.lname}`
      }))
    }
  } catch (error) {
    console.error('Failed to fetch employees:', error)
  }
}

const openAddDialog = () => {
  isEditMode.value = false
  resetForm()
  showFormDialog.value = true
}

const editDepartment = (dept: Department) => {
  isEditMode.value = true
  formData.value = {
    name: dept.name,
    code: dept.code || '',
    description: dept.description || '',
    manager_id: dept.manager_id || null,
    status: dept.status
  }
  selectedDepartment.value = dept
  showFormDialog.value = true
}

const editFromView = () => {
  if (selectedDepartment.value) {
    showViewDialog.value = false
    editDepartment(selectedDepartment.value)
  }
}

const viewDepartment = (dept: Department) => {
  selectedDepartment.value = dept
  showViewDialog.value = true
}

const saveDepartment = async () => {
  if (!formData.value.name.trim()) {
    toast.add({
      severity: 'warn',
      summary: 'Validation Error',
      detail: 'Department name is required',
      life: 3000
    })
    return
  }

  saving.value = true
  try {
    const url = isEditMode.value ? `/api/departments/${selectedDepartment.value?.id}` : '/api/departments'
    const method = isEditMode.value ? 'put' : 'post'

    const response = await axios[method](url, formData.value, {
      headers: { 'Authorization': `Bearer ${authStore.token}` }
    })

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Department ${isEditMode.value ? 'updated' : 'created'} successfully`,
        life: 3000
      })
      showFormDialog.value = false
      await fetchDepartments()
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || `Failed to ${isEditMode.value ? 'update' : 'create'} department`,
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

const confirmDelete = (dept: Department) => {
  confirm.require({
    message: `Are you sure you want to delete "${dept.name}"?`,
    header: 'Confirm Deletion',
    icon: 'pi pi-exclamation-triangle',
    acceptClass: 'p-button-danger',
    accept: async () => {
      try {
        const response = await axios.delete(`/api/departments/${dept.id}`, {
          headers: { 'Authorization': `Bearer ${authStore.token}` }
        })
        if (response.data.success) {
          toast.add({
            severity: 'success',
            summary: 'Deleted',
            detail: 'Department deleted successfully',
            life: 3000
          })
          await fetchDepartments()
        }
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to delete department',
          life: 3000
        })
      }
    }
  })
}

const resetForm = () => {
  formData.value = {
    name: '',
    code: '',
    description: '',
    manager_id: null,
    status: 'active'
  }
}

const clearFilters = () => {
  filters.value.search = ''
  filters.value.status = null
}

const formatDate = (dateString: string): string => {
  if (!dateString) return 'N/A'
  try {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  } catch {
    return dateString
  }
}

const getInitials = (name: string): string => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

// Lifecycle
onMounted(() => {
  fetchDepartments()
  fetchEmployees()
})
</script>
