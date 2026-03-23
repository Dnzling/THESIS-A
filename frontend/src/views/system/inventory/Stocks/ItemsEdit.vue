<template>
  <div class="max-w-4xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.items' })" />
      <div>
        <h2 class="text-lg font-bold text-gray-800">Edit Inventory Item</h2>
      </div>
    </div>

    <div v-if="loadingItem" class="flex justify-center py-12">
      <ProgressSpinner />
    </div>

    <Card v-else>
      <template #content>
        <!-- Product info (read-only) -->
        <div class="bg-gray-50 rounded-lg p-4 mb-6 flex gap-4 items-center">
          <i class="pi pi-box text-2xl text-blue-500"></i>
          <div>
            <p class="font-semibold text-gray-800">{{ item?.product?.product_name || 'Unknown Product' }}</p>
            <p class="text-sm text-gray-500">SKU: {{ item?.product?.sku || 'N/A' }}</p>
          </div>
          <div class="ml-auto">
            <Tag :value="getStockLabel(item?.stock_status)" :severity="getStockSeverity(item?.stock_status)" />
          </div>
        </div>

        <form class="space-y-6" @submit.prevent="submitForm">
          <!-- Quantities -->
          <Message severity="info" :closable="false">
            On-hand stock is read-only here. Use Stock Adjustments, Stock Counts, or Stock Transfers to change quantity.
          </Message>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Quantity on Hand
              </label>
              <InputNumber
                v-model="form.quantity_on_hand"
                :min="0"
                class="w-full"
                :disabled="true"
              />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Quantity Damaged</label>
              <InputNumber v-model="form.quantity_damaged" :min="0" showButtons class="w-full" />
            </div>
          </div>

          <!-- Stock Thresholds -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Reorder Point</label>
              <InputNumber v-model="form.reorder_point" :min="0" showButtons class="w-full" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Reorder Quantity</label>
              <InputNumber v-model="form.reorder_quantity" :min="0" showButtons class="w-full" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Safety Stock</label>
              <InputNumber v-model="form.safety_stock" :min="0" showButtons class="w-full" />
            </div>
          </div>

          <!-- Warehouse Location -->
          <Divider>
            <span class="text-sm font-semibold text-gray-600">Warehouse Location</span>
          </Divider>

          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Section</label>
              <InputText v-model="form.warehouse_section" placeholder="e.g. A" class="w-full" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Aisle</label>
              <InputText v-model="form.aisle" placeholder="e.g. 01" class="w-full" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Rack</label>
              <InputText v-model="form.rack" placeholder="e.g. R1" class="w-full" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Shelf</label>
              <InputText v-model="form.shelf" placeholder="e.g. S1" class="w-full" />
            </div>
          </div>

          <!-- Cost -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Unit Cost</label>
              <InputNumber v-model="form.unit_cost" :min="0" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Average Cost</label>
              <InputNumber v-model="form.average_cost" :min="0" :minFractionDigits="2" :maxFractionDigits="2" class="w-full" />
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-3 pt-4">
            <Button
              label="Cancel"
              severity="secondary"
              text
              type="button"
              @click="router.push({ name: 'inventory.items' })"
            />
            <Button
              label="Save Changes"
              icon="pi pi-check"
              type="submit"
              :loading="submitting"
            />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()

const itemId = Number(route.params.id)
const loadingItem = ref(true)
const submitting = ref(false)
const item = ref<any>(null)

const form = reactive({
  quantity_on_hand: 0,
  quantity_damaged: 0,
  reorder_point: 10,
  reorder_quantity: 10,
  safety_stock: 5,
  warehouse_section: '',
  aisle: '',
  rack: '',
  shelf: '',
  unit_cost: null as number | null,
  average_cost: null as number | null
})

const errors = reactive<Record<string, string>>({})

const loadItem = async () => {
  loadingItem.value = true
  try {
    const response = await inventoryService.getInventoryItem(itemId)
    item.value = response?.data ?? response
    const d = item.value

    form.quantity_on_hand = d.quantity_on_hand ?? 0
    form.quantity_damaged = d.quantity_damaged ?? 0
    form.reorder_point = d.reorder_point ?? 10
    form.reorder_quantity = d.reorder_quantity ?? 10
    form.safety_stock = d.safety_stock ?? 5
    form.warehouse_section = d.warehouse_section ?? ''
    form.aisle = d.aisle ?? ''
    form.rack = d.rack ?? ''
    form.shelf = d.shelf ?? ''
    form.unit_cost = d.unit_cost ?? null
    form.average_cost = d.average_cost ?? null
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to load inventory item',
      life: 3000
    })
    router.push({ name: 'inventory.items' })
  } finally {
    loadingItem.value = false
  }
}

const getStockLabel = (status: string) => {
  const labels: Record<string, string> = {
    in_stock: 'In Stock',
    low_stock: 'Low Stock',
    out_of_stock: 'Out of Stock',
    overstock: 'Overstock'
  }
  return labels[status] ?? 'Unknown'
}

const getStockSeverity = (status: string) => {
  const severities: Record<string, string> = {
    in_stock: 'success',
    low_stock: 'warning',
    out_of_stock: 'danger',
    overstock: 'info'
  }
  return severities[status] ?? 'secondary'
}

const validate = (): boolean => {
  Object.keys(errors).forEach(k => delete errors[k])

  return Object.keys(errors).length === 0
}

const submitForm = async () => {
  if (!validate()) return

  submitting.value = true
  try {
    const { quantity_on_hand, ...payload } = form
    await inventoryService.updateInventoryItem(itemId, { ...payload })
    toast.add({
      severity: 'success',
      summary: 'Updated',
      detail: 'Inventory item updated successfully',
      life: 3000
    })
    router.push({ name: 'inventory.items' })
  } catch (error: any) {
    const msg = error.message || 'Failed to update inventory item'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: msg,
      life: 4000
    })
    if (error.errors) {
      Object.assign(errors, error.errors)
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadItem()
})
</script>
