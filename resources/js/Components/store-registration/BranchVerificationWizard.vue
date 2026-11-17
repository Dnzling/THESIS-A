<template>
  <div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6">
    <header class="rounded-2xl border border-orange-100 bg-gradient-to-r from-orange-50 to-white p-5">
      <p class="text-xs font-semibold uppercase tracking-wider text-orange-700">Branch verification</p>
      <h1 class="mt-1 text-2xl font-semibold text-slate-900">Verify {{ branch?.name || 'this branch' }}</h1>
      <p class="mt-2 text-sm text-slate-600">Submit the permits for this location. Optional OCR details are suggestions; review and correct them before submitting.</p>
    </header>

    <Message v-if="branch?.status === 'rejected' && rejectionReason" severity="error" :closable="false">Previous submission needs changes: {{ rejectionReason }}</Message>
    <Message v-if="branch?.status === 'pending'" severity="warn" :closable="false">Documents were submitted and are waiting for super-admin review.</Message>
    <Message v-if="branch?.status === 'active'" severity="success" :closable="false">This branch is verified and active.</Message>

    <template v-if="branch?.status !== 'active' && branch?.status !== 'pending'">
      <Card v-if="step === 'documents'" class="border border-slate-200 shadow-sm">
        <template #title>Location documents</template>
        <template #content>
          <div class="grid gap-5 md:grid-cols-2">
            <section v-for="required in requiredDocuments" :key="required.key" class="space-y-3 rounded-xl border border-slate-200 p-4">
              <div><h2 class="text-sm font-semibold text-slate-900">{{ required.label }} <span class="text-red-500">*</span></h2><p class="mt-1 text-xs text-slate-500">PDF, JPG, or PNG, up to 10 MB.</p></div>
              <FileUpload mode="basic" :auto="false" :customUpload="true" :chooseLabel="required.file ? 'Replace file' : 'Choose file'" chooseIcon="pi pi-upload" accept=".pdf,.jpg,.jpeg,.png" :maxFileSize="10485760" @select="event => setRequiredFile(required.key, event.files?.[0])" />
              <p v-if="required.file" class="break-all text-xs text-slate-600">{{ required.file.name }}</p>
              <div class="grid gap-3 sm:grid-cols-2">
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Reference number (if available)</label><InputText v-model="required.reference_number" size="small" fluid placeholder="Optional" /></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Expiration (if available)</label><DatePicker v-model="required.expiryDate" size="small" fluid dateFormat="mm/dd/yy" showIcon @date-select="setRequiredExpiry(required.key)" /></div>
              </div>
              <p class="text-xs text-slate-500">{{ required.ocrMessage }}</p>
            </section>
          </div>

          <section class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="text-sm font-semibold text-slate-900">Additional attachments</h2><p class="mt-1 text-xs text-slate-600">Optional, up to five documents. OCR will suggest a reference number and expiration date when readable.</p></div><Button label="Add attachment" icon="pi pi-plus" size="small" severity="warn" outlined :disabled="extraDocuments.length >= 5" @click="addExtra" /></div>
            <div v-if="extraDocuments.length" class="mt-4 space-y-3">
              <div v-for="(document, index) in extraDocuments" :key="index" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-3 lg:grid-cols-4 lg:items-end">
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Label</label><InputText v-model="document.label" size="small" fluid placeholder="Document name" /></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Reference number</label><InputText v-model="document.reference_number" size="small" fluid placeholder="Optional" /></div>
                <div><label class="mb-1 block text-xs font-medium text-slate-600">Expiration date</label><DatePicker v-model="document.expiryDate" size="small" fluid dateFormat="mm/dd/yy" showIcon @date-select="setExtraExpiry(index)" /></div>
                <div class="flex items-center gap-2"><FileUpload mode="basic" :auto="false" :customUpload="true" :chooseLabel="document.file ? 'Replace' : 'Choose file'" chooseIcon="pi pi-upload" accept=".pdf,.jpg,.jpeg,.png" :maxFileSize="10485760" @select="event => setExtraFile(index, event.files?.[0])" /><Button icon="pi pi-trash" severity="danger" text size="small" aria-label="Remove attachment" @click="extraDocuments.splice(index, 1)" /></div>
                <p class="text-xs text-slate-500 lg:col-span-4">{{ document.file?.name || 'No file selected' }} <span v-if="document.ocrMessage">· {{ document.ocrMessage }}</span></p>
              </div>
            </div>
          </section>
          <div class="mt-6 flex justify-end"><Button label="Review documents" icon="pi pi-arrow-right" iconPos="right" severity="warn" :disabled="!requiredDocuments.every(document => document.file)" @click="step = 'review'" /></div>
        </template>
      </Card>

      <Card v-else class="border border-slate-200 shadow-sm">
        <template #title>Review submitted documents</template>
        <template #content>
          <p class="mb-5 text-sm text-slate-600">Check the attached files and any OCR-suggested details. Only these documents will be sent for branch review.</p>
          <div class="grid gap-4 md:grid-cols-2">
            <article v-for="document in [...requiredDocuments, ...extraDocuments]" :key="document.key || document.label" class="space-y-2 rounded-xl border border-slate-200 p-4">
              <h3 class="text-sm font-semibold text-slate-900">{{ document.label || 'Additional document' }}</h3>
              <p class="break-all text-xs text-slate-600">{{ document.file?.name }}</p>
              <p v-if="document.reference_number" class="text-xs text-slate-600">Reference: <strong>{{ document.reference_number }}</strong></p>
              <p v-if="document.expires_at" class="text-xs text-slate-600">Expires: <strong>{{ document.expires_at }}</strong></p>
            </article>
          </div>
          <Message v-if="submitError" class="mt-4" severity="error" :closable="false">{{ submitError }}</Message>
          <div class="mt-6 flex flex-col-reverse justify-between gap-3 sm:flex-row"><Button label="Back to documents" severity="secondary" outlined @click="step = 'documents'" /><Button label="Submit for verification" icon="pi pi-check" severity="warn" :loading="submitting" @click="submit" /></div>
        </template>
      </Card>
    </template>

    <div class="flex justify-end"><Button label="Back to branch" severity="secondary" text @click="router.visit(`/store/branches/${branchId}`)" /></div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import Card from 'primevue/card'
