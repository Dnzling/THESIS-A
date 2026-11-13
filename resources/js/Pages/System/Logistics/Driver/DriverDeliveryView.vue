<template>
  <div class="mx-auto space-y-2 px-4 py-6 pb-28 sm:px-6 md:pb-24 lg:px-8">
    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Delivery Details</template>

      <template #content>
        <div v-if="loading" class="py-10 text-center text-slate-500">
          Loading delivery...
        </div>

        <div v-else-if="detail" class="space-y-6">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <small class="text-slate-500">Reference</small>
              <p class="font-semibold">
                {{ detail.order?.order_number || detail.order?.po_number || '-' }}
              </p>
            </div>

            <div>
              <small class="text-slate-500">Status</small>
              <p>
                <Tag
                  :value="statusLabel"
                  :severity="detail.delivery?.status === 'delivered' ? 'success' : 'info'"
                />
              </p>
            </div>

            <div v-if="source === 'ecommerce'">
              <small class="text-slate-500">Payment Method</small>
              <p>{{ label(detail.order?.payment_method || detail.order?.payment?.payment_method || '-') }}</p>
            </div>

            <div v-if="source === 'ecommerce'">
              <small class="text-slate-500">Payment Status</small>
              <p>{{ label(detail.order?.payment_status || detail.order?.payment?.status || '-') }}</p>
            </div>

            <div v-if="source === 'pickup' || !['ecommerce', 'sales', 'replacement'].includes(source)">
              <small class="text-slate-500">{{ source === 'pickup' ? 'Supplier Pickup' : 'Recipient' }}</small>
              <p>{{ source === 'pickup' ? supplierPickupName : recipientName }}</p>
            </div>

            <div>
              <small class="text-slate-500">Driver</small>
              <p>{{ driverName }}</p>
            </div>

               <div>
              <small class="text-slate-500">Driver Contact Number</small>
              <p>{{ driverContact }}</p>
            </div>

            <div>
              <small class="text-slate-500">Vehicle</small>
              <p>{{ vehicleLabel }}</p>
            </div>

            <div v-if="detail.delivery?.expected_delivery_date || detail.delivery?.estimated_delivery_at || detail.order?.expected_delivery_date || detail.order?.estimated_delivery_at">
              <small class="text-slate-500">{{ source === 'pickup' ? 'Expected Pickup' : 'Expected Date' }}</small>
              <p>
                {{ formatDate(detail.delivery?.expected_delivery_date || detail.delivery?.estimated_delivery_at || detail.order?.expected_delivery_date || detail.order?.estimated_delivery_at) }}
              </p>
            </div>
          </div>

          <div class="rounded-2xl border border-orange-100 bg-orange-50/60 p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Who to hand off to</p>
                <p class="mt-1 text-base font-semibold text-slate-900">{{ recipientName }}</p>
              </div>
              <a v-if="recipientPhone && recipientPhone !== '-'" :href="`tel:${recipientPhone}`" class="rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-orange-700 shadow-sm hover:bg-orange-100">Call {{ recipientPhone }}</a>
            </div>
            <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3">
              <div v-if="recipientPhone"><span class="block text-xs text-slate-500">Phone</span><span class="font-medium text-slate-800">{{ recipientPhone }}</span></div>
              <div v-if="recipientEmail"><span class="block text-xs text-slate-500">Email</span><span class="break-all font-medium text-slate-800">{{ recipientEmail }}</span></div>
              <div class="sm:col-span-2 lg:col-span-3"><span class="block text-xs text-slate-500">{{ recipientAddressLabel }}</span><span class="font-medium text-slate-800">{{ recipientAddress }}</span></div>
            </div>
          </div>

          <div v-if="!['ecommerce', 'sales', 'replacement'].includes(source)" class="grid gap-3 md:grid-cols-2">
            <!-- <div class="rounded-2xl bg-slate-50 p-4">
              <small class="text-slate-500">Pickup address</small>
              <p class="mt-1 font-medium">{{ pickupAddress }}</p>
            </div> -->

            <div class="rounded-2xl bg-slate-50 p-4">
              <small class="text-slate-500">Drop-off Destination</small>
              <p class="mt-1 font-medium">{{ destinationAddress }}</p>
            </div>
          </div>

          <div
            v-if="source === 'return_pickup' && returnRequest"
            class="rounded-2xl border border-slate-200 bg-white p-4"
          >
            <h3 class="mb-3 font-semibold text-slate-900">Return Details</h3>
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <div class="rounded-xl bg-slate-50 p-3">
                <small class="text-slate-500">Return Number</small>
                <p class="mt-1 font-semibold">{{ returnRequest.return_number || '-' }}</p>
              </div>
              <div class="rounded-xl bg-slate-50 p-3">
                <small class="text-slate-500">Requested Quantity</small>
                <p class="mt-1 font-semibold">{{ returnRequest.requested_quantity ?? 1 }}</p>
              </div>
              <div class="rounded-xl bg-slate-50 p-3">
                <small class="text-slate-500">Resolution</small>
                <p class="mt-1 font-semibold">{{ label(returnRequest.return_type || 'pending') }}</p>
              </div>
              <div class="rounded-xl bg-slate-50 p-3 sm:col-span-2 lg:col-span-3">
                <small class="text-slate-500">Reason</small>
                <p class="mt-1 font-medium">{{ returnRequest.reason || '-' }}</p>
                <p v-if="returnRequest.details" class="mt-1 text-sm text-slate-600">{{ returnRequest.details }}</p>
              </div>
            </div>
            <div v-if="returnEvidence.length" class="mt-4">
              <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Evidence</p>
              <div class="flex flex-wrap gap-3">
                <a v-for="(url, index) in returnEvidence" :key="url" :href="url" target="_blank" rel="noopener"
                  class="block h-24 w-24 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                  <img :src="url" :alt="`Return evidence ${index + 1}`" class="h-full w-full object-cover transition hover:scale-105" />
                </a>
              </div>
            </div>
          </div>

          <div
            v-if="source === 'pickup' && detail.order"
            class="rounded-2xl border border-slate-200 bg-white p-4"
          >
            <h3 class="mb-3 font-semibold text-slate-900">
              Linked Procurement Records
            </h3>

            <div class="grid gap-3 sm:grid-cols-2">
              <div class="rounded-xl bg-slate-50 p-3">
                <small class="text-slate-500">Purchase Order</small>

                <div class="mt-1 flex flex-wrap items-center justify-between gap-2">
                  <span class="font-semibold">
                    {{ detail.order.po_number || detail.order.order_number || '-' }}
                  </span>

                  <Tag
                    :value="label(detail.order.status)"
                    :severity="detail.order.status === 'delivered' ? 'success' : 'info'"
                  />
                </div>
              </div>

              <div class="rounded-xl bg-slate-50 p-3">
                <small class="text-slate-500">Purchase Requisition</small>

                <div
                  v-if="linkedRequisition"
                  class="mt-1 flex flex-wrap items-center justify-between gap-2"
                >
                  <span class="font-semibold">
                    {{ linkedRequisition.pr_number || `PR-${linkedRequisition.id}` }}
                  </span>

                  <Tag
                    :value="label(linkedRequisition.status)"
                    :severity="linkedRequisition.status === 'delivered' ? 'success' : 'info'"
                  />
                </div>

                <p v-else class="mt-1 text-sm text-slate-500">
                  No requisition linked to this PO.
                </p>
              </div>
            </div>

            <Message
              v-if="isDelivered && linkedStatusesDelivered"
              severity="success"
              :closable="false"
              class="mt-3"
            >
              Delivery complete. The purchase order{{
                linkedRequisition ? ' and linked requisition are' : ' is'
              }}
              marked delivered.
            </Message>
          </div>

          <div
            v-if="lastKnownAddress"
            v-show="trackingActive"
            class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900"
          >
            <div class="flex gap-2">
              <i class="pi pi-map-marker mt-0.5 text-blue-600" />

              <div>
                <p class="font-semibold">Last known location</p>
                <p>{{ lastKnownAddress }}</p>
              </div>
            </div>
          </div>

          <div
            v-show="trackingActive"
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
          >
            <div
              class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3"
            >
              <div>
                <h3 class="font-semibold text-slate-900">
                  Live Delivery Tracking
                </h3>
                <p class="text-xs text-slate-500">
                  The assigned driver's GPS location updates while the pickup is active.
                </p>
              </div>

              <Tag
                :value="
                  trackingActive
                    ? 'Live tracking active'
                    : isDelivered
                      ? 'Tracking completed'
                      : 'Tracking unavailable'
                "
                :severity="
                  trackingActive
                    ? 'success'
                    : isDelivered
                      ? 'secondary'
                      : 'warn'
                "
              />
            </div>

            <div ref="mapElement" class="h-[360px] w-full sm:h-[440px]"></div>

            <div
              v-if="!destinationPoint"
              class="px-4 py-3 text-sm text-amber-700"
            >
              {{ source === 'pickup' && isSupplierLeg
                ? 'Supplier coordinates are not available. The live driver location will still appear on the map.'
                : source === 'return_pickup' && returnPickupToCustomer
                ? 'The customer pickup address does not have map coordinates yet.'
                : source === 'pickup'
                  ? 'The destination branch does not have map coordinates yet.'
                  : 'The drop-off branch does not have map coordinates yet.' }}
            </div>
          </div>

          <div>
            <h3 class="mb-3 font-semibold text-slate-900">
              {{
                source === 'pickup'
                  ? 'Purchase Order Items'
                  : source === 'stock_transfer'
                    ? 'Transfer Items'
                    : 'Order Items'
              }}
            </h3>

            <DataTable
              :value="detail.order?.items || []"
              stripedRows
              responsiveLayout="scroll"
              class="text-sm"
            >
              <template #empty>
                <div class="py-6 text-center text-slate-500">
                  No PO items found.
                </div>
              </template>

              <Column header="Product">
                <template #body="{ data }">
                  <p class="font-medium">
                    {{
                      data.product?.product_name ||
                      data.product_name ||
                      `Product #${data.product_id}`
                    }}
                  </p>

                  <small class="text-slate-500">
                    {{ data.product?.sku || data.sku || '-' }}
                  </small>
                  <div v-if="data.customization_request" class="mt-2 rounded-md bg-orange-50 p-2 text-xs text-slate-700">
                    <p class="font-semibold text-orange-800"><i class="pi pi-palette mr-1" />Upholstery order</p>
                    <p class="mt-1 whitespace-pre-line">{{ data.customization_request }}</p>
                    <p v-if="data.customization_lead_time_days" class="mt-1 text-slate-500">Lead time: {{ data.customization_lead_time_days }} days</p>
                  </div>
                </template>
              </Column>

              <Column header="Variation">
                <template #body="{ data }">
                  {{
                    data.variation?.variation_name ||
                    data.variation_name ||
                    '-'
                  }}
                </template>
              </Column>

              <Column header="Ordered">
                <template #body="{ data }">
                  {{
                    data.quantity_ordered ??
                    data.approved_quantity ??
                    data.requested_quantity ??
                    data.quantity ??
                    0
                  }}
                </template>
              </Column>

              <Column header="Unit Cost">
                <template #body="{ data }">
                  ₱{{
                    money(
                      data.unit_cost ??
                        data.unit_value ??
                        data.unit_price,
                    )
                  }}
                </template>
              </Column>

              <Column v-if="false" header="Line Total">
                <template #body="{ data }">
                  ₱{{
                    money(
                      data.total_cost ??
                        data.line_total ??
                        Number(
                          data.quantity_ordered ??
                            data.approved_quantity ??
                            data.requested_quantity ??
                            data.quantity ??
                            0,
                        ) *
                          Number(
                            data.unit_cost ??
                              data.unit_value ??
                              0,
                          ),
                    )
                  }}
                </template>
              </Column>
            </DataTable>
          </div>

          <div>
            <h3 class="mb-3 font-semibold text-slate-900">
              Delivery Activity
            </h3>

            <div
              v-if="detail.logs?.length"
              class="divide-y divide-slate-200"
            >
              <div
                v-for="log in detail.logs"
                :key="log.id"
                class="space-y-2 py-4"
              >
                <div class="flex flex-wrap items-start justify-between gap-2">
                  <div>
                    <p class="font-medium">
                      {{ label(log.event_type || log.status_to || 'Update') }}
                    </p>

                    <p
                      v-if="log.notes || log.message"
                      class="text-sm text-slate-600"
                    >
                      {{ formatActivityMessage(log.notes || log.message) }}
                    </p>
                  </div>

                  <small class="text-slate-500">
                    {{ formatDateTime(log.logged_at || log.created_at) }}
                  </small>
                </div>

                <p
                  v-if="log.location_address"
                  class="text-sm text-blue-700"
                >
                  <i class="pi pi-map-marker mr-1" />
                  {{ log.location_address }}
                </p>

                <small
                  v-else-if="log.latitude && log.longitude"
                  class="text-slate-500"
                >
                  Location captured
                </small>

                <div
                  v-if="logAttachments(log).length"
                  class="flex flex-wrap gap-3"
                >
                  <a
                    v-for="attachment in logAttachments(log)"
                    :key="attachment.id || attachment.public_url"
                    :href="attachment.public_url"
                    target="_blank"
                    rel="noopener"
                  >
                    <img
                      :src="attachment.public_url"
                      alt="Delivery proof"
                      class="h-28 w-36 rounded-xl border border-slate-200 object-cover transition hover:scale-105"
                    />
                  </a>
                </div>
              </div>
            </div>

            <p v-else class="text-sm text-slate-500">
              No activity recorded.
            </p>
          </div>
        </div>

        <p v-else class="py-10 text-center text-slate-500">
          Delivery not found.
        </p>
      </template>
    </Card>

    <Dialog
      v-if="canOperateDelivery"
      v-model:visible="proofDialog"
      modal
      :header="nextStatusLabel"
      :style="{ width: 'min(94vw, 560px)' }"
    >
      <div class="space-y-4">
        <Message severity="info" :closable="false">
          {{ proofInstruction }}
        </Message>

        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700">
            {{ proofPhotoLabel }} *
          </label>

          <div v-if="source === 'replacement'" class="mb-3">
            <label class="mb-1 block text-xs font-medium text-slate-600">Received by *</label>
            <InputText v-model="replacementReceivedBy" size="small" fluid placeholder="Customer or recipient name" />
          </div>

          <input
            type="file"
            accept="image/*"
            capture="environment"
            class="block w-full rounded-xl border border-slate-300 p-3 text-sm"
            @change="selectPhoto"
          />
        </div>

        <img
          v-if="photoPreview"
          :src="photoPreview"
          alt="Proof preview"
          class="max-h-64 w-full rounded-2xl border border-slate-200 object-contain"
        />

        <div
          v-if="capturedAddress"
          class="rounded-xl bg-blue-50 p-3 text-sm text-blue-900"
        >
          <p class="font-medium">Detected location</p>
          <p>{{ capturedAddress }}</p>
        </div>

        <Textarea
          v-model="statusNotes"
          rows="3"
          fluid
          placeholder="Optional pickup notes"
        />
      </div>

      <template #footer>
        <Button
          label="Cancel"
          text
          @click="proofDialog = false"
        />

        <Button
          :label="proofSubmitLabel"
          icon="pi pi-map-marker"
          :disabled="!proofPhoto || updating || (source === 'replacement' && !replacementReceivedBy.trim())"
          :loading="updating"
          @click="submitPickupStatus"
        />
      </template>
    </Dialog>

    <div v-if="canShowDeliveryAction" class="fixed inset-x-0 bottom-0 z-40 flex justify-center border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-8px_24px_rgba(15,23,42,0.12)] backdrop-blur md:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom))">
      <Button
        icon="pi pi-check"
        :label="nextStatusLabel"
        size="large"
        class="w-full max-w-md"
        :loading="updating"
        :disabled="updating"
        @click="beginAdvance"
      />
    </div>
    <div v-if="canShowDeliveryAction" class="fixed bottom-6 right-8 z-40 hidden md:block">
      <Button
        icon="pi pi-check"
        :label="nextStatusLabel"
        size="large"
        class="min-w-56 shadow-lg"
        :loading="updating"
        :disabled="updating"
        @click="beginAdvance"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onActivated, onBeforeUnmount, onDeactivated, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import InputText from 'primevue/inputtext'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import logisticsService from '@/services/logistics.service'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import {
  fetchMapboxRoadRoute,
  forwardGeocodeMapbox,
  requireMapboxToken,
  reverseGeocodeMapbox,
} from '@/utils/mapbox'

