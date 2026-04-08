<template>
  <div class="space-y-6 p-6">
    <div class="rounded-3xl border border-slate-200 bg-linear-to-r from-emerald-50 via-cyan-50 to-blue-50 p-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 class="text-3xl font-bold tracking-tight text-slate-900">Finance Dashboard</h1>
          <p class="mt-1 text-sm text-slate-600">MVP overview of payables, operating spend, and payroll cash pressure.</p>
        </div>
        <Button icon="pi pi-refresh" label="Refresh" outlined :loading="loading" @click="loadDashboard" />
      </div>
      <div v-if="loadError" class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        {{ loadError }}
      </div>
    </div>
  
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
      <Card class="shadow-sm hover:shadow-md transition-shadow">
        <template #content>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-500">Payables</p>
              <i class="pi pi-credit-card text-blue-400 text-sm"></i>
            </div>
            <p class="text-xl font-semibold text-gray-900">PHP {{ formatMoney(stats.payables) }}</p>
          </div>
        </template>
      </Card>
  
      <Card class="shadow-sm hover:shadow-md transition-shadow">
        <template #content>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-500">Invoices Due</p>
              <i class="pi pi-file text-indigo-400 text-sm"></i>
            </div>
            <p class="text-xl font-semibold text-gray-900">PHP {{ formatMoney(stats.invoices_due) }}</p>
          </div>
        </template>
      </Card>
  
      <Card class="shadow-sm hover:shadow-md transition-shadow">
        <template #content>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-500">Payments Completed</p>
              <i class="pi pi-check-circle text-emerald-400 text-sm"></i>
            </div>
            <p class="text-xl font-semibold text-gray-900">PHP {{ formatMoney(stats.payments_completed) }}</p>
          </div>
        </template>
      </Card>
  
      <Card class="shadow-sm hover:shadow-md transition-shadow">
        <template #content>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-500">Expenses Pending</p>
              <i class="pi pi-clock text-amber-400 text-sm"></i>
            </div>
            <p class="text-xl font-semibold text-gray-900">PHP {{ formatMoney(stats.expenses_pending) }}</p>
          </div>
        </template>
      </Card>
  
      <Card class="shadow-sm hover:shadow-md transition-shadow">
        <template #content>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <p class="text-sm font-medium text-gray-500">Payroll Pending</p>
              <i class="pi pi-users text-rose-400 text-sm"></i>
            </div>
            <p class="text-xl font-semibold text-gray-900">PHP {{ formatMoney(stats.payroll_pending) }}</p>
          </div>
        </template>
      </Card>
    </div>
  
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <Card class="rounded-2xl border border-slate-200 shadow-sm xl:col-span-2">
        <template #title>
          <div class="flex items-center justify-between">
            <span class="text-base font-semibold text-slate-800">6-Month Outflow Trend</span>
            <Tag value="Chart.js" severity="info" />
          </div>
        </template>
        <template #content>
          <Chart type="line" :data="outflowTrendData" :options="lineOptions" class="h-80" />
        </template>
      </Card>
  
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center justify-between gap-2">
            <span class="text-base font-semibold text-slate-800">Overdue Invoices</span>
            <Button text size="small" label="View All" @click="goTo('finance.payables')" />
          </div>
        </template>
        <template #content>
          <div v-if="overdueInvoices.length === 0" class="flex h-80 items-center justify-center text-sm text-slate-500">
            No overdue invoices right now.
          </div>
          <div v-else class="space-y-3">
            <div v-for="invoice in overdueInvoices" :key="invoice.id || invoice.reference"
              class="rounded-xl border border-red-100 bg-red-50/60 p-3">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold text-slate-900">{{ invoice.reference || 'Invoice' }}</p>
                  <p class="mt-1 text-xs text-slate-600">Due {{ formatDate(invoice.due_date) }}</p>
                </div>
                <Tag severity="danger" :value="`${invoice.daysOverdue}d overdue`" />
              </div>
              <div class="mt-2 flex items-center justify-between text-sm">
                <span class="text-slate-500">Amount</span>
                <span class="font-semibold text-red-700">PHP {{ formatMoney(invoice.amount || invoice.payment_amount)
                  }}</span>
              </div>
            </div>
          </div>
        </template>
      </Card>
    </div>
  
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
      <Card class="rounded-2xl border border-slate-200 shadow-sm xl:col-span-2">
        <template #title>
          <span class="text-base font-semibold text-slate-800">Pending Workload by Team</span>
        </template>
        <template #content>
          <Chart type="bar" :data="workloadData" :options="barOptions" class="h-72" />
        </template>
      </Card>
  
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <span class="text-base font-semibold text-slate-800">Quick Actions</span>
        </template>
        <template #content>
          <div class="space-y-3">
            <Button fluid icon="pi pi-wallet" label="Review Payables" outlined @click="goTo('finance.payables')" />
            <Button fluid icon="pi pi-chart-line" label="Cashflow" outlined @click="goTo('finance.cashflow')" />
            <Button fluid icon="pi pi-money-bill" label="Process Expenses" outlined @click="goTo('finance.expenses')" />
            <Button fluid icon="pi pi-users" label="Approve Payroll" outlined @click="goTo('finance.payroll')" />
            <Button fluid icon="pi pi-chart-bar" label="View Reports" outlined @click="goTo('finance.reports')" />
          </div>
        </template>
      </Card>
    </div>

    <Card class="rounded-2xl border border-slate-200 shadow-sm">
      <template #title>
        <span class="text-base font-semibold text-slate-800">Needed For Approval</span>
      </template>
      <template #content>
        <div class="space-y-3">
          <div
            v-for="item in approvalsNeeded"
            :key="`${item.source_module}-${item.workflow}`"
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
          >
            <div class="text-sm text-slate-700">
              <span class="font-semibold">{{ item.source_module }}</span>
              <span class="mx-2 text-slate-400">--></span>
              <span>{{ item.workflow }}</span>
              <span class="mx-2 text-slate-400">--></span>
              <span class="font-medium">{{ item.target_approval }}</span>
            </div>
            <div class="flex items-center gap-2">
              <Tag :value="`${item.pending_count} pending`" :severity="item.pending_count > 0 ? 'warning' : 'success'" />
              <Button
                v-if="item.pending_count > 0"
                size="small"
                text
                label="Open"
                @click="openApprovalRoute(item.route)"
              />
            </div>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Chart from 'primevue/chart'
