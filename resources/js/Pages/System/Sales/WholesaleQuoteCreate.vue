<template>
  <div class="mx-auto max-w-7xl space-y-5 p-4 pb-8">
    <ConfirmDialog />
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3"><Button icon="pi pi-arrow-left" text rounded
          @click="router.visit('/sales/wholesale-quotes')" />
        <div>
          <h1 class="text-xl font-semibold text-slate-900">Create Wholesale Quotation</h1>
          <p class="text-sm text-slate-500">Set negotiated prices for a business customer without applying retail
            promotions.</p>
        </div>
      </div>
    </div>
    <Message severity="info" :closable="false">Apply either a discount percentage or a fixed discount amount to each product line. Both values stay synchronized.</Message>
    <div v-if="loading" class="space-y-3">
      <Skeleton height="9rem" />
      <Skeleton height="20rem" />
    </div>
    <template v-else>
      <div class="grid gap-4 lg:grid-cols-3">
        <Card class="border border-slate-200 shadow-sm lg:col-span-2"><template #title>Customer and
            branch</template><template #content>
            <div class="grid gap-4 md:grid-cols-2">
              <div class="field">
                <label>Customer / Business Name *</label>
                <InputText v-model.trim="form.customer_name" fluid placeholder="Enter customer or business name"
                  :invalid="Boolean(customerNameError)" @blur="touched.customer_name = true" />
                <small v-if="customerNameError" class="text-red-600">{{ customerNameError }}</small>
              </div>
              <div class="field">
                <label>Contact Email *</label>
                <InputText v-model.trim="form.customer_email" type="email" fluid placeholder="name@business.com"
                  :invalid="Boolean(customerEmailError)" @blur="touched.customer_email = true" />
                <small v-if="customerEmailError" class="text-red-600">{{ customerEmailError }}</small>
              </div>
              <div class="field">
                <label>Contact Number *</label>
                <InputMask v-model="form.customer_phone" mask="0999 999 9999" placeholder="09XX XXX XXXX"
                  :autoClear="false" fluid :invalid="Boolean(customerPhoneError)" @blur="touched.customer_phone = true" />
                <small v-if="customerPhoneError" class="text-red-600">{{ customerPhoneError }}</small>
              </div>
              <div class="field"><label>Fulfilment branch *</label><Select v-model="form.branch_id" :options="branches"
                  optionLabel="name" optionValue="id" fluid placeholder="Select branch" :disabled="!branches.length" @change="resetItems"><template #option="{ option }"><div><p class="font-medium">{{ option.name }}</p><p class="text-xs capitalize text-slate-500">{{ option.branch_type }} branch</p></div></template></Select><small v-if="!branches.length" class="text-amber-700">No active storefront or wholesale branches are available.</small></div>
              <div class="field"><label>Valid until</label>
                <DatePicker v-model="form.valid_until" showIcon fluid :minDate="new Date()" />
              </div>
              <div class="field"><label>Payment terms *</label>
                <Select v-model="form.payment_terms" :options="paymentTerms" optionLabel="label" optionValue="value"
                  fluid placeholder="Select payment terms"><template #option="{ option }"><div><p class="font-medium">{{ option.label }}</p><p class="text-xs text-slate-500">{{ option.help }}</p></div></template></Select>
              </div>
              <div class="field md:col-span-2">
                <label>Fulfilment</label>
                <div class="flex flex-wrap gap-2">
                  <Button label="Store pickup" :outlined="form.delivery_required" severity="warn" size="small" @click="form.delivery_required = false" />
                  <Button label="Delivery" :outlined="!form.delivery_required" severity="warn" size="small" @click="form.delivery_required = true" />
                </div>
              </div>
              <div v-if="form.delivery_required" class="field md:col-span-2">
                <label>Delivery address</label>
                <div class="grid gap-3 md:grid-cols-2">
                  <div class="field"><label>Province *</label><Select v-model="addressSelection.provinceId" :options="provinceOptions" optionLabel="label" optionValue="value" filter fluid placeholder="Select province" :invalid="addressAttempted && !addressSelection.provinceId" @change="onProvinceChange" /></div>
                  <div class="field"><label>City / Municipality *</label><Select v-model="addressSelection.cityId" :options="cityOptions" optionLabel="label" optionValue="value" filter fluid placeholder="Select city" :disabled="!addressSelection.provinceId" :invalid="addressAttempted && !addressSelection.cityId" @change="onCityChange" /></div>
                  <div class="field"><label>Barangay *</label><Select v-model="addressSelection.barangayCode" :options="barangayOptions" optionLabel="label" optionValue="value" filter fluid placeholder="Select barangay" :disabled="!addressSelection.cityId" :invalid="addressAttempted && !addressSelection.barangayCode" /></div>
                  <div class="field"><label>Street / Building / Landmark *</label><InputText v-model.trim="form.delivery_address_line" fluid placeholder="House number, street or building" :invalid="addressAttempted && !form.delivery_address_line" /></div>
                </div>
                <div class="field mt-2"><label>Map pin *</label><div class="flex flex-col gap-2 sm:flex-row"><InputText :modelValue="pinnedAddress" fluid readonly placeholder="No map pin selected" :invalid="addressAttempted && !pinnedAddress" /><Button label="Choose on map" icon="pi pi-map-marker" outlined severity="warn" :disabled="!addressSelection.provinceId || !addressSelection.cityId || !addressSelection.barangayCode || !form.delivery_address_line" @click="mapVisible = true" /></div><small class="text-slate-500">Complete the address, then search or place the exact destination pin.</small></div>
                <div class="field mt-2"><label>Delivery notes</label><Textarea v-model="form.delivery_notes" rows="2" fluid placeholder="Gate, contact instructions or landmark (optional)" /></div>
              </div>
            </div>
          </template></Card>
        <Card class="border border-slate-200 shadow-sm"><template #title>Quotation summary</template><template #content>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between text-slate-500"><span>Line items</span><span>{{ form.items.length
              }}</span></div>
              <div class="flex justify-between text-slate-500"><span>Total quantity</span><span>{{ totalQuantity
              }}</span></div>
              <div v-if="form.delivery_required" class="flex justify-between text-slate-500"><span>Delivery fee</span><span>{{ feeLoading ? 'Calculating...' : form.delivery_latitude == null ? 'Choose a destination' : money(shippingFee) }}</span></div>
              <div class="border-t pt-3 flex justify-between text-base font-semibold"><span>Quoted total</span><span>{{ money(total + (form.delivery_required ? shippingFee : 0)) }}</span></div>
              <p class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800">Stock is validated again when an accepted
                quotation becomes an order.</p>
            </div>
          </template>
        </Card>
      </div>
      <Card class="border border-slate-200 shadow-sm"><template #title>
          <div class="flex items-center justify-between"><span>Quoted products</span><Button label="Add item"
              icon="pi pi-plus" text size="small" :disabled="!form.branch_id" @click="form.items.push(newItem())" />
          </div>
        </template><template #content>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[1100px] text-sm">
              <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                  <th class="px-3 py-3">Product</th>
                  <th class="px-3 py-3 w-28">Available</th>
                  <th class="px-3 py-3 w-28">Quantity</th>
                  <th class="px-3 py-3 w-44">Retail reference</th>
                  <th class="px-3 py-3 w-36">Discount %</th>
                  <th class="px-3 py-3 w-44">Discount amount</th>
                  <th class="px-3 py-3 w-40 text-right">Agreed total</th>
                  <th class="w-12"></th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr v-for="(item, index) in form.items" :key="index">
                  <td class="p-3"><Select v-model="item.branch_inventory_id" :options="availableFor(index)"
                      optionLabel="product_name" optionValue="id" filter :filterFields="['product_name', 'variant_name', 'variant_specs', 'sku']" fluid placeholder="Select product"
                      @change="selectProduct(item)">
                      <template #option="{ option }">
                        <div class="min-w-0 py-0.5">
                          <p class="truncate text-sm font-semibold text-slate-900">{{ option.product_name }}</p>
                          <p class="mt-0.5 truncate text-xs text-slate-500">
                            {{ option.variant_name || option.variant_specs || 'Standard item' }}
                            <span class="mx-1">·</span>SKU {{ option.sku || '—' }}
                            <span class="mx-1">·</span>{{ option.available }} in stock
                          </p>
                        </div>
                      </template>
                      <template #value="{ value, placeholder }">
                        <div v-if="value" class="min-w-0">
                          <p class="truncate text-sm font-medium text-slate-900">{{ selected({ branch_inventory_id: value })?.product_name }}</p>
                          <p class="truncate text-xs text-slate-500">
                            {{ selected({ branch_inventory_id: value })?.variant_name || selected({ branch_inventory_id: value })?.variant_specs || 'Standard item' }}
                            <span class="mx-1">·</span>SKU {{ selected({ branch_inventory_id: value })?.sku || '—' }}
                            <span class="mx-1">·</span>{{ selected({ branch_inventory_id: value })?.available }} in stock
                          </p>
                        </div>
                        <span v-else class="text-slate-400">{{ placeholder }}</span>
                      </template>
                    </Select></td>
                  <td class="p-3">{{ selected(item)?.available ?? '—' }}</td>
                  <td class="p-3">
                    <InputNumber v-model="item.quantity" :min="1" :max="selected(item)?.available || undefined" fluid @update:model-value="syncFromPercent(item)" />
                  </td>
                  <td class="p-3 text-slate-500">{{ money(selected(item)?.base_price) }}</td>
                  <td class="p-3"><InputNumber v-model="item.discount_percent" suffix="%" :min="0" :max="100" :maxFractionDigits="2" fluid @update:model-value="syncFromPercent(item)" /></td>
                  <td class="p-3"><InputNumber v-model="item.discount_amount" mode="currency" currency="PHP" locale="en-PH" :min="0" :max="grossLine(item)" fluid @update:model-value="syncFromAmount(item)" /></td>
                  <td class="p-3 text-right"><p class="font-semibold">{{ money(netLine(item)) }}</p><p v-if="item.discount_amount > 0" class="text-xs text-slate-400 line-through">{{ money(grossLine(item)) }}</p></td>
                  <td><Button icon="pi pi-trash" severity="danger" text rounded :disabled="form.items.length === 1"
                      @click="form.items.splice(index, 1)" /></td>
                </tr>
              </tbody>
            </table>
          </div>
        </template></Card>
      <Card class="border border-slate-200 shadow-sm"><template #title>Internal notes</template><template
          #content><Textarea v-model="form.notes" rows="4" fluid
            placeholder="Delivery expectations, negotiated terms, or customer requests" /></template>
      </Card>
      <div class="flex justify-end gap-2"><Button label="Cancel" severity="secondary" outlined
          @click="router.visit('/sales/wholesale-quotes')" /><Button label="Save Draft" icon="pi pi-save"
          :loading="saving" @click="save(false)" /><Button label="Submit Quotation" icon="pi pi-send" severity="warn" :loading="saving" @click="confirmSubmit" /></div>
    </template>
    <Dialog v-model:visible="mapVisible" modal header="Choose delivery destination" class="w-full max-w-2xl" @show="initMap" @hide="destroyMap">
      <div class="flex gap-2"><InputText v-model="mapSearch" fluid placeholder="Search address in the Philippines" @keydown.enter.prevent="searchMap" /><Button label="Search" icon="pi pi-search" outlined severity="warn" :loading="mapSearching" @click="searchMap" /></div>
      <div ref="mapEl" class="mt-3 h-80 w-full rounded-lg border border-slate-200" />
      <p class="mt-2 text-xs text-slate-500">Click the map or drag the pin to choose the exact destination.</p>
      <p v-if="pendingPin" class="mt-2 rounded-lg bg-orange-50 p-3 text-sm text-slate-700">{{ pinLoading ? 'Finding address...' : pendingAddress || 'Address unavailable. Try another pin.' }}</p>
      <template #footer><Button label="Cancel" severity="secondary" text @click="mapVisible = false" /><Button label="Use this location" severity="warn" :disabled="!pendingPin || !pendingAddress || pinLoading" @click="confirmPin" /></template>
    </Dialog>
    <Dialog v-model:visible="responseVisible" modal header="Quotation submitted" :closable="false" class="w-full max-w-md">
      <div class="space-y-2 text-sm text-slate-700">
        <p class="font-semibold text-slate-900">{{ createdQuote?.quote_number }}</p>
        <p>{{ createdQuote?.status === 'approved' ? 'Your quotation was approved and is ready to become a sales order.' : 'Your quotation is pending approval.' }}</p>
      </div>
      <template #footer><Button label="View Quotation" icon="pi pi-arrow-right" severity="warn" @click="router.visit(`/sales/wholesale-quotes/${createdQuote.id}`)" /></template>
    </Dialog>
  </div>
