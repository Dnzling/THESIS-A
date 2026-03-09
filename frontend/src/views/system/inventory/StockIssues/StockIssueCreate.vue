<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.stock-issues' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create Stock Issue</h2>
        <p class="text-sm text-gray-500 mt-1">Record stock out transactions (damaged, lost, expired, etc.)</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitIssue">
          <!-- Header Section -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Issue Type <span class="text-red-500">*</span>
              </label>
              <Select 
                v-model="form.issue_type" 
                :options="typeOptions" 
                optionLabel="label" 
                optionValue="value"
                placeholder="Select issue type" 
                :class="{ 'p-invalid': errors.issue_type }"
                fluid
              >
                <template #option="slotProps">
                  <div class="flex items-center gap-2">
                    <i :class="getTypeIcon(slotProps.option.value)" class="text-lg"></i>
                    <span>{{ slotProps.option.label }}</span>
                  </div>
                </template>
              </Select>
              <small v-if="errors.issue_type" class="text-red-500">{{ errors.issue_type[0] }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Issue Date <span class="text-red-500">*</span>
              </label>
              <DatePicker 
                v-model="form.issue_date" 
                dateFormat="yy-mm-dd" 
                class="w-full" 
                :maxDate="today"
                :class="{ 'p-invalid': errors.issue_date }"
                fluid
              />
              <small v-if="errors.issue_date" class="text-red-500">{{ errors.issue_date[0] }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Reference Number
              </label>
              <InputText 
                v-model="form.reference_number" 
                placeholder="Auto-generated if empty"
                fluid
              />
              <small class="text-gray-500">Leave empty for auto-generation</small>
            </div>
          </div>

          <!-- Items Section -->
          <Divider>
            <span class="text-sm font-semibold text-gray-600">Items to Issue</span>
          </Divider>

          <!-- Add Item Form -->
          <div class="bg-gray-50 p-4 rounded-lg space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">Add Item to Issue</h3>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
              <div class="flex flex-col gap-2 md:col-span-5">
                <label class="text-sm text-gray-600">Product <span class="text-red-500">*</span></label>
                <Select 
                  v-model="newItem.inventory_item_id" 
                  :options="availableProducts" 
                  optionLabel="displayName"
                  optionValue="id" 
                  placeholder="Search product..." 
                  :loading="inventoryItemsLoading" 
                  filter 
                  showClear
                  fluid
                >
                  <template #option="{ option }">
                    <div class="flex flex-col">
                      <div class="flex items-center justify-between">
                        <span class="font-medium">{{ option.productName }}</span>
                        <Tag 
                          :value="option.stock" 
                          :severity="getStockSeverityValue(option.stock)"
                          class="text-xs"
                        />
                      </div>
                      <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                        <span>SKU: {{ option.sku }}</span>
                        <span>•</span>
                        <span>Bin: {{ option.binCode || 'N/A' }}</span>
                        <span>•</span>
                        <span>Cost: {{ formatCurrency(option.unitCost) }}</span>
                      </div>
                    </div>
                  </template>
                  <template #empty>
                    <div class="p-2 text-center text-gray-500">
                      No products available with stock
                    </div>
                  </template>
                </Select>
              </div>

              <div class="flex flex-col gap-2 md:col-span-2">
                <label class="text-sm text-gray-600">Quantity <span class="text-red-500">*</span></label>
                <InputNumber 
                  v-model="newItem.quantity" 
                  :min="1" 
                  :max="selectedProductStock"
                  showButtons 
                  buttonLayout="horizontal"
                  fluid
                >
                  <template #incrementbuttonicon>
                    <span class="pi pi-plus" />
                  </template>
                  <template #decrementbuttonicon>
                    <span class="pi pi-minus" />
                  </template>
                </InputNumber>
              </div>

              <div class="flex flex-col gap-2 md:col-span-2">
                <label class="text-sm text-gray-600">Available Stock</label>
                <InputText 
                  :value="selectedProductStock" 
                  disabled 
                  class="bg-gray-100"
                  fluid
                />
              </div>

              <div class="flex flex-col gap-2 md:col-span-3">
                <label class="text-sm text-gray-600">Reason (Optional)</label>
                <Select
                  v-model="newItem.reason"
                  :options="reasonOptions"
                  optionLabel="label"
                  optionValue="value"
                  placeholder="Select reason"
                  showClear
                  fluid
                />
              </div>
            </div>

            <!-- Stock Info & Warning -->
            <div v-if="selectedProduct" class="text-sm bg-white p-3 rounded border border-blue-200">
              <div class="flex justify-between items-center">
                <div>
                  <span class="font-medium">{{ selectedProduct.productName }}</span>
                  <span class="text-xs text-gray-500 ml-2">SKU: {{ selectedProduct.sku }}</span>
                </div>
                <div class="flex items-center gap-3">
                  <span class="font-medium">
                    Unit Cost: <span class="text-blue-600">{{ formatCurrency(selectedProduct.unitCost) }}</span>
                  </span>
                  <span class="font-medium">
                    Available: <span :class="getStockClass(selectedProduct.stock)">{{ selectedProduct.stock }}</span> units
                  </span>
                </div>
              </div>
              <div v-if="selectedProduct.stock < newItem.quantity" class="mt-2 text-amber-600 text-xs">
                <i class="pi pi-exclamation-triangle mr-1"></i>
                Warning: Issuing more than available stock will result in negative inventory
              </div>
            </div>

            <!-- Add Button -->
            <div class="flex justify-end">
              <Button 
                icon="pi pi-plus" 
                label="Add Item" 
                @click="addItem" 
                :disabled="!canAddItem" 
                severity="success"
              />
            </div>
          </div>

          <!-- Items Table -->
          <DataTable :value="form.items" class="p-datatable-sm" stripedRows showGridlines>
            <template #empty>
              <div class="text-center py-8 text-gray-500">
                <i class="pi pi-inbox text-4xl mb-2"></i>
                <p>No items added yet. Add items using the form above.</p>
              </div>
            </template>

            <Column header="Product" style="min-width: 250px">
              <template #body="{ data }">
                <div class="flex flex-col">
                  <span class="font-medium">{{ getProductName(data.inventory_item_id) }}</span>
                  <span class="text-xs text-gray-500">SKU: {{ getProductSku(data.inventory_item_id) }}</span>
                </div>
              </template>
            </Column>

            <Column field="quantity" header="Quantity" style="width: 100px">
              <template #body="{ data }">
                <span class="font-medium text-red-600">{{ data.quantity }}</span>
              </template>
            </Column>

            <Column header="Unit Cost" style="width: 120px">
              <template #body="{ data }">
                {{ formatCurrency(getItemUnitCost(data)) }}
              </template>
            </Column>

            <Column header="Total" style="width: 120px">
              <template #body="{ data }">
                <span class="font-medium text-blue-600">
                  {{ formatCurrency(getItemTotal(data)) }}
                </span>
              </template>
            </Column>

            <Column field="reason" header="Reason" style="min-width: 150px">
              <template #body="{ data }">
                {{ data.reason ? formatReason(data.reason) : '-' }}
              </template>
            </Column>

            <Column field="remarks" header="Remarks" style="min-width: 150px">
              <template #body="{ data }">
                {{ data.remarks || '-' }}
              </template>
            </Column>

            <Column header="Actions" style="width: 80px">
              <template #body="{ index }">
                <Button 
                  icon="pi pi-trash" 
                  text 
                  rounded 
                  severity="danger" 
                  size="small" 
                  @click="removeItem(index)"
                  v-tooltip="'Remove item'" 
                />
              </template>
            </Column>
          </DataTable>

          <!-- Summary Section -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Description & Remarks -->
            <div class="space-y-4">
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Description</label>
                <Textarea 
                  v-model="form.description" 
                  rows="3" 
                  placeholder="Overall description of the stock issue"
                  :class="{ 'p-invalid': errors.description }"
                  fluid
                />
                <small v-if="errors.description" class="text-red-500">{{ errors.description[0] }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Remarks</label>
                <Textarea 
                  v-model="form.remarks" 
                  rows="3" 
                  placeholder="Any additional remarks"
                  fluid
                />
              </div>
            </div>

            <!-- Totals Card -->
            <div class="bg-linear-to-br from-gray-50 to-gray-100 rounded-xl p-6 border">
              <h4 class="text-sm font-medium text-gray-600 mb-4">Issue Summary</h4>
              <div class="space-y-3">
                <div class="flex justify-between items-center py-2 border-b">
                  <span>Total Items:</span>
                  <span class="font-semibold text-lg">{{ form.items.length }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b">
                  <span>Total Quantity:</span>
                  <span class="font-semibold text-lg">{{ totalQuantity }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                  <span class="text-lg font-medium">Total Value:</span>
                  <span class="text-2xl font-bold text-red-600">{{ formatCurrency(totalValue) }}</span>
                </div>
              </div>

              <div v-if="form.items.length > 0" class="mt-4 grid grid-cols-2 gap-2">
                <div class="bg-white p-2 rounded text-center">
                  <div class="text-xs text-gray-500">Avg Cost</div>
                  <div class="font-semibold">{{ formatCurrency(averageCost) }}</div>
                </div>
                <div class="bg-white p-2 rounded text-center">
                  <div class="text-xs text-gray-500">Items with Stock</div>
                  <div class="font-semibold">{{ itemsWithStock }}</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Form Actions -->
          <div class="pt-4 flex gap-2 justify-end border-t border-gray-200">
            <Button 
              label="Cancel" 
              severity="secondary" 
              outlined 
              type="button" 
              @click="cancel"
            />
            <Button 
              label="Create Stock Issue" 
              icon="pi pi-check" 
              :loading="submitting" 
              type="submit" 
              :disabled="!isFormValid"
              class="bg-red-600 hover:bg-red-700 border-red-600"
            />
          </div>
        </form>
      </template>
    </Card>

    <!-- Confirmation Dialog for Cancel -->
    <Dialog v-model:visible="showCancelDialog" header="Discard Changes" :modal="true" class="w-full sm:w-96">
      <div class="space-y-4">
        <p class="text-gray-600">Are you sure you want to cancel? Any unsaved changes will be lost.</p>
        <div class="flex justify-end gap-2">
          <Button label="No, Stay" severity="secondary" outlined @click="showCancelDialog = false" />
          <Button label="Yes, Discard" severity="danger" @click="confirmCancel" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

const router = useRouter()
const toast = useToast()

// State
const submitting = ref(false)
const inventoryItemsLoading = ref(false)
const showCancelDialog = ref(false)
const inventoryItems = ref<any[]>([])

const today = new Date()

// Form state
const form = reactive({
  issue_type: '',
  issue_date: today,
  reference_number: '',
  description: '',
  remarks: '',
  items: [] as Array<{
    inventory_item_id: number
    quantity: number
    reason: string | null
    remarks: string | null
  }>
})

// New item form
const newItem = reactive({
  inventory_item_id: null as number | null,
  quantity: 1,
  reason: '',
  remarks: ''
})

// Validation errors
const errors = ref<any>({})

// Type options
const typeOptions = [
  { label: 'Damaged', value: 'damaged' },
  { label: 'Lost', value: 'lost' },
  { label: 'Expired', value: 'expired' },
  { label: 'Theft', value: 'theft' },
  { label: 'Other', value: 'other' }
]

// Reason options
const reasonOptions = [
  { label: 'Customer Return', value: 'customer_return' },
  { label: 'Quality Issue', value: 'quality_issue' },
  { label: 'Damaged in Transit', value: 'damaged_transit' },
  { label: 'Expired', value: 'expired' },
  { label: 'Theft', value: 'theft' },
  { label: 'Inventory Adjustment', value: 'adjustment' },
  { label: 'Other', value: 'other' }
]

// Get type icon
const getTypeIcon = (type: string) => {
  const icons: Record<string, string> = {
    damaged: 'pi pi-exclamation-triangle text-orange-500',
    lost: 'pi pi-question-circle text-red-500',
    expired: 'pi pi-clock text-yellow-500',
    theft: 'pi pi-lock text-purple-500',
    other: 'pi pi-ellipsis-h text-gray-500'
  }
  return icons[type] || 'pi pi-tag'
}

// Format reason
const formatReason = (reason: string) => {
  const option = reasonOptions.find(opt => opt.value === reason)
  return option?.label || reason
}

// Transform inventory items for select component
const availableProducts = computed(() => {
  if (!inventoryItems.value || inventoryItems.value.length === 0) {
    return []
  }

  const addedIds = form.items.map(item => item.inventory_item_id)

  return inventoryItems.value
    .filter(item => !addedIds.includes(item.id) && item.quantity_available > 0)
    .map(item => ({
      id: item.id,
      productId: item.product_id,
      variationId: item.variation_id,
      productName: item.product?.product_name || 'Unknown Product',
      sku: item.product?.sku || 'N/A',
      stock: item.quantity_available || 0,
      binCode: item.bin_code,
      unitCost: parseFloat(item.unit_cost || item.average_cost || 0),
      displayName: `${item.product?.product_name || 'Unknown'} (Stock: ${item.quantity_available || 0})`,
      original: item
    }))
})

// Selected product
const selectedProduct = computed(() => {
  if (!newItem.inventory_item_id) return null
  return availableProducts.value.find(item => item.id === newItem.inventory_item_id)
})

// Selected product stock
const selectedProductStock = computed(() => {
  return selectedProduct.value?.stock || 0
})

// Can add item
const canAddItem = computed(() => {
  return newItem.inventory_item_id && 
         newItem.quantity > 0 && 
         newItem.quantity <= selectedProductStock.value
})

// Form validity
const isFormValid = computed(() => {
  return form.issue_type && form.issue_date && form.items.length > 0
})

// Totals
const totalQuantity = computed(() => {
  return form.items.reduce((sum, item) => sum + item.quantity, 0)
})

const totalValue = computed(() => {
  return form.items.reduce((sum, item) => {
    const inventoryItem = inventoryItems.value.find(i => i.id === item.inventory_item_id)
    const unitCost = parseFloat(inventoryItem?.unit_cost || inventoryItem?.average_cost || 0)
    return sum + (item.quantity * unitCost)
  }, 0)
})

const averageCost = computed(() => {
  if (totalQuantity.value === 0) return 0
  return totalValue.value / totalQuantity.value
})

const itemsWithStock = computed(() => {
  return form.items.filter(item => {
    const inventoryItem = inventoryItems.value.find(i => i.id === item.inventory_item_id)
    return inventoryItem && inventoryItem.quantity_available > 0
  }).length
})

// Helper functions
const getStockSeverityValue = (stock: number) => {
  if (stock === 0) return 'danger'
  if (stock < 10) return 'warning'
  return 'success'
}

const getStockClass = (stock: number) => {
  if (stock === 0) return 'text-red-600 font-semibold'
  if (stock < 10) return 'text-orange-600 font-semibold'
  return 'text-green-600 font-semibold'
}

const getProductName = (inventoryItemId: number) => {
  const item = inventoryItems.value.find(i => i.id === inventoryItemId)
  return item?.product?.product_name || `Item #${inventoryItemId}`
}

const getProductSku = (inventoryItemId: number) => {
  const item = inventoryItems.value.find(i => i.id === inventoryItemId)
  return item?.product?.sku || ''
}

const getItemUnitCost = (item: any) => {
  const inventoryItem = inventoryItems.value.find(i => i.id === item.inventory_item_id)
  return parseFloat(inventoryItem?.unit_cost || inventoryItem?.average_cost || 0)
}

const getItemTotal = (item: any) => {
  return item.quantity * getItemUnitCost(item)
}

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(value)
}

// Load inventory items
const loadInventoryItems = async () => {
  inventoryItemsLoading.value = true
  try {
    const params: any = {
      per_page: 1000
    }

    const response = await axios.get('/api/inventory/items', { params })

    if (response.data?.success && Array.isArray(response.data.data)) {
      inventoryItems.value = response.data.data
    } else if (Array.isArray(response.data)) {
      inventoryItems.value = response.data
    } else {
      inventoryItems.value = []
    }

    // Show message if no items with stock
    const itemsWithStock = inventoryItems.value.filter(item => item.quantity_available > 0)
    if (itemsWithStock.length === 0) {
      toast.add({
        severity: 'info',
        summary: 'No Stock',
        detail: 'No items with available stock found',
        life: 3000
      })
    }
  } catch (error: any) {
    console.error('Failed to load inventory items', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load products',
      life: 3000
    })
    inventoryItems.value = []
  } finally {
    inventoryItemsLoading.value = false
  }
}

// Add item
const addItem = () => {
  if (!canAddItem.value) return

  form.items.push({
    inventory_item_id: newItem.inventory_item_id!,
    quantity: newItem.quantity,
    reason: newItem.reason || null,
    remarks: newItem.remarks || null
  })

  toast.add({
    severity: 'success',
    summary: 'Item Added',
    detail: `${getProductName(newItem.inventory_item_id!)} has been added to the issue`,
    life: 2000
  })

  // Reset new item form
  newItem.inventory_item_id = null
  newItem.quantity = 1
  newItem.reason = ''
  newItem.remarks = ''
}

// Remove item
const removeItem = (index: number) => {
  form.items.splice(index, 1)

  toast.add({
    severity: 'info',
    summary: 'Item Removed',
    detail: 'Item has been removed from the issue',
    life: 2000
  })
}

// Submit form
const submitIssue = async () => {
  // Validate at least one item
  if (form.items.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Please add at least one item to issue',
      life: 3000
    })
    return
  }

  // Validate quantities don't exceed available stock
  const exceededItems = form.items.some(item => {
    const inventoryItem = inventoryItems.value.find(i => i.id === item.inventory_item_id)
    return inventoryItem && item.quantity > inventoryItem.quantity_available
  })

  if (exceededItems) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Quantity cannot exceed available stock',
      life: 3000
    })
    return
  }

  submitting.value = true
  errors.value = {}

  // Prepare data for API
  const submitData = {
    issue_type: form.issue_type,
    issue_date: form.issue_date.toISOString().split('T')[0],
    reference_number: form.reference_number || null,
    description: form.description || null,
    remarks: form.remarks || null,
    items: form.items
  }

  try {
    const response = await axios.post('/api/inventory/issues', submitData)

    if (response.data?.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock issue created successfully',
        life: 3000
      })
      router.push({ name: 'inventory.stock-issues' })
    }
  } catch (error: any) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please check the form for errors',
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to create stock issue',
        life: 3000
      })
    }
  } finally {
    submitting.value = false
  }
}

// Cancel handlers
const cancel = () => {
  if (form.items.length > 0 || form.description || form.remarks || form.issue_type || form.reference_number) {
    showCancelDialog.value = true
  } else {
    router.push({ name: 'inventory.stock-issues' })
  }
}

const confirmCancel = () => {
  showCancelDialog.value = false
  router.push({ name: 'inventory.stock-issues' })
}

// Watch for product selection
watch(() => newItem.inventory_item_id, (newVal) => {
  if (newVal && selectedProduct.value) {
    const product = selectedProduct.value
    newItem.quantity = Math.min(1, product.stock)
    
    if (product.stock === 0) {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'This item is out of stock',
        life: 4000
      })
    }
  }
})

// Lifecycle
onMounted(() => {
  loadInventoryItems()
})
</script>