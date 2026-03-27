<template>
  <div class="min-h-screen bg-linear-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <Dialog
      v-model:visible="showDialog"
      :modal="true"
      :closable="false"
      :draggable="false"
      :style="{ width: '760px', maxWidth: '96vw' }"
      header="Set up your free trial"
    >
      <div class="space-y-5">
        <div class="flex items-center gap-2 text-xs text-slate-500">
          <span
            v-for="step in steps"
            :key="step.id"
            class="rounded-full px-3 py-1 font-medium"
            :class="currentStep >= step.id ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'"
          >
            {{ step.id }}. {{ step.label }}
          </span>
        </div>

        <div v-if="currentStep === 1" class="space-y-4">
          <p class="text-sm text-slate-600">Tell us your team size so we can tailor limits and defaults.</p>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">How many employees do you have?</label>
              <Select
                v-model="form.employee_range"
                :options="employeeRangeOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select employee count"
                class="w-full"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">How many branches do you operate?</label>
              <Select
                v-model="form.branch_range"
                :options="branchRangeOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select branch count"
                class="w-full"
              />
            </div>
          </div>
        </div>

        <div v-if="currentStep === 2" class="space-y-4">
          <p class="text-sm text-slate-600">Choose modules to activate for your trial workspace.</p>
          <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Modules</label>
            <MultiSelect
              v-model="form.modules"
              :options="moduleOptions"
              optionLabel="label"
              optionValue="value"
              display="chip"
              filter
              placeholder="Select modules"
              class="w-full"
            />
          </div>
          <div class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600">
            Pick at least one module. You can enable or disable modules later.
          </div>
        </div>

        <div v-if="currentStep === 3" class="space-y-4">
          <p class="text-sm text-slate-600">Final details so we can prepare your onboarding recommendations.</p>
          <div class="grid gap-4 md:grid-cols-2">
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Primary goal</label>
              <Select
                v-model="form.primary_goal"
                :options="goalOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select your primary goal"
                class="w-full"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm font-medium text-gray-700">Who will use the system first?</label>
              <Select
                v-model="form.first_team"
                :options="teamOptions"
                optionLabel="label"
                optionValue="value"
                placeholder="Select first team"
                class="w-full"
              />
            </div>
          </div>
          <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-700">
            Selected plan: <strong>{{ trialPlanLabel }}</strong>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex w-full items-center justify-between">
          <Button label="Back" icon="pi pi-arrow-left" text :disabled="currentStep === 1 || loading" @click="prevStep" />
          <div class="flex items-center gap-2">
            <Button label="Skip for now" severity="secondary" outlined :disabled="loading" @click="skipOnboarding" />
            <Button
              v-if="currentStep < 3"
              label="Next"
              icon="pi pi-arrow-right"
              iconPos="right"
              :disabled="!canProceedCurrentStep || loading"
              @click="nextStep"
            />
            <Button
              v-else
              label="Finish Setup"
              icon="pi pi-check"
              :loading="loading"
              :disabled="!canFinish"
              @click="submitOnboarding"
            />
          </div>
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const showDialog = ref(true)
const loading = ref(false)
const currentStep = ref(1)
const trialPlan = ref(localStorage.getItem('trial_plan') || 'simple')
const registerToken = ref(localStorage.getItem('register_token') || '')

const form = ref({
  plan: trialPlan.value === 'unlimited' ? 'unlimited' : 'simple',
  employee_range: '',
  branch_range: '',
  modules: [] as string[],
  primary_goal: '',
  first_team: '',
})

const steps = [
  { id: 1, label: 'Team Size' },
  { id: 2, label: 'Modules' },
  { id: 3, label: 'Goals' },
]

const employeeRangeOptions = [
  { label: '1-5 employees', value: '1-5' },
  { label: '6-20 employees', value: '6-20' },
  { label: '21-50 employees', value: '21-50' },
  { label: '51-100 employees', value: '51-100' },
  { label: '100+ employees', value: '100+' },
]

const branchRangeOptions = [
  { label: '1 branch', value: '1' },
  { label: '2-3 branches', value: '2-3' },
  { label: '4-10 branches', value: '4-10' },
  { label: '10+ branches', value: '10+' },
]

const moduleOptions = [
  { label: 'Inventory', value: 'inventory' },
  { label: 'Procurement', value: 'procurement' },
  { label: 'Sales / POS', value: 'sales' },
  { label: 'HR / Payroll', value: 'hr' },
  { label: 'Logistics / Delivery', value: 'logistics' },
  { label: 'Finance', value: 'finance' },
  { label: 'Supplier Portal', value: 'supplier' },
  { label: 'E-commerce', value: 'ecommerce' },
]

const goalOptions = [
  { label: 'Improve inventory accuracy', value: 'inventory-accuracy' },
  { label: 'Speed up sales operations', value: 'faster-sales' },
  { label: 'Automate payroll and attendance', value: 'hr-automation' },
  { label: 'Control purchasing and suppliers', value: 'procurement-control' },
  { label: 'Centralize all branches', value: 'branch-centralization' },
]

const teamOptions = [
  { label: 'Owner / Admin', value: 'owner' },
  { label: 'Operations Team', value: 'operations' },
  { label: 'Sales Team', value: 'sales' },
  { label: 'HR Team', value: 'hr' },
  { label: 'Finance Team', value: 'finance' },
]

const trialPlanLabel = computed(() => (form.value.plan === 'unlimited' ? 'Unlimited Trial' : 'Simple Trial'))

const canProceedCurrentStep = computed(() => {
  if (currentStep.value === 1) {
    return Boolean(form.value.employee_range && form.value.branch_range)
  }
  if (currentStep.value === 2) {
    return form.value.modules.length > 0
  }
  return true
})

const canFinish = computed(() => {
  return Boolean(
    form.value.employee_range &&
    form.value.branch_range &&
    form.value.modules.length > 0 &&
    form.value.primary_goal &&
    form.value.first_team &&
    !loading.value
  )
})

const nextStep = () => {
  if (currentStep.value < 3 && canProceedCurrentStep.value) currentStep.value += 1
}

const prevStep = () => {
  if (currentStep.value > 1) currentStep.value -= 1
}

const cleanupTempAuth = () => {
  localStorage.removeItem('register_token')
  localStorage.removeItem('otp_context')
  localStorage.removeItem('trial_plan')
  delete axios.defaults.headers.common['Authorization']
}

const goLogin = (onboarded: boolean) => {
  router.push({
    path: '/login',
    query: {
      registered: 'true',
      onboarded: onboarded ? 'true' : 'false',
    },
  })
}

const submitOnboarding = async () => {
  if (!canFinish.value) return

  loading.value = true
  try {
    await axios.post('/api/auth/trial-onboarding', form.value, {
      headers: {
        Authorization: `Bearer ${registerToken.value}`,
      },
    })

    localStorage.setItem('trial_onboarding', JSON.stringify({ ...form.value, created_at: new Date().toISOString() }))
    cleanupTempAuth()
    goLogin(true)
  } catch (error) {
    console.error('Failed to save trial onboarding:', error)
  } finally {
    loading.value = false
  }
}

const skipOnboarding = () => {
  cleanupTempAuth()
  goLogin(false)
}
</script>