</template>
<script setup lang="ts">
import { computed, nextTick, onMounted, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import salesService from '@/services/sales.service'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import ConfirmDialog from 'primevue/confirmdialog'
import Dialog from 'primevue/dialog'
import DatePicker from 'primevue/datepicker'
import InputMask from 'primevue/inputmask'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'
import { useConfirm } from 'primevue/useconfirm'
import { showResponseDialog } from '@/utils/responseDialogBus'
import { forwardGeocodeMapbox, reverseGeocodeMapbox, requireMapboxToken } from '@/utils/mapbox'
import 'mapbox-gl/dist/mapbox-gl.css'
const confirm = useConfirm()
const responseVisible = ref(false)
const createdQuote = ref<any>(null)
const toast = useToast(),
  loading = ref(true),
  saving = ref(false),
  products = ref<any[]>([]),
  branches = ref<any[]>([])
const newItem = () => ({
  branch_inventory_id: null as number | null,
  quantity: 1,
  unit_price: 0,
  discount_percent: 0,
  discount_amount: 0
})
const form = reactive({
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  branch_id: null as number | null,
  valid_until: null as Date | null,
  payment_terms: 'online_payment',
  delivery_required: false,
  delivery_province: '',
  delivery_city: '',
  delivery_barangay: '',
  delivery_address_line: '',
  delivery_notes: '',
  delivery_latitude: null as number | null,
  delivery_longitude: null as number | null,
  notes: '',
  items: [newItem()]
})
const touched = reactive({ customer_name: false, customer_email: false, customer_phone: false })
const addressAttempted = ref(false)
const addressSelection = reactive({ provinceId: null as string | null, cityId: null as string | null, barangayCode: null as string | null })
const provinces = ref<any[]>([]), cities = ref<any[]>([]), barangays = ref<any[]>([])
const provinceOptions = computed(() => provinces.value.map(p => ({ label: p.name, value: p.province_id })))
const cityOptions = computed(() => cities.value.map(c => ({ label: c.name, value: c.city_id })))
const barangayOptions = computed(() => barangays.value.map(b => ({ label: b.name, value: b.code })))
const onProvinceChange = async () => {
  addressSelection.cityId = null; addressSelection.barangayCode = null; cities.value = []; barangays.value = []
  form.delivery_latitude = null; form.delivery_longitude = null
  pinnedAddress.value = ''; pendingAddress.value = ''; pendingPin.value = null
  if (addressSelection.provinceId) {
    try { cities.value = (await axiosClient.get('/api/locations/cities', { params: { province_id: addressSelection.provinceId } })).data?.data || [] }
    catch { toast.add({ severity: 'error', summary: 'Unable to load cities', life: 3000 }) }
  }
}
const onCityChange = async () => {
  addressSelection.barangayCode = null; barangays.value = []
  form.delivery_latitude = null; form.delivery_longitude = null
  pinnedAddress.value = ''; pendingAddress.value = ''; pendingPin.value = null
  if (addressSelection.cityId) {
    try { barangays.value = (await axiosClient.get('/api/locations/barangays', { params: { city_id: addressSelection.cityId } })).data?.data || [] }
    catch { toast.add({ severity: 'error', summary: 'Unable to load barangays', life: 3000 }) }
  }
}
watch(() => [addressSelection.barangayCode, form.delivery_address_line], () => {
  form.delivery_latitude = null; form.delivery_longitude = null
  pendingPin.value = null
  pinnedAddress.value = ''
  pendingAddress.value = ''
})
const customerNameError = computed(() => touched.customer_name && form.customer_name.trim().length < 2
  ? 'Enter at least 2 characters.' : '')
const customerEmailError = computed(() => touched.customer_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.customer_email.trim())
  ? 'Enter a valid email address.' : '')
