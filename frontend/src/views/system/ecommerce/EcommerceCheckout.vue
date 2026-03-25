<template>
  <div class="space-y-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Checkout</h1>
      </div>
      <Button label="Back to Cart" icon="pi pi-arrow-left" severity="secondary" class="w-full sm:w-auto" @click="goCart" />
    </div>

    <Card v-if="loading" class="border border-slate-200 shadow-none">
      <template #content>
        <Skeleton height="4rem" />
      </template>
    </Card>

    <Card v-else class="cursor-pointer border border-slate-200 shadow-none" @click="addressDrawerVisible = true">
      <template #content>
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Shipping Address</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ selectedAddress?.full_name || 'Select address template' }}</p>
            <p class="text-sm text-slate-600">{{ selectedAddress?.contact_number || '-' }}</p>
            <p class="text-sm text-slate-600">{{ selectedAddressSummary }}</p>
          </div>
          <Button icon="pi pi-chevron-right" text severity="secondary" />
        </div>
      </template>
    </Card>

    <div v-if="loading" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      <Card class="border border-slate-200 shadow-none lg:col-span-2">
        <template #content>
          <div class="space-y-3">
            <Skeleton v-for="idx in 3" :key="`checkout-left-${idx}`" height="5rem" />
          </div>
        </template>
      </Card>
      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-3">
            <Skeleton v-for="idx in 6" :key="`checkout-right-${idx}`" height="1.1rem" />
            <Skeleton height="2.5rem" />
          </div>
        </template>
      </Card>
    </div>

    <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      <Card class="border border-slate-200 shadow-none lg:col-span-2">
        <template #content>
          <div class="space-y-3">
            <div
              v-for="item in checkoutItems"
              :key="item.id"
              class="flex items-start justify-between gap-3 rounded-lg border border-slate-200 p-3"
            >
              <div class="flex min-w-0 items-start gap-3">
                <img
                  :src="item.image || '/F.svg'"
                  :alt="item.product_name"
                  class="h-14 w-14 rounded-lg border border-slate-200 object-cover"
                />
                <div class="min-w-0">
                  <p class="text-xs font-medium text-slate-500">Furni Shop</p>
                  <p class="truncate text-sm font-semibold text-slate-900">{{ item.product_name }}</p>
                  <p class="truncate text-xs text-slate-500">Variant: {{ item.sku || 'Standard' }}</p>
                  <p class="mt-1 text-xs text-slate-500">Delivery: PHP {{ shippingFeePerItem.toFixed(2) }} - {{ estimatedDeliveryDate }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-semibold text-slate-900">PHP {{ Number(item.unit_price).toFixed(2) }}</p>
                <p class="text-xs text-slate-500">Qty {{ item.quantity }}</p>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-none">
        <template #content>
          <div class="space-y-3 text-sm">
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment Method</p>
              <p class="mt-1 text-sm font-semibold text-slate-900">{{ paymentMethodLabel(selectedPaymentMethod) }}</p>
              <Button label="View all payment methods" size="small" link severity="info" @click="paymentDrawerVisible = true" />
            </div>

            <div class="rounded-lg border border-slate-200 p-3">
              <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Voucher</label>
              <div class="flex flex-col gap-2 sm:flex-row">
                <InputText v-model="voucherCode" fluid placeholder="Enter voucher code" />
                <Button label="Apply" size="small" severity="info" class="w-full sm:w-auto" :loading="applyingVoucher" @click="applyVoucher" />
              </div>
              <p v-if="appliedVoucher" class="mt-1 text-xs text-emerald-600">
                Applied: {{ appliedVoucher.code }} ({{ voucherLabel }})
              </p>
            </div>

            <Divider />
            <div class="flex justify-between"><span>Items</span><span>{{ itemsCount }}</span></div>
            <div class="flex justify-between"><span>Subtotal</span><span>PHP {{ subtotal.toFixed(2) }}</span></div>
            <div class="flex justify-between"><span>Shipping Fee</span><span>PHP {{ shippingFeeTotal.toFixed(2) }}</span></div>
            <div class="flex justify-between" v-if="discountAmount > 0">
              <span>Voucher Discount</span>
              <span class="text-emerald-600">- PHP {{ discountAmount.toFixed(2) }}</span>
            </div>
            <Divider />
            <div class="flex justify-between text-base font-bold"><span>Total</span><span>PHP {{ totalAmount.toFixed(2) }}</span></div>
            <Button label="Place Order" severity="info" class="mt-2 w-full" :loading="placing || paymongoCreating" @click="placeOrder" />
          </div>
        </template>
      </Card>
    </div>

    <Drawer v-model:visible="addressDrawerVisible" header="Shipping Address Templates" position="right" class="w-full sm:w-[30rem] lg:!w-[30rem]">
      <div class="space-y-3">
        <Button label="Add New Address" text severity="secondary" @click="showAddAddressForm = !showAddAddressForm" />
        <div v-for="address in addressTemplates" :key="address.id" class="rounded-lg border border-slate-200 p-3">
          <div class="flex items-start gap-2">
            <RadioButton v-model="selectedAddressId" :inputId="`address-${address.id}`" :value="address.id" />
            <label :for="`address-${address.id}`" class="cursor-pointer">
              <p class="text-sm font-semibold text-slate-900">{{ address.full_name }} - {{ address.contact_number }}</p>
              <p class="text-xs text-slate-600">{{ address.province }}, {{ address.city }}, {{ address.barangay }}, {{ address.address_line }}</p>
            </label>
          </div>
        </div>

        

        <div v-if="showAddAddressForm" class="space-y-2 rounded-lg border border-slate-200 p-3">
          <InputText v-model="newAddress.full_name" fluid placeholder="Full name" />
          <InputMask mask="9999-999-9999" v-model="newAddress.contact_number" fluid placeholder="0912-456-7890" />
          <Select
            v-model="newAddressSelection.provinceId"
            :options="provinceOptions"
            optionLabel="label"
            optionValue="value"
            fluid
            placeholder="Select Province"
            @change="onProvinceChange"
          />
          <Select
            v-model="newAddressSelection.cityId"
            :options="cityOptions"
            optionLabel="label"
            optionValue="value"
            fluid
            placeholder="Select City"
            :disabled="!newAddressSelection.provinceId"
            @change="onCityChange"
          />
          <Select
            v-model="newAddressSelection.barangayCode"
            :options="barangayOptions"
            optionLabel="label"
            optionValue="value"
            fluid
            placeholder="Select Barangay"
            :disabled="!newAddressSelection.cityId"
          />
          <Textarea v-model="newAddress.address_line" rows="2" fluid placeholder="Address line" />
          <Button label="Save Address Template" severity="info" @click="saveNewAddress" />
        </div>

        <Button label="Use Selected Address" severity="info" fluid @click="addressDrawerVisible = false" />
      </div>
    </Drawer>

    <Drawer v-model:visible="paymentDrawerVisible" header="All Payment Methods" position="right" class="w-full sm:w-[26rem]">
      <div class="space-y-3">
        <div v-for="method in allPaymentMethods" :key="method.value" class="rounded-lg border border-slate-200 p-3">
          <div class="flex items-center gap-2">
            <RadioButton v-model="selectedPaymentMethod" :inputId="`payment-${method.value}`" :value="method.value" />
            <label :for="`payment-${method.value}`" class="cursor-pointer text-sm text-slate-800">{{ method.label }}</label>
          </div>
        </div>
        <Button label="Use Payment Method" severity="info" fluid @click="paymentDrawerVisible = false" />
      </div>
    </Drawer>

    <Dialog
      v-model:visible="gcashModal.visible"
      modal
      :draggable="false"
      :closable="!gcashModal.processing"
      class="w-full max-w-md"
      :pt="{ root: { class: 'overflow-hidden' } }"
    >
      <template #header>
        <div class="flex w-full items-center gap-2 rounded-t-lg bg-blue-600 px-3 py-2 text-white">
          <i class="pi pi-wallet text-sm"></i>
          <span class="text-sm font-semibold">GCash Checkout Details</span>
        </div>
      </template>
      <div class="space-y-3 p-1">
        <Message severity="info" :closable="false">
          Enter your GCash number and receipt email before redirecting to PayMongo.
        </Message>
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">GCash Number</label>
          <InputMask
            v-model="gcashModal.phone"
            mask="09999999999"
            fluid
            placeholder="09999999999"
            :autoClear="false"
          />
        </div>
        <div>
          <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
          <InputText v-model="gcashModal.email" type="email" fluid placeholder="name@example.com" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined :disabled="gcashModal.processing" @click="gcashModal.visible = false" />
        <Button
          label="Continue to PayMongo"
          severity="info"
          :loading="gcashModal.processing"
          :disabled="gcashModal.processing"
          @click="proceedGcashCheckout"
        />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Drawer from 'primevue/drawer'
import RadioButton from 'primevue/radiobutton'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Dialog from 'primevue/dialog'
import ecommerceService from '@/services/ecommerce.service'
import paymongoService from '@/services/paymongo.service'
import { useAuthStore } from '@/stores/auth'
import InputMask from 'primevue/inputmask'

type AddressTemplate = {
  id: number
  full_name: string
  contact_number: string
  province: string
  city: string
  barangay: string
  address_line: string
  is_default?: boolean
}

type AppliedVoucher = {
  code: string
  discount_type: 'fixed' | 'percent'
  discount_value: number
}

const router = useRouter()
const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()

const placing = ref(false)
const paymongoCreating = ref(false)
const applyingVoucher = ref(false)
const loading = ref(false)
const checkoutItems = ref<any[]>([])
const selectedItemIds = ref<number[]>([])
const addressDrawerVisible = ref(false)
const paymentDrawerVisible = ref(false)
const showAddAddressForm = ref(false)
const pendingGcashIntentId = ref<string | null>(null)
const pendingGcashOrderId = ref<number | null>(null)
const customerLatitude = ref<number | null>(null)
const customerLongitude = ref<number | null>(null)

const gcashModal = reactive({
  visible: false,
  processing: false,
  phone: '',
  email: '',
})

const shippingFeePerItem = 120
const estimatedDeliveryDate = computed(() => {
  const date = new Date()
  date.setDate(date.getDate() + 5)
  return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
})

const addressTemplates = ref<AddressTemplate[]>([])
const selectedAddressId = ref<number | null>(null)
const newAddress = reactive<AddressTemplate>({
  id: 0,
  full_name: '',
  contact_number: '',
  province: '',
  city: '',
  barangay: '',
  address_line: '',
})
const newAddressSelection = reactive({
  provinceId: '',
  cityId: '',
  barangayCode: '',
})

const provinces = ref<any[]>([])
const cities = ref<any[]>([])
const barangays = ref<any[]>([])
const citiesCache = ref<Record<string, any[]>>({})

const selectedPaymentMethod = ref<'cod' | 'gcash' | 'card'>('cod')
const allPaymentMethods = [
  { label: 'Cash on Delivery (COD)', value: 'cod' as const },
  { label: 'GCash (PayMongo)', value: 'gcash' as const },
  { label: 'Credit Card', value: 'card' as const },
]

const voucherCode = ref('')
const appliedVoucher = ref<AppliedVoucher | null>(null)
const validatedDiscountAmount = ref(0)

const selectedAddress = computed(() => addressTemplates.value.find((a) => a.id === selectedAddressId.value) || null)
const selectedAddressSummary = computed(() =>
  selectedAddress.value
    ? `${selectedAddress.value.province}, ${selectedAddress.value.city}, ${selectedAddress.value.barangay}, ${selectedAddress.value.address_line}`
    : 'No shipping address selected.',
)
const voucherLabel = computed(() => {
  if (!appliedVoucher.value) return ''
  return appliedVoucher.value.discount_type === 'percent'
    ? `${appliedVoucher.value.discount_value}%`
    : `PHP ${Number(appliedVoucher.value.discount_value).toFixed(2)}`
})
const provinceOptions = computed(() => provinces.value.map((p: any) => ({ label: p.name, value: p.province_id })))
const cityOptions = computed(() => cities.value.map((c: any) => ({ label: c.name, value: c.city_id })))
const barangayOptions = computed(() => barangays.value.map((b: any) => ({ label: b.name, value: b.code })))

const itemsCount = computed(() => checkoutItems.value.reduce((sum, item) => sum + Number(item.quantity || 0), 0))
const subtotal = computed(() => checkoutItems.value.reduce((sum, item) => sum + Number(item.line_subtotal || 0), 0))
const shippingFeeTotal = computed(() => checkoutItems.value.length * shippingFeePerItem)
const discountAmount = computed(() => validatedDiscountAmount.value)
const totalAmount = computed(() => Math.max(0, subtotal.value + shippingFeeTotal.value - discountAmount.value))

function paymentMethodLabel(method: 'cod' | 'gcash' | 'card') {
  return allPaymentMethods.find((m) => m.value === method)?.label || 'Cash on Delivery (COD)'
}

async function loadAddressTemplates() {
  try {
    const response = await ecommerceService.getAddressTemplates()
    addressTemplates.value = response.data?.data || []
    selectedAddressId.value = addressTemplates.value.find((a) => a.is_default)?.id || addressTemplates.value[0]?.id || null
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Address', detail: error?.response?.data?.message || 'Failed to load addresses', life: 2500 })
  }
}

async function saveNewAddress() {
  if (
    !newAddress.full_name ||
    !newAddress.contact_number ||
    !newAddressSelection.provinceId ||
    !newAddressSelection.cityId ||
    !newAddressSelection.barangayCode ||
    !newAddress.address_line
  ) {
    toast.add({ severity: 'warn', summary: 'Incomplete', detail: 'Please complete all address fields.', life: 2200 })
    return
  }

  try {
    const provinceName = provinces.value.find((p: any) => p.province_id === newAddressSelection.provinceId)?.name || ''
    const cityName = cities.value.find((c: any) => c.city_id === newAddressSelection.cityId)?.name || ''
    const barangayName = barangays.value.find((b: any) => b.code === newAddressSelection.barangayCode)?.name || ''

    const response = await ecommerceService.createAddressTemplate({
      full_name: newAddress.full_name,
      contact_number: newAddress.contact_number,
      province: provinceName,
      city: cityName,
      barangay: barangayName,
      address_line: newAddress.address_line,
      is_default: addressTemplates.value.length === 0,
    })

    addressTemplates.value.push(response.data?.data)
    selectedAddressId.value = response.data?.data?.id || selectedAddressId.value

    newAddress.id = 0
    newAddress.full_name = ''
    newAddress.contact_number = ''
    newAddress.province = ''
    newAddress.city = ''
    newAddress.barangay = ''
    newAddress.address_line = ''
    newAddressSelection.provinceId = ''
    newAddressSelection.cityId = ''
    newAddressSelection.barangayCode = ''
    cities.value = []
    barangays.value = []
    showAddAddressForm.value = false
    toast.add({ severity: 'success', summary: 'Address Saved', detail: 'New address template has been saved.', life: 1800 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Address', detail: error?.response?.data?.message || 'Failed to save address', life: 2500 })
  }
}

async function loadCheckoutItems() {
  const response = await ecommerceService.getCart()
  const cart = response.data?.data
  const allItems = cart?.items || []

  selectedItemIds.value = String(route.query.item_ids || '')
    .split(',')
    .map((id) => Number(id.trim()))
    .filter((id) => Number.isInteger(id) && id > 0)

  checkoutItems.value = selectedItemIds.value.length
    ? allItems.filter((item: any) => selectedItemIds.value.includes(item.id))
    : allItems

  if (!checkoutItems.value.length) {
    toast.add({ severity: 'warn', summary: 'Cart Empty', detail: 'Please select cart items first.', life: 2500 })
    router.push({ name: 'ecommerce.cart' })
  }
}

async function applyVoucher() {
  if (!voucherCode.value.trim()) {
    toast.add({ severity: 'warn', summary: 'Voucher', detail: 'Enter voucher code first.', life: 1800 })
    return
  }

  applyingVoucher.value = true
  try {
    const response = await ecommerceService.validateVoucher({
      code: voucherCode.value.trim(),
      amount: subtotal.value + shippingFeeTotal.value,
    })

    const voucher = response.data?.data?.voucher
    const discount = Number(response.data?.data?.discount_amount || 0)

    appliedVoucher.value = {
      code: String(voucher?.code || voucherCode.value.trim().toUpperCase()),
      discount_type: voucher?.discount_type || 'fixed',
      discount_value: Number(voucher?.discount_value || 0),
    }
    validatedDiscountAmount.value = discount
    toast.add({ severity: 'success', summary: 'Voucher Applied', detail: 'Voucher has been applied.', life: 1800 })
  } catch (error: any) {
    appliedVoucher.value = null
    validatedDiscountAmount.value = 0
    toast.add({ severity: 'error', summary: 'Invalid Voucher', detail: error?.response?.data?.message || 'Voucher does not exist.', life: 2200 })
  } finally {
    applyingVoucher.value = false
  }
}

function toBackendPaymentMethod(method: 'cod' | 'gcash' | 'card') {
  if (method === 'gcash') return 'e_wallet'
  if (method === 'card') return 'card'
  return 'cod'
}

async function fetchProvinces() {
  try {
    const response = await ecommerceService.getProvinces()
    provinces.value = response.data || []
  } catch {
    provinces.value = []
  }
}

async function fetchCities(provinceId: string) {
  if (!provinceId) {
    cities.value = []
    return
  }

  if (citiesCache.value[provinceId]) {
    cities.value = citiesCache.value[provinceId]
    return
  }

  try {
    const response = await ecommerceService.getCities(provinceId)
    citiesCache.value[provinceId] = response.data || []
    cities.value = citiesCache.value[provinceId]
  } catch {
    cities.value = []
  }
}

async function fetchBarangays(cityId: string) {
  if (!cityId) {
    barangays.value = []
    return
  }

  try {
    const response = await ecommerceService.getBarangays(cityId)
    barangays.value = response.data || []
  } catch {
    barangays.value = []
  }
}

async function onProvinceChange() {
  newAddressSelection.cityId = ''
  newAddressSelection.barangayCode = ''
  barangays.value = []
  await fetchCities(newAddressSelection.provinceId)
}

async function onCityChange() {
  newAddressSelection.barangayCode = ''
  await fetchBarangays(newAddressSelection.cityId)
}

async function placeOrder() {
  if (!selectedAddress.value) {
    toast.add({ severity: 'warn', summary: 'Address Required', detail: 'Please select a shipping address.', life: 2500 })
    return
  }

  placing.value = true
  try {
    const payload = {
      shipping_name: selectedAddress.value.full_name,
      shipping_phone: selectedAddress.value.contact_number,
      shipping_email: authStore.user?.email || undefined,
      shipping_address: `${selectedAddress.value.province}, ${selectedAddress.value.city}, ${selectedAddress.value.barangay}, ${selectedAddress.value.address_line}`,
      payment_method: toBackendPaymentMethod(selectedPaymentMethod.value) as 'cod' | 'bank_transfer' | 'card' | 'e_wallet',
      shipping_fee: shippingFeeTotal.value,
      discount_amount: discountAmount.value,
      voucher_code: appliedVoucher.value?.code,
      notes: appliedVoucher.value ? `Voucher: ${appliedVoucher.value.code}` : '',
      item_ids: selectedItemIds.value.length ? selectedItemIds.value : undefined,
      customer_latitude: customerLatitude.value ?? undefined,
      customer_longitude: customerLongitude.value ?? undefined,
    }

    const response = await ecommerceService.checkout(payload)
    const orderId = response.data?.data?.id
    const orderStoreId = Number(response.data?.data?.store_id || checkoutItems.value?.[0]?.store_id || 0)

    if (selectedPaymentMethod.value === 'gcash') {
      if (!orderId || !orderStoreId) {
        throw new Error('Order ID or store ID is missing for PayMongo checkout.')
      }

      paymongoCreating.value = true
      const intentResponse = await paymongoService.createIntent({
        amount: Math.max(Math.round(totalAmount.value * 100), 1),
        payment_method_allowed: ['gcash'],
        store_id: orderStoreId,
        payable_type: 'ecommerce_order',
        payable_id: Number(orderId),
      })
      const intentId = intentResponse?.data?.data?.id
      if (!intentId) {
        throw new Error('Failed to initialize PayMongo payment intent.')
      }

      pendingGcashIntentId.value = String(intentId)
      pendingGcashOrderId.value = Number(orderId)
      gcashModal.phone = cleanPhoneNumber(selectedAddress.value.contact_number || '')
      gcashModal.email = authStore.user?.email || payload.shipping_email || ''
      gcashModal.visible = true
      toast.add({ severity: 'success', summary: 'Order Placed', detail: 'Enter GCash details to continue payment.', life: 2500 })
      return
    }

    toast.add({ severity: 'success', summary: 'Order Placed', detail: 'Your order was created successfully.', life: 2500 })
    router.push({ name: 'ecommerce.orders', query: { placed: orderId } })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Checkout Failed', detail: error?.response?.data?.message || 'Please try again.', life: 3000 })
  } finally {
    placing.value = false
    paymongoCreating.value = false
  }
}

function cleanPhoneNumber(raw: string) {
  const digits = String(raw || '').replace(/\D/g, '')
  if (!digits) return ''
  if (digits.startsWith('63') && digits.length >= 12) {
    return `0${digits.slice(2, 12)}`
  }
  if (digits.length > 11) {
    return digits.slice(digits.length - 11)
  }
  return digits
}

async function proceedGcashCheckout() {
  if (!pendingGcashIntentId.value || !pendingGcashOrderId.value) {
    toast.add({ severity: 'warn', summary: 'Missing Payment Context', detail: 'Please place the order again.', life: 2500 })
    return
  }

  const phone = cleanPhoneNumber(gcashModal.phone)
  if (!/^09\d{9}$/.test(phone)) {
    toast.add({ severity: 'warn', summary: 'Invalid Number', detail: 'Use an 11-digit GCash number (09XXXXXXXXX).', life: 2500 })
    return
  }
  if (!gcashModal.email.trim()) {
    toast.add({ severity: 'warn', summary: 'Email Required', detail: 'Please provide receipt email.', life: 2500 })
    return
  }

  gcashModal.processing = true
  paymongoCreating.value = true
  try {
    const gcashResponse = await paymongoService.startGcash(pendingGcashIntentId.value, {
      name: selectedAddress.value?.full_name || 'Customer',
      email: gcashModal.email.trim(),
      phone,
      return_url: `${window.location.origin}/shop/orders/${pendingGcashOrderId.value}`,
    })
    const redirectUrl = gcashResponse?.data?.redirect_url
    if (!redirectUrl) {
      throw new Error('PayMongo did not return a checkout URL.')
    }
    window.location.href = redirectUrl
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Checkout Failed', detail: error?.response?.data?.message || 'Unable to start GCash checkout.', life: 3200 })
  } finally {
    gcashModal.processing = false
    paymongoCreating.value = false
  }
}

function goCart() {
  router.push({ name: 'ecommerce.cart' })
}

onMounted(async () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        customerLatitude.value = Number(position.coords.latitude)
        customerLongitude.value = Number(position.coords.longitude)
      },
      () => {
        customerLatitude.value = null
        customerLongitude.value = null
      },
      { enableHighAccuracy: false, maximumAge: 120000, timeout: 5000 }
    )
  }

  loading.value = true
  try {
    await fetchProvinces()
    await loadAddressTemplates()
    await loadCheckoutItems()
  } finally {
    loading.value = false
  }
})
</script>
