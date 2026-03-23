<template>
  <div class="relative min-h-screen bg-[#f2f3f7] px-3 py-5 sm:px-4 sm:py-8">
    <div class="mx-auto mt-6 w-full max-w-125 rounded-[26px] bg-white/70 p-0 shadow-sm sm:mt-10 sm:rounded-[44px]">
      <div class="rounded-[22px] bg-white px-4 py-7 sm:rounded-[40px] sm:px-8 sm:py-10 md:px-12">
        <div class="mb-6 text-center sm:mb-8">
          <div class="flex items-center justify-center rounded-lg">
            <img src="/Furnishop.png" alt="Furni Shop" class="w-auto object-contain sm:w-28 md:w-100 " />
          </div>
          <p class="mt-2 text-sm text-slate-500 sm:text-lg">Create your customer account</p>
        </div>
  
        <form class="space-y-5 sm:space-y-6" @submit.prevent="submitRegister">
          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="space-y-2">
              <label class="text-base font-semibold text-slate-900 sm:text-lg">First Name</label>
              <InputText v-model="form.fname" fluid placeholder="First name" autocomplete="given-name" />
            </div>
            <div class="space-y-2">
              <label class="text-base font-semibold text-slate-900 sm:text-lg">Last Name</label>
              <InputText v-model="form.lname" fluid placeholder="Last name" autocomplete="family-name" />
            </div>
          </div>
  
          <div class="space-y-2">
            <label class="text-base font-semibold text-slate-900 sm:text-lg">Email</label>
            <InputText v-model="form.email" fluid placeholder="Email address" autocomplete="email" />
          </div>
  
          <div class="space-y-2">
            <label class="text-base font-semibold text-slate-900 sm:text-lg">Birthday</label>
            <DatePicker v-model="form.birthday" fluid showIcon dateFormat="yy-mm-dd" placeholder="Select birthday" />
          </div>
  
  
          <div class="space-y-2">
            <label class="text-base font-semibold text-slate-900 sm:text-lg">Password</label>
            <Password v-model="form.password" fluid :feedback="false" toggleMask placeholder="Password" />
          </div>
          <div class="space-y-2">
            <label class="text-base font-semibold text-slate-900 sm:text-lg">Confirm Password</label>
            <Password v-model="form.confirmPassword" fluid :feedback="false" toggleMask placeholder="Confirm password" />
          </div>
  
  
          <div class="flex items-start gap-2 text-sm sm:text-base">
            <Checkbox v-model="acceptedTerms" inputId="acceptedTerms" :binary="true" />
            <label for="acceptedTerms" class="leading-6 text-slate-700">
              I agree to the
              <a href="#" class="font-medium text-[#6d5efc] hover:underline">Terms</a>
              and
              <a href="#" class="font-medium text-[#6d5efc] hover:underline">Privacy Policy</a>.
            </label>
          </div>
  
          <Button type="submit" :loading="isSubmitting" fluid severity="info"
            class="text-base font-semibold text-white sm:py-3 sm:text-xl">
            Register
          </Button>
  
          <p class="text-center text-sm text-slate-600 sm:text-base">
            Already have an account?
            <Button @click="router.push({ name: 'customer.login' })" severity="info" link label="Login" />
          </p>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import axios from 'axios'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import DatePicker from 'primevue/datepicker'

const router = useRouter()
const toast = useToast()
const isSubmitting = ref(false)
const acceptedTerms = ref(false)

const form = reactive({
  fname: '',
  lname: '',
  email: '',
  birthday: null as Date | null,
  password: '',
  confirmPassword: '',
})

async function submitRegister() {
  if (isSubmitting.value) return

  if (!form.fname || !form.lname || !form.email || !form.password || !form.confirmPassword || !form.birthday) {
    toast.add({ severity: 'warn', summary: 'Missing fields', detail: 'Please complete all required fields.', life: 3000 })
    return
  }

  if (form.password !== form.confirmPassword) {
    toast.add({ severity: 'warn', summary: 'Password mismatch', detail: 'Password and confirm password do not match.', life: 3000 })
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
      role_id: 16, //  role ID for customers
      device_name: 'web-browser',
    })

    localStorage.setItem('register_token', response.data.user.access_token)
    localStorage.setItem('otp_context', 'customer')
    toast.add({ severity: 'success', summary: 'Registered', detail: 'Verify your OTP to continue.', life: 1800 })
    router.push({ name: 'VerifyOtp' })
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
        detail: error?.response?.data?.message || 'Something went wrong. Please try again.',
        life: 5000,
      })
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>
