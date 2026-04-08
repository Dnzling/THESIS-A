<template>
  <div class="supplier-invoice-detail space-y-6 p-6">
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-start gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.back()" />
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Invoice Details</h1>
          <p class="text-sm text-slate-500">Supplier invoice summary and line items.</p>
        </div>
      </div>
      <Button v-if="invoice" label="Print" icon="pi pi-print" outlined @click="printInvoice" />
    </div>

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <Card class="lg:col-span-2 rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <Skeleton height="220px" class="rounded-lg" />
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <Skeleton height="220px" class="rounded-lg" />
        </template>
      </Card>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h2 class="text-lg font-semibold text-slate-900">Invoice Summary</h2>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-slate-600">
              <div>
                <p class="text-xs text-slate-500">Invoice Number</p>
                <p class="font-semibold text-slate-900">{{ invoice?.invoice_number || '-' }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Invoice Date</p>
                <p>{{ formatDate(invoice?.invoice_date) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500">Status</p>
                <p>{{ formatStatus(invoice?.status) }}</p>
              </div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h2 class="text-lg font-semibold text-slate-900 mb-3">Items</h2>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
              <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-slate-500">
                  <tr>
                    <th class="px-4 py-2">Item</th>
                    <th class="px-4 py-2 text-right">Qty</th>
                    <th class="px-4 py-2 text-right">Price</th>
                    <th class="px-4 py-2 text-right">Line Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in invoice?.items || []" :key="item.id" class="border-t">
                    <td class="px-4 py-2">
                      <div class="font-medium text-slate-900">{{ item.product?.product_name || item.description || 'Item' }}</div>
                      <div class="text-xs text-slate-400">{{ item.product?.sku || '' }}</div>
                    </td>
                    <td class="px-4 py-2 text-right">{{ item.quantity_invoiced }}</td>
                    <td class="px-4 py-2 text-right">₱ {{ formatMoney(item.unit_price) }}</td>
                    <td class="px-4 py-2 text-right font-semibold">₱ {{ formatMoney(item.line_amount) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
        </Card>
      </div>

      <div class="space-y-6">
        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h3 class="text-lg font-semibold text-slate-900">Totals</h3>
            <div class="mt-4 space-y-2 text-sm text-slate-600">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Subtotal</span>
                <span class="font-semibold text-slate-900">₱ {{ formatMoney(invoice?.invoice_amount) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Delivery</span>
                <span class="font-semibold text-emerald-600">₱ {{ formatMoney(invoice?.shipping_cost) }}</span>
              </div>
              <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base font-semibold">
                <span>Total</span>
                <span>₱ {{ formatMoney(invoice?.net_amount || 0) }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h3 class="text-lg font-semibold text-slate-900">PO Reference</h3>
            <div class="mt-3 text-sm text-slate-600">
              <div>PO #: <span class="font-medium text-slate-900">{{ po?.po_number || '-' }}</span></div>
              <div>Supplier: <span class="font-medium text-slate-900">{{ po?.supplier?.supplier_name || '-' }}</span></div>
              <div>Branch: <span class="font-medium text-slate-900">{{ po?.branch?.name || '-' }}</span></div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Skeleton from 'primevue/skeleton'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const po = ref<any>(null)
const invoice = ref<any>(null)

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

const formatMoney = (value: number) => new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(value || 0)

const loadInvoice = async () => {
  try {
    loading.value = true
    const id = Number(route.params.id)
    const poRes = await supplierService.getSupplierPODetail(id)
    const poPayload = poRes.data || poRes
    po.value = poPayload?.data?.po || poPayload?.po || null

    const invoiceRes = await supplierService.getPOInvoice(id)
    const invoicePayload = invoiceRes.data || invoiceRes
    invoice.value = invoicePayload?.data || invoicePayload || null
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load invoice.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const printInvoice = () => {
  window.print()
}

onMounted(loadInvoice)
</script>

<style scoped lang="scss">
.supplier-invoice-detail {
  padding: 20px;
}
</style>
