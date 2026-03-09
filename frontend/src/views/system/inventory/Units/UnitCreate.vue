<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Create Unit</h1>
        <p class="text-gray-600 mt-1">Add a new measurement unit</p>
      </div>

      <Card>
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Unit Name *</label>
                <InputText
                  v-model="form.name"
                  placeholder="Enter unit name (e.g., Kilogram, Liter)"
                  class="w-full"
                  :class="{ 'p-invalid': errors.name }"
                />
                <small v-if="errors.name" class="p-error">{{ errors.name[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Abbreviation *</label>
                <InputText
                  v-model="form.abbreviation"
                  placeholder="Enter abbreviation (e.g., kg, L)"
                  class="w-full"
                  :class="{ 'p-invalid': errors.abbreviation }"
                />
                <small v-if="errors.abbreviation" class="p-error">{{ errors.abbreviation[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                <Select
                  v-model="form.type"
                  :options="typeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select unit type"
                  class="w-full"
                  :class="{ 'p-invalid': errors.type }"
                />
                <small v-if="errors.type" class="p-error">{{ errors.type[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Conversion Factor</label>
                <InputNumber
                  v-model="form.conversion_factor"
                  mode="decimal"
                  :minFractionDigits="1"
                  :maxFractionDigits="6"
                  placeholder="1.0"
                  class="w-full"
                  :class="{ 'p-invalid': errors.conversion_factor }"
                />
                <small v-if="errors.conversion_factor" class="p-error">{{ errors.conversion_factor[0] }}</small>
                <small class="text-gray-500 mt-1">Factor to convert to base unit (optional)</small>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <Textarea
                  v-model="form.description"
                  placeholder="Enter unit description"
                  class="w-full"
                  rows="3"
                  :class="{ 'p-invalid': errors.description }"
                />
                <small v-if="errors.description" class="p-error">{{ errors.description[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                <Select
                  v-model="form.status"
                  :options="statusOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select status"
                  class="w-full"
                  :class="{ 'p-invalid': errors.status }"
                />
                <small v-if="errors.status" class="p-error">{{ errors.status[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                <InputNumber
                  v-model="form.display_order"
                  placeholder="0"
                  class="w-full"
                  :class="{ 'p-invalid': errors.display_order }"
                />
                <small v-if="errors.display_order" class="p-error">{{ errors.display_order[0] }}</small>
                <small class="text-gray-500 mt-1">Lower numbers appear first</small>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t">
              <Button
                type="button"
                label="Cancel"
                severity="secondary"
                @click="goBack"
                :disabled="loading"
              />
              <Button
                type="submit"
                label="Create Unit"
                :loading="loading"
                class="bg-blue-600 hover:bg-blue-700"
              />
            </div>
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const errors = ref<any>({})
const toast = useToast()
const router = useRouter()

const form = reactive({
  name: '',
  abbreviation: '',
  type: '',
  conversion_factor: null as number | null,
  description: '',
  status: 'active',
  display_order: 0
})

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const typeOptions = [
  { label: 'Weight', value: 'weight' },
  { label: 'Volume', value: 'volume' },
  { label: 'Length', value: 'length' },
  { label: 'Area', value: 'area' },
  { label: 'Count', value: 'count' },
  { label: 'Other', value: 'other' }
]

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  try {
    const response = await inventoryService.createUnit(form)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Unit created successfully',
        life: 3000
      })
      router.push({ name: 'inventory.units' })
    } else {
      errors.value = response.errors || {}
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Please check the form for errors',
        life: 3000
      })
    }
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to create unit',
        life: 3000
      })
    }
  } finally {
    loading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.units' })
}
</script>