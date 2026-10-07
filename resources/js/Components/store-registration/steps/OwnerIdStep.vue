<template>
  <form class="space-y-6" @submit.prevent="handleNext">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-600">Step 1</p>
      <h2 class="mt-1 text-2xl font-bold text-slate-950">Owner primary ID</h2>
      <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
        Select the store owner's Philippine ID and upload a clear photo. Submit it for OCR to read the reference number.
      </p>
    </div>

    <section class="rounded-2xl border border-orange-100 bg-orange-50/40 p-5">
      <div class="grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="space-y-4">
          <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">Available ID *</label>
            <Select
              v-model="localForm.primaryIdType"
              :options="idOptions"
              optionLabel="label"
              optionValue="value"
              placeholder="Select primary ID"
              class="w-full"
              @change="resetExtraction"
            />
          </div>

          <div v-if="ocrRead || manualFallback">
            <label class="mb-2 block text-sm font-medium text-slate-700">{{ numberLabel }}</label>
            <p v-if="ocrRead" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">{{ localForm.primaryIdNumber }}</p>
            <InputText
              v-else
              v-model="localForm.primaryIdNumber"
              class="w-full"
              :placeholder="selectedId?.placeholder || 'Enter ID number'"
              @input="syncForm"
            />
            <p v-if="manualFallback" class="mt-1 text-xs text-slate-500">OCR could not read the reference number. Enter it exactly as printed on the ID.</p>
          </div>
          <p v-if="localForm.primaryIdReadMessage" class="rounded-xl bg-white px-3 py-2 text-xs text-orange-700" role="status">{{ localForm.primaryIdReadMessage }}</p>
          <Button type="button" label="Submit ID for OCR" icon="pi pi-search" :loading="reading" :disabled="!localForm.primaryIdType || !hasFrontFile || reading" @click="tryExtractIdNumber(localForm.primaryIdFront)" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <UploadSection
            title="ID front *"
            description="Upload the front side. We will try to read the ID number from this image."
            :file="localForm.primaryIdFront"
            required
            @upload="handleFrontUpload"
            @remove="() => handleFileRemove('primaryIdFront')"
          />

          <UploadSection
            title="ID back"
            description="Upload the back side if the ID has important details there."
            :file="localForm.primaryIdBack"
            @upload="(file) => handleFileUpload(file, 'primaryIdBack')"
            @remove="() => handleFileRemove('primaryIdBack')"
          />
        </div>
      </div>
    </section>

    <div class="flex justify-end border-t border-slate-200 pt-5">
      <Button type="submit" label="Next: Business Registration" icon="pi pi-arrow-right" iconPos="right" severity="warn" :disabled="!isStepValid || reading" />
    </div>
  </form>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import UploadSection from '../shared/UploadSection.vue'
import axiosClient from '@/axios'

const props = defineProps<{ formData: any }>()
const emit = defineEmits<{
  (e: 'update:formData', data: any): void
  (e: 'next'): void
}>()

const toast = useToast()

const idOptions = [
  { value: 'national_id', label: 'PhilSys National ID', placeholder: 'XXXX-XXXX-XXXX-XXXX', helper: 'Use the PhilSys card number exactly as printed.' },
  { value: 'umid', label: 'UMID', placeholder: 'CRN / UMID number', helper: 'Use the CRN or UMID number exactly as printed.' },
  { value: 'sss', label: 'SSS ID', placeholder: 'XX-XXXXXXX-X', helper: 'Use the 10-digit SSS number.' },
  { value: 'tin', label: 'TIN ID', placeholder: 'XXX-XXX-XXX-XXX', helper: 'Use the 9 to 12 digit TIN.' },
  { value: 'driver_license', label: "Driver's License", placeholder: 'NXX-XX-XXXXXX', helper: 'Use the license number exactly as printed.' },
  { value: 'passport', label: 'Philippine Passport', placeholder: 'P1234567A', helper: 'Use the passport number exactly as printed.' },
]

