<template>
  <div class="px-6 max-w-8xl">
    <div :class="props.horizontal ? 'flex flex-col gap-3' : 'flex flex-col gap-4 md:flex-row md:items-start'">
      <!-- Settings Sidebar -->
      <aside :class="props.horizontal ? 'w-full overflow-x-auto rounded-xl border border-gray-100 bg-white p-2 shadow-sm' : 'w-full shrink-0 rounded-xl border border-gray-100 bg-white p-2 shadow-sm md:w-56 md:flex-none'">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            props.horizontal ? 'inline-flex mr-1 items-center rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors' : 'mb-1 flex w-full items-center rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors',
            activeTab === tab.id
              ? 'bg-orange-50 text-orange-600'
              : 'text-gray-500 hover:bg-orange-50 hover:text-orange-600'
          ]"
        >
          <span>{{ tab.label }}</span>
        </button>
      </aside>

      <!-- Content Area -->
      <div class="min-h-[600px] min-w-0 flex-1 overflow-hidden rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
      <!-- Deduction Types Tab -->
      <DeductionType v-if="activeTab === 'deduction-types'" />

      <!-- Activity Log Tab -->
      <ActivityLog v-else-if="activeTab === 'activity-log'" />

      <!-- Recruitment Settings -->
      <div v-else-if="activeTab === 'interview-settings'" class="max-w-xl space-y-4">
        <div>
          <h3 class="text-lg font-semibold text-slate-900">Recruitment Capacity</h3>
          <p class="text-sm text-slate-500">Set how many applicants can be scheduled for interviews per day.</p>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Daily interview capacity</label>
          <InputNumber v-model="dailyInterviewLimit" :min="1" :max="50" class="w-full" size="small" />
          <p class="text-xs text-slate-500">Default: 10 per day.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div><label class="text-sm font-medium text-slate-700">Interview duration (minutes)</label><InputNumber v-model="recruitmentSettings.interviewDuration" :min="15" :max="240" class="w-full" size="small" /></div>
          <div><label class="text-sm font-medium text-slate-700">Scheduling notice (hours)</label><InputNumber v-model="recruitmentSettings.schedulingNoticeHours" :min="0" :max="168" class="w-full" size="small" /></div>
          <div class="md:col-span-2"><label class="text-sm font-medium text-slate-700">Applicant status options</label><InputText v-model="recruitmentSettings.statusOptions" class="w-full" size="small" placeholder="Applied, Screening, Interview, Hired, Rejected" /></div>
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Save" size="small" :loading="savingSettings" @click="saveInterviewSettings" />
        </div>
      </div>

      <div v-else-if="activeTab === 'workforce-rules'" class="max-w-3xl space-y-4">
        <div><h3 class="text-lg font-semibold text-slate-900">Workforce Rules</h3><p class="text-sm text-slate-500">Set the basic limits used for employee schedules and attendance.</p></div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div><label class="text-sm font-medium text-slate-700">Daily hours</label><InputNumber v-model="workforceRules.dailyHours" :min="1" :max="24" class="w-full" size="small" /></div>
          <div><label class="text-sm font-medium text-slate-700">Days per week</label><InputNumber v-model="workforceRules.weeklyDays" :min="1" :max="7" class="w-full" size="small" /></div>
          <div><label class="text-sm font-medium text-slate-700">Break duration (minutes)</label><InputNumber v-model="workforceRules.breakMinutes" :min="0" :max="480" class="w-full" size="small" /></div>
          <div class="flex items-center gap-2"><InputSwitch v-model="workforceRules.overtimeEnabled" /><label class="text-sm font-medium text-slate-700">Overtime enabled</label></div>
        </div>
        <div class="flex justify-end"><Button label="Save Rules" severity="warn" size="small" :loading="savingSettings" @click="saveWorkforceRules" /></div>
      </div>

      <div v-else-if="activeTab === 'attendance-settings'" class="max-w-3xl space-y-4">
        <div><h3 class="text-lg font-semibold text-slate-900">Attendance Policies</h3><p class="text-sm text-slate-500">The location card mirrors Store Settings. Configure attendance rules below.</p></div>
        <Card>
          <template #content>
            <div class="grid gap-4 lg:grid-cols-[1fr_0.8fr]">
              <div class="space-y-3 text-sm text-slate-700">
                <div><div class="text-xs uppercase text-slate-400">Address</div><div class="font-semibold">{{ attendance.address || 'Not set' }}</div></div>
                <div class="grid grid-cols-2 gap-3"><div><div class="text-xs uppercase text-slate-400">Barangay</div><div class="font-semibold">{{ attendance.barangay || 'Not set' }}</div></div><div><div class="text-xs uppercase text-slate-400">City</div><div class="font-semibold">{{ attendance.city || 'Not set' }}</div></div><div><div class="text-xs uppercase text-slate-400">Province</div><div class="font-semibold">{{ attendance.province || 'Not set' }}</div></div></div>
              </div>
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-4"><div class="text-xs uppercase text-slate-400">Protection</div><div class="mt-1 font-semibold text-slate-900">OTP required before updates</div><p class="mt-2 text-xs leading-5 text-slate-500">Changes require authorization from the store account email.</p><Button class="mt-4 w-full" label="Edit Attendance Location" icon="pi pi-map-marker" outlined size="small" @click="openAttendanceEditor" /></div>
            </div>
          </template>
        </Card>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div><label class="text-sm font-medium text-slate-700">Late threshold (minutes)</label><InputNumber v-model="attendanceRules.lateThreshold" :min="0" :max="240" class="w-full" size="small" /></div>
          <div><label class="text-sm font-medium text-slate-700">Grace period (minutes)</label><InputNumber v-model="attendanceRules.gracePeriod" :min="0" :max="240" class="w-full" size="small" /></div>
          <div><label class="text-sm font-medium text-slate-700">Timezone</label><Select v-model="attendanceRules.timezone" :options="timezones" class="w-full" size="small" /></div>
        </div>
        <div class="flex justify-end"><Button label="Save Attendance Rules" severity="warn" size="small" :loading="savingSettings" @click="saveAttendanceRules" /></div>
      </div>

      <div v-else-if="activeTab === 'holidays'" class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div><h3 class="text-lg font-semibold text-slate-900">Holidays</h3><p class="text-sm text-slate-500">Manage holidays used by attendance, leave, and payroll for this store.</p></div>
          <div class="flex gap-2"><input ref="holidayFileInput" type="file" accept=".csv,text/csv" class="hidden" @change="importHolidays" /><Button label="Import CSV" icon="pi pi-upload" severity="secondary" outlined size="small" @click="holidayFileInput?.click()" /><Button label="Add Holiday" icon="pi pi-plus" severity="warn" size="small" @click="holidayDialogVisible = true" /></div>
        </div>
        <div class="flex items-center gap-2"><Select v-model="holidayYear" :options="holidayYears" class="w-32" size="small" @change="loadHolidays" /><span class="text-xs text-slate-500">CSV columns: name, holiday_date, holiday_type, is_working_holiday, rate_multiplier, description</span></div>
        <Card><template #content><DataTable :value="holidays" :loading="holidaysLoading" size="small" class="text-sm"><template #loading><div class="space-y-2"><Skeleton v-for="n in 5" :key="n" height="2rem" /></div></template><template #empty><div class="py-10 text-center text-sm text-slate-500">No holidays recorded for {{ holidayYear }}.</div></template><Column field="name" header="Holiday" /><Column field="holiday_date" header="Date" /><Column field="holiday_type" header="Type"><template #body="{ data }"><Tag :value="data.holiday_type" severity="info" /></template></Column><Column header="Work status"><template #body="{ data }"><Tag :value="data.is_working_holiday ? 'Working holiday' : 'Non-working'" :severity="data.is_working_holiday ? 'warn' : 'success'" /></template></Column><Column header="Actions"><template #body="{ data }"><Button icon="pi pi-trash" text severity="danger" size="small" @click="deleteHoliday(data)" /></template></Column></DataTable></template></Card>
      </div>

      <Dialog v-model:visible="holidayDialogVisible" modal header="Add Holiday" :style="{ width: '32rem' }">
        <div class="space-y-3 text-sm"><div><label class="font-medium">Holiday name</label><InputText v-model="holidayForm.name" class="mt-1 w-full" size="small" /></div><div><label class="font-medium">Date or date range</label><DatePicker v-model="holidayForm.dates" selectionMode="range" dateFormat="MM dd, yy" :manualInput="false" class="mt-1 w-full" size="small" showIcon /></div><p class="text-xs text-slate-500">Use a range for multi-day holidays. Each date will be saved separately.</p><div><label class="font-medium">Type</label><Select v-model="holidayForm.holiday_type" :options="holidayTypes" optionLabel="label" optionValue="value" class="mt-1 w-full" size="small" /></div><div class="flex items-center gap-2"><InputSwitch v-model="holidayForm.is_working_holiday" /><label>Working holiday</label></div></div>
        <template #footer><Button label="Cancel" severity="secondary" text @click="holidayDialogVisible = false" /><Button label="Save Holiday" severity="warn" size="small" :loading="holidaySaving" @click="saveHoliday" /></template>
      </Dialog>

      <Dialog v-model:visible="attendanceEditorVisible" modal header="Edit Attendance Geolocation" :style="{ width: '38rem' }">
        <div class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr] text-sm">
          <div>
            <div class="mb-3 flex gap-2"><InputText v-model="attendanceSearch" class="min-w-0 flex-1" placeholder="Search address..." size="small" /><Button label="Search" icon="pi pi-search" severity="secondary" size="small" @click="searchAttendanceAddress" /></div>
            <div ref="attendanceMapEl" class="h-72 w-full rounded-xl border border-slate-200"></div>
            <p class="mt-2 text-xs text-slate-500">Click the map or drag the pin to set the attendance location.</p>
          </div>
          <div class="space-y-4">
          <div class="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
            <div><div class="text-xs uppercase text-slate-400">Geofence</div><div class="font-semibold">{{ attendanceDraft.geofence_enabled ? 'Enabled' : 'Disabled' }}</div></div>
            <InputSwitch v-model="attendanceDraft.geofence_enabled" />
          </div>
          <div><div class="mb-2 flex justify-between"><label class="text-sm font-medium text-slate-700">Attendance radius</label><span class="font-semibold">{{ attendanceDraft.geofence_radius_m }} meters</span></div><Slider v-model="attendanceDraft.geofence_radius_m" :min="0" :max="1000" :step="1" class="w-full" @input="updateAttendanceCircle" /></div>
          <div class="grid grid-cols-3 gap-3 text-xs text-slate-600"><div><span class="text-slate-400">Barangay</span><div class="font-semibold">{{ attendanceDraft.barangay || 'Not set' }}</div></div><div><span class="text-slate-400">City</span><div class="font-semibold">{{ attendanceDraft.city || 'Not set' }}</div></div><div><span class="text-slate-400">Province</span><div class="font-semibold">{{ attendanceDraft.province || 'Not set' }}</div></div></div>
          <p class="rounded-lg bg-orange-50 p-3 text-xs text-orange-700">Saving will continue to OTP verification before the attendance location is updated.</p>
          </div>
        </div>
        <template #footer><Button label="Cancel" severity="secondary" text @click="attendanceEditorVisible = false" /><Button label="Continue to OTP" severity="warn" :loading="savingAttendance" @click="saveAttendance" /></template>
      </Dialog>

      <div v-if="activeTab === 'payroll-settings'" class="max-w-2xl space-y-4">
        <div><h3 class="text-lg font-semibold text-slate-900">Payroll Basics</h3><p class="text-sm text-slate-500">Configure defaults used when creating employee payroll records.</p></div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div><label class="text-sm font-medium text-slate-700">Pay frequency</label><Select v-model="payrollSettings.frequency" :options="payFrequencies" optionLabel="label" optionValue="value" class="w-full" size="small" /></div>
          <div><label class="text-sm font-medium text-slate-700">Overtime multiplier</label><InputNumber v-model="payrollSettings.overtimeMultiplier" :min="1" :max="5" :minFractionDigits="2" class="w-full" size="small" /></div>
          <div><label class="text-sm font-medium text-slate-700">Default salary basis</label><Select v-model="payrollSettings.salaryBasis" :options="salaryBases" optionLabel="label" optionValue="value" class="w-full" size="small" /></div>
          <div><label class="text-sm font-medium text-slate-700">Late/undertime deduction</label><Select v-model="payrollSettings.deductionRule" :options="deductionRules" optionLabel="label" optionValue="value" class="w-full" size="small" /></div>
        </div>
        <div class="flex items-center gap-2"><InputSwitch v-model="payrollSettings.governmentContributions" /><label class="text-sm font-medium text-slate-700">Include government contribution settings</label></div>
        <div class="flex justify-end"><Button label="Save Payroll Defaults" severity="warn" size="small" :loading="savingSettings" @click="savePayrollSettings" /></div>
      </div>

      <!-- Leave Settings -->
      <div v-else-if="activeTab === 'leave-settings'" class="space-y-6">
        <div class="max-w-3xl space-y-4">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Default Leave Quotas</h3>
            <p class="text-sm text-slate-500">Set default yearly quotas used when initializing balances for employees.</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div v-for="type in leaveTypeOptions" :key="type.value" class="space-y-2">
              <label class="text-sm font-medium text-slate-700">{{ type.label }}</label>
              <InputNumber v-model="leaveDefaults[type.value]" :min="0" class="w-full" />
            </div>
          </div>
          <div class="flex items-center gap-2"><InputSwitch v-model="leaveSettings.paidLeave" /><label class="text-sm font-medium text-slate-700">Treat default leave as paid</label></div>
          <div class="flex justify-end gap-2">
            <Button label="Save Defaults" size="small" :loading="savingLeaveSettings" @click="saveLeaveDefaults" />
          </div>
        </div>

        <div class="border-t border-gray-100 pt-6 max-w-3xl space-y-4">
          <div>
            <h3 class="text-lg font-semibold text-slate-900">Adjust Employee Balance</h3>
            <p class="text-sm text-slate-500">Apply manual adjustments for a specific employee.</p>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-700">Employee</label>
              <Select
                v-model="adjustForm.employeeId"
                :options="employeeOptions"
                optionLabel="name"
                optionValue="id"
                placeholder="Select employee"
                class="w-full"
                @change="loadEmployeeBalances"
              />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-700">Leave Type</label>
              <Select
                v-model="adjustForm.leaveType"
                :options="leaveTypeOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select leave type"
                class="w-full"
              />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-700">Adjustment Type</label>
              <Select
                v-model="adjustForm.adjustmentType"
                :options="adjustmentOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
              />
            </div>
            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-700">Days</label>
              <InputNumber v-model="adjustForm.days" :min="0" class="w-full" />
            </div>
          </div>
          <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">Reason (optional)</label>
            <InputText v-model="adjustForm.reason" placeholder="Reason for adjustment" class="w-full" />
          </div>
          <div v-if="selectedBalance" class="text-sm text-slate-600 bg-slate-50 border border-slate-100 rounded-lg p-3">
            Current quota: <span class="font-semibold">{{ selectedBalance.yearly_quota }}</span> days ·
            Used: <span class="font-semibold">{{ selectedBalance.used_days }}</span> ·
            Remaining: <span class="font-semibold text-blue-600">{{ selectedBalance.remaining_days }}</span>
          </div>
          <div class="flex justify-end gap-2">
            <Button
              label="Apply Adjustment"
              size="small"  
              :loading="savingAdjustment"
              @click="applyLeaveAdjustment"
            />
          </div>
        </div>
      </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, nextTick } from 'vue'
