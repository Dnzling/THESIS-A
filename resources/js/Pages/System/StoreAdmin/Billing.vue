<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Billing</h1>
      </div>
      <Button label="Upgrade Plan" icon="pi pi-arrow-up-right" severity="warn" @click="goToUpgrade" size="small" />
    </div>

    <div class="grid gap-4 ">
      <Card class="border border-slate-200 shadow-sm">
        <template #title>Subscription Plan</template>
        <template #content>
          <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-white p-4">
              <div class="text-xs font-semibold uppercase text-slate-400">Current Plan</div>
              <div class="mt-2 text-2xl font-bold text-slate-950">{{ subscription.plan_name }}</div>
              <div class="mt-1 text-sm text-slate-500">{{ subscription.module_count }} enabled module(s)</div>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-4">
              <div class="text-xs font-semibold uppercase text-slate-400">Ends On</div>
              <div class="mt-2 text-xl font-bold text-slate-950">{{ endsAtLabel }}</div>
              <div class="mt-1 text-sm" :class="subscription.is_expired ? 'text-red-600' : 'text-slate-500'">
                {{ remainingLabel }}
              </div>
            </div>
         
          </div>

          <div class="mt-5 rounded-lg border p-4" :class="subscription.is_expired ? 'border-red-100 bg-red-50 text-red-700' : 'border-green-100 bg-green-50 text-green-700'">
            <div class="font-semibold">{{ subscription.is_expired ? 'Subscription expired' : 'Subscription active' }}</div>
            <p class="mt-1 text-sm">
              {{ subscription.is_expired ? 'Renew your plan to keep paid modules available.' : 'Your store can continue using the modules included in this plan.' }}
            </p>
          </div>

          <div class="mt-5 rounded-xl border border-orange-100 bg-orange-50/60 p-4">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <div class="text-sm font-semibold text-slate-950">Enabled Modules</div>
                <p class="text-xs text-slate-500">Modules currently unlocked by this subscription plan.</p>
              </div>
              <Tag :value="`${enabledModules.length} active`" severity="warn" />
            </div>

            <div v-if="enabledModules.length" class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
              <div
                v-for="module in enabledModules"
                :key="module.key"
                class="flex items-center gap-2 rounded-lg border border-white bg-white/90 px-3 py-2 text-sm font-medium text-slate-800 shadow-sm"
              >
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-orange-100 text-orange-600">
                  <i class="pi pi-check text-xs"></i>
                </span>
                <span>{{ module.name }}</span>
              </div>
            </div>

            <div v-else class="mt-4 rounded-lg border border-dashed border-orange-200 bg-white/70 p-4 text-sm text-slate-500">
              No enabled modules were found for this plan yet.
            </div>
          </div>

          <div class="mt-5 rounded-xl border border-slate-200 p-4">
            <div>
              <div class="text-sm font-semibold text-slate-950">Plan Limits & Usage</div>
              <p class="text-xs text-slate-500">Configured subscription caps and current usage for this store.</p>
            </div>

            <div v-if="planLimits.length" class="mt-4 grid gap-3 md:grid-cols-3">
              <div v-for="limit in planLimits" :key="limit.key" class="rounded-lg border border-slate-200 bg-white p-3">
                <div class="flex items-start justify-between gap-2">
                  <span class="text-sm font-medium text-slate-700">{{ limit.label }}</span>
                  <Tag :value="limit.limit == null ? 'Unlimited' : `${formatCount(limit.limit)} max`" :severity="limit.limit == null ? 'secondary' : 'info'" />
                </div>
                <div class="mt-3 text-lg font-semibold text-slate-950">
                  {{ formatCount(limit.used) }}
                  <span class="text-xs font-normal text-slate-500">{{ limit.limit == null ? 'currently used' : `of ${formatCount(limit.limit)} used` }}</span>
                </div>
                <ProgressBar v-if="limit.limit != null" :value="usagePercent(limit.used, limit.limit)" :showValue="false" class="mt-2 h-2" />
              </div>
            </div>
            <div v-else class="mt-3 text-sm text-slate-500">No account, branch, or product caps are configured for this plan.</div>

            <div v-if="planFeatures.length" class="mt-4 border-t border-slate-100 pt-3">
              <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Other configured plan features</div>
              <div class="flex flex-wrap gap-2">
                <Tag v-for="(feature, index) in planFeatures" :key="`${index}-${feature}`" :value="feature" severity="secondary" />
              </div>
            </div>
          </div>
        </template>
      </Card>

      
    </div>

    <Card class="border border-slate-200 shadow-sm">
      <template #title>Billing Records History</template>
      <template #content>
        <DataTable
          :value="billingRecords"
          :paginator="billingRecords.length > 10"
          :rows="10"
          size="small"
          stripedRows
          dataKey="id"
          class="p-datatable-sm"
        >
          <Column field="created_at" header="Date" sortable>
            <template #body="{ data }">
              <div class="font-medium text-slate-900">{{ formatDate(data.created_at) }}</div>
              <div class="text-xs text-slate-500">{{ data.reference }}</div>
            </template>
          </Column>
          <Column field="plan" header="Plan" sortable>
            <template #body="{ data }">
              <div class="font-semibold capitalize text-slate-900">{{ data.plan }}</div>
              <div class="text-xs capitalize text-slate-500">{{ data.billing_cycle }} · {{ data.months }} month(s)</div>
            </template>
          </Column>
          <Column field="payment_method" header="Method" />
          <Column field="amount" header="Amount" sortable>
            <template #body="{ data }">
              <span class="font-semibold">{{ formatMoney(data.amount, data.currency) }}</span>
            </template>
          </Column>
          <Column field="status" header="Status" sortable>
            <template #body="{ data }">
              <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" />
            </template>
          </Column>
          <Column field="paid_at" header="Paid On">
            <template #body="{ data }">
              {{ data.paid_at ? formatDate(data.paid_at) : 'Not paid' }}
            </template>
          </Column>
        </DataTable>

        <div v-if="billingRecords.length === 0" class="rounded-lg border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">
          No billing records yet. Paid subscription transactions will appear here after checkout.
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'

