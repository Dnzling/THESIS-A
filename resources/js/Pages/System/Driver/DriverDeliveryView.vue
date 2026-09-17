<template>
  <div class="mx-auto space-y-2 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      
      <div class="flex gap-2 justify-end items-center">
        <Button v-if="nextStatus" icon="pi pi-check" :label="nextStatusLabel" size="small" :loading="updating" @click="beginAdvance" />
      </div>
    </div>
  
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Delivery Details</template>
      <template #content>
        <div v-if="loading" class="py-10 text-center text-slate-500">Loading delivery...</div>
        <div v-else-if="detail" class="space-y-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div><small class="text-slate-500">Reference</small>
              <p class="font-semibold">{{ detail.order?.order_number || detail.order?.po_number || '-' }}</p>
            </div>
            <div><small class="text-slate-500">Status</small>
              <p>
                <Tag :value="statusLabel" :severity="detail.delivery?.status === 'delivered' ? 'success' : 'info'" />
              </p>
            </div>
            <div><small class="text-slate-500">Supplier / Recipient</small>
              <p>{{ recipientName }}</p>
            </div>
            <div><small class="text-slate-500">Driver</small>
              <p>{{ detail.delivery?.driver_name || '-' }}</p>
            </div>
            <div><small class="text-slate-500">Vehicle</small>
              <p>{{ vehicleLabel }}</p>
            </div>
            <div><small class="text-slate-500">Expected date</small>
              <p>{{ formatDate(detail.delivery?.expected_delivery_date || detail.order?.expected_delivery_date) }}</p>
            </div>
          </div>
  
          <div class="grid gap-3 md:grid-cols-2">
            <div class="rounded-2xl bg-slate-50 p-4"><small class="text-slate-500">Pickup address</small>
              <p class="mt-1 font-medium">{{ pickupAddress }}</p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4"><small class="text-slate-500">Store destination</small>
              <p class="mt-1 font-medium">{{ destinationAddress }}</p>
            </div>
          </div>
  
          <div v-if="lastKnownAddress" v-show="trackingActive" class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
            <div class="flex gap-2"><i class="pi pi-map-marker mt-0.5 text-blue-600" />
              <div>
                <p class="font-semibold">Last known location</p>
                <p>{{ lastKnownAddress }}</p>
              </div>
            </div>
          </div>

          <div v-show="trackingActive" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3">
              <div>
                <h3 class="font-semibold text-slate-900">Live Delivery Tracking</h3>
                <p class="text-xs text-slate-500">Your current GPS position refreshes the delivery is active.</p>
              </div>
              <Tag :value="trackingActive ? 'Live tracking active' : (isDelivered ? 'Tracking completed' : 'Tracking unavailable')"
                :severity="trackingActive ? 'success' : (isDelivered ? 'secondary' : 'warn')" />
            </div>
            <div ref="mapElement" class="h-[360px] w-full sm:h-[440px]"></div>
            <div v-if="!destinationPoint" class="px-4 py-3 text-sm text-amber-700">
              The store destination does not have map coordinates yet.
            </div>
          </div>
  
          <div>
            <h3 class="mb-3 font-semibold text-slate-900">Purchase Order Items</h3>
            <DataTable :value="detail.order?.items || []" stripedRows responsiveLayout="scroll" class="text-sm">
              <template #empty>
                <div class="py-6 text-center text-slate-500">No PO items found.</div>
              </template>
              <Column header="Product"><template #body="{ data }">
                  <p class="font-medium">{{ data.product?.product_name || data.product_name || `Product
                    #${data.product_id}` }}</p><small class="text-slate-500">{{ data.product?.sku || data.sku || '-'
                    }}</small>
                </template></Column>
              <Column header="Variation"><template #body="{ data }">{{ data.variation?.variation_name ||
                  data.variation_name || '-' }}</template></Column>
              <Column field="quantity_ordered" header="Ordered" />
              <Column header="Received"><template #body="{ data }">{{ data.quantity_received ?? 0 }}</template></Column>
              <Column header="Unit Cost"><template #body="{ data }">₱{{ money(data.unit_cost ?? data.unit_price)
                  }}</template></Column>
              <Column header="Line Total"><template #body="{ data }">₱{{ money(data.total_cost ?? data.line_total ??
                  Number(data.quantity_ordered || 0) * Number(data.unit_cost || 0)) }}</template></Column>
            </DataTable>
          </div>
  
          <div>
            <h3 class="mb-3 font-semibold text-slate-900">Delivery Activity</h3>
            <div v-if="detail.logs?.length" class="divide-y divide-slate-200">
              <div v-for="log in detail.logs" :key="log.id" class="space-y-2 py-4">
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <p class="font-medium">{{ label(log.event_type || log.status_to || 'Update') }}</p>
                    <p v-if="log.notes || log.message" class="text-sm text-slate-600">{{ log.notes || log.message }}</p>
                  </div><small class="text-slate-500">{{ formatDateTime(log.logged_at || log.created_at) }}</small>
                </div>
                <p v-if="log.location_address" class="text-sm text-blue-700"><i class="pi pi-map-marker mr-1" />{{
                  log.location_address }}</p>
                <small v-else-if="log.latitude && log.longitude" class="text-slate-500">Location captured</small>
                <div v-if="log.attachments?.length" class="flex flex-wrap gap-3"><a v-for="attachment in log.attachments"
                    :key="attachment.id" :href="attachment.public_url" target="_blank" rel="noopener"><img
                      :src="attachment.public_url" alt="Delivery proof"
                      class="h-28 w-36 rounded-xl border border-slate-200 object-cover transition hover:scale-105" /></a></div>
              </div>
            </div>
            <p v-else class="text-sm text-slate-500">No activity recorded.</p>
          </div>
        </div>
        <p v-else class="py-10 text-center text-slate-500">Delivery not found.</p>
      </template>
    </Card>
  
    <Dialog v-model:visible="proofDialog" modal :header="nextStatusLabel" :style="{ width: 'min(94vw, 560px)' }">
      <div class="space-y-4">
        <Message severity="info" :closable="false">{{ proofInstruction }}</Message>
        <div><label class="mb-2 block text-sm font-medium text-slate-700">{{ proofPhotoLabel }} *</label><input type="file"
            accept="image/*" capture="environment" class="block w-full rounded-xl border border-slate-300 p-3 text-sm"
            @change="selectPhoto" /></div>
        <img v-if="photoPreview" :src="photoPreview" alt="Proof preview"
          class="max-h-64 w-full rounded-2xl border border-slate-200 object-contain" />
        <div v-if="capturedAddress" class="rounded-xl bg-blue-50 p-3 text-sm text-blue-900">
          <p class="font-medium">Detected location</p>
          <p>{{ capturedAddress }}</p>
        </div>
        <Textarea v-model="statusNotes" rows="3" fluid placeholder="Optional pickup notes" />
      </div>
      <template #footer><Button label="Cancel" text @click="proofDialog = false" /><Button
          :label="proofSubmitLabel" icon="pi pi-map-marker" :disabled="!proofPhoto" :loading="updating"
          @click="submitPickupStatus" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'; import Card from 'primevue/card'; import Tag from 'primevue/tag'; import Dialog from 'primevue/dialog'; import Message from 'primevue/message'; import Textarea from 'primevue/textarea'; import DataTable from 'primevue/datatable'; import Column from 'primevue/column'
