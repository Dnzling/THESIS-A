<template>
  <div class="px-6 max-w-8xl">
    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <div class="flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'flex items-center gap-2 px-1 py-4 text-sm font-medium border-b-2 transition-colors',
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          <i :class="tab.icon" class="text-base"></i>
          <span>{{ tab.label }}</span>
        </button>
      </div>
    </div>

    <!-- Content Area -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[600px]">
      <!-- Deduction Types Tab -->
      <DeductionType v-if="activeTab === 'deduction-types'" />

      <!-- Activity Log Tab -->
      <ActivityLog v-else-if="activeTab === 'activity-log'" />

      <!-- Interview Settings -->
      <div v-else-if="activeTab === 'interview-settings'" class="max-w-xl space-y-4">
        <div>
          <h3 class="text-lg font-semibold text-slate-900">Interview Capacity</h3>
          <p class="text-sm text-slate-500">Limit how many applicants can be scheduled per day.</p>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Daily interview limit</label>
          <InputNumber v-model="dailyInterviewLimit" :min="1" :max="50" class="w-full" />
          <p class="text-xs text-slate-500">Default: 10 per day.</p>
        </div>
        <div class="flex justify-end gap-2">
          <Button label="Save" severity="info" :loading="savingSettings" @click="saveInterviewSettings" />
        </div>
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
          <div class="flex justify-end gap-2">
            <Button label="Save Defaults" severity="info" :loading="savingLeaveSettings" @click="saveLeaveDefaults" />
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
              severity="info"
              :loading="savingAdjustment"
              @click="applyLeaveAdjustment"
            />
          </div>
        </div>
      </div>

      <!-- Other Settings Placeholder -->
      <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
        <i class="pi pi-cog text-6xl mb-4"></i>
        <p class="text-lg">Settings for {{ activeTab }} coming soon</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import DeductionType from './DeductionType.vue';
import ActivityLog from './ActivityLog.vue';
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import { useToast } from 'primevue/usetoast'
import hrService from '../../../services/hr.services'
// import ShiftSwaps from './ShiftSwaps.vue'

// Tabs configuration
  const tabs = [
    {
      id: 'deduction-types',
      label: 'Deduction Types',
      icon: 'pi pi-percentage'
    },
    {
      id: 'interview-settings',
      label: 'Interview Settings',
      icon: 'pi pi-calendar'
    },
    {
      id: 'leave-settings',
      label: 'Leave Settings',
      icon: 'pi pi-briefcase'
    },
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
const dailyInterviewLimit = ref(10)
const savingSettings = ref(false)
const savingLeaveSettings = ref(false)
const savingAdjustment = ref(false)

const leaveDefaults = ref<Record<string, number>>({
  vacation: 15,
  sick: 10,
  personal: 5,
  maternity: 0,
  paternity: 0,
  bereavement: 0,
  others: 0,
})

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
  } catch (error) {
    // silent fallback to default
  }
}

const saveInterviewSettings = async () => {
  savingSettings.value = true
  try {
    await hrService.updateHrSettings({ daily_interview_limit: Number(dailyInterviewLimit.value || 10) })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Interview settings updated.', life: 2500 })
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
    await hrService.updateHrSettings({ leave_defaults: leaveDefaults.value })
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