import DeductionType from './DeductionType.vue';
import ActivityLog from './ActivityLog.vue';
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import InputSwitch from 'primevue/inputswitch'
import Dialog from 'primevue/dialog'
import Slider from 'primevue/slider'
import Skeleton from 'primevue/skeleton'
import DatePicker from 'primevue/datepicker'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png'
import markerIcon from 'leaflet/dist/images/marker-icon.png'
import markerShadow from 'leaflet/dist/images/marker-shadow.png'
import { useToast } from 'primevue/usetoast'
import hrService from '../../../services/hr.services'
import { useRouter } from 'vue-router'
import { router as inertiaRouter } from '@inertiajs/vue3'
// import ShiftSwaps from './ShiftSwaps.vue'

// Tabs configuration
const props = defineProps<{ horizontal?: boolean }>()
const tabs = [
    {
      id: 'deduction-types',
      label: 'Deduction Types',
      icon: 'pi pi-percentage'
    },
    { id: 'workforce-rules', label: 'Workforce Rules', icon: 'pi pi-clock' },
    { id: 'attendance-settings', label: 'Attendance', icon: 'pi pi-map-marker' },
    { id: 'holidays', label: 'Holidays', icon: 'pi pi-calendar' },
    {
      id: 'interview-settings',
      label: 'Recruitment',
      icon: 'pi pi-calendar'
    },
    {
      id: 'leave-settings',
      label: 'Leave Settings',
      icon: 'pi pi-briefcase'
    },
    { id: 'payroll-settings', label: 'Payroll', icon: 'pi pi-wallet' },
  // {
  //   id: 'overtime-rules',
  //   label: 'Overtime Rules',
  //   icon: 'pi pi-clock'
  // },
  // {
  //   id: 'payroll-settings',
  //   label: 'Payroll Settings',
  //   icon: 'pi pi-dollar'
  // },
  // {
  //   id: 'company-info',
  //   label: 'Company Info',
  //   icon: 'pi pi-building'
  // },
  // {
  //   id: 'activity-log',
  //   label: 'Activity Log',
  //   icon: 'pi pi-book'
  // }
]

