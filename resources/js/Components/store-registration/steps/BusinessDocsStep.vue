<template>
  <div>
    <div class="mb-6">
      <p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-600">Step 2</p>
      <h2 class="mt-1 text-2xl font-bold text-slate-950">Business Documents</h2>
      <p class="mt-2 text-sm text-slate-600">Upload the documents needed to validate the store business record.</p>
    </div>
  
    <form @submit.prevent="handleNext">
      <div class="grid gap-5 lg:grid-cols-3">
        <div class="space-y-3">
          <UploadSection title="Business Registration Permit" description="DTI/SEC/CDA registration certificate" :file="localForm.registrationPermit" @upload="(file) => handleFileUpload(file, 'registrationPermit')" @remove="() => handleFileRemove('registrationPermit')" accept=".jpg,.jpeg,.png,.pdf" required />
          <label class="block text-xs font-medium text-slate-600">Expiration date (if applicable)<input v-model="localForm.registrationExpiresAt" type="date" class="mt-1 w-full rounded-lg border border-slate-200 p-2 text-sm" @change="updateField('registrationExpiresAt', localForm.registrationExpiresAt)" /></label>
        </div>
        <div class="space-y-3">
          <UploadSection title="BIR Tax Certificate" description="BIR Certificate of Registration" :file="localForm.taxCertificate" @upload="(file) => handleFileUpload(file, 'taxCertificate')" @remove="() => handleFileRemove('taxCertificate')" accept=".pdf" required />
          <label class="block text-xs font-medium text-slate-600">Expiration date (if applicable)<input v-model="localForm.taxExpiresAt" type="date" class="mt-1 w-full rounded-lg border border-slate-200 p-2 text-sm" @change="updateField('taxExpiresAt', localForm.taxExpiresAt)" /></label>
        </div>
        <div class="space-y-3">
          <UploadSection title="Mayor's/Business Permit" description="Current Mayor's Permit" :file="localForm.mayorPermit" @upload="(file) => handleFileUpload(file, 'mayorPermit')" @remove="() => handleFileRemove('mayorPermit')" accept=".pdf" required />
          <label class="block text-xs font-medium text-slate-600">Expiration date (if applicable)<input v-model="localForm.permitExpiresAt" type="date" class="mt-1 w-full rounded-lg border border-slate-200 p-2 text-sm" @change="updateField('permitExpiresAt', localForm.permitExpiresAt)" /></label>
        </div>
        <div class="lg:col-span-3">
          <label for="business-registration-number" class="mb-2 block text-sm font-medium text-slate-700">Business registration number *</label>
          <InputText id="business-registration-number" :modelValue="localForm.businessRegistrationNumber || ''"
            @update:modelValue="updateField('businessRegistrationNumber', $event)" size="small" class="w-full" placeholder="Enter or confirm the number on your certificate" />
          <p class="mt-1 text-xs text-slate-500">{{ registrationReadMessage }}</p>
        </div>
      </div>

      <section class="mt-7 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div><h3 class="text-sm font-semibold text-slate-900">Additional attachments</h3><p class="mt-1 text-xs text-slate-600">Optional documents can include a reference number or expiry date when OCR can read them. Review the detected values before submitting.</p></div>
          <Button type="button" label="Add Attachment" icon="pi pi-plus" size="small" severity="warn" outlined :disabled="additionalDocuments.length >= 5" @click="addAdditionalDocument" />
        </div>
        <div v-if="additionalDocuments.length" class="mt-4 space-y-3">
          <div v-for="(document, index) in additionalDocuments" :key="index" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-3 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Document label</label>
              <InputText v-model="document.label" size="small" fluid placeholder="e.g. Local business certificate" @input="syncAdditionalDocuments" />
              <p class="mt-1 text-xs text-slate-500">{{ document.file?.name }}</p>
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Reference number (if found)</label>
              <InputText v-model="document.reference_number" size="small" fluid placeholder="Optional" @input="syncAdditionalDocuments" />
            </div>
            <div>
              <label class="mb-1 block text-xs font-medium text-slate-600">Expiration date (if found)</label>
              <DatePicker v-model="document.expirationDate" size="small" fluid dateFormat="mm/dd/yy" showIcon @date-select="syncAdditionalExpiry(index)" />
            </div>
            <div class="flex items-center gap-2">
              <Button type="button" icon="pi pi-upload" size="small" severity="secondary" outlined aria-label="Replace attachment" @click="triggerAdditionalUpload(index)" />
              <Button type="button" icon="pi pi-trash" size="small" severity="danger" text aria-label="Remove attachment" @click="removeAdditionalDocument(index)" />
              <input :ref="(el) => setAdditionalInput(el, index)" type="file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" @change="(event) => uploadAdditionalDocument(index, event)" />
            </div>
            <p class="text-xs text-slate-500 lg:col-span-4">{{ document.ocr_message || 'OCR is optional. You can enter or correct these values manually.' }}</p>
          </div>
        </div>
      </section>
  
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
import DatePicker from 'primevue/datepicker'
import axiosClient from '@/axios'
import UploadSection from '../shared/UploadSection.vue'

