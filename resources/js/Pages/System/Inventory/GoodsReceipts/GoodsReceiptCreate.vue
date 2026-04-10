<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <!-- Toast notifications -->
    <Toast />

    <!-- Header -->
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'inventory.goods-receipts' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Goods Receipt</h2>
        <p class="text-sm text-gray-500 mt-1">Receive and verify purchased items from supplier</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <!-- Section 1: PO Selection -->
          <div class="border-b pb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-link text-blue-600"></i>
              Reference Purchase Order
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
              <!-- PO Selection -->
              <div class="md:col-span-6">
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  <span class="text-red-500">*</span> Select Purchase Order
                </label>
                <Select
                  v-model="form.purchase_order_id"
                  :options="approvedPOs"
                  option-label="po_number"
                  option-value="id"
                  placeholder="Select an approved PO..."
                  class="w-full"
                  @change="onPoSelected"
                  :loading="loadingPOs"
                />
                <p class="text-xs text-gray-500 mt-1">Only approved purchase orders are available</p>
              </div>

            <!-- Receipt Date -->
              <div class="md:col-span-2">
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  <span class="text-red-500">*</span> Receipt Date
                </label>
                <DatePicker fluid v-model="form.receipt_date" date-format="yy-mm-dd" class="w-full" show-icon />
              </div>

              <!-- Receipt Time -->
              <div class="md:col-span-2">
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  <span class="text-red-500">*</span> Receipt Time
                </label>
                <InputText v-model="form.receipt_time" placeholder="HH:mm:ss" class="w-full" />
              </div>

              <!-- Receipt Status -->
              <div class="md:col-span-2">
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  <span class="text-red-500">*</span> Receipt Type
                </label>
                <Select
                  v-model="form.receipt_status"
                  :options="receiptStatusOptions"
                  option-label="label"
                  option-value="value"
                  class="w-full"
                />
              </div>
            </div>

            <!-- PO Details Summary -->
            <div v-if="selectedPO" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                <p class="text-xs text-blue-600 font-semibold">Supplier</p>
                <p class="text-gray-800 font-semibold">{{ selectedPO.supplier?.supplier_name }}</p>
              </div>
              <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                <p class="text-xs text-green-600 font-semibold">Total Items</p>
                <p class="text-gray-800 font-semibold">{{ selectedPO.items?.length || 0 }} items</p>
              </div>
              <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                <p class="text-xs text-purple-600 font-semibold">PO Total</p>
                <p class="text-gray-800 font-semibold">₱ {{ (parseFloat(selectedPO?.total_amount) || 0).toFixed(2) }}</p>
              </div>
            </div>
          </div>

          <!-- Section 2: Barcode Scanner / Quick Add -->
          <div class="border-b pb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-barcode text-purple-600"></i>
              Receive Items
            </h3>

            <div class="space-y-4">
              <!-- Barcode Input -->
              <div>
                <label class="text-sm font-semibold text-gray-700 block mb-2">
                  Barcode Scanner / Product Search
                </label>
                <div class="flex gap-2">
                  <InputText
                    v-model="barcodeInput"
                    placeholder="Scan barcode or type product name..."
                    class="flex-1"
                    @keyup.enter="addByBarcode"
                  />
                  <Button
                    icon="pi pi-search"
                    :loading="scanningBarcode"
                    @click="addByBarcode"
                    v-tooltip="'Or press Enter'"
                  />
                </div>
                <p class="text-xs text-gray-500 mt-1">🔍 Barcode scanner will auto-populate product and any custom quantity</p>
              </div>

              <!-- Quick Add from PO Items -->
              <div v-if="selectedPO?.items?.length" class="p-4 bg-amber-50 border-l-4 border-amber-400 rounded">
                <p class="text-sm font-semibold text-gray-700 mb-3">📦 Quick Add from PO:</p>
                <div class="flex gap-2 flex-wrap">
                  <Button
                    v-for="item in selectedPO.items"
                    :key="item.id"
                    :label="`${item.product?.product_name} (${item.quantity_ordered})`"
                    size="small"
                    severity="secondary"
                    outlined
                    @click="quickAddItem(item)"
                    class="text-xs"
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Section 3: Received Items Table -->
          <div class="border-b pb-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                <i class="pi pi-inbox text-green-600"></i>
                Received Items ({{ receivedItems.length }})
              </h3>
            </div>

            <!-- Discrepancy Alert -->
            <div v-if="hasDiscrepancies" class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 rounded">
              <p class="text-sm font-semibold text-red-800">
                ⚠️ Discrepancies Detected - Review items below
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
                    <th class="text-center p-3">Action</th>
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
                        v-model="item.quantity_received"
                        :min="0" fluid
                        class="w-16 text-center"
                        @input="calculateVariance(index)"
                      />
                    </td>
                    <td class="p-3 text-center font-semibold" :class="getVarianceColor(item)">
                      {{ item.variance }}
                      <span class="text-xs">({{ item.variance_percent }}%)</span>
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
                    <td class="p-3 text-center">
                      <Button
                        icon="pi pi-trash"
                        text
                        severity="danger"
                        size="small"
                        @click="removeReceivedItem(index)"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="text-center py-8 bg-gray-50 border border-dashed border-gray-300 rounded">
              <i class="pi pi-inbox text-gray-400 text-3xl mb-2"></i>
              <p class="text-gray-500">No items received yet. Use barcode scanner or quick add buttons above.</p>
            </div>
          </div>

          <!-- Section 4: Verification Summary -->
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
            />
          </div>

          <!-- Section 6: Action Buttons -->
          <div class="pt-4 flex justify-end gap-3 border-t">
            <Button
              label="Cancel"
              severity="secondary"
              text
              type="button"
              @click="router.push({ name: 'inventory.goods-receipts' })"
            />
            <Button
              label="Save as Draft"
              icon="pi pi-download"
              severity="info"
              :loading="saving"
              @click="saveDraft = true; submitForm()"
            />
            <Button
              label="Complete Receipt"
              icon="pi pi-check"
              severity="success"
              :loading="saving"
              @click="saveDraft = false; submitForm()"
            />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()

