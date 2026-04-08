<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create Invoice</h2>
        <p class="text-sm text-gray-500 mt-1">Create a new supplier invoice</p>
      </div>
    </div>

    <!-- Form -->
    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <!-- PO Selection -->
          <div class="bg-blue-50 p-4 rounded border border-blue-200">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Reference Purchase Order *
            </label>
            <Select
              v-model="form.purchase_order_id"
              :options="purchaseOrders"
              optionLabel="po_number"
              optionValue="id"
              placeholder="Select PO"
              class="w-full"
              @change="onPOSelected"
            />
            <p class="text-xs text-gray-600 mt-2">
              Selecting a PO auto-fills supplier and product lines. Tax and delivery are entered separately.
            </p>
          </div>

          <!-- Supplier & Invoice Info -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Supplier *
              </label>
              <InputText
                v-model="form.supplier_name"
                placeholder="Supplier"
                disabled
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Invoice Number *
              </label>
              <InputText
                v-model="form.invoice_number"
                placeholder="INV-2026-001"
                class="w-full"
                required
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Invoice Date *
              </label>
              <DatePicker
                v-model="form.invoice_date"
                dateFormat="yy-mm-dd"
                class="w-full"
              />
            </div>
          </div>

          <!-- Dates -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Due Date *
              </label>
              <DatePicker
                v-model="form.due_date"
                dateFormat="yy-mm-dd"
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                GRN Number
              </label>
              <InputText
                v-model="form.grn_number"
                placeholder="GRN-2026-001"
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Reference Number
              </label>
              <InputText
                v-model="form.reference_number"
                placeholder="Optional reference"
                class="w-full"
              />
            </div>
          </div>

          <!-- Line Items -->
          <div class="border-t pt-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-800">Line Items</h3>
              <Button
                icon="pi pi-plus"
                label="Add Item"
                class="p-button-sm"
                @click="addLineItem"
              />
            </div>

            <DataTable :value="form.line_items" class="p-datatable-sm">
              <Column field="description" header="Description" style="width: 30%">
                <template #body="{ data }">
                  <InputText
                    v-model="data.description"
                    placeholder="Item description"
                    class="w-full"
                    @input="calculateLineAmount"
                  />
                </template>
              </Column>
              <Column field="quantity" header="Qty" style="width: 10%">
                <template #body="{ data }">
                  <InputNumber
                    v-model="data.quantity"
                    placeholder="0"
                    class="w-full"
                    @input="calculateLineAmount"
                  />
                </template>
              </Column>
              <Column field="unit_price" header="Unit Price" style="width: 12%">
                <template #body="{ data }">
                  <InputNumber
                    v-model="data.unit_price"
                    placeholder="0.00"
                    mode="currency"
                    currency="PHP"
                    class="w-full"
                    @input="calculateLineAmount"
                  />
                </template>
              </Column>
              <Column field="tax_rate" header="Tax %" style="width: 8%">
                <template #body="{ data }">
                  <InputNumber
                    v-model="data.tax_rate"
                    placeholder="12"
                    suffix="%"
                    class="w-full"
                    @input="calculateLineAmount"
                  />
                </template>
              </Column>
              <Column header="Amount" style="width: 12%">
                <template #body="{ data }">
                  ₱ {{ formatNumber(data.quantity * data.unit_price) }}
                </template>
              </Column>
              <Column header="Delete" style="width: 8%">
                <template #body="{ index }">
                  <Button
                    icon="pi pi-trash"
                    class="p-button-sm p-button-danger text-white"
                    @click="removeLineItem(index)"
                  />
                </template>
              </Column>
            </DataTable>
          </div>

          <!-- Totals -->
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4 bg-gray-50 p-4 rounded">
            <div>
              <p class="text-gray-600 text-sm">Product Subtotal</p>
              <p class="text-2xl font-bold text-gray-800">₱ {{ formatNumber(subtotal) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Tax</p>
              <p class="text-2xl font-bold text-orange-600">₱ {{ formatNumber(totalTax) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Delivery Charge</p>
              <p class="text-2xl font-bold text-blue-600">₱ {{ formatNumber(form.shipping_cost || 0) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Discount</p>
              <p class="text-2xl font-bold">₱ {{ formatNumber(form.discount_amount || 0) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Total Payable</p>
              <p class="text-3xl font-bold text-green-600">₱ {{ formatNumber(form.net_amount || 0) }}</p>
            </div>
          </div>

          <!-- Charges -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Delivery Charge
              </label>
              <InputNumber
                v-model="form.shipping_cost"
                mode="currency"
                currency="PHP"
                placeholder="0.00"
                @input="calculateTotals"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Discount Amount
              </label>
              <InputNumber
                v-model="form.discount_amount"
                mode="currency"
                currency="PHP"
                placeholder="0.00"
                @input="calculateTotals"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Currency
              </label>
              <InputText
                v-model="form.currency"
                maxlength="3"
                class="w-full uppercase"
              />
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Notes
            </label>
            <Textarea
              v-model="form.notes"
              placeholder="Add any notes..."
              rows="3"
              class="w-full"
            />
          </div>

          <!-- Actions -->
          <div class="flex gap-2 justify-end pt-4 border-t">
            <Button label="Cancel" severity="secondary" @click="goBack" />
            <Button
              label="Save as Draft"
              icon="pi pi-save"
              @click="saveDraft"
              :loading="saving"
            />
            <Button
              label="Create & Review"
              icon="pi pi-check"
              @click="submitForm"
              :loading="saving"
            />
          </div>
        </form>
      </template>
    </Card>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const toast = useToast()

// State
const purchaseOrders = ref<any[]>([])
const saving = ref(false)

const form = ref({
  purchase_order_id: 0,
  supplier_id: 0,
  supplier_name: '',
  invoice_number: '',
  invoice_date: new Date(),
  due_date: new Date(),
  grn_number: '',
  reference_number: '',
  line_items: [
    { product_id: null as number | null, description: '', quantity: 0, unit_price: 0, tax_rate: 12, tax_amount: 0 },
  ],
  discount_amount: 0,
  tax_amount: 0,
  shipping_cost: 0,
  invoice_amount: 0,
  net_amount: 0,
  currency: 'PHP',
  notes: '',
})

// Computed
const subtotal = computed(() => {
  return form.value.line_items.reduce((sum: number, item: any) => {
    return sum + (item.quantity * item.unit_price)
  }, 0)
})

const totalTax = computed(() => {
  return form.value.line_items.reduce((sum: number, item: any) => {
    const amount = item.quantity * item.unit_price
    return sum + ((amount * item.tax_rate) / 100)
  }, 0)
})

// Methods
async function loadPurchaseOrders() {
  try {
    const response = await procurementService.getPurchaseOrders({
      status: 'approved',
      per_page: 100,
    })
    purchaseOrders.value = response.data?.data || []
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load purchase orders',
      life: 3000,
    })
  }
}

async function onPOSelected() {
  const po = purchaseOrders.value.find((p: any) => p.id === form.value.purchase_order_id)
  if (po) {
    form.value.supplier_id = Number(po.supplier_id)
    form.value.supplier_name = po.supplier?.supplier_name || po.supplier_name || ''
    form.value.grn_number = String(po.po_number || '').replace('PO', 'GRN')
    form.value.shipping_cost = Number(po.shipping_cost || 0)

    try {
      const poResponse = await procurementService.getPurchaseOrder(form.value.purchase_order_id)
      const poDetail = poResponse?.data || poResponse
      const poItems = poDetail?.items || []

      if (Array.isArray(poItems) && poItems.length > 0) {
        form.value.line_items = poItems.map((item: any) => ({
          product_id: item.product_id ? Number(item.product_id) : null,
          description: item.product?.product_name || item.product_name || item.description || '',
          quantity: Number(item.quantity_ordered || 0),
          unit_price: Number(item.unit_cost || item.unit_price || 0),
          tax_rate: Number(item.tax_rate || 0),
          tax_amount: 0,
        }))
      }
    } catch (error) {
      toast.add({
        severity: 'warn',
        summary: 'PO Lines',
        detail: 'Could not auto-load PO line items. You can still enter them manually.',
        life: 3000,
      })
    }

    calculateTotals()
  }
}

function calculateLineAmount() {
  calculateTotals()
}

function calculateTotals() {
  form.value.tax_amount = Number(totalTax.value || 0)
  form.value.invoice_amount = Number(subtotal.value || 0)
  form.value.net_amount =
    Number(form.value.invoice_amount || 0) +
    Number(form.value.tax_amount || 0) +
    Number(form.value.shipping_cost || 0) -
    Number(form.value.discount_amount || 0)
}

function addLineItem() {
  form.value.line_items.push({
    product_id: null,
    description: '',
    quantity: 0,
    unit_price: 0,
    tax_rate: 12,
    tax_amount: 0,
  })
}

function removeLineItem(index: number) {
  form.value.line_items.splice(index, 1)
  calculateTotals()
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH').format(value)
}

function formatDateForAPI(date: any): string {
  let result = ''
  if (!date) {
    result = new Date().toISOString().split('T')[0] ?? ''
  } else if (date instanceof Date) {
    result = date.toISOString().split('T')[0] ?? ''
  } else {
    result = String(date) || ''
  }
  return result.length > 0 ? result : new Date().toISOString().split('T')[0] ?? '2026-03-10'
}

async function saveDraft() {
  try {
    saving.value = true
    calculateTotals()
    const payload = {
      supplier_id: form.value.supplier_id,
      purchase_order_id: form.value.purchase_order_id,
      invoice_number: form.value.invoice_number,
      invoice_date: formatDateForAPI(form.value.invoice_date),
      due_date: formatDateForAPI(form.value.due_date),
      invoice_amount: Number(form.value.invoice_amount || 0),
      tax_amount: Number(form.value.tax_amount || 0),
      shipping_cost: Number(form.value.shipping_cost || 0),
      discount_amount: Number(form.value.discount_amount || 0),
      currency: (form.value.currency || 'PHP').toUpperCase(),
      remarks: form.value.notes || null,
      items: form.value.line_items.map((item: any) => {
        const qty = Number(item.quantity || 0)
        const unit = Number(item.unit_price || 0)
        const line = Number((qty * unit).toFixed(2))
        const taxRate = Number(item.tax_rate || 0)
        return {
          product_id: item.product_id || null,
          quantity_invoiced: qty,
          unit_price: unit,
          line_amount: line,
          tax_rate: taxRate,
          tax_amount: Number(((line * taxRate) / 100).toFixed(2)),
        }
      }),
    }
    const response = await procurementService.createInvoice(payload)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Invoice saved as draft',
      life: 3000,
    })
    router.push({
      name: 'procurement.invoices.detail',
      params: { id: response.data?.id || response.data?.data?.id },
    })
  } catch (error) {
    const message = (error as any)?.response?.data?.message || 'Failed to save invoice'
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: message,
      life: 3000,
    })
  } finally {
    saving.value = false
  }
}

async function submitForm() {
  if (!form.value.purchase_order_id) {
    toast.add({
      severity: 'warn',
      summary: 'Required',
      detail: 'Please select a purchase order',
      life: 3000,
    })
    return
  }

  if (!form.value.invoice_number) {
    toast.add({
      severity: 'warn',
      summary: 'Required',
      detail: 'Please enter invoice number',
      life: 3000,
    })
    return
  }

  await saveDraft()
}

function goBack() {
  router.push({ name: 'procurement.invoices' })
}

onMounted(() => {
  loadPurchaseOrders()
})
</script>
