<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <ConfirmDialog />
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded aria-label="Go back" @click="router.back()" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">{{ damagedPullout ? 'Pull Damaged Stock to Warehouse' : reorderSuggestionId ? 'Request Restock from Warehouse' : 'Create Stock Transfer' }}</h2>
        <p class="text-sm text-gray-500 mt-1">{{ damagedPullout ? 'Move quarantined units to an active warehouse. They remain unavailable for sale after receipt.' : reorderSuggestionId ? 'This grouped request is linked to reorder suggestions. The warehouse can replenish any shortage before approving dispatch.' : 'Request available stock from a warehouse or another branch.' }}</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitTransfer">
          <!-- Header Section -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                From Location <span class="text-red-500">*</span>
              </label>
              <div>
                <Skeleton v-if="autoFillBranchLoading" height="2.5rem" />
                <Select 
                  v-else
                  v-model="form.from_branch_id" 
                  :options="sourceLocationOptions"
                  optionLabel="name" 
                  optionValue="id" 
                  placeholder="Select warehouse or branch"
                  :loading="loadingBranches"
                  :disabled="Boolean(reorderSuggestionId) || damagedPullout"
                  fluid
                  @change="onFromBranchChange"
                  :class="{ 'p-invalid': errors.from_branch_id }"
                />
              </div>
              <small class="text-gray-500">Products and available stock are loaded from this location.</small>
              <small v-if="errors.from_branch_id" class="text-red-500">{{ errors.from_branch_id }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">
                Receiving Location
              </label>
              <div class="flex min-h-10 items-center rounded-md border border-slate-200 bg-slate-50 px-3 text-sm font-medium text-slate-700">
                <Select v-if="damagedPullout" v-model="form.to_branch_id" :options="warehouseOptions" optionLabel="name" optionValue="id" placeholder="Select active warehouse" fluid :loading="loadingBranches" />
                <span v-else>{{ receivingLocation?.name || 'No assigned branch' }}</span>
              </div>
              <small class="text-gray-500">{{ damagedPullout ? 'Damaged units are quarantined at this warehouse.' : 'Your assigned inventory branch receives this request.' }}</small>
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
              {{ damagedPullout ? 'Pull-out Reason' : 'Transfer Reason' }} <span class="text-red-500">*</span>
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

          <Message v-if="reorderRequestNotice" severity="info" :closable="false" class="mb-3">
            {{ reorderRequestNotice }}
          </Message>

          <!-- Add Item Form -->
          <div v-if="!reorderSuggestionId" class="bg-gray-50 p-4 rounded-lg space-y-4">
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
                        {{ option.product?.sku || 'No SKU' }} · {{ damagedPullout ? 'Damaged' : 'Available' }}: {{ sourceQuantity(option).toLocaleString() }} {{ getProductUnit(option.id) }}
                      </span>
                    </div>
                  </template>
                </Select>
              </div>

              <div class="flex flex-col gap-2 md:col-span-3">
              <label class="text-sm text-gray-600">{{ damagedPullout ? 'Damaged quantity' : 'Quantity' }} <span class="text-red-500">*</span></label>
                <InputNumber 
                  v-model="newItem.quantity" 
                  :min="1" 
                  :max="selectedProduct ? sourceQuantity(selectedProduct) : 999999"
                  showButtons
                  buttonLayout="horizontal"
                 fluid
                />
              </div>

              <div class="flex flex-col gap-2 md:col-span-2">
                <label class="text-sm text-gray-600">Unit</label>
                <div class="flex min-h-10 items-center rounded-md border border-slate-200 bg-white px-3 text-sm text-slate-700">
                  {{ selectedProduct ? getProductUnit(selectedProduct.id) : '—' }}
                </div>
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
                  'text-green-600 font-medium': sourceQuantity(selectedProduct) > 0,
                  'text-red-600 font-medium': sourceQuantity(selectedProduct) === 0
                }">
                  {{ damagedPullout ? 'Damaged' : 'Available' }}: {{ sourceQuantity(selectedProduct).toLocaleString() }} {{ getProductUnit(selectedProduct.id) }}
                </span>
              </div>
              <div v-if="newItem.quantity > (selectedProduct ? sourceQuantity(selectedProduct) : 0)"
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
            <Column header="Unit" style="width: 15%">
              <template #body="{ data }">{{ getProductUnit(data.inventory_item_id) }}</template>
            </Column>

            <Column v-if="!reorderSuggestionId" header="Actions" style="width: 20%">
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
          <div v-if="form.items.length > 0" class="ml-auto max-w-md rounded-xl border border-slate-200 bg-white p-4">
            <h3 class="mb-3 text-sm font-semibold text-slate-800">Transfer Rundown</h3>
            <div class="space-y-2 text-sm">
              <div class="flex justify-between"><span class="text-slate-500">Items</span><span>{{ form.items.length }}</span></div>
              <div class="flex justify-between"><span class="text-slate-500">{{ damagedPullout ? 'Damaged units to pull out' : 'Total quantity' }}</span><span>{{ totalQuantity.toLocaleString() }}</span></div>
              <template v-if="!damagedPullout">
                <div class="flex justify-between"><span class="text-slate-500">Goods value</span><span>{{ money(estimate.goods_value) }}</span></div>
                <div class="flex justify-between"><span class="text-slate-500">Route distance</span><span>{{ formatNumber(estimate.distance_km) }} km</span></div>
              </template>
              <div v-if="!damagedPullout" class="flex justify-between"><span class="text-slate-500">Estimated shipping fee</span><span class="font-medium">{{ money(estimate.shipping_fee) }}</span></div>
              <div v-if="!damagedPullout" class="border-t border-slate-200 pt-2 flex justify-between font-semibold text-slate-900"><span>Estimated total transfer value</span><span>{{ money(estimate.total_value) }}</span></div>
              <small v-if="estimating" class="block text-right text-slate-400">Calculating fee…</small>
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

  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted, watch } from 'vue'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '../../../../services/inventory.service'
