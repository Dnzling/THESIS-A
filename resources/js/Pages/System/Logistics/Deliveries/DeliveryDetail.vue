```vue
<template>
  <div
    class="mx-auto max-w-7xl space-y-4 px-4 py-5 text-sm sm:px-6 lg:px-8"
  >
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <Button
          icon="pi pi-arrow-left"
          text
          rounded
          size="small"
          severity="secondary"
          @click="goBack"
        />
        <div>
          <h1 class="text-xl font-semibold text-slate-900">
            {{
              order?.order_number ||
              order?.po_number ||
              'Delivery Details'
            }}
          </h1>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <Tag
          :value="formatDeliveryStatus(delivery?.status || 'pending')"
          :severity="deliverySeverity(delivery?.status)"
          class="text-xs"
        />

        <Button
          v-if="canAddToTrip"
          icon="pi pi-send"
          label="Add to Trip"
          severity="warn"
          size="small"
          @click="openAssign"
        />

        <Button
          v-if="canAssignDelivery"
          icon="pi pi-truck"
          label="Book with Lalamove"
          severity="warn"
          outlined
          size="small"
          @click="openLalamoveDialog"
        />

        <Button
          v-if="delivery?.lalamove_order_id && canManageLalamove"
          icon="pi pi-sync"
          label="Refresh Lalamove"
          outlined
          severity="secondary"
          size="small"
          :loading="refreshingLalamove"
          @click="refreshLalamove"
        />
      </div>
    </header>

    <div
      v-if="loading && !order"
      class="grid gap-4 lg:grid-cols-3"
    >
      <Skeleton
        v-for="item in 6"
        :key="item"
        height="8rem"
        borderRadius="16px"
      />
    </div>

    <template v-else-if="order">
      <!-- Order & Courier Information -->
      <section class="grid gap-4 lg:grid-cols-[1.25fr_0.75fr]">
        <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
          <template #title>
            <span class="text-base">Order Overview</span>
          </template>

          <template #content>
            <div class="grid gap-4 sm:grid-cols-2">
              <Info
                label="Customer"
                :value="customerName || '-'"
              />

              <Info
                label="Contact"
                :value="customerContact || '-'"
              />

              <Info
                label="Delivery Address"
                :value="deliveryAddress || '-'"
                class="sm:col-span-2"
              />

              <Info
                label="Order Status"
                :value="formatStatus(order.status)"
              />

              <Info
                v-if="source === 'ecommerce'"
                label="Payment Method"
                :value="formatPaymentMethod(order.payment_method || order.payment?.payment_method)"
              />

              <Info
                v-if="source === 'ecommerce'"
                label="Payment Status"
                :value="formatStatus(order.payment_status || order.payment?.status)"
              />

              <Info
                label="Order Total"
                :value="
                  formatCurrency(
                    order.total_amount || order.grand_total
                  )
                "
              />

              <Info
                label="Tracking Number"
                :value="delivery?.tracking_number || '-'"
              />

              <Info
                label="Expected Delivery"
                :value="
                  formatDateTime(
                    delivery?.estimated_delivery_at ||
                    delivery?.scheduled_delivery_at ||
                    delivery?.expected_delivery_date
                  )
                "
              />
            </div>
          </template>
        </Card>

        <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
          <template #title>
            <span class="text-base">Courier Details</span>
          </template>

          <template #content>
            <div
              v-if="delivery?.courier_provider === 'lalamove'"
              class="space-y-3 rounded-xl border border-orange-200 bg-orange-50/70 p-4"
            >
              <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="font-semibold text-slate-900">Lalamove delivery</p>
                <Tag :value="formatStatus(delivery.lalamove_status || 'quotation')" severity="info" />
              </div>
              <Info label="Lalamove Order ID" :value="delivery.lalamove_order_id || 'Not booked yet'" />
              <Info label="Vehicle service" :value="formatLalamoveService(delivery.lalamove_service_type)" />
              <div v-if="delivery.lalamove_price_breakdown?.total" class="text-sm">
                <span class="text-slate-500">Quoted delivery total:</span>
                <strong class="ml-1 text-slate-900">{{ formatLalamoveTotal(delivery.lalamove_price_breakdown) }}</strong>
              </div>
              <a
                v-if="delivery.lalamove_share_link"
                :href="delivery.lalamove_share_link"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 text-sm font-medium text-orange-700 hover:underline"
              >
                Track on Lalamove <i class="pi pi-external-link" />
              </a>
            </div>
            <div
              v-if="delivery && delivery.courier_provider !== 'lalamove'"
              class="space-y-4"
            >
              <div>
                <p class="font-semibold text-slate-900">
                  {{ driverName }}
                </p>

                <p class="text-xs text-slate-500">
                  Primary driver
                </p>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <Info
                  label="Contact"
                  :value="
                    delivery.courier_contact ||
                    delivery.driver_contact ||
                    delivery.driver?.phone_number ||
                    '-'
                  "
                />

                <Info
                  label="Plate Number"
                  :value="
                    delivery.vehicle?.plate_number ||
                    delivery.plate_number ||
                    '-'
                  "
                />

                <Info
                  label="Vehicle"
                  :value="vehicleLabel"
                  class="col-span-2"
                />
              </div>

              <Button
                v-if="delivery.trip_id"
                :label="`View Trip #${delivery.trip_id}`"
                icon="pi pi-external-link"
                severity="secondary"
                outlined
                size="small"
                @click="router.push({ name: 'logistics.trips.detail', params: { id: delivery.trip_id } })"
              />

              <div
                v-if="delivery.assistants?.length"
                class="border-t border-slate-100 pt-3"
              >
                <p
                  class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500"
                >
                  Delivery Assistants
                </p>

                <div class="flex flex-wrap gap-2">
                  <Chip
                    v-for="assistant in delivery.assistants"
                    :key="assistant.id"
                    :label="
                      assistant.branch
                        ? `${assistant.name} - ${assistant.branch}`
                        : assistant.name
                    "
                    class="text-xs"
                  />
                </div>
              </div>
            </div>

            <p
              v-else-if="!delivery"
              class="text-slate-500"
            >
              No courier has been assigned.
            </p>
          </template>
        </Card>
      </section>

      <!-- Live Delivery Tracking -->
      <Card
        v-if="showLiveTracking"
        class="rounded-2xl border border-slate-200/80 shadow-sm"
      >
        <template #title>
          <div class="flex items-center justify-between gap-2">
            <div>
              <p class="text-base">
                Live Delivery Tracking
              </p>

              <p class="text-xs font-normal text-slate-500">
                Latest driver location and destination
              </p>
            </div>

            <Tag
              :value="
                currentPoint
                  ? 'Location available'
                  : 'Waiting for location'
              "
              :severity="currentPoint ? 'success' : 'warn'"
              class="text-xs"
            />
          </div>
        </template>

        <template #content>
          <div
            ref="mapElement"
            class="h-[340px] w-full overflow-hidden rounded-xl border border-slate-200"
          ></div>

          <p
            v-if="!currentPoint"
            class="mt-2 text-xs text-amber-700"
          >
            The map updates after the driver shares a GPS location.
          </p>

          <p
            v-else-if="!destinationPoint"
            class="mt-2 text-xs text-amber-700"
          >
            A destination GPS location is not available for this order.
          </p>

          <p
            v-if="routeUnavailable"
            class="mt-2 text-xs text-amber-700"
          >
            Mapbox could not find a road route between the current location
            and destination.
          </p>
        </template>
      </Card>

      <!-- Order Items -->
      <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
        <template #title>
          <span class="text-base">
            Order Items ({{ orderItems.length }})
          </span>
        </template>

        <template #content>
          <DataTable
            :value="orderItems"
            size="small"
            stripedRows
            responsiveLayout="scroll"
            class="text-xs"
          >
            <template #empty>
              <div class="py-8 text-center text-slate-500">
                No order items recorded.
              </div>
            </template>

            <Column
              header="Product"
              style="min-width: 15rem"
            >
              <template #body="{ data }">
                <p class="font-medium text-slate-900">
                  {{ itemName(data) }}
                </p>

                <p class="text-xs text-slate-500">
                  SKU: {{ itemSku(data) }}
                </p>
                <div v-if="data.customization_request" class="mt-2 rounded-md bg-orange-50 p-2 text-xs text-slate-700">
                  <p class="font-semibold text-orange-800"><i class="pi pi-palette mr-1" />Upholstery order</p>
                  <p class="mt-1 whitespace-pre-line">{{ data.customization_request }}</p>
                  <p v-if="data.customization_lead_time_days" class="mt-1 text-slate-500">Lead time: {{ data.customization_lead_time_days }} days</p>
                </div>
              </template>
            </Column>

            <Column header="Quantity / Unit">
              <template #body="{ data }">
                <span class="font-medium">
                  {{ formatQuantity(itemQuantity(data)) }}
                  {{ itemUom(data) }}
                </span>
              </template>
            </Column>

            <Column header="Unit Price">
              <template #body="{ data }">
                {{ formatCurrency(itemUnitPrice(data)) }}
              </template>
            </Column>

            <Column header="Line Total">
              <template #body="{ data }">
                <span class="font-semibold">
                  {{ formatCurrency(itemLineTotal(data)) }}
                </span>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <!-- Delivery Logs -->
      <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
        <template #title>
          <div>
            <p class="text-base">
              Delivery Logs
            </p>

            <p class="text-xs font-normal text-slate-500">
              Read-only progress from the assigned delivery team
            </p>
          </div>
        </template>

        <template #content>
          <div
            v-if="!displayLogs.length"
            class="py-10 text-center text-slate-500"
          >
            No delivery activity has been recorded.
          </div>

          <div
            v-else
            class="space-y-3"
          >
            <article
              v-for="entry in displayLogs"
              :key="entry.id"
              class="rounded-xl border border-slate-200 p-4"
            >
              <div
                class="flex flex-wrap items-start justify-between gap-2"
              >
                <div>
                  <Tag
                    :value="
                      formatStatus(entry.event_type || 'update')
                    "
                    :severity="logSeverity(entry.event_type)"
                    class="text-xs"
                  />

                  <p class="mt-2 text-slate-800">
                    {{
                      logMessage(entry)
                    }}
                  </p>

                  <p
                    v-if="entry.creator"
                    class="mt-1 text-xs text-slate-500"
                  >
                    Recorded by {{ personName(entry.creator) }}
                  </p>
                </div>

                <time class="text-xs text-slate-500">
                  {{
                    formatDateTime(
                      entry.logged_at || entry.created_at
                    )
                  }}
                </time>
              </div>

              <div
                v-if="entry.status_from || entry.status_to"
                class="mt-2 text-xs text-slate-500"
              >
                {{ formatStatus(entry.status_from) }}
                to
                {{ formatStatus(entry.status_to) }}
              </div>

              <div
                v-if="entry.attachments?.length"
                class="mt-3 flex flex-wrap gap-3 border-t border-slate-100 pt-3"
              >
                <button
                  v-for="attachment in entry.attachments"
                  :key="attachment.id || attachment.public_url"
                  type="button"
                  class="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50"
                  @click="openMedia(attachment.public_url)"
                >
                  <img
                    :src="attachment.public_url"
                    alt="Delivery attachment"
                    class="h-28 w-36 object-cover transition group-hover:scale-105"
                  />

                  <span
                    class="absolute inset-x-0 bottom-0 bg-slate-950/65 px-2 py-1 text-center text-xs text-white"
                  >
                    View attachment
                  </span>
                </button>
              </div>
            </article>
          </div>
        </template>
      </Card>
    </template>

    <Message
      v-else
      severity="warn"
      :closable="false"
    >
      Delivery order was not found.
    </Message>

    <Dialog v-model:visible="tripChoiceVisible" modal header="Add Delivery to a Trip" class="w-full max-w-2xl">
      <div v-if="loadingCompatibleTrips" class="space-y-2">
        <Skeleton v-for="row in 3" :key="row" height="4.5rem" borderRadius="10px" />
      </div>
      <div v-else-if="compatibleTrips.length" class="space-y-3">
        <article v-for="trip in compatibleTrips" :key="trip.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
          <div>
            <p class="font-semibold text-slate-900">{{ tripReference(trip) }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ trip.driver?.name || trip.driver_name || 'Driver not set' }} · {{ trip.vehicle?.vehicle_name || trip.vehicle_name || 'Vehicle not set' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ trip.scheduled_departure_at ? formatDateTime(trip.scheduled_departure_at) : 'No departure time set' }}</p>
          </div>
          <Button label="Add to Trip" size="small" severity="warn" :loading="assigningTripId === Number(trip.id)" :disabled="Boolean(assigningTripId)" @click="addDeliveryToTrip(trip.id)" />
        </article>
      </div>
      <p v-else class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-center text-sm text-slate-600">
        No compatible planned trips are available. Create a trip for this delivery instead.
      </p>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="tripChoiceVisible = false" />
        <Button label="Create New Trip" icon="pi pi-plus" severity="warn" :disabled="Boolean(assigningTripId)" @click="openCreateTrip" />
      </template>
    </Dialog>

    <Dialog v-model:visible="createTripVisible" modal header="Create Trip" class="w-full max-w-2xl">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Driver</label>
          <Select v-model="tripForm.driver_user_id" :options="tripDrivers" optionLabel="name" optionValue="id" filter fluid placeholder="Select driver" :loading="loadingTripOptions" />
          <p v-if="!loadingTripOptions && !tripDrivers.length" class="mt-1 text-xs text-amber-700">No active driver is available for this store.</p>
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Vehicle</label>
          <Select v-model="tripForm.vehicle_id" :options="tripVehicles" optionLabel="label" optionValue="id" filter fluid placeholder="Select vehicle" :loading="loadingTripOptions" />
          <p v-if="!loadingTripOptions && !tripVehicles.length" class="mt-1 text-xs text-amber-700">No active vehicle is available for this store.</p>
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Scheduled Departure</label>
          <DatePicker v-model="tripForm.scheduled_departure_at" :minDate="new Date()" fluid />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Notes</label>
          <Textarea v-model="tripForm.notes" rows="3" fluid placeholder="Optional notes" />
        </div>
      </div>
      <template #footer>
        <Button label="Back to Trips" severity="secondary" outlined @click="createTripVisible = false" />
        <Button label="Create and Add Delivery" icon="pi pi-check" severity="warn" :loading="creatingTrip" :disabled="loadingTripOptions || !tripForm.driver_user_id || !tripForm.vehicle_id" @click="createTripAndAddDelivery" />
      </template>
    </Dialog>

    <!-- Media Preview -->
    <Dialog
      v-model:visible="mediaPreviewVisible"
      modal
      header="Delivery Attachment"
      class="w-full max-w-4xl"
    >
      <div
        class="flex min-h-72 items-center justify-center rounded-xl bg-slate-50 p-3"
      >
        <img
          :src="mediaPreviewUrl"
          alt="Delivery attachment preview"
          class="max-h-[70vh] max-w-full rounded-lg object-contain"
        />
      </div>
    </Dialog>

    <Dialog v-model:visible="lalamoveDialogVisible" modal header="Book with Lalamove" class="w-full max-w-xl">
      <div class="space-y-4">
        <Message severity="info" :closable="false">
          Sandbox booking only. Lalamove's quoted amount is shown for review; this does not change the customer's order total.
        </Message>
        <div v-if="loadingLalamoveServices" class="space-y-2">
          <Skeleton height="2.5rem" />
          <Skeleton height="2.5rem" />
        </div>
        <template v-else>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Service area *</label>
            <Select v-model="selectedLalamoveCity" :options="lalamoveCityOptions" optionLabel="label" optionValue="locode" fluid placeholder="Choose service area" @change="onLalamoveCityChange">
              <template #option="{ option }">
                <div class="flex min-w-0 items-center justify-between gap-4 py-1">
                  <span class="text-sm font-medium text-slate-800">{{ option.label }}</span>
                  <span class="shrink-0 text-xs text-slate-500">{{ option.locode }}</span>
                </div>
              </template>
            </Select>
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Vehicle service *</label>
            <Select v-model="selectedLalamoveService" :options="lalamoveServiceOptions" optionLabel="label" optionValue="key" fluid placeholder="Choose a vehicle service" @change="clearLalamoveQuote">
              <template #option="{ option }">
                <div class="min-w-0 py-1">
                  <p class="text-sm font-medium text-slate-800">{{ option.label }}</p>
                  <p class="text-xs text-slate-500">{{ option.description || 'Lalamove delivery service' }}</p>
                  <p v-if="option.serviceDetails" class="mt-0.5 text-xs text-slate-500">{{ option.serviceDetails }}</p>
                </div>
              </template>
            </Select>
            <small v-if="selectedLalamoveServiceOption" class="mt-1 block text-slate-500">
              {{ selectedLalamoveServiceOption.description || 'Lalamove delivery service' }}
              <template v-if="selectedLalamoveServiceOption.serviceDetails"> | {{ selectedLalamoveServiceOption.serviceDetails }}</template>
            </small>
          </div>

          <div v-if="lalamoveQuote" class="rounded-xl border border-orange-200 bg-orange-50 p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-orange-800">Sandbox quotation</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ formatLalamoveTotal(lalamoveQuote.price_breakdown) }}</p>
                <p class="mt-1 text-xs text-slate-600">{{ selectedLalamoveServiceOption?.label }} · Expires {{ formatDateTime(lalamoveQuote.expires_at) }}</p>
              </div>
              <Tag value="Review before booking" severity="warn" />
            </div>
            <p v-if="lalamoveQuoteExpired" class="mt-3 text-xs font-medium text-red-700">
              This quotation has expired. Get a new quotation before booking.
            </p>
          </div>
        </template>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="lalamoveDialogVisible = false" />
        <Button
          v-if="!lalamoveQuote || lalamoveQuoteExpired"
          :label="lalamoveQuote ? 'Get New Quotation' : 'Get Quotation'"
          icon="pi pi-calculator"
          severity="warn"
          :loading="quotingLalamove"
          :disabled="loadingLalamoveServices || !selectedLalamoveService"
          @click="requestLalamoveQuote"
        />
        <Button
          v-else
          label="Confirm Sandbox Booking"
          icon="pi pi-check"
          severity="warn"
          :loading="bookingLalamove"
          :disabled="lalamoveQuoteExpired"
          @click="bookLalamove"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import {
  computed,
  defineComponent,
  h,
  nextTick,
  onActivated,
  onBeforeUnmount,
  onDeactivated,
  onMounted,
  reactive,
  ref,
  watch,
} from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Chip from 'primevue/chip'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'

