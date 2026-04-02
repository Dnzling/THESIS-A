<template>
  <Toast />
  <div class="min-h-screen bg-slate-50">
    <div class="h-screen w-full bg-white">
      <div class="grid h-full gap-0 lg:grid-cols-2">
        <div class="flex flex-col justify-center p-8 lg:p-12">
          <div class="mb-6 text-center sm:mb-8">
            <div class="portal-title text-orange-500 text-2xl font-bold">Furnisync Shop</div>
            <p class="text-sm text-slate-500 sm:mt-2 sm:text-lg">Log in to continue shopping in 3D</p>
          </div>
  
          <form class="space-y-5 sm:space-y-6" @submit.prevent="submitLogin">
            <div class="space-y-2">
              <label class="text-base font-semibold text-slate-900 sm:text-xl">Email</label>
              <InputText v-model="form.login" fluid placeholder="Email address"
                class="px-3 py-2.5 text-base sm:px-4 sm:py-3 sm:text-lg" autocomplete="username" />
            </div>
  
            <div class="space-y-2">
              <label class="text-base font-semibold text-slate-900 sm:text-xl">Password</label>
              <Password v-model="form.password" fluid :feedback="false" toggleMask placeholder="Password"
                inputClass="rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:px-4 sm:py-3 sm:text-lg"
                autocomplete="current-password" />
            </div>
  
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex items-center gap-2 text-sm text-slate-700 sm:text-base">
                <Checkbox v-model="rememberMe" inputId="rememberCustomer" :binary="true" />
                <label for="rememberCustomer">Remember me</label>
              </div>
              <Link href="/forgot-password" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline">
              Forgot password?
              </Link>
            </div>
  
            <Button type="submit" :loading="isSubmitting" fluid severity="warn"
              class="text-base font-semibold text-white sm:py-3 sm:text-xl">
              Log In
            </Button>
  
            <p class="text-center text-sm text-slate-600 sm:text-base">
              No account yet?
              <button type="button" @click="router.visit('/customer/register')" label="Register Here" 
                class="font-semibold text-orange-500 hover:underline">Register Here</button>
            </p>
          </form>
        </div>
  
        <CustomerAuth3DHero class="h-full" title="Welcome Back!"
          subtitle="Spin, zoom, and inspect your furniture picks before you check out."
          footer="Continue your immersive shopping journey with Furnisync." />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import CustomerAuth3DHero from '@/Components/auth/CustomerAuth3DHero.vue'

const page = usePage()
const toast = useToast()
const authStore = useAuthStore()

const isSubmitting = ref(false)
const rememberMe = ref(false)
const form = reactive({
  login: '',
  password: '',
})

const getQueryParam = (key: string): string | null => {
  const query = String(page.url || '').split('?')[1] || ''
  return new URLSearchParams(query).get(key)
}

async function submitLogin() {
  if (isSubmitting.value) return
  if (!form.login || !form.password) {
    toast.add({ severity: 'warn', summary: 'Missing fields', detail: 'Please enter email and password.', life: 2500 })
    return
  }

  isSubmitting.value = true
  try {
    await authStore.login(form.login, form.password)
    if (!authStore.isCustomer) {
      await authStore.logout()
      toast.add({ severity: 'error', summary: 'Access denied', detail: 'Customer access only.', life: 3500 })
      return
    }

    if (rememberMe.value) {
      localStorage.setItem('rememberedCustomerLogin', form.login)
    } else {
      localStorage.removeItem('rememberedCustomerLogin')
    }

    toast.add({ severity: 'success', summary: 'Welcome back', detail: 'Signed in successfully.', life: 1800 })
    const redirect = getQueryParam('redirect') || '/shop'
    router.visit(redirect)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Login failed', detail: error?.response?.data?.message || 'Check your credentials.', life: 3500 })
  } finally {
    isSubmitting.value = false
  }
}

const remembered = localStorage.getItem('rememberedCustomerLogin')
if (remembered) {
  form.login = remembered
  rememberMe.value = true
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap');

.portal-title {
  font-family: 'Space Grotesk', sans-serif;
}

:deep(.p-password-input) {
  width: 100% !important;
}

:deep(.p-password .p-inputtext) {
  width: 100% !important;
}

:deep(.p-checkbox .p-checkbox-box) {
  border-radius: 6px;
}
</style>
