<template>
  <main class="mx-auto max-w-5xl space-y-4 px-3 py-4 pb-28 text-sm sm:px-6 sm:py-6 sm:pb-8">
    <header class="flex items-center gap-3">
      <Button icon="pi pi-chevron-left" rounded aria-label="Go back" class="!h-10 !w-10 border border-slate-200 bg-white" @click="router.back()" />
      <div><h1 class="text-xl font-semibold text-slate-900">Exchange Order</h1><p class="text-xs text-slate-500">Choose an in-stock item from {{ branchName || 'your order’s issuing branch' }}.</p></div>
    </header>
    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <Card v-if="loading"><template #content><Skeleton height="7rem" /></template></Card>
    <template v-else-if="exchange">
      <Card class="border border-slate-200 shadow-none"><template #content>
        <div class="grid gap-3 sm:grid-cols-3">
          <div><p class="text-xs text-slate-500">Return</p><p class="font-semibold">{{ exchange.data.return_number }}</p></div>
          <div><p class="text-xs text-slate-500">Returned product value paid</p><p class="font-semibold">{{ money(exchange.original_paid_value) }}</p></div>
          <div><p class="text-xs text-slate-500">Quantity to exchange</p><p class="font-semibold">{{ exchange.quantity }}</p></div>
        </div>
      </template></Card>
      <section class="space-y-3">
        <div class="flex items-end justify-between"><div><h2 class="font-semibold text-slate-900">Available at {{ branchName }}</h2><p class="text-xs text-slate-500">Shipping fees from the original order are excluded.</p></div><span class="text-xs text-slate-500">{{ exchange.alternatives.length }} available</span></div>
        <div v-if="!exchange.alternatives.length" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">There are no eligible items in stock at this branch yet. Your return remains on hold.</div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <Card v-for="product in exchange.alternatives" :key="product.inventory_id" class="border border-slate-200 shadow-none">
            <template #content><div class="space-y-3">
              <div><p class="font-semibold text-slate-900">{{ product.product_name }}</p><p class="text-xs text-slate-500">{{ product.sku || 'Standard item' }} · {{ product.quantity_available }} in stock</p></div>
              <div class="flex justify-between text-xs"><span>Exchange total</span><strong>{{ money(product.total_price) }}</strong></div>
              <div v-if="product.remaining_value > 0" class="rounded-lg bg-amber-50 p-2 text-xs text-amber-900">Add {{ money(product.remaining_value) }} more to meet the returned item value.</div>
              <div v-else class="rounded-lg bg-emerald-50 p-2 text-xs text-emerald-800">{{ product.price_difference > 0 ? `Pay ${money(product.price_difference)} at checkout` : 'Equal value · no extra payment' }}</div>
              <Button :label="cart?.inventory_id === product.inventory_id ? 'Selected' : 'Add to exchange cart'" :icon="cart?.inventory_id === product.inventory_id ? 'pi pi-check' : 'pi pi-shopping-cart'" size="small" :severity="cart?.inventory_id === product.inventory_id ? 'secondary' : 'warn'" fluid :outlined="cart?.inventory_id === product.inventory_id" @click="cart = product" />
            </div></template>
          </Card>
        </div>
      </section>
      <Card v-if="cart" class="border border-orange-200 shadow-none">
        <template #content><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div><p class="font-semibold">Exchange cart: {{ cart.product_name }}</p><p class="text-xs text-slate-600">{{ cart.quantity }} item(s) · {{ money(cart.total_price) }} <span v-if="cart.price_difference > 0">· Due now {{ money(cart.price_difference) }}</span></p></div>
          <Button :label="cart.remaining_value > 0 ? `Add ${money(cart.remaining_value)} more` : exchange.data.replacement_status === 'awaiting_payment' ? 'Continue payment' : cart.price_difference > 0 ? 'Continue to checkout' : 'Confirm equal-value exchange'" severity="warn" :disabled="cart.remaining_value > 0" :loading="submitting" @click="checkout" />
        </div></template>
      </Card>
      <div v-if="cart && cart.price_difference > 0" class="mx-auto max-w-md">
        <label class="mb-1 block text-xs font-medium text-slate-700">Payment method</label>
        <Select v-model="selectedPaymentMethod" :options="paymentOptions" optionLabel="label" optionValue="value" size="small" fluid :disabled="paymentMethodsLoading" placeholder="Choose a payment method" />
        <p v-if="paymentError" class="mt-1 text-xs text-red-600">{{ paymentError }}</p>
      </div>
    </template>
  </main>
</template>