const customerPhoneError = computed(() => touched.customer_phone && !/^09\d{2}\s?\d{3}\s?\d{4}$/.test(form.customer_phone.trim())
  ? 'Enter a valid 11-digit Philippine mobile number.' : '')
const paymentTerms = [
  { label: 'PayMongo payment link', value: 'online_payment', help: 'Share a secure payment link and wait for confirmation.' },
  { label: 'Verified bank transfer', value: 'bank_transfer', help: 'Confirm the funds in your bank before releasing the order.' }
]
const branchProducts = computed(() =>
  products.value
    .filter(x => Number(x.branch_id) === Number(form.branch_id))
    .map(x => ({ ...x, label: x.product_name }))
)
const selected = (item: any) =>
  products.value.find(x => x.id === item.branch_inventory_id)
const availableFor = (index: number) =>
  branchProducts.value.filter(
    x =>
      !form.items.some(
        (item, i) => i !== index && item.branch_inventory_id === x.id
      )
  )
const total = computed(() =>
  form.items.reduce((sum, item) => sum + netLine(item), 0)
)
const totalQuantity = computed(() =>
  form.items.reduce((s, x) => s + (+x.quantity || 0), 0)
)
const mapVisible = ref(false), mapEl = ref<HTMLElement | null>(null), mapSearch = ref(''), mapSearching = ref(false), pendingPin = ref<{ latitude: number; longitude: number } | null>(null)
const pinnedAddress = ref(''), pendingAddress = ref(''), pinLoading = ref(false)
let pinLookupId = 0
const shippingFee = ref(0), feeLoading = ref(false)
let map: import('mapbox-gl').Map | null = null, marker: import('mapbox-gl').Marker | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
const destroyMap = () => { pinLookupId++; map?.remove(); map = null; marker = null }
onBeforeUnmount(destroyMap)
const initMap = async () => {
  await nextTick()
  if (!mapEl.value || !mapVisible.value) return
  pendingPin.value = null
  pendingAddress.value = ''
  try {
    mapboxgl = (await import('mapbox-gl')).default
    mapboxgl.accessToken = requireMapboxToken()
    const latitude = form.delivery_latitude ?? 14.3, longitude = form.delivery_longitude ?? 120.9
    map = new mapboxgl.Map({ container: mapEl.value, style: 'mapbox://styles/mapbox/streets-v12', center: [longitude, latitude], zoom: 10 })
    marker = new mapboxgl.Marker({ color: '#f97316', draggable: true }).setLngLat([longitude, latitude]).addTo(map)
    const setPin = (lat: number, lng: number) => {
      pendingPin.value = { latitude: lat, longitude: lng }
      pendingAddress.value = ''
      marker?.setLngLat([lng, lat])
      const lookupId = ++pinLookupId
      pinLoading.value = true
      reverseGeocodeMapbox(lat, lng)
        .then(address => { if (lookupId === pinLookupId) pendingAddress.value = address })
        .catch(() => { if (lookupId === pinLookupId) pendingAddress.value = '' })
        .finally(() => { if (lookupId === pinLookupId) pinLoading.value = false })
    }
    if (form.delivery_latitude != null && form.delivery_longitude != null) setPin(latitude, longitude)
    map.on('click', e => setPin(e.lngLat.lat, e.lngLat.lng))
    marker.on('dragend', () => { const p = marker?.getLngLat(); if (p) setPin(p.lat, p.lng) })
    map.once('load', () => map?.resize())
  } catch (e: any) { toast.add({ severity: 'error', summary: 'Map unavailable', detail: e.message, life: 3500 }) }
}
const searchMap = async () => {
  const query = [mapSearch.value.trim() || form.delivery_address_line,
    barangayOptions.value.find(x => x.value === addressSelection.barangayCode)?.label,
    cityOptions.value.find(x => x.value === addressSelection.cityId)?.label,
    provinceOptions.value.find(x => x.value === addressSelection.provinceId)?.label,
    'Philippines'].filter(Boolean).join(', ')
  if (!query.trim()) return
  mapSearching.value = true
  try { const result = await forwardGeocodeMapbox(query); if (!result) throw new Error('Address not found.'); pinLookupId++; pinLoading.value = false; pendingPin.value = { latitude: result.latitude, longitude: result.longitude }; pendingAddress.value = result.address; marker?.setLngLat([result.longitude, result.latitude]); map?.flyTo({ center: [result.longitude, result.latitude], zoom: 15 }) }
  catch (e: any) { toast.add({ severity: 'warn', summary: 'Address not found', detail: e.message, life: 3000 }) }
  finally { mapSearching.value = false }
}
const confirmPin = async () => {
  if (!pendingPin.value || !pendingAddress.value || pinLoading.value) return
  form.delivery_latitude = pendingPin.value.latitude
  form.delivery_longitude = pendingPin.value.longitude
  pinnedAddress.value = pendingAddress.value
  mapVisible.value = false
}
const money = (v: any) =>
  new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(+v || 0)
