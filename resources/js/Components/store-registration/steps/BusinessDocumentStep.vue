<template>
  <div class="space-y-6">
    <div>
      <p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-600">Step {{ step }}</p>
      <h2 class="mt-1 text-2xl font-bold text-slate-950">{{ config.title }}</h2>
      <p class="mt-2 text-sm text-slate-600">Upload a clear document. OCR starts automatically; confirm the extracted details before continuing.</p>
    </div>
    <div class="grid gap-6 rounded-2xl border border-orange-100 bg-orange-50/40 p-5 lg:grid-cols-2">
      <UploadSection :title="config.title + ' *'" :description="config.description" :file="localForm[config.file]" :accept="config.accept" required @upload="handleUpload" @remove="handleRemove" />
      <div class="space-y-4">
        <div><label class="mb-2 block text-sm font-medium text-slate-700">Reference number *</label><InputText v-model="localForm[config.number]" class="w-full" placeholder="Number printed on the document" @input="syncForm" /></div>
        <div v-if="config.expiry"><label class="mb-2 block text-sm font-medium text-slate-700">Expiration date *</label><DatePicker v-model="expirationDate" showIcon fluid dateFormat="MM d, yy" :minDate="new Date()" placeholder="Month Day, Year" @update:modelValue="syncExpiration" /></div>
        <p class="rounded-xl bg-white px-3 py-2 text-sm text-slate-600" role="status">{{ readMessage }}</p>
      </div>
    </div>
    <div class="flex justify-between border-t border-slate-200 pt-5"><Button label="Previous" severity="secondary" outlined @click="$emit('prev')" /><Button :label="step === 4 ? 'Next: Review' : 'Next'" severity="warn" icon="pi pi-arrow-right" iconPos="right" :disabled="!canContinue" @click="$emit('next')" /></div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import UploadSection from '../shared/UploadSection.vue'
import axiosClient from '@/axios'

const props = defineProps<{ formData: any; step: number }>()
const emit = defineEmits<{ (e: 'update:formData', data: any): void; (e: 'next'): void; (e: 'prev'): void }>()
const toast = useToast()
const localForm = ref({ ...props.formData })
const reading = ref(false)
const hasRead = ref(Boolean(props.formData[`documentOcrRead${props.step}`]))
const readMessage = ref('Upload a document to read its reference number and expiration date.')
const config = computed(() => ({
  2: { title: 'Business Registration Permit', description: 'DTI, SEC, or CDA certificate (JPG, PNG, PDF)', file: 'registrationPermit', number: 'businessRegistrationNumber', expiry: 'registrationExpiresAt', accept: '.jpg,.jpeg,.png,.pdf', endpoint: '/api/store-verification/business-registration/extract', uploadKey: 'registration_file', type: 'registration', max: 5 },
  3: { title: 'BIR Tax Certificate', description: 'BIR Certificate of Registration (JPG, PNG, PDF)', file: 'taxCertificate', number: 'taxCertificateNumber', expiry: '', accept: '.jpg,.jpeg,.png,.pdf', endpoint: '/api/store-verification/business-document/extract', uploadKey: 'document_file', type: 'bir', max: 10 },
  4: { title: "Mayor's Permit", description: "Current Mayor's or Business Permit (JPG, PNG, PDF)", file: 'mayorPermit', number: 'permitNumber', expiry: 'permitExpiresAt', accept: '.jpg,.jpeg,.png,.pdf', endpoint: '/api/store-verification/business-document/extract', uploadKey: 'document_file', type: 'mayor', max: 10 },
}[props.step] || { title: '', description: '', file: '', number: '', expiry: '', accept: '', endpoint: '', uploadKey: '', type: '', max: 5 }))
const expirationDate = ref<Date | null>(config.value.expiry && localForm.value[config.value.expiry] ? new Date(`${localForm.value[config.value.expiry]}T00:00:00`) : null)
const canContinue = computed(() => hasRead.value && !reading.value && localForm.value[config.value.file] instanceof File && Boolean(String(localForm.value[config.value.number] || '').trim()) && (!config.value.expiry || Boolean(localForm.value[config.value.expiry])))
const syncForm = () => emit('update:formData', { ...localForm.value })
const syncExpiration = () => {
  const date = expirationDate.value
  if (config.value.expiry) localForm.value[config.value.expiry] = date ? `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}` : ''
  syncForm()
}
const handleUpload = async (file: File) => {
  const cfg = config.value
  if (file.size > cfg.max * 1024 * 1024 || !['application/pdf', 'image/jpeg', 'image/png'].includes(file.type)) {
    toast.add({ severity: 'error', summary: 'Invalid document', detail: `Upload a supported file up to ${cfg.max} MB.`, life: 4000 }); return
  }
  hasRead.value = false
  localForm.value[`documentOcrRead${props.step}`] = false
  localForm.value[cfg.file] = file
  localForm.value[cfg.number] = ''
  if (cfg.expiry) { localForm.value[cfg.expiry] = ''; expirationDate.value = null }
  syncForm()
  const payload = new FormData()
  payload.append(cfg.uploadKey, file)
  if (cfg.type !== 'registration') payload.append('document_type', cfg.type)
  reading.value = true
  readMessage.value = 'Reading document...'
  try {
    const { data } = await axiosClient.post(cfg.endpoint, payload)
    console.log(`[Store verification OCR] ${cfg.title}:`, data?.data ?? data)
    if (localForm.value[cfg.file] !== file) return
    localForm.value[cfg.number] = data?.data?.registration_number || data?.data?.reference_number || ''
    const expiry = data?.data?.expires_at
    if (cfg.expiry && expiry) { expirationDate.value = new Date(`${expiry}T00:00:00`); syncExpiration() }
    hasRead.value = true
    localForm.value[`documentOcrRead${props.step}`] = true
    readMessage.value = data?.data?.message || 'OCR finished. Confirm the document details.'
    syncForm()
  } catch (error: any) {
    if (localForm.value[cfg.file] !== file) return
    hasRead.value = true
    localForm.value[`documentOcrRead${props.step}`] = true
    readMessage.value = `${error?.response?.data?.message || 'OCR could not read this document.'} Please enter the reference number${cfg.expiry ? ' and expiration date' : ''} from the document manually.`
    syncForm()
  }
  finally { reading.value = false }
}
const handleRemove = () => {
  hasRead.value = false
  localForm.value[`documentOcrRead${props.step}`] = false
  localForm.value[config.value.file] = null
  localForm.value[config.value.number] = ''
  if (config.value.expiry) { localForm.value[config.value.expiry] = ''; expirationDate.value = null }
  readMessage.value = 'Upload a document to start OCR.'
  syncForm()
}
</script>
