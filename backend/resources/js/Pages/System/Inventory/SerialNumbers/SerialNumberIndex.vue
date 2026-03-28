<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Serial Numbers</h1>
            <p class="text-gray-600 mt-1">Manage product serial numbers and tracking</p>
          </div>
          <div class="flex gap-2">
            <Button
              label="Import Serial Numbers"
              icon="pi pi-upload"
              severity="secondary"
              outlined
            />
            <Button
              label="Export Serial Numbers"
              icon="pi pi-download"
              severity="secondary"
              outlined
            />
            <Button
              label="Add Serial Number"
              icon="pi pi-plus"
              @click="createSerialNumber"
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
                placeholder="Search by serial number, product..."
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
              <label class="block text-sm font-medium text-gray-700 mb-2">Warehouse</label>
              <Dropdown
                v-model="filters.warehouse_id"
                :options="warehouses"
                option-label="name"
                option-value="id"
                placeholder="Select Warehouse"
                class="w-full"
                show-clear
                @change="applyFilters"
              />
            </div>
          </div>
        </template>
      </Card>

      <!-- Serial Numbers Table -->
      <Card>
        <template #content>
          <DataTable
            :value="serialNumbers"
            :loading="loading"
            :paginator="true"
            :rows="perPage"
            :total-records="totalRecords"
            :lazy="true"
            @page="onPage"
            @sort="onSort"
            paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rows-per-page-options="[10, 25, 50, 100]"
            current-page-report-template="Showing {first} to {last} of {totalRecords} serial numbers"
            class="p-datatable-sm"
            striped-rows
            show-gridlines
            responsive-layout="scroll"
          >
            <template #empty>
              <div class="text-center py-8">
                <i class="pi pi-inbox text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-600">No serial numbers found</p>
              </div>
            </template>

            <Column field="serial_number" header="Serial Number" sortable>
              <template #body="slotProps">
                <div class="font-medium text-blue-600">{{ slotProps.data.serial_number }}</div>
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

            <Column field="batch.batch_number" header="Batch" sortable>
              <template #body="slotProps">
                <div v-if="slotProps.data.batch">
                  <div class="font-medium">{{ slotProps.data.batch.batch_number }}</div>
                  <div class="text-sm text-gray-500">{{ formatDate(slotProps.data.batch.expiry_date) }}</div>
                </div>
                <div v-else class="text-gray-500 italic">No batch</div>
              </template>
            </Column>

            <Column field="warehouse.name" header="Warehouse" sortable>
              <template #body="slotProps">
                <div>
                  <div class="font-medium">{{ slotProps.data.warehouse?.name }}</div>
                  <div class="text-sm text-gray-500">{{ slotProps.data.location?.name }}</div>
                </div>
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

            <Column field="condition" header="Condition" sortable>
              <template #body="slotProps">
                <Tag
                  :value="slotProps.data.condition"
                  :severity="getConditionSeverity(slotProps.data.condition)"
                  class="capitalize"
                />
              </template>
            </Column>

            <Column field="purchase_date" header="Purchase Date" sortable>
              <template #body="slotProps">
                <div>{{ formatDate(slotProps.data.purchase_date) }}</div>
              </template>
            </Column>

            <Column field="warranty_expiry" header="Warranty Expiry" sortable>
              <template #body="slotProps">
                <div v-if="slotProps.data.warranty_expiry">
                  <div>{{ formatDate(slotProps.data.warranty_expiry) }}</div>
                  <div class="text-sm" :class="isWarrantyExpired(slotProps.data.warranty_expiry) ? 'text-red-600' : 'text-green-600'">
                    {{ isWarrantyExpired(slotProps.data.warranty_expiry) ? 'Expired' : 'Active' }}
                  </div>
                </div>
                <div v-else class="text-gray-500 italic">No warranty</div>
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
                    @click="viewSerialNumber(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-pencil"
                    severity="warning"
                    outlined
                    size="small"
                    v-tooltip.top="'Edit Serial Number'"
                    @click="editSerialNumber(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-qrcode"
                    severity="secondary"
                    outlined
                    size="small"
                    v-tooltip.top="'Print QR Code'"
                    @click="printQRCode(slotProps.data)"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    outlined
                    size="small"
                    v-tooltip.top="'Delete Serial Number'"
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
            <p class="font-medium">Are you sure you want to delete this serial number?</p>
            <p class="text-sm text-gray-600 mt-1">
              Serial Number: <strong>{{ selectedSerialNumber?.serial_number }}</strong>
            </p>
            <p class="text-sm text-gray-600">This action cannot be undone.</p>
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
            @click="deleteSerialNumber"
            :loading="deleting"
          />
        </template>
      </Dialog>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, reactive } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'
