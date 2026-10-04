<template>
  <div class="pos-container">
    <ConfirmDialog />

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <!-- Products Section -->
      <Card class="lg:col-span-2">
        <template #title>
          <div class="flex items-center justify-between flex-wrap gap-2">
            <span>Products</span>
            <div class="relative w-full sm:w-64">
              <InputText 
                v-model="search" 
                placeholder="Search products..." 
                fluid
                class="pl-9"
              />
            </div>
          </div>
        </template>
        <template #content>
          <DataTable 
            :value="products" 
            :loading="loadingProducts" 
            :rows="10"
            :paginator="true"
          >
            <Column field="product.product_name" header="Product">
              <template #body="{ data }">
                <div>
                  <div class="font-medium">{{ data.product?.product_name }}</div>
                  <div v-if="data.variation?.variation_name" class="truncate text-xs text-slate-500">
                    Variant: {{ data.variation.variation_name }}
                  </div>
                </div>
              </template>
            </Column>
            <Column header="SKU">
              <template #body="{ data }">
                <span class="text-sm text-slate-600">{{ data.variation?.variation_sku || data.product?.sku || '—' }}</span>
              </template>
            </Column>
            <Column field="quantity_available" header="Stock">
              <template #body="{ data }">
                <badge 
                  :value="`${Number(data.quantity_available || 0)} available`"
                  :severity="getStockSeverity(data.quantity_available)" 
                />
              </template>
            </Column>
            <Column header="Price">
              <template #body="{ data }">
                {{ money(data.product?.discounted_price || data.product?.base_price || 0) }}
              </template>
            </Column>
            <Column header="" style="width: 60px">
              <template #body="{ data }">
                <Button 
                  text 
                   
                  icon="pi pi-plus" 
                  :disabled="!canManagePos || cartQuantity(data.id) >= Number(data.quantity_available || 0)"
                  @click="addToCart(data)"
                />
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <!-- Cart Section -->
      <Card>
        <template #title>
          <div class="flex items-center gap-2">
            <span>Cart</span>
            <Badge v-if="cart.length" :value="cart.length" class="ml-auto" />
          </div>
        </template>
        <template #content>
          <div v-if="!cart.length" class="text-center py-8 text-gray-500">
            <i class="pi pi-inbox text-4xl mb-2 text-gray-300"></i>
            <p>No items in cart</p>
          </div>
          <div v-else class="space-y-2 max-h-96 overflow-y-auto mb-4">
            <div v-for="item in cart" :key="item.branch_inventory_id" class="border rounded-lg p-3">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <p class="font-medium">{{ item.product_name }}</p>
                  <p class="text-sm text-gray-600">{{ money(item.unit_price) }}</p>
                  <p class="text-xs text-slate-500">{{ item.stock_available }} in inventory</p>
                </div>
                <Button 
                  text 
                  severity="danger" 
                  icon="pi pi-trash" 
                  :disabled="!canManagePos" 
                  @click="removeCart(item)"
                  size="small"
                />
              </div>
              <div class="flex w-full items-stretch" role="group" :aria-label="`Quantity for ${item.product_name}`">
                <Button
                  icon="pi pi-minus"
                  severity="secondary"
                  outlined
                  class="shrink-0 rounded-r-none"
                  :disabled="!canManagePos || item.quantity <= 1"
                  :aria-label="`Decrease ${item.product_name} quantity`"
                  @click="changeCartQuantity(item, -1)"
                />
                <InputNumber
                  v-model="item.quantity"
                  :min="1"
                  :max="item.stock_available"
                  :useGrouping="false"
                  inputClass="w-full text-center rounded-none"
                  class="min-w-0 flex-1 [&_.p-inputnumber-input]:rounded-none"
                  @update:model-value="normalizeCartQuantity(item)"
                />
                <Button
                  icon="pi pi-plus"
                  
                  class="shrink-0 rounded-l-none"
                  :disabled="!canManagePos || item.quantity >= item.stock_available"
                  :aria-label="`Increase ${item.product_name} quantity`"
                  @click="changeCartQuantity(item, 1)"
                />
              </div>
            </div>
          </div>

          <Divider />

          <!-- Delivery Option -->
          <div class="flex items-center gap-2 mb-3">
            <Checkbox v-model="deliveryRequired" binary inputId="delivery" />
            <label for="delivery" class="text-sm">For Delivery</label>
          </div>

          <!-- Customer Info Button -->
          <Button 
            label="Customer Information" 
            icon="pi pi-user" 
            severity="secondary" 
            outlined 
            @click="customerDialog = true"
            fluid
            class="mb-3"
          />
          
          <div v-if="customerForm.name || customerForm.phone" class="bg-gray-50 rounded-lg p-2 mb-3 text-sm">
            <i class="pi pi-user-circle mr-2"></i>
            {{ customerForm.name || 'Customer' }} · {{ customerForm.phone || 'No phone' }}
          </div>

          <!-- Payment Method -->
          <label for="payment-mode" class="mb-1 block text-sm font-medium text-gray-700">Payment Mode</label>
          <Select 
            inputId="payment-mode"
            v-model="paymentMethod" 
            :options="paymentOptions" 
            optionLabel="label" 
            optionValue="value" 
            fluid
            class="mb-3"
          />

          <!-- Amount Tendered (Non-GCash) -->
          <InputNumber 
            v-if="paymentMethod !== 'gcash'"
            v-model="amountTendered" 
            fluid 
            mode="currency" 
            currency="PHP" 
            :min="0"
            :invalid="tenderedAmountInvalid"
            placeholder="Amount tendered"
            class="mb-3"
          />
          <Message v-if="tenderedAmountInvalid" severity="error" :closable="false" class="-mt-2 mb-3">
            Amount tendered must be at least {{ money(total) }}.
          </Message>

          <!-- GCash Info -->
          <Message v-if="paymentMethod === 'gcash'"  class="mb-3">
            <i class="pi pi-info-circle mr-2"></i>
            GCash checkout opens after you submit. We will auto-refresh payment status.
          </Message>

          <!-- Totals -->
          <div class="bg-gray-50 rounded-lg p-3 mb-3">
            <div class="flex justify-between py-1 text-sm">
              <span>Subtotal</span>
              <span class="font-medium">{{ money(subtotal) }}</span>
            </div>
            <div v-if="deliveryRequired" class="flex justify-between py-1 text-sm">
              <span>Shipping fee</span>
              <span class="font-medium">{{ estimatingShipping ? 'Calculating...' : money(shippingFee) }}</span>
            </div>
            <div class="flex justify-between py-1 text-base font-semibold border-t mt-1 pt-2">
              <span>Total</span>
              <span class="text-blue-600">{{ money(total) }}</span>
            </div>
            <div v-if="paymentMethod !== 'gcash' && amountTendered" class="flex justify-between py-1 text-sm text-green-600">
              <span>Change</span>
              <span>{{ money(changeAmount) }}</span>
            </div>
          </div>

          <!-- Checkout Button -->
          <Button 
             
            fluid 
            :loading="checkingOut" 
            :disabled="!canManagePos || !cart.length || tenderedAmountInvalid || (paymentMethod !== 'gcash' && amountTendered === null)"
            label="Checkout"
            @click="checkout"
          />
        </template>
      </Card>
    </div>

    <!-- Customer Dialog -->
    <Dialog 
      v-model:visible="customerDialog" 
      header="Customer Information" 
      modal 
      :style="{ width: '90%', maxWidth: '800px' }"
      @show="initMap"
      @hide="destroyMap"
    >
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">Name</label>
          <InputText v-model="customerForm.name" fluid placeholder="Customer name" />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">Email</label>
          <InputText v-model="customerForm.email" type="email" fluid placeholder="Email address" />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">Phone</label>
          <InputMask
            v-model="customerForm.phone"
            mask="0999 999 9999"
            placeholder="09XX XXX XXXX"
            :autoClear="false"
            fluid
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">Province</label>
          <Select 
            v-model="addressSelection.provinceId" 
            :options="provinceOptions" 
             optionLabel="label" optionValue="value"
            filter 
            placeholder="Select Province" 
            fluid
            @change="onProvinceChange"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">City / Municipality</label>
          <Select 
            v-model="addressSelection.cityId" 
            :options="cityOptions" 
             optionLabel="label" optionValue="value"
            filter 
            placeholder="Select City" 
            fluid
            :disabled="!addressSelection.provinceId" 
            @change="onCityChange"
          />
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 block mb-1">Barangay</label>
          <Select 
            v-model="addressSelection.barangayCode" 
            :options="barangayOptions" 
             optionLabel="label" optionValue="value"
            filter 
            placeholder="Select Barangay" 
            fluid
            :disabled="!addressSelection.cityId"
          />
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium text-gray-700 block mb-1">Address Line</label>
          <Textarea v-model="customerForm.addressLine" rows="2" auto-resize fluid placeholder="Street, building, landmark" />
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium text-gray-700 block mb-1">Pin Location</label>
          <div class="flex gap-2 mb-2">
            <InputText v-model="addressSearchText" placeholder="Search address to pin" fluid />
            <Button text icon="pi pi-search" @click="geocodeCustomerAddress" />
          </div>
          <div ref="mapEl" class="h-64 w-full rounded-lg border border-gray-200"></div>
          <div class="text-xs text-gray-500 mt-2">
            Lat: {{ customerForm.latitude?.toFixed(6) || '-' }} · Lng: {{ customerForm.longitude?.toFixed(6) || '-' }}
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium text-gray-700 block mb-1">Delivery Notes</label>
          <Textarea v-model="customerForm.deliveryNotes" rows="2" auto-resize fluid placeholder="Gate, landmark, instructions (optional)" />
        </div>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" text @click="customerDialog = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import salesService from '@/services/sales.service'
