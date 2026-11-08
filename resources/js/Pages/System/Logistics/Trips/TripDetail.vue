<template>
  <div class="mx-auto space-y-5 px-4 py-5 sm:px-6 lg:px-8">
    <header class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex min-w-0 items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded aria-label="Go back" class="!h-10 !w-10 shrink-0 border border-slate-200 bg-white shadow-sm" @click="goBack" />
        <div class="min-w-0">
          <h1 class="truncate text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">{{ tripReference }}</h1>
          <p class="mt-1 text-sm text-slate-500">Delivery trip details and assigned stops</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <Tag v-if="trip" :value="formatStatus(trip.status)" :severity="statusSeverity(trip.status)" />
        <Button v-if="canManage && trip?.status === 'planned'" icon="pi pi-pencil" label="Edit Trip" size="small" severity="warn" outlined :disabled="loading" @click="openEditTrip" />
        <Button icon="pi pi-print" size="small" severity="secondary" outlined :label="printingManifest ? 'Opening...' : 'Print Manifest'" :loading="printingManifest" @click="printTripManifest" />
      </div>
    </header>

    <div v-if="loading" class="grid gap-4 md:grid-cols-3"><div v-for="item in 3" :key="item" class="h-28 animate-pulse rounded-2xl border border-slate-200 bg-white" /></div>
    <div v-else-if="!trip" class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500">Trip not found.</div>
    <template v-else>
      <div class="flex flex-wrap gap-3">
        <div class="min-w-[min(100%,16rem)] flex-1 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Departure</p>
          <p class="mt-2 text-lg font-semibold text-slate-900">{{ formatDateOnly(trip.scheduled_departure_at) }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ assignedOrders.length }} assigned stop{{ assignedOrders.length === 1 ? '' : 's' }}</p>
        </div>
        <div class="min-w-[min(100%,16rem)] flex-1 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Driver & vehicle</p>
          <p class="mt-2 truncate text-base font-semibold text-slate-900">{{ driverName }}</p>
          <p class="mt-1 truncate text-sm text-slate-500">{{ vehicleLabel }}</p>
        </div>
        <div v-if="trip.status !== 'completed'" class="min-w-[min(100%,16rem)] flex-1 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
          <div class="flex items-center justify-between gap-3">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Truck load</p>
              <p class="mt-2 text-lg font-semibold text-slate-900">{{ capacityLabel }}</p>
            </div>
            <i class="pi pi-truck rounded-xl bg-orange-50 p-3 text-orange-600" />
          </div>
          <div v-if="vehicleCapacity > 0" class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
            <div class="h-full rounded-full transition-all" :class="capacityWarning ? 'bg-red-500' : 'bg-orange-500'" :style="{ width: `${capacityProgressPercent}%` }" />
          </div>
          <p class="mt-2 text-xs" :class="capacityWarning ? 'text-red-600' : 'text-slate-500'">
            {{ vehicleCapacity > 0 ? (capacityWarning ? 'Truck capacity exceeded' : `${capacityRemaining.toFixed(2)} kg capacity remaining`) : 'Truck capacity is not set' }}
          </p>
        </div>
      </div>

      <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
        <template #title><span class="text-base font-semibold">Trip & crew</span></template>
        <template #content>
          <div class="grid gap-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Accompanying employees</p>
              <div v-if="trip.assistant_users?.length" class="mt-2 flex flex-wrap gap-1.5"><Tag v-for="employee in trip.assistant_users" :key="employee.id" :value="`${employee.fname || ''} ${employee.lname || ''}`.trim() || employee.email" severity="secondary" /></div>
              <p v-else class="mt-2 text-slate-700">No assistants assigned</p>
            </div>
            <div>
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Trip created</p>
              <p class="mt-2 text-slate-900">{{ formatDateTime(trip.created_at) }}</p>
            </div>
            <div class="sm:col-span-2 lg:col-span-1">
              <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Notes</p>
              <p class="mt-2 whitespace-pre-line text-slate-700">{{ trip.notes || 'No notes added.' }}</p>
            </div>
          </div>
        </template>
      </Card>
    </template>

    <ConfirmDialog />

    <Dialog v-model:visible="editTripVisible" modal header="Edit Planned Trip" class="w-full max-w-2xl">
      <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Driver *</label>
          <Select v-model="editTripForm.driver_user_id" :options="editTripDrivers" optionLabel="name" optionValue="id" filter fluid placeholder="Select driver" :loading="loadingEditTripOptions" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Vehicle *</label>
          <Select v-model="editTripForm.vehicle_id" :options="editTripVehicles" optionLabel="label" optionValue="id" filter fluid placeholder="Select vehicle" :loading="loadingEditTripOptions" />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Employees Accompanying the Driver</label>
          <MultiSelect v-model="editTripForm.assistant_user_ids" :options="editableAssistants" optionLabel="name" optionValue="id" filter display="chip" fluid placeholder="Select employees (optional)" :loading="loadingEditTripOptions" />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Scheduled Departure</label>
          <DatePicker v-model="editTripForm.scheduled_departure_at" dateFormat="M d, yy" :disabledDates="editTripUnavailableDates" showIcon fluid>
            <template #date="{ date }"><span :class="{ 'text-red-600 line-through': isEditTripDateUnavailable(date) }">{{ date.day }}</span></template>
          </DatePicker>
          <p v-if="editTripForm.vehicle_id" class="mt-1 text-xs text-slate-500">Crossed-out dates already have a planned trip for this vehicle.</p>
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm text-slate-600">Notes</label>
          <Textarea v-model="editTripForm.notes" rows="3" fluid placeholder="Optional notes" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined :disabled="savingTripEdit" @click="editTripVisible = false" />
        <Button label="Save Changes" icon="pi pi-check" severity="warn" :loading="savingTripEdit" :disabled="loadingEditTripOptions || !editTripForm.driver_user_id || !editTripForm.vehicle_id || isEditTripDateUnavailable(editTripForm.scheduled_departure_at)" @click="saveTripEdit" />
      </template>
    </Dialog>
  
    <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
      <template #title>
        <div class="flex flex-wrap items-center justify-between gap-2">
          <span class="text-base font-semibold">Assigned stops</span>
          <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">{{ assignedOrders.length }} total</span>
        </div>
      </template>
      <template #content>
        <div v-if="!assignedOrders.length" class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
          <i class="pi pi-inbox text-xl text-slate-400" />
          <p class="mt-2 text-sm font-medium text-slate-700">No stops assigned yet</p>
          <p class="mt-1 text-xs text-slate-500">{{ trip?.status === 'planned' ? 'Add dispatch-ready orders below to build this trip.' : 'This trip has no assigned deliveries.' }}</p>
        </div>
        <div v-else class="grid gap-3 lg:hidden">
          <article v-for="row in assignedOrders" :key="row.id" class="rounded-xl border border-slate-200 bg-white p-4">
            <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="text-xs font-medium text-slate-500">{{ row.source }}</p><p class="mt-1 truncate font-semibold text-slate-900">{{ row.order_number || '-' }}</p></div><Tag :value="formatStatus(row.status)" :severity="statusSeverity(row.status)" /></div>
            <p class="mt-3 text-sm font-medium text-slate-800">{{ row.customer_name || '-' }}</p>
            <p class="mt-1 line-clamp-2 text-sm text-slate-500">{{ row.address || '-' }}</p>
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
              <span v-if="row.delivery_date">Delivery: {{ formatDateOnly(row.delivery_date) }}</span>
              <span>Weight: {{ Number(row.weight_kg || 0).toFixed(2) }} kg</span>
            </div>
            <div class="mt-4 flex flex-wrap gap-2"><Button label="View" icon="pi pi-eye" size="small" outlined @click="openAssignedOrderDetail(row)" /><Button v-if="canManage && trip?.status === 'planned'" label="Remove" icon="pi pi-times" size="small" severity="danger" outlined @click="removeOrder(row)" /></div>
          </article>
        </div>
        <DataTable v-if="assignedOrders.length" :value="assignedOrders" dataKey="id" rowHover class="hidden lg:block">
          <Column header="Stop / Order" style="min-width: 13rem">
            <template #body="{ data }"><p class="text-xs text-slate-500">{{ data.source }}</p><p class="mt-0.5 font-medium text-slate-900">{{ data.order_number || '-' }}</p></template>
          </Column>
          <Column field="customer_name" header="Recipient" style="min-width: 11rem" />
          <Column header="Delivery Date" style="width: 10rem"><template #body="{ data }">{{ formatDateOnly(data.delivery_date) }}</template></Column>
          <Column field="address" header="Destination" style="min-width: 15rem" />
          <Column header="Weight" style="width: 7rem">
            <template #body="{ data }"><span class="whitespace-nowrap text-slate-600">{{ Number(data.weight_kg || 0).toFixed(2) }} kg</span></template>
          </Column>
          <Column header="Status" style="width: 9rem">
            <template #body="{ data }">
              <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column header="Actions" style="width: 9rem">
            <template #body="{ data }">
              <div class="flex items-center gap-1"><Button icon="pi pi-eye" text rounded aria-label="View stop" @click="openAssignedOrderDetail(data)" /><Button v-if="canManage && trip?.status === 'planned'" icon="pi pi-times" text rounded severity="danger" aria-label="Remove stop" @click="removeOrder(data)" /></div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  
    <Card v-if="canManage && trip?.status === 'planned'" class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Add Orders to Trip</template>
      <template #content>
        <div class="flex flex-wrap items-center gap-2 mb-3">
          <Select v-model="availableSource" :options="sourceOptions" optionLabel="label" optionValue="value" size="small"
            class="w-40" />
          <Button :loading="loadingAvailable" label="Load Ready Orders" outlined @click="loadAvailableOrders" rounded severity="contrast" size="small" />
          <Button :loading="loadingSuggestion" :disabled="!canManage" icon="pi pi-sparkles" size="small" rounded
            label="Suggest Batch" severity="help" outlined @click="suggestBatch" />
          <Button v-if="selectedOrders.length" :disabled="!canManage || willExceedCapacity" label="Add to Trip" size="small" rounded @click="addSelected" />
        </div>
        <div v-if="!canManage" class="mb-2 text-xs text-rose-600">
          Action disabled: requires `logistics.deliveries.manage` permission.
        </div>
        <div v-if="vehicleCapacity > 0" class="mb-3 text-xs text-slate-500">
          Selected weight: <strong>{{ selectedWeightKg.toFixed(2) }}</strong> kg / Remaining capacity:
          <strong>{{ capacityRemaining.toFixed(2) }}</strong> kg
          <span v-if="willExceedCapacity" class="ml-2 font-semibold text-rose-600">Over capacity</span>
        </div>
        <div v-if="selectedOrders.length && suggestedArea" class="mb-3 rounded-xl border border-violet-100 bg-violet-50 px-3 py-2 text-xs text-violet-800">
          Suggested batch: {{ selectedOrders.length }} order{{ selectedOrders.length === 1 ? '' : 's' }} for <strong>{{ suggestedArea }}</strong>,
          selected by delivery area, distance, vehicle weight, and stop limit. Review, then click <strong>Add to Trip</strong>.
        </div>
  
        <DataTable :value="availableOrders" dataKey="id" stripedRows selectionMode="multiple" :loading="loadingAvailable" class="text-sm"
          v-model:selection="selectedOrders" paginator :rows="availablePerPage" :totalRecords="availableTotal"
          :first="(availablePage - 1) * availablePerPage" @page="handleAvailablePage">
          <template #empty>
            <div class="px-4 py-9 text-center">
              <i v-if="loadingAvailable" class="pi pi-spinner pi-spin text-xl text-slate-400" />
              <i v-else class="pi pi-inbox text-2xl text-slate-400" />
              <p class="mt-2 text-sm font-semibold text-slate-700">{{ loadingAvailable ? 'Loading dispatch-ready orders...' : 'No dispatch-ready orders found' }}</p>
              <p v-if="!loadingAvailable" class="mx-auto mt-1 max-w-md text-xs text-slate-500">
                There are no eligible {{ sourceOptions.find(option => option.value === availableSource)?.label || 'orders' }} orders available for this trip. Refresh to check again.
              </p>
            </div>
          </template>
          <Column selectionMode="multiple" headerStyle="width: 3rem" />
          <Column header="Stop" style="width: 5rem">
            <template #body="{ data }">
              <span class="font-semibold text-violet-700">{{ data.stop_sequence ? `#${data.stop_sequence}` : '-' }}</span>
            </template>
          </Column>
          <Column field="order_number" header="Order #" />
          <Column field="customer_name" header="Customer" />
          <Column header="Preferred Date" style="width: 12rem"><template #body="{ data }">{{ formatDateTime(data.requested_fulfillment_date || data.delivery_date) }}</template></Column>
          <Column field="delivery_address" header="Address" />
          <Column header="Status">
            <template #body="{ data }">
              <Tag :value="formatStatusLabel(data.order_status)" severity="info" />
            </template>
          </Column>
          <Column header="Distance (km)" style="width: 8rem">
            <template #body="{ data }">
              <span class="text-xs text-slate-500">{{ Number(data.distance_km || 0).toFixed(2) }}</span>
            </template>
          </Column>
          <Column header="Weight (kg)" style="width: 7rem">
            <template #body="{ data }">
              <span class="text-xs text-slate-600">{{ Number(data.weight_kg || 0).toFixed(2) }}</span>
            </template>
          </Column>
          <Column header="View" style="width: 5rem">
            <template #body="{ data }">
              <Button icon="pi pi-eye" text rounded @click="openOrderDetail(data)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <div class="flex justify-end gap-3">
              <Button v-if="canManage && trip?.status === 'planned'" icon="pi pi-times" label="Cancel Trip" severity="secondary" rounded outlined :loading="savingStatus" @click="confirmCancelTrip" />
       <Button v-if="canManage && trip?.status === 'planned'" icon="pi pi-send" label="Dispatch Trip" rounded :loading="savingStatus" :disabled="!assignedOrders.length" @click="dispatchTrip" />

    </div>
  
    <Dialog v-model:visible="detailDialog" modal header="Order Details" class="w-full max-w-3xl">
      <div v-if="detailLoading" class="text-sm text-slate-500">Loading...</div>
      <div v-else-if="!detailOrder" class="text-sm text-slate-500">Order not found.</div>
      <div v-else class="space-y-4 text-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
          <div><span class="text-slate-500">Order #:</span> <strong>{{ detailOrder.order_number }}</strong></div>
          <div><span class="text-slate-500">Status:</span>
            <Tag :value="formatStatus(detailOrder.status)" :severity="statusSeverity(detailOrder.status)" />
          </div>
          <div><span class="text-slate-500">Customer:</span> <strong>{{ detailCustomerName }}</strong></div>
          <div><span class="text-slate-500">Contact:</span> <strong>{{ detailCustomerContact }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Address:</span> <strong>{{ detailAddress }}</strong>
          </div>
          <div class="md:col-span-2">
            <span class="text-slate-500">Map pin:</span>
            <strong v-if="detailCoordinates">{{ detailCoordinates.latitude.toFixed(6) }}, {{ detailCoordinates.longitude.toFixed(6) }}</strong>
            <span v-else class="text-slate-500">No saved map pin for this order.</span>
          </div>
          <div><span class="text-slate-500">Total:</span> <strong>{{ formatCurrency(detailOrder.total_amount) }}</strong>
          </div>
          <div><span class="text-slate-500">Source:</span> <strong>{{ detailSourceLabel }}</strong></div>
        </div>

        <div v-if="detailCoordinates" class="space-y-1">
          <div ref="detailMapElement" class="h-64 overflow-hidden rounded-xl border border-slate-200 bg-slate-100" aria-label="Order delivery location map" />
          <p v-if="detailMapError" class="text-xs text-amber-700">The map could not load. The saved pin coordinates are shown above.</p>
        </div>
  
        <div>
          <h4 class="text-sm font-semibold text-slate-700">Order Items</h4>
          <DataTable :value="detailOrder.items || []" dataKey="id" stripedRows class="mt-2">
            <Column header="Product">
              <template #body="{ data }">{{ data.product_name || data.product?.product_name || data.product?.name || '-' }}</template>
            </Column>
            <Column header="SKU" style="width: 8rem"><template #body="{ data }">{{ data.sku || data.product?.sku || '-' }}</template></Column>
            <Column header="Qty" style="width: 5rem"><template #body="{ data }">{{ data.quantity ?? data.shipped_quantity ?? data.approved_quantity ?? data.requested_quantity ?? '-' }}</template></Column>
            <Column header="Unit Price" style="width: 8rem">
              <template #body="{ data }">
                <span class="text-xs text-slate-600">{{ formatCurrency(data.unit_price) }}</span>
              </template>
            </Column>
          <Column v-if="detailSource !== 'stock_transfer'" header="Line Total" style="width: 8rem">
              <template #body="{ data }">
                <span class="text-xs text-slate-600">{{ formatCurrency(data.line_total) }}</span>
              </template>
            </Column>
          </DataTable>
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import logisticsService from '@/services/logistics.service'
import { openReceiptPreview } from '@/utils/receiptPreview'
import { useAuthStore } from '@/stores/auth'
import { requireMapboxToken } from '@/utils/mapbox'
import 'mapbox-gl/dist/mapbox-gl.css'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const canManage = authStore.hasPermission('logistics.deliveries.manage')
const canViewDriverTrips = authStore.hasPermission('logistics.driver.trips') || authStore.hasPermission('driver.trips.view')

const trip = ref<any>(null)
const loading = ref(false)
const savingStatus = ref(false)
const printingManifest = ref(false)
const editTripVisible = ref(false)
const loadingEditTripOptions = ref(false)
const savingTripEdit = ref(false)
const editTripDrivers = ref<any[]>([])
const editTripEmployees = ref<any[]>([])
const editTripVehicles = ref<any[]>([])
const editTripUnavailableDates = ref<Date[]>([])
const editTripForm = reactive({ driver_user_id: null as number | null, assistant_user_ids: [] as number[], vehicle_id: null as number | null, scheduled_departure_at: null as Date | null, notes: '' })
const editableAssistants = computed(() => editTripEmployees.value.filter(employee => Number(employee.id) !== Number(editTripForm.driver_user_id)))
watch(() => editTripForm.driver_user_id, driverId => {
  editTripForm.assistant_user_ids = editTripForm.assistant_user_ids.filter(id => Number(id) !== Number(driverId))
})
const tripReference = computed(() => trip.value ? `TRIP-${new Date(trip.value.created_at || Date.now()).getFullYear()}-${String(trip.value.id).padStart(6, '0')}` : 'Trip')

const availableOrders = ref<any[]>([])
const loadingAvailable = ref(false)
const loadingSuggestion = ref(false)
const suggestedArea = ref('')
const selectedOrders = ref<any[]>([])
const availableSource = ref<'ecommerce' | 'sales' | 'return_pickup' | 'stock_transfer' | 'replacement'>('ecommerce')
const availablePage = ref(1)
const availablePerPage = ref(10)
const availableTotal = ref(0)
const detailDialog = ref(false)
const detailLoading = ref(false)
const detailOrder = ref<any>(null)
const detailSource = ref<'ecommerce' | 'sales' | 'pickup' | 'stock_transfer'>('ecommerce')
const detailMapElement = ref<HTMLElement | null>(null)
const detailMapError = ref(false)
let detailMap: any = null
let detailMapRenderId = 0

const sourceOptions = [
  { label: 'Ecommerce', value: 'ecommerce' },
  { label: 'Sales', value: 'sales' },
  { label: 'Return Pickup', value: 'return_pickup' },
  { label: 'Stock Transfer', value: 'stock_transfer' },
  { label: 'Replacement Delivery', value: 'replacement' },
]

const assignedOrders = computed(() => {
  if (!trip.value) return []
  const ecommerce = (trip.value.ecommerce_deliveries || []).map((d: any) => ({
    id: `e-${d.id}`,
    source: 'Ecommerce',
    order_number: d.order?.order_number,
    customer_name: d.order?.shipping_name,
    address: d.order?.shipping_address,
    delivery_date: d.estimated_delivery_at || d.order?.requested_fulfillment_date,
    requested_fulfillment_date: d.order?.requested_fulfillment_date,
    status: d.status,
    weight_kg: calcWeight(d.order?.items || []),
    order_id: d.order?.id,
    is_return: false,
  }))
  const sales = (trip.value.sales_deliveries || []).map((d: any) => ({
    id: `s-${d.id}`,
    source: 'Sales',
    order_number: d.order?.order_number,
    customer_name: d.order?.customer_name,
    address: d.order?.delivery_address,
    delivery_date: d.scheduled_delivery_at,
    status: d.status,
    weight_kg: calcWeight(d.order?.items || []),
    order_id: d.order?.id,
    is_return: false,
  }))
  const returns = (trip.value.return_pickups || []).map((p: any) => ({
    id: `r-${p.id}`,
    source: 'Return Pickup',
    order_number: p.return_request?.return_number || '-',
    customer_name: p.pickup_name,
    address: p.pickup_address,
    delivery_date: p.scheduled_at,
    status: p.status,
    weight_kg: calcReturnWeight(p.return_request),
    order_id: p.id,
    is_return: true,
  }))
  const transfers = (trip.value.stock_transfers || []).map((transfer: any) => ({
    id: `t-${transfer.id}`,
    source: 'Stock Transfer',
    order_number: transfer.transfer_number,
    customer_name: `${transfer.from_branch?.name || 'Source branch'} to ${transfer.to_branch?.name || 'Destination branch'}`,
    address: [transfer.to_branch?.address, transfer.to_branch?.city, transfer.to_branch?.province].filter(Boolean).join(', '),
    delivery_date: transfer.expected_delivery_date,
    status: transfer.delivery_status || 'assigned',
    weight_kg: (transfer.items || []).reduce((total: number, item: any) => total + unitWeight(item) * Number(item.shipped_quantity ?? item.approved_quantity ?? item.requested_quantity ?? 0), 0),
    order_id: transfer.id,
    is_return: false,
  }))
  const replacements = (trip.value.replacement_returns || []).map((replacement: any) => {
    const order = replacement.source_type === 'pos' ? replacement.sales_order : replacement.order
    return {
      id: `replacement-${replacement.id}`,
      source: 'Replacement Delivery',
      order_number: replacement.return_number || '-',
      customer_name: replacement.source_type === 'pos' ? (replacement.return_contact_name || order?.customer_name) : (order?.shipping_name || replacement.return_contact_name),
      address: replacement.source_type === 'pos' ? order?.delivery_address : order?.shipping_address,
      delivery_date: trip.value.scheduled_departure_at,
      status: replacement.replacement_status,
      weight_kg: calcReturnWeight(replacement),
      order_id: replacement.id,
      is_return: false,
    }
  })
  return [...ecommerce, ...sales, ...returns, ...transfers, ...replacements]
})

const selectedOrderIds = computed(() => selectedOrders.value.map(o => o.order_id))

const driverName = computed(() => trip.value?.driver ? `${trip.value.driver.fname} ${trip.value.driver.lname}` : '-')
const vehicleLabel = computed(() => trip.value?.vehicle ? `${trip.value.vehicle.vehicle_name} (${trip.value.vehicle.plate_number})` : '-')
const assignedCargoWeight = computed(() => assignedOrders.value.reduce((sum, row) => {
  const status = String(row.status || '').toLowerCase()
  if (['delivered', 'cancelled'].includes(status)) return sum
  if (row.is_return && !['picked_up', 'out_for_delivery'].includes(status)) return sum
  return sum + Number(row.weight_kg || 0)
}, 0))
const tripTotalWeight = computed(() => trip.value?.current_weight_kg !== null && trip.value?.current_weight_kg !== undefined
  ? Number(trip.value.current_weight_kg)
  : assignedCargoWeight.value)
const vehicleCapacity = computed(() => Number(trip.value?.vehicle?.capacity_kg || 0))
const vehicleStopLimit = computed(() => Number(trip.value?.vehicle?.max_orders_per_trip || 0))
const selectedWeightKg = computed(() => selectedOrders.value.reduce((sum, row) => sum + Number(row.weight_kg || 0), 0))
const capacityRemaining = computed(() => {
  if (!vehicleCapacity.value) return 0
  return Math.max(0, vehicleCapacity.value - tripTotalWeight.value)
})
const willExceedCapacity = computed(() => vehicleCapacity.value > 0 && (availableSource.value === 'return_pickup'
  ? selectedWeightKg.value > vehicleCapacity.value
  : (selectedWeightKg.value + tripTotalWeight.value) > vehicleCapacity.value))
const capacityLabel = computed(() => {
  if (!vehicleCapacity.value) return `${tripTotalWeight.value.toFixed(2)} kg / No capacity set`
  return `${tripTotalWeight.value.toFixed(2)} kg / ${vehicleCapacity.value.toFixed(2)} kg`
})
const capacityProgressPercent = computed(() => vehicleCapacity.value > 0
  ? Math.min(100, (tripTotalWeight.value / vehicleCapacity.value) * 100)
  : 0)
const capacityWarning = computed(() => vehicleCapacity.value > 0 && tripTotalWeight.value > vehicleCapacity.value)
const batchSuggestion = computed(() => {
  const remainingWeight = vehicleCapacity.value > 0 ? Math.max(0, vehicleCapacity.value - tripTotalWeight.value) : Number.POSITIVE_INFINITY
  const remainingStops = vehicleStopLimit.value > 0
    ? Math.max(0, vehicleStopLimit.value - assignedOrders.value.length)
    : Number.POSITIVE_INFINITY
  let weight = 0
  const picked: any[] = []
  for (const row of [...availableOrders.value].sort((a, b) => Number(a.distance_km || 0) - Number(b.distance_km || 0))) {
    if (picked.length >= remainingStops) break
    const rowWeight = Number(row.weight_kg || 0)
    if (weight + rowWeight > remainingWeight) continue
    picked.push(row)
    weight += rowWeight
  }
  return picked
})

const suggestBatch = async () => {
  if (!trip.value) return
  loadingSuggestion.value = true
  try {
    const res = await logisticsService.getTripSuggestions(trip.value.id, availableSource.value)
    const rows = res?.data?.orders || []
    suggestedArea.value = res?.data?.area || ''
    availableOrders.value = rows
    availableTotal.value = rows.length
    availablePage.value = 1
    selectedOrders.value = [...rows]
    toast.add({
      severity: rows.length ? 'info' : 'warn',
      summary: rows.length ? 'Batch Suggested' : 'No Eligible Batch',
      detail: rows.length ? `${rows.length} order(s) selected for ${suggestedArea.value}.` : 'No ready orders fit the same delivery area and vehicle limits.',
      life: 3500,
    })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Suggestion Failed', detail: error?.response?.data?.message || 'Failed to suggest a delivery batch.', life: 3500 })
  } finally {
    loadingSuggestion.value = false
  }
}

const loadTrip = async () => {
  loading.value = true
  try {
    const res = await logisticsService.getTrip(String(route.params.id))
    trip.value = res?.data || null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load trip.', life: 3000 })
  } finally {
    loading.value = false
  }
}

const openEditTrip = async () => {
  if (!trip.value || trip.value.status !== 'planned' || !canManage) return
  editTripForm.driver_user_id = Number(trip.value.driver_user_id || trip.value.driver?.id) || null
  editTripForm.assistant_user_ids = (trip.value.assistant_user_ids || []).map((id: any) => Number(id))
  editTripForm.vehicle_id = Number(trip.value.vehicle_id || trip.value.vehicle?.id) || null
  editTripForm.scheduled_departure_at = trip.value.scheduled_departure_at ? new Date(trip.value.scheduled_departure_at) : null
  editTripForm.notes = trip.value.notes || ''
  editTripVisible.value = true
  void loadEditTripUnavailableDates(editTripForm.vehicle_id)
  if (editTripDrivers.value.length && editTripVehicles.value.length) return
  loadingEditTripOptions.value = true
  try {
    const [employeeResponse, vehicleResponse] = await Promise.all([
      logisticsService.getLogisticsEmployees(),
      logisticsService.getVehicles({ per_page: 100, status: 'active' }),
    ])
    editTripDrivers.value = employeeResponse?.data?.drivers || []
    editTripEmployees.value = employeeResponse?.data?.employees || []
    editTripVehicles.value = (vehicleResponse?.data?.data || []).map((vehicle: any) => ({ ...vehicle, label: `${vehicle.vehicle_name} (${vehicle.plate_number})` }))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Options Failed', detail: error?.response?.data?.message || 'Unable to load employees and vehicles.', life: 3500 })
  } finally {
    loadingEditTripOptions.value = false
  }
}

watch(() => editTripForm.vehicle_id, vehicleId => {
  if (editTripVisible.value) void loadEditTripUnavailableDates(vehicleId)
})

const loadEditTripUnavailableDates = async (vehicleId: number | null) => {
  editTripUnavailableDates.value = []
  if (!vehicleId || !trip.value?.id) return
  try {
    const response = await logisticsService.getVehicleUnavailableDepartureDates(vehicleId, trip.value.id)
    editTripUnavailableDates.value = (response?.data || []).map((value: string) => new Date(`${value}T00:00:00`))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Schedule Check Failed', detail: error?.response?.data?.message || 'Unable to check this vehicle’s planned trips.', life: 3500 })
  }
}

const isEditTripDateUnavailable = (date: any) => editTripUnavailableDates.value.some(item =>
  item.getFullYear() === date.year && item.getMonth() === date.month && item.getDate() === date.day,
)

const saveTripEdit = async () => {
  if (!trip.value || trip.value.status !== 'planned' || !editTripForm.driver_user_id || !editTripForm.vehicle_id || savingTripEdit.value) return
  if (editTripForm.scheduled_departure_at && isEditTripDateUnavailable({
    year: editTripForm.scheduled_departure_at.getFullYear(),
    month: editTripForm.scheduled_departure_at.getMonth(),
    day: editTripForm.scheduled_departure_at.getDate(),
  })) {
    toast.add({ severity: 'warn', summary: 'Vehicle Already Booked', detail: 'Choose another vehicle or departure date.', life: 3000 })
    return
  }
  const earlyOrders = ecommerceOrdersBeforePreferredDate(assignedOrders.value, editTripForm.scheduled_departure_at)
  if (earlyOrders.length) {
    confirm.require({
      header: 'Trip departs before preferred delivery date',
      message: earlyDeliveryWarning(earlyOrders),
      icon: 'pi pi-exclamation-triangle',
      acceptLabel: 'Customer Confirmed, Continue',
      rejectLabel: 'Review Date',
      accept: () => { void submitTripEdit() },
    })
    return
  }
  await submitTripEdit()
}

const submitTripEdit = async () => {
  if (!trip.value || trip.value.status !== 'planned' || !editTripForm.driver_user_id || !editTripForm.vehicle_id || savingTripEdit.value) return
  savingTripEdit.value = true
  try {
    await logisticsService.updateTrip(trip.value.id, {
      driver_user_id: editTripForm.driver_user_id,
      assistant_user_ids: editTripForm.assistant_user_ids,
      vehicle_id: editTripForm.vehicle_id,
      scheduled_departure_at: toDateOnly(editTripForm.scheduled_departure_at),
      notes: editTripForm.notes || null,
    })
    editTripVisible.value = false
    toast.add({ severity: 'success', summary: 'Trip Updated', detail: 'Planned trip details were saved.', life: 2500 })
    await loadTrip()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Update Failed', detail: error?.response?.data?.message || 'Unable to update the trip.', life: 3500 })
  } finally {
    savingTripEdit.value = false
  }
}

const changeStatus = async (status: 'out_for_delivery' | 'completed' | 'cancelled') => {
  if (!trip.value || savingStatus.value) return
  savingStatus.value = true
  try {
    await logisticsService.updateTripStatus(trip.value.id, { status })
    toast.add({ severity: 'success', summary: 'Updated', detail: `Trip is now ${formatStatus(status)}.`, life: 2500 })
    await loadTrip()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Update Failed', detail: error?.response?.data?.message || 'Failed to update status.', life: 3000 })
  } finally {
    savingStatus.value = false
  }
}

const loadAvailableOrders = async () => {
  loadingAvailable.value = true
  try {
    const res = await logisticsService.getDeliveryOrders({
      source: availableSource.value,
      status: 'ready_for_dispatch',
      per_page: availablePerPage.value,
      page: availablePage.value,
    })
    const rows = res?.data?.data || []
    availableTotal.value = Number(res?.data?.total || rows.length)
    availableOrders.value = rows
      .map((row: any) => ({
        ...row,
        weight_kg: Number(row.weight_kg || 0),
        distance_km: estimateDistanceKm(row.origin_latitude, row.origin_longitude, row.destination_latitude, row.destination_longitude),
        order_status: 'ready_for_dispatch',
      }))
      .sort((a: any, b: any) => Number(a.distance_km || 0) - Number(b.distance_km || 0))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load orders.', life: 3000 })
  } finally {
    loadingAvailable.value = false
  }
}

const addSelected = async () => {
  if (!trip.value || !selectedOrderIds.value.length) return
  if (willExceedCapacity.value) {
    toast.add({ severity: 'warn', summary: 'Capacity Exceeded', detail: 'Selected orders exceed the vehicle capacity.', life: 3000 })
    return
  }
  if (availableSource.value === 'ecommerce') {
    const earlyOrders = ecommerceOrdersBeforePreferredDate(selectedOrders.value, trip.value.scheduled_departure_at)
    if (earlyOrders.length) {
      confirm.require({
        header: 'Trip departs before preferred delivery date',
        message: earlyDeliveryWarning(earlyOrders),
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Customer Confirmed, Continue',
        rejectLabel: 'Review Date',
        accept: () => { void submitSelectedOrders() },
      })
      return
    }
  }
  await submitSelectedOrders()
}

const submitSelectedOrders = async () => {
  if (!trip.value || !selectedOrderIds.value.length) return
  try {
    const res = await logisticsService.addOrdersToTrip(trip.value.id, {
      source_type: availableSource.value,
      order_ids: selectedOrderIds.value,
    })
    const added = Number(res?.data?.added || 0)
    const overCapacity = Number(res?.data?.over_capacity || 0)
    const message = res?.message || 'Orders added to trip.'
    toast.add({
      severity: overCapacity > 0 ? 'warn' : 'success',
      summary: overCapacity > 0 ? 'Partially Added' : 'Added',
      detail: message,
      life: 3000,
    })
    selectedOrders.value = []
    await Promise.all([loadTrip(), loadAvailableOrders()])
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Add Failed', detail: error?.response?.data?.message || 'Failed to add orders.', life: 3000 })
  }
}

const removeOrder = async (row: any) => {
  if (!trip.value || !row?.order_id) return
  confirm.require({
    message: row.source === 'Replacement Delivery'
      ? 'Remove this replacement from the trip? Its stock reservation will remain in place so it can be assigned to another trip.'
      : 'Remove this order from the trip? This will delete the delivery assignment and reset the order to For Dispatch.',
    header: 'Confirm Removal',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Remove',
    rejectLabel: 'Cancel',
    accept: async () => {
      try {
        await logisticsService.removeOrdersFromTrip(trip.value.id, {
          source_type: row.source === 'Sales' ? 'sales' : row.source === 'Return Pickup' ? 'return_pickup' : row.source === 'Stock Transfer' ? 'stock_transfer' : row.source === 'Replacement Delivery' ? 'replacement' : 'ecommerce',
          order_ids: [row.order_id],
        })
        toast.add({ severity: 'success', summary: 'Removed', detail: 'Order removed from trip.', life: 2000 })
        await loadTrip()
      } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Remove Failed', detail: error?.response?.data?.message || 'Failed to remove order.', life: 3000 })
      }
    },
  })
}

