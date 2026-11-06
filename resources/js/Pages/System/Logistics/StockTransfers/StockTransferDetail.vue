<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
          <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">{{ detail?.transfer_number || 'Stock Transfer' }}</h1>
            <p class="mt-1 text-sm text-slate-500">Internal branch transfer</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Tag :value="statusLabel" :severity="statusSeverity(detail?.delivery_status || detail?.status)" />
          <Button
            v-if="canAddToTrip"
            icon="pi pi-send"
            label="Add to Trip"
            severity="warn"
            @click="openTripDialog"
          />
          <Button icon="pi pi-refresh" label="Refresh" outlined @click="loadDetail" />
        </div>
      </div>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Transfer Summary</template>
      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading transfer detail...</div>
        <div v-else-if="!detail" class="text-sm text-slate-500">No transfer data found.</div>
        <div v-else class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">From Branch:</span> <strong>{{ detail?.from_branch?.name || detail?.fromBranch?.name || '-' }}</strong></div>
          <div><span class="text-slate-500">To Branch:</span> <strong>{{ detail?.to_branch?.name || detail?.toBranch?.name || '-' }}</strong></div>
          <div><span class="text-slate-500">Requested Date:</span> <strong>{{ formatDate(detail?.requested_date || detail?.created_at) }}</strong></div>
          <div><span class="text-slate-500">Expected Delivery:</span> <strong>{{ formatDate(detail?.expected_delivery_date) }}</strong></div>
          <div><span class="text-slate-500">Driver:</span> <strong>{{ assignedDriverName }}</strong></div>
          <div><span class="text-slate-500">Trip:</span> <strong>{{ detail?.trip?.id ? tripReference(detail.trip) : 'Not assigned' }}</strong></div>
          <div><span class="text-slate-500">Vehicle:</span> <strong>{{ detail?.trip?.vehicle?.vehicle_name || detail?.vehicle_type || '-' }}</strong></div>
          <div><span class="text-slate-500">Tracking Number:</span> <strong>{{ detail?.tracking_number || '-' }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Reason:</span> <strong>{{ detail?.reason || '-' }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Notes:</span> <strong>{{ displayNotes || '-' }}</strong></div>
        </div>
      </template>
    </Card>

    <Card v-if="transferSteps.length > 1" class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #content>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="step in transferSteps" :key="step.key" class="flex items-start gap-3">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold" :class="step.active ? 'bg-emerald-500 text-white' : 'bg-slate-200 text-slate-500'">{{ step.index }}</span>
            <div>
              <p class="text-sm font-semibold" :class="step.active ? 'text-slate-900' : 'text-slate-500'">{{ step.label }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ step.date || 'Not completed' }}</p>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <div v-if="detail" class="grid gap-4 lg:grid-cols-2">
      <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
        <template #title><span class="flex items-center gap-2"><i class="pi pi-arrow-up-right text-blue-600"></i>Sending Branch</span></template>
        <template #content>
          <p class="text-lg font-semibold text-slate-900">{{ branchName(detail?.from_branch || detail?.fromBranch) }}</p>
          <p class="mt-2 text-sm text-slate-600">{{ branchAddress(detail?.from_branch || detail?.fromBranch) }}</p>
          <div class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
            <p><span class="text-slate-500">Branch code:</span> {{ (detail?.from_branch || detail?.fromBranch)?.branch_code || '-' }}</p>
            <p><span class="text-slate-500">Contact:</span> {{ (detail?.from_branch || detail?.fromBranch)?.contact_number || '-' }}</p>
          </div>
        </template>
      </Card>
      <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
        <template #title><span class="flex items-center gap-2"><i class="pi pi-map-marker text-emerald-600"></i>Receiving Branch</span></template>
        <template #content>
          <p class="text-lg font-semibold text-slate-900">{{ branchName(detail?.to_branch || detail?.toBranch) }}</p>
          <p class="mt-2 text-sm text-slate-600">{{ branchAddress(detail?.to_branch || detail?.toBranch) }}</p>
          <div class="mt-4 grid gap-2 text-sm sm:grid-cols-2">
            <p><span class="text-slate-500">Branch code:</span> {{ (detail?.to_branch || detail?.toBranch)?.branch_code || '-' }}</p>
            <p><span class="text-slate-500">Contact:</span> {{ (detail?.to_branch || detail?.toBranch)?.contact_number || '-' }}</p>
          </div>
        </template>
      </Card>
    </div>

    <div v-if="detail" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
      <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
        <template #title>Approvals & Transfer Details</template>
        <template #content>
          <div class="grid gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
            <div><p class="text-xs uppercase tracking-wide text-slate-500">Requested By</p><p class="mt-1 font-medium text-slate-900">{{ employeeName(detail?.requested_by || detail?.requestedBy) }}</p><p class="text-xs text-slate-500">{{ formatDate(detail?.requested_date) }}</p></div>
            <div><p class="text-xs uppercase tracking-wide text-slate-500">Sender Approval</p><p class="mt-1 font-medium text-slate-900">{{ employeeName(detail?.sender_approved_by || detail?.senderApprovedBy) }}</p><p class="text-xs text-slate-500">{{ formatDate(detail?.sender_approved_date) }}</p></div>
            <div><p class="text-xs uppercase tracking-wide text-slate-500">Receiver Acknowledgment</p><p class="mt-1 font-medium text-slate-900">{{ employeeName(detail?.receiver_acknowledged_by || detail?.receiverAcknowledgedBy) }}</p><p class="text-xs text-slate-500">{{ formatDate(detail?.receiver_acknowledged_date) }}</p></div>
            <div><p class="text-xs uppercase tracking-wide text-slate-500">Finance Approval</p><p class="mt-1 font-medium text-slate-900">{{ employeeName(detail?.finance_approved_by || detail?.financeApprovedBy) }}</p><p class="text-xs text-slate-500">{{ formatDate(detail?.finance_approved_date) }}</p></div>
            <div><p class="text-xs uppercase tracking-wide text-slate-500">Approval Policy</p><p class="mt-1 font-medium text-slate-900">{{ label(detail?.approval_policy_used) || '-' }}</p></div>
            <div><p class="text-xs uppercase tracking-wide text-slate-500">Cost Method / Distance</p><p class="mt-1 font-medium text-slate-900">{{ label(detail?.cost_method) || '-' }}<span v-if="detail?.distance_km"> · {{ formatDecimal(detail.distance_km) }} km</span></p></div>
            <div><p class="text-xs uppercase tracking-wide text-slate-500">Created</p><p class="mt-1 font-medium text-slate-900">{{ formatDate(detail?.created_at) }}</p></div>
          </div>
          <div v-if="detail?.rejection_reason" class="mt-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
            <p class="font-semibold">Rejection Reason</p><p class="mt-1">{{ detail.rejection_reason }}</p>
          </div>
        </template>
      </Card>
      <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
        <template #title>Cost Summary</template>
        <template #content>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between gap-3"><span class="text-slate-600">Goods value</span><span class="font-medium">{{ formatCurrency(detail?.goods_value) }}</span></div>
            <div class="flex justify-between gap-3"><span class="text-slate-600">Shipping fee</span><span class="font-medium">{{ formatCurrency(detail?.transfer_cost) }}</span></div>
            <div class="border-t border-slate-200 pt-3"><div class="flex justify-between gap-3 text-base"><span class="font-semibold text-slate-900">Total transfer value</span><span class="font-semibold text-slate-900">{{ formatCurrency(Number(detail?.goods_value || 0) + Number(detail?.transfer_cost || 0)) }}</span></div></div>
          </div>
          <p v-if="detail?.cost_calculation_notes" class="mt-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-600">{{ detail.cost_calculation_notes }}</p>
        </template>
      </Card>
    </div>

    <Card v-if="showShipmentOverview" class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>
        <div class="flex items-center gap-2">
          <i class="pi pi-truck text-emerald-600"></i>
          <span>Shipment Overview</span>
        </div>
      </template>
      <template #content>
        <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
          <div
            v-for="step in shipmentSteps"
            :key="step.key"
            class="flex items-center gap-3 rounded-xl border border-slate-200 px-3 py-2"
          >
            <span
              class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold"
              :class="step.active ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500'"
            >
              {{ step.index }}
            </span>
            <span class="text-sm font-medium" :class="step.active ? 'text-slate-900' : 'text-slate-500'">{{ step.label }}</span>
          </div>
        </div>

        <div class="text-sm text-slate-700">
          <div class="mb-2 flex flex-wrap gap-4">
            <span class="font-semibold">{{ detail?.driver_name || 'Driver not set' }}</span>
            <span>Truck/Van: {{ detail?.vehicle_type || '-' }}</span>
            <span>Tracking #: {{ detail?.tracking_number || '-' }}</span>
            <span>Contact: {{ detail?.driver_contact || '-' }}</span>
          </div>
          <div class="mb-3 grid gap-2 sm:grid-cols-2">
            <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs"><span class="text-slate-500">Shipped by:</span> {{ employeeName(detail?.shipped_by || detail?.shippedBy) }} · {{ formatDate(detail?.shipped_date) }}</p>
            <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs"><span class="text-slate-500">Received by:</span> {{ employeeName(detail?.received_by || detail?.receivedBy) }} · {{ formatDate(detail?.received_date) }}</p>
          </div>
          <div v-if="proofImages.length" class="mb-3 rounded-xl border border-slate-200 px-4 py-3">
            <div class="mb-2 text-sm font-semibold text-slate-800">Proof of Delivery</div>
            <div class="flex flex-wrap gap-2">
              <Button
                v-for="(img, idx) in proofImages"
                :key="`${img.url}-${idx}`"
                icon="pi pi-image"
                outlined
                size="small"
                :label="img.name || `Photo ${idx + 1}`"
                @click="openMedia(img.url)"
              />
            </div>
          </div>
          <div class="rounded-xl border border-slate-200">
            <div class="flex items-center justify-between gap-2 border-b border-slate-200 px-4 py-2">
              <div class="text-sm font-semibold text-slate-800">Delivery Logs</div>
              <Button
                v-if="canRecordShipmentLog"
                :icon="canMarkDelivered ? 'pi pi-check-circle' : 'pi pi-plus'"
                :label="canMarkDelivered ? 'Mark as Delivered' : 'Record a Log'"
                size="small"
                text
                @click="onShipmentActionClick"
              />
            </div>
            <div v-if="deliveryLogs.length" class="divide-y divide-slate-100">
              <div v-for="(log, idx) in deliveryLogs" :key="`${log.label}-${idx}`" class="px-4 py-3">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                  <span class="font-medium text-slate-900">{{ log.label }}</span>
                  <span class="rounded bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ log.time }}</span>
                </div>
                <p v-if="log.by" class="mt-1 text-xs text-slate-500">By {{ log.by }}</p>
              </div>
            </div>
            <div v-else class="px-4 py-4 text-sm text-slate-500">No delivery logs yet.</div>
          </div>
        </div>
      </template>
    </Card>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Line Items</template>
      <template #content>
        <DataTable :value="detail?.items || []" dataKey="id" stripedRows responsiveLayout="scroll">
          <template #empty>
            <div class="py-8 text-center text-slate-500">No transfer items found.</div>
          </template>

          <Column header="Product">
            <template #body="{ data }">
              <p class="font-medium text-slate-900">{{ data.product?.product_name || data.product?.name || '-' }}</p>
              <p class="mt-1 text-xs text-slate-500">SKU: {{ data.product?.sku || '-' }}</p>
            </template>
          </Column>
          <Column header="Variation">
            <template #body="{ data }">{{ data.variation?.name || data.variation?.variation_name || '-' }}</template>
          </Column>
          <Column header="Unit Value" class="text-right">
            <template #body="{ data }">{{ formatCurrency(data.unit_value) }}</template>
          </Column>
          <Column header="Requested Qty">
            <template #body="{ data }">
              {{ formatQuantity(data.requested_quantity) }}
            </template>
          </Column>
          <Column header="Approved Qty">
            <template #body="{ data }">
              {{ formatQuantity(data.approved_quantity) }}
            </template>
          </Column>
          <Column header="Shipped Qty">
            <template #body="{ data }">
              {{ formatQuantity(data.shipped_quantity) }}
            </template>
          </Column>
          <Column header="Received Qty">
            <template #body="{ data }">
              {{ formatQuantity(data.received_quantity) }}
            </template>
          </Column>
          <Column header="Damaged Qty">
            <template #body="{ data }"><span :class="Number(data.damaged_quantity || 0) > 0 ? 'font-semibold text-rose-600' : ''">{{ formatQuantity(data.damaged_quantity) }}</span></template>
          </Column>
          <Column header="Line Value" class="text-right">
            <template #body="{ data }">{{ formatCurrency(Number(data.unit_value || 0) * Number(data.shipped_quantity ?? data.approved_quantity ?? data.requested_quantity ?? 0)) }}</template>
          </Column>
          <Column field="notes" header="Notes" />
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="recordLogDialogVisible" modal header="Record Delivery Log" class="w-full max-w-xl">
      <div class="space-y-3">
        <Select
          v-model="recordLogEvent"
          :options="recordLogEventOptions"
          optionLabel="label"
          optionValue="value"
          fluid
          placeholder="Select log event"
        />
        <Textarea
          v-model="recordLogNotes"
          rows="3"
          fluid
          placeholder="Add notes (optional)"
        />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="recordLogDialogVisible = false" />
        <Button
          icon="pi pi-check"
          label="Save Log"
          :loading="savingLog"
          :disabled="!recordLogEvent"
          @click="saveShipmentLog"
        />
      </template>
    </Dialog>

    <Dialog v-model:visible="tripChoiceVisible" modal header="Add Stock Transfer to a Trip" class="w-full max-w-2xl">
      <div v-if="loadingCompatibleTrips" class="space-y-2">
        <Skeleton v-for="row in 3" :key="row" height="4.5rem" borderRadius="10px" />
      </div>
      <div v-else-if="compatibleTrips.length" class="space-y-3">
        <article v-for="trip in compatibleTrips" :key="trip.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
          <div>
            <p class="font-semibold text-slate-900">{{ tripReference(trip) }}</p>
            <p class="mt-1 text-xs text-slate-600">{{ trip.driver?.fname || trip.driver_name || 'Driver not set' }} · {{ trip.vehicle?.vehicle_name || trip.vehicle_name || 'Vehicle not set' }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ trip.scheduled_departure_at ? formatDate(trip.scheduled_departure_at) : 'No departure time set' }} · {{ trip.assigned_stops || 0 }} current stops</p>
          </div>
          <Button label="Add to Trip" size="small" severity="warn" :loading="assigningTripId === Number(trip.id)" :disabled="Boolean(assigningTripId)" @click="addTransferToTrip(trip.id)" />
        </article>
      </div>
      <Message v-else severity="info" :closable="false">No compatible planned trips are available. Create a trip for this transfer.</Message>
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
        <Button label="Create and Add Transfer" icon="pi pi-check" severity="warn" :loading="creatingTrip" :disabled="loadingTripOptions || !tripForm.driver_user_id || !tripForm.vehicle_id" @click="createTripAndAddTransfer" />
      </template>
    </Dialog>

    <Dialog v-model:visible="markDeliveredDialogVisible" modal header="Proof of Delivery" class="w-full max-w-2xl">
      <div class="space-y-3">
        <p class="text-sm text-slate-600">
          Attach at least one image proof of delivery before confirming. Photos become part of the delivery log.
        </p>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Received By <span class="text-rose-500">*</span></label>
          <InputText
            v-model="podReceivedBy"
            fluid
            placeholder="Name of person who received"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Notes (optional)</label>
          <Textarea
            v-model="markDeliveredNotes"
            rows="4"
            fluid
            placeholder="Describe any observations, gate codes, or special instructions"
          />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Attachments</label>
          <input
            type="file"
            accept="image/*"
            multiple
            class="block w-full text-sm text-slate-600"
            @change="onPodAttachmentChange"
          />
          <p class="mt-1 text-xs text-slate-500">Minimum 1 photo, up to 10MB each.</p>
        </div>
        <p v-if="podAttachmentNames" class="text-xs text-slate-500">{{ podAttachmentNames }}</p>
        <div v-if="podAttachmentPreviews.length" class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-3">
          <button
            v-for="(preview, idx) in podAttachmentPreviews"
            :key="`${preview.url}-${idx}`"
            type="button"
            class="overflow-hidden rounded-lg border border-slate-200 bg-slate-50"
            @click="openMedia(preview.url)"
          >
            <img :src="preview.url" :alt="preview.name || `Attachment ${idx + 1}`" class="h-24 w-full object-cover" />
            <div class="truncate px-2 py-1 text-left text-[11px] text-slate-600">{{ preview.name || `Attachment ${idx + 1}` }}</div>
          </button>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="markDeliveredDialogVisible = false" />
        <Button
          icon="pi pi-check-circle"
          label="Confirm Delivery"
          severity="success"
          :loading="markingDelivered"
          :disabled="!canConfirmDelivered"
          @click="markAsDelivered"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Skeleton from 'primevue/skeleton'
