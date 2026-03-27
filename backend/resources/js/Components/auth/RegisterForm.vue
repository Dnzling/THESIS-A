<template>
    <div class="min-h-screen flex items-center justify-center bg-linear-to-br from-blue-50 to-indigo-100 p-4">
        <Card class="w-full max-w-md shadow-2xl">
            <template #title>
                <div class="flex items-center justify-left mb-2 mt-3">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Register</h1>
                        <p class="text-gray-400 mt-1 text-sm font-medium">Create your account to start</p>
                    </div>
                </div>
            </template>
    
            <template #content>
                <!-- Error Message -->
                <Message v-if="errorMessage" severity="error" :closable="true" @close="errorMessage = ''" class="mb-6">
                    {{ errorMessage }}
                </Message>
    
                <!-- Success Message (for registration redirect) -->
                <Message v-if="successMessage" severity="success" :closable="true" @close="successMessage = ''"
                    class="mb-6">
                    {{ successMessage }}
                </Message>
    
                <!-- Login Form -->
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-4">
                            <!-- First Name -->
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700">
                                    Firstname <span class="text-red-500">*</span>
                                </label>
                                <InputText v-model="formData.fname" placeholder="ex. Juan"
                                    :class="{ 'p-invalid': validationErrors.fname }" class="w-full mt-1" />
                                <small v-if="validationErrors.fname" class="p-error">
                                    {{ validationErrors.fname }}
                                </small>
                            </div>
    
                            <!-- Last Name -->
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700">
                                    Lastname <span class="text-red-500">*</span>
                                </label>
                                <InputText v-model="formData.lname" placeholder="Dela Cruz"
                                    :class="{ 'p-invalid': validationErrors.lname }" class="w-full mt-1" />
                                <small v-if="validationErrors.lname" class="p-error">
                                    {{ validationErrors.lname }}
                                </small>
                            </div>
                        </div>
                    </div>
    
                    <!-- Email Field -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email <span class="text-red-500">*</span>
                            </label>
    
                        </div>
                        <div class="p-inputgroup">
                            <InputText id="email" type="email" v-model="formData.email" placeholder="email@example.com"
                                :class="{ 'p-invalid': validationErrors.email }" class="w-full" />
                        </div>
                        <small v-if="validationErrors.email" class="p-error">
                            {{ validationErrors.email }}
                        </small>
                    </div>
    
                    <div class="space-y-2">
                        <label for="birthday" class="block text-sm font-medium text-gray-700">
                            Birthday <span class="text-red-500">*</span>
                        </label>
    
                        <DatePicker id="birthday" v-model="formData.birthday"
                            :class="{ 'p-invalid': validationErrors.birthday }" class="w-full" placeholder="MM/DD/YYYY"
                            dateFormat="mm/dd/yy" :showIcon="true" iconDisplay="input" :maxDate="maxDate" :minDate="minDate"
                            showButtonBar inputClass="w-full" />
    
                        <small v-if="validationErrors.birthday" class="p-error">
                            {{ validationErrors.birthday }}
                        </small>
                    </div>
    
                    <!-- Password Field -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Password <span class="text-red-500">*</span>
                            </label>
    
                        </div>
                        <div class="p-inputgroup">
                            <Password id="password" v-model="formData.password" :feedback="false" toggleMask
                                placeholder="Enter your password" :class="{ 'p-invalid': validationErrors.password }"
                                class="w-full" autocomplete="new-password" />
                        </div>
                        <small v-if="validationErrors.password" class="p-error">
                            {{ validationErrors.password }}
                        </small>
                    </div>
    
                    <!-- Confirm Password -->
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Confirm Password <span class="text-red-500">*</span>
                            </label>
    
                        </div>
                        <div class="p-inputgroup">
                            <Password id="confirmPassword" v-model="formData.confirmPassword" :feedback="false" toggleMask
                                placeholder="Re-enter your password"
                                :class="{ 'p-invalid': validationErrors.confirmPassword }" class="w-full"
                                autocomplete="new-password" />
                        </div>
                        <small v-if="validationErrors.confirmPassword" class="p-error">
                            {{ validationErrors.confirmPassword }}
                        </small>
                    </div>
    
                    <!-- Submit Button -->
                    <Button type="submit" label="Sign Up"  :loading="props.isSubmitting"
                        class="w-full" severity="contrast"
                        :disabled="props.isSubmitting" />
                </form>
            </template>
    
            <template #footer>
                <div class="text-center pt-6 border-t border-gray-100">
                    <p class="text-sm text-gray-500 mt-4">
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
                <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" fill="transparent"
                    animationDuration=".5s" />
                <p class="mt-4 text-lg font-medium text-gray-700">Creating your account...</p>
                <p class="text-gray-500">Please wait a moment</p>
            </div>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed} from 'vue'

import DatePicker from 'primevue/datepicker'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Message from 'primevue/message'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'

const props = defineProps<{
  isSubmitting? :boolean
}>()

const emit = defineEmits<{
  submit: [data: RegisterFormData]
  error: [message: string]
}>()

// Types
interface RegisterFormData {
  fname: string
  lname: string
  email: string
  birthday: Date | null
  password: string
  confirmPassword: string
}

interface ValidationErrors {
  email?: string
  fname?: string
  lname?: string
  birthday?: Date
  password?: string
  confirmPassword?: string
}

// State
const formData = reactive<RegisterFormData>({
  fname: '',
  lname: '',
  email: '',
  password: '',
  confirmPassword: '',
  birthday: null,
})

const validationErrors = reactive<ValidationErrors>({})
const errorMessage = ref<string>('')
const successMessage = ref<string>('')


const maxDate = computed(() => {
  const date = new Date()
  date.setFullYear(date.getFullYear() - 18)
  return date
})

const minDate = computed(() => {
  const date = new Date()
  date.setFullYear(date.getFullYear() - 100)
  return date
})

// Validation
const validateForm = (): boolean => {
  let isValid = true
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

  // Clear previous errors
  validationErrors.fname = ''
  validationErrors.lname = ''
  validationErrors.email = ''
  validationErrors.password = ''
  validationErrors.confirmPassword = ''

  if (!formData.fname.trim()) {
    validationErrors.fname = 'Firstname is required'
    isValid = false
  }

  if (!formData.lname.trim()) {
    validationErrors.lname = 'Lastname is required'
    isValid = false
  }

  // Email validation
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
  } else if (formData.password.length < 8) {
    validationErrors.password = 'Password must be at least 8 characters'
    isValid = false
  }

  if (formData.password != formData.confirmPassword) {
    validationErrors.confirmPassword = 'Please re-type your password'
    isValid = false
  }

  return isValid
}

const handleSubmit = () => {
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