<template>
  <div class="space-y-5 pb-8">
    <ConfirmDialog />
    <section class="rounded-2xl border border-orange-100 bg-white p-5 shadow-sm sm:p-7">
      <p class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-600">For business buyers</p>
      <h1 class="mt-2 text-2xl font-bold text-slate-900">Wholesale access</h1>
      <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
        Request wholesale access from a store that offers it. Each store reviews applications separately;
        using the same email as a previous in-store purchase does not automatically grant wholesale pricing.
      </p>
    </section>

    <Message v-if="!authStore.isAuthenticated" severity="info" :closable="false">
      Sign in to apply. You can browse stores before signing in.
    </Message>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
      <div class="mb-4 flex items-center justify-between gap-3">
        <div>
          <h2 class="text-base font-semibold text-slate-900">Stores accepting applications</h2>
          <p class="mt-1 text-xs text-slate-500">Approved access is specific to the store that reviewed your request.</p>
        </div>
        <Button icon="pi pi-refresh" text rounded severity="secondary" aria-label="Refresh stores" :loading="loading" @click="load" />
      </div>

      <div v-if="loading" class="grid gap-3 md:grid-cols-2">
        <div v-for="n in 4" :key="n" class="rounded-xl border border-slate-100 p-4">
          <Skeleton height="1.25rem" width="60%" class="mb-3" />
          <Skeleton height=".9rem" width="35%" />
        </div>
      </div>
      <div v-else-if="!stores.length" class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center">
        <i class="pi pi-shop text-2xl text-slate-300" />
        <p class="mt-2 text-sm font-medium text-slate-700">No stores are accepting wholesale applications right now.</p>
      </div>
      <div v-else class="grid gap-3 md:grid-cols-2">
        <article v-for="store in stores" :key="store.id" class="flex flex-col justify-between gap-4 rounded-xl border border-slate-200 p-4">
          <div>
            <div class="flex items-start justify-between gap-2">
              <h3 class="font-semibold text-slate-900">{{ store.name }}</h3>
              <Badge v-if="requestForStore(store.id)" :value="statusLabel(requestForStore(store.id).status)" :severity="statusSeverity(requestForStore(store.id).status)" />
            </div>
            <p class="mt-1 text-xs text-slate-500">{{ [store.city, store.province].filter(Boolean).join(', ') || 'Location not listed' }}</p>
            <p v-if="requestForStore(store.id)?.review_note" class="mt-3 rounded-lg bg-slate-50 p-3 text-xs text-slate-600">{{ requestForStore(store.id).review_note }}</p>
          </div>
          <Button v-if="requestForStore(store.id)?.status === 'approved'" label="Choose products" icon="pi pi-shopping-bag" severity="warn" size="small" @click="openProductPicker(store)" />
          <Button v-else-if="requestForStore(store.id)?.status === 'pending'" label="Request pending" icon="pi pi-clock" severity="secondary" outlined size="small" disabled />
          <Button v-else :label="requestForStore(store.id)?.status === 'rejected' ? 'Apply again' : 'Request wholesale access'" icon="pi pi-briefcase" severity="warn" size="small" @click="startApplication(store)" />
        </article>
      </div>
    </section>

    <section v-if="authStore.isAuthenticated && quoteRequests.length" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
      <h2 class="text-base font-semibold text-slate-900">Your product quote requests</h2>
      <p class="mt-1 text-xs text-slate-500">A store will review your requested items and prepare its prices and fulfillment terms.</p>
      <div class="mt-3 divide-y divide-slate-100">
        <article v-for="request in quoteRequests" :key="request.id" class="flex flex-wrap items-center justify-between gap-3 py-3">
          <div class="min-w-0">
            <p class="font-semibold text-slate-900">{{ request.request_number }} <span class="font-normal text-slate-500">· {{ request.store_name }}</span></p>
            <p class="mt-1 text-xs text-slate-500">{{ requestItemsLabel(request.items) }} · {{ dateLabel(request.created_at) }}</p>
          </div>
          <Badge :value="statusLabel(request.status)" :severity="request.status === 'quoted' ? 'success' : request.status === 'cancelled' ? 'secondary' : 'warn'" />
        </article>
      </div>
    </section>

    <section v-if="authStore.isAuthenticated && customerQuotes.length" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
      <div class="mb-4">
        <h2 class="text-base font-semibold text-slate-900">Wholesale quotations linked to your account</h2>
        <p class="mt-1 text-xs text-slate-500">Quotes created by a store for your approved account, including in-store requests using your registered email.</p>
      </div>
      <div class="divide-y divide-slate-100">
        <div v-for="quote in customerQuotes" :key="quote.id" class="flex flex-wrap items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
          <div>
            <p class="font-semibold text-slate-900">{{ quote.quote_number }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ quote.store_name }} · {{ dateLabel(quote.created_at) }}</p>
            <div v-for="(item, index) in parseItems(quote.items)" :key="`${item.product_id || item.sku}:${index}`" class="mt-2 text-xs text-slate-600">
              {{ item.quantity }} × {{ item.product_name }}<span v-if="item.variant_name"> · {{ item.variant_name }}</span>
              <span class="ml-2 font-medium">{{ money(item.line_total ?? ((Number(item.unit_price) || 0) * (Number(item.quantity) || 0))) }}</span>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ quote.delivery_required ? 'Delivery' : 'Store pickup' }}<span v-if="quote.delivery_required && quote.delivery_address"> · {{ quote.delivery_address }}</span><span v-if="quote.payment_terms"> · {{ paymentTermLabel(quote.payment_terms) }}</span></p>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-slate-900">{{ money(Number(quote.subtotal || 0) + Number(quote.shipping_fee || 0)) }}</span>
            <Badge :value="statusLabel(quote.status)" :severity="statusSeverity(quote.status)" />
            <Badge v-if="quote.customer_response" :value="`You ${quote.customer_response}`" :severity="quote.customer_response === 'accepted' ? 'success' : 'secondary'" />
          </div>
          <div v-if="quote.customer_request_id && quote.status === 'approved' && !quote.customer_response" class="w-full flex justify-end gap-2">
            <Button label="Decline" severity="secondary" outlined size="small" :disabled="quoteResponding === quote.id" @click="confirmQuoteResponse(quote, 'declined')" />
            <Button label="Accept quote" severity="warn" size="small" :loading="quoteResponding === quote.id" @click="confirmQuoteResponse(quote, 'accepted')" />
          </div>
        </div>
      </div>
    </section>

    <Dialog v-model:visible="applicationOpen" modal header="Request wholesale access" :style="{ width: 'min(94vw, 32rem)' }">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">Application for <strong class="text-slate-900">{{ selectedStore?.name }}</strong>. The store will review your business details.</p>
        <div>
          <label for="wholesale-business-name" class="mb-1 block text-sm font-medium text-slate-700">Business name <span class="text-red-500">*</span></label>
          <InputText id="wholesale-business-name" v-model="form.business_name" fluid maxlength="255" placeholder="Registered or trading name" />
        </div>
        <div>
          <label for="wholesale-request-note" class="mb-1 block text-sm font-medium text-slate-700">Message to the store <span class="text-slate-400">(optional)</span></label>
          <Textarea id="wholesale-request-note" v-model="form.request_note" fluid rows="3" maxlength="1000" placeholder="Briefly describe your business or purchasing needs" />
        </div>
        <Message v-if="formError" severity="error" :closable="false">{{ formError }}</Message>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined :disabled="submitting" @click="applicationOpen = false" />
        <Button label="Send request" icon="pi pi-send" severity="warn" :loading="submitting" :disabled="!form.business_name.trim()" @click="submitApplication" />
      </template>
    </Dialog>

    <Dialog v-model:visible="productPickerOpen" modal :header="`Choose products · ${selectedStore?.name || ''}`" :style="{ width: 'min(96vw, 58rem)' }" :breakpoints="{ '640px': '96vw' }">
      <div class="space-y-4">
        <Message severity="info" :closable="false">Select the products or variants you want priced. The store will confirm availability, final pricing, and pickup or delivery details in its quotation.</Message>
        <div v-if="productLoading" class="grid gap-3 sm:grid-cols-2">
          <div v-for="n in 4" :key="n" class="flex gap-3 rounded-xl border border-slate-100 p-3"><Skeleton width="5rem" height="5rem" /><div class="flex-1 space-y-2"><Skeleton width="75%" /><Skeleton width="55%" /></div></div>
        </div>
        <div v-else-if="!storeProducts.length" class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">No available products are listed for wholesale quotation right now.</div>
        <div v-else class="grid max-h-[48vh] gap-3 overflow-y-auto pr-1 sm:grid-cols-2">
          <article v-for="product in storeProducts" :key="productKey(product)" class="flex gap-3 rounded-xl border border-slate-200 p-3">
            <img v-if="product.image_url" :src="product.image_url" :alt="product.product_name" class="h-20 w-20 shrink-0 rounded-lg bg-slate-50 object-contain" />
            <div v-else class="flex h-20 w-20 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-slate-300"><i class="pi pi-image text-xl" /></div>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-slate-900">{{ product.product_name }}</p>
              <p v-if="product.variant_name || product.variant_specs" class="truncate text-xs text-slate-600">{{ product.variant_name || product.variant_specs }}</p>
              <p class="truncate text-xs text-slate-500">SKU {{ product.sku || '—' }} · {{ product.available_stock }} available</p>
              <p class="mt-1 text-sm font-semibold text-slate-800">{{ money(product.reference_price) }} <span class="font-normal text-slate-500">retail reference</span></p>
              <div v-if="selectedRequestItem(product)" class="mt-2 flex items-center gap-2">
                <label class="text-xs text-slate-600">Qty</label>
                <InputNumber v-model="selectedRequestItem(product).quantity" :min="1" :max="10000" :useGrouping="false" size="small" class="w-24" />
                <Button icon="pi pi-trash" text rounded severity="danger" aria-label="Remove product" @click="removeRequestItem(product)" />
              </div>
              <Button v-else label="Add to request" icon="pi pi-plus" text size="small" class="mt-1" @click="addRequestItem(product)" />
            </div>
          </article>
        </div>
        <div v-if="selectedRequestItems.length" class="space-y-2 border-t border-slate-100 pt-3">
          <label for="wholesale-customer-note" class="block text-sm font-medium text-slate-700">Note to the store (optional)</label>
          <Textarea id="wholesale-customer-note" v-model="quoteRequestNote" rows="2" fluid maxlength="2000" placeholder="Mention quantities, preferred fulfillment, or questions" />
          <p class="text-xs text-slate-500">{{ selectedRequestItems.length }} distinct item(s), {{ selectedRequestItems.reduce((sum, row) => sum + Number(row.quantity || 0), 0) }} total unit(s)</p>
        </div>
        <Message v-if="quoteRequestError" severity="error" :closable="false">{{ quoteRequestError }}</Message>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined :disabled="quoteSubmitting" @click="productPickerOpen = false" />
        <Button label="Send quote request" icon="pi pi-send" severity="warn" :loading="quoteSubmitting" :disabled="productLoading || !selectedRequestItems.length" @click="submitQuoteRequest" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import axiosClient from '@/axios'
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import ConfirmDialog from 'primevue/confirmdialog'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'

