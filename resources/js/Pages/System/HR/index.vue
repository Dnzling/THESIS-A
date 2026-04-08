<template>
  <div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">HR Operations Dashboard</h1>
          <p class="mt-1 text-sm text-slate-500">
            {{ todayStats.day_name || 'Today' }} · {{ todayStats.date || '-' }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <Tag :value="healthBadge.label" :severity="healthBadge.severity" rounded />
          <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined :loading="loading" @click="reloadDashboard" />
        </div>
      </div>
    </div>

    <Card class="rounded-2xl border border-slate-200 shadow-sm">
      <template #title>
        <div class="flex items-center justify-between">
          <span class="text-sm font-semibold text-slate-700">Needs Attention Today</span>
          <small class="text-slate-500">Highest-priority HR queue</small>
        </div>
      </template>
      <template #content>
        <div class="grid gap-3 md:grid-cols-3">
          <button
            class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-left transition hover:bg-rose-100"
            @click="router.push({ name: 'hr.leave' })"
          >
            <p class="text-xs uppercase tracking-wider text-rose-600">Pending Leave Requests</p>
            <p class="mt-1 text-3xl font-bold text-rose-700">{{ todayStats.pending_leave_requests }}</p>
            <p class="mt-1 text-xs text-rose-700/80">Review and approve leave requests</p>
          </button>
          <button
            class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-left transition hover:bg-amber-100"
            @click="router.push({ name: 'hr.attendance' })"
          >
            <p class="text-xs uppercase tracking-wider text-amber-700">Pending Overtime</p>
            <p class="mt-1 text-3xl font-bold text-amber-700">{{ todayStats.pending_overtime }}</p>
            <p class="mt-1 text-xs text-amber-700/80">Validate overtime before payroll cut-off</p>
          </button>
          <button
            class="rounded-xl border border-orange-200 bg-orange-50 p-4 text-left transition hover:bg-orange-100"
            @click="router.push({ name: 'hr.shifts' })"
          >
            <p class="text-xs uppercase tracking-wider text-orange-700">Uncovered Shifts</p>
            <p class="mt-1 text-3xl font-bold text-orange-700">{{ coverageGap }}</p>
            <p class="mt-1 text-xs text-orange-700/80">Reschedule to avoid operations gaps</p>
          </button>
        </div>
      </template>
    </Card>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
      <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-slate-500">Total Employees</p>
        <p class="mt-2 text-3xl font-bold text-slate-900">{{ todayStats.total_employees }}</p>
        <p class="mt-1 text-xs text-slate-500">Scheduled today: {{ todayStats.scheduled_today }}</p>
      </div>
      <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-emerald-700">Attendance Rate</p>
        <p class="mt-2 text-3xl font-bold text-emerald-700">{{ todayStats.attendance_rate }}%</p>
        <p class="mt-1 text-xs text-emerald-700/80">Present: {{ todayStats.attended_today }}</p>
      </div>
      <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-amber-700">On Leave Today</p>
        <p class="mt-2 text-3xl font-bold text-amber-700">{{ todayStats.on_leave_today }}</p>
        <p class="mt-1 text-xs text-amber-700/80">Planned leave count</p>
      </div>
      <div class="rounded-2xl border border-rose-100 bg-rose-50 p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-rose-700">Absent Today</p>
        <p class="mt-2 text-3xl font-bold text-rose-700">{{ todayStats.absent_today }}</p>
        <p class="mt-1 text-xs text-rose-700/80">Unplanned absences</p>
      </div>
      <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-indigo-700">Productive Coverage</p>
        <p class="mt-2 text-3xl font-bold text-indigo-700">{{ productiveCoverage }}%</p>
        <p class="mt-1 text-xs text-indigo-700/80">Present vs scheduled</p>
      </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-700">Weekly Attendance Trend</span>
            <Tag :value="weeklyStats.attendance_summary" severity="info" size="small" />
          </div>
        </template>
        <template #content>
          <Chart
            v-if="attendanceChartData"
            type="bar"
            :data="attendanceChartData"
            :options="attendanceChartOptions"
            class="h-64"
          />
          <div v-else class="py-12 text-center text-xs text-slate-500">
            {{ weeklyError || 'Loading attendance history...' }}
          </div>
          <div class="mt-4 grid grid-cols-2 gap-3 text-center sm:grid-cols-4">
            <div class="rounded-2xl bg-emerald-50 p-3">
              <p class="text-xs text-slate-500">Present</p>
              <p class="text-lg font-semibold text-emerald-700">{{ weeklyTotals.present }}</p>
            </div>
            <div class="rounded-2xl bg-orange-50 p-3">
              <p class="text-xs text-slate-500">Late</p>
              <p class="text-lg font-semibold text-orange-700">{{ weeklyTotals.late }}</p>
            </div>
            <div class="rounded-2xl bg-amber-50 p-3">
              <p class="text-xs text-slate-500">Leave</p>
              <p class="text-lg font-semibold text-amber-700">{{ weeklyTotals.leave }}</p>
            </div>
            <div class="rounded-2xl bg-rose-50 p-3">
              <p class="text-xs text-slate-500">Absent</p>
              <p class="text-lg font-semibold text-rose-700">{{ weeklyTotals.absent }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-sm font-semibold text-slate-700">Monthly Workforce Summary</span>
              <Tag value="Filterable" severity="secondary" size="small" />
            </div>
            <div class="flex flex-wrap gap-2">
              <Select v-model="selectedPayPeriod" :options="payPeriodOptions" optionLabel="label" optionValue="value" class="w-44" placeholder="Pay period" showClear />
              <Select v-model="selectedMonth" :options="monthOptions" optionLabel="label" optionValue="value" class="w-32" />
              <Select v-model="selectedYear" :options="yearOptions" optionLabel="label" optionValue="value" class="w-28" />
            </div>
          </div>
        </template>
        <template #content>
          <div v-if="monthlyError" class="mb-3 text-xs text-rose-600">{{ monthlyError }}</div>
          <div class="space-y-3">
            <div v-for="item in monthlySummaryDisplay" :key="item.label" class="space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">{{ item.label }}</span>
                <span class="font-semibold text-slate-900">{{ item.value }}</span>
              </div>
              <ProgressBar :value="item.percent" :showValue="false" class="h-2" />
            </div>
          </div>
          <div class="mt-4 rounded-xl border border-amber-100 bg-amber-50 p-3">
            <p class="text-xs uppercase tracking-wider text-amber-700">Overtime Requests (Month)</p>
            <p class="text-2xl font-bold text-amber-700">{{ monthlySummary.total_overtime || 0 }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- <div class="grid gap-4 xl:grid-cols-3">
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>Recommended Actions</template>
        <template #content>
          <div class="space-y-3">
            <div
              v-for="item in recommendedActions"
              :key="item.title"
              class="rounded-xl border p-3"
              :class="item.tone"
            >
              <p class="text-xs uppercase tracking-wider">{{ item.priority }}</p>
              <p class="mt-1 text-sm font-semibold">{{ item.title }}</p>
              <p class="mt-1 text-xs opacity-80">{{ item.detail }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>Quick Navigation</template>
        <template #content>
          <div class="space-y-2">
            <Button label="Leave Management" icon="pi pi-calendar" severity="secondary" text class="justify-start w-full" @click="router.push({ name: 'hr.leave' })" />
            <Button label="Attendance" icon="pi pi-clock" severity="secondary" text class="justify-start w-full" @click="router.push({ name: 'hr.attendance' })" />
            <Button label="Shift Management" icon="pi pi-calendar-plus" severity="secondary" text class="justify-start w-full" @click="router.push({ name: 'hr.shifts' })" />
            <Button label="Payroll" icon="pi pi-money-bill" severity="secondary" text class="justify-start w-full" @click="router.push({ name: 'hr.payroll' })" />
            <Button label="Employees" icon="pi pi-users" severity="secondary" text class="justify-start w-full" @click="router.push({ name: 'hr.employees' })" />
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>Current Focus</template>
        <template #content>
          <p class="text-sm text-slate-600">
            Use this dashboard to clear pending approvals first, then stabilize shift coverage,
            and finish attendance validation before running payroll.
          </p>
          <div class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
            Last refresh: {{ lastRefreshLabel }}
          </div>
        </template>
      </Card>
    </div> -->
  </div>
</template>

<script setup lang="ts">
import  Card  from 'primevue/card'
import Tag from 'primevue/tag'
import Chart from 'primevue/chart'
import ProgressBar from 'primevue/progressbar'
import Button from 'primevue/button'
import Select from 'primevue/select'
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import hrService from '../../../services/hr.services'

const router = useRouter()
const loading = ref(false)
const lastRefreshAt = ref<Date | null>(null)
const todayStats = ref({
  date: '',
  day_name: '',
  total_employees: 0,
  scheduled_today: 0,
  attended_today: 0,
  attendance_rate: 0,
  absent_today: 0,
  on_leave_today: 0,
  pending_leave_requests: 0,
  pending_overtime: 0
})
const weeklyAttendance = ref<any[]>([])
const monthlySummary = ref({
  total_scheduled: 0,
  total_attendance: 0,
  total_leaves: 0,
  total_overtime: 0
})
const payPeriods = ref<any[]>([])
const selectedPayPeriod = ref<number | null>(null)
const weeklyError = ref('')
const monthlyError = ref('')

const monthOptions = ref(
  Array.from({ length: 12 }, (_, i) => ({
    label: new Date(0, i).toLocaleString('default', { month: 'short' }),
    value: i + 1
  }))
)

const yearOptions = ref(
  Array.from({ length: 3 }, (_, i) => {
    const year = new Date().getFullYear() - i
    return { label: String(year), value: year }
  })
)

const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())

const payPeriodOptions = computed(() =>
  payPeriods.value.map((period: any) => ({
    label: period.name,
    value: period.id
  }))
)

const attendanceChartData = computed(() => {
  if (!weeklyAttendance.value.length) return null
  return {
    labels: weeklyAttendance.value.map((day) => day.day),
    datasets: [
      {
        label: 'Present',
        data: weeklyAttendance.value.map((day) => day.present),
        backgroundColor: '#10b981'
      },
      {
        label: 'Late',
        data: weeklyAttendance.value.map((day) => day.late),
        backgroundColor: '#f97316'
      },
      {
        label: 'Leave',
        data: weeklyAttendance.value.map((day) => day.leave),
        backgroundColor: '#facc15'
      },
      {
        label: 'Absent',
        data: weeklyAttendance.value.map((day) => day.absent),
        backgroundColor: '#ef4444'
      }
    ]
  }
})

const attendanceChartOptions = {
  scales: {
    x: { stacked: true, ticks: { color: '#475569' } },
    y: { stacked: true, beginAtZero: true, ticks: { color: '#475569' } }
  },
  plugins: {
    legend: { position: 'bottom' }
  },
  responsive: true,
  maintainAspectRatio: false
}

const weeklyTotals = computed(() =>
  weeklyAttendance.value.reduce(
    (acc, day) => {
      acc.present += day.present
      acc.late += day.late
      acc.leave += day.leave
      acc.absent += day.absent
      return acc
    },
    { present: 0, late: 0, leave: 0, absent: 0 }
  )
)

const weeklyStats = computed(() => {
  const scheduled = weeklyAttendance.value.reduce(
    (acc, day) => acc + day.present + day.late + day.leave + day.absent,
    0
  )
  const rate = scheduled ? Math.round((weeklyTotals.value.present / scheduled) * 100) : 0
  return { attendance_summary: `${rate}% in week` }
})

const coverageGap = computed(() => {
  const gap = Number(todayStats.value.scheduled_today || 0) - Number(todayStats.value.attended_today || 0)
  return gap > 0 ? gap : 0
})

const productiveCoverage = computed(() => {
  const scheduled = Number(todayStats.value.scheduled_today || 0)
  if (!scheduled) return 0
  return Math.round((Number(todayStats.value.attended_today || 0) / scheduled) * 100)
})

const healthBadge = computed(() => {
  if (todayStats.value.pending_leave_requests > 5 || todayStats.value.pending_overtime > 5 || coverageGap.value > 5) {
    return { label: 'High workload', severity: 'warn' as const }
  }
  if (todayStats.value.pending_leave_requests > 0 || todayStats.value.pending_overtime > 0 || coverageGap.value > 0) {
    return { label: 'Needs review', severity: 'info' as const }
  }
  return { label: 'Stable', severity: 'success' as const }
})

const recommendedActions = computed(() => [
  {
    priority: todayStats.value.pending_leave_requests > 0 ? 'High Priority' : 'Monitor',
    title: 'Resolve Leave Queue',
    detail: `${todayStats.value.pending_leave_requests} leave request(s) are awaiting action.`,
    tone: todayStats.value.pending_leave_requests > 0
      ? 'border-rose-200 bg-rose-50 text-rose-700'
      : 'border-slate-200 bg-slate-50 text-slate-700'
  },
  {
    priority: todayStats.value.pending_overtime > 0 ? 'High Priority' : 'Monitor',
    title: 'Review Overtime Requests',
    detail: `${todayStats.value.pending_overtime} overtime request(s) need validation.`,
    tone: todayStats.value.pending_overtime > 0
      ? 'border-amber-200 bg-amber-50 text-amber-700'
      : 'border-slate-200 bg-slate-50 text-slate-700'
  },
  {
    priority: coverageGap.value > 0 ? 'Medium Priority' : 'Healthy',
    title: 'Close Coverage Gaps',
    detail: `${coverageGap.value} uncovered schedule slot(s) detected today.`,
    tone: coverageGap.value > 0
      ? 'border-orange-200 bg-orange-50 text-orange-700'
      : 'border-emerald-200 bg-emerald-50 text-emerald-700'
  }
])

const lastRefreshLabel = computed(() => {
  if (!lastRefreshAt.value) return 'Not refreshed yet'
  return lastRefreshAt.value.toLocaleString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
})

const monthlySummaryDisplay = computed(() => {
  const total = monthlySummary.value.total_scheduled || 1
  return [
    { label: 'Scheduled', value: monthlySummary.value.total_scheduled, percent: (monthlySummary.value.total_scheduled / total) * 100 },
    { label: 'Present', value: monthlySummary.value.total_attendance, percent: (monthlySummary.value.total_attendance / total) * 100 },
    { label: 'Leaves', value: monthlySummary.value.total_leaves, percent: (monthlySummary.value.total_leaves / total) * 100 }
  ]
})

const fetchTodayStats = async () => {
  try {
    const response = await hrService.api.get('/api/hr/dashboard/today-stats')
    todayStats.value = response.data.data
  } catch (error) {
    // keep defaults
  }
}

const fetchWeeklyAttendance = async () => {
  weeklyError.value = ''
  try {
    const response = await hrService.api.get('/api/hr/dashboard/weekly-attendance')
    weeklyAttendance.value = response.data.data
  } catch (error) {
    weeklyError.value = 'Unable to load weekly attendance.'
  }
}

const fetchMonthlySummary = async () => {
  monthlyError.value = ''
  try {
    const response = await hrService.api.get('/api/hr/dashboard/monthly-summary', {
      params: { month: selectedMonth.value, year: selectedYear.value }
    })
    monthlySummary.value = response.data.data
  } catch (error) {
    monthlyError.value = 'Unable to load monthly summary.'
  }
}

const fetchPayPeriods = async () => {
  try {
    const response = await hrService.api.get('/api/payroll/pay-periods')
    payPeriods.value = response.data.data || response.data || []
  } catch (error) {
    payPeriods.value = []
  }
}

const settle = async (task: Promise<unknown>) => {
  try {
    await task
  } catch {
    // Errors are handled by each fetch function so the dashboard can load partial data.
  }
}

const reloadDashboard = async () => {
  loading.value = true
  await Promise.all([
    settle(fetchTodayStats()),
    settle(fetchWeeklyAttendance()),
    settle(fetchMonthlySummary()),
    settle(fetchPayPeriods())
  ])
  lastRefreshAt.value = new Date()
  loading.value = false
}

watch([selectedMonth, selectedYear], () => {
  fetchMonthlySummary()
})

watch(selectedPayPeriod, (periodId) => {
  if (!periodId) return
  const match = payPeriods.value.find((period: any) => period.id === periodId)
  if (!match) return
  const startDate = new Date(match.start_date)
  if (!Number.isNaN(startDate.getTime())) {
    selectedMonth.value = startDate.getMonth() + 1
    selectedYear.value = startDate.getFullYear()
  }
})

onMounted(reloadDashboard)
</script>

