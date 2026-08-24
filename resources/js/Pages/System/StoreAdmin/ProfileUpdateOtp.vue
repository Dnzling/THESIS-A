<template>
  <div class="min-h-screen bg-linear-to-br from-orange-50 via-white to-amber-50 px-4 py-12">
    <Toast />
    <div class="mx-auto max-w-md">
      <div class="mb-5 text-center">
        <span class="inline-flex items-center rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
          {{ contextLabel || 'Store Settings Verification' }}
        </span>
        <h1 class="mt-5 text-3xl font-bold text-slate-950">{{ heading || 'Verify store update' }}</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">
          {{ description || 'Enter the 6-digit OTP before we save your store profile changes.' }}
          <span class="block">Code sent to {{ email || 'your account email' }}.</span>
          <span class="mt-1 block text-xs text-slate-500">Code expires in 15 minutes.</span>
        </p>
      </div>

      <Card class="border border-orange-100 bg-white shadow-xl shadow-orange-100/70">
        <template #content>
          <form class="space-y-6" @submit.prevent="submitOtp">
            <Message severity="warn" :closable="false">
              {{ notice || 'This verification is only for Store Settings updates.' }}
            </Message>

            <div class="flex justify-center">
              <InputOtp v-model="otp" integerOnly :length="6" />
            </div>

            <div v-if="errorMessage" class="rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-center text-sm text-red-700">
              {{ errorMessage }}
            </div>

            <div class="flex flex-col gap-3">
              <Button type="submit" label="Verify and Save" icon="pi pi-check" severity="warn" :loading="submitting" :disabled="otpValue.length !== 6" />
              <Button
                type="button"
                :label="resendLabel"
                severity="secondary"
                outlined
                :loading="resending"
                :disabled="resending || countdown > 0"
                @click="resendOtp"
              />
              <Button type="button" label="Cancel" severity="secondary" text @click="cancelUpdate" />
            </div>
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Message from 'primevue/message'
import Toast from 'primevue/toast'
import { useToast } from 'primevue/usetoast'

defineOptions({ layout: null })

const toast = useToast()
const props = withDefaults(defineProps<{
  email?: string
  contextLabel?: string
  heading?: string
  description?: string
  notice?: string
  verifyEndpoint?: string
  resendEndpoint?: string
}>(), {
  verifyEndpoint: '/store/settings/profile/otp/verify',
  resendEndpoint: '/store/settings/profile/otp/resend',
})
const otp = ref('')
const submitting = ref(false)
const resending = ref(false)
const errorMessage = ref('')
const countdown = ref(60)
let timer: ReturnType<typeof setInterval> | null = null

const otpValue = computed(() => String(otp.value || '').replace(/\D/g, '').slice(0, 6))
const resendLabel = computed(() => countdown.value > 0 ? `Resend OTP (${countdown.value}s)` : 'Resend OTP')

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

const submitOtp = () => {
  if (otpValue.value.length !== 6) {
    errorMessage.value = 'Please enter the complete 6-digit OTP.'
    return
  }

  submitting.value = true
  errorMessage.value = ''
  router.post(
    props.verifyEndpoint,
    { otp: otpValue.value },
    {
      preserveScroll: true,
      onError: (errors) => {
        errorMessage.value = String(errors?.otp || errors?.profile || 'Invalid or expired OTP.')
        otp.value = ''
      },
      onFinish: () => { submitting.value = false },
    }
  )
}

const resendOtp = () => {
  if (countdown.value > 0) return
  resending.value = true
  errorMessage.value = ''
  router.post(
    props.resendEndpoint,
    {},
    {
      preserveScroll: true,
      onSuccess: () => {
        toast.add({ severity: 'warn', summary: 'OTP resent', detail: 'Check your email inbox.', life: 2500 })
        otp.value = ''
        startCountdown()
      },
      onError: (errors) => {
        errorMessage.value = String(errors?.otp || errors?.email || 'Unable to resend OTP.')
      },
      onFinish: () => { resending.value = false },
    }
  )
}

const cancelUpdate = () => {
  router.visit('/store/settings')
}

onMounted(() => {
  startCountdown()
})

onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
})
</script>