import DatePicker from 'primevue/datepicker'
import FileUpload from 'primevue/fileupload'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const branchId = Number(route.query.branch_id || 0)
const branch = ref<any>(null)
const rejectionReason = ref('')
const step = ref<'documents' | 'review'>('documents')
const submitting = ref(false)
const submitError = ref('')
const requiredDocuments = ref<any[]>([
  { key: 'registration', label: 'Store Registration', file: null, reference_number: '', expires_at: '', expiryDate: null, ocrMessage: 'OCR is optional; confirm the suggested values.' },
  { key: 'permit', label: 'Store Permit', file: null, reference_number: '', expires_at: '', expiryDate: null, ocrMessage: 'OCR is optional; confirm the suggested values.' },
])
const extraDocuments = ref<any[]>([])

const applyOcr = async (file: File, target: any) => {
  const payload = new FormData()
  payload.append('document_file', file)
  target.ocrMessage = 'Reading available details...'
  try {
    const { data } = await axiosClient.post('/api/branch-verification/extract', payload)
    target.reference_number = data?.data?.reference_number || ''
    if (data?.data?.expires_at) {
      target.expiryDate = new Date(`${data.data.expires_at}T00:00:00`)
      const date = target.expiryDate as Date
      target.expires_at = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
    }
    target.ocrMessage = target.reference_number || target.expires_at ? 'Possible values found; please confirm them.' : 'No reference or expiry found. Enter one manually if present.'
  } catch {
    target.ocrMessage = 'OCR could not read this file. You can enter the values manually.'
  }
}
const setRequiredFile = (key: string, file?: File) => {
  const target = requiredDocuments.value.find(document => document.key === key)
  if (!target || !file || file.size > 10 * 1024 * 1024 || !['application/pdf', 'image/jpeg', 'image/png'].includes(file.type)) return
  target.file = file
  void applyOcr(file, target)
}
const setRequiredExpiry = (key: string) => {
  const target = requiredDocuments.value.find(document => document.key === key)
  const date = target?.expiryDate
  if (target && date instanceof Date) target.expires_at = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}
const addExtra = () => { if (extraDocuments.value.length < 5) extraDocuments.value.push({ label: '', file: null, reference_number: '', expires_at: '', expiryDate: null, ocrMessage: '' }) }
const setExtraFile = (index: number, file?: File) => {
  const target = extraDocuments.value[index]
  if (!target || !file || file.size > 10 * 1024 * 1024 || !['application/pdf', 'image/jpeg', 'image/png'].includes(file.type)) return
  target.file = file
  target.label ||= file.name.replace(/\.[^.]+$/, '')
  void applyOcr(file, target)
}
const setExtraExpiry = (index: number) => {
  const target = extraDocuments.value[index]
  const date = target?.expiryDate
  if (target && date instanceof Date) target.expires_at = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
}
const load = async () => {
  if (!branchId) return
  try {
    const { data } = await axiosClient.get(`/api/branches/${branchId}`)
    branch.value = data?.data
    const response = await axiosClient.get(`/api/branches/${branchId}/verification/status`)
    branch.value.status = response?.data?.data?.status || branch.value.status
    rejectionReason.value = response?.data?.data?.rejection_reason || ''
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to load branch', detail: error?.response?.data?.message || 'Please try again.', life: 3500 })
  }
}
const submit = async () => {
  if (!requiredDocuments.value.every(document => document.file)) return
  submitting.value = true
  submitError.value = ''
  const payload = new FormData()
  payload.append('registration_file', requiredDocuments.value[0].file)
  payload.append('permit_file', requiredDocuments.value[1].file)
  const extras = extraDocuments.value.filter(document => document.file)
  extras.forEach(document => payload.append('additional_files[]', document.file))
  payload.append('required_metadata', JSON.stringify(requiredDocuments.value.map(({ key, reference_number, expires_at }) => ({ key, reference_number, expires_at }))))
  payload.append('additional_metadata', JSON.stringify(extras.map(document => ({ label: document.label || document.file.name, reference_number: document.reference_number || null, expires_at: document.expires_at || null }))))
  try {
    await axiosClient.post(`/api/branches/${branchId}/verification/submit`, payload, { headers: { 'Content-Type': 'multipart/form-data' } })
    toast.add({ severity: 'success', summary: 'Submitted for review', detail: 'Branch permits were sent to the super-admin team.', life: 3500 })
    router.visit(`/store/branches/${branchId}`)
  } catch (error: any) {
    submitError.value = error?.response?.data?.message || Object.values(error?.response?.data?.errors || {})?.flat()?.[0] || 'Unable to submit branch documents.'
  } finally { submitting.value = false }
}

onMounted(load)
</script>