const handleAvailablePage = (event: any) => {
  availablePage.value = Math.floor(event.first / event.rows) + 1
  availablePerPage.value = event.rows
  loadAvailableOrders()
}

const manilaDateKey = (value: string | Date | null | undefined): string | null => {
  if (!value) return null
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return null
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'Asia/Manila',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
  }).formatToParts(date)
  const values = Object.fromEntries(parts.map(part => [part.type, part.value]))
  return `${values.year}-${values.month}-${values.day}`
}

const ecommerceOrdersBeforePreferredDate = (orders: any[], departure: string | Date | null | undefined) => {
  const departureDate = manilaDateKey(departure)
  if (!departureDate) return []
  return orders.filter((order: any) => {
    const preferredDate = manilaDateKey(order.requested_fulfillment_date || order.delivery_date || order.expected_pickup_date)
    return preferredDate && departureDate < preferredDate
  })
}

const earlyDeliveryWarning = (orders: any[]) => {
  const references = orders.map((order: any) => order.order_number || 'reference unavailable').join(', ')
  return `The trip is scheduled before the customer's preferred delivery date for ${references}. Confirm the customer agreed to an earlier delivery before continuing. Their preferred date will remain unchanged.`
}

const dispatchTrip = async () => {
  if (!trip.value || trip.value.status !== 'planned' || !assignedOrders.value.length) return
  confirm.require({
    header: 'Dispatch this trip?',
    message: 'The trip and its assigned deliveries will move to Out For Delivery. Continue?',
    acceptLabel: 'Dispatch Trip',
    rejectLabel: 'Keep Planned',
    accept: () => { void changeStatus('out_for_delivery') },
  })
}