const route = useRoute()
const router = useRouter()
const page = usePage()
const toast = useToast()
const authStore = useAuthStore()

const loading = ref(false)
const updating = ref(false)
const sharingLocation = ref(false)
const detail = ref<any>(null)
const resolvedCurrentAddress = ref('')
const resolvedSupplierPoint = ref<[number, number] | null>(null)

const proofDialog = ref(false)
const proofPhoto = ref<File | null>(null)
const photoPreview = ref('')
const statusNotes = ref('')
const capturedAddress = ref('')
const replacementReceivedBy = ref('')

const mapElement = ref<HTMLElement | null>(null)
const trackingActive = ref(false)

let trackingMap: MapboxMap | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
let truckMarker: MapboxMarker | null = null
let destinationMarker: MapboxMarker | null = null
let locationWatcher: number | null = null
let locationTimer: number | null = null
let lastLocationSentAt = 0

const source = computed(() =>
  String(route.params.source || (route.name === 'driver.replacements.detail' ? 'replacement' : route.name === 'logistics.supplier-pickups.detail' ? 'pickup' : '')).toLowerCase(),
)

const orderId = computed(() => String(route.params.orderId || route.params.id))

const label = (value: string) =>
  String(value || 'Pending')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase())

const formatActivityMessage = (value: unknown) => {
  const message = String(value || '').replace(
    /\b(from|to)\s+([a-z][a-z0-9_]*)(?=\b|\.)/gi,
    (_match, direction: string, status: string) =>
      `${direction.toLowerCase()} ${label(status)}`,
  )
  if (source.value !== 'return_pickup') return message

  const tripReference = detail.value?.delivery?.trip_reference
  return tripReference
    ? message.replace(/return pickup assigned to trip #\d+\.?/i, `Return pickup assigned to ${tripReference}.`)
    : message
}

const isDelivered = computed(
  () =>
    String(detail.value?.delivery?.status || '').toLowerCase() ===
    'delivered',
)

const statusLabel = computed(() =>
  label(detail.value?.delivery?.status),
)

const linkedRequisition = computed(
  () =>
    detail.value?.order?.purchase_requisition ||
    detail.value?.order?.purchaseRequisition ||
    null,
)

const returnRequest = computed(() => detail.value?.return_request || detail.value?.order?.return_request || null)
const returnEvidence = computed<string[]>(() => Array.isArray(returnRequest.value?.evidence_urls) ? returnRequest.value.evidence_urls : [])

const linkedStatusesDelivered = computed(
  () =>
    String(detail.value?.order?.status || '').toLowerCase() ===
      'delivered' &&
    (!linkedRequisition.value ||
      String(linkedRequisition.value.status || '').toLowerCase() ===
        'delivered'),
)

const handoffBranch = computed(() =>
  source.value === 'return_pickup'
    ? detail.value?.delivery?.destination_branch || detail.value?.order?.branch || {}
    : source.value === 'pickup'
      ? detail.value?.order?.purchase_requisition?.branch || detail.value?.order?.purchaseRequisition?.branch || detail.value?.order?.branch || {}
    : detail.value?.order?.branch || {},
)

const supplierPickupName = computed(() => detail.value?.order?.supplier?.supplier_name || '-')

const recipientName = computed(() => {
  if (source.value === 'stock_transfer') {
    return `${detail.value?.order?.from_branch?.name || 'Source branch'} to ${detail.value?.order?.to_branch?.name || 'Destination branch'}`
  }

  if (source.value === 'pickup' || (source.value === 'return_pickup' && !returnPickupToCustomer.value)) {
    const branch = handoffBranch.value
    const manager = branch.manager
    const managerName = [manager?.fname, manager?.lname].filter(Boolean).join(' ')
    return managerName || branch.name || 'Branch recipient'
  }

  return detail.value?.order?.shipping_name || detail.value?.order?.customer_name || detail.value?.order?.pickup_name || 'Customer'
})

const recipientPhone = computed(() => {
  if (source.value === 'pickup' || (source.value === 'return_pickup' && !returnPickupToCustomer.value)) {
    return handoffBranch.value.contact_number || '-'
  }
  return detail.value?.order?.shipping_phone || detail.value?.order?.customer_phone || detail.value?.order?.pickup_phone || '-'
})

const recipientEmail = computed(() => {
  if (source.value === 'pickup' || (source.value === 'return_pickup' && !returnPickupToCustomer.value)) {
    return handoffBranch.value.email || ''
  }
  return detail.value?.order?.shipping_email || detail.value?.order?.delivery_email || ''
})

const recipientAddress = computed(() => {
  if (source.value === 'pickup' || (source.value === 'return_pickup' && !returnPickupToCustomer.value)) {
    return handoffBranch.value.address || detail.value?.delivery?.destination_address || '-'
  }
  return detail.value?.order?.shipping_address || detail.value?.order?.delivery_address || detail.value?.delivery?.pickup_address || '-'
})

const recipientAddressLabel = computed(() =>
  source.value === 'pickup' || (source.value === 'return_pickup' && !returnPickupToCustomer.value)
    ? 'Receiving branch'
    : 'Delivery address',
)

const driverName = computed(() => {
  const delivery = detail.value?.delivery
  return delivery?.courier_name || delivery?.driver_name
    || [delivery?.driver_user?.fname, delivery?.driver_user?.lname].filter(Boolean).join(' ') || '-'
})

const driverContact = computed(() => {
  const delivery = detail.value?.delivery
  return delivery?.courier_contact || delivery?.driver_contact || delivery?.driver_user?.phone_number || '-'
})

const pickupAddress = computed(() =>
  source.value === 'return_pickup'
    ? detail.value?.delivery?.pickup_address || detail.value?.order?.shipping_address || '-'
    : source.value === 'stock_transfer'
    ? detail.value?.delivery?.origin_address ||
      detail.value?.order?.from_branch?.address ||
      '-'
    : detail.value?.delivery?.origin_address ||
      detail.value?.order?.supplier?.address ||
      detail.value?.order?.assigned_branch?.address ||
      detail.value?.order?.assignedBranch?.address ||
      '-',
)

const destinationAddress = computed(() =>
  source.value === 'pickup'
    ? handoffBranch.value.address || detail.value?.delivery?.destination_address || '-'
    : source.value === 'stock_transfer'
    ? detail.value?.delivery?.destination_address ||
      detail.value?.order?.to_branch?.address ||
      '-'
    : detail.value?.delivery?.destination_address ||
      detail.value?.order?.shipping_address ||
      detail.value?.order?.branch?.address ||
      '-',
)

const returnPickupToCustomer = computed(() =>
  source.value === 'return_pickup' &&
  !['picked_up', 'out_for_delivery', 'delivered'].includes(currentDeliveryStatus.value),
)

const destinationPoint = computed<[number, number] | null>(() => {
  if (isSupplierLeg.value) {
    const portal = detail.value?.order?.supplier_quotation?.supplier_portal
    const supplier = detail.value?.order?.supplier || {}
    const candidates = [
      [portal?.latitude, portal?.longitude],
      [supplier.latitude, supplier.longitude],
    ]
    for (const [rawLatitude, rawLongitude] of candidates) {
      const latitude = Number(rawLatitude)
      const longitude = Number(rawLongitude)
      if (Number.isFinite(latitude) && Number.isFinite(longitude) && latitude !== 0 && longitude !== 0) {
        return [latitude, longitude]
      }
    }
    return resolvedSupplierPoint.value
  }

  const branch = source.value === 'pickup'
    ? handoffBranch.value
    : detail.value?.order?.branch || {}
  const isCustomerDestination = ['ecommerce', 'sales', 'replacement'].includes(source.value) || returnPickupToCustomer.value

  const latitude = Number(
    isCustomerDestination
      ? source.value === 'sales' ? detail.value?.order?.delivery_latitude : detail.value?.order?.customer_latitude
      : branch.latitude ?? detail.value?.delivery?.destination_latitude,
  )

  const longitude = Number(
    isCustomerDestination
      ? source.value === 'sales' ? detail.value?.order?.delivery_longitude : detail.value?.order?.customer_longitude
      : branch.longitude ?? detail.value?.delivery?.destination_longitude,
  )

  return (
    Number.isFinite(latitude) &&
    Number.isFinite(longitude) &&
    latitude !== 0 &&
    longitude !== 0
  )
    ? [latitude, longitude]
    : null
})

const lastKnownAddress = computed(
  () =>
    detail.value?.delivery?.current_address ||
    resolvedCurrentAddress.value,
)

const vehicleLabel = computed(
  () =>
    `${
      detail.value?.delivery?.vehicle?.vehicle_name ||
      detail.value?.delivery?.truck_brand ||
      '-'
    } · ${
      detail.value?.delivery?.plate_number ||
      detail.value?.delivery?.vehicle?.plate_number ||
      '-'
    }`,
)

const currentDeliveryStatus = computed(() =>
  String(detail.value?.delivery?.status || '').toLowerCase(),
)
const isSupplierLeg = computed(() =>
  source.value === 'pickup' && ['pending', 'en_route_to_supplier'].includes(currentDeliveryStatus.value),
)

const isAssignedDriver = computed(() => {
  const userId = Number((page.props as any)?.auth?.user?.id)
  const assignedDriverId = Number(detail.value?.delivery?.driver_user_id)
  return userId > 0 && assignedDriverId > 0 && userId === assignedDriverId
})
const canTrackOwnLocation = computed(() => {
  if (!isAssignedDriver.value) return false

  const status = currentDeliveryStatus.value
  if (source.value === 'pickup') {
    return ['en_route_to_supplier', 'in_transit', 'out_for_delivery'].includes(status)
  }
  if (source.value === 'return_pickup') {
    return ['picked_up', 'out_for_delivery'].includes(status)
  }
  if (source.value === 'replacement') return status === 'out_for_delivery'
  if (['ecommerce', 'sales', 'stock_transfer'].includes(source.value)) {
    return ['in_transit', 'out_for_delivery'].includes(status)
  }
  return false
})
const canOperateDelivery = computed(() =>
  isAssignedDriver.value
  && (source.value !== 'pickup' || authStore.hasPermission('logistics.driver.supplier-pickups')),
)
const canShowDeliveryAction = computed(() =>
  canOperateDelivery.value
  && !!nextStatus.value
  && (source.value === 'pickup' || currentDeliveryStatus.value === 'out_for_delivery'),
)

const returnDeliverySteps = [
  { label: 'Pickup', value: 'picked_up' },
  { label: 'Out for Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
]
const returnStatusIndex = computed(() => returnDeliverySteps.findIndex(step => step.value === currentDeliveryStatus.value))
const isReturnStepCompleted = (status: string) => returnDeliverySteps.findIndex(step => step.value === status) < returnStatusIndex.value
const returnStepSeverity = (status: string) => isReturnStepCompleted(status) || (status === 'delivered' && currentDeliveryStatus.value === 'delivered') ? 'success' : currentDeliveryStatus.value === status ? 'info' : 'secondary'
const returnStepIcon = (status: string) => isReturnStepCompleted(status) || (status === 'delivered' && currentDeliveryStatus.value === 'delivered') ? 'pi pi-check' : status === 'picked_up' ? 'pi pi-camera' : status === 'out_for_delivery' ? 'pi pi-truck' : 'pi pi-check-circle'

const nextStatus = computed(() =>
  source.value === 'replacement'
    ? (currentDeliveryStatus.value === 'out_for_delivery' ? 'delivered' : null)
    : source.value === 'return_pickup'
    ? ({ assigned: 'out_for_delivery', picked_up: 'out_for_delivery', out_for_delivery: 'delivered' } as any)[currentDeliveryStatus.value]
    : source.value === 'pickup'
    ? (
        {
          pending: 'en_route_to_supplier',
          en_route_to_supplier: 'out_for_delivery',
          in_transit: 'out_for_delivery',
          out_for_delivery: 'delivered',
        } as any
      )[currentDeliveryStatus.value]
    : (
        {
          assigned: 'in_transit',
          packed: 'in_transit',
          shipped: 'in_transit',
          in_transit: 'out_for_delivery',
          out_for_delivery: 'delivered',
        } as any
      )[currentDeliveryStatus.value],
)

const nextStatusLabel = computed(() => {
  if (source.value === 'pickup' && nextStatus.value === 'en_route_to_supplier') {
    return 'Start Route to Supplier'
  }
  if (source.value === 'pickup' && nextStatus.value === 'in_transit') {
    return 'Confirm Supplier Pickup'
  }
  if (source.value === 'pickup' && nextStatus.value === 'out_for_delivery' && currentDeliveryStatus.value === 'en_route_to_supplier') {
    return 'Confirm Pickup & Start Delivery'
  }
  if (source.value === 'pickup' && nextStatus.value === 'out_for_delivery') {
    return 'Continue Delivery to Store'
  }
  if (source.value === 'return_pickup' && nextStatus.value === 'out_for_delivery' && currentDeliveryStatus.value === 'assigned') {
    return 'Confirm Pickup & Start Delivery'
  }
  if (nextStatus.value === 'out_for_delivery') {
    return source.value === 'pickup' ? 'Confirm Pickup & Start Delivery' : 'Mark Out for Delivery'
  }

  if (nextStatus.value === 'delivered') {
    return source.value === 'pickup'
      ? 'Confirm Delivery to Store'
      : 'Mark Delivered'
  }

  return source.value === 'pickup'
    ? 'Start Supplier Pickup'
    : `Mark ${label(nextStatus.value)}`
})

const proofPhotoLabel = computed(() =>
  source.value === 'replacement' && nextStatus.value === 'delivered'
    ? 'Replacement handoff photo'
    : source.value === 'return_pickup' && nextStatus.value === 'out_for_delivery' && currentDeliveryStatus.value === 'assigned'
    ? 'Customer pickup photo'
    : source.value === 'return_pickup' && nextStatus.value === 'delivered'
      ? 'Return delivered to branch photo'
      :
  source.value === 'pickup' && ['en_route_to_supplier', 'in_transit'].includes(currentDeliveryStatus.value) && nextStatus.value === 'out_for_delivery'
    ? 'Supplier pickup photo'
    : nextStatus.value === 'delivered'
    ? source.value === 'pickup'
      ? 'Store handoff photo'
      : 'Delivered order photo'
    : source.value === 'pickup'
      ? 'Supplier pickup photo'
      : 'Dispatch pickup photo',
)

const proofInstruction = computed(() =>
  source.value === 'replacement'
    ? 'Attach a photo confirming the replacement was handed to the customer. Enter the recipient name; your final GPS location will be captured.'
    : source.value === 'return_pickup'
    ? nextStatus.value === 'out_for_delivery' && currentDeliveryStatus.value === 'assigned'
      ? 'Attach a photo confirming the item was collected from the customer. Your GPS location will also be captured.'
      : 'Attach a photo confirming the returned item was delivered to the destination branch. Your final GPS location will be captured.'
    : source.value === 'pickup' && ['en_route_to_supplier', 'in_transit'].includes(currentDeliveryStatus.value) && nextStatus.value === 'out_for_delivery'
      ? 'Attach a photo confirming the goods were collected from the supplier. Driver location is included when available.'
    :
  nextStatus.value === 'delivered'
    ? `Attach a photo showing the ${
        source.value === 'pickup'
          ? 'goods handed off at the receiving store'
          : 'order delivered to the customer'
      }. Your final GPS location will be captured before tracking stops.`
    : `Attach a photo showing the ${
        source.value === 'pickup'
          ? 'supplies at the supplier pickup point'
          : 'order before dispatch'
      }. Your GPS location will be captured before marking it In Transit.`,
)

const proofSubmitLabel = computed(() =>
  source.value === 'return_pickup' && nextStatus.value === 'out_for_delivery' && currentDeliveryStatus.value === 'assigned'
    ? 'Confirm Pickup & Start Delivery'
    : source.value === 'pickup' && ['en_route_to_supplier', 'in_transit'].includes(currentDeliveryStatus.value) && nextStatus.value === 'out_for_delivery'
      ? 'Confirm Pickup & Start Delivery'
    : nextStatus.value === 'delivered'
    ? source.value === 'pickup' ? 'Confirm Delivery to Store' : 'Mark Delivered'
    : 'Mark In Transit',
)

const money = (value: any) =>
  Number(value || 0).toLocaleString(undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })

const formatDate = (value: any) =>
  value ? new Date(value).toLocaleDateString() : '-'

const formatDateTime = (value: any) =>
  value ? new Date(value).toLocaleString() : '-'

const logAttachments = (log: any) => {
  const attachments = Array.isArray(log?.attachments) ? [...log.attachments] : []
  if (log?.proof_photo_url && !attachments.some((attachment: any) => attachment.public_url === log.proof_photo_url)) {
    attachments.push({ id: `return-proof-${log.id}`, public_url: log.proof_photo_url })
  }
  return attachments.filter((attachment: any) => attachment?.public_url)
}

const coordinates = () =>
  new Promise<{ latitude: number; longitude: number }>(
    (resolve, reject) => {
      if (!navigator.geolocation) {
        return reject(
          new Error('Location is not supported by this browser.'),
        )
      }

      navigator.geolocation.getCurrentPosition(
        (position) =>
          resolve({
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
          }),
        reject,
        {
          enableHighAccuracy: true,
          timeout: 12000,
          maximumAge: 0,
        },
      )
    },
  )

const reverseGeocode = async (point: {
  latitude: number
  longitude: number
}) =>
  reverseGeocodeMapbox(point.latitude, point.longitude)

const resolveSupplierDestination = async () => {
  if (source.value !== 'pickup' || !isSupplierLeg.value || destinationPoint.value) return

  const supplier = detail.value?.order?.supplier || {}
  const supplierAddress = [
    supplier.address,
    supplier.barangay,
    supplier.city,
    supplier.province,
    supplier.postal_code,
    supplier.country || 'Philippines',
  ].filter(Boolean).join(', ')

  if (!supplierAddress) return

  const geocoded = await forwardGeocodeMapbox(supplierAddress).catch(() => null)
  if (geocoded) resolvedSupplierPoint.value = [geocoded.latitude, geocoded.longitude]
}

const load = async () => {
  loading.value = true

  try {
    if (source.value === 'return_pickup') {
      const response = await logisticsService.getReturnPickup(orderId.value)
      const pickup = response?.data || null
      const request = pickup?.return_request || null
      const orderItem = request?.order_item || null
      detail.value = pickup ? {
        return_request: request,
        order: {
          ...(request?.order || {}),
          branch: pickup.destination_branch || null,
          order_number: request?.return_number,
          status: request?.status,
          shipping_name: pickup.pickup_name || request?.order?.shipping_name,
          shipping_phone: pickup.pickup_phone || request?.order?.shipping_phone,
          shipping_address: pickup.pickup_address || request?.order?.shipping_address,
          items: orderItem ? [{ ...orderItem, quantity: request?.requested_quantity || 1 }] : [],
          return_request: request,
        },
        delivery: {
          ...pickup,
          destination_address: pickup.destination_branch?.address,
          destination_latitude: pickup.destination_branch?.latitude,
          destination_longitude: pickup.destination_branch?.longitude,
          courier_name: [pickup.driver?.fname, pickup.driver?.lname].filter(Boolean).join(' '),
          courier_contact: pickup.driver?.phone_number,
        },
        logs: pickup.logs || [],
      } : null
    } else {
      const response = await logisticsService.getDeliveryOrderDetail(source.value as any, orderId.value)
      detail.value = response?.data || null
    }

    const delivery = detail.value?.delivery
    resolvedCurrentAddress.value = ''
    resolvedSupplierPoint.value = null

    if (
      !delivery?.current_address &&
      delivery?.current_latitude &&
      delivery?.current_longitude
    ) {
      resolvedCurrentAddress.value = await reverseGeocode({
        latitude: Number(delivery.current_latitude),
        longitude: Number(delivery.current_longitude),
      }).catch(() => '')
    }

    updateTrackingState()

    loading.value = false

    await nextTick()
    // Mapbox rendering is independent from loading the delivery data. Do not
    // leave status buttons waiting if the map style or network is slow.
    void renderTrackingMap().catch(() => undefined)
    void resolveSupplierDestination().then(async () => {
      if (!resolvedSupplierPoint.value) return
      await nextTick()
      await renderTrackingMap().catch(() => undefined)
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Load Failed',
      detail:
        error?.response?.data?.message ||
        'Failed to load delivery.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const selectPhoto = (event: Event) => {
  const file =
    (event.target as HTMLInputElement).files?.[0] || null

  proofPhoto.value = file

  if (photoPreview.value) {
    URL.revokeObjectURL(photoPreview.value)
  }

  photoPreview.value = file
    ? URL.createObjectURL(file)
    : ''
}

const beginAdvance = async () => {
  if (!canOperateDelivery.value || !nextStatus.value) return

  const needsProof =
    ['pickup', 'ecommerce', 'sales', 'return_pickup', 'replacement'].includes(source.value) &&
    (source.value === 'return_pickup'
      ? (currentDeliveryStatus.value === 'assigned' || nextStatus.value === 'delivered')
        : source.value === 'replacement'
          ? nextStatus.value === 'delivered'
      : source.value === 'pickup'
        ? ['in_transit', 'delivered'].includes(nextStatus.value)
        : ['in_transit', 'delivered'].includes(nextStatus.value))

  if (needsProof) {
    proofPhoto.value = null
    statusNotes.value = ''
    capturedAddress.value = ''

    if (photoPreview.value) {
      URL.revokeObjectURL(photoPreview.value)
    }

    photoPreview.value = ''
    proofDialog.value = true
    replacementReceivedBy.value = ''
    return
  }

  await updateRegularDelivery()
}

const updateRegularDelivery = async () => {
  if (!canOperateDelivery.value) return
  const targetStatus = nextStatus.value

  if (!targetStatus) return

  updating.value = true

  try {
    if (source.value === 'return_pickup') {
      await logisticsService.updateReturnPickup(orderId.value, { status: targetStatus as any })
      if (detail.value?.delivery) detail.value.delivery.status = targetStatus
      toast.add({ severity: 'success', summary: 'Status Updated', detail: `Return delivery is now ${label(targetStatus)}.`, life: 2500 })
      void load()
      return
    }

    // Use the latest background-tracked point when available. A status action
    // must not be blocked by browser location permission or geocoding latency.
    const rawLatitude = detail.value?.delivery?.current_latitude
    const rawLongitude = detail.value?.delivery?.current_longitude
    const latitude = rawLatitude !== null && rawLatitude !== undefined && rawLatitude !== ''
      ? Number(rawLatitude)
      : NaN
    const longitude = rawLongitude !== null && rawLongitude !== undefined && rawLongitude !== ''
      ? Number(rawLongitude)
      : NaN
    const hasCurrentPoint = Number.isFinite(latitude) && Number.isFinite(longitude)

    await logisticsService.updateUnifiedDeliveryStatus(
      source.value as any,
      orderId.value,
      {
        status: targetStatus,
        ...(hasCurrentPoint ? { latitude, longitude } : {}),
        location_address: detail.value?.delivery?.current_address || undefined,
      },
    )

    if (detail.value?.delivery) {
      detail.value.delivery.status = targetStatus
    }

    toast.add({
      severity: 'success',
      summary: 'Status Updated',
      detail:
        targetStatus === 'out_for_delivery'
          ? 'The order is now out for delivery.'
          : targetStatus === 'delivered'
            ? 'The order has been marked as delivered.'
          : 'Delivery progress and GPS location were saved.',
      life: 2500,
    })

    // Refresh logs and linked records after the button has completed. This is
    // deliberately non-blocking so a slow map cannot keep the action loading.
    void load()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Update Failed',
      detail:
        error?.response?.data?.message ||
        error?.message ||
        'GPS access is required to update this delivery.',
      life: 3500,
    })
  } finally {
    updating.value = false
  }
}

const submitPickupStatus = async () => {
  if (!canOperateDelivery.value || !proofPhoto.value || !nextStatus.value) return

  const targetStatus = nextStatus.value
  updating.value = true

  try {
    const form = new FormData()

    form.append('status', targetStatus)

    // Reuse the latest tracked location when available. Proof submission and
    // the delivered API call must not wait for a new browser GPS reading.
    const rawLatitude = detail.value?.delivery?.current_latitude
    const rawLongitude = detail.value?.delivery?.current_longitude
    let latitude = rawLatitude !== null && rawLatitude !== undefined && rawLatitude !== ''
      ? Number(rawLatitude)
      : NaN
    let longitude = rawLongitude !== null && rawLongitude !== undefined && rawLongitude !== ''
      ? Number(rawLongitude)
      : NaN
    let hasTrackedLocation = Number.isFinite(latitude) && Number.isFinite(longitude)

    const canCaptureActionLocation = source.value === 'replacement'
      || (source.value === 'return_pickup' && currentDeliveryStatus.value !== 'assigned')
    if (canCaptureActionLocation && !hasTrackedLocation) {
      const point = await coordinates()
      latitude = point.latitude
      longitude = point.longitude
      hasTrackedLocation = true
      capturedAddress.value = await reverseGeocode(point).catch(() => '')
    }

    if (hasTrackedLocation) {
      form.append('latitude', String(latitude))
      form.append('longitude', String(longitude))
    }

    capturedAddress.value = capturedAddress.value || String(detail.value?.delivery?.current_address || '')

    if (capturedAddress.value) {
      form.append('location_address', capturedAddress.value)
    }

    form.append('photo', proofPhoto.value)

    if (statusNotes.value.trim()) {
      form.append('notes', statusNotes.value.trim())
    }

    if (source.value === 'replacement') {
      const replacementForm = new FormData()
      replacementForm.append('received_by', replacementReceivedBy.value.trim())
      replacementForm.append('proof', proofPhoto.value)
      if (hasTrackedLocation) {
        replacementForm.append('latitude', String(latitude))
        replacementForm.append('longitude', String(longitude))
      }
      if (capturedAddress.value) replacementForm.append('location_address', capturedAddress.value)
      await logisticsService.completeReplacementDelivery(orderId.value, replacementForm)
    } else if (source.value === 'return_pickup') {
      await logisticsService.updateReturnPickupWithProof(orderId.value, form)
    } else {
      await logisticsService.updateUnifiedDeliveryStatus(source.value as any, orderId.value, form)
    }

    proofDialog.value = false

    if (detail.value?.delivery) {
      detail.value.delivery.status = targetStatus
    }

    toast.add({
      severity: 'success',
      summary:
        targetStatus === 'delivered'
          ? source.value === 'pickup' ? 'Store Handoff Confirmed' : 'Delivery Completed'
          : source.value === 'pickup' && targetStatus === 'out_for_delivery'
            ? 'Supplier Pickup Confirmed'
          : 'Delivery In Transit',
      detail:
        targetStatus === 'delivered'
          ? source.value === 'pickup'
            ? 'The handoff photo was recorded and the goods were delivered to the receiving store.'
            : 'The delivery proof was recorded and the order was marked delivered.'
          : source.value === 'pickup' && targetStatus === 'out_for_delivery'
            ? 'The supplier pickup photo was recorded and the delivery is now on its way to the store.'
            : 'The proof photo was recorded and the delivery is now in transit.',
      life: 3000,
    })

    void load()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Submission Failed',
      detail:
        error?.response?.data?.message ||
        error?.message ||
        'A proof photo is required to update this delivery.',
      life: 3500,
    })
  } finally {
    updating.value = false
  }
}

