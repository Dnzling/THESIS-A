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
  
      <!-- Owner ID Review -->
      <ReviewSection title="Owner Identification" :canEdit="true" @edit="$emit('edit-step', 2)">
        <div class="space-y-4">
          <InfoItem label="Primary ID Type" :value="formatIdType(formData.primaryIdType)" />
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <FilePreview :file="formData.idFront" label="ID Front" />
            <FilePreview :file="formData.idBack" label="ID Back" />
            <FilePreview :file="formData.selfiePhoto" label="Selfie with ID" />
          </div>
        </div>
      </ReviewSection>
  
      <!-- Business Documents Review -->
      <ReviewSection title="Business Documents" :canEdit="true" @edit="$emit('edit-step', 3)">
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
import { useAuthStore } from '../../../stores/auth'
import axios from 'axios'

const toast = useToast()
const authStore = useAuthStore()
const isSubmitting = ref<boolean>(false)
const storeId = ref<number>()

interface Props {
  formData: any
}

interface Emits {
  (e: 'update:formData', data: any): void
  (e: 'prev'): void
  (e: 'submit'): void
  (e: 'edit-step', step: number): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// City map for formatting
const cityMap: Record<string, string> = {
  'bacoor': 'Bacoor',
  'carmona': 'Carmona',
  'cavite-city': 'Cavite City',
  'dasmarinas': 'Dasmarinañas',
  'general-mariano-alvarez': 'General Mariano Alvarez',
  'general-trias': 'General Trias',
  'imus': 'Imus',
  'indang': 'Indang',
  'kawit': 'Kawit',
  'magallanes': 'Magallanes',
  'mendez': 'Mendez',
  'naic': 'Naic',
  'novaleta': 'Noveleta',
  'rosario': 'Rosario',
  'silang': 'Silang',
  'tagaytay': 'Tagaytay City',
  'tanza': 'Tanza',
  'ternate': 'Ternate',
  'trece-martires': 'Trece Martires City'
}

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

  const cityName = cityMap[addr.city] || addr.city
  const parts = []

  if (addr.address) parts.push(addr.address)
  if (cityName) parts.push(cityName)
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

const formatIdType = (type: string) => {
  const types: Record<string, string> = {
    umid: 'UMID',
    driver: "Driver's License",
    passport: 'Passport',
    national: 'National ID'
  }
  return types[type] || type || 'Not provided'
}

// Map frontend ID type to backend format
const mapIdType = (frontendType: string): string => {
  const mapping: Record<string, string> = {
    'umid': 'umid',
    'driver': 'driver_license',
    'passport': 'passport',
    'national': 'national_id'
  }
  return mapping[frontendType] || 'other'
}

// Submit registration
const submitRegistration = async () => {
  if (!isStepValid.value) return

  isSubmitting.value = true

  try {
    // 1. Prepare FormData for file uploads
    const formData = new FormData()

    // Step 1: Store Information
    formData.append('business_registration_number', localForm.value.businessNumber)

    // Step 2: Owner Identification
    formData.append('gov_id_type', mapIdType(localForm.value.primaryIdType))
    formData.append('gov_id_number', localForm.value.primaryIdNumber || '')

    // Step 3: Documents - Append files if they exist
    if (localForm.value.registrationPermit instanceof File) {
      formData.append('business_registration_file', localForm.value.registrationPermit)
    }

    if (localForm.value.idFront instanceof File) {
      formData.append('gov_id_front_file', localForm.value.idFront)
    }

    if (localForm.value.idBack instanceof File) {
      formData.append('gov_id_back_file', localForm.value.idBack)
    }

    if (localForm.value.selfiePhoto instanceof File) {
      formData.append('selfie_with_id_file', localForm.value.selfiePhoto)
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
      province: 'Cavite',
      latitude: localForm.value.businessAddress?.latitude || '',
      longitude: localForm.value.businessAddress?.longitude || '',
      contact_person: authStore.user.fname + ' ' + authStore.user.lname
    }

    const storeResponse = await axios.post('api/stores/register', payload, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Content-Type': 'application/json'
      }
    })

    storeId.value = storeResponse.data.store.store_id;

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

    // Update store_id for user
    const updateUser = await axios.put(
      `api/users/${authStore.user.id}`,
      {
        store_id: storeId.value
      },
      {
        headers: {
          'Authorization': `Bearer ${authStore.token}`
        }
      }
    );

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
      await axios.delete(`/api/stores/${storeId.value}`, {
        headers: {
          'Authorization': `Bearer ${authStore.token}`
        }
      });
    } catch (rollbackError) {
      console.error('Failed to rollback store creation:', rollbackError);
      // You might want to notify admin about orphaned store
    }

    if (error.response?.status === 422) {
      // Validation errors
      const errors = error.response.data.errors
      const firstError = Object.values(errors)[0]

      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: Array.isArray(firstError) ? firstError[0] : 'Please check your input',
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