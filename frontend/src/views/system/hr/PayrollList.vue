<template>
  <div class="payroll-batches p-4">
    <!-- Search and Filters -->
    <div class="flex gap-3 mb-4 flex-wrap text-sm">
      <IconField iconPosition="left">
        <InputIcon>
          <i class="pi pi-search" />
        </InputIcon>
        <InputText v-model="filters.search" placeholder="Search period" @input="debouncedFetch" />
      </IconField>
      <Select v-model="filters.status" :options="statusOptions" placeholder="All Status" showClear
        @change="fetchPayPeriods" />
      <DatePicker v-model="filters.dateRange" showIcon showClear selectionMode="range" placeholder="Date Range"
        @update:modelValue="fetchPayPeriods" />
      <Button label="Generate Payroll" @click="showGenerateModal = true" severity="info" class="ml-auto" />
    </div>
  
    <!-- Batches Table -->
    <DataTable :value="filteredBatches" class="w-full text-sm" :loading="loading" paginator :rows="10" rowHover
      :rowsPerPageOptions="[5, 10, 20, 50]"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
      currentPageReportTemplate="Showing {first} to {last} of {totalRecords} periods" showGridlines removableSort
      responsiveLayout="scroll" sortField="periodName" :sortOrder="1" tableStyle="min-width: 50rem" @sort="handleSort">
  
      <Column field="name" header="Period" class="w-40" sortable>
        <template #body="{ data }">
          <div class="font-medium">{{ data.name }}</div>
          <small class="text-gray-500">{{ getPeriodType(data) }}</small>
        </template>
      </Column>
  
      <Column field="start_date" header="Start Date" sortable>
        <template #body="{ data }">
          {{ formatDate(data.start_date) }}
        </template>
      </Column>
  
      <Column field="end_date" header="End Date" sortable>
        <template #body="{ data }">
          {{ formatDate(data.end_date) }}
        </template>
      </Column>
  
      <Column field="pay_date" header="Pay Date" sortable>
        <template #body="{ data }">
          <div>{{ formatDate(data.pay_date) }}</div>
          <small :class="getPayDateWarningClass(data)" v-if="data.status !== 'completed'">
            {{ getDaysUntilPay(data) }}
          </small>
        </template>
      </Column>
  
      <Column field="created_by" header="Created By" sortable>
        <template #body="{ data }">
          <div>{{ data.created_by }}</div>
          <small class="text-gray-500">{{ formatDateTime(data.created_at) }}</small>
        </template>
      </Column>
  
      <Column field="employees_count" header="Employees" sortable>
        <template #body="{ data }">
          <Tag :value="data.employees_count" :severity="getEmployeeCountSeverity(data)" />
          <div v-if="data.status === 'processing'" class="text-xs mt-1">
            <!-- <ProgressBar :value="getProcessingProgress(data)" style="height: 4px" /> -->
          </div>
        </template>
      </Column>
  
      <Column field="total_net_worth" header="Net Payroll" sortable>
        <template #body="{ data }">
          <div class="font-medium">{{ formatCurrency(data.total_net_worth) }}</div>
          <small class="text-gray-500">Gross: {{ formatCurrency(data.total_gross_worth) }}</small>
        </template>
      </Column>
  
      <Column field="status" header="Status" sortable>
        <template #body="{ data }">
          <Tag :severity="getStatusSeverity(data.status)" :value="data.status" class="capitalize" />
        </template>
      </Column>
  
      <Column header="Actions" style="min-width: 120px">
        <template #body="{ data }">
          <div class="flex gap-2">
            <Button icon="pi pi-eye" severity="info" text @click="viewBatch(data)" v-tooltip.top="'View batch details'" />
            <Button icon="pi pi-pencil" severity="success" text @click="editBatch(data)" v-tooltip.top="'Edit batch'"
              :disabled="data.status === 'completed' || data.status === 'locked'" />
            <Button icon="pi pi-file-export" severity="warning" text @click="exportBatch(data)"
              v-tooltip.top="'Export CSV'" :disabled="data.employees_count === 0" />
            <!-- <Button icon="pi pi-trash" severity="danger" text @click="confirmDeleteBatch(data)"
                  v-tooltip.top="'Delete batch'" :disabled="data.status !== 'draft'" /> -->
          </div>
        </template>
      </Column>
  
      <template #empty>
        <div class="text-center py-8 text-gray-500">
          <i class="pi pi-calendar text-4xl mb-2"></i>
          <p>No pay periods found</p>
          <Button label="Create First Period" icon="pi pi-plus" @click="showGenerateModal = true" class="mt-4" text />
        </div>
      </template>
    </DataTable>
  
    <!-- Delete Confirmation Dialog -->
    <Dialog v-model:visible="showDeleteModal" header="Confirm Delete" :style="{ width: '400px' }" modal>
      <div class="text-center">
        <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-3"></i>
        <p>Are you sure you want to delete this period?</p>
        <p class="font-bold">{{ batchToDelete?.name }}</p>
        <p class="text-sm text-gray-600 mt-2">Period: {{ formatDate(batchToDelete?.start_date) }} - {{
          formatDate(batchToDelete?.end_date) }}</p>
        <small class="text-gray-500">This action cannot be undone.</small>
      </div>
  
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined @click="showDeleteModal = false"
          :disabled="isDeleting" />
        <Button label="Delete" icon="pi pi-trash" severity="danger" @click="deleteBatch" :loading="isDeleting" />
      </template>
    </Dialog>
  
    <!-- View Batch Details Dialog -->
    <Dialog v-model:visible="showViewModal" header="Pay Period Details" :style="{ width: '800px' }" modal maximizable>
      <div v-if="selectedBatch" class="space-y-4">
        <!-- Period Summary -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <Card>
            <template #content>
              <div class="text-center">
                <span class="text-sm text-gray-500">Period</span>
                <div class="font-medium">{{ formatDateRange(selectedBatch.start_date, selectedBatch.end_date) }}</div>
              </div>
            </template>
          </Card>
          <Card>
            <template #content>
              <div class="text-center">
                <span class="text-sm text-gray-500">Pay Date</span>
                <div class="font-medium">{{ formatDate(selectedBatch.pay_date) }}</div>
              </div>
            </template>
          </Card>
          <Card>
            <template #content>
              <div class="text-center">
                <span class="text-sm text-gray-500">Employees</span>
                <div class="font-medium">{{ selectedBatch.employees_count }}</div>
              </div>
            </template>
          </Card>
          <Card>
            <template #content>
              <div class="text-center">
                <span class="text-sm text-gray-500">Total Payroll</span>
                <div class="font-medium">{{ formatCurrency(selectedBatch.total_net_worth) }}</div>
              </div>
            </template>
          </Card>
        </div>
  
        <!-- Employee List -->
        <h3 class="font-medium text-lg mt-4">Employee Payroll Summary</h3>
        <DataTable :value="selectedBatch.employees || []" class="w-full" :loading="loadingEmployees">
          <Column field="employee_number" header="ID" style="width: 100px"></Column>
          <Column field="name" header="Employee Name"></Column>
          <Column field="department" header="Department"></Column>
          <Column field="payroll.net_salary" header="Net Pay">
            <template #body="{ data }">
              {{ formatCurrency(data.payroll.net_salary) }}
            </template>
          </Column>
          <Column field="payroll.status" header="Status">
            <template #body="{ data }">
              <Tag :severity="getStatusSeverity(data.payroll.status)" :value="data.payroll.status" size="small" />
            </template>
          </Column>
        </DataTable>
      </div>
      <template #footer>
        <Button label="Close" icon="pi pi-times" @click="showViewModal = false" />
        <Button label="Export CSV" icon="pi pi-file-excel" severity="success" @click="exportBatch(selectedBatch)" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="showGenerateModal" header="Generate Payroll" :style="{ width: '600px' }" modal
      :closable="!isGenerating" :closeOnEscape="!isGenerating" :dismissableMask="!isGenerating">
      <GeneratePayrollDialog @close="showGenerateModal = false" @generated="handlePayrollGenerated" />
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import GeneratePayrollDialog from '../../../components/dialogs/GeneratePayrollDialog.vue'
import axios from 'axios'
import { useAuthStore } from '../../../stores/auth'
import { debounce } from 'lodash'

