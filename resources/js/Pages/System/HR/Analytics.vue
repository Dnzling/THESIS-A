<template>
  <div class="space-y-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">HR Analytics</h1>
          <p class="mt-1 text-sm text-slate-500">Operational trends and workforce insights for HR decision-making.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Select
            v-model="selectedBranchId"
            :options="branchOptions"
            optionLabel="name"
            optionValue="id"
            placeholder="All branches"
            showClear
            class="w-[14rem]"
          />
          <DatePicker v-model="dateRange" selectionMode="range" dateFormat="yy-mm-dd" showIcon :manualInput="false" class="w-[18rem]" />
          <Button label="Apply" icon="pi pi-filter" severity="secondary" outlined :loading="loading" @click="applyDateRange" />
          <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined :loading="loading" @click="loadDashboard" />
        </div>
      </div>
      <div class="mt-3 text-xs text-slate-500">
        Period: {{ overview.period.start_date || '-' }} to {{ overview.period.end_date || '-' }}
      </div>
    </div>

    <div v-if="errorMessage" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
      {{ errorMessage }}
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-slate-500">Total Workforce</p>
        <p class="mt-2 text-3xl font-bold text-slate-900">{{ overview.workforce.total_employees }}</p>
        <p class="mt-1 text-xs text-slate-500">Active: {{ overview.workforce.active_employees }}</p>
      </div>
      <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-emerald-700">Attendance Rate</p>
        <p class="mt-2 text-3xl font-bold text-emerald-700">{{ overview.attendance.attendance_rate }}%</p>
        <p class="mt-1 text-xs text-emerald-700/80">Scheduled: {{ overview.attendance.scheduled }}</p>
      </div>
      <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-amber-700">Leave Queue</p>
        <p class="mt-2 text-3xl font-bold text-amber-700">{{ overview.leave_overtime.pending_leave_requests }}</p>
        <p class="mt-1 text-xs text-amber-700/80">Approved in period: {{ overview.leave_overtime.approved_leave_requests }}</p>
      </div>
      <div class="rounded-2xl border border-orange-100 bg-orange-50 p-4 shadow-sm">
        <p class="text-xs uppercase tracking-wider text-orange-700">Overtime Queue</p>
        <p class="mt-2 text-3xl font-bold text-orange-700">{{ overview.leave_overtime.pending_overtime_requests }}</p>
        <p class="mt-1 text-xs text-orange-700/80">Approved OT hours: {{ approvedOtHours }}</p>
      </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-700">Attendance Distribution</span>
            <Tag :value="attendanceHealthLabel" :severity="attendanceHealthSeverity" size="small" />
          </div>
        </template>
        <template #content>
          <Chart v-if="attendanceChartData" type="doughnut" :data="attendanceChartData" :options="doughnutOptions" class="h-72" />
          <div v-else class="py-10 text-center text-xs text-slate-500">No attendance data in selected period.</div>
          <div class="mt-4 grid grid-cols-2 gap-3 text-center sm:grid-cols-4">
            <div class="rounded-2xl bg-emerald-50 p-3">
              <p class="text-xs text-slate-500">Present</p>
              <p class="text-lg font-semibold text-emerald-700">{{ overview.attendance.present }}</p>
            </div>
            <div class="rounded-2xl bg-orange-50 p-3">
              <p class="text-xs text-slate-500">Late</p>
              <p class="text-lg font-semibold text-orange-700">{{ overview.attendance.late }}</p>
            </div>
            <div class="rounded-2xl bg-amber-50 p-3">
              <p class="text-xs text-slate-500">Leave</p>
              <p class="text-lg font-semibold text-amber-700">{{ overview.attendance.on_leave }}</p>
            </div>
            <div class="rounded-2xl bg-rose-50 p-3">
              <p class="text-xs text-slate-500">Absent</p>
              <p class="text-lg font-semibold text-rose-700">{{ overview.attendance.absent }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <span class="text-sm font-semibold text-slate-700">Workforce Movement</span>
        </template>
        <template #content>
          <div class="space-y-3">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">
              <p class="text-xs uppercase tracking-wide text-emerald-700">New Hires</p>
              <p class="text-2xl font-bold text-emerald-700">{{ overview.workforce.new_hires }}</p>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-3">
              <p class="text-xs uppercase tracking-wide text-rose-700">Attrition</p>
              <p class="text-2xl font-bold text-rose-700">{{ overview.workforce.attrition_count }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
              Late rate: <strong>{{ overview.attendance.late_rate }}%</strong>
            </div>
          </div>
        </template>
      </Card>
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <span class="text-sm font-semibold text-slate-700">Department Distribution</span>
        </template>
        <template #content>
          <div class="space-y-3">
            <div v-for="item in overview.breakdowns.department_distribution" :key="item.name" class="space-y-1">
              <div class="flex items-center justify-between text-sm">
                <span class="text-slate-600">{{ item.name }}</span>
                <span class="font-semibold text-slate-900">{{ item.total }}</span>
              </div>
              <ProgressBar :value="distributionPercent(item.total, totalDepartmentHeadcount)" :showValue="false" class="h-2" />
            </div>
            <div v-if="!overview.breakdowns.department_distribution.length" class="py-4 text-center text-xs text-slate-500">
              No department records available.
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-slate-200 shadow-sm">
        <template #title>
          <span class="text-sm font-semibold text-slate-700">Top Absence Cases</span>
        </template>
        <template #content>
          <div class="space-y-3">
            <div v-for="row in overview.breakdowns.top_absences" :key="row.employee_id" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <p class="text-sm font-semibold text-slate-900">{{ row.name || 'Unknown Employee' }}</p>
                  <p class="text-xs text-slate-500">{{ row.department || 'Unassigned Department' }}</p>
                </div>
                <Tag :value="`${row.total_absent_days} day(s)`" severity="danger" />
              </div>
            </div>
            <div v-if="!overview.breakdowns.top_absences.length" class="py-4 text-center text-xs text-slate-500">
              No absence spikes in selected period.
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Chart from 'primevue/chart'
import ProgressBar from 'primevue/progressbar'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import hrService from '../../../services/hr.services'

interface HrAnalyticsOverview {
  period: { start_date: string; end_date: string }
  workforce: {
    total_employees: number
    active_employees: number
    new_hires: number
    attrition_count: number
  }
  attendance: {
    scheduled: number
    present: number
    late: number
    absent: number
    on_leave: number
    attendance_rate: number
    late_rate: number
  }
  leave_overtime: {
    pending_leave_requests: number
    approved_leave_requests: number
    pending_overtime_requests: number
    approved_overtime_minutes: number
  }
  breakdowns: {
    department_distribution: Array<{ name: string; total: number }>
    employment_distribution: Array<{ name: string; total: number }>
    top_absences: Array<{ employee_id: number; name: string; department: string | null; total_absent_days: number }>
  }
}

const loading = ref(false)
const errorMessage = ref('')
const branchOptions = ref<Array<{ id: number; name: string }>>([])
const selectedBranchId = ref<number | null>(null)

const dateRange = ref<Date[] | null>([
  new Date(new Date().setDate(new Date().getDate() - 29)),
  new Date(),
])

const overview = ref<HrAnalyticsOverview>({
  period: { start_date: '', end_date: '' },
  workforce: {
    total_employees: 0,
    active_employees: 0,
    new_hires: 0,
    attrition_count: 0,
  },
  attendance: {
    scheduled: 0,
    present: 0,
    late: 0,
    absent: 0,
    on_leave: 0,
    attendance_rate: 0,
    late_rate: 0,
  },
  leave_overtime: {
    pending_leave_requests: 0,
    approved_leave_requests: 0,
    pending_overtime_requests: 0,
    approved_overtime_minutes: 0,
  },
  breakdowns: {
    department_distribution: [],
    employment_distribution: [],
    top_absences: [],
  },
})

const formatDate = (value?: Date | null): string | undefined => {
  if (!value) return undefined
  return value.toISOString().slice(0, 10)
}

const loadDashboard = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const [start, end] = dateRange.value || []
    const response = await hrService.getHrAnalyticsOverview({
      start_date: formatDate(start),
      end_date: formatDate(end),
      branch_id: selectedBranchId.value,
    })

    overview.value = response?.data || overview.value
  } catch (error: any) {
    errorMessage.value = error?.response?.data?.message || 'Unable to load HR analytics right now.'
  } finally {
    loading.value = false
  }
}

