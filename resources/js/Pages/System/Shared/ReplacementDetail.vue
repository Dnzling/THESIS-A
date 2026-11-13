<template>
  <div class="mx-auto space-y-4 px-4 py-6 text-sm sm:px-6">
    <header class="flex items-start justify-between gap-3">
      <div>
        <Button icon="pi pi-chevron-left" rounded aria-label="Go back" class="!h-10 !w-10 border border-slate-200 bg-white shadow-sm" @click="goBack" />
        <h1 class="mt-2 text-xl font-semibold text-slate-900">Replacement {{ item?.return_number || '' }}</h1>
        <p class="mt-1 text-xs text-slate-500">Return receipt, stock reservation, and customer delivery</p>
      </div>
      <Badge v-if="item" :value="label(item.replacement_status || item.status)" :severity="replacementSeverity(item.replacement_status || item.status)" />
    </header>

    <Card v-if="loading"><template #content><Skeleton height="12rem" /></template></Card>
    <div v-else-if="item" class="space-y-4">
      <Card class="border border-slate-200 shadow-sm"><template #content>
        <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
          <div><span class="text-xs text-slate-500">Customer</span><p class="font-medium text-slate-900">{{ item.order?.shipping_name || item.return_contact_name || item.sales_order?.customer_name || '-' }}</p></div>
          <div><span class="text-xs text-slate-500">Order</span><p class="font-medium text-slate-900">{{ item.order?.order_number || item.sales_order?.order_number || '-' }}</p></div>
          <div><span class="text-xs text-slate-500">Exact item</span><p class="font-medium text-slate-900">{{ sourceItem?.product_name || '-' }} · {{ sourceItem?.sku || '-' }}</p></div>
          <div v-if="item.replacement_inventory"><span class="text-xs text-slate-500">Replacement item</span><p class="font-medium text-slate-900">{{ item.replacement_inventory.variation?.variation_name ? `${item.replacement_inventory.product?.product_name} - ${item.replacement_inventory.variation.variation_name}` : item.replacement_inventory.product?.product_name || item.replacement_product?.product_name || sourceItem?.product_name || '-' }} <span v-if="item.replacement_inventory.product?.sku" class="text-slate-500">· {{ item.replacement_inventory.variation?.variation_sku || item.replacement_inventory.product.sku }}</span></p><p class="mt-0.5 text-xs text-slate-500">{{ replacementWeightText }}</p></div>
          <div><span class="text-xs text-slate-500">Quantity</span><p class="font-medium text-slate-900">{{ item.received_quantity || item.requested_quantity }}</p></div>
          <div><span class="text-xs text-slate-500">Returned to</span><p class="font-medium text-slate-900">{{ item.pickup?.destination_branch?.name || 'Not assigned' }}</p></div>
          <div><span class="text-xs text-slate-500">Replacement sourced from</span><p class="font-medium text-slate-900">{{ item.replacement_branch?.name || 'Not reserved' }}</p></div>
          <div v-if="Number(item.replacement_price_difference) > 0"><span class="text-xs text-slate-500">Upgrade payment</span><p class="font-medium text-slate-900">{{ money(item.replacement_price_difference) }} · {{ label(item.replacement_payment_status) }} ({{ label(item.replacement_payment_method) }})</p><p class="text-xs text-slate-500">Reference: {{ item.replacement_payment_reference || '-' }}</p></div>
          <div><span class="text-xs text-slate-500">Trip</span><p class="font-medium text-slate-900">{{ tripReference(item.replacement_trip) || 'Not assigned' }}</p></div>
          <div><span class="text-xs text-slate-500">Driver and vehicle</span><p class="font-medium text-slate-900">{{ item.replacement_trip?.driver ? `${item.replacement_trip.driver.fname} ${item.replacement_trip.driver.lname}` : '-' }} <span v-if="item.replacement_trip?.vehicle">· {{ item.replacement_trip.vehicle.vehicle_name }} ({{ item.replacement_trip.vehicle.plate_number || '-' }})</span></p></div>
          <div><span class="text-xs text-slate-500">Return pickup</span><p class="font-medium text-slate-900">{{ label(item.pickup?.status) }}</p></div>
          <div class="sm:col-span-2"><span class="text-xs text-slate-500">Replacement delivery address</span><p class="font-medium text-slate-900">{{ replacementAddress || '-' }}</p></div>
        </div>
      </template></Card>

      <Card v-if="item.status === 'approved' && item.pickup?.status === 'delivered' && !isLogistics">
        <template #title><span class="text-base">1. Receive returned item</span></template>
        <template #content>
          <p class="mb-3 text-xs text-slate-500">This unit goes into quarantine. It will not increase sellable stock or issue a replacement yet.</p>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-medium">Received quantity</label><InputNumber v-model="receiveForm.received_quantity" :min="1" :max="item.requested_quantity" size="small" fluid /></div>
            <div><label class="mb-1 block text-xs font-medium">Condition</label><Select v-model="receiveForm.condition" :options="conditions" optionLabel="label" optionValue="value" size="small" fluid /></div>
            <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium">Inspection notes</label><Textarea v-model="receiveForm.notes" rows="2" size="small" fluid /></div>
          </div>
          <Button label="Receive into quarantine" severity="warn" size="small" class="mt-3" :loading="saving" @click="receive" />
        </template>
      </Card>

      <Card v-if="['approved', 'received'].includes(item.status) && ['awaiting_stock', null].includes(item.replacement_status) && !isLogistics">
        <template #title><span class="text-base">2. Reserve replacement stock</span></template>
        <template #content>
          <p class="mb-3 text-xs text-slate-500">Reserve the same SKU and variant at the branch that fulfilled the order. The customer exchange can be dispatched before the returned item is inspected; stock is deducted when the trip is dispatched.</p>
          <Select v-model="selectedBranch" :options="branchOptions" optionLabel="label" optionValue="id" placeholder="Select fulfillment branch" size="small" fluid />
          <Button label="Reserve stock" severity="warn" size="small" class="mt-3" :disabled="!selectedBranch" :loading="saving" @click="reserve" />
        </template>
      </Card>

      <Card v-if="item.status === 'received' && ['awaiting_stock', null].includes(item.replacement_status) && !isLogistics">
        <template #title><span class="text-base">Offer a higher-value alternative</span></template>
        <template #content>
          <p class="mb-3 text-xs text-slate-600">If the original item is unavailable, select an in-stock alternative. Confirm the customer accepts it and verify collection of the displayed difference before recording payment and reserving the item. This records the collection in Finance; it does not initiate a payment.</p>
          <Message v-if="!alternatives.length" severity="warn" :closable="false">No higher-value alternative with enough available branch stock was found. Check that the product is active and has available inventory at an active branch.</Message>
          <Select v-else v-model="alternativeForm.inventory_id" :options="alternatives" optionLabel="label" optionValue="inventory_id" placeholder="Select an alternative item" size="small" fluid />
          <div v-if="selectedAlternative" class="mt-3 grid gap-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs sm:grid-cols-2 lg:grid-cols-4">
            <p>Original item paid: <strong>{{ money(selectedAlternative.original_paid_value) }}</strong></p>
            <p>Alternative total: <strong>{{ money(selectedAlternative.total_price) }}</strong></p>
            <p>Replacement weight: <strong>{{ selectedAlternative.total_weight_kg == null ? 'Not recorded' : `${selectedAlternative.total_weight_kg} kg` }}</strong></p>
            <p class="text-orange-700">Collect difference: <strong>{{ money(selectedAlternative.price_difference) }}</strong></p>
          </div>
          <label class="mt-3 flex items-start gap-2 text-xs text-slate-700"><input v-model="alternativeForm.customer_accepted" type="checkbox" class="mt-0.5 rounded border-slate-300 text-orange-600 focus:ring-orange-500" /><span>Customer accepts this alternative and the additional amount shown above.</span></label>
          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-medium">Payment method</label><Select v-model="alternativeForm.payment_method" :options="paymentMethods" optionLabel="label" optionValue="value" size="small" fluid /></div>
            <div v-if="alternativeForm.payment_method !== 'cash'"><label class="mb-1 block text-xs font-medium">Payment reference</label><InputText v-model="alternativeForm.payment_reference" size="small" fluid placeholder="Transaction/reference number" /></div>
          </div>
          <label class="mt-3 flex items-start gap-2 text-xs text-slate-700"><input v-model="alternativeForm.payment_verified" type="checkbox" class="mt-0.5 rounded border-slate-300 text-orange-600 focus:ring-orange-500" /><span>I have verified that the additional amount was collected.</span></label>
          <Button label="Confirm collection and reserve alternative" severity="warn" size="small" class="mt-3" :disabled="!selectedAlternative || !alternativeForm.customer_accepted || !alternativeForm.payment_verified || (alternativeForm.payment_method !== 'cash' && !alternativeForm.payment_reference.trim())" :loading="saving" @click="reserveAlternative" />
        </template>
      </Card>

      <Card v-if="item.replacement_status === 'reserved' && canManageTrips" class="border border-slate-200 shadow-sm">
        <template #title><span class="text-base">3. Assign replacement to a trip</span></template>
        <template #content>
          <p class="mb-3 text-xs text-slate-600">Add this replacement to a planned trip. Its return pickup will be linked automatically, so the driver can exchange both items at the customer in one stop.</p>
          <Button label="Assign to Trip" icon="pi pi-truck" severity="warn" size="small" :loading="loadingCompatibleTrips" @click="openTripDialog" />
        </template>
      </Card>

      <Card v-if="item.replacement_status === 'assigned' && canManageTrips" class="border border-slate-200 shadow-sm">
        <template #title><span class="text-base">4. Waiting for trip dispatch</span></template>
        <template #content>
          <p class="text-sm text-slate-600">The replacement will leave the branch when its assigned trip is dispatched. Reserved stock will be deducted at that point.</p>
          <p v-if="item.replacement_trip" class="mt-2 text-sm font-semibold text-slate-900">{{ tripReference(item.replacement_trip) }} · {{ item.replacement_trip.status ? label(item.replacement_trip.status) : 'Planned' }}</p>
        </template>
      </Card>

      <Card v-if="item.replacement_status === 'out_for_delivery' && isLogistics">
        <template #title><span class="text-base">5. Confirm customer delivery</span></template>
        <template #content>
          <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="mb-1 block text-xs font-medium">Received by</label><InputText v-model="receivedBy" size="small" fluid /></div>
            <div><label class="mb-1 block text-xs font-medium">Delivery proof photo</label><input type="file" accept="image/*" class="block w-full text-xs" @change="onProofSelected" /></div>
          </div>
          <Button label="Complete replacement" severity="warn" size="small" class="mt-3" :disabled="!receivedBy.trim() || !proof" :loading="saving" @click="deliver" />
          <Button label="Delivery failed" severity="danger" outlined size="small" class="ml-2 mt-3" @click="failureDialog = true" />
        </template>
      </Card>

      <Card v-if="item.replacement_status === 'delivery_failed'">
        <template #title><span class="text-base">Delivery needs reattempt</span></template>
        <template #content>
          <p class="text-xs text-slate-600">{{ item.replacement_failure_reason || 'Delivery was not completed.' }}</p>
          <Button v-if="!isDriver" label="Reattempt delivery" severity="warn" size="small" class="mt-3" :loading="saving" @click="act(`${endpoint}/reattempt`)" />
        </template>
      </Card>

      <Card v-if="item.status === 'replaced'"><template #content>
        <p class="font-semibold text-emerald-700">Replacement delivered and case closed.</p>
        <p class="mt-1 text-xs text-slate-500">Received by {{ item.replacement_received_by || '-' }} · {{ formatDate(item.replacement_delivered_at) }}</p>
        <a v-if="item.replacement_proof_path" :href="`/storage/${item.replacement_proof_path}`" target="_blank" rel="noopener" class="mt-2 inline-block text-xs text-orange-600 hover:underline">View delivery proof</a>
      </template></Card>
      <Card v-if="item.status === 'replaced' && !item.inventory_disposition && !isLogistics">
        <template #title><span class="text-base">Return quarantine disposition</span></template>
        <template #content>
          <p class="mb-3 text-xs text-slate-500">After separate quality review, release a good item to sellable stock or discard it. Damaged items cannot be released.</p>
          <Button v-if="item.product_condition === 'good'" label="Release to stock" severity="warn" size="small" :loading="saving" @click="dispose('resell')" />
          <Button label="Discard returned item" severity="danger" outlined size="small" class="ml-2" :loading="saving" @click="dispose('discard')" />
        </template>
      </Card>
    </div>
    <Card v-else><template #content><p class="text-sm text-slate-500">Replacement case could not be loaded.</p></template></Card>
    <ConfirmDialog />
    <Dialog v-model:visible="tripChoiceVisible" modal header="Add Replacement to a Trip" class="w-full max-w-2xl">
      <div v-if="loadingCompatibleTrips" class="space-y-2"><Skeleton v-for="row in 3" :key="row" height="4.5rem" /></div>
      <div v-else-if="compatibleTrips.length" class="max-h-[55vh] space-y-2 overflow-y-auto">
        <button v-for="trip in compatibleTrips" :key="trip.id" type="button" class="flex w-full items-center justify-between gap-4 rounded-xl border border-slate-200 p-3 text-left transition hover:border-orange-300 hover:bg-orange-50/40 disabled:opacity-60" :disabled="Boolean(assigningTripId)" @click="addReplacementToTrip(trip.id)">
          <div class="min-w-0"><p class="font-semibold text-slate-900">{{ trip.trip_reference }}</p><p class="mt-1 truncate text-xs text-slate-500">{{ trip.driver_name }} · {{ trip.vehicle_name }} · {{ formatDate(trip.scheduled_departure_at) }}</p><p class="mt-1 text-xs text-slate-500">{{ trip.assigned_stops }} stop(s) · {{ trip.current_weight_kg }} kg / {{ trip.vehicle_capacity_kg || 'No limit' }} kg</p></div>
          <Button label="Add" size="small" severity="warn" :loading="assigningTripId === Number(trip.id)" @click.stop="addReplacementToTrip(trip.id)" />
        </button>
      </div>
      <Message v-else severity="info" :closable="false">No compatible planned trip is available. Create a trip for this replacement delivery.</Message>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="tripChoiceVisible = false" />
        <Button label="Create New Trip" icon="pi pi-plus" severity="warn" :disabled="Boolean(assigningTripId)" @click="openCreateTrip" />
      </template>
    </Dialog>
    <Dialog v-model:visible="createTripVisible" modal header="Create Trip" class="w-full max-w-3xl">
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Driver</label>
          <Select v-model="tripForm.driver_user_id" :options="tripDrivers" optionLabel="name" optionValue="id" filter size="small" fluid placeholder="Select driver" :loading="loadingTripOptions" />
          <div v-if="selectedTripDriver" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex items-start gap-3"><div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-orange-100 text-sm font-semibold text-orange-700">{{ tripInitials(selectedTripDriver.name) }}</div><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-slate-900">{{ selectedTripDriver.name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ selectedTripDriver.role || 'Delivery Driver' }}<span v-if="selectedTripDriver.employee_number"> ({{ selectedTripDriver.employee_number }})</span></p></div></div>
            <div class="mt-3 grid gap-x-4 gap-y-2 text-xs sm:grid-cols-2"><div><span class="text-slate-500">Contact</span><p class="mt-0.5 break-all font-medium text-slate-800">{{ selectedTripDriver.contact || 'Not provided' }}</p></div><div><span class="text-slate-500">Email</span><p class="mt-0.5 break-all font-medium text-slate-800">{{ selectedTripDriver.email || 'Not provided' }}</p></div><div class="sm:col-span-2"><span class="text-slate-500">Branch</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedTripDriver.branch || 'No branch assigned' }}</p></div></div>
          </div>
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Vehicle</label>
          <Select v-model="tripForm.vehicle_id" :options="tripVehicles" optionLabel="label" optionValue="id" filter size="small" fluid placeholder="Select vehicle" :loading="loadingTripOptions" />
          <div v-if="selectedTripVehicle" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ selectedTripVehicle.vehicle_name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ selectedTripVehicle.vehicle_type || 'Vehicle' }}<span v-if="selectedTripVehicle.plate_number"> ({{ selectedTripVehicle.plate_number }})</span></p></div><Badge :value="selectedTripVehicle.status || 'Active'" :severity="selectedTripVehicle.status === 'active' ? 'success' : 'secondary'" /></div>
            <div class="mt-3 grid gap-x-4 gap-y-2 text-xs sm:grid-cols-2"><div><span class="text-slate-500">Make / model</span><p class="mt-0.5 font-medium text-slate-800">{{ [selectedTripVehicle.brand, selectedTripVehicle.model].filter(Boolean).join(' ') || 'Not provided' }}</p></div><div><span class="text-slate-500">Color</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedTripVehicle.color || 'Not provided' }}</p></div><div><span class="text-slate-500">Load capacity</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedTripVehicle.capacity_kg ? `${Number(selectedTripVehicle.capacity_kg).toLocaleString()} kg` : 'Not set' }}</p></div><div><span class="text-slate-500">Stops per trip</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedTripVehicle.max_orders_per_trip ?? 'Not set' }}</p></div><div class="sm:col-span-2"><span class="text-slate-500">Assigned branch</span><p class="mt-0.5 font-medium text-slate-800">{{ selectedTripVehicle.branch?.name || 'Branch not specified' }}</p></div><div v-if="selectedTripVehicle.notes" class="sm:col-span-2"><span class="text-slate-500">Vehicle notes</span><p class="mt-0.5 whitespace-pre-line text-slate-700">{{ selectedTripVehicle.notes }}</p></div></div>
          </div>
        </div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium">Employees Accompanying the Driver</label><MultiSelect v-model="tripForm.assistant_user_ids" :options="availableTripAssistants" optionLabel="name" optionValue="id" display="chip" filter size="small" fluid placeholder="Select employees (optional)" :loading="loadingTripOptions" /></div>
          <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium">Scheduled Departure</label><DatePicker v-model="tripForm.scheduled_departure_at" :minDate="new Date()" :disabledDates="unavailableDepartureDates" size="small" fluid dateFormat="M d, yy"><template #date="slotProps"><span :class="{ 'text-red-600 line-through': isDepartureUnavailable(slotProps.date) }">{{ slotProps.date.day }}</span></template></DatePicker><p class="mt-1 text-xs text-slate-500">Optional. Dates crossed out already have a planned trip for this vehicle.</p></div>
        <div class="sm:col-span-2"><label class="mb-1 block text-xs font-medium">Notes</label><Textarea v-model="tripForm.notes" rows="2" size="small" fluid placeholder="Optional notes" /></div>
      </div>
      <template #footer><Button label="Back" severity="secondary" outlined @click="createTripVisible = false" /><Button label="Create and Assign" severity="warn" :loading="creatingTrip" :disabled="loadingTripOptions || !tripForm.driver_user_id || !tripForm.vehicle_id" @click="createTripAndAddReplacement" /></template>
    </Dialog>
    <Dialog v-model:visible="failureDialog" header="Why did delivery fail?" modal class="w-full max-w-md">
      <Textarea v-model="failureReason" rows="3" size="small" fluid placeholder="Provide a reason for the next attempt" />
      <template #footer>
        <Button label="Cancel" text size="small" @click="failureDialog = false" />
        <Button label="Save failure" severity="danger" size="small" :disabled="!failureReason.trim()" :loading="saving" @click="markFailed" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import axios from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import DatePicker from 'primevue/datepicker'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Skeleton from 'primevue/skeleton'
