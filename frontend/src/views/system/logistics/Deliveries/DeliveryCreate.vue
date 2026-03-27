<template>
  <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-slate-200/80 bg-linear-to-br from-cyan-50 via-white to-emerald-50 p-6 shadow-sm">
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
          <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
          <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Create Delivery Assignment</h1>
            <p class="mt-1 text-sm text-slate-600">Assign logistics staff and set transport charges for this order.</p>
          </div>
        </div>
      </div>
    </div>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Order Summary</template>
      <template #content>
        <div v-if="loading" class="text-sm text-slate-500">Loading order details...</div>
        <div v-else-if="!order" class="text-sm text-slate-500">Order not found.</div>
        <div v-else class="grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
          <div><span class="text-slate-500">Source:</span> <strong>{{ sourceLabel }}</strong></div>
          <div><span class="text-slate-500">Order #:</span> <strong>{{ order.order_number }}</strong></div>
          <div><span class="text-slate-500">Customer:</span> <strong>{{ customerName }}</strong></div>
          <div><span class="text-slate-500">Contact:</span> <strong>{{ customerContact }}</strong></div>
          <div class="md:col-span-2"><span class="text-slate-500">Address:</span> <strong>{{ deliveryAddress }}</strong></div>
          <div><span class="text-slate-500">Order Status:</span> <Tag :value="formattedOrderStatus" severity="secondary" /></div>
          <div><span class="text-slate-500">Total:</span> <strong>₱ {{ orderTotal }}</strong></div>
        </div>
      </template>
    </Card>

    <Card class="rounded-3xl border border-slate-200/80 shadow-sm">
      <template #title>Delivery Assignment Form</template>
      <template #content>
        <form class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="submitAssignment">
          <div>
            <label class="mb-1 block text-sm text-slate-600">Logistics Employee</label>
            <Select
              v-model="form.driver_user_id"
              :options="employees"
              optionLabel="name"
              optionValue="id"
              fluid
              filter
              placeholder="Select logistics employee"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-600">Vehicle</label>
            <Select
              v-model="form.vehicle_id"
              :options="vehicles"
              optionLabel="label"
              optionValue="id"
              fluid
              filter
              placeholder="Select vehicle"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-600">Courier Contact Number</label>
            <InputText v-model="form.courier_contact" fluid placeholder="09xxxxxxxxx" />
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-600">Estimated Delivery Time</label>
            <DatePicker v-model="form.estimated_delivery_at" showTime hourFormat="12" fluid />
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-600">Distance (KM)</label>
            <InputNumber v-model="form.distance_km" :min="0" :minFractionDigits="2" :maxFractionDigits="2" fluid />
          </div>

          <div>
            <label class="mb-1 block text-sm text-slate-600">Per KM Charge</label>
            <InputNumber v-model="form.per_km_charge" mode="currency" currency="PHP" locale="en-PH" :min="0" fluid />
          </div>

          <div class="md:col-span-2 rounded-2xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p class="font-medium">Estimated Delivery Fee</p>
                <p class="text-xs">Distance × Per KM Charge</p>
              </div>
              <p class="text-xl font-semibold">₱ {{ estimatedFee }}</p>
            </div>
          </div>

          <div class="md:col-span-2">
            <label class="mb-1 block text-sm text-slate-600">Notes</label>
            <Textarea v-model="form.notes" rows="3" fluid placeholder="Optional logistics notes" />
          </div>

          <div class="md:col-span-2 flex flex-wrap gap-2">
            <Button type="button" outlined icon="pi pi-map-marker" label="Calculate Distance" :loading="estimating" @click="calculateDistance" />
            <Button type="submit" icon="pi pi-check" label="Assign Delivery" :loading="submitting" :disabled="!canManageDeliveries || !canSubmit" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import logisticsService from '../../../../services/logistics.service'
import { useAuthStore } from '../../../../stores/auth'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canManageDeliveries = authStore.hasPermission('logistics.deliveries.manage')

const source = computed(() => (String(route.query.source || '').toLowerCase() === 'sales' ? 'sales' : 'ecommerce'))
const orderId = computed(() => Number(route.query.order_id || 0))

const loading = ref(false)
const estimating = ref(false)
const submitting = ref(false)
const order = ref<any>(null)

const employees = ref<any[]>([])
const vehicles = ref<any[]>([])

const form = reactive({
  driver_user_id: null as number | null,
  vehicle_id: null as number | null,
  courier_contact: '',
  distance_km: 0,
  per_km_charge: 0,
  estimated_delivery_at: null as Date | null,
  notes: '',
})

