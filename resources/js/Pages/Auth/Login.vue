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
</template>

<script setup lang="ts">
import LoginForm from '@/Components/auth/LoginForm.vue'
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import { Head, router, usePage } from '@inertiajs/vue3'
import { LoginFormData } from '@/Components/auth/LoginForm.vue'
import { useAuthStore } from '@/stores/auth'

const page = usePage()
const toast = useToast()
const authStore = useAuthStore()
const isSubmitting = ref(false)

const getFirstAvailableRoute = (): string => {
  const normalizedRole = String(authStore.user?.role || '').toLowerCase()
  const displayRole = String((authStore.user as any)?.display_role || '').toLowerCase()

  if (normalizedRole.includes('customer') || displayRole.includes('customer')) {
    return '/shop'
  }

  if (normalizedRole === 'super_admin') return '/admin/dashboard'
  if (normalizedRole === 'supplier') return '/supplier-portal/dashboard'

  const items = authStore.navigation
    .filter((item: any) => item.is_active && item.route_path && !item.meta?.is_group && !item.route_path.startsWith('#'))
    .sort((a: any, b: any) => (a.display_order || 0) - (b.display_order || 0))
  if (items.length) {
    return items[0].route_path
  }
  return authStore.defaultRoute || '/store/index'
}

onMounted(async () => {
  if (authStore.isAuthenticated) {
    if (authStore.user?.role !== 'super_admin') {
      await authStore.loadPermissions() // ensure navigation is fresh
    }
    router.visit(getFirstAvailableRoute())
  }
})

const getQueryParam = (key: string): string | null => {
  const query = String(page.url || '').split('?')[1] || ''
  return new URLSearchParams(query).get(key)
}

const handleLogin = async (formData: LoginFormData) => {
  if (isSubmitting.value) {
    console.log('⏸️ Already submitting, ignoring...')
    return
  }

  isSubmitting.value = true

  try {
    // Let authStore handle the entire login process
    await authStore.login(formData.login, formData.password)

    // console.log('✅ Login successful')
    // console.log('User role:', authStore.user?.role)
    // console.log('User:', authStore.user)

    // ✅ Show success toast
    toast.add({
      severity: 'success',
      summary: 'Login Successful!',
      detail: 'Redirecting to dashboard...',
      life: 2000
    })

    // ✅ Default routing (SystemLayout)
    let redirectTo = getFirstAvailableRoute()

    // Override with query redirect if available
    const redirectParam = getQueryParam('redirect')
    const isCustomerRole =
      String(authStore.user?.role || '').toLowerCase().includes('customer') ||
      String((authStore.user as any)?.display_role || '').toLowerCase().includes('customer')

    // For customer logins, always land on /shop first to avoid auth loop back to /customer/login.
    if (!isCustomerRole && redirectParam) {
      redirectTo = redirectParam
    }

    // ✅ Single redirect with delay (for toast to show)
    setTimeout(() => {
      router.visit(redirectTo)
    }, 500) // Reduced from 1500ms

  } catch (error: any) {
    console.error('❌ Login error:', error)

    const requiresVerification = Boolean(error?.response?.data?.requires_verification)
    if (requiresVerification) {
      const verificationToken = error?.response?.data?.access_token
      const verificationEmail = error?.response?.data?.email || formData.login

      if (verificationToken) {
        localStorage.setItem('register_token', verificationToken)
      }
      localStorage.setItem('otp_context', 'saas')

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
