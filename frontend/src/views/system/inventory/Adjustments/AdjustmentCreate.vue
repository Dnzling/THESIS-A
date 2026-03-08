<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.adjustments' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create Stock Adjustment</h2>
        <p class="text-sm text-gray-500 mt-1">Perform physical count or stock correction</p>
      </div>
    </div>
  
    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitAdjustment">
          <!-- Header Section -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Branch <span class="text-red-500">*</span>
              </label>
              <Select 
                v-model="form.branch_id" 
                :options="branches" 
                optionLabel="name" 
                optionValue="id"
                placeholder="Select branch" 
                :loading="loadingBranches" 
                @change="onBranchChange"
                :class="{ 'p-invalid': errors.branch_id }"
                fluid
              />
              <small v-if="errors.branch_id" class="text-red-500">{{ errors.branch_id }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Adjustment Type <span class="text-red-500">*</span>
              </label>
              <Select 
                v-model="form.type" 
                :options="adjustmentTypeOptions" 
                optionLabel="label" 
                optionValue="value"
                placeholder="Select adjustment type" 
                :class="{ 'p-invalid': errors.type }"
                fluid
              />
              <small v-if="errors.type" class="text-red-500">{{ errors.type }}</small>
            </div>
  
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Adjustment Date <span class="text-red-500">*</span>
              </label>
              <DatePicker 
                v-model="form.adjustment_date" 
                dateFormat="yy-mm-dd" 
                class="w-full" 
                :maxDate="new Date()"
                :class="{ 'p-invalid': errors.adjustment_date }"
                fluid
              />
              <small v-if="errors.adjustment_date" class="text-red-500">{{ errors.adjustment_date }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Reason <span class="text-red-500">*</span>
              </label>
              <Select 
                v-model="form.reason" 
                :options="reasonOptions" 
                optionLabel="label" 
                optionValue="value"
                placeholder="Select reason" 
                :class="{ 'p-invalid': errors.reason }"
                fluid
              />
              <small v-if="errors.reason" class="text-red-500">{{ errors.reason }}</small>
            </div>
          </div>
  
          <!-- Items Section -->
          <Divider>
            <span class="text-sm font-semibold text-gray-600">Adjustment Items</span>
          </Divider>
  
          <!-- Add Item Form -->
          <div class="bg-gray-50 p-4 rounded-lg space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">Add Item to Adjustment</h3>
  
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
              <div class="flex flex-col gap-2 md:col-span-4">
                <label class="text-sm text-gray-600">Product <span class="text-red-500">*</span></label>
                <Select 
                  v-model="newItem.inventory_item_id" 
                  :options="productOptions" 
                  optionLabel="displayName"
                  optionValue="id" 
                  placeholder="Select product..." 
                  :loading="loadingProducts" 
                  filter 
                  showClear
                  fluid
                >
                  <template #option="{ option }">
                    <div class="flex flex-col">
                      <span class="font-medium">{{ option.productName }}</span>
                      <span class="text-xs text-gray-500">
                        SKU: {{ option.sku }} | Stock: {{ option.stock }}
                      </span>
                    </div>
                  </template>
                  <template #empty>
                    <div class="p-2 text-center text-gray-500">
                      No products available for this branch
                    </div>
                  </template>
                </Select>
              </div>
  
              <div class="flex flex-col gap-2 md:col-span-2">
                <label class="text-sm text-gray-600">Adjustment Type <span class="text-red-500">*</span></label>
                <Select 
                  v-model="newItem.adjustment_type" 
                  :options="adjustmentTypes" 
                  optionLabel="label" 
                  optionValue="value"
                  placeholder="Type"
                  fluid
                />
              </div>
  
              <div class="flex flex-col gap-2 md:col-span-2">
                <label class="text-sm text-gray-600">System Qty</label>
                <InputNumber 
                  v-model="newItem.system_quantity" 
                  :min="0" 
                  disabled 
                  class="w-full bg-gray-100"
                  fluid
                />
              </div>
  
              <div class="flex flex-col gap-2 md:col-span-2">
                <label class="text-sm text-gray-600">Actual Qty <span class="text-red-500">*</span></label>
                <InputNumber 
                  v-model="newItem.quantity" 
                  :min="0" 
                  showButtons 
                  buttonLayout="horizontal"
                  class="w-full"
                  fluid
                />
              </div>
  
              <div class="flex flex-col gap-2 justify-end md:col-span-2">
                <Button 
                  icon="pi pi-plus" 
                  label="Add" 
                  @click="addItem" 
                  :disabled="!canAddItem" 
                  class="mt-6 w-full"
                  fluid
                />
              </div>
            </div>
  
            <!-- Stock Info -->
            <div v-if="selectedProduct" class="text-sm bg-white p-3 rounded border border-blue-200">
              <div class="flex justify-between items-center">
                <div>
                  <span class="font-medium">{{ selectedProduct.productName }}</span>
                  <span class="text-xs text-gray-500 ml-2">SKU: {{ selectedProduct.sku }}</span>
                </div>
                <span class="font-medium">
                  Current Stock: <span class="text-blue-600">{{ selectedProduct.stock }}</span> units
                </span>
              </div>
              <div v-if="newItem.adjustment_type === 'deduct' && selectedProduct.stock < newItem.quantity" 
                   class="mt-2 text-amber-600 text-xs">
                <i class="pi pi-exclamation-triangle mr-1"></i>
                Warning: Deducting more than current stock will result in negative inventory
              </div>
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
  
            <Column header="Product" style="width: 25%">
              <template #body="{ data }">
                <div class="flex flex-col">
                  <span class="font-medium">{{ getProductName(data.inventory_item_id) }}</span>
                  <span class="text-xs text-gray-500">SKU: {{ getProductSku(data.inventory_item_id) }}</span>
                </div>
              </template>
            </Column>
  
            <Column field="adjustment_type" header="Type" style="width: 10%">
              <template #body="{ data }">
                <Tag 
                  :severity="data.adjustment_type === 'add' ? 'success' : 'danger'"
                  :value="data.adjustment_type === 'add' ? 'ADD' : 'DEDUCT'" 
                />
              </template>
            </Column>
  
            <Column header="System Qty" style="width: 10%">
              <template #body="{ data }">
                {{ getCurrentStock(data.inventory_item_id) }}
              </template>
            </Column>
  
            <Column field="quantity" header="Actual Qty" style="width: 10%" />
  
            <Column header="New Qty" style="width: 10%">
              <template #body="{ data }">
                <span :class="{
                  'text-green-600 font-medium': data.adjustment_type === 'add',
                  'text-red-600 font-medium': data.adjustment_type === 'deduct'
                }">
                  {{ calculateNewQuantity(data) }}
                </span>
              </template>
            </Column>
  
            <Column header="Variance" style="width: 10%">
              <template #body="{ data }">
                <span :class="{
                  'text-green-600': data.quantity > getCurrentStock(data.inventory_item_id),
                  'text-red-600': data.quantity < getCurrentStock(data.inventory_item_id),
                  'text-gray-600': data.quantity === getCurrentStock(data.inventory_item_id)
                }">
                  {{ data.quantity - getCurrentStock(data.inventory_item_id) }}
                </span>
              </template>
            </Column>
  
            <Column field="remarks" header="Notes" style="width: 15%">
              <template #body="{ data }">
                {{ data.remarks || '-' }}
              </template>
            </Column>
  
            <Column header="Actions" style="width: 10%">
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
  
          <!-- Remarks -->
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Additional Remarks</label>
            <Textarea 
              v-model="form.remarks" 
              rows="2" 
              placeholder="Any additional notes about this adjustment..."
              fluid
            />
          </div>
  
          <!-- Form Actions -->
          <div class="pt-4 flex gap-2 justify-end border-t border-gray-200">
            <Button 
              label="Cancel" 
              severity="secondary" 
              outlined 
              type="button" 
              @click="cancel"
              fluid
            />
            <Button 
              label="Save as Draft" 
              severity="secondary" 
              type="button" 
              :loading="savingDraft" 
              @click="saveDraft"
              :disabled="form.items.length === 0"
              fluid
            />
            <Button 
              label="Submit Adjustment" 
              icon="pi pi-check" 
              :loading="submitting" 
              type="submit" 
              :disabled="!isFormValid"
              fluid
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
import inventoryService from '../../../../services/inventory.service'