const localForm = ref({ ...props.formData })
const reading = ref(false)
const ocrRead = ref(Boolean(props.formData.primaryIdOcrRead))
const manualFallback = ref(Boolean(props.formData.primaryIdOcrAttempted && !props.formData.primaryIdOcrRead))

const selectedId = computed(() => idOptions.find(option => option.value === localForm.value.primaryIdType))
const numberLabel = computed(() => `${selectedId.value?.label || 'Primary ID'} number *`)
const hasFrontFile = computed(() => typeof File !== 'undefined' && localForm.value.primaryIdFront instanceof File)
const isStepValid = computed(() =>
  Boolean(localForm.value.primaryIdType)
  && Boolean(String(localForm.value.primaryIdNumber || '').trim())
  && hasFrontFile.value
  && (ocrRead.value || manualFallback.value)
)

const syncForm = () => emit('update:formData', { ...localForm.value })
const resetExtraction = () => {
  ocrRead.value = false
  manualFallback.value = false
  localForm.value.primaryIdOcrRead = false
  localForm.value.primaryIdOcrAttempted = false
  localForm.value.primaryIdNumber = ''
  localForm.value.primaryIdReadMessage = ''
  syncForm()
}

const validateFile = (file: File) => {
  if (file.size > 5 * 1024 * 1024) {
    toast.add({ severity: 'error', summary: 'File too large', detail: 'Please upload files smaller than 5MB.', life: 3000 })
    return false
  }

  if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
    toast.add({ severity: 'error', summary: 'Invalid file type', detail: 'Please upload JPG or PNG files only.', life: 3000 })
    return false
  }

  return true
}

const tryExtractIdNumber = async (file: File) => {
  if (!localForm.value.primaryIdType || !(file instanceof File)) return
  resetExtraction()

  const payload = new FormData()
  const idType = localForm.value.primaryIdType
  payload.append('id_type', idType)
  payload.append('id_file', file)

  try {
    reading.value = true
    const response = await axiosClient.post('/api/store-verification/owner-id/extract', payload, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    console.log('[Store verification OCR] Owner primary ID:', response.data?.data ?? response.data)
    if (localForm.value.primaryIdFront !== file || localForm.value.primaryIdType !== idType) return
    const extractedNumber = String(response?.data?.data?.id_number || '').trim()
    if (extractedNumber) {
      localForm.value.primaryIdNumber = extractedNumber
      ocrRead.value = true
      localForm.value.primaryIdOcrRead = true
    } else {
      manualFallback.value = true
    }
    localForm.value.primaryIdOcrAttempted = true
    localForm.value.primaryIdReadMessage = response?.data?.data?.message || 'Please confirm the ID number before submitting.'
    syncForm()
  } catch (error) {
    if (localForm.value.primaryIdFront !== file || localForm.value.primaryIdType !== idType) return
    manualFallback.value = true
    localForm.value.primaryIdOcrAttempted = true
    localForm.value.primaryIdReadMessage = 'We could not read this image clearly. Please enter the ID number manually.'
    syncForm()
  } finally {
    reading.value = false
  }
}

const handleFileUpload = (file: File, field: string) => {
  if (!validateFile(file)) return
  localForm.value[field] = file
  syncForm()
}

const handleFrontUpload = (file: File) => {
  resetExtraction()
  handleFileUpload(file, 'primaryIdFront')
}

const handleFileRemove = (field: string) => {
  localForm.value[field] = null
  if (field === 'primaryIdFront') resetExtraction()
  syncForm()
}

const handleNext = () => {
  if (!isStepValid.value) {
    toast.add({ severity: 'warn', summary: 'Complete owner ID', detail: 'Upload the front of the ID and submit it for OCR. If OCR fails, enter its reference number.', life: 3500 })
    return
  }
  emit('next')
  window.scrollTo(0, 0)
}
</script>
