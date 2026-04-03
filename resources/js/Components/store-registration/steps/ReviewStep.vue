<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Review & Submit</h2>
  
    <div class="space-y-8">
      <!-- Store Information Review -->
      <ReviewSection title="Store Information" :canEdit="true" @edit="$emit('edit-step', 1)">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <InfoItem label="Store Name" :value="formData.storeName" />
          <InfoItem label="Business Type" :value="formatBusinessType(formData.businessType)" />
          <InfoItem label="Business Number" :value="formData.businessNumber" />
          <InfoItem label="Contact Number" :value="formData.contactNumber" />
          <InfoItem label="Email" :value="formData.email" class="md:col-span-2" />
          <!-- Address part -->
          <InfoItem label="Business Address" :value="formattedAddress" class="md:col-span-2" />
  
          <!-- Coordinates (if you want them separate) -->
          <InfoItem v-if="formData.businessAddress?.latitude || formData.businessAddress?.longitude" label="Coordinates"
            :value="formattedCoordinates" class="md:col-span-2" />
        </div>
      </ReviewSection>  
      <!-- Business Documents Review -->
      <ReviewSection title="Business Documents" :canEdit="true" @edit="$emit('edit-step', 2)">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <FilePreview :file="formData.registrationPermit" label="Registration Permit" />
          <FilePreview :file="formData.taxCertificate" label="Tax Certificate" />
          <FilePreview :file="formData.mayorPermit" label="Mayor's Permit" />
        </div>
        <InfoItem v-if="formData.additionalNotes" label="Additional Notes" :value="formData.additionalNotes" />
      </ReviewSection>
  
      <!-- Terms and Conditions -->
      <div class="border border-gray-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Terms & Conditions</h3>
  
        <div class="space-y-4">
          <label class="flex items-start">
            <input type="checkbox" v-model="localForm.termsAccepted" class="mt-1 mr-3" required />
            <span class="text-sm text-gray-700">
              I agree to the <a href="#" class="text-blue-600 hover:underline">Terms of Service</a> and confirm that all
              information provided is accurate and complete.
            </span>
          </label>
  
          <label class="flex items-start">
            <input type="checkbox" v-model="localForm.privacyAccepted" class="mt-1 mr-3" required />
            <span class="text-sm text-gray-700">
              I agree to the <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a> and consent to the
              processing of my personal data for verification purposes.
            </span>
          </label>
        </div>
      </div>
  
      <!-- Navigation Buttons -->
      <div class="flex justify-end pt-6 border-t border-gray-200">
        <Button type="button" @click="submitRegistration" class="w-1/5" severity="success" label="Submit Registration"
          :disabled="!isStepValid || isSubmitting" />
      </div>
    </div>
  </div>
  
  <Dialog v-model:visible="isSubmitting" modal :closable="false" :showHeader="false" :style="{ width: '300px' }">
    <div class="flex flex-col items-center justify-center p-6">
      <ProgressSpinner style="width: 50px; height: 50px" strokeWidth="4" fill="transparent" animationDuration=".5s" />
      <p class="mt-4 text-lg font-medium text-gray-700">Submitting your form...</p>
      <p class="text-gray-500">Please wait a moment</p>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import ReviewSection from '../shared/ReviewSection.vue'
import InfoItem from '../shared/InfoItem.vue'
import FilePreview from '../shared/FilePreview.vue'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

const toast = useToast()
const authStore = useAuthStore()
const isSubmitting = ref<boolean>(false)
const storeId = ref<number>()

const getContactPersonName = (): string => {
  const user = authStore.user as any
  const firstName = String(user?.first_name ?? user?.fname ?? '').trim()
  const lastName = String(user?.last_name ?? user?.lname ?? '').trim()
  const fullName = `${firstName} ${lastName}`.trim()
  return fullName || 'Store Owner'
}

interface Props {
  formData: any
}