const confirmCancelTrip = () => confirm.require({
  message: 'Cancel this planned trip?',
  header: 'Cancel Trip',
  icon: 'pi pi-exclamation-triangle',
  acceptLabel: 'Cancel Trip',
  rejectLabel: 'Keep Trip',
  accept: () => { void changeStatus('cancelled') },
})

const openOrderDetail = async (row: any) => {
  if (!row?.order_id) return
  if (availableSource.value === 'replacement') {
    router.push({ name: 'logistics.replacements.detail', params: { id: row.order_id } })
    return
  }
  detailDialog.value = true
  detailLoading.value = true
  detailSource.value = (availableSource.value === 'return_pickup' ? 'pickup' : availableSource.value) as typeof detailSource.value
  try {
    const res = await logisticsService.getDeliveryOrderDetail(detailSource.value, row.order_id)
    detailOrder.value = res?.data?.order || null
  } catch (error: any) {
    detailOrder.value = null
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load order.', life: 3000 })
  } finally {
    detailLoading.value = false
  }
}

const openAssignedOrderDetail = async (row: any) => {
  if (!row?.order_id) return
  if (row.source === 'Replacement Delivery') {
    router.push({ name: canManage ? 'logistics.replacements.detail' : 'driver.replacements.detail', params: { id: row.order_id } })
    return
  }
  if (row.source === 'Stock Transfer' && canManage) {
    router.push({ name: 'logistics.stock-transfers.detail', params: { id: row.order_id } })
    return
  }
  const source = row.source === 'Sales' ? 'sales' : row.source === 'Return Pickup' ? 'return_pickup' : row.source === 'Stock Transfer' ? 'stock_transfer' : 'ecommerce'
  router.push({ name: 'driver.deliveries.view', params: { source, orderId: row.order_id } })
}

