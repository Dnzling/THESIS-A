<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <ConfirmDialog />
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.transfers' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create Stock Transfer</h2>
        <p class="text-sm text-gray-500 mt-1">Prepare transfer request between branches</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitTransfer">
          <!-- Header Section -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                From Branch <span class="text-red-500">*</span>
              </label>
              <div>
                <Skeleton v-if="autoFillBranchLoading" height="2.5rem" />
                <Select 
                  v-else
                  v-model="form.from_branch_id" 
                  :options="branches" 
                  optionLabel="name" 
                  optionValue="id" 
                  placeholder="Select source branch"
                  :loading="loadingBranches"
                  :disabled="true" fluid
                  @change="onFromBranchChange"
                  :class="{ 'p-invalid': errors.from_branch_id }"
                />
              </div>
              <small class="text-gray-500">Auto-filled from your assigned branch</small>
              <small v-if="errors.from_branch_id" class="text-red-500">{{ errors.from_branch_id }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                To Branch <span class="text-red-500">*</span>
              </label>
              <Select 
                v-model="form.to_branch_id" 
                :options="toBranchOptions" 
                optionLabel="name" 
                optionValue="id" 
                placeholder="Select destination branch"
                :disabled="!form.from_branch_id"
                :class="{ 'p-invalid': errors.to_branch_id }"
              />
              <small v-if="errors.to_branch_id" class="text-red-500">{{ errors.to_branch_id }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Expected Receive Date <span class="text-red-500">*</span>
              </label>
              <DatePicker 
                v-model="form.expected_receive_date" 
                dateFormat="yy-mm-dd" 
                fluid showIcon
                :minDate="new Date()"
                :class="{ 'p-invalid': errors.expected_receive_date }"
              />
              <small v-if="errors.expected_receive_date" class="text-red-500">{{ errors.expected_receive_date }}</small>
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">
              Transfer Reason <span class="text-red-500">*</span>
            </label>
            <InputText
              v-model="form.reason"
              placeholder="e.g. Branch replenishment"
              :class="{ 'p-invalid': errors.reason }"
            />
            <small v-if="errors.reason" class="text-red-500">{{ errors.reason }}</small>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Remarks</label>
            <Textarea 
              v-model="form.remarks" 
              rows="2" 
              placeholder="Add any additional notes about this transfer..."
            />
          </div>

          <!-- Items Section -->
          <Divider>
            <span class="text-sm font-semibold text-gray-600">Transfer Items</span>
          </Divider>

          <!-- Add Item Form -->
          <div class="bg-gray-50 p-4 rounded-lg space-y-4">
            <h3 class="text-sm font-semibold text-gray-700">Add Transfer Item</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
              <div class="flex flex-col gap-2 md:col-span-5">
                <label class="text-sm text-gray-600">Product <span class="text-red-500">*</span></label>
                <Select 
                  v-model="newItem.inventory_item_id" 
                  :options="availableProducts" 
                  optionLabel="product.product_name" 
                  optionValue="id" 
                  placeholder="Select product..."
                  :loading="loadingProducts"
                  filter
                  showClear
                >
                  <template #option="{ option }">
                    <div class="flex flex-col">
                      <span class="font-medium">{{ option.product?.product_name || 'Unknown' }}</span>
                      <span class="text-xs text-gray-500">
                        SKU: {{ option.product?.sku || 'N/A' }} | Available: {{ option.quantity_on_hand || 0 }}
                      </span>
                    </div>
                  </template>
                </Select>
              </div>

              <div class="flex flex-col gap-2 md:col-span-3">
                <label class="text-sm text-gray-600">Quantity <span class="text-red-500">*</span></label>
                <InputNumber 
                  v-model="newItem.quantity" 
                  :min="1" 
                  :max="selectedProduct?.quantity_on_hand || 999999"
                  showButtons
                  buttonLayout="horizontal"
                 fluid
                />
              </div>

              <div class="flex flex-col gap-2 md:col-span-2">
                <label class="text-sm text-gray-600">Notes</label>
                <InputText 
                  v-model="newItem.notes" 
                  placeholder="Optional notes"
                />
              </div>

              <div class="flex flex-col gap-2 justify-end md:col-span-2">
                <Button 
                  icon="pi pi-plus" 
                  label="Add" 
                  @click="addItem" 
                  :disabled="!canAddItem"
                  class="mt-6 w-full"
                />
              </div>
            </div>

            <!-- Available Stock Info -->
            <div v-if="selectedProduct" class="text-sm bg-white p-3 rounded border border-blue-200">
              <div class="flex justify-between items-center">
                <div>
                  <span class="font-medium">{{ selectedProduct.product?.product_name }}</span>
                  <span class="text-xs text-gray-500 ml-2">SKU: {{ selectedProduct.product?.sku }}</span>
                </div>
                <span :class="{
                  'text-green-600 font-medium': selectedProduct.quantity_on_hand > 0,
                  'text-red-600 font-medium': selectedProduct.quantity_on_hand === 0
                }">
                  Available: {{ selectedProduct.quantity_on_hand || 0 }} units
                </span>
              </div>
              <div v-if="newItem.quantity > (selectedProduct?.quantity_on_hand || 0)" 
                   class="mt-2 text-amber-600 text-xs">
                <i class="pi pi-exclamation-triangle mr-1"></i>
                Warning: Requested quantity exceeds available stock
              </div>
            </div>
          </div>

          <!-- Items Table -->
          <DataTable 
            :value="form.items" 
            class="p-datatable-sm" 
            stripedRows
            showGridlines
          >
            <template #empty>
              <div class="text-center py-8 text-gray-500">
                <i class="pi pi-inbox text-4xl mb-2"></i>
                <p>No items added yet. Add items using the form above.</p>
              </div>
            </template>

            <Column header="Product" style="width: 40%">
              <template #body="{ data }">
                <div class="flex flex-col">
                  <span class="font-medium">{{ getProductName(data.inventory_item_id) }}</span>
                  <span class="text-xs text-gray-500">SKU: {{ getProductSku(data.inventory_item_id) }}</span>
                </div>
              </template>
            </Column>
            
            <Column field="quantity" header="Quantity" style="width: 15%" />
            
            <Column field="notes" header="Notes" style="width: 25%">
              <template #body="{ data }">
                {{ data.notes || '-' }}
              </template>
            </Column>

            <Column header="Actions" style="width: 20%">
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

          <!-- Summary -->
          <div v-if="form.items.length > 0" class="bg-gray-50 p-4 rounded-lg">
            <div class="flex justify-between items-center">
              <span class="font-semibold">Total Items:</span>
              <span>{{ form.items.length }}</span>
            </div>
            <div class="flex justify-between items-center mt-2">
              <span class="font-semibold">Total Quantity:</span>
              <span>{{ totalQuantity }}</span>
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
              label="Submit Transfer" 
              icon="pi pi-check" 
              :loading="saving" 
              type="submit"
              :disabled="!isFormValid"
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
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'
import { useAuthStore } from '../../../../stores/auth'
import axios from 'axios'

const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const saving = ref(false)
const loadingBranches = ref(false)
const loadingProducts = ref(false)
const autoFillBranchLoading = ref(true)
const showCancelDialog = ref(false)
const authStore = useAuthStore()

// Form state
const form = reactive({
  from_branch_id: null as number | null,
  to_branch_id: null as number | null,
  transfer_date: new Date(),
  expected_receive_date: null as Date | null,
  reason: 'Branch replenishment',
  remarks: '',
  items: [] as Array<{
    inventory_item_id: number
    quantity: number
    notes?: string
  }>
})

// New item form
const newItem = reactive({
  inventory_item_id: null as number | null,
  quantity: 1,
  notes: ''
})

// Validation errors
const errors = ref({
  from_branch_id: '',
  to_branch_id: '',
  expected_receive_date: '',
  reason: ''
})

// API data
const branches = ref<any[]>([])
const inventoryItems = ref<any[]>([])

// Computed
const toBranchOptions = computed(() => {
  return branches.value.filter(b => b.id !== form.from_branch_id)
})

const availableProducts = computed(() => {
  if (!form.from_branch_id) return []
  // Filter products from source branch and not already added
  const addedIds = form.items.map(item => item.inventory_item_id)
  return inventoryItems.value.filter(p => !addedIds.includes(p.id))
})

const selectedProduct = computed(() => {
  if (!newItem.inventory_item_id) return null
  return inventoryItems.value.find(p => p.id === newItem.inventory_item_id)
})

const canAddItem = computed(() => {
  if (!newItem.inventory_item_id || newItem.quantity <= 0) return false
  if (!selectedProduct.value) return false
  return newItem.quantity <= Number(selectedProduct.value.quantity_on_hand || 0)
})

const isFormValid = computed(() => {
  return (
    form.from_branch_id &&
    form.to_branch_id &&
    form.expected_receive_date &&
    form.reason.trim().length > 0 &&
    form.items.length > 0
  )
})

const totalQuantity = computed(() => {
  return form.items.reduce((sum, item) => sum + item.quantity, 0)
})

const resolveAssignedBranchId = (): number => {
  const user: any = authStore.user || {}
  const candidate = Number(
    user.branch_id ??
    user.branch?.id ??
    user.employee?.branch_id ??
    user.employee_branch_id ??
    0
  )
  return Number.isFinite(candidate) ? candidate : 0
}

const resolveAssignedBranchIdFromEmployee = async (): Promise<number> => {
  try {
    const response = await axios.get('/api/employees/me')
    const payload: any = response?.data || {}
    const data = payload?.data || payload
    const candidate = Number(
      data?.branch_id ??
      data?.employee?.branch_id ??
      data?.branch?.id ??
      0
    )
    return Number.isFinite(candidate) ? candidate : 0
  } catch {
    return 0
  }
}

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

const onFromBranchChange = async () => {
  form.to_branch_id = null
  form.items = [] // Clear items when source branch changes
  inventoryItems.value = []
  newItem.inventory_item_id = null
  
  if (form.from_branch_id) {
    await loadInventoryForBranch(form.from_branch_id)
  }
}

const loadInventoryForBranch = async (branchId: number | null) => {
  if (!branchId) return
  
  loadingProducts.value = true
  try {
    const response = await inventoryService.getBranchInventory(branchId, { per_page: 100 })
    inventoryItems.value = response.data?.data || response.data || []
    
    if (inventoryItems.value.length === 0) {
      toast.add({
        severity: 'info',
        summary: 'No Items',
        detail: 'No inventory items found for this branch',
        life: 3000
      })
    }
  } catch (error) {
    console.error('Failed to load inventory:', error)
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

const getProductName = (inventoryItemId: number): string => {
  const item = inventoryItems.value.find(p => p.id === inventoryItemId)
  return item?.product?.product_name || `Item #${inventoryItemId}`
}

const getProductSku = (inventoryItemId: number): string => {
  const item = inventoryItems.value.find(p => p.id === inventoryItemId)
  return item?.product?.sku || '-'
}

const addItem = () => {
  if (!canAddItem.value) return
  
  form.items.push({
    inventory_item_id: newItem.inventory_item_id!,
    quantity: newItem.quantity,
    notes: newItem.notes || undefined
  })

  toast.add({
    severity: 'success',
    summary: 'Item Added',
    detail: `${getProductName(newItem.inventory_item_id!)} added to transfer`,
    life: 2000
  })

  // Reset form
  newItem.inventory_item_id = null
  newItem.quantity = 1
  newItem.notes = ''
}

const removeItem = (index: number) => {
  form.items.splice(index, 1)
  
  toast.add({
    severity: 'info',
    summary: 'Item Removed',
    detail: 'Item removed from transfer',
    life: 2000
  })
}

const validateForm = (): boolean => {
  errors.value = { from_branch_id: '', to_branch_id: '', expected_receive_date: '', reason: '' }
  
  let isValid = true
  
  if (!form.from_branch_id) {
    errors.value.from_branch_id = 'From branch is required'
    isValid = false
  }
  
  if (!form.to_branch_id) {
    errors.value.to_branch_id = 'To branch is required'
    isValid = false
  }

  if (form.from_branch_id === form.to_branch_id) {
    errors.value.to_branch_id = 'From and To branches must be different'
    isValid = false
  }

  if (!form.expected_receive_date) {
    errors.value.expected_receive_date = 'Expected receive date is required'
    isValid = false
  }

  if (!form.reason.trim()) {
    errors.value.reason = 'Reason is required'
    isValid = false
  }

  return isValid
}

const doCreateTransfer = async () => {
  saving.value = true
  try {
    const payload = {
      from_branch_id: form.from_branch_id,
      to_branch_id: form.to_branch_id,
      expected_delivery_date: form.expected_receive_date?.toISOString().split('T')[0],
      reason: form.reason,
      remarks: form.remarks || undefined,
      items: form.items.map(item => {
        const inventoryItem = inventoryItems.value.find(inv => inv.id === item.inventory_item_id)
        return {
          product_id: inventoryItem?.product_id,
          variation_id: inventoryItem?.variation_id || null,
          requested_quantity: item.quantity,
          notes: item.notes
        }
      })
    }

    const response = await inventoryService.createTransfer(payload)
    const transferId = response.data?.id || response.data?.data?.id
    
    toast.add({ 
      severity: 'success', 
      summary: 'Success', 
      detail: `Transfer #${transferId} submitted and is now pending manager approval`,
      life: 3000
    })
    
    router.push({ name: 'inventory.transfers' })
  } catch (error: any) {
    console.error('Failed to create transfer', error)
    const responseData = error?.response?.data || {}
    const validationErrors = responseData?.errors

    let message =
      responseData?.message ||
      responseData?.error ||
      error?.message ||
      'Failed to create transfer'

    if (validationErrors && typeof validationErrors === 'object') {
      const firstEntry = Object.entries(validationErrors)[0]
      if (firstEntry) {
        const [, value] = firstEntry
        const firstError = Array.isArray(value) ? value[0] : String(value)
        message = `${message}: ${firstError}`
      }
    }

    if (!message || message === 'Request failed with status code 500') {
      const raw = typeof responseData === 'string' ? responseData : ''
      message = raw?.slice(0, 220) || `Server error (${error?.response?.status || 500}). Please check backend logs.`
    }

    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: message,
      life: 5000
    })
  } finally {
    saving.value = false
  }
}

const submitTransfer = async () => {
  if (!validateForm()) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Validation Error', 
      detail: 'Please fix validation errors',
      life: 3000
    })
    return
  }

  confirm.require({
    message: 'Submit this transfer now? You can still cancel before manager approval.',
    header: 'Confirm Submit',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      await doCreateTransfer()
    }
  })
}

