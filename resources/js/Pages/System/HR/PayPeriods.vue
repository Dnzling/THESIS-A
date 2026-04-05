<template>
  <div class="space-y-6">
    <!-- Periods Table -->
    <Card>
      <template #title>
        <div class="flex gap-3 mb-4">
          <IconField iconPosition="left">
            <InputIcon>
              <i class="pi pi-search" />
            </InputIcon>
            <InputText v-model="filters.search" placeholder="Search period" class="w-full" />
          </IconField>
          <Select v-model="filters.status" :options="statusOptions" placeholder="All Status" showClear class="w-48" />
          <DatePicker v-model="filters.dateRange" showIcon showClear selectionMode="range" :manualInput="false"
            placeholder="Date Range" fluid />
          <Button label="New Period" icon="pi pi-plus" severity="info" class="ml-auto" @click="createPeriod" />
        </div>
      </template>
  
      <template #content>
        <DataTable :value="filteredPayPeriods" class="w-full" :loading="loading" paginator :rows="10"
          :rowsPerPageOptions="[5, 10, 20, 50]"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} periods" rowHover showGridlines
          removableSort responsiveLayout="scroll" sortField="name" :sortOrder="1" tableStyle="min-width: 50rem">
          <Column field="period" header="Period" sortable></Column>
          <Column field="cutoffStart" header="Start Date" sortable></Column>
          <Column field="cutoffEnd" header="End Date" sortable></Column>
          <Column field="payDate" header="Pay Date" sortable></Column>
          <Column field="status" header="Status" sortable>
            <template #body="slotProps">
              <div class="flex items-center gap-2">
                <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" class="capitalize" />
                <Select
                  :modelValue="slotProps.data.status"
                  :options="statusOptions"
                  placeholder="Change status"
                  class="w-36"
                  size="small"
                  :disabled="isStatusLocked(slotProps.data.status) || updatingStatusId === slotProps.data.id"
                  @update:modelValue="(value) => changePeriodStatus(slotProps.data, value)"
                />
              </div>
            </template>
          </Column>
          <Column header="Actions">
            <template #body="slotProps">
              <div class="flex gap-2">
                <Button icon="pi pi-pencil" text @click="editPeriod(slotProps.data)" />
                <Button icon="pi pi-trash" text severity="danger" @click="confirmDeletePeriod(slotProps.data)" />
              </div>
            </template>
          </Column>
  
          <template #empty>
            <div class="text-center py-12">
              <p class="text-gray-500 text-lg">No periods found</p>
              <p class="text-gray-400 text-sm mb-4">Try adjusting your search or filter criteria</p>
              <Button label="Add Your First Period" icon="pi pi-plus" severity="info" @click="showDialog = true" />
            </div>
          </template>
        </DataTable>
      </template>
  
      <ScrollTop />
    </Card>
  
    <!-- Create/Edit Dialog -->
    <Dialog v-model:visible="showDialog" :header="dialogTitle" modal :style="{ width: '500px' }">
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm mb-1">Start Date</label>
            <DatePicker v-model="periodForm.startDate" showIcon showClear fluid iconDisplay="input" />
          </div>
          <div>
            <label class="block text-sm mb-1">End Date</label>
            <DatePicker v-model="periodForm.endDate" showIcon showClear fluid iconDisplay="input" />
          </div>
        </div>

        <div>
          <label class="block text-sm mb-1">Auto Period Name</label>
          <InputText :modelValue="periodForm.name" class="w-full" readonly />
          <small class="text-gray-500">Generated from selected start and end dates.</small>
        </div>
  
        <div class="flex flex-wrap gap-4">
          <label for="">Type:</label>
          <div class="flex items-center gap-2">
            <RadioButton v-model="periodForm.halfType" value="1st Half" />
            <label>1st Half</label>
          </div>
          <div class="flex items-center gap-2">
            <RadioButton v-model="periodForm.halfType" value="2nd Half" />
            <label>2nd Half</label>
          </div>
        </div>
        <div>
          <label class="block text-sm mb-1">Pay Date</label>
          <DatePicker v-model="periodForm.payDate" showIcon fluid showClear iconDisplay="input" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" @click="resetForm" />
        <Button label="Save" severity="info" @click="savePeriod" />
      </template>
    </Dialog>
  
    <Dialog v-model:visible="showDeleteModal" header="Confirm Delete" :style="{ width: '400px' }" modal>
      <div class="text-center">
        <i class="pi pi-exclamation-triangle text-4xl text-yellow-500 mb-3"></i>
        <p>Are you sure you want to delete this batch?</p>
        <p class="font-bold">{{ periodToDelete?.period }}</p>
        <small class="text-gray-500">This action cannot be undone.</small>
      </div>
  
      <template #footer>
        <Button label="Cancel" severity="secondary" rounded @click="showDeleteModal = false" />
        <Button label="Delete" severity="danger" rounded @click="deletePeriod" />
      </template>
    </Dialog>
  
  
  </div>