defineOptions({ layout: EcommerceMobileWrapper })

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()
const loading = ref(false)
const submitting = ref(false)
const stores = ref<any[]>([])
const requests = ref<any[]>([])
const customerQuotes = ref<any[]>([])
const quoteResponding = ref<number | null>(null)
const quoteRequests = ref<any[]>([])
const storeProducts = ref<any[]>([])
const selectedRequestItems = ref<any[]>([])
const productPickerOpen = ref(false)
const productLoading = ref(false)
const quoteSubmitting = ref(false)
const quoteRequestNote = ref('')
const quoteRequestError = ref('')
const applicationOpen = ref(false)
const selectedStore = ref<any | null>(null)
const formError = ref('')
const form = reactive({ business_name: '', request_note: '' })

function requestForStore(storeId: number) {
  return requests.value.find((request) => Number(request.store_id) === Number(storeId))
}

function statusLabel(status: string) {
  return String(status || '').replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

function statusSeverity(status: string) {
  return status === 'approved' ? 'success' : status === 'rejected' ? 'danger' : 'warn'
}

function dateLabel(value: string) {
  return value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(new Date(value)) : 'Date unavailable'
}

function money(value: number) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value || 0)
}

function parseItems(value: any) {
  try { return typeof value === 'string' ? JSON.parse(value) : (value || []) } catch { return [] }
}

