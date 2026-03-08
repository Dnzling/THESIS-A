<template>
  <LoginForm 
    :is-submitting="isSubmitting" 
    @submit="handleLogin" 
    @error="handleFormError" 
  />
</template>

<script setup lang="ts">
import LoginForm from '../../components/auth/LoginForm.vue'
import { ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRoute, useRouter } from 'vue-router'
import { LoginFormData } from '../../components/auth/LoginForm.vue'
import { useAuthStore } from '../../stores/auth'

const route = useRoute()
const toast = useToast()
const router = useRouter()
const authStore = useAuthStore()
const isSubmitting = ref(false)

const handleLogin = async (formData: LoginFormData) => {
  if (isSubmitting.value) {
    console.log('⏸️ Already submitting, ignoring...')
    return
  }

  isSubmitting.value = true

  try {
    // Let authStore handle the entire login process
    await authStore.login(formData.email, formData.password)

    console.log('✅ Login successful')
    console.log('User role:', authStore.user?.role)
    console.log('User:', authStore.user)

    // ✅ Show success toast
    toast.add({
      severity: 'success',
      summary: 'Login Successful!',
      detail: 'Redirecting to dashboard...',
      life: 2000
    })

    // ✅ Role-based routing (using authStore.defaultRoute)
    let redirectTo = authStore.defaultRoute

    // Override with query redirect if available
    if (route.query.redirect && typeof route.query.redirect === 'string') {
      redirectTo = route.query.redirect
    }

    // ✅ Single redirect with delay (for toast to show)
    setTimeout(() => {
      router.push(redirectTo)
    }, 500) // Reduced from 1500ms

  } catch (error: any) {
    console.error('❌ Login error:', error)

    // Handle validation errors
    if (error.response?.status === 422) {
      const errors = error.response.data?.errors

      if (errors && Object.keys(errors).length > 0) {
        const firstError = Object.values(errors)[0] as string[]
        toast.add({
          severity: 'error',
          summary: 'Validation Error',
          detail: firstError[0],
          life: 5000
        })
      }
    } else {
      // General error
      toast.add({
        severity: 'error',
        summary: 'Login Failed',
        detail: error.response?.data?.message || error.message || 'Something went wrong. Please try again.',
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