<template>
  <div class="min-h-screen">
    <!-- Conditionally render the components based on verification status -->
    <Verification v-if="!hasSubmittedVerification" @submitted="handleVerificationSubmitted" />
    <VerificationStatus v-else  :verification-data="verificationData" @resubmit="handleResubmit" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axiosClient from '../../axios'
import { useAuthStore } from '../../stores/auth'
import Verification from '../../components/store-registration/StoreRegistrationWizard.vue'
import VerificationStatus from '../../components/VerifyStoreStatus.vue'

const authStore = useAuthStore()

const hasSubmittedVerification = ref(false)
const verificationData = ref<Record<string, any> | null>(null)

const resolveUserStoreId = (): number | null => {
  const user = authStore.currentUser as any
  const parsed = Number(user?.store_id)
  return Number.isFinite(parsed) && parsed > 0 ? parsed : null
}

onMounted(() => {
  loadVerificationData()
})

const loadVerificationData = async () => {
  try {
    await authStore.fetchCurrentUser()

    const storeId = resolveUserStoreId()
    if (!storeId) {
      hasSubmittedVerification.value = false
      verificationData.value = null
      return
    }

    const response = await axiosClient.get(`/api/stores/${storeId}/verification/status`)
    const data = response?.data?.data || null

    verificationData.value = data
    hasSubmittedVerification.value = Boolean(data?.documents_submitted)
  } catch (_error) {
    hasSubmittedVerification.value = false
    verificationData.value = null
  }
}

const handleVerificationSubmitted = async (_data: boolean) => {
  await loadVerificationData()
}

const handleResubmit = async () => {
  hasSubmittedVerification.value = false
  verificationData.value = null

  await authStore.fetchCurrentUser()
  const storeId = resolveUserStoreId()
  if (!storeId) {
    return
  }

  try {
    const response = await axiosClient.get(`/api/stores/${storeId}/verification/status`)
    const data = response?.data?.data || null
    if (data?.documents_submitted) {
      verificationData.value = data
    hasSubmittedVerification.value = true
    }
  } catch (_error) {
    hasSubmittedVerification.value = false
  }
}
</script>