const props = defineProps<{
  store: any
  subscription: any
  billing_records: any[]
}>()

const store = computed(() => props.store || {})
const subscription = computed(() => props.subscription || {})
const billingRecords = computed(() => Array.isArray(props.billing_records) ? props.billing_records : [])
const enabledModules = computed(() => {
  const modules = subscription.value.enabled_modules
  return Array.isArray(modules) ? modules : []
})
const planLimits = computed(() => {
  const limits = subscription.value.limits
  return Array.isArray(limits) ? limits : []
})
const planFeatures = computed(() => {
  const features = subscription.value.plan_features
  return Array.isArray(features) ? features.filter((feature) => typeof feature === 'string' && feature.trim()) : []
})

const formatCount = (value: number | string | null | undefined) => {
  const count = Number(value || 0)
  return new Intl.NumberFormat('en-PH', { maximumFractionDigits: 0 }).format(count)
}

const usagePercent = (used: number | string, limit: number | string) => {
  const maximum = Number(limit)
  if (!Number.isFinite(maximum) || maximum <= 0) return 0
  return Math.min(100, Math.max(0, (Number(used || 0) / maximum) * 100))
}

const formatMoney = (value: number | string, currency = 'PHP') => {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(Number(value || 0))
}

const formatDate = (value?: string | null) => {
  if (!value) return 'Unlimited'
  const parsed = new Date(value)
  if (Number.isNaN(parsed.getTime())) return value
  return new Intl.DateTimeFormat('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  }).format(parsed)
}

const endsAtLabel = computed(() => formatDate(subscription.value.ends_at))

const remainingLabel = computed(() => {
  const days = Number(subscription.value.days_remaining)
  if (!subscription.value.ends_at) return 'No expiration'
  if (Number.isNaN(days)) return 'No expiration'
  if (days < 0) return `Expired ${Math.abs(days)} day(s) ago`
  if (days === 0) return 'Ends today'
  return `${days} day(s) remaining`
})

const statusLabel = (status: string) => {
  const normalized = String(status || 'unknown').replace(/_/g, ' ')
  return normalized.replace(/\b\w/g, (c) => c.toUpperCase())
}

const statusSeverity = (status: string) => {
  const normalized = String(status || '').toLowerCase()
  if (normalized === 'succeeded' || normalized === 'paid') return 'success'
  if (normalized.includes('awaiting') || normalized === 'processing') return 'warn'
  if (normalized === 'failed' || normalized === 'cancelled' || normalized === 'canceled') return 'danger'
  return 'secondary'
}

const goToUpgrade = () => {
  const storeId = Number(store.value?.id || subscription.value?.store_id || 0)
  router.visit(`/subscription-plans${storeId ? `?store_id=${storeId}` : ''}`)
}
</script>
