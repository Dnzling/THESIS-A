<template>
  <Toast />
  <RegisterForm :is-submitting="isSubmitting" @submit="handleRegister" @error="handleFormError" />
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
const getQueryParam = (key: string): string | null => {
  const query = String(page.url || '').split('?')[1] || ''
  return new URLSearchParams(query).get(key)
}

// Handle form submission ===== for API =====
const handleRegister = async (formData: RegisterFormData) => {

  isSubmitting.value = true
  try {
    // await axios.get('/sanctum/csrf-cookie')

    const response = await axios.post('/api/auth/register', {
      fname: formData.fname,
      lname: formData.lname,
      email: formData.email,
      password: formData.password,
      role_id: 2,
      birthday: formData.birthday,
      device_name: 'web-browswer'
    })

    // Success
    localStorage.setItem('register_token', response.data.user.access_token)
    const role = String(response.data.user?.role || '').toLowerCase()
    localStorage.setItem('otp_context', role || 'saas')

    localStorage.removeItem('selected_subscription_plan')
    localStorage.removeItem('subscription_flow')
    localStorage.removeItem('pending_subscription_plan')
    localStorage.removeItem('pending_subscription_flow')
    localStorage.removeItem('post_otp_redirect')
    localStorage.setItem('onboarding_next_step', 'login')

    router.visit('/verify-otp')

  } catch (error: any) {
    console.error('Registration error:', error)

    // Handle validation errors
    if (error.response?.status === 422) {
      const errors = error.response.data?.errors

      // Show first error in toast
      if (errors && Object.keys(errors).length > 0) {
        const firstError = Object.values(errors)[0][0]
        toast.add({
          severity: 'error',
          summary: 'Validation Error',
          detail: firstError,
          life: 5000
        })
      }
    } else {
      // General error
      toast.add({
        severity: 'error',
        summary: 'Registration Failed',
        detail: error.response?.data?.message || 'Something went wrong. Please try again.',
        life: 5000
      })
    }
    throw error
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
