<template>
  <div class="space-y-6 p-6">
    <div class="flex items-start justify-between gap-4">
      <div class="flex items-start gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push(`/supplier-portal/pos/${poId}/delivery-template`)" />
        <div>
          <h1 class="text-2xl font-semibold text-slate-900">Shipment Confirmation</h1>
          <p class="text-sm text-slate-500">Review delivery details and confirm shipment.</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Button label="Print Shipment" icon="pi pi-print" outlined @click="printShipment" />
        <Tag v-if="po" :value="formatStatus(po.status)" :severity="statusSeverity(po.status)" />
      </div>
    </div>

    <PortalStepper class="mb-4" :steps="steps" :current="2" />

    <div v-if="loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <Card class="lg:col-span-2 rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <Skeleton height="200px" class="rounded-lg" />
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <Skeleton height="240px" class="rounded-lg" />
        </template>
      </Card>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <Card class="lg:col-span-2 rounded-2xl border border-slate-200/70 shadow-sm">
        <template #content>
          <h2 class="text-lg font-semibold text-slate-900">PO Details</h2>
          <div v-if="po" class="mt-4 space-y-6">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">PO Number</span>
                <span class="font-semibold text-slate-900">{{ po.po_number }}</span>
              </div>
              <div class="flex items-center justify-between mt-2">
                <span class="text-slate-500">Branch From</span>
                <span>{{ po.branch?.name || '-' }}</span>
              </div>
              <div class="mt-2 text-slate-500">
                {{ po.branch?.address || '-' }} {{ po.branch?.city || '' }}
              </div>
            </div>

            <div>
              <h3 class="text-sm font-semibold text-slate-700 mb-2">Items</h3>
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
                    <tr v-for="item in po.items || []" :key="item.id" class="border-t">
                      <td class="px-4 py-2">
                        <div class="font-medium text-slate-900">{{ item.product?.product_name || 'Item' }}</div>
                        <div class="text-xs text-slate-400">{{ item.product?.sku || '' }}</div>
                      </td>
                      <td class="px-4 py-2 text-right">{{ item.quantity_ordered }}</td>
                      <td class="px-4 py-2 text-right">PHP {{ formatMoney(item.unit_cost) }}</td>
                      <td class="px-4 py-2 text-right font-semibold">PHP {{ formatMoney(item.line_total) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Subtotal (Items)</span>
                <span class="font-semibold text-slate-900">PHP {{ formatMoney(subtotalAmount) }}</span>
              </div>
              <div class="flex items-center justify-between mt-2">
                <span class="text-slate-500">VAT</span>
                <span class="font-semibold text-slate-900">PHP {{ formatMoney(taxVat) }}</span>
              </div>
              <div class="flex items-center justify-between mt-2">
                <span class="text-slate-500">Delivery Charge</span>
                <span class="font-semibold text-emerald-600">PHP {{ formatMoney(deliveryCharge) }}</span>
              </div>
              <div class="flex items-center justify-between mt-3 border-t border-slate-200 pt-3 text-base font-semibold">
                <span>Total</span>
                <span>PHP {{ formatMoney(totalAmount) }}</span>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <div class="space-y-6">
        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h3 class="text-lg font-semibold text-slate-900">Delivery Details</h3>
            <div class="mt-4 space-y-3 text-sm text-slate-600">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Driver</span>
                <span>{{ draft?.driver_name || '-' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Plate Number</span>
                <span>{{ draft?.plate_number || '-' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Cost per KM</span>
                <span>PHP {{ formatMoney(draft?.cost_per_km || 0) }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Distance</span>
                <span>{{ distanceDisplay }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Tax Rate</span>
                <span>{{ Number(draft?.tax_rate || 12).toFixed(2) }}%</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Expected Delivery</span>
                <span>{{ formatDate(draft?.expected_delivery_date) }}</span>
              </div>
              <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-base font-semibold">
                <span>Delivery Charge</span>
                <span class="text-emerald-600">PHP {{ formatMoney(deliveryCharge) }}</span>
              </div>
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h3 class="text-lg font-semibold text-slate-900">Delivery Logs</h3>
            <p class="text-sm text-slate-500 mb-4">Timeline of recorded events.</p>
            <div class="space-y-3">
              <div v-if="logsLoading" class="text-sm text-slate-500">Loading logs…</div>
              <div v-else-if="!deliveryLogs.length" class="text-sm text-slate-500">No events yet. Add one below.</div>
              <div v-else class="space-y-2">
                <div v-for="log in deliveryLogs" :key="log.id" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                  <div class="flex items-center justify-between text-xs uppercase text-slate-500">
                    <span>{{ log.event_type }}</span>
                    <span>{{ formatDate(log.logged_at, true) }}</span>
                  </div>
                  <p class="text-slate-700 mt-2">{{ log.notes || 'No notes recorded' }}</p>
                </div>
              </div>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-3">
              <Select
                v-model="logEventType"
                :options="logTypes"
                optionLabel="label"
                optionValue="value"
                placeholder="Select milestone"
                class="w-full"
              />
              <TextArea v-model="logNotes" rows="2" placeholder="Notes (optional)" autoResize />
              <Button
                label="Add Log"
                icon="pi pi-save"
                class="w-full"
                :loading="logSubmitting"
                @click="submitLog"
                :disabled="!shipment?.id"
              />
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200/70 shadow-sm">
          <template #content>
            <h3 class="text-lg font-semibold text-slate-900">Confirmation</h3>
            <p class="text-sm text-slate-500 mt-2">This will create the shipment record for this PO.</p>
            <Button
              label="Confirm Shipment"
              icon="pi pi-check"
              class="w-full mt-4"
              :loading="submitting"
              @click="confirmSend"
            />
          </template>
        </Card>
      </div>
    </div>

    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import ConfirmDialog from 'primevue/confirmdialog'
import Skeleton from 'primevue/skeleton'
import PortalStepper from '../../../components/system/supplier/PortalStepper.vue'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const poId = Number(route.params.id)
const po = ref<any>(null)
const draft = ref<any>(null)
const shipment = ref<any>(null)
const submitting = ref(false)
const loading = ref(false)
const deliveryLogs = ref<any[]>([])
const logsLoading = ref(false)
const logEventType = ref('Arrived')
const logNotes = ref('')
const logSubmitting = ref(false)

const steps = [
  { label: 'Review PO', description: 'Approve or reject' },
  { label: 'Delivery Form', description: 'Set delivery details' },
  { label: 'Shipment', description: 'Confirm shipment' },
]

const logTypes = [
  { label: 'Arrived', value: 'Arrived' },
  { label: 'Started unloading', value: 'Start Unloading' },
  { label: 'Finished unloading', value: 'Finish Unloading' },
  { label: 'Delivered', value: 'Delivered' },
  { label: 'Issue', value: 'Issue' },
]

const deliveryCharge = computed(() => {
  if (!draft.value) return 0
  const km = Number(draft.value.distance_km || 0)
  const rate = Number(draft.value.cost_per_km || 0)
  return km * rate
})

const distanceDisplay = computed(() => {
  const km = draft.value?.distance_km
  return km ? `${Number(km).toFixed(2)} km` : '-'
})

const formatMoney = (value: number) => new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2 }).format(Number(value || 0))
const formatDate = (value?: string, includeTime = false) => {
  if (!value) return '-'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  const options: Intl.DateTimeFormatOptions = { year: 'numeric', month: 'short', day: 'numeric' }
  if (includeTime) {
    options.hour = 'numeric'
    options.minute = '2-digit'
  }
  return date.toLocaleDateString('en-PH', options)
}


const subtotalAmount = computed(() => {
  if (!po.value) return 0
  const direct = Number(po.value.subtotal ?? po.value.sub_total ?? po.value.items_subtotal ?? 0)
  if (direct > 0) return direct
  if (!Array.isArray(po.value.items)) return 0
  return po.value.items.reduce((sum: number, item: any) => {
    const qty = Number(item?.quantity_ordered || 0)
    const unit = Number(item?.unit_cost || 0)
    const line = Number(item?.line_total ?? qty * unit)
    return sum + line
  }, 0)
})

const taxVat = computed(() => Number(po.value?.tax_amount ?? po.value?.vat_amount ?? 0))

const totalAmount = computed(() => subtotalAmount.value + deliveryCharge.value + taxVat.value)

const statusSeverity = (status: string) => {
  if (status === 'sent_to_supplier') return 'info'
  if (status === 'supplier_accepted') return 'success'
  if (status === 'declined_supplier') return 'danger'
  return 'secondary'
}

const formatStatus = (status: string) => status?.split('_').map(word => word[0]?.toUpperCase() + word.slice(1)).join(' ') || '-'

const loadPO = async () => {
  loading.value = true
  try {
    const res = await supplierService.getSupplierPODetail(poId)
    const payload = res.data || res
    po.value = payload?.data?.po || payload?.po || null
  } finally {
    loading.value = false
  }
}

const loadShipment = async () => {
  if (!po.value) return
  try {
    const res = await supplierService.getPOShipment(po.value.id)
    const payload = res.data || res
    shipment.value = payload?.data?.shipment || null
    await loadDeliveryLogs()
  } catch (error) {
    console.error(error)
  }
}

const loadDeliveryLogs = async () => {
  if (!shipment.value?.id) return
  logsLoading.value = true
  try {
    const res = await supplierService.getShipmentLogs(shipment.value.id)
    const payload = res.data || res
    deliveryLogs.value = payload?.data?.logs || []
  } finally {
    logsLoading.value = false
  }
}

const submitLog = async () => {
  if (!shipment.value?.id) return
  logSubmitting.value = true
  try {
    const res = await supplierService.addShipmentLog(shipment.value.id, {
      event_type: logEventType.value,
      notes: logNotes.value || undefined,
      latitude: draft.value?.current_latitude ?? undefined,
      longitude: draft.value?.current_longitude ?? undefined,
    })
    const payload = res.data || res
    if (payload?.data) {
      deliveryLogs.value = [payload.data, ...deliveryLogs.value]
      logNotes.value = ''
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error.response?.data?.message || 'Failed to save log', life: 3000 })
  } finally {
    logSubmitting.value = false
  }
}

const loadDraft = () => {
  const raw = localStorage.getItem(`supplier_delivery_draft_${poId}`)
  if (!raw) return null
  try {
    return JSON.parse(raw)
  } catch {
    return null
  }
}

const confirmSend = () => {
  confirm.require({
    header: 'Confirm Shipment?',
    message: 'This will create the shipment record for this PO. Continue?',
    icon: 'pi pi-exclamation-triangle',
    acceptProps: {
      label: 'Yes, Confirm',
      severity: 'success',
    },
    rejectProps: {
      label: 'Cancel',
      severity: 'secondary',
      outlined: true,
    },
    accept: submitDelivery,
  })
}

const submitDelivery = async () => {
  if (!draft.value) return
  submitting.value = true
  try {
    const response = await supplierService.createPOShipment({
      purchase_order_id: poId,
      truck_brand: draft.value.truck_brand || undefined,
      truck_type: draft.value.truck_type || undefined,
      wheel_count: draft.value.wheel_count ?? undefined,
      plate_number: draft.value.plate_number || undefined,
      driver_name: draft.value.driver_name,
      driver_contact: draft.value.driver_contact || undefined,
      cost_per_km: Number(draft.value.cost_per_km || 0),
      distance_km: draft.value.distance_km ?? null,
      current_latitude: draft.value.current_latitude ?? null,
      current_longitude: draft.value.current_longitude ?? null,
      tax_rate: Number(draft.value.tax_rate || 12),
      expected_delivery_date: draft.value.expected_delivery_date || null,
    })
    const payload = response.data || response
    localStorage.removeItem(`supplier_delivery_draft_${poId}`)
    localStorage.setItem('last_delivery_form_po_id', String(poId))
    toast.add({
      severity: 'success',
      summary: 'Sent',
      detail: payload?.message || 'Shipment created successfully.',
      life: 3000,
    })
    router.push('/supplier-portal/pos')
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to create shipment.',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const printShipment = () => {
  if (!po.value || !draft.value) {
    toast.add({ severity: 'warn', summary: 'Missing Data', detail: 'Shipment details are not available yet.', life: 2500 })
    return
  }
  const itemsRows = (po.value.items || []).map((item: any) => `
    <tr>
      <td>${item.product?.product_name || 'Item'}</td>
      <td style="text-align:right;">${Number(item.quantity_ordered || 0)}</td>
      <td style="text-align:right;">PHP ${formatMoney(item.unit_cost)}</td>
      <td style="text-align:right;">PHP ${formatMoney(item.line_total)}</td>
    </tr>
  `).join('')
  const html = `
    <html>
    <head>
      <title>Shipment Details - ${po.value.po_number}</title>
      <style>
        body { font-family: Arial, sans-serif; color: #0f172a; padding: 24px; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        h2 { font-size: 14px; margin: 20px 0 8px; text-transform: uppercase; color: #475569; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        th { text-align: left; color: #475569; background: #f8fafc; }
        .row { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 12px; }
        .total { font-weight: 700; font-size: 14px; margin-top: 10px; }
      </style>
    </head>
    <body>
      <h1>Shipment Details</h1>
      <div class="row"><span>PO Number</span><span>${po.value.po_number}</span></div>
      <div class="row"><span>Branch</span><span>${po.value.branch?.name || '-'}</span></div>
      <div class="row"><span>Driver</span><span>${draft.value.driver_name || '-'}</span></div>
      <div class="row"><span>Plate Number</span><span>${draft.value.plate_number || '-'}</span></div>
      <div class="row"><span>Distance</span><span>${distanceDisplay.value}</span></div>
      <div class="row"><span>Tax Rate</span><span>${Number(draft.value.tax_rate || 12).toFixed(2)}%</span></div>
      <div class="row"><span>Expected Delivery</span><span>${formatDate(draft.value.expected_delivery_date)}</span></div>
      <div class="row"><span>Delivery Charge</span><span>PHP ${formatMoney(deliveryCharge.value)}</span></div>

      <h2>Items</h2>
      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th style="text-align:right;">Qty</th>
            <th style="text-align:right;">Unit Price</th>
            <th style="text-align:right;">Line Total</th>
          </tr>
        </thead>
        <tbody>${itemsRows}</tbody>
      </table>

      <div class="total">Total: PHP ${formatMoney(totalAmount.value)}</div>
    </body>
    </html>
  `
  const w = window.open('', '_blank')
  if (!w) return
  w.document.open()
  w.document.write(html)
  w.document.close()
  w.focus()
  w.print()
}

onMounted(async () => {
  draft.value = loadDraft()
  if (!draft.value) {
    toast.add({ severity: 'warn', summary: 'Missing Delivery Form', detail: 'Please complete the delivery form first.', life: 2500 })
    router.push(`/supplier-portal/pos/${poId}/delivery-template`)
    return
  }
  await loadPO()
  await loadShipment()
})
</script>