import ecommerceService from '@/services/ecommerce.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Divider from 'primevue/divider'
import Checkbox from 'primevue/checkbox'
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Message from 'primevue/message'
import ConfirmDialog from 'primevue/confirmdialog'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useAuthStore } from '@/stores/auth'
import { onBeforeUnmount } from 'vue'
import type { Map as MapboxMap, Marker as MapboxMarker } from 'mapbox-gl'
import 'mapbox-gl/dist/mapbox-gl.css'
import { forwardGeocodeMapbox, requireMapboxToken } from '@/utils/mapbox'

const toast = useToast()
const confirm = useConfirm()
const router = useRouter()
const authStore = useAuthStore()
const search = ref('')
const products = ref<any[]>([])
const loadingProducts = ref(false)
const checkingOut = ref(false)
const cart = ref<any[]>([])
const paymentMethod = ref('cash')
const amountTendered = ref<number | null>(null)
const deliveryRequired = ref(false)
const shippingFee = ref(0)
const estimatingShipping = ref(false)
const customerDialog = ref(false)
const customerForm = ref({
  name: '',
  email: '',
  phone: '',
  addressLine: '',
  latitude: null as number | null,
  longitude: null as number | null,
  deliveryNotes: '',
})
const addressSearchText = ref('')
const addressSelection = ref({
  provinceId: null as string | null,
  cityId: null as string | null,
  barangayCode: null as string | null,
})
const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const provinceOptions = computed(() => provinces.value.map((p: any) => ({ label: p.name, value: p.province_id })))
const cityOptions = computed(() => cities.value.map((c: any) => ({ label: c.name, value: c.city_id })))
const barangayOptions = computed(() => barangays.value.map((b: any) => ({ label: b.name, value: b.code })))