import Message from 'primevue/message'
import inventoryService from '../../../../services/inventory.service'
import logisticsService from '../../../../services/logistics.service'
import { useAuthStore } from '../../../../stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canManageTrips = authStore.hasPermission('logistics.deliveries.manage')

const loading = ref(false)
const detail = ref<any>(null)
const transferId = computed(() => Number(route.params.id || 0))
const tripChoiceVisible = ref(false)
const compatibleTrips = ref<any[]>([])
const loadingCompatibleTrips = ref(false)
const assigningTripId = ref<number | null>(null)
const createTripVisible = ref(false)
const loadingTripOptions = ref(false)
const creatingTrip = ref(false)
const tripDrivers = ref<any[]>([])
const tripVehicles = ref<any[]>([])
const tripForm = ref<any>({ driver_user_id: null, vehicle_id: null, scheduled_departure_at: null, notes: '' })
const recordLogDialogVisible = ref(false)
const recordLogEvent = ref<string | null>(null)
const recordLogNotes = ref('')
const savingLog = ref(false)
const markDeliveredDialogVisible = ref(false)
const markDeliveredNotes = ref('')
const markingDelivered = ref(false)
const podReceivedBy = ref('')
const podAttachments = ref<File[]>([])
const podAttachmentPreviewUrls = ref<string[]>([])
const recordLogEventOptions = [
  { label: 'Arrived at Location', value: 'arrived_at_location' },
  { label: 'Unloading Started', value: 'unloading_started' },
  { label: 'Unloading Completed', value: 'unloading_completed' },
  { label: 'Delivery Delay', value: 'delivery_delay' },
  { label: 'Delivery Issue', value: 'delivery_issue' },
  { label: 'Received by Branch', value: 'received_by_branch' },
  { label: 'Custom Note', value: 'custom_note' },
]