const router = useRouter()
const toast = useToast()

// State
const savingDraft = ref(false)
const submitting = ref(false)
const loadingBranches = ref(false)
const loadingProducts = ref(false)
const showCancelDialog = ref(false)

// Form data - Updated to match backend expectations
const form = reactive({
  branch_id: null as number | null,
  type: '' as string,
  adjustment_date: new Date(),
  reason: '' as string,
  remarks: '',
  items: [] as Array<{
    inventory_item_id: number
    adjustment_type: 'add' | 'deduct'
    quantity: number
    remarks?: string
  }>
})

// New item form
const newItem = reactive({
  inventory_item_id: null as number | null,
  adjustment_type: 'add' as 'add' | 'deduct',
  quantity: 1,
  system_quantity: 0,
  remarks: ''
})

// Validation errors - Updated with type
const errors = ref({
  adjustment_date: '',
  branch_id: '',
  type: '',
  reason: ''
})

// Data from API
const branches = ref<any[]>([])
const inventoryItems = ref<any[]>([])

// Options
const adjustmentTypeOptions = [
  { label: 'Physical Count', value: 'physical_count' },
  { label: 'Cycle Count', value: 'cycle_count' },
  { label: 'Spot Check', value: 'spot_check' },
  { label: 'Damage', value: 'damage' },
  { label: 'Loss', value: 'loss' },
  { label: 'Found', value: 'found' },
  { label: 'Correction', value: 'correction' },
  { label: 'Write Off', value: 'writeoff' }
]

