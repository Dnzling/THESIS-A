<template>
  <div class="min-h-full">
    <div class="mx-auto max-w-7xl px-6 py-8">
      <div class="flex flex-wrap items-center gap-3">
        <Button label="Back to Decision" icon="pi pi-arrow-left" severity="secondary" text @click="router.push({ name: 'hr.job-applications.decision', params: { applicationId: route.params.applicationId } })" />
      </div>

      <div class="mt-5 rounded-[2rem] border border-blue-100 bg-white/95 p-6 shadow-sm">
        <div class="space-y-2">
          <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Employee Onboarding</p>
          <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Create employee and assign the first shift plan</h1>
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
                    <label class="text-sm font-medium text-slate-700">Hire Date</label>
                    <DatePicker v-model="employeeForm.hire_date" class="w-full" fluid />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Salary</label>
                    <InputNumber v-model="employeeForm.salary" mode="currency" currency="PHP" locale="en-PH" class="w-full" inputClass="w-full" />
                  </div>
                </div>
              </template>
            </Card>
          </section>

          <section v-else-if="activeStep === 1" class="space-y-6">
            <Card class="border border-blue-100 shadow-none">
              <template #title>
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <span>Shift Planner</span>
                  <Button label="Copy Monday to Enabled Days" icon="pi pi-copy" severity="secondary" outlined @click="copyMondayToAll" />
                </div>
              </template>
              <template #content>
                <div class="mb-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Schedule Week Start</label>
                    <DatePicker v-model="shiftForm.week_start" class="w-full" />
                  </div>
                  <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Shift Label</label>
                    <InputText v-model="shiftForm.name" class="w-full" placeholder="e.g. New Hire Morning Shift" />
                  </div>
                </div>

                <div class="overflow-x-auto">
                  <table class="min-w-full border-separate border-spacing-y-3">
                    <thead>
                      <tr class="text-left text-xs font-semibold uppercase tracking-wide text-blue-700">
                        <th class="px-3">Day</th>
                        <th class="px-3">Enabled</th>
                        <th class="px-3">Start</th>
                        <th class="px-3">End</th>
                        <th class="px-3">Break (hrs)</th>
                        <th class="px-3">Preview</th>
                        <th class="px-3">Copy</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(day, index) in shiftDays" :key="day.key" class="rounded-2xl bg-blue-50/50">
                        <td class="px-3 py-3 font-semibold text-slate-900">{{ day.label }}</td>
                        <td class="px-3 py-3">
                          <Checkbox v-model="day.enabled" binary />
                        </td>
                        <td class="px-3 py-3">
                          <InputText v-model="day.startRaw" class="w-32" placeholder="8 AM" @blur="normalizeDayTime(day, 'startRaw')" />
                        </td>
                        <td class="px-3 py-3">
                          <InputText v-model="day.endRaw" class="w-32" placeholder="5 PM" @blur="normalizeDayTime(day, 'endRaw')" />
                        </td>
                        <td class="px-3 py-3">
                          <InputNumber v-model="day.breakHours" :min="0" :max="4" :step="0.5" inputClass="w-24" />
                        </td>
                        <td class="px-3 py-3 text-sm text-slate-600">
                          <div>{{ dayPreview(day) }}</div>
                          <small :class="workingHours(day) > 8 ? 'text-red-500' : 'text-slate-500'">
                            {{ workingHours(day).toFixed(1) }} hrs working
                          </small>
                        </td>
                        <td class="px-3 py-3">
                          <Button icon="pi pi-angle-double-down" severity="secondary" text rounded :disabled="index === shiftDays.length - 1" @click="copyToNextDay(index)" />
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <Message v-if="shiftWarning" severity="warn" :closable="false" class="mt-4">
                  {{ shiftWarning }}
                </Message>
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
                      <div v-for="day in enabledShiftDays" :key="day.key" class="rounded-2xl bg-blue-50/70 p-4">
                        <div class="flex items-center justify-between gap-3">
                          <p class="font-semibold text-slate-900">{{ day.label }}</p>
                          <span class="text-xs text-slate-500">{{ dayPreview(day) }}</span>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ workingHours(day).toFixed(1) }} working hours - {{ breakLabel(day.breakHours) }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </Card>

            <Card class="border border-blue-100 bg-blue-50/70 shadow-none">
              <template #title>Final Confirmation</template>
              <template #content>
                <div class="space-y-4 text-sm">
                  <p class="leading-6 text-slate-600">
                    We will create the employee account, apply existing company deductions, create the shift record, and publish the selected weekly schedule for this hire.
                  </p>
                  <Message severity="info" :closable="false">
                    Review everything carefully. This final action commits the hire and onboarding setup.
                  </Message>
                  <Button label="Create Employee and Schedule" icon="pi pi-check-circle" severity="info" fluid :loading="submitting" :disabled="submitting || hasBlockingError" @click="submitOnboarding" />
                </div>
              </template>
            </Card>
          </section>
        </div>

        <div v-if="!loading" class="mt-8 flex items-center justify-between gap-3">
          <Button label="Previous" severity="secondary" outlined :disabled="activeStep === 0 || submitting" @click="activeStep -= 1" />
          <Button v-if="activeStep < 2" :label="activeStep === 1 ? 'Preview' : 'Next'" severity="info" :disabled="submitting || hasBlockingError" @click="goNextStep" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import axiosClient from '../../../../axios'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import hrService from '../../../../services/hr.services'

interface ShiftDayRow {
  key: string
  label: string
  offset: number
  enabled: boolean
  startRaw: string
  endRaw: string
  breakHours: number
}

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const submitting = ref(false)
const activeStep = ref(0)
const errorMessage = ref('')
const errorBannerRef = ref()
const application = ref<any | null>(null)
const createdEmployeeId = ref<number | null>(null)

const stepItems = [
  { label: 'Create Employee' },
  { label: 'Assign Shift' },
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
  salary: 0,
  position: '',
})