import { useAuthStore } from '../../../../stores/auth'
import axios from 'axios'
import Message from 'primevue/message'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const saving = ref(false)
const loadingBranches = ref(false)
const loadingProducts = ref(false)
const estimating = ref(false)
let estimateTimer: number | null = null
const autoFillBranchLoading = ref(true)
const reorderRequestNotice = ref('')
const reorderSuggestionIds = computed(() => {
  const plural = route.query.reorder_suggestion_ids
  const value = plural || route.query.reorder_suggestion_id || ''
  const values = Array.isArray(value) ? value : [value]
  return [...new Set(values.flatMap(item => String(item || '').split(',')).map(Number).filter(id => Number.isInteger(id) && id > 0))]
})
const reorderSuggestionId = computed(() => reorderSuggestionIds.value[0] || 0)
const damagedPullout = computed(() => String(route.query.damaged_pullout || '') === '1')
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
const estimate = reactive({ goods_value: 0, distance_km: 0, shipping_fee: 0, total_value: 0, cost_method: 'none' })

// Computed
const receivingLocation = computed(() => branches.value.find(branch => Number(branch.id) === Number(form.to_branch_id)) || null)
const sourceLocationOptions = computed(() => branches.value.filter(branch =>
  Number(branch.id) !== Number(form.to_branch_id)
  && (!damagedPullout.value || Number(branch.id) === Number(form.from_branch_id))
  && (!reorderSuggestionId.value || (String(branch.branch_type || '').toLowerCase() === 'warehouse' && String(branch.status || '').toLowerCase() === 'active'))
))
const warehouseOptions = computed(() => branches.value.filter((branch: any) =>
  String(branch.branch_type || '').toLowerCase() === 'warehouse'
  && String(branch.status || '').toLowerCase() === 'active'
  && Number(branch.id) !== Number(form.from_branch_id)
))
const sourceQuantity = (inventory: any) => Number(damagedPullout.value ? inventory?.quantity_damaged || 0 : inventory?.quantity_available || 0)

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
  return newItem.quantity <= sourceQuantity(selectedProduct.value)
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
const money = (value: unknown) => `₱${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
const formatNumber = (value: unknown) => Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 })

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

const prefillFromReorderSuggestion = async () => {
  if (!reorderSuggestionIds.value.length) return

  try {
    const suggestions = await Promise.all(reorderSuggestionIds.value.map(async id => {
      const response = await inventoryService.getReorderSuggestion(id)
      return response?.data?.data ?? response?.data
    }))
    if (suggestions.some(suggestion => !suggestion?.id || !suggestion?.product_id || !suggestion?.branch_id)) {
      throw new Error('One or more reorder suggestions are missing their product or receiving branch.')
    }

    const receivingBranchIds = new Set(suggestions.map(suggestion => Number(suggestion.branch_id)))
    if (receivingBranchIds.size !== 1) throw new Error('Group suggestions for one receiving branch at a time.')
    const productKeys = suggestions.map(suggestion => `${Number(suggestion.product_id)}:${Number(suggestion.variation_id || 0)}`)
    if (new Set(productKeys).size !== productKeys.length) throw new Error('Select only one pending suggestion per product or variant.')

    form.to_branch_id = Number(suggestions[0].branch_id)
    const warehouses = branches.value.filter((branch: any) =>
      String(branch.branch_type || '').toLowerCase() === 'warehouse' && String(branch.status || '').toLowerCase() === 'active'
    )
    if (!warehouses.length) throw new Error('This store no longer has an active warehouse branch.')

    const stockChecks = await Promise.all(warehouses.map(async (branch: any) => {
      const result = await inventoryService.getBranchInventory(Number(branch.id), { per_page: 1000 })
      const payload = result?.data ?? result
      const rows = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : []
      const items = suggestions.map(suggestion => rows.find((row: any) => Number(row.product_id) === Number(suggestion.product_id)
        && Number(row.variation_id || 0) === Number(suggestion.variation_id || 0)))
      if (items.some(item => !item)) return null
      const available = items.reduce((total: number, item: any) => total + Math.max(0, Number(item.quantity_available || 0)), 0)
      const shortage = items.reduce((total: number, item: any, index: number) =>
        total + Math.max(0, Math.ceil(Number(suggestions[index].suggested_quantity || 1)) - Math.floor(Number(item.quantity_available || 0))), 0)
      return { branch, items, available, shortage }
    }))
    const match = stockChecks.filter(Boolean).sort((left: any, right: any) => left.shortage - right.shortage || right.available - left.available)[0] as any

    if (!match) {
      const names = suggestions.map(suggestion => suggestion.product?.product_name || `Product #${suggestion.product_id}`)
      reorderRequestNotice.value = `No single active warehouse has inventory records for every selected item (${names.join(', ')}). Split the selection by warehouse or ask the warehouse team to add the missing items to its catalog.`
      return
    }

    form.from_branch_id = Number(match.branch.id)
    await onFromBranchChange()
    const requestItems = suggestions.map((suggestion: any) => {
      const sourceItem = inventoryItems.value.find((row: any) => Number(row.product_id) === Number(suggestion.product_id)
        && Number(row.variation_id || 0) === Number(suggestion.variation_id || 0))
      if (!sourceItem) throw new Error(`The warehouse stock record for ${suggestion.product?.product_name || 'a selected item'} changed. Refresh the page and try again.`)
      return {
        suggestion,
        sourceItem,
        requestQty: Math.max(1, Math.ceil(Number(suggestion.suggested_quantity || 1))),
        availableQty: Math.max(0, Math.floor(Number(sourceItem.quantity_available || 0))),
      }
    })
    form.items = requestItems.map(({ sourceItem, requestQty }: any) => ({ inventory_item_id: Number(sourceItem.id), quantity: requestQty }))
    form.reason = 'Branch replenishment from warehouse'
    const shortages = requestItems.filter(({ availableQty, requestQty }: any) => availableQty < requestQty)
    if (shortages.length) {
      const shortageNotes = shortages.map(({ suggestion, availableQty, requestQty }: any) => {
        const shortfall = requestQty - availableQty
        return `${suggestion.product?.product_name || `Product #${suggestion.product_id}`}: need ${requestQty}, warehouse has ${availableQty}, procure ${shortfall}`
      })
      reorderRequestNotice.value = `${match.branch.name} is short on ${shortages.length} selected item(s). The grouped request will include all suggested quantities; the warehouse should procure the listed shortfalls before approving dispatch.`
      form.remarks = `Grouped reorder suggestions ${suggestions.map(suggestion => `#${suggestion.id}`).join(', ')}. Warehouse shortfalls: ${shortageNotes.join('; ')}.`
    } else {
      reorderRequestNotice.value = `Prefilled ${suggestions.length} item(s) from ${match.branch.name} for ${receivingLocation.value?.name || 'the receiving branch'}.`
      form.remarks = `Grouped reorder suggestions ${suggestions.map(suggestion => `#${suggestion.id}`).join(', ')}.`
    }
  } catch (error: any) {
    reorderRequestNotice.value = error?.response?.data?.message || error?.message || 'Unable to prefill this warehouse request.'
  }
}