const loadDetail = async () => {
  if (!transferId.value) return
  loading.value = true
  try {
    const response = await inventoryService.getTransfer(transferId.value)
    detail.value = response?.data || null
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Load Failed',
      detail: error?.response?.data?.message || 'Failed to load stock transfer detail.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const statusLabel = computed(() => {
  const deliveryStatus = String(detail.value?.delivery_status || '').toLowerCase()
  const status = String(detail.value?.status || '').toLowerCase()
  const notes = String(detail.value?.notes || '').toLowerCase()
  const hasDelivery = !!detail.value?.driver_name || !!detail.value?.vehicle_type || !!detail.value?.tracking_number
  if (deliveryStatus === 'assigned') return 'Assigned to Trip'
  if (deliveryStatus === 'ready_for_dispatch') return 'For Dispatch'
  if (deliveryStatus) return label(deliveryStatus)
  if (status === 'in_transit' && notes.includes('sent to logistics') && !hasDelivery) return 'Logistics Processing'
  if (!status) return '-'
  return status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
})

const canAddToTrip = computed(() => canManageTrips
  && String(detail.value?.status || '').toLowerCase() === 'in_transit'
  && ['ready_for_dispatch', null, undefined].includes(detail.value?.delivery_status)
  && !detail.value?.trip_id)
