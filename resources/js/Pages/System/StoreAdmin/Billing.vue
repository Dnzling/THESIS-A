<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Billing</h1>
      </div>
    </div>

    <div class="grid gap-4 ">
      <Card class="border border-slate-200 shadow-sm">
        <template #title>Subscription Plan</template>
        <template #content>
          <div class="grid gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-4 sm:divide-x sm:divide-slate-200">
            <div class="sm:pr-4">
              <div class="text-xs font-semibold uppercase text-slate-400">Current Plan</div>
              <div class="mt-2 text-lg font-semibold text-slate-950">{{ subscription.plan_name }}</div>
            </div>
            <div class="sm:px-4">
              <div class="text-xs font-semibold uppercase text-slate-400">{{ subscription.status === 'trial' ? 'Trial Ends' : 'Ends On' }}</div>
              <div class="mt-2 text-lg font-semibold text-slate-950">{{ endsAtLabel }}</div>
            </div>
            <div class="sm:pl-4"><div class="text-xs font-semibold uppercase text-slate-400">Billing Amount</div><div class="mt-2 text-lg font-semibold text-slate-950">{{ formatMoney(subscription.monthly_price) }}<span class="text-sm font-normal text-slate-500"> / month</span></div></div>
               <Button v-if="subscription.next_plan_name" :label="`Upgrade to ${subscription.next_plan_name}`" rounded @click="goToUpgrade" size="small" />
          </div>

          <div class="mt-5 rounded-lg border p-4" :class="subscription.sales_paused ? 'border-red-100 bg-red-50 text-red-700' : subscription.in_grace ? 'border-orange-100 bg-orange-50 text-orange-700' : 'border-green-100 bg-green-50 text-green-700'">
            <div class="font-semibold">{{ subscription.sales_paused ? 'New sales paused' : subscription.in_grace ? 'Renewal grace period' : subscription.status === 'trial' ? 'Free Trial' : 'Subscription active' }}</div>
            <p class="mt-1 text-sm">
              {{ subscription.sales_paused ? 'Subscribe to accept new ecommerce and POS orders. Existing records and orders remain available.' : subscription.in_grace ? 'Your plan has ended, but new sales remain available during the 7-day renewal grace period.' : 'Your store can continue using the modules included in this plan.' }}
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

            <div v-if="planLimits.length" class="mt-4 divide-y divide-slate-100">
              <div v-for="limit in planLimits" :key="limit.key" class="py-3 first:pt-0 last:pb-0">
                <div class="flex items-center justify-between gap-3 text-sm">
                  <span class="font-medium text-slate-700">{{ limit.label }}</span>
                  <span class="font-semibold text-slate-900">{{ formatCount(limit.used) }} <span class="font-normal text-slate-500">{{ limit.limit == null ? 'used' : `of ${formatCount(limit.limit)} used` }}</span></span>
                </div>
                <ProgressBar v-if="limit.limit != null" :value="usagePercent(limit.used, limit.limit)" :showValue="false" class="mt-2 overflow-hidden" style="height: 8px" />
              </div>
            </div>
            <div v-else class="mt-3 text-sm text-slate-500">No account, branch, or product caps are configured for this plan.</div>
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
          rowHover
          dataKey="id"
          class="p-datatable-sm text-xs"
        >
          <Column field="created_at" header="Date">
            <template #body="{ data }">
              <div class="text-sm font-medium text-slate-900">{{ formatDate(data.created_at) }}</div>
            </template>
          </Column>
          <Column field="plan" header="Plan">
            <template #body="{ data }">
              <div class="text-xs font-semibold capitalize text-slate-900">{{ data.plan }}</div>
              <div class="text-xs capitalize text-slate-500">{{ data.billing_cycle }} · {{ data.months }} month(s)</div>
            </template>
          </Column>
          <Column field="payment_method" header="Method" />
          <Column field="amount" header="Amount">
            <template #body="{ data }">
              <span class="text-xs font-semibold">{{ formatMoney(data.amount, data.currency) }}</span>
            </template>
          </Column>
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="statusLabel(data.status)" :severity="statusSeverity(data.status)" class="text-xs" />
            </template>
          </Column>
          <Column header=""><template #body="{ data }"><Button label="View" size="small" text severity="warn" @click="selectedRecord = data" /></template></Column>
        </DataTable>

        <div v-if="billingRecords.length === 0" class="rounded-lg border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500">
          No billing records yet. Paid subscription transactions will appear here after checkout.
        </div>
      </template>
    </Card>
    <Dialog :visible="!!selectedRecord" modal header="Payment details" class="w-[min(92vw,520px)]" @update:visible="selectedRecord = null">
      <div v-if="selectedRecord" class="space-y-3 text-sm">
        <div class="rounded-lg bg-slate-50 p-4"><div class="text-xs uppercase tracking-wide text-slate-500">Amount</div><div class="mt-1 text-xl font-semibold text-slate-950">{{ formatMoney(selectedRecord.amount, selectedRecord.currency) }}</div></div>
        <div v-for="detail in recordDetails" :key="detail.label" class="flex justify-between gap-4 border-b border-slate-100 pb-2 last:border-0"><span class="shrink-0 text-slate-500">{{ detail.label }}</span><span class="break-all text-right font-medium text-slate-900">{{ detail.value }}</span></div>
      </div>
      <template #footer><Button label="Close" size="small" severity="secondary" outlined @click="selectedRecord = null" /></template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import ProgressBar from 'primevue/progressbar'
import Dialog from 'primevue/dialog'

const props = defineProps<{
  store: any
  subscription: any
  billing_records: any[]
}>()

const store = computed(() => props.store || {})
const subscription = computed(() => props.subscription || {})
const billingRecords = computed(() => Array.isArray(props.billing_records) ? props.billing_records : [])
const selectedRecord = ref<any | null>(null)
const enabledModules = computed(() => {
  const modules = subscription.value.enabled_modules
  return Array.isArray(modules) ? modules : []
})
const planLimits = computed(() => {
  const limits = subscription.value.limits
  return Array.isArray(limits) ? limits : []
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

const recordDetails = computed(() => {
  const record = selectedRecord.value
  if (!record) return []
  return [
    { label: 'Reference', value: record.reference || 'Not available' },
    { label: 'Plan', value: record.plan },
    { label: 'Billing cycle', value: `${statusLabel(record.billing_cycle)} (${record.months} month${record.months === 1 ? '' : 's'})` },
    { label: 'Payment method', value: record.payment_method || 'Not available' },
    { label: 'Status', value: statusLabel(record.status) },
    { label: 'Created on', value: formatDate(record.created_at) },
    { label: 'Paid on', value: record.paid_at ? formatDate(record.paid_at) : 'Not paid' },
  ]
})

const goToUpgrade = () => {
  const storeId = Number(store.value?.id || subscription.value?.store_id || 0)
  router.visit(`/subscription-plans${storeId ? `?store_id=${storeId}` : ''}`)
}
</script>
