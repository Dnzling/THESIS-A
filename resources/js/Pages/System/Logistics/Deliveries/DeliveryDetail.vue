<template>
  <div class="mx-auto max-w-7xl space-y-4 px-4 py-5 text-sm sm:px-6 lg:px-8">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" text rounded size="small" severity="secondary" @click="goBack" />
        <div><p class="text-xs font-medium uppercase tracking-wide text-orange-600">{{ sourceLabel }} delivery</p><h1 class="text-xl font-semibold text-slate-900">{{ order?.order_number || order?.po_number || 'Delivery Details' }}</h1></div>
      </div>
      <div class="flex items-center gap-2">
        <Tag :value="formatStatus(delivery?.status || 'pending')" :severity="deliverySeverity(delivery?.status)" class="text-xs" />
        <Button icon="pi pi-refresh" label="Refresh" outlined severity="secondary" size="small" :loading="loading" @click="loadAll" />
        <Button v-if="canAssignDelivery" icon="pi pi-send" label="Assign Delivery" severity="warn" size="small" @click="openAssign" />
      </div>
    </header>

    <div v-if="loading && !order" class="grid gap-4 lg:grid-cols-3"><Skeleton v-for="item in 6" :key="item" height="8rem" borderRadius="16px" /></div>
    <template v-else-if="order">
      <section class="grid gap-4 lg:grid-cols-[1.25fr_0.75fr]">
        <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
          <template #title><span class="text-base">Order Overview</span></template>
          <template #content><div class="grid gap-4 sm:grid-cols-2">
            <Info label="Customer" :value="customerName || '-'" /><Info label="Contact" :value="customerContact || '-'" />
            <Info label="Delivery Address" :value="deliveryAddress || '-'" class="sm:col-span-2" />
            <Info label="Order Status" :value="formatStatus(order.status)" /><Info label="Order Total" :value="formatCurrency(order.total_amount || order.grand_total)" />
            <Info label="Tracking Number" :value="delivery?.tracking_number || '-'" /><Info label="Expected Delivery" :value="formatDateTime(delivery?.estimated_delivery_at || delivery?.scheduled_delivery_at || delivery?.expected_delivery_date)" />
          </div></template>
        </Card>

        <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
          <template #title><span class="text-base">Courier Details</span></template>
          <template #content><div v-if="delivery" class="space-y-4">
            <div><p class="font-semibold text-slate-900">{{ driverName }}</p><p class="text-xs text-slate-500">Primary driver</p></div>
            <div class="grid grid-cols-2 gap-3"><Info label="Contact" :value="delivery.courier_contact || delivery.driver_contact || delivery.driver?.phone_number || '-'" /><Info label="Plate Number" :value="delivery.vehicle?.plate_number || delivery.plate_number || '-'" /><Info label="Vehicle" :value="vehicleLabel" class="col-span-2" /></div>
            <div v-if="delivery.assistants?.length" class="border-t border-slate-100 pt-3"><p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Delivery Assistants</p><div class="flex flex-wrap gap-2"><Chip v-for="assistant in delivery.assistants" :key="assistant.id" :label="assistant.branch ? `${assistant.name} - ${assistant.branch}` : assistant.name" class="text-xs" /></div></div>
          </div><p v-else class="text-slate-500">No courier has been assigned.</p></template>
        </Card>
      </section>

      <Card v-if="delivery" class="rounded-2xl border border-slate-200/80 shadow-sm">
        <template #title><div class="flex items-center justify-between gap-2"><div><p class="text-base">Live Delivery Tracking</p><p class="text-xs font-normal text-slate-500">Latest driver location and destination</p></div><Tag :value="currentPoint ? 'Location available' : 'Waiting for location'" :severity="currentPoint ? 'success' : 'warn'" class="text-xs" /></div></template>
        <template #content><div ref="mapElement" class="h-[340px] w-full overflow-hidden rounded-xl border border-slate-200"></div><p v-if="!currentPoint" class="mt-2 text-xs text-amber-700">The map updates after the driver shares a GPS location.</p></template>
      </Card>

      <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
        <template #title><span class="text-base">Order Items ({{ orderItems.length }})</span></template>
        <template #content><DataTable :value="orderItems" size="small" stripedRows responsiveLayout="scroll" class="text-xs">
          <template #empty><div class="py-8 text-center text-slate-500">No order items recorded.</div></template>
          <Column header="Product" style="min-width:15rem"><template #body="{ data }"><p class="font-medium text-slate-900">{{ itemName(data) }}</p><p class="text-xs text-slate-500">SKU: {{ itemSku(data) }}</p></template></Column>
          <Column header="Quantity / UOM"><template #body="{ data }"><span class="font-medium">{{ formatQuantity(itemQuantity(data)) }} {{ itemUom(data) }}</span></template></Column>
          <Column header="Unit Price"><template #body="{ data }">{{ formatCurrency(itemUnitPrice(data)) }}</template></Column>
          <Column header="Line Total"><template #body="{ data }"><span class="font-semibold">{{ formatCurrency(itemLineTotal(data)) }}</span></template></Column>
        </DataTable></template>
      </Card>

      <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
        <template #title><div><p class="text-base">Delivery Logs</p><p class="text-xs font-normal text-slate-500">Read-only progress from the assigned delivery team</p></div></template>
        <template #content>
          <div v-if="!logs.length" class="py-10 text-center text-slate-500">No delivery activity has been recorded.</div>
          <div v-else class="space-y-3"><article v-for="entry in logs" :key="entry.id" class="rounded-xl border border-slate-200 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2"><div><Tag :value="formatStatus(entry.event_type || 'update')" :severity="logSeverity(entry.event_type)" class="text-xs" /><p class="mt-2 text-slate-800">{{ entry.message || entry.notes || 'Delivery activity recorded.' }}</p><p v-if="entry.creator" class="mt-1 text-xs text-slate-500">Recorded by {{ personName(entry.creator) }}</p></div><time class="text-xs text-slate-500">{{ formatDateTime(entry.logged_at || entry.created_at) }}</time></div>
            <div v-if="entry.status_from || entry.status_to" class="mt-2 text-xs text-slate-500">{{ formatStatus(entry.status_from) }} to {{ formatStatus(entry.status_to) }}</div>
            <div v-if="entry.attachments?.length" class="mt-3 flex flex-wrap gap-3 border-t border-slate-100 pt-3"><button v-for="attachment in entry.attachments" :key="attachment.id || attachment.public_url" type="button" class="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50" @click="openMedia(attachment.public_url)"><img :src="attachment.public_url" alt="Delivery attachment" class="h-28 w-36 object-cover transition group-hover:scale-105" /><span class="absolute inset-x-0 bottom-0 bg-slate-950/65 px-2 py-1 text-center text-xs text-white">View attachment</span></button></div>
          </article></div>
        </template>
      </Card>
    </template>
    <Message v-else severity="warn" :closable="false">Delivery order was not found.</Message>

    <Dialog v-model:visible="mediaPreviewVisible" modal header="Delivery Attachment" class="w-full max-w-4xl"><div class="flex min-h-72 items-center justify-center rounded-xl bg-slate-50 p-3"><img :src="mediaPreviewUrl" alt="Delivery attachment preview" class="max-h-[70vh] max-w-full rounded-lg object-contain" /></div></Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'; import Card from 'primevue/card'; import Chip from 'primevue/chip'; import Column from 'primevue/column'; import DataTable from 'primevue/datatable'; import Dialog from 'primevue/dialog'; import Message from 'primevue/message'; import Skeleton from 'primevue/skeleton'; import Tag from 'primevue/tag'
