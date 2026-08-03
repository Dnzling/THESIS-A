<template>
  <div class="min-h-full">
    <div class="mx-auto max-w-7xl px-6 py-8">
      <div class="flex flex-wrap items-center gap-3">
        <Button label="Back to Decision" icon="pi pi-arrow-left" severity="secondary" text @click="router.push({ name: 'hr.job-applications.decision', params: { applicationId: route.params.applicationId } })" />
      </div>

      <div class="mt-5 rounded-[2rem] border border-blue-100 bg-white/95 p-6 shadow-sm">
        <div class="space-y-2">
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Employee Onboarding</p>
          <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Create employee and build a weekly shift plan</h1>
          <p class="text-sm leading-6 text-slate-500">This guided flow keeps hiring and shift planning together, with a final preview before anything is committed.</p>
        </div>

        <div class="mt-6">
          <Steps :model="stepItems" :readonly="true" :activeStep="activeStep" />
        </div>

        <Message ref="errorBannerRef" v-if="errorMessage" severity="error" :closable="false" class="mt-6 border border-red-200">
          {{ errorMessage }}
        </Message>

        <div v-if="loading" class="mt-6 grid gap-4">
          <Skeleton v-for="item in 3" :key="item" width="100%" height="9rem" />
        </div>

        <div v-else class="mt-8">
          <section v-if="activeStep === 0">
            <Card class="border border-blue-100 bg-blue-50/40 shadow-none">
              <template #title>Employee Setup</template>
              <template #content>
                <div class="grid gap-4 md:grid-cols-2">
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">First Name</label>
                    <InputText v-model="employeeForm.first_name" class="w-full" disabled />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Last Name</label>
                    <InputText v-model="employeeForm.last_name" class="w-full" disabled />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Email</label>
                    <InputText v-model="employeeForm.email" class="w-full" disabled />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Phone</label>
                    <InputText v-model="employeeForm.phone" class="w-full" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Province (PSGC)</label>
                    <Select v-model="employeeForm.province_code" :options="provinceOptions" optionLabel="name" optionValue="psgc_id" placeholder="Select province" class="w-full" @change="onProvinceChange" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">City / Municipality</label>
                    <Select v-model="employeeForm.city_code" :options="cityOptions" optionLabel="name" optionValue="psgc_id" placeholder="Select city" class="w-full" :disabled="!employeeForm.province_code" @change="onCityChange" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Barangay</label>
                    <Select v-model="employeeForm.barangay_code" :options="barangayOptions" optionLabel="name" optionValue="psgc_id" placeholder="Select barangay" class="w-full" :disabled="!employeeForm.city_code" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Branch</label>
                    <Select v-model="employeeForm.branch_id" :options="branchOptions" optionLabel="name" optionValue="id" placeholder="Select branch" class="w-full" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Department</label>
                    <Select v-model="employeeForm.department_id" :options="departmentOptions" optionLabel="name" optionValue="id" placeholder="Select department" class="w-full" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Role</label>
                    <Select v-model="employeeForm.role_id" :options="roleOptions" optionLabel="display_name" optionValue="id" placeholder="Select role" class="w-full" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Employment Type</label>
                    <Select v-model="employeeForm.employment_type" :options="employmentTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Pay Type</label>
                    <Select v-model="employeeForm.pay_type" :options="payTypeOptions" optionLabel="label" optionValue="value" class="w-full" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Hire Date</label>
                    <DatePicker v-model="employeeForm.hire_date" class="w-full" fluid />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Salary</label>
                    <InputNumber
                      v-model="employeeForm.salary"
                      mode="currency"
                      :min="salaryRangeMin ?? 0"
                      :max="salaryRangeMax ?? undefined"
                      currency="PHP"
                      locale="en-PH"
                      class="w-full"
                      inputClass="w-full"
                    />
                    <p v-if="salaryRangeMin !== null || salaryRangeMax !== null" class="text-xs text-slate-500">
                      Allowed range: {{ formatCurrency(salaryRangeMin ?? 0) }} - {{ formatCurrency(salaryRangeMax ?? 0) }}
                    </p>
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <section v-else-if="activeStep === 1" class="space-y-6">
            <Card class="border border-blue-100 shadow-none">
              <template #title>
                <span>Shift Planner</span>
              </template>
              <template #content>
                <div class="mb-4 grid gap-4 lg:grid-cols-2">
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Schedule Week Start</label>
                    <DatePicker v-model="shiftForm.week_start" class="w-full" />
                  </div>
                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    Fill each day manually. Use 12-hour time like `08:00 AM`.
                  </div>
                </div>

                <div class="overflow-x-auto">
                  <table class="min-w-full border-separate border-spacing-0">
                    <thead>
                      <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="border-b border-slate-200 px-3 py-2">Day</th>
                        <th class="border-b border-slate-200 px-3 py-2">Working</th>
                        <th class="border-b border-slate-200 px-3 py-2">Start</th>
                        <th class="border-b border-slate-200 px-3 py-2">End</th>
                        <th class="border-b border-slate-200 px-3 py-2">Hours</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in weeklyPlanner" :key="row.day" class="align-top">
                        <td class="border-b border-slate-100 px-3 py-3 font-medium text-slate-800">{{ formatDayLabel(row.day) }}</td>
                        <td class="border-b border-slate-100 px-3 py-3">
                          <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input
                              :checked="row.is_working"
                              type="checkbox"
                              class="h-4 w-4"
                              @change="(event) => onPlannerWorkingToggle(row, (event.target as HTMLInputElement).checked)"
                            />
                            Working
                          </label>
                        </td>
                        <td class="border-b border-slate-100 px-3 py-3">
                          <InputText
                            :model-value="row.start_time"
                            placeholder="08:00 AM"
                            class="w-full"
                            :disabled="!row.is_working"
                            @update:model-value="(value) => onPlannerStartTimeChange(row, String(value || ''))"
                          />
                        </td>
                        <td class="border-b border-slate-100 px-3 py-3">
                          <InputText
                            :model-value="row.end_time"
                            placeholder="05:00 PM"
                            class="w-full"
                            :disabled="!row.is_working"
                            @update:model-value="(value) => onPlannerEndTimeChange(row, String(value || ''))"
                          />
                        </td>
                        <td class="border-b border-slate-100 px-3 py-3 text-sm text-slate-700">
                          {{ row.is_working ? (row.hours || '0') + ' hrs' : 'Off' }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Total Weekly Hours</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ totalWeeklyHours.toFixed(2) }} hrs</p>
                  </div>
                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Max Daily</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">12 hrs</p>
                  </div>
                  <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Max Weekly</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">48 hrs</p>
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <section v-else class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <Card class="border border-blue-100 shadow-none">
              <template #title>Preview and Confirmation</template>
              <template #content>
                <div class="space-y-5 text-sm">
                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Employee Setup</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-2">
                      <div class="rounded-2xl bg-slate-50 p-4"><strong>Name:</strong> {{ employeeForm.first_name }} {{ employeeForm.last_name }}</div>
                      <div class="rounded-2xl bg-slate-50 p-4"><strong>Salary:</strong> {{ formatCurrency(employeeForm.salary) }}</div>
                      <div class="rounded-2xl bg-slate-50 p-4"><strong>Hire Date:</strong> {{ formatDateOnly(employeeForm.hire_date) }}</div>
                    </div>
                  </div>

                  <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Shift Plan</p>
                    <div class="mt-3 space-y-3">
                      <div class="rounded-2xl bg-blue-50/70 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                          <p class="font-semibold text-slate-900">Weekly table schedule</p>
                          <span class="text-xs text-slate-500">Week of {{ formatDateOnly(shiftForm.week_start) }}</span>
                        </div>
                        <div class="space-y-2">
                          <div v-for="day in weeklyPlanner" :key="`preview-${day.day}`" class="flex items-center justify-between text-sm text-slate-700">
                            <span>{{ formatDayLabel(day.day) }}</span>
                            <span>{{ day.is_working ? `${day.start_time || '--:--'} - ${day.end_time || '--:--'}` : 'Off' }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                    Payroll card will be assigned later from the employee profile.
                  </div>
                </div>
              </template>
            </Card>

            <Card class="border border-blue-100 bg-blue-50/70 shadow-none">
              <template #title>Final Confirmation</template>
              <template #content>
                <div class="space-y-4 text-sm">
                  <p class="leading-6 text-slate-600">
                    We will create the employee account and attach the selected shift template for the week.
                  </p>
                  <Message severity="info" :closable="false">
                    Review everything carefully. This final action commits the hire and onboarding setup.
                  </Message>
                  <Button label="Create Employee and Assign Template" icon="pi pi-check-circle" severity="info" fluid :loading="submitting" :disabled="submitting || hasBlockingError" @click="submitOnboarding" />
                </div>
              </template>
            </Card>
          </section>
        </div>

        <div v-if="!loading" class="mt-8 flex items-center justify-between gap-3">
          <Button label="Previous" severity="secondary" outlined :disabled="activeStep === 0 || submitting" @click="activeStep -= 1" />
          <Button v-if="activeStep < 2" :label="activeStep === 1 ? 'Preview' : 'Next'" severity="info" @click="goNextStep" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import hrService from '@/services/hr.services'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'

type ShiftTemplatePatternEntry = {
  day: string
  is_working: boolean
  shift_id?: number
  start_time?: string
  end_time?: string
}

type ShiftTemplateOption = {
  id: number | string
  name: string
  source: 'schedule' | 'shift'
  pattern: Record<string, ShiftTemplatePatternEntry> | ShiftTemplatePatternEntry[]
  valid_from?: string | null
  valid_to?: string | null
}

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const submitting = ref(false)
const activeStep = ref(0)
const errorMessage = ref('')
const errorBannerRef = ref<HTMLElement | null>(null)
const application = ref<any | null>(null)
const createdEmployeeId = ref<number | null>(null)
const shiftTemplates = ref<ShiftTemplateOption[]>([])
const templatesLoading = ref(false)
const selectedTemplateId = ref<number | string | null>(null)

const stepItems = [
  { label: 'Create Employee' },
  { label: 'Shift Planner' },
  { label: 'Preview' },
]

const branchOptions = ref<any[]>([])
const departmentOptions = ref<any[]>([])
const roleOptions = ref<any[]>([])
const provinceOptions = ref<any[]>([])
const cityOptions = ref<any[]>([])
const barangayOptions = ref<any[]>([])

const employmentTypeOptions = [
  { label: 'Full Time', value: 'full_time' },
  { label: 'Part Time', value: 'part_time' },
  { label: 'Contract', value: 'contract' },
  { label: 'Intern', value: 'intern' },
]

const cardStatusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Pending', value: 'pending' },
  { label: 'Inactive', value: 'inactive' },
]

