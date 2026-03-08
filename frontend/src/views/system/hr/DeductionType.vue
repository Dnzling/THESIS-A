<template>
  <div class=" mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-gray-800">Deduction Types</h1>
        <p class="text-sm text-gray-500 mt-1">Manage company deduction types and configurations</p>
      </div>
      <Button label="Add Deduction Type" icon="pi pi-plus" severity="info" @click="openCreateDialog" />
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center jusstify-between">
          <div>
            <div class="text-sm text-gray-500">Total Types</div>
            <div class="text-2xl font-semibold text-gray-800">{{ deductionTypes.length }}</div>
          </div>
          <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-list text-blue-500"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Active</div>
            <div class="text-2xl font-semibold text-green-600">{{ activeCount }}</div>
          </div>
          <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-check-circle text-green-500"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Mandatory</div>
            <div class="text-2xl font-semibold text-orange-600">{{ mandatoryCount }}</div>
          </div>
          <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-lock text-orange-500"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-500">Taxable</div>
            <div class="text-2xl font-semibold text-purple-600">{{ taxableCount }}</div>
          </div>
          <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center">
            <i class="pi pi-percentage text-purple-500"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
      <div class="flex flex-wrap gap-4">
        <IconField>
          <InputIcon class="pi pi-search" />
          <InputText v-model="filters.search" placeholder="Search..." class="w-64" @input="fetchDeductionTypes" />
        </IconField>

        <Select
          v-model="filters.category"
          :options="categoryOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="All Categories"
          showClear
          class="w-48"
          @change="fetchDeductionTypes"
        />

        <Select
          v-model="filters.calculationType"
          :options="calculationTypeOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="All Calculation Types"
          showClear
          class="w-48"
          @change="fetchDeductionTypes"
        />

        <Select
          v-model="filters.isActive"
          :options="statusOptions"
          optionLabel="label"
          optionValue="value"
          placeholder="All Status"
          showClear
          class="w-40"
          @change="fetchDeductionTypes"
        />
      </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <DataTable
        :value="deductionTypes"
        :loading="loading"
        :paginator="true"
        :rows="10"
        :rowsPerPageOptions="[5, 10, 20, 50]"
        paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageSelect"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
        rowHover
        responsiveLayout="scroll"
        class="text-sm"
      >
        <Column field="code" header="Code" sortable style="width: 100px">
          <template #body="{ data }">
            <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">{{ data.code }}</span>
          </template>
        </Column>

        <Column field="name" header="Name" sortable style="min-width: 200px">
          <template #body="{ data }">
            <div class="font-medium text-gray-800">{{ data.name }}</div>
            <div v-if="data.description" class="text-xs text-gray-500">{{ data.description }}</div>
          </template>
        </Column>

        <Column field="category" header="Category" sortable style="width: 120px">
          <template #body="{ data }">
            <Tag :value="formatCategory(data.category)" :severity="getCategorySeverity(data.category)" />
          </template>
        </Column>

        <Column field="calculation_type" header="Calculation" sortable style="width: 120px">
          <template #body="{ data }">
            <div class="flex items-center gap-2">
              <i :class="getCalculationIcon(data.calculation_type)" class="text-gray-500"></i>
              <span>{{ formatCalculationType(data.calculation_type) }}</span>
            </div>
          </template>
        </Column>

        <Column header="Amount / Rate" style="width: 150px">
          <template #body="{ data }">
            <div v-if="data.calculation_type === 'fixed'">
              <span class="font-medium">₱{{ formatNumber(data.default_amount) }}</span>
            </div>
            <div v-else-if="data.calculation_type === 'percentage'">
              <span class="font-medium">{{ data.percentage_value }}%</span>
              <div class="text-xs text-gray-500">of {{ data.percentage_basis }}</div>
            </div>
            <div v-else>
              <span class="text-gray-500 text-xs">Formula-based</span>
            </div>
          </template>
        </Column>

        <Column field="frequency" header="Frequency" sortable style="width: 120px">
          <template #body="{ data }">
            <span class="capitalize">{{ data.frequency || 'Monthly' }}</span>
          </template>
        </Column>

        <Column header="Flags" style="width: 150px">
          <template #body="{ data }">
            <div class="flex flex-wrap gap-1">
              <Tag v-if="data.is_mandatory" value="Mandatory" severity="warning" size="small" />
              <Tag v-if="data.is_taxable" value="Taxable" severity="help" size="small" />
              <Tag v-if="data.show_on_payslip" value="Payslip" severity="info" size="small" />
            </div>
          </template>
        </Column>

        <Column field="is_active" header="Status" sortable style="width: 100px">
          <template #body="{ data }">
            <Tag
              :value="data.is_active ? 'Active' : 'Inactive'"
              :severity="data.is_active ? 'success' : 'danger'"
            />
          </template>
        </Column>

        <Column header="Actions" style="width: 120px">
          <template #body="{ data }">
            <div class="flex gap-1">
              <Button
                icon="pi pi-pencil"
                text
                rounded
                severity="info"
                size="small"
                @click="openEditDialog(data)"
                v-tooltip.top="'Edit'"
              />
              <Button
                icon="pi pi-eye"
                text
                rounded
                severity="success"
                size="small"
                @click="viewDetails(data)"
                v-tooltip.top="'View Details'"
              />
              <Button
                :icon="data.is_active ? 'pi pi-pause' : 'pi pi-play'"
                text
                rounded
                :severity="data.is_active ? 'warning' : 'secondary'"
                size="small"
                @click="toggleActive(data)"
                v-tooltip.top="data.is_active ? 'Deactivate' : 'Activate'"
              />
              <Button
                icon="pi pi-trash"
                text
                rounded
                severity="danger"
                size="small"
                @click="confirmDelete(data)"
                v-tooltip.top="'Delete'"
                :disabled="data.employee_deductions_count > 0"
              />
            </div>
          </template>
        </Column>

        <template #empty>
          <div class="text-center py-12">
            <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">No deduction types found</p>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Create/Edit Dialog -->
    <Dialog
      v-model:visible="showDialog"
      :header="isEditing ? 'Edit Deduction Type' : 'Create Deduction Type'"
      modal
      :style="{ width: '600px' }"
      :closable="true"
      class="deduction-dialog"
    >
      <div class="space-y-4">
        <!-- Basic Info -->
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Code *</label>
            <InputText v-model="form.code" class="w-full" placeholder="e.g., SSS" :disabled="isEditing" />
            <small v-if="errors.code" class="text-red-500">{{ errors.code[0] }}</small>
          </div>
          <div class="col-span-2 md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
            <InputText v-model="form.name" class="w-full" placeholder="e.g., SSS Contribution" />
            <small v-if="errors.name" class="text-red-500">{{ errors.name[0] }}</small>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
          <Textarea v-model="form.description" class="w-full" rows="2" placeholder="Optional description..." />
        </div>

        <!-- Category & Calculation Type -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
            <Select
              v-model="form.category"
              :options="categoryOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
            />
            <small v-if="errors.category" class="text-red-500">{{ errors.category[0] }}</small>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Calculation Type *</label>
            <Select
              v-model="form.calculation_type"
              :options="calculationTypeOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
            />
            <small v-if="errors.calculation_type" class="text-red-500">{{ errors.calculation_type[0] }}</small>
          </div>
        </div>

        <!-- Dynamic Fields Based on Calculation Type -->
        <div v-if="form.calculation_type === 'fixed'" class="bg-gray-50 p-4 rounded-lg">
          <label class="block text-sm font-medium text-gray-700 mb-1">Default Amount *</label>
          <InputNumber
            v-model="form.default_amount"
            mode="currency"
            currency="PHP"
            locale="en-PH"
            class="w-full"
            placeholder="0.00"
          />
          <small v-if="errors.default_amount" class="text-red-500">{{ errors.default_amount[0] }}</small>
        </div>

        <div v-else-if="form.calculation_type === 'percentage'" class="bg-gray-50 p-4 rounded-lg space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Percentage Value *</label>
              <InputNumber
                v-model="form.percentage_value"
                :min="0"
                :max="100"
                suffix="%"
                class="w-full"
                placeholder="0"
              />
              <small v-if="errors.percentage_value" class="text-red-500">{{ errors.percentage_value[0] }}</small>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Basis *</label>
              <Select
                v-model="form.percentage_basis"
                :options="basisOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
              />
              <small v-if="errors.percentage_basis" class="text-red-500">{{ errors.percentage_basis[0] }}</small>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Min Amount</label>
              <InputNumber
                v-model="form.min_amount"
                mode="currency"
                currency="PHP"
                locale="en-PH"
                class="w-full"
                placeholder="0.00"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Max Amount</label>
              <InputNumber
                v-model="form.max_amount"
                mode="currency"
                currency="PHP"
                locale="en-PH"
                class="w-full"
                placeholder="0.00"
              />
            </div>
          </div>
        </div>

        <div v-else-if="form.calculation_type === 'formula'" class="bg-gray-50 p-4 rounded-lg">
          <label class="block text-sm font-medium text-gray-700 mb-1">Formula (JSON)</label>
          <Textarea
            v-model="formulaJson"
            class="w-full font-mono text-sm"
            rows="4"
            placeholder='{"formula": "basic_salary * 0.02", "variables": ["basic_salary"]}'
          />
          <small class="text-gray-500">Enter formula as JSON with variables</small>
        </div>

        <!-- Frequency -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
          <Select
            v-model="form.frequency"
            :options="frequencyOptions"
            optionLabel="label"
            optionValue="value"
            class="w-full"
          />
        </div>

        <!-- Toggles -->
        <div class="grid grid-cols-2 gap-4 pt-2">
          <div class="flex items-center gap-2">
            <Checkbox v-model="form.is_mandatory" inputId="mandatory" binary />
            <label for="mandatory" class="text-sm text-gray-700">Mandatory Deduction</label>
          </div>
          <div class="flex items-center gap-2">
            <Checkbox v-model="form.is_taxable" inputId="taxable" binary />
            <label for="taxable" class="text-sm text-gray-700">Taxable</label>
          </div>
          <div class="flex items-center gap-2">
            <Checkbox v-model="form.show_on_payslip" inputId="payslip" binary />
            <label for="payslip" class="text-sm text-gray-700">Show on Payslip</label>
          </div>
          <div class="flex items-center gap-2">
            <Checkbox v-model="form.is_active" inputId="active" binary />
            <label for="active" class="text-sm text-gray-700">Active</label>
          </div>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" severity="secondary" @click="closeDialog" />
        <Button :label="isEditing ? 'Update' : 'Create'" severity="info" @click="saveDeductionType" :loading="saving" />
      </template>
    </Dialog>

    <!-- View Details Dialog -->
    <Dialog
      v-model:visible="showDetailsDialog"
      :header="'Deduction Type Details'"
      modal
      :style="{ width: '500px' }"
    >
      <div v-if="selectedDeduction" class="space-y-4">
        <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <i class="pi pi-list text-blue-600 text-xl"></i>
          </div>
          <div>
            <div class="font-semibold text-lg text-gray-800">{{ selectedDeduction.name }}</div>
            <div class="text-sm text-gray-500">{{ selectedDeduction.code }}</div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500">Category</div>
            <Tag :value="formatCategory(selectedDeduction.category)" :severity="getCategorySeverity(selectedDeduction.category)" />
          </div>

          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500">Calculation</div>
            <div class="font-medium">{{ formatCalculationType(selectedDeduction.calculation_type) }}</div>
          </div>

          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500">Frequency</div>
            <div class="font-medium capitalize">{{ selectedDeduction.frequency || 'Monthly' }}</div>
          </div>

          <div class="bg-gray-50 p-3 rounded-lg">
            <div class="text-xs text-gray-500">Status</div>
            <Tag :value="selectedDeduction.is_active ? 'Active' : 'Inactive'" :severity="selectedDeduction.is_active ? 'success' : 'danger'" />
          </div>
        </div>

        <div v-if="selectedDeduction.calculation_type === 'fixed'" class="bg-gray-50 p-3 rounded-lg">
          <div class="text-xs text-gray-500">Default Amount</div>
          <div class="text-xl font-semibold text-gray-800">₱{{ formatNumber(selectedDeduction.default_amount) }}</div>
        </div>

        <div v-else-if="selectedDeduction.calculation_type === 'percentage'" class="bg-gray-50 p-3 rounded-lg space-y-2">
          <div class="flex justify-between">
            <div class="text-xs text-gray-500">Percentage</div>
            <div class="font-medium">{{ selectedDeduction.percentage_value }}%</div>
          </div>
          <div class="flex justify-between">
            <div class="text-xs text-gray-500">Basis</div>
            <div class="font-medium capitalize">{{ selectedDeduction.percentage_basis }}</div>
          </div>
          <div v-if="selectedDeduction.min_amount" class="flex justify-between">
            <div class="text-xs text-gray-500">Min Amount</div>
            <div class="font-medium">₱{{ formatNumber(selectedDeduction.min_amount) }}</div>
          </div>
          <div v-if="selectedDeduction.max_amount" class="flex justify-between">
            <div class="text-xs text-gray-500">Max Amount</div>
            <div class="font-medium">₱{{ formatNumber(selectedDeduction.max_amount) }}</div>
          </div>
        </div>

        <div v-if="selectedDeduction.description" class="bg-gray-50 p-3 rounded-lg">
          <div class="text-xs text-gray-500 mb-1">Description</div>
          <div class="text-sm text-gray-700">{{ selectedDeduction.description }}</div>
        </div>

        <div class="flex flex-wrap gap-2">
          <Tag v-if="selectedDeduction.is_mandatory" value="Mandatory" severity="warning" />
          <Tag v-if="selectedDeduction.is_taxable" value="Taxable" severity="help" />
          <Tag v-if="selectedDeduction.show_on_payslip" value="Show on Payslip" severity="info" />
        </div>

        <div class="text-xs text-gray-400 pt-2 border-t">
          <div>ID: {{ selectedDeduction.id }}</div>
          <div>Created: {{ formatDateTime(selectedDeduction.created_at) }}</div>
          <div v-if="selectedDeduction.updated_at">Updated: {{ formatDateTime(selectedDeduction.updated_at) }}</div>
        </div>
      </div>

      <template #footer>
        <Button label="Close" severity="secondary" @click="showDetailsDialog = false" />
        <Button label="Edit" icon="pi pi-pencil" severity="info" @click="editFromDetails" />
      </template>
    </Dialog>

    <!-- Delete Confirmation Dialog -->
    <Dialog
      v-model:visible="showDeleteDialog"
      header="Confirm Delete"
      modal
      :style="{ width: '400px' }"
    >
      <div class="flex items-start gap-3">
        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
          <i class="pi pi-exclamation-triangle text-red-600"></i>
        </div>
        <div>
          <p class="text-gray-700">
            Are you sure you want to delete <strong>{{ selectedDeduction?.name }}</strong>?
          </p>
          <p v-if="selectedDeduction?.employee_deductions_count > 0" class="text-red-500 text-sm mt-2">
            This deduction type is assigned to {{ selectedDeduction.employee_deductions_count }} employee(s) and cannot be deleted.
          </p>
          <p v-else class="text-gray-500 text-sm mt-2">
            This action cannot be undone.
          </p>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" severity="secondary" @click="showDeleteDialog = false" />
        <Button
          label="Delete"
          severity="danger"
          @click="deleteDeductionType"
          :disabled="selectedDeduction?.employee_deductions_count > 0"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'
