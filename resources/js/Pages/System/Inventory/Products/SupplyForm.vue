<template>
  <div class="p-6 max-w-3xl mx-auto">
    <Card>
      <template #title>
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-2xl font-semibold">{{ isEdit ? 'Edit Supply' : 'Add Supply' }}</h2>
            <p class="text-sm text-gray-500 mt-1">Add simple stockable supplies without the extra product fields.</p>
          </div>
          <Button label="Back" icon="pi pi-arrow-left" severity="secondary" text @click="goBack" />
        </div>
      </template>
      <template #content>
        <form @submit.prevent="submitForm" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
              <label class="block text-sm font-medium mb-1">Item Name</label>
              <InputText v-model="form.product_name" class="w-full" required />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Unit of Measurement</label>
              <Select v-model="form.unit_of_measurement" :options="unitOptions" class="w-full" placeholder="Select unit" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Unit Cost</label>
              <InputNumber v-model="form.unit_cost" mode="currency" currency="PHP" locale="en-PH" class="w-full" required />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Supplier (optional)</label>
              <InputText v-model="form.supplier_name" class="w-full" placeholder="Supplier name" />
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Initial Stock (optional)</label>
              <InputNumber v-model="form.initial_stock" :min="0" class="w-full" />
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <Button label="Cancel" severity="secondary" text @click="goBack" />
            <Button :label="isEdit ? 'Update Supply' : 'Create Supply'" type="submit" :loading="saving" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const isEdit = computed(() => Boolean(route.params.id))
const saving = ref(false)
const unitOptions = ['ream', 'box', 'piece', 'kg', 'liter']

const form = reactive({
  product_name: '',
  unit_of_measurement: null as string | null,
  unit_cost: 0,
  supplier_name: '',
  initial_stock: null as number | null,
})

const loadSupply = async (id: string | string[]) => {
  try {
    const response = await inventoryService.getSupply(Number(id))
    const data = response.data
    form.product_name = data.product_name || ''
    form.unit_of_measurement = data.unit_of_measurement || null
    form.unit_cost = Number(data.cost_price ?? data.base_price ?? 0)
    form.supplier_name = data.supplier_name || ''
    form.initial_stock = data.initial_stock != null ? Number(data.initial_stock) : null
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load supply', life: 3000 })
  }
}

const submitForm = async () => {
  saving.value = true
  try {
    const payload: {
      product_name: string
      unit_of_measurement: string | null
      unit_cost: number
      supplier_name: string | null
      initial_stock: number | null
      product_type: 'supply' | 'raw_material' | 'finished_good'
      sku?: undefined
      base_price: number
      cost_price: number
      is_active: boolean
    } = {
      product_name: form.product_name,
      unit_of_measurement: form.unit_of_measurement,
      unit_cost: form.unit_cost,
      supplier_name: form.supplier_name || null,
      initial_stock: form.initial_stock ?? null,
      product_type: 'supply',
      sku: undefined,
      base_price: form.unit_cost,
      cost_price: form.unit_cost,
      is_active: true,
    }

    if (isEdit.value) {
      await inventoryService.updateSupply(Number(route.params.id), payload)
      toast.add({ severity: 'success', summary: 'Success', detail: 'Supply updated', life: 3000 })
    } else {
      await inventoryService.createSupply(payload)
      toast.add({ severity: 'success', summary: 'Success', detail: 'Supply created', life: 3000 })
    }
    goBack()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save supply', life: 3000 })
  } finally {
    saving.value = false
  }
}

const goBack = () => router.push({ name: 'inventory.products.index' })

onMounted(async () => {
  if (isEdit.value) {
    await loadSupply(route.params.id)
  }
})
</script>