</template>

<script setup lang="ts">
import { useToast } from 'primevue/usetoast'
import { computed, ref, onMounted, watch } from 'vue'
import hrService from '@/services/hr.services'
import { useAuthStore } from '../../../stores/auth'


interface PayPeriods {
  id: number
  period: string
  cutoffStart: string
  cutoffEnd: string
  payDate: string
  status: string
}

interface Filters {
  search: string
  status: string | null
  dateRange: Date[] | null
}

// Data
const toast = useToast()
const payPeriods = ref<PayPeriods[]>([])
const loading = ref(false)
const error = ref<string | null>(null)
const showDialog = ref(false)
const dialogTitle = ref('Create Pay Period')
const editingId = ref<number | null>(null)
const showDeleteModal = ref(false)
const periodToDelete = ref<PayPeriods | null>(null)
const authStore = useAuthStore()
const updatingStatusId = ref<number | null>(null)

const filters = ref<Filters>({
  search: '',
  status: null,
  dateRange: null
})

const periodForm = ref({
  name: '',
  startDate: null as Date | null,
  endDate: null as Date | null,
  halfType: null as string | null,
  payDate: null as Date | null
})

const statusOptions = ref(['draft', 'processing', 'locked', 'completed'])

// Computed
const filteredPayPeriods = computed(() => {
  return payPeriods.value.filter(item => {
    const matchesSearch = !filters.value.search ||
      item.period.toLowerCase().includes(filters.value.search.toLowerCase())
    const matchesStatus = !filters.value.status || item.status === filters.value.status

    // Date range filter
    let matchesDateRange = true
    if (filters.value.dateRange && filters.value.dateRange[0] && filters.value.dateRange[1]) {
      const filterStart = new Date(filters.value.dateRange[0])
      const filterEnd = new Date(filters.value.dateRange[1])

      // Reset time to start/end of day for accurate comparison
      filterStart.setHours(0, 0, 0, 0)
      filterEnd.setHours(23, 59, 59, 999)

      // Convert string dates to Date objects
      const itemStart = new Date(item.cutoffStart)
      const itemEnd = new Date(item.cutoffEnd)

      // Check if period overlaps with selected range
      // This will include periods that have any overlap with the selected date range
      matchesDateRange = itemStart <= filterEnd && itemEnd >= filterStart

      // Alternative: Strict inclusion (period must be completely within range)
      // matchesDateRange = itemStart >= filterStart && itemEnd <= filterEnd
    }

    return matchesSearch && matchesStatus && matchesDateRange
  })
})

// Then your fetch becomes simpler:
const fetchPayPeriods = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await hrService.api.get('/api/payroll/periods', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`
      }
    })

    if (response.data.success) {
      payPeriods.value = response.data.data
    } else {
      error.value = response.data.message || 'Failed to fetch pay periods'
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.value,
        life: 3000
      })
    }
  } catch (err: any) {
    error.value = 'Failed to fetch pay periods'
    console.error('Fetch error:', err)

    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to connect to server',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const toYmd = (date: Date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const generatePeriodName = (start: Date | null, end: Date | null) => {
  if (!start || !end) return ''
  return `Pay Period ${toYmd(start)} to ${toYmd(end)}`
}

// Keep generated name and related defaults in sync with selected date range.
watch([() => periodForm.value.startDate, () => periodForm.value.endDate], ([newStartDate, newEndDate]) => {
  if (!newStartDate) {
    periodForm.value.name = ''
    return
  }

  const start = new Date(newStartDate)

  if (!newEndDate) {
    const defaultEndDate = new Date(start)
    defaultEndDate.setDate(start.getDate() + 14)
    periodForm.value.endDate = defaultEndDate
    return
  }

  const end = new Date(newEndDate)
  periodForm.value.halfType = start.getDate() <= 15 ? '1st Half' : '2nd Half'
  periodForm.value.name = generatePeriodName(start, end)

  // Default pay date to period end unless user has chosen one.
  if (!periodForm.value.payDate) {
    periodForm.value.payDate = new Date(end)
  }
})

const createPeriod = () => {
  // Get current date for default values
  const today = new Date()
  const currentYear = today.getFullYear()
  const currentMonth = today.getMonth()
  const day = today.getDate()

  // Determine which half of the month we're in
  const defaultHalfType = day <= 15 ? '1st Half' : '2nd Half'

  // Set start date based on half type
  let startDate
  if (defaultHalfType === '1st Half') {
    startDate = new Date(currentYear, currentMonth, 1)
  } else {
    startDate = new Date(currentYear, currentMonth, 16)
  }

  // Calculate end date and pay date
  const endDate = new Date(startDate)
  endDate.setDate(startDate.getDate() + 14)

  const payDate = new Date(startDate)
  payDate.setDate(startDate.getDate() + 14)

  // Format month name
  const monthName = startDate.toLocaleString('default', { month: 'long' })

  // Generate the period name
  const periodName = `${monthName} ${currentYear} (${defaultHalfType})`

  periodForm.value = {
    name: periodName,
    startDate: startDate,
    endDate: endDate,
    halfType: defaultHalfType,
    payDate: payDate
  }

  dialogTitle.value = 'Create Pay Period'
  showDialog.value = true
}
const savePeriod = async () => {
  try {
    const startDate = periodForm.value.startDate
    const endDate = periodForm.value.endDate
    const payDate = periodForm.value.payDate

    if (!startDate || !endDate || !payDate) {
      toast.add({
        severity: 'warn',
        summary: 'Validation',
        detail: 'Start date, end date, and pay date are required.',
        life: 3000
      })
      return
    }

    const start = new Date(startDate)
    const end = new Date(endDate)
    const pay = new Date(payDate)

    if (end < start) {
      toast.add({
        severity: 'warn',
        summary: 'Validation',
        detail: 'End date must be after start date.',
        life: 3000
      })
      return
    }

    if (pay < start || pay > end) {
      toast.add({
        severity: 'warn',
        summary: 'Validation',
        detail: 'Pay date must be within the selected period range.',
        life: 3000
      })
      return
    }

    const generatedName = generatePeriodName(start, end)
    periodForm.value.name = generatedName

    const response = await hrService.api.post('/api/payroll/periods',
      {
        name: generatedName,
        start_date: toYmd(start),
        end_date: toYmd(end),
        cutoff_date: toYmd(pay),
        notes: '' // Add notes if needed
      },
      {
        headers: {
          'Authorization': `Bearer ${authStore.token}`
        }
      }
    )

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Pay period created successfully`,
        life: 3000
      })

      showDialog.value = false
      resetForm()
      await fetchPayPeriods()
    }
  } catch (err: any) {
    console.error('Save error:', err.response?.data || err)

    // Show validation errors if they exist
    const errorMessage = err.response?.data?.message || 'Failed to save pay period'
    const errors = err.response?.data?.errors

    if (errors) {
      // Handle validation errors
      Object.values(errors).forEach((error: any) => {
        toast.add({
          severity: 'error',
          summary: 'Validation Error',
          detail: error[0],
          life: 3000
        })
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: errorMessage,
        life: 3000
      })
    }
  }
}