interface Emits {
  (e: 'update:formData', data: any): void
  (e: 'next'): void
  (e: 'prev'): void
  (e: 'submit'): void
  (e: 'edit-step', step: number): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local form data
const localForm = ref({
  ...props.formData,
  termsAccepted: props.formData.termsAccepted || false,
  privacyAccepted: props.formData.privacyAccepted || false
})

// Validation
const isStepValid = computed(() => {
  return localForm.value.termsAccepted && localForm.value.privacyAccepted
})

// Format functions
const formatBusinessType = (type: string) => {
  const types: Record<string, string> = {
    retail: 'Retail Store',
    restaurant: 'Restaurant',
    service: 'Service Provider',
    wholesale: 'Wholesale',
    online: 'Online Store',
    other: 'Other'
  }
  return types[type] || type || 'Not provided'
}

const formattedAddress = computed(() => {
  const addr = localForm.value.businessAddress
  if (!addr) return 'Not provided'

  const parts = []

  if (addr.address) parts.push(addr.address)
  if (addr.barangay) parts.push(addr.barangay)
  if (addr.city) parts.push(addr.city)
  parts.push('Cavite')

  return parts.join(', ') || 'Not provided'
})

const formattedCoordinates = computed(() => {
  const addr = localForm.value.businessAddress
  if (!addr) return 'Not set'

  if (addr.latitude && addr.longitude) {
    return `${addr.latitude}, ${addr.longitude}`
  }
  return 'Not set'
})

const parseNullableNumber = (value: unknown): number | null => {
  if (value === null || value === undefined || value === '') {
    return null
  }
  const parsed = Number(value)
  return Number.isFinite(parsed) ? parsed : null
}

// Submit registration
const submitRegistration = async () => {
  if (!isStepValid.value) return

  const missingStoreFields: string[] = []
  if (!String(localForm.value.storeName || '').trim()) missingStoreFields.push('Store Name')
  if (!String(localForm.value.contactNumber || '').trim()) missingStoreFields.push('Contact Number')
  if (!String(localForm.value.businessAddress?.city || '').trim()) missingStoreFields.push('City')
  if (!String(localForm.value.businessAddress?.barangay || '').trim()) missingStoreFields.push('Barangay')
  if (!String(localForm.value.businessAddress?.address || '').trim()) missingStoreFields.push('Business Address')

  if (missingStoreFields.length > 0) {
    toast.add({
      severity: 'warn',
      summary: 'Missing Required Fields',
      detail: `Please complete: ${missingStoreFields.join(', ')}`,
      life: 5000,
    })
    return
  }

  const missingRequiredFiles: string[] = []
  if (!(localForm.value.registrationPermit instanceof File)) missingRequiredFiles.push('Business Registration Permit')
  if (!(localForm.value.taxCertificate instanceof File)) missingRequiredFiles.push('BIR Tax Certificate')
  if (!(localForm.value.mayorPermit instanceof File)) missingRequiredFiles.push("Mayor's Permit")

  if (missingRequiredFiles.length > 0) {
    toast.add({
      severity: 'warn',
      summary: 'Missing Required Documents',
      detail: `Please upload: ${missingRequiredFiles.join(', ')}`,
      life: 5000,
    })
    return
  }

  isSubmitting.value = true

  try {
    // 1. Prepare FormData for file uploads
    const formData = new FormData()

    // Step 1: Store Information
    formData.append('business_registration_number', localForm.value.businessNumber)
    formData.append('business_registration_date', new Date().toISOString().slice(0, 10))

    // Step 2: Documents - Append files if they exist
    if (localForm.value.registrationPermit instanceof File) {
      formData.append('business_registration_file', localForm.value.registrationPermit)
    }

    if (localForm.value.taxCertificate instanceof File) {
      formData.append('tax_certificate_file', localForm.value.taxCertificate)
    }

    if (localForm.value.mayorPermit instanceof File) {
      formData.append('business_permit_file', localForm.value.mayorPermit)
    }

    // 1. Prepare data object
    const payload = {
      store_name: localForm.value.storeName,
      business_type: localForm.value.businessType,
      business_registration_number: localForm.value.businessNumber,
      contact_number: localForm.value.contactNumber,
      email: localForm.value.email,
      address: localForm.value.businessAddress?.address || '',
      city: localForm.value.businessAddress?.city || '',
      barangay: localForm.value.businessAddress?.barangay || '',
      province: 'Cavite',
      latitude: parseNullableNumber(localForm.value.businessAddress?.latitude),
      longitude: parseNullableNumber(localForm.value.businessAddress?.longitude),
      contact_person: getContactPersonName()
    }

    const storeResponse = await axios.post('api/stores/register', payload, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json'
      }
    })

    const createdStoreId = Number(storeResponse?.data?.store?.store_id || storeResponse?.data?.store?.id)
    if (!Number.isFinite(createdStoreId) || createdStoreId <= 0) {
      throw new Error('Store registration succeeded but no valid store id was returned.')
    }

    storeId.value = createdStoreId

    // Make API call
    const verifyResponse = await axios.post(
      `api/stores/${storeId.value}/verification/submit`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }
    )

    // 4. Handle success
    toast.add({
      severity: 'success',
      summary: 'Registration Submitted!',
      detail: verifyResponse.data.message || 'Your store registration is under review.',
      life: 5000
    })

    // 5. Emit success to parent
    emit('submit')

  } catch (error: any) {
    console.error('Submission error:', error)

    try {
      if (storeId.value) {
        await axios.delete(`/api/stores/${storeId.value}`, {
          headers: {
            'Authorization': `Bearer ${authStore.token}`
          }
        })
      }
    } catch (rollbackError) {
      console.error('Failed to rollback store creation:', rollbackError)
    }

    if (error.response?.status === 422) {
      // Validation errors
      const errors = error.response?.data?.errors
      const errorValues = errors && typeof errors === 'object' ? Object.values(errors) : []
      const firstError = errorValues.length > 0 ? errorValues[0] : null
      const backendError = error.response?.data?.error
      const backendMessage = error.response?.data?.message

      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: Array.isArray(firstError)
          ? String(firstError[0] || 'Please check your input')
          : (backendError || backendMessage || 'Please check your input'),
        life: 5000
      })
    } else if (error.response?.status === 403) {
      toast.add({
        severity: 'error',
        summary: 'Permission Denied',
        detail: 'You do not have permission to submit verification for this store.',
        life: 5000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Submission Failed',
        detail: error.response?.data?.message || 'Please try again later',
        life: 3000
      })
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>