const payTypeOptions = [
  { label: 'Monthly (Salary)', value: 'monthly' },
  { label: 'Hourly', value: 'hourly' },
  { label: 'Hybrid', value: 'hybrid' },
]

const employeeForm = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  province_code: '',
  city_code: '',
  barangay_code: '',
  branch_id: null as number | null,
  department_id: null as number | null,
  role_id: null as number | null,
  hire_date: new Date(),
  employment_type: 'full_time',
  pay_type: 'monthly',
  salary: 0,
  position: '',
})

const shiftForm = reactive({
  week_start: new Date(),
})

const weeklyPlanner = reactive([
  { day: 'monday', is_working: false, shift_id: null as number | null, start_time: '08:00 AM', end_time: '05:00 PM', hours: 0 },
  { day: 'tuesday', is_working: false, shift_id: null as number | null, start_time: '08:00 AM', end_time: '05:00 PM', hours: 0 },
  { day: 'wednesday', is_working: false, shift_id: null as number | null, start_time: '08:00 AM', end_time: '05:00 PM', hours: 0 },
  { day: 'thursday', is_working: false, shift_id: null as number | null, start_time: '08:00 AM', end_time: '05:00 PM', hours: 0 },
  { day: 'friday', is_working: false, shift_id: null as number | null, start_time: '08:00 AM', end_time: '05:00 PM', hours: 0 },
  { day: 'saturday', is_working: false, shift_id: null as number | null, start_time: '08:00 AM', end_time: '05:00 PM', hours: 0 },
  { day: 'sunday', is_working: false, shift_id: null as number | null, start_time: '08:00 AM', end_time: '05:00 PM', hours: 0 },
])