// Form State
const form = reactive({
  purchase_order_id: null as number | null,
  branch_id: null as number | null,
  receipt_date: new Date(),
  receipt_time: new Date().toTimeString().split(' ')[0],
  receipt_status: 'full' as 'full' | 'partial' | 'damaged' | 'rejected',
  notes: ''
})

// UI State
const saving = ref(false)
const saveDraft = ref(false)
const loadingPOs = ref(false)
const scanningBarcode = ref(false)
const barcodeInput = ref('')
const approvedPOs = ref<any[]>([])
const selectedPO = ref<any>(null)
const receivedItems = ref<any[]>([])

const receiptStatusOptions = [
  { label: 'Full Receipt', value: 'full' },
  { label: 'Partial Receipt', value: 'partial' },
  { label: 'Damaged Items', value: 'damaged' },
  { label: 'Rejected', value: 'rejected' }
]

const itemStatusOptions = [
  { label: 'Complete', value: 'complete' },
  { label: 'Short', value: 'short' },
  { label: 'Damaged', value: 'damaged' },
  { label: 'Wrong Item', value: 'wrong' }
]

// Computed
const hasDiscrepancies = computed(() => {
  return receivedItems.value.some((item) => item.status !== 'complete' || item.variance !== 0)
})

const totalOrdered = computed(() => {
  return receivedItems.value.reduce((sum, item) => sum + item.quantity_ordered, 0)
})

const totalReceived = computed(() => {
  return receivedItems.value.reduce((sum, item) => sum + item.quantity_received, 0)
})

const totalVariance = computed(() => {
  return totalReceived.value - totalOrdered.value
})

const completionPercent = computed(() => {
  if (totalOrdered.value === 0) return 0
  return Math.round((totalReceived.value / totalOrdered.value) * 100)
})

// Methods
onMounted(async () => {
  await loadApprovedPOs()

  // If PO ID provided in query, auto-select
  const poIdFromQuery = route.query.po_id
  if (poIdFromQuery) {
    form.purchase_order_id = Number(poIdFromQuery)
    await onPoSelected()
  }
})

const loadApprovedPOs = async () => {
  loadingPOs.value = true
  try {
    const response = await procurementService.getApprovedPurchaseOrders?.({ per_page: 100 })
      .catch(() => ({ data: [] }))
    approvedPOs.value = response?.data?.data || response?.data || []
  } catch (error) {
    console.error('Failed to load approved POs', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load purchase orders',
      life: 3000
    })
  } finally {
    loadingPOs.value = false
  }
}

const onPoSelected = async () => {
  if (!form.purchase_order_id) {
    selectedPO.value = null
    receivedItems.value = []
    return
  }

  try {
      const response = await procurementService.getPurchaseOrder(form.purchase_order_id)
      const payload = response?.data ?? response
      selectedPO.value = payload?.data ?? payload ?? null
      form.branch_id = selectedPO.value?.branch_id

    // Initialize received items from PO items
      receivedItems.value = (selectedPO.value?.items || []).map((item: any) => ({
        id: item.id,
        purchase_order_item_id: item.id,
        product_id: item.product_id,
        product: item.product,
        variation_id: item.variation_id,
        quantity_ordered: item.quantity_ordered,
        quantity_expected: item.quantity_ordered,
        quantity_received: item.quantity_ordered,
        quantity_damaged: 0,
        variance: 0,
        variance_percent: 0,
        status: 'complete',
        remarks: ''
      }))
  } catch (error) {
    console.error('Failed to load PO details', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load purchase order details',
      life: 3000
    })
  }
}

