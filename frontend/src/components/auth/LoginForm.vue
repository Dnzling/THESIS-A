<template>
  <div class="min-h-screen flex items-center justify-center bg-linear-to-br from-blue-50 to-indigo-100 p-4">
    <Card class="w-full max-w-md shadow-2xl">
      <template #title>
        <div class="flex items-center justify-left mb-2 mt-3">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Welcome Back</h1>
            <p class="text-gray-400 mt-1 text-sm font-medium">Sign in to your account</p>
          </div>
        </div>
      </template>
  
      <template #content>
        <!-- Error Message -->
        <Message v-if="errorMessage" severity="error" :closable="true" @close="errorMessage = ''" class="mb-6">
          {{ errorMessage }}
        </Message>
  
        <!-- Success Message (for registration redirect) -->
        <Message v-if="successMessage" severity="success" :closable="true" @close="successMessage = ''" class="mb-6">
          {{ successMessage }}
        </Message>
  
        <!-- Login Form -->
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- User ID Field -->
          <div class="space-y-2">
            <label for="email" class="block text-sm font-medium text-gray-700">
              Email Address <span class="text-red-500">*</span>
            </label>
            <div class="p-inputgroup">
              <InputText id="email" v-model="formData.email" type="text" placeholder="ex. john@example.com"
                :class="{ 'p-invalid': validationErrors.email }" class="w-full" autocomplete="email" />
            </div>
            <small v-if="validationErrors.email" class="p-error">
              {{ validationErrors.email }}
            </small>
          </div>
  
          <!-- Password Field -->
          <div class="space-y-2">
            <div class="flex justify-between items-center">
              <label for="password" class="block text-sm font-medium text-gray-700">
                Password <span class="text-red-500">*</span>
              </label>
              <!-- <router-link 
                      to="/forgot-password" 
                      class="text-sm text-blue-600 hover:text-blue-800 hover:underline"
                    >
                      Forgot password?
                    </router-link> -->
            </div>
            <div class="p-inputgroup">
              <Password id="password" v-model="formData.password" :feedback="false" toggleMask
                placeholder="Enter your password" :class="{ 'p-invalid': validationErrors.password }" class="w-full"
                autocomplete="current-password" />
            </div>
            <small v-if="validationErrors.password" class="p-error">
              {{ validationErrors.password }}
            </small>
          </div>
  
          <!-- Remember Me & Forgot Password -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <Checkbox v-model="formData.rememberMe" inputId="rememberMe" :binary="true" />
              <label for="rememberMe" class="ml-2 text-sm text-gray-700 cursor-pointer">
                Remember me
              </label>
            </div>
          </div>
  
          <!-- Submit Button -->
          <Button type="submit" label="Log In" :loading="props.isSubmitting"
            class="w-full"
            :disabled="props.isSubmitting" severity="contrast" />
        </form>
      </template>
  
      <template #footer>
        <div class="text-center pt-6 border-t border-gray-100">
          <!-- <p class="text-gray-600">
            Don't have an account?
            <router-link to="/pricing" class="text-blue-600 font-semibold hover:text-blue-800 hover:underline ml-1">
              Sign up here
            </router-link>
          </p> -->
          <p class="text-sm text-gray-500">
            By signing in, you agree to our
            <a href="#" class="text-blue-600 hover:underline">Terms</a> and
            <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>
          </p>
        </div>
      </template>
    </Card>
  
    <!-- Loading Overlay -->
    <Dialog v-model:visible="props.isSubmitting" modal :closable="false" :showHeader="false" :style="{ width: '350px' }">
      <div class="flex flex-col items-center justify-center p-6">
        <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" fill="transparent" animationDuration=".5s" />
        <p class="mt-4 text-lg font-medium text-gray-700">Signing you in...</p>
        <p class="text-gray-500">Please wait a moment</p>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'

const props = defineProps<{
  isSubmitting?: boolean
}>()

const emit = defineEmits<{
  submit: [data: LoginFormData]
  error: [message: string]
}>()

// Types
interface LoginFormData {
  email: string
  password: string
  rememberMe: boolean
}

interface ValidationErrors {
  email?: string
  password?: string
}

// Router
// const router = useRouter()
const route = useRoute()

// State
const formData = reactive<LoginFormData>({
  email: '',
  password: '',
  rememberMe: false
})

const validationErrors = reactive<ValidationErrors>({})
const errorMessage = ref<string>('')
const successMessage = ref<string>('')

// Check for success messages from registration
onMounted(() => {
  if (route.query.registered === 'true') {
    successMessage.value = 'Account created successfully! Please sign in.'
  }
  if (route.query.reset === 'true') {
    successMessage.value = 'Password reset successfully! Please sign in with your new password.'
  }

  // Load remembered user ID if exists
  const rememberedEmail = localStorage.getItem('rememberedEmail')
  if (rememberedEmail) {
    formData.email = rememberedEmail
    formData.rememberMe = true
  }
})

// Validation
const validateForm = (): boolean => {
  let isValid = true

  // Clear previous errors
  validationErrors.email = ''
  validationErrors.password = ''
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  // User ID validation
  if (!formData.email.trim()) {
    validationErrors.email = 'Email is required'
    isValid = false
  } else if (!emailRegex.test(formData.email)) {
    validationErrors.email = 'Please enter valid email address (e.g., user@example.com)'
    isValid = false
  }

  // Password validation
  if (!formData.password) {
    validationErrors.password = 'Password is required'
    isValid = false
  } else if (formData.password.length < 6) {
    validationErrors.password = 'Password must be at least 6 characters'
    isValid = false
  }

  return isValid
}

// Handle form submission
const handleSubmit = async () => {
  if (!validateForm()) {
    emit('error', 'Please fix the form errors')
    return
  }

  emit('submit', formData)
}
</script>

<style scoped>
/* Custom styles */
:deep(.p-card) {
  border-radius: 1rem;
}

:deep(.p-card .p-card-title) {
  font-size: 1.5rem;
  font-weight: 700;
}

:deep(.p-card .p-card-content) {
  padding: 1.5rem 0;
}

:deep(.p-inputgroup-addon) {
  background: #f3f4f6;
  border-color: #d1d5db;
  color: #6b7280;
}

:deep(.p-button) {
  padding: 0.75rem 1.5rem;
  font-weight: 500;
}

:deep(.p-password-input) {
  width: 100%;
}
</style>