const printTripManifest = async () => {
  if (!trip.value || printingManifest.value) return
  printingManifest.value = true
  try {
    await openReceiptPreview(() => logisticsService.getTripManifestPdf(trip.value.id))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to open trip manifest', detail: error?.response?.data?.message || error?.message || 'Please try again.', life: 4000 })
  } finally {
    printingManifest.value = false
  }
}

const estimateDistanceKm = (oLat?: number, oLng?: number, dLat?: number, dLng?: number) => {
  const lat1 = Number(oLat)
  const lon1 = Number(oLng)
  const lat2 = Number(dLat)
  const lon2 = Number(dLng)
  if (![lat1, lon1, lat2, lon2].every(Number.isFinite)) return 0
  const toRad = (v: number) => (v * Math.PI) / 180
  const R = 6371
  const dLatRad = toRad(lat2 - lat1)
  const dLonRad = toRad(lon2 - lon1)
  const a = Math.sin(dLatRad / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLonRad / 2) ** 2
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
  return R * c
}

const calcWeight = (items: any[]) => {
  return (items || []).reduce((sum, item) => {
    const w = unitWeight(item)
    return sum + w * Number(item?.quantity || 0)
  }, 0)
}

const unitWeight = (item: any) => Number(
  item?.branch_inventory?.variation?.weight_kg
  ?? item?.variation?.weight_kg
  ?? item?.product?.weight_kg
  ?? 0,
)

