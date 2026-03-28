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
            v-tooltip.top="'Back to Location'"
          />
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Location</h1>
            <p class="text-gray-600 mt-1">Update location information</p>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Form -->
      <Card v-else-if="location">
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Location Name <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.name"
                    placeholder="Enter location name"
                    class="w-full"
                    :class="{ 'p-invalid': errors.name }"
                    required
                  />
                  <small v-if="errors.name" class="p-error">{{ errors.name[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Location Code <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.code"
                    placeholder="Enter location code"
                    class="w-full"
                    :class="{ 'p-invalid': errors.code }"
                    required
                  />
                  <small v-if="errors.code" class="p-error">{{ errors.code[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Warehouse <span class="text-red-500">*</span>
                  </label>
                  <Select
                    v-model="form.warehouse_id"
                    :options="warehouses"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select warehouse"
                    class="w-full"
                    :class="{ 'p-invalid': errors.warehouse_id }"
                    :loading="warehousesLoading"
                    required
                  />
                  <small v-if="errors.warehouse_id" class="p-error">{{ errors.warehouse_id[0] }}</small>
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
                    placeholder="Select location type"
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
              </div>
            </div>

            <!-- Location Details -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Location Details</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Aisle
                  </label>
                  <InputText
                    v-model="form.aisle"
                    placeholder="Enter aisle"
                    class="w-full"
                    :class="{ 'p-invalid': errors.aisle }"
                  />
                  <small v-if="errors.aisle" class="p-error">{{ errors.aisle[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Rack
                  </label>
                  <InputText
                    v-model="form.rack"
                    placeholder="Enter rack"
                    class="w-full"
                    :class="{ 'p-invalid': errors.rack }"
                  />
                  <small v-if="errors.rack" class="p-error">{{ errors.rack[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Shelf
                  </label>
                  <InputText
                    v-model="form.shelf"
                    placeholder="Enter shelf"
                    class="w-full"
                    :class="{ 'p-invalid': errors.shelf }"
                  />
                  <small v-if="errors.shelf" class="p-error">{{ errors.shelf[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Bin
                  </label>
                  <InputText
                    v-model="form.bin"
                    placeholder="Enter bin"
                    class="w-full"
                    :class="{ 'p-invalid': errors.bin }"
                  />
                  <small v-if="errors.bin" class="p-error">{{ errors.bin[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Level
                  </label>
                  <InputNumber
                    v-model="form.level"
                    placeholder="Enter level"
                    class="w-full"
                    :class="{ 'p-invalid': errors.level }"
                    :min="0"
                  />
                  <small v-if="errors.level" class="p-error">{{ errors.level[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Position
                  </label>
                  <InputText
                    v-model="form.position"
                    placeholder="Enter position"
                    class="w-full"
                    :class="{ 'p-invalid': errors.position }"
                  />
                  <small v-if="errors.position" class="p-error">{{ errors.position[0] }}</small>
                </div>
              </div>
            </div>

            <!-- Dimensions -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Dimensions</h3>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Length
                  </label>
                  <InputNumber
                    v-model="form.length"
                    placeholder="Enter length"
                    class="w-full"
                    :class="{ 'p-invalid': errors.length }"
                    :min="0"
                    suffix=" cm"
                    :maxFractionDigits="2"
                  />
                  <small v-if="errors.length" class="p-error">{{ errors.length[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Width
                  </label>
                  <InputNumber
                    v-model="form.width"
                    placeholder="Enter width"
                    class="w-full"
                    :class="{ 'p-invalid': errors.width }"
                    :min="0"
                    suffix=" cm"
                    :maxFractionDigits="2"
                  />
                  <small v-if="errors.width" class="p-error">{{ errors.width[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Height
                  </label>
                  <InputNumber
                    v-model="form.height"
                    placeholder="Enter height"
                    class="w-full"
                    :class="{ 'p-invalid': errors.height }"
                    :min="0"
                    suffix=" cm"
                    :maxFractionDigits="2"
                  />
                  <small v-if="errors.height" class="p-error">{{ errors.height[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Weight Limit
                  </label>
                  <InputNumber
                    v-model="form.weight_limit"
                    placeholder="Enter weight limit"
                    class="w-full"
                    :class="{ 'p-invalid': errors.weight_limit }"
                    :min="0"
                    suffix=" kg"
                    :maxFractionDigits="2"
                  />
                  <small v-if="errors.weight_limit" class="p-error">{{ errors.weight_limit[0] }}</small>
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
                  placeholder="Enter location description"
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
                label="Update Location"
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
        <h3 class="text-lg font-medium text-gray-900 mb-2">Location Not Found</h3>
        <p class="text-gray-600 mb-4">The location you're looking for doesn't exist or has been deleted.</p>
        <Button label="Back to Locations" @click="goBack" />
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
const warehousesLoading = ref(false)
const errors = ref<any>({})
const location = ref<any>(null)
const warehouses = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const form = reactive({
  name: '',
  code: '',
  warehouse_id: '',
  type: '',
  status: '',
  capacity: null as number | null,
  aisle: '',
  rack: '',
  shelf: '',
  bin: '',
  level: null as number | null,
  position: '',
  length: null as number | null,
  width: null as number | null,
  height: null as number | null,
  weight_limit: null as number | null,
  description: ''
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const typeOptions = [
  { label: 'Rack', value: 'rack' },
  { label: 'Shelf', value: 'shelf' },
  { label: 'Bin', value: 'bin' },
  { label: 'Floor', value: 'floor' },
  { label: 'Cold Storage', value: 'cold_storage' },
  { label: 'Secure', value: 'secure' }
]

const loadLocation = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getLocation(route.params.id as string)

    if (response.success) {
      location.value = response.data
      populateForm()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load location',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load location',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadWarehouses = async () => {
  warehousesLoading.value = true
  try {
    const response = await inventoryService.getWarehouses({ per_page: 1000 })

    if (response.success) {
      warehouses.value = response.data || []
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load warehouses',
      life: 3000
    })
  } finally {
    warehousesLoading.value = false
  }
}

const populateForm = () => {
  if (!location.value) return

  form.name = location.value.name || ''
  form.code = location.value.code || ''
  form.warehouse_id = location.value.warehouse_id || ''
  form.type = location.value.type || ''
  form.status = location.value.status || ''
  form.capacity = location.value.capacity || null
  form.aisle = location.value.aisle || ''
  form.rack = location.value.rack || ''
  form.shelf = location.value.shelf || ''
  form.bin = location.value.bin || ''
  form.level = location.value.level || null
  form.position = location.value.position || ''
  form.length = location.value.length || null
  form.width = location.value.width || null
  form.height = location.value.height || null
  form.weight_limit = location.value.weight_limit || null
  form.description = location.value.description || ''
}

const goBack = () => {
  router.push({ name: 'inventory.locations.detail', params: { id: route.params.id } })
}

const submitForm = async () => {
  saving.value = true
  errors.value = {}

  try {
    const response = await inventoryService.updateLocation(route.params.id as string, form)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Location updated successfully',
        life: 3000
      })
      router.push({ name: 'inventory.locations.detail', params: { id: route.params.id } })
    } else {
      if (response.errors) {
        errors.value = response.errors
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: response.message || 'Failed to update location',
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
        detail: error.response?.data?.message || 'Failed to update location',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadWarehouses()
  loadLocation()
})
</script>