const reasonOptions = [
  { label: 'Physical Count Correction', value: 'physical_count' },
  { label: 'Damaged Goods', value: 'damaged' },
  { label: 'Expired Items', value: 'expired' },
  { label: 'Theft/Loss', value: 'theft' },
  { label: 'Wrong Delivery', value: 'wrong_delivery' },
  { label: 'Quality Control', value: 'quality_control' },
  { label: 'Sample/Demo Usage', value: 'sample' },
  { label: 'Other', value: 'other' }
]

const adjustmentTypes = [
  { label: 'Add Stock', value: 'add' },
  { label: 'Deduct Stock', value: 'deduct' }
]

// Computed
// Transform inventory items into flat structure for Select component
const productOptions = computed(() => {
  if (!inventoryItems.value || inventoryItems.value.length === 0) {
    return []
  }
  
  const addedIds = form.items.map(item => item.inventory_item_id)
  
  return inventoryItems.value
    .filter(item => !addedIds.includes(item.id))
    .map(item => ({
      id: item.id,
      productId: item.product_id,
      variationId: item.variation_id,
      productName: item.product?.product_name || 'Unknown Product',
      sku: item.product?.sku || 'N/A',
      stock: item.quantity_on_hand || 0,
      displayName: `${item.product?.product_name || 'Unknown'} (Stock: ${item.quantity_on_hand || 0})`,
      // Keep original data for reference
      original: item
    }))
})

const selectedProduct = computed(() => {
  if (!newItem.inventory_item_id) return null
  const product = productOptions.value.find(item => item.id === newItem.inventory_item_id)
  
  // Update system quantity when product changes
  if (product && product.stock !== newItem.system_quantity) {
    newItem.system_quantity = product.stock
  }
  
  return product
})

