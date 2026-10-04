<template>
  <Toast />
  <RegisterForm :is-submitting="isSubmitting" :server-errors="serverErrors" @submit="handleRegister" @error="handleFormError" />
</template>

<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import { router, usePage } from '@inertiajs/vue3'
import { RegisterFormData } from '@/Components/auth/RegisterForm.vue'
import RegisterForm from '@/Components/auth/RegisterForm.vue';

const toast = useToast()
const page = usePage()
const isSubmitting = ref(false)
const serverErrors = ref<Record<string, string>>({})
const getQueryParam = (key: string): string | null => {
  const query = String(page.url || '').split('?')[1] || ''
  return new URLSearchParams(query).get(key)
}

// Handle form submission ===== for API =====
const handleRegister = async (formData: RegisterFormData) => {

  isSubmitting.value = true
  serverErrors.value = {}
  try {
    // await axios.get('/sanctum/csrf-cookie')

    const response = await axios.post('/api/auth/register', {
      fname: formData.fname,
      lname: formData.lname,
      email: formData.email,
      password: formData.password,
      // Resolve the system role by name server-side; numeric role IDs vary by DB.
      account_type: 'owner',
      birthday: formData.birthday ? new Date(formData.birthday).toISOString().slice(0, 10) : null,
      device_name: 'web-browser'
    })

    // Success
    localStorage.setItem('register_token', response.data.user.access_token)
    localStorage.setItem('otp_context', 'business')

    localStorage.removeItem('selected_subscription_plan')
    localStorage.removeItem('subscription_flow')
    localStorage.removeItem('pending_subscription_plan')
    localStorage.removeItem('pending_subscription_flow')
    localStorage.removeItem('post_otp_redirect')
    localStorage.setItem('onboarding_next_step', 'login')

    router.visit('/verify-otp')

  } catch (error: any) {
    console.error('Registration error:', error.response?.data ?? error)

    // Handle validation errors
    if (error.response?.status === 422) {
      const errors = error.response.data?.errors as Record<string, string[]> | undefined

      // Show first error in toast
      if (errors && Object.keys(errors).length > 0) {
        serverErrors.value = Object.fromEntries(
          Object.entries(errors).map(([field, messages]) => [field, messages[0] ?? 'Invalid value.'])
        )
        const firstError = Object.values(serverErrors.value)[0]
        toast.add({
          severity: 'error',
          summary: 'Validation Error',
          detail: firstError,
          life: 5000
        })
      } else {
        toast.add({
          severity: 'error',
          summary: 'Registration Failed',
          detail: error.response.data?.message || 'Please check your details and try again.',
          life: 5000
        })
      }
    } else {
      // General error
      toast.add({
        severity: 'error',
        summary: 'Registration Failed',
        detail: error.response?.data?.error || error.response?.data?.message || 'Something went wrong. Please try again.',
        life: 5000
      })
    }
  } finally {
    isSubmitting.value = false
  }
}

const handleFormError = (errorMessage: string) => {
  toast.add({
    severity: 'warn',
    summary: 'Form Error',
    detail: errorMessage,
    life: 3000
  })
}
</script>