import type { Map as MapboxMap } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'

import logisticsService from '../../../../services/logistics.service'
import { useAuthStore } from '../../../../stores/auth'
import {
  fetchMapboxRoadRoute,
  requireMapboxToken,
} from '../../../../utils/mapbox'

const Info = defineComponent({
  props: {
    label: {
      type: String,
      required: true,
    },
    value: {
      type: [String, Number],
      default: '-',
    },
  },

  setup: (props) => () =>
    h('div', [
      h(
        'p',
        { class: 'text-xs text-slate-500' },
        props.label,
      ),
      h(
        'p',
        { class: 'mt-1 font-medium text-slate-900' },
        String(props.value || '-'),
      ),
    ]),
})

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const loading = ref(false)
const order = ref<any>(null)
const delivery = ref<any>(null)
const logs = ref<any[]>([])
const lalamoveDialogVisible = ref(false)
const loadingLalamoveServices = ref(false)
const quotingLalamove = ref(false)
const bookingLalamove = ref(false)
const refreshingLalamove = ref(false)
const lalamoveCities = ref<any[]>([])
const selectedLalamoveCity = ref<string | null>(null)
const selectedLalamoveService = ref<string | null>(null)
const lalamoveQuote = ref<any>(null)
const lalamoveQuoteClock = ref(Date.now())
let lalamoveQuoteClockTimer: ReturnType<typeof setInterval> | null = null