const sourceLabel = computed(() => (source.value === 'sales' ? 'Sales' : 'Ecommerce'))
const customerName = computed(() => (source.value === 'sales' ? order.value?.customer_name : order.value?.shipping_name) || '-')
const customerContact = computed(() => (source.value === 'sales' ? order.value?.customer_phone : order.value?.shipping_phone) || '-')
const deliveryAddress = computed(() => (source.value === 'sales' ? order.value?.delivery_address : order.value?.shipping_address) || '-')
const formattedOrderStatus = computed(() => {
  const status = String(order.value?.status || '')
  if (!status) return '-'
  return status.replace(/_/g, ' ').replace(/\b\w/g, (m) => m.toUpperCase())
})
const orderTotal = computed(() => Number(order.value?.total_amount || 0).toFixed(2))
const estimatedFee = computed(() => (Number(form.distance_km || 0) * Number(form.per_km_charge || 0)).toFixed(2))

const canSubmit = computed(() => {
  return !!form.driver_user_id && !!form.vehicle_id && !!form.courier_contact && Number(form.per_km_charge) >= 0
})

const loadOptions = async () => {
  const [employeeRes, vehicleRes] = await Promise.all([
    logisticsService.getLogisticsEmployees(),
    logisticsService.getVehicles({ per_page: 100 }),
  ])

  employees.value = employeeRes?.data || []

  const vehicleRows = vehicleRes?.data?.data || []
  vehicles.value = vehicleRows.map((vehicle: any) => ({
    ...vehicle,
    label: `${vehicle.vehicle_name} (${vehicle.plate_number})`,
  }))
}

const loadOrderDetail = async () => {
  if (!orderId.value) return

  const response = await logisticsService.getDeliveryOrderDetail(source.value as 'ecommerce' | 'sales', orderId.value)
  order.value = response?.data?.order || null

  const existingDeliveryStatus = String(response?.data?.delivery?.status || '').toLowerCase()
  if (response?.data?.delivery && existingDeliveryStatus !== 'pending') {
    toast.add({
      severity: 'warn',
      summary: 'Already Assigned',
      detail: 'Delivery has already been assigned for this order.',
      life: 3000,
    })

    router.replace({
      name: 'logistics.deliveries.detail',
      params: { source: source.value, orderId: String(orderId.value) },
    })
    return
  }
}

const calculateDistance = async () => {
  const originLatitude = Number(source.value === 'sales' ? order.value?.branch?.latitude : order.value?.assigned_branch?.latitude)
  const originLongitude = Number(source.value === 'sales' ? order.value?.branch?.longitude : order.value?.assigned_branch?.longitude)
  const destinationLatitude = Number(source.value === 'sales' ? order.value?.delivery_latitude : order.value?.customer_latitude)
  const destinationLongitude = Number(source.value === 'sales' ? order.value?.delivery_longitude : order.value?.customer_longitude)

  if (![originLatitude, originLongitude, destinationLatitude, destinationLongitude].every(Number.isFinite)) {
    toast.add({ severity: 'warn', summary: 'Missing Coordinates', detail: 'Unable to calculate distance for this order.', life: 3000 })
    return
  }

  estimating.value = true
  try {
    const response = await logisticsService.estimateDeliveryDistance({
      origin_latitude: originLatitude,
      origin_longitude: originLongitude,
      destination_latitude: destinationLatitude,
      destination_longitude: destinationLongitude,
      per_km_charge: form.per_km_charge,
    })

    const payload = response?.data || {}
    form.distance_km = Number(payload.distance_km || 0)

    if (payload.per_km_charge !== null && payload.per_km_charge !== undefined) {
      form.per_km_charge = Number(payload.per_km_charge)
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Distance Error', detail: error?.response?.data?.message || 'Distance calculation failed.', life: 3000 })
  } finally {
    estimating.value = false
  }
}

const submitAssignment = async () => {
  if (!canManageDeliveries) return

  submitting.value = true
  try {
    await logisticsService.assignDelivery({
      source_type: source.value,
      order_id: orderId.value,
      driver_user_id: form.driver_user_id,
      vehicle_id: form.vehicle_id,
      courier_contact: form.courier_contact,
      distance_km: Number(form.distance_km || 0),
      per_km_charge: Number(form.per_km_charge || 0),
      estimated_delivery_at: form.estimated_delivery_at ? new Date(form.estimated_delivery_at).toISOString() : null,
      notes: form.notes || null,
    })

    toast.add({ severity: 'success', summary: 'Assigned', detail: 'Delivery has been assigned successfully.', life: 2500 })
    router.push({
      name: 'logistics.deliveries.detail',
      params: { source: source.value, orderId: String(orderId.value) },
    })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Assign Failed', detail: error?.response?.data?.message || 'Failed to assign delivery.', life: 3500 })
  } finally {
    submitting.value = false
  }
}

const goBack = () => router.push({ name: 'logistics.deliveries' })

onMounted(async () => {
  if (!orderId.value) {
    toast.add({ severity: 'warn', summary: 'Missing Order', detail: 'Please select an order from the list.', life: 3000 })
    router.replace({ name: 'logistics.deliveries' })
    return
  }

  loading.value = true
  try {
    await Promise.all([loadOptions(), loadOrderDetail()])
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Load Failed', detail: error?.response?.data?.message || 'Failed to load create form.', life: 3500 })
  } finally {
    loading.value = false
  }
})
</script>
