<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Batches</h1>
            <p class="text-gray-600 mt-1">Manage product batches and expiry tracking</p>
          </div>
          <div class="flex gap-2">
            <Button
              label="Import Batches"
              icon="pi pi-upload"
              severity="secondary"
              outlined
            />
            <Button
              label="Export Batches"
              icon="pi pi-download"
              severity="secondary"
              outlined
            />
            <Button
              label="Add Batch"
              icon="pi pi-plus"
              @click="createBatch"
            />
          </div>
        </div>
      </div>

      <!-- Filters -->
      <Card class="mb-6">
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
              <InputText
                v-model="filters.search"
                placeholder="Search by batch number, product..."
                class="w-full"
                @input="debouncedFilter"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
              <Dropdown
                v-model="filters.product_id"
                :options="products"
                option-label="name"
                option-value="id"
                placeholder="Select Product"
                class="w-full"
                show-clear
                filter
                @change="applyFilters"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
              <Dropdown
                v-model="filters.status"
                :options="statusOptions"
                option-label="label"
                option-value="value"
                placeholder="Select Status"
                class="w-full"
                show-clear
                @change="applyFilters"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Status</label>
              <Dropdown
                v-model="filters.expiry_status"
                :options="expiryStatusOptions"
                option-label="label"
                option-value="value"
                placeholder="Select Expiry Status"
                class="w-full"
                show-clear
                @change="applyFilters"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Batches Table -->
      <Card>
        <template #content>
          <DataTable
            :value="batches"
            :loading="loading"
            :paginator="true"
            :rows="perPage"
            :total-records="totalRecords"
            :lazy="true"
            @page="onPage"
            @sort="onSort"
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rows-per-page-options="[10, 25, 50, 100]"
            current-page-report-template="Showing {first} to {last} of {totalRecords} batches"
            class="p-datatable-sm"
            striped-rows
            show-gridlines
            responsive-layout="scroll"
          >
            <template #empty>
              <div class="text-center py-8">
                <i class="pi pi-inbox text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">No batches found</p>
              </div>
            </template>

            <Column field="batch_number" header="Batch Number" sortable>
              <template #body="slotProps">
                <div class="font-medium text-blue-600">{{ slotProps.data.batch_number }}</div>
              </template>
            </Column>

            <Column field="product.name" header="Product" sortable>
              <template #body="slotProps">
                <div>
                  <div class="font-medium">{{ slotProps.data.product?.name }}</div>
                  <div class="text-sm text-gray-500">{{ slotProps.data.product?.code }}</div>
                </div>
              </template>
            </Column>

            <Column field="quantity" header="Quantity" sortable>
              <template #body="slotProps">
                <div class="text-right">
                  <div class="font-medium">{{ slotProps.data.quantity || 0 }}</div>
                  <div class="text-sm text-gray-500">{{ slotProps.data.unit?.name }}</div>
                </div>
              </template>
            </Column>

            <Column field="remaining_quantity" header="Remaining" sortable>
              <template #body="slotProps">
                <div class="text-right">
                  <div class="font-medium">{{ slotProps.data.remaining_quantity || 0 }}</div>
                  <div class="text-sm text-gray-500">
                    {{ slotProps.data.quantity ? Math.round(((slotProps.data.remaining_quantity || 0) / slotProps.data.quantity) * 100) : 0 }}% remaining
                  </div>
                </div>
              </template>
            </Column>

            <Column field="manufacture_date" header="Manufacture Date" sortable>
              <template #body="slotProps">
                <div>{{ formatDate(slotProps.data.manufacture_date) }}</div>
              </template>
            </Column>

            <Column field="expiry_date" header="Expiry Date" sortable>
              <template #body="slotProps">
                <div v-if="slotProps.data.expiry_date">
                  <div>{{ formatDate(slotProps.data.expiry_date) }}</div>
                  <div class="text-sm" :class="getExpiryStatus(slotProps.data.expiry_date).color">
                    {{ getExpiryStatus(slotProps.data.expiry_date).status }}
                  </div>
                </div>
                <div v-else class="text-gray-500 italic">No expiry</div>
              </template>
            </Column>

            <Column field="status" header="Status" sortable>
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.status"
                  :severity="getStatusSeverity(slotProps.data.status)"
                  class="capitalize"
                />
              </template>
            </Column>

            <Column header="Actions" style="width: 200px">
              <template #body="slotProps">
                <div class="flex gap-1">
                  <Button
                    icon="pi pi-eye"
                    severity="info"
                    outlined
                    size="small"
                    v-tooltip.top="'View Details'"
                    @click="viewBatch(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-pencil"
                    severity="warning"
                    outlined
                    size="small"
                    v-tooltip.top="'Edit Batch'"
                    @click="editBatch(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-qrcode"
                    severity="secondary"
                    outlined
                    size="small"
                    v-tooltip.top="'Print Labels'"
                    @click="printLabels(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    size="small"
                    v-tooltip.top="'Delete Batch'"
                    @click="confirmDelete(slotProps.data)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <!-- Delete Confirmation Dialog -->
      <Dialog
        v-model:visible="deleteDialogVisible"
        modal
        header="Confirm Delete"
        :style="{ width: '450px' }"
      >
        <div class="flex items-center gap-3">
          <i class="pi pi-exclamation-triangle text-3xl text-red-500"></i>
          <div>
            <p class="font-medium">Are you sure you want to delete this batch?</p>
            <p class="text-sm text-gray-600 mt-1">
              Batch Number: <strong>{{ selectedBatch?.batch_number }}</strong>
            </p>
            <p class="text-sm text-gray-600">This action cannot be undone and will affect all associated serial numbers.</p>
          </div>
        </div>
        <template #footer>
          <Button
            label="Cancel"
            icon="pi pi-times"
            severity="secondary"
            outlined
            @click="deleteDialogVisible = false"
          />
          <Button
            label="Delete"
            icon="pi pi-check"
            severity="danger"
            @click="deleteBatch"
            :loading="deleting"
          />
        </template>
      </Dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'