const cardForm = reactive({
  card_type: 'payroll',
  status: 'active',
  card_number: '',
})

const applicantName = computed(() => application.value?.full_name || `${application.value?.first_name || ''} ${application.value?.last_name || ''}`.trim())
const totalWeeklyHours = computed(() => weeklyPlanner.reduce((sum, row) => sum + Number(row.hours || 0), 0))

const salaryRangeMin = computed<number | null>(() => {
  const raw = application.value?.jobPosting?.salary_min
  const parsed = Number(raw)
  return Number.isFinite(parsed) ? parsed : null
})

const salaryRangeMax = computed<number | null>(() => {
  const raw = application.value?.jobPosting?.salary_max
  const parsed = Number(raw)
  return Number.isFinite(parsed) ? parsed : null
})

const selectedTemplate = computed(() => shiftTemplates.value.find((template) => template.id === selectedTemplateId.value) || null)

const weekDayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']

const sortPatternByWeekDay = (entries: ShiftTemplatePatternEntry[]) => {
  return [...entries].sort((a, b) => {
    const aIndex = weekDayOrder.indexOf((a.day || '').toLowerCase())
    const bIndex = weekDayOrder.indexOf((b.day || '').toLowerCase())
    return (aIndex === -1 ? 999 : aIndex) - (bIndex === -1 ? 999 : bIndex)
  })
}

