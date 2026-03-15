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
            v-tooltip.top="'Back to Stock Counts'"
          />
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Create Stock Count</h1>
            <p class="text-gray-600 mt-1">Start a new inventory stock count</p>
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
                    Reference Number <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.reference_number"
                    placeholder="Enter reference number"
                    class="w-full"
                    :class="{ 'p-invalid': errors.reference_number }"
                    required
                  />
                  <small v-if="errors.reference_number" class="p-error">{{ errors.reference_number[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Count Date <span class="text-red-500">*</span>
                  </label>
                  <Calendar
                    v-model="form.count_date"
                    placeholder="Select count date"
                    class="w-full"
                    showIcon
                    dateFormat="yy-mm-dd"
                    :class="{ 'p-invalid': errors.count_date }"
                    required
                  />
                  <small v-if="errors.count_date" class="p-error">{{ errors.count_date[0] }}</small>
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
                    @change="onWarehouseChange"
                  />
                  <small v-if="errors.warehouse_id" class="p-error">{{ errors.warehouse_id[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Location (Optional)
                  </label>
                  <Select
                    v-model="form.location_id"
                    :options="locations"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select location (optional)"
                    class="w-full"
                    :loading="locationsLoading"
                    showClear
                    :disabled="!form.warehouse_id"
                  />
                </div>
              </div>
            </div>

            <!-- Count Type -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Count Configuration</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Count Type <span class="text-red-500">*</span>
                  </label>
                  <Select
                    v-model="form.count_type"
                    :options="countTypeOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select count type"
                    class="w-full"
                    :class="{ 'p-invalid': errors.count_type }"
                    required
                  />
                  <small v-if="errors.count_type" class="p-error">{{ errors.count_type[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Count Method
                  </label>
                  <Select
                    v-model="form.count_method"
                    :options="countMethodOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select count method"
                    class="w-full"
                  />
                </div>
              </div>
            </div>

            <!-- Product Selection -->
            <div>
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Products to Count</h3>
                <div class="flex gap-2">
                  <Button
                    label="Auto-Suggest Cycle"
                    icon="pi pi-bolt"
                    severity="warning"
                    outlined
                    @click="applyCycleSuggestions"
                    type="button"
                    :loading="suggestLoading"
                    :disabled="!form.warehouse_id"
                  />
                  <Button
                    label="Add All Products"
                    icon="pi pi-plus"
                    severity="info"
                    outlined
                    @click="addAllProducts"
                    type="button"
                    :disabled="!form.warehouse_id"
                  />
                  <Button
                    label="Add Selected"
                    icon="pi pi-plus"
                    severity="success"
                    @click="addSelectedProducts"
                    type="button"
                    :disabled="!form.warehouse_id || selectedProducts.length === 0"
                  />
                </div>
              </div>

              <!-- Available Products -->
              <div class="mb-4">
                <DataTable
                  :value="availableProducts"
                  :loading="productsLoading"
                  class="p-datatable-sm"
                  tableStyle="min-width: 50rem"
                  :paginator="true"
                  :rows="10"
                  selectionMode="multiple"
                  v-model:selection="selectedProducts"
                  dataKey="id"
                >
                  <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                  <Column field="name" header="Product" style="min-width: 200px">
                    <template #body="slotProps">
                      <div class="text-sm">
                        <div class="font-medium">{{ slotProps.data.name }}</div>
                        <div class="text-gray-500">{{ slotProps.data.code }}</div>
                      </div>
                    </template>
                  </Column>
                  <Column field="category.name" header="Category" style="min-width: 120px"></Column>
                  <Column field="unit.name" header="Unit" style="min-width: 100px"></Column>
                  <Column field="current_stock" header="Current Stock" style="min-width: 120px">
                    <template #body="slotProps">
                      {{ slotProps.data.current_stock || 0 }}
                    </template>
                  </Column>
                </DataTable>
              </div>

              <!-- Selected Products for Counting -->
              <div v-if="form.items.length > 0">
                <h4 class="text-md font-medium text-gray-700 mb-2">Selected for Counting</h4>
                <DataTable
                  :value="form.items"
                  class="p-datatable-sm"
                  tableStyle="min-width: 50rem"
                >
                  <Column field="product.name" header="Product" style="min-width: 200px">
                    <template #body="slotProps">
                      <div class="text-sm">
                        <div class="font-medium">{{ slotProps.data.product?.name }}</div>
                        <div class="text-gray-500">{{ slotProps.data.product?.code }}</div>
                      </div>
                    </template>
                  </Column>
                  <Column field="expected_quantity" header="Expected Qty" style="min-width: 120px">
                    <template #body="slotProps">
                      {{ slotProps.data.expected_quantity || 0 }}
                    </template>
                  </Column>
                  <Column field="counted_quantity" header="Counted Qty" style="min-width: 120px">
                    <template #body="slotProps">
                      <InputNumber
                        v-model="slotProps.data.counted_quantity"
                        :min="0"
                        class="w-full"
                        placeholder="Enter count"
                      />
                    </template>
                  </Column>
                  <Column field="discrepancy" header="Discrepancy" style="min-width: 120px">
                    <template #body="slotProps">
                      <span :class="getDiscrepancyClass(slotProps.data)">
                        {{ calculateDiscrepancy(slotProps.data) }}
                      </span>
                    </template>
                  </Column>
                  <Column header="Actions" style="min-width: 100px">
                    <template #body="slotProps">
                      <Button
                        icon="pi pi-trash"
                        severity="danger"
                        outlined
                        @click="removeProduct(slotProps.index)"
                      />
                    </template>
                  </Column>
                </DataTable>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Notes
              </label>
              <Textarea
                v-model="form.notes"
                placeholder="Enter count notes"
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
                label="Create Stock Count"
                type="submit"
                :loading="saving"
                :disabled="form.items.length === 0"
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
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const saving = ref(false)
const productsLoading = ref(false)
const warehousesLoading = ref(false)
const locationsLoading = ref(false)
const errors = ref<any>({})
const warehouses = ref<any[]>([])
const locations = ref<any[]>([])
const availableProducts = ref<any[]>([])
const selectedProducts = ref<any[]>([])
const suggestLoading = ref(false)
const toast = useToast()
const router = useRouter()

const form = reactive({
  reference_number: '',
  count_date: new Date(),
  warehouse_id: null as number | null,
  location_id: null as number | null,
  count_type: 'full',
  count_method: 'manual',
  items: [] as any[],
  notes: ''
})

const countTypeOptions = [
  { label: 'Full Count', value: 'full' },
  { label: 'Partial Count', value: 'partial' },
  { label: 'Cycle Count', value: 'cycle' },
  { label: 'Spot Check', value: 'spot_check' }
]

const countMethodOptions = [
  { label: 'Manual Count', value: 'manual' },
  { label: 'Barcode Scanner', value: 'barcode' },
  { label: 'RFID', value: 'rfid' },
  { label: 'Automated', value: 'automated' }
]

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

const loadLocations = async (warehouseId: number) => {
  locationsLoading.value = true
  try {
    const response = await inventoryService.getLocations({ warehouse_id: warehouseId, per_page: 1000 })

    if (response.success) {
      locations.value = response.data || []
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load locations',
      life: 3000
    })
  } finally {
    locationsLoading.value = false
  }
}

const loadProducts = async () => {
  if (!form.warehouse_id) return

  productsLoading.value = true
  try {
    const params = {
      warehouse_id: form.warehouse_id,
      location_id: form.location_id || undefined,
      per_page: 1000
    }

    const response = await inventoryService.getProducts(params)

    if (response.success) {
      availableProducts.value = response.data || []
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

const applyCycleSuggestions = async () => {
  if (!form.warehouse_id) return

  suggestLoading.value = true
  try {
    const response = await inventoryService.getStockCountSuggestions({
      branch_id: form.warehouse_id,
      limit: 50
    })

    if (response.success) {
      const suggestions = response.data?.items || []
      form.count_type = 'cycle'

      const newItems = suggestions
        .filter((item: any) => !form.items.some(existing => existing.product_id === item.product_id))
        .map((item: any) => ({
          product_id: item.product_id,
          product: {
            id: item.product_id,
            name: item.product_name,
            code: item.sku
          },
          expected_quantity: item.current_stock || 0,
          counted_quantity: null,
          discrepancy: 0
        }))

      form.items.push(...newItems)
      toast.add({
        severity: 'success',
        summary: 'Suggestions Applied',
        detail: `Added ${newItems.length} products to cycle count`,
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load suggestions',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load suggestions',
      life: 3000
    })
  } finally {
    suggestLoading.value = false
  }
}

const onWarehouseChange = () => {
  form.location_id = null
  locations.value = []
  availableProducts.value = []
  selectedProducts.value = []
  form.items = []

  if (form.warehouse_id) {
    loadLocations(form.warehouse_id)
    loadProducts()
  }
}

const addAllProducts = () => {
  const newItems = availableProducts.value
    .filter(product => !form.items.some(item => item.product_id === product.id))
    .map(product => ({
      product_id: product.id,
      product: product,
      expected_quantity: product.current_stock || 0,
      counted_quantity: null,
      discrepancy: 0
    }))

  form.items.push(...newItems)
}

const addSelectedProducts = () => {
  const newItems = selectedProducts.value
    .filter(product => !form.items.some(item => item.product_id === product.id))
    .map(product => ({
      product_id: product.id,
      product: product,
      expected_quantity: product.current_stock || 0,
      counted_quantity: null,
      discrepancy: 0
    }))

  form.items.push(...newItems)
  selectedProducts.value = []
}

const removeProduct = (index: number) => {
  form.items.splice(index, 1)
}

const calculateDiscrepancy = (item: any) => {
  if (item.counted_quantity === null || item.counted_quantity === undefined) return '-'
  return item.counted_quantity - (item.expected_quantity || 0)
}

const getDiscrepancyClass = (item: any) => {
  const discrepancy = calculateDiscrepancy(item)
  if (discrepancy === '-') return ''
  return discrepancy === 0 ? 'text-green-600' : 'text-red-600 font-medium'
}

const goBack = () => {
  router.push({ name: 'inventory.stock-counts.index' })
}

const submitForm = async () => {
  saving.value = true
  errors.value = {}

  // Prepare form data
  const submitData = {
    ...form,
    count_date: form.count_date ? form.count_date.toISOString().split('T')[0] : null,
    items: form.items.map(item => ({
      product_id: item.product_id,
      expected_quantity: item.expected_quantity,
      counted_quantity: item.counted_quantity
    }))
  }

  try {
    const response = await inventoryService.createStockCount(submitData)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock count created successfully',
        life: 3000
      })
      router.push({ name: 'inventory.stock-counts.detail', params: { id: response.data.id } })
    } else {
      if (response.errors) {
        errors.value = response.errors
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: response.message || 'Failed to create stock count',
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
        detail: error.response?.data?.message || 'Failed to create stock count',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadWarehouses()
})
</script>