function paymentTermLabel(value: string) {
  return value === 'bank_transfer' ? 'Bank transfer' : 'Online payment'
}

function confirmQuoteResponse(quote: any, response: 'accepted' | 'declined') {
  confirm.require({
    header: `${response === 'accepted' ? 'Accept' : 'Decline'} this quotation?`,
    message: response === 'accepted'
      ? 'The store will be notified and can continue processing your order.'
      : 'The store will be notified that you do not want to proceed with these quoted terms.',
    rejectProps: { label: 'Keep Reviewing', severity: 'secondary', outlined: true },
    acceptProps: { label: response === 'accepted' ? 'Accept Quotation' : 'Decline Quotation', severity: 'warn' },
    accept: async () => {
      quoteResponding.value = Number(quote.id)
      try {
        await axiosClient.post(`/api/ecommerce/wholesale/quotes/${quote.id}/response`, { response })
        toast.add({ severity: 'success', summary: 'Response sent', detail: 'The store has been notified of your decision.', life: 2500 })
        const result = await axiosClient.get('/api/ecommerce/wholesale/quotes')
        customerQuotes.value = result.data?.data || []
      } catch (error: any) {
        toast.add({ severity: 'error', summary: 'Unable to send response', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
      } finally {
        quoteResponding.value = null
      }
    },
  })
}

async function load() {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/public/wholesale/stores')
    stores.value = response.data?.data || []
    if (authStore.isAuthenticated) {
      const ownRequests = await axiosClient.get('/api/ecommerce/wholesale/requests')
      requests.value = ownRequests.data?.data || []
      const ownQuotes = await axiosClient.get('/api/ecommerce/wholesale/quotes')
      customerQuotes.value = ownQuotes.data?.data || []
      const ownQuoteRequests = await axiosClient.get('/api/ecommerce/wholesale/quote-requests')
      quoteRequests.value = ownQuoteRequests.data?.data || []
    } else {
      requests.value = []
      customerQuotes.value = []
      quoteRequests.value = []
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to load wholesale stores', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
  } finally {
    loading.value = false
  }
}

function productKey(product: any) {
  return `${product.product_id}:${product.variation_id || 'standard'}`
}

function selectedRequestItem(product: any) {
  return selectedRequestItems.value.find((item) => item.key === productKey(product))
}

function addRequestItem(product: any) {
  selectedRequestItems.value.push({
    key: productKey(product),
    product_id: product.product_id,
    variation_id: product.variation_id,
    product_name: product.product_name,
    variant_name: product.variant_name,
    sku: product.sku,
    quantity: 1,
  })
}

function removeRequestItem(product: any) {
  selectedRequestItems.value = selectedRequestItems.value.filter((item) => item.key !== productKey(product))
}

function requestItemsLabel(items: any) {
  try {
    const parsed = typeof items === 'string' ? JSON.parse(items) : items
    return `${Array.isArray(parsed) ? parsed.length : 0} requested item(s)`
  } catch { return 'Requested items' }
}

async function openProductPicker(store: any) {
  if (!authStore.isAuthenticated) {
    router.push({ name: 'customer.login', query: { redirect: '/wholesale' } })
    return
  }
  selectedStore.value = store
  selectedRequestItems.value = []
  quoteRequestNote.value = ''
  quoteRequestError.value = ''
  productPickerOpen.value = true
  productLoading.value = true
  try {
    const response = await axiosClient.get(`/api/ecommerce/wholesale/stores/${store.id}/products`)
    storeProducts.value = response.data?.data || []
  } catch (error: any) {
    quoteRequestError.value = error.response?.data?.message || 'Unable to load the store product list.'
  } finally {
    productLoading.value = false
  }
}

async function submitQuoteRequest() {
  if (!selectedStore.value || !selectedRequestItems.value.length || quoteSubmitting.value) return
  quoteSubmitting.value = true
  quoteRequestError.value = ''
  try {
    await axiosClient.post(`/api/ecommerce/wholesale/stores/${selectedStore.value.id}/quote-requests`, {
      items: selectedRequestItems.value.map(({ product_id, variation_id, quantity }) => ({ product_id, variation_id, quantity })),
      customer_note: quoteRequestNote.value.trim() || null,
    })
    productPickerOpen.value = false
    toast.add({ severity: 'success', summary: 'Quote request sent', detail: 'The store will review your items and prepare a quotation.', life: 3000 })
    const response = await axiosClient.get('/api/ecommerce/wholesale/quote-requests')
    quoteRequests.value = response.data?.data || []
  } catch (error: any) {
    quoteRequestError.value = error.response?.data?.message || 'Unable to send your quote request.'
  } finally {
    quoteSubmitting.value = false
  }
}

function startApplication(store: any) {
  if (!authStore.isAuthenticated) {
    router.push({ name: 'customer.login', query: { redirect: '/wholesale' } })
    return
  }
  selectedStore.value = store
  form.business_name = requestForStore(store.id)?.business_name || ''
  form.request_note = ''
  formError.value = ''
  applicationOpen.value = true
}

async function submitApplication() {
  if (!selectedStore.value || !form.business_name.trim()) return
  submitting.value = true
  formError.value = ''
  try {
    await axiosClient.post(`/api/ecommerce/wholesale/stores/${selectedStore.value.id}/requests`, form)
    applicationOpen.value = false
    toast.add({ severity: 'success', summary: 'Request sent', detail: 'The store will review your wholesale access request.', life: 3000 })
    await load()
  } catch (error: any) {
    formError.value = error.response?.data?.message || 'Unable to submit your request. Please try again.'
  } finally {
    submitting.value = false
  }
}

onMounted(load)
</script>