const prefillFromStockView = async () => {
  if (String(route.query.stock_view_prefill || '') !== '1') return

  const productId = Number(route.query.product_id || 0)
  const variationId = Number(route.query.variation_id || 0)
  const requestedQuantity = Math.max(1, Math.ceil(Number(route.query.requested_quantity || 1)))
  if (!productId) return

  const warehouses = branches.value.filter((branch: any) =>
    String(branch.branch_type || '').toLowerCase() === 'warehouse'
    && String(branch.status || '').toLowerCase() === 'active'
  )
  if (!warehouses.length) {
    reorderRequestNotice.value = 'There is no active warehouse for this store. Return to the item and create a supplier purchase order instead.'
    return
  }

  let candidates: Array<any>
  try {
    candidates = await Promise.all(warehouses.map(async (branch: any) => {
      const response = await inventoryService.getBranchInventory(Number(branch.id), { per_page: 1000 })
      const payload = response?.data ?? response
      const rows = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload?.data?.data) ? payload.data.data : Array.isArray(payload) ? payload : []
      const stock = rows.find((row: any) => Number(row.product_id) === productId
        && Number(row.variation_id || 0) === variationId
        && Number(row.quantity_available || 0) > 0)
      return stock ? { branch, stock, available: Math.floor(Number(stock.quantity_available)) } : null
    }))
  } catch (error: any) {
    reorderRequestNotice.value = error?.response?.data?.message || 'Warehouse stock could not be checked. Refresh the form and try again.'
    return
  }
  const match = candidates.filter(Boolean).sort((a: any, b: any) => b.available - a.available)[0] as any
  if (!match) {
    form.from_branch_id = Number(warehouses[0].id)
    reorderRequestNotice.value = 'An active warehouse exists, but none has available stock for this exact item or variant. Select another stocked item or ask the warehouse to receive it first.'
    return
  }

  form.from_branch_id = Number(match.branch.id)
  inventoryItems.value = [match.stock]
  const quantity = Math.min(requestedQuantity, match.available)
  form.items = [{ inventory_item_id: Number(match.stock.id), quantity }]
  form.reason = 'Branch replenishment'
  form.remarks = String(route.query.notes || 'Restock request from inventory item details.')
  reorderRequestNotice.value = quantity < requestedQuantity
    ? `Prefilled from ${match.branch.name}. The requested quantity was reduced to ${quantity}, the available stock at that warehouse.`
    : `Prefilled the exact product/variant from ${match.branch.name}. Available stock: ${match.available}.`
}