const normalizePattern = (pattern: any): ShiftTemplatePatternEntry[] => {
  if (!pattern) return []
  if (Array.isArray(pattern)) {
    return sortPatternByWeekDay(pattern.map((entry) => ({
      day: entry.day,
      is_working: entry.is_working,
      shift_id: entry.shift_id,
      start_time: entry.start_time,
      end_time: entry.end_time,
    })))
  }
  return sortPatternByWeekDay(Object.entries(pattern).map(([day, value]) => {
    const parsed = (value || {}) as Record<string, any>
    return {
      day,
      is_working: parsed['is_working'] ?? false,
      shift_id: parsed['shift_id'],
      start_time: parsed['start_time'],
      end_time: parsed['end_time'],
    }
  }))
}

const templatePatternEntries = (template: ShiftTemplateOption | null) => normalizePattern(template?.pattern)
const templateWorkingDaysFor = (template: any) => templatePatternEntries(template).filter((entry) => entry.is_working)
const templateOptions = computed(() =>
  shiftTemplates.value.map((template) => ({
    label: `${template.name} - ${formatTemplateSummary(template)}`,
    value: template.id,
  }))
)

const currentTemplateDays = computed(() => templateWorkingDaysFor(selectedTemplate.value))

const formatTemplateSummary = (template: any) => {
  const days = templateWorkingDaysFor(template)
  if (!days.length) return 'No working days defined'
  const dayLabels = days.map((entry) => formatDayLabel(entry.day))
  return dayLabels.slice(0, 3).join(', ') + (dayLabels.length > 3 ? ` +${dayLabels.length - 3} more` : '')
}

const dayLabelMap: Record<string, string> = {
  monday: 'Monday',
  tuesday: 'Tuesday',
  wednesday: 'Wednesday',
  thursday: 'Thursday',
  friday: 'Friday',
  saturday: 'Saturday',
  sunday: 'Sunday',
}

const formatDayLabel = (value?: string) => {
  if (!value) return 'Day'
  return dayLabelMap[value.toLowerCase()] || value.charAt(0).toUpperCase() + value.slice(1)
}

const formatTimeLabel = (value?: string) => formatTime12h(value)

const formatDateOnly = (value: Date | string | null) => {
  if (!value) return 'N/A'
  const date = typeof value === 'string' ? new Date(value) : value
  return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatCurrency = (value?: number | string) =>
  new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0 }).format(Number(value || 0))

const formatTime12h = (value?: string) => {
  if (!value) return 'Not set'
  const parsed = parseTimeToMinutes(value)
  if (parsed === null) return value
  const hours24 = Math.floor(parsed / 60)
  const minutes = parsed % 60
  const period = hours24 >= 12 ? 'PM' : 'AM'
  const hours12 = ((hours24 + 11) % 12) + 1
  return `${String(hours12).padStart(2, '0')}:${String(minutes).padStart(2, '0')} ${period}`
}

