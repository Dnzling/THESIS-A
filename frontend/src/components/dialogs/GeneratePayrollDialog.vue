<template>
  <div class="generate-payroll-modal">

    <!-- Loading state -->
    <div v-if="loadingData" class="flex items-center justify-center py-10">
      <i class="pi pi-spin pi-spinner text-3xl text-blue-500 mr-3"></i>
      <span class="text-gray-600">Loading pay periods and employees...</span>
    </div>

    <template v-else>
      <!-- Step 1: Period Selection -->
      <div class="mb-4">
        <label class="font-bold block mb-2">Pay Period <span class="text-red-500">*</span></label>
        <Select
          v-model="formData.periodId"
          :options="availablePayPeriods"
          optionLabel="period"
          optionValue="id"
          placeholder="Select Pay Period"
          class="w-full"
          :class="{ 'p-invalid': !formData.periodId && submitted }"
          @change="handlePeriodChange"
        />
        <small v-if="!formData.periodId && submitted" class="text-red-500">Pay period is required</small>
        <small v-if="availablePayPeriods.length === 0" class="text-amber-500">
          No pay periods found. Please create a pay period first.
        </small>
      </div>

      <!-- Step 2: Generation Method -->
      <div class="mb-4">
        <label class="font-bold block mb-2">Generate For</label>
        <div class="flex gap-2">
          <Button
            :label="`All Active Employees (${readyCount})`"
            :class="{ 'p-button-outlined': generationMethod !== 'all' }"
            severity="info"
            @click="generationMethod = 'all'"
            class="flex-1"
          />
          <Button
            label="Specific Selection"
            :class="{ 'p-button-outlined': generationMethod !== 'specific' }"
            severity="info"
            @click="generationMethod = 'specific'"
            class="flex-1"
          />
        </div>
      </div>

      <!-- Step 3: Filters for Specific Selection -->
      <div v-if="generationMethod === 'specific'" class="filters-section border rounded p-3 mb-4">
        <h4 class="font-medium mb-3">Filter Employees</h4>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block mb-1 text-sm">Branch</label>
            <Select
              v-model="filters.branch"
              :options="branches"
              placeholder="All Branches"
              showClear
              class="w-full"
              @change="applyFilters"
            />
          </div>

          <div>
            <label class="block mb-1 text-sm">Department</label>
            <Select
              v-model="filters.department"
              :options="departments"
              placeholder="All Departments"
              showClear
              class="w-full"
              @change="applyFilters"
            />
          </div>

          <div>
            <label class="block mb-1 text-sm">Employment Type</label>
            <Select
              v-model="filters.employmentType"
              :options="employmentTypeOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="All Types"
              showClear
              class="w-full"
              @change="applyFilters"
            />
          </div>
        </div>

        <!-- Employee List Preview -->
        <div class="mt-3">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium">{{ filteredEmployees.length }} Employees Found</span>
            <div class="flex gap-2">
              <Button
                label="Select All"
                severity="info"
                text
                size="small"
                @click="selectAllFiltered"
              />
              <Button
                label="Clear All"
                severity="secondary"
                text
                size="small"
                @click="selectedEmployeeIds = []"
              />
            </div>
          </div>

          <div class="employee-list border rounded max-h-48 overflow-y-auto">
            <div
              v-for="emp in filteredEmployees"
              :key="emp.id"
              class="flex items-center p-2 hover:bg-gray-50 border-b last:border-b-0 cursor-pointer"
              @click="toggleEmployee(emp)"
            >
              <Checkbox
                :inputId="'emp_' + emp.id"
                :value="emp.id"
                v-model="selectedEmployeeIds"
              />
              <label :for="'emp_' + emp.id" class="ml-2 flex-1 cursor-pointer">
                <div class="font-medium">{{ emp.full_name }}</div>
                <small class="text-gray-500">
                  {{ emp.employee_number }} • {{ emp.branch }} • {{ emp.department }}
                </small>
              </label>
              <div class="text-right">
                <Tag :value="emp.employment_type" severity="info" size="small" />
                <div class="text-xs text-gray-500 mt-1">{{ formatCurrency(emp.monthly_salary) }}/mo</div>
              </div>
            </div>

            <div v-if="filteredEmployees.length === 0" class="text-center py-4 text-gray-500 text-sm">
              No employees match the selected filters.
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Section -->
      <div v-if="showSummary" class="summary-section bg-blue-50 p-3 rounded mb-4">
        <h4 class="font-bold mb-2">Generation Summary</h4>
        <div class="grid grid-cols-2 gap-2 text-sm">
          <div>Employees to process:</div>
          <div class="font-bold">{{ employeeCount }}</div>

          <div>Estimated Gross Pay:</div>
          <div class="font-bold">{{ formatCurrency(estimatedGross) }}</div>

          <div>Estimated Net Pay (approx):</div>
          <div class="font-bold text-blue-600">{{ formatCurrency(estimatedNet) }}</div>
        </div>
      </div>

      <!-- Generation Options -->
      <div class="options-section border rounded p-3 mb-4">
        <h4 class="font-medium mb-2">Generation Options</h4>

        <div class="flex flex-col gap-2">
          <!-- Draft / Processing toggle -->
          <div class="flex items-center gap-3 p-2 rounded-lg" :class="options.saveAsDraft ? 'bg-gray-50' : 'bg-blue-50'">
            <Checkbox v-model="options.saveAsDraft" inputId="saveAsDraft" binary />
            <div>
              <label for="saveAsDraft" class="text-sm font-medium cursor-pointer select-none">
                Save as Draft
              </label>
              <p class="text-xs mt-0.5" :class="options.saveAsDraft ? 'text-gray-500' : 'text-blue-600'">
                <span v-if="options.saveAsDraft">Payrolls will be saved as <strong>Draft</strong> — HR can review before submitting for approval.</span>
                <span v-else>Payrolls will be created directly as <strong>Processing</strong> — submitted for approval immediately.</span>
              </p>
            </div>
          </div>

          <div class="flex items-center">
            <Checkbox v-model="options.recalculate" inputId="recalculate" binary />
            <label for="recalculate" class="ml-2 text-sm">
              Recalculate existing payrolls for this period
            </label>
          </div>

          <div class="flex items-center">
            <Checkbox v-model="options.sendNotification" inputId="sendNotification" binary />
            <label for="sendNotification" class="ml-2 text-sm">
              Notify employees when payroll is approved
            </label>
          </div>
        </div>
      </div>

      <!-- Error display -->
      <div v-if="generationErrors.length > 0" class="mb-4 p-3 bg-red-50 border border-red-200 rounded">
        <h4 class="font-medium text-red-700 mb-2">
          <i class="pi pi-exclamation-triangle mr-1"></i>
          {{ generationErrors.length }} employee(s) had errors:
        </h4>
        <ul class="text-sm text-red-600 list-disc list-inside">
          <li v-for="(err, i) in generationErrors" :key="i">
            <strong>{{ err.employee }}</strong>: {{ err.error }}
          </li>
        </ul>
      </div>

      <!-- Footer Buttons -->
      <div class="flex justify-end gap-2 mt-4">
        <Button
          label="Cancel"
          severity="secondary"
          outlined
          @click="$emit('close')"
          :disabled="generating"
        />
        <Button
          label="Generate Payroll"
          severity="info"
          icon="pi pi-cog"
          @click="generatePayroll"
          :loading="generating"
          :disabled="!isValid || generating"
        />
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import { useAuthStore } from '../../stores/auth'
import Select from 'primevue/select'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Tag from 'primevue/tag'

