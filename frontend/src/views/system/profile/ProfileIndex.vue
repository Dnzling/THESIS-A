<template>
  <div class="px-6 pb-10 max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <p class="text-xs uppercase tracking-[0.2em] text-blue-500">Employee Portal</p>
        <h1 class="text-2xl font-semibold text-slate-900">My Profile</h1>
        <p class="text-sm text-slate-500">Credentials, attendance, leave, swaps, and payslips in one place.</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" @click="refreshAll" />
    </div>

    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
          <div class="lg:col-span-2">
            <Tabs v-model:value="activeTab" class="w-full">
              <TabList>
                <Tab value="profile">Profile</Tab>
                <Tab value="attendance">
                  Attendance
                  <Badge v-if="unseen.attendance" :value="unseen.attendance" class="ml-2" severity="info" />
                </Tab>
                <Tab value="leave">
                  Leave
                  <Badge v-if="unseen.leave" :value="unseen.leave" class="ml-2" severity="warning" />
                </Tab>
                <Tab value="overtime">
                  Overtime
                  <Badge v-if="unseen.overtime" :value="unseen.overtime" class="ml-2" severity="danger" />
                </Tab>
                <Tab value="swaps">
                  Shift Swaps
                  <Badge v-if="unseen.swaps" :value="unseen.swaps" class="ml-2" severity="info" />
                </Tab>
                <Tab value="payslips">Payslips</Tab>
              </TabList>

              <TabPanels>
                <TabPanel value="profile">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label class="text-xs font-semibold text-slate-500">First Name</label>
                      <InputText v-model="profileForm.fname" class="w-full" />
                    </div>
                    <div>
                      <label class="text-xs font-semibold text-slate-500">Last Name</label>
                      <InputText v-model="profileForm.lname" class="w-full" />
                    </div>
                    <div>
                      <label class="text-xs font-semibold text-slate-500">Email</label>
                      <InputText v-model="profileForm.email" class="w-full" />
                    </div>
                    <div>
                      <label class="text-xs font-semibold text-slate-500">Phone</label>
                      <InputText v-model="profileForm.phone_number" class="w-full" inputmode="numeric" />
                    </div>
                    <div class="md:col-span-2">
                      <label class="text-xs font-semibold text-slate-500">Address</label>
                      <InputText v-model="profileForm.address" class="w-full" />
                    </div>
                    <div>
                      <label class="text-xs font-semibold text-slate-500">City</label>
                      <InputText v-model="profileForm.city" class="w-full" />
                    </div>
                    <div>
                      <label class="text-xs font-semibold text-slate-500">Province</label>
                      <InputText v-model="profileForm.province" class="w-full" />
                    </div>
                  </div>
                  <div class="flex justify-end mt-4">
                    <div class="flex items-center gap-2">
                      <Button label="Change Password" icon="pi pi-lock" severity="secondary" @click="showPasswordDialog = true" />
                      <Button label="Save Changes" icon="pi pi-check" :disabled="savingProfile || !canSaveProfile" :loading="savingProfile" @click="saveProfile" />
                    </div>
                  </div>
                </TabPanel>

                <TabPanel value="attendance">
                  <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <div>
                      <h3 class="text-sm font-semibold text-slate-700">Attendance Records</h3>
                      <p class="text-xs text-slate-500">Working hours are computed from total worked minutes.</p>
                    </div>
                    <Button label="Request Overtime" icon="pi pi-clock" severity="info" @click="showOvertimeDialog = true" />
                  </div>
                  <DataTable :value="attendanceRecords" class="p-datatable-sm" :loading="loading.attendance" responsiveLayout="scroll">
                    <Column header="Date">
                      <template #body="{ data }">
                        {{ formatDate(data.attendance_date) }}
                      </template>
                    </Column>
                    <Column header="Status">
                      <template #body="{ data }">
                        <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
                      </template>
                    </Column>
                    <Column header="Clock In">
                      <template #body="{ data }">
                        {{ formatDateTime(data.clock_in) }}
                      </template>
                    </Column>
                    <Column header="Clock Out">
                      <template #body="{ data }">
                        {{ formatDateTime(data.clock_out) }}
                      </template>
                    </Column>
                    <Column header="Worked">
                      <template #body="{ data }">
                        {{ formatMinutes(data.total_worked_minutes) }}
                      </template>
                    </Column>
                  </DataTable>
                </TabPanel>

                <TabPanel value="leave">
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="space-y-4">
                      <div>
                        <h3 class="text-sm font-semibold text-slate-700">Apply Leave</h3>
                        <p class="text-xs text-slate-500">Requests are routed to HR for approval.</p>
                      </div>
                      <Button label="Request Leave" icon="pi pi-send" severity="info" @click="showLeaveDialog = true" />
                    </div>
                    <div>
                      <h3 class="text-sm font-semibold text-slate-700">Leave Calendar</h3>
                      <p class="text-xs text-slate-500 mb-2">Highlights approved and pending leaves.</p>
                      <DatePicker v-model="leaveCalendarDates" selectionMode="multiple" inline class="w-full" />
                    </div>
                  </div>
                  <div class="mt-6">
                    <h3 class="text-sm font-semibold text-slate-700 mb-2">My Leave Requests</h3>
                    <DataTable :value="leaveRequests" class="p-datatable-sm" :loading="loading.leave" responsiveLayout="scroll">
                      <Column header="Type">
                        <template #body="{ data }">
                          {{ data.leave_type_label || formatStatus(data.leave_type) }}
                        </template>
                      </Column>
                      <Column header="Start">
                        <template #body="{ data }">
                          {{ data.start_date_formatted || formatDate(data.start_date) }}
                        </template>
                      </Column>
                      <Column header="End">
                        <template #body="{ data }">
                          {{ data.end_date_formatted || formatDate(data.end_date) }}
                        </template>
                      </Column>
                      <Column header="Status">
                        <template #body="{ data }">
                          <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
                        </template>
                      </Column>
                    </DataTable>
                  </div>
                </TabPanel>

                <TabPanel value="overtime">
                  <div class="space-y-4">
                    <div>
                      <h3 class="text-sm font-semibold text-slate-700">Overtime Requests</h3>
                      <p class="text-xs text-slate-500">Submit overtime tied to a specific attendance record.</p>
                    </div>
                    <Button label="Create Overtime Request" icon="pi pi-plus" severity="info" @click="showOvertimeDialog = true" />
                    <DataTable :value="overtimeRequests" class="p-datatable-sm" :loading="loading.overtime" responsiveLayout="scroll">
                      <Column header="Start">
                        <template #body="{ data }">
                          {{ formatDateTime(data.ot_start) }}
                        </template>
                      </Column>
                      <Column header="End">
                        <template #body="{ data }">
                          {{ formatDateTime(data.ot_end) }}
                        </template>
                      </Column>
                      <Column header="Type">
                        <template #body="{ data }">
                          {{ formatStatus(data.ot_type) }}
                        </template>
                      </Column>
                      <Column header="Status">
                        <template #body="{ data }">
                          <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
                        </template>
                      </Column>
                    </DataTable>
                    <Button label="Request Leave" icon="pi pi-calendar-plus" severity="secondary" @click="activeTab = 'leave'" />
                  </div>
                </TabPanel>

                <TabPanel value="swaps">
                  <div class="space-y-4">
                    <div class="flex items-center justify-between">
                      <div>
                        <h3 class="text-sm font-semibold text-slate-700">Shift Swap Requests</h3>
                        <p class="text-xs text-slate-500">Request a swap with teammates in the same role.</p>
                      </div>
                      <Button label="Request Swap" icon="pi pi-exchange" @click="showSwapDialog = true" />
                    </div>
                    <DataTable :value="shiftSwaps" class="p-datatable-sm" :loading="loading.swaps" responsiveLayout="scroll">
                      <Column header="Requestor Shift">
                        <template #body="{ data }">
                          {{ formatDate((data.requestorSchedule || data.requestor_schedule)?.schedule_date) }} -
                          {{ formatTime((data.requestorSchedule || data.requestor_schedule)?.shift?.start_time) }}
                          to {{ formatTime((data.requestorSchedule || data.requestor_schedule)?.shift?.end_time) }}
                        </template>
                      </Column>
                      <Column header="Receiver Shift">
                        <template #body="{ data }">
                          {{ formatDate((data.receiverSchedule || data.receiver_schedule)?.schedule_date) }} -
                          {{ formatTime((data.receiverSchedule || data.receiver_schedule)?.shift?.start_time) }}
                          to {{ formatTime((data.receiverSchedule || data.receiver_schedule)?.shift?.end_time) }}
                        </template>
                      </Column>
                      <Column header="Status">
                        <template #body="{ data }">
                          <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
                        </template>
                      </Column>
                    </DataTable>
                  </div>
                </TabPanel>

                <TabPanel value="payslips">
                  <div class="space-y-4">
                    <div>
                      <h3 class="text-sm font-semibold text-slate-700">Payslip History</h3>
                      <p class="text-xs text-slate-500">Download PDFs with the print timestamp included.</p>
                    </div>
                    <DataTable :value="payslips" class="p-datatable-sm" :loading="loading.payslips" responsiveLayout="scroll">
                      <Column field="pay_period" header="Pay Period">
                        <template #body="{ data }">{{ data.pay_period?.name ?? 'Period' }}</template>
                      </Column>
                      <Column field="net_salary" header="Net Pay" />
                      <Column header="Status">
                        <template #body="{ data }">
                          <Tag :value="formatStatus(data.status)" :severity="statusSeverity(data.status)" />
                        </template>
                      </Column>
                      <Column header="Actions">
                        <template #body="{ data }">
                          <Button icon="pi pi-download" text severity="info" @click="downloadPayslip(data)" />
                          <Button icon="pi pi-print" text severity="secondary" @click="printPayslip(data)" />
                        </template>
                      </Column>
                    </DataTable>
                  </div>
                </TabPanel>
              </TabPanels>
            </Tabs>
          </div>

          <div class="space-y-4">
            <div class="rounded-xl border border-slate-200 p-4">
              <h3 class="text-sm font-semibold text-slate-700">Employee Snapshot</h3>
              <div v-if="employee">
                <p class="text-sm text-slate-600 mt-2">ID: {{ employee.employee_number }}</p>
                <p class="text-sm text-slate-600">Role: {{ employee.user?.role_name ?? 'Employee' }}</p>
                <p class="text-sm text-slate-600">Department: {{ employee.department ?? 'N/A' }}</p>
                <p class="text-sm text-slate-600">Branch: {{ employee.user?.branch?.name ?? 'N/A' }}</p>
              </div>
              <Skeleton v-else height="80px" />
            </div>

            <div class="rounded-xl border border-slate-200 p-4">
              <h3 class="text-sm font-semibold text-slate-700">Notifications</h3>
              <div class="space-y-2 mt-2">
                <div class="flex items-center justify-between text-xs text-slate-500">
                  <span>Pending Leave</span>
                  <Badge :value="pendingCounts.leave" severity="warning" />
                </div>
                <div class="flex items-center justify-between text-xs text-slate-500">
                  <span>Pending Overtime</span>
                  <Badge :value="pendingCounts.overtime" severity="danger" />
                </div>
                <div class="flex items-center justify-between text-xs text-slate-500">
                  <span>Pending Swaps</span>
                  <Badge :value="pendingCounts.swaps" severity="info" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="showLeaveDialog" header="Leave Request" :modal="true" :style="{ width: '520px' }">
      <div class="space-y-4">
        <div>
          <label class="text-xs font-semibold text-slate-500">Leave Type</label>
          <Select v-model="leaveForm.leave_type" :options="leaveTypes" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Date Range</label>
          <DatePicker
            v-model="leaveForm.range"
            selectionMode="range"
            :minDate="today"
            :manualInput="false"
            class="w-full p-fluid"
          />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Reason</label>
          <Textarea v-model="leaveForm.reason" rows="3" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showLeaveDialog = false" />
        <Button label="Submit Leave Request" icon="pi pi-send" :disabled="!canSubmitLeave" @click="submitLeave" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showPasswordDialog" header="Change Password" :modal="true" :style="{ width: '480px' }">
      <div class="space-y-4">
        <div>
          <label class="text-xs font-semibold text-slate-500">Current Password</label>
          <Password v-model="passwordForm.current_password" toggleMask class="w-full" :feedback="false" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">New Password</label>
          <Password v-model="passwordForm.password" toggleMask class="w-full" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Confirm New Password</label>
          <Password v-model="passwordForm.password_confirmation" toggleMask class="w-full" :feedback="false" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="showPasswordDialog = false" />
        <Button label="Update Password" icon="pi pi-check" :disabled="!canChangePassword" @click="changePassword" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showEmailOtpDialog" header="Verify New Email" :modal="true" :style="{ width: '420px' }">
      <div class="space-y-4">
        <p class="text-sm text-slate-600">
          We sent a 6-digit OTP to your new email. Enter it to verify your email change.
        </p>
        <InputOtp v-model="emailOtp" :length="6" />
        <div class="flex items-center justify-between text-xs text-slate-500">
          <span>Didn’t get it?</span>
          <Button label="Resend OTP" text size="small" @click="resendEmailOtp" />
        </div>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" text @click="showEmailOtpDialog = false" />
        <Button label="Verify Email" icon="pi pi-check" :disabled="emailOtp.length !== 6" @click="verifyEmailOtp" />
      </template>
    </Dialog>

    <Dialog v-model:visible="showOvertimeDialog" header="Overtime Request" :style="{ width: '520px' }">
      <div class="space-y-3">
        <div>
          <label class="text-xs font-semibold text-slate-500">Attendance Record</label>
          <Select v-model="overtimeForm.attendance_id" :options="attendanceOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Overtime Type</label>
          <Select v-model="overtimeForm.ot_type" :options="otTypes" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Start</label>
          <DatePicker v-model="overtimeForm.ot_start" showTime hourFormat="12" :minDate="today" class="w-full" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">End</label>
          <DatePicker v-model="overtimeForm.ot_end" showTime hourFormat="12" :minDate="today" class="w-full" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Reason</label>
          <Textarea v-model="overtimeForm.reason" rows="3" class="w-full" />
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="showOvertimeDialog = false" />
          <Button label="Submit" icon="pi pi-send" :disabled="!canSubmitOvertime" @click="submitOvertime" />
        </div>
      </div>
    </Dialog>

    <Dialog v-model:visible="showSwapDialog" header="Shift Swap Request" :style="{ width: '540px' }">
      <div class="space-y-3">
        <div>
          <label class="text-xs font-semibold text-slate-500">My Shift</label>
          <Select v-model="swapForm.requestor_schedule_id" :options="scheduleOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Swap Type</label>
          <Select v-model="swapForm.swap_type" :options="swapTypes" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Receiver Shift</label>
          <Select v-model="swapForm.receiver_schedule_id" :options="receiverScheduleOptions" optionLabel="label" optionValue="value" class="w-full" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-500">Reason</label>
          <Textarea v-model="swapForm.reason" rows="3" class="w-full" />
        </div>
        <Message v-if="swapSuggestions.length" severity="info">
          We found other employees in the same role available on that date.
        </Message>
        <div v-if="swapSuggestions.length" class="space-y-2">
          <div v-for="suggestion in swapSuggestions" :key="suggestion.schedule.id" class="p-2 border border-slate-200 rounded-lg text-xs text-slate-600">
            {{ suggestion.employee?.fname }} {{ suggestion.employee?.lname }} - {{ suggestion.schedule.schedule_date }}
          </div>
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="showSwapDialog = false" />
          <Button label="Submit Swap" icon="pi pi-send" :disabled="!canSubmitSwap" @click="submitSwap" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import axios from '../../../axios'

