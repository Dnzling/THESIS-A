<template>
  <div class="min-h-screen bg-linear-to-br from-blue-50 to-indigo-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="flex justify-center">
      </div>
      <div class="mb-3 flex justify-center">
        <span
          class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
          :class="isCustomerOtp ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'"
        >
          {{ otpContextLabel }}
        </span>
      </div>
      <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
        Verify Your Email
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        {{ isCustomerOtp ? "We've sent a Furnisync Shop 6-digit code to your email." : "We've sent a 6-digit code to email." }}
        <span class="block text-xs text-gray-500 mt-1">Code expires in 15 minutes.</span>
      </p>
    </div>
  
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <form @submit.prevent="verifyOtp" class="space-y-6">
          <!-- OTP Input -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Enter 6-digit verification code
            </label>
            <div class="flex justify-center space-x-2 mb-6">
              <input v-for="(_, index) in otpDigits" :key="index" ref="otpInputs" v-model="otpDigits[index]"
                type="text" maxlength="1" @input="handleOtpInput(index, $event)"
                @keydown="handleOtpKeydown(index, $event)" @paste="handlePaste"
                class="w-12 h-12 text-center text-2xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 outline-none transition"
                :class="{ 'border-red-300': errorMessage }" />
            </div>
  
            <!-- Error Message -->
            <div v-if="errorMessage" class="text-red-600 text-sm text-center mb-4">
              {{ errorMessage }}
            </div>
          </div>
  
          <!-- Success Message -->
          <div v-if="successMessage" class="bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
              <div class="shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-green-800">
                  {{ successMessage }}
                </p>
              </div>
            </div>
          </div>
  
          <!-- Submit Button -->
          <div>
            <Button type="submit" severity="warn" :disabled="isLoading || isVerified"
              class="w-full flex justify-center py-3 px-4 font-semibold">
              <span v-if="isLoading">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                  viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                  </path>
                </svg>
              </span>
              <span v-if="isVerified">Verified ✓</span>
              <span v-else>{{ isLoading ? 'Verifying...' : 'Verify Code' }}</span>
            </Button>
          </div>
  
          <!-- Resend Code Section -->
          <div class="text-center">
            <p class="text-sm text-gray-600">
              Didn't receive the code?
              <button type="button" @click="resendCode" :disabled="resendCooldown > 0"
                class="font-medium text-blue-600 hover:text-blue-500 disabled:text-gray-400 disabled:cursor-not-allowed">
                {{ resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend code' }}
              </button>
            </p>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

// OTP handling
const otpDigits = ref<string[]>(Array(6).fill(''))
const otpInputs = ref<(HTMLInputElement | null)[]>([])
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const isVerified = ref(false)
const resendCooldown = ref(0)
const resendCooldownSeconds = 60
const accessToken = ref<string | null>(null)
let resendInterval: ReturnType<typeof setInterval> | null = null

// Compute full OTP from digits
const fullOtp = computed(() => otpDigits.value.join(''))
const otpContext = computed(() => localStorage.getItem('otp_context') || 'saas')
const isCustomerOtp = computed(() => otpContext.value === 'customer')
const isProfileOtp = computed(() => otpContext.value === 'profile_email_change')
const isSupplierOtp = computed(() => otpContext.value === 'supplier')
const otpContextLabel = computed(() => {
  if (isCustomerOtp.value) return 'Furnisync Shop Customer Verification'
  if (isProfileOtp.value) return 'Profile Email Change'
  if (isSupplierOtp.value) return 'Supplier Verification'
  return 'Furnisync Verification'
})

const focusOtpInput = (index: number) => {
  const input = otpInputs.value[index]
  if (input) input.focus()
}

// Handle OTP input
const handleOtpInput = (index: number, event: Event) => {
  const target = event.target as HTMLInputElement | null
  const value = target?.value || ''

  // Only allow numbers
  if (!/^\d*$/.test(value)) {
    otpDigits.value[index] = ''
    return
  }

  // Auto-focus next input if a digit is entered
  if (value && index < 5) {
    focusOtpInput(index + 1)
  }

  // Clear error when user types
  errorMessage.value = ''
}

// Handle keyboard navigation
const handleOtpKeydown = (index: number, event: KeyboardEvent) => {
  // Handle backspace
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    focusOtpInput(index - 1)
  }

  // Handle arrow keys
  if (event.key === 'ArrowLeft' && index > 0) {
    focusOtpInput(index - 1)
  }
  if (event.key === 'ArrowRight' && index < 5) {
    focusOtpInput(index + 1)
  }
}

// Handle paste
const handlePaste = (event: ClipboardEvent) => {
  event.preventDefault()
  const pastedData = event.clipboardData?.getData('text').trim() || ''

  // Only accept numbers and exactly 6 digits
  if (/^\d{6}$/.test(pastedData)) {
    const digits = pastedData.split('')
    digits.forEach((digit: string, index: number) => {
      if (index < 6) {
        otpDigits.value[index] = digit
      }
    })
    focusOtpInput(5)
    errorMessage.value = ''
  }
}

const verifyOtp = async () => { // Add async here
  if (isVerified.value) return

  const enteredOtp = fullOtp.value

  // Check if all digits are filled
  if (enteredOtp.length !== 6) {
    errorMessage.value = 'Please enter all 6 digits'
    focusOtpInput(0)
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await axios.post('/api/auth/verify-otp', {
      otp: enteredOtp
    }, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`
      }
    })
    console.log('Verification response:', response.data)

    // Check if verification was successful
    if (response.data.success) {
      successMessage.value = response.data.message || 'Email verified successfully!'
      isVerified.value = true

      // Clear OTP context once verified.
      localStorage.removeItem('otp_context')

      // Remove axios auth header since token is no longer needed
      delete axios.defaults.headers.common['Authorization']

      if (isCustomerOtp.value) {
        setTimeout(() => {
          localStorage.removeItem('register_token')
          router.get('/customer/login', {
            registered: 'true',
            email: response.data.user?.email || ''
          })
        }, 1200)
      } else if (isProfileOtp.value) {
        setTimeout(() => {
          localStorage.removeItem('register_token')
          router.visit('/hr/profile')
        }, 1200)
      } else if (isSupplierOtp.value) {
        setTimeout(() => {
          localStorage.removeItem('register_token')
          router.visit('/login')
        }, 1200)
      } else {
        setTimeout(() => {
          localStorage.removeItem('register_token')
          router.get('/login', { registered: 'true' })
        }, 1200)
      }
    } else {
      // Handle verification failure from API
      errorMessage.value = response.data.message || 'Invalid verification code. Please try again.'

      // Clear OTP for retry
      otpDigits.value = Array(6).fill('')
      focusOtpInput(0)
    }
  } catch (error: any) {
    console.error('Verification error:', error)

    // Handle different error cases
    if (error.response?.status === 422) {
      errorMessage.value = error.response.data.message || 'Invalid OTP format'
    } else if (error.response?.status === 400) {
      errorMessage.value = 'Invalid or expired verification code'
    } else if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message
    } else {
      errorMessage.value = 'Verification failed. Please try again.'
    }

    // Clear OTP for retry
    otpDigits.value = Array(6).fill('')
    focusOtpInput(0)
  } finally {
    isLoading.value = false
  }
}

const startResendCooldown = (seconds = resendCooldownSeconds) => {
  resendCooldown.value = seconds
  if (resendInterval) clearInterval(resendInterval)
  resendInterval = setInterval(() => {
    resendCooldown.value--
    if (resendCooldown.value <= 0) {
      resendCooldown.value = 0
      if (resendInterval) clearInterval(resendInterval)
      resendInterval = null
    }
  }, 1000)
}

// Resend code functionality
const resendCode = async () => {
  if (resendCooldown.value > 0) return

  try {
    // Make API call to resend OTP
    const response = await axios.post('/api/auth/resend-otp', {}, {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`
      }
    })

    // Start 60-second cooldown
    startResendCooldown()

    // Show success message
    successMessage.value = response.data.message || 'New verification code sent to your email!'

    // Clear message after 3 seconds
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)

    // Clear OTP inputs for fresh entry
    otpDigits.value = Array(6).fill('')
    focusOtpInput(0)
  } catch (error: any) {
    console.error('Resend error:', error)
    errorMessage.value = error.response?.data?.message || 'Failed to resend code. Please try again.'
  }
}

// Auto-focus first input on mount
onMounted(() => {
  setTimeout(() => {
    focusOtpInput(0)
  }, 100)

  accessToken.value = localStorage.getItem('register_token')
    || localStorage.getItem('auth_token')
    || localStorage.getItem('access_token')

  // Set axios authorization header
  if (accessToken.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${accessToken.value}`

    console.log('Authorization header set with register_token')
  } else {
    console.warn('No register_token found in localStorage')
  }

  // Start initial 60s countdown on page load
  startResendCooldown()
})

onBeforeUnmount(() => {
  if (resendInterval) clearInterval(resendInterval)
  resendInterval = null
})
</script>

<style scoped>
/* Add any custom styles here */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type=number] {
  appearance: textfield;
  -moz-appearance: textfield;
}
</style>
