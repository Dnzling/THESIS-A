<template>
  <Toast />
  <div class="min-h-screen bg-white">
    <div class="grid min-h-screen w-full bg-white lg:grid-cols-2">
        <div class="order-2 flex flex-col justify-center px-5 py-8 sm:p-8 lg:order-1 lg:p-12">
          <div class="mb-5 text-center sm:mb-8">
            <div class="portal-title text-xl font-bold text-orange-500 sm:text-2xl">Furnisync Shop</div>
            <p class="mt-1 text-sm text-slate-500 sm:mt-2 sm:text-lg">Create your customer account to start shopping</p>
          </div>
  
          <form class="mx-auto w-full max-w-md space-y-4 sm:space-y-6" @submit.prevent="submitRegister">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
              <div class="space-y-1.5 sm:space-y-2">
                <label class="text-sm font-semibold text-slate-900 sm:text-base">First Name</label>
                <InputText :model-value="form.fname" fluid placeholder="First name" autocomplete="given-name" @update:model-value="form.fname = sanitizeName($event)" />
                <small v-if="validationErrors.fname" class="text-xs text-red-600">{{ validationErrors.fname }}</small>
              </div>
              <div class="space-y-1.5 sm:space-y-2">
                <label class="text-sm font-semibold text-slate-900 sm:text-base">Last Name</label>
                <InputText :model-value="form.lname" fluid placeholder="Last name" autocomplete="family-name" @update:model-value="form.lname = sanitizeName($event)" />
                <small v-if="validationErrors.lname" class="text-xs text-red-600">{{ validationErrors.lname }}</small>
              </div>
            </div>
  
            <div class="space-y-1.5 sm:space-y-2">
              <label class="text-sm font-semibold text-slate-900 sm:text-base">Email</label>
              <InputText v-model="form.email" type="email" fluid placeholder="Email address" autocomplete="email"
                :class="{ 'p-invalid': validationErrors.email }" @blur="validateEmail" @input="validationErrors.email = ''" />
              <small v-if="validationErrors.email" class="text-xs text-red-600">{{ validationErrors.email }}</small>
            </div>
  
            <div class="space-y-1.5 sm:space-y-2">
              <label class="text-sm font-semibold text-slate-900 sm:text-base">Birthday</label>
              <DatePicker v-model="form.birthday" :maxDate="minimumAdultDate" fluid showIcon dateFormat="yy-mm-dd"
                placeholder="Select birthday" />
            </div>
  
  
            <div class="space-y-1.5 sm:space-y-2">
              <label class="text-sm font-semibold text-slate-900 sm:text-base">Password</label>
              <Password v-model="form.password" fluid :feedback="false" autocomplete="new-password"
                name="new_password" toggleMask
                placeholder="Password"
                @focus="passwordFocused = true"
                @blur="passwordFocused = false" />
              <Transition name="password-guide">
                <div v-if="showPasswordRules" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm">
                  <p class="mb-2 font-semibold text-slate-800">Password must include:</p>
                  <ul class="space-y-2">
                    <li
                      v-for="rule in passwordRuleItems"
                      :key="rule.key"
                      class="flex items-center gap-2 transition-colors duration-300"
                      :class="rule.met ? 'text-emerald-700' : 'text-slate-500'"
                    >
                      <span
                        class="flex h-5 w-5 items-center justify-center rounded-full border text-xs font-bold transition-all duration-300"
                        :class="rule.met ? 'scale-100 border-emerald-500 bg-emerald-500 text-white' : 'scale-90 border-slate-300 bg-white text-slate-400'"
                      >
                        <i :class="rule.met ? 'pi pi-check' : 'pi pi-minus'"></i>
                      </span>
                      <span>{{ rule.label }}</span>
                    </li>
                  </ul>
                </div>
              </Transition>
            </div>
            <div class="space-y-1.5 sm:space-y-2">
              <label class="text-sm font-semibold text-slate-900 sm:text-base">Confirm Password</label>
              <Password v-model="form.confirmPassword" autocomplete="new-password"
  name="new_password" fluid :feedback="false" toggleMask
                placeholder="Confirm password" />
            </div>
  
  
            <div class="flex items-start gap-2 text-xs sm:text-sm">
              <Checkbox v-model="acceptedTerms" inputId="acceptedTerms" :binary="true" />
              <div class="leading-6 text-slate-700">
                <label for="acceptedTerms" class="cursor-pointer">I agree to the</label>
                <button type="button" class="font-medium text-orange-500 hover:underline" @click="openLegal('terms')">Terms</button>
                and
                <button type="button" class="font-medium text-orange-500 hover:underline" @click="openLegal('privacy')">Privacy Policy</button>.
              </div>
            </div>
  
            <Button type="submit" :loading="isSubmitting" fluid severity="warn"
              class="text-sm font-semibold text-white sm:py-3 sm:text-base">
              Register
            </Button>
  
            <p class="text-center text-sm text-slate-600 sm:text-base">
              Already have an account?
              <button type="button" @click="router.visit('/customer/login')"
                class="font-semibold text-orange-500 hover:underline"> Login </button>
            </p>
          </form>
        </div>
  
        <div class="order-2 hidden min-h-screen lg:order-2 lg:block">
          <CustomerAuth3DHero class="h-full" title="Register to join!"
            subtitle="Save favorites, build a cart, and inspect every piece from every angle."
            footer="Furnisync makes furniture shopping feel real before delivery." />
        </div>
        <section class="order-1 bg-gradient-to-r from-orange-500 to-orange-600 px-6 py-5 text-white lg:hidden">
          <h2 class="text-2xl font-semibold">Welcome to Furnisync Shop</h2>
          <p class="mt-2 max-w-sm text-sm leading-6 text-orange-50">Save favorites, build a cart, and explore furniture in 3D.</p>
        </section>
    </div>
  </div>

  <Dialog v-model:visible="legalVisible" modal :header="legalTitle" class="w-[min(48rem,95vw)]">
    <div class="max-h-[70vh] overflow-y-auto pr-2"><EcommerceLegalContent :type="legalType" /></div>
    <template #footer><Button label="Close" severity="secondary" outlined @click="legalVisible = false" /></template>
  </Dialog>
