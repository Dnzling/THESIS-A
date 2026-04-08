<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Edit Invoice</h2>
        <p class="text-sm text-gray-500 mt-1">{{ invoice?.invoice_number }}</p>
      </div>
    </div>

    <!-- Status Alert -->
    <div v-if="invoice?.status !== 'draft'" class="bg-orange-50 border border-orange-200 rounded p-4">
      <p class="text-sm text-orange-800">
        <strong>Note:</strong> This invoice has been {{ invoice?.status }}. Only certain fields can be edited.
      </p>
    </div>

    <!-- Form -->
    <Card>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <!-- Basic Info - Read Only for Approved -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Invoice Number
              </label>
              <InputText
                v-model="invoice.invoice_number"
                :disabled="invoice.status !== 'draft'"
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Invoice Date
              </label>
              <DatePicker
                v-model="invoice.invoice_date"
                dateFormat="yy-mm-dd"
                :disabled="invoice.status !== 'draft'"
                class="w-full"
              />
            </div>
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-2">
                Due Date
              </label>
              <DatePicker
                v-model="invoice.due_date"
                dateFormat="yy-mm-dd"
                class="w-full"
              />
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Notes
            </label>
            <Textarea
              v-model="invoice.notes"
              :disabled="invoice.status === 'paid'"
              rows="3"
              class="w-full"
            />
          </div>

          <!-- Line Items - Read only if approved -->
          <div v-if="invoice.status === 'draft'" class="border-t pt-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-semibold text-gray-800">Line Items</h3>
              <Button
                icon="pi pi-plus"
                label="Add Item"
                class="p-button-sm"
                @click="addLineItem"
              />
            </div>

            <DataTable :value="invoiceItems" class="p-datatable-sm">
              <Column field="description" header="Description" style="width: 30%">
                <template #body="{ data }">
                  <InputText
                    v-model="data.description"
                    placeholder="Item description"
                    class="w-full"
                  />
                </template>
              </Column>
              <Column field="quantity" header="Qty" style="width: 10%">
                <template #body="{ data }">
                  <InputNumber
                    v-model="data.quantity"
                    placeholder="0"
                    class="w-full"
                  />
                </template>
              </Column>
              <Column field="unit_price" header="Unit Price" style="width: 12%">
                <template #body="{ data }">
                  <InputNumber
                    v-model="data.unit_price"
                    mode="currency"
                    currency="PHP"
                    class="w-full"
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
          <div v-if="invoice.status === 'draft'" class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded">
            <div>
              <p class="text-gray-600 text-sm">Subtotal</p>
              <p class="text-2xl font-bold text-gray-800">₱ {{ formatNumber(invoice.net_amount) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Tax</p>
              <p class="text-2xl font-bold text-orange-600">₱ {{ formatNumber(invoice.tax_amount) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Discount</p>
              <p class="text-2xl font-bold">₱ {{ formatNumber(invoice.discount_amount || 0) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Total Amount</p>
              <p class="text-3xl font-bold text-green-600">₱ {{ formatNumber(invoice.total_amount) }}</p>
            </div>
          </div>

          <!-- Read Only Totals -->
          <div v-else class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50 p-4 rounded">
            <div>
              <p class="text-gray-600 text-sm">Subtotal</p>
              <p class="text-2xl font-bold text-gray-800">₱ {{ formatNumber(invoice.net_amount) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Tax</p>
              <p class="text-2xl font-bold text-orange-600">₱ {{ formatNumber(invoice.tax_amount) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Discount</p>
              <p class="text-2xl font-bold">₱ {{ formatNumber(invoice.discount_amount || 0) }}</p>
            </div>
            <div>
              <p class="text-gray-600 text-sm">Total Amount</p>
              <p class="text-3xl font-bold text-green-600">₱ {{ formatNumber(invoice.total_amount) }}</p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-2 justify-end pt-4 border-t">
            <Button label="Cancel" severity="secondary" @click="goBack" />
            <Button
              v-if="invoice.status === 'draft'"
              label="Save Changes"
              icon="pi pi-save"
              @click="submitForm"
              :loading="saving"
            />
            <Button
              v-else
              label="Done"
              icon="pi pi-arrow-left"
              @click="goBack"
            />
          </div>
        </form>
      </template>
    </Card>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()

// State
const invoice = ref<any>(null)
const invoiceItems = ref<any[]>([])
const saving = ref(false)
const loading = ref(false)

// Methods
async function loadInvoice() {
  loading.value = true
  try {
    const response = await procurementService.getInvoice(Number(route.params.id))
    invoice.value = response.data?.data || response.data
    invoiceItems.value = invoice.value?.items || []
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load invoice',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function addLineItem() {
  invoiceItems.value.push({
    description: '',
    quantity: 0,
    unit_price: 0,
    tax_rate: 12,
  })
}

function removeLineItem(index: number) {
  invoiceItems.value.splice(index, 1)
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH').format(value)
}

async function submitForm() {
  if (invoice.value.status !== 'draft') {
    toast.add({
      severity: 'warn',
      summary: 'Not Editable',
      detail: 'Only draft invoices can be edited',
      life: 3000,
    })
    return
  }

  saving.value = true
  try {
    await procurementService.updateInvoice(invoice.value.id, {
      ...invoice.value,
      items: invoiceItems.value,
    })
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Invoice updated',
      life: 3000,
    })
    goBack()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to update invoice',
      life: 3000,
    })
  } finally {
    saving.value = false
  }
}

function goBack() {
  router.push({
    name: 'procurement.invoices.detail',
    params: { id: invoice.value.id },
  })
}

onMounted(() => {
  loadInvoice()
})
</script>
