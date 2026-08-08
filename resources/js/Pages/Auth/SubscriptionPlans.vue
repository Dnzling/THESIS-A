<template>
  <div class="min-h-screen bg-gradient-to-b from-white via-orange-50/20 to-slate-50 text-slate-900">
    <div class="relative overflow-hidden py-20 lg:py-28">
      <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(245,158,107,0.18),transparent_55%)]"></div>
      <div class="mx-auto max-w-7xl px-4 text-center lg:px-8">
        <span class="inline-flex rounded-full bg-orange-100 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-orange-700">Subscription Available</span>
        <h1 class="mx-auto mt-5 max-w-3xl text-4xl font-bold leading-tight sm:text-5xl">Simple plans for modern furniture teams.</h1>
        <p class="mx-auto mt-5 max-w-2xl text-lg text-slate-600">Pick Free Trial to enter the system now, or choose a paid plan and enter your payment details to continue.</p>

        <div class="mt-8 inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-4 py-3 shadow-sm">
          <span class="text-sm font-medium" :class="isYearly ? 'text-slate-900' : 'text-orange-500'">Monthly</span>
          <button type="button" class="relative h-7 w-12 rounded-full transition" :class="isYearly ? 'bg-orange-500' : 'bg-slate-300'" @click="toggleBillingCycle">
            <span class="absolute top-1 h-5 w-5 rounded-full bg-white shadow transition-transform" :class="isYearly ? 'translate-x-6' : 'translate-x-1'"></span>
          </button>
          <span class="text-sm font-medium" :class="!isYearly ? 'text-slate-900' : 'text-orange-500'">Yearly</span>
          <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">Save 20%</span>
        </div>
      </div>
    </div>

    <section class="pb-16">
      <div class="mx-auto max-w-7xl px-4 lg:px-8">
        <div v-if="loadingPlans && !visiblePlans.length" class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
          <h2 class="text-2xl font-bold text-orange-500">Loading plans</h2>
          <p class="mt-2 text-slate-600">Please wait while we fetch the latest offers.</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
          <article v-for="plan in visiblePlans" :key="plan.id" class="relative rounded-3xl border bg-white p-8 shadow-lg shadow-slate-200/60 transition hover:-translate-y-1 hover:shadow-xl" :class="plan.is_featured ? 'border-orange-200 ring-1 ring-orange-200' : 'border-slate-200'">
            <div v-if="plan.is_featured" class="absolute right-6 top-6 rounded-full bg-orange-500 px-3 py-1 text-xs font-semibold text-white">Most Popular</div>
            <div class="space-y-1 pr-24">
              <h2 class="text-2xl font-bold text-slate-900">{{ plan.name }}</h2>
              <p class="text-sm leading-6 text-slate-600">{{ plan.description || 'Flexible plan for growing furniture teams.' }}</p>
            </div>
            <div class="mt-6 flex items-end gap-2">
              <span class="text-4xl font-bold tracking-tight text-slate-900">₱{{ formatPrice(plan) }}</span>
              <span class="pb-1 text-sm text-slate-500">/ {{ billingPeriod }}</span>
            </div>
            <p class="mt-2 text-sm text-slate-500">
              Billed {{ isYearly ? 'yearly' : 'monthly' }}
              <span v-if="isYearly" class="ml-2 font-semibold text-orange-700">Save ₱{{ yearlySavings(plan) }}</span>
            </p>
            <button type="button" class="mt-8 inline-flex w-full items-center justify-center rounded-full bg-orange-500 px-5 py-3 font-semibold text-white shadow-lg shadow-orange-500/20 transition hover:-translate-y-0.5 hover:bg-orange-600" @click="plan.plan_key === 'free' || Number(plan.monthly_price || 0) === 0 ? chooseFreeTrial(plan) : openConfirmation(plan)">
              {{ plan.plan_key === 'free' || Number(plan.monthly_price || 0) === 0 ? 'Start Free Trial' : 'Choose Plan' }}
            </button>
            <div class="mt-6 space-y-3">
              <div v-for="feature in plan.features || []" :key="feature" class="flex items-start gap-3 text-sm text-slate-700">
                <span class="mt-1 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-orange-100 text-orange-600">✓</span>
                <span>{{ feature }}</span>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

    <Dialog v-model:visible="confirmVisible" modal header="Confirm Subscription" class="w-full max-w-lg">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">You selected <strong>{{ selectedPlan?.name }}</strong>. Continue to payment details?</p>
        <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
          <p><span class="font-semibold">Plan:</span> {{ selectedPlan?.name }}</p>
          <p><span class="font-semibold">Amount:</span> ₱{{ selectedPlan ? formatPrice(selectedPlan) : '0.00' }}</p>
        </div>
        <div class="flex justify-end gap-3">
          <Button label="Cancel" severity="secondary" outlined @click="confirmVisible = false" />
          <Button label="Continue" severity="warning" :loading="paymentInitializing" @click="openPaymentDialog" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="paymentDialogVisible" modal header="Enter Payment Details" class="w-full max-w-lg">
      <div class="space-y-4">
        <div class="grid gap-3">
          <label class="text-sm font-semibold text-slate-700">Payment Method</label>
          <Select v-model="selectedPaymentMethod" :options="paymentMethodOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>

        <div v-if="selectedPaymentMethod === 'card'" class="space-y-3">
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Card Number</label>
            <InputMask v-model="cardForm.cardNumber" mask="0000 0000 0000 0000" fluid placeholder="4111 1111 1111 1111" />
          </div>
          <div class="grid grid-cols-3 gap-2">
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">MM</label>
              <InputMask v-model="cardForm.expMonth" mask="00" inputmode="numeric" fluid placeholder="01" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">YYYY</label>
              <InputMask v-model="cardForm.expYear" mask="0000" inputmode="numeric" fluid placeholder="2030" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">CVC</label>
              <InputMask v-model="cardForm.cvc" mask="000" inputmode="numeric" fluid placeholder="123" />
            </div>
          </div>
        </div>

        <div v-else class="space-y-3">
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Name</label>
            <InputText v-model="gcashForm.name" fluid placeholder="Full name" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">GCash Number</label>
            <InputMask v-model="gcashForm.phone" mask="09999999999" fluid placeholder="09XXXXXXXXX" :autoClear="false" />
          </div>
          <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
            <InputText v-model="gcashForm.email" type="email" fluid placeholder="name@example.com" />
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined :disabled="paymentProcessing" @click="paymentDialogVisible = false" />
        <Button label="Submit Payment" severity="warn" :loading="paymentProcessing" @click="submitPayment" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import InputMask from 'primevue/inputmask'
