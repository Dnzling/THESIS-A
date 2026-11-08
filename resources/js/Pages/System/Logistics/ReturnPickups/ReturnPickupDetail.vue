<template>
  <div class="mx-auto max-w-6xl space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <header class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex min-w-0 items-center gap-3">
        <Button icon="pi pi-chevron-left" rounded aria-label="Go back" v-tooltip.bottom="'Go back'"
          class="!h-10 !w-10 !border-slate-200 !bg-white !text-slate-600 !shadow-sm" @click="goBack" />
        <div class="min-w-0">
          <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">Return Pickup</p>
          <h1 class="truncate text-xl font-semibold tracking-tight text-slate-900">{{ returnIdLabel }}</h1>
          <p class="mt-1 truncate text-sm text-slate-600">Order {{ sourceOrderReference }}</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
          <Badge :value="formatStatus(pickup?.status || 'ready_for_dispatch')" :severity="statusSeverity(pickup?.status)" />
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
            label="Add to Trip"
            severity="warn"
            size="small"
            @click="openAssignment"
          />
      </div>
    </header>

    <Card class="rounded-2xl border border-slate-200/80 shadow-sm">
      <template #title><span class="text-base">Pickup Overview</span></template>
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton height="1.25rem" width="45%" />
          <Skeleton height="4.5rem" />
        </div>
        <div v-else-if="!pickup" class="text-sm text-slate-500">No pickup data found.</div>
        <div v-else class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
          <div class="rounded-xl border border-orange-200 bg-orange-50/60 p-3"><span class="text-xs font-medium text-slate-500">Pickup Date</span><strong class="mt-1 block text-slate-900">{{ pickup.scheduled_at ? formatDateOnly(pickup.scheduled_at) : 'Not scheduled' }}</strong></div>
          <div class="rounded-xl border border-slate-200 p-3"><span class="text-xs font-medium text-slate-500">Driver</span><strong class="mt-1 block text-slate-900">{{ driverLabel }}</strong></div>
          <div class="rounded-xl border border-slate-200 p-3"><span class="text-xs font-medium text-slate-500">Vehicle</span><strong class="mt-1 block text-slate-900">{{ vehicleLabel }}</strong><small v-if="pickup.vehicle?.plate_number" class="mt-0.5 block text-slate-500">{{ pickup.vehicle.plate_number }}</small></div>
          <div class="rounded-xl border border-slate-200 p-3"><span class="text-xs font-medium text-slate-500">Trip</span><strong class="mt-1 block text-slate-900">{{ pickup.trip_reference || 'Not assigned' }}</strong></div>
          <div class="rounded-xl border border-slate-200 p-3 sm:col-span-2"><span class="text-xs font-medium text-slate-500">Customer Pickup Address</span><strong class="mt-1 block whitespace-pre-line text-slate-900">{{ pickup.pickup_address || '-' }}</strong></div>
          <div class="rounded-xl border border-slate-200 p-3 sm:col-span-2"><span class="text-xs font-medium text-slate-500">Return Destination</span><strong class="mt-1 block text-slate-900">{{ pickup.destination_branch?.name || 'Branch not assigned' }}</strong><small class="mt-0.5 block whitespace-pre-line text-slate-600">{{ pickup.destination_branch?.address || '-' }}</small></div>
          <div class="rounded-xl border border-slate-200 p-3"><span class="text-xs font-medium text-slate-500">Pickup Contact</span><strong class="mt-1 block text-slate-900">{{ pickup.pickup_name || '-' }}</strong><small v-if="pickup.pickup_phone" class="mt-0.5 block text-slate-600">{{ pickup.pickup_phone }}</small></div>
          <div class="rounded-xl border border-slate-200 p-3"><span class="text-xs font-medium text-slate-500">Picked Up At</span><strong class="mt-1 block text-slate-900">{{ pickup.picked_up_at ? formatDateTime(pickup.picked_up_at) : 'Not picked up' }}</strong></div>
          <div class="rounded-xl border border-slate-200 p-3"><span class="text-xs font-medium text-slate-500">Distance</span><strong class="mt-1 block text-slate-900">{{ pickup.distance_km != null ? `${pickup.distance_km} km` : '-' }}</strong></div>
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
          <div><span class="text-slate-500">Order Reference:</span> <strong>{{ sourceOrderReference }}</strong></div>
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
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="pickup?.return_request" class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Returned Items</template>
      <template #content>
        <DataTable :value="returnItems" size="small" rowHover responsiveLayout="scroll">
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

    <Dialog v-model:visible="scheduleDialogVisible" modal header="Edit Pickup Schedule" class="w-full max-w-3xl" @show="onScheduleDialogShow">
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Scheduled At</label>
          <DatePicker v-model="scheduleForm.scheduled_at" :minDate="getTodayDate()" dateFormat="M d, yy" showIcon class="w-full" />
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
          <div ref="returnPickupMapContainer" class="h-64 w-full rounded-xl border border-slate-200" />
          <p class="text-xs text-slate-500">Set the customer's pickup point. The return destination is the branch that fulfilled the original order.</p>
          <div class="rounded-xl border border-orange-200 bg-orange-50/70 p-3">
            <div class="flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Return route estimate</p>
                <p class="mt-1 text-sm text-slate-700">To {{ pickup?.destination_branch?.name || pickup?.return_request?.sales_order?.branch?.name || pickup?.return_request?.order?.assigned_branch?.name || 'Original fulfillment branch' }}</p>
              </div>
              <div class="text-right">
                <p class="text-xs text-slate-500">Estimated shipping fee</p>
                <strong v-if="routeEstimate" class="text-lg text-slate-900">₱ {{ formatMoney(routeEstimate.estimated_fee) }}</strong>
                <span v-else-if="estimateLoading" class="text-sm text-slate-500">Calculating…</span>
                <span v-else class="text-sm text-slate-500">Not calculated</span>
              </div>
            </div>
            <p v-if="routeEstimate" class="mt-2 text-xs text-slate-600">{{ Number(routeEstimate.distance_km).toFixed(2) }} km route distance<span v-if="routeEstimate.breakdown?.responsibility === 'store'"> · Fee covered by the store</span></p>
            <div v-if="routeEstimate?.breakdown" class="mt-2 flex flex-wrap gap-x-4 gap-y-1 border-t border-orange-200 pt-2 text-xs text-slate-600">
              <span>Base ₱{{ formatMoney(routeEstimate.breakdown.base_fee) }}</span>
              <span>Distance ₱{{ formatMoney(routeEstimate.breakdown.distance_fee) }}</span>
              <span>Weight ₱{{ formatMoney(routeEstimate.breakdown.weight_fee) }}</span>
              <span v-if="Number(routeEstimate.breakdown.minimum_fee_adjustment || 0) > 0">Minimum fee adjustment ₱{{ formatMoney(routeEstimate.breakdown.minimum_fee_adjustment) }}</span>
            </div>
            <p v-if="estimateError" class="mt-2 text-xs text-red-700">{{ estimateError }}</p>
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm text-slate-600">Notes</label>
          <Textarea v-model="scheduleForm.notes" rows="2" class="w-full" autoResize />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="scheduleDialogVisible = false" />
        <Button icon="pi pi-save" label="Save" :loading="savingSchedule" :disabled="!routeEstimate || estimateLoading" @click="saveSchedule" />
      </template>
    </Dialog>

    <Dialog v-model:visible="tripChoiceVisible" modal header="Assign Return Pickup to a Trip" class="w-full max-w-2xl">
      <div class="space-y-4">
        <div v-if="pickup" class="rounded-xl border border-orange-100 bg-orange-50/70 p-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-orange-700">Return pickup</p>
          <p class="mt-1 font-semibold text-slate-900">{{ returnIdLabel }}</p>
          <p class="text-sm text-slate-600">{{ pickup.pickup_name || 'Customer' }} · {{ pickup.pickup_address || 'No pickup address' }}</p>
          <p class="mt-1 text-xs text-slate-500">Estimated item weight: {{ returnPickupWeightKg.toFixed(2) }} kg</p>
        </div>

        <div v-if="loadingCompatibleTrips" class="space-y-3">
          <Skeleton height="5.5rem" borderRadius="12px" />
          <Skeleton height="5.5rem" borderRadius="12px" />
        </div>
        <div v-else-if="compatibleTrips.length" class="space-y-3">
          <p class="text-sm font-medium text-slate-700">Select a compatible planned trip</p>
          <article v-for="trip in compatibleTrips" :key="trip.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
            <div class="min-w-0 flex-1">
              <p class="font-semibold text-slate-900">{{ trip.trip_reference }} · {{ trip.driver_name }}</p>
              <p class="mt-1 text-sm text-slate-600">{{ trip.vehicle_name }}<span v-if="trip.plate_number"> · {{ trip.plate_number }}</span></p>
              <p class="mt-1 text-xs text-slate-500">
                {{ trip.assigned_stops }}{{ trip.max_stops ? ` / ${trip.max_stops}` : '' }} stops
                <span v-if="trip.remaining_capacity_after_kg !== null"> · {{ trip.remaining_capacity_after_kg }} kg remaining after adding</span>
                <span v-if="trip.scheduled_departure_at"> · Departs {{ formatDateOnly(trip.scheduled_departure_at) }}</span>
              </p>
            </div>
            <Button label="Select Trip" icon="pi pi-plus" size="small" severity="warn"
              :loading="assigningTripId === trip.id" :disabled="Boolean(assigningTripId)"
              @click="addPickupToTrip(trip)" />
          </article>
        </div>
        <div v-else-if="!loadingCompatibleTrips" class="rounded-xl border border-dashed border-slate-300 p-5 text-center">
          <i class="pi pi-inbox text-2xl text-slate-400" />
          <p class="mt-2 text-sm font-semibold text-slate-700">No compatible planned trips</p>
          <p class="mt-1 text-xs text-slate-500">A trip may not have enough vehicle capacity or stop slots, or may serve a different pickup area.</p>
        </div>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined :disabled="Boolean(assigningTripId)" @click="tripChoiceVisible = false" />
        <Button label="Create New Trip" icon="pi pi-plus" severity="warn" :disabled="Boolean(assigningTripId) || !pickup" @click="createTripForPickup" />
      </template>
    </Dialog>

    <Dialog v-model:visible="createTripVisible" modal header="Create Trip for Return Pickup" class="w-full max-w-3xl">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Driver *</label>
          <Select v-model="tripForm.driver_user_id" :options="tripDrivers" optionLabel="name" optionValue="id" filter fluid placeholder="Select driver" :loading="loadingTripOptions" />
          <div v-if="selectedTripDriver" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex items-start gap-3"><div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm font-semibold text-orange-700">{{ tripInitials(selectedTripDriver.name) }}</div><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-slate-900">{{ selectedTripDriver.name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ selectedTripDriver.role || 'Delivery Driver' }}<span v-if="selectedTripDriver.employee_number"> ({{ selectedTripDriver.employee_number }})</span></p></div></div>
            <div class="mt-3 grid gap-x-4 gap-y-2 text-xs sm:grid-cols-2"><div><span class="text-slate-500">Contact</span><p class="mt-0.5 break-all font-medium text-slate-800">{{ selectedTripDriver.contact || 'Not provided' }}</p></div><div><span class="text-slate-500">Email</span><p class="mt-0.5 break-all font-medium text-slate-800">{{ selectedTripDriver.email || 'Not provided' }}</p></div><div class="sm:col-span-2"><span class="text-slate-500">Branch</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedTripDriver.branch || 'No branch assigned' }}</p></div></div>
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Vehicle *</label>
          <Select v-model="tripForm.vehicle_id" :options="tripVehicles" optionLabel="label" optionValue="id" filter fluid placeholder="Select vehicle" :loading="loadingTripOptions" />
          <div v-if="selectedTripVehicle" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ selectedTripVehicle.vehicle_name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ selectedTripVehicle.vehicle_type || 'Vehicle' }}<span v-if="selectedTripVehicle.plate_number"> ({{ selectedTripVehicle.plate_number }})</span></p></div><Badge :value="selectedTripVehicle.status || 'Active'" :severity="selectedTripVehicle.status === 'active' ? 'success' : 'secondary'" /></div>
            <div class="mt-3 grid gap-x-4 gap-y-2 text-xs sm:grid-cols-2"><div><span class="text-slate-500">Make / model</span><p class="mt-0.5 font-medium text-slate-800">{{ [selectedTripVehicle.brand, selectedTripVehicle.model].filter(Boolean).join(' ') || 'Not provided' }}</p></div><div><span class="text-slate-500">Color</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedTripVehicle.color || 'Not provided' }}</p></div><div><span class="text-slate-500">Load capacity</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedTripVehicle.capacity_kg ? `${Number(selectedTripVehicle.capacity_kg).toLocaleString()} kg` : 'Not set' }}</p></div><div><span class="text-slate-500">Stops per trip</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedTripVehicle.max_orders_per_trip ?? 'Not set' }}</p></div><div class="sm:col-span-2"><span class="text-slate-500">Assigned branch</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedTripVehicle.branch?.name || 'Branch not specified' }}</p></div><div v-if="selectedTripVehicle.notes" class="sm:col-span-2"><span class="text-slate-500">Vehicle notes</span><p class="mt-0.5 whitespace-pre-line text-slate-700">{{ selectedTripVehicle.notes }}</p></div></div>
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Employees Accompanying the Driver</label>
          <MultiSelect v-model="tripForm.assistant_user_ids" :options="availableAssistants" optionLabel="name" optionValue="id" filter display="chip" fluid placeholder="Select employees (optional)" />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Scheduled Departure</label>
          <DatePicker v-model="tripForm.scheduled_departure_at" :minDate="new Date()" :disabledDates="tripUnavailableDepartureDates" fluid>
            <template #date="{ date }"><span :class="{ 'text-red-600 line-through': isTripDateUnavailable(date) }">{{ date.day }}</span></template>
          </DatePicker>
          <p v-if="tripForm.vehicle_id" class="mt-1 text-xs text-slate-500">Crossed-out dates already have a planned trip for this vehicle.</p>
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Notes</label>
          <Textarea v-model="tripForm.notes" rows="3" fluid placeholder="Optional notes" />
        </div>
      </div>
      <template #footer>
        <Button label="Back" severity="secondary" outlined :disabled="creatingTrip" @click="createTripVisible = false; tripChoiceVisible = true" />
        <Button label="Create and Add Pickup" icon="pi pi-check" severity="warn" :loading="creatingTrip" :disabled="loadingTripOptions || !tripForm.driver_user_id || !tripForm.vehicle_id || hasTripDateConflict(tripForm.scheduled_departure_at)" @click="createTripAndAddPickup" />
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
import Skeleton from 'primevue/skeleton'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import Textarea from 'primevue/textarea'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import { forwardGeocodeMapbox, requireMapboxToken } from '@/utils/mapbox'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const id = computed(() => Number((route as any).params?.id))
const loading = ref(false)
const pickup = ref<any>(null)
const tripChoiceVisible = ref(false)
const loadingCompatibleTrips = ref(false)
const compatibleTrips = ref<any[]>([])
const assigningTripId = ref<number | null>(null)
const createTripVisible = ref(false)
const loadingTripOptions = ref(false)
const creatingTrip = ref(false)
const tripDrivers = ref<any[]>([])
const tripAssistants = ref<any[]>([])
const tripVehicles = ref<any[]>([])
const tripUnavailableDepartureDates = ref<Date[]>([])
const tripForm = reactive({ driver_user_id: null as number | null, assistant_user_ids: [] as number[], vehicle_id: null as number | null, scheduled_departure_at: null as Date | null, notes: '' })
const availableAssistants = computed(() => tripAssistants.value.filter(employee => Number(employee.id) !== Number(tripForm.driver_user_id)))
const selectedTripDriver = computed(() => tripDrivers.value.find(driver => Number(driver.id) === Number(tripForm.driver_user_id)) || null)
const selectedTripVehicle = computed(() => tripVehicles.value.find(vehicle => Number(vehicle.id) === Number(tripForm.vehicle_id)) || null)
const tripInitials = (name?: string) => String(name || '?').trim().split(/\s+/).slice(0, 2).map(part => part[0]?.toUpperCase() || '').join('')
watch(() => tripForm.driver_user_id, driverId => {
  tripForm.assistant_user_ids = tripForm.assistant_user_ids.filter(id => Number(id) !== Number(driverId))
})
watch(() => tripForm.vehicle_id, vehicleId => { void loadTripUnavailableDepartureDates(vehicleId) })

