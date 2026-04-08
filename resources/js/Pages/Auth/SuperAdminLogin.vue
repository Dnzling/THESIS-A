<template>
  <Toast />
  <Head title="Super Admin Login">
    <meta name="robots" content="noindex, nofollow" />
  </Head>
  <div class="min-h-screen bg-slate-950 px-4 py-10 text-slate-100 flex items-center justify-center">
    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-slate-900/70 p-8 shadow-2xl backdrop-blur">
      <div class="mb-8 text-center">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10">
          <i class="pi pi-shield text-xl text-white/80"></i>
        </div>
        <h1 class="text-2xl font-semibold tracking-tight">Platform Admin</h1>
        <p class="mt-2 text-sm text-slate-400">Restricted access for system administrators</p>
      </div>

      <form class="space-y-5" @submit.prevent="handleLogin">
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-300">Email or ID</label>
          <InputText
            v-model="form.login"
            placeholder="admin@company.com"
            autocomplete="username"
            class="w-full bg-slate-900/60 border border-white/10 text-slate-100"
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-300">Password</label>
          <Password
            v-model="form.password"
            :feedback="false"
            toggleMask fluid
            placeholder="Enter your password"
            inputClass="w-full bg-slate-900/60 border border-white/10 text-slate-100"
          />
        </div>

        <Button type="submit" :loading="isSubmitting" fluid severity="contrast">
          Log in
        </Button>
      </form>

      <p class="mt-6 text-center text-xs text-slate-500">
        This area is monitored. Unauthorized access is prohibited.
      </p>
    </div>
  </div>

  <!-- Loading Dialog -->
  <Dialog 
    v-model:visible="showLoadingDialog" 
    :closable="false" 
    :closeOnEscape="false"
    :showHeader="false"
    modal
    class="!w-auto"
  >
    <div class="flex flex-col items-center justify-center p-6 rounded-lg">
      <ProgressSpinner 
        strokeWidth="4" 
        animationDuration=".8s" 
        class="w-12 h-12 mb-4"
      />
      <p class="text-violet-600 text-sm font-medium">Authenticating...</p>
      <p class="text-violet-800 text-xs mt-2">Please wait while we verify your credentials</p>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import { Head, router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'

const toast = useToast()
const authStore = useAuthStore()
const isSubmitting = ref(false)
const showLoadingDialog = ref(false)
const form = reactive({
  login: '',
  password: '',
})

const isSuperAdmin = (): boolean => {
  const role = String(authStore.user?.role || '').toLowerCase()
  return role === 'super_admin'
}

const handleLogin = async () => {
  if (isSubmitting.value) return
  isSubmitting.value = true
  showLoadingDialog.value = true

  try {
    if (!form.login || !form.password) {
      toast.add({
        severity: 'warn',
        summary: 'Missing fields',
        detail: 'Please enter your credentials.',
        life: 2500
      })
      return
    }

    await authStore.login(form.login, form.password)

    if (!isSuperAdmin()) {
      await authStore.logout({ redirect: false })
      toast.add({
        severity: 'error',
        summary: 'Access denied',
        detail: 'Super Admin access only.',
        life: 4000
      })
      return
    }

    toast.add({
      severity: 'success',
      summary: 'Login Successful!',
      detail: 'Redirecting to admin dashboard...',
      life: 2000
    })

    setTimeout(() => {
      router.visit('/admin/dashboard')
    }, 500)
  } catch (error: any) {
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
        return
      }
    }

    toast.add({
      severity: 'error',
      summary: 'Login Failed',
      detail: error.response?.data?.message || error.message || 'Something went wrong. Please try again.',
      life: 5000
    })
  } finally {
    isSubmitting.value = false
    showLoadingDialog.value = false
  }
}
</script>