const shareLocation = async () => {
  if (!canTrackOwnLocation.value) return

  sharingLocation.value = true

  try {
    const point = await coordinates()
    await sendLiveLocation(point, true)
  } catch (error: any) {
    toast.add({
      severity: 'warn',
      summary: 'Location Unavailable',
      detail:
        error?.message ||
        'Could not access your location.',
      life: 3000,
    })
  } finally {
    sharingLocation.value = false
  }
}

const sendLiveLocation = async (
  point: {
    latitude: number
    longitude: number
  },
  refresh = false,
) => {
  if (
    !canTrackOwnLocation.value ||
    !['pickup', 'ecommerce', 'sales', 'stock_transfer', 'return_pickup', 'replacement'].includes(
      source.value,
    ) ||
    isDelivered.value
  ) {
    return
  }

  const now = Date.now()

  if (!refresh && now - lastLocationSentAt < 5000) return

  lastLocationSentAt = now

  const locationAddress = await reverseGeocode(point).catch(
    () => '',
  )

  const locationPayload = {
      location_address: locationAddress || undefined,
      ...point,
  }
  if (source.value === 'return_pickup') await logisticsService.updateReturnPickupLocation(orderId.value, locationPayload)
  else await logisticsService.updateUnifiedDeliveryLocation(source.value as 'pickup' | 'ecommerce' | 'sales' | 'stock_transfer', orderId.value, locationPayload)

  if (detail.value?.delivery) {
    detail.value.delivery.current_latitude = point.latitude
    detail.value.delivery.current_longitude = point.longitude
    detail.value.delivery.current_address =
      locationAddress ||
      detail.value.delivery.current_address
  }

  await renderTrackingMap()
}