const prefillDamagedPullout = async () => {
  if (!damagedPullout.value) return
  const inventoryId = Number(route.query.branch_inventory_id || 0)
  if (!inventoryId) {
    reorderRequestNotice.value = 'Open this action from an inventory item that has damaged units recorded.'
    return
  }

  try {
    const response = await inventoryService.getInventoryItem(inventoryId)
    const inventory = response?.data ?? response
    const sourceBranchId = Number(inventory?.branch_id || route.query.branch_id || 0)
    if (!inventory?.id || !sourceBranchId) {
      reorderRequestNotice.value = 'The source stock record could not be matched to a branch.'
      return
    }
    form.from_branch_id = sourceBranchId
    form.to_branch_id = null
    const destinationId = Number(route.query.to_branch_id || warehouseOptions.value[0]?.id || 0)
    if (!destinationId) {
      reorderRequestNotice.value = 'No active warehouse is available in this store. Use the supplier-return option or activate a warehouse first.'
      return
    }
    form.to_branch_id = destinationId
    await loadInventoryForBranch(sourceBranchId)
    const sourceItem = inventoryItems.value.find((row: any) => Number(row.id) === inventoryId)
    if (!sourceItem || Number(sourceItem.quantity_damaged || 0) < 1) {
      reorderRequestNotice.value = 'This item no longer has damaged units available to pull out.'
      return
    }

    const requested = Number(route.query.requested_quantity || sourceItem.quantity_damaged)
    const quantity = Math.min(Number(sourceItem.quantity_damaged), Math.max(1, Math.floor(requested)))
    form.items = [{ inventory_item_id: inventoryId, quantity }]
    form.reason = 'Damaged stock pull-out to warehouse'
    form.remarks = 'Keep received units quarantined; do not make them available for sale.'
    reorderRequestNotice.value = `Prefilled ${quantity} damaged unit(s) from ${sourceItem.branch?.name || 'the source branch'}. The warehouse will receive them as quarantined stock.`
  } catch (error: any) {
    reorderRequestNotice.value = error?.response?.data?.message || 'Unable to load this damaged inventory record.'
  }
}