const activeTab = ref('deduction-types')
const toast = useToast()
const router = useRouter()
const dailyInterviewLimit = ref(10)
const savingSettings = ref(false)
const savingLeaveSettings = ref(false)
const savingAdjustment = ref(false)
const workforceRules = ref({ dailyHours: 8, weeklyDays: 6, overtimeEnabled: true, breakMinutes: 60 })
const payrollSettings = ref({ frequency: 'monthly', overtimeMultiplier: 1.25, salaryBasis: 'monthly', deductionRule: 'per_minute', governmentContributions: false })
const recruitmentSettings = ref({ interviewDuration: 30, schedulingNoticeHours: 24, statusOptions: 'Applied, Screening, Interview, Hired, Rejected' })
const attendanceRules = ref({ geofenceEnabled: true, radiusMeters: 100, lateThreshold: 15, gracePeriod: 5, timezone: 'Asia/Manila' })
const timezones = ['Asia/Manila', 'UTC', 'Asia/Singapore']
const salaryBases = [{ label: 'Hourly', value: 'hourly' }, { label: 'Monthly', value: 'monthly' }]
const deductionRules = [{ label: 'Per minute', value: 'per_minute' }, { label: 'Per hour', value: 'per_hour' }, { label: 'Disabled', value: 'disabled' }]
const attendance = ref({ address: '', barangay: '', city: '', province: '', latitude: null, longitude: null, branch_id: null, geofence_radius_m: 0, geofence_enabled: true })
const attendanceEditorVisible = ref(false)
const savingAttendance = ref(false)
const attendanceDraft = ref({ ...attendance.value })
const holidays = ref<any[]>([])
const holidaysLoading = ref(false)
const holidaySaving = ref(false)
const holidayDialogVisible = ref(false)
const holidayFileInput = ref<HTMLInputElement | null>(null)
const holidayYear = ref(new Date().getFullYear())
const holidayYears = Array.from({ length: 5 }, (_, index) => new Date().getFullYear() - 2 + index)
const holidayTypes = [{ label: 'Regular', value: 'regular' }, { label: 'Special', value: 'special' }, { label: 'Company', value: 'company' }]
const holidayForm = ref({ name: '', dates: [] as Date[], holiday_type: 'regular', is_working_holiday: false })
const attendanceSearch = ref('')
const attendanceMapEl = ref<HTMLElement | null>(null)
let attendanceMap: any = null
let attendanceMarker: any = null
let attendanceCircle: any = null
const payFrequencies = [
  { label: 'Weekly', value: 'weekly' },
  { label: 'Biweekly', value: 'biweekly' },
  { label: 'Monthly', value: 'monthly' },
]