const updateTrackingState = () => {
  const status = String(
    detail.value?.delivery?.status || '',
  ).toLowerCase()

  const shouldTrack =
    (source.value !== 'replacement' || isAssignedDriver.value) &&
    (source.value !== 'pickup' || Number(detail.value?.delivery?.driver_user_id || 0) > 0) &&
    ['pickup', 'ecommerce', 'sales', 'stock_transfer', 'return_pickup', 'replacement'].includes(
      source.value,
    ) &&
    (source.value === 'pickup'
      ? ['en_route_to_supplier', 'in_transit', 'out_for_delivery']
      : source.value === 'return_pickup'
        ? ['picked_up', 'out_for_delivery']
        : source.value === 'replacement'
          ? ['out_for_delivery']
          : ['in_transit', 'out_for_delivery']).includes(
      status,
    )

  trackingActive.value = shouldTrack

  if (shouldTrack && canTrackOwnLocation.value && locationWatcher === null) {
    startLocationTracking()
  }

  if ((!shouldTrack || !canTrackOwnLocation.value) && locationWatcher !== null) {
    stopLocationTracking()
  }
}

const startLocationTracking = () => {
  if (!canTrackOwnLocation.value) return
  if (!navigator.geolocation || locationWatcher !== null) return

  coordinates()
    .then((point) => sendLiveLocation(point, true))
    .catch(() => undefined)

  locationWatcher = navigator.geolocation.watchPosition(
    (position) => {
      sendLiveLocation({
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
      }).catch(() => undefined)
    },
    () => {
      if (source.value !== 'pickup') trackingActive.value = false
    },
    {
      enableHighAccuracy: true,
      maximumAge: 5000,
      timeout: 15000,
    },
  )

  locationTimer = window.setInterval(() => {
    coordinates()
      .then((point) => sendLiveLocation(point))
      .catch(() => undefined)
  }, 5000)
}

