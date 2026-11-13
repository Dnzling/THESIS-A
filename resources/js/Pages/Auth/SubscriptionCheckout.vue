<template>
  <div class="min-h-screen bg-[#fffaf5] text-slate-900">
    <div v-if="isPaymentReturn" class="flex min-h-screen items-center justify-center px-4 py-10">
      <div class="w-full max-w-xl rounded-3xl border border-orange-100 bg-white p-8 text-center shadow-xl sm:p-12">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-orange-600">
          <i class="pi pi-spin pi-spinner text-2xl"></i>
        </div>
        <template v-if="!pageError">
          <p class="text-sm font-semibold text-orange-600">Confirming payment</p>
          <h1 class="mt-3 text-3xl font-bold">We're activating your subscription.</h1>
          <p class="mx-auto mt-4 max-w-md leading-7 text-slate-600">Keep this tab open while FurniSync confirms the payment and updates your store modules.</p>
        </template>
        <template v-else>
          <p class="text-sm font-semibold text-red-600">Confirmation delayed</p>
          <h1 class="mt-3 text-3xl font-bold">We couldn't confirm the payment yet.</h1>
          <p class="mx-auto mt-4 max-w-md leading-7 text-slate-600">{{ pageError }}</p>
          <Button label="Open Billing" severity="warn" class="mt-6" @click="router.visit('/store/billing')" />
        </template>
      </div>
    </div>

    <template v-else>
      <header class="border-b border-orange-100 bg-white">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-5 lg:px-8">
          <button type="button" class="flex h-10 w-10 items-center justify-center rounded-full text-slate-600 hover:bg-orange-50 hover:text-orange-600" aria-label="Back to plans" @click="goBack">
            <i class="pi pi-arrow-left"></i>
          </button>
          <img src="/F.svg" alt="FurniSync" class="h-10 w-10" />
          <div><p class="portal-brand text-orange-500">FURNISYNC</p><p class="text-xs text-slate-500">Configure your subscription</p></div>
        </div>
      </header>

      <main class="mx-auto max-w-7xl px-4 py-10 lg:px-8 lg:py-14">
        <div v-if="loading" class="grid gap-8 lg:grid-cols-[1.08fr_0.92fr]">
          <Skeleton height="34rem" class="rounded-3xl" />
          <Skeleton height="30rem" class="rounded-3xl" />
        </div>

        <div v-else-if="pageError" class="mx-auto max-w-xl rounded-3xl border border-red-200 bg-white p-8 text-center shadow-sm">
          <i class="pi pi-exclamation-circle text-3xl text-red-500"></i>
          <h1 class="mt-4 text-2xl font-bold">Checkout is unavailable</h1>
          <p class="mt-2 text-sm leading-6 text-slate-600">{{ pageError }}</p>
          <Button label="Back to plans" severity="warn" class="mt-6" @click="goBack" />
        </div>

        <div v-else-if="selectedPlan" class="grid gap-8 lg:grid-cols-[1.08fr_0.92fr] lg:items-start">
          <div class="space-y-8">
            <section v-if="isTrialCheckout" class="rounded-3xl border border-orange-100 bg-white p-6 shadow-sm sm:p-8">
              <p class="text-sm font-semibold text-orange-600">One-time free trial</p>
              <h1 class="mt-2 text-3xl font-bold">Start your one-month trial</h1>
              <p class="mt-3 text-sm leading-6 text-slate-600">No payment method is needed. Your amount due today is {{ formatMoney(0) }}. This store can use the free trial only once.</p>
            </section>

            <section v-else class="rounded-3xl border border-orange-100 bg-white p-6 shadow-sm sm:p-8">
              <p class="text-sm font-semibold text-orange-600">Payment method</p>
              <h1 class="mt-2 text-3xl font-bold">How would you like to pay?</h1>
              <p class="mt-2 text-sm text-slate-500">You'll complete payment securely through PayMongo.</p>

              <div class="mt-7 grid gap-3 sm:grid-cols-2">
                <button v-for="method in paymentMethods" :key="method.value" type="button"
                  class="flex min-h-20 items-center gap-4 rounded-2xl border px-5 text-left transition"
                  :class="selectedMethod === method.value ? 'border-orange-500 bg-orange-50 text-orange-700 ring-1 ring-orange-500' : 'border-slate-200 text-slate-700 hover:border-orange-300'"
                  @click="selectedMethod = method.value">
                  <i :class="[method.icon, 'text-2xl']"></i>
                  <div><p class="font-semibold">{{ method.label }}</p><p class="mt-0.5 text-xs text-slate-500">{{ method.description }}</p></div>
                  <i v-if="selectedMethod === method.value" class="pi pi-check-circle ml-auto text-orange-500"></i>
                </button>
              </div>
            </section>

            <section v-if="!isTrialCheckout" class="rounded-3xl border border-orange-100 bg-white p-6 shadow-sm sm:p-8">
              <div class="flex items-start justify-between gap-4">
                <div><p class="text-sm font-semibold text-orange-600">Billing address</p><h2 class="mt-2 text-2xl font-bold">Account details</h2></div>
                <Button :label="editingAddress ? 'Cancel' : 'Update'" text severity="warn" size="small" @click="editingAddress = !editingAddress" />
              </div>

              <form v-if="editingAddress" class="mt-6 grid gap-4 sm:grid-cols-2" @submit.prevent="saveBillingAddress">
                <div class="sm:col-span-2"><label class="mb-2 block text-sm font-medium">Street address</label><InputText v-model="billingForm.address" fluid /></div>
                <div><label class="mb-2 block text-sm font-medium">Barangay</label><InputText v-model="billingForm.barangay" fluid /></div>
                <div><label class="mb-2 block text-sm font-medium">City</label><InputText v-model="billingForm.city" fluid /></div>
                <div><label class="mb-2 block text-sm font-medium">Province</label><InputText v-model="billingForm.province" fluid /></div>
                <div><label class="mb-2 block text-sm font-medium">Phone</label><InputText v-model="billingForm.phone_number" fluid /></div>
                <div class="sm:col-span-2 flex items-center justify-end gap-3 pt-2">
                  <span v-if="addressMessage" class="mr-auto text-sm" :class="addressSaved ? 'text-emerald-600' : 'text-red-600'">{{ addressMessage }}</span>
                  <Button type="submit" label="Save address" icon="pi pi-save" severity="warn" size="small" :loading="savingAddress" />
                </div>
              </form>

              <div v-else class="mt-6 border-t border-slate-100 pt-5 text-sm leading-7 text-slate-600">
                <p class="font-semibold text-slate-900">{{ accountName }}</p>
                <p>{{ billingAddress || 'No billing address saved yet.' }}</p>
                <p v-if="billingForm.phone_number">{{ billingForm.phone_number }}</p>
              </div>
            </section>

            <section v-if="!hasActiveAgreement" class="rounded-3xl border border-orange-100 bg-white p-6 shadow-sm sm:p-8">
              <p class="text-sm font-semibold text-orange-600">Required before store setup</p>
              <h2 class="mt-2 text-2xl font-bold">Review and sign the service agreement</h2>
              <p class="mt-2 text-sm leading-6 text-slate-600">This agreement records your store's selected {{ selectedPlan.name }} subscription. It becomes active after the trial or payment is activated.</p>
              <div class="mt-4 max-h-48 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs leading-5 text-slate-600">
                <p class="font-semibold text-slate-800">FurniSync Store Subscription Service Agreement</p>
                <p class="mt-2">The store may use the modules and limits included in its selected plan. Paid subscriptions start after payment confirmation. Any eligible one-month trial starts when FurniSync activates it, with no charge collected during the trial period.</p>
                <p class="mt-2">The store is responsible for maintaining accurate business and account information, protecting its credentials, and reviewing plan pricing, renewal, expiration, and cancellation details shown in the system.</p>
                <p class="mt-2">By signing, you confirm that you are authorized to accept these terms for {{ storeName }} and authorize FurniSync to provide the selected subscription service.</p>
              </div>
              <label class="mt-4 flex items-start gap-2 text-sm text-slate-700">
                <input v-model="agreementAccepted" type="checkbox" class="mt-1 accent-orange-500" />
                <span>I have read and agree to this service agreement on behalf of the store.</span>
              </label>
              <div class="mt-4">
                <label class="mb-1 block text-sm font-medium text-slate-700">Authorized representative</label>
                <InputText v-model="signerName" fluid autocomplete="name" placeholder="Full legal name" />
              </div>
              <div class="mt-4 rounded-xl border border-slate-200 bg-white p-3">
                <div class="mb-2 flex items-center justify-between gap-3">
                  <span class="text-sm font-medium text-slate-700">Draw your signature</span>
                  <Button label="Clear" icon="pi pi-eraser" severity="secondary" text size="small" @click="clearSignature" />
                </div>
                <canvas ref="signatureCanvas" width="900" height="180" class="h-36 w-full touch-none rounded-lg border border-dashed border-slate-300 bg-white" aria-label="Draw electronic signature" @pointerdown="startSignature" @pointermove="drawSignature" @pointerup="stopSignature" @pointercancel="stopSignature" @pointerleave="stopSignature" />
                <p class="mt-1 text-xs text-slate-500">Use a mouse, stylus, or finger. Your signature is attached to the saved PDF agreement.</p>
                <p v-if="agreementSaved" class="mt-2 text-xs font-medium text-emerald-700"><i class="pi pi-check-circle mr-1" />Signature saved. It will be attached to the active agreement.</p>
              </div>
            </section>
          </div>

          <aside class="rounded-3xl bg-slate-950 p-6 text-white shadow-2xl sm:p-8 lg:sticky lg:top-8">
            <div class="flex items-start justify-between gap-5">
              <div><p class="text-sm font-semibold text-orange-400">Selected plan</p><h2 class="mt-2 text-3xl font-bold">{{ selectedPlan.name }}</h2></div>
              <span class="rounded-full bg-orange-500/15 px-3 py-1 text-xs font-semibold text-orange-300">{{ billingCycleLabel }}</span>
            </div>
            <p class="mt-4 text-sm leading-6 text-slate-400">{{ selectedPlan.description || 'Tools for operating and growing your furniture business.' }}</p>

            <div class="mt-7 border-t border-white/10 pt-6">
              <p class="text-sm font-semibold text-slate-200">Included features</p>
              <ul class="mt-4 space-y-3">
                <li v-for="feature in selectedPlan.features || []" :key="feature" class="flex gap-3 text-sm text-slate-300">
                  <svg class="mt-1 h-3.5 w-3.5 shrink-0 text-orange-400" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="m3 8.25 3.1 3.1L13 4.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" /></svg><span>{{ feature }}</span>
                </li>
              </ul>
            </div>

            <div class="mt-8 space-y-3 border-t border-white/10 pt-6 text-sm">
              <div class="flex justify-between gap-4 text-slate-400"><span>{{ isTrialCheckout ? `${billingCycleLabel} plan price` : `${billingCycleLabel} subscription` }}</span><span>{{ formatMoney(planAmount) }}</span></div>
              <div v-if="isTrialCheckout" class="flex justify-between gap-4 text-emerald-300"><span>One-month trial</span><span>-{{ formatMoney(planAmount) }}</span></div>
              <div v-else class="flex justify-between gap-4 text-slate-400"><span>Payment method</span><span>{{ selectedMethodLabel }}</span></div>
              <div class="flex justify-between gap-4 border-t border-white/10 pt-4 text-lg font-bold text-white"><span>Due today</span><span>{{ formatMoney(isTrialCheckout ? 0 : planAmount) }}</span></div>
            </div>

            <Button :label="isTrialCheckout ? 'Activate Free Trial' : 'Continue to secure payment'" class="mt-7 w-full !py-3" :loading="paymentInitializing || signing" :disabled="(!isTrialCheckout && !selectedMethod) || (!hasActiveAgreement && (!agreementAccepted || !signatureDrawn || !signerName.trim()))" @click="subscribe" />
            <p v-if="paymentError" class="mt-4 text-sm leading-6 text-red-300">{{ paymentError }}</p>
            <p v-if="isTrialCheckout" class="mt-5 text-xs leading-5 text-slate-400">No payment will be collected today. After the trial, choose a paid plan to continue using paid features.</p>
            <p v-else class="mt-5 text-xs leading-5 text-slate-500">Your plan is activated only after PayMongo confirms a successful payment. FurniSync does not store card or wallet credentials.</p>
          </aside>
        </div>
      </main>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Skeleton from 'primevue/skeleton'
