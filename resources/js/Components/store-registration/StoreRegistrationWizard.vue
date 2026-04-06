<template>
  <div class="min-h-screenpy-8">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Store Registration</h1>
        <p class="text-gray-600">
          Complete all 3 steps to register your store
        </p>
      </div>
  
      <!-- Progress Steps -->
      <div class="mb-10">
        <div class="flex justify-center items-center">
          <div class="flex items-center w-full max-w-4xl">
            <!-- Step 1 -->
            <div class="flex items-center flex-1">
              <div :class="[
                          'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm',
                          currentStep >= 1 ? 'bg-black' : 'bg-gray-300'
                        ]">
                1
              </div>
              <div :class="[
                          'ml-2 text-sm font-medium',
                          currentStep >= 1 ? 'text-black' : 'text-gray-400'
                        ]">
                Store Info
              </div>
            </div>
  
            <!-- Connector -->
            <div :class="[
                      'h-1 flex-1 mx-2',
                      currentStep >= 2 ? 'bg-black' : 'bg-gray-300'
                    ]"></div>
  
            <!-- Step 2 -->
            <div class="flex items-center flex-1">
              <div :class="[
                          'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm',
                          currentStep >= 2 ? 'bg-black' : 'bg-gray-300'
                        ]">
                2
              </div>
              <div :class="[
                          'ml-2 text-sm font-medium',
                          currentStep >= 2 ? 'text-black' : 'text-gray-400'
                        ]">
                Business Docs
              </div>
            </div>
  
            <!-- Connector -->
            <div :class="[
                      'h-1 flex-1 mx-2',
                      currentStep >= 3 ? 'bg-black' : 'bg-gray-300'
                    ]"></div>
  
            <!-- Step 3 -->
            <div class="flex items-center flex-1">
              <div :class="[
                          'w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm',
                          currentStep >= 3 ? 'bg-black' : 'bg-gray-300'
                        ]">
                3
              </div>
              <div :class="[
                          'ml-2 text-sm font-medium',
                          currentStep >= 3 ? 'text-black' : 'text-gray-400'
                        ]">
                Review
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Step Content - THIS IS WHERE COMPONENTS ARE RENDERED -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <!-- Conditionally render the current step component -->
        <KeepAlive>
          <component :is="currentStepComponent" :formData="formData" @update:formData="handleFormUpdate"
            @next="goToNextStep" @prev="goToPrevStep" @submit="handleSubmitSuccess" @edit-step="goToStep" />
        </KeepAlive>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, defineAsyncComponent, nextTick } from 'vue'
import { useToast } from 'primevue/usetoast'
import { router } from '@inertiajs/vue3'

const toast = useToast()

interface Emits {
  (e: 'submitted', data: boolean): void
}

const emit = defineEmits<Emits>()

// Async components
const StoreInfoStep = defineAsyncComponent(() => import('./steps/StoreInfoStep.vue'))
const BusinessDocsStep = defineAsyncComponent(() => import('./steps/BusinessDocsStep.vue'))
const ReviewStep = defineAsyncComponent(() => import('./steps/ReviewStep.vue'))

// Current step
const currentStep = ref(1)

// Form data
const formData = ref({
  // Step 1
  storeName: '',
  businessType: '',
  businessNumber: '',
  businessAddress: {
    address: '',
    city: '',
    cityId: '',
    barangay: '',
    barangayCode: '',
    longitude: '',
    latitude: '',
  },
  contactNumber: '',
  email: '',

  // Step 2
  registrationPermit: null as File | null,
  taxCertificate: null as File | null,
  mayorPermit: null as File | null,
  additionalNotes: '',

  // Step 3
  termsAccepted: false,
  privacyAccepted: false
})

// Step components mapping
const stepComponents = {
  1: StoreInfoStep,
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
    currentStep.value++
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
  router.visit('/store/settings')
}
</script>