</template>

<script setup lang="ts">
import axios from 'axios'
import { computed, reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import CustomerAuth3DHero from '@/Components/auth/CustomerAuth3DHero.vue'
import EcommerceLegalContent from '@/Components/ecommerce/EcommerceLegalContent.vue'

const toast = useToast()
const isSubmitting = ref(false)
const acceptedTerms = ref(false)
const legalVisible = ref(false)
const legalType = ref<'terms' | 'privacy'>('terms')
const legalTitle = computed(() => legalType.value === 'terms' ? 'Terms and Conditions' : 'Privacy Policy')
const openLegal = (type: 'terms' | 'privacy') => { legalType.value = type; legalVisible.value = true }
const minimumAdultDate = computed(() => {
  const d = new Date()
  d.setFullYear(d.getFullYear() - 18)
  return d
})

const form = reactive({
  fname: '',
  lname: '',
  email: '',
  birthday: null as Date | null,
  password: '',
  confirmPassword: '',
})
const validationErrors = reactive({ fname: '', lname: '', email: '' })
const sanitizeName = (value: string) => String(value || '').replace(/[^\p{L}\p{M}\s'-]/gu, '').replace(/\s{2,}/g, ' ')
const validateEmail = () => {
  const email = form.email.trim()
  validationErrors.email = !email
    ? 'Email is required.'
    : /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)
      ? ''
      : 'Enter a valid email address.'
  return !validationErrors.email
}

const passwordRules = [
  'at least 8 characters',
  'one uppercase letter',
  'one number',
  'one special character',
]

const passwordRegex = /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/
const passwordFocused = ref(false)
const passwordRuleItems = reactive([
  { key: 'length', label: 'At least 8 characters', met: false },
  { key: 'uppercase', label: 'One uppercase letter', met: false },
  { key: 'number', label: 'One number', met: false },
  { key: 'special', label: 'One special character', met: false },
])

const showPasswordRules = computed(() => passwordFocused.value && form.password.length > 0)

const updatePasswordRules = (value: string) => {
  passwordRuleItems[0].met = value.length >= 8
  passwordRuleItems[1].met = /[A-Z]/.test(value)
  passwordRuleItems[2].met = /\d/.test(value)
  passwordRuleItems[3].met = /[^A-Za-z0-9]/.test(value)
}

watch(
  () => form.password,
  (value) => updatePasswordRules(value),
  { immediate: true }
)

async function submitRegister() {
  if (isSubmitting.value) return

  form.fname = sanitizeName(form.fname).trim()
  form.lname = sanitizeName(form.lname).trim()
  validationErrors.fname = form.fname ? '' : 'First name is required.'
  validationErrors.lname = form.lname ? '' : 'Last name is required.'
  const emailValid = validateEmail()
  if (validationErrors.fname || validationErrors.lname || !emailValid) return

  if (!form.fname || !form.lname || !form.email || !form.password || !form.confirmPassword || !form.birthday) {
    toast.add({ severity: 'warn', summary: 'Missing fields', detail: 'Please complete all required fields.', life: 3000 })
    return
  }

  if (form.birthday) {
    const birthDate = new Date(form.birthday)
    if (Number.isNaN(birthDate.getTime()) || birthDate > minimumAdultDate.value) {
      toast.add({ severity: 'warn', summary: 'Age restriction', detail: 'You must be at least 18 years old to register.', life: 3500 })
      return
    }
  }

  if (form.password !== form.confirmPassword) {
    toast.add({ severity: 'warn', summary: 'Password mismatch', detail: 'Password and confirm password do not match.', life: 3000 })
    return
  }

  if (!passwordRegex.test(form.password)) {
    toast.add({
      severity: 'warn',
      summary: 'Weak password',
      detail: `Password must be ${passwordRules.join(', ')}.`,
      life: 4000,
    })
    return
  }

  if (!acceptedTerms.value) {
    toast.add({ severity: 'warn', summary: 'Terms required', detail: 'Please accept Terms and Privacy Policy.', life: 3000 })
    return
  }

  isSubmitting.value = true
  try {
    const response = await axios.post('/api/auth/register', {
      fname: form.fname,
      lname: form.lname,
      email: form.email,
      birthday: form.birthday ? new Date(form.birthday).toISOString().slice(0, 10) : null,
      password: form.password,
      account_type: 'customer',
      device_name: 'web-browser',
    })

    localStorage.setItem('register_token', response.data.user.access_token)
    localStorage.setItem('otp_context', 'customer')
    toast.add({ severity: 'success', summary: 'Registered', detail: 'Verify your OTP to continue.', life: 1800 })
    router.visit('/verify-otp')
  } catch (error: any) {
    if (error.response?.status === 422) {
      const errors = error.response.data?.errors
      if (errors && Object.keys(errors).length > 0) {
        const firstError = Object.values(errors)[0][0]
        toast.add({ severity: 'error', summary: 'Validation Error', detail: String(firstError), life: 5000 })
      }
    } else {
      toast.add({
        severity: 'error',
        summary: 'Registration Failed',
        detail: error?.response?.data?.error || error?.response?.data?.message || 'Something went wrong. Please try again.',
        life: 5000,
      })
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap');

.portal-title {
  font-family: 'Space Grotesk', sans-serif;
}
</style>

<style scoped>
.password-guide-enter-active,
.password-guide-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease, max-height 0.22s ease;
  max-height: 180px;
}

.password-guide-enter-from,
.password-guide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
  max-height: 0;
}

.password-guide-enter-to,
.password-guide-leave-from {
  opacity: 1;
  transform: translateY(0);
  max-height: 180px;
}
</style>
