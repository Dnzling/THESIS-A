<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Conditionally render the components based on verification status -->
    <Verification v-if="!hasSubmittedVerification" @submitted="handleVerificationSubmitted" />
    <VerificationStatus v-else  :verification-data="verificationData" @resubmit="handleResubmit" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
// import Verification from '../../components/VerifyStoreForm.vue'
import Verification from '../../components/store-registration/StoreRegistrationWizard.vue'
import VerificationStatus from '../../components/VerifyStoreStatus.vue'

// Check if verification has been submitted
const hasSubmittedVerification = ref()
const verificationData = ref()

// Load verification status from localStorage on mount
onMounted(() => {
  loadVerificationData()
})

const loadVerificationData = () => {
  const savedData = localStorage.getItem('storeVerification')
  if (savedData) {
    verificationData.value = JSON.parse(savedData)
    hasSubmittedVerification.value = false
  }
}

// Handle when verification is submitted
const handleVerificationSubmitted = (data : boolean) => {
  verificationData.value = data
  hasSubmittedVerification.value = true
  // Optionally save to localStorage
  localStorage.setItem('storeVerification', JSON.stringify(data))
}

// Handle resubmission
const handleResubmit = () => {
  hasSubmittedVerification.value = false
  verificationData.value = null
  localStorage.removeItem('storeVerification')
}
</script>