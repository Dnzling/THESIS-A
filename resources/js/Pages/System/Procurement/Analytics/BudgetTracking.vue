<template>
  <div class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Budget Tracking</h1>
        <p class="mt-1 text-sm text-slate-500">Compare store budget allocations with approved and active purchase orders.</p>
      </div>
      <div class="flex items-end gap-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Year</label>
          <Select v-model="selectedYear" :options="yearOptions" class="w-28" @change="loadBudgetData" />
        </div>
        <Button icon="pi pi-refresh" label="Refresh" size="small" severity="secondary" outlined :loading="loading" @click="loadBudgetData" />
      </div>
    </header>

    <Message v-if="loadError" severity="error" :closable="false">{{ loadError }}</Message>
    <div v-if="loading" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Skeleton v-for="item in 4" :key="item" height="6rem" borderRadius="0.75rem" />
    </div>
    <section v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <Card v-for="card in summaryCards" :key="card.label" class="border border-slate-200 shadow-none">
        <template #content>
          <p class="text-xs font-medium text-slate-500">{{ card.label }}</p>
          <p class="mt-2 text-xl font-semibold text-slate-900">{{ card.value }}</p>
          <p v-if="card.hint" class="mt-1 text-xs text-slate-500">{{ card.hint }}</p>
        </template>
      </Card>
    </section>

    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <p class="text-sm font-semibold text-slate-900">Annual Budget Utilization</p>
            <p class="mt-1 text-xs text-slate-500">Store-wide budgets compared with commitments in approved and active POs.</p>
          </div>
          <Badge :value="budgetData.budget_status || 'On Track'" :severity="budgetStatusSeverity(budgetData.budget_status)" />
        </div>
        <div class="mt-4 flex items-center gap-3">
          <ProgressBar :value="Math.min(Number(budgetData.utilization_percentage || 0), 100)" class="h-3 flex-1" />
          <span class="w-14 text-right text-sm font-semibold text-slate-700">{{ number(budgetData.utilization_percentage) }}%</span>
        </div>
        <p v-if="!Number(budgetData.annual_budget) && Number(budgetData.period_spend)" class="mt-3 text-xs text-amber-700">
          Purchase orders have been committed, but no budget allocation is recorded for {{ selectedYear }}.
        </p>
        <p v-else-if="!Number(budgetData.annual_budget)" class="mt-3 text-xs text-slate-500">
          No budget allocation has been recorded for {{ selectedYear }}. Budget entries are managed from Finance.
        </p>
      </template>
    </Card>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
      <Card class="border border-slate-200 shadow-none">
        <template #title><span class="text-base font-semibold">Monthly Budget vs PO Commitments</span></template>
        <template #content>
          <Chart v-if="monthlyRows.length" type="bar" :data="monthlyChartData" :options="chartOptions" class="h-72 w-full" />
          <div v-else class="py-10 text-center text-sm text-slate-500">No monthly data available.</div>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-none">
        <template #title><span class="text-base font-semibold">Budget by Category</span></template>
        <template #content>
          <DataTable v-if="!loading" :value="categoryBudgets" rowHover responsiveLayout="scroll" class="text-sm">
            <template #empty>
              <div class="py-8 text-center text-sm text-slate-500">No purchase-order spend or budget categories found for {{ selectedYear }}.</div>
            </template>
            <Column field="category_name" header="Category" sortable />
            <Column field="budget" header="Budget" sortable>
              <template #body="{ data }">{{ currency(data.budget) }}</template>
            </Column>
            <Column field="spent" header="PO Commitments" sortable>
              <template #body="{ data }">{{ currency(data.spent) }}</template>
            </Column>
            <Column field="remaining" header="Remaining" sortable>
              <template #body="{ data }">
                <span :class="Number(data.remaining) < 0 ? 'font-semibold text-red-700' : 'font-medium text-slate-800'">{{ currency(data.remaining) }}</span>
              </template>
            </Column>
            <Column header="Usage">
              <template #body="{ data }">
                <div class="flex min-w-32 items-center gap-2">
                  <ProgressBar :value="Math.min(Number(data.percentage || 0), 100)" class="h-2 flex-1" />
                  <span class="w-12 text-right text-xs text-slate-600">{{ data.percentage === null ? 'No budget' : `${number(data.percentage)}%` }}</span>
                </div>
              </template>
            </Column>
            <Column header="Status">
              <template #body="{ data }"><Badge :value="categoryStatusLabel(data.status)" :severity="categoryStatusSeverity(data.status)" /></template>
            </Column>
          </DataTable>
          <div v-else class="space-y-2">
            <Skeleton v-for="row in 5" :key="row" height="2.5rem" borderRadius="0.5rem" />
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import procurementService from '../../../../services/procurement.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Select from 'primevue/select'
import Badge from 'primevue/badge'
import ProgressBar from 'primevue/progressbar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import Message from 'primevue/message'
import Chart from 'primevue/chart'