import axiosClient from '@/axios'
import paymongoService from '@/services/paymongo.service'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const params = new URLSearchParams(window.location.search)
const isPaymentReturn = computed(() => params.get('payment') === 'success')
const isTrialCheckout = computed(() => params.get('trial') === '1')
const loading = ref(true)
const pageError = ref('')
const paymentError = ref('')
const paymentInitializing = ref(false)
const agreementAccepted = ref(false)
const hasActiveAgreement = ref(false)
const agreementSaved = ref(false)
const signatureDrawn = ref(false)
const signing = ref(false)
const isDrawingSignature = ref(false)
const signatureCanvas = ref<HTMLCanvasElement | null>(null)
const signerName = ref('')
const savingAddress = ref(false)
const editingAddress = ref(false)
const addressMessage = ref('')
const addressSaved = ref(false)
const selectedPlan = ref<any | null>(null)
const selectedMethod = ref<'card' | 'gcash' | 'paymaya' | 'grab_pay'>('card')
const billingForm = reactive({ address: '', barangay: '', city: '', province: '', phone_number: '' })

const paymentMethods = [
  { label: 'Credit or debit card', value: 'card' as const, icon: 'pi pi-credit-card', description: 'Visa or Mastercard' },
  { label: 'GCash', value: 'gcash' as const, icon: 'pi pi-wallet', description: 'Pay using your GCash wallet' },
  { label: 'Maya', value: 'paymaya' as const, icon: 'pi pi-mobile', description: 'Pay using your Maya wallet' },
  { label: 'GrabPay', value: 'grab_pay' as const, icon: 'pi pi-wallet', description: 'Pay using GrabPay' },
]