const leaveDefaults = ref<Record<string, number>>({
  vacation: 15,
  sick: 10,
  personal: 5,
  maternity: 0,
  paternity: 0,
  bereavement: 0,
  others: 0,
})
const leaveSettings = ref({ paidLeave: true })

const leaveTypeOptions = [
  { label: 'Vacation Leave', value: 'vacation' },
  { label: 'Sick Leave', value: 'sick' },
  { label: 'Personal Leave', value: 'personal' },
  { label: 'Maternity Leave', value: 'maternity' },
  { label: 'Paternity Leave', value: 'paternity' },
  { label: 'Bereavement Leave', value: 'bereavement' },
  { label: 'Other Leave', value: 'others' },
]

const adjustmentOptions = [
  { label: 'Add Days', value: 'add' },
  { label: 'Deduct Days', value: 'deduct' },
  { label: 'Set Quota', value: 'set' },
]

const employeeOptions = ref<{ id: number; name: string }[]>([])
const employeeBalances = ref<Record<string, any> | null>(null)

const adjustForm = ref({
  employeeId: null as number | null,
  leaveType: 'vacation',
  adjustmentType: 'add',
  days: 0,
  reason: '',
})

const selectedBalance = computed(() => {
  if (!employeeBalances.value || !adjustForm.value.leaveType) return null
  return employeeBalances.value[adjustForm.value.leaveType] || null
})