import axiosClient from '@/axios'
import paymongoService from '@/services/paymongo.service'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const plans = ref<any[]>([])
const loadingPlans = ref(false)
const isYearly = ref(false)
const confirmVisible = ref(false)
const paymentDialogVisible = ref(false)
const paymentInitializing = ref(false)
const paymentProcessing = ref(false)
const selectedPlan = ref<any | null>(null)
const selectedPaymentMethod = ref<'card' | 'gcash'>('card')
const paymentIntentId = ref('')
const paymentClientKey = ref('')
const storeIdFromQuery = ref<string>(new URLSearchParams(window.location.search).get('store_id') || '')

const cardForm = reactive({ cardNumber: '', expMonth: '', expYear: '', cvc: '' })
const gcashForm = reactive({ name: '', phone: '', email: '' })

const paymentMethodOptions = [
  { label: 'Card', value: 'card' },
  { label: 'GCash', value: 'gcash' },
]

const billingPeriod = computed(() => (isYearly.value ? 'year' : 'month'))

const visiblePlans = computed(() =>
  plans.value
    .filter((plan) => plan.is_active !== false)
    .slice()
    .sort((a, b) => {
      const order = ['free', 'simple', 'unlimited']
      const aKey = String(a.plan_key || a.name || '').toLowerCase()
      const bKey = String(b.plan_key || b.name || '').toLowerCase()
      const aIndex = order.findIndex((key) => aKey.includes(key))
      const bIndex = order.findIndex((key) => bKey.includes(key))
      if (aIndex !== -1 || bIndex !== -1) {
        const safeA = aIndex === -1 ? order.length : aIndex
        const safeB = bIndex === -1 ? order.length : bIndex
        if (safeA !== safeB) return safeA - safeB
      }
      return String(a.name || '').localeCompare(String(b.name || ''))
    }),
)

const formatPrice = (plan: any) => {
  const price = isYearly.value ? Number(plan.yearly_price || 0) : Number(plan.monthly_price || 0)
  return price.toFixed(2)
}