const resetItems = () => (form.items = [newItem()])
const roundMoney = (value: number) => Math.round(value * 100) / 100
const grossLine = (item: any) => roundMoney((Number(item.quantity) || 0) * (Number(selected(item)?.base_price) || 0))
const netLine = (item: any) => Math.max(0, roundMoney(grossLine(item) - (Number(item.discount_amount) || 0)))
watch(() => [form.branch_id, form.delivery_latitude, form.delivery_longitude, total.value, form.delivery_required], async () => {
  shippingFee.value = 0
  if (!form.delivery_required || !form.branch_id || form.delivery_latitude == null || form.delivery_longitude == null) return
  feeLoading.value = true
  try { const result = await salesService.estimateWholesaleDelivery({ branch_id: form.branch_id, subtotal: total.value, delivery_latitude: form.delivery_latitude, delivery_longitude: form.delivery_longitude }); shippingFee.value = Number(result.data.shipping_fee) || 0 }
  catch (e: any) { toast.add({ severity: 'warn', summary: 'Delivery fee unavailable', detail: e.response?.data?.message || 'Check the destination.', life: 3000 }) }
  finally { feeLoading.value = false }
})
const syncFromPercent = (item: any) => {
  item.discount_percent = Math.min(100, Math.max(0, Number(item.discount_percent) || 0))
  item.discount_amount = roundMoney(grossLine(item) * item.discount_percent / 100)
  item.unit_price = item.quantity > 0 ? roundMoney(netLine(item) / item.quantity) : 0
}
const syncFromAmount = (item: any) => {
  item.discount_amount = Math.min(grossLine(item), Math.max(0, Number(item.discount_amount) || 0))
  item.discount_percent = grossLine(item) > 0 ? roundMoney(item.discount_amount / grossLine(item) * 100) : 0
  item.unit_price = item.quantity > 0 ? roundMoney(netLine(item) / item.quantity) : 0
}
const selectProduct = (item: any) => {
  item.discount_percent = 0
  item.discount_amount = 0
  item.unit_price = Number(selected(item)?.base_price) || 0
}
const date = (v: Date | null) =>
  v
    ? `${v.getFullYear()}-${String(v.getMonth() + 1).padStart(
      2,
      '0'
    )}-${String(v.getDate()).padStart(2, '0')}`
    : null