import { useAuthStore } from '../../../stores/auth'

// Toast
const toast = useToast()
const authStore = useAuthStore()

// Set authorization header
axios.defaults.headers.common['Authorization'] = `Bearer ${authStore.token}`

// State
const loading = ref(false)
const saving = ref(false)
const deductionTypes = ref<any[]>([])
const showDialog = ref(false)
const showDetailsDialog = ref(false)
const showDeleteDialog = ref(false)
const isEditing = ref(false)
const selectedDeduction = ref<any>(null)
const errors = ref<any>({})
const formulaJson = ref('')

// Filters
const filters = ref({
  search: '',
  category: null as string | null,
  calculationType: null as string | null,
  isActive: null as boolean | null
})

// Form data
const form = ref({
  id: null as number | null,
  code: '',
  name: '',
  description: '',
  category: 'company',
  calculation_type: 'fixed',
  frequency: 'monthly',
  default_amount: 0,
  percentage_value: 0,
  percentage_basis: 'basic',
  min_amount: null as number | null,
  max_amount: null as number | null,
  formula_data: null as any,
  is_mandatory: false,
  is_taxable: false,
  is_active: true,
  show_on_payslip: true,
  sort_order: 0
})

// Options
const categoryOptions = [
  { label: 'Government', value: 'government' },
  { label: 'Company', value: 'company' },
  { label: 'Loan', value: 'loan' },
  { label: 'Benefit', value: 'benefit' },
  { label: 'Other', value: 'other' }
]

