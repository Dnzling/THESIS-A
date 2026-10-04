<template>
  <div class="module-dashboard dashboard--hr space-y-5 pb-6 text-sm">
    <div class="dashboard-hero flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-violet-700">People operations</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-950">HR Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Today's workforce picture and the requests waiting for you.</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" :loading="loading" @click="loadDashboard" />
    </div>

    <div v-if="loading" class="space-y-5">
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <Skeleton v-for="index in 4" :key="index" height="118px" class="rounded-2xl" />
      </div>
      <div class="grid gap-4 xl:grid-cols-3">
        <Skeleton height="280px" class="rounded-2xl xl:col-span-2" />
        <Skeleton height="280px" class="rounded-2xl" />
      </div>
      <div class="grid gap-4 xl:grid-cols-2">
        <Skeleton v-for="index in 4" :key="index" height="220px" class="rounded-2xl" />
      </div>
    </div>
    <div v-else-if="loadError" class="dashboard-panel rounded-2xl border border-red-200 bg-white p-6">
      <p class="font-semibold text-slate-900">The HR dashboard could not load.</p>
      <p class="mt-1 text-slate-500">{{ loadError }}</p>
      <Button label="Try again" severity="warn" size="small" class="mt-4" @click="loadDashboard" />
    </div>
    <template v-else>
      <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <section class="dashboard-panel rounded-2xl border border-violet-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Active employees</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ queues.summary?.active_employees || 0 }}</p>
          <p class="mt-2 text-xs text-slate-500">Across the store</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Attendance today</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ today.attended_today }} <span class="text-sm font-normal text-slate-400">/ {{ today.scheduled_today }}</span></p>
          <p class="mt-2 text-xs text-slate-500">{{ attendanceRate }}% of scheduled employees checked in</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">Pending approvals</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ pendingApprovals }}</p>
          <p class="mt-2 text-xs text-slate-500">Leave, overtime, and shift swaps</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <p class="text-xs font-medium text-slate-500">New applicants</p>
          <p class="mt-2 text-2xl font-semibold text-slate-950">{{ queues.summary?.new_applicants || 0 }}</p>
          <p class="mt-2 text-xs text-slate-500">Awaiting first review</p>
        </section>
      </div>

      <div class="grid gap-4 xl:grid-cols-3">
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5 xl:col-span-2">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h2 class="font-semibold text-slate-950">Attendance this week</h2>
              <p class="mt-1 text-xs text-slate-500">Daily check-ins, late arrivals, leave, and absences</p>
            </div>
            <Button label="View attendance" severity="secondary" text size="small" @click="go('hr.attendance')" />
          </div>
          <Chart v-if="weekly.length" type="bar" :data="attendanceChart" :options="barOptions" class="mt-4 h-56" />
          <p v-else class="flex h-56 items-center justify-center text-xs text-slate-500">No attendance records this week.</p>
        </section>
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <h2 class="font-semibold text-slate-950">Approval workload</h2>
          <p class="mt-1 text-xs text-slate-500">Open HR requests by type</p>
          <div v-if="pendingApprovals" class="mt-3">
            <Chart type="doughnut" :data="workloadChart" :options="doughnutOptions" class="mx-auto h-44 max-w-52" />
            <div class="mt-2 grid grid-cols-3 gap-1 text-center text-xs">
              <span class="text-violet-700">Leave <strong>{{ queues.summary?.pending_leaves || 0 }}</strong></span>
              <span class="text-orange-700">OT <strong>{{ queues.summary?.pending_overtime || 0 }}</strong></span>
              <span class="text-sky-700">Swaps <strong>{{ queues.summary?.pending_swaps || 0 }}</strong></span>
            </div>
          </div>
          <p v-else class="flex h-48 items-center justify-center text-center text-xs text-slate-500">All approval queues are clear.</p>
        </section>
      </div>

      <div class="grid gap-4 xl:grid-cols-2">
        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div><h2 class="font-semibold text-slate-950">Leave requests</h2><p class="text-xs text-slate-500">Upcoming leave to approve or decline</p></div>
            <Button label="View all" severity="secondary" text size="small" @click="go('hr.leave')" />
          </div>
          <DataTable :value="queues.leaves || []" size="small" rowHover class="text-xs" @row-click="openLeave">
            <Column field="employee" header="Employee"><template #body="{ data }"><span class="font-medium text-slate-900">{{ data.employee || 'Employee' }}</span></template></Column>
            <Column field="type" header="Type"><template #body="{ data }">{{ label(data.type) }}</template></Column>
            <Column header="Dates"><template #body="{ data }">{{ shortDate(data.start_date) }}<span v-if="data.end_date && data.end_date !== data.start_date"> - {{ shortDate(data.end_date) }}</span></template></Column>
            <Column header="Action" headerClass="text-right" bodyClass="text-right"><template #body="{ data }"><Button label="View" severity="secondary" text size="small" @click.stop="go('hr.leaves.detail', data.id)" /></template></Column>
            <template #empty><div class="py-8 text-center text-xs text-slate-500">No leave requests need review.</div></template>
          </DataTable>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] text-xs">
              <thead><tr class="border-b border-slate-200 text-left text-slate-500"><th class="pb-3 font-medium">Employee</th><th class="pb-3 font-medium">Type</th><th class="pb-3 font-medium">Dates</th><th class="pb-3 text-right font-medium">Action</th></tr></thead>
              <tbody><tr v-for="item in queues.leaves" :key="item.id" class="border-b border-slate-100 last:border-0">
                <td class="py-3 font-medium text-slate-900">{{ item.employee || 'Employee' }}</td>
                <td class="py-3 text-slate-600">{{ label(item.type) }}</td>
                <td class="py-3 text-slate-600">{{ shortDate(item.start_date) }}<span v-if="item.end_date && item.end_date !== item.start_date"> – {{ shortDate(item.end_date) }}</span></td>
                <td class="py-2 text-right"><Button label="View" severity="secondary" text size="small" @click="go('hr.leaves.detail', item.id)" /></td>
              </tr></tbody>
            </table>
          </div>
        </section>

        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div><h2 class="font-semibold text-slate-950">Overtime requests</h2><p class="text-xs text-slate-500">Review before the payroll cutoff</p></div>
            <Button label="View employees" severity="secondary" text size="small" @click="go('hr.employees')" />
          </div>
          <DataTable :value="queues.overtime || []" size="small" rowHover class="text-xs" @row-click="openOvertime">
            <Column field="employee" header="Employee"><template #body="{ data }"><span class="font-medium text-slate-900">{{ data.employee || 'Employee' }}</span></template></Column>
            <Column field="date" header="Date"><template #body="{ data }">{{ shortDate(data.date) }}</template></Column>
            <Column field="minutes" header="Hours"><template #body="{ data }">{{ (data.minutes / 60).toFixed(1) }} hrs</template></Column>
            <Column header="Action" headerClass="text-right" bodyClass="text-right"><template #body="{ data }"><Button label="View" severity="secondary" text size="small" @click.stop="go('hr.employees.view', data.employee_id, 'overtime')" /></template></Column>
            <template #empty><div class="py-8 text-center text-xs text-slate-500">No overtime requests need review.</div></template>
          </DataTable>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] text-xs">
              <thead><tr class="border-b border-slate-200 text-left text-slate-500"><th class="pb-3 font-medium">Employee</th><th class="pb-3 font-medium">Date</th><th class="pb-3 font-medium">Hours</th><th class="pb-3 text-right font-medium">Action</th></tr></thead>
              <tbody><tr v-for="item in queues.overtime" :key="item.id" class="border-b border-slate-100 last:border-0">
                <td class="py-3 font-medium text-slate-900">{{ item.employee || 'Employee' }}</td>
                <td class="py-3 text-slate-600">{{ shortDate(item.date) }}</td>
                <td class="py-3 text-slate-600">{{ (item.minutes / 60).toFixed(1) }} hrs</td>
                <td class="py-2 text-right"><Button label="View" severity="secondary" text size="small" @click="go('hr.employees.view', item.employee_id, 'overtime')" /></td>
              </tr></tbody>
            </table>
          </div>
        </section>

        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div><h2 class="font-semibold text-slate-950">Shift swap requests</h2><p class="text-xs text-slate-500">Requests that may affect coverage</p></div>
            <Button label="View all" severity="secondary" text size="small" @click="go('hr.shifts', undefined, 'swaps')" />
          </div>
          <DataTable :value="queues.swaps || []" size="small" rowHover class="text-xs" @row-click="openSwap">
            <Column field="requestor" header="Requested by"><template #body="{ data }"><span class="font-medium text-slate-900">{{ data.requestor || 'Employee' }}</span></template></Column>
            <Column field="receiver" header="With"><template #body="{ data }">{{ data.receiver || 'Unassigned' }}</template></Column>
            <Column field="date" header="Shift date"><template #body="{ data }">{{ shortDate(data.date) }}</template></Column>
            <Column header="Action" headerClass="text-right" bodyClass="text-right"><template #body="{ data }"><Button label="View" severity="secondary" text size="small" @click.stop="go('hr.shifts', undefined, 'swaps', data.id)" /></template></Column>
            <template #empty><div class="py-8 text-center text-xs text-slate-500">No shift swaps need review.</div></template>
          </DataTable>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] text-xs">
              <thead><tr class="border-b border-slate-200 text-left text-slate-500"><th class="pb-3 font-medium">Requested by</th><th class="pb-3 font-medium">With</th><th class="pb-3 font-medium">Shift date</th><th class="pb-3 text-right font-medium">Action</th></tr></thead>
              <tbody><tr v-for="item in queues.swaps" :key="item.id" class="border-b border-slate-100 last:border-0">
                <td class="py-3 font-medium text-slate-900">{{ item.requestor || 'Employee' }}</td>
                <td class="py-3 text-slate-600">{{ item.receiver || 'Unassigned' }}</td>
                <td class="py-3 text-slate-600">{{ shortDate(item.date) }}</td>
                <td class="py-2 text-right"><Button label="View" severity="secondary" text size="small" @click="go('hr.shifts', undefined, 'swaps', item.id)" /></td>
              </tr></tbody>
            </table>
          </div>
        </section>

        <section class="dashboard-panel rounded-2xl border border-slate-200 bg-white p-5">
          <div class="mb-4 flex items-start justify-between gap-3">
            <div><h2 class="font-semibold text-slate-950">New applicants</h2><p class="text-xs text-slate-500">Oldest applications awaiting screening</p></div>
            <Button label="View jobs" severity="secondary" text size="small" @click="go('hr.recuitment')" />
          </div>
          <DataTable :value="queues.applications || []" size="small" rowHover class="text-xs" @row-click="openApplicant">
            <Column field="name" header="Applicant"><template #body="{ data }"><span class="font-medium text-slate-900">{{ data.name }}</span></template></Column>
            <Column field="position" header="Position"><template #body="{ data }">{{ data.position || '-' }}</template></Column>
            <Column field="date" header="Applied"><template #body="{ data }">{{ shortDate(data.date) }}</template></Column>
            <Column header="Action" headerClass="text-right" bodyClass="text-right"><template #body="{ data }"><Button label="View" severity="secondary" text size="small" @click.stop="go('hr.job-applications.review', data.id)" /></template></Column>
            <template #empty><div class="py-8 text-center text-xs text-slate-500">No new applications to screen.</div></template>
          </DataTable>
          <div class="overflow-x-auto">
            <table class="w-full min-w-[480px] text-xs">
              <thead><tr class="border-b border-slate-200 text-left text-slate-500"><th class="pb-3 font-medium">Applicant</th><th class="pb-3 font-medium">Position</th><th class="pb-3 font-medium">Applied</th><th class="pb-3 text-right font-medium">Action</th></tr></thead>
              <tbody><tr v-for="item in queues.applications" :key="item.id" class="border-b border-slate-100 last:border-0">
                <td class="py-3 font-medium text-slate-900">{{ item.name }}</td>
                <td class="py-3 text-slate-600">{{ item.position || '—' }}</td>
                <td class="py-3 text-slate-600">{{ shortDate(item.date) }}</td>
                <td class="py-2 text-right"><Button label="View" severity="secondary" text size="small" @click="go('hr.job-applications.review', item.id)" /></td>
              </tr></tbody>
            </table>
          </div>
        </section>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import hrService from '../../../services/hr.services'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'

