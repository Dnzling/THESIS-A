<!-- views/system/hr/CreateShift.vue -->
<template>
  <div class="p-6 max-w-4xl mx-auto">
    <ConfirmDialog />
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-semibold text-gray-800">Create New Shift</h1>
        <p class="text-sm text-gray-500 mt-1">Define shift details and assignment rules</p>
      </div>
      <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined @click="cancel" />
    </div>

    <!-- Main Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="p-6 space-y-6">

        <!-- Basic Information -->
        <div>
          <h2 class="font-semibold text-gray-700 mb-4">Basic Information</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Shift Name <span class="text-red-500">*</span></label>
              <InputText v-model="form.name" placeholder="e.g., Morning Shift" class="w-full"
                :class="{ 'p-invalid': errors.name }" />
              <small class="text-red-500" v-if="errors.name">{{ errors.name[0] }}</small>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Shift Type <span class="text-red-500">*</span></label>
              <Select v-model="form.shift_type" :options="shiftTypeOptions" optionLabel="label" optionValue="value"
                placeholder="Select type" class="w-full" :class="{ 'p-invalid': errors.shift_type }" />
              <small class="text-red-500" v-if="errors.shift_type">{{ errors.shift_type[0] }}</small>
            </div>
          </div>
          <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
            <Textarea v-model="form.description" rows="2" class="w-full"
              placeholder="Optional description for this shift..." />
          </div>
        </div>

        <!-- Schedule -->
        <div class="border-t border-gray-100 pt-6">
          <h2 class="font-semibold text-gray-700 mb-4">Schedule</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Start Time <span class="text-red-500">*</span></label>
              <DatePicker v-model="form.start_time" timeOnly hourFormat="12" class="w-full"
                :class="{ 'p-invalid': errors.start_time }" />
              <small class="text-red-500" v-if="errors.start_time">{{ errors.start_time[0] }}</small>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">End Time <span class="text-red-500">*</span></label>
              <InputText :modelValue="endTimeDisplay" disabled class="w-full bg-gray-50" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Break Start</label>
              <DatePicker v-model="form.break_start" timeOnly hourFormat="12" class="w-full" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Break End</label>
              <InputText :modelValue="breakEndDisplay" disabled class="w-full bg-gray-50" />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Grace Period (minutes) <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.grace_period_minutes" :min="0" :max="60" class="w-full"
                :class="{ 'p-invalid': errors.grace_period_minutes }" />
              <small class="text-red-500" v-if="errors.grace_period_minutes">{{ errors.grace_period_minutes[0] }}</small>
            </div>
          </div>
        </div>

        <!-- Working Days -->
        <div class="border-t border-gray-100 pt-6">
          <h2 class="font-semibold text-gray-700 mb-4">Working Days</h2>
          <div class="flex gap-2">
            <div v-for="day in weekDays" :key="day.value" class="flex-1">
              <div class="border rounded-lg p-3 text-center cursor-pointer transition-colors"
                :class="form.week_days.includes(day.value) ? 'bg-blue-50 border-blue-300 text-blue-700' : 'hover:bg-gray-50 border-gray-200'"
                @click="toggleDay(day.value)">
                <div class="text-sm font-medium">{{ day.label }}</div>
                <div class="text-xs text-gray-500 mt-1">{{ day.full }}</div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Footer Actions -->
      <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex justify-end gap-2">
        <Button label="Cancel" icon="pi pi-times" severity="secondary" outlined @click="cancel" />
        <Button label="Create Shift" icon="pi pi-check" severity="info" @click="confirmCreateShift" :loading="saving" />
      </div>
    </div>

    <!-- Success Dialog -->
    <Dialog v-model:visible="showSuccessDialog" modal :style="{ width: '400px' }" :closable="false">
      <div class="text-center p-4">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <i class="pi pi-check text-green-500 text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Shift Created Successfully!</h3>
        <p class="text-sm text-gray-500 mb-4">
          Shift "<strong>{{ form.name }}</strong>" has been created and is ready for assignment.
        </p>
        <div class="flex gap-2 justify-center">
          <Button label="Back to Shifts" severity="info" @click="goBack" />
          <Button label="Create Another" severity="secondary" outlined @click="createAnother" />
        </div>
      </div>
    </Dialog>

    <!-- Error Dialog -->
    <Dialog v-model:visible="showErrorDialog" modal header="Error" :style="{ width: '400px' }">
      <div class="flex items-center gap-3 p-2">
        <i class="pi pi-times-circle text-red-500 text-3xl"></i>
        <p>{{ errorMessage }}</p>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="showErrorDialog = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import hrService from '@/services/hr.services'
