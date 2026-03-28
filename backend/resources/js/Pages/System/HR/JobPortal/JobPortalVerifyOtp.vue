<template>
  <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),_transparent_38%),linear-gradient(180deg,_#eff6ff_0%,_#f8fafc_42%,_#ffffff_100%)] px-4 py-10">
    <div class="mx-auto max-w-xl">
      <Card class="border border-blue-100 bg-white shadow-xl shadow-blue-100/60">
        <template #content>
          <div class="space-y-6 text-center">
            <div class="space-y-2">
              <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm shadow-blue-200">
                <i class="pi pi-envelope text-xl" />
              </div>
              <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-600">Email Verification</p>
              <h1 class="text-3xl font-semibold text-slate-900">Enter your OTP</h1>
              <p class="mx-auto max-w-md text-sm leading-6 text-slate-500">Use the 6-digit code sent to your email to unlock your applicant account.</p>
            </div>

            <Message severity="info" :closable="false">
              Once verified, we’ll return you to the job post or application step you were trying to access.
            </Message>

            <div class="flex justify-center">
              <InputOtp v-model="otp" integerOnly :length="6" />
            </div>

            <div class="flex flex-col gap-3">
              <Button label="Verify Email" icon="pi pi-check" severity="info" :loading="submitting" @click="submitOtp" />
              <Button label="Resend OTP" severity="secondary" outlined :loading="resending" @click="resendOtp" />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useJobPortalAuthStore } from '../../../../stores/jobPortalAuth'

const router = useRouter()
const toast = useToast()
const portalAuth = useJobPortalAuthStore()

const otp = ref('')
const submitting = ref(false)
const resending = ref(false)

const submitOtp = async () => {
  submitting.value = true
  try {
    await portalAuth.verifyOtp(String(otp.value))
    const redirectTo = portalAuth.consumePendingRedirect() || '/job-portal/applications'
    await portalAuth.fetchMe()
    toast.add({ severity: 'success', summary: 'Email verified', detail: 'You can continue now.', life: 2500 })
    router.push(redirectTo)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Verification failed', detail: error.response?.data?.message || 'Invalid OTP.', life: 3000 })
  } finally {
    submitting.value = false
  }
}

const resendOtp = async () => {
  resending.value = true
  try {
    await portalAuth.resendOtp()
    toast.add({ severity: 'info', summary: 'OTP resent', detail: 'Check your email inbox.', life: 2500 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to resend OTP', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
  } finally {
    resending.value = false
  }
}
</script>
