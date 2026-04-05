<template>
  <div class="min-h-screen bg-linear-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
    <Dialog
      v-model:visible="showDialog"
      :modal="true"
      :closable="false"
      :draggable="false"
      :style="{ width: '500px', maxWidth: '96vw' }"
      header="Set up your simple free trial"
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

        <div v-if="currentStep === 1" class="space-y-3">
          <p class="text-sm text-slate-600">
            This free trial is intentionally simple and includes selected modules so you can quickly evaluate if the system fits your business.
          </p>
          <div class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-700">
            During trial, module customization is disabled. Full module selection is available in paid plans.
          </div>
        </div>

        <div v-if="currentStep === 2" class="space-y-4">
          <p class="text-sm text-slate-600">Included free-trial modules (fixed set):</p>
          <div class="rounded-lg border border-slate-200 bg-white p-4">
            <div class="text-xs uppercase tracking-wide text-slate-400 mb-2">Included Modules</div>
            <div class="flex flex-wrap gap-2">
              <span
                v-for="module in selectedModuleLabels"
                :key="module"
                class="px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700"
              >
                {{ module }}
              </span>
            </div>
            <p class="text-xs text-slate-500 mt-3">
              You can manage modules later in Settings.
            </p>
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
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const showDialog = ref(true)
const loading = ref(false)
const currentStep = ref(1)
const trialPlan = ref(localStorage.getItem('trial_plan') || 'simple')
const registerToken = ref(localStorage.getItem('register_token') || '')

const form = ref({
  plan: trialPlan.value === 'unlimited' ? 'unlimited' : 'simple',
  employee_range: '1-5',
  branch_range: '',
  modules: ['inventory', 'sales', 'procurement', 'finance', 'hr'] as string[],
  primary_goal: '',
  first_team: '',
})

const steps = [
  { id: 1, label: 'About Trial' },
  { id: 2, label: 'Modules' },
  { id: 3, label: 'Goals' },
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
    return true
  }
  if (currentStep.value === 2) {
    return form.value.modules.length > 0
  }
  return true
})

const canFinish = computed(() => {
  return Boolean(
    form.value.modules.length > 0 &&
    form.value.primary_goal &&
    form.value.first_team &&
    !loading.value
  )
})

const FIXED_TRIAL_MODULES = ['inventory', 'sales', 'procurement', 'finance', 'hr']

const selectedModules = computed(() => {
  return FIXED_TRIAL_MODULES
})

const selectedModuleLabels = computed(() => {
  const map = new Map(moduleOptions.map(option => [option.value, option.label]))
  return selectedModules.value.map((value) => map.get(value) || value)
})

watch(selectedModules, (modules) => {
  form.value.modules = modules
}, { immediate: true })

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
  router.get('/login', {
    registered: 'true',
    onboarded: onboarded ? 'true' : 'false',
  })
}

const submitOnboarding = async () => {
  if (!canFinish.value) return

  loading.value = true
  try {
    await axios.get('/sanctum/csrf-cookie')
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
