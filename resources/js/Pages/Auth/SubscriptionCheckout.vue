<template>
  <div class="min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,rgba(249,115,22,0.16),transparent_42%),linear-gradient(180deg,#fff7ed_0%,#ffffff_38%,#f8fafc_100%)] text-slate-900">
    <div class="flex min-h-screen items-center justify-center px-4 py-10">
      <div class="w-full max-w-xl rounded-[2rem] border border-orange-100 bg-white/90 p-8 text-center shadow-[0_30px_100px_rgba(15,23,42,0.12)] backdrop-blur sm:p-12">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-3xl text-orange-600">
          <span class="loading-dot"></span>
        </div>
        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-orange-500">Payment processing</p>
        <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Wait for a moment, we are syncing your Furniture.</h1>
        <p class="mx-auto mt-4 max-w-md text-base leading-7 text-slate-600 sm:text-lg">
          We are confirming the payment with PayMongo and applying your subscription. You will enter your store automatically after it succeeds.
        </p>
        <div class="mt-8 flex items-center justify-center gap-2 text-sm font-medium text-slate-500">
          <span class="inline-flex h-2.5 w-2.5 rounded-full bg-orange-500 animate-pulse"></span>
          Please keep this tab open while we finish syncing.
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axiosClient from '@/axios'
import paymongoService from '@/services/paymongo.service'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const isRedirecting = ref(false)
const sleep = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

const normalizeStatus = (value: unknown) => String(value || '').toLowerCase()

const syncSubscriptionFromIntent = async (storeId: number, pendingPlan: any) => {
  if (!storeId) return false

  const latest = await paymongoService.getLatestIntentByPayable('subscription_upgrade', storeId, { sync: true })
  const intentData = latest?.data?.data ?? latest?.data ?? null
  const status = normalizeStatus(intentData?.status ?? intentData?.attributes?.status)

  if (status !== 'succeeded') {
    return false
  }

  if (pendingPlan?.plan_key && pendingPlan.plan_key !== 'free') {
    await axiosClient.put(`/api/stores/${storeId}/subscription`, {
      subscription_tier: String(pendingPlan.plan_key).toLowerCase(),
      setup_mode: 'paid',
      months: Number(pendingPlan.months || 1),
      billing_cycle: String(pendingPlan.billing_cycle || 'monthly'),
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

  return true
}

onMounted(async () => {
  if (isRedirecting.value) return
  isRedirecting.value = true

  const params = new URLSearchParams(window.location.search)
  const storeId = Number(params.get('store_id') || (authStore.currentUser as any)?.store_id || 0)
  const pendingPlan = JSON.parse(localStorage.getItem('pending_subscription_plan') || 'null')

  try {
    if (storeId > 0) {
      const startedAt = Date.now()
      const timeoutMs = 60000

      while (Date.now() - startedAt < timeoutMs) {
        try {
          const settled = await syncSubscriptionFromIntent(storeId, pendingPlan)
          if (settled) {
            router.visit('/store/index')
            return
          }
        } catch (_syncError) {
          // Keep polling while PayMongo/webhook sync catches up.
        }

        await sleep(2000)
      }
    }
  } catch (_error) {
    // If something unexpected happens, leave the user on the loading screen instead of bouncing away early.
  }

  if (storeId > 0) {
    try {
      const settled = await syncSubscriptionFromIntent(storeId, pendingPlan)
      if (settled) {
        router.visit('/store/index')
      }
    } catch (_finalError) {
      // stay on the loading page
    }
  }
})
</script>

<style scoped>
.loading-dot {
  width: 1rem;
  height: 1rem;
  border-radius: 9999px;
  background: linear-gradient(135deg, #f97316, #fb923c);
  box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.35);
  animation: pulse-ring 1.4s ease-out infinite;
}

@keyframes pulse-ring {
  0% {
    transform: scale(0.9);
    box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.35);
  }
  70% {
    transform: scale(1);
    box-shadow: 0 0 0 18px rgba(249, 115, 22, 0);
  }
  100% {
    transform: scale(0.9);
    box-shadow: 0 0 0 0 rgba(249, 115, 22, 0);
  }
}
</style>