const stopLocationTracking = () => {
  if (locationWatcher !== null) {
    navigator.geolocation.clearWatch(locationWatcher)
  }

  if (locationTimer !== null) {
    window.clearInterval(locationTimer)
  }

  locationWatcher = null
  locationTimer = null
  trackingActive.value = false
}

const fetchRoadRoute = fetchMapboxRoadRoute

const renderTrackingMap = async () => {
  if (
    !mapElement.value ||
    !['pickup', 'ecommerce', 'sales', 'stock_transfer', 'return_pickup', 'replacement'].includes(
      source.value,
    )
  ) {
    return
  }

  if (trackingMap && trackingMap.getContainer() !== mapElement.value) {
    trackingMap.remove()
    trackingMap = null
    truckMarker = null
    destinationMarker = null
  }

  if (!trackingMap) {
    mapboxgl = (await import('mapbox-gl')).default
    if (!mapElement.value || !trackingActive.value) return
    mapboxgl.accessToken = requireMapboxToken()
    const center = destinationPoint.value || [14.5995, 120.9842]
    const map = new mapboxgl.Map({ container: mapElement.value, style: 'mapbox://styles/mapbox/streets-v12', center: [center[1], center[0]], zoom: destinationPoint.value ? 13 : 10 })
    trackingMap = map
    await new Promise<void>((resolve) => map.once('load', () => resolve()))
    if (trackingMap !== map || !trackingActive.value) return
  }
  if (!trackingMap || !mapboxgl) return

  const delivery = detail.value?.delivery || {}

  const logs = (detail.value?.logs || [])
    .slice()
    .reverse()
    .filter(
      (log: any) =>
        log.latitude !== null &&
        log.longitude !== null,
    )

  const points: [number, number][] = logs
    .map((log: any) => [
      Number(log.latitude),
      Number(log.longitude),
    ])
    .filter(
      ([latitude, longitude]) =>
        Number.isFinite(latitude) &&
        Number.isFinite(longitude),
    )

  if (
    delivery.current_latitude &&
    delivery.current_longitude
  ) {
    points.push([
      Number(delivery.current_latitude),
      Number(delivery.current_longitude),
    ])
  }

  const lastPoint = points[points.length - 1]
  truckMarker?.remove()
  truckMarker = null
  destinationMarker?.remove()
  destinationMarker = null

  if (lastPoint) {
    const truckElement = document.createElement('img')
    truckElement.src = '/images/truck-map-marker-orange.png'
    truckElement.alt = 'Truck location'
    truckElement.style.cssText = 'width:100px;height:100px;object-fit:contain;'
    truckMarker = new mapboxgl.Marker({ element: truckElement, anchor: 'center' }).setLngLat([lastPoint[1], lastPoint[0]]).setPopup(new mapboxgl.Popup({ offset: 25 }).setText('Truck location')).addTo(trackingMap)
  }

  if (destinationPoint.value) {
    const destinationElement = document.createElement('div')
    destinationElement.style.cssText = 'width:18px;height:18px;border:3px solid white;border-radius:50%;background:#2563eb;box-shadow:0 1px 5px #0008;'
    const destinationLabel = returnPickupToCustomer.value ? 'Customer pickup location' : isSupplierLeg.value ? 'Supplier pickup location' : source.value === 'return_pickup' ? 'Drop-off branch' : ['ecommerce', 'sales'].includes(source.value) ? 'Delivery destination' : 'Store destination'
    destinationMarker = new mapboxgl.Marker({ element: destinationElement, anchor: 'center' }).setLngLat([destinationPoint.value[1], destinationPoint.value[0]]).setPopup(new mapboxgl.Popup({ offset: 15 }).setText(destinationLabel)).addTo(trackingMap)

    let linePoints: [number, number][] = lastPoint
      ? [lastPoint, destinationPoint.value]
      : [destinationPoint.value]

    if (lastPoint) {
      try {
        const roadPoints = await fetchRoadRoute(
          lastPoint,
          destinationPoint.value,
        )

        if (roadPoints.length > 1) {
          linePoints = roadPoints
        }
      } catch {
        // Keep the direct fallback line if the public routing service is unavailable.
      }
    }

    if (!trackingMap) return
    const routeFeature = { type: 'Feature' as const, properties: {}, geometry: { type: 'LineString' as const, coordinates: linePoints.map(([lat, lng]) => [lng, lat]) } }
    if (trackingMap.getSource('driver-delivery-route')) {
      (trackingMap.getSource('driver-delivery-route') as import('mapbox-gl').GeoJSONSource).setData(routeFeature)
    } else {
      trackingMap.addSource('driver-delivery-route', { type: 'geojson', data: routeFeature })
      trackingMap.addLayer({ id: 'driver-delivery-route-line', type: 'line', source: 'driver-delivery-route', paint: { 'line-color': '#2563eb', 'line-width': 5, 'line-opacity': 0.8 } })
    }

    const bounds = new mapboxgl.LngLatBounds()
    linePoints.forEach(([lat, lng]) => bounds.extend([lng, lat]))
    trackingMap.fitBounds(bounds, { padding: 40, maxZoom: 15 })
  }

  trackingMap.resize()
}