const mapElement = ref<HTMLElement | null>(null)

const mediaPreviewVisible = ref(false)
const mediaPreviewUrl = ref('')
const tripChoiceVisible = ref(false)
const createTripVisible = ref(false)
const loadingCompatibleTrips = ref(false)
const loadingTripOptions = ref(false)
const creatingTrip = ref(false)
const assigningTripId = ref<number | null>(null)
const compatibleTrips = ref<any[]>([])
const tripDrivers = ref<any[]>([])
const tripVehicles = ref<any[]>([])
const tripForm = reactive({
  driver_user_id: null as number | null,
  vehicle_id: null as number | null,
  scheduled_departure_at: null as Date | null,
  notes: '',
})

const routeUnavailable = ref(false)

let trackingMap: MapboxMap | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
let mapRenderId = 0

const source = computed<'ecommerce' | 'sales' | 'pickup'>(() => {
  const value = String(route.params.source || '').toLowerCase()

  return value === 'sales' || value === 'pickup'
    ? value
    : 'ecommerce'
})

const orderId = computed(() =>
  Number(route.params.orderId || 0),
)

const sourceLabel = computed(() => {
  if (source.value === 'pickup') {
    return 'Supplier pickup'
  }

  if (source.value === 'sales') {
    return 'Sales'
  }

  return 'Ecommerce'
})

