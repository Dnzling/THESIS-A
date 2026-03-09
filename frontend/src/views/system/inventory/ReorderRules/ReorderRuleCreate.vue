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
            v-tooltip.top="'Back to Reorder Rules'"
          />
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Create Reorder Rule</h1>
            <p class="text-gray-600 mt-1">Set up automatic reorder rules for inventory management</p>
          </div>
        </div>
      </div>

      <Card>
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Product <span class="text-red-500">*</span>
                  </label>
                  <Select
                    v-model="form.product_id"
                    :options="products"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select product"
                    class="w-full"
                    :loading="productsLoading"
                    :class="{ 'p-invalid': errors.product_id }"
                    required
                    @change="onProductChange"
                  />
                  <small v-if="errors.product_id" class="p-error">{{ errors.product_id[0] }}</small>
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
                    :loading="warehousesLoading"
                    :class="{ 'p-invalid': errors.warehouse_id }"
                    required
                  />
                  <small v-if="errors.warehouse_id" class="p-error">{{ errors.warehouse_id[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Trigger Type <span class="text-red-500">*</span>
                  </label>
                  <Select
                    v-model="form.trigger_type"
                    :options="triggerTypeOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select trigger type"
                    class="w-full"
                    :class="{ 'p-invalid': errors.trigger_type }"
                    required
                  />
                  <small v-if="errors.trigger_type" class="p-error">{{ errors.trigger_type[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Status
                  </label>
                  <Select
                    v-model="form.status"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select status"
                    class="w-full"
                  />
                </div>
              </div>
            </div>

            <!-- Stock Levels -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Stock Levels</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Minimum Stock Level <span class="text-red-500">*</span>
                  </label>
                  <InputNumber
                    v-model="form.min_stock_level"
                    :min="0"
                    placeholder="Enter minimum stock"
                    class="w-full"
                    :class="{ 'p-invalid': errors.min_stock_level }"
                    required
                  />
                  <small v-if="errors.min_stock_level" class="p-error">{{ errors.min_stock_level[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Maximum Stock Level <span class="text-red-500">*</span>
                  </label>
                  <InputNumber
                    v-model="form.max_stock_level"
                    :min="0"
                    placeholder="Enter maximum stock"
                    class="w-full"
                    :class="{ 'p-invalid': errors.max_stock_level }"
                    required
                  />
                  <small v-if="errors.max_stock_level" class="p-error">{{ errors.max_stock_level[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reorder Quantity <span class="text-red-500">*</span>
                  </label>
                  <InputNumber
                    v-model="form.reorder_quantity"
                    :min="1"
                    placeholder="Enter reorder quantity"
                    class="w-full"
                    :class="{ 'p-invalid': errors.reorder_quantity }"
                    required
                  />
                  <small v-if="errors.reorder_quantity" class="p-error">{{ errors.reorder_quantity[0] }}</small>
                </div>
              </div>

              <!-- Current Stock Info -->
              <div v-if="selectedProduct" class="mt-4 p-4 bg-blue-50 rounded-lg">
                <h4 class="text-sm font-medium text-blue-800 mb-2">Current Stock Information</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                  <div>
                    <span class="text-gray-600">Current Stock:</span>
                    <span class="font-medium ml-2">{{ selectedProduct.current_stock || 0 }}</span>
                  </div>
                  <div>
                    <span class="text-gray-600">Available Stock:</span>
                    <span class="font-medium ml-2">{{ selectedProduct.available_stock || 0 }}</span>
                  </div>
                  <div>
                    <span class="text-gray-600">Stock Status:</span>
                    <Tag
                      :value="getStockStatus(selectedProduct)"
                      :severity="getStockStatusSeverity(selectedProduct)"
                      class="ml-2"
                    />
                  </div>
                </div>
              </div>
            </div>

            <!-- Advanced Settings -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Advanced Settings</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Lead Time (Days)
                  </label>
                  <InputNumber
                    v-model="form.lead_time_days"
                    :min="0"
                    placeholder="Enter lead time in days"
                    class="w-full"
                    :class="{ 'p-invalid': errors.lead_time_days }"
                  />
                  <small v-if="errors.lead_time_days" class="p-error">{{ errors.lead_time_days[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Safety Stock Level
                  </label>
                  <InputNumber
                    v-model="form.safety_stock_level"
                    :min="0"
                    placeholder="Enter safety stock level"
                    class="w-full"
                    :class="{ 'p-invalid': errors.safety_stock_level }"
                  />
                  <small v-if="errors.safety_stock_level" class="p-error">{{ errors.safety_stock_level[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Auto Order
                  </label>
                  <Select
                    v-model="form.auto_order"
                    :options="autoOrderOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select auto order setting"
                    class="w-full"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Priority
                  </label>
                  <Select
                    v-model="form.priority"
                    :options="priorityOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select priority"
                    class="w-full"
                  />
                </div>
              </div>
            </div>

            <!-- Notifications -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Notifications</h3>
              <div class="space-y-3">
                <div class="flex items-center">
                  <Checkbox
                    v-model="form.notify_on_trigger"
                    inputId="notify_trigger"
                    class="mr-3"
                  />
                  <label for="notify_trigger" class="text-sm text-gray-700">
                    Notify when reorder rule is triggered
                  </label>
                </div>
                <div class="flex items-center">
                  <Checkbox
                    v-model="form.notify_on_reorder"
                    inputId="notify_reorder"
                    class="mr-3"
                  />
                  <label for="notify_reorder" class="text-sm text-gray-700">
                    Notify when reorder is placed
                  </label>
                </div>
                <div class="flex items-center">
                  <Checkbox
                    v-model="form.notify_on_low_stock"
                    inputId="notify_low_stock"
                    class="mr-3"
                  />
                  <label for="notify_low_stock" class="text-sm text-gray-700">
                    Notify on low stock alerts
                  </label>
                </div>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Notes
              </label>
              <Textarea
                v-model="form.notes"
                placeholder="Enter rule notes"
                class="w-full"
                rows="4"
                :class="{ 'p-invalid': errors.notes }"
              />
              <small v-if="errors.notes" class="p-error">{{ errors.notes[0] }}</small>
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
                label="Create Reorder Rule"
                type="submit"
                :loading="saving"
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
import { ref, onMounted, reactive } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const saving = ref(false)
const productsLoading = ref(false)
const warehousesLoading = ref(false)
const errors = ref<any>({})
const products = ref<any[]>([])
const warehouses = ref<any[]>([])
const selectedProduct = ref<any>(null)
const toast = useToast()
const router = useRouter()

const form = reactive({
  product_id: null as number | null,
  warehouse_id: null as number | null,
  trigger_type: '',
  min_stock_level: null as number | null,
  max_stock_level: null as number | null,
  reorder_quantity: null as number | null,
  lead_time_days: null as number | null,
  safety_stock_level: null as number | null,
  auto_order: 'manual',
  priority: 'medium',
  status: 'active',
  notify_on_trigger: true,
  notify_on_reorder: false,
  notify_on_low_stock: true,
  notes: ''
})

const triggerTypeOptions = [
  { label: 'Min Stock Level', value: 'min_stock' },
  { label: 'Max Stock Level', value: 'max_stock' },
  { label: 'Reorder Point', value: 'reorder_point' },
  { label: 'Demand Forecast', value: 'demand_forecast' }
]

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

const autoOrderOptions = [
  { label: 'Manual', value: 'manual' },
  { label: 'Automatic', value: 'automatic' },
  { label: 'Semi-Automatic', value: 'semi_automatic' }
]

const priorityOptions = [
  { label: 'Low', value: 'low' },
  { label: 'Medium', value: 'medium' },
  { label: 'High', value: 'high' },
  { label: 'Critical', value: 'critical' }
]

const loadProducts = async () => {
  productsLoading.value = true
  try {
    const response = await inventoryService.getProducts({ per_page: 1000 })

    if (response.success) {
      products.value = response.data || []
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load products',
      life: 3000
    })
  } finally {
    productsLoading.value = false
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

const onProductChange = () => {
  selectedProduct.value = products.value.find(p => p.id === form.product_id) || null
}

const goBack = () => {
  router.push({ name: 'inventory.reorder-rules.index' })
}

const submitForm = async () => {
  saving.value = true
  errors.value = {}

  try {
    const response = await inventoryService.createReorderRule(form)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Reorder rule created successfully',
        life: 3000
      })
      router.push({ name: 'inventory.reorder-rules.detail', params: { id: response.data.id } })
    } else {
      if (response.errors) {
        errors.value = response.errors
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: response.message || 'Failed to create reorder rule',
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
        detail: error.response?.data?.message || 'Failed to create reorder rule',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

const getStockStatus = (product: any) => {
  const stock = product.current_stock || 0
  if (stock === 0) return 'Out of Stock'
  if (stock <= (form.min_stock_level || 0)) return 'Low Stock'
  if (stock >= (form.max_stock_level || 0)) return 'Overstock'
  return 'In Stock'
}

const getStockStatusSeverity = (product: any) => {
  const status = getStockStatus(product)
  switch (status) {
    case 'Out of Stock': return 'danger'
    case 'Low Stock': return 'warning'
    case 'Overstock': return 'info'
    case 'In Stock': return 'success'
    default: return 'secondary'
  }
}

onMounted(() => {
  loadProducts()
  loadWarehouses()
})
</script>