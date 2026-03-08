<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Owner Identification</h2>
  
    <form @submit.prevent="handleNext">
      <!-- Primary ID Type -->
      <div class="mb-8">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Primary ID Type *
        </label>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <label v-for="idType in idTypes" :key="idType.value" :class="[
                    'p-4 border rounded-lg cursor-pointer transition',
                    localForm.primaryIdType === idType.value
                      ? 'border-blue-500 bg-blue-50'
                      : 'border-gray-300 hover:border-gray-400'
                  ]">
            <input type="radio" v-model="localForm.primaryIdType" :value="idType.value" class="hidden" />
            <div class="flex flex-col items-center">
              <i :class="idType.icon" class="text-2xl mb-2"></i>
              <span class="text-sm font-medium">{{ idType.label }}</span>
            </div>
          </label>
        </div>
      </div>
      <!-- Business Registration Number -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Primary ID Number*
        </label>
        <InputNumber v-model="localForm.primaryIdNumber" type="text" required label="Primary ID"
          placeholder="1234-456-7890" class="w-full mb-6 py-2 " />
      </div>
  
      <!-- Upload Sections -->
      <div class="space-y-6">
        <!-- ID Front -->
        <UploadSection title="ID Front *" description="Upload front side of your ID" :file="localForm.idFront"
          @upload="(file) => handleFileUpload(file, 'idFront')" @remove="() => handleFileRemove('idFront')" required />
  
        <!-- ID Back -->
        <UploadSection title="ID Back *" description="Upload back side of your ID (if applicable)"
          :file="localForm.idBack" @upload="(file) => handleFileUpload(file, 'idBack')"
          @remove="() => handleFileRemove('idBack')" />
  
        <!-- Selfie with ID -->
        <UploadSection title="Selfie with ID *" description="Take a selfie while holding your ID"
          :file="localForm.selfiePhoto" @upload="(file) => handleFileUpload(file, 'selfiePhoto')"
          @remove="() => handleFileRemove('selfiePhoto')" required />
      </div>
  
      <!-- Navigation Buttons -->
      <div class="flex justify-between mt-8 pt-6 border-t border-gray-200">
        <Button type="button" @click="handlePrev" class="w-1/5" severity="contrast" variant="outlined" label="Previous" />
        <Button type="submit" class="w-1/5" severity="contrast" :disabled="!isStepValid" label="Next" />
      </div>
    </form>
  </div>
</template>


<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import UploadSection from '../shared/UploadSection.vue'
import InputNumber from 'primevue/inputnumber'

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

// ID Types
const idTypes = [
  { value: 'umid', label: 'UMID', icon: 'pi pi-id-card' },
  { value: 'driver', label: "Driver's License", icon: 'pi pi-car' },
  { value: 'passport', label: 'Passport', icon: 'pi pi-globe' },
  { value: 'national', label: 'National ID', icon: 'pi pi-flag' }
]

// Local form data - only sync from parent on creation
const localForm = ref({ ...props.formData })

// Update field and emit immediately
const updateField = (field: string, value: any) => {
  localForm.value[field] = value
  emit('update:formData', { ...localForm.value })
}

// Validation
const isStepValid = computed(() => {
  return localForm.value.primaryIdType &&
    localForm.value.idFront &&
    localForm.value.selfiePhoto
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

  const validTypes = ['image/jpeg', 'image/png', 'image/jpg']
  if (!validTypes.includes(file.type)) {
    toast.add({
      severity: 'error',
      summary: 'Invalid file type',
      detail: 'Please upload JPG or PNG files only',
      life: 3000
    })
    return
  }

  updateField(field, file)
}

const handleFileRemove = (field: string) => {
  updateField(field, null)
}

// Update radio button
const handleIdTypeChange = (value: string) => {
  updateField('primaryIdType', value)
}

const handleNext = () => {
  if (isStepValid.value) {
    emit('next')
    window.scrollTo(0, 0)
  }
}

const handlePrev = () => {
  emit('prev')
  window.scrollTo(0, 0)
}
</script>