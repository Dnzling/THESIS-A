<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center gap-4">
          <Button
            icon="pi pi-arrow-left"
            severity="secondary"
            text
            @click="goBack"
            v-tooltip.top="'Back to Warehouse'"
          />
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Warehouse</h1>
            <p class="text-gray-600 mt-1">Update warehouse information</p>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Form -->
      <Card v-else-if="warehouse">
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Warehouse Name <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.name"
                    placeholder="Enter warehouse name"
                    class="w-full"
                    :class="{ 'p-invalid': errors.name }"
                    required
                  />
                  <small v-if="errors.name" class="p-error">{{ errors.name[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Warehouse Code <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.code"
                    placeholder="Enter warehouse code"
                    class="w-full"
                    :class="{ 'p-invalid': errors.code }"
                    required
                  />
                  <small v-if="errors.code" class="p-error">{{ errors.code[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Type <span class="text-red-500">*</span>
                  </label>
                  <Select
                    v-model="form.type"
                    :options="typeOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select warehouse type"
                    class="w-full"
                    :class="{ 'p-invalid': errors.type }"
                    required
                  />
                  <small v-if="errors.type" class="p-error">{{ errors.type[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status <span class="text-red-500">*</span>
                  </label>
                  <Select
                    v-model="form.status"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select status"
                    class="w-full"
                    :class="{ 'p-invalid': errors.status }"
                    required
                  />
                  <small v-if="errors.status" class="p-error">{{ errors.status[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Capacity
                  </label>
                  <InputNumber
                    v-model="form.capacity"
                    placeholder="Enter capacity"
                    class="w-full"
                    :class="{ 'p-invalid': errors.capacity }"
                    :min="0"
                    suffix=" units"
                  />
                  <small v-if="errors.capacity" class="p-error">{{ errors.capacity[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Manager
                  </label>
                  <InputText
                    v-model="form.manager"
                    placeholder="Enter manager name"
                    class="w-full"
                    :class="{ 'p-invalid': errors.manager }"
                  />
                  <small v-if="errors.manager" class="p-error">{{ errors.manager[0] }}</small>
                </div>
              </div>
            </div>

            <!-- Address Information -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Address Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Address <span class="text-red-500">*</span>
                  </label>
                  <Textarea
                    v-model="form.address"
                    placeholder="Enter full address"
                    class="w-full"
                    rows="3"
                    :class="{ 'p-invalid': errors.address }"
                    required
                  />
                  <small v-if="errors.address" class="p-error">{{ errors.address[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    City <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.city"
                    placeholder="Enter city"
                    class="w-full"
                    :class="{ 'p-invalid': errors.city }"
                    required
                  />
                  <small v-if="errors.city" class="p-error">{{ errors.city[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    State/Province <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.state"
                    placeholder="Enter state/province"
                    class="w-full"
                    :class="{ 'p-invalid': errors.state }"
                    required
                  />
                  <small v-if="errors.state" class="p-error">{{ errors.state[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Postal Code <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.postal_code"
                    placeholder="Enter postal code"
                    class="w-full"
                    :class="{ 'p-invalid': errors.postal_code }"
                    required
                  />
                  <small v-if="errors.postal_code" class="p-error">{{ errors.postal_code[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Country <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.country"
                    placeholder="Enter country"
                    class="w-full"
                    :class="{ 'p-invalid': errors.country }"
                    required
                  />
                  <small v-if="errors.country" class="p-error">{{ errors.country[0] }}</small>
                </div>
              </div>
            </div>

            <!-- Contact Information -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Contact Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Phone
                  </label>
                  <InputText
                    v-model="form.phone"
                    placeholder="Enter phone number"
                    class="w-full"
                    :class="{ 'p-invalid': errors.phone }"
                  />
                  <small v-if="errors.phone" class="p-error">{{ errors.phone[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Email
                  </label>
                  <InputText
                    v-model="form.email"
                    type="email"
                    placeholder="Enter email address"
                    class="w-full"
                    :class="{ 'p-invalid': errors.email }"
                  />
                  <small v-if="errors.email" class="p-error">{{ errors.email[0] }}</small>
                </div>
              </div>
            </div>

            <!-- Additional Information -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Additional Information</h3>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Description
                </label>
                <Textarea
                  v-model="form.description"
                  placeholder="Enter warehouse description"
                  class="w-full"
                  rows="4"
                  :class="{ 'p-invalid': errors.description }"
                />
                <small v-if="errors.description" class="p-error">{{ errors.description[0] }}</small>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t">
              <Button
                label="Cancel"
                severity="secondary"
                @click="goBack"
                type="button"
              />
              <Button
                label="Update Warehouse"
                type="submit"
                :loading="saving"
                class="bg-blue-600 hover:bg-blue-700"
              />
            </div>
          </form>
        </template>
      </Card>

      <!-- Not Found -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Warehouse Not Found</h3>
        <p class="text-gray-600 mb-4">The warehouse you're looking for doesn't exist or has been deleted.</p>
        <Button label="Back to Warehouses" @click="goBack" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const saving = ref(false)
const errors = ref<any>({})
const warehouse = ref<any>(null)
const toast = useToast()
const router = useRouter()
const route = useRoute()

const form = reactive({
  name: '',
  code: '',
  type: '',
  status: '',
  capacity: null as number | null,
  manager: '',
  address: '',
  city: '',
  state: '',
  postal_code: '',
  country: '',
  phone: '',
  email: '',
  description: ''
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const typeOptions = [
  { label: 'Main', value: 'main' },
  { label: 'Branch', value: 'branch' },
  { label: 'Distribution', value: 'distribution' },
  { label: 'Storage', value: 'storage' },
  { label: 'Retail', value: 'retail' }
]

const loadWarehouse = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getWarehouse(route.params.id as string)

    if (response.success) {
      warehouse.value = response.data
      populateForm()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load warehouse',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load warehouse',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const populateForm = () => {
  if (!warehouse.value) return

  form.name = warehouse.value.name || ''
  form.code = warehouse.value.code || ''
  form.type = warehouse.value.type || ''
  form.status = warehouse.value.status || ''
  form.capacity = warehouse.value.capacity || null
  form.manager = warehouse.value.manager || ''
  form.address = warehouse.value.address || ''
  form.city = warehouse.value.city || ''
  form.state = warehouse.value.state || ''
  form.postal_code = warehouse.value.postal_code || ''
  form.country = warehouse.value.country || ''
  form.phone = warehouse.value.phone || ''
  form.email = warehouse.value.email || ''
  form.description = warehouse.value.description || ''
}

const goBack = () => {
  router.push({ name: 'inventory.warehouses.detail', params: { id: route.params.id } })
}

const submitForm = async () => {
  saving.value = true
  errors.value = {}

  try {
    const response = await inventoryService.updateWarehouse(route.params.id as string, form)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Warehouse updated successfully',
        life: 3000
      })
      router.push({ name: 'inventory.warehouses.detail', params: { id: route.params.id } })
    } else {
      if (response.errors) {
        errors.value = response.errors
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: response.message || 'Failed to update warehouse',
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
        detail: error.response?.data?.message || 'Failed to update warehouse',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadWarehouse()
})
</script>