<template>
  <div class="mx-auto max-w-5xl space-y-5 pb-10">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-5 py-7 text-white sm:px-8">
        <Button
          label="Back to shop"
          icon="pi pi-arrow-left"
          severity="secondary"
          text
          class="!mb-4 !px-0 !text-white hover:!bg-white/10"
          @click="router.push({ name: 'ecommerce.home' })"
        />
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-100">FurniSync Order Updates</p>
        <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Track your order</h1>
        <p class="mt-2 max-w-xl text-sm text-orange-50">Enter the Logistics Reference (LGS number) from your delivery update to view its status and location.</p>
      </div>

      <form class="flex flex-col gap-3 p-4 sm:flex-row sm:items-end sm:p-6" @submit.prevent="submitTracking">
        <div class="min-w-0 flex-1">
          <label for="tracking-number" class="mb-1.5 block text-sm font-medium text-slate-700">Logistics Reference / Tracking Number</label>
          <InputText
            id="tracking-number"
            v-model="trackingNumber"
            class="w-full"
            placeholder="Example: LGS-20261115-0001"
            autocomplete="off"
            :invalid="Boolean(errorMessage)"
            @input="errorMessage = ''"
          />
          <small v-if="errorMessage" class="mt-1 block text-red-600">{{ errorMessage }}</small>
        </div>
        <Button type="submit" label="Track Order" icon="pi pi-search" severity="warn" :loading="loading" class="sm:min-w-36" />
      </form>
    </section>

    <Card v-if="loading && !tracking" class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="space-y-4">
          <Skeleton height="1.5rem" width="45%" />
          <Skeleton height="1rem" width="30%" />
          <Skeleton height="20rem" />
        </div>
      </template>
    </Card>

    <Message v-if="errorMessage && !loading" severity="error" :closable="false" class="w-full">
      {{ errorMessage }}
    </Message>

    <Card v-if="tracking && !loading" class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
          <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Order reference</p>
            <h2 class="mt-1 break-all text-xl font-bold text-slate-900">{{ tracking.order_number }}</h2>
            <p class="mt-1 break-all text-sm text-slate-500">Logistics Reference: {{ tracking.tracking_number }}</p>
          </div>
          <Badge :value="statusLabel(tracking.status)" :severity="statusSeverity(tracking.status)" class="self-start !px-3 !py-1.5" />
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Delivery status</p>
            <p class="mt-1 text-base font-semibold text-slate-900">{{ statusLabel(tracking.status) }}</p>
          </div>
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Expected delivery</p>
            <p class="mt-1 text-base font-semibold text-slate-900">{{ formatDate(tracking.expected_delivery_date) }}</p>
          </div>
        </div>

        <section v-if="isOutForDelivery" class="mt-5 space-y-3">
          <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
              <h3 class="font-semibold text-slate-900">Live delivery map</h3>
              <p class="mt-1 text-sm text-slate-500">Showing the delivery destination and the driver's latest shared location.</p>
            </div>
            <span class="inline-flex items-center gap-2 text-xs text-slate-500">
              <span class="h-2 w-2 animate-pulse rounded-full bg-green-500" />
              Updates every 30 seconds
            </span>
          </div>

          <div v-if="hasMapLocation" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
            <div ref="mapElement" class="h-[300px] w-full sm:h-[420px]" />
          </div>
          <div v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-600">
            Map location is not available yet. The delivery status will continue to update here.
          </div>

          <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-600">
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full border-2 border-white bg-blue-600 shadow" /> Delivery destination</span>
            <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-orange-500 shadow" /> Driver's latest location</span>
            <span v-if="hasMapLocation && !tracking.live_tracking?.destination" class="text-amber-700">The order has no saved destination pin.</span>
            <span v-if="hasMapLocation && !tracking.live_tracking?.driver" class="text-amber-700">Waiting for the driver to share a GPS location.</span>
            <span v-if="tracking.live_tracking?.last_updated_at">Location updated {{ formatDateTime(tracking.live_tracking.last_updated_at) }}</span>
          </div>
          <Message v-if="mapError" severity="warn" :closable="false">{{ mapError }}</Message>
        </section>

        <Message v-else-if="isDelivered" severity="success" :closable="false" class="mt-5">
          This order has been delivered. Live location sharing has ended.
        </Message>
        <Message v-else severity="info" :closable="false" class="mt-5">
          The live map will become available when the delivery is out for delivery.
        </Message>
      </template>
    </Card>

    <div v-if="!tracking && !loading && !errorMessage" class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-12 text-center">
      <i class="pi pi-map-marker text-3xl text-orange-500" />
      <p class="mt-3 font-semibold text-slate-800">Your delivery updates will appear here</p>
      <p class="mt-1 text-sm text-slate-500">Use the Logistics Reference (LGS number) provided in your delivery update.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import ecommerceService from '@/services/ecommerce.service'