const loadBranches = async () => {
  try {
    const response = await hrService.getBranches()
    const raw = response?.data || response || []
    branchOptions.value = (Array.isArray(raw) ? raw : []).map((branch: any) => ({
      id: Number(branch.id),
      name: String(branch.name || branch.branch_name || `Branch ${branch.id}`),
    }))
  } catch {
    branchOptions.value = []
  }
}

const applyDateRange = async () => {
  const [start, end] = dateRange.value || []
  if (!start || !end) {
    errorMessage.value = 'Please select a complete date range.'
    return
  }
  await loadDashboard()
}

const approvedOtHours = computed(() => {
  const minutes = Number(overview.value.leave_overtime.approved_overtime_minutes || 0)
  return (minutes / 60).toFixed(1)
})

const attendanceHealthLabel = computed(() => {
  const rate = Number(overview.value.attendance.attendance_rate || 0)
  if (rate >= 95) return 'Excellent'
  if (rate >= 85) return 'Good'
  if (rate >= 70) return 'Needs Attention'
  return 'Critical'
})

const attendanceHealthSeverity = computed(() => {
  const rate = Number(overview.value.attendance.attendance_rate || 0)
  if (rate >= 95) return 'success'
  if (rate >= 85) return 'info'
  if (rate >= 70) return 'warn'
  return 'danger'
})

const totalDepartmentHeadcount = computed(() => {
  return overview.value.breakdowns.department_distribution.reduce((acc, item) => acc + Number(item.total || 0), 0)
})

const distributionPercent = (count: number, total: number) => {
  if (!total) return 0
  return Math.round((count / total) * 100)
}

const attendanceChartData = computed(() => {
  const attendance = overview.value.attendance
  const values = [attendance.present, attendance.late, attendance.on_leave, attendance.absent]
  const hasData = values.some((v) => Number(v || 0) > 0)
  if (!hasData) return null

  return {
    labels: ['Present', 'Late', 'On Leave', 'Absent'],
    datasets: [
      {
        data: values,
        backgroundColor: ['#10b981', '#f97316', '#f59e0b', '#ef4444'],
        borderWidth: 0,
      },
    ],
  }
})

const doughnutOptions = {
  plugins: {
    legend: {
      position: 'bottom',
    },
  },
  cutout: '62%',
  responsive: true,
  maintainAspectRatio: false,
}

onMounted(async () => {
  await loadBranches()
  await loadDashboard()
})
</script>