const yearlySavings = (plan: any) => {
  const monthly = Number(plan.monthly_price || 0)
  const yearly = Number(plan.yearly_price || 0)
  const savings = monthly * 12 - yearly
  return savings > 0 ? savings.toFixed(2) : '0.00'
}

const toggleBillingCycle = () => {
  isYearly.value = !isYearly.value
}

const loadPlans = async () => {
  loadingPlans.value = true
  try {
    const response = await axiosClient.get('/api/public/subscription-plans')
    plans.value = response.data?.data || []
  } catch {
    plans.value = []
  } finally {
    loadingPlans.value = false
  }
}

const chooseFreeTrial = async (plan: any) => {
  const storeId = Number(storeIdFromQuery.value || (authStore.currentUser as any)?.store_id || 0)
  if (!storeId) {
    router.visit('/store/registration')
    return
  }
  await axiosClient.put(`/api/stores/${storeId}/subscription`, {
    subscription_tier: String(plan.plan_key || 'free').toLowerCase(),
    setup_mode: 'free',
  })
  await authStore.fetchCurrentUser({ reloadPermissions: true })
  router.visit('/store/index')
}

const openConfirmation = (plan: any) => {
  selectedPlan.value = plan
  confirmVisible.value = true
}

const openPaymentDialog = async () => {
  if (!selectedPlan.value) return
  const storeId = Number(storeIdFromQuery.value || (authStore.currentUser as any)?.store_id || 0)
  if (!storeId) {
    router.visit('/store/registration')
    return
  }

  localStorage.setItem('pending_subscription_plan', JSON.stringify({
    plan_key: selectedPlan.value.plan_key,
    name: selectedPlan.value.name,
    monthly_price: selectedPlan.value.monthly_price,
    yearly_price: selectedPlan.value.yearly_price,
  }))

  paymentInitializing.value = true
  try {
    const intentResponse = await paymongoService.createIntent({
      amount: Math.max(Math.round(Number(selectedPlan.value.monthly_price || 0) * 100), 1),
      currency: 'PHP',
      description: `Subscription for ${selectedPlan.value.name}`,
      statement_descriptor: 'Furniture Store SaaS',
      payment_method_allowed: [selectedPaymentMethod.value],
      metadata: {
        plan_key: selectedPlan.value.plan_key,
        subscription_tier: selectedPlan.value.plan_key,
        months: isYearly.value ? 12 : 1,
        billing_cycle: isYearly.value ? 'yearly' : 'monthly',
        setup_mode: 'paid',
        store_id: storeId,
      },
      store_id: storeId,
      payable_type: 'subscription_upgrade',
      payable_id: storeId,
    })

    paymentIntentId.value = String(intentResponse?.data?.data?.id || '')
    paymentClientKey.value = String(intentResponse?.data?.data?.attributes?.client_key || '')
    if (!paymentIntentId.value || !paymentClientKey.value) {
      throw new Error(intentResponse?.message || 'Failed to initialize payment.')
    }

    confirmVisible.value = false
    paymentDialogVisible.value = true
  } catch (error: any) {
    alert(error?.response?.data?.message || error?.message || 'Unable to start payment.')
  } finally {
    paymentInitializing.value = false
  }
}

const base64Encode = (value: string) => {
  try {
    return btoa(value)
  } catch {
    return btoa(unescape(encodeURIComponent(value)))
  }
}

const getPublicKey = async () => {
  const res = await paymongoService.getPublicKey()
  const key = String(res?.data?.public_key || '').trim()
  if (!key) throw new Error('Missing Online Payment public key.')
  return key
}

