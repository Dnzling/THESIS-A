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
              Selecting a PO auto-fills supplier and expected amounts
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
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded">
            <div>
              <p class="text-gray-600 text-sm">Subtotal</p>
              <p class="text-2xl font-bold text-gray-800">₱ {{ formatNumber(subtotal) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Tax</p>
              <p class="text-2xl font-bold text-orange-600">₱ {{ formatNumber(totalTax) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Discount</p>
              <p class="text-2xl font-bold">₱ {{ formatNumber(form.discount_amount || 0) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Total Amount</p>
              <p class="text-3xl font-bold text-green-600">₱ {{ formatNumber(form.invoice_amount) }}</p>
            </div>
          </div>

          <!-- Discount Input -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
    { description: '', quantity: 0, unit_price: 0, tax_rate: 12, tax_amount: 0 },
  ],
  discount_amount: 0,
  tax_amount: 0,
  invoice_amount: 0,
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

function onPOSelected() {
  const po = purchaseOrders.value.find((p: any) => p.id === form.value.purchase_order_id)
  if (po) {
    form.value.supplier_id = Number(po.supplier_id)
    form.value.supplier_name = po.supplier_name
    form.value.grn_number = po.po_number.replace('PO', 'GRN')
  }
}

function calculateLineAmount() {
  calculateTotals()
}

function calculateTotals() {
  form.value.tax_amount = totalTax.value
  form.value.invoice_amount = subtotal.value + totalTax.value - (form.value.discount_amount || 0)
}

function addLineItem() {
  form.value.line_items.push({
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
    const payload = {
      ...form.value,
      invoice_date: formatDateForAPI(form.value.invoice_date),
      due_date: formatDateForAPI(form.value.due_date),
      status: 'draft' as const,
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
      params: { id: response.data.data.id },
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save invoice',
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
