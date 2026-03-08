<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Stock Adjustments</h1>
        <p class="text-gray-600 mt-1">Manage and track inventory adjustments</p>
      </div>
      <Button label="Create Adjustment" icon="pi pi-plus" severity="success"
        @click="router.push({ name: 'inventory.adjustments.create' })" />
    </div>
  
    <!-- Filters -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
            placeholder="All Statuses" showClear @change="() => loadAdjustments(1)" />
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search reference no..." @keyup.enter="loadAdjustments(1)" />
          </IconField>
          <Calendar v-model="filters.start_date" dateFormat="yy-mm-dd" placeholder="From Date"
            @date-select="() => loadAdjustments(1)" />
          <Button icon="pi pi-filter-slash" label="Reset" @click="resetFilters" />
        </div>
      </template>
    </Card>
  
    <!-- Adjustments Table -->
    <Card>
      <template #content>
        <DataTable :value="adjustments" :loading="loading" paginator :rows="pagination.per_page"
          :totalRecords="pagination.total" :first="(pagination.current_page - 1) * pagination.per_page"
          @page="onPageChange" dataKey="id" class="p-datatable-sm" stripedRows>
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No adjustments found</p>
            </div>
          </template>
  
          <Column field="reference_no" header="Reference No." style="width: 15%">
            <template #body="{ data }">
              <span class="font-medium">{{ data.adjustment_number }}</span>
            </template>
          </Column>
  
          <Column field="reason" header="Reason" style="width: 20%">
            <template #body="{ data }">
              {{ capitalizeFirstLetter(data.reason || 'N/A') }}
            </template>
          </Column>
  
          <Column field="adjustment_date" header="Date" style="width: 15%">
            <template #body="{ data }">
              {{ formatDate(data.adjustment_date) }}
            </template>
          </Column>
  
          <Column field="status" header="Status" style="width: 15%">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
  
          <Column header="Actions" style="width: 15%">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-eye" size="small" text severity="info"
                  @click="router.push({ name: 'inventory.adjustments.detail', params: { id: data.id } })"
                  v-tooltip="'View details'" />
                <Button v-if="data.status === 'draft'" icon="pi pi-pencil" size="small" text severity="warning"
                  @click="router.push({ name: 'inventory.adjustments.detail', params: { id: data.id } })"
                  v-tooltip="'Edit'" />
                <Button v-if="data.status === 'draft'" icon="pi pi-times" size="small" text severity="danger"
                  @click="confirmCancel(data)" v-tooltip="'Cancel'" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <!-- Cancel Confirmation Dialog -->
    <Dialog
      v-model:visible="showCancelDialog"
      header="Cancel Adjustment"
      :modal="true"
      :style="{ width: '400px' }"
    >
      <p class="text-gray-600">Are you sure you want to cancel this adjustment? This action cannot be undone.</p>
      <template #footer>
        <Button label="No, Keep" severity="secondary" outlined @click="showCancelDialog = false" />
        <Button label="Yes, Cancel" severity="danger" :loading="cancelling" @click="doCancel" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

interface Pagination {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number
  to?: number
}

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const cancelling = ref(false)
const adjustments = ref<any[]>([])
const showCancelDialog = ref(false)
const cancelTarget = ref<any>(null)

const pagination = reactive<Pagination>({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0
})

const filters = reactive({
  status: null as string | null,
  search: '',
  start_date: null as Date | null
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Submitted', value: 'submitted' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' }
]

const statusSeverity = (status: string) => {
  const severities: Record<string, string> = {
    draft: 'secondary',
    submitted: 'info',
    approved: 'success',
    rejected: 'danger'
  }
  return severities[status] || 'secondary'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const capitalizeFirstLetter = (string: string) => {
  if (!string) return 'N/A'
  return string.charAt(0).toUpperCase() + string.slice(1)
}

const formatStatus = (status: string) => {
  if (!status) return 'N/A'
  return status.split('_')
    .map((word: string) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
}

const loadAdjustments = async (page = pagination.current_page) => {
  loading.value = true
  try {
    const params: Record<string, any> = {
      page,
      per_page: pagination.per_page
    }

    if (filters.status) params.status = filters.status
    if (filters.search) params.search = filters.search
    if (filters.start_date) params.start_date = (filters.start_date as Date).toISOString().split('T')[0]

    const response = await inventoryService.getAdjustments(params)

    // Handle Laravel pagination format
    if (response?.success && response?.data) {
      const paginatedData = response.data
      adjustments.value = paginatedData.data || []
      pagination.current_page = paginatedData.current_page || page
      pagination.last_page = paginatedData.last_page || 1
      pagination.per_page = paginatedData.per_page || pagination.per_page
      pagination.total = paginatedData.total || 0
    }
    // Handle direct array response (fallback)
    else if (Array.isArray(response)) {
      adjustments.value = response
      pagination.total = response.length
    }
    else {
      adjustments.value = []
    }

  } catch (error: any) {
    console.error('Failed to load adjustments', error)
    adjustments.value = []
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load adjustments',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const onPageChange = (event: any) => {
  pagination.current_page = event.page + 1
  pagination.per_page = event.rows
  loadAdjustments()
}

const confirmCancel = (adjustment: any) => {
  cancelTarget.value = adjustment
  showCancelDialog.value = true
}

const doCancel = async () => {
  if (!cancelTarget.value) return
  cancelling.value = true
  try {
    await inventoryService.cancelAdjustment(cancelTarget.value.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Adjustment cancelled successfully',
      life: 2000
    })
    showCancelDialog.value = false
    loadAdjustments(pagination.current_page)
  } catch (error: any) {
    console.error('Failed to cancel adjustment', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to cancel adjustment',
      life: 3000
    })
  } finally {
    cancelling.value = false
  }
}

const resetFilters = () => {
  filters.status = null
  filters.search = ''
  filters.start_date = null
  loadAdjustments(1)
}

onMounted(() => {
  loadAdjustments()
})
</script>