<script setup lang="ts">
import axiosClient from '@/axios'
import paymongoService from '@/services/paymongo.service'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const exchange = ref<any>(null)
const cart = ref<any>(null)
const branchName = computed(() => exchange.value?.branch?.name || 'issuing branch')
const allowedPaymentMethods = ref<string[]>([])
const selectedPaymentMethod = ref('')
const paymentMethodsLoading = ref(false)
const paymentError = ref('')
const paymentOptions = computed(() => [
  { value: 'gcash', label: 'GCash' }, { value: 'paymaya', label: 'Maya' }, { value: 'card', label: 'Card' }, { value: 'qrph', label: 'QR Ph' },
].filter((option) => allowedPaymentMethods.value.includes(option.value)))
const money = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))

async function load() {
  loading.value = true
  try {
    const id = Number(route.params.returnId)
    const result = await axiosClient.get(`/api/ecommerce/exchange-orders/${id}`)
    exchange.value = result.data
    if (exchange.value.data.replacement_status === 'reserved') {
      await router.replace({ name: 'ecommerce.order-detail', params: { id: exchange.value.data.order_id } })
      return
    }
    if (exchange.value.selected_replacement) cart.value = exchange.value.selected_replacement
    if (Number(exchange.value.data.replacement_price_difference || 0) > 0 || !exchange.value.selected_replacement) await loadPaymentMethods()
    if (route.query.paymongo === 'success' && Number(exchange.value?.data?.replacement_price_difference || 0) > 0) {
      await paymongoService.getLatestIntentByPayable('replacement_upgrade', id, { sync: true })
      await router.replace({ name: 'ecommerce.order-detail', params: { id: exchange.value.data.order_id } })
    }
  } catch (cause: any) {
    error.value = cause?.response?.data?.message || 'This exchange order is not available.'
  } finally { loading.value = false }
}

async function loadPaymentMethods() {
  const storeId = Number(exchange.value?.data?.store_id || 0)
  if (!storeId) return
  paymentMethodsLoading.value = true
  try {
    const response = await paymongoService.getAvailableCheckoutMethods(storeId)
    allowedPaymentMethods.value = Array.isArray(response?.data?.methods) ? response.data.methods : []
    if (!selectedPaymentMethod.value || !allowedPaymentMethods.value.includes(selectedPaymentMethod.value)) selectedPaymentMethod.value = allowedPaymentMethods.value[0] || ''
    if (!allowedPaymentMethods.value.length) paymentError.value = 'No online payment methods are available for this store right now.'
  } catch (cause: any) {
    paymentError.value = cause?.response?.data?.message || 'Unable to load payment methods.'
  } finally { paymentMethodsLoading.value = false }
}

async function checkout() {
  if (!cart.value || cart.value.remaining_value > 0 || submitting.value) return
  submitting.value = true
  error.value = ''
  try {
    const id = Number(route.params.returnId)
    const alreadyReserved = exchange.value.data.replacement_status === 'awaiting_payment'
    if (cart.value.price_difference > 0 && !selectedPaymentMethod.value) throw new Error(paymentError.value || 'Choose an available payment method.')
    const result = alreadyReserved ? { data: { payment_required: true, store_id: exchange.value.data.store_id, amount: Math.round(Number(exchange.value.data.replacement_price_difference || 0) * 100) } }
      : await axiosClient.post(`/api/ecommerce/exchange-orders/${id}/checkout`, { inventory_id: cart.value.inventory_id, payment_method: selectedPaymentMethod.value || 'gcash' })
    if (result.data.payment_required) {
      const returnPath = router.resolve({ name: 'ecommerce.exchange-order', params: { returnId: id } }).href
      const successUrl = new URL(returnPath, window.location.origin)
      successUrl.searchParams.set('paymongo', 'success')
      const cancelUrl = new URL(returnPath, window.location.origin)
      cancelUrl.searchParams.set('paymongo', 'cancel')
      const response = await paymongoService.createCheckoutSession({ amount: result.data.amount, currency: 'PHP', description: `Exchange order ${exchange.value.data.return_number}`, payment_method_allowed: [selectedPaymentMethod.value], store_id: result.data.store_id, payable_type: 'replacement_upgrade', payable_id: id, success_url: successUrl.toString(), cancel_url: cancelUrl.toString() })
      const checkoutUrl = String(response?.data?.checkout_url || '')
      if (!checkoutUrl.startsWith('https://checkout.paymongo.com/')) throw new Error('PayMongo did not return a valid checkout page.')
      window.location.assign(checkoutUrl)
      return
    }
    await router.push({ name: 'ecommerce.order-detail', params: { id: exchange.value.data.order_id } })
  } catch (cause: any) { error.value = cause?.response?.data?.message || cause?.message || 'Unable to start exchange checkout.' }
  finally { submitting.value = false }
}

onMounted(load)
</script>