const canAssignDelivery = computed(() => {
  if (!authStore.hasPermission('logistics.deliveries.manage') || source.value === 'pickup') {
    return false
  }

  const orderStatus = String(order.value?.status || '').toLowerCase()
  const deliveryStatus = String(delivery.value?.status || '').toLowerCase()
  const lalamoveRetryable = delivery.value?.courier_provider === 'lalamove'
    && ['failed_delivery', 'cancelled'].includes(deliveryStatus)
  const dispatchApproved = orderStatus === 'ready_for_dispatch'
    || (source.value === 'sales' && orderStatus === 'completed' && deliveryStatus === 'pending')
  const deliveryNotAssigned = !delivery.value || ['pending', 'ready_for_dispatch'].includes(deliveryStatus) || lalamoveRetryable

  return dispatchApproved && deliveryNotAssigned
})
const canAddToTrip = computed(() => canAssignDelivery.value
  && (!delivery.value || ['pending', 'ready_for_dispatch'].includes(String(delivery.value.status || '').toLowerCase())))
const canManageLalamove = computed(() => authStore.hasPermission('logistics.deliveries.manage'))
const lalamoveCityOptions = computed(() => lalamoveCities.value.map((city: any) => ({
  ...city,
  label: formatLalamoveServiceArea(city.name),
})))
const selectedLalamoveServiceOptions = computed(() => lalamoveCities.value.find(city => city.locode === selectedLalamoveCity.value)?.services || [])
const lalamoveServiceOptions = computed(() => selectedLalamoveServiceOptions.value.map((service: any) => ({
  ...service,
  label: formatLalamoveService(service.key),
  serviceDetails: [
    service.load?.value ? `Capacity ${service.load.value} ${service.load.unit || 'kg'}` : null,
    formatLalamoveDimensions(service.dimensions),
  ].filter(Boolean).join(' | '),
})))
const selectedLalamoveServiceOption = computed(() => lalamoveServiceOptions.value.find((service: any) => service.key === selectedLalamoveService.value))
const lalamoveQuoteExpired = computed(() => {
  if (!lalamoveQuote.value?.expires_at) return true
  const expiresAt = Date.parse(lalamoveQuote.value.expires_at)
  return !Number.isFinite(expiresAt) || expiresAt <= lalamoveQuoteClock.value
})