const toast = useToast()
const today = new Date()

const activeTab = ref('profile')
const profileForm = reactive({
  fname: '',
  lname: '',
  email: '',
  phone_number: '',
  address: '',
  city: '',
  province: ''
})
const employee = ref<any>(null)

const attendanceRecords = ref<any[]>([])
const leaveRequests = ref<any[]>([])
const overtimeRequests = ref<any[]>([])
const shiftSwaps = ref<any[]>([])
const payslips = ref<any[]>([])

const loading = reactive({
  profile: false,
  attendance: false,
  leave: false,
  overtime: false,
  swaps: false,
  payslips: false
})

const unseen = reactive({
  attendance: 0,
  leave: 0,
  overtime: 0,
  swaps: 0
})

const pendingCounts = reactive({
  leave: 0,
  overtime: 0,
  swaps: 0
})

const showOvertimeDialog = ref(false)
const showLeaveDialog = ref(false)
const showPasswordDialog = ref(false)
const showEmailOtpDialog = ref(false)
const emailOtp = ref('')

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: ''
})
const showSwapDialog = ref(false)

const leaveTypes = [
  { label: 'Sick', value: 'sick' },
  { label: 'Vacation', value: 'vacation' },
  { label: 'Personal', value: 'personal' },
  { label: 'Maternity', value: 'maternity' },
  { label: 'Paternity', value: 'paternity' },
  { label: 'Bereavement', value: 'bereavement' },
  { label: 'Others', value: 'others' }
]