// ==================== INTERFACES ====================
interface PayPeriod {
  id: number
  name: string
  start_date: string
  end_date: string
  pay_date: string
  status: 'draft' | 'processing' | 'locked' | 'completed'
  employees_count: number
  total_net_worth: number
  total_gross_worth: number
  created_by: string
  created_at: string
  employees?: any[]
}

interface Filters {
  search: string
  status: string | null
  dateRange: Date[] | null
}

interface Statistics {
  totalPeriods: number
  totalPayroll: number
  averagePeriodPayroll: number
  periodsByStatus: Record<string, { count: number; total: number }>
}

// ==================== STATE ====================
const toast = useToast()
const router = useRouter()
const loading = ref(false)
const loadingEmployees = ref(false)
const showDeleteModal = ref(false)
const showViewModal = ref(false)
const batchToDelete = ref<PayPeriod | null>(null)
const selectedBatch = ref<PayPeriod | null>(null)
const showGenerateModal = ref(false)
const isGenerating = ref(false)
const isDeleting = ref(false)
const authStore = useAuthStore()

const payPeriods = ref<PayPeriod[]>([])
const statistics = ref<Statistics>({
  totalPeriods: 0,
  totalPayroll: 0,
  averagePeriodPayroll: 0,
  periodsByStatus: {}
})

