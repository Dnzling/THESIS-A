<template>
  <div class="pos-container">

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
                  <div class="text-xs text-gray-500">{{ data.product?.sku }}</div>
                </div>
              </template>
            </Column>
            <Column field="quantity_available" header="Stock">
              <template #body="{ data }">
                <Tag 
                  :value="data.quantity_available" 
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
                  severity="info" 
                  icon="pi pi-plus" 
                  :disabled="!canManagePos" 
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
            <i class="pi pi-shopping-cart text-blue-500"></i>
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
                </div>
                <Button 
                  text 
                  severity="danger" 
                  icon="pi pi-trash" 
                  :disabled="!canManagePos" 
                  @click="removeCart(item.branch_inventory_id)"
                  size="small"
                />
              </div>
              <InputNumber 
                v-model="item.quantity" 
                :min="1" 
                showButtons
                buttonLayout="horizontal"
                :step="1"
                fluid
                @update:model-value="updateCartTotal"
              />
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
          <Select 
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
            placeholder="Amount tendered"
            class="mb-3"
          />

          <!-- GCash Info -->
          <Message v-if="paymentMethod === 'gcash'" severity="info" class="mb-3">
            <i class="pi pi-info-circle mr-2"></i>
            GCash checkout opens after you submit. We will auto-refresh payment status.
          </Message>

          <!-- Totals -->
          <div class="bg-gray-50 rounded-lg p-3 mb-3">
            <div class="flex justify-between py-1 text-sm">
              <span>Subtotal</span>
              <span class="font-medium">{{ money(subtotal) }}</span>
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
            severity="info" 
            fluid 
            :loading="checkingOut" 
            :disabled="!canManagePos || !cart.length"
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
          <InputText v-model="customerForm.phone" fluid placeholder="Contact number" />
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
import salesService from '@/services/sales.service'
import ecommerceService from '@/services/ecommerce.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Select from 'primevue/select'
import Divider from 'primevue/divider'
import Checkbox from 'primevue/checkbox'
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Message from 'primevue/message'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import { onBeforeUnmount, nextTick } from 'vue'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'

const toast = useToast()
const authStore = useAuthStore()
const search = ref('')
const products = ref<any[]>([])
const loadingProducts = ref(false)
const checkingOut = ref(false)
const cart = ref<any[]>([])
const paymentMethod = ref('cash')
const amountTendered = ref<number | null>(null)
const deliveryRequired = ref(false)
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
let map: L.Map | null = null
let marker: L.Marker | null = null
const paymentOptions = [
  { label: 'Cash', value: 'cash' },
  { label: 'Card', value: 'card' },
  { label: 'GCash', value: 'gcash' },
  { label: 'Cash On Delivery', value: 'cod' },
]
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

const addToCart = (row: any) => {
  const id = row.id
  const existing = cart.value.find((i) => i.branch_inventory_id === id)
  if (existing) { 
    existing.quantity += 1
    updateCartTotal()
    return 
  }
  cart.value.push({
    branch_inventory_id: id,
    product_name: row.product?.product_name || 'Product',
    unit_price: Number(row.product?.discounted_price || row.product?.base_price || 0),
    quantity: 1,
  })
  updateCartTotal()
  
  toast.add({
    severity: 'success',
    summary: 'Added',
    detail: `${row.product?.product_name} added to cart`,
    life: 1500
  })
}

const removeCart = (id: number) => { 
  cart.value = cart.value.filter((i) => i.branch_inventory_id !== id)
  updateCartTotal()
}

const subtotal = computed(() => cart.value.reduce((s, i) => s + (Number(i.unit_price) * Number(i.quantity || 0)), 0))
const total = computed(() => subtotal.value)
const changeAmount = computed(() => Math.max(0, Number(amountTendered.value || 0) - total.value))

const checkout = async () => {
  if (!cart.value.length) {
    toast.add({ severity: 'warn', summary: 'Empty Cart', detail: 'Please add items to cart first.', life: 2500 })
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
    loadProducts()
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
onMounted(async () => {
  loadProducts()
  await fetchProvinces()
})

watch(customerDialog, async (visible) => {
  if (!visible) return
  await nextTick()
  initMap()
})

function initMap() {
  if (!mapEl.value) return
  if (map) {
    map.invalidateSize()
    return
  }

  L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
  })

  const startLat = customerForm.value.latitude ?? 14.5995
  const startLng = customerForm.value.longitude ?? 120.9842
  map = L.map(mapEl.value).setView([startLat, startLng], 12)

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors',
    maxZoom: 19,
  }).addTo(map)

  marker = L.marker([startLat, startLng], { draggable: true }).addTo(map)
  marker.on('dragend', () => {
    const pos = marker?.getLatLng()
    if (!pos) return
    customerForm.value.latitude = pos.lat
    customerForm.value.longitude = pos.lng
  })

  map.on('click', (e: L.LeafletMouseEvent) => {
    marker?.setLatLng(e.latlng)
    customerForm.value.latitude = e.latlng.lat
    customerForm.value.longitude = e.latlng.lng
  })
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

onBeforeUnmount(() => {
  if (map) {
    map.remove()
    map = null
    marker = null
  }
})
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