let pickupTrackingRefreshTimer: number | null = null

const refreshPickupTracking = async () => {
  if (source.value !== 'pickup' || isAssignedDriver.value || !trackingActive.value || loading.value) return

  try {
    const response = await logisticsService.getDeliveryOrderDetail('pickup', orderId.value)
    const latest = response?.data
    if (!latest?.delivery || !detail.value) return

    detail.value = {
      ...detail.value,
      order: latest.order || detail.value.order,
      delivery: latest.delivery,
      logs: latest.logs || detail.value.logs,
    }
    updateTrackingState()
    await nextTick()
    await renderTrackingMap()
  } catch {
    // Keep the last known pickup coordinates visible if a refresh is interrupted.
  }
}

const startPickupTrackingRefresh = () => {
  if (pickupTrackingRefreshTimer !== null) return
  pickupTrackingRefreshTimer = window.setInterval(() => {
    void refreshPickupTracking()
  }, 5000)
}

const stopPickupTrackingRefresh = () => {
  if (pickupTrackingRefreshTimer === null) return
  window.clearInterval(pickupTrackingRefreshTimer)
  pickupTrackingRefreshTimer = null
}

onMounted(() => {
  void load()
  startPickupTrackingRefresh()
})

watch(trackingActive, async (active) => {
  if (!active) return
  await nextTick()
  if (trackingMap) trackingMap.resize()
  else void renderTrackingMap().catch(() => undefined)
}, { flush: 'post' })

onActivated(() => {
  updateTrackingState()
  startPickupTrackingRefresh()
  if (trackingActive.value) void renderTrackingMap().catch(() => undefined)
})

const destroyMap = () => {
  stopLocationTracking()

  if (trackingMap) {
    trackingMap.remove()
    trackingMap = null
  }
  truckMarker = null
  destinationMarker = null
}

onDeactivated(() => {
  destroyMap()
  stopPickupTrackingRefresh()
})

onBeforeUnmount(() => {
  destroyMap()
  stopPickupTrackingRefresh()

  if (photoPreview.value) {
    URL.revokeObjectURL(photoPreview.value)
  }
})
</script>