import logisticsService from '@/services/logistics.service'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'
import { fetchMapboxRoadRoute, mapboxAttribution, mapboxTileUrl, reverseGeocodeMapbox } from '@/utils/mapbox'

const route = useRoute(); const router = useRouter(); const toast = useToast()
const loading = ref(false); const updating = ref(false); const sharingLocation = ref(false); const detail = ref<any>(null); const resolvedCurrentAddress = ref('')
const proofDialog = ref(false); const proofPhoto = ref<File | null>(null); const photoPreview = ref(''); const statusNotes = ref(''); const capturedAddress = ref('')
const mapElement = ref<HTMLElement | null>(null)
const trackingActive = ref(false)
let trackingMap: L.Map | null = null
let truckMarker: L.Marker | null = null
let destinationMarker: L.Marker | null = null
let trackingLine: L.Polyline | null = null
let tileLayerAdded = false
let locationWatcher: number | null = null
let locationTimer: number | null = null
let lastLocationSentAt = 0
const source = computed(() => String(route.params.source || '').toLowerCase()); const orderId = computed(() => String(route.params.orderId))
const label = (value: string) => String(value || 'Pending').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
const isDelivered = computed(() => String(detail.value?.delivery?.status || '').toLowerCase() === 'delivered')
const statusLabel = computed(() => label(detail.value?.delivery?.status))
const recipientName = computed(() => detail.value?.order?.supplier?.supplier_name || detail.value?.order?.shipping_name || detail.value?.order?.customer_name || '-')
const pickupAddress = computed(() => detail.value?.delivery?.origin_address || detail.value?.order?.supplier?.address || detail.value?.order?.assigned_branch?.address || detail.value?.order?.assignedBranch?.address || '-')
const destinationAddress = computed(() => detail.value?.delivery?.destination_address || detail.value?.order?.shipping_address || detail.value?.order?.branch?.address || '-')
const destinationPoint = computed<[number, number] | null>(() => {
  const branch = detail.value?.order?.branch || {}
  const latitude = Number(source.value === 'ecommerce'
    ? detail.value?.order?.customer_latitude
    : branch.latitude ?? detail.value?.delivery?.destination_latitude)
  const longitude = Number(source.value === 'ecommerce'
    ? detail.value?.order?.customer_longitude
    : branch.longitude ?? detail.value?.delivery?.destination_longitude)
  return Number.isFinite(latitude) && Number.isFinite(longitude) && latitude !== 0 && longitude !== 0
    ? [latitude, longitude]
    : null
})
const lastKnownAddress = computed(() => detail.value?.delivery?.current_address || resolvedCurrentAddress.value)
const vehicleLabel = computed(() => `${detail.value?.delivery?.vehicle?.vehicle_name || detail.value?.delivery?.truck_brand || '-'} · ${detail.value?.delivery?.plate_number || detail.value?.delivery?.vehicle?.plate_number || '-'}`)
const currentDeliveryStatus = computed(() => String(detail.value?.delivery?.status || '').toLowerCase())
const nextStatus = computed(() => source.value === 'pickup'
  ? ({ pending: 'in_transit', in_transit: 'delivered' } as any)[currentDeliveryStatus.value]
  : ({ assigned: 'in_transit', packed: 'in_transit', shipped: 'in_transit', in_transit: 'out_for_delivery', on_the_way: 'out_for_delivery', out_for_delivery: 'delivered' } as any)[currentDeliveryStatus.value])