const parseTimeToMinutes = (value?: string) => {
  if (!value) return null
  const raw = String(value).trim().toUpperCase()
  const match = raw.match(/^(\d{1,2}):(\d{2})\s*([AP]M)?$/)
  if (!match) return null
  let hours = Number(match[1])
  const minutes = Number(match[2])
  const meridiem = match[3]
  if (Number.isNaN(hours) || Number.isNaN(minutes) || minutes > 59) return null

  if (meridiem) {
    if (hours < 1 || hours > 12) return null
    if (meridiem === 'AM') hours = hours === 12 ? 0 : hours
    if (meridiem === 'PM') hours = hours === 12 ? 12 : hours + 12
  } else if (hours > 23) {
    return null
  }

  return hours * 60 + minutes
}

const calculateHours = (start?: string, end?: string) => {
  const startMinutes = parseTimeToMinutes(start)
  const endMinutes = parseTimeToMinutes(end)
  if (startMinutes === null || endMinutes === null) return 0
  const diff = endMinutes - startMinutes
  return diff > 0 ? diff / 60 : 0
}

const detectMatchingShift = (start?: string, end?: string) => {
  const normalizedStart = String(start || '').trim()
  const normalizedEnd = String(end || '').trim()
  if (!normalizedStart || !normalizedEnd) return null

  return shiftOptions.value.find((shift: any) => {
    const shiftStart = formatTime12h(shift.start_time)
    const shiftEnd = formatTime12h(shift.end_time)
    return shiftStart === normalizedStart && shiftEnd === normalizedEnd
  }) || null
}

const formatMinutesToTime12h = (minutesTotal: number) => {
  const normalized = ((minutesTotal % 1440) + 1440) % 1440
  const hours24 = Math.floor(normalized / 60)
  const minutes = normalized % 60
  const period = hours24 >= 12 ? 'PM' : 'AM'
  const hours12 = ((hours24 + 11) % 12) + 1
  return `${String(hours12).padStart(2, '0')}:${String(minutes).padStart(2, '0')} ${period}`
}

const onPlannerStartTimeChange = (row: any, value: string) => {
  row.start_time = value

  const startMinutes = parseTimeToMinutes(value)
  if (startMinutes === null) {
    row.end_time = ''
    row.hours = 0
    return
  }

  const endMinutes = startMinutes + 9 * 60
  row.end_time = formatMinutesToTime12h(endMinutes)
  row.hours = 9
  row.is_working = true
  row.shift_id = detectMatchingShift(row.start_time, row.end_time)?.value || row.shift_id
}

const onPlannerEndTimeChange = (row: any, value: string) => {
  row.end_time = value
  row.hours = row.is_working ? calculateHours(row.start_time, row.end_time) : 0
  row.shift_id = detectMatchingShift(row.start_time, row.end_time)?.value || row.shift_id
}

const onPlannerWorkingToggle = (row: any, checked: boolean) => {
  row.is_working = checked

  if (checked) {
    const defaultStart = row.start_time || '09:00 AM'
    row.start_time = defaultStart
    const startMinutes = parseTimeToMinutes(defaultStart)
    row.end_time = startMinutes === null ? '06:00 PM' : formatMinutesToTime12h(startMinutes + 9 * 60)
    row.hours = calculateHours(row.start_time, row.end_time) || 9
    row.shift_id = detectMatchingShift(row.start_time, row.end_time)?.value || row.shift_id
    return
  }

  row.shift_id = null
  row.hours = 0
}

const toIsoDate = (value: Date | string) => {
  if (!value) return ''
  const date = value instanceof Date ? value : new Date(value)
  if (Number.isNaN(date.getTime())) return ''
  return date.toISOString().slice(0, 10)
}

const addDays = (value: Date | string, days: number) => {
  const date = value instanceof Date ? new Date(value) : new Date(value)
  date.setDate(date.getDate() + days)
  return date
}

const mapShiftToPattern = (shift: any) => {
  const days: Record<string, any> = {}
  const weekDays = Array.isArray(shift.week_days) ? shift.week_days : (typeof shift.week_days === 'string' && shift.week_days ? JSON.parse(shift.week_days) : [])
  weekDays.forEach((day: string) => {
    const key = day.toLowerCase()
    days[key] = {
      day: key,
      is_working: true,
      shift_id: shift.id,
      start_time: shift.start_time,
      end_time: shift.end_time,
    }
  })
  return days
}