const loadInterviewSettings = async () => {
  try {
    const response = await hrService.getHrSettings()
    dailyInterviewLimit.value = Number(response?.data?.daily_interview_limit || 10)
    if (response?.data?.leave_defaults) {
      leaveDefaults.value = {
        ...leaveDefaults.value,
        ...response.data.leave_defaults,
      }
    }
    leaveSettings.value = { ...leaveSettings.value, ...(response?.data?.leave_settings || {}) }
    workforceRules.value = { ...workforceRules.value, ...(response?.data?.workforce_rules || {}) }
    payrollSettings.value = { ...payrollSettings.value, ...(response?.data?.payroll_settings || {}) }
    recruitmentSettings.value = { ...recruitmentSettings.value, ...(response?.data?.recruitment_settings || {}) }
    attendanceRules.value = { ...attendanceRules.value, ...(response?.data?.attendance_rules || {}) }
    const storeResponse = await hrService.api.get('/api/store/settings')
    attendance.value = { ...attendance.value, ...(storeResponse?.data?.data?.attendance || {}) }
    await loadHolidays()
  } catch (error) {
    // silent fallback to default
  }
}

const loadHolidays = async () => {
  holidaysLoading.value = true
  try { const response = await hrService.api.get('api/holidays', { params: { year: holidayYear.value } }); holidays.value = response.data?.data || [] }
  catch (_) { holidays.value = [] }
  finally { holidaysLoading.value = false }
}