const validateForm = () => {
  touched.customer_name = true
  touched.customer_email = true
  touched.customer_phone = true
  addressAttempted.value = true
  if (
    customerNameError.value || customerEmailError.value || customerPhoneError.value ||
    !form.branch_id ||
    !form.payment_terms ||
    (form.delivery_required && (!addressSelection.provinceId || !addressSelection.cityId || !addressSelection.barangayCode || !form.delivery_address_line || form.delivery_latitude == null || form.delivery_longitude == null || feeLoading.value)) ||
    form.items.some(
      x => !x.branch_inventory_id || x.quantity < 1 || x.unit_price <= 0
    )
  ) {
    toast.add({
      severity: 'warn',
      summary: 'Complete required fields',
      detail: 'Enter valid customer details, choose a branch, and complete the product lines.',
      life: 3000
    })
    return false
  }
  return true
}
const confirmSubmit = () => {
  if (saving.value || !validateForm()) return
  confirm.require({
    header: 'Submit wholesale quotation?',
    message: 'Submit this quotation for approval? Review the customer, products, fulfilment, and total before continuing.',
    icon: 'pi pi-file-check',
    acceptLabel: 'Submit Quotation',
    rejectLabel: 'Keep Editing',
    acceptProps: { severity: 'warn' },
    rejectProps: { severity: 'secondary', outlined: true },
    accept: () => save(true),
  })
}
const save = async (submit: boolean) => {
  if (saving.value || !validateForm()) return
  saving.value = true
  try {
    const r = await salesService.createWholesaleQuote({
      ...form,
      submit,
      delivery_province: form.delivery_required ? provinceOptions.value.find(x => x.value === addressSelection.provinceId)?.label : null,
      delivery_city: form.delivery_required ? cityOptions.value.find(x => x.value === addressSelection.cityId)?.label : null,
      delivery_barangay: form.delivery_required ? barangayOptions.value.find(x => x.value === addressSelection.barangayCode)?.label : null,
      valid_until: date(form.valid_until)
    })
    if (submit) {
      createdQuote.value = r.data
      responseVisible.value = true
    } else {
      toast.add({ severity: 'success', summary: 'Draft saved', life: 2000 })
      router.visit(`/sales/wholesale-quotes/${r.data.id}`)
    }
  } catch (e: any) {
    showResponseDialog({
      severity: 'error',
      title: 'Unable to Save',
      message: e.response?.data?.message || 'Please try again.',
      details: String(Object.values(e.response?.data?.errors || {})[0] || ''),
    })
  } finally {
    saving.value = false
  }
}
onMounted(async () => {
  try { provinces.value = (await axiosClient.get('/api/locations/provinces')).data?.data || [] }
  catch { toast.add({ severity: 'error', summary: 'Unable to load provinces', life: 3000 }) }
  try {
    const r = await salesService.getWholesaleOptions()
    products.value = r.data?.products || []
    branches.value = r.data?.branches || []
  } catch (e: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to load form',
      detail: e.response?.data?.message || 'Please try again.',
      life: 3500
    })
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.field {
  display: flex;
  flex-direction: column;
  gap: .4rem
}

.field label {
  font-size: .875rem;
  font-weight: 600;
  color: #334155
}
</style>