const extractTemplatesFromPayload = (payload: any): any[] => {
  if (!payload) return []
  if (Array.isArray(payload)) return payload

  const queue = [payload]
  while (queue.length > 0) {
    const current = queue.shift()
    if (!current || typeof current !== 'object') continue

    if (Array.isArray(current)) return current
    if (Array.isArray(current.data)) return current.data
    if (Array.isArray(current.items)) return current.items
    if (Array.isArray(current.results)) return current.results

    for (const key of ['data', 'payload', 'result']) {
      if (current[key]) queue.push(current[key])
    }
  }

  return []
}

const normalizeTemplate = (template: any): ShiftTemplateOption | null => {
  const id = template?.id
  const name = template?.name
  if (!id || !name) return null
  return {
    id,
    name,
    source: 'schedule',
    pattern: template?.pattern || {},
    valid_from: template?.valid_from || null,
    valid_to: template?.valid_to || null,
  }
}

const loadShiftTemplates = async () => {
  templatesLoading.value = true
  try {
    const scheduleTemplatesPayload = await hrService.getScheduleTemplates({ is_active: 1, per_page: 100 })
    let resolvedTemplates = extractTemplatesFromPayload(scheduleTemplatesPayload)
      .map(normalizeTemplate)
      .filter(Boolean) as ShiftTemplateOption[]

    if (!resolvedTemplates.length) {
      const shiftTemplatesPayload = await hrService.getShiftManagementTemplates({ is_active: 1, per_page: 100 })
      const fallbackItems = extractTemplatesFromPayload(shiftTemplatesPayload)
      resolvedTemplates = fallbackItems.map((shift: any) => ({
        id: `shift-${shift.id}`,
        name: shift.name,
        source: 'shift',
        pattern: mapShiftToPattern(shift),
        valid_from: shift.created_at || null,
        valid_to: null,
      }))
    }

    shiftTemplates.value = resolvedTemplates
    if (!selectedTemplateId.value && resolvedTemplates.length) {
      selectedTemplateId.value = resolvedTemplates[0].id
    }
  } catch (error: any) {
    const message = error?.response?.data?.message || 'Unable to load shift templates.'
    await setError(message)
  } finally {
    templatesLoading.value = false
  }
}

const hasBlockingError = computed(() => Boolean(errorMessage.value))

const selectedScheduleTemplateId = computed<number | null>(() => {
  if (!selectedTemplate.value || selectedTemplate.value.source !== 'schedule') {
    return null
  }

  const parsed = Number(selectedTemplate.value.id)
  return Number.isFinite(parsed) ? parsed : null
})

const resolveErrorBannerElement = () => {
  const target = errorBannerRef.value as any
  if (!target) return null

  if (typeof target.scrollIntoView === 'function') {
    return target as HTMLElement
  }

  const rootEl = target?.$el
  if (rootEl && typeof rootEl.scrollIntoView === 'function') {
    return rootEl as HTMLElement
  }

  return null
}