import { debounce } from 'lodash'

const loading = ref(false)
const deleting = ref(false)
const deleteDialogVisible = ref(false)
const selectedSerialNumber = ref<any>(null)
const serialNumbers = ref<any[]>([])
const products = ref<any[]>([])
const warehouses = ref<any[]>([])
const totalRecords = ref(0)
const perPage = ref(25)

const filters = reactive({
  search: '',
  product_id: null,
  status: null,
  warehouse_id: null
})

const statusOptions = [
  { label: 'Available', value: 'available' },
  { label: 'Sold', value: 'sold' },
  { label: 'Reserved', value: 'reserved' },
  { label: 'Damaged', value: 'damaged' },
  { label: 'Lost', value: 'lost' }
]

const toast = useToast()
const router = useRouter()

const debouncedFilter = debounce(() => {
  applyFilters()
}, 500)

const loadSerialNumbers = async (page = 1) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: perPage.value,
      ...filters
    }

    const response = await inventoryService.getSerialNumbers(params)

    if (response.success) {
      serialNumbers.value = response.data || []
      totalRecords.value = response.meta?.total || 0
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load serial numbers',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load serial numbers',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadFilterData = async () => {
  try {
    const [productsResponse, warehousesResponse] = await Promise.all([
      inventoryService.getProducts({ per_page: 1000 }),
      inventoryService.getWarehouses({ per_page: 1000 })
    ])

    if (productsResponse.success) {
      products.value = productsResponse.data || []
    }

    if (warehousesResponse.success) {
      warehouses.value = warehousesResponse.data || []
    }
  } catch (error) {
    // Ignore filter data loading errors
  }
}

const applyFilters = () => {
  loadSerialNumbers(1)
}

const onPage = (event: any) => {
  loadSerialNumbers(event.page + 1)
}

const onSort = (event: any) => {
  // Handle sorting if needed
  loadSerialNumbers(1)
}

const createSerialNumber = () => {
  router.push({ name: 'inventory.serial-numbers.create' })
}

const viewSerialNumber = (serialNumber: any) => {
  router.push({
    name: 'inventory.serial-numbers.show',
    params: { id: serialNumber.id }
  })
}

const editSerialNumber = (serialNumber: any) => {
  router.push({
    name: 'inventory.serial-numbers.edit',
    params: { id: serialNumber.id }
  })
}

const printQRCode = (serialNumber: any) => {
  // This would open a QR code printing dialog
  toast.add({
    severity: 'info',
    summary: 'Info',
    detail: 'QR code printing functionality would be implemented here',
    life: 3000
  })
}

const confirmDelete = (serialNumber: any) => {
  selectedSerialNumber.value = serialNumber
  deleteDialogVisible.value = true
}

const deleteSerialNumber = async () => {
  if (!selectedSerialNumber.value) return

  deleting.value = true
  try {
    const response = await inventoryService.deleteSerialNumber(selectedSerialNumber.value.id)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Serial number deleted successfully',
        life: 3000
      })
      deleteDialogVisible.value = false
      loadSerialNumbers(1)
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete serial number',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete serial number',
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

const isWarrantyExpired = (warrantyExpiry: string) => {
  if (!warrantyExpiry) return false
  return new Date(warrantyExpiry) < new Date()
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'available': return 'success'
    case 'sold': return 'info'
    case 'reserved': return 'warning'
    case 'damaged': return 'danger'
    case 'lost': return 'danger'
    default: return 'secondary'
  }
}

const getConditionSeverity = (condition: string) => {
  switch (condition) {
    case 'new': return 'success'
    case 'good': return 'info'
    case 'fair': return 'warning'
    case 'poor': return 'danger'
    case 'damaged': return 'danger'
    default: return 'secondary'
  }
}

onMounted(() => {
  loadSerialNumbers()
  loadFilterData()
})
</script>