const assignedDriverName = computed(() => {
  const driver = detail.value?.trip?.driver || detail.value?.driver
  const linkedName = [driver?.fname, driver?.lname].filter(Boolean).join(' ').trim()
  return linkedName || detail.value?.driver_name || '-'
})
const tripReference = (trip: any) => trip?.id
  ? `TRIP-${new Date(trip.created_at || Date.now()).getFullYear()}-${String(trip.id).padStart(6, '0')}`
  : 'Trip'

const showShipmentOverview = computed(() => {
  const status = String(detail.value?.status || '').toLowerCase()
  return !!detail.value?.delivery_status || ['out_for_delivery', 'received'].includes(status)
})
const canRecordShipmentLog = computed(() => {
  const status = String(detail.value?.delivery_status || detail.value?.status || '').toLowerCase()
  return ['assigned', 'in_transit', 'out_for_delivery', 'delivered', 'received'].includes(status)
})
const isReceived = computed(() => String(detail.value?.status || '').toLowerCase() === 'received')

const shipmentSteps = computed(() => {
  const status = String(detail.value?.delivery_status || '').toLowerCase()
  const created = ['assigned', 'in_transit', 'out_for_delivery', 'delivered'].includes(status)
  const inTransit = ['in_transit', 'out_for_delivery', 'delivered'].includes(status)
  const outForDelivery = ['out_for_delivery', 'delivered'].includes(status)
  const delivered = detail.value?.delivery_status === 'delivered'
  const received = String(detail.value?.status || '').toLowerCase() === 'received' || status === 'received'

  return [
    { key: 'created', label: 'Delivery Created', index: 1, active: created },
    { key: 'transit', label: 'In Transit', index: 2, active: inTransit },
    { key: 'out_for_delivery', label: 'Out for Delivery', index: 3, active: outForDelivery },
    { key: 'delivered', label: 'Driver Delivered', index: 4, active: delivered, date: formatDate(detail.value?.delivered_at) },
    { key: 'received', label: 'Received by Branch', index: 5, active: received, date: formatDate(detail.value?.received_date) },
  ]
})