const saveHoliday = async () => {
  holidaySaving.value = true
  try {
    const dates = holidayForm.value.dates || []
    if (!dates[0]) throw new Error('Select a holiday date.')
    const start = new Date(dates[0]); const end = new Date(dates[1] || dates[0]); const requests = []
    for (const date = new Date(start); date <= end; date.setDate(date.getDate() + 1)) {
      const dateValue = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
      requests.push(hrService.api.post('api/holidays', { name: holidayForm.value.name, holiday_date: dateValue, holiday_type: holidayForm.value.holiday_type, is_working_holiday: holidayForm.value.is_working_holiday }))
    }
    await Promise.all(requests)
    holidayDialogVisible.value = false
    holidayForm.value = { name: '', dates: [], holiday_type: 'regular', is_working_holiday: false }
    await loadHolidays()
    toast.add({ severity: 'success', summary: 'Saved', detail: `${requests.length} holiday date(s) added.`, life: 2500 })
  }
  catch (error: any) { toast.add({ severity: 'error', summary: 'Save Failed', detail: error?.response?.data?.message || 'Unable to save holiday.', life: 3000 }) }
  finally { holidaySaving.value = false }
}

const importHolidays = async (event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0]
  if (!file) return
  const form = new FormData(); form.append('file', file)
  try { await hrService.api.post('api/holidays/import', form, { headers: { 'Content-Type': 'multipart/form-data' } }); await loadHolidays(); toast.add({ severity: 'success', summary: 'Imported', detail: 'Holiday CSV imported.', life: 2500 }) }
  catch (error: any) { toast.add({ severity: 'error', summary: 'Import Failed', detail: error?.response?.data?.message || 'Unable to import holiday CSV.', life: 3000 }) }
  finally { if (holidayFileInput.value) holidayFileInput.value.value = '' }
}