const storeId = computed(() => Number(params.get('store_id') || (authStore.currentUser as any)?.store_id || (authStore.currentUser as any)?.store?.id || 0))
const billingCycle = computed(() => params.get('cycle') === 'yearly' ? 'yearly' : 'monthly')
const billingCycleLabel = computed(() => billingCycle.value === 'yearly' ? 'Yearly' : 'Monthly')
const planAmount = computed(() => Number(billingCycle.value === 'yearly' ? selectedPlan.value?.yearly_price : selectedPlan.value?.monthly_price) || 0)
const selectedMethodLabel = computed(() => paymentMethods.find(method => method.value === selectedMethod.value)?.label || '')
const accountName = computed(() => {
  const user: any = authStore.currentUser
  return user?.full_name || `${user?.first_name || ''} ${user?.last_name || ''}`.trim() || 'Store account'
})
const storeName = computed(() => String((authStore.currentUser as any)?.store?.name || (authStore.currentUser as any)?.store_name || 'your store'))
const billingAddress = computed(() => [billingForm.address, billingForm.barangay, billingForm.city, billingForm.province].filter(Boolean).join(', '))

const formatMoney = (amount: number) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 2 }).format(amount)
const sleep = (ms: number) => new Promise(resolve => setTimeout(resolve, ms))
const normalizeStatus = (value: unknown) => String(value || '').toLowerCase()
const refreshAgreementStatus = async () => {
  const response = await axiosClient.get('/api/store/subscription-agreement/status')
  hasActiveAgreement.value = response.data?.data?.has_active_agreement === true
}