const transferSteps = computed(() => {
  const transfer = detail.value
  if (!transfer) return []
  const status = String(transfer.status || '').toLowerCase()
  const deliveryStatus = String(transfer.delivery_status || '').toLowerCase()
  const senderApproved = !!transfer.sender_approved_date || !!transfer.sender_approved_by || !!transfer.senderApprovedBy
    || ['sender_approved', 'receiver_acknowledged', 'receiver_acknowledge', 'pending_finance_approval', 'finance_approved', 'approved', 'in_transit', 'out_for_delivery', 'received'].includes(status)
  const receiverAcknowledged = !!transfer.receiver_acknowledged_date || !!transfer.receiver_acknowledged_by || !!transfer.receiverAcknowledgedBy
    || ['receiver_acknowledged', 'receiver_acknowledge', 'pending_finance_approval', 'finance_approved', 'approved', 'in_transit', 'out_for_delivery', 'received'].includes(status)
  const financeApproved = !!transfer.finance_approved_date || !!transfer.finance_approved_by || !!transfer.financeApprovedBy
    || ['finance_approved', 'approved', 'in_transit', 'out_for_delivery', 'received'].includes(status)
  const shipped = !!transfer.shipped_date || ['in_transit', 'out_for_delivery', 'received'].includes(status) || ['in_transit', 'out_for_delivery', 'delivered'].includes(deliveryStatus)
  const received = !!transfer.received_date || status === 'received'

  return [
    { key: 'requested', label: 'Requested', index: 1, active: status !== 'draft', date: formatDate(transfer.requested_date || transfer.created_at) },
    { key: 'sender-approved', label: 'Sender Approved', index: 2, active: senderApproved, date: formatDate(transfer.sender_approved_date) },
    { key: 'receiver-acknowledged', label: 'Receiver Acknowledged', index: 3, active: receiverAcknowledged, date: formatDate(transfer.receiver_acknowledged_date) },
    { key: 'finance-approved', label: 'Finance Approved', index: 4, active: financeApproved, date: formatDate(transfer.finance_approved_date) },
    { key: 'shipped', label: 'In Transit', index: 5, active: shipped, date: formatDate(transfer.shipped_date) },
    { key: 'out-for-delivery', label: 'Out for Delivery', index: 6, active: status === 'out_for_delivery' || ['out_for_delivery', 'delivered'].includes(deliveryStatus), date: formatDate(transfer.out_for_delivery_at) },
    { key: 'received', label: 'Received', index: 7, active: received || deliveryStatus === 'delivered', date: formatDate(transfer.received_date || transfer.delivered_at) },
  ]
})

