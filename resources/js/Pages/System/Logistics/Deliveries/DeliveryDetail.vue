<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-slate-200/80 bg-linear-to-br from-indigo-50 via-white to-sky-50 p-6 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
          <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Delivery Detail</h1>
            <p class="mt-1 text-sm text-slate-600">{{ sourceLabel }} • {{ order?.order_number || '-' }}</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <Button icon="pi pi-refresh" label="Refresh" outlined @click="loadAll" />
          <Button
            v-if="canAssignDelivery"
            icon="pi pi-send"
            label="Assign Delivery"
            severity="success"
            @click="openAssign"
          />
        </div>
      </div>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Order & Delivery Snapshot</template>
      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading details...</div>
        <div v-else-if="!order" class="text-sm text-slate-500">No order data found.</div>
        <div v-else class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">Customer:</span> <strong>{{ customerName }}</strong></div>
          <div><span class="text-slate-500">Contact:</span> <strong>{{ customerContact }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Address:</span> <strong>{{ deliveryAddress }}</strong></div>
          <div><span class="text-slate-500">Order Status:</span> <Tag :value="formatStatus(order?.status)" severity="secondary" /></div>
          <div>
            <span class="text-slate-500">Delivery Status:</span>
            <Tag :value="formatStatus(delivery?.status || 'pending')" :severity="deliverySeverity(delivery?.status || 'pending')" />
          </div>
          <div><span class="text-slate-500">Tracking #:</span> <strong>{{ delivery?.tracking_number || '-' }}</strong></div>
          <div v-if="delivery?.trip_id">
            <span class="text-slate-500">Trip:</span>
            <Button
              text
              severity="info"
              class="p-0"
              :label="`#${delivery.trip_id}`"
              @click="openTrip(delivery.trip_id)"
            />
          </div>
          <div><span class="text-slate-500">Courier Contact:</span> <strong>{{ delivery?.courier_contact || '-' }}</strong></div>
          <div><span class="text-slate-500">Driver:</span> <strong>{{ driverName }}</strong></div>
          <div><span class="text-slate-500">Delivered At:</span> <strong>{{ delivery?.delivered_at ? formatDateTime(delivery.delivered_at) : '-' }}</strong></div>
          <div><span class="text-slate-500">Order Total:</span> <strong>₱ {{ totalAmount }}</strong></div>
        </div>
      </template>
    </Card>

    <div v-if="delivery" class="grid grid-cols-1 gap-4 lg:grid-cols-2">
      <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
        <template #title>Status Logs</template>
        <template #content>
          <div v-if="isDelivered" class="mb-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">
            Delivery is already marked as delivered. Inputs are now read-only.
          </div>

          <div class="space-y-3">
            <div>
              <label class="mb-1 block text-sm text-slate-600">Update Delivery Status</label>
              <Select
                v-model="statusForm.status"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                fluid
                :disabled="!canManageDeliveries || isDelivered"
              />
            </div>
            <Textarea
              v-model="statusForm.notes"
              rows="3"
              fluid
              placeholder="Status notes (optional)"
              :disabled="!canManageDeliveries || isDelivered"
            />
            <Button
              icon="pi pi-save"
              label="Save Status"
              :loading="statusUpdating"
              :disabled="!canManageDeliveries || isDelivered || !statusForm.status"
              @click="saveStatus"
            />
          </div>
        </template>
      </Card>

      <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
        <template #title>Delivered Proof</template>
        <template #content>
          <div v-if="delivery?.proof_photo_url || delivery?.proof_signature_url" class="mb-3 flex flex-wrap gap-2">
            <Button v-if="delivery?.proof_photo_url" icon="pi pi-image" label="View Proof Photo" outlined @click="openMedia(delivery.proof_photo_url)" />
            <Button v-if="delivery?.proof_signature_url" icon="pi pi-pencil" label="View Signature" outlined @click="openMedia(delivery.proof_signature_url)" />
          </div>

          <div class="space-y-3">
            <Message v-if="isDelivered" severity="success" :closable="false">This delivery is finalized.</Message>

            <template v-else>
              <Button
                icon="pi pi-check-circle"
                label="Mark Delivered"
                severity="success"
                :disabled="!canManageDeliveries"
                @click="openDeliveredDialog"
              />
            </template>
          </div>
        </template>
      </Card>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Timeline</template>
      <template #content>
        <div v-if="!logs.length" class="text-sm text-slate-500">No timeline entries yet.</div>
        <div v-else class="space-y-3">
          <div v-for="entry in logs" :key="entry.id" class="rounded-2xl border border-slate-200 bg-slate-50 p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <Tag :value="formatStatus(entry.event_type || 'note')" severity="info" />
              <span class="text-xs text-slate-500">{{ formatDateTime(entry.created_at) }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-800">{{ entry.message }}</p>
            <p v-if="entry.status_from || entry.status_to" class="mt-1 text-xs text-slate-500">
              {{ entry.status_from || '-' }} → {{ entry.status_to || '-' }}
            </p>
          </div>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="deliveredDialogVisible" modal header="Upload Proof of Delivery" class="w-full max-w-xl">
      <div class="space-y-3">
        <div>
          <label class="mb-1 block text-sm text-slate-600">Proof Photo</label>
          <input type="file" accept="image/*" class="block w-full text-sm" @change="onPhotoChange" />
        </div>
        <div>
          <label class="mb-1 block text-sm text-slate-600">Signature</label>
          <input type="file" accept="image/*" class="block w-full text-sm" @change="onSignatureChange" />
        </div>
        <Textarea v-model="deliveredNotes" rows="3" fluid placeholder="Proof notes (optional)" />
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="deliveredDialogVisible = false" />
        <Button
          icon="pi pi-check-circle"
          label="Submit and Deliver"
          severity="success"
          :loading="delivering"
          :disabled="!photoFile || !signatureFile"
          @click="markDelivered"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'
import logisticsService from '../../../../services/logistics.service'
import { useAuthStore } from '../../../../stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canManageDeliveries = authStore.hasPermission('logistics.deliveries.manage')

const source = computed(() => (String(route.params.source || '').toLowerCase() === 'sales' ? 'sales' : 'ecommerce'))
const orderId = computed(() => Number(route.params.orderId || 0))
const sourceLabel = computed(() => (source.value === 'sales' ? 'Sales' : 'Ecommerce'))

const loading = ref(false)
const statusUpdating = ref(false)
const delivering = ref(false)
const deliveredDialogVisible = ref(false)

const order = ref<any>(null)
const delivery = ref<any>(null)
const logs = ref<any[]>([])

const photoFile = ref<File | null>(null)
const signatureFile = ref<File | null>(null)
const deliveredNotes = ref('')

const statusForm = reactive({
  status: '',
  notes: '',
})

const statusOptions = [
  { label: 'Assigned', value: 'assigned' },
  { label: 'Packed', value: 'packed' },
  { label: 'In Transit', value: 'in_transit' },
  { label: 'Out For Delivery', value: 'out_for_delivery' },
  { label: 'Delivered', value: 'delivered' },
  { label: 'Failed Delivery', value: 'failed_delivery' },
  { label: 'Cancelled', value: 'cancelled' },
]

const customerName = computed(() => (source.value === 'sales' ? order.value?.customer_name : order.value?.shipping_name) || '-')
const customerContact = computed(() => (source.value === 'sales' ? order.value?.customer_phone : order.value?.shipping_phone) || '-')
const deliveryAddress = computed(() => (source.value === 'sales' ? order.value?.delivery_address : order.value?.shipping_address) || '-')
const totalAmount = computed(() => Number(order.value?.total_amount || 0).toFixed(2))
const driverName = computed(() => {
  const d = delivery.value?.driver
  return d ? `${d.fname || ''} ${d.lname || ''}`.trim() : delivery.value?.courier_name || '-'
})
const isDelivered = computed(() => String(delivery.value?.status || '').toLowerCase() === 'delivered')
const canAssignDelivery = computed(() => canManageDeliveries && !delivery.value && !!order.value)

const loadAll = async () => {
  if (!orderId.value) return
  loading.value = true
  try {
    const response = await logisticsService.getDeliveryOrderDetail(source.value as 'ecommerce' | 'sales', orderId.value)
    const payload = response?.data || {}
    order.value = payload.order || null
    delivery.value = payload.delivery || null
    logs.value = payload.logs || []

    if (delivery.value) {
      statusForm.status = delivery.value.status || 'assigned'
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load detail.', life: 3500 })
  } finally {
    loading.value = false
  }
}

const saveStatus = async () => {
  if (!delivery.value) return

  statusUpdating.value = true
  try {
    await logisticsService.updateUnifiedDeliveryStatus(source.value as 'ecommerce' | 'sales', orderId.value, {
      status: statusForm.status,
      notes: statusForm.notes || null,
    })

    toast.add({ severity: 'success', summary: 'Updated', detail: 'Delivery status updated.', life: 2500 })
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Update Failed', detail: error?.response?.data?.message || 'Failed to update delivery status.', life: 3500 })
  } finally {
    statusUpdating.value = false
  }
}

const markDelivered = async () => {
  if (!photoFile.value || !signatureFile.value) return

  delivering.value = true
  try {
    const formData = new FormData()
    formData.append('photo', photoFile.value)
    formData.append('signature', signatureFile.value)
    formData.append('notes', deliveredNotes.value)

    await logisticsService.markUnifiedDelivered(source.value as 'ecommerce' | 'sales', orderId.value, formData)

    toast.add({ severity: 'success', summary: 'Delivered', detail: 'Proof uploaded and marked as delivered.', life: 2500 })

    photoFile.value = null
    signatureFile.value = null
    deliveredNotes.value = ''
    deliveredDialogVisible.value = false

    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Deliver Failed', detail: error?.response?.data?.message || 'Failed to mark as delivered.', life: 3500 })
  } finally {
    delivering.value = false
  }
}

const onPhotoChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  photoFile.value = target.files?.[0] || null
}

const onSignatureChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  signatureFile.value = target.files?.[0] || null
}

const goBack = () => router.push({ name: 'logistics.deliveries' })

const openDeliveredDialog = () => {
  deliveredDialogVisible.value = true
}

const openAssign = () => {
  router.push({
    name: 'logistics.deliveries.create',
    query: { source: source.value, order_id: String(orderId.value) },
  })
}

const openMedia = (url: string) => {
  if (!url) return
  window.open(url, '_blank')
}

const openTrip = (tripId: number) => {
  router.push({ name: 'logistics.trips.detail', params: { id: String(tripId) } })
}

const formatStatus = (value: string) => value?.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase()) || '-'

const deliverySeverity = (status: string) => {
  if (status === 'delivered') return 'success'
  if (status === 'failed_delivery' || status === 'cancelled') return 'danger'
  if (status === 'out_for_delivery') return 'warning'
  if (status === 'pending') return 'warn'
  return 'info'
}

const formatDateTime = (value: string) => (value ? new Date(value).toLocaleString('en-PH') : '-')

onMounted(loadAll)
</script>
