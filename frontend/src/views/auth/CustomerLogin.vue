<template>
  <div class="relative min-h-screen bg-[#f2f3f7] px-30 py-5 sm:px-30 sm:py-8">
    <div
      class="mx-auto mt-8 w-full max-w-155 rounded-[26px] bg-white/70 p-0 shadow-sm sm:mt-12 sm:rounded-[44px]">
      <div class="rounded-[22px] bg-white px-4 py-7 sm:rounded-[40px] sm:px-8 sm:py-12 md:px-12">
        <div class="mb-6 text-center sm:mb-8">
          <div class="flex items-center justify-center rounded-lg">
            <img src="/Furnishop.png" alt="Furni Shop" class="w-auto object-contain sm:w-28 md:w-100 " />
          </div>
          <p class=" text-sm text-slate-500 sm:mt-2 sm:text-lg">Log In to continue</p>
        </div>
  
        <form class="space-y-5 sm:space-y-6" @submit.prevent="submitLogin">
          <div class="space-y-2">
            <label class="text-base font-semibold text-slate-900 sm:text-xl">Email</label>
            <InputText
              v-model="form.login"
              fluid
              placeholder="Email address"
              class="px-3 py-2.5 text-base sm:px-4 sm:py-3 sm:text-lg"
              autocomplete="username"
            />
          </div>
  
          <div class="space-y-2">
            <label class="text-base font-semibold text-slate-900 sm:text-xl">Password</label>
            <Password
              v-model="form.password"
              fluid
              :feedback="false"
              toggleMask
              placeholder="Password"
              inputClass="rounded-xl border border-slate-300 px-3 py-2.5 text-base sm:px-4 sm:py-3 sm:text-lg"
              autocomplete="current-password"
            />
          </div>
  
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2 text-sm text-slate-700 sm:text-base">
              <Checkbox v-model="rememberMe" inputId="rememberCustomer" :binary="true" />
              <label for="rememberCustomer">Remember me</label>
            </div>
            <Button to="/forgot-password" text severity="info" class="text-sm font-medium">
              Forgot password?</Button>
          </div>
  
          <Button type="submit" :loading="isSubmitting" fluid severity="info"
            class="text-base font-semibold text-white sm:py-3 sm:text-xl">
            Log In
          </Button>

          <p class="text-center text-sm text-slate-600 sm:text-base">
            No account yet?
            <Button   @click="router.push({ name: 'customer.register' })" label="Register Here" link severity="info" class="font-semibold text-[#6d5efc] hover:underline"/>
          </p>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()

const isSubmitting = ref(false)
const rememberMe = ref(false)
const form = reactive({
  login: '',
  password: '',
})

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
    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/shop'
    router.push(redirect)
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