const displayNotes = computed(() => String(detail.value?.notes || '')
  .split('\n')
  .map((line) => line.trim())
  .filter((line) => line && !/^(LOG2?\||POD\|)/.test(line))
  .join('\n'))

const branchName = (branch: any) => branch?.name || branch?.branch_name || '-'
const branchAddress = (branch: any) => [branch?.address, branch?.barangay, branch?.city, branch?.province]
  .filter((part, index, parts) => !!part && parts.indexOf(part) === index)
  .join(', ') || 'No address provided'
const employeeName = (employee: any) => {
  if (!employee) return '-'
  const user = employee.user || employee.employee?.user || employee
  const name = [user.fname || user.first_name, user.lname || user.last_name].filter(Boolean).join(' ').trim()
  return name || employee.name || employee.full_name || '-'
}
const label = (value?: string | null) => String(value || '')
  .replace(/[_-]+/g, ' ')
  .replace(/\b\w/g, (letter) => letter.toUpperCase())
const formatCurrency = (value?: number | string | null) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
  minimumFractionDigits: 2,
}).format(Number(value || 0))
const formatQuantity = (value?: number | string | null) => value === null || value === undefined || value === ''
  ? '-'
  : new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number(value))
const formatDecimal = (value?: number | string | null) => value === null || value === undefined || value === ''
  ? '-'
  : new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number(value))

