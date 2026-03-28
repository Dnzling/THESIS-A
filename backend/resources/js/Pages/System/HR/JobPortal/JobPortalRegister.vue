<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top_right,_rgba(59,130,246,0.18),_transparent_34%),linear-gradient(180deg,_#eff6ff_0%,_#f8fafc_42%,_#ffffff_100%)] px-4 py-10">
    <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1fr_1fr] lg:items-center">
      <section class="hidden rounded-[2rem] border border-blue-100 bg-white/75 p-10 shadow-sm backdrop-blur lg:block">
        <div class="space-y-5">
          <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700">
            <i class="pi pi-user-plus" />
            Applicant Registration
          </div>
          <h1 class="text-4xl font-semibold tracking-tight text-slate-900">Create your applicant account once, then apply faster every time.</h1>
          <p class="text-base leading-7 text-slate-600">
            Verify your email with OTP, keep your applications in one dashboard, and receive interview updates directly from hiring teams.
          </p>
          <div class="rounded-3xl bg-blue-600 p-6 text-white">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-blue-100">What happens next</p>
            <ul class="mt-4 space-y-3 text-sm leading-6 text-blue-50">
              <li class="flex items-start gap-3">
                <i class="pi pi-check-circle mt-0.5" />
                Verify your email through a one-time password.
              </li>
              <li class="flex items-start gap-3">
                <i class="pi pi-check-circle mt-0.5" />
                Go back to the job post you were viewing.
              </li>
              <li class="flex items-start gap-3">
                <i class="pi pi-check-circle mt-0.5" />
                Track responses and interview schedules in your applicant dashboard.
              </li>
            </ul>
          </div>
        </div>
      </section>

      <Card class="border border-blue-100 bg-white shadow-xl shadow-blue-100/60">
        <template #content>
          <form class="space-y-5" @submit.prevent="submitRegister">
            <div class="space-y-2 text-center">
              <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm shadow-blue-200">
                <i class="pi pi-id-card text-xl" />
              </div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Job Portal</p>
              <h1 class="text-3xl font-semibold text-slate-900">Create Applicant Account</h1>
              <p class="text-sm text-slate-500">We’ll verify your email first before you continue.</p>
            </div>

            <Message severity="info" :closable="false">
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

            <Button type="submit" label="Create Account" icon="pi pi-user-plus" severity="info" :loading="submitting" fluid />

            <div class="text-center text-sm text-slate-500">
              Already have an applicant account?
              <button type="button" class="font-semibold text-blue-600 transition hover:text-blue-700" @click="router.push({ name: 'job-portal.login' })">
                Login here
              </button>
            </div>
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
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