watch(lalamoveDialogVisible, (visible) => {
  if (visible) {
    lalamoveQuoteClock.value = Date.now()
    lalamoveQuoteClockTimer = setInterval(() => {
      lalamoveQuoteClock.value = Date.now()
    }, 1000)
    return
  }

  if (lalamoveQuoteClockTimer) {
    clearInterval(lalamoveQuoteClockTimer)
    lalamoveQuoteClockTimer = null
  }
})

const showLiveTracking = computed(
  () =>
    !!delivery.value &&
    delivery.value.courier_provider !== 'lalamove' &&
    !(
      source.value === 'ecommerce' &&
      String(delivery.value.status || '').toLowerCase() === 'delivered'
    ),
)

const orderItems = computed(() => order.value?.items || [])

const customerName = computed(() => {
  if (source.value === 'pickup') {
    return order.value?.supplier?.supplier_name
  }

  if (source.value === 'sales') {
    return order.value?.customer_name
  }

  return order.value?.shipping_name
})

const customerContact = computed(() => {
  if (source.value === 'pickup') {
    return order.value?.supplier?.phone
  }

  if (source.value === 'sales') {
    return order.value?.customer_phone
  }

  return order.value?.shipping_phone
})

const deliveryAddress = computed(() => {
  if (source.value === 'pickup') {
    return order.value?.branch?.address
  }

  if (source.value === 'sales') {
    return order.value?.delivery_address
  }

  return order.value?.shipping_address
})

const personName = (person: any) =>
  person
    ? [person.fname, person.lname]
        .filter(Boolean)
        .join(' ')
        .trim()
    : ''

const driverName = computed(
  () =>
    personName(
      delivery.value?.driver ||
      delivery.value?.driver_user,
    ) ||
    delivery.value?.driver_name ||
    delivery.value?.courier_name ||
    '-',
)

const vehicleLabel = computed(
  () =>
    [
      delivery.value?.vehicle?.vehicle_name,
      delivery.value?.vehicle?.brand,
      delivery.value?.vehicle?.model,
    ]
      .filter(Boolean)
      .join(' ') ||
    delivery.value?.truck_brand ||
    '-',
)

const point = (
  lat: any,
  lng: any,
): [number, number] | null => {
  if (
    Number.isFinite(Number(lat)) &&
    Number.isFinite(Number(lng)) &&
    Number(lat) !== 0 &&
    Number(lng) !== 0
  ) {
    return [Number(lat), Number(lng)]
  }

  return null
}

const currentPoint = computed<[number, number] | null>(() =>
  point(
    delivery.value?.current_latitude,
    delivery.value?.current_longitude,
  ),
)

