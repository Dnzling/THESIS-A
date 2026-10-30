<template>
  <Toast />
  <div class="min-h-screen bg-white">
    <div class="grid min-h-screen w-full bg-white lg:grid-cols-2">
        <div class="order-2 flex flex-col justify-center px-5 py-8 sm:p-8 lg:order-1 lg:p-12">
          <div class="mb-5 text-center sm:mb-8">
            <div class="portal-title text-xl font-bold text-orange-500 sm:text-2xl">Furnisync Shop</div>
            <p class="mt-1 text-sm text-slate-500 sm:mt-2 sm:text-lg">Log in to continue shopping in 3D</p>
          </div>

          <Message v-if="status" severity="success" :closable="false" class="mb-5">
            {{ status }}
          </Message>
  
          <form class="mx-auto w-full max-w-md space-y-4 sm:space-y-6" @submit.prevent="submitLogin">
            <div class="space-y-1.5 sm:space-y-2">
              <label class="text-sm font-semibold text-slate-900 sm:text-base">Email</label>
              <InputText v-model="form.login" fluid placeholder="Email address"
                class="px-3 py-2.5 text-sm sm:px-4 sm:py-3 sm:text-base" autocomplete="username" />
            </div>
  
            <div class="space-y-1.5 sm:space-y-2">
              <label class="text-sm font-semibold text-slate-900 sm:text-base">Password</label>
              <Password v-model="form.password" fluid :feedback="false" toggleMask placeholder="Password"
                inputClass="rounded-xl border border-slate-300 px-3 py-2.5 text-sm sm:px-4 sm:py-3 sm:text-base"
                autocomplete="current-password" />
            </div>
  
            <div class="flex flex-row flex-wrap items-center justify-between gap-2">
              <div class="flex items-center gap-2 text-sm text-slate-700 sm:text-base">
                <Checkbox v-model="rememberMe" inputId="rememberCustomer" :binary="true" />
                <label for="rememberCustomer">Remember me</label>
              </div>
              <Link href="/customer/forgot-password" class="text-sm font-medium text-orange-600 hover:text-orange-700 hover:underline">
              Forgot password?
              </Link>
            </div>
  
            <Button type="submit" :loading="isSubmitting" fluid severity="warn"
              class="text-sm font-semibold text-white sm:py-3 sm:text-base">
              Log In
            </Button>
  
            <p class="text-center text-sm text-slate-600 sm:text-base">
              No account yet?
              <button type="button" @click="router.visit('/customer/register')" label="Register Here" 
                class="font-semibold text-orange-500 hover:underline">Register Here</button>
            </p>
            <p class="text-center text-xs text-slate-500">
              <button type="button" class="hover:text-orange-600 hover:underline" @click="openLegal('terms')">Terms</button>
              <span class="px-1">·</span>
              <button type="button" class="hover:text-orange-600 hover:underline" @click="openLegal('privacy')">Privacy Policy</button>
            </p>
          </form>
        </div>
  
        <div class="order-2 hidden min-h-screen lg:order-2 lg:block">
          <CustomerAuth3DHero class="h-full" title="Welcome Back!"
            subtitle="Spin, zoom, and inspect your furniture picks before you check out."
            footer="Continue your immersive shopping journey with Furnisync." />
        </div>
        <section class="order-1 bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-5 text-white lg:hidden">
          <h2 class="text-2xl font-semibold">Welcome Back!</h2>
          <p class="mt-2 max-w-sm text-sm leading-6 text-orange-50">Spin, zoom, and inspect your furniture picks before you check out.</p>
        </section>
    </div>
  </div>

  <Dialog v-model:visible="isSubmitting" modal :closable="false" :showHeader="false" :style="{ width: '350px' }">
    <div class="flex flex-col items-center justify-center p-6">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" fill="transparent" animationDuration=".5s" />
      <p class="mt-4 text-lg font-medium text-gray-700">Logging you in...</p>
      <p class="text-gray-500">Please wait a moment</p>
    </div>
  </Dialog>

  <Dialog v-model:visible="legalVisible" modal :header="legalTitle" class="w-[min(48rem,95vw)]">
    <div class="max-h-[70vh] overflow-y-auto pr-2"><EcommerceLegalContent :type="legalType" /></div>
    <template #footer><Button label="Close" severity="secondary" outlined @click="legalVisible = false" /></template>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/auth'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import Dialog from 'primevue/dialog'
import ProgressSpinner from 'primevue/progressspinner'
import Message from 'primevue/message'
import CustomerAuth3DHero from '@/Components/auth/CustomerAuth3DHero.vue'
import EcommerceLegalContent from '@/Components/ecommerce/EcommerceLegalContent.vue'

defineProps<{ status?: string | null }>()

const page = usePage()
const toast = useToast()
const authStore = useAuthStore()

const isSubmitting = ref(false)
const rememberMe = ref(false)
const legalVisible = ref(false)
const legalType = ref<'terms' | 'privacy'>('terms')
const legalTitle = computed(() => legalType.value === 'terms' ? 'Terms and Conditions' : 'Privacy Policy')
const openLegal = (type: 'terms' | 'privacy') => { legalType.value = type; legalVisible.value = true }
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
    await authStore.login(form.login, form.password, '/api/auth/customer/login')

    if (rememberMe.value) {
      localStorage.setItem('rememberedCustomerLogin', form.login)
    } else {
      localStorage.removeItem('rememberedCustomerLogin')
    }

    toast.add({ severity: 'success', summary: 'Welcome back', detail: 'Signed in successfully.', life: 1800 })
    const redirect = getQueryParam('redirect') || '/shop'
    router.visit(redirect)
  } catch (error: any) {
    // Prefer validation errors if present
    const resp = error?.response?.data || null
    let detail = 'Check your credentials.'
    if (resp) {
      if (resp.errors) {
        // flatten validation errors
        const parts: string[] = []
        Object.keys(resp.errors).forEach(k => {
          const v = resp.errors[k]
          if (Array.isArray(v)) parts.push(...v)
          else if (typeof v === 'string') parts.push(v)
        })
        if (parts.length) detail = parts.join(' ')
      } else if (resp.message) {
        detail = resp.message
      }
    }
    toast.add({ severity: 'error', summary: 'Login failed', detail, life: 6000 })
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
