<template>
  <div class="module-dashboard dashboard--crm space-y-5 pb-6 text-sm">
    <div class="dashboard-hero flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div><h1 class="text-2xl font-semibold text-slate-950">CRM Dashboard</h1><p class="mt-1 text-sm text-slate-500">Customer activity and the conversations needing your attention.</p></div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" :loading="loading" @click="refreshDashboard" />
    </div>
    <div v-if="loading" class="space-y-5">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><Skeleton v-for="i in 4" :key="i" height="118px" class="rounded-2xl" /></div>
      <div class="grid gap-4 xl:grid-cols-3"><Skeleton height="290px" class="rounded-2xl xl:col-span-2" /><Skeleton height="290px" class="rounded-2xl" /></div>
      <Skeleton v-for="i in 2" :key="i" height="190px" class="rounded-2xl" />
    </div>
    <div v-else-if="error" class="dashboard-panel rounded-2xl border border-red-200 bg-white p-6"><p class="font-semibold text-slate-900">The CRM dashboard could not load.</p><p class="mt-1 text-slate-500">{{ error }}</p><Button label="Try again" severity="warn" size="small" class="mt-4" @click="load" /></div>
    <template v-else-if="data">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <section v-for="(card, index) in cards" :key="card.title" class="dashboard-panel rounded-2xl border bg-white p-5" :class="index === 0 ? 'border-orange-200' : 'border-slate-200'">
          <div class="flex items-start justify-between"><p class="text-xs font-medium text-slate-500">{{ card.title }}</p><i :class="[card.icon, 'text-orange-500']" /></div>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ card.value }}</p><p class="mt-2 text-xs text-slate-500">{{ card.caption }}</p>
        </section>
      </div>
      <div v-if="!hasActivity" class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-8 text-center"><i class="pi pi-chart-line text-2xl text-slate-400" /><h2 class="mt-3 font-semibold text-slate-800">No CRM activity yet</h2><p class="mt-1 text-xs text-slate-500">Add a customer lead or respond to a chat to start seeing trends.</p><a href="/crm/leads" class="mt-3 inline-block text-xs font-medium text-orange-600">Go to Leads</a></div>
      <div class="grid gap-4 xl:grid-cols-3">
        <Card class="dashboard-panel border border-slate-200 shadow-sm xl:col-span-2"><template #title><div class="flex flex-wrap items-center justify-between gap-2"><span class="text-sm font-semibold text-slate-950">CRM activity</span><Select v-model="activityPeriod" :options="activityPeriods" optionLabel="label" optionValue="value" class="w-40" aria-label="CRM activity period" /></div></template><template #content><p class="mb-4 text-xs text-slate-500">{{ activityDescription }}</p><Skeleton v-if="activityLoading" height="14rem" class="rounded-xl" /><div v-else-if="activityError" class="flex h-56 flex-col items-center justify-center gap-2 text-xs text-red-600">{{ activityError }}<Button label="Try again" text size="small" @click="loadActivity" /></div><Chart v-else-if="hasPeriodActivity" type="line" :data="activityChart" :options="chartOptions" class="h-56" /><p v-else class="flex h-56 items-center justify-center text-xs text-slate-500">No activity in this period.</p></template></Card>
        <Card class="dashboard-panel border border-slate-200 shadow-sm"><template #title><span class="text-sm font-semibold text-slate-950">Lead pipeline</span></template><template #content><p class="mb-3 text-xs text-slate-500">Customers by current stage</p><Chart v-if="data.summary.leads" type="doughnut" :data="pipelineChart" :options="doughnutOptions" class="mx-auto h-44 max-w-52" /><p v-else class="flex h-44 items-center justify-center text-xs text-slate-500">No leads yet.</p></template></Card>
      </div>
      <div class="grid gap-4 xl:grid-cols-2">
        <Card class="border border-slate-100 shadow-sm"><template #title>Review ratings</template><template #content><Chart v-if="data.summary.reviews" type="bar" :data="ratingsChart" :options="chartOptions" class="h-64" /><p v-else class="flex h-64 items-center justify-center text-sm text-slate-500">No reviews yet.</p><p class="mt-3 text-sm text-slate-500">{{ data.summary.pending_reviews }} awaiting a reply · {{ data.summary.average_rating }} / 5 average</p></template></Card>
        <Card class="border border-slate-100 shadow-sm"><template #title>Return status</template><template #content><Chart v-if="data.summary.returns" type="doughnut" :data="returnsChart" :options="doughnutOptions" class="mx-auto h-64 max-w-sm" /><p v-else class="flex h-64 items-center justify-center text-sm text-slate-500">No returns yet.</p><p class="mt-3 text-sm text-slate-500">{{ data.summary.open_returns }} open returns need attention</p></template></Card>
      </div>
      <div class="grid gap-4 xl:grid-cols-2">
        <Card class="border border-slate-100 shadow-sm"><template #title>Recent leads</template><template #content><div v-if="!data.recent_leads.length" class="py-8 text-center text-sm text-slate-500">No leads to show.</div><a v-for="lead in data.recent_leads" :key="lead.id" href="/crm/leads" class="flex justify-between border-b border-slate-100 py-3 last:border-0"><span><strong class="block text-sm text-slate-800">{{ lead.full_name }}</strong><small class="text-slate-500">{{ lead.lead_code }}</small></span><Tag :value="format(lead.stage)" severity="info" /></a></template></Card>
        <Card class="border border-slate-100 shadow-sm"><template #title>Recent returns</template><template #content><div v-if="!data.recent_returns.length" class="py-8 text-center text-sm text-slate-500">No returns to show.</div><a v-for="item in data.recent_returns" :key="item.id" href="/crm/returns" class="flex justify-between border-b border-slate-100 py-3 last:border-0"><span class="text-sm font-medium text-slate-800">{{ item.return_number || `Return #${item.id}` }}</span><Tag :value="format(item.status)" severity="warn" /></a></template></Card>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Card from 'primevue/card'
