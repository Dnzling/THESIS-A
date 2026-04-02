<template>
  <JobPortalLayout>
    <div class="flex min-h-[calc(100vh-88px)] items-center justify-center px-4 py-10">
      <div class="grid w-full max-w-6xl overflow-hidden rounded-3xl border border-orange-100 bg-white shadow-2xl lg:grid-cols-2">
        <div class="p-8 lg:p-10">
          <form class="space-y-6" @submit.prevent="submitRegister">
            <div class="space-y-2">
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-500">Job Portal</p>
              <h1 class="text-3xl font-semibold text-slate-900">Create Applicant Account</h1>
              <p class="text-sm text-slate-500">We'll verify your email first before you continue.</p>
            </div>

            <Message severity="warn" :closable="false">
              This account is only for the applicant portal and job applications.
            </Message>

            <div class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700">First Name</label>
                <InputText v-model="form.fname" class="w-full" />
              </div>
              <div class="space-y-2">
                <label class="text-sm font-medium text-slate-700">Last Name</label>
                <InputText v-model="form.lname" class="w-full" />
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-700">Email</label>
              <InputText v-model="form.email" type="email" class="w-full" placeholder="you@example.com" />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-700">Password</label>
              <Password v-model="form.password" class="w-full" inputClass="w-full" toggleMask />
            </div>

            <Button type="submit" label="Create Account" severity="warn" :loading="submitting" fluid />

            <div class="text-center text-sm text-slate-500">
              Already have an applicant account?
              <button type="button" class="font-semibold text-orange-600 transition hover:text-orange-700" @click="router.push({ name: 'job-portal.login' })">
                Login here
              </button>
            </div>
          </form>
        </div>
        <div class="bg-gradient-to-br from-orange-500 via-orange-500 to-orange-600 p-10 text-white">
          <div class="space-y-6">
            <div>
              <p class="text-sm font-semibold uppercase tracking-[0.24em] text-orange-100">Applicant Portal</p>
              <h2 class="mt-4 text-4xl font-semibold leading-tight">Create your profile once, apply faster every time.</h2>
              <p class="mt-4 text-sm leading-6 text-orange-100">
                Keep your job history, preferred roles, and interview updates together while applying across stores.
              </p>
            </div>
            <div class="space-y-3 text-sm text-orange-100">
              <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">Email verification for secure access</div>
              <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">Saved profile details for re-use</div>
              <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">Track interview progress easily</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </JobPortalLayout>
</template>

<script setup lang="ts">
import JobPortalLayout from './JobPortalLayout.vue'
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useJobPortalAuthStore } from '../../../../stores/jobPortalAuth'

const router = useRouter()
const toast = useToast()
const portalAuth = useJobPortalAuthStore()
const submitting = ref(false)

const form = reactive({
  fname: '',
  lname: '',
  email: '',
  password: '',
})

const submitRegister = async () => {
  submitting.value = true
  try {
    await portalAuth.register(form)
    toast.add({ severity: 'success', summary: 'Account created', detail: 'Verify your email to continue.', life: 2500 })
    router.push({ name: 'job-portal.verify-otp' })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Registration failed', detail: error.response?.data?.message || 'Unable to create account.', life: 3200 })
  } finally {
    submitting.value = false
  }
}
</script>