import { debounce } from 'lodash'

const loading = ref(false)
const deleting = ref(false)
const deleteDialogVisible = ref(false)
const selectedBatch = ref<any>(null)
const batches = ref<any[]>([])
const products = ref<any[]>([])
const totalRecords = ref(0)
const perPage = ref(25)

const filters = reactive({
  search: '',
  product_id: null,
  status: null,
  expiry_status: null
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Expired', value: 'expired' },
  { label: 'Recalled', value: 'recalled' },
  { label: 'Quarantined', value: 'quarantined' }
]

const expiryStatusOptions = [
  { label: 'Not Expired', value: 'not_expired' },
  { label: 'Expiring Soon', value: 'expiring_soon' },
  { label: 'Expired', value: 'expired' },
  { label: 'No Expiry', value: 'no_expiry' }
]

const toast = useToast()
const router = useRouter()

const debouncedFilter = debounce(() => {
  applyFilters()
}, 500)

const loadBatches = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: perPage.value,
      ...filters
    }

    const response = await inventoryService.getBatches(params)

    if (response.success) {
      batches.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load batches',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load batches',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadFilterData = async () => {
  try {
    const response = await inventoryService.getProducts({ per_page: 1000 })
    if (response.success) {
      products.value = response.data || []
    }
  } catch (error) {
    // Ignore filter data loading errors
  }
}

const applyFilters = () => {
  loadBatches(1)
}

const onPage = (event: any) => {
  loadBatches(event.page + 1)
}

const onSort = (event: any) => {
  // Handle sorting if needed
  loadBatches(1)
}

const createBatch = () => {
  router.push({ name: 'inventory.batches.create' })
}

const viewBatch = (batch: any) => {
  router.push({
    name: 'inventory.batches.show',
    params: { id: batch.id }
  })
}

const editBatch = (batch: any) => {
  router.push({
    name: 'inventory.batches.edit',
    params: { id: batch.id }
  })
}

const printLabels = (batch: any) => {
  // This would open a label printing dialog
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'Label printing functionality would be implemented here',
    life: 3000
  })
}

const confirmDelete = (batch: any) => {
  selectedBatch.value = batch
  deleteDialogVisible.value = true
}

const deleteBatch = async () => {
  if (!selectedBatch.value) return

  deleting.value = true
  try {
    const response = await inventoryService.deleteBatch(selectedBatch.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Batch deleted successfully',
        life: 3000
      })
      deleteDialogVisible.value = false
      loadBatches(1)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete batch',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete batch',
      life: 3000
    })
  } finally {
    deleting.value = false
  }
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const getExpiryStatus = (expiryDate: string) => {
  if (!expiryDate) return { status: 'No Expiry', color: 'text-gray-500' }

  const now = new Date()
  const expiry = new Date(expiryDate)
  const daysUntilExpiry = Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24))

  if (daysUntilExpiry < 0) {
    return { status: 'Expired', color: 'text-red-600' }
  } else if (daysUntilExpiry <= 30) {
    return { status: 'Expiring Soon', color: 'text-orange-600' }
  } else {
    return { status: 'Valid', color: 'text-green-600' }
  }
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'expired': return 'danger'
    case 'recalled': return 'danger'
    case 'quarantined': return 'warning'
    default: return 'secondary'
  }
}

onMounted(() => {
  loadBatches()
  loadFilterData()
})
</script>