const startSignature = (event: PointerEvent) => {
  const canvas = signatureCanvas.value
  const context = canvas?.getContext('2d')
  if (!canvas || !context) return
  canvas.setPointerCapture(event.pointerId)
  const rect = canvas.getBoundingClientRect()
  context.beginPath()
  context.moveTo((event.clientX - rect.left) * canvas.width / rect.width, (event.clientY - rect.top) * canvas.height / rect.height)
  context.strokeStyle = '#172033'
  context.lineWidth = 3
  context.lineCap = 'round'
  context.lineJoin = 'round'
  isDrawingSignature.value = true
}
const drawSignature = (event: PointerEvent) => {
  if (!isDrawingSignature.value || !signatureCanvas.value) return
  const context = signatureCanvas.value.getContext('2d')
  if (!context) return
  const rect = signatureCanvas.value.getBoundingClientRect()
  context.lineTo((event.clientX - rect.left) * signatureCanvas.value.width / rect.width, (event.clientY - rect.top) * signatureCanvas.value.height / rect.height)
  context.stroke()
  signatureDrawn.value = true
  agreementSaved.value = false
}
const stopSignature = () => { isDrawingSignature.value = false }
const clearSignature = () => {
  const canvas = signatureCanvas.value
  canvas?.getContext('2d')?.clearRect(0, 0, canvas.width, canvas.height)
  signatureDrawn.value = false
  agreementSaved.value = false
}
const saveSignedAgreement = async () => {
  if (agreementSaved.value || signing.value) return
  const signature = signatureCanvas.value?.toDataURL('image/png')
  if (!signature || !signatureDrawn.value || !agreementAccepted.value || !signerName.value.trim()) {
    throw new Error('Please accept the agreement, enter your name, and draw your signature.')
  }
  signing.value = true
  try {
    await axiosClient.post('/api/store/subscription-agreement/sign', {
      plan_key: selectedPlan.value?.plan_key,
      billing_cycle: billingCycle.value,
      signer_name: signerName.value.trim(),
      accepted: agreementAccepted.value,
      signature,
    }, { headers: { 'X-Suppress-Success-Dialog': '1' } })
    agreementSaved.value = true
  } finally {
    signing.value = false
  }
}

