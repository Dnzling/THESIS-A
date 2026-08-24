<template>
  <div class="min-h-screen py-6">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-8 rounded-3xl border border-orange-100 bg-gradient-to-br from-orange-50 via-white to-amber-50 p-6 text-center shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-orange-600">Owner verification</p>
        <h1 class="mt-2 text-3xl font-bold text-slate-950">Verify the store owner</h1>
        <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-600">
          Upload the store owner's primary Philippine ID, then submit the required business documents for review.
        </p>
      </div>
  
      <div class="mb-8 flex justify-center">
        <div class="flex w-full max-w-4xl items-center gap-3">
          <div class="flex items-center gap-3">
            <div :class="[
              'flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold',
              currentStep >= 1 ? 'bg-orange-500 text-white shadow-lg shadow-orange-200' : 'bg-slate-200 text-slate-500',
            ]">
              1
            </div>
            <span :class="currentStep >= 1 ? 'font-semibold text-slate-950' : 'text-slate-400'">Owner IDs</span>
          </div>
          <div class="h-1 flex-1 rounded-full" :class="currentStep >= 2 ? 'bg-orange-500' : 'bg-slate-200'"></div>
          <div class="flex items-center gap-3">
            <div :class="[
              'flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold',
              currentStep >= 2 ? 'bg-orange-500 text-white shadow-lg shadow-orange-200' : 'bg-slate-200 text-slate-500',
            ]">
              2
            </div>
            <span :class="currentStep >= 2 ? 'font-semibold text-slate-950' : 'text-slate-400'">Business Docs</span>
          </div>
          <div class="h-1 flex-1 rounded-full" :class="currentStep >= 3 ? 'bg-orange-500' : 'bg-slate-200'"></div>
          <div class="flex items-center gap-3">
            <div :class="[
              'flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold',
              currentStep >= 3 ? 'bg-orange-500 text-white shadow-lg shadow-orange-200' : 'bg-slate-200 text-slate-500',
            ]">
              3
            </div>
            <span :class="currentStep >= 3 ? 'font-semibold text-slate-950' : 'text-slate-400'">Review</span>
          </div>
        </div>
      </div>
      <div class="mb-8 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <div v-if="loadingStoreData" class="flex min-h-64 items-center justify-center text-sm text-slate-500">
          Checking your store record...
        </div>
        <component v-else :key="currentStep" :is="currentStepComponent" :formData="formData" @update:formData="handleFormUpdate"
          @next="goToNextStep" @prev="goToPrevStep" @verification-submitted="handleSubmitSuccess" @edit-step="goToStep" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, defineAsyncComponent, nextTick, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'

const toast = useToast()

interface Emits {
  (e: 'submitted', data: boolean): void
}

const emit = defineEmits<Emits>()

// Async components
const OwnerIdStep = defineAsyncComponent(() => import('./steps/OwnerIdStep.vue'))
const BusinessDocsStep = defineAsyncComponent(() => import('./steps/BusinessDocsStep.vue'))
const ReviewStep = defineAsyncComponent(() => import('./steps/ReviewStep.vue'))

// Current step
const currentStep = ref(1)
const loadingStoreData = ref(true)

// Form data
const formData = ref({
  storeId: null as number | null,
  primaryIdType: '',
  primaryIdNumber: '',
  primaryIdFront: null as File | null,
  primaryIdBack: null as File | null,
  primaryIdReadMessage: '',
  registrationPermit: null as File | null,
  taxCertificate: null as File | null,
  mayorPermit: null as File | null,
  additionalNotes: '',
  termsAccepted: false,
  privacyAccepted: false
})

const stepComponents = {
  1: OwnerIdStep,
  2: BusinessDocsStep,
  3: ReviewStep
}

// Get current component based on step
const currentStepComponent = computed(() => {
  return stepComponents[currentStep.value as keyof typeof stepComponents]
})

const scrollToTop = () => {
  // Target your main content wrapper
  const container = document.querySelector('.bg-gray-50') || 
                    document.querySelector('main') || 
                    document.documentElement
  
  if (container) {
    container.scrollTop = 0
  }
}

// Navigation with scroll
const goToNextStep = () => {
  if (currentStep.value < 3) {
    currentStep.value = Math.min(currentStep.value + 1, 3)
    nextTick(scrollToTop) // Scroll after DOM updates
  }
}

const goToPrevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
    nextTick(scrollToTop) // Scroll after DOM updates
  }
}

const goToStep = (step: number) => {
  if (step >= 1 && step <= 3) {
    currentStep.value = step
    nextTick(scrollToTop) // Scroll after DOM updates
  }
}

// Handle form updates from child components
const handleFormUpdate = (data: any) => {
  formData.value = { ...formData.value, ...data }
}

const handleSubmitSuccess = () => {
  emit('submitted', true)
}

const loadRecordedStoreData = async () => {
  try {
    const response = await axiosClient.get('/api/store/settings')
    const payload = response?.data?.data || {}
    const store = payload.store || {}
    formData.value = {
      ...formData.value,
      storeId: Number(store.id || 0) || null,
    }
  } catch (error) {
    console.error('Failed to load recorded store information', error)
    toast.add({ severity: 'warn', summary: 'Store data unavailable', detail: 'Please reload or return to Store Settings.', life: 3500 })
  } finally {
    loadingStoreData.value = false
  }
}

onMounted(loadRecordedStoreData)
</script>