// ==================== INTERFACES ====================
interface ApiEmployee {
  id: number
  employee_number: string
  full_name: string
  first_name: string
  last_name: string
  branch: string
  department: string
  employment_type: string
  status: string
  monthly_salary: number
}

interface PayPeriod {
  id: number
  period: string       // formatted label from PayPeriodResource
  cutoffStart: string
  cutoffEnd: string
  payDate: string
  status: string
  notes: string | null
}

interface Filters {
  branch: string | null
  department: string | null
  employmentType: string | null
}

interface GenerationOptions {
  saveAsDraft: boolean
  recalculate: boolean
  sendNotification: boolean
}

// ==================== PROPS & EMITS ====================
const props = defineProps<{
  initialPeriodId?: number
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'generated', result: any): void
}>()

// ==================== STORES ====================
const toast = useToast()
const authStore = useAuthStore()

// ==================== STATE ====================
const loadingData = ref(false)
const submitted = ref(false)
const generating = ref(false)

const generationMethod = ref<'all' | 'specific'>('all')
const selectedEmployeeIds = ref<number[]>([])
const generationErrors = ref<{ employee: string; error: string }[]>([])

const formData = ref({
  periodId: props.initialPeriodId ?? null as number | null
})

const filters = ref<Filters>({
  branch: null,
  department: null,
  employmentType: null
})