const calcReturnWeight = (returnRequest: any) => {
  if (returnRequest?.replacement_inventory) {
    const inventory = returnRequest.replacement_inventory
    const weight = Number(inventory?.variation?.weight_kg ?? inventory?.product?.weight_kg ?? 0)
    return weight * Number(returnRequest?.received_quantity || returnRequest?.requested_quantity || 0)
  }
  const item = returnRequest?.source_type === 'pos'
    ? (returnRequest?.sales_order_item || returnRequest?.salesOrderItem)
    : (returnRequest?.order_item || returnRequest?.orderItem)
  return unitWeight(item) * Number(returnRequest?.received_quantity || returnRequest?.requested_quantity || 0)
}

const goBack = () => {
  if (window.history.state?.back) router.back()
  else router.push({ name: 'logistics.trips' })
}

const formatStatus = (value?: string) => value === 'ready_for_dispatch' ? 'For Dispatch' : value ? value.replace(/_/g, ' ').replace(/\b\w/g, m => m.toUpperCase()) : '-'
const formatStatusLabel = (value?: string) => formatStatus(value || 'ready_for_dispatch')
const statusSeverity = (value?: string) => {
  const status = String(value || '').toLowerCase()
  if (['completed', 'delivered', 'picked_up'].includes(status)) return 'success'
  if (status === 'cancelled') return 'secondary'
  if (['failed', 'failed_delivery', 'delivery_failed'].includes(status)) return 'danger'
  if (['assigned', 'in_transit', 'out_for_delivery', 'dispatched'].includes(status)) return 'info'
  if (['planned', 'pending', 'ready_for_dispatch', 'scheduled'].includes(status)) return 'warn'
  return 'secondary'
}
const formatDateTime = (value?: string) => value ? new Date(value).toLocaleString('en-PH') : '-'
const formatDateOnly = (value?: string) => {
  if (!value) return '-'
  const raw = String(value)
  const dateOnly = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/)
  if (dateOnly) {
    const date = new Date(Date.UTC(Number(dateOnly[1]), Number(dateOnly[2]) - 1, Number(dateOnly[3])))
    return new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit', timeZone: 'UTC' }).format(date)
  }

  const date = new Date(raw)
  return Number.isNaN(date.getTime()) ? '-' : new Intl.DateTimeFormat('en-PH', { year: 'numeric', month: 'short', day: '2-digit', timeZone: 'Asia/Manila' }).format(date)
}
const toDateOnly = (value: Date | null) => value
  ? `${value.getFullYear()}-${String(value.getMonth() + 1).padStart(2, '0')}-${String(value.getDate()).padStart(2, '0')}`
  : null
