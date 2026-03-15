<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Stock Order Requests</h1>
        <p class="text-gray-600 mt-1">Manage requests for low stock or out of stock items</p>
      </div>
      <Button 
        label="Create Request"
        icon="pi pi-plus"
        severity="success"
        @click="router.push({ name: 'stock-order-requests.create' })"
      />
    </div>

    <!-- Filters -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search product or reason" class="w-full" @keyup.enter="loadRequests" />
          </IconField>

          <Select
            v-model="filters.status"
            :options="statuses"
            optionLabel="label"
            optionValue="value"
            placeholder="Status"
            class="w-full"
            showClear
            @change="loadRequests"
          />

          <Button icon="pi pi-search" label="Search" @click="loadRequests" />
          <Button icon="pi pi-filter-slash" label="Reset" severity="secondary" @click="resetFilters" />
        </div>
      </template>
    </Card>

    <!-- Requests Table -->
    <Card>
      <template #content>
        <DataTable
          :value="requests"
          :loading="loading"
          paginator
          :rows="filters.per_page"
          :totalRecords="totalRecords"
          :lazy="true"
          @page="onPage"
          dataKey="id"
          :rowsPerPageOptions="[15, 25, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          class="p-datatable-sm"
          stripedRows
        >
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No stock order requests found</p>
            </div>
          </template>

          <Column field="branch_inventory.product.product_name" header="Product" style="width: 20%">
            <template #body="{ data }">
              <div>
                <div class="font-medium text-gray-800">{{ data.branch_inventory?.product?.product_name || 'N/A' }}</div>
                <div class="text-sm text-gray-500">SKU: {{ data.branch_inventory?.product?.sku || 'N/A' }}</div>
              </div>
            </template>
          </Column>

          <Column field="requested_quantity" header="Qty Requested" style="width: 12%">
            <template #body="{ data }">
              <span class="font-medium">{{ data.requested_quantity }}</span> units
            </template>
          </Column>

          <Column field="branch_inventory.branch.name" header="Branch" style="width: 15%">
            <template #body="{ data }">
              {{ data.branch_inventory?.branch?.name || 'N/A' }}
            </template>
          </Column>

          <Column field="status" header="Status" style="width: 15%">
            <template #body="{ data }">
              <Tag :value="getStatusLabel(data.status)" :severity="getStatusSeverity(data.status)" />
            </template>
          </Column>

          <Column field="created_at" header="Created" style="width: 12%">
            <template #body="{ data }">
              {{ formatDate(data.created_at) }}
            </template>
          </Column>

          <Column header="Actions" style="width: 16%">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button
                  icon="pi pi-eye"
                  size="small"
                  text
                  severity="info"
                  @click="router.push({ name: 'stock-order-requests.detail', params: { id: data.id } })"
                  v-tooltip="'View details'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <ConfirmDialog />
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'

const loading = ref(false)
const requests = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()
const confirm = useConfirm()
const router = useRouter()

const filters = reactive({
  search: '',
  status: null as string | null,
  page: 1,
  per_page: 15
})

const statuses = [
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Converted to PO', value: 'converted_to_po' },
  { label: 'Rejected', value: 'rejected' },
  { label: 'Cancelled', value: 'cancelled' },
  { label: 'Partially Ordered', value: 'partially_ordered' }
]

const loadRequests = async () => {
  loading.value = true
  try {
    const params: any = {
      page: filters.page,
      per_page: filters.per_page
    }

    if (filters.search) params.search = filters.search
    if (filters.status) params.status = filters.status
    const response = await procurementService.getStockOrderRequests(params)

    const pageData = response?.data

    if (pageData?.data) {
      requests.value = pageData.data
      totalRecords.value = pageData.total ?? pageData.meta?.total ?? requests.value.length
    } else {
      requests.value = []
      totalRecords.value = 0
    }
  } catch (error: any) {
    console.error('Failed to load stock order requests', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to load stock order requests',
      life: 3000
    })
    requests.value = []
    totalRecords.value = 0
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadRequests()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = null
  filters.page = 1
  filters.per_page = 15
  loadRequests()
}

const getStatusLabel = (status: string) => {
  const labels: Record<string, string> = {
    pending: 'Pending',
    approved: 'Approved',
    converted_to_po: 'Converted to PO',
    rejected: 'Rejected',
    cancelled: 'Cancelled',
    partially_ordered: 'Partially Ordered'
  }
  return labels[status] ?? status
}

const getStatusSeverity = (status: string) => {
  const severities: Record<string, string> = {
    pending: 'warning',
    approved: 'success',
    converted_to_po: 'info',
    rejected: 'danger',
    cancelled: 'secondary',
    partially_ordered: 'info'
  }
  return severities[status] ?? 'secondary'
}

const formatDate = (date: string) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const approveRequest = (request: any) => {
  confirm.require({
    message: `Approve stock order request for "${request.product?.product_name}"?`,
    header: 'Confirm Approval',
    icon: 'pi pi-question-circle',
    acceptSeverity: 'success',
    accept: async () => {
      try {
        await procurementService.approveStockOrderRequest(request.id)
        toast.add({
          severity: 'success',
          summary: 'Approved',
          detail: 'Request approved successfully',
          life: 3000
        })
        loadRequests()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.message || 'Failed to approve request',
          life: 3000
        })
      }
    }
  })
}

const rejectRequest = (request: any) => {
  confirm.require({
    message: `Reject stock order request for "${request.product?.product_name}"?`,
    header: 'Confirm Rejection',
    icon: 'pi pi-exclamation-triangle',
    acceptSeverity: 'danger',
    accept: async () => {
      try {
        await procurementService.rejectStockOrderRequest(request.id, 'Rejected by user')
        toast.add({
          severity: 'success',
          summary: 'Rejected',
          detail: 'Request rejected successfully',
          life: 3000
        })
        loadRequests()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.message || 'Failed to reject request',
          life: 3000
        })
      }
    }
  })
}

onMounted(() => {
  loadRequests()
})
</script>