import ConfirmDialog from 'primevue/confirmdialog'
import Dialog from 'primevue/dialog'
import Message from 'primevue/message'
import logisticsService from '@/services/logistics.service'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const isDriver = window.location.pathname.startsWith('/driver/')
const isLogistics = window.location.pathname.startsWith('/logistics/') || isDriver
const canManageTrips = authStore.hasPermission('logistics.deliveries.manage')
const item = ref<any>(null)
const branches = ref<any[]>([])
const stock = ref<any[]>([])
const alternatives = ref<any[]>([])
const alternativeForm = reactive({ inventory_id: null as number | null, customer_accepted: false, payment_verified: false, payment_method: 'cash', payment_reference: '' })
const paymentMethods = [{ label: 'Cash', value: 'cash' }, { label: 'Card', value: 'card' }, { label: 'GCash', value: 'gcash' }, { label: 'Bank transfer', value: 'bank_transfer' }]
const tripChoiceVisible = ref(false)
const compatibleTrips = ref<any[]>([])
const loadingCompatibleTrips = ref(false)
const assigningTripId = ref<number | null>(null)
const createTripVisible = ref(false)
const loadingTripOptions = ref(false)
const creatingTrip = ref(false)
const tripDrivers = ref<any[]>([])
const tripAssistants = ref<any[]>([])
const tripVehicles = ref<any[]>([])
const unavailableDepartureDates = ref<Date[]>([])
const tripForm = ref<any>({ driver_user_id: null, assistant_user_ids: [], vehicle_id: null, scheduled_departure_at: null, notes: '' })
const loading = ref(true)
const saving = ref(false)
const selectedBranch = ref<number | null>(null)
const receivedBy = ref('')
const proof = ref<File | null>(null)
const failureDialog = ref(false)
const failureReason = ref('')
const receiveForm = reactive({ received_quantity: 1, condition: 'good', notes: '' })
const conditions = [{ label: 'Good (quarantine)', value: 'good' }, { label: 'Damaged (quarantine)', value: 'bad' }]
const branchOptions = computed(() => branches.value.map(branch => {
  const available = Number(stock.value.find(row => Number(row.branch_id) === Number(branch.id))?.quantity_available || 0)
  return { id: branch.id, label: `${branch.name} (${branch.branch_type === 'warehouse' ? 'Warehouse' : 'Store'}) · ${available} available` }
}))
const selectedAlternative = computed(() => alternatives.value.find(option => Number(option.inventory_id) === Number(alternativeForm.inventory_id)) || null)
const sourceItem = computed(() => item.value?.source_type === 'pos' ? item.value?.sales_order_item : item.value?.order_item)
const replacementUnitWeight = computed(() => {
  const inventory = item.value?.replacement_inventory
  const weight = inventory?.variation?.weight_kg ?? inventory?.product?.weight_kg
  return weight === null || weight === undefined || weight === '' ? null : Number(weight)
})
const replacementTotalWeight = computed(() => replacementUnitWeight.value === null
  ? null
  : replacementUnitWeight.value * Number(item.value?.received_quantity || item.value?.requested_quantity || 1))