const goBack = () => router.visit(`/subscription-plans${storeId.value ? `?store_id=${storeId.value}` : ''}`)

const loadCheckout = async () => {
  try {
    await authStore.fetchCurrentUser()
    await refreshAgreementStatus()
    if (!signerName.value) signerName.value = accountName.value
    const planKey = String(params.get('plan') || '').toLowerCase()
    if (!planKey || !storeId.value) throw new Error('Choose a subscription plan before opening checkout.')

    const plansResponse = await axiosClient.get('/api/public/subscription-plans')
    const plans = plansResponse.data?.data || []
    selectedPlan.value = plans.find((plan: any) => String(plan.plan_key).toLowerCase() === planKey && plan.is_active !== false) || null
    if (!selectedPlan.value || planKey === 'free' || (!isTrialCheckout.value && planAmount.value <= 0)) throw new Error('This subscription plan is not available.')

    if (isTrialCheckout.value) {
      const store = (authStore.currentUser as any)?.store || authStore.currentUser as any || {}
      const activeTrial = store.subscription_status === 'trial' && store.trial_ends_at && new Date(store.trial_ends_at) > new Date()
      const eligible = activeTrial || (!store.trial_started_at && ['free', 'pending'].includes(String(store.subscription_status || '').toLowerCase()))
      if (!eligible) throw new Error('This store has already used its free trial. Continue with the paid checkout instead.')
    }

    if (!isTrialCheckout.value) {
      const profileResponse = await axiosClient.get('/api/profile')
      const profile = profileResponse.data?.data || {}
      const employee = profile.employee || {}
      const user = profile.user || {}
      billingForm.address = employee.address || ''
      billingForm.barangay = employee.barangay || ''
      billingForm.city = employee.city || ''
      billingForm.province = employee.province || ''
      billingForm.phone_number = user.phone_number || employee.phone_number || ''
      editingAddress.value = !billingAddress.value
    }
  } catch (error: any) {
    pageError.value = error?.response?.data?.message || error?.message || 'Unable to load subscription checkout.'
  } finally {
    loading.value = false
  }
}

const saveBillingAddress = async (): Promise<boolean> => {
  addressMessage.value = ''
  addressSaved.value = false
  if (!billingForm.address.trim() || !billingForm.city.trim() || !billingForm.province.trim()) {
    addressMessage.value = 'Street address, city, and province are required.'
    return false
  }

  savingAddress.value = true
  try {
    await axiosClient.put('/api/profile', { ...billingForm }, { headers: { 'X-Suppress-Success-Dialog': '1' } })
    addressSaved.value = true
    addressMessage.value = 'Billing address saved.'
    editingAddress.value = false
    return true
  } catch (error: any) {
    const errors = error?.response?.data?.errors
    addressMessage.value = errors ? String(Object.values(errors).flat()[0]) : (error?.response?.data?.message || 'Unable to save billing address.')
    return false
  } finally {
    savingAddress.value = false
  }
}

