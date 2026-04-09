<template>
  <Toast />
  <div class="min-h-screen bg-slate-50">
    <div class="h-screen w-full bg-white">
      <div class="grid h-full gap-0 lg:grid-cols-2">
        <div class="flex flex-col justify-center p-8 lg:p-12">
          <div class="mb-6 text-center sm:mb-8">
            <div class="portal-title text-2xl font-bold text-teal-600">Supplier Account Registration</div>
            <p class="mt-2 text-sm text-slate-500 sm:text-lg">Create your supplier access for Furnisync IMS</p>
          </div>

          <form class="space-y-5 sm:space-y-6" @submit.prevent="submitRegister">
            <div class="space-y-2">
              <label class="text-base font-semibold text-slate-900 sm:text-lg">Supplier Name</label>
              <InputText v-model="form.supplier_name" fluid placeholder="Company / Supplier name" autocomplete="organization" />
            </div>

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
              <label class="text-base font-semibold text-slate-900 sm:text-lg">Password</label>
              <Password v-model="form.password" fluid :feedback="false" toggleMask autocomplete="new-password" placeholder="Password" />
            </div>

            <div class="space-y-2">
              <label class="text-base font-semibold text-slate-900 sm:text-lg">Confirm Password</label>
              <Password v-model="form.confirmPassword" fluid :feedback="false" toggleMask autocomplete="new-password" placeholder="Confirm password" />
            </div>

            <div class="flex items-start gap-2 text-sm sm:text-base">
              <Checkbox v-model="acceptedTerms" inputId="acceptedSupplierTerms" :binary="true" />
              <label for="acceptedSupplierTerms" class="leading-6 text-slate-700">
                I agree to the
                <a href="#" class="font-medium text-teal-600 hover:underline">Supplier Terms</a>
                and
                <a href="#" class="font-medium text-teal-600 hover:underline">Platform Policy</a>.
              </label>
            </div>

            <Button type="submit" :loading="isSubmitting" fluid class="bg-teal-600 border-teal-600 hover:bg-teal-700 hover:border-teal-700 text-base font-semibold text-white sm:py-3 sm:text-xl">
              Register
            </Button>

            <p class="text-center text-sm text-slate-600 sm:text-base">
              Already have a supplier account?
              <button type="button" @click="router.visit('/login')" class="font-semibold text-teal-600 hover:underline">
                Login
              </button>
            </p>
          </form>
        </div>

        <SupplierAuth3DHero
          class="h-full"
          title="Become a Verified Supplier"
          subtitle="Register now, verify your identity, and start receiving RFQs from partner stores."
          footer="After account creation, continue to supplier portal registration and verification."
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import axios from 'axios'
import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Button from 'primevue/button'
import Checkbox from 'primevue/checkbox'
import SupplierAuth3DHero from '@/Components/auth/SupplierAuth3DHero.vue'

const toast = useToast()
const isSubmitting = ref(false)
const acceptedTerms = ref(false)

const form = reactive({
  supplier_name: '',
  fname: '',
  lname: '',
  email: '',
  password: '',
  confirmPassword: '',
})

async function submitRegister() {
  if (isSubmitting.value) return

  if (!form.supplier_name || !form.fname || !form.lname || !form.email || !form.password || !form.confirmPassword) {
    toast.add({ severity: 'warn', summary: 'Missing fields', detail: 'Please complete all required fields.', life: 3000 })
    return
  }

  if (form.password !== form.confirmPassword) {
    toast.add({ severity: 'warn', summary: 'Password mismatch', detail: 'Password and confirm password do not match.', life: 3000 })
    return
  }

  if (!acceptedTerms.value) {
    toast.add({ severity: 'warn', summary: 'Terms required', detail: 'Please accept the terms first.', life: 3000 })
    return
  }

  isSubmitting.value = true
  try {
    const response = await axios.post('/api/auth/supplier/register', {
      supplier_name: form.supplier_name,
      fname: form.fname,
      lname: form.lname,
      email: form.email,
      password: form.password,
      password_confirmation: form.confirmPassword,
      device_name: 'web-browser',
    })

    localStorage.setItem('register_token', response.data.user.access_token)
    localStorage.setItem('otp_context', 'supplier')
    toast.add({ severity: 'success', summary: 'Registered', detail: 'Verify your OTP to continue.', life: 1800 })
    router.visit('/verify-otp?portal=supplier')
  } catch (error: any) {
    if (error.response?.status === 422) {
      const validationErrors = error.response.data?.errors
      if (validationErrors && Object.keys(validationErrors).length > 0) {
        const firstError = Object.values(validationErrors)[0][0]
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

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap');

.portal-title {
  font-family: 'Space Grotesk', sans-serif;
}
</style>