const replacementWeightText = computed(() => replacementUnitWeight.value === null
  ? 'Product weight is not recorded'
  : `${replacementUnitWeight.value} kg each · ${replacementTotalWeight.value} kg total`)
const availableTripAssistants = computed(() => tripAssistants.value.filter(employee => Number(employee.id) !== Number(tripForm.value.driver_user_id)))
const selectedTripDriver = computed(() => tripDrivers.value.find(driver => Number(driver.id) === Number(tripForm.value.driver_user_id)) || null)
const selectedTripVehicle = computed(() => tripVehicles.value.find(vehicle => Number(vehicle.id) === Number(tripForm.value.vehicle_id)) || null)
const tripInitials = (name?: string) => String(name || '?').trim().split(/\s+/).slice(0, 2).map(part => part[0]?.toUpperCase() || '').join('')
watch(() => tripForm.value.vehicle_id, async (vehicleId) => {
  unavailableDepartureDates.value = []
  if (!vehicleId) return
  try {
    const response = await logisticsService.getVehicleUnavailableDepartureDates(vehicleId)
    unavailableDepartureDates.value = (response?.data || []).map((date: string) => new Date(`${date}T00:00:00`))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Schedule Check Failed', detail: error?.response?.data?.message || 'Unable to check vehicle availability.', life: 3500 })
  }
})
const isDepartureUnavailable = (date: any) => unavailableDepartureDates.value.some(value =>
  value.getFullYear() === date.year && value.getMonth() === date.month && value.getDate() === date.day,
)
const label = (value: any) => String(value || 'Pending').replaceAll('_', ' ').replace(/\b\w/g, (char: string) => char.toUpperCase())
const formatDate = (value: any) => value ? new Date(value).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '-'
const money = (value: any) => `\u20B1${Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
const endpoint = `/api/logistics/replacements/${route.params.id}`
const replacementAddress = computed(() => item.value?.source_type === 'pos'
  ? item.value?.sales_order?.delivery_address || item.value?.order?.shipping_address
  : item.value?.order?.shipping_address)
const replacementSeverity = (status: any) => {
  const value = String(status || '').toLowerCase()
  if (['delivered', 'replaced', 'completed'].includes(value)) return 'success'
  if (['out_for_delivery', 'assigned', 'reserved'].includes(value)) return 'info'
  if (['delivery_failed', 'rejected'].includes(value)) return 'danger'
  if (['awaiting_stock', 'pending', 'approved', 'received'].includes(value)) return 'warn'
  return 'secondary'
}
const tripReference = (trip: any) => trip?.id
  ? `TRIP-${new Date(trip.created_at || Date.now()).getFullYear()}-${String(trip.id).padStart(6, '0')}`
  : ''

const load = async () => {
  loading.value = true
  try {
    const response = await axios.get(endpoint)
    item.value = response.data?.data || null
    branches.value = response.data?.branches || []
    stock.value = response.data?.stock || []
    alternatives.value = (response.data?.alternatives || []).map((option: any) => ({ ...option, label: `${option.product_name} · ${option.sku} · ${option.branch_name} · ${option.quantity_available} available · ${option.weight_kg == null ? 'Weight not recorded' : `${option.weight_kg} kg each`} · ${money(option.total_price)}` }))
    receiveForm.received_quantity = Number(item.value?.requested_quantity || 1)
    selectedBranch.value = item.value?.replacement_branch_id || null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load failed', detail: error?.response?.data?.message || 'Unable to load replacement.', life: 3500 })
  } finally { loading.value = false }
}

const act = async (url: string, payload?: any) => {
  saving.value = true
  try {
    const response = await axios.post(url, payload)
    toast.add({ severity: 'success', summary: 'Updated', detail: response.data?.message || 'Replacement updated.', life: 3000 })
    await load()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Update failed', detail: error?.response?.data?.message || 'Please try again.', life: 4000 })
  } finally { saving.value = false }
}
const receive = () => act(`/api/crm/returns/${route.params.id}/receive`, receiveForm)
const reserve = () => act(`${endpoint}/reserve`, { branch_id: selectedBranch.value })
const reserveAlternative = () => act(`${endpoint}/alternative`, { inventory_id: alternativeForm.inventory_id, customer_accepted: alternativeForm.customer_accepted, payment_verified: alternativeForm.payment_verified, payment_method: alternativeForm.payment_method, payment_reference: alternativeForm.payment_reference.trim() || null })
const openTripDialog = async () => {
  tripChoiceVisible.value = true
  loadingCompatibleTrips.value = true
  try {
    const response = await logisticsService.getCompatibleTripsForOrder('replacement', Number(route.params.id))
    compatibleTrips.value = response?.data || []
  } catch (error: any) {
    tripChoiceVisible.value = false
    toast.add({ severity: 'error', summary: 'Could Not Load Trips', detail: error?.response?.data?.message || 'Unable to load compatible trips.', life: 3500 })
  } finally { loadingCompatibleTrips.value = false }
}
const addReplacementToTrip = async (tripId: number | string) => {
  if (assigningTripId.value) return
  assigningTripId.value = Number(tripId)
  try {
    const response = await logisticsService.addOrdersToTrip(tripId, { source_type: 'replacement', order_ids: [Number(route.params.id)] })
    if (Number(response?.data?.added || 0) !== 1) throw new Error(response?.message || 'The replacement could not be added to the trip.')
    tripChoiceVisible.value = false
    createTripVisible.value = false
    toast.add({ severity: 'success', summary: 'Added to Trip', detail: 'The replacement delivery was assigned to the trip.', life: 3000 })
    await load()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Assignment Failed', detail: error?.response?.data?.message || error?.message || 'Unable to add this replacement to the trip.', life: 4000 })
  } finally { assigningTripId.value = null }
}
const openCreateTrip = async () => {
  tripForm.value = { driver_user_id: null, assistant_user_ids: [], vehicle_id: null, scheduled_departure_at: null, notes: '' }
  createTripVisible.value = true
  if (tripDrivers.value.length && tripVehicles.value.length) return
  loadingTripOptions.value = true
  try {
    const [employeeResponse, vehicleResponse] = await Promise.all([
      logisticsService.getLogisticsEmployees(),
      logisticsService.getVehicles({ per_page: 100, status: 'active' }),
    ])
    tripDrivers.value = employeeResponse?.data?.drivers || []
    tripAssistants.value = employeeResponse?.data?.employees || []
    tripVehicles.value = (vehicleResponse?.data?.data || []).map((vehicle: any) => ({ ...vehicle, label: `${vehicle.vehicle_name} (${vehicle.plate_number || '-'})` }))
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Options Failed', detail: error?.response?.data?.message || 'Unable to load drivers and vehicles.', life: 3500 })
  } finally { loadingTripOptions.value = false }
}
const createTripAndAddReplacement = async () => {
  if (!tripForm.value.driver_user_id || !tripForm.value.vehicle_id || creatingTrip.value) return
  creatingTrip.value = true
  try {
    const selectedDate = tripForm.value.scheduled_departure_at
    const scheduledDate = !selectedDate ? null : selectedDate instanceof Date
      ? `${selectedDate.getFullYear()}-${String(selectedDate.getMonth() + 1).padStart(2, '0')}-${String(selectedDate.getDate()).padStart(2, '0')}`
      : String(selectedDate).slice(0, 10)
    const response = await logisticsService.createTrip({
      driver_user_id: tripForm.value.driver_user_id,
      assistant_user_ids: tripForm.value.assistant_user_ids,
      vehicle_id: tripForm.value.vehicle_id,
      scheduled_departure_at: scheduledDate,
      notes: tripForm.value.notes || null,
    })
    const tripId = Number(response?.data?.id)
    if (!tripId) throw new Error('Trip was created but no trip reference was returned.')
    await addReplacementToTrip(tripId)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Create Failed', detail: error?.response?.data?.message || error?.message || 'Unable to create a trip.', life: 4000 })
  } finally { creatingTrip.value = false }
}
const onProofSelected = (event: Event) => { proof.value = (event.target as HTMLInputElement).files?.[0] || null }
const deliver = () => {
  const data = new FormData()
  data.append('received_by', receivedBy.value.trim())
  if (proof.value) data.append('proof', proof.value)
  act(`${endpoint}/deliver`, data)
}
const markFailed = async () => {
  await act(`${endpoint}/fail`, { reason: failureReason.value.trim() })
  if (item.value?.replacement_status === 'delivery_failed') {
    failureDialog.value = false
    failureReason.value = ''
  }
}
const dispose = (disposition: 'resell' | 'discard') => confirm.require({
  header: disposition === 'resell' ? 'Release returned item?' : 'Discard returned item?',
  message: disposition === 'resell' ? 'This will add the quarantined unit to sellable stock.' : 'This will remove the unit from quarantine without adding sellable stock.',
  rejectProps: { label: 'Cancel', outlined: true }, acceptProps: { label: 'Confirm', severity: 'warn' },
  accept: () => act(`${endpoint}/disposition`, { disposition }),
})
const goBack = () => router.push({ name: isDriver ? 'logistics.trips' : isLogistics ? 'logistics.deliveries' : window.location.pathname.startsWith('/warehouse/') ? 'warehouse.returns' : 'inventory.stock-returns' })
onMounted(load)
</script>