import Tag from 'primevue/tag'
import financeService from '../../../services/finance.service'

const router = useRouter()

const loading = ref(false)
const loadError = ref('')

const stats = ref({
  payables: 0,
  invoices_due: 0,
  payments_completed: 0,
  expenses_pending: 0,
  payroll_pending: 0,
  approvals_needed: [] as Array<{
    source_module: string
    workflow: string
    target_approval: string
    pending_count: number
    route: string
  }>,
})

const approvalsNeeded = computed(() => stats.value.approvals_needed || [])

const invoices = ref<any[]>([])
const expenses = ref<any[]>([])
const payrolls = ref<any[]>([])

const formatMoney = (value: number | string) => {
  const amount = typeof value === 'string' ? parseFloat(value) : value || 0
  return new Intl.NumberFormat('en-PH', { maximumFractionDigits: 2 }).format(Number.isFinite(amount) ? amount : 0)
}

const toAmount = (value: unknown) => {
  const numeric = Number(value)
  return Number.isFinite(numeric) ? numeric : 0
}

const toRows = (payload: any) => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  return []
}

const parseDate = (value: unknown) => {
  if (!value) return null
  const dt = new Date(String(value))
  return Number.isNaN(dt.getTime()) ? null : dt
}

const formatDate = (value: unknown) => {
  const dt = parseDate(value)
  if (!dt) return '-'
  return dt.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: '2-digit' })
}

const monthBuckets = computed(() => {
  const now = new Date()
  const months: { key: string; label: string }[] = []
  for (let i = 5; i >= 0; i -= 1) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
    const key = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    const label = d.toLocaleString('en-PH', { month: 'short' })
    months.push({ key, label })
  }
  return months
})