const shiftForm = reactive({
  week_start: new Date(),
  name: '',
})

const shiftDays = reactive<ShiftDayRow[]>([
  { key: 'monday', label: 'Monday', offset: 0, enabled: true, startRaw: '8:00 AM', endRaw: '5:00 PM', breakHours: 1 },
  { key: 'tuesday', label: 'Tuesday', offset: 1, enabled: true, startRaw: '8:00 AM', endRaw: '5:00 PM', breakHours: 1 },
  { key: 'wednesday', label: 'Wednesday', offset: 2, enabled: true, startRaw: '8:00 AM', endRaw: '5:00 PM', breakHours: 1 },
  { key: 'thursday', label: 'Thursday', offset: 3, enabled: true, startRaw: '8:00 AM', endRaw: '5:00 PM', breakHours: 1 },
  { key: 'friday', label: 'Friday', offset: 4, enabled: true, startRaw: '8:00 AM', endRaw: '5:00 PM', breakHours: 1 },
  { key: 'saturday', label: 'Saturday', offset: 5, enabled: false, startRaw: '8:00 AM', endRaw: '12:00 PM', breakHours: 0.5 },
])

const applicantName = computed(() => application.value?.full_name || `${application.value?.first_name || ''} ${application.value?.last_name || ''}`.trim())
const enabledShiftDays = computed(() => shiftDays.filter((day) => day.enabled))
const shiftWarning = computed(() => enabledShiftDays.value.some((day) => workingHours(day) > 8) ? 'One or more selected days are above the standard 8 working hours.' : '')
const hasBlockingError = computed(() => Boolean(errorMessage.value))

