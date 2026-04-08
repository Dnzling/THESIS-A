<template>
  <JobPortalLayout>
    <div class="flex min-h-[calc(100vh-88px)] items-center justify-center px-4 py-10">
      <div
        class="grid w-full max-w-6xl overflow-hidden rounded-3xl border border-orange-100 bg-white shadow-2xl lg:grid-cols-2">
        <div class="bg-gradient-to-br from-orange-500 via-orange-500 to-orange-600 p-10 text-white">
          <div class="space-y-6">
            <div>
              <h2 class="mt-4 text-4xl font-semibold leading-tight">Find roles, track applications, and stay updated.</h2>
              <p class="mt-4 text-sm leading-6 text-orange-100">
                Use one applicant profile to apply across partner stores, follow interview updates, and keep every
                response organized.
              </p>
            </div>
            <div class="space-y-3 text-sm text-orange-100">
              <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">Centralized application history</div>
              <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">Interview updates in one place</div>
              <div class="rounded-xl border border-white/20 bg-white/10 px-4 py-3">Faster re-apply with saved profile
              </div>
            </div>
          </div>
        </div>
        <div class="p-8 lg:p-10">
          <form class="space-y-6" @submit.prevent="submitLogin">
            <div class="space-y-2">
              <h1 class="text-3xl font-semibold text-slate-900">Welcome Back</h1>
              <p class="text-sm text-slate-500">Sign in to your applicant account.</p>
            </div>
  
            <Message severity="warn" :closable="false">
              This login is only for the applicant job portal.
            </Message>
  
            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-700">Email</label>
              <InputText v-model="form.email" type="email" class="w-full" placeholder="you@example.com" />
            </div>
  
            <div class="space-y-2">
              <label class="text-sm font-medium text-slate-700">Password</label>
              <Password v-model="form.password" class="w-full" inputClass="w-full" :feedback="false" toggleMask />
            </div>
  
            <Button type="submit" label="Log In" severity="warn" :loading="submitting" fluid />
  
            <div class="text-center text-sm text-slate-500">
              No applicant account yet?
              <button type="button" class="font-semibold text-orange-600 transition hover:text-orange-700"
                @click="router.visit('/job-portal/register')">
                Create one here
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </JobPortalLayout>
</template>

<script setup lang="ts">
import JobPortalLayout from './JobPortalLayout.vue'
import { reactive, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import { useJobPortalAuthStore } from '../../../../stores/jobPortalAuth'

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
      router.visit('/job-portal/verify-otp')
      return
    }

    toast.add({ severity: 'success', summary: 'Login successful', detail: 'Welcome back to the job portal.', life: 2200 })
    router.visit(redirectTo)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Login failed', detail: error.response?.data?.message || 'Unable to login.', life: 3200 })
  } finally {
    submitting.value = false
  }
}
</script>
