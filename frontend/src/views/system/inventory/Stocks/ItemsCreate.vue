<template>
  <div class="max-w-4xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.items' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Add Inventory Item</h2>
        <p class="text-sm text-gray-500 mt-1">Register a product in your branch inventory</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <!-- Product Selection -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Product <span class="text-red-500">*</span>
              </label>
              <Select
                v-model="form.product_id"
                :options="products"
                optionLabel="product_name"
                optionValue="id"
                placeholder="Select a product"
                :loading="loadingProducts"
                filter
                showClear
                :class="{ 'p-invalid': errors.product_id }"
              >
                <template #option="{ option }">
                  <div class="flex flex-col">
                    <span class="font-medium">{{ option.product_name }}</span>
                    <span class="text-xs text-gray-500">SKU: {{ option.sku }}</span>
                  </div>
                </template>
              </Select>
              <small v-if="errors.product_id" class="text-red-500">{{ errors.product_id }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Quantity on Hand <span class="text-red-500">*</span>
              </label>
              <InputNumber
                v-model="form.quantity_on_hand"
                :min="0"
                showButtons
                :class="{ 'p-invalid': errors.quantity_on_hand }"
                class="w-full"
              />
              <small v-if="errors.quantity_on_hand" class="text-red-500">{{ errors.quantity_on_hand }}</small>
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
              label="Save Item"
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
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'

const router = useRouter()
const toast = useToast()

const submitting = ref(false)
const loadingProducts = ref(false)
const products = ref<any[]>([])

const form = reactive({
  product_id: null as number | null,
  quantity_on_hand: 0,
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

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const response = await inventoryService.getProducts({ per_page: 200 })
    products.value = response?.data ?? []
  } catch (error) {
    console.error('Failed to load products', error)
  } finally {
    loadingProducts.value = false
  }
}

const validate = (): boolean => {
  Object.keys(errors).forEach(k => delete errors[k])

  if (!form.product_id) {
    errors.product_id = 'Product is required'
  }
  if (form.quantity_on_hand === null || form.quantity_on_hand < 0) {
    errors.quantity_on_hand = 'Quantity must be 0 or greater'
  }

  return Object.keys(errors).length === 0
}

const submitForm = async () => {
  if (!validate()) return

  submitting.value = true
  try {
    await inventoryService.createInventoryItem({ ...form })
    toast.add({
      severity: 'success',
      summary: 'Created',
      detail: 'Inventory item added successfully',
      life: 3000
    })
    router.push({ name: 'inventory.items' })
  } catch (error: any) {
    const msg = error.message || 'Failed to create inventory item'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: msg,
      life: 4000
    })
    // Propagate field-level validation errors from backend
    if (error.errors) {
      Object.assign(errors, error.errors)
    }
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadProducts()
})
</script>