const getStatusSeverity = (status: string): 'info' | 'success' | 'warn' | 'secondary' | 'danger' => {
  const map: Record<string, 'info' | 'success' | 'warn' | 'secondary' | 'danger'> = {
    draft: 'secondary',
    processing: 'warn',
    locked: 'info',
    completed: 'success'
  }
  return map[status] || 'info'
}

const isStatusLocked = (status: string) => ['locked', 'completed'].includes(status)

const changePeriodStatus = async (period: PayPeriods, status: string) => {
  if (!status || status === period.status || isStatusLocked(period.status)) return

  updatingStatusId.value = period.id
  try {
    const response = await hrService.api.put(`/api/payroll/periods/${period.id}`, {
      status
    })

    if (response.data.success) {
      period.status = status
      toast.add({
        severity: 'success',
        summary: 'Updated',
        detail: `Status changed to ${status}`,
        life: 2500
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.data.message || 'Failed to update status',
        life: 3000
      })
    }
  } catch (err: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: err.response?.data?.message || 'Failed to update status',
      life: 3000
    })
  } finally {
    updatingStatusId.value = null
  }
}

const editPeriod = (period: PayPeriods) => {
  editingId.value = period.id
  periodForm.value = {
    name: period.period,
    startDate: new Date(period.cutoffStart),
    endDate: new Date(period.cutoffEnd),
    halfType: null,
    payDate: new Date(period.payDate)
  }
  dialogTitle.value = 'Edit Pay Period'
  showDialog.value = true
}

const confirmDeletePeriod = (period: PayPeriods) => {
  periodToDelete.value = period
  showDeleteModal.value = true
}

const deletePeriod = async () => {
  if (!periodToDelete.value) return

  try {
    const response = await hrService.api.delete(`/api/payroll/periods/${periodToDelete.value.id}`)

    if (response.data.success) {
      toast.add({
        severity: 'success',
        summary: 'Deleted',
        detail: `Period "${periodToDelete.value.period}" has been deleted`,
        life: 3000
      })

      // Refresh the list
      await fetchPayPeriods()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.data.message || 'Failed to delete',
        life: 3000
      })
    }
  } catch (err) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to delete period',
      life: 3000
    })
  } finally {
    showDeleteModal.value = false
    periodToDelete.value = null
  }
}



const resetForm = () => {
  periodForm.value = {
    name: '',
    startDate: null,
    endDate: null,
    halfType: null,
    payDate: null
  }
  editingId.value = null
  dialogTitle.value = 'Create Pay Period'
}

// Lifecycle
onMounted(() => {
  fetchPayPeriods()
})
</script>