const otTypes = [
  { label: 'Regular', value: 'regular' },
  { label: 'Holiday', value: 'holiday' },
  { label: 'Rest Day', value: 'rest_day' }
]

const swapTypes = [
  { label: 'Full Swap', value: 'full_swap' },
  { label: 'Give Away', value: 'give_away' },
  { label: 'Pick Up', value: 'pick_up' }
]

const leaveForm = reactive({
  leave_type: 'vacation',
  range: [] as Date[],
  reason: ''
})

const overtimeForm = reactive({
  attendance_id: null as number | null,
  ot_type: 'regular',
  ot_start: null as Date | null,
  ot_end: null as Date | null,
  reason: ''
})

const swapForm = reactive({
  requestor_schedule_id: null as number | null,
  receiver_schedule_id: null as number | null,
  swap_type: 'full_swap',
  reason: ''
})

const attendanceOptions = computed(() =>
  attendanceRecords.value.map((item) => ({
    label: `${formatDate(item.attendance_date)} (${formatStatus(item.status)})`,
    value: item.id
  }))
)

const scheduleOptions = computed(() =>
  (employee.value?.schedules ?? []).map((sched: any) => ({
    label: `${formatDate(sched.schedule_date)} - ${formatTime(sched.shift?.start_time)} to ${formatTime(sched.shift?.end_time)}`,
    value: sched.id
  }))
)

