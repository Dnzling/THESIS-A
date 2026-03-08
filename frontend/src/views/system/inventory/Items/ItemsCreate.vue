<template>
  <div class="max-w-4xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.items' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Add Inventory Item</h2>
        <p class="text-sm text-gray-500 mt-1">Create a new inventory record for a branch</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Product <span class="text-red-500">*</span>
              </label>
              <InputText
                v-model="form.product_id"
                placeholder="Product ID"
                :class="{ 'p-invalid': errors.product_id }"
              />
              <small v-if="errors.product_id" class="text-red-500">{{ errors.product_id }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Variation ID</label>
              <InputText v-model="form.variation_id" placeholder="Variation ID (optional)" />
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Quantity On Hand <span class="text-red-500">*</span>
              </label>
              <InputNumber
                v-model="form.quantity_on_hand"
                :min="0"
                showButtons
                :class="{ 'p-invalid': errors.quantity_on_hand }"
              />
              <small v-if="errors.quantity_on_hand" class="text-red-500">{{ errors.quantity_on_hand }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Reorder Point</label>
              <InputNumber v-model="form.reorder_point" :min="0" showButtons />
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Reorder Quantity</label>
              <InputNumber v-model="form.reorder_quantity" :min="0" showButtons />
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Maximum Stock</label>
              <InputNumber v-model="form.maximum_stock" :min="0" showButtons />
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Safety Stock</label>
              <InputNumber v-model="form.safety_stock" :min="0" showButtons />
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Unit Cost</label>
              <InputNumber v-model="form.unit_cost" :min="0" :minFractionDigits="2" :maxFractionDigits="2" mode="currency" currency="PHP" locale="en-PH" />
            </div>
          </div>

          <Divider><span class="text-sm font-semibold text-gray-600">Location</span></Divider>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Warehouse Section</label>
              <InputText v-model="form.warehouse_section" placeholder="e.g. A" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Aisle</label>
              <InputText v-model="form.aisle" placeholder="e.g. 01" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Rack</label>
              <InputText v-model="form.rack" placeholder="e.g. R1" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Shelf</label>
              <InputText v-model="form.shelf" placeholder="e.g. S2" />
            </div>
          </div>

          <div class="flex flex-col gap-2 max-w-xs">
            <label class="text-sm font-semibold text-gray-700">Bin Code</label>
            <InputText v-model="form.bin_code" placeholder="e.g. BIN-001" />
          </div>

          <div class="pt-4 flex gap-2 justify-end border-t border-gray-200">
            <Button label="Cancel" severity="secondary" outlined type="button" @click="router.push({ name: 'inventory.items' })" />
            <Button label="Save Item" icon="pi pi-check" :loading="saving" type="submit" />
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
const saving = ref(false)

const form = reactive({
  product_id: '' as string,
  variation_id: '' as string,
  quantity_on_hand: 0,
  reorder_point: 0,
  reorder_quantity: 0,
  maximum_stock: 0,
  safety_stock: 0,
  unit_cost: null as number | null,
  warehouse_section: '',
  aisle: '',
  rack: '',
  shelf: '',
  bin_code: ''
})

const errors = ref<Record<string, string>>({})

const validateForm = () => {
  errors.value = {}
  if (!form.product_id) errors.value.product_id = 'Product ID is required'
  if (form.quantity_on_hand < 0) errors.value.quantity_on_hand = 'Quantity must be 0 or more'
  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  saving.value = true
  try {
    const payload: Record<string, any> = {
      product_id: Number(form.product_id),
      quantity_on_hand: form.quantity_on_hand,
      reorder_point: form.reorder_point,
      reorder_quantity: form.reorder_quantity,
      maximum_stock: form.maximum_stock,
      safety_stock: form.safety_stock
    }

    if (form.variation_id) payload.variation_id = Number(form.variation_id)
    if (form.unit_cost !== null) payload.unit_cost = form.unit_cost
    if (form.warehouse_section) payload.warehouse_section = form.warehouse_section
    if (form.aisle) payload.aisle = form.aisle
    if (form.rack) payload.rack = form.rack
    if (form.shelf) payload.shelf = form.shelf
    if (form.bin_code) payload.bin_code = form.bin_code

    await inventoryService.createInventoryItem(payload)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Inventory item created successfully',
      life: 3000
    })
    router.push({ name: 'inventory.items' })
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = Object.fromEntries(
        Object.entries(error.response.data.errors).map(([k, v]) => [k, (v as string[]).join(', ')])
      )
    }
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to create item',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}
</script>
