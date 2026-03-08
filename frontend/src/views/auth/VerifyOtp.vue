<template>
  <div class="min-h-screen bg-linear-to-br from-blue-50 to-indigo-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="flex justify-center">
      </div>
      <h2 class="mt-6 text-center text-3xl font-bold text-gray-900" style="font-family: 'Poppins';">
        Verify Your Email
      </h2>
      <p class="mt-2 text-center text-sm text-gray-600">
        We've sent a 6-digit code to email.
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
              <input v-for="(digit, index) in otpDigits" :key="index" ref="otpInputs" v-model="otpDigits[index]"
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
            <Button type="submit" severity="info" :disabled="isLoading || isVerified"
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
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()

// OTP handling
const otpDigits = ref(Array(6).fill(''))
const otpInputs = ref([])
const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const isVerified = ref(false)
const resendCooldown = ref(0)
const accessToken = ref('')

// Compute full OTP from digits
const fullOtp = computed(() => otpDigits.value.join(''))

// Handle OTP input
const handleOtpInput = (index, event) => {
  const value = event.target.value

  // Only allow numbers
  if (!/^\d*$/.test(value)) {
    otpDigits.value[index] = ''
    return
  }

  // Auto-focus next input if a digit is entered
  if (value && index < 5) {
    otpInputs.value[index + 1]?.focus()
  }

  // Clear error when user types
  errorMessage.value = ''
}

// Handle keyboard navigation
const handleOtpKeydown = (index, event) => {
  // Handle backspace
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    otpInputs.value[index - 1]?.focus()
  }

  // Handle arrow keys
  if (event.key === 'ArrowLeft' && index > 0) {
    otpInputs.value[index - 1]?.focus()
  }
  if (event.key === 'ArrowRight' && index < 5) {
    otpInputs.value[index + 1]?.focus()
  }
}

// Handle paste
const handlePaste = (event) => {
  event.preventDefault()
  const pastedData = event.clipboardData.getData('text').trim()

  // Only accept numbers and exactly 6 digits
  if (/^\d{6}$/.test(pastedData)) {
    const digits = pastedData.split('')
    digits.forEach((digit, index) => {
      if (index < 6) {
        otpDigits.value[index] = digit
      }
    })
    otpInputs.value[5]?.focus()
    errorMessage.value = ''
  }
}

const verifyOtp = async () => { // Add async here
  if (isVerified.value) return

  const enteredOtp = fullOtp.value

  // Check if all digits are filled
  if (enteredOtp.length !== 6) {
    errorMessage.value = 'Please enter all 6 digits'
    otpInputs.value[0]?.focus()
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

      // Clear the register_token after successful verification
      localStorage.removeItem('register_token')

      // Remove axios auth header since token is no longer needed
      delete axios.defaults.headers.common['Authorization']

      // Redirect to login after delay
      setTimeout(() => {
        router.push({
          path: '/login',
          query: {
            registered: 'true',
            email: response.data.user?.email || ''
          }
        })
      }, 2000)
    } else {
      // Handle verification failure from API
      errorMessage.value = response.data.message || 'Invalid verification code. Please try again.'

      // Clear OTP for retry
      otpDigits.value = Array(6).fill('')
      otpInputs.value[0]?.focus()
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
    otpInputs.value[0]?.focus()
  } finally {
    isLoading.value = false
  }
}

// Resend code functionality
const resendCode = async () => {
  if (resendCooldown.value > 0) return

  try {
    // Make API call to resend OTP
    const response = await axios.post('/api/auth/resend-otp', {
      headers: {
        'Authorization': `Bearer ${accessToken.value}`
      }
    })

    // Start 30-second cooldown
    resendCooldown.value = 30

    const interval = setInterval(() => {
      resendCooldown.value--
      if (resendCooldown.value <= 0) {
        clearInterval(interval)
      }
    }, 1000)

    // Show success message
    successMessage.value = response.data.message || 'New verification code sent to your email!'

    // Clear message after 3 seconds
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)

    // Clear OTP inputs for fresh entry
    otpDigits.value = Array(6).fill('')
    otpInputs.value[0]?.focus()
  } catch (error: any) {
    console.error('Resend error:', error)
    errorMessage.value = error.response?.data?.message || 'Failed to resend code. Please try again.'
  }
}

// Go back to login
const goBack = () => {
  router.push('/login')
}

// Auto-focus first input on mount
onMounted(() => {
  setTimeout(() => {
    otpInputs.value[0]?.focus()
  }, 100)

  accessToken.value = localStorage.getItem('register_token')

  // Set axios authorization header
  if (accessToken.value) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${accessToken.value}`

    console.log('Authorization header set with register_token')
  } else {
    console.warn('No register_token found in localStorage')
  }

  // Auto-resend countdown if needed
  if (resendCooldown.value > 0) {
    const interval = setInterval(() => {
      resendCooldown.value--
      if (resendCooldown.value <= 0) {
        clearInterval(interval)
      }
    }, 1000)
  }
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
  -moz-appearance: textfield;
}
</style>