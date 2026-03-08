<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Business Documents</h2>
  
    <form @submit.prevent="handleNext">
      <div class="space-y-6">
        <!-- Registration Permit -->
        <UploadSection title="Business Registration Permit *"
          description="Upload your DTI/SEC/CDA registration certificate" :file="localForm.registrationPermit"
          @upload="(file) => handleFileUpload(file, 'registrationPermit')"
          @remove="() => handleFileRemove('registrationPermit')" accept=".jpg,.jpeg,.png,.pdf" required />
  
        <!-- Tax Certificate -->
        <UploadSection title="BIR Tax Certificate *" description="Upload your BIR Certificate of Registration"
          :file="localForm.taxCertificate" @upload="(file) => handleFileUpload(file, 'taxCertificate')"
          @remove="() => handleFileRemove('taxCertificate')" accept=".jpg,.jpeg,.png,.pdf" required />
  
        <!-- Mayor's Permit -->
        <UploadSection title="Mayor's/Business Permit *" description="Upload your current Mayor's Permit"
          :file="localForm.mayorPermit" @upload="(file) => handleFileUpload(file, 'mayorPermit')"
          @remove="() => handleFileRemove('mayorPermit')" accept=".jpg,.jpeg,.png,.pdf" required />
      </div>
  
      <!-- Additional Notes -->
      <div class="mt-8">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Additional Notes (Optional)
        </label>
        <textarea v-model="localForm.additionalNotes" rows="3"
          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
          placeholder="Any additional information about your documents..."></textarea>
      </div>
  
      <!-- Navigation Buttons -->
      <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
        <Button type="button" @click="handlePrev" class="w-1/5" severity="contrast" variant="outlined" label="Previous" />
  
        <Button type="submit" class="w-1/5" severity="success" label="Submit & Review" :disabled="!isStepValid" />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import UploadSection from '../shared/UploadSection.vue'

const toast = useToast()

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
  additionalNotes: ''
})

// Update field and emit immediately
const updateField = (field: string, value: any) => {
  localForm.value[field] = value
  emit('update:formData', { ...localForm.value })
}

// Validation
const isStepValid = computed(() => {
  return localForm.value.registrationPermit &&
    localForm.value.taxCertificate &&
    localForm.value.mayorPermit
})

// File handling
const handleFileUpload = (file: File, field: string) => {
  // Validate file
  if (file.size > 5 * 1024 * 1024) {
    toast.add({
      severity: 'error',
      summary: 'File too large',
      detail: 'Please upload files smaller than 5MB',
      life: 3000
    })
    return
  }

  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']
  if (!validTypes.includes(file.type)) {
    toast.add({
      severity: 'error',
      summary: 'Invalid file type',
      detail: 'Please upload JPG, PNG, or PDF files only',
      life: 3000
    })
    return
  }

  updateField(field, file)
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