import L from 'leaflet'; import 'leaflet/dist/leaflet.css'; import markerIcon from 'leaflet/dist/images/marker-icon.png'; import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'; import markerShadow from 'leaflet/dist/images/marker-shadow.png'
import logisticsService from '../../../../services/logistics.service'
import { useAuthStore } from '../../../../stores/auth'

const Info = defineComponent({ props: { label: { type: String, required: true }, value: { type: [String, Number], default: '-' } }, setup: (props) => () => h('div', [h('p', { class: 'text-xs text-slate-500' }, props.label), h('p', { class: 'mt-1 font-medium text-slate-900' }, String(props.value || '-'))]) })
const route = useRoute(); const router = useRouter(); const toast = useToast(); const authStore = useAuthStore()
const loading = ref(false); const order = ref<any>(null); const delivery = ref<any>(null); const logs = ref<any[]>([]); const mapElement = ref<HTMLElement | null>(null); const mediaPreviewVisible = ref(false); const mediaPreviewUrl = ref(''); let trackingMap: L.Map | null = null
const source = computed<'ecommerce' | 'sales' | 'pickup'>(() => { const value = String(route.params.source || '').toLowerCase(); return value === 'sales' || value === 'pickup' ? value : 'ecommerce' })
const orderId = computed(() => Number(route.params.orderId || 0)); const sourceLabel = computed(() => source.value === 'pickup' ? 'Supplier pickup' : source.value === 'sales' ? 'Sales' : 'Ecommerce')
const canAssignDelivery = computed(() => authStore.hasPermission('logistics.deliveries.manage') && !delivery.value && source.value !== 'pickup'); const orderItems = computed(() => order.value?.items || [])
const customerName = computed(() => source.value === 'pickup' ? order.value?.supplier?.supplier_name : source.value === 'sales' ? order.value?.customer_name : order.value?.shipping_name)
const customerContact = computed(() => source.value === 'pickup' ? order.value?.supplier?.phone : source.value === 'sales' ? order.value?.customer_phone : order.value?.shipping_phone)
const deliveryAddress = computed(() => source.value === 'pickup' ? order.value?.branch?.address : source.value === 'sales' ? order.value?.delivery_address : order.value?.shipping_address)
const personName = (person: any) => person ? [person.fname, person.lname].filter(Boolean).join(' ').trim() : ''
const driverName = computed(() => personName(delivery.value?.driver || delivery.value?.driver_user) || delivery.value?.driver_name || delivery.value?.courier_name || '-')
const vehicleLabel = computed(() => [delivery.value?.vehicle?.vehicle_name, delivery.value?.vehicle?.brand, delivery.value?.vehicle?.model].filter(Boolean).join(' ') || delivery.value?.truck_brand || '-')
const point = (lat: any, lng: any): [number, number] | null => Number.isFinite(Number(lat)) && Number.isFinite(Number(lng)) && Number(lat) !== 0 && Number(lng) !== 0 ? [Number(lat), Number(lng)] : null
const currentPoint = computed<[number, number] | null>(() => point(delivery.value?.current_latitude, delivery.value?.current_longitude)); const destinationPoint = computed<[number, number] | null>(() => source.value === 'sales' ? point(order.value?.delivery_latitude, order.value?.delivery_longitude) : source.value === 'ecommerce' ? point(order.value?.customer_latitude, order.value?.customer_longitude) : point(order.value?.branch?.latitude, order.value?.branch?.longitude))
const formatStatus = (value: any) => String(value || '-').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase()); const formatDateTime = (value: any) => value ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '-'; const formatCurrency = (value: any) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0); const formatQuantity = (value: any) => Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: 2 })
const deliverySeverity = (status: any) => String(status) === 'delivered' ? 'success' : ['failed_delivery', 'cancelled'].includes(String(status)) ? 'danger' : ['in_transit', 'out_for_delivery'].includes(String(status)) ? 'warn' : 'info'; const logSeverity = (type: any) => String(type).includes('deliver') ? 'success' : String(type).includes('cancel') || String(type).includes('fail') ? 'danger' : String(type).includes('transit') ? 'warn' : 'info'
const itemName = (item: any) => item.product?.product_name || item.product_name || '-'; const itemSku = (item: any) => item.product?.sku || item.sku || '-'; const itemQuantity = (item: any) => item.quantity_ordered ?? item.quantity ?? 0; const itemUom = (item: any) => item.product?.unit_of_measurement || item.unit_of_measurement || item.unit || 'unit'; const itemUnitPrice = (item: any) => item.unit_cost ?? item.unit_price ?? 0; const itemLineTotal = (item: any) => item.line_total ?? Number(itemUnitPrice(item)) * Number(itemQuantity(item))

