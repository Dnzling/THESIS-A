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
              v-tooltip.top="'Back to Serial Numbers'"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-800">Add Serial Number</h1>
              <p class="text-gray-600 mt-1">Create a new serial number for product tracking</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Form -->
      <Card>
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
                label="Create Serial Number"
                icon="pi pi-check"
                type="submit"
                :loading="submitting"
              />
            </div>
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const submitting = ref(false)
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
    const response = await inventoryService.createSerialNumber(form)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Serial number created successfully',
        life: 3000
      })
      router.push({ name: 'inventory.serial-numbers.index' })
    } else {
      if (response.errors) {
        errors.value = response.errors
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: response.message || 'Failed to create serial number',
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
        detail: error.response?.data?.message || 'Failed to create serial number',
        life: 3000
      })
    }
  } finally {
    submitting.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.serial-numbers.index' })
}

onMounted(() => {
  loadFormData()
})
</script>