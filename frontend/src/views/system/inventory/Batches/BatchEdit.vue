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
              v-tooltip.top="'Back to Batch'"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-800">Edit Batch</h1>
              <p class="text-gray-600 mt-1">Update batch information and tracking details</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Form -->
      <Card v-else-if="batch">
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Basic Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Batch Number <span class="text-red-500">*</span>
                </label>
                <InputText
                  v-model="form.batch_number"
                  placeholder="Enter batch number"
                  class="w-full"
                  :class="{ 'p-invalid': errors.batch_number }"
                  required
                />
                <small v-if="errors.batch_number" class="p-error">{{ errors.batch_number[0] }}</small>
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
                />
                <small v-if="errors.product_id" class="p-error">{{ errors.product_id[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Quantity <span class="text-red-500">*</span>
                </label>
                <InputNumber
                  v-model="form.quantity"
                  placeholder="Enter batch quantity"
                  class="w-full"
                  :class="{ 'p-invalid': errors.quantity }"
                  :min="0"
                  required
                />
                <small v-if="errors.quantity" class="p-error">{{ errors.quantity[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Unit</label>
                <Dropdown
                  v-model="form.unit_id"
                  :options="units"
                  option-label="name"
                  option-value="id"
                  placeholder="Select Unit"
                  class="w-full"
                  show-clear
                  filter
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Manufacture Date <span class="text-red-500">*</span>
                </label>
                <Calendar
                  v-model="form.manufacture_date"
                  placeholder="Select manufacture date"
                  class="w-full"
                  :class="{ 'p-invalid': errors.manufacture_date }"
                  date-format="yy-mm-dd"
                  show-icon
                  required
                />
                <small v-if="errors.manufacture_date" class="p-error">{{ errors.manufacture_date[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                <Calendar
                  v-model="form.expiry_date"
                  placeholder="Select expiry date"
                  class="w-full"
                  date-format="yy-mm-dd"
                  show-icon
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cost Price</label>
                <InputNumber
                  v-model="form.cost_price"
                  placeholder="Enter cost price per unit"
                  class="w-full"
                  mode="currency"
                  currency="USD"
                  :min-fraction-digits="2"
                  :max-fraction-digits="2"
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Storage Conditions</label>
                <InputText
                  v-model="form.storage_conditions"
                  placeholder="Enter storage conditions"
                  class="w-full"
                />
              </div>
            </div>

            <!-- Quality Control -->
            <div>
              <h3 class="text-lg font-medium text-gray-800 mb-4">Quality Control</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Quality Check Date</label>
                  <Calendar
                    v-model="form.quality_check_date"
                    placeholder="Select quality check date"
                    class="w-full"
                    date-format="yy-mm-dd"
                    show-icon
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Quality Status</label>
                  <Dropdown
                    v-model="form.quality_status"
                    :options="qualityStatusOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Select Quality Status"
                    class="w-full"
                    show-clear
                  />
                </div>

                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Quality Notes</label>
                  <Textarea
                    v-model="form.quality_notes"
                    placeholder="Enter quality control notes"
                    class="w-full"
                    rows="3"
                  />
                </div>
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
                label="Update Batch"
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
        <h3 class="text-lg font-medium text-gray-900 mb-2">Batch Not Found</h3>
        <p class="text-gray-600 mb-4">The batch you're trying to edit doesn't exist or has been deleted.</p>
        <Button label="Back to Batches" @click="goBack" />
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
const batch = ref<any>(null)
const products = ref<any[]>([])
const units = ref<any[]>([])
const customFields = ref<any[]>([])

const form = reactive({
  batch_number: '',
  product_id: null,
  quantity: null,
  unit_id: null,
  manufacture_date: null,
  expiry_date: null,
  cost_price: null,
  supplier: '',
  status: 'active',
  storage_conditions: '',
  quality_check_date: null,
  quality_status: null,
  quality_notes: '',
  notes: '',
  custom_fields: {} as any
})

const errors = ref<any>({})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Expired', value: 'expired' },
  { label: 'Recalled', value: 'recalled' },
  { label: 'Quarantined', value: 'quarantined' }
]

const qualityStatusOptions = [
  { label: 'Passed', value: 'passed' },
  { label: 'Failed', value: 'failed' },
  { label: 'Pending', value: 'pending' },
  { label: 'Not Required', value: 'not_required' }
]

const toast = useToast()
const router = useRouter()
const route = useRoute()

const loadBatch = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getBatch(route.params.id as string)

    if (response.success) {
      batch.value = response.data
      populateForm()
      loadFormData()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load batch',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load batch',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const populateForm = () => {
  if (!batch.value) return

  form.batch_number = batch.value.batch_number || ''
  form.product_id = batch.value.product_id || null
  form.quantity = batch.value.quantity || null
  form.unit_id = batch.value.unit_id || null
  form.manufacture_date = batch.value.manufacture_date ? new Date(batch.value.manufacture_date) : null
  form.expiry_date = batch.value.expiry_date ? new Date(batch.value.expiry_date) : null
  form.cost_price = batch.value.cost_price || null
  form.supplier = batch.value.supplier || ''
  form.status = batch.value.status || 'active'
  form.storage_conditions = batch.value.storage_conditions || ''
  form.quality_check_date = batch.value.quality_check_date ? new Date(batch.value.quality_check_date) : null
  form.quality_status = batch.value.quality_status || null
  form.quality_notes = batch.value.quality_notes || ''
  form.notes = batch.value.notes || ''
  form.custom_fields = batch.value.custom_fields || {}
}

const loadFormData = async () => {
  try {
    const [productsResponse, unitsResponse] = await Promise.all([
      inventoryService.getProducts({ per_page: 1000 }),
      inventoryService.getUnits({ per_page: 1000 })
    ])

    if (productsResponse.success) {
      products.value = productsResponse.data || []
    }

    if (unitsResponse.success) {
      units.value = unitsResponse.data || []
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

const submitForm = async () => {
  submitting.value = true
  errors.value = {}

  try {
    const response = await inventoryService.updateBatch(route.params.id as string, form)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Batch updated successfully',
        life: 3000
      })
      router.push({
        name: 'inventory.batches.show',
        params: { id: route.params.id }
      })
    } else {
      if (response.errors) {
        errors.value = response.errors
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: response.message || 'Failed to update batch',
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
        detail: error.response?.data?.message || 'Failed to update batch',
        life: 3000
      })
    }
  } finally {
    submitting.value = false
  }
}

const goBack = () => {
  router.push({
    name: 'inventory.batches.show',
    params: { id: route.params.id }
  })
}

onMounted(() => {
  loadBatch()
})
</script>