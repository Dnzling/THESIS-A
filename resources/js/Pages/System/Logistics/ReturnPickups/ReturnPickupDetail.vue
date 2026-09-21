<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
          <div>
            <h1 class="text-xl font-semibold text-slate-900">{{ returnIdLabel }}</h1>
            <p class="mt-1 text-sm text-slate-600">
              Customer Return Pickup
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Tag :value="formatStatus(pickup?.status || 'ready_for_dispatch')" :severity="pickup?.status === 'picked_up' ? 'success' : 'warning'" />
          <Button
            v-if="pickup && ['ready_for_dispatch', 'scheduled'].includes(pickup.status)"
            icon="pi pi-calendar"
            label="Edit Schedule"
            severity="secondary"
            outlined
            @click="openScheduleDialog"
          />
          <Button
            v-if="pickup && ['ready_for_dispatch', 'scheduled'].includes(pickup.status)"
            icon="pi pi-send"
            label="Assign Delivery"
            severity="warn"
            size="small"
            @click="openAssignment"
          />
        </div>
      </div>
    </header>

    <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
      <template #title><span class="text-base">Pickup & Courier Overview</span></template>
      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading...</div>
        <div v-else-if="!pickup" class="text-sm text-slate-500">No pickup data found.</div>
        <div v-else class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">Scheduled:</span> <strong>{{ pickup.scheduled_at ? formatDateTime(pickup.scheduled_at) : '-' }}</strong></div>
          <div><span class="text-slate-500">Driver:</span> <strong>{{ driverLabel }}</strong></div>
          <div><span class="text-slate-500">Vehicle:</span> <strong>{{ vehicleLabel }}</strong></div>
          <div><span class="text-slate-500">Plate Number:</span> <strong>{{ pickup.vehicle?.plate_number || '-' }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Pickup Address:</span> <strong>{{ pickup.pickup_address || '-' }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Delivery Destination:</span> <strong>{{ pickup.destination_branch ? `${pickup.destination_branch.name} - ${pickup.destination_branch.address || ''}` : '-' }}</strong></div>
          <div><span class="text-slate-500">Contact:</span> <strong>{{ pickup.pickup_name || '-' }}</strong></div>
          <div><span class="text-slate-500">Phone:</span> <strong>{{ pickup.pickup_phone || '-' }}</strong></div>
          <div><span class="text-slate-500">Picked Up At:</span> <strong>{{ pickup.picked_up_at ? formatDateTime(pickup.picked_up_at) : '-' }}</strong></div>
          <div><span class="text-slate-500">Distance:</span> <strong>{{ pickup.distance_km != null ? `${pickup.distance_km} km` : '-' }}</strong></div>
          <div><span class="text-slate-500">Estimated Fee:</span> <strong>₱ {{ formatMoney(pickup.estimated_fee) }}</strong></div>
          <div v-if="pickup.assistants?.length" class="md:col-span-2">
            <span class="text-slate-500">Delivery Assistants:</span>
            <div class="mt-2 flex flex-wrap gap-2">
              <Chip v-for="assistant in pickup.assistants" :key="assistant.id" :label="assistant.name" />
            </div>
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="pickup?.logs?.length" class="rounded-2xl border border-slate-200/80 shadow-sm">
      <template #title><span class="text-base">Delivery Activity</span></template>
      <template #content>
        <div class="divide-y divide-slate-100">
          <div v-for="log in pickup.logs" :key="log.id" class="py-3 first:pt-0 last:pb-0">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div><p class="font-medium text-slate-900">{{ formatStatus(log.event_type) }}</p><p class="mt-1 text-sm text-slate-600">{{ formatLogMessage(log.message) }}</p></div>
              <span class="text-xs text-slate-500">{{ formatDateTime(log.created_at) }}</span>
            </div>
            <p v-if="log.location_address" class="mt-1 text-xs text-slate-500"><i class="pi pi-map-marker mr-1"></i>{{ log.location_address }}</p>
            <a v-if="log.proof_photo_url" :href="log.proof_photo_url" target="_blank" class="mt-2 block h-20 w-20 overflow-hidden rounded-lg border border-slate-200"><img :src="log.proof_photo_url" alt="Delivery proof" class="h-full w-full object-cover" /></a>
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="pickup?.return_request" class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Return Context</template>
      <template #content>
        <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">Return Number:</span> <strong>{{ pickup.return_request?.return_number || '-' }}</strong></div>
          <div><span class="text-slate-500">Customer:</span> <strong>{{ pickup.return_request?.user?.full_name || pickup.return_request?.user?.email || '-' }}</strong></div>
          <div><span class="text-slate-500">Email:</span> <strong>{{ pickup.return_request?.user?.email || '-' }}</strong></div>
          <div><span class="text-slate-500">Reason:</span> <strong>{{ pickup.return_request?.reason || '-' }}</strong></div>
          <div><span class="text-slate-500">Qty:</span> <strong>{{ pickup.return_request?.requested_quantity ?? 1 }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Details:</span> <strong>{{ pickup.return_request?.details || '-' }}</strong></div>
          <div class="md:col-span-2 space-y-2">
            <p class="text-slate-500">Evidence</p>
            <div v-if="evidenceItems.length" class="flex flex-wrap gap-3">
              <button v-for="(evidence, index) in evidenceItems" :key="evidence.url" type="button"
                class="group relative h-24 w-24 overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                :aria-label="`Preview ${evidence.name}`" @click="openEvidence(index)">
                <img :src="evidence.url" :alt="evidence.name"
                  class="h-full w-full object-cover transition group-hover:scale-105" />
                <span class="absolute inset-x-0 bottom-0 bg-slate-950/60 px-1.5 py-1 text-center text-[10px] text-white">
                  {{ evidence.name }}
                </span>
              </button>
            </div>
            <p v-else class="text-sm text-slate-400">No evidence attached.</p>
            <div class="flex flex-wrap gap-2">
              <Button
                v-if="pickup.return_request?.order_id"
                icon="pi pi-external-link"
                label="Open Order"
                outlined
                severity="secondary"
                @click="openOrder(pickup.return_request.order_id)"
              />
            </div>
            <!-- <Button
              v-if="pickup.return_id"
              icon="pi pi-external-link"
              label="Open Sales Return"
              outlined
              severity="secondary"
              @click="openSalesReturn(pickup.return_id)"
            /> -->
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="pickup?.return_request" class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Returned Items</template>
      <template #content>
        <DataTable :value="returnItems" size="small" stripedRows responsiveLayout="scroll">
          <Column field="product_name" header="Product" style="min-width: 14rem" />
          <Column field="sku" header="SKU" style="min-width: 10rem" />
          <Column field="quantity" header="Qty" style="width: 6rem" />
          <Column field="unit_price" header="Unit Price" style="width: 10rem">
            <template #body="{ data }">₱ {{ formatMoney(data.unit_price) }}</template>
          </Column>
          <Column field="line_total" header="Line Total" style="width: 10rem">
            <template #body="{ data }">₱ {{ formatMoney(data.line_total) }}</template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="evidenceDialogVisible" header="Evidence" modal class="w-full max-w-5xl">
      <Galleria
        v-if="evidenceItems.length"
        :value="evidenceItems"
        v-model:activeIndex="activeEvidenceIndex"
        :numVisible="6"
        :circular="true"
        :showItemNavigators="true"
        :showThumbnails="true"
        containerStyle="max-width: 100%"
      >
        <template #item="{ item }">
          <div class="flex justify-center bg-black/5 rounded-lg overflow-hidden">
            <img :src="item.url" :alt="item.name" class="max-h-[520px] w-auto object-contain" />
          </div>
        </template>
        <template #thumbnail="{ item }">
          <img :src="item.url" :alt="item.name" class="h-14 w-14 object-cover rounded-md" />
        </template>
      </Galleria>
      <div v-else class="py-10 text-center text-sm text-gray-600">No evidence.</div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="evidenceDialogVisible = false" />
      </template>
    </Dialog>

    <Dialog v-model:visible="scheduleDialogVisible" modal header="Edit Pickup Schedule" class="w-full max-w-3xl">
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Scheduled At</label>
          <DatePicker v-model="scheduleForm.scheduled_at" :minDate="getTodayDate()" showIcon showTime hourFormat="12" class="w-full" />
          <small class="text-xs text-slate-500">Schedule can be set to today.</small>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
          <div>
            <label class="mb-1 block text-sm text-slate-600">Pickup Name</label>
            <InputText v-model="scheduleForm.pickup_name" class="w-full" />
          </div>
          <div>
            <label class="mb-1 block text-sm text-slate-600">Pickup Phone</label>
            <InputText v-model="scheduleForm.pickup_phone" class="w-full" />
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Pickup Address</label>
          <Textarea v-model="scheduleForm.pickup_address" rows="3" class="w-full" autoResize />
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-3 space-y-2">
          <div class="flex flex-col md:flex-row gap-2 md:items-end">
            <div class="flex-1">
              <label class="mb-1 block text-sm text-slate-600">Search location</label>
              <InputText v-model="mapState.searchQuery" class="w-full" placeholder="Search address / city" />
            </div>
            <Button icon="pi pi-search" label="Search" :loading="mapState.searching" @click="searchLocation" />
          </div>
          <div class="text-xs text-slate-500">Customer coords: {{ customerCoordsLabel }}</div>
          <div id="return-pickup-map" class="h-64 w-full rounded-xl border border-slate-200" />
          <div class="text-xs text-slate-500">Tip: Click the map to reposition the marker.</div>
        </div>

        <div>
          <label class="mb-1 block text-sm text-slate-600">Notes</label>
          <Textarea v-model="scheduleForm.notes" rows="2" class="w-full" autoResize />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="scheduleDialogVisible = false" />
        <Button icon="pi pi-save" label="Save" :loading="savingSchedule" @click="saveSchedule" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import logisticsService from '@/services/logistics.service'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Chip from 'primevue/chip'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const id = computed(() => Number((route as any).params?.id))
const loading = ref(false)
const pickup = ref<any>(null)

const returnIdLabel = computed(() => pickup.value?.return_request?.return_number || 'Return number unavailable')

const driverLabel = computed(() => {
  const d = pickup.value?.driver
  if (!d) return '-'
  const name = `${d.fname || ''} ${d.lname || ''}`.trim()
  return name || d.email || '-'
})
const vehicleLabel = computed(() => {
  const vehicle = pickup.value?.vehicle
  if (!vehicle) return '-'
  return [vehicle.vehicle_name, [vehicle.brand, vehicle.model].filter(Boolean).join(' ')].filter(Boolean).join(' · ')
})

const goBack = () => router.push({ name: 'logistics.deliveries' })

const loadPickup = async () => {
  loading.value = true
  try {
    const res = await logisticsService.getReturnPickup(id.value)
    pickup.value = res?.data || null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load pickup.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const formatStatus = (status?: string) => {
  if (!status) return '-'
  return String(status).replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
}
const formatLogMessage = (message?: string) => String(message || '').replace(/\b(ready_for_dispatch|out_for_delivery|in_transit|assigned|delivered)\b/g, value => formatStatus(value))

const formatDateTime = (value: any) => {
  const date = new Date(value)
  return new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' }).format(date)
}

const openOrder = (orderId: number) => router.push({ name: 'logistics.deliveries.detail', params: { source: 'ecommerce', orderId } })
const openSalesReturn = (returnId: number) => router.push({ name: 'sales.returns.detail', params: { id: returnId } })

const returnItems = computed(() => {
  const rr = pickup.value?.return_request
  if (!rr) return []
  const orderItem = rr.order_item || rr.orderItem
  const productName = orderItem?.product?.product_name || orderItem?.product_name || rr.product_name || '-'
  const sku = orderItem?.product?.sku || orderItem?.sku || rr.sku || '-'
  const unitPrice = Number(orderItem?.unit_price || 0)
  const qty = Number(rr.requested_quantity ?? 1)
  return [
    {
      product_name: productName,
      sku,
      quantity: qty,
      unit_price: unitPrice,
      line_total: unitPrice * qty,
    },
  ]
})

const formatMoney = (value: any) => {
  const num = Number(value || 0)
  return num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const openAssignment = () => router.push({
  name: 'logistics.deliveries.create',
  query: { source: 'return_pickup', order_id: String(id.value) },
})

const evidenceDialogVisible = ref(false)
const activeEvidenceIndex = ref(0)
const evidenceItems = computed(() => {
  const urls: string[] = pickup.value?.return_request?.evidence_urls || []
  return urls.map((url: string, idx: number) => ({ url, name: `Evidence ${idx + 1}` }))
})
const openEvidence = (index: number) => {
  activeEvidenceIndex.value = index
  evidenceDialogVisible.value = true
}

const scheduleDialogVisible = ref(false)
const savingSchedule = ref(false)
const scheduleForm = reactive({
  scheduled_at: null as any,
  pickup_name: '',
  pickup_phone: '',
  pickup_address: '',
  notes: '',
})

const getTodayDate = () => new Date()

const openScheduleDialog = async () => {
  if (!pickup.value) return
  scheduleForm.scheduled_at = pickup.value.scheduled_at ? new Date(pickup.value.scheduled_at) : null
  scheduleForm.pickup_name = pickup.value.pickup_name || pickup.value.return_request?.order?.shipping_name || ''
  scheduleForm.pickup_phone = pickup.value.pickup_phone || pickup.value.return_request?.order?.shipping_phone || ''
  scheduleForm.pickup_address = pickup.value.pickup_address || pickup.value.return_request?.order?.shipping_address || ''
  scheduleForm.notes = pickup.value.notes || ''
  mapState.searchQuery = scheduleForm.pickup_address || pickup.value.return_request?.order?.shipping_address || mapState.searchQuery
  scheduleDialogVisible.value = true
  await nextTick()
  initMap()
}

const toIsoDateTime = (date: Date) => {
  const d = new Date(date)
  const yyyy = d.getFullYear()
  const mm = String(d.getMonth() + 1).padStart(2, '0')
  const dd = String(d.getDate()).padStart(2, '0')
  const hh = String(d.getHours()).padStart(2, '0')
  const mi = String(d.getMinutes()).padStart(2, '0')
  const ss = String(d.getSeconds()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd} ${hh}:${mi}:${ss}`
}

const saveSchedule = async () => {
  if (!pickup.value) return
  savingSchedule.value = true
  try {
    const payload: any = {
      status: pickup.value.status,
      scheduled_at: scheduleForm.scheduled_at ? toIsoDateTime(scheduleForm.scheduled_at) : null,
      pickup_name: scheduleForm.pickup_name || undefined,
      pickup_phone: scheduleForm.pickup_phone || undefined,
      pickup_address: scheduleForm.pickup_address || undefined,
      notes: scheduleForm.notes || undefined,
    }
    await logisticsService.updateReturnPickup(id.value, payload)
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Pickup schedule updated.', life: 2500 })
    scheduleDialogVisible.value = false
    await loadPickup()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to update pickup.', life: 3000 })
  } finally {
    savingSchedule.value = false
  }
}

declare const L: any
let leafletMap: any = null
let leafletMarker: any = null
let mapReady = false
const mapState = reactive({
  searching: false,
  searchQuery: 'Dasmariñas City, Cavite',
  latitude: null as number | null,
  longitude: null as number | null,
})

const customerCoordsLabel = computed(() => {
  const lat = pickup.value?.return_request?.order?.customer_latitude
  const lng = pickup.value?.return_request?.order?.customer_longitude
  if (!lat || !lng) return '-'
  return `${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`
})

const initMap = () => {
  const container = document.getElementById('return-pickup-map')
  if (!container || typeof L === 'undefined') return

  const lat = Number(mapState.latitude ?? pickup.value?.return_request?.order?.customer_latitude ?? 14.3294) || 14.3294
  const lng = Number(mapState.longitude ?? pickup.value?.return_request?.order?.customer_longitude ?? 120.9367) || 120.9367
  mapState.latitude = Number(lat.toFixed(6))
  mapState.longitude = Number(lng.toFixed(6))

  if (!mapReady) {
    leafletMap = L.map(container).setView([lat, lng], 14)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors',
    }).addTo(leafletMap)

    leafletMap.on('click', (e: any) => {
      mapState.latitude = Number(e.latlng.lat.toFixed(6))
      mapState.longitude = Number(e.latlng.lng.toFixed(6))
      redrawMarker()
    })

    mapReady = true
  }

  redrawMarker()
  setTimeout(() => leafletMap?.invalidateSize(), 150)
}

const redrawMarker = () => {
  if (!leafletMap) return
  const lat = Number(mapState.latitude ?? 14.3294) || 14.3294
  const lng = Number(mapState.longitude ?? 120.9367) || 120.9367

  if (leafletMarker) leafletMarker.remove()
  leafletMarker = L.marker([lat, lng], { draggable: true }).addTo(leafletMap)
  leafletMarker.on('dragend', () => {
    const pos = leafletMarker.getLatLng()
    mapState.latitude = Number(pos.lat.toFixed(6))
    mapState.longitude = Number(pos.lng.toFixed(6))
  })

  leafletMap.setView([lat, lng], 14)
}

async function searchLocation() {
  if (!mapState.searchQuery.trim()) return
  mapState.searching = true
  try {
    const q = encodeURIComponent(mapState.searchQuery.trim())
    const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${q}`)
    const results = await res.json()
    if (results && results.length > 0) {
      const first = results[0]
      mapState.latitude = Number(Number(first.lat).toFixed(6))
      mapState.longitude = Number(Number(first.lon).toFixed(6))
      redrawMarker()
    }
  } catch (e) {
    console.warn('Search failed', e)
  } finally {
    mapState.searching = false
  }
}

watch(
  () => scheduleDialogVisible.value,
  async (visible) => {
    if (visible) {
      await nextTick()
      initMap()
    }
  },
)

onBeforeUnmount(() => {
  if (leafletMap) {
    leafletMap.remove()
    leafletMap = null
    leafletMarker = null
    mapReady = false
  }
})

onMounted(async () => {
  await loadPickup()
})
</script>