const renderMap = async () => { await nextTick(); if (!mapElement.value) return; trackingMap?.remove(); const center = currentPoint.value || destinationPoint.value || [14.5995, 120.9842]; trackingMap = L.map(mapElement.value).setView(center, currentPoint.value || destinationPoint.value ? 13 : 9); L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(trackingMap); const icon = L.icon({ iconRetinaUrl: markerIcon2x, iconUrl: markerIcon, shadowUrl: markerShadow, iconSize: [25, 41], iconAnchor: [12, 41] }); const points: [number, number][] = []; if (currentPoint.value) { L.marker(currentPoint.value, { icon }).addTo(trackingMap).bindTooltip('Driver location'); points.push(currentPoint.value) } if (destinationPoint.value) { L.marker(destinationPoint.value, { icon }).addTo(trackingMap).bindTooltip('Destination'); points.push(destinationPoint.value) } if (points.length > 1) { L.polyline(points, { color: '#f97316', weight: 4 }).addTo(trackingMap); trackingMap.fitBounds(points, { padding: [40, 40] }) } }
const loadAll = async () => { if (!orderId.value) return; loading.value = true; try { const response = await logisticsService.getDeliveryOrderDetail(source.value, orderId.value); const payload = response?.data || {}; order.value = payload.order || null; delivery.value = payload.delivery || null; logs.value = payload.logs || []; await renderMap() } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load delivery', detail: error?.response?.data?.message || 'Please try again.', life: 3500 }) } finally { loading.value = false } }
const openMedia = (url: string) => { if (url) { mediaPreviewUrl.value = url; mediaPreviewVisible.value = true } }; const openAssign = () => router.push({ name: 'logistics.deliveries.create', query: { source: source.value, order_id: String(orderId.value) } }); const goBack = () => router.push({ name: 'logistics.deliveries' })
onMounted(loadAll); onBeforeUnmount(() => { trackingMap?.remove(); trackingMap = null })
</script>