const formatCurrency = (value?: number) => {
  const amount = Number(value || 0)
  return amount.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
}

const detailSourceLabel = computed(() => ({ sales: 'Sales', pickup: 'Return Pickup', stock_transfer: 'Stock Transfer' }[detailSource.value] || 'Ecommerce'))
const detailCustomerName = computed(() => {
  if (!detailOrder.value) return '-'
  if (detailSource.value === 'stock_transfer') return `${detailOrder.value.from_branch?.name || 'Source branch'} to ${detailOrder.value.to_branch?.name || 'Destination branch'}`
  return detailSource.value === 'sales'
    ? detailOrder.value.customer_name
    : detailOrder.value.shipping_name
})
const detailCustomerContact = computed(() => {
  if (!detailOrder.value) return '-'
  if (detailSource.value === 'stock_transfer') return detailOrder.value.to_branch?.contact_number || '-'
  return detailSource.value === 'sales'
    ? detailOrder.value.customer_phone
    : detailOrder.value.shipping_phone
})
const detailAddress = computed(() => {
  if (!detailOrder.value) return '-'
  if (detailSource.value === 'stock_transfer') return [detailOrder.value.to_branch?.address, detailOrder.value.to_branch?.city, detailOrder.value.to_branch?.province].filter(Boolean).join(', ')
  return detailSource.value === 'sales'
    ? detailOrder.value.delivery_address
    : detailOrder.value.shipping_address
})