type QueueRow = { id: number; [key: string]: any }
type ActionQueues = {
  summary?: { active_employees: number; pending_leaves: number; pending_overtime: number; pending_swaps: number; new_applicants: number }
  leaves?: QueueRow[]
  overtime?: QueueRow[]
  swaps?: QueueRow[]
  applications?: QueueRow[]
}
type AttendanceDay = { day: string; present: number; late: number; leave: number; absent: number }

const router = useRouter()
const loading = ref(true)
const loadError = ref('')
const queues = ref<ActionQueues>({})
const today = ref({ scheduled_today: 0, attended_today: 0 })
const weekly = ref<AttendanceDay[]>([])

const pendingApprovals = computed(() =>
  Number(queues.value.summary?.pending_leaves || 0) +
  Number(queues.value.summary?.pending_overtime || 0) +
  Number(queues.value.summary?.pending_swaps || 0))
const attendanceRate = computed(() => today.value.scheduled_today
  ? Math.round((today.value.attended_today / today.value.scheduled_today) * 100)
  : 0)
const attendanceChart = computed(() => ({
  labels: weekly.value.map(day => day.day.slice(0, 3)),
  datasets: [
    { label: 'Present', data: weekly.value.map(day => day.present), backgroundColor: '#34d399' },
    { label: 'Late', data: weekly.value.map(day => day.late), backgroundColor: '#fb923c' },
    { label: 'Leave', data: weekly.value.map(day => day.leave), backgroundColor: '#a78bfa' },
    { label: 'Absent', data: weekly.value.map(day => day.absent), backgroundColor: '#f87171' },
  ],
}))
const workloadChart = computed(() => ({
  labels: ['Leave', 'Overtime', 'Shift swaps'],
  datasets: [{
    data: [queues.value.summary?.pending_leaves || 0, queues.value.summary?.pending_overtime || 0, queues.value.summary?.pending_swaps || 0],
    backgroundColor: ['#8b5cf6', '#fb923c', '#38bdf8'],
    borderWidth: 0,
  }],
}))
const barOptions = {
  maintainAspectRatio: false,
  scales: {
    x: { stacked: true, grid: { display: false }, ticks: { color: '#64748b' } },
    y: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#64748b', precision: 0 } },
  },
  plugins: { legend: { position: 'bottom' as const, labels: { boxWidth: 9, usePointStyle: true } } },
}
const doughnutOptions = { maintainAspectRatio: false, cutout: '74%', plugins: { legend: { display: false } } }

