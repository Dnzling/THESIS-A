<template>
  <div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between bg-white p-4 rounded-2xl shadow-sm">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Team health snapshot</h1>
        <p class="text-sm text-slate-500 mt-1">Today · {{ todayStats.date || '-' }} · {{ todayStats.day_name || '-' }}</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined @click="reloadDashboard" />
    </div>

    <div class="grid gap-3 md:grid-cols-5">
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500 uppercase tracking-wider">Employees</span>
          <Tag value="Live" severity="success" size="small" />
        </div>
        <p class="text-3xl font-bold text-slate-900">{{ todayStats.total_employees }}</p>
        <p class="text-xs text-slate-500">Scheduled today {{ todayStats.scheduled_today }}</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500 uppercase tracking-wider">Attendance</span>
          <Tag :value="`${todayStats.attendance_rate}%`" severity="info" size="small" />
        </div>
        <p class="text-3xl font-bold text-emerald-600">{{ todayStats.attended_today }}</p>
        <p class="text-xs text-slate-500">Present · {{ todayStats.attended_today }} of {{ todayStats.scheduled_today }}</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500 uppercase tracking-wider">On Leave</span>
          <Tag value="Today" severity="secondary" size="small" />
        </div>
        <p class="text-3xl font-bold text-amber-600">{{ todayStats.on_leave_today }}</p>
        <p class="text-xs text-slate-500">Leave coverage today</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500 uppercase tracking-wider">Absences</span>
          <Tag value="Alert" severity="warning" size="small" />
        </div>
        <p class="text-3xl font-bold text-rose-600">{{ todayStats.absent_today }}</p>
        <p class="text-xs text-slate-500">Leave hold {{ todayStats.pending_leave_requests }}</p>
      </div>
      <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-500 uppercase tracking-wider">Overtime</span>
          <Tag :value="todayStats.pending_overtime" severity="info" size="small" />
        </div>
        <p class="text-3xl font-bold text-amber-600">{{ todayStats.pending_overtime }}</p>
        <p class="text-xs text-slate-500">Requests waiting</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-700">Weekly Attendance</span>
            <Tag :value="weeklyStats.attendance_summary" severity="info" size="small" />
          </div>
        </template>
        <template #content>
          <Chart
            v-if="attendanceChartData"
            type="bar"
            :data="attendanceChartData"
            :options="attendanceChartOptions"
            class="h-60"
          />
          <div v-else class="text-xs text-slate-500 text-center py-12">
            {{ weeklyError || 'Loading attendance history…' }}
          </div>
          <div class="mt-4 grid grid-cols-4 gap-3 text-center text-xs">
            <div class="rounded-2xl bg-emerald-50 p-3">
              <p class="text-xxs text-slate-500">Present</p>
              <p class="text-lg font-semibold text-emerald-700">{{ weeklyTotals.present }}</p>
            </div>
            <div class="rounded-2xl bg-orange-50 p-3">
              <p class="text-xxs text-slate-500">Late</p>
              <p class="text-lg font-semibold text-orange-700">{{ weeklyTotals.late }}</p>
            </div>
            <div class="rounded-2xl bg-amber-50 p-3">
              <p class="text-xxs text-slate-500">Leave</p>
              <p class="text-lg font-semibold text-amber-700">{{ weeklyTotals.leave }}</p>
            </div>
            <div class="rounded-2xl bg-rose-50 p-3">
              <p class="text-xxs text-slate-500">Absent</p>
              <p class="text-lg font-semibold text-rose-700">{{ weeklyTotals.absent }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 shadow-sm space-y-4">
        <div class="flex items-center justify-between">
          <span class="text-sm font-semibold text-slate-700">Monthly Summary</span>
          <div class="flex flex-wrap gap-2">
            <Select v-model="selectedPayPeriod" :options="payPeriodOptions" optionLabel="label" optionValue="value" class="w-40" placeholder="Pay period" showClear />
            <Select v-model="selectedMonth" :options="monthOptions" optionLabel="label" optionValue="value" class="w-32" />
            <Select v-model="selectedYear" :options="yearOptions" optionLabel="label" optionValue="value" class="w-24" />
          </div>
        </div>
        <div v-if="monthlyError" class="text-xs text-rose-600">{{ monthlyError }}</div>
        <div class="space-y-3">
          <div v-for="item in monthlySummaryDisplay" :key="item.label" class="flex items-center justify-between gap-3">
            <div>
              <p class="text-xs text-slate-500">{{ item.label }}</p>
              <p class="text-sm font-semibold text-slate-900">{{ item.value }}</p>
            </div>
            <ProgressBar :value="item.percent" :showValue="false" class="w-32 h-2" />
          </div>
        </div>
        <div class="pt-4 border-t border-slate-100">
          <p class="text-xs text-slate-500 uppercase tracking-[0.3em]">Overtime requests</p>
          <p class="text-2xl font-bold text-amber-600">{{ monthlySummary.total_overtime || 0 }}</p>
        </div>
      </Card>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <Card class="rounded-2xl border border-slate-200 shadow-sm space-y-3">
        <p class="text-sm font-semibold text-slate-700">Action Shortcuts</p>
        <div class="space-y-2">
          <Button label="Approve Leaves" icon="pi pi-calendar" severity="info" text class="justify-start" @click="router.push({ name: 'hr.leave' })" />
          <Button label="Review Overtime" icon="pi pi-clock" severity="info" text class="justify-start" @click="router.push({ name: 'hr.attendance' })" />
          <Button label="Check Shifts" icon="pi pi-calendar-plus" severity="info" text class="justify-start" @click="router.push({ name: 'hr.shifts' })" />
          <Button label="Open Payroll" icon="pi pi-money-bill" severity="info" text class="justify-start" @click="router.push({ name: 'hr.payroll' })" />
        </div>
      </Card>
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>Pending Alerts</template>
        <template #content>
          <div class="space-y-2">
            <button class="w-full flex items-center justify-between rounded-xl border border-rose-100 bg-rose-50/60 px-3 py-2 text-left" @click="router.push({ name: 'hr.leave' })">
              <span class="text-xs text-rose-600">Pending leaves</span>
              <span class="text-sm font-semibold text-rose-700">{{ todayStats.pending_leave_requests }}</span>
            </button>
            <button class="w-full flex items-center justify-between rounded-xl border border-amber-100 bg-amber-50/60 px-3 py-2 text-left" @click="router.push({ name: 'hr.attendance' })">
              <span class="text-xs text-amber-600">Pending overtime</span>
              <span class="text-sm font-semibold text-amber-700">{{ todayStats.pending_overtime }}</span>
            </button>
          </div>
        </template>
      </Card>
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>Next focus</template>
        <template #content>
          <p class="text-sm text-slate-600">Use the latest attendance data to plan reschedules, confirm leave coverage, and ensure payroll accuracy before submitting.</p>
        </template>
      </Card>
    </div>
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
import axios from 'axios'

const router = useRouter()
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
    const response = await axios.get('/api/hr/dashboard/today-stats')
    todayStats.value = response.data.data
  } catch (error) {
    // keep defaults
  }
}

const fetchWeeklyAttendance = async () => {
  weeklyError.value = ''
  try {
    const response = await axios.get('/api/hr/dashboard/weekly-attendance')
    weeklyAttendance.value = response.data.data
  } catch (error) {
    weeklyError.value = 'Unable to load weekly attendance.'
  }
}

const fetchMonthlySummary = async () => {
  monthlyError.value = ''
  try {
    const response = await axios.get('/api/hr/dashboard/monthly-summary', {
      params: { month: selectedMonth.value, year: selectedYear.value }
    })
    monthlySummary.value = response.data.data
  } catch (error) {
    monthlyError.value = 'Unable to load monthly summary.'
  }
}

const fetchPayPeriods = async () => {
  try {
    const response = await axios.get('/api/payroll/pay-periods')
    payPeriods.value = response.data.data || response.data || []
  } catch (error) {
    payPeriods.value = []
  }
}

const reloadDashboard = () => {
  fetchTodayStats()
  fetchWeeklyAttendance()
  fetchMonthlySummary()
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
onMounted(fetchPayPeriods)
</script>