const options = ref<GenerationOptions>({
  saveAsDraft: true,
  recalculate: false,
  sendNotification: false
})

// Data from API
const availablePayPeriods = ref<PayPeriod[]>([])
const allEmployees = ref<ApiEmployee[]>([])

// Static options
const employmentTypeOptions = ref([
  { label: 'Full Time', value: 'full_time' },
  { label: 'Part Time', value: 'part_time' },
  { label: 'Contract', value: 'contract' },
  { label: 'Intern', value: 'intern' },
])

// ==================== COMPUTED ====================

/** Unique branches derived from employees */
const branches = computed(() =>
  [...new Set(allEmployees.value.map(e => e.branch).filter(Boolean))]
)

/** Unique departments derived from employees */
const departments = computed(() =>
  [...new Set(allEmployees.value.map(e => e.department).filter(Boolean))]
)

/** Active employees only */
const readyEmployees = computed(() =>
  allEmployees.value.filter(e => e.status === 'active')
)

const readyCount = computed(() => readyEmployees.value.length)

/** Employees filtered by the current filter selections */
const filteredEmployees = computed(() => {
  if (generationMethod.value !== 'specific') return []

  return readyEmployees.value.filter(emp => {
    const matchesBranch = !filters.value.branch || emp.branch === filters.value.branch
    const matchesDept = !filters.value.department || emp.department === filters.value.department
    const matchesType = !filters.value.employmentType || emp.employment_type === filters.value.employmentType
    return matchesBranch && matchesDept && matchesType
  })
})

/** Number of employees that will be processed */
const employeeCount = computed(() => {
  if (generationMethod.value === 'all') return readyCount.value
  return selectedEmployeeIds.value.length
})

const showSummary = computed(() =>
  !!formData.value.periodId && employeeCount.value > 0
)

const isValid = computed(() => {
  if (!formData.value.periodId) return false
  if (generationMethod.value === 'specific' && selectedEmployeeIds.value.length === 0) return false
  return true
})

/** Rough estimated gross (sum of monthly salaries) */
const estimatedGross = computed(() => {
  if (generationMethod.value === 'all') {
    return readyEmployees.value.reduce((sum, e) => sum + (e.monthly_salary ?? 0), 0)
  }
  return allEmployees.value
    .filter(e => selectedEmployeeIds.value.includes(e.id))
    .reduce((sum, e) => sum + (e.monthly_salary ?? 0), 0)
})

/** Rough estimated net (gross minus ~20% for taxes/deductions) */
const estimatedNet = computed(() => estimatedGross.value * 0.8)

// ==================== METHODS ====================

const formatCurrency = (value: number): string => {
  if (!value) return '₱0.00'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(value)
}