const destinationPoint = computed<
  [number, number] | null
>(() => {
  if (source.value === 'sales') {
    return point(
      order.value?.delivery_latitude,
      order.value?.delivery_longitude,
    )
  }

  if (source.value === 'ecommerce') {
    return point(
      order.value?.customer_latitude,
      order.value?.customer_longitude,
    )
  }

  return point(
    order.value?.branch?.latitude,
    order.value?.branch?.longitude,
  )
})

const formatStatus = (value: any) =>
  String(value || '-')
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

const formatPaymentMethod = (value: any) =>
  String(value || '-')
    .replace(/[_-]/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

const displayLogs = computed(() => logs.value.filter((entry: any) => {
  if (entry.event_type !== 'status_updated' || !/^Delivery status updated from /i.test(entry.message || '')) return true

  return !logs.value.some((other: any) => {
    if (other.id === entry.id || other.event_type !== 'status_updated'
      || !/^Order status updated from /i.test(other.message || '')) return false
    const entryTime = new Date(entry.logged_at || entry.created_at).getTime()
    const otherTime = new Date(other.logged_at || other.created_at).getTime()
    return entry.status_from === other.status_from
      && entry.status_to === other.status_to
      && entry.created_by === other.created_by
      && Number.isFinite(entryTime) && Number.isFinite(otherTime)
      && Math.abs(entryTime - otherTime) <= 5000
  })
}))

const logMessage = (entry: any) => {
  if (entry.event_type === 'status_updated' && entry.status_to) {
    const subject = /^Order status updated/i.test(entry.message || '') ? 'Order' : 'Delivery'
    return `${subject} moved to ${formatStatus(entry.status_to)}.`
  }
  return String(entry.message || entry.notes || 'Delivery activity recorded.')
    .replace(/\b[a-z]+(?:_[a-z]+)+\b/g, (status) => formatStatus(status))
}

const formatDeliveryStatus = (value: any) => formatStatus(value)

const formatDateTime = (value: any) =>
  value
    ? new Date(value).toLocaleString('en-PH', {
        dateStyle: 'medium',
        timeStyle: 'short',
      })
    : '-'

const formatCurrency = (value: any) =>
  new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(Number(value) || 0)

const formatLalamoveTotal = (breakdown: any) => {
  const total = Number(breakdown?.total ?? breakdown?.totalExcludePriorityFee)
  const currency = String(breakdown?.currency || 'PHP')
  if (!Number.isFinite(total)) return '-'
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency }).format(total)
}

const formatLalamoveService = (value: any) => {
  const formatted = String(value || '').replace(/[_-]+/g, ' ').trim().toLowerCase()
  return formatted ? formatted.replace(/\b\w/g, character => character.toUpperCase()) : '-'
}

const formatLalamoveServiceArea = (value: any) => {
  const formatted = String(value || '').replace(/[_-]+/g, ' ').trim().toLowerCase()
  return formatted ? formatted.replace(/\b\w/g, character => character.toUpperCase()) : 'Unknown service area'
}

const formatLalamoveDimensions = (dimensions: any) => {
  if (!dimensions) return null
  const parts = ['length', 'width', 'height']
    .map(key => dimensions[key]?.value ? `${dimensions[key].value}${dimensions[key].unit || 'm'}` : null)
    .filter(Boolean)
  return parts.length ? `Dimensions ${parts.join(' x ')}` : null
}

const formatQuantity = (value: any) =>
  Number(value || 0).toLocaleString('en-PH', {
    maximumFractionDigits: 2,
  })

const deliverySeverity = (status: any) => {
  const value = String(status)

  if (value === 'delivered') {
    return 'success'
  }

  if (['failed_delivery', 'cancelled'].includes(value)) {
    return 'danger'
  }

  if (['in_transit', 'out_for_delivery'].includes(value)) {
    return 'warn'
  }

  return 'info'
}

const logSeverity = (type: any) => {
  const value = String(type)

  if (value.includes('deliver')) {
    return 'success'
  }

  if (
    value.includes('cancel') ||
    value.includes('fail')
  ) {
    return 'danger'
  }

  if (value.includes('transit')) {
    return 'warn'
  }

  return 'info'
}

const itemName = (item: any) =>
  item.product?.product_name ||
  item.product_name ||
  '-'

const itemSku = (item: any) =>
  item.product?.sku ||
  item.sku ||
  '-'

const itemQuantity = (item: any) =>
  item.quantity_ordered ??
  item.quantity ??
  0

const itemUom = (item: any) =>
  item.product?.unit_of_measurement ||
  item.unit_of_measurement ||
  item.unit ||
  'unit'

const itemUnitPrice = (item: any) =>
  item.unit_cost ??
  item.unit_price ??
  0

const itemLineTotal = (item: any) =>
  item.line_total ??
  Number(itemUnitPrice(item)) *
    Number(itemQuantity(item))

