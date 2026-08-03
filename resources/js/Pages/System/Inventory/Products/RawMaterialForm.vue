<template>
  <div class="max-w-4xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded @click="goBack" />
        <div>
          <h1 class="text-2xl font-bold text-gray-800">Create Raw Material</h1>
          <p class="text-sm text-gray-500">Inventory raw material entry</p>
        </div>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="grid grid-cols-1 md:grid-cols-2 gap-4" @submit.prevent="handleSubmit">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 mb-1">Item Name <span class="text-red-500">*</span></label>
            <InputText v-model="form.product_name" class="w-full" placeholder="e.g. Marine Plywood 18mm" />
            <small v-if="errors.product_name" class="text-red-500">{{ errors.product_name }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Unit of Measurement</label>
            <Select v-model="form.unit_of_measurement" :options="unitOptions" class="w-full" placeholder="Select unit" />
            <small v-if="errors.unit_of_measurement" class="text-red-500">{{ errors.unit_of_measurement }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost <span class="text-red-500">*</span></label>
            <InputNumber v-model="form.unit_cost" mode="currency" currency="PHP" locale="en-PH" :min="0" class="w-full" fluid />
            <small v-if="errors.unit_cost" class="text-red-500">{{ errors.unit_cost }}</small>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Supplier (optional)</label>
            <InputText v-model="form.supplier_name" class="w-full" placeholder="Supplier name" />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Initial Stock (optional)</label>
            <InputNumber v-model="form.initial_stock" :min="0" class="w-full" fluid />
          </div>

          <div class="md:col-span-2 flex justify-end gap-2 pt-2">
            <Button label="Cancel" severity="secondary" outlined @click="goBack" />
            <Button type="submit" label="Save Raw Material" icon="pi pi-check" :loading="submitting" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const router = useRouter()
const toast = useToast()

const submitting = ref(false)
const errors = ref<Record<string, string>>({})
const unitOptions = ['sheet', 'box', 'piece', 'kg', 'liter', 'pack']

const form = reactive({
  product_name: '',
  unit_of_measurement: null as string | null,
  unit_cost: null as number | null,
  supplier_name: '',
  initial_stock: null as number | null,
})

const validate = () => {
  errors.value = {}
  if (!form.product_name) errors.value.product_name = 'Item name is required'
  if (!form.unit_cost || form.unit_cost <= 0) errors.value.unit_cost = 'Unit cost is required'
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async () => {
  if (!validate()) return

  submitting.value = true
  try {
    await inventoryService.createProduct({
      product_name: form.product_name,
      product_type: 'raw_material',
      unit_of_measurement: form.unit_of_measurement || null,
      unit_cost: form.unit_cost ?? 0,
      supplier_name: form.supplier_name || null,
      initial_stock: form.initial_stock ?? null,
      base_price: form.unit_cost ?? 0,
      cost_price: form.unit_cost ?? 0,
    })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Raw material created successfully', life: 3000 })
    goBack()
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data?.errors || {}
    }
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save raw material', life: 4000 })
  } finally {
    submitting.value = false
  }
}

const goBack = () => router.push({ name: 'inventory.products.index' })
</script>
