<template>
  <JobPortalLayout>
    <div class="py-8 lg:py-12">
      <div class="mx-auto max-w-xl">
      <Card class="border border-orange-100 bg-white shadow-xl shadow-orange-100/60">
        <template #content>
          <div class="space-y-6 text-center">
            <div class="space-y-2">
              <h1 class="text-3xl font-semibold text-slate-900">Enter your OTP</h1>
              <p class="mx-auto max-w-md text-sm leading-6 text-slate-500">Use the 6-digit code sent to your email to unlock your applicant account.</p>
            </div>

            <Message severity="warn" :closable="false">
              Once verified, we’ll return you to the job post or application step you were trying to access.
            </Message>

            <div class="flex justify-center">
              <InputOtp v-model="otp" integerOnly :length="6" />
            </div>

            <div class="flex flex-col gap-3">
              <Button label="Verify Email" icon="pi pi-check" severity="warn" :loading="submitting" @click="submitOtp" />
              <Button
                :label="resendLabel"
                severity="secondary"
                outlined
                :loading="resending"
                :disabled="resending || countdown > 0"
                @click="resendOtp"
              />
              <p v-if="countdown > 0" class="text-xs text-slate-400">
                You can resend the code in {{ countdown }}s.
              </p>
            </div>
          </div>
        </template>
      </Card>
      </div>
    </div>
  </JobPortalLayout>
</template>

<script setup lang="ts">
import JobPortalLayout from './JobPortalLayout.vue'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useJobPortalAuthStore } from '../../../../stores/jobPortalAuth'

const router = useRouter()
const toast = useToast()
const portalAuth = useJobPortalAuthStore()

const otp = ref('')
const submitting = ref(false)
const resending = ref(false)
const countdown = ref(60)
let timer: ReturnType<typeof setInterval> | null = null

const tick = () => {
  if (countdown.value <= 0) {
    if (timer) clearInterval(timer)
    timer = null
    return
  }
  countdown.value -= 1
}

const startCountdown = () => {
  if (timer) clearInterval(timer)
  countdown.value = 60
  timer = setInterval(tick, 1000)
}

const resendLabel = computed(() => {
  return countdown.value > 0 ? `Resend OTP (${countdown.value}s)` : 'Resend OTP'
})

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
  if (countdown.value > 0) return
  resending.value = true
  try {
    await portalAuth.resendOtp()
    toast.add({ severity: 'warn', summary: 'OTP resent', detail: 'Check your email inbox.', life: 2500 })
    startCountdown()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to resend OTP', detail: error.response?.data?.message || 'Please try again.', life: 3000 })
  } finally {
    resending.value = false
  }
}

onMounted(() => {
  startCountdown()
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>