const shortDate = (value: unknown) => value
  ? new Intl.DateTimeFormat('en-PH', { month: 'short', day: 'numeric', timeZone: 'Asia/Manila' }).format(new Date(String(value).slice(0, 10) + 'T12:00:00+08:00'))
  : '—'
const label = (value: unknown) => String(value || '—').replaceAll('_', ' ')
const go = (name: string, id?: number, tab?: string, swapId?: number) => router.push({
  name,
  ...(id ? { params: { id } } : {}),
  ...(tab ? { query: { tab, ...(swapId ? { swap: swapId } : {}) } } : {}),
})
const openLeave = (event: { data: QueueRow }) => go('hr.leaves.detail', event.data.id)
const openOvertime = (event: { data: QueueRow }) => go('hr.employees.view', event.data.employee_id, 'overtime')
const openSwap = (event: { data: QueueRow }) => go('hr.shifts', undefined, 'swaps', event.data.id)
const openApplicant = (event: { data: QueueRow }) => go('hr.job-applications.review', event.data.id)

const loadDashboard = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const [queueResponse, todayResponse, weeklyResponse] = await Promise.all([
      hrService.api.get('/api/hr/dashboard/action-queues'),
      hrService.api.get('/api/hr/dashboard/today-stats'),
      hrService.api.get('/api/hr/dashboard/weekly-attendance'),
    ])
    queues.value = queueResponse.data.data || {}
    today.value = todayResponse.data.data || { scheduled_today: 0, attended_today: 0 }
    weekly.value = weeklyResponse.data.data || []
  } catch (error: any) {
    loadError.value = error?.response?.data?.message || 'Please try again.'
  } finally {
    loading.value = false
  }
}

onMounted(loadDashboard)
</script>

<style scoped>
:deep(.p-datatable-tbody > tr) { cursor: pointer; }
.dashboard--hr .overflow-x-auto { display: none; }
</style>