const deleteHoliday = async (holiday: any) => {
  if (!window.confirm(`Delete ${holiday.name}?`)) return
  try { await hrService.api.delete(`api/holidays/${holiday.id}`); await loadHolidays(); toast.add({ severity: 'success', summary: 'Deleted', detail: 'Holiday removed.', life: 2500 }) }
  catch (_) { toast.add({ severity: 'error', summary: 'Delete Failed', detail: 'Unable to remove holiday.', life: 3000 }) }
}

const saveWorkforceRules = async () => {
  savingSettings.value = true
  try { await hrService.updateHrSettings({ workforce_rules: workforceRules.value }); toast.add({ severity: 'success', summary: 'Saved', detail: 'Workforce rules updated.', life: 2500 }) }
  catch (error: any) { toast.add({ severity: 'error', summary: 'Save Failed', detail: error?.response?.data?.message || 'Unable to save workforce rules.', life: 3000 }) }
  finally { savingSettings.value = false }
}

const savePayrollSettings = async () => {
  savingSettings.value = true
  try { await hrService.updateHrSettings({ payroll_settings: payrollSettings.value }); toast.add({ severity: 'success', summary: 'Saved', detail: 'Payroll defaults updated.', life: 2500 }) }
  catch (error: any) { toast.add({ severity: 'error', summary: 'Save Failed', detail: error?.response?.data?.message || 'Unable to save payroll defaults.', life: 3000 }) }
  finally { savingSettings.value = false }
}

const saveAttendanceRules = async () => {
  savingSettings.value = true
  try { await hrService.updateHrSettings({ attendance_rules: attendanceRules.value }); toast.add({ severity: 'success', summary: 'Saved', detail: 'Attendance rules updated.', life: 2500 }) }
  catch (error: any) { toast.add({ severity: 'error', summary: 'Save Failed', detail: error?.response?.data?.message || 'Unable to save attendance rules.', life: 3000 }) }
  finally { savingSettings.value = false }
}

const openAttendanceEditor = () => {
  attendanceDraft.value = { ...attendance.value }
  attendanceSearch.value = attendance.value.address || ''
  attendanceEditorVisible.value = true
  nextTick(initAttendanceMap)
}