const renderMap = async () => {
  await nextTick()

  if (!mapElement.value) {
    return
  }

  const renderId = ++mapRenderId
  trackingMap?.remove()
  trackingMap = null
  routeUnavailable.value = false

  const current = currentPoint.value
  const destination = destinationPoint.value
  const center = current || destination || [14.5995, 120.9842]

  try {
    mapboxgl = (await import('mapbox-gl')).default
    mapboxgl.accessToken = requireMapboxToken()
    if (renderId !== mapRenderId || !mapElement.value) return
    const map = new mapboxgl.Map({
      container: mapElement.value,
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [center[1], center[0]],
      zoom: current || destination ? 13 : 9,
      attributionControl: true,
    })
    trackingMap = map
    await new Promise<void>((resolve) => map.once('load', () => resolve()))
    if (renderId !== mapRenderId || trackingMap !== map) return
  } catch (error) {
    routeUnavailable.value = true
    toast.add({ severity: 'error', summary: 'Map Unavailable', detail: 'Unable to load the Mapbox map. Check the map token and connection.', life: 3500 })
    return
  }

  const truckElement = document.createElement('img')
  truckElement.src = '/images/truck-map-marker-orange.png'
  truckElement.alt = 'Driver location'
  truckElement.style.cssText = 'width:56px;height:56px;object-fit:contain;cursor:pointer;'
  const destinationElement = document.createElement('div')
  destinationElement.style.cssText = 'width:18px;height:18px;border:3px solid white;border-radius:50%;background:#2563eb;box-shadow:0 1px 5px #0008;'

  if (current) new mapboxgl!.Marker({ element: truckElement, anchor: 'center' }).setLngLat([current[1], current[0]]).setPopup(new mapboxgl!.Popup({ offset: 25 }).setText('Driver location')).addTo(trackingMap!)
  if (destination) new mapboxgl!.Marker({ element: destinationElement, anchor: 'center' }).setLngLat([destination[1], destination[0]]).setPopup(new mapboxgl!.Popup({ offset: 15 }).setText('Destination')).addTo(trackingMap!)

  if (current && destination) {
    try {
      const roadPoints =
        await fetchMapboxRoadRoute(
          current,
          destination,
        )
      if (renderId !== mapRenderId || !trackingMap) return

      if (roadPoints.length > 1) {
        trackingMap!.addSource('delivery-route', { type: 'geojson', data: { type: 'Feature', properties: {}, geometry: { type: 'LineString', coordinates: roadPoints.map(([lat, lng]) => [lng, lat]) } } })
        trackingMap!.addLayer({ id: 'delivery-route-line', type: 'line', source: 'delivery-route', paint: { 'line-color': '#f97316', 'line-width': 5, 'line-opacity': 0.9 } })
        const bounds = new mapboxgl!.LngLatBounds()
        roadPoints.forEach(([lat, lng]) => bounds.extend([lng, lat]))
        trackingMap!.fitBounds(bounds, { padding: 40, maxZoom: 15 })
      } else {
        routeUnavailable.value = true

        trackingMap!.fitBounds([[current[1], current[0]], [destination[1], destination[0]]], { padding: 40, maxZoom: 15 })
      }
    } catch {
      if (renderId !== mapRenderId || !trackingMap) return
      // Do not draw a misleading straight line when
      // road routing is unavailable.
      routeUnavailable.value = true

      trackingMap!.fitBounds([[current[1], current[0]], [destination[1], destination[0]]], { padding: 40, maxZoom: 15 })
    }
  }
}

const loadAll = async () => {
  if (!orderId.value) {
    return
  }

  loading.value = true

  try {
    const response =
      await logisticsService.getDeliveryOrderDetail(
        source.value,
        orderId.value,
      )

    const payload = response?.data || {}

    order.value = payload.order || null
    delivery.value = payload.delivery || null
    logs.value = payload.logs || []

  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to load delivery',
      detail:
        error?.response?.data?.message ||
        'Please try again.',
      life: 3500,
    })
  } finally {
    loading.value = false
  }
}

const openLalamoveDialog = async () => {
  lalamoveDialogVisible.value = true
  lalamoveQuote.value = null
  loadingLalamoveServices.value = true
  try {
    const response = await logisticsService.getLalamoveServices()
    lalamoveCities.value = response?.data || []
    const manila = lalamoveCities.value.find((city: any) => /manila|ncr|luzon/i.test(`${city.name} ${city.locode}`))
    selectedLalamoveCity.value = manila?.locode || lalamoveCities.value[0]?.locode || null
    selectedLalamoveService.value = selectedLalamoveServiceOptions.value[0]?.key || null
  } catch (error: any) {
    lalamoveDialogVisible.value = false
    toast.add({ severity: 'error', summary: 'Lalamove unavailable', detail: error?.response?.data?.message || 'Could not load Lalamove sandbox services.', life: 4500 })
  } finally {
    loadingLalamoveServices.value = false
  }
}

const clearLalamoveQuote = () => { lalamoveQuote.value = null }
const onLalamoveCityChange = () => {
  clearLalamoveQuote()
  selectedLalamoveService.value = selectedLalamoveServiceOptions.value[0]?.key || null
}

const requestLalamoveQuote = async () => {
  if (!selectedLalamoveService.value) return
  quotingLalamove.value = true
  try {
    const response = await logisticsService.requestLalamoveQuote(source.value as 'ecommerce' | 'sales', orderId.value, { service_type: selectedLalamoveService.value })
    lalamoveQuote.value = response?.data || null
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Quotation failed', detail: error?.response?.data?.message || 'Unable to request a Lalamove quotation.', life: 4500 })
  } finally {
    quotingLalamove.value = false
  }
}

