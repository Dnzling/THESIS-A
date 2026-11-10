<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <ConfirmDialog />
    <!-- Toast notifications -->
    <Toast />

    <!-- Header -->
    <div class="flex items-center gap-3">
      <Button label="Back" text @click="router.push({ name: isWarehouseFlow ? 'warehouse.receiving' : 'inventory.goods-receipts' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Goods Receipt</h2>
        <p class="text-sm text-gray-500 mt-1">Receive and verify purchased items from supplier</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="requestReceiptConfirmation(false)">
          <!-- Section 1: Purchase Order -->
          <div class="border-b pb-6">
            <h3 class="mb-4 text-lg font-semibold text-gray-800">Reference Purchase Order</h3>

            <!-- PO Details Summary -->
            <div v-if="selectedPO" class="grid grid-cols-1 gap-3 rounded-xl border border-slate-300 bg-white p-4 sm:grid-cols-2 xl:grid-cols-4">
              <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">PO Number</p>
                <p class="mt-1 break-words font-semibold text-slate-950">{{ selectedPO.po_number }}</p>
              </div>
              <div class="min-w-0 border-t border-slate-200 pt-3 sm:border-l sm:border-t-0 sm:pl-4 sm:pt-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Supplier</p>
                <p class="mt-1 break-words font-semibold text-slate-950">{{ selectedPO.supplier?.supplier_name || '—' }}</p>
              </div>
              <div class="min-w-0 border-t border-slate-200 pt-3 sm:border-l sm:border-t-0 sm:pl-4 sm:pt-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Total Items</p>
                <p class="mt-1 font-semibold text-slate-950">{{ selectedPO.items?.length || 0 }} items</p>
              </div>
              <div class="min-w-0 border-t border-slate-200 pt-3 sm:border-l sm:border-t-0 sm:pl-4 sm:pt-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">PO Total</p>
                <p class="mt-1 font-semibold text-slate-950">₱ {{ (parseFloat(selectedPO?.total_amount) || 0).toFixed(2) }}</p>
              </div>
            </div>
            <div v-else class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
              Open this form from a submitted purchase order when the goods arrive.
            </div>
          </div>

          <!-- Received Items Table -->
          <div class="border-b pb-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-800">Received Items ({{ receivedItems.length }})</h3>
            </div>

            <!-- Discrepancy Alert -->
            <div v-if="hasDiscrepancies" class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 rounded">
              <p class="text-sm font-semibold text-red-800">
                Discrepancies detected. Review the items below.
              </p>
            </div>

            <!-- Items Table -->
            <div v-if="receivedItems.length > 0" class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                  <tr>
                    <th class="text-left p-3">Product</th>
                    <th class="text-center p-3">Ordered</th>
                    <th class="text-center p-3">Received</th>
                    <th class="text-center p-3">Variance</th>
                    <th class="text-center p-3">Status</th>
                    <th class="text-center p-3">Remarks</th>
                   
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, index) in receivedItems"
                    :key="index"
                    :class="[
                      'border-b hover:bg-gray-50 transition-colors',
                      getRowHighlight(item)
                    ]"
                  >
                    <td class="p-3">
                      <div class="flex items-center gap-2">
                        <img
                          v-if="item.product?.image_url"
                          :src="item.product.image_url"
                          :alt="item.product?.product_name"
                          class="w-8 h-8 rounded object-cover"
                        />
                        <div>
                          <p class="font-semibold">{{ item.product?.product_name }}</p>
                          <p class="text-xs text-gray-500">SKU: {{ item.product?.sku }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="p-3 text-center">{{ item.quantity_ordered }}</td>
                    <td class="p-3 text-center">
                      <InputNumber
                        :modelValue="item.quantity_received"
                        :min="0" fluid
                        class="w-16 text-center"
                        @update:modelValue="onReceivedQuantityUpdate(index, $event)"
                        @blur="onReceivedQuantityBlur(index)"
                      />
                      <p v-if="item.quantityError" class="mt-1 text-xs text-red-600">{{ item.quantityError }}</p>
                    </td>
                    <td class="p-3 text-center font-semibold" :class="getVarianceColor(item)">
                      <template v-if="item.varianceTouched">
                        {{ item.variance }}
                        <span class="text-xs">({{ item.variance_percent }}%)</span>
                      </template>
                      <span v-else class="text-gray-400">—</span>
                    </td>
                    <td class="p-3 text-center">
                      <Select
                        v-model="item.status"
                        :options="itemStatusOptions"
                        option-label="label"
                        option-value="value" fluid
                        class="w-full"
                        size="small"
                      />
                    </td>
                    <td class="p-3">
                      <InputText
                        v-model="item.remarks"
                        placeholder="Damaged, short, etc..."
                        class="w-full text-xs"
                      />
                    </td>
                  
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="text-center py-8 bg-gray-50 border border-dashed border-gray-300 rounded">
              <p class="text-gray-500">No purchase-order items are available to receive.</p>
            </div>
          </div>

          <!-- Verification Summary -->
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <Card class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200">
              <template #content>
                <p class="text-xs text-blue-600 font-semibold">Total Ordered</p>
                <p class="text-2xl font-bold text-blue-900">{{ totalOrdered }}</p>
              </template>
            </Card>

            <Card class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200">
              <template #content>
                <p class="text-xs text-green-600 font-semibold">Total Received</p>
                <p class="text-2xl font-bold text-green-900">{{ totalReceived }}</p>
              </template>
            </Card>

            <Card class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200">
              <template #content>
                <p class="text-xs text-orange-600 font-semibold">Variance</p>
                <p class="text-2xl font-bold text-orange-900">{{ totalVariance }}</p>
              </template>
            </Card>

            <Card class="bg-gradient-to-br from-purple-50 to-purple-100 border border-purple-200">
              <template #content>
                <p class="text-xs text-purple-600 font-semibold">Completion %</p>
                <p class="text-2xl font-bold text-purple-900">{{ completionPercent }}%</p>
              </template>
            </Card>
          </div>

          <!-- Section 5: Notes -->
          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">
              Receiving Notes (Optional)
            </label>
            <Textarea
              v-model="form.notes"
              rows="3"
              placeholder="Add any special notes about this receipt..."
              fluid
            />
          </div>

          <!-- Section 6: Action Buttons -->
          <div class="pt-4 flex justify-end gap-3 border-t">
            <Button
              label="Cancel"
              severity="secondary"
              text
              type="button"
              @click="router.push({ name: isWarehouseFlow ? 'warehouse.receiving' : 'inventory.goods-receipts' })"
            />
            <Button
              label="Save as Draft"
              severity="secondary"
              type="button"
              :loading="saving"
              @click="requestReceiptConfirmation(true)"
            />
            <Button
              label="Complete Receipt"
              type="button"
              :loading="saving"
              @click="requestReceiptConfirmation(false)"
            />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'
import inventoryService from '../../../../services/inventory.service'
import WarehouseService from '@/services/warehouse.service'
import axiosClient from '@/axios'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const isInventoryFlow = window.location.pathname.startsWith('/inventory/')
const isWarehouseFlow = window.location.pathname.startsWith('/warehouse/')
const receiptApiBase = isWarehouseFlow ? '/api/warehouse/goods-receipts' : '/api/inventory/goods-receipts'

// Form State
const form = reactive({
  purchase_order_id: null as number | null,
  branch_id: null as number | null,
  notes: ''
})

// UI State
const saving = ref(false)
const saveDraft = ref(false)
const selectedPO = ref<any>(null)
const receivedItems = ref<any[]>([])
const draftId = ref<number | null>(null)

const itemStatusOptions = [
  { label: 'Complete', value: 'complete' },
  { label: 'Short', value: 'short' },
  { label: 'Damaged', value: 'damaged' },
  { label: 'Wrong Item', value: 'wrong' }
]

// Computed
const hasDiscrepancies = computed(() => {
  return receivedItems.value.some((item) => item.varianceTouched && (item.status !== 'complete' || item.variance !== 0))
})

const totalOrdered = computed(() => {
  return receivedItems.value.reduce((sum, item) => sum + item.quantity_ordered, 0)
})

const totalReceived = computed(() => {
  return receivedItems.value.reduce((sum, item) => sum + Number(item.confirmedQuantityReceived ?? 0), 0)
})

const totalVariance = computed(() => {
  return receivedItems.value.reduce((sum, item) => sum + (item.varianceTouched ? item.variance : 0), 0)
})

const completionPercent = computed(() => {
  if (totalOrdered.value === 0) return 0
  return Math.round((totalReceived.value / totalOrdered.value) * 100)
})

// Methods
onMounted(async () => {
  const draftIdFromQuery = Number(new URLSearchParams(window.location.search).get('draft_id') || 0)
  if ((isInventoryFlow || isWarehouseFlow) && draftIdFromQuery > 0) {
    await prefillFromDraft(draftIdFromQuery)
    return
  }
  // If PO ID provided in query, auto-select and prefill
  const poIdFromQuery = Number(new URLSearchParams(window.location.search).get('po_id') || route.query.po_id || 0)
  const warehousePrId = Number(route.query.warehouse_pr_id || 0)
  if (isWarehouseFlow && warehousePrId > 0 && poIdFromQuery > 0) {
    await prefillFromWarehouseRequisition(warehousePrId, poIdFromQuery)
    return
  }
  if (poIdFromQuery > 0) {
    await prefillFromPurchaseOrder(poIdFromQuery)
    if (selectedPO.value) return
  }

  // Fallback: if PR ID provided, resolve linked PO then prefill
  const prIdFromQuery = Number(route.query.pr_id || 0)
  if (prIdFromQuery > 0) {
    await prefillFromRequisition(prIdFromQuery)
  }
})

const hydrateReceiptFromPO = (po: any) => {
  selectedPO.value = po
  form.branch_id = po?.branch_id ?? null
  receivedItems.value = (po?.items || []).map((item: any) => ({
    remaining: Math.max(0, Number(item.quantity_ordered || 0) - Number(item.quantity_received || 0) - Number(item.quantity_rejected || 0)),
    ...item,
  })).filter((item: any) => item.remaining > 0).map((item: any) => ({
    id: item.id,
    purchase_order_item_id: item.id,
    product_id: item.product_id,
    product: item.product,
    variation_id: item.variation_id,
    quantity_ordered: item.remaining,
    quantity_expected: item.remaining,
    quantity_received: isInventoryFlow || isWarehouseFlow ? 0 : item.remaining,
    confirmedQuantityReceived: isInventoryFlow || isWarehouseFlow ? 0 : item.remaining,
    quantity_damaged: 0,
    variance: isInventoryFlow || isWarehouseFlow ? -item.remaining : 0,
    variance_percent: isInventoryFlow || isWarehouseFlow ? -100 : 0,
    varianceTouched: false,
    quantityError: '',
    status: isInventoryFlow || isWarehouseFlow ? 'short' : 'complete',
    remarks: ''
  }))
}

const prefillFromPurchaseOrder = async (poId: number) => {
  try {
    const response = isInventoryFlow
      ? await axiosClient.get(`/api/inventory/purchase-orders/${poId}`)
      : await procurementService.getPurchaseOrder(poId)
    const payload = response?.data ?? response
    const po = payload?.data ?? payload ?? null
    if (!po?.id) return

    form.purchase_order_id = po.id
    hydrateReceiptFromPO(po)
  } catch (error) {
    console.error('Failed to prefill from purchase order', error)
  }
}

const prefillFromWarehouseRequisition = async (requisitionId: number, purchaseOrderId: number) => {
  try {
    const requisition = await WarehouseService.purchaseRequisition(requisitionId)
    const purchaseOrders = Array.isArray(requisition?.purchase_orders)
      ? requisition.purchase_orders
      : (Array.isArray(requisition?.purchaseOrders) ? requisition.purchaseOrders : [])
    const po = purchaseOrders.find((item: any) => Number(item.id) === purchaseOrderId)
    if (!po) {
      toast.add({ severity: 'error', summary: 'Purchase order unavailable', detail: 'This PO is not linked to the selected warehouse requisition.', life: 3500 })
      return
    }

    form.purchase_order_id = Number(po.id)
    hydrateReceiptFromPO(po)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to load requisition', detail: error?.response?.data?.message || 'Could not load the linked PO items.', life: 3500 })
  }
}

const prefillFromDraft = async (id: number) => {
  try {
    const response = await axiosClient.get(`${receiptApiBase}/${id}`)
    const draft = response?.data?.data ?? response?.data
    if (draft?.receipt_status !== 'draft' || !draft.purchase_order_id) return
    await prefillFromPurchaseOrder(Number(draft.purchase_order_id))
    if (!selectedPO.value) return
    draftId.value = id
    form.notes = draft.quality_notes || ''
    for (const item of receivedItems.value) {
      const saved = draft.items?.find((entry: any) => Number(entry.purchase_order_item_id) === Number(item.purchase_order_item_id))
      if (saved) {
        item.quantity_received = Number(saved.quantity_received || 0)
        calculateVariance(receivedItems.value.indexOf(item))
      }
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Draft unavailable', detail: 'Unable to load this draft receipt.', life: 3000 })
  }
}

const prefillFromRequisition = async (prId: number) => {
  try {
    const response = isWarehouseFlow
      ? await WarehouseService.purchaseRequisition(prId)
      : await inventoryService.getPurchaseRequisition(prId)
    const requisition = response?.data ?? response
    const data = requisition?.data ?? requisition
    const purchaseOrders = Array.isArray(data?.purchase_orders)
      ? data.purchase_orders
      : (Array.isArray(data?.purchaseOrders) ? data.purchaseOrders : [])
    const poId = Number(purchaseOrders?.[0]?.id || 0)

    if (poId > 0) {
      await prefillFromPurchaseOrder(poId)
      return
    }

    toast.add({
      severity: 'warn',
      summary: 'No Linked PO',
      detail: 'This requisition has no linked purchase order yet.',
      life: 3000
    })
  } catch (error) {
    console.error('Failed to prefill from requisition', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to prefill goods receipt from requisition',
      life: 3000
    })
  }
}

  const calculateVariance = (index: number) => {
    const item = receivedItems.value[index]
    if (!item) return
    const received = Number(item.quantity_received ?? 0)
    item.varianceTouched = true
    item.quantityError = !Number.isInteger(received) || received < 0
      ? 'Enter a valid quantity.'
      : received > item.quantity_ordered
        ? 'Received quantity cannot exceed the outstanding quantity.'
        : ''
    if (item.quantityError) return
    item.confirmedQuantityReceived = received
    item.variance = received - item.quantity_ordered
    item.variance_percent = item.quantity_ordered > 0
      ? Math.round((item.variance / item.quantity_ordered) * 100)
      : 0
    if (item.status === 'short' || item.status === 'complete') item.status = item.variance === 0 ? 'complete' : 'short'
  }

  const onReceivedQuantityUpdate = (index: number, value: number | null) => {
    const item = receivedItems.value[index]
    if (!item) return
    item.quantity_received = value
    calculateVariance(index)
  }

  const onReceivedQuantityBlur = async (index: number) => {
    await nextTick()
    calculateVariance(index)
  }


  const getVarianceColor = (item: any) => {
    if (item.variance === 0) return 'text-green-600'
    if (item.variance > 0) return 'text-blue-600'
  if (item.variance_percent < -5) return 'text-red-600'
  return 'text-orange-600'
}

  const getRowHighlight = (item: any) => {
    if (item.status === 'damaged') return 'bg-red-50'
    if (item.status === 'short' || item.variance < 0) return 'bg-yellow-50'
    if (item.status === 'wrong') return 'bg-blue-50'
    return ''
  }

  const mapStatusToCondition = (status: string): 'good' | 'damaged' | 'defective' => {
    if (status === 'damaged') return 'damaged'
    if (status === 'wrong') return 'defective'
    return 'good'
  }

const requestReceiptConfirmation = (asDraft: boolean) => {
  confirm.require({
    header: asDraft ? 'Save receipt as draft?' : 'Complete this goods receipt?',
    message: asDraft
      ? 'The receipt will be saved as a draft and will not update inventory yet.'
      : 'Confirm the received quantities and complete this receipt. Inventory will be updated based on the verified quantities.',
    icon: asDraft ? 'pi pi-save' : 'pi pi-check-circle',
    acceptLabel: asDraft ? 'Save Draft' : 'Complete Receipt',
    rejectLabel: 'Review Again',
    acceptProps: { severity: asDraft ? 'secondary' : 'warn' },
    rejectProps: { severity: 'secondary', outlined: true },
    accept: () => {
      saveDraft.value = asDraft
      submitForm()
    },
  })
}

const submitForm = async () => {
  receivedItems.value.forEach((_, index) => calculateVariance(index))
  if (receivedItems.value.some((item) => item.quantityError)) {
    toast.add({ severity: 'error', summary: 'Invalid Quantity', detail: 'Correct the received quantities before continuing.', life: 3000 })
    return
  }
  // Validation
  if (!form.purchase_order_id) {
    toast.add({ severity: 'error', summary: 'Purchase Order Required', detail: 'Open this form from a submitted purchase order.', life: 3000 })
    return
  }

  if (receivedItems.value.length === 0) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Please add at least one received item', life: 3000 })
    return
  }

  saving.value = true
  try {
      const payload: Record<string, any> = {
        purchase_order_id: form.purchase_order_id,
        branch_id: form.branch_id,
        quality_notes: form.notes,
        items: receivedItems.value.map((item) => ({
          purchase_order_item_id: item.purchase_order_item_id,
          product_id: item.product_id,
          variation_id: item.variation_id ?? null,
          quantity_expected: item.quantity_expected,
          quantity_received: item.quantity_received,
          quantity_damaged: item.quantity_damaged ?? 0,
          condition: mapStatusToCondition(item.status),
          notes: item.remarks || ''
        })),
        status: saveDraft.value ? 'draft' : 'completed'
      }

    if ((isInventoryFlow || isWarehouseFlow) && draftId.value) {
      await axiosClient.put(`${receiptApiBase}/${draftId.value}`, payload)
      if (!saveDraft.value) {
        await axiosClient.post(`${receiptApiBase}/${draftId.value}/verify`)
      }
    } else if (isInventoryFlow || isWarehouseFlow) {
      await axiosClient.post(receiptApiBase, payload)
    } else {
      await procurementService.createGoodsReceipt(payload as any)
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Goods receipt ${saveDraft.value ? 'saved as draft' : 'completed'} successfully`,
      life: 2000
    })

    setTimeout(() => {
      router.push({ name: isWarehouseFlow ? 'warehouse.receiving' : 'inventory.goods-receipts' })
    }, 1500)
  } catch (error) {
    console.error('Failed to create goods receipt', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: (error as any)?.response?.data?.message || 'Failed to create goods receipt',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter-from {
  transform: translateX(10px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateX(-10px);
  opacity: 0;
}
</style>