const loadTripUnavailableDepartureDates = async (vehicleId: number | null) => {
  tripUnavailableDepartureDates.value = []
  if (!vehicleId) return
  try {
    const response = await logisticsService.getVehicleUnavailableDepartureDates(vehicleId)
    tripUnavailableDepartureDates.value = (response?.data || []).map((value: string) => new Date(`${value}T00:00:00`))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Schedule Check Failed', detail: error?.response?.data?.message || 'Unable to check this vehicle’s planned trips.', life: 3500 })
  }
}

const isTripDateUnavailable = (date: any) => tripUnavailableDepartureDates.value.some(item =>
  item.getFullYear() === date.year && item.getMonth() === date.month && item.getDate() === date.day,
)
const hasTripDateConflict = (value: Date | null) => Boolean(value && isTripDateUnavailable({ year: value.getFullYear(), month: value.getMonth(), day: value.getDate() }))

const returnIdLabel = computed(() => pickup.value?.return_request?.return_number || 'Return number unavailable')
const sourceOrderReference = computed(() => pickup.value?.return_request?.order?.order_number || 'Order reference unavailable')

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

const goBack = () => {
  if ((window.history.state as any)?.back) {
    router.back()
    return
  }
  router.push('/logistics/return-pickups')
}

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
  if (status === 'ready_for_dispatch') return 'For Dispatch'
  return String(status).replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
}
const statusSeverity = (status?: string) => {
  if (['picked_up', 'delivered'].includes(String(status))) return 'success'
  if (['assigned', 'out_for_delivery'].includes(String(status))) return 'info'
  if (['ready_for_dispatch', 'scheduled'].includes(String(status))) return 'warn'
  if (status === 'cancelled') return 'secondary'
  return 'secondary'
}
const formatLogMessage = (message?: string) => String(message || '').replace(/\b(ready_for_dispatch|out_for_delivery|in_transit|assigned|delivered)\b/g, value => formatStatus(value))

