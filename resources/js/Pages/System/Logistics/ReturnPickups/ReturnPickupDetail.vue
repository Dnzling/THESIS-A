<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-slate-200/80 bg-linear-to-br from-amber-50 via-white to-sky-50 p-6 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
          <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Return Pickup</h1>
            <p class="mt-1 text-sm text-slate-600">
              {{ orderNumber }} • {{ formatStatus(pickup?.status) }}
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button icon="pi pi-refresh" label="Refresh" outlined @click="loadPickup" />
          <Button
            v-if="pickup && pickup.status !== 'picked_up' && pickup.status !== 'cancelled'"
            icon="pi pi-calendar"
            label="Edit Schedule"
            severity="secondary"
            outlined
            @click="openScheduleDialog"
          />
          <Button
            v-if="pickup && pickup.status !== 'picked_up' && pickup.status !== 'cancelled'"
            icon="pi pi-user-plus"
            label="Assign Driver"
            severity="info"
            @click="assignDialogVisible = true"
          />
          <Button
            v-if="pickup && pickup.status !== 'picked_up' && pickup.status !== 'cancelled'"
            icon="pi pi-check-circle"
            label="Upload Pickup Proof"
            severity="success"
            @click="proofDialogVisible = true"
          />
        </div>
      </div>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Pickup Snapshot</template>
      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading...</div>
        <div v-else-if="!pickup" class="text-sm text-slate-500">No pickup data found.</div>
        <div v-else class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">Scheduled:</span> <strong>{{ pickup.scheduled_at ? formatDateTime(pickup.scheduled_at) : '-' }}</strong></div>
          <div><span class="text-slate-500">Driver:</span> <strong>{{ driverLabel }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Pickup Address:</span> <strong>{{ pickup.pickup_address || '-' }}</strong></div>
          <div><span class="text-slate-500">Contact:</span> <strong>{{ pickup.pickup_name || '-' }}</strong></div>
          <div><span class="text-slate-500">Phone:</span> <strong>{{ pickup.pickup_phone || '-' }}</strong></div>
          <div><span class="text-slate-500">Picked Up At:</span> <strong>{{ pickup.picked_up_at ? formatDateTime(pickup.picked_up_at) : '-' }}</strong></div>
        </div>
      </template>
    </Card>

    <Card v-if="pickup?.return_request" class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Return Context</template>
      <template #content>
        <div class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">Return ID:</span> <strong>#{{ pickup.return_request?.id || pickup.return_id || '-' }}</strong></div>
          <div><span class="text-slate-500">Customer:</span> <strong>{{ pickup.return_request?.user?.full_name || pickup.return_request?.user?.email || '-' }}</strong></div>
          <div><span class="text-slate-500">Email:</span> <strong>{{ pickup.return_request?.user?.email || '-' }}</strong></div>
          <div><span class="text-slate-500">Reason:</span> <strong>{{ pickup.return_request?.reason || '-' }}</strong></div>
          <div><span class="text-slate-500">Qty:</span> <strong>{{ pickup.return_request?.requested_quantity ?? 1 }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Details:</span> <strong>{{ pickup.return_request?.details || '-' }}</strong></div>
          <div class="md:col-span-2 flex flex-wrap gap-2">
            <Button
              v-if="pickup.return_request?.evidence_urls?.length"
              icon="pi pi-images"
              label="View Evidence"
              severity="warn"
              outlined
              @click="evidenceDialogVisible = true"
            />
            <Button
              v-if="pickup.return_request?.order_id"
              icon="pi pi-external-link"
              label="Open Order"
              outlined
              severity="secondary"
              @click="openOrder(pickup.return_request.order_id)"
            />
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

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Proof</template>
      <template #content>
        <div v-if="pickup?.proof_photo_url" class="flex flex-wrap gap-2">
          <Button v-if="pickup?.proof_photo_url" icon="pi pi-image" severity="warn" label="View Photo" outlined @click="openMedia(pickup.proof_photo_url)" />
        </div>
        <Message v-else severity="info" :closable="false">No pickup proof uploaded yet.</Message>
      </template>
    </Card>

    <Dialog v-model:visible="assignDialogVisible" modal header="Assign Driver" class="w-full max-w-xl">
      <div class="space-y-3">
        <Select v-model="assignForm.driver_user_id" :options="drivers" optionLabel="name" optionValue="id" fluid placeholder="Select driver" />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="assignDialogVisible = false" />
        <Button icon="pi pi-check" label="Assign" :loading="assigning" :disabled="!assignForm.driver_user_id" @click="assignDriver" />
      </template>
    </Dialog>

    <Dialog v-model:visible="proofDialogVisible" modal header="Upload Pickup Proof" class="w-full max-w-xl">
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Pickup Photo</label>
          <input type="file" accept="image/*" class="block w-full text-sm" @change="onProofPhoto" />
        </div>
        <Textarea v-model="proofNotes" rows="3" fluid placeholder="Notes (optional)" />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="proofDialogVisible = false" />
        <Button icon="pi pi-upload" label="Upload" severity="success" :loading="uploading" :disabled="!proofPhoto" @click="uploadProof" />
      </template>
    </Dialog>

    <Dialog v-model:visible="evidenceDialogVisible" header="Evidence" modal class="w-full max-w-5xl">
      <Galleria
        v-if="evidenceItems.length"
        :value="evidenceItems"
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

    <Dialog v-model:visible="mediaDialogVisible" modal header="Preview" class="w-full max-w-5xl">
      <div class="flex justify-center bg-black/5 rounded-lg overflow-hidden">
        <img v-if="mediaUrl" :src="mediaUrl" alt="Preview" class="max-h-[75vh] w-auto object-contain" />
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="mediaDialogVisible = false" />
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
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const id = computed(() => Number((route as any).params?.id))
const loading = ref(false)
const pickup = ref<any>(null)

const orderNumber = computed(() => pickup.value?.return_request?.order?.order_number || `Return #${pickup.value?.return_id || '-'}`)

const driverLabel = computed(() => {
  const d = pickup.value?.driver
  if (!d) return '-'
  const name = `${d.fname || ''} ${d.lname || ''}`.trim()
  return name || d.email || '-'
})

const goBack = () => router.push({ name: 'logistics.return-pickups' })

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

const drivers = ref<any[]>([])
const assignDialogVisible = ref(false)
const assigning = ref(false)
const assignForm = reactive({ driver_user_id: null as null | number })

const loadDrivers = async () => {
  try {
    const res = await logisticsService.getDrivers()
    drivers.value = res?.data || []
  } catch {
    drivers.value = []
  }
}

const assignDriver = async () => {
  if (!assignForm.driver_user_id) return
  assigning.value = true
  try {
    await logisticsService.assignReturnPickupDriver(id.value, { driver_user_id: assignForm.driver_user_id })
    toast.add({ severity: 'success', summary: 'Assigned', detail: 'Driver assigned.', life: 2500 })
    assignDialogVisible.value = false
    await loadPickup()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to assign driver.', life: 3000 })
  } finally {
    assigning.value = false
  }
}

const proofDialogVisible = ref(false)
const uploading = ref(false)
const proofPhoto = ref<File | null>(null)
const proofNotes = ref('')

const onProofPhoto = (e: any) => {
  const file = e?.target?.files?.[0]
  proofPhoto.value = file || null
}
const uploadProof = async () => {
  if (!proofPhoto.value) return
  uploading.value = true
  try {
    const fd = new FormData()
    fd.append('photo', proofPhoto.value)
    if (proofNotes.value.trim()) fd.append('notes', proofNotes.value.trim())
    await logisticsService.uploadReturnPickupProof(id.value, fd)
    toast.add({ severity: 'success', summary: 'Uploaded', detail: 'Pickup proof uploaded.', life: 2500 })
    proofDialogVisible.value = false
    proofPhoto.value = null
    proofNotes.value = ''
    await loadPickup()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Failed to upload proof.', life: 3000 })
  } finally {
    uploading.value = false
  }
}

const evidenceDialogVisible = ref(false)
const evidenceItems = computed(() => {
  const urls: string[] = pickup.value?.return_request?.evidence_urls || []
  return urls.map((url: string, idx: number) => ({ url, name: `Evidence ${idx + 1}` }))
})

const mediaDialogVisible = ref(false)
const mediaUrl = ref<string | null>(null)
const openMedia = (url: string) => {
  mediaUrl.value = url
  mediaDialogVisible.value = true
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
  await Promise.all([loadPickup(), loadDrivers()])
})
</script>
