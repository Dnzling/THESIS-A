<template>
  <RegisterForm :is-submitting="isSubmitting" @submit="handleRegister" @error="handleFormError" />
</template>

<script setup lang="ts">
import axios from 'axios';
import { ref } from 'vue';
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import { RegisterFormData } from '../../components/auth/RegisterForm.vue'
import RegisterForm from '../../components/auth/RegisterForm.vue';

const toast = useToast()
const router = useRouter()
const isSubmitting = ref(false)

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
    router.push('/verify-otp')

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