const formatDateTime = (value: any) => {
  const date = new Date(value)
  return new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' }).format(date)
}

const formatDateOnly = (value: any) => {
  const raw = String(value || '')
  const date = /^\d{4}-\d{2}-\d{2}$/.test(raw) ? new Date(`${raw}T00:00:00`) : new Date(raw)
  return Number.isNaN(date.getTime()) ? '-' : new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit' }).format(date)
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

const returnPickupWeightKg = computed(() => {
  const product = pickup.value?.return_request?.order_item?.product || pickup.value?.return_request?.orderItem?.product
  return Number(product?.weight_kg || 0) * Number(pickup.value?.return_request?.requested_quantity || 0)
})

const openAssignment = async () => {
  tripChoiceVisible.value = true
  loadingCompatibleTrips.value = true
  compatibleTrips.value = []
  try {
    const response = await logisticsService.getCompatibleTripsForOrder('return_pickup', id.value)
    compatibleTrips.value = response?.data || []
  } catch (error: any) {
    tripChoiceVisible.value = false
    toast.add({ severity: 'error', summary: 'Could Not Load Trips', detail: error?.response?.data?.message || 'Unable to check planned trips for this return pickup.', life: 3500 })
  } finally {
    loadingCompatibleTrips.value = false
  }
}

const addPickupToTrip = async (trip: any) => {
  if (!pickup.value || assigningTripId.value) return
  assigningTripId.value = Number(trip.id)
  try {
    const response = await logisticsService.addOrdersToTrip(trip.id, {
      source_type: 'return_pickup',
      order_ids: [id.value],
    })
    if (Number(response?.data?.added || 0) !== 1) {
      throw new Error(response?.message || 'The return pickup could not be added to this trip.')
    }
    tripChoiceVisible.value = false
    toast.add({ severity: 'success', summary: 'Trip Updated', detail: 'The return pickup was added to the selected trip.', life: 3000 })
    await loadPickup()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Assignment Failed', detail: error?.response?.data?.message || error?.message || 'Unable to add the return pickup to this trip.', life: 4000 })
  } finally {
    assigningTripId.value = null
  }
}

const createTripForPickup = () => {
  if (!pickup.value) return
  tripChoiceVisible.value = false
  tripForm.driver_user_id = null
  tripForm.assistant_user_ids = []
  tripForm.vehicle_id = null
  tripForm.scheduled_departure_at = null
  tripForm.notes = ''
  createTripVisible.value = true
  void loadTripOptions()
}

const loadTripOptions = async () => {
  if (tripDrivers.value.length && tripAssistants.value.length && tripVehicles.value.length) return
  loadingTripOptions.value = true
  try {
    const [employeeResponse, vehicleResponse] = await Promise.all([
      logisticsService.getLogisticsEmployees(),
      logisticsService.getVehicles({ per_page: 100, status: 'active' }),
    ])
    tripDrivers.value = employeeResponse?.data?.drivers || []
    tripAssistants.value = employeeResponse?.data?.employees || []
    tripVehicles.value = (vehicleResponse?.data?.data || []).map((vehicle: any) => ({ ...vehicle, label: `${vehicle.vehicle_name} (${vehicle.plate_number})` }))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Options Failed', detail: error?.response?.data?.message || 'Unable to load employees and vehicles.', life: 3500 })
  } finally {
    loadingTripOptions.value = false
  }
}

const createTripAndAddPickup = async () => {
  if (!pickup.value || !tripForm.driver_user_id || !tripForm.vehicle_id || creatingTrip.value) return
  if (hasTripDateConflict(tripForm.scheduled_departure_at)) {
    toast.add({ severity: 'warn', summary: 'Vehicle Already Booked', detail: 'Choose another vehicle or departure date.', life: 3000 })
    return
  }
  creatingTrip.value = true
  let tripId: number | null = null
  try {
    const response = await logisticsService.createTrip({
      driver_user_id: tripForm.driver_user_id,
      assistant_user_ids: tripForm.assistant_user_ids,
      vehicle_id: tripForm.vehicle_id,
      scheduled_departure_at: toDateOnly(tripForm.scheduled_departure_at),
      notes: tripForm.notes || null,
    })
    tripId = Number(response?.data?.id) || null
    if (!tripId) throw new Error('The trip was created but no trip ID was returned.')
    createTripVisible.value = false
    await addPickupToTrip({ id: tripId })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Create Failed', detail: `${error?.response?.data?.message || error?.message || 'Unable to create a trip.'}${tripId ? ' The trip was created, but the pickup could not be linked. Open Trips to finish assigning it.' : ''}`, life: 5000 })
  } finally {
    creatingTrip.value = false
  }
}

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
const returnPickupMapContainer = ref<HTMLElement | null>(null)
const savingSchedule = ref(false)
const scheduleForm = reactive({
  scheduled_at: null as any,
  pickup_name: '',
  pickup_phone: '',
  pickup_address: '',
  notes: '',
})
const routeEstimate = ref<any>(null)
const estimateLoading = ref(false)
const estimateError = ref('')
let estimateTimer: ReturnType<typeof setTimeout> | null = null
let estimateRequestId = 0

const getTodayDate = () => new Date()
const toDateOnly = (value: Date | null) => value
  ? `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
  : null

const openScheduleDialog = async () => {
  if (!pickup.value) return
  const scheduledDate = pickup.value.scheduled_at ? new Date(pickup.value.scheduled_at) : null
  scheduleForm.scheduled_at = scheduledDate && !Number.isNaN(scheduledDate.getTime())
    ? new Date(scheduledDate.getFullYear(), scheduledDate.getMonth(), scheduledDate.getDate())
    : null
  scheduleForm.pickup_name = pickup.value.pickup_name || pickup.value.return_request?.order?.shipping_name || ''
  scheduleForm.pickup_phone = pickup.value.pickup_phone || pickup.value.return_request?.order?.shipping_phone || ''
  scheduleForm.pickup_address = pickup.value.pickup_address || pickup.value.return_request?.order?.shipping_address || ''
  scheduleForm.notes = pickup.value.notes || ''
  mapState.latitude = Number(pickup.value.pickup_latitude ?? pickup.value.return_request?.order?.customer_latitude ?? pickup.value.return_request?.sales_order?.delivery_latitude ?? 14.3294)
  mapState.longitude = Number(pickup.value.pickup_longitude ?? pickup.value.return_request?.order?.customer_longitude ?? pickup.value.return_request?.sales_order?.delivery_longitude ?? 120.9367)
  routeEstimate.value = null
  estimateError.value = ''
  mapState.searchQuery = scheduleForm.pickup_address || pickup.value.return_request?.order?.shipping_address || mapState.searchQuery
  scheduleDialogVisible.value = true
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
      pickup_latitude: mapState.latitude ?? undefined,
      pickup_longitude: mapState.longitude ?? undefined,
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

let pickupMap: MapboxMap | null = null
let pickupMarker: MapboxMarker | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
const mapState = reactive({
  searching: false,
  searchQuery: 'Dasmariñas City, Cavite',
  latitude: null as number | null,
  longitude: null as number | null,
})

const requestRouteEstimate = async () => {
  if (!scheduleDialogVisible.value || !pickup.value || mapState.latitude === null || mapState.longitude === null) return
  const requestId = ++estimateRequestId
  estimateLoading.value = true
  estimateError.value = ''
  try {
    const response = await logisticsService.estimateReturnPickup(id.value, {
      pickup_latitude: mapState.latitude,
      pickup_longitude: mapState.longitude,
    })
    if (requestId === estimateRequestId) routeEstimate.value = response?.data || null
  } catch (error: any) {
    if (requestId === estimateRequestId) {
      routeEstimate.value = null
      estimateError.value = error?.response?.data?.message || 'Unable to calculate distance and shipping fee for this pin.'
    }
  } finally {
    if (requestId === estimateRequestId) estimateLoading.value = false
  }
}

watch(
  () => [mapState.latitude, mapState.longitude, scheduleDialogVisible.value] as const,
  ([latitude, longitude, visible]) => {
    if (estimateTimer) clearTimeout(estimateTimer)
    if (!visible || latitude === null || longitude === null) return
    estimateTimer = setTimeout(() => void requestRouteEstimate(), 400)
  },
)

const setPickupMapPoint = (longitude: number, latitude: number) => {
  const normalizedLatitude = Number(latitude.toFixed(6))
  const normalizedLongitude = Number(longitude.toFixed(6))
  mapState.latitude = normalizedLatitude
  mapState.longitude = normalizedLongitude
  if (!pickupMap || !mapboxgl) return
  const point: [number, number] = [normalizedLongitude, normalizedLatitude]
  if (pickupMarker) pickupMarker.setLngLat(point)
  else pickupMarker = new mapboxgl.Marker({ color: '#f97316', draggable: true }).setLngLat(point).addTo(pickupMap)
  pickupMap.flyTo({ center: point, zoom: Math.max(pickupMap.getZoom(), 14) })
}

const initMap = async () => {
  const container = returnPickupMapContainer.value
  if (!container) return

  const lat = Number(mapState.latitude ?? pickup.value?.return_request?.order?.customer_latitude ?? 14.3294) || 14.3294
  const lng = Number(mapState.longitude ?? pickup.value?.return_request?.order?.customer_longitude ?? 120.9367) || 120.9367
  mapState.latitude = Number(lat.toFixed(6))
  mapState.longitude = Number(lng.toFixed(6))

  if (!mapboxgl) {
    mapboxgl = (await import('mapbox-gl')).default
    mapboxgl.accessToken = requireMapboxToken()
  }

  if (!pickupMap) {
    pickupMap = new mapboxgl.Map({
      container,
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [lng, lat],
      zoom: 14,
    })
    pickupMarker = new mapboxgl.Marker({ color: '#f97316', draggable: true })
      .setLngLat([lng, lat])
      .addTo(pickupMap)
    pickupMarker.on('dragend', () => {
      const point = pickupMarker?.getLngLat()
      if (point) {
        mapState.latitude = Number(point.lat.toFixed(6))
        mapState.longitude = Number(point.lng.toFixed(6))
      }
    })
    pickupMap.on('click', (event) => setPickupMapPoint(event.lngLat.lng, event.lngLat.lat))
    pickupMap.once('load', () => pickupMap?.resize())
  } else {
    pickupMarker?.setLngLat([lng, lat])
    pickupMap.setCenter([lng, lat])
    pickupMap.resize()
  }
}

const onScheduleDialogShow = async () => {
  await nextTick()
  requestAnimationFrame(() => {
    void initMap().catch((error: any) => {
      toast.add({ severity: 'error', summary: 'Map unavailable', detail: error?.message || 'Unable to load the pickup map.', life: 3500 })
    })
  })
}

async function searchLocation() {
  if (!mapState.searchQuery.trim()) return
  mapState.searching = true
  try {
    const result = await forwardGeocodeMapbox(mapState.searchQuery.trim())
    if (result) setPickupMapPoint(result.longitude, result.latitude)
    else toast.add({ severity: 'warn', summary: 'Location not found', detail: 'Try a more specific pickup address.', life: 3000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Map search failed', detail: error?.message || 'Unable to search this address.', life: 3500 })
  } finally {
    mapState.searching = false
  }
}

onBeforeUnmount(() => {
  if (estimateTimer) clearTimeout(estimateTimer)
  if (pickupMap) {
    pickupMap.remove()
    pickupMap = null
    pickupMarker = null
    mapboxgl = null
  }
})

onMounted(async () => {
  await loadPickup()
})
</script>