const outflowTrendData = computed(() => {
  const labels = monthBuckets.value.map((m) => m.label)
  const invoiceMap: Record<string, number> = {}
  const expenseMap: Record<string, number> = {}
  const payrollMap: Record<string, number> = {}

  monthBuckets.value.forEach((m) => {
    invoiceMap[m.key] = 0
    expenseMap[m.key] = 0
    payrollMap[m.key] = 0
  })

  invoices.value.forEach((row) => {
    const dt = parseDate(row?.due_date || row?.created_at)
    if (!dt) return
    const key = `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}`
    if (key in invoiceMap) invoiceMap[key] = (invoiceMap[key] || 0) + toAmount(row?.amount || row?.payment_amount)
  })

  expenses.value.forEach((row) => {
    const dt = parseDate(row?.created_at)
    if (!dt) return
    const key = `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}`
    if (key in expenseMap) expenseMap[key] = (expenseMap[key] || 0) + toAmount(row?.amount)
  })

  payrolls.value.forEach((row) => {
    const dt = parseDate(row?.created_at)
    if (!dt) return
    const key = `${dt.getFullYear()}-${String(dt.getMonth() + 1).padStart(2, '0')}`
    if (key in payrollMap) payrollMap[key] = (payrollMap[key] || 0) + toAmount(row?.net_salary)
  })

  return {
    labels,
    datasets: [
      {
        label: 'Invoices',
        data: monthBuckets.value.map((m) => invoiceMap[m.key]),
        borderColor: '#1d4ed8',
        backgroundColor: 'rgba(29, 78, 216, 0.15)',
        fill: true,
        tension: 0.35,
      },
      {
        label: 'Expenses',
        data: monthBuckets.value.map((m) => expenseMap[m.key]),
        borderColor: '#d97706',
        backgroundColor: 'rgba(217, 119, 6, 0.15)',
        fill: true,
        tension: 0.35,
      },
      {
        label: 'Payroll',
        data: monthBuckets.value.map((m) => payrollMap[m.key]),
        borderColor: '#be123c',
        backgroundColor: 'rgba(190, 18, 60, 0.15)',
        fill: true,
        tension: 0.35,
      },
    ],
  }
})

const overdueInvoices = computed(() => {
  const today = new Date()
  return invoices.value
    .map((row) => {
      const due = parseDate(row?.due_date)
      const amount = toAmount(row?.amount || row?.payment_amount)
      const status = String(row?.status || '').toLowerCase()
      if (!due || amount <= 0 || status === 'paid') return null

      const daysOverdue = Math.floor((today.getTime() - due.getTime()) / (1000 * 60 * 60 * 24))
      if (daysOverdue <= 0) return null

      return {
        ...row,
        amount,
        daysOverdue,
      }
    })
    .filter((row): row is any => !!row)
    .sort((a, b) => b.daysOverdue - a.daysOverdue)
    .slice(0, 6)
})

const workloadData = computed(() => ({
  labels: ['Payables', 'Expenses', 'Payroll'],
  datasets: [
    {
      label: 'Pending Amount (PHP)',
      data: [
        toAmount(stats.value.invoices_due),
        toAmount(stats.value.expenses_pending),
        toAmount(stats.value.payroll_pending),
      ],
      borderRadius: 8,
      backgroundColor: ['#2563eb', '#f59e0b', '#e11d48'],
    },
  ],
}))

const lineOptions = {
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'bottom' as const },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value: number | string) => `PHP ${formatMoney(value as number)}`,
      },
    },
  },
}

const barOptions = {
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value: number | string) => `PHP ${formatMoney(value as number)}`,
      },
    },
  },
}

const goTo = (name: string) => router.push({ name })
const openApprovalRoute = (path: string) => {
  if (!path) return
  window.location.href = path
}

const settle = async <T>(promise: Promise<T>) => {
  try {
    const value = await promise
    return { ok: true as const, value }
  } catch (error) {
    return { ok: false as const, error }
  }
}

const loadDashboard = async () => {
  loading.value = true
  loadError.value = ''

  try {
    const [dashboardRes, invoiceRes, expenseRes, payrollRes] = await Promise.all([
      settle(financeService.getDashboard()),
      settle(financeService.getInvoices({ per_page: 100 })),
      settle(financeService.getExpenses({ per_page: 100 })),
      settle(financeService.getPayroll({ per_page: 100 })),
    ])

    if (dashboardRes.ok) {
      stats.value = dashboardRes.value.data || stats.value
    }

    if (invoiceRes.ok) {
      invoices.value = toRows(invoiceRes.value)
    }

    if (expenseRes.ok) {
      expenses.value = toRows(expenseRes.value)
    }

    if (payrollRes.ok) {
      payrolls.value = toRows(payrollRes.value)
    }

    const anyFailed = [dashboardRes, invoiceRes, expenseRes, payrollRes].some((x) => !x.ok)
    if (anyFailed) {
      loadError.value = 'Some data sources could not be loaded. Dashboard is showing available data only.'
    }
  } catch {
    loadError.value = 'Unable to load finance dashboard data right now.'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>
