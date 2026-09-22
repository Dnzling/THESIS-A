<template>
  <div class="mx-auto space-y-2 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex gap-2 justify-end items-center">
        <Button
          v-if="nextStatus && source !== 'return_pickup'"
          icon="pi pi-check"
          :label="nextStatusLabel"
          size="small"
          :loading="updating"
          :disabled="updating"
          @click="beginAdvance"
        />
      </div>
    </div>

    <Card v-if="source === 'return_pickup' && detail" class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title><span class="text-base">Return Delivery Progress</span></template>
      <template #content>
        <div class="flex flex-col gap-2 md:flex-row md:items-center">
          <template v-for="(step, index) in returnDeliverySteps" :key="step.value">
            <Button
              :label="step.label"
              :icon="returnStepIcon(step.value)"
              size="small"
              :severity="returnStepSeverity(step.value)"
              :outlined="!isReturnStepCompleted(step.value) && currentDeliveryStatus !== step.value"
              :disabled="step.value !== nextStatus || updating"
              :loading="updating && step.value === nextStatus"
              class="flex-1"
              @click="beginAdvance"
            />
            <i v-if="index < returnDeliverySteps.length - 1" class="pi pi-angle-right hidden text-slate-400 md:block"></i>
            <i v-if="index < returnDeliverySteps.length - 1" class="pi pi-angle-down self-center text-slate-400 md:hidden"></i>
          </template>
        </div>
        <p class="mt-3 text-xs text-slate-500">The next step uses the required GPS and delivery-proof process.</p>
      </template>
    </Card>

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

            <div>
              <small class="text-slate-500">Recipient</small>
              <p>{{ recipientName }}</p>
            </div>

            <div>
              <small class="text-slate-500">Driver</small>
              <p>{{ detail.delivery?.courier_name || '-' }}</p>
            </div>

               <div>
              <small class="text-slate-500">Courier Contact Number</small>
              <p>{{ detail.delivery?.courier_contact || '-' }}</p>
            </div>

            <div>
              <small class="text-slate-500">Vehicle</small>
              <p>{{ vehicleLabel }}</p>
            </div>

            <div>
              <small class="text-slate-500">Expected date</small>
              <p>
                {{
                  formatDate(
                    detail.delivery?.estimated_delivery_at ||
                      detail.order?.estimated_delivery_at,
                  )
                }}
              </p>
            </div>
          </div>

          <div class="grid gap-3 md:grid-cols-2">
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
                  Your current GPS position refreshes the delivery is active.
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
              The store destination does not have map coordinates yet.
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

              <Column header="Received">
                <template #body="{ data }">
                  {{
                    data.quantity_received ??
                    data.received_quantity ??
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

              <Column header="Line Total">
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
          :disabled="!proofPhoto || updating"
          :loading="updating"
          @click="submitPickupStatus"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import logisticsService from '@/services/logistics.service'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import {
  fetchMapboxRoadRoute,
  requireMapboxToken,
  reverseGeocodeMapbox,
} from '@/utils/mapbox'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const updating = ref(false)
const sharingLocation = ref(false)
const detail = ref<any>(null)
const resolvedCurrentAddress = ref('')

const proofDialog = ref(false)
const proofPhoto = ref<File | null>(null)
const photoPreview = ref('')
const statusNotes = ref('')
const capturedAddress = ref('')

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
  String(route.params.source || '').toLowerCase(),
)

const orderId = computed(() => String(route.params.orderId))

const label = (value: string) =>
  String(value || 'Pending')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase())

const formatActivityMessage = (value: unknown) =>
  String(value || '').replace(
    /\b(from|to)\s+([a-z][a-z0-9_]*)(?=\b|\.)/gi,
    (_match, direction: string, status: string) =>
      `${direction.toLowerCase()} ${label(status)}`,
  )

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