const canAddItem = computed(() => {
  return newItem.inventory_item_id && newItem.quantity > 0
})

const isFormValid = computed(() => {
  return (
    form.branch_id &&
    form.type &&
    form.adjustment_date &&
    form.reason &&
    form.items.length > 0
  )
})

// Methods
const loadBranches = async () => {
  loadingBranches.value = true
  try {
    const response = await inventoryService.getBranches()
    branches.value = response.data?.data || response.data || []
  } catch (error) {
    console.error('Failed to load branches:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load branches',
      life: 3000
    })
  } finally {
    loadingBranches.value = false
  }
}

const loadInventoryItems = async () => {
  if (!form.branch_id) {
    inventoryItems.value = []
    return
  }

  loadingProducts.value = true
  try {
    const response = await inventoryService.getBranchInventory(form.branch_id, { per_page: 100 })
    
    // Handle nested data structure properly
    if (response.data?.success === true && response.data?.data) {
      inventoryItems.value = response.data.data
    } else if (response.data?.data) {
      inventoryItems.value = response.data.data
    } else if (Array.isArray(response.data)) {
      inventoryItems.value = response.data
    } else {
      inventoryItems.value = []
    }

    if (inventoryItems.value.length === 0) {
      toast.add({
        severity: 'info',
        summary: 'No Items',
        detail: 'No inventory items found for this branch',
        life: 3000
      })
    }
  } catch (error) {
    console.error('Failed to load inventory items:', error)
    inventoryItems.value = []
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load products',
      life: 3000
    })
  } finally {
    loadingProducts.value = false
  }
}

const onBranchChange = () => {
  // Reset products and items when branch changes
  inventoryItems.value = []
  form.items = []
  newItem.inventory_item_id = null
  newItem.system_quantity = 0

  if (form.branch_id) {
    loadInventoryItems()
  }
}

const getProductName = (inventoryItemId: number) => {
  if (!inventoryItems.value || inventoryItems.value.length === 0) return `Item #${inventoryItemId}`
  const item = inventoryItems.value.find(i => i.id === inventoryItemId)
  return item?.product?.product_name || `Item #${inventoryItemId}`
}

const getProductSku = (inventoryItemId: number) => {
  if (!inventoryItems.value || inventoryItems.value.length === 0) return ''
  const item = inventoryItems.value.find(i => i.id === inventoryItemId)
  return item?.product?.sku || ''
}

const getCurrentStock = (inventoryItemId: number) => {
  if (!inventoryItems.value || inventoryItems.value.length === 0) return 0
  const item = inventoryItems.value.find(i => i.id === inventoryItemId)
  return item?.quantity_on_hand || 0
}

const calculateNewQuantity = (item: any) => {
  const currentStock = getCurrentStock(item.inventory_item_id)
  if (item.adjustment_type === 'add') {
    return currentStock + item.quantity
  } else {
    return currentStock - item.quantity
  }
}

const addItem = () => {
  if (!canAddItem.value) return

  form.items.push({
    inventory_item_id: newItem.inventory_item_id!,
    adjustment_type: newItem.adjustment_type,
    quantity: newItem.quantity,
    remarks: newItem.remarks || undefined
  })

  toast.add({
    severity: 'success',
    summary: 'Item Added',
    detail: `${getProductName(newItem.inventory_item_id!)} has been added to the adjustment`,
    life: 2000
  })

  // Reset new item form
  newItem.inventory_item_id = null
  newItem.adjustment_type = 'add'
  newItem.quantity = 1
  newItem.system_quantity = 0
  newItem.remarks = ''
}

const removeItem = (index: number) => {
  form.items.splice(index, 1)

  toast.add({
    severity: 'info',
    summary: 'Item Removed',
    detail: `Item has been removed from the adjustment`,
    life: 2000
  })
}