const detailCoordinates = computed(() => {
  if (!detailOrder.value) return null
  const order = detailOrder.value
  const latitude = Number(detailSource.value === 'stock_transfer'
    ? order.to_branch?.latitude
    : detailSource.value === 'sales'
    ? order.delivery_latitude ?? order.customer_latitude ?? order.latitude
    : order.customer_latitude ?? order.delivery_latitude ?? order.latitude)
  const longitude = Number(detailSource.value === 'stock_transfer'
    ? order.to_branch?.longitude
    : detailSource.value === 'sales'
    ? order.delivery_longitude ?? order.customer_longitude ?? order.longitude
    : order.customer_longitude ?? order.delivery_longitude ?? order.longitude)
  return Number.isFinite(latitude) && Number.isFinite(longitude) && latitude !== 0 && longitude !== 0
    ? { latitude, longitude }
    : null
})

const destroyDetailMap = () => {
  detailMapRenderId++
  detailMap?.remove()
  detailMap = null
}

const renderDetailMap = async () => {
  const coordinates = detailCoordinates.value
  if (!detailDialog.value || !coordinates || !detailMapElement.value) return

  const renderId = ++detailMapRenderId
  detailMap?.remove()
  detailMap = null
  detailMapError.value = false
  detailMapElement.value.replaceChildren()

  try {
    const mapboxgl = (await import('mapbox-gl')).default
    mapboxgl.accessToken = requireMapboxToken()
    if (renderId !== detailMapRenderId || !detailMapElement.value) return

    const map = new mapboxgl.Map({
      container: detailMapElement.value,
      style: 'mapbox://styles/mapbox/streets-v12',
      center: [coordinates.longitude, coordinates.latitude],
      zoom: 14,
      attributionControl: true,
    })
    detailMap = map
    new mapboxgl.Marker({ color: '#f97316' })
      .setLngLat([coordinates.longitude, coordinates.latitude])
      .setPopup(new mapboxgl.Popup({ offset: 25 }).setText(detailAddress.value))
      .addTo(map)
    map.once('load', () => {
      if (detailMap === map) map.resize()
    })
    window.setTimeout(() => {
      if (detailMap === map) map.resize()
    }, 150)
  } catch {
    detailMapError.value = true
  }
}

watch([detailDialog, detailCoordinates], async ([visible, coordinates]) => {
  if (!visible || !coordinates) {
    destroyDetailMap()
    return
  }
  await nextTick()
  await renderDetailMap()
}, { flush: 'post' })

onMounted(async () => {
  await loadTrip()
  await loadAvailableOrders()
})

onBeforeUnmount(destroyDetailMap)

watch(availableSource, () => {
  availablePage.value = 1
  selectedOrders.value = []
  loadAvailableOrders()
})
</script>