const calculationTypeOptions = [
  { label: 'Fixed Amount', value: 'fixed' },
  { label: 'Percentage', value: 'percentage' },
  { label: 'Formula', value: 'formula' }
]

const basisOptions = [
  { label: 'Basic Salary', value: 'basic' },
  { label: 'Gross Salary', value: 'gross' },
  { label: 'Taxable Income', value: 'taxable' }
]

const frequencyOptions = [
  { label: 'One-time', value: 'one-time' },
  { label: 'Monthly', value: 'monthly' },
  { label: 'Bi-monthly', value: 'bi-monthly' },
  { label: 'Quarterly', value: 'quarterly' },
  { label: 'Annual', value: 'annual' }
]

const statusOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false }
]

// Computed
const activeCount = computed(() => deductionTypes.value.filter(d => d.is_active).length)
const mandatoryCount = computed(() => deductionTypes.value.filter(d => d.is_mandatory).length)
const taxableCount = computed(() => deductionTypes.value.filter(d => d.is_taxable).length)

// Helper functions
const formatNumber = (num: number) => {
  return num?.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) || '0.00'
}

const formatCategory = (category: string) => {
  const map: Record<string, string> = {
    government: 'Government',
    company: 'Company',
    loan: 'Loan',
    benefit: 'Benefit',
    other: 'Other'
  }
  return map[category] || category
}

