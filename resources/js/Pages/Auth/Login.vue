<template>
  <Toast />
  <Head title="Login">
    <meta name="robots" content="noindex, nofollow" />
  </Head>
  <LoginForm 
    :is-submitting="isSubmitting" 
    @submit="handleLogin" 
    @error="handleFormError" 
  />
  <Dialog v-model:visible="showAttendanceDialog" modal :closable="false" header="Welcome to work" :style="{ width: 'min(26rem, 94vw)' }">
    <p class="text-sm text-slate-700">Hello {{ employeeName }}, {{ attendanceMessage }}</p>
    <p v-if="lateMessage" class="mt-3 text-sm font-medium text-orange-700">{{ lateMessage }}</p>
    <template #footer><Button label="Continue" severity="warn" size="small" @click="continueAfterLogin" /></template>
  </Dialog>
</template>

<script setup lang="ts">
import LoginForm from '@/Components/auth/LoginForm.vue'
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import { Head, router } from '@inertiajs/vue3'
import { LoginFormData } from '@/Components/auth/LoginForm.vue'
import { useAuthStore } from '@/stores/auth'
import axios from '@/axios'

const toast = useToast()
const authStore = useAuthStore()
const isSubmitting = ref(false)
const showAttendanceDialog = ref(false)
const employeeName = ref('')
const attendanceMessage = ref('')
const lateMessage = ref('')
const nextRoute = ref('')
const continueAfterLogin = () => { showAttendanceDialog.value = false; router.visit(nextRoute.value) }
const formatLate = (seconds: number) => {
  if (seconds <= 0) return ''
  if (seconds < 60) return 'Less than a minute late.'
  const minutes = Math.ceil(seconds / 60)
  const hours = Math.floor(minutes / 60)
  const remaining = minutes % 60
  return `You are late by ${hours ? `${hours} hour${hours === 1 ? '' : 's'}` : ''}${hours && remaining ? ' and ' : ''}${remaining ? `${remaining} minute${remaining === 1 ? '' : 's'}` : ''}.`
}

onMounted(async () => {
  if (authStore.isAuthenticated) {
    if (authStore.user?.role !== 'super_admin') {
      await authStore.loadPermissions() // ensure navigation is fresh
    }
    router.visit(authStore.getFirstNavigationRoute())
  }
})

const handleLogin = async (formData: LoginFormData) => {
  if (isSubmitting.value) {
    console.log('⏸️ Already submitting, ignoring...')
    return
  }

  isSubmitting.value = true

  try {
    // Let authStore handle the entire login process
    const loginResponse = await authStore.login(formData.login, formData.password)
    // Login already loads permissions; avoid requesting the navigation endpoint twice.
    await authStore.fetchCurrentUser()

    // console.log('✅ Login successful')
    // console.log('User role:', authStore.user?.role)
    // console.log('User:', authStore.user)

    const employeeAttendance = loginResponse.data?.data?.employee_attendance

    const hasStore = Boolean(
      authStore.currentUser?.store_id ||
      (authStore.currentUser as any)?.store?.id ||
      authStore.user?.store_id ||
      (authStore.user as any)?.store?.id
    )
    const isCustomerRole =
      String(authStore.user?.role || '').toLowerCase().includes('customer') ||
      String((authStore.user as any)?.display_role || '').toLowerCase().includes('customer')

    if (isCustomerRole) {
      router.visit('/shop')
      return
    }

    const isSupplierRole = String(authStore.user?.role || '').toLowerCase() === 'supplier'
    const isDriverRole = String(authStore.user?.role || '').toLowerCase() === 'driver'
    if (isDriverRole) {
      router.visit('/driver/deliveries')
      return
    }
    if (isSupplierRole) {
      try {
        await axios.get('/api/supplier-portal/my-portal')
        router.visit('/supplier-portal/dashboard')
        return
      } catch (error: any) {
        if (error?.response?.status === 404) {
          router.visit('/supplier-portal/registration')
          return
        }
        throw error
      }
    }

    let redirectTo = '/store/registration'

    if (hasStore) {
      // Users with a store continue into the system; others complete store registration first.
      redirectTo = authStore.getFirstNavigationRoute()
    }

    // Navigate immediately after authentication instead of adding an artificial delay.
    if (employeeAttendance) {
      employeeName.value = String(authStore.user?.fname || (authStore.user as any)?.first_name || 'employee')
      attendanceMessage.value = employeeAttendance.state === 'clocked_in' ? 'you are now clocked in.'
        : employeeAttendance.state === 'already_clocked_in' ? 'you are already clocked in.'
        : employeeAttendance.state === 'completed' ? 'your attendance is complete for today.'
        : employeeAttendance.state === 'not_recorded' ? employeeAttendance.message || 'attendance could not be recorded.'
        : 'your attendance was already recorded today.'
      lateMessage.value = ['clocked_in', 'already_clocked_in'].includes(employeeAttendance.state) ? formatLate(Number(employeeAttendance.late_seconds || 0)) : ''
      nextRoute.value = redirectTo
      showAttendanceDialog.value = true
      return
    }
    router.visit(redirectTo)

  } catch (error: any) {
    console.error('❌ Login error:', error)

    const requiresVerification = Boolean(error?.response?.data?.requires_verification)
    if (requiresVerification) {
      const verificationToken = error?.response?.data?.access_token
      const verificationEmail = error?.response?.data?.email || formData.login
      const verificationRole = String(error?.response?.data?.role || '').toLowerCase()

      if (verificationToken) {
        localStorage.setItem('register_token', verificationToken)
      }
      localStorage.setItem('otp_context', verificationRole || 'saas')

      toast.add({
        severity: 'info',
        summary: 'Email Verification Required',
        detail: `Enter the OTP sent to ${verificationEmail}.`,
        life: 3500
      })

      setTimeout(() => {
        router.visit('/verify-otp')
      }, 500)
      return
    }

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
