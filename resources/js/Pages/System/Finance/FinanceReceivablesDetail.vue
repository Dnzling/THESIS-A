<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-gray-100 bg-linear-to-r from-slate-50 via-white to-blue-50 p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" rounded text @click="goBack" />
        <div>
          <h1 class="text-xl font-semibold tracking-tight text-gray-900">Receivable Detail</h1>
          <p class="mt-0.5 text-sm text-gray-500">Full order and invoice details for customer billing.</p>
        </div>
      </div>

      <Button icon="pi pi-print" label="Print Invoice" :disabled="!detail" @click="printInvoice" />
      </div>
    </div>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #content>
        <div v-if="loading" class="p-6 text-sm text-gray-500">Loading receivable details...</div>
        <div v-else-if="!detail" class="p-6 text-sm text-gray-500">Receivable record not found.</div>
        <div v-else class="grid grid-cols-1 gap-4 p-6 md:grid-cols-3">
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Invoice Reference</p>
            <p class="mt-1 font-semibold text-gray-900">{{ detail.reference }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Source</p>
            <Tag :value="detail.source_type === 'ecommerce' ? 'Ecommerce' : 'Sales'" :severity="detail.source_type === 'ecommerce' ? 'info' : 'secondary'" />
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Customer</p>
            <p class="mt-1 font-semibold text-gray-900">{{ detail.customer?.name || '-' }}</p>
            <p class="text-sm text-gray-500">{{ detail.customer?.phone || '-' }}</p>
            <p class="text-sm text-gray-500">{{ detail.customer?.email || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Address</p>
            <p class="mt-1 text-sm text-gray-700">{{ detail.billing?.address || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Payment Method</p>
            <p class="mt-1 text-sm text-gray-700">{{ detail.payment?.method || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Payment Status</p>
            <Tag :value="formatStatus(detail.status)" :severity="statusSeverity(detail.status)" />
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Receipt Number</p>
            <p class="mt-1 text-sm text-gray-700">{{ detail.receipt?.receipt_number || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Created Date</p>
            <p class="mt-1 text-sm text-gray-700">{{ formatDate(detail.dates?.created_at) }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Due Date</p>
            <p class="mt-1 text-sm text-gray-700">{{ formatDate(detail.dates?.due_date) }}</p>
          </div>
        </div>
      </template>
    </Card>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6">
          <h2 class="text-lg font-semibold text-gray-900">Delivery Details</h2>
        </div>
      </template>
      <template #content>
        <div class="grid grid-cols-1 gap-4 p-6 pt-2 md:grid-cols-3">
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Tracking #</p>
            <p class="mt-1 text-sm font-medium text-gray-900">{{ detail?.delivery?.tracking_number || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Courier</p>
            <p class="mt-1 text-sm text-gray-700">{{ detail?.delivery?.courier_name || '-' }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wide text-gray-500">Courier Contact</p>
            <p class="mt-1 text-sm text-gray-700">{{ detail?.delivery?.courier_contact || '-' }}</p>
          </div>
          <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500">Distance</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ formatDecimal(displayDistanceKm) }} km</p>
          </div>
          <div class="rounded-xl border border-gray-200 bg-gray-50 p-3">
            <p class="text-xs uppercase tracking-wide text-gray-500">Per KM Charge</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">₱ {{ formatMoney(detail?.delivery?.per_km_charge || 0) }}</p>
          </div>
          <div class="rounded-xl border border-blue-200 bg-blue-50 p-3">
            <p class="text-xs uppercase tracking-wide text-blue-700">Delivery Fee</p>
            <p class="mt-1 text-base font-semibold text-blue-900">₱ {{ formatMoney(detail?.delivery?.estimated_fee || 0) }}</p>
          </div>
        </div>
      </template>
    </Card>

    <Card class="overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
      <template #header>
        <div class="px-6 pt-6">
          <h2 class="text-lg font-semibold text-gray-900">Order Items</h2>
        </div>
      </template>
      <template #content>
        <div class="p-6 pt-2">
          <DataTable :value="detail?.items || []" stripedRows responsiveLayout="scroll" class="p-datatable-sm">
            <Column field="sku" header="SKU" style="min-width: 130px" />
            <Column field="name" header="Item" style="min-width: 220px" />
            <Column field="quantity" header="Qty" style="width: 90px" />
            <Column field="unit_price" header="Unit Price" style="width: 140px">
              <template #body="{ data }">₱ {{ formatMoney(data.unit_price) }}</template>
            </Column>
            <Column field="line_total" header="Line Total" style="width: 150px">
              <template #body="{ data }"><span class="font-semibold text-green-700">₱ {{ formatMoney(data.line_total) }}</span></template>
            </Column>
            <template #empty>
              <div class="py-8 text-center text-sm text-gray-500">No line items found.</div>
            </template>
          </DataTable>

          <div v-if="detail" class="mt-4 grid grid-cols-1 gap-2 text-sm md:ml-auto md:max-w-sm">
            <div class="flex items-center justify-between"><span class="text-gray-500">Subtotal</span><span>₱ {{ formatMoney(detail.amounts?.subtotal) }}</span></div>
            <div class="flex items-center justify-between"><span class="text-gray-500">Discount</span><span>₱ {{ formatMoney(detail.amounts?.discount) }}</span></div>
            <div class="flex items-center justify-between"><span class="text-gray-500">Tax</span><span>₱ {{ formatMoney(detail.amounts?.tax) }}</span></div>
            <div class="flex items-center justify-between"><span class="text-gray-500">Order Total</span><span>₱ {{ formatMoney(detail.amounts?.total) }}</span></div>
            <div class="flex items-center justify-between"><span class="text-gray-500">Delivery Fee</span><span>₱ {{ formatMoney(detail.delivery?.estimated_fee || 0) }}</span></div>
            <div class="flex items-center justify-between border-t border-gray-200 pt-2 text-base font-semibold"><span>Grand Total</span><span>₱ {{ formatMoney(grandTotal) }}</span></div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Tag from 'primevue/tag'
import financeService from '../../../services/finance.service'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const detail = ref<any>(null)

const displayDistanceKm = computed(() => {
  const rawDistance = Number(detail.value?.delivery?.distance_km || 0)
  if (rawDistance > 0) return rawDistance

  const fee = Number(detail.value?.delivery?.estimated_fee || 0)
  const perKm = Number(detail.value?.delivery?.per_km_charge || 0)
  if (fee > 0 && perKm > 0) return fee / perKm

  return rawDistance
})

const grandTotal = computed(() => {
  const orderTotal = Number(detail.value?.amounts?.total || 0)
  const deliveryFee = Number(detail.value?.delivery?.estimated_fee || 0)
  return orderTotal + deliveryFee
})

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount)
}

const formatDecimal = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount)
}

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatStatus = (status: string) => {
  if (!status) return '-'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const statusSeverity = (status: string) => {
  if (status === 'paid') return 'success'
  if (status === 'partial') return 'info'
  if (status === 'pending') return 'warn'
  if (status === 'cancelled') return 'danger'
  return 'secondary'
}

const goBack = () => router.push({ name: 'finance.receivables' })

const loadDetail = async () => {
  loading.value = true
  try {
    const source = String(route.params.source || 'sales') as 'sales' | 'ecommerce'
    const id = Number(route.params.id)
    const res = await financeService.getReceivableDetail(source, id)
    detail.value = res.data || null
  } finally {
    loading.value = false
  }
}

const printInvoice = () => {
  if (!detail.value) return

  const d = detail.value
  const deliveryFee = Number(d.delivery?.estimated_fee || 0)
  const orderTotal = Number(d.amounts?.total || 0)
  const printGrandTotal = orderTotal + deliveryFee
  const rows = (d.items || [])
    .map((item: any) => `
      <tr>
        <td style="padding:10px 12px;border-bottom:1px solid #edf0f4;">${item.sku || '-'}</td>
        <td style="padding:10px 12px;border-bottom:1px solid #edf0f4;">${item.name || '-'}</td>
        <td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;">${item.quantity || 0}</td>
        <td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;">PHP ${formatMoney(item.unit_price || 0)}</td>
        <td style="padding:10px 12px;border-bottom:1px solid #edf0f4;text-align:right;font-weight:600;">PHP ${formatMoney(item.line_total || 0)}</td>
      </tr>
    `)
    .join('')

  const html = `
    <html>
      <head>
        <title>Invoice ${d.reference}</title>
        <style>
          body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; margin: 0; background: #f5f7fb; color: #101828; }
          .page { max-width: 920px; margin: 24px auto; background: #ffffff; border: 1px solid #e7ebf1; border-radius: 16px; padding: 24px; }
          .top { display: flex; justify-content: space-between; align-items: start; gap: 12px; margin-bottom: 20px; }
          .title { font-size: 26px; font-weight: 700; letter-spacing: -0.3px; margin: 0; }
          .subtitle { margin: 4px 0 0 0; color: #667085; font-size: 12px; }
          .pill { background: #eff4ff; border: 1px solid #dbe8ff; color: #1d4ed8; font-size: 11px; font-weight: 600; border-radius: 999px; padding: 6px 10px; }
          .meta-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 18px; }
          .meta-card { border: 1px solid #edf0f4; background: #fafbfd; border-radius: 10px; padding: 10px; }
          .meta-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: #667085; margin-bottom: 4px; }
          .meta-value { font-size: 12px; font-weight: 600; color: #1f2937; }
          .section-title { margin: 18px 0 10px 0; font-size: 13px; font-weight: 700; color: #111827; }
          table { width: 100%; border-collapse: collapse; font-size: 12px; border: 1px solid #edf0f4; border-radius: 12px; overflow: hidden; }
          thead th { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; background: #f8fafc; color: #475467; font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; }
          .totals { margin-top: 16px; margin-left: auto; width: 360px; border: 1px solid #edf0f4; border-radius: 12px; padding: 12px 14px; background: #fcfdff; }
          .row { display: flex; justify-content: space-between; font-size: 12px; padding: 3px 0; color: #374151; }
          .row strong { color: #111827; }
          .grand { margin-top: 8px; padding-top: 8px; border-top: 1px solid #dfe4ea; font-size: 14px; font-weight: 700; }
          .footer { margin-top: 20px; font-size: 10px; color: #98a2b3; text-align: center; }
        </style>
      </head>
      <body>
        <div class="page">
          <div class="top">
            <div>
              <h2 class="title">Official Receipt</h2>
              <p class="subtitle">Reference: ${d.reference} ${d.receipt?.receipt_number ? `| Receipt: ${d.receipt.receipt_number}` : ''}</p>
            </div>
            <div class="pill">${d.source_type === 'ecommerce' ? 'Ecommerce' : 'Sales'} Invoice</div>
          </div>

          <div class="meta-grid">
            <div class="meta-card">
              <div class="meta-label">Customer</div>
              <div class="meta-value">${d.customer?.name || '-'}</div>
            </div>
            <div class="meta-card">
              <div class="meta-label">Phone</div>
              <div class="meta-value">${d.customer?.phone || '-'}</div>
            </div>
            <div class="meta-card">
              <div class="meta-label">Issue Date</div>
              <div class="meta-value">${formatDate(d.dates?.created_at)}</div>
            </div>
            <div class="meta-card">
              <div class="meta-label">Status</div>
              <div class="meta-value">${formatStatus(d.status)}</div>
            </div>
          </div>

          <div class="meta-card" style="margin-bottom: 12px;">
            <div class="meta-label">Billing Address</div>
            <div class="meta-value">${d.billing?.address || '-'}</div>
          </div>

          <h3 class="section-title">Order Items</h3>
          <table>
          <thead>
            <tr>
              <th>SKU</th>
              <th>Item</th>
              <th style="text-align:right;">Qty</th>
              <th style="text-align:right;">Unit Price</th>
              <th style="text-align:right;">Line Total</th>
            </tr>
          </thead>
          <tbody>${rows}</tbody>
        </table>

          <div class="totals">
            <div class="row"><span>Subtotal</span><strong>PHP ${formatMoney(d.amounts?.subtotal || 0)}</strong></div>
            <div class="row"><span>Discount</span><strong>PHP ${formatMoney(d.amounts?.discount || 0)}</strong></div>
            <div class="row"><span>Tax</span><strong>PHP ${formatMoney(d.amounts?.tax || 0)}</strong></div>
            <div class="row"><span>Shipping Fee</span><strong>PHP ${formatMoney(d.amounts?.shipping_fee || 0)}</strong></div>
            <div class="row"><span>Delivery Fee</span><strong>PHP ${formatMoney(deliveryFee)}</strong></div>
            <div class="row"><span>Order Total</span><strong>PHP ${formatMoney(orderTotal)}</strong></div>
            <div class="row grand"><span>Grand Total</span><strong>PHP ${formatMoney(printGrandTotal)}</strong></div>
          </div>

          <div class="footer">
            Generated on ${formatDate(new Date().toISOString())}
          </div>
        </div>
      </body>
    </html>
  `

  const printWindow = window.open('', '_blank', 'width=900,height=700')
  if (!printWindow) return
  printWindow.document.open()
  printWindow.document.write(html)
  printWindow.document.close()
  printWindow.focus()
  setTimeout(() => {
    printWindow.print()
  }, 250)
}

onMounted(loadDetail)
</script>