const formatCalculationType = (type: string) => {
  const map: Record<string, string> = {
    fixed: 'Fixed',
    percentage: 'Percentage',
    formula: 'Formula'
  }
  return map[type] || type
}

const getCategorySeverity = (category: string) => {
  const map: Record<string, string> = {
    government: 'info',
    company: 'success',
    loan: 'warning',
    benefit: 'help',
    other: 'secondary'
  }
  return map[category] || 'info'
}

const getCalculationIcon = (type: string) => {
  const map: Record<string, string> = {
    fixed: 'pi pi-dollar',
    percentage: 'pi pi-percentage',
    formula: 'pi pi-calculator'
  }
  return map[type] || 'pi pi-list'
}

const formatDateTime = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const resetForm = () => {
  form.value = {
    id: null,
    code: '',
    name: '',
    description: '',
    category: 'company',
    calculation_type: 'fixed',
    frequency: 'monthly',
    default_amount: 0,
    percentage_value: 0,
    percentage_basis: 'basic',
    min_amount: null,
    max_amount: null,
    formula_data: null,
    is_mandatory: false,
    is_taxable: false,
    is_active: true,
    show_on_payslip: true,
    sort_order: 0
  }
  formulaJson.value = ''
  errors.value = {}
}

// API Functions
const fetchDeductionTypes = async () => {
  loading.value = true
  try {
    const params: any = {}
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.category) params.category = filters.value.category
    if (filters.value.calculationType) params.calculation_type = filters.value.calculationType
    if (filters.value.isActive !== null) params.is_active = filters.value.isActive
    params.with_counts = true

    const response = await axios.get('api/deductions/deduction-types', { params })
    if (response.data.success) {
      deductionTypes.value = response.data.data
    }
  } catch (error: any) {
    console.error('Error fetching deduction types:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to fetch deduction types',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const saveDeductionType = async () => {
  saving.value = true
  errors.value = {}

  try {
    // Prepare data
    const data: any = { ...form.value }

    // Handle formula JSON
    if (data.calculation_type === 'formula' && formulaJson.value) {
      try {
        data.formula_data = JSON.parse(formulaJson.value)
      } catch (e) {
        errors.value.formula_data = ['Invalid JSON format']
        saving.value = false
        return
      }
    }

    let response
    if (isEditing.value) {
      response = await axios.put(`api/deductions/deduction-types/${form.value.id}`, data)
    } else {
      response = await axios.post('api/deductions/deduction-types', data)
    }

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: isEditing.value ? 'Deduction type updated successfully' : 'Deduction type created successfully',
        life: 3000
      })
      closeDialog()
      await fetchDeductionTypes()
    }
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to save deduction type',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

const toggleActive = async (deduction: any) => {
  try {
    const response = await axios.post(`api/deductions/deduction-types/${deduction.id}/toggle-active`)
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Deduction type ${deduction.is_active ? 'deactivated' : 'activated'} successfully`,
        life: 3000
      })
      await fetchDeductionTypes()
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to toggle status',
      life: 3000
    })
  }
}

const deleteDeductionType = async () => {
  if (!selectedDeduction.value) return

  try {
    const response = await axios.delete(`api/deductions/deduction-types/${selectedDeduction.value.id}`)
    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Deduction type deleted successfully',
        life: 3000
      })
      showDeleteDialog.value = false
      selectedDeduction.value = null
      await fetchDeductionTypes()
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete deduction type',
      life: 3000
    })
  }
}

// Dialog handlers
const openCreateDialog = () => {
  resetForm()
  isEditing.value = false
  showDialog.value = true
}

const openEditDialog = (deduction: any) => {
  isEditing.value = true
  form.value = {
    id: deduction.id,
    code: deduction.code,
    name: deduction.name,
    description: deduction.description || '',
    category: deduction.category,
    calculation_type: deduction.calculation_type,
    frequency: deduction.frequency || 'monthly',
    default_amount: deduction.default_amount || 0,
    percentage_value: deduction.percentage_value || 0,
    percentage_basis: deduction.percentage_basis || 'basic',
    min_amount: deduction.min_amount,
    max_amount: deduction.max_amount,
    formula_data: deduction.formula_data,
    is_mandatory: deduction.is_mandatory || false,
    is_taxable: deduction.is_taxable || false,
    is_active: deduction.is_active || false,
    show_on_payslip: deduction.show_on_payslip || false,
    sort_order: deduction.sort_order || 0
  }
  if (deduction.formula_data) {
    formulaJson.value = JSON.stringify(deduction.formula_data, null, 2)
  }
  showDialog.value = true
}

const viewDetails = (deduction: any) => {
  selectedDeduction.value = deduction
  showDetailsDialog.value = true
}

const editFromDetails = () => {
  if (selectedDeduction.value) {
    openEditDialog(selectedDeduction.value)
    showDetailsDialog.value = false
  }
}

const confirmDelete = (deduction: any) => {
  selectedDeduction.value = deduction
  showDeleteDialog.value = true
}

const closeDialog = () => {
  showDialog.value = false
  resetForm()
}

// Lifecycle
onMounted(() => {
  fetchDeductionTypes()
})
</script>