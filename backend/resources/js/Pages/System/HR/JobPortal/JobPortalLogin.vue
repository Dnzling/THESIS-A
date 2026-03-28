<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.22),_transparent_40%),linear-gradient(180deg,_#eff6ff_0%,_#f8fafc_46%,_#ffffff_100%)] px-4 py-10">
    <div class="mx-auto grid max-w-6xl gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
      <section class="hidden rounded-[2rem] border border-blue-100 bg-white/75 p-10 shadow-sm backdrop-blur lg:block">
        <div class="max-w-xl space-y-6">
          <div class="inline-flex items-center gap-2 rounded-full bg-blue-50 px-4 py-2 text-sm font-medium text-blue-700">
            <i class="pi pi-briefcase" />
            Applicant Portal
          </div>
          <div class="space-y-3">
            <h1 class="text-4xl font-semibold tracking-tight text-slate-900">Find the right role and keep every application in one place.</h1>
            <p class="text-base leading-7 text-slate-600">
              Sign in to continue your job applications, review interview updates, and track every response from partner stores.
            </p>
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <Card class="border border-blue-100 bg-blue-50/70 shadow-none">
              <template #content>
                <div class="space-y-2">
                  <span class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white">
                    <i class="pi pi-send text-sm" />
                  </span>
                  <p class="font-semibold text-slate-900">Resume your application</p>
                  <p class="text-sm leading-6 text-slate-600">We bring you back to the job you were viewing after login.</p>
                </div>
              </template>
            </Card>
            <Card class="border border-blue-100 bg-white shadow-none">
              <template #content>
                <div class="space-y-2">
                  <span class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-white">
                    <i class="pi pi-bell text-sm" />
                  </span>
                  <p class="font-semibold text-slate-900">Stay updated</p>
                  <p class="text-sm leading-6 text-slate-600">Interview schedules and hiring responses stay visible in your dashboard.</p>
                </div>
              </template>
            </Card>
          </div>
        </div>
      </section>

      <Card class="border border-blue-100 bg-white shadow-xl shadow-blue-100/60">
        <template #content>
          <form class="space-y-5" @submit.prevent="submitLogin">
            <div class="space-y-2 text-center">
              <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm shadow-blue-200">
                <i class="pi pi-sign-in text-xl" />
              </div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Job Portal</p>
              <h1 class="text-3xl font-semibold text-slate-900">Applicant Login</h1>
              <p class="text-sm text-slate-500">Sign in to continue your application flow.</p>
            </div>

            <Message severity="info" :closable="false">
              Use your applicant account here. This is separate from the internal system login.
            </Message>

            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-700">Email</label>
              <InputText v-model="form.email" type="email" class="w-full" placeholder="you@example.com" />
            </div>

            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-700">Password</label>
              <Password v-model="form.password" class="w-full" inputClass="w-full" :feedback="false" toggleMask />
            </div>

            <Button type="submit" label="Login" icon="pi pi-sign-in" severity="info" :loading="submitting" fluid />

            <div class="text-center text-sm text-slate-500">
              No applicant account yet?
              <button type="button" class="font-semibold text-blue-600 transition hover:text-blue-700" @click="router.push({ name: 'job-portal.register' })">
                Create one here
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
  email: '',
  password: '',
})

const submitLogin = async () => {
  submitting.value = true
  try {
    const response = await portalAuth.login(form)
    const redirectTo = portalAuth.consumePendingRedirect() || '/job-portal/applications'

    if (response.requires_verification) {
      router.push({ name: 'job-portal.verify-otp' })
      return
    }

    toast.add({ severity: 'success', summary: 'Login successful', detail: 'Welcome back to the job portal.', life: 2200 })
    router.push(redirectTo)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Login failed', detail: error.response?.data?.message || 'Unable to login.', life: 3200 })
  } finally {
    submitting.value = false
  }
}
</script>
