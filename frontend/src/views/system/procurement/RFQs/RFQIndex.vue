<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Request for Quotations</h1>
        <p class="text-gray-600 mt-1">Manage RFQ lifecycle and supplier responses</p>
      </div>
      <Button label="Create RFQ" icon="pi pi-plus" severity="success" @click="router.push({ name: 'procurement.rfqs.create' })" size="large" />
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total RFQs</p>
              <p class="text-3xl font-bold text-gray-900">{{ summary.total || 0 }}</p>
            </div>
            <i class="pi pi-briefcase text-4xl text-blue-500 opacity-20"></i>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Draft</p>
              <p class="text-3xl font-bold text-gray-600">{{ summary.draft || 0 }}</p>
            </div>
            <i class="pi pi-file text-4xl text-gray-500 opacity-20"></i>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Active</p>
              <p class="text-3xl font-bold text-blue-600">{{ summary.sent || 0 }}</p>
            </div>
            <i class="pi pi-send text-4xl text-blue-500 opacity-20"></i>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Awarded</p>
              <p class="text-3xl font-bold text-green-600">{{ summary.awarded || 0 }}</p>
            </div>
            <i class="pi pi-check text-4xl text-green-500 opacity-20"></i>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold">Filter by Status</label>
            <Select v-model="filterStatus" :options="statusOptions" optionLabel="label" optionValue="value" 
              placeholder="All Statuses" clearable @change="loadRFQs" />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold">Search</label>
            <InputText v-model="searchQuery" placeholder="Search RFQ number or title..." @keyup="loadRFQs" />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold">Per Page</label>
            <Select v-model="perPage" :options="[10, 15, 20, 50]" @change="loadRFQs" />
          </div>
        </div>
      </template>
    </Card>

    <!-- DataTable -->
    <Card>
      <template #content>
        <DataTable 
          :value="rfqs" 
          :loading="loading" 
          class="p-datatable-sm" 
          stripedRows
          :expandedRows="expandedRows"
          responsiveLayout="scroll"
          paginator
          :rows="perPage"
          :totalRecords="total"
          :first="(currentPage - 1) * perPage"
          @page="onPageChange"
        >

          
          <!-- RFQ Number -->
          <Column field="rfq_number" header="RFQ No." style="width: 120px">
            <template #body="{ data }">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                {{ data.rfq_number }}
              </span>
            </template>
          </Column>

          <!-- Title -->
          <Column field="title" header="Title" style="width: 250px">
            <template #body="{ data }">
              <div>
                <p class="font-semibold text-gray-900">{{ data.title }}</p>
                <p class="text-xs text-gray-600 mt-1">Type: <span class="font-medium">{{ capitalizeWords(data.rfq_type) }}</span></p>
              </div>
            </template>
          </Column>

          <!-- Created By -->
          <Column field="created_by" header="Created By" style="width: 150px">
            <template #body="{ data }">
              <div v-if="data.created_by">
                <p class="font-medium text-gray-900">{{ data.created_by.fname }} {{ data.created_by.lname }}</p>
                <p class="text-xs text-gray-600">{{ data.created_by.employee_number }}</p>
              </div>
            </template>
          </Column>

          <!-- Deadline -->
          <Column field="deadline_date" header="Deadline" style="width: 140px">
            <template #body="{ data }">
              <div>
                <p class="font-medium text-gray-900">{{ formatDate(data.deadline_date) }}</p>
                <p class="text-xs font-semibold" :class="getDaysRemainingClass(data.deadline_date)">
                  {{ calculateDaysRemaining(data.deadline_date) }}
                </p>
              </div>
            </template>
          </Column>

          <!-- Status -->
          <Column field="status" header="Status" style="width: 130px">
            <template #body="{ data }">
              <Tag :value="data.status.toUpperCase()" :severity="statusSeverity(data.status)" />
            </template>
          </Column>

          <!-- Currency & Payment Terms -->
          <Column header="Terms" style="width: 150px">
            <template #body="{ data }">
              <div class="text-sm">
                <p class="font-medium">{{ data.currency }} • {{ capitalizeWords(data.payment_terms) }}</p>
                <p class="text-xs text-gray-600" v-if="data.shipping_terms">{{ data.shipping_terms }}</p>
              </div>
            </template>
          </Column>

          <!-- Actions -->
          <Column header="Actions" style="width: 160px">
            <template #body="{ data }">
              <div class="flex gap-2 items-center justify-end">
                <Button 
                  icon="pi pi-eye" 
                  text 
                  rounded 
                  severity="info" 
                  @click="router.push({ name: 'procurement.rfqs.detail', params: { id: data.id } })"
                  v-tooltip="'View Details'"
                />
                <Button
                  v-if="data.status === 'completed'"
                  icon="pi pi-shopping-cart"
                  text
                  rounded
                  severity="success"
                  @click="createPOFromRFQ(data.id)"
                  v-tooltip="'Create PO'"
                />
                <Button 
                  v-if="data.status === 'draft'"
                  icon="pi pi-pencil" 
                  text 
                  rounded 
                  severity="warning" 
                  @click="editRFQ(data.id)"
                  v-tooltip="'Edit'"
                />
                <Button 
                  v-if="data.status === 'draft'"
                  icon="pi pi-trash" 
                  text 
                  rounded 
                  severity="danger" 
                  @click="deleteRFQ(data.id)"
                  v-tooltip="'Delete'"
                />
              </div>
            </template>
          </Column>

          <!-- Expanded Row Detail -->
          <template #expansion="{ data }">
            <div class="p-6 bg-gradient-to-r from-gray-50 to-gray-100 border-t">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- RFQ Info -->
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">📋 RFQ Information</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Issue Date</p>
                      <p class="font-medium text-gray-900">{{ formatDate(data.issue_date) }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Expected Delivery</p>
                      <p class="font-medium text-gray-900">{{ formatDate(data.expected_delivery_date) || 'Not specified' }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Created</p>
                      <p class="font-medium text-gray-900">{{ formatDateTime(data.created_at) }}</p>
                    </div>
                    <div v-if="data.description">
                      <p class="text-gray-600">Description</p>
                      <p class="font-medium text-gray-900 line-clamp-2">{{ data.description }}</p>
                    </div>
                  </div>
                </div>

                <!-- Terms Info -->
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">💰 Terms & Conditions</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Currency</p>
                      <p class="font-medium text-gray-900">{{ data.currency }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Payment Terms</p>
                      <p class="font-medium text-gray-900">{{ capitalizeWords(data.payment_terms) }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Shipping Terms</p>
                      <p class="font-medium text-gray-900">{{ data.shipping_terms || 'Not specified' }}</p>
                    </div>
                    <div v-if="data.qualification_requirements">
                      <p class="text-gray-600">Qualifications</p>
                      <p class="font-medium text-gray-900 line-clamp-2">{{ data.qualification_requirements }}</p>
                    </div>
                  </div>
                </div>

                <!-- Assignment Info -->
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">👤 Assignment</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Assigned To</p>
                      <p class="font-medium text-gray-900">{{ data.assigned_to ? `Employee #${data.assigned_to}` : 'Not assigned' }}</p>
                    </div>
                    <div v-if="data.awarded_to_supplier_id">
                      <p class="text-gray-600">Awarded To</p>
                      <p class="font-medium text-green-600">Supplier #{{ data.awarded_to_supplier_id }}</p>
                    </div>
                    <div v-if="data.awarded_at">
                      <p class="text-gray-600">Awarded Date</p>
                      <p class="font-medium text-gray-900">{{ formatDateTime(data.awarded_at) }}</p>
                    </div>
                    <div v-if="data.evaluation_notes">
                      <p class="text-gray-600">Notes</p>
                      <p class="font-medium text-gray-900 line-clamp-2">{{ data.evaluation_notes }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </template>

          <!-- Empty State -->
          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-inbox text-5xl text-gray-300 mb-4"></i>
              <p class="text-lg text-gray-600">No RFQs found</p>
              <p class="text-sm text-gray-500 mt-1">Create a new RFQ to get started</p>
            </div>
          </template>

          <!-- Loading -->
          <template #loadingicon>
            <i class="pi pi-spin pi-spinner"></i>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const toast = useToast()

const loading = ref(false)
const rfqs = ref<any[]>([])
const expandedRows = ref<any[]>([])
const currentPage = ref(1)
const perPage = ref(15)
const total = ref(0)
const filterStatus = ref<string | null>(null)
const searchQuery = ref('')

const summary = computed(() => {
  return {
    total: rfqs.value.length,
    draft: rfqs.value.filter(r => r.status === 'draft').length,
    sent: rfqs.value.filter(r => r.status === 'sent').length,
    awarded: rfqs.value.filter(r => r.status === 'awarded').length,
  }
})

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Sent', value: 'sent' },
  { label: 'Quotes Received', value: 'quotes_received' },
  { label: 'Awarded', value: 'awarded' },
  { label: 'Completed', value: 'completed' },
  { label: 'Cancelled', value: 'cancelled' },
]

const formatDate = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const capitalizeWords = (str: string | null): string => {
  if (!str) return ''
  return str
    .split('_')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(' ')
}

const calculateDaysRemaining = (deadline: string | null): string => {
  if (!deadline) return 'N/A'
  const deadlineDate = new Date(deadline)
  const today = new Date()
  const days = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))
  
  if (days < 0) return `${Math.abs(days)}d ago`
  if (days === 0) return 'Today'
  if (days === 1) return 'Tomorrow'
  return `${days}d remaining`
}

const getDaysRemainingClass = (deadline: string | null): string => {
  if (!deadline) return 'text-gray-600'
  const deadlineDate = new Date(deadline)
  const today = new Date()
  const days = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))
  
  if (days < 0) return 'text-red-600 font-semibold'
  if (days < 3) return 'text-orange-600 font-semibold'
  return 'text-green-600'
}