const mapEl = ref<HTMLDivElement | null>(null)
let map: MapboxMap | null = null
let marker: MapboxMarker | null = null
let mapboxgl: typeof import('mapbox-gl').default | null = null
const paymentOptions = ref([
  { label: 'Cash', value: 'cash' },
  { label: 'Online Payment', value: 'card' },
])
const canManagePos = authStore.hasPermission('sales.pos.manage')

const getStockSeverity = (stock: number) => {
  if (stock <= 0) return 'danger'
  if (stock < 10) return 'warning'
  return 'success'
}

const loadProducts = async () => {
  loadingProducts.value = true
  try {
    const res = await salesService.getPosProducts({ per_page: 40, search: search.value || undefined })
    products.value = res?.data?.data || []
  } finally { loadingProducts.value = false }
}

const updateCartTotal = () => {
  cart.value = [...cart.value]
}

const normalizeCartQuantity = (item: any) => {
  item.quantity = Math.max(1, Math.min(Number(item.stock_available || 1), Number(item.quantity || 1)))
  updateCartTotal()
}

const changeCartQuantity = (item: any, amount: number) => {
  item.quantity = Number(item.quantity || 1) + amount
  normalizeCartQuantity(item)
}

const cartQuantity = (inventoryId: number) => Number(cart.value.find((item) => item.branch_inventory_id === inventoryId)?.quantity || 0)