const onFromBranchChange = async () => {
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
    const response = await inventoryService.getBranchInventory(branchId, { per_page: reorderSuggestionId.value ? 1000 : 100 })
    const payload = response?.data ?? response
    inventoryItems.value = Array.isArray(payload?.data) ? payload.data : Array.isArray(payload) ? payload : []
    
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

const getProductUnit = (inventoryItemId: number): string => {
  const item = inventoryItems.value.find(p => p.id === inventoryItemId)
  return item?.variation?.unit_of_measurement || item?.product?.unit_of_measurement || 'unit'
}

const estimatePayloadItems = () => form.items.map(item => {
  const inventoryItem = inventoryItems.value.find(inv => inv.id === item.inventory_item_id)
  return {
    product_id: inventoryItem?.product_id,
    variation_id: inventoryItem?.variation_id || null,
    requested_quantity: item.quantity,
  }
})

const loadEstimate = async () => {
  if (damagedPullout.value) {
    Object.assign(estimate, { goods_value: 0, distance_km: 0, shipping_fee: 0, total_value: 0, cost_method: 'none' })
    return
  }
  if (!form.from_branch_id || !form.to_branch_id || !form.items.length) {
    Object.assign(estimate, { goods_value: 0, distance_km: 0, shipping_fee: 0, total_value: 0, cost_method: 'none' })
    return
  }
  estimating.value = true
  try {
    const response = await axios.post('/api/inventory/transfers/estimate', {
      from_branch_id: form.from_branch_id,
      to_branch_id: form.to_branch_id,
      reorder_suggestion_ids: reorderSuggestionIds.value.length ? reorderSuggestionIds.value : undefined,
      items: estimatePayloadItems(),
    }, { headers: { 'X-Suppress-Success-Dialog': '1' } })
    Object.assign(estimate, response.data?.data || {})
  } catch (error: any) {
    Object.assign(estimate, { goods_value: 0, distance_km: 0, shipping_fee: 0, total_value: 0, cost_method: 'none' })
    toast.add({ severity: 'warn', summary: 'Fee unavailable', detail: error.response?.data?.message || 'Unable to calculate the transfer fee.', life: 3000 })
  } finally {
    estimating.value = false
  }
}

const scheduleEstimate = () => {
  if (estimateTimer !== null) window.clearTimeout(estimateTimer)
  estimateTimer = window.setTimeout(loadEstimate, 250)
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
      transfer_kind: damagedPullout.value ? 'damaged_pullout' : 'standard',
      expected_delivery_date: form.expected_receive_date?.toISOString().split('T')[0],
      reason: form.reason,
      remarks: form.remarks || undefined,
      reorder_suggestion_ids: reorderSuggestionIds.value.length ? reorderSuggestionIds.value : undefined,
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
    
    router.push({ name: 'inventory.stock-requests.detail', params: { type: 'transfer', id: transferId } })
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
    message: damagedPullout.value
      ? `Move ${totalQuantity.value} damaged unit(s) from ${branches.value.find(branch => Number(branch.id) === Number(form.from_branch_id))?.name || 'the selected branch'} to ${receivingLocation.value?.name || 'the selected warehouse'}? They will remain unavailable for sale.`
      : `Submit this request from ${branches.value.find(branch => branch.id === form.from_branch_id)?.name || 'the selected location'} to ${receivingLocation.value?.name || 'your branch'} with a ${money(estimate.shipping_fee)} shipping fee?`,
    header: damagedPullout.value ? 'Confirm Damaged Stock Pull-out' : 'Confirm Transfer Request',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'No',
    acceptLabel: 'Confirm',
    rejectProps: { severity: 'secondary', outlined: true },
    accept: async () => {
      await doCreateTransfer()
    }
  })
}

const cancel = () => {
  router.push({ name: 'inventory.stock-requests' })
}

// Watch for quantity validation
watch(() => newItem.quantity, (newVal) => {
  if (selectedProduct.value && newVal > sourceQuantity(selectedProduct.value)) {
    toast.add({
      severity: 'warn',
      summary: 'Warning',
      detail: 'Quantity exceeds available stock',
      life: 3000
    })
  }
})

watch(() => form.items.map(item => `${item.inventory_item_id}:${item.quantity}`).join('|'), scheduleEstimate)

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

    const requestedReceivingBranchId = Number(route.query.receiving_branch_id || 0)
    const requestedReceivingBranch = branches.value.find((branch: any) =>
      Number(branch.id) === requestedReceivingBranchId
      && String(branch.branch_type || '').toLowerCase() !== 'warehouse'
    )
    if (requestedReceivingBranch) {
      form.to_branch_id = requestedReceivingBranchId
    } else if (userBranchId) {
      form.to_branch_id = userBranchId
    } else if (branches.value.length > 0) {
      form.to_branch_id = Number(branches.value[0].id)
      toast.add({
        severity: 'warn',
        summary: 'Branch Fallback',
        detail: 'Unable to detect your assigned branch. Using the first available receiving location.',
        life: 4000
      })
    }
    if (damagedPullout.value) {
      await prefillDamagedPullout()
    } else {
      await prefillFromReorderSuggestion()
      if (!reorderSuggestionIds.value.length) await prefillFromStockView()
    }
  } finally {
    autoFillBranchLoading.value = false
  }
})
</script>
