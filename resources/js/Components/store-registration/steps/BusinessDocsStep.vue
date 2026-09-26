<template>
  <div>
    <div class="mb-6">
      <p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-600">Step 2</p>
      <h2 class="mt-1 text-2xl font-bold text-slate-950">Business Documents</h2>
      <p class="mt-2 text-sm text-slate-600">Upload the documents needed to validate the store business record.</p>
    </div>
  
    <form @submit.prevent="handleNext">
      <div class="space-y-6">
        <!-- Registration Permit -->
        <UploadSection title="Business Registration Permit *"
          description="Upload your DTI/SEC/CDA registration certificate" :file="localForm.registrationPermit"
          @upload="(file) => handleFileUpload(file, 'registrationPermit')"
          @remove="() => handleFileRemove('registrationPermit')" accept=".jpg,.jpeg,.png,.pdf" required />
        <div>
          <label for="business-registration-number" class="mb-2 block text-sm font-medium text-slate-700">Business registration number *</label>
          <InputText id="business-registration-number" :modelValue="localForm.businessRegistrationNumber || ''"
            @update:modelValue="updateField('businessRegistrationNumber', $event)" size="small" class="w-full" placeholder="Enter or confirm the number on your certificate" />
          <p class="mt-1 text-xs text-slate-500">{{ registrationReadMessage }}</p>
        </div>
  
        <!-- Tax Certificate -->
        <UploadSection title="BIR Tax Certificate *" description="Upload your BIR Certificate of Registration"
          :file="localForm.taxCertificate" @upload="(file) => handleFileUpload(file, 'taxCertificate')"
          @remove="() => handleFileRemove('taxCertificate')" accept=".pdf" required />
  
        <!-- Mayor's Permit -->
        <UploadSection title="Mayor's/Business Permit *" description="Upload your current Mayor's Permit"
          :file="localForm.mayorPermit" @upload="(file) => handleFileUpload(file, 'mayorPermit')"
          @remove="() => handleFileRemove('mayorPermit')" accept=".pdf" required />
      </div>
  
      <!-- Additional Notes -->
      <div class="mt-8">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Additional Notes (Optional)
        </label>
        <textarea v-model="localForm.additionalNotes" rows="3"
          class="w-full rounded-xl border border-orange-100 px-4 py-2 outline-none focus:border-orange-400 focus:ring-2 focus:ring-orange-100"
          @input="updateField('additionalNotes', localForm.additionalNotes)"
          placeholder="Any additional information about your documents..."></textarea>
      </div>
  
      <!-- Navigation Buttons -->
      <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
        <Button type="button" @click="handlePrev" severity="secondary" outlined label="Previous" />
  
        <Button type="button" severity="warn" label="Next: Review" :disabled="!isStepValid" @click="handleNext" />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import InputText from 'primevue/inputtext'
import axiosClient from '@/axios'
import UploadSection from '../shared/UploadSection.vue'

const toast = useToast()
const registrationReadMessage = ref('Upload a certificate to try reading its registration number, or enter it manually.')

interface Props {
  formData: any
}

interface Emits {
  (e: 'update:formData', data: any): void
  (e: 'next'): void
  (e: 'prev'): void
}

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local form data
const localForm = ref({
  ...props.formData,
  additionalNotes: props.formData.additionalNotes || ''
})

// Update field and emit immediately
const updateField = (field: string, value: any) => {
  localForm.value[field] = value
  emit('update:formData', { ...localForm.value })
}

// Validation
const isStepValid = computed(() => {
  return localForm.value.registrationPermit &&
    Boolean(String(localForm.value.businessRegistrationNumber || '').trim()) &&
    localForm.value.taxCertificate &&
    localForm.value.mayorPermit
})

// File handling
const handleFileUpload = (file: File, field: string) => {
  // Validate file
  const maxMb = ['taxCertificate', 'mayorPermit'].includes(field) ? 10 : 5
  if (file.size > maxMb * 1024 * 1024) {
    toast.add({
      severity: 'error',
      summary: 'File too large',
      detail: `Please upload files up to ${maxMb}MB`,
      life: 3000
    })
    return
  }

  const pdfOnlyFields = ['taxCertificate', 'mayorPermit']
  const validTypes = pdfOnlyFields.includes(field)
    ? ['application/pdf']
    : ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']

  if (!validTypes.includes(file.type)) {
    toast.add({
      severity: 'error',
      summary: 'Invalid file type',
      detail: pdfOnlyFields.includes(field)
        ? 'Please upload PDF files only'
        : 'Please upload JPG, PNG, or PDF files only',
      life: 3000
    })
    return
  }

  updateField(field, file)
  if (field === 'registrationPermit') {
    const payload = new FormData()
    payload.append('registration_file', file)
    registrationReadMessage.value = 'Reading registration certificate...'
    axiosClient.post('/api/store-verification/business-registration/extract', payload)
      .then(({ data }) => {
        const number = String(data?.data?.registration_number || '').trim()
        if (number && !localForm.value.businessRegistrationNumber) updateField('businessRegistrationNumber', number)
        registrationReadMessage.value = data?.data?.message || 'Please confirm the registration number.'
      })
      .catch(() => { registrationReadMessage.value = 'Could not read the number. Please enter it manually.' })
  }
}

const handleFileRemove = (field: string) => {
  updateField(field, null)
}

const handleNext = () => {
  if (isStepValid.value) {
    emit('next')
    window.scrollTo(0, 0)
  }
}

const handlePrev = () => {
  window.scrollTo(0, 0)
  emit('prev')
  window.scrollTo(0, 0)
}
</script>