const quickAddItem = (item: any) => {
  // Check if already added
  const exists = receivedItems.value.find((ri) => ri.product_id === item.product_id)
  if (!exists && selectedPO.value?.items) {
    const poItem = selectedPO.value.items.find((i: any) => i.product_id === item.product_id)
    if (poItem) {
      receivedItems.value.push({
        id: poItem.id,
        purchase_order_item_id: poItem.id,
        product_id: item.product_id,
        product: item.product,
        variation_id: poItem.variation_id,
        quantity_ordered: item.quantity_ordered,
        quantity_expected: item.quantity_ordered,
        quantity_received: item.quantity_ordered,
        quantity_damaged: 0,
        variance: 0,
        variance_percent: 0,
        status: 'complete',
        remarks: ''
      })
      toast.add({
        severity: 'success',
        summary: 'Added',
        detail: `${item.product?.product_name} added`,
        life: 2000
      })
    }
  }
}

const addByBarcode = async () => {
  if (!barcodeInput.value.trim()) return

  scanningBarcode.value = true
  try {
    // Simulate barcode lookup - in real scenario, call API
    // For now, search in PO items
    const matchedItem = selectedPO.value?.items?.find((item: any) =>
      item.product?.sku?.includes(barcodeInput.value) ||
      item.product?.product_name?.toLowerCase().includes(barcodeInput.value.toLowerCase())
    )

    if (matchedItem) {
      const exists = receivedItems.value.find((ri) => ri.product_id === matchedItem.product_id)
      if (!exists) {
        receivedItems.value.push({
          id: matchedItem.id,
          purchase_order_item_id: matchedItem.id,
          product_id: matchedItem.product_id,
          product: matchedItem.product,
          variation_id: matchedItem.variation_id,
          quantity_ordered: matchedItem.quantity_ordered,
          quantity_expected: matchedItem.quantity_ordered,
          quantity_received: 1, // Start with 1 for barcode scans
          quantity_damaged: 0,
          variance: 1 - matchedItem.quantity_ordered,
          variance_percent: Math.round(
            ((1 - matchedItem.quantity_ordered) / matchedItem.quantity_ordered) * 100
          ),
          status: 'complete',
          remarks: ''
        })
        toast.add({
          severity: 'success',
          summary: 'Scanned',
          detail: `${matchedItem.product?.product_name} detected`,
          life: 2000
        })
      } else {
        toast.add({
          severity: 'warn',
          summary: 'Already Added',
          detail: 'Product already in receipt list',
          life: 2000
        })
      }
    } else {
      toast.add({
        severity: 'warn',
        summary: 'Not Found',
        detail: 'Product not found in this PO',
        life: 2000
      })
    }

    barcodeInput.value = ''
  } finally {
    scanningBarcode.value = false
  }
}

  const calculateVariance = (index: number) => {
    const item = receivedItems.value[index]
    item.variance = item.quantity_received - item.quantity_ordered
    item.variance_percent = item.quantity_ordered > 0
      ? Math.round((item.variance / item.quantity_ordered) * 100)
      : 0
  }

const removeReceivedItem = (index: number) => {
  receivedItems.value.splice(index, 1)
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

const submitForm = async () => {
  // Validation
  if (!form.purchase_order_id) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Please select a purchase order', life: 3000 })
    return
  }

  if (receivedItems.value.length === 0) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Please add at least one received item', life: 3000 })
    return
  }

  saving.value = true
  try {
    // Convert Date to ISO string date for API
    const receiptDate = (form.receipt_date instanceof Date
      ? form.receipt_date.toISOString().split('T')[0]
      : form.receipt_date) || new Date().toISOString().split('T')[0]

      const payload: Record<string, any> = {
        purchase_order_id: form.purchase_order_id,
        branch_id: form.branch_id,
        receipt_date: receiptDate,
        receipt_time: form.receipt_time || new Date().toTimeString().split(' ')[0],
        receipt_status: form.receipt_status,
        notes: form.notes,
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

    await procurementService.createGoodsReceipt(payload as any)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Goods receipt ${saveDraft.value ? 'saved as draft' : 'completed'} successfully`,
      life: 2000
    })

    setTimeout(() => {
      router.push({ name: 'inventory.goods-receipts' })
    }, 1500)
  } catch (error) {
    console.error('Failed to create goods receipt', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to create goods receipt',
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