const receiverScheduleOptions = computed(() =>
  swapSuggestions.value.map((suggestion: any) => ({
    label: `${suggestion.employee?.fname} ${suggestion.employee?.lname} - ${formatDate(suggestion.schedule?.schedule_date)} - ${formatTime(suggestion.schedule?.shift?.start_time)} to ${formatTime(suggestion.schedule?.shift?.end_time)}`,
    value: suggestion.schedule?.id
  }))
)
const swapSuggestions = ref<any[]>([])
const selectedReceiver = computed(() =>
  swapSuggestions.value.find((item: any) => item.schedule?.id === swapForm.receiver_schedule_id)
)

const leaveCalendarDates = ref<Date[]>([])

const canSaveProfile = computed(() => profileForm.fname && profileForm.lname && profileForm.email)
const savingProfile = ref(false)

const canSubmitLeave = computed(() => leaveForm.leave_type && leaveForm.range?.length === 2 && leaveForm.reason)
const canSubmitOvertime = computed(() => overtimeForm.attendance_id && overtimeForm.ot_start && overtimeForm.ot_end && overtimeForm.reason)
const canSubmitSwap = computed(() => swapForm.requestor_schedule_id && swapForm.receiver_schedule_id && swapForm.swap_type && selectedReceiver.value?.employee?.id)