import { requireMapboxToken } from '@/utils/mapbox'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

defineOptions({ layout: EcommerceMobileWrapper })

const route = useRoute()
const router = useRouter()
const trackingNumber = ref('')
const tracking = ref<any>(null)
const loading = ref(false)
const errorMessage = ref('')
const mapError = ref('')
const mapElement = ref<HTMLElement | null>(null)
const map = ref<MapboxMap | null>(null)
const destinationMarker = ref<MapboxMarker | null>(null)
const driverMarker = ref<MapboxMarker | null>(null)
let refreshTimer: ReturnType<typeof setInterval> | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
let requestSequence = 0

function normalizeStatus(status: unknown) {
  return String(status || '').trim().toLowerCase().replace(/[ -]+/g, '_')
}

const isOutForDelivery = computed(() => normalizeStatus(tracking.value?.status) === 'out_for_delivery')
const isDelivered = computed(() => normalizeStatus(tracking.value?.status) === 'delivered')
const hasMapLocation = computed(() => Boolean(
  coordinatePoint(tracking.value?.live_tracking?.destination)
  || coordinatePoint(tracking.value?.live_tracking?.driver),
))

async function submitTracking() {
  const value = trackingNumber.value.trim()
  if (!value) {
    errorMessage.value = 'Enter your tracking number to continue.'
    return
  }

  tracking.value = null
  mapError.value = ''
  errorMessage.value = ''
  stopPolling()
  disposeMap()
  await fetchTracking(value)
}

async function fetchTracking(value = trackingNumber.value.trim(), quiet = false) {
  if (!value || (loading.value && !quiet)) return
  const sequence = ++requestSequence
  if (!quiet) loading.value = true

  try {
    const response = await ecommerceService.trackOrder(value)
    if (sequence !== requestSequence) return
    tracking.value = response.data?.data || null
    if (!tracking.value) throw new Error('We could not find an active delivery with that tracking number.')
    errorMessage.value = ''
  } catch (error: any) {
    if (sequence !== requestSequence) return
    if (!quiet) {
      tracking.value = null
      errorMessage.value = error?.response?.data?.message || error?.message || 'Unable to find that delivery. Check the tracking number and try again.'
    }
  } finally {
    if (sequence === requestSequence && !quiet) loading.value = false
  }
}

function updatePolling() {
  if (!isOutForDelivery.value) {
    stopPolling()
    return
  }
  if (!refreshTimer) refreshTimer = setInterval(() => fetchTracking(trackingNumber.value.trim(), true), 30_000)
}

function stopPolling() {
  if (refreshTimer) clearInterval(refreshTimer)
  refreshTimer = null
}

function coordinatePoint(value: any): [number, number] | null {
  const latitude = Number(value?.latitude)
  const longitude = Number(value?.longitude)
  if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude < -90 || latitude > 90 || longitude < -180 || longitude > 180 || (latitude === 0 && longitude === 0)) return null
  return [longitude, latitude]
}