const scrollToAlert = async () => {
  await nextTick()
  const messageElement = errorBannerRef.value?.$el as HTMLElement | undefined
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

const parseTimeToMinutes = (value: string): number | null => {
  const input = String(value || '').trim().toLowerCase()
  if (!input) return null

  const meridiemMatch = input.match(/^(\d{1,2})(?::(\d{1,2}))?\s*(am|pm)$/i)
  if (meridiemMatch) {
    const rawHour = Number(meridiemMatch[1])
    const minute = Number(meridiemMatch[2] || 0)
    if (rawHour < 1 || rawHour > 12 || minute < 0 || minute > 59) return null
    const meridiem = meridiemMatch[3].toLowerCase()
    let hour = rawHour % 12
    if (meridiem === 'pm') hour += 12
    return (hour * 60) + minute
  }

  const hhmmMatch = input.match(/^(\d{1,2}):(\d{1,2})$/)
  if (hhmmMatch) {
    const hour = Number(hhmmMatch[1])
    const minute = Number(hhmmMatch[2])
    if (hour < 0 || hour > 23 || minute < 0 || minute > 59) return null
    return (hour * 60) + minute
  }

  const numeric = Number(input.replace(/[^0-9]/g, ''))
  if (Number.isNaN(numeric) || numeric <= 0 || numeric > 23) return null
  if (numeric <= 12) return (numeric % 12) * 60
  return numeric * 60
}

const minutesTo24h = (totalMinutes: number) => {
  const hour = Math.floor(totalMinutes / 60) % 24
  const minute = totalMinutes % 60
  return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`
}

const minutesTo12hLabel = (totalMinutes: number) => {
  const hour24 = Math.floor(totalMinutes / 60) % 24
  const minute = totalMinutes % 60
  const meridiem = hour24 >= 12 ? 'PM' : 'AM'
  const displayHour = (hour24 % 12) || 12
  return `${displayHour}:${String(minute).padStart(2, '0')} ${meridiem}`
}

const to24HourString = (value: string) => {
  const minutes = parseTimeToMinutes(value || '')
  if (minutes === null) return ''
  return minutesTo24h(minutes)
}

const loadPage = async () => {
  loading.value = true
  try {
    const [applicationResponse, branches, departments, roles, provinces] = await Promise.all([
      hrService.getJobApplication(route.params.applicationId as string),
      hrService.getBranches(),
      hrService.getDepartments(),
      hrService.getRoles(),
      axiosClient.get('/api/address/provinces'),
    ])

    application.value = applicationResponse?.data || applicationResponse

    // If backend didn't include jobPosting relationship, fetch it explicitly
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
    shiftForm.name = `${application.value?.jobPosting?.title || 'Employee'} Shift`

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
    const response = await axiosClient.get(`/api/address/cities/${employeeForm.province_code}`)
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
    const response = await axiosClient.get(`/api/address/barangays/${employeeForm.city_code}`)
    const barangayItems = response.data || []
    barangayOptions.value = (barangayItems || []).map((item: any) => ({ psgc_id: item.code, name: item.name }))
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Load failed', detail: 'Unable to load barangays for selected city.', life: 2500 })
  }
}

const normalizeHourInput = (value: string, isEnd = false) => {
  const parsedMinutes = parseTimeToMinutes(value)
  if (parsedMinutes === null) return ''
  let normalizedMinutes = parsedMinutes
  if (isEnd && normalizedMinutes <= (7 * 60)) {
    normalizedMinutes += 12 * 60
  }
  return minutesTo12hLabel(normalizedMinutes)
}

const normalizeDayTime = (day: ShiftDayRow, field: 'startRaw' | 'endRaw') => {
  const normalized = normalizeHourInput(day[field], field === 'endRaw')
  day[field] = normalized || day[field]
}

const toDisplayTime = (value: string) => {
  const minutes = parseTimeToMinutes(value || '')
  if (minutes === null) return 'Not set'
  const hour = Math.floor(minutes / 60) % 24
  const minute = minutes % 60
  const meridiem = hour >= 12 ? 'PM' : 'AM'
  const displayHour = hour % 12 || 12
  return `${displayHour}:${String(minute).padStart(2, '0')} ${meridiem}`
}

const dayPreview = (day: ShiftDayRow) => {
  if (!day.startRaw || !day.endRaw) return 'Set time range'
  return `${toDisplayTime(day.startRaw)} - ${toDisplayTime(day.endRaw)}`
}

const workingHours = (day: ShiftDayRow) => {
  if (!day.startRaw || !day.endRaw) return 0
  const start = timeToMinutes(day.startRaw)
  const end = timeToMinutes(day.endRaw)
  if (start === null || end === null) return 0
  let diff = end - start
  if (diff <= 0) diff += 24 * 60
  return Math.max(diff / 60 - day.breakHours, 0)
}

const breakLabel = (hours: number) => `${hours} hour${hours === 1 ? '' : 's'} break`

const timeToMinutes = (value: string) => {
  return parseTimeToMinutes(value)
}

const formatDateOnly = (value: Date | string | null) => {
  if (!value) return 'N/A'
  const date = typeof value === 'string' ? new Date(value) : value
  return date.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const formatCurrency = (value?: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', minimumFractionDigits: 0 }).format(Number(value || 0))

const copyMondayToAll = () => {
  const monday = shiftDays[0]
  shiftDays.slice(1).forEach((day) => {
    if (day.enabled) {
      day.startRaw = monday.startRaw
      day.endRaw = monday.endRaw
      day.breakHours = monday.breakHours
    }
  })
}

const copyToNextDay = (index: number) => {
  const source = shiftDays[index]
  const target = shiftDays[index + 1]
  if (!target) return
  target.enabled = true
  target.startRaw = source.startRaw
  target.endRaw = source.endRaw
  target.breakHours = source.breakHours
}

const goNextStep = () => {
  errorMessage.value = ''

  if (activeStep.value === 0) {
    if (!employeeForm.branch_id || !employeeForm.department_id || !employeeForm.role_id || !employeeForm.position || !employeeForm.salary) {
      void setError('Please complete the employee details before continuing.')
      return
    }
  }

  if (activeStep.value === 1) {
    if (!enabledShiftDays.value.length) {
      void setError('Enable at least one shift day before continuing.')
      return
    }
    if (enabledShiftDays.value.some((day) => !day.startRaw || !day.endRaw)) {
      void setError('Complete the working hours for each enabled day.')
      return
    }
    if (enabledShiftDays.value.some((day) => timeToMinutes(day.startRaw) === null || timeToMinutes(day.endRaw) === null)) {
      void setError('Enter valid shift times. Use formats like 8 AM, 5 PM, or 08:00.')
      return
    }
  }

  activeStep.value += 1
}

const scheduleDatesForDay = (day: ShiftDayRow) => {
  const weekStart = new Date(shiftForm.week_start)
  const monday = new Date(weekStart)
  monday.setHours(0, 0, 0, 0)
  const dayDate = new Date(monday)
  dayDate.setDate(monday.getDate() + day.offset)
  return dayDate.toISOString().slice(0, 10)
}

const submitOnboarding = async () => {
  errorMessage.value = ''
  submitting.value = true

  try {
    const hireResponse = await hrService.hireApplicant(route.params.applicationId as string, {
      branch_id: employeeForm.branch_id as number,
      department_id: employeeForm.department_id as number,
      role_id: employeeForm.role_id as number,
      hire_date: new Date(employeeForm.hire_date).toISOString().slice(0, 10),
      employment_type: employeeForm.employment_type as any,
      salary: Number(employeeForm.salary),
      position: employeeForm.position,
      phone: employeeForm.phone || undefined,
    })

    const employee = hireResponse?.data?.employee
    createdEmployeeId.value = employee?.id || null

    const groupedPatterns = new Map<string, ShiftDayRow[]>()
    enabledShiftDays.value.forEach((day) => {
      const key = `${day.startRaw}|${day.endRaw}|${day.breakHours}`
      const existing = groupedPatterns.get(key) || []
      existing.push(day)
      groupedPatterns.set(key, existing)
    })

    let patternIndex = 1
    for (const [key, days] of groupedPatterns.entries()) {
      const [startRaw, endRaw, breakHoursRaw] = key.split('|')
      const breakHours = Number(breakHoursRaw)
      const start24 = to24HourString(startRaw)
      const end24 = to24HourString(endRaw)
      if (!start24 || !end24) {
        await setError('Shift time must be in a valid format (e.g., 8:00 AM or 17:00).')
        submitting.value = false
        return
      }
      const startMinutes = timeToMinutes(startRaw)
      const endMinutes = timeToMinutes(endRaw)
      if (startMinutes === null || endMinutes === null) {
        await setError('Enter valid shift times before saving.')
        submitting.value = false
        return
      }

      const shiftResponse = await hrService.createShift({
        name: `${shiftForm.name} ${patternIndex}`,
        code: `NH-${Date.now().toString().slice(-6)}-${patternIndex}`,
        shift_type: 'fixed',
        start_time: start24,
        end_time: end24,
        total_hours: Math.max(((endMinutes - startMinutes + (endMinutes <= startMinutes ? 24 * 60 : 0)) / 60) - breakHours, 0),
        week_days: days.map((day) => day.key),
        grace_period_minutes: 15,
        has_night_diff: false,
        min_employees_required: 1,
        color: '#3b82f6',
        description: `Onboarding shift for ${applicantName.value}`,
      })

      const shiftId = shiftResponse?.data?.id
      if (shiftId && createdEmployeeId.value) {
        await hrService.createShiftScheduleBulk({
          employee_ids: [createdEmployeeId.value],
          shift_id: shiftId,
          schedule_dates: days.map((day) => scheduleDatesForDay(day)),
        })
      }

      patternIndex += 1
    }

    toast.add({
      severity: 'success',
      summary: 'Onboarding completed',
      detail: 'Employee profile and initial shift schedule were created successfully.',
      life: 3000,
    })
    router.push({ name: 'hr.employees.view', params: { id: createdEmployeeId.value } })
  } catch (error: any) {
    const firstError = error?.response?.data?.errors
      ? Object.values(error.response.data.errors)[0]
      : null
    const message = Array.isArray(firstError) ? firstError[0] : (error?.response?.data?.message || 'Unable to complete onboarding.')
    await setError(message)
  } finally {
    submitting.value = false
  }
}

watch([employeeForm, shiftForm, shiftDays], () => {
  if (errorMessage.value && !submitting.value) {
    errorMessage.value = ''
  }
}, { deep: true })

onMounted(loadPage)
</script>