const syncAttendancePoint = async (lat: number, lng: number) => {
  attendanceDraft.value.latitude = lat
  attendanceDraft.value.longitude = lng
  if (attendanceMap) attendanceMap.setView([lat, lng])
  if (attendanceMarker) attendanceMarker.setLatLng([lat, lng])
  if (attendanceCircle) attendanceCircle.setLatLng([lat, lng])
  try {
    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=en`)
    const data = await response.json()
    const address = data?.address || {}
    attendanceDraft.value.address = data?.display_name || attendanceDraft.value.address
    attendanceDraft.value.barangay = address.suburb || address.village || address.neighbourhood || attendanceDraft.value.barangay
    attendanceDraft.value.city = address.city || address.town || address.municipality || attendanceDraft.value.city
    attendanceDraft.value.province = address.state || address.region || attendanceDraft.value.province
  } catch (_) {}
}

const initAttendanceMap = () => {
  if (!attendanceMapEl.value) return
  if (attendanceMap) attendanceMap.remove()
  const center: [number, number] = [attendanceDraft.value.latitude ?? 14.5995, attendanceDraft.value.longitude ?? 120.9842]
  attendanceMap = L.map(attendanceMapEl.value).setView(center, attendanceDraft.value.latitude ? 16 : 12)
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(attendanceMap)
  attendanceMarker = L.marker(center, { draggable: true }).addTo(attendanceMap)
  attendanceCircle = L.circle(center, { radius: attendanceDraft.value.geofence_radius_m || 5, color: '#f97316', fillColor: '#fb923c', fillOpacity: 0.15 }).addTo(attendanceMap)
  attendanceMarker.on('dragend', () => { const p = attendanceMarker.getLatLng(); syncAttendancePoint(p.lat, p.lng) })
  attendanceMap.on('click', (event: any) => syncAttendancePoint(event.latlng.lat, event.latlng.lng))
}

const searchAttendanceAddress = async () => {
  if (!attendanceSearch.value.trim()) return
  const response = await fetch(`https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(attendanceSearch.value)}&limit=1`)
  const results = await response.json()
  if (results?.[0]) await syncAttendancePoint(Number(results[0].lat), Number(results[0].lon))
}

const saveAttendance = () => {
  savingAttendance.value = true
  inertiaRouter.post('/store/settings/attendance/prepare', {
    branch_id: attendanceDraft.value.branch_id,
    address: attendanceDraft.value.address || null,
    barangay: attendanceDraft.value.barangay || null,
    city: attendanceDraft.value.city || null,
    province: attendanceDraft.value.province || null,
    latitude: attendanceDraft.value.latitude,
    longitude: attendanceDraft.value.longitude,
    geofence_radius_m: attendanceDraft.value.geofence_radius_m || 0,
    geofence_enabled: attendanceDraft.value.geofence_enabled,
  }, { onFinish: () => { savingAttendance.value = false } })
}

const updateAttendanceCircle = () => {
  if (attendanceCircle) attendanceCircle.setRadius(attendanceDraft.value.geofence_radius_m || 0)
}

const saveInterviewSettings = async () => {
  savingSettings.value = true
  try {
    await hrService.updateHrSettings({ daily_interview_limit: Number(dailyInterviewLimit.value || 10), recruitment_settings: recruitmentSettings.value })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Recruitment settings updated.', life: 2500 })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Save Failed',
      detail: error?.response?.data?.message || 'Unable to update interview settings.',
      life: 3000,
    })
  } finally {
    savingSettings.value = false
  }
}

const saveLeaveDefaults = async () => {
  savingLeaveSettings.value = true
  try {
    await hrService.updateHrSettings({ leave_defaults: leaveDefaults.value, leave_settings: leaveSettings.value })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Leave defaults updated.', life: 2500 })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Save Failed',
      detail: error?.response?.data?.message || 'Unable to update leave defaults.',
      life: 3000,
    })
  } finally {
    savingLeaveSettings.value = false
  }
}

const loadEmployeeOptions = async () => {
  try {
    const response = await hrService.getEmployees()
    const records = response?.data?.data || response?.data || []
    employeeOptions.value = records.map((emp: any) => ({
      id: emp.id,
      name: `${emp.fname} ${emp.lname}`.trim(),
    }))
  } catch (error) {
    employeeOptions.value = []
  }
}

const loadEmployeeBalances = async () => {
  if (!adjustForm.value.employeeId) {
    employeeBalances.value = null
    return
  }
  try {
    const year = new Date().getFullYear()
    const response = await hrService.getEmployeeLeaveBalances(adjustForm.value.employeeId, year)
    const balances = response?.data?.balances || []
    const map: Record<string, any> = {}
    balances.forEach((balance: any) => {
      map[balance.leave_type] = balance
    })
    employeeBalances.value = map
  } catch (error) {
    employeeBalances.value = null
  }
}

const applyLeaveAdjustment = async () => {
  if (!adjustForm.value.employeeId || !selectedBalance.value) {
    toast.add({ severity: 'warn', summary: 'Missing Data', detail: 'Select an employee and leave type first.', life: 2500 })
    return
  }
  if (!adjustForm.value.days || adjustForm.value.days < 0) {
    toast.add({ severity: 'warn', summary: 'Invalid Days', detail: 'Enter a valid number of days.', life: 2500 })
    return
  }
  savingAdjustment.value = true
  try {
    const balance = selectedBalance.value
    const currentQuota = Number(balance.yearly_quota || 0)
    let newQuota = currentQuota
    if (adjustForm.value.adjustmentType === 'add') {
      newQuota = currentQuota + Number(adjustForm.value.days || 0)
    } else if (adjustForm.value.adjustmentType === 'deduct') {
      newQuota = Math.max(0, currentQuota - Number(adjustForm.value.days || 0))
    } else {
      newQuota = Number(adjustForm.value.days || 0)
    }
    await hrService.updateLeaveBalance(balance.id, {
      yearly_quota: newQuota,
      notes: adjustForm.value.reason || null,
    })
    toast.add({ severity: 'success', summary: 'Updated', detail: 'Leave balance adjusted.', life: 2500 })
    await loadEmployeeBalances()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Update Failed',
      detail: error?.response?.data?.message || 'Unable to adjust leave balance.',
      life: 3000,
    })
  } finally {
    savingAdjustment.value = false
  }
}

onMounted(loadInterviewSettings)
onMounted(loadEmployeeOptions)
</script>