async function renderMap() {
  if (!isOutForDelivery.value || !hasMapLocation.value || !mapElement.value) {
    disposeMap()
    return
  }

  const destination = coordinatePoint(tracking.value?.live_tracking?.destination)
  const driver = coordinatePoint(tracking.value?.live_tracking?.driver)
  if (!destination && !driver) {
    mapError.value = 'Map pins are not available because this order has no saved delivery pin or driver GPS update yet.'
    disposeMap()
    return
  }

  try {
    if (!map.value) {
      mapElement.value.replaceChildren()
      mapboxgl = (await import('mapbox-gl')).default
      mapboxgl.accessToken = requireMapboxToken()
      const center = driver || destination!
      map.value = new mapboxgl.Map({
        container: mapElement.value,
        style: 'mapbox://styles/mapbox/streets-v12',
        center,
        zoom: 12,
      })
      await new Promise<void>((resolve) => map.value!.once('load', () => resolve()))
    }
    if (!map.value || !mapboxgl) return

    if (destination) {
      if (destinationMarker.value) destinationMarker.value.setLngLat(destination)
      else {
        const pin = document.createElement('div')
        pin.style.cssText = 'width:20px;height:20px;border:3px solid #fff;border-radius:50%;background:#2563eb;box-shadow:0 1px 6px #0007;'
        destinationMarker.value = new mapboxgl.Marker({ element: pin }).setLngLat(destination).setPopup(new mapboxgl.Popup({ offset: 14 }).setText('Delivery destination')).addTo(map.value)
      }
    } else {
      destinationMarker.value?.remove()
      destinationMarker.value = null
    }

    if (driver) {
      if (driverMarker.value) driverMarker.value.setLngLat(driver)
      else {
        const pin = document.createElement('div')
        pin.style.cssText = 'width:18px;height:18px;border:3px solid #fff;border-radius:50%;background:#f97316;box-shadow:0 1px 6px #0007;'
        driverMarker.value = new mapboxgl.Marker({ element: pin }).setLngLat(driver).setPopup(new mapboxgl.Popup({ offset: 14 }).setText("Driver's latest location")).addTo(map.value)
      }
    } else {
      driverMarker.value?.remove()
      driverMarker.value = null
    }

    const points = [destination, driver].filter(Boolean) as [number, number][]
    if (points.length > 1) {
      const bounds = new mapboxgl.LngLatBounds()
      points.forEach((point) => bounds.extend(point))
      map.value.fitBounds(bounds, { padding: 56, maxZoom: 15 })
    } else {
      map.value.flyTo({ center: points[0], zoom: 14 })
    }
    map.value.resize()
    mapError.value = ''
  } catch (error: any) {
    mapError.value = error?.message || 'The map could not be loaded. Please try again shortly.'
  }
}

function disposeMap() {
  destinationMarker.value?.remove()
  driverMarker.value?.remove()
  destinationMarker.value = null
  driverMarker.value = null
  map.value?.remove()
  map.value = null
  mapElement.value?.replaceChildren()
  mapboxgl = null
}

function statusLabel(status: string) {
  return String(status || 'unknown').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function statusSeverity(status: string) {
  const value = String(status || '').toLowerCase()
  if (['delivered', 'completed'].includes(value)) return 'success'
  if (['failed_delivery', 'cancelled', 'rejected'].includes(value)) return 'danger'
  if (['out_for_delivery', 'in_transit', 'assigned', 'dispatched'].includes(value)) return 'info'
  return 'warn'
}

function formatDate(value: string | null) {
  if (!value) return 'Not available'
  const date = new Date(`${value.slice(0, 10)}T12:00:00`)
  return Number.isNaN(date.getTime()) ? 'Not available' : new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(date)
}

function formatDateTime(value: string | null) {
  if (!value) return 'Not available'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? 'Not available' : new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}

watch(() => tracking.value, async () => {
  await nextTick()
  if (!isOutForDelivery.value) disposeMap()
  else await renderMap()
  updatePolling()
}, { deep: true, flush: 'post' })

onMounted(async () => {
  const queryValue = route.query.tracking_number
  const initialNumber = Array.isArray(queryValue) ? queryValue[0] : queryValue
  if (typeof initialNumber === 'string' && initialNumber.trim()) {
    trackingNumber.value = initialNumber.trim()
    await fetchTracking(trackingNumber.value)
  }
})

onBeforeUnmount(() => {
  requestSequence++
  stopPolling()
  disposeMap()
})
</script>