const statusSeverity = (status: string): string => {
  const severityMap: Record<string, string> = {
    draft: 'secondary',
    sent: 'info',
    quotes_received: 'warning',
    awarded: 'success',
    completed: 'success',
    cancelled: 'danger',
  }
  return severityMap[status] || 'secondary'
}

const loadRFQs = async (page: number = 1) => {
  loading.value = true
  try {
    const params: any = {
      page,
      per_page: perPage.value,
    }

    if (filterStatus.value) {
      params.status = filterStatus.value
    }

    if (searchQuery.value) {
      params.search = searchQuery.value
    }

    const response = await procurementService.getRFQs(params)
    rfqs.value = response.data?.data || []
    total.value = response.data?.total || 0
    currentPage.value = page
  } catch (error) {
    console.error('Failed to load RFQs', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load RFQs',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onPageChange = (event: any) => {
  loadRFQs(event.page + 1)
}

const editRFQ = (id: number) => {
  // TODO: Implement edit functionality
  toast.add({
    severity: 'info',
    summary: 'Not Implemented',
    detail: 'Edit functionality coming soon',
    life: 3000,
  })
}

const deleteRFQ = (id: number) => {
  // TODO: Implement delete with confirmation
  toast.add({
    severity: 'info',
    summary: 'Not Implemented',
    detail: 'Delete functionality coming soon',
    life: 3000,
  })
}

const createPOFromRFQ = (rfqId: number) => {
  router.push({
    name: 'procurement.purchase-orders.create-legacy',
    query: { rfq_id: rfqId },
  })
}

onMounted(() => {
  loadRFQs()
})
</script>