const toast = useToast()
const registrationReadMessage = ref('Upload a certificate to try reading its registration number, or enter it manually.')
const additionalInputs = new Map<number, HTMLInputElement>()

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
  ,additionalDocuments: (props.formData.additionalDocuments || []).map((document: any) => ({ ...document, expirationDate: document.expires_at ? new Date(`${document.expires_at}T00:00:00`) : null }))
})

const additionalDocuments = computed(() => localForm.value.additionalDocuments)
const syncAdditionalDocuments = () => updateField('additionalDocuments', additionalDocuments.value.map((document: any) => ({ ...document, expires_at: document.expires_at || '' })))
const syncAdditionalExpiry = (index: number) => {
  const date = additionalDocuments.value[index]?.expirationDate
  if (date instanceof Date) additionalDocuments.value[index].expires_at = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
  syncAdditionalDocuments()
}
const addAdditionalDocument = () => {
  if (additionalDocuments.value.length >= 5) return
  additionalDocuments.value.push({ label: '', file: null as any, reference_number: '', expires_at: '', expirationDate: null, ocr_message: 'Choose a PDF, JPG, or PNG document.' } as any)
  syncAdditionalDocuments()
  triggerAdditionalUpload(additionalDocuments.value.length - 1)
}
const setAdditionalInput = (element: any, index: number) => { if (element instanceof HTMLInputElement) additionalInputs.set(index, element); else additionalInputs.delete(index) }
const triggerAdditionalUpload = (index: number) => additionalInputs.get(index)?.click()
const removeAdditionalDocument = (index: number) => { additionalDocuments.value.splice(index, 1); syncAdditionalDocuments() }
const uploadAdditionalDocument = async (index: number, event: Event) => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  if (file.size > 10 * 1024 * 1024 || !['application/pdf', 'image/jpeg', 'image/png'].includes(file.type)) {
    toast.add({ severity: 'warn', summary: 'Unsupported attachment', detail: 'Choose a PDF, JPG, or PNG file up to 10 MB.', life: 3500 })
    input.value = ''
    return
  }
  const doc = additionalDocuments.value[index]
  doc.file = file
  doc.label = doc.label || file.name.replace(/\\.[^.]+$/, '')
  doc.ocr_message = 'Reading available document details...'
  syncAdditionalDocuments()
  const payload = new FormData()
  payload.append('document_file', file)
  try {
    const { data } = await axiosClient.post('/api/store-verification/additional-document/extract', payload)
    if (additionalDocuments.value[index] !== doc) return
    doc.reference_number = data?.data?.reference_number || ''
    const expiry = data?.data?.expires_at
    if (expiry) {
      doc.expirationDate = new Date(`${expiry}T00:00:00`)
      syncAdditionalExpiry(index)
    }
    doc.ocr_message = doc.reference_number || expiry ? 'OCR found possible details. Confirm them against the document.' : 'No reference number or expiry date was detected. You can enter them manually if present.'
  } catch {
    doc.ocr_message = 'OCR could not read the document. You can still enter its reference number or expiry date manually.'
  }
  syncAdditionalDocuments()
  input.value = ''
}

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