const validateForm = () => {
  let isValid = true
  errors.value = { adjustment_date: '', branch_id: '', type: '', reason: '' }

  if (!form.adjustment_date) {
    errors.value.adjustment_date = 'Adjustment date is required'
    isValid = false
  }

  if (!form.branch_id) {
    errors.value.branch_id = 'Branch is required'
    isValid = false
  }

  if (!form.type) {
    errors.value.type = 'Adjustment type is required'
    isValid = false
  }

  if (!form.reason) {
    errors.value.reason = 'Reason is required'
    isValid = false
  }

  if (form.items.length === 0) {
    toast.add({
      severity: 'warn',
      summary: 'No Items',
      detail: 'Please add at least one item to adjust',
      life: 3000
    })
    isValid = false
  }

  return isValid
}

const toDateString = (value: Date | null) => {
  if (!value) return ''
  return value.toISOString().split('T')[0]
}

const saveAdjustment = async (submit: boolean) => {
  if (!validateForm()) return

  if (submit) {
    submitting.value = true
  } else {
    savingDraft.value = true
  }

  try {
    // Prepare data to match backend validation
    const adjustmentData = {
      branch_id: form.branch_id,
      type: form.type,
      reason: form.reason,
      adjustment_date: toDateString(form.adjustment_date),
      remarks: form.remarks || undefined,
      items: form.items.map(item => {
        // Find the original inventory item to get product_id and variation_id
        const inventoryItem = inventoryItems.value.find(i => i.id === item.inventory_item_id)
        
        if (!inventoryItem) {
          throw new Error(`Inventory item ${item.inventory_item_id} not found`)
        }
        
        return {
          product_id: inventoryItem.product_id,
          variation_id: inventoryItem.variation_id,
          system_quantity: getCurrentStock(item.inventory_item_id),
          actual_quantity: item.quantity,
          notes: item.remarks || undefined
        }
      })
    }

    const response = await inventoryService.createAdjustment(adjustmentData)
    const adjustmentId = response.data?.id || response.data?.data?.id

    if (!adjustmentId) {
      throw new Error('No adjustment ID returned')
    }

    toast.add({
      severity: 'success',
      summary: !submit ? 'Draft Saved' : 'Adjustment Created',
      detail: `Adjustment #${adjustmentId} has been created successfully`,
      life: 3000
    })

    router.push({ name: 'inventory.adjustments' })

  } catch (error: any) {
    console.error('Failed to save adjustment:', error)
    
    // Show detailed validation errors
    if (error.response?.data?.errors) {
      const validationErrors = error.response.data.errors
      Object.keys(validationErrors).forEach(key => {
        toast.add({
          severity: 'error',
          summary: 'Validation Error',
          detail: `${key}: ${validationErrors[key].join(', ')}`,
          life: 5000
        })
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to save adjustment',
        life: 5000
      })
    }
  } finally {
    savingDraft.value = false
    submitting.value = false
  }
}

const saveDraft = async () => {
  await saveAdjustment(false)
}

const submitAdjustment = async () => {
  await saveAdjustment(true)
}

const cancel = () => {
  if (form.items.length > 0 || form.reason || form.remarks || form.type) {
    showCancelDialog.value = true
  } else {
    router.push({ name: 'inventory.adjustments' })
  }
}

const confirmCancel = () => {
  showCancelDialog.value = false
  router.push({ name: 'inventory.adjustments' })
}

// Watch for product selection to show warnings
watch(() => newItem.inventory_item_id, (newVal) => {
  if (newVal && selectedProduct.value) {
    const product = selectedProduct.value
    if (product.stock === 0 && newItem.adjustment_type === 'deduct') {
      toast.add({
        severity: 'warn',
        summary: 'Warning',
        detail: 'This item is currently out of stock. Deducting may result in negative inventory.',
        life: 4000
      })
    }
  }
})

// Lifecycle
onMounted(() => {
  loadBranches()
})
</script>