const scrollToAlert = async () => {
  await nextTick()
  const messageElement = resolveErrorBannerElement()
  if (messageElement) {
    messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' })
    return
  }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const setError = async (message: string) => {
  errorMessage.value = message
  await scrollToAlert()
}

const validateSalaryRange = async (): Promise<boolean> => {
  const salary = Number(employeeForm.salary)
  const min = salaryRangeMin.value
  const max = salaryRangeMax.value

  if (!Number.isFinite(salary) || salary <= 0) {
    await setError('Please provide a valid salary amount.')
    return false
  }

  if (min !== null && salary < min) {
    await setError(`Salary must be at least ${formatCurrency(min)} based on the job posting range.`)
    return false
  }

  if (max !== null && salary > max) {
    await setError(`Salary must not exceed ${formatCurrency(max)} based on the job posting range.`)
    return false
  }

  return true
}

const validateWeeklyPlanner = async (): Promise<boolean> => {
  for (const row of weeklyPlanner) {
    row.hours = row.is_working ? calculateHours(row.start_time, row.end_time) : 0

    if (row.is_working && row.hours <= 0) {
      await setError(`Provide a valid start and end time for ${formatDayLabel(row.day)}.`)
      return false
    }

    if (row.hours > 9) {
      await setError(`Working hours for ${formatDayLabel(row.day)} cannot exceed 9 hours.`)
      return false
    }
  }

  if (totalWeeklyHours.value > 54) {
    await setError('Total weekly working hours cannot exceed 54 hours.')
    return false
  }

  return true
}

const loadPage = async () => {
  loading.value = true
  try {
    const [
      applicationResponse,
      branches,
      departments,
      roles,
      provinces,
    ] = await Promise.all([
      hrService.getJobApplication(route.params.applicationId as string),
      hrService.getBranches(),
      hrService.getDepartments(),
      hrService.getRoles(),
      hrService.api.get('/api/address/provinces'),
    ])

    application.value = applicationResponse?.data || applicationResponse

    if (!application.value?.jobPosting && application.value?.job_posting_id) {
      const jobPostingResponse = await hrService.getJobPosting(application.value.job_posting_id)
      const jobPosting = jobPostingResponse?.data || jobPostingResponse
      if (jobPosting) {
        application.value.jobPosting = jobPosting
      }
    }

    branchOptions.value = branches.data || branches || []
    departmentOptions.value = departments.data?.data || departments.data || departments || []
    roleOptions.value = roles.data || roles || []
    const provinceItems = provinces.data || []
    provinceOptions.value = provinceItems.map((item: any) => ({
      psgc_id: item.province_id,
      name: item.name,
    }))

    const normalize = (value: any) => String(value || '').trim().toLowerCase()
    const applicationProvince = normalize(application.value?.province)
    const applicationCity = normalize(application.value?.city)
    const applicationBarangay = normalize(application.value?.barangay)

    if (applicationProvince) {
      const matchedProvince = provinceOptions.value.find((p: any) => normalize(p.name) === applicationProvince)
      if (matchedProvince) {
        employeeForm.province_code = matchedProvince.psgc_id
      }
    }

    if (!employeeForm.province_code) {
      const cavite = provinceOptions.value.find((p: any) => normalize(p.name) === 'cavite')
      if (cavite) {
        employeeForm.province_code = cavite.psgc_id
      }
    }

    if (employeeForm.province_code) {
      await onProvinceChange()
    }

    employeeForm.first_name = application.value?.first_name || ''
    employeeForm.last_name = application.value?.last_name || ''
    employeeForm.email = application.value?.email || ''
    employeeForm.phone = application.value?.phone || ''
    employeeForm.position = application.value?.jobPosting?.title || ''
    employeeForm.salary = Number(application.value?.jobPosting?.salary_min || 0)

    if (applicationCity && cityOptions.value.length) {
      const matchedCity = cityOptions.value.find((city: any) => normalize(city.name) === applicationCity)
      if (matchedCity) {
        employeeForm.city_code = matchedCity.psgc_id
        await onCityChange()
      }
    }

    if (applicationBarangay && barangayOptions.value.length) {
      const matchedBarangay = barangayOptions.value.find((barangay: any) => normalize(barangay.name) === applicationBarangay)
      if (matchedBarangay) {
        employeeForm.barangay_code = matchedBarangay.psgc_id
      }
    }

    if (application.value?.jobPosting?.role?.id) {
      employeeForm.role_id = Number(application.value.jobPosting.role.id)
    }

    if (application.value?.jobPosting?.role_id) {
      employeeForm.role_id = Number(application.value.jobPosting.role_id)
    }

    if (application.value?.jobPosting?.department) {
      const matchedDepartment = departmentOptions.value.find((dept: any) =>
        String(dept?.name || '').trim().toLowerCase() === String(application.value.jobPosting.department).trim().toLowerCase())
      if (matchedDepartment?.id) {
        employeeForm.department_id = Number(matchedDepartment.id)
      }
    }

    if (branchOptions.value.length === 1 && !employeeForm.branch_id) {
      employeeForm.branch_id = Number(branchOptions.value[0].id)
    }

  } catch (error: any) {
    const message = error?.response?.data?.message || 'Unable to load onboarding context.'
    await setError(message)
  } finally {
    loading.value = false
  }
}

const onProvinceChange = async () => {
  employeeForm.city_code = ''
  employeeForm.barangay_code = ''
  cityOptions.value = []
  barangayOptions.value = []
  if (!employeeForm.province_code) return
  try {
    const response = await hrService.api.get(`/api/address/cities/${employeeForm.province_code}`)
    const cityItems = response.data || []
    cityOptions.value = cityItems.map((item: any) => ({
      psgc_id: item.city_id,
      name: item.name,
    }))
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Load failed', detail: 'Unable to load cities for selected province.', life: 2500 })
  }
}

const onCityChange = async () => {
  employeeForm.barangay_code = ''
  barangayOptions.value = []
  if (!employeeForm.city_code) return
  try {
    const response = await hrService.api.get(`/api/address/barangays/${employeeForm.city_code}`)
    const barangayItems = response.data || []
    barangayOptions.value = (barangayItems || []).map((item: any) => ({ psgc_id: item.code, name: item.name }))
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Load failed', detail: 'Unable to load barangays for selected city.', life: 2500 })
  }
}

const goNextStep = async () => {
  errorMessage.value = ''

  if (activeStep.value === 0) {
    if (!employeeForm.branch_id || !employeeForm.department_id || !employeeForm.role_id || !employeeForm.position || !employeeForm.salary) {
      void setError('Please complete the employee details before continuing.')
      return
    }

    const isValid = await validateSalaryRange()
    if (!isValid) return
    activeStep.value += 1
    return
  }

  if (activeStep.value === 1) {
    const weeklyValid = await validateWeeklyPlanner()
    if (!weeklyValid) {
      return
    }
  }

  activeStep.value += 1
}

const submitOnboarding = async () => {
  errorMessage.value = ''
  submitting.value = true

  try {
    const salaryValid = await validateSalaryRange()
    if (!salaryValid) {
      submitting.value = false
      return
    }

    const hireResponse = await hrService.hireApplicant(route.params.applicationId as string, {
      branch_id: employeeForm.branch_id as number,
      department_id: employeeForm.department_id as number,
      role_id: employeeForm.role_id as number,
      hire_date: toIsoDate(employeeForm.hire_date),
      employment_type: employeeForm.employment_type as any,
      pay_type: employeeForm.pay_type as any,
      salary: Number(employeeForm.salary),
      position: employeeForm.position,
      phone: employeeForm.phone || undefined,
    })

    const employee = hireResponse?.data?.employee
    createdEmployeeId.value = employee?.id || null

    if (!employee?.id) {
      throw new Error('Employee record was not created.')
    }

    const startDateStr = toIsoDate(shiftForm.week_start)
    if (!startDateStr) {
      await setError('Provide a valid week start date.')
      submitting.value = false
      return
    }

    const dayOffsets: Record<string, number> = {
      monday: 0,
      tuesday: 1,
      wednesday: 2,
      thursday: 3,
      friday: 4,
      saturday: 5,
      sunday: 6,
    }

    const startDate = new Date(shiftForm.week_start)
    for (const row of weeklyPlanner) {
      if (!row.is_working) continue
      const matchedShift = detectMatchingShift(row.start_time, row.end_time)
      const resolvedShiftId = Number(row.shift_id || matchedShift?.value || 0)
      if (!resolvedShiftId) {
        throw new Error(`No matching shift found for ${formatDayLabel(row.day)}. Please adjust the time range.`)
      }
      const scheduleDate = new Date(startDate)
      scheduleDate.setDate(startDate.getDate() + dayOffsets[row.day])
      await hrService.api.post('/api/shift-schedules', {
        employee_id: employee.id,
        shift_id: resolvedShiftId,
        schedule_date: toIsoDate(scheduleDate),
        status: 'scheduled',
        notes: `Onboarding schedule: ${formatDayLabel(row.day)} ${formatTime12h(row.start_time)} - ${formatTime12h(row.end_time)}`,
      })
    }

    toast.add({
      severity: 'success',
      summary: 'Onboarding completed',
      detail: 'Employee profile and weekly schedule were created successfully.',
      life: 3000,
    })
    router.push({ name: 'hr.employees.view', params: { id: createdEmployeeId.value } })
  } catch (error: any) {
    const firstError = error?.response?.data?.errors
      ? Object.values(error.response.data.errors)[0]
      : null
    const message =
      Array.isArray(firstError)
        ? firstError[0]
        : error?.response?.data?.message || error?.message || 'Unable to complete onboarding.'
    await setError(message)
  } finally {
    submitting.value = false
  }
}

watch([employeeForm, shiftForm, selectedTemplateId], () => {
  if (errorMessage.value && !submitting.value) {
    errorMessage.value = ''
  }
}, { deep: true })

onMounted(async () => {
  await loadPage()
  await loadShiftTemplates()
})
</script>