// Filters
const filters = ref<Filters>({
  search: '',
  status: null,
  dateRange: null
})

// Options
const statusOptions = ref(['draft', 'processing', 'locked', 'completed'])

// ==================== COMPUTED ====================
const filteredBatches = computed(() => {
  let filtered = [...payPeriods.value]

  if (filters.value.search) {
    const searchLower = filters.value.search.toLowerCase()
    filtered = filtered.filter(p =>
      p.name.toLowerCase().includes(searchLower)
    )
  }

  if (filters.value.status) {
    filtered = filtered.filter(p => p.status === filters.value.status)
  }

  if (filters.value.dateRange && filters.value.dateRange[0] && filters.value.dateRange[1]) {
    const start = filters.value.dateRange[0]
    const end = filters.value.dateRange[1]
    filtered = filtered.filter(p =>
      new Date(p.start_date) >= start && new Date(p.end_date) <= end
    )
  }

  return filtered
})

const stats = computed(() => ({
  totalPeriods: payPeriods.value.length,
  totalPayroll: payPeriods.value.reduce((sum, p) => sum + p.total_net_worth, 0)
}))

// ==================== METHODS ====================
const fetchPayPeriods = async () => {
  loading.value = true
  try {
    const params: any = {}

    if (filters.value.status) {
      params.status = filters.value.status
    }

    if (filters.value.dateRange && filters.value.dateRange[0]) {
      params.year = filters.value.dateRange[0].getFullYear()
      params.month = filters.value.dateRange[0].getMonth() + 1
    }

    const response = await axios.get('/api/payroll/pay-periods', { params })

    if (response.data.success) {
      payPeriods.value = response.data.data
      statistics.value = response.data.statistics
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch pay periods',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const fetchPeriodDetails = async (id: number) => {
  loadingEmployees.value = true
  try {
    const response = await axios.get(`/api/payroll/pay-periods/${id}/payroll`, {
      params: {
        include_department_breakdown: true,
        include_status_breakdown: true
      }
    })

    if (response.data.success) {
      selectedBatch.value = response.data.data.period
      selectedBatch.value.employees = response.data.data.employees
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to fetch period details',
      life: 3000
    })
  } finally {
    loadingEmployees.value = false
  }
}

const debouncedFetch = debounce(() => {
  fetchPayPeriods()
}, 300)

const formatDate = (date: string): string => {
  if (!date) return 'N/A'
  return new Intl.DateTimeFormat('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit'
  }).format(new Date(date))
}

const formatDateTime = (date: string): string => {
  if (!date) return 'N/A'
  return new Intl.DateTimeFormat('en-PH', {
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(date))
}

const formatDateRange = (start: string, end: string): string => {
  return `${formatDate(start)} - ${formatDate(end)}`
}

const formatCurrency = (amount: number): string => {
  if (!amount) return '₱0.00'
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount)
}