const subscribe = async () => {
  if (!selectedPlan.value || (!isTrialCheckout.value && !selectedMethod.value) || paymentInitializing.value) return
  paymentError.value = ''
  paymentInitializing.value = true

  try {
    if (!hasActiveAgreement.value) await saveSignedAgreement()
  } catch (error: any) {
    paymentError.value = error?.response?.data?.message || error?.message || 'Unable to save the signed agreement.'
    paymentInitializing.value = false
    return
  }

  if (isTrialCheckout.value) {
    try {
      await axiosClient.put(`/api/stores/${storeId.value}/subscription`, {
        subscription_tier: String(selectedPlan.value.plan_key).toLowerCase(),
        setup_mode: 'trial',
      })
      if (!hasActiveAgreement.value) await axiosClient.post('/api/store/subscription-agreement/activate')
      await authStore.fetchCurrentUser({ reloadPermissions: true })
      router.visit((authStore.currentUser as any)?.position_setup_required ? '/store/positions/setup' : '/store/index')
    } catch (error: any) {
      paymentError.value = error?.response?.data?.message || error?.message || 'Unable to activate the free trial.'
    } finally {
      paymentInitializing.value = false
    }
    return
  }

  if (!billingAddress.value) {
    editingAddress.value = true
    paymentError.value = 'Add and save your billing address before continuing.'
    paymentInitializing.value = false
    return
  }

  paymentInitializing.value = true
  try {
    const successUrl = new URL('/subscription-checkout', window.location.origin)
    successUrl.searchParams.set('payment', 'success')
    successUrl.searchParams.set('store_id', String(storeId.value))
    const cancelUrl = new URL(window.location.href)
    const response = await paymongoService.createCheckoutSession({
      amount: Math.max(Math.round(planAmount.value * 100), 1), currency: 'PHP',
      description: `${billingCycleLabel.value} subscription for ${selectedPlan.value.name}`,
      payment_method_allowed: [selectedMethod.value],
      metadata: { plan_key: selectedPlan.value.plan_key, subscription_tier: selectedPlan.value.plan_key, months: billingCycle.value === 'yearly' ? 12 : 1, billing_cycle: billingCycle.value, amount_php: planAmount.value, setup_mode: 'paid', store_id: storeId.value },
      store_id: storeId.value, payable_type: 'subscription_upgrade', payable_id: storeId.value,
      success_url: successUrl.toString(), cancel_url: cancelUrl.toString(),
    })
    const checkoutUrl = String(response?.data?.checkout_url || '')
    if (!checkoutUrl.startsWith('https://checkout.paymongo.com/')) throw new Error('PayMongo did not return a valid checkout page.')
    localStorage.setItem('pending_subscription_plan', JSON.stringify({ plan_key: selectedPlan.value.plan_key, billing_cycle: billingCycle.value }))
    window.location.assign(checkoutUrl)
  } catch (error: any) {
    paymentError.value = error?.response?.data?.message || error?.message || 'Unable to start secure payment.'
  } finally {
    paymentInitializing.value = false
  }
}

const syncSubscriptionFromIntent = async (pendingPlan: any) => {
  if (!storeId.value) return false
  const latest = await paymongoService.getLatestIntentByPayable('subscription_upgrade', storeId.value, { sync: true })
  const intentData = latest?.data?.data ?? latest?.data ?? null
  if (!['succeeded', 'paid'].includes(normalizeStatus(intentData?.status ?? intentData?.attributes?.status))) return false
  await authStore.fetchCurrentUser({ reloadPermissions: true })
  if (!hasActiveAgreement.value) await axiosClient.post('/api/store/subscription-agreement/activate')
  if (pendingPlan?.plan_key) localStorage.removeItem('pending_subscription_plan')
  return true
}

const processPaymentReturn = async () => {
  try {
    await authStore.fetchCurrentUser()
    await refreshAgreementStatus()
  } catch (error: any) {
    pageError.value = error?.response?.data?.message || 'Unable to verify the store subscription agreement.'
    return
  }
  const pendingPlan = JSON.parse(localStorage.getItem('pending_subscription_plan') || 'null')
  const startedAt = Date.now()
  while (Date.now() - startedAt < 60000) {
    try {
      if (await syncSubscriptionFromIntent(pendingPlan)) {
        router.visit((authStore.currentUser as any)?.position_setup_required ? '/store/positions/setup' : '/store/index')
        return
      }
    } catch {
      // PayMongo webhooks can arrive shortly after the browser redirect.
    }
    await sleep(2000)
  }
  pageError.value = 'Payment confirmation is taking longer than expected. Check Billing before trying again.'
}

onMounted(() => isPaymentReturn.value ? processPaymentReturn() : loadCheckout())
</script>

<style scoped>
@font-face { font-family: 'Barabara'; src: url('/fonts/BARABARA-final.otf') format('opentype'); font-display: swap; }
.portal-brand { font-family: 'Barabara', sans-serif; }
</style>