const addToCart = (row: any) => {
  const id = row.id
  const existing = cart.value.find((i) => i.branch_inventory_id === id)
  if (existing) { 
    if (existing.quantity >= existing.stock_available) return
    existing.quantity += 1
    updateCartTotal()
    return 
  }
  cart.value.push({
    branch_inventory_id: id,
    product_name: row.product?.product_name || 'Product',
    unit_price: Number(row.product?.discounted_price || row.product?.base_price || 0),
    quantity: 1,
    stock_available: Number(row.quantity_available || 0),
  })
  updateCartTotal()
  
  toast.add({
    severity: 'success',
    summary: 'Added',
    detail: `${row.product?.product_name} added to cart`,
    life: 1500
  })
}

const removeCart = (item: any) => {
  if (!item) return
  const id = Number(item.branch_inventory_id || 0)
  if (!id) return

  confirm.require({
    header: 'Remove Item',
    message: `Remove ${item.product_name || 'this item'} from the cart?`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Remove',
    rejectLabel: 'Cancel',
    acceptClass: 'p-button-danger',
    accept: () => {
      cart.value = cart.value.filter((i) => i.branch_inventory_id !== id)
      updateCartTotal()
    },
  })
}

const subtotal = computed(() => cart.value.reduce((s, i) => s + (Number(i.unit_price) * Number(i.quantity || 0)), 0))
const total = computed(() => subtotal.value + (deliveryRequired.value ? shippingFee.value : 0))
const tenderedAmountInvalid = computed(() =>
  paymentMethod.value !== 'gcash'
  && amountTendered.value !== null
  && Number(amountTendered.value) < total.value
)
const changeAmount = computed(() => Math.max(0, Number(amountTendered.value || 0) - total.value))

let shippingEstimateRequest = 0
const updateShippingFee = async () => {
  const requestId = ++shippingEstimateRequest
  if (!deliveryRequired.value || !cart.value.length) {
    shippingFee.value = 0
    return
  }

  estimatingShipping.value = true
  try {
    const response = await salesService.estimatePosShipping({
      subtotal: subtotal.value,
      delivery_latitude: customerForm.value.latitude,
      delivery_longitude: customerForm.value.longitude,
    })
    if (requestId === shippingEstimateRequest) {
      shippingFee.value = Number(response?.data?.shipping_fee || 0)
    }
  } catch (error: any) {
    if (requestId === shippingEstimateRequest) {
      shippingFee.value = 0
      toast.add({ severity: 'error', summary: 'Shipping fee', detail: error?.response?.data?.message || 'Unable to calculate shipping fee.', life: 2500 })
    }
  } finally {
    if (requestId === shippingEstimateRequest) estimatingShipping.value = false
  }
}