const getStatusSeverity = (status: string): 'info' | 'success' | 'warn' | 'secondary' | 'danger' => {
  const map: Record<string, any> = {
    'draft': 'secondary',
    'processing': 'info',
    'locked': 'warn',
    'completed': 'success',
    'approved': 'success',
    'paid': 'success',
    'cancelled': 'danger'
  }
  return map[status] || 'info'
}

const getEmployeeCountSeverity = (period: PayPeriod): 'info' | 'success' | 'warn' | 'danger' => {
  if (period.employees_count === 0) return 'danger'
  if (period.employees_count < 10) return 'warn'
  if (period.employees_count < 30) return 'info'
  return 'success'
}

const getPeriodType = (period: PayPeriod): string => {
  const start = new Date(period.start_date)
  const end = new Date(period.end_date)
  const days = Math.ceil((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1

  if (days <= 15) return 'Semi-monthly'
  if (days <= 31) return 'Monthly'
  return 'Custom Period'
}

const getDaysUntilPay = (period: PayPeriod): string => {
  const today = new Date()
  const payDate = new Date(period.pay_date)
  const days = Math.ceil((payDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

  if (days < 0) return 'Past due'
  if (days === 0) return 'Today'
  if (days === 1) return 'Tomorrow'
  return `${days} days until pay`
}

const getPayDateWarningClass = (period: PayPeriod): string => {
  const today = new Date()
  const payDate = new Date(period.pay_date)
  const days = Math.ceil((payDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

  if (days < 0) return 'text-red-500'
  if (days <= 3) return 'text-yellow-500 font-medium'
  return 'text-gray-500'
}

const getProcessingProgress = (period: PayPeriod): number => {
  // Mock progress based on dates
  const start = new Date(period.start_date)
  const end = new Date(period.end_date)
  const today = new Date()

  if (today < start) return 0
  if (today > end) return 100

  const total = end.getTime() - start.getTime()
  const passed = today.getTime() - start.getTime()
  return Math.round((passed / total) * 100)
}

const handleSort = (event: any) => {
  // Handle sort if needed
  console.log('Sort:', event)
}

const viewBatch = async (batch: PayPeriod) => {
  router.push({
    name: 'hr.payroll.view',
    params: { id: batch.id.toString() }
  })
}

const editBatch = (batch: PayPeriod) => {
  router.push({
    name: 'hr.payroll.edit',
    params: { id: batch.id.toString() }
  })
}

const exportBatch = async (batch: PayPeriod) => {
  try {
    const response = await axios.get(`/api/payroll/pay-periods/${batch.id}/export`, {
      responseType: 'blob'
    })

    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payroll_${batch.name.replace(/\s+/g, '_')}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Payroll exported successfully',
      life: 3000
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to export payroll',
      life: 3000
    })
  }
}

const confirmDeleteBatch = (batch: PayPeriod) => {
  batchToDelete.value = batch
  showDeleteModal.value = true
}

const deleteBatch = async () => {
  if (!batchToDelete.value) return

  isDeleting.value = true
  try {
    await axios.delete(`api/payroll/pay-periods/${batchToDelete.value.id}`)

    payPeriods.value = payPeriods.value.filter(p => p.id !== batchToDelete.value?.id)

    toast.add({
      severity: 'success',
      summary: 'Deleted',
      detail: `Period "${batchToDelete.value.name}" has been deleted`,
      life: 3000
    })

    showDeleteModal.value = false
    batchToDelete.value = null
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete pay period',
      life: 3000
    })
  } finally {
    isDeleting.value = false
  }
}

const handlePayrollGenerated = (newBatch: any) => {
  isGenerating.value = false
  showGenerateModal.value = false
  fetchPayPeriods()

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: `Payroll batch generated successfully`,
    life: 3000
  })
}

// ==================== WATCHERS ====================
watch(() => filters.value.status, () => {
  fetchPayPeriods()
})

// ==================== LIFECYCLE ====================
onMounted(() => {
  // Set axios default headers
  axios.defaults.headers.common['Authorization'] = `Bearer ${authStore.token}`
  fetchPayPeriods()
})
</script>

<style scoped>
.payroll-batches {
  container-type: inline-size;
}

@container (max-width: 640px) {
  .flex.gap-3 {
    flex-direction: column;
  }

  .w-60,
  .w-50 {
    width: 100%;
  }
}
</style>