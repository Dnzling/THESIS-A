<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
      <!-- Header -->
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <Button
              icon="pi pi-arrow-left"
              severity="secondary"
              text
              @click="goBack"
              v-tooltip.top="'Back to Serial Number'"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-800">Edit Serial Number</h1>
              <p class="text-gray-600 mt-1">Update serial number information</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Form -->
      <Card v-else-if="serialNumber">
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Serial Number <span class="text-red-500">*</span>
                </label>
                <InputText
                  v-model="form.serial_number"
                  placeholder="Enter serial number"
                  class="w-full"
                  :class="{ 'p-invalid': errors.serial_number }"
                  required
                />
                <small v-if="errors.serial_number" class="p-error">{{ errors.serial_number[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Product <span class="text-red-500">*</span>
                </label>
                <Dropdown
                  v-model="form.product_id"
                  :options="products"
                  option-label="name"
                  option-value="id"
                  placeholder="Select Product"
                  class="w-full"
                  :class="{ 'p-invalid': errors.product_id }"
                  show-clear
                  filter
                  required
                  @change="onProductChange"
                />
                <small v-if="errors.product_id" class="p-error">{{ errors.product_id[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Batch</label>
                <Dropdown
                  v-model="form.batch_id"
                  :options="availableBatches"
                  option-label="batch_number"
                  option-value="id"
                  placeholder="Select Batch (Optional)"
                  class="w-full"
                  show-clear
                  filter
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Warehouse <span class="text-red-500">*</span>
                </label>
                <Dropdown
                  v-model="form.warehouse_id"
                  :options="warehouses"
                  option-label="name"
                  option-value="id"
                  placeholder="Select Warehouse"
                  class="w-full"
                  :class="{ 'p-invalid': errors.warehouse_id }"
                  show-clear
                  filter
                  required
                  @change="onWarehouseChange"
                />
                <small v-if="errors.warehouse_id" class="p-error">{{ errors.warehouse_id[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                <Dropdown
                  v-model="form.location_id"
                  :options="availableLocations"
                  option-label="name"
                  option-value="id"
                  placeholder="Select Location (Optional)"
                  class="w-full"
                  show-clear
                  filter
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Status <span class="text-red-500">*</span>
                </label>
                <Dropdown
                  v-model="form.status"
                  :options="statusOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Select Status"
                  class="w-full"
                  :class="{ 'p-invalid': errors.status }"
                  required
                />
                <small v-if="errors.status" class="p-error">{{ errors.status[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Condition <span class="text-red-500">*</span>
                </label>
                <Dropdown
                  v-model="form.condition"
                  :options="conditionOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Select Condition"
                  class="w-full"
                  :class="{ 'p-invalid': errors.condition }"
                  required
                />
                <small v-if="errors.condition" class="p-error">{{ errors.condition[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Date</label>
                <Calendar
                  v-model="form.purchase_date"
                  placeholder="Select purchase date"
                  class="w-full"
                  date-format="yy-mm-dd"
                  show-icon
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Purchase Price</label>
                <InputNumber
                  v-model="form.purchase_price"
                  placeholder="Enter purchase price"
                  class="w-full"
                  mode="currency"
                  currency="USD"
                  :min-fraction-digits="2"
                  :max-fraction-digits="2"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Warranty Expiry Date</label>
                <Calendar
                  v-model="form.warranty_expiry"
                  placeholder="Select warranty expiry date"
                  class="w-full"
                  date-format="yy-mm-dd"
                  show-icon
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Supplier</label>
                <InputText
                  v-model="form.supplier"
                  placeholder="Enter supplier name"
                  class="w-full"
                />
              </div>
            </div>

            <!-- Additional Information -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
              <Textarea
                v-model="form.notes"
                placeholder="Enter any additional notes"
                class="w-full"
                rows="3"
              />
            </div>

            <!-- Custom Fields -->
            <div v-if="customFields.length > 0">
              <h3 class="text-lg font-medium text-gray-800 mb-4">Custom Fields</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="field in customFields" :key="field.id">
                  <label class="block text-sm font-medium text-gray-700 mb-2">{{ field.name }}</label>
                  <InputText
                    v-if="field.type === 'text'"
                    v-model="form.custom_fields[field.id]"
                    :placeholder="`Enter ${field.name.toLowerCase()}`"
                    class="w-full"
                  />
                  <InputNumber
                    v-else-if="field.type === 'number'"
                    v-model="form.custom_fields[field.id]"
                    :placeholder="`Enter ${field.name.toLowerCase()}`"
                    class="w-full"
                  />
                  <Calendar
                    v-else-if="field.type === 'date'"
                    v-model="form.custom_fields[field.id]"
                    :placeholder="`Select ${field.name.toLowerCase()}`"
                    class="w-full"
                    date-format="yy-mm-dd"
                    show-icon
                  />
                  <Textarea
                    v-else-if="field.type === 'textarea'"
                    v-model="form.custom_fields[field.id]"
                    :placeholder="`Enter ${field.name.toLowerCase()}`"
                    class="w-full"
                    rows="3"
                  />
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-6 border-t">
              <Button
                label="Cancel"
                icon="pi pi-times"
                severity="secondary"
                outlined
                @click="goBack"
              />
              <Button
                label="Update Serial Number"
                icon="pi pi-check"
                type="submit"
                :loading="submitting"
              />
            </div>
          </form>
        </template>
      </Card>

      <!-- Not Found -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Serial Number Not Found</h3>
        <p class="text-gray-600 mb-4">The serial number you're trying to edit doesn't exist or has been deleted.</p>
        <Button label="Back to Serial Numbers" @click="goBack" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const submitting = ref(false)
const serialNumber = ref<any>(null)
const products = ref<any[]>([])
const warehouses = ref<any[]>([])
const availableBatches = ref<any[]>([])
const availableLocations = ref<any[]>([])
const customFields = ref<any[]>([])

const form = reactive({
  serial_number: '',
  product_id: null,
  batch_id: null,
  warehouse_id: null,
  location_id: null,
  status: 'available',
  condition: 'new',
  purchase_date: null,
  purchase_price: null,
  warranty_expiry: null,
  supplier: '',
  notes: '',
  custom_fields: {} as any
})

const errors = ref<any>({})

const statusOptions = [
  { label: 'Available', value: 'available' },
  { label: 'Sold', value: 'sold' },
  { label: 'Reserved', value: 'reserved' },
  { label: 'Damaged', value: 'damaged' },
  { label: 'Lost', value: 'lost' }
]

const conditionOptions = [
  { label: 'New', value: 'new' },
  { label: 'Good', value: 'good' },
  { label: 'Fair', value: 'fair' },
  { label: 'Poor', value: 'poor' },
  { label: 'Damaged', value: 'damaged' }
]

const toast = useToast()
const router = useRouter()
const route = useRoute()

const loadSerialNumber = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getSerialNumber(route.params.id as string)

    if (response.success) {
      serialNumber.value = response.data
      populateForm()
      loadFormData()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load serial number',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load serial number',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const populateForm = () => {
  if (!serialNumber.value) return

  form.serial_number = serialNumber.value.serial_number || ''
  form.product_id = serialNumber.value.product_id || null
  form.batch_id = serialNumber.value.batch_id || null
  form.warehouse_id = serialNumber.value.warehouse_id || null
  form.location_id = serialNumber.value.location_id || null
  form.status = serialNumber.value.status || 'available'
  form.condition = serialNumber.value.condition || 'new'
  form.purchase_date = serialNumber.value.purchase_date ? new Date(serialNumber.value.purchase_date) : null
  form.purchase_price = serialNumber.value.purchase_price || null
  form.warranty_expiry = serialNumber.value.warranty_expiry ? new Date(serialNumber.value.warranty_expiry) : null
  form.supplier = serialNumber.value.supplier || ''
  form.notes = serialNumber.value.notes || ''
  form.custom_fields = serialNumber.value.custom_fields || {}
}

const loadFormData = async () => {
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

    // Load available batches for the current product
    if (form.product_id) {
      await onProductChange()
    }

    // Load available locations for the current warehouse
    if (form.warehouse_id) {
      await onWarehouseChange()
    }
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load form data',
      life: 3000
    })
  }
}

const onProductChange = async () => {
  if (form.product_id) {
    try {
      const response = await inventoryService.getBatches({ product_id: form.product_id })
      if (response.success) {
        availableBatches.value = response.data || []
      }
    } catch (error) {
      // Ignore batch loading errors
    }
  } else {
    availableBatches.value = []
  }
}

const onWarehouseChange = async () => {
  if (form.warehouse_id) {
    try {
      const response = await inventoryService.getLocations({ warehouse_id: form.warehouse_id })
      if (response.success) {
        availableLocations.value = response.data || []
      }
    } catch (error) {
      // Ignore location loading errors
    }
  } else {
    availableLocations.value = []
  }
}

const submitForm = async () => {
  submitting.value = true
  errors.value = {}

  try {
    const response = await inventoryService.updateSerialNumber(route.params.id as string, form)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Serial number updated successfully',
        life: 3000
      })
      router.push({
        name: 'inventory.serial-numbers.show',
        params: { id: route.params.id }
      })
    } else {
      if (response.errors) {
        errors.value = response.errors
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: response.message || 'Failed to update serial number',
          life: 3000
        })
      }
    }
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to update serial number',
        life: 3000
      })
    }
  } finally {
    submitting.value = false
  }
}

const goBack = () => {
  router.push({
    name: 'inventory.serial-numbers.show',
    params: { id: route.params.id }
  })
}

onMounted(() => {
  loadSerialNumber()
})
</script>