const checkout = async () => {
  if (!cart.value.length) {
    toast.add({ severity: 'warn', summary: 'Empty Cart', detail: 'Please add items to cart first.', life: 2500 })
    return
  }

  if (paymentMethod.value !== 'gcash' && (amountTendered.value === null || Number(amountTendered.value) < total.value)) {
    return
  }
  
  if (deliveryRequired.value) {
    if (!customerForm.value.name || !customerForm.value.phone) {
      toast.add({ severity: 'warn', summary: 'Customer info', detail: 'Please complete customer name and phone.', life: 2500 })
      customerDialog.value = true
      return
    }
    if (!addressSelection.value.provinceId || !addressSelection.value.cityId || !addressSelection.value.barangayCode || !customerForm.value.addressLine) {
      toast.add({ severity: 'warn', summary: 'Delivery address', detail: 'Please complete the delivery address details.', life: 2500 })
      customerDialog.value = true
      return
    }
  }
  
  checkingOut.value = true
  try {
    const provinceLabel = provinceOptions.value.find(p => p.value === addressSelection.value.provinceId)?.label
    const cityLabel = cityOptions.value.find(c => c.value === addressSelection.value.cityId)?.label
    const barangayLabel = barangayOptions.value.find(b => b.value === addressSelection.value.barangayCode)?.label
    const deliveryAddress = deliveryRequired.value
      ? `${provinceLabel || ''}${provinceLabel ? ', ' : ''}${cityLabel || ''}${cityLabel ? ', ' : ''}${barangayLabel || ''}${barangayLabel ? ', ' : ''}${customerForm.value.addressLine}`.trim()
      : undefined

    const response = await salesService.checkout({
      customer_name: customerForm.value.name || undefined,
      customer_phone: customerForm.value.phone || undefined,
      payment_method: paymentMethod.value,
      amount_tendered: amountTendered.value || 0,
      return_url: window.location.href,
      delivery_required: deliveryRequired.value,
      delivery_address: deliveryAddress,
      delivery_notes: deliveryRequired.value ? customerForm.value.deliveryNotes : undefined,
      delivery_province: deliveryRequired.value ? provinceLabel : undefined,
      delivery_city: deliveryRequired.value ? cityLabel : undefined,
      delivery_barangay: deliveryRequired.value ? barangayLabel : undefined,
      delivery_address_line: deliveryRequired.value ? customerForm.value.addressLine : undefined,
      delivery_latitude: deliveryRequired.value ? customerForm.value.latitude : undefined,
      delivery_longitude: deliveryRequired.value ? customerForm.value.longitude : undefined,
      delivery_email: deliveryRequired.value ? customerForm.value.email : undefined,
      items: cart.value.map((i) => ({ branch_inventory_id: i.branch_inventory_id, quantity: i.quantity })),
    })
    const orderId = Number(response?.data?.id || 0)

    if (response?.checkout_url) {
      openCheckout(response.checkout_url)
      toast.add({ severity: 'info', summary: 'Checkout Opened', detail: 'Complete the payment in GCash.', life: 3000 })
    } else {
      toast.add({ severity: 'success', summary: 'Success', detail: 'POS checkout completed.', life: 2500 })
    }

    cart.value = []
    amountTendered.value = null
    deliveryRequired.value = false
    customerForm.value = { name: '', email: '', phone: '', addressLine: '', latitude: null, longitude: null, deliveryNotes: '' }
    addressSelection.value = { provinceId: null, cityId: null, barangayCode: null }
    if (orderId) {
      await router.push({ name: 'sales.pos.order-detail', params: { id: orderId } })
    } else {
      loadProducts()
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Checkout failed', detail: error?.response?.data?.message || 'Failed checkout.', life: 3000 })
  } finally { checkingOut.value = false }
}

const openCheckout = (url: string) => {
  if (!url) return
  window.open(url, '_blank', 'noopener')
}

const money = (v: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(v || 0))

watch(search, () => loadProducts())
watch([deliveryRequired, subtotal, () => customerForm.value.latitude, () => customerForm.value.longitude], updateShippingFee)
onMounted(async () => {
  loadProducts()
  try {
    const response = await salesService.getPosPaymentOptions()
    if (response?.data?.gcash) paymentOptions.value.push({ label: 'GCash', value: 'gcash' })
  } catch {
    // Cash and Card remain available when payment configuration cannot be loaded.
  }
  await fetchProvinces()
})

async function initMap() {
  if (!mapEl.value) return
  if (map) {
    map.resize()
    return
  }

  const startLat = customerForm.value.latitude ?? 14.5995
  const startLng = customerForm.value.longitude ?? 120.9842
  try {
    mapboxgl = (await import('mapbox-gl')).default
    mapboxgl.accessToken = requireMapboxToken()
    const currentMap = new mapboxgl.Map({ container: mapEl.value, style: 'mapbox://styles/mapbox/streets-v12', center: [startLng, startLat], zoom: 12 })
    map = currentMap
    await new Promise<void>((resolve) => currentMap.once('load', () => resolve()))
    if (map !== currentMap || !customerDialog.value) return
    marker = new mapboxgl.Marker({ draggable: true, color: '#f97316' }).setLngLat([startLng, startLat]).addTo(map)
    marker.on('dragend', () => {
      const position = marker?.getLngLat()
      if (!position) return
      customerForm.value.latitude = position.lat
      customerForm.value.longitude = position.lng
    })
    map.on('click', (event) => {
      marker?.setLngLat(event.lngLat)
      customerForm.value.latitude = event.lngLat.lat
      customerForm.value.longitude = event.lngLat.lng
    })
    map.resize()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Mapbox Unavailable', detail: error?.message || 'Unable to load the location map.', life: 3500 })
  }
}

const geocodeAddressText = async (addressText: string): Promise<{ latitude: number; longitude: number } | null> => {
  const query = String(addressText || '').trim()
  if (!query) return null

  try {
    const result = await forwardGeocodeMapbox(query)
    return result ? { latitude: result.latitude, longitude: result.longitude } : null
  } catch {
    return null
  }
}

async function geocodeCustomerAddress() {
  const built = `${customerForm.value.addressLine || ''}, ${barangayOptions.value.find(b => b.value === addressSelection.value.barangayCode)?.label || ''}, ${cityOptions.value.find(c => c.value === addressSelection.value.cityId)?.label || ''}, ${provinceOptions.value.find(p => p.value === addressSelection.value.provinceId)?.label || ''}, Philippines ${addressSearchText.value ? ` ${addressSearchText.value}` : ''}`
  const geocoded = await geocodeAddressText(built)
  if (!geocoded) {
    toast.add({ severity: 'warn', summary: 'Not Found', detail: 'Unable to find location for that address.' })
    return
  }
  customerForm.value.latitude = geocoded.latitude
  customerForm.value.longitude = geocoded.longitude
  if (marker) marker.setLngLat([geocoded.longitude, geocoded.latitude])
  if (map) map.flyTo({ center: [geocoded.longitude, geocoded.latitude], zoom: 14 })
}

async function fetchProvinces() {
  try {
    const response = await ecommerceService.getProvinces()
    provinces.value = response?.data || []
  } catch (error) {
    provinces.value = []
  }
}

async function fetchCities(provinceId: string) {
  try {
    const response = await ecommerceService.getCities(provinceId)
    cities.value = response?.data || []
  } catch (error) {
    cities.value = []
  }
}

async function fetchBarangays(cityId: string) {
  try {
    const response = await ecommerceService.getBarangays(cityId)
    barangays.value = response?.data || []
  } catch (error) {
    barangays.value = []
  }
}

async function onProvinceChange() {
  addressSelection.value.cityId = null
  addressSelection.value.barangayCode = null
  barangays.value = []
  if (addressSelection.value.provinceId) {
    await fetchCities(addressSelection.value.provinceId)
  }
}

async function onCityChange() {
  addressSelection.value.barangayCode = null
  if (addressSelection.value.cityId) {
    await fetchBarangays(addressSelection.value.cityId)
  }
}

const destroyMap = () => {
  if (map) {
    map.remove()
    map = null
    marker = null
  }
}

onBeforeUnmount(destroyMap)
</script>

<style scoped>
.pos-container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

@media (max-width: 768px) {
  .pos-container {
    padding: 16px;
  }
}

.mb-4 {
  margin-bottom: 20px;
}
</style>
