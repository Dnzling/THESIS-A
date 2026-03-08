<template>
  <div class="max-w-4xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.items' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Edit Inventory Item</h2>
        <p class="text-sm text-gray-500 mt-1">Update inventory record details</p>
      </div>
    </div>

    <div v-if="loading">
      <Skeleton height="400px" class="rounded-lg" />
    </div>

    <Card v-else>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <!-- Product Info (read-only) -->
          <div class="bg-blue-50 p-4 rounded-lg" v-if="item">
            <div class="flex items-center gap-3">
              <i class="pi pi-box text-blue-600 text-xl"></i>
              <div>
                <p class="font-semibold text-gray-900">{{ item.product?.product_name || 'Unknown Product' }}</p>
                <p class="text-sm text-gray-500">SKU: {{ item.product?.sku || 'N/A' }} | Branch: {{ item.branch?.name || 'N/A' }}</p>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
              <label class="text-sm font-semibold text-gray-700">Quantity Damaged</label>
              <InputNumber v-model="form.quantity_damaged" :min="0" showButtons />
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
            <Button label="Save Changes" icon="pi pi-check" :loading="saving" type="submit" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const saving = ref(false)
const item = ref<any>(null)
const itemId = computed(() => Number(route.params.id))

const form = reactive({
  quantity_on_hand: 0,
  quantity_damaged: 0,
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

const loadItem = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getInventoryItem(itemId.value)
    item.value = response.data?.data || response.data || null

    if (item.value) {
      form.quantity_on_hand = item.value.quantity_on_hand ?? 0
      form.quantity_damaged = item.value.quantity_damaged ?? 0
      form.reorder_point = item.value.reorder_point ?? 0
      form.reorder_quantity = item.value.reorder_quantity ?? 0
      form.maximum_stock = item.value.maximum_stock ?? 0
      form.safety_stock = item.value.safety_stock ?? 0
      form.unit_cost = item.value.unit_cost ?? null
      form.warehouse_section = item.value.warehouse_section ?? ''
      form.aisle = item.value.aisle ?? ''
      form.rack = item.value.rack ?? ''
      form.shelf = item.value.shelf ?? ''
      form.bin_code = item.value.bin_code ?? ''
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load item',
      life: 3000
    })
    router.push({ name: 'inventory.items' })
  } finally {
    loading.value = false
  }
}

const validateForm = () => {
  errors.value = {}
  if (form.quantity_on_hand < 0) errors.value.quantity_on_hand = 'Quantity must be 0 or more'
  return Object.keys(errors.value).length === 0
}

const submitForm = async () => {
  if (!validateForm()) return

  saving.value = true
  try {
    const payload: Record<string, any> = {
      quantity_on_hand: form.quantity_on_hand,
      quantity_damaged: form.quantity_damaged,
      reorder_point: form.reorder_point,
      reorder_quantity: form.reorder_quantity,
      maximum_stock: form.maximum_stock,
      safety_stock: form.safety_stock
    }

    if (form.unit_cost !== null) payload.unit_cost = form.unit_cost
    if (form.warehouse_section) payload.warehouse_section = form.warehouse_section
    if (form.aisle) payload.aisle = form.aisle
    if (form.rack) payload.rack = form.rack
    if (form.shelf) payload.shelf = form.shelf
    if (form.bin_code) payload.bin_code = form.bin_code

    await inventoryService.updateInventoryItem(itemId.value, payload)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Inventory item updated successfully',
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
      detail: error.response?.data?.message || 'Failed to update item',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadItem()
})
</script>