const createCardPaymentMethod = async () => {
  const publicKey = await getPublicKey()
  const response = await fetch('https://api.paymongo.com/v1/payment_methods', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Basic ${base64Encode(`${publicKey}:`)}`,
    },
    body: JSON.stringify({
      data: {
        attributes: {
          type: 'card',
          details: {
            card_number: String(cardForm.cardNumber || '').replace(/\s+/g, ''),
            exp_month: Number(cardForm.expMonth),
            exp_year: Number(cardForm.expYear),
            cvc: String(cardForm.cvc || ''),
          },
          billing: {
            name: (authStore.currentUser as any)?.name || 'Customer',
            email: (authStore.currentUser as any)?.email || '',
          },
        },
      },
    }),
  })
  const payload = await response.json().catch(() => null)
  if (!response.ok) throw new Error(payload?.errors?.[0]?.detail || 'Unable to create card payment method.')
  return String(payload?.data?.id || '')
}

const submitPayment = async () => {
  if (!selectedPlan.value || !paymentIntentId.value) return
  const storeId = Number(storeIdFromQuery.value || (authStore.currentUser as any)?.store_id || 0)
  if (!storeId) {
    router.visit('/store/registration')
    return
  }

  paymentProcessing.value = true
  try {
    if (selectedPaymentMethod.value === 'gcash') {
      if (!gcashForm.name || !gcashForm.phone || !gcashForm.email) throw new Error('Please complete the GCash details.')
      const response = await paymongoService.startGcash(paymentIntentId.value, {
        name: gcashForm.name,
        email: gcashForm.email,
        phone: gcashForm.phone,
        return_url: `${window.location.origin}/subscription-plans?payment=success&store_id=${encodeURIComponent(String(storeId))}`,
      })
      const redirectUrl = response?.data?.redirect_url
      if (!redirectUrl) throw new Error(response?.message || 'Unable to start GCash payment.')
      paymentDialogVisible.value = false
      window.location.href = String(redirectUrl)
      return
    }

    if (!cardForm.cardNumber || !cardForm.expMonth || !cardForm.expYear || !cardForm.cvc) {
      throw new Error('Please complete the card details.')
    }

    const paymentMethodId = await createCardPaymentMethod()
    const attachResponse = await fetch(`https://api.paymongo.com/v1/payment_intents/${encodeURIComponent(paymentIntentId.value)}/attach`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Basic ${base64Encode(`${paymentClientKey.value}:`)}`,
      },
      body: JSON.stringify({
        data: {
          attributes: {
            payment_method: paymentMethodId,
            return_url: `${window.location.origin}/subscription-plans?payment=success&store_id=${encodeURIComponent(String(storeId))}`,
          },
        },
      }),
    })
    const attachPayload = await attachResponse.json().catch(() => null)
    if (!attachResponse.ok) throw new Error(attachPayload?.errors?.[0]?.detail || 'Unable to attach payment method.')
    const nextUrl = attachPayload?.data?.attributes?.next_action?.redirect?.url
    paymentDialogVisible.value = false
    if (nextUrl) {
      window.location.href = String(nextUrl)
    } else {
      router.visit('/store/index')
    }
  } catch (error: any) {
    alert(error?.response?.data?.message || error?.message || 'Unable to process payment.')
  } finally {
    paymentProcessing.value = false
  }
}

onMounted(async () => {
  await authStore.fetchCurrentUser()
  await loadPlans()
  const params = new URLSearchParams(window.location.search)
  if (params.get('payment') === 'success') {
    const storeId = Number(params.get('store_id') || (authStore.currentUser as any)?.store_id || 0)
    const pendingPlan = JSON.parse(localStorage.getItem('pending_subscription_plan') || 'null')

    try {
      if (storeId > 0) {
        await paymongoService.getLatestIntentByPayable('subscription_upgrade', storeId, { sync: true })

        if (pendingPlan?.plan_key && pendingPlan.plan_key !== 'free') {
          await axiosClient.put(`/api/stores/${storeId}/subscription`, {
            subscription_tier: String(pendingPlan.plan_key).toLowerCase(),
            setup_mode: 'paid',
          })
        } else if (pendingPlan?.plan_key === 'free') {
          await axiosClient.put(`/api/stores/${storeId}/subscription`, {
            subscription_tier: 'free',
            setup_mode: 'free',
          })
        }

        await authStore.fetchCurrentUser({ reloadPermissions: true })

        if (pendingPlan?.plan_key) {
          localStorage.removeItem('pending_subscription_plan')
        }
      }
    } catch (_error) {
      if (storeId > 0 && pendingPlan?.plan_key) {
        try {
          await axiosClient.put(`/api/stores/${storeId}/subscription`, {
            subscription_tier: String(pendingPlan.plan_key).toLowerCase(),
            setup_mode: pendingPlan.plan_key === 'free' ? 'free' : 'paid',
          })
          await authStore.fetchCurrentUser({ reloadPermissions: true })
        } catch (_fallbackError) {
          // Leave the user on the page rather than falsely claiming success.
          return
        } finally {
          localStorage.removeItem('pending_subscription_plan')
        }
      }
    }

    router.visit('/store/index')
  }
})
</script>