/** Ensure auth token is set on axios */
const ensureAuthHeader = () => {
  const token = authStore.token || localStorage.getItem('auth_token')
  if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
  }
}

/** Fetch available pay periods from GET /api/payroll/periods */
const fetchPayPeriods = async () => {
  try {
    const response = await axios.get('/api/payroll/periods')
    if (response.data.success) {
      // Only show draft/processing periods (not locked/completed)
      availablePayPeriods.value = (response.data.data as PayPeriod[]).filter(
        p => p.status === 'Draft' || p.status === 'Processing' || p.status === 'draft' || p.status === 'processing'
      )
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message ?? 'Failed to load pay periods',
      life: 3000
    })
  }
}

/** Fetch employees from GET /api/payroll/getEmployeesBasicSalary */
const fetchEmployees = async () => {
  try {
    const response = await axios.get('/api/payroll/getEmployeesBasicSalary')
    if (response.data.success) {
      allEmployees.value = response.data.data as ApiEmployee[]
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message ?? 'Failed to load employees',
      life: 3000
    })
  }
}

const handlePeriodChange = () => {
  // Reset errors when period changes
  generationErrors.value = []
}

const applyFilters = () => {
  // Filters are applied via computed property; clear selection on filter change
  selectedEmployeeIds.value = []
}

const selectAllFiltered = () => {
  selectedEmployeeIds.value = filteredEmployees.value.map(e => e.id)
}

const toggleEmployee = (emp: ApiEmployee) => {
  const idx = selectedEmployeeIds.value.indexOf(emp.id)
  if (idx === -1) {
    selectedEmployeeIds.value.push(emp.id)
  } else {
    selectedEmployeeIds.value.splice(idx, 1)
  }
}

/** Call POST /api/payroll/generate */
const generatePayroll = async () => {
  submitted.value = true
  generationErrors.value = []

  if (!isValid.value) {
    toast.add({
      severity: 'warn',
      summary: 'Validation',
      detail: formData.value.periodId
        ? 'Please select at least one employee.'
        : 'Please select a pay period.',
      life: 3000
    })
    return
  }

  generating.value = true

  try {
    const payload: Record<string, any> = {
      pay_period_id:  formData.value.periodId,
      recalculate:    options.value.recalculate,
      initial_status: options.value.saveAsDraft ? 'draft' : 'processing',
    }

    if (generationMethod.value === 'specific') {
      payload.employee_ids = selectedEmployeeIds.value
    }

    const response = await axios.post('/api/payroll/generate', payload)

    if (response.data.success) {
      const { generated, errors } = response.data.data

      if (errors && errors.length > 0) {
        generationErrors.value = errors
      }

      toast.add({
        severity: errors?.length > 0 ? 'warn' : 'success',
        summary: errors?.length > 0 ? 'Partial Success' : 'Success',
        detail: `Payroll generated for ${generated} employee(s).${errors?.length > 0 ? ` ${errors.length} error(s) occurred.` : ''}`,
        life: 5000
      })

      emit('generated', response.data.data)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.data.message ?? 'Failed to generate payroll',
        life: 4000
      })
    }
  } catch (error: any) {
    const message = error.response?.data?.message ?? 'Failed to generate payroll'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: message,
      life: 4000
    })
  } finally {
    generating.value = false
  }
}

// ==================== LIFECYCLE ====================
onMounted(async () => {
  ensureAuthHeader()
  loadingData.value = true
  try {
    await Promise.all([fetchPayPeriods(), fetchEmployees()])
  } finally {
    loadingData.value = false
  }
})
</script>

<style scoped>
.generate-payroll-modal {
  container-type: inline-size;
}

.filters-section {
  background-color: #f8f9fa;
}

.employee-list {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f1f5f9;
}

.employee-list::-webkit-scrollbar {
  width: 6px;
}

.employee-list::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.employee-list::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 3px;
}

@container (max-width: 400px) {
  .grid-cols-2 {
    grid-template-columns: 1fr;
  }
}
</style>