import { useAuthStore } from '../../../stores/auth'
import { useToast } from 'primevue/usetoast'
import DatePicker from 'primevue/datepicker'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()

// --- State ---
const saving = ref(false)
const showSuccessDialog = ref(false)
const showErrorDialog = ref(false)
const errorMessage = ref('')
const errors = ref<Record<string, string[]>>({})

// --- Form ---
const form = ref({
  name: '',
  shift_type: 'fixed' as string,
  start_time: new Date(0, 0, 0, 9, 0, 0) as Date | null,
  break_start: null as Date | null,
  week_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as string[],
  grace_period_minutes: 15 as number,
  description: ''
})

// --- Options ---
const shiftTypeOptions = [
  { label: 'Fixed', value: 'fixed' },
  { label: 'Rotating', value: 'rotating' },
  { label: 'Flexible', value: 'flexible' }
]

const weekDays = [
  { label: 'M', full: 'Monday', value: 'monday' },
  { label: 'T', full: 'Tuesday', value: 'tuesday' },
  { label: 'W', full: 'Wednesday', value: 'wednesday' },
  { label: 'T', full: 'Thursday', value: 'thursday' },
  { label: 'F', full: 'Friday', value: 'friday' },
  { label: 'S', full: 'Saturday', value: 'saturday' },
  { label: 'S', full: 'Sunday', value: 'sunday' }
]

// --- Methods ---
const toggleDay = (day: string) => {
  const index = form.value.week_days.indexOf(day)
  if (index === -1) {
    if (form.value.week_days.length >= 5) {
      toast.add({ severity: 'warn', summary: 'Limit reached', detail: 'Select up to 5 working days only.', life: 2500 })
      return
    }
    form.value.week_days.push(day)
  } else {
    form.value.week_days.splice(index, 1)
  }
}

const toTimeString = (date: Date | null): string | null => {
  if (!date) return null
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${hours}:${minutes}`
}

const addHours = (date: Date | null, hours: number): Date | null => {
  if (!date) return null
  const next = new Date(date.getTime())
  next.setHours(next.getHours() + hours)
  return next
}

const endTimeDisplay = computed(() => {
  const end = addHours(form.value.start_time, 8)
  return end ? end.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '--:--'
})

const breakEndDisplay = computed(() => {
  const end = addHours(form.value.break_start, 1)
  return end ? end.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' }) : '--:--'
})

const confirmCreateShift = () => {
  confirm.require({
    header: 'Create Shift',
    message: 'Save this shift? End time will be set to 8 hours after the start time.',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Save',
    rejectLabel: 'Cancel',
    accept: () => createShift(),
  })
}

const createShift = async () => {
  errors.value = {}

  if (!form.value.name || !form.value.start_time) {
    toast.add({ severity: 'warn', summary: 'Validation', detail: 'Please fill in all required fields', life: 3000 })
    return
  }

  saving.value = true
  try {
    const startTime = toTimeString(form.value.start_time)
    if (!startTime) {
      toast.add({ severity: 'warn', summary: 'Validation', detail: 'Start time is required.', life: 3000 })
      return
    }

    const payload: Record<string, any> = {
      name: form.value.name,
      shift_type: form.value.shift_type,
      start_time: startTime,
      week_days: form.value.week_days.length > 0 ? form.value.week_days : null,
      grace_period_minutes: form.value.grace_period_minutes,
      description: form.value.description || null
    }

    const breakStart = toTimeString(form.value.break_start)
    if (breakStart) payload.break_start = breakStart

    const response = await hrService.api.post('/api/shifts', payload, {
      headers: { Authorization: `Bearer ${authStore.token}` }
    })

    if (response.data.success) {
      showSuccessDialog.value = true
    }
  } catch (err: any) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
      toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please fix the highlighted fields', life: 4000 })
    } else {
      errorMessage.value = err.response?.data?.message || 'Failed to create shift. Please try again.'
      showErrorDialog.value = true
    }
  } finally {
    saving.value = false
  }
}

const cancel = () => {
  router.push({ name: 'hr.shifts' })
}

const goBack = () => {
  router.push({ name: 'hr.shifts' })
}

const createAnother = () => {
  showSuccessDialog.value = false
  form.value = {
    name: '',
    shift_type: 'fixed' as string,
    start_time: new Date(0, 0, 0, 9, 0, 0),
    break_start: null,
    week_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as string[],
    grace_period_minutes: 15 as number,
    description: ''
  }
  errors.value = {}
}
</script>
