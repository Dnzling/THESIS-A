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
          <DatePicker v-model="filters.dateRange" showIcon showClear selectionMode="range" placeholder="Date Range"
            fluid />
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
          <Column field="status" header="Status" sortable></Column>
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
import axios from 'axios'
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

const statusOptions = ref(['draft', 'for-review', 'approved', 'released'])

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
    const response = await axios.get('/api/payroll/periods', {
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

// Watch for changes in startDate to auto-update endDate and payDate
watch(() => periodForm.value.startDate, (newStartDate) => {
  if (newStartDate) {
    const start = new Date(newStartDate)

    // Auto-calculate end date (+14 days)
    const newEndDate = new Date(start)
    newEndDate.setDate(start.getDate() + 14)
    periodForm.value.endDate = newEndDate

    // Auto-calculate pay date (+15 days)
    const newPayDate = new Date(start)
    newPayDate.setDate(start.getDate() + 14)
    periodForm.value.payDate = newPayDate

    // Auto-update half type based on start date
    const day = start.getDate()
    periodForm.value.halfType = day <= 15 ? '1st Half' : '2nd Half'

    // Auto-update name
    const monthName = start.toLocaleString('default', { month: 'long' })
    const year = start.getFullYear()
    periodForm.value.name = `${monthName} ${year} (${periodForm.value.halfType})`
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


    console.log(periodForm.value.name)
    // Format dates to YYYY-MM-DD
    const formatDate = (date: Date | null) => {
      if (!date) return null
      const d = new Date(date)
      const year = d.getFullYear()
      const month = String(d.getMonth() + 1).padStart(2, '0')
      const day = String(d.getDate()).padStart(2, '0')
      return `${year}-${month}-${day}`
    }

    const response = await axios.post('/api/payroll/periods',
      {
        name: periodForm.value.name,
        start_date: formatDate(periodForm.value.startDate),
        end_date: formatDate(periodForm.value.endDate),
        cutoff_date: formatDate(periodForm.value.payDate),
        notes: '' // Add notes if needed
      },
      {
        headers: {
          'Authorization': `Bearer 14|Lcuhac078HH2u8ryNGLIgirhwYUVyyDvnu7SgxqD069d74a9`
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
    const response = await axios.delete(`/api/payroll/periods/${periodToDelete.value.id}`)

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