const currentYear = new Date().getFullYear()
const selectedYear = ref(currentYear)
const yearOptions = Array.from({ length: 6 }, (_, index) => currentYear - index)
const loading = ref(false)
const loadError = ref('')
const categoryBudgets = ref<any[]>([])
const monthlyRows = ref<any[]>([])
const budgetData = ref<any>({})

const summaryCards = computed(() => [
  { label: 'Annual Budget', value: currency(budgetData.value.annual_budget), hint: `Recorded for ${selectedYear.value}` },
  { label: 'Committed PO Spend', value: currency(budgetData.value.ytd_spend), hint: 'Approved and active purchase orders' },
  { label: 'Budget Remaining', value: currency(budgetData.value.remaining_budget), hint: 'Annual budget less commitments to date' },
  { label: 'Current Month PO Spend', value: currency(budgetData.value.current_month_spend), hint: 'Current-year month only' },
])

const monthlyChartData = computed(() => ({
  labels: monthlyRows.value.map(row => monthLabel(row.month)),
  datasets: [
    { label: 'Budgeted', data: monthlyRows.value.map(row => Number(row.budgeted || 0)), backgroundColor: '#fed7aa', borderColor: '#f97316', borderWidth: 1, borderRadius: 5 },
    { label: 'PO Commitments', data: monthlyRows.value.map(row => Number(row.actual || 0)), backgroundColor: '#334155', borderColor: '#334155', borderWidth: 1, borderRadius: 5 },
  ],
}))
const chartOptions = {
  maintainAspectRatio: false,
  responsive: true,
  plugins: { legend: { position: 'bottom' } },
  scales: {
    y: { beginAtZero: true, ticks: { callback: (value: number) => currency(value) } },
    x: { grid: { display: false } },
  },
}

async function loadBudgetData() {
  loading.value = true
  loadError.value = ''
  try {
    const response = await procurementService.getBudgetTracking({ year: selectedYear.value })
    const payload = response?.data?.data ?? response?.data ?? {}
    budgetData.value = payload
    categoryBudgets.value = Array.isArray(payload.categories) ? payload.categories : []
    monthlyRows.value = Array.isArray(payload.monthly) ? payload.monthly : []
  } catch (error: any) {
    loadError.value = error?.response?.data?.message || 'Unable to load procurement budget tracking.'
    budgetData.value = {}
    categoryBudgets.value = []
    monthlyRows.value = []
  } finally {
    loading.value = false
  }
}

function currency(value: unknown) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 2 }).format(Number(value || 0))
}
function number(value: unknown) {
  return Number(value || 0).toLocaleString('en-PH', { maximumFractionDigits: 1 })
}
function monthLabel(value: string) {
  const [year, month] = String(value || '').split('-').map(Number)
  return year && month ? new Intl.DateTimeFormat('en-PH', { month: 'short' }).format(new Date(year, month - 1, 1)) : value
}
function budgetStatusSeverity(value: string) {
  if (value === 'Over Budget') return 'danger'
  if (value === 'At Risk') return 'warn'
  if (value === 'Unbudgeted') return 'secondary'
  return 'success'
}
function categoryStatusLabel(value: string) {
  return ({ over_budget: 'Over Budget', at_risk: 'At Risk', unbudgeted: 'Unbudgeted', on_track: 'On Track' } as Record<string, string>)[value] || 'On Track'
}
function categoryStatusSeverity(value: string) {
  return ({ over_budget: 'danger', at_risk: 'warn', unbudgeted: 'secondary', on_track: 'success' } as Record<string, string>)[value] || 'secondary'
}

onMounted(loadBudgetData)
</script>