const parsedDeliveryLogs = computed(() => {
  const logs: Array<{ label: string; time: string; by?: string; eventKey?: string }> = []

  if (detail.value?.shipped_date) {
    logs.push({
      label: 'In Transit',
      time: formatDate(detail.value.shipped_date),
      by: detail.value?.driver_name || 'Logistics',
    })
  }

  if (detail.value?.received_date) {
    logs.unshift({
      label: 'Delivered',
      time: formatDate(detail.value.received_date),
      by: employeeName(detail.value?.received_by || detail.value?.receivedBy) === '-'
        ? 'Receiver'
        : employeeName(detail.value?.received_by || detail.value?.receivedBy),
    })
  }

  const eventLabelMap: Record<string, string> = {
    arrived_at_location: 'Arrived at Location',
    unloading_started: 'Unloading Started',
    unloading_completed: 'Unloading Completed',
    delivery_delay: 'Delivery Delay',
    delivery_issue: 'Delivery Issue',
    received_by_branch: 'Received by Branch',
    custom_note: 'Custom Note',
  }

  const noteLines = String(detail.value?.notes || '')
    .split('\n')
    .map((line) => line.trim())
    .filter((line) => line.startsWith('LOG2|') || line.startsWith('LOG|'))

  noteLines.forEach((line) => {
    if (line.startsWith('LOG2|')) {
      const [, ts, by, eventKey, notes] = line.split('|')
      const baseLabel = eventLabelMap[String(eventKey || '').trim()] || 'Delivery Log'
      const label = String(notes || '').trim() ? `${baseLabel} - ${String(notes).trim()}` : baseLabel
      logs.unshift({
        label,
        time: formatDate(ts || ''),
        by: by || 'Logistics',
        eventKey: String(eventKey || ''),
      })
      return
    }

    // Backward compatibility for old LOG format.
    const [, ts, by, msg] = line.split('|')
    logs.unshift({
      label: msg || 'Delivery Log',
      time: formatDate(ts || ''),
      by: by || 'Logistics',
    })
  })

  return logs
})

const deliveryLogs = computed(() => parsedDeliveryLogs.value.map(({ label, time, by }) => ({ label, time, by })))

const hasUnloadingCompleted = computed(() =>
  parsedDeliveryLogs.value.some((log) => {
    const key = String(log.eventKey || '').toLowerCase()
    const label = String(log.label || '').toLowerCase()
    return key === 'unloading_completed' || label.startsWith('unloading completed')
  })
)

const canMarkDelivered = computed(() => canRecordShipmentLog.value && hasUnloadingCompleted.value && !isReceived.value)
const podAttachmentNames = computed(() => podAttachments.value.map((file) => file.name).join(', '))
const canConfirmDelivered = computed(() => podReceivedBy.value.trim().length > 0 && podAttachments.value.length > 0 && !markingDelivered.value)
const podAttachmentPreviews = computed(() =>
  podAttachments.value.map((file, index) => ({
    name: file.name,
    url: podAttachmentPreviewUrls.value[index] || '',
  })).filter((item) => !!item.url)
)
const proofImages = computed(() => {
  const lines = String(detail.value?.notes || '')
    .split('\n')
    .map((line) => line.trim())
    .filter((line) => line.startsWith('POD|'))

  return lines
    .map((line) => {
      const [, url, name] = line.split('|')
      return {
        url: String(url || '').trim(),
        name: String(name || '').trim(),
      }
    })
    .filter((entry) => !!entry.url)
})

const openTripDialog = async () => {
  tripChoiceVisible.value = true
  loadingCompatibleTrips.value = true
  try {
    const response = await logisticsService.getCompatibleTripsForOrder('stock_transfer', transferId.value)
    compatibleTrips.value = response?.data || []
  } catch (error: any) {
    tripChoiceVisible.value = false
    toast.add({ severity: 'error', summary: 'Could Not Load Trips', detail: error?.response?.data?.message || 'Unable to load compatible trips.', life: 3500 })
  } finally {
    loadingCompatibleTrips.value = false
  }
}

const addTransferToTrip = async (tripId: number | string) => {
  if (assigningTripId.value) return
  assigningTripId.value = Number(tripId)
  try {
    const response = await logisticsService.addOrdersToTrip(tripId, {
      source_type: 'stock_transfer',
      order_ids: [transferId.value],
    })
    if (Number(response?.data?.added || 0) !== 1) throw new Error(response?.message || 'The transfer could not be added to this trip.')
    tripChoiceVisible.value = false
    toast.add({ severity: 'success', summary: 'Added to Trip', detail: 'The stock transfer was added to the selected trip.', life: 3000 })
    await loadDetail()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Assignment Failed', detail: error?.response?.data?.message || error?.message || 'Unable to add this transfer to the trip.', life: 4000 })
  } finally {
    assigningTripId.value = null
  }
}

