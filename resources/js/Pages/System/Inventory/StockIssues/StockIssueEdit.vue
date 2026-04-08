<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit Stock Issue</h1>
        <p class="text-gray-600 mt-1">Update stock issue information - {{ stockIssue?.issue_number }}</p>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <ProgressSpinner />
      </div>

      <Card v-else-if="stockIssue">
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Issue Details -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Issue Details</h3>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Issue Type *</label>
                <Select
                  v-model="form.issue_type"
                  :options="typeOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select issue type"
                  class="w-full"
                  :class="{ 'p-invalid': errors.issue_type }"
                />
                <small v-if="errors.issue_type" class="p-error">{{ errors.issue_type[0] }}</small>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Issue Date</label>
                <Calendar
                  v-model="form.issue_date"
                  dateFormat="yy-mm-dd"
                  showTime
                  hourFormat="24"
                  class="w-full"
                  :class="{ 'p-invalid': errors.issue_date }"
                />
                <small v-if="errors.issue_date" class="p-error">{{ errors.issue_date[0] }}</small>
              </div>

              <!-- Product Selection -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Items to Issue</h3>
              </div>

              <div v-for="(item, index) in form.items" :key="index" class="md:col-span-2 border p-4 rounded-lg mb-4">
                <div class="flex justify-between items-center mb-3">
                  <h4 class="font-medium">Item #{{ index + 1 }}</h4>
                  <Button
                    v-if="form.items.length > 1"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    @click="removeItem(index)"
                  />
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product *</label>
                    <Select
                      v-model="item.product_id"
                      :options="products"
                      optionLabel="product_name"
                      optionValue="id"
                      placeholder="Select product"
                      class="w-full"
                      :class="{ 'p-invalid': errors[`items.${index}.product_id`] }"
                      @change="() => onProductChange(index)"
                    />
                    <small v-if="errors[`items.${index}.product_id`]" class="p-error">
                      {{ errors[`items.${index}.product_id`][0] }}
                    </small>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Inventory Item *</label>
                    <Select
                      v-model="item.inventory_item_id"
                      :options="getAvailableInventory(item.product_id)"
                      optionLabel="label"
                      optionValue="id"
                      placeholder="Select inventory item"
                      class="w-full"
                      :class="{ 'p-invalid': errors[`items.${index}.inventory_item_id`] }"
                    >
                      <template #option="slotProps">
                        <div>
                          <div>{{ slotProps.option.label }}</div>
                          <div class="text-xs text-gray-500">
                            Available: {{ slotProps.option.quantity_available }} | 
                            Location: {{ formatLocation(slotProps.option) }}
                          </div>
                        </div>
                      </template>
                    </Select>
                    <small v-if="errors[`items.${index}.inventory_item_id`]" class="p-error">
                      {{ errors[`items.${index}.inventory_item_id`][0] }}
                    </small>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                    <InputNumber
                      v-model="item.quantity"
                      placeholder="0"
                      class="w-full"
                      :class="{ 'p-invalid': errors[`items.${index}.quantity`] }"
                      :min="1"
                      :max="getMaxQuantity(item)"
                    />
                    <small v-if="errors[`items.${index}.quantity`]" class="p-error">
                      {{ errors[`items.${index}.quantity`][0] }}
                    </small>
                    <small v-if="item.inventory_item_id" class="text-gray-500">
                      Available: {{ getItemAvailableQuantity(item.inventory_item_id) }}
                    </small>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit Cost</label>
                    <InputNumber
                      v-model="item.unit_cost"
                      mode="currency"
                      currency="PHP"
                      locale="en-PH"
                      placeholder="0.00"
                      class="w-full"
                      :class="{ 'p-invalid': errors[`items.${index}.unit_cost`] }"
                    />
                    <small v-if="errors[`items.${index}.unit_cost`]" class="p-error">
                      {{ errors[`items.${index}.unit_cost`][0] }}
                    </small>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
                    <Select
                      v-model="item.reason"
                      :options="reasonOptions"
                      optionLabel="label"
                      optionValue="value"
                      placeholder="Select reason"
                      class="w-full"
                      :class="{ 'p-invalid': errors[`items.${index}.reason`] }"
                    />
                    <small v-if="errors[`items.${index}.reason`]" class="p-error">
                      {{ errors[`items.${index}.reason`][0] }}
                    </small>
                  </div>

                  <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                    <InputText
                      v-model="item.remarks"
                      placeholder="Optional remarks for this item"
                      class="w-full"
                    />
                  </div>
                </div>
              </div>

              <div class="md:col-span-2">
                <Button
                  type="button"
                  label="Add Another Item"
                  icon="pi pi-plus"
                  severity="secondary"
                  @click="addItem"
                  :disabled="!canAddMoreItems"
                />
              </div>

              <!-- Totals -->
              <div class="md:col-span-2">
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="flex justify-between items-center">
                    <span class="font-medium">Total Items:</span>
                    <span class="text-xl font-bold">{{ totalItems }}</span>
                  </div>
                  <div class="flex justify-between items-center mt-2">
                    <span class="font-medium">Total Value:</span>
                    <span class="text-xl font-bold text-red-600">
                      ₱{{ formatNumber(totalValue) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Additional Information -->
              <div class="md:col-span-2">
                <h3 class="text-lg font-semibold mb-4">Additional Information</h3>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <Textarea
                  v-model="form.description"
                  placeholder="Enter description or reason for this stock issue"
                  class="w-full"
                  rows="3"
                  :class="{ 'p-invalid': errors.description }"
                />
                <small v-if="errors.description" class="p-error">{{ errors.description[0] }}</small>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                <Textarea
                  v-model="form.remarks"
                  placeholder="Enter any additional remarks"
                  class="w-full"
                  rows="2"
                  :class="{ 'p-invalid': errors.remarks }"
                />
                <small v-if="errors.remarks" class="p-error">{{ errors.remarks[0] }}</small>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t">
              <Button
                type="button"
                label="Cancel"
                severity="secondary"
                @click="goBack"
                :disabled="submitting"
              />
              <Button
                type="submit"
                label="Update Stock Issue"
                :loading="submitting"
                class="bg-blue-600 hover:bg-blue-700"
              />
            </div>
          </form>
        </template>
      </Card>

      <div v-else class="text-center py-12">
        <p class="text-gray-500">Stock issue not found</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const submitting = ref(false)
const products = ref<any[]>([])
const inventoryItems = ref<any[]>([])
const stockIssue = ref<any>(null)
const errors = ref<any>({})
const toast = useToast()
const router = useRouter()
const route = useRoute()

interface IssueItem {
  id?: number
  product_id: number | null
  inventory_item_id: number | null
  quantity: number | null
  unit_cost: number | null
  reason: string
  remarks: string
}

const form = reactive({
  issue_type: '',
  issue_date: new Date(),
  description: '',
  remarks: '',
  items: [] as IssueItem[]
})

const typeOptions = [
  { label: 'Expired', value: 'expired' },
  { label: 'Damaged', value: 'damaged' },
  { label: 'Lost', value: 'lost' },
  { label: 'Internal Use', value: 'internal_use' },
  { label: 'Sample', value: 'sample' },
  { label: 'Quality Issue', value: 'quality_issue' },
  { label: 'Other', value: 'other' }
]

const reasonOptions = [
  { label: 'Quality Issue', value: 'quality_issue' },
  { label: 'Damaged', value: 'damaged' },
  { label: 'Expired', value: 'expired' },
  { label: 'Lost', value: 'lost' },
  { label: 'Internal Use', value: 'internal_use' },
  { label: 'Sample', value: 'sample' },
  { label: 'Other', value: 'other' }
]

const totalItems = computed(() => {
  return form.items.reduce((sum, item) => sum + (item.quantity || 0), 0)
})

const totalValue = computed(() => {
  return form.items.reduce((sum, item) => sum + ((item.quantity || 0) * (item.unit_cost || 0)), 0)
})

const canAddMoreItems = computed(() => {
  return form.items.length < 10 // Limit to 10 items per issue
})

const loadStockIssue = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getStockIssue(route.params.id as string)
    if (response.success) {
      stockIssue.value = response.data
      populateForm()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Failed to load stock issue details',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock issue details',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadProducts = async () => {
  try {
    const response = await inventoryService.getProducts({
      is_active: true,
      per_page: 1000
    })
    if (response.success) {
      if (response.data && Array.isArray(response.data.data)) {
        products.value = response.data.data
      } else if (Array.isArray(response.data)) {
        products.value = response.data
      } else {
        products.value = []
      }
    }
  } catch (error) {
    console.error('Failed to load products', error)
  }
}

const loadInventoryItems = async () => {
  try {
    const response = await inventoryService.getBranchInventory(1, {
      per_page: 1000
    })
    if (response.success) {
      if (response.data && Array.isArray(response.data.data)) {
        inventoryItems.value = response.data.data
      } else if (Array.isArray(response.data)) {
        inventoryItems.value = response.data
      } else {
        inventoryItems.value = []
      }
    }
  } catch (error) {
    console.error('Failed to load inventory items', error)
  }
}

const getAvailableInventory = (productId: number | null) => {
  if (!productId) return []
  
  return inventoryItems.value
    .filter(item => 
      item.product_id === productId && 
      item.quantity_available > 0
    )
    .map(item => ({
      id: item.id,
      label: `${item.product?.product_name || 'Unknown'} - ${formatLocation(item)}`,
      quantity_available: item.quantity_available,
      warehouse_section: item.warehouse_section,
      aisle: item.aisle,
      rack: item.rack,
      shelf: item.shelf,
      bin_code: item.bin_code
    }))
}

const getItemAvailableQuantity = (inventoryItemId: number | null) => {
  if (!inventoryItemId) return 0
  const item = inventoryItems.value.find(i => i.id === inventoryItemId)
  return item?.quantity_available || 0
}

const getMaxQuantity = (item: IssueItem) => {
  if (!item.inventory_item_id) return 1
  return getItemAvailableQuantity(item.inventory_item_id) + (getOriginalItemQuantity(item) || 0)
}

const getOriginalItemQuantity = (item: IssueItem) => {
  if (!item.id || !stockIssue.value?.items) return 0
  const originalItem = stockIssue.value.items.find((i: any) => i.id === item.id)
  return originalItem?.quantity || 0
}

const formatLocation = (item: any) => {
  if (!item) return 'No Location'
  const parts = []
  if (item.warehouse_section) parts.push(item.warehouse_section)
  if (item.aisle) parts.push(item.aisle)
  if (item.rack) parts.push(item.rack)
  if (item.shelf) parts.push(item.shelf)
  return parts.join('-') || item.bin_code || 'No Location'
}

const populateForm = () => {
  if (!stockIssue.value) return

  form.issue_type = stockIssue.value.issue_type || ''
  form.issue_date = stockIssue.value.issue_date ? new Date(stockIssue.value.issue_date) : new Date()
  form.description = stockIssue.value.description || ''
  form.remarks = stockIssue.value.remarks || ''

  // Populate items
  if (stockIssue.value.items && stockIssue.value.items.length > 0) {
    form.items = stockIssue.value.items.map((item: any) => ({
      id: item.id,
      product_id: item.inventory_item?.product_id || null,
      inventory_item_id: item.inventory_item_id,
      quantity: item.quantity,
      unit_cost: parseFloat(item.unit_cost) || null,
      reason: item.reason || 'other',
      remarks: item.remarks || ''
    }))
  }
}

const onProductChange = (index: number) => {
  // Reset inventory item when product changes
  form.items[index].inventory_item_id = null
  form.items[index].quantity = null
  form.items[index].unit_cost = null
}

const addItem = () => {
  form.items.push({
    id: undefined,
    product_id: null,
    inventory_item_id: null,
    quantity: null,
    unit_cost: null,
    reason: 'other',
    remarks: ''
  })
}

const removeItem = (index: number) => {
  form.items.splice(index, 1)
}

const submitForm = async () => {
  submitting.value = true
  errors.value = {}

  try {
    const payload = {
      issue_type: form.issue_type,
      issue_date: form.issue_date.toISOString().split('T')[0],
      description: form.description,
      remarks: form.remarks,
      items: form.items.map(item => ({
        id: item.id, // Include ID for existing items
        inventory_item_id: item.inventory_item_id,
        quantity: item.quantity,
        unit_cost: item.unit_cost,
        reason: item.reason,
        remarks: item.remarks
      }))
    }

    const response = await inventoryService.updateStockIssue(stockIssue.value.id, payload)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock issue updated successfully',
        life: 3000
      })
      router.push({ name: 'inventory.stock-issues.detail', params: { id: stockIssue.value.id } })
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
        detail: error.response?.data?.message || 'Failed to update stock issue',
        life: 3000
      })
    }
  } finally {
    submitting.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.stock-issues.detail', params: { id: stockIssue.value.id } })
}

const formatNumber = (value: number) => {
  return value.toFixed(2)
}

onMounted(async () => {
  await Promise.all([
    loadStockIssue(),
    loadProducts(),
    loadInventoryItems()
  ])
})
</script>