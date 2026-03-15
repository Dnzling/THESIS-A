<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Supplier Contracts</h1>
        <p class="text-gray-600 mt-1">Manage supplier contracts with terms and document attachments</p>
      </div>
      <Button label="Create Contract" icon="pi pi-plus" severity="success" @click="router.push({ name: 'procurement.supplier-contracts.create' })" size="large" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Total Contracts</p>
              <p class="text-3xl font-bold text-gray-900">{{ summary.total }}</p>
            </div>
            <i class="pi pi-file-contract text-4xl text-blue-500 opacity-20"></i>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Active</p>
              <p class="text-3xl font-bold text-green-600">{{ summary.active }}</p>
            </div>
            <i class="pi pi-check-circle text-4xl text-green-500 opacity-20"></i>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Expiring Soon</p>
              <p class="text-3xl font-bold text-orange-600">{{ summary.expiring }}</p>
            </div>
            <i class="pi pi-calendar text-4xl text-orange-500 opacity-20"></i>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm text-gray-600">Draft</p>
              <p class="text-3xl font-bold text-gray-600">{{ summary.draft }}</p>
            </div>
            <i class="pi pi-pencil text-4xl text-gray-500 opacity-20"></i>
          </div>
        </template>
      </Card>
    </div>

    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold">Filter by Status</label>
            <Select v-model="filterStatus" :options="statusOptions" optionLabel="label" optionValue="value" 
              placeholder="All Statuses" clearable @change="loadContracts" />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold">Filter by Type</label>
            <Select v-model="filterType" :options="contractTypeOptions" optionLabel="label" optionValue="value" 
              placeholder="All Types" clearable @change="loadContracts" />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold">Search Supplier</label>
            <InputText v-model="filterSupplier" placeholder="Supplier name or code" @input="loadContracts" />
          </div>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold">Per Page</label>
            <Select v-model="perPage" :options="[10, 15, 20, 50]" @change="loadContracts" />
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable 
          :value="contracts" 
          :loading="loading" 
          class="p-datatable-sm" 
          stripedRows
          :expandedRows="expandedRows"
          @update:expandedRows="expandedRows = $event"
          responsiveLayout="scroll"
          paginator
          :rows="perPage"
          :totalRecords="total"
          :first="(currentPage - 1) * perPage"
          @page="onPageChange"
        >
          <Column :expander="true" style="width: 3rem" />
          
          <Column field="contract_number" header="Contract No." style="width: 140px">
            <template #body="{ data }">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800">
                {{ data?.contract_number }}
              </span>
            </template>
          </Column>

          <Column header="Supplier" style="width: 160px">
            <template #body="{ data }">
              <div class="text-sm">
                <p class="font-medium text-gray-900">{{ data?.supplier?.supplier_name || 'N/A' }}</p>
                <p class="text-xs text-gray-600">{{ data?.supplier?.supplier_code }}</p>
              </div>
            </template>
          </Column>

          <Column field="contract_title" header="Title" style="width: 180px">
            <template #body="{ data }">
              <span class="text-sm text-gray-900">{{ data?.contract_title || 'N/A' }}</span>
            </template>
          </Column>

          <Column header="Dates" style="width: 160px">
            <template #body="{ data }">
              <div class="text-sm">
                <p class="font-medium text-gray-900">{{ formatDate(data?.start_date) }}</p>
                <p class="text-xs text-gray-600">to {{ formatDate(data?.end_date) }}</p>
                <p class="text-xs font-semibold mt-1" :class="getExpiryClass(data?.end_date)">
                  {{ calculateDaysRemaining(data?.end_date) }}
                </p>
              </div>
            </template>
          </Column>

          <Column header="Type & Terms" style="width: 140px">
            <template #body="{ data }">
              <div class="text-sm">
                <p class="font-medium text-gray-900 capitalize">{{ data?.contract_type?.replace('_', ' ') }}</p>
                <p class="text-xs text-gray-600 mt-1">
                  <i class="pi pi-check-circle text-green-600 mr-1"></i>Min Order: ₱{{ parseFloat(data?.minimum_order_value || 0).toLocaleString('en-PH') }}
                </p>
              </div>
            </template>
          </Column>

          <Column header="Discount & Terms" style="width: 130px">
            <template #body="{ data }">
              <div class="text-sm">
                <p class="font-bold text-orange-600">{{ data?.discount_percentage || 0 }}% OFF</p>
                <p class="text-xs text-gray-600">Net {{ data?.payment_terms_days }} Days</p>
              </div>
            </template>
          </Column>

          <Column field="status" header="Status" style="width: 110px">
            <template #body="{ data }">
              <Tag :value="data?.status?.toUpperCase() || 'DRAFT'" :severity="statusSeverity(data?.status)" />
            </template>
          </Column>

          <Column header="Actions" style="width: 140px">
            <template #body="{ data }">
              <div class="flex gap-2 items-center justify-end">
                <Button icon="pi pi-eye" text rounded severity="info" 
                  @click="router.push({ name: 'procurement.supplier-contracts.detail', params: { id: data?.id } })"
                  v-tooltip="'View Details'" />
                <Button v-if="data?.status === 'draft'" icon="pi pi-pencil" text rounded severity="warning" 
                  @click="editContract(data?.id)" v-tooltip="'Edit'" />
                <Button v-if="data?.status === 'draft'" icon="pi pi-trash" text rounded severity="danger" 
                  @click="deleteContract(data?.id)" v-tooltip="'Delete'" />
              </div>
            </template>
          </Column>

          <template #expansion="{ data }">
            <div class="p-6 bg-gradient-to-r from-gray-50 to-gray-100 border-t">
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">📋 Contract Details</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Contract Number</p>
                      <p class="font-medium text-gray-900">{{ data?.contract_number }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Type</p>
                      <p class="font-medium text-gray-900 capitalize">{{ data?.contract_type?.replace('_', ' ') }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Created By</p>
                      <p class="font-medium text-gray-900">{{ data?.created_by?.fname }} {{ data?.created_by?.lname }}</p>
                    </div>
                  </div>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">💰 Financial Terms</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Minimum Order Value</p>
                      <p class="font-bold text-orange-600">₱{{ parseFloat(data?.minimum_order_value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Discount</p>
                      <p class="font-medium text-green-600 text-lg">{{ data?.discount_percentage || 0 }}%</p>
                    </div>
                    <div>
                      <p class="text-gray-600">Payment Terms</p>
                      <p class="font-medium text-gray-900">Net {{ data?.payment_terms_days }} Days</p>
                    </div>
                  </div>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-800 mb-3">📅 Validity Period</h4>
                  <div class="space-y-2 text-sm">
                    <div>
                      <p class="text-gray-600">Start Date</p>
                      <p class="font-medium text-gray-900">{{ formatDate(data?.start_date) }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600">End Date</p>
                      <p class="font-medium text-gray-900">{{ formatDate(data?.end_date) }}</p>
                    </div>
                    <div>
                      <p class="text-gray-600 font-semibold mb-1">Days Remaining</p>
                      <p class="font-bold text-lg" :class="getExpiryClass(data?.end_date)">{{ calculateDaysRemaining(data?.end_date) }}</p>
                    </div>
                  </div>
                </div>
              </div>
              <div class="mt-4 p-3 bg-white rounded border border-gray-200">
                <p class="text-xs font-semibold text-gray-600 mb-2 uppercase">Terms & Conditions</p>
                <p class="text-sm text-gray-900">{{ data?.terms_conditions || 'No additional terms specified' }}</p>
              </div>
              <div v-if="data?.contract_file_path" class="mt-4">
                <Button icon="pi pi-download" label="Download Contract Document" severity="info" outlined @click="downloadDocument(data?.contract_file_path)" />
              </div>
            </div>
          </template>

          <template #empty>
            <div class="text-center py-12">
              <i class="pi pi-inbox text-5xl text-gray-300 mb-4"></i>
              <p class="text-lg text-gray-600">No supplier contracts found</p>
            </div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, reactive, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const contracts = ref<any[]>([])
const expandedRows = ref<any[]>([])
const currentPage = ref(1)
const perPage = ref(15)
const total = ref(0)
const filterStatus = ref<string | null>(null)
const filterType = ref<string | null>(null)
const filterSupplier = ref<string>('')

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Draft', value: 'draft' },
  { label: 'Expired', value: 'expired' },
  { label: 'Terminated', value: 'terminated' },
]

const contractTypeOptions = [
  { label: 'Fixed Price', value: 'fixed_price' },
  { label: 'Volume Discount', value: 'volume_discount' },
  { label: 'Consignment', value: 'consignment' },
  { label: 'Exclusive', value: 'exclusive' },
]

const summary = computed(() => {
  const today = new Date()
  const thirtyDaysAhead = new Date(today.getTime() + 30 * 24 * 60 * 60 * 1000)

  return {
    total: total.value,
    active: contracts.value.filter(c => c?.status === 'active').length,
    draft: contracts.value.filter(c => c?.status === 'draft').length,
    expiring: contracts.value.filter(c => {
      const endDate = new Date(c?.end_date)
      return endDate <= thirtyDaysAhead && endDate >= today && c?.status === 'active'
    }).length,
  }
})

const formatDate = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const calculateDaysRemaining = (deadline: string | null): string => {
  if (!deadline) return 'N/A'
  const deadlineDate = new Date(deadline)
  const today = new Date()
  const days = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))
  if (days < 0) return `Expired ${Math.abs(days)}d ago`
  if (days === 0) return 'Expires Today'
  if (days === 1) return 'Expires Tomorrow'
  if (days < 30) return `Expires in ${days}d`
  return `Expires in ${Math.ceil(days / 30)}mo`
}

const getExpiryClass = (deadline: string | null): string => {
  if (!deadline) return 'text-gray-600'
  const deadlineDate = new Date(deadline)
  const today = new Date()
  const days = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))
  if (days < 0) return 'text-red-600 font-semibold'
  if (days < 30) return 'text-orange-600 font-semibold'
  return 'text-green-600'
}

const statusSeverity = (status: string): string => {
  const severityMap: Record<string, string> = {
    active: 'success',
    draft: 'secondary',
    expired: 'danger',
    terminated: 'warning',
  }
  return severityMap[status] || 'secondary'
}

const loadContracts = async (page: number = 1) => {
  loading.value = true
  try {
    const params: any = {
      page,
      per_page: perPage.value,
    }
    if (filterStatus.value) params.status = filterStatus.value
    if (filterType.value) params.contract_type = filterType.value
    if (filterSupplier.value) params.supplier_search = filterSupplier.value

    const response = await procurementService.getSupplierContracts(params)
    contracts.value = response.data?.data || []
    total.value = response.data?.total || 0
    currentPage.value = page
  } catch (error) {
    console.error('Failed to load contracts', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load contracts',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const onPageChange = (event: any) => {
  loadContracts(event.page + 1)
}

const editContract = (id: number) => {
  router.push({ name: 'procurement.supplier-contracts.edit', params: { id } })
}

const deleteContract = (id: number) => {
  toast.add({ severity: 'info', summary: 'Not Implemented', detail: 'Delete coming soon', life: 3000 })
}

const downloadDocument = (filePath: string) => {
  if (filePath) {
    window.location.href = filePath
  }
}

onMounted(() => {
  loadContracts()
})
</script>