import Chart from 'primevue/chart'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import crmService from '@/services/crm.service'

type ActivityPeriod = 'week' | 'month' | 'year'
const activityPeriods: { label: string; value: ActivityPeriod }[] = [{ label: '7 days', value: 'week' }, { label: 'Per month', value: 'month' }, { label: 'Per year', value: 'year' }]
const activityPeriod = ref<ActivityPeriod>('week')
const activityPoints = ref<any[]>([])
const activityLoading = ref(true)
const activityError = ref('')
const activityDescription = computed(() => activityPeriod.value === 'week' ? 'Daily activity over the last 7 days' : activityPeriod.value === 'month' ? 'Monthly activity over the last 12 months' : 'Yearly activity over the last 5 years')
const data = ref<any>(null)
const loading = ref(true)
const error = ref('')
const load = async () => { loading.value = true; error.value = ''; try { data.value = (await crmService.getDashboardOverview({ days: 30 })).data } catch { error.value = 'Could not load the CRM dashboard.' } finally { loading.value = false } }
let activityRequest = 0
const loadActivity = async () => {
  const request = ++activityRequest
  activityLoading.value = true
  activityError.value = ''
  try {
    const response = await crmService.getDashboardActivity(activityPeriod.value)
    if (request === activityRequest) activityPoints.value = response?.data?.points || []
  } catch {
    if (request === activityRequest) activityError.value = 'Could not load CRM activity.'
  } finally {
    if (request === activityRequest) activityLoading.value = false
  }
}
const refreshDashboard = () => { load(); loadActivity() }
watch(activityPeriod, loadActivity)
onMounted(refreshDashboard)
const format = (value: string) => String(value || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
const cards = computed(() => data.value ? [
  { title: 'Leads', value: data.value.summary.leads, caption: `${data.value.summary.new_leads} new · ${data.value.summary.open_leads} open`, icon: 'pi pi-users', href: '/crm/leads' },
  { title: 'Customer chats', value: data.value.summary.chats, caption: `${data.value.summary.unread_messages} unread customer messages`, icon: 'pi pi-comments', href: '/crm/chats' },
  { title: 'Reviews', value: data.value.summary.reviews, caption: `${data.value.summary.pending_reviews} awaiting reply`, icon: 'pi pi-star', href: '/crm/reviews' },
  { title: 'Returns', value: data.value.summary.returns, caption: `${data.value.summary.open_returns} open`, icon: 'pi pi-replay', href: '/crm/returns' },
] : [])
const hasActivity = computed(() => cards.value.some((card: any) => Number(card.value) > 0))
const hasPeriodActivity = computed(() => activityPoints.value.some((point: any) => point.leads || point.chats || point.reviews || point.returns))
const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' as const } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
const doughnutOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' as const } } }
const activityChart = computed(() => ({ labels: activityPoints.value.map((v: any) => v.label), datasets: [
  { label: 'Leads', data: activityPoints.value.map((v: any) => v.leads), borderColor: '#f97316', tension: .3 },
  { label: 'Chats', data: activityPoints.value.map((v: any) => v.chats), borderColor: '#3b82f6', tension: .3 },
  { label: 'Reviews', data: activityPoints.value.map((v: any) => v.reviews), borderColor: '#10b981', tension: .3 },
  { label: 'Returns', data: activityPoints.value.map((v: any) => v.returns), borderColor: '#ef4444', tension: .3 },
] }))
const pipelineChart = computed(() => ({ labels: ['New', 'Contacted', 'Qualified', 'Proposal', 'Won', 'Lost'], datasets: [{ label: 'Leads', data: ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'].map(s => Number(data.value?.stages?.[s] || 0)), backgroundColor: ['#f97316', '#38bdf8', '#34d399', '#a78bfa', '#64748b', '#f87171'], borderWidth: 0 }] }))
const ratingsChart = computed(() => ({ labels: ['1 star', '2 stars', '3 stars', '4 stars', '5 stars'], datasets: [{ label: 'Reviews', data: [1, 2, 3, 4, 5].map(n => Number(data.value?.ratings?.[n] || 0)), backgroundColor: '#fbbf24' }] }))
const returnsChart = computed(() => ({ labels: Object.keys(data.value?.return_statuses || {}).map(format), datasets: [{ data: Object.values(data.value?.return_statuses || {}), backgroundColor: ['#f97316', '#3b82f6', '#10b981', '#ef4444', '#a78bfa', '#64748b'] }] }))
</script>