const bookLalamove = async () => {
  if (!lalamoveQuote.value) return
  bookingLalamove.value = true
  try {
    await logisticsService.bookLalamoveDelivery(source.value as 'ecommerce' | 'sales', orderId.value)
    lalamoveDialogVisible.value = false
    toast.add({ severity: 'success', summary: 'Lalamove booked', detail: 'The order is now assigned to Lalamove sandbox.', life: 3500 })
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Booking failed', detail: error?.response?.data?.message || 'Unable to book with Lalamove.', life: 4500 })
  } finally {
    bookingLalamove.value = false
  }
}

const refreshLalamove = async () => {
  refreshingLalamove.value = true
  try {
    await logisticsService.refreshLalamoveDelivery(source.value as 'ecommerce' | 'sales', orderId.value)
    await loadAll()
    toast.add({ severity: 'success', summary: 'Lalamove status refreshed', detail: 'The latest sandbox delivery status has been loaded.', life: 2500 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Refresh failed', detail: error?.response?.data?.message || 'Unable to refresh Lalamove status.', life: 4500 })
  } finally {
    refreshingLalamove.value = false
  }
}

const openMedia = (url: string) => {
  if (!url) {
    return
  }

  mediaPreviewUrl.value = url
  mediaPreviewVisible.value = true
}

const openAssign = async () => {
  tripChoiceVisible.value = true
  loadingCompatibleTrips.value = true
  try {
    const response = await logisticsService.getCompatibleTripsForOrder(source.value as 'ecommerce' | 'sales', orderId.value)
    compatibleTrips.value = response?.data || []
  } catch (error: any) {
    tripChoiceVisible.value = false
    toast.add({ severity: 'error', summary: 'Could Not Load Trips', detail: error?.response?.data?.message || 'Unable to load compatible trips.', life: 3500 })
  } finally {
    loadingCompatibleTrips.value = false
  }
}

const tripReference = (trip: any) => `TRIP-${String(trip.id).padStart(6, '0')}`

const addDeliveryToTrip = async (tripId: number | string) => {
  if (assigningTripId.value) return
  assigningTripId.value = Number(tripId)
  try {
    const response = await logisticsService.addOrdersToTrip(tripId, {
      source_type: source.value,
      order_ids: [orderId.value],
    })
    if (Number(response?.data?.added || 0) !== 1) {
      throw new Error(response?.message || 'The delivery could not be added to this trip.')
    }
    tripChoiceVisible.value = false
    toast.add({ severity: 'success', summary: 'Added to Trip', detail: 'The delivery was added to the selected trip.', life: 3000 })
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Assignment Failed', detail: error?.response?.data?.message || error?.message || 'Unable to add this delivery to the trip.', life: 4000 })
  } finally {
    assigningTripId.value = null
  }
}

const openCreateTrip = async () => {
  tripForm.driver_user_id = null
  tripForm.vehicle_id = null
  tripForm.scheduled_departure_at = null
  tripForm.notes = ''
  createTripVisible.value = true
  if (tripDrivers.value.length && tripVehicles.value.length) return

  loadingTripOptions.value = true
  try {
    const [employeeResponse, vehicleResponse] = await Promise.all([
      logisticsService.getLogisticsEmployees(),
      logisticsService.getVehicles({ per_page: 100, status: 'active' }),
    ])
    tripDrivers.value = employeeResponse?.data?.drivers || []
    tripVehicles.value = (vehicleResponse?.data?.data || []).map((vehicle: any) => ({
      ...vehicle,
      label: `${vehicle.vehicle_name} (${vehicle.plate_number})`,
    }))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Options Failed', detail: error?.response?.data?.message || 'Failed to load drivers and vehicles.', life: 3500 })
  } finally {
    loadingTripOptions.value = false
  }
}

const createTripAndAddDelivery = async () => {
  if (!tripForm.driver_user_id || !tripForm.vehicle_id || creatingTrip.value) return
  creatingTrip.value = true
  try {
    const response = await logisticsService.createTrip({
      driver_user_id: tripForm.driver_user_id,
      vehicle_id: tripForm.vehicle_id,
      scheduled_departure_at: tripForm.scheduled_departure_at ? new Date(tripForm.scheduled_departure_at).toISOString() : null,
      notes: tripForm.notes || null,
    })
    const tripId = Number(response?.data?.id)
    if (!tripId) throw new Error('Trip was created but no trip ID was returned.')

    createTripVisible.value = false
    await addDeliveryToTrip(tripId)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Create Failed', detail: error?.response?.data?.message || error?.message || 'Failed to create trip.', life: 4000 })
  } finally {
    creatingTrip.value = false
  }
}

const goBack = () =>
  router.push({
    name: 'logistics.deliveries',
  })

onMounted(loadAll)

watch(mapElement, (element) => {
  if (element) {
    void renderMap()
  } else {
    mapRenderId++
    trackingMap?.remove()
    trackingMap = null
  }
}, { flush: 'post' })

onActivated(() => {
  if (mapElement.value && !trackingMap) void renderMap()
})

const destroyMap = () => {
  mapRenderId++
  trackingMap?.remove()
  trackingMap = null
}

onDeactivated(destroyMap)
onBeforeUnmount(() => {
  if (lalamoveQuoteClockTimer) clearInterval(lalamoveQuoteClockTimer)
  destroyMap()
})
</script>
```