const formatMinutes = (minutes: number) => {
  if (!minutes && minutes !== 0) return '0h 0m'
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return `${hours}h ${mins}m`
}

const formatDate = (value: string | Date | null | undefined) => {
  if (!value) return '—'
  const date = typeof value === 'string' ? new Date(value) : value
  if (Number.isNaN(date.getTime())) return '—'
  return date.toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' })
}

const formatDateTime = (value: string | Date | null | undefined) => {
  if (!value) return '—'
  const date = typeof value === 'string' ? new Date(value) : value
  if (Number.isNaN(date.getTime())) return '—'
  return date.toLocaleString(undefined, { month: 'short', day: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit' })
}

const formatTime = (value: string | null | undefined) => {
  if (!value) return '—'
  const [hourStr, minuteStr] = value.split(':')
  const hour = Number(hourStr)
  if (Number.isNaN(hour)) return value
  const date = new Date()
  date.setHours(hour, Number(minuteStr || 0), 0, 0)
  return date.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
}

const formatStatus = (value: string | null | undefined) => {
  if (!value) return 'Unknown'
  return value.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

const statusSeverity = (value: string | null | undefined) => {
  switch (value) {
    case 'approved':
    case 'active':
    case 'present':
      return 'success'
    case 'pending':
      return 'warning'
    case 'rejected':
    case 'cancelled':
    case 'absent':
      return 'danger'
    case 'late':
      return 'info'
    default:
      return 'secondary'
  }
}

const fetchProfile = async () => {
  loading.profile = true
  try {
    const response = await axios.get('/api/profile')
    const data = response.data?.data
    profileForm.fname = data?.user?.fname || ''
    profileForm.lname = data?.user?.lname || ''
    profileForm.email = data?.user?.email || ''
    profileForm.phone_number = data?.user?.phone_number || ''
    profileForm.address = data?.employee?.address || ''
    profileForm.city = data?.employee?.city || ''
    profileForm.province = data?.employee?.province || ''
    employee.value = data?.employee
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to load profile', life: 3000 })
  } finally {
    loading.profile = false
  }
}

const fetchEmployee = async () => {
  try {
    const response = await axios.get('/api/employees/me')
    employee.value = response.data?.data
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to load employee data', life: 3000 })
  }
}

const fetchAttendance = async () => {
  if (!employee.value?.id) return
  loading.attendance = true
  try {
    const response = await axios.get('/api/attendances', { params: { employee_id: employee.value.id } })
    attendanceRecords.value = response.data?.data?.data ?? response.data?.data ?? []
    unseen.attendance = attendanceRecords.value.length
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to load attendance', life: 3000 })
  } finally {
    loading.attendance = false
  }
}

const fetchLeaves = async () => {
  if (!employee.value?.id) return
  loading.leave = true
  try {
    const response = await axios.get(`/api/users/${employee.value.id}/leaves`)
    const payload = response.data?.data
    leaveRequests.value = payload?.leaves?.data ?? payload?.leaves ?? []
    const pending = leaveRequests.value.filter((item) => item.status === 'pending').length
    pendingCounts.leave = pending
    unseen.leave = pending
    leaveCalendarDates.value = leaveRequests.value
      .filter((leave) => leave.status !== 'rejected')
      .flatMap((leave) => {
        const start = new Date(leave.start_date)
        const end = new Date(leave.end_date)
        const dates: Date[] = []
        for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
          dates.push(new Date(d))
        }
        return dates
      })
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to load leaves', life: 3000 })
  } finally {
    loading.leave = false
  }
}

const fetchOvertime = async () => {
  if (!employee.value?.id) return
  loading.overtime = true
  try {
    const response = await axios.get('/api/overtime-requests', { params: { employee_id: employee.value.id } })
    overtimeRequests.value = response.data?.data?.data ?? response.data?.data ?? []
    const pending = overtimeRequests.value.filter((item) => item.status === 'pending').length
    pendingCounts.overtime = pending
    unseen.overtime = pending
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to load overtime requests', life: 3000 })
  } finally {
    loading.overtime = false
  }
}

const fetchShiftSwaps = async () => {
  if (!employee.value?.id) return
  loading.swaps = true
  try {
    const response = await axios.get('/api/shift-swaps', { params: { employee_id: employee.value.id } })
    shiftSwaps.value = response.data?.data?.data ?? response.data?.data ?? []
    const pending = shiftSwaps.value.filter((item) => item.status === 'pending').length
    pendingCounts.swaps = pending
    unseen.swaps = pending
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to load swap requests', life: 3000 })
  } finally {
    loading.swaps = false
  }
}

const fetchSchedules = async () => {
  if (!employee.value?.id) return
  try {
    const response = await axios.get('/api/shift-schedules', { params: { employee_id: employee.value.id } })
    employee.value = { ...employee.value, schedules: response.data?.data?.data ?? response.data?.data ?? [] }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to load schedules', life: 3000 })
  }
}

const fetchPayslips = async () => {
  if (!employee.value?.id) return
  loading.payslips = true
  try {
    const response = await axios.get(`/api/payroll/payslip/${employee.value.id}`)
    payslips.value = response.data?.data?.data ?? response.data?.data ?? []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to load payslips', life: 3000 })
  } finally {
    loading.payslips = false
  }
}

const saveProfile = async () => {
  if (!canSaveProfile.value) return
  savingProfile.value = true
  try {
    const response = await axios.put('/api/profile', profileForm)
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Profile updated', life: 3000 })
    if (response.data?.requires_verification) {
      showEmailOtpDialog.value = true
      emailOtp.value = ''
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to update profile', life: 3000 })
  } finally {
    savingProfile.value = false
  }
}

const submitLeave = async () => {
  if (!employee.value?.id || !canSubmitLeave.value) return
  const [start, end] = leaveForm.range
  try {
    await axios.post('/api/leaves', {
      employee_id: employee.value.id,
      leave_type: leaveForm.leave_type,
      start_date: start?.toISOString().slice(0, 10),
      end_date: end?.toISOString().slice(0, 10),
      reason: leaveForm.reason,
      is_paid: true
    })
    toast.add({ severity: 'success', summary: 'Submitted', detail: 'Leave request sent', life: 3000 })
    leaveForm.reason = ''
    leaveForm.range = []
    showLeaveDialog.value = false
    await fetchLeaves()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to submit leave', life: 3000 })
  }
}

const canChangePassword = computed(() => {
  return passwordForm.current_password
    && passwordForm.password
    && passwordForm.password_confirmation
    && passwordForm.password === passwordForm.password_confirmation
    && passwordForm.password.length >= 8
})

const changePassword = async () => {
  if (!canChangePassword.value) return
  try {
    await axios.post('/api/auth/change-password', {
      current_password: passwordForm.current_password,
      password: passwordForm.password,
      password_confirmation: passwordForm.password_confirmation
    })
    toast.add({ severity: 'success', summary: 'Updated', detail: 'Password changed successfully', life: 3000 })
    showPasswordDialog.value = false
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to change password', life: 3000 })
  }
}

const verifyEmailOtp = async () => {
  if (emailOtp.value.length !== 6) return
  try {
    await axios.post('/api/auth/verify-otp', { otp: emailOtp.value })
    toast.add({ severity: 'success', summary: 'Verified', detail: 'Email verified successfully', life: 3000 })
    showEmailOtpDialog.value = false
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Invalid OTP', life: 3000 })
  }
}

const resendEmailOtp = async () => {
  try {
    await axios.post('/api/auth/resend-otp')
    toast.add({ severity: 'success', summary: 'Sent', detail: 'OTP resent to your email', life: 3000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to resend OTP', life: 3000 })
  }
}

const submitOvertime = async () => {
  if (!employee.value?.id || !canSubmitOvertime.value) return
  try {
    await axios.post('/api/overtime-requests', {
      employee_id: employee.value.id,
      attendance_id: overtimeForm.attendance_id,
      ot_start: overtimeForm.ot_start?.toISOString(),
      ot_end: overtimeForm.ot_end?.toISOString(),
      ot_type: overtimeForm.ot_type,
      rate_multiplier: 1.25,
      reason: overtimeForm.reason
    })
    toast.add({ severity: 'success', summary: 'Submitted', detail: 'Overtime request sent', life: 3000 })
    showOvertimeDialog.value = false
    await fetchOvertime()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to submit overtime', life: 3000 })
  }
}

const submitSwap = async () => {
  if (!employee.value?.id || !canSubmitSwap.value) return
  swapSuggestions.value = []
  try {
    await axios.post('/api/shift-swaps', {
      receiver_id: selectedReceiver.value?.employee?.id,
      requestor_schedule_id: swapForm.requestor_schedule_id,
      receiver_schedule_id: swapForm.receiver_schedule_id,
      swap_type: swapForm.swap_type,
      reason: swapForm.reason
    })
    toast.add({ severity: 'success', summary: 'Submitted', detail: 'Swap request sent', life: 3000 })
    showSwapDialog.value = false
    await fetchShiftSwaps()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Failed', detail: error?.response?.data?.message || 'Unable to submit swap', life: 3000 })
    await fetchSwapSuggestions()
  }
}

const fetchSwapSuggestions = async () => {
  if (!swapForm.requestor_schedule_id) return
  try {
    const response = await axios.get('/api/shift-swaps/suggestions', {
      params: { requestor_schedule_id: swapForm.requestor_schedule_id }
    })
    swapSuggestions.value = response.data?.data ?? []
  } catch (error) {
    swapSuggestions.value = []
  }
}

const downloadPayslip = async (payslip: any) => {
  try {
    const response = await axios.get(`/api/payroll/${payslip.id}/payslip/pdf`, { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payslip_${payslip.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Failed', detail: 'Unable to download payslip', life: 3000 })
  }
}

const printPayslip = (payslip: any) => {
  window.open(`/api/payroll/${payslip.id}/payslip/print`, '_blank')
}

const refreshAll = async () => {
  await fetchProfile()
  await fetchEmployee()
  await fetchAttendance()
  await fetchLeaves()
  await fetchOvertime()
  await fetchShiftSwaps()
  await fetchPayslips()
  await fetchSchedules()
}

watch(activeTab, (value) => {
  if (value === 'attendance') unseen.attendance = 0
  if (value === 'leave') unseen.leave = 0
  if (value === 'overtime') unseen.overtime = 0
  if (value === 'swaps') unseen.swaps = 0
})

watch(() => swapForm.requestor_schedule_id, async () => {
  if (!swapForm.requestor_schedule_id) return
  swapForm.receiver_schedule_id = null
  await fetchSwapSuggestions()
})

onMounted(async () => {
  await refreshAll()
})
</script>