const nextStatusLabel = computed(() => {
  if (nextStatus.value === 'out_for_delivery') return 'Mark Out for Delivery'
  if (nextStatus.value === 'delivered') return source.value === 'pickup' ? 'Confirm Arrival at Store' : 'Mark Delivered'
  return source.value === 'pickup' ? 'Start Supplier Pickup' : `Mark ${label(nextStatus.value)}`
})
const proofPhotoLabel = computed(() => nextStatus.value === 'delivered'
  ? (source.value === 'pickup' ? 'Arrival at store photo' : 'Delivered order photo')
  : (source.value === 'pickup' ? 'Supplier pickup photo' : 'Dispatch pickup photo'))
const proofInstruction = computed(() => nextStatus.value === 'delivered'
  ? `Attach a photo showing the ${source.value === 'pickup' ? 'supplies arriving at the store' : 'order delivered to the customer'}. Your final GPS location will be captured before tracking stops.`
  : `Attach a photo showing the ${source.value === 'pickup' ? 'supplies at the supplier pickup point' : 'order before dispatch'}. Your GPS location will be captured before marking it In Transit.`)
const proofSubmitLabel = computed(() => nextStatus.value === 'delivered' ? 'Mark Delivered' : 'Mark In Transit')
const money = (value: any) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); const formatDate = (value: any) => value ? new Date(value).toLocaleDateString() : '-'; const formatDateTime = (value: any) => value ? new Date(value).toLocaleString() : '-'
const coordinates = () => new Promise<{ latitude: number; longitude: number }>((resolve, reject) => { if (!navigator.geolocation) return reject(new Error('Location is not supported by this browser.')); navigator.geolocation.getCurrentPosition(p => resolve({ latitude: p.coords.latitude, longitude: p.coords.longitude }), reject, { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }) })
const reverseGeocode = async (point: { latitude: number; longitude: number }) => reverseGeocodeMapbox(point.latitude, point.longitude)
const load = async () => { loading.value = true; try { const response = await logisticsService.getDeliveryOrderDetail(source.value as any, orderId.value); detail.value = response?.data || null; const delivery = detail.value?.delivery; resolvedCurrentAddress.value = ''; if (!delivery?.current_address && delivery?.current_latitude && delivery?.current_longitude) { resolvedCurrentAddress.value = await reverseGeocode({ latitude: Number(delivery.current_latitude), longitude: Number(delivery.current_longitude) }).catch(() => '') } updateTrackingState(); loading.value = false; await nextTick(); await renderTrackingMap() } catch (error: any) { toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load delivery.', life: 3000 }) } finally { loading.value = false } }
const selectPhoto = (event: Event) => { const file = (event.target as HTMLInputElement).files?.[0] || null; proofPhoto.value = file; if (photoPreview.value) URL.revokeObjectURL(photoPreview.value); photoPreview.value = file ? URL.createObjectURL(file) : '' }
const beginAdvance = async () => { if (!nextStatus.value) return; const needsProof = ['pickup', 'ecommerce'].includes(source.value) && ['in_transit', 'delivered'].includes(nextStatus.value); if (needsProof) { proofPhoto.value = null; statusNotes.value = ''; capturedAddress.value = ''; if (photoPreview.value) URL.revokeObjectURL(photoPreview.value); photoPreview.value = ''; proofDialog.value = true; return } await updateRegularDelivery() }
const updateRegularDelivery = async () => {
  const targetStatus = nextStatus.value
  if (!targetStatus) return
  updating.value = true
  try {
    const point = await coordinates()
    const locationAddress = await reverseGeocode(point).catch(() => '')
    await logisticsService.updateUnifiedDeliveryStatus(source.value as any, orderId.value, {
      status: targetStatus,
      latitude: point.latitude,
      longitude: point.longitude,
      location_address: locationAddress || undefined,
    })
    await load()
    toast.add({ severity: 'success', summary: 'Status Updated', detail: targetStatus === 'out_for_delivery' ? 'The order is now out for delivery.' : 'Delivery progress and GPS location were saved.', life: 2500 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Update Failed', detail: error?.response?.data?.message || error?.message || 'GPS access is required to update this delivery.', life: 3500 })
  } finally { updating.value = false }
}
const submitPickupStatus = async () => { if (!proofPhoto.value || !nextStatus.value) return; const targetStatus = nextStatus.value; updating.value = true; try { const point = await coordinates(); capturedAddress.value = await reverseGeocode(point).catch(() => ''); const form = new FormData(); form.append('status', targetStatus); form.append('latitude', String(point.latitude)); form.append('longitude', String(point.longitude)); if (capturedAddress.value) form.append('location_address', capturedAddress.value); form.append('photo', proofPhoto.value); if (statusNotes.value.trim()) form.append('notes', statusNotes.value.trim()); await logisticsService.updateUnifiedDeliveryStatus(source.value as any, orderId.value, form); proofDialog.value = false; await load(); toast.add({ severity: 'success', summary: targetStatus === 'delivered' ? 'Delivery Completed' : 'Delivery In Transit', detail: 'The proof photo and GPS location were recorded.', life: 3000 }) } catch (error: any) { toast.add({ severity: 'error', summary: 'Submission Failed', detail: error?.response?.data?.message || error?.message || 'Photo and current location are required.', life: 3500 }) } finally { updating.value = false } }
const shareLocation = async () => { sharingLocation.value = true; try { const point = await coordinates(); await sendLiveLocation(point, true) } catch (error: any) { toast.add({ severity: 'warn', summary: 'Location Unavailable', detail: error?.message || 'Could not access your location.', life: 3000 }) } finally { sharingLocation.value = false } }

const sendLiveLocation = async (point: { latitude: number; longitude: number }, refresh = false) => {
  if (!['pickup', 'ecommerce'].includes(source.value) || isDelivered.value) return
  const now = Date.now()
  if (!refresh && now - lastLocationSentAt < 5000) return
  lastLocationSentAt = now
  const locationAddress = await reverseGeocode(point).catch(() => '')
  await logisticsService.updateUnifiedDeliveryLocation(source.value as 'pickup' | 'ecommerce', orderId.value, {
    location_address: locationAddress || undefined,
    ...point,
  })
  if (detail.value?.delivery) {
    detail.value.delivery.current_latitude = point.latitude
    detail.value.delivery.current_longitude = point.longitude
    detail.value.delivery.current_address = locationAddress || detail.value.delivery.current_address
  }
  await renderTrackingMap()
}

const updateTrackingState = () => {
  const status = String(detail.value?.delivery?.status || '').toLowerCase()
  const shouldTrack = ['pickup', 'ecommerce'].includes(source.value) && ['in_transit', 'out_for_delivery', 'on_the_way'].includes(status)
  trackingActive.value = shouldTrack
  if (shouldTrack && locationWatcher === null) startLocationTracking()
  if (!shouldTrack && locationWatcher !== null) stopLocationTracking()
}

const startLocationTracking = () => {
  if (!navigator.geolocation || locationWatcher !== null) return
  locationWatcher = navigator.geolocation.watchPosition(
    (position) => {
      sendLiveLocation({ latitude: position.coords.latitude, longitude: position.coords.longitude }).catch(() => undefined)
    },
    () => { trackingActive.value = false },
    { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 },
  )
  locationTimer = window.setInterval(() => {
    coordinates().then(point => sendLiveLocation(point)).catch(() => undefined)
  }, 5000)
}

const stopLocationTracking = () => {
  if (locationWatcher !== null) navigator.geolocation.clearWatch(locationWatcher)
  if (locationTimer !== null) window.clearInterval(locationTimer)
  locationWatcher = null
  locationTimer = null
  trackingActive.value = false
}

const fetchRoadRoute = fetchMapboxRoadRoute

const renderTrackingMap = async () => {
  if (!mapElement.value || !['pickup', 'ecommerce'].includes(source.value)) return
  const destinationIcon = L.icon({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    shadowSize: [41, 41],
    shadowAnchor: [12, 41],
  })
  const truckIcon = L.icon({
    iconUrl: '/images/truck-map-marker-orange.png',
    iconSize: [100, 100],
    iconAnchor: [48, 48],
    popupAnchor: [0, -48],
  })
  if (!trackingMap) trackingMap = L.map(mapElement.value).setView(destinationPoint.value || [14.5995, 120.9842], destinationPoint.value ? 13 : 10)
  if (!trackingMap) return
  if (!tileLayerAdded) {
    L.tileLayer(mapboxTileUrl(), { attribution: mapboxAttribution, tileSize: 512, zoomOffset: -1 }).addTo(trackingMap)
    tileLayerAdded = true
  }

  const delivery = detail.value?.delivery || {}
  const logs = (detail.value?.logs || []).slice().reverse().filter((log: any) => log.latitude !== null && log.longitude !== null)
  const points: [number, number][] = logs.map((log: any) => [Number(log.latitude), Number(log.longitude)])
    .filter(([latitude, longitude]) => Number.isFinite(latitude) && Number.isFinite(longitude))
  if (delivery.current_latitude && delivery.current_longitude) points.push([Number(delivery.current_latitude), Number(delivery.current_longitude)])

  const lastPoint = points[points.length - 1]
  if (lastPoint) {
    if (!truckMarker) truckMarker = L.marker(lastPoint, { icon: truckIcon }).addTo(trackingMap).bindTooltip('Truck location')
    else truckMarker.setLatLng(lastPoint)
  }
  if (destinationPoint.value) {
    if (!destinationMarker) destinationMarker = L.marker(destinationPoint.value, { icon: destinationIcon }).addTo(trackingMap).bindTooltip(source.value === 'ecommerce' ? 'Delivery destination' : 'Store destination')
    else destinationMarker.setLatLng(destinationPoint.value)
    let linePoints: [number, number][] = lastPoint ? [lastPoint, destinationPoint.value] : [destinationPoint.value]
    if (lastPoint) {
      try {
        const roadPoints = await fetchRoadRoute(lastPoint, destinationPoint.value)
        if (roadPoints.length > 1) linePoints = roadPoints
      } catch {
        // Keep the direct fallback line if the public routing service is unavailable.
      }
    }
    if (!trackingMap) return
    if (!trackingLine) trackingLine = L.polyline(linePoints, { color: '#2563eb', weight: 5, opacity: 0.8 }).addTo(trackingMap)
    else trackingLine.setLatLngs(linePoints)
    const bounds = L.latLngBounds(linePoints)
    if (bounds.isValid()) trackingMap.fitBounds(bounds.pad(0.15), { maxZoom: 15 })
  }
  setTimeout(() => trackingMap?.invalidateSize(), 100)
}

onMounted(load); onBeforeUnmount(() => { stopLocationTracking(); if (trackingMap) trackingMap.remove(); if (photoPreview.value) URL.revokeObjectURL(photoPreview.value) })
</script>