const cancel = () => {
  if (form.items.length > 0 || form.remarks || form.expected_receive_date) {
    showCancelDialog.value = true
  } else {
    router.push({ name: 'inventory.transfers' })
  }
}

const confirmCancel = () => {
  showCancelDialog.value = false
  router.push({ name: 'inventory.transfers' })
}

// Watch for quantity validation
watch(() => newItem.quantity, (newVal) => {
  if (selectedProduct.value && newVal > selectedProduct.value.quantity_on_hand) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Quantity exceeds available stock',
      life: 3000
    })
  }
})

// Load initial data
onMounted(async () => {
  try {
    autoFillBranchLoading.value = true
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    form.expected_receive_date = tomorrow

    let userBranchId = resolveAssignedBranchId()
    if (!userBranchId) {
      try {
        await authStore.fetchCurrentUser()
        userBranchId = resolveAssignedBranchId()
      } catch {
        // keep fallback behavior below
      }
    }

    if (!userBranchId) {
      userBranchId = await resolveAssignedBranchIdFromEmployee()
    }

    await loadBranches()

    if (userBranchId) {
      form.from_branch_id = userBranchId
      await onFromBranchChange()
    } else if (branches.value.length > 0) {
      // Fallback so form is still usable even if branch is missing in user payload.
      form.from_branch_id = Number(branches.value[0].id)
      await onFromBranchChange()
      toast.add({
        severity: 'warn',
        summary: 'Branch Fallback',
        detail: 'Unable to detect your assigned branch from profile. Using first available branch.',
        life: 4000
      })
    }
  } finally {
    autoFillBranchLoading.value = false
  }
})
</script>