const openCreateTrip = async () => {
  tripForm.value = { driver_user_id: null, vehicle_id: null, scheduled_departure_at: null, notes: '' }
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

const createTripAndAddTransfer = async () => {
  if (!tripForm.value.driver_user_id || !tripForm.value.vehicle_id || creatingTrip.value) return
  creatingTrip.value = true
  try {
    const response = await logisticsService.createTrip({
      driver_user_id: tripForm.value.driver_user_id,
      vehicle_id: tripForm.value.vehicle_id,
      scheduled_departure_at: tripForm.value.scheduled_departure_at ? new Date(tripForm.value.scheduled_departure_at).toISOString() : null,
      notes: tripForm.value.notes || null,
    })
    const tripId = Number(response?.data?.id)
    if (!tripId) throw new Error('Trip was created but no trip ID was returned.')
    createTripVisible.value = false
    await addTransferToTrip(tripId)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Create Failed', detail: error?.response?.data?.message || error?.message || 'Failed to create trip.', life: 4000 })
  } finally {
    creatingTrip.value = false
  }
}

const saveShipmentLog = async () => {
  if (!recordLogEvent.value) return
  savingLog.value = true
  try {
    await inventoryService.addTransferDeliveryLog(transferId.value, {
      event: recordLogEvent.value as any,
      notes: recordLogNotes.value.trim() || undefined,
    })
    toast.add({
      severity: 'success',
      summary: 'Logged',
      detail: 'Delivery log recorded.',
      life: 2200,
    })
    recordLogEvent.value = null
    recordLogNotes.value = ''
    recordLogDialogVisible.value = false
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Log Failed',
      detail: error?.response?.data?.message || 'Unable to record delivery log.',
      life: 3000,
    })
  } finally {
    savingLog.value = false
  }
}

const onShipmentActionClick = () => {
  if (canMarkDelivered.value) {
    markDeliveredDialogVisible.value = true
    return
  }
  recordLogDialogVisible.value = true
}

const onPodAttachmentChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  podAttachmentPreviewUrls.value.forEach((url) => URL.revokeObjectURL(url))
  podAttachmentPreviewUrls.value = files.map((file) => URL.createObjectURL(file))
  podAttachments.value = files
}

const openMedia = (url: string) => {
  if (!url) return
  window.open(url, '_blank')
}

const markAsDelivered = async () => {
  if (!detail.value?.items?.length) {
    toast.add({
      severity: 'error',
      summary: 'No Items',
      detail: 'No transfer items found to complete delivery.',
      life: 2800,
    })
    return
  }

  if (!podReceivedBy.value.trim()) {
    toast.add({
      severity: 'error',
      summary: 'Missing Receiver',
      detail: 'Please enter who received the stock transfer.',
      life: 2800,
    })
    return
  }

  if (!podAttachments.value.length) {
    toast.add({
      severity: 'error',
      summary: 'Missing Attachment',
      detail: 'Please attach at least one proof-of-delivery image.',
      life: 2800,
    })
    return
  }

  markingDelivered.value = true
  try {
    const items = detail.value.items.map((item: any) => ({
      id: Number(item.id),
      received_quantity: Number(item.shipped_quantity ?? item.approved_quantity ?? item.requested_quantity ?? 0),
      damaged_quantity: 0,
    }))

    const notesParts = [
      `Received By: ${podReceivedBy.value.trim()}`,
      markDeliveredNotes.value.trim() ? `Notes: ${markDeliveredNotes.value.trim()}` : null,
      podAttachments.value.length ? `Attachments: ${podAttachments.value.map((file) => file.name).join(', ')}` : null,
    ].filter(Boolean) as string[]

    const formData = new FormData()
    formData.append('notes', notesParts.join('\n'))
    formData.append('items_json', JSON.stringify(items))
    podAttachments.value.forEach((file) => {
      formData.append('photos[]', file)
    })

    await inventoryService.receiveTransfer(transferId.value, formData)

    toast.add({
      severity: 'success',
      summary: 'Delivered',
      detail: 'Stock transfer marked as delivered successfully.',
      life: 2500,
    })

    markDeliveredDialogVisible.value = false
    markDeliveredNotes.value = ''
    podReceivedBy.value = ''
    podAttachmentPreviewUrls.value.forEach((url) => URL.revokeObjectURL(url))
    podAttachmentPreviewUrls.value = []
    podAttachments.value = []
    await loadDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Deliver Failed',
      detail: error?.response?.data?.message || 'Unable to mark transfer as delivered.',
      life: 3200,
    })
  } finally {
    markingDelivered.value = false
  }
}

const statusSeverity = (status?: string) => {
  const s = String(status || '').toLowerCase()
  if (['received', 'delivered'].includes(s)) return 'success'
  if (['assigned', 'in_transit'].includes(s)) return 'info'
  if (['ready_for_dispatch', 'out_for_delivery'].includes(s)) return 'warn'
  if (s === 'receiver_acknowledged' || s === 'receiver_acknowledge') return 'warning'
  if (s === 'cancelled') return 'danger'
  return 'secondary'
}

const formatDate = (value?: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString('en-PH', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const goBack = () => router.push({ name: 'logistics.stock-transfers' })

onMounted(async () => {
  await loadDetail()
})
</script>