const recipientName = computed(() =>
  source.value === 'stock_transfer'
    ? `${detail.value?.order?.from_branch?.name || 'Source branch'} to ${
        detail.value?.order?.to_branch?.name || 'Destination branch'
      }`
    : detail.value?.order?.supplier?.supplier_name ||
      detail.value?.order?.shipping_name ||
      detail.value?.order?.customer_name ||
      '-',
)

const pickupAddress = computed(() =>
  source.value === 'stock_transfer'
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
  source.value === 'stock_transfer'
    ? detail.value?.delivery?.destination_address ||
      detail.value?.order?.to_branch?.address ||
      '-'
    : detail.value?.delivery?.destination_address ||
      detail.value?.order?.shipping_address ||
      detail.value?.order?.branch?.address ||
      '-',
)

const destinationPoint = computed<[number, number] | null>(() => {
  const branch = detail.value?.order?.branch || {}

  const latitude = Number(
    source.value === 'ecommerce'
      ? detail.value?.order?.customer_latitude
      : branch.latitude ?? detail.value?.delivery?.destination_latitude,
  )

  const longitude = Number(
    source.value === 'ecommerce'
      ? detail.value?.order?.customer_longitude
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
  source.value === 'return_pickup'
    ? ({ assigned: 'picked_up', picked_up: 'out_for_delivery', out_for_delivery: 'delivered' } as any)[currentDeliveryStatus.value]
    : source.value === 'pickup'
    ? (
        {
          pending: 'in_transit',
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
  if (source.value === 'return_pickup' && nextStatus.value === 'picked_up') {
    return 'Confirm Pickup & Start Delivery'
  }
  if (nextStatus.value === 'out_for_delivery') {
    return 'Mark Out for Delivery'
  }

  if (nextStatus.value === 'delivered') {
    return source.value === 'pickup'
      ? 'Confirm Arrival at Store'
      : 'Mark Delivered'
  }

  return source.value === 'pickup'
    ? 'Start Supplier Pickup'
    : `Mark ${label(nextStatus.value)}`
})

const proofPhotoLabel = computed(() =>
  source.value === 'return_pickup' && nextStatus.value === 'picked_up'
    ? 'Customer pickup photo'
    : source.value === 'return_pickup' && nextStatus.value === 'delivered'
      ? 'Return delivered to branch photo'
      :
  nextStatus.value === 'delivered'
    ? source.value === 'pickup'
      ? 'Arrival at store photo'
      : 'Delivered order photo'
    : source.value === 'pickup'
      ? 'Supplier pickup photo'
      : 'Dispatch pickup photo',
)

const proofInstruction = computed(() =>
  source.value === 'return_pickup'
    ? nextStatus.value === 'picked_up'
      ? 'Attach a photo confirming the item was collected from the customer. Your GPS location will also be captured.'
      : 'Attach a photo confirming the returned item was delivered to the destination branch. Your final GPS location will be captured.'
    :
  nextStatus.value === 'delivered'
    ? `Attach a photo showing the ${
        source.value === 'pickup'
          ? 'supplies arriving at the store'
          : 'order delivered to the customer'
      }. Your final GPS location will be captured before tracking stops.`
    : `Attach a photo showing the ${
        source.value === 'pickup'
          ? 'supplies at the supplier pickup point'
          : 'order before dispatch'
      }. Your GPS location will be captured before marking it In Transit.`,
)

const proofSubmitLabel = computed(() =>
  source.value === 'return_pickup' && nextStatus.value === 'picked_up'
    ? 'Confirm Pickup'
    : nextStatus.value === 'delivered'
    ? 'Mark Delivered'
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
  if (!nextStatus.value) return

  const needsProof =
    ['pickup', 'ecommerce', 'return_pickup'].includes(source.value) &&
    (source.value === 'return_pickup'
      ? ['picked_up', 'delivered'].includes(nextStatus.value)
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
    return
  }

  await updateRegularDelivery()
}

const updateRegularDelivery = async () => {
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

    // Delivery milestones are status actions. Send them immediately instead
    // of blocking the request on browser GPS or Mapbox reverse geocoding.
    const isImmediateMilestone = ['out_for_delivery', 'delivered'].includes(targetStatus)
    let point: { latitude: number; longitude: number } | null = null
    let locationAddress = ''

    if (!isImmediateMilestone) {
      point = await coordinates()
      locationAddress = await reverseGeocode(point).catch(() => '')
    }

    await logisticsService.updateUnifiedDeliveryStatus(
      source.value as any,
      orderId.value,
      {
        status: targetStatus,
        ...(point ? { latitude: point.latitude, longitude: point.longitude } : {}),
        location_address: locationAddress || undefined,
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
  if (!proofPhoto.value || !nextStatus.value) return

  const targetStatus = nextStatus.value
  updating.value = true

  try {
    const form = new FormData()

    form.append('status', targetStatus)

    // Reuse the latest tracked location when available. Proof submission and
    // the delivered API call must not wait for a new browser GPS reading.
    let latitude = Number(detail.value?.delivery?.current_latitude)
    let longitude = Number(detail.value?.delivery?.current_longitude)
    let hasTrackedLocation = Number.isFinite(latitude) && Number.isFinite(longitude)

    if (source.value === 'return_pickup' && !hasTrackedLocation) {
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

    if (source.value === 'return_pickup') {
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
          ? 'Delivery Completed'
          : 'Delivery In Transit',
      detail:
        targetStatus === 'delivered'
          ? 'The delivery proof was recorded and the order was marked delivered.'
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
    !['pickup', 'ecommerce', 'stock_transfer'].includes(
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
  else await logisticsService.updateUnifiedDeliveryLocation(source.value as 'pickup' | 'ecommerce' | 'stock_transfer', orderId.value, locationPayload)

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
    ['pickup', 'ecommerce', 'stock_transfer', 'return_pickup'].includes(
      source.value,
    ) &&
    (source.value === 'return_pickup' ? ['picked_up', 'out_for_delivery'] : ['in_transit', 'out_for_delivery']).includes(
      status,
    )

  trackingActive.value = shouldTrack

  if (shouldTrack && locationWatcher === null) {
    startLocationTracking()
  }

  if (!shouldTrack && locationWatcher !== null) {
    stopLocationTracking()
  }
}

const startLocationTracking = () => {
  if (!navigator.geolocation || locationWatcher !== null) return

  locationWatcher = navigator.geolocation.watchPosition(
    (position) => {
      sendLiveLocation({
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
      }).catch(() => undefined)
    },
    () => {
      trackingActive.value = false
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
    !['pickup', 'ecommerce', 'stock_transfer', 'return_pickup'].includes(
      source.value,
    )
  ) {
    return
  }

  if (!trackingMap) {
    mapboxgl = (await import('mapbox-gl')).default
    mapboxgl.accessToken = requireMapboxToken()
    const center = destinationPoint.value || [14.5995, 120.9842]
    trackingMap = new mapboxgl.Map({ container: mapElement.value, style: 'mapbox://styles/mapbox/streets-v12', center: [center[1], center[0]], zoom: destinationPoint.value ? 13 : 10 })
    await new Promise<void>((resolve) => trackingMap!.once('load', () => resolve()))
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
    destinationMarker = new mapboxgl.Marker({ element: destinationElement, anchor: 'center' }).setLngLat([destinationPoint.value[1], destinationPoint.value[0]]).setPopup(new mapboxgl.Popup({ offset: 15 }).setText(source.value === 'ecommerce' ? 'Delivery destination' : 'Store destination')).addTo(trackingMap)

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

onMounted(load)

onBeforeUnmount(() => {
  stopLocationTracking()

  if (trackingMap) {
    trackingMap.remove()
    trackingMap = null
  }
  truckMarker = null
  destinationMarker = null

  if (photoPreview.value) {
    URL.revokeObjectURL(photoPreview.value)
  }
})
</script>
