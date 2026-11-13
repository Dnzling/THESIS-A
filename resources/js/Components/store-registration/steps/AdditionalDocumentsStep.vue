<template>
  <section class="space-y-6">
    <div><p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-600">Step 5</p><h2 class="mt-1 text-2xl font-bold text-slate-950">Additional attachments</h2><p class="mt-2 text-sm text-slate-600">Attach optional supporting documents. OCR may suggest a reference number or expiration date; verify any detected values.</p></div>
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-sm text-slate-600">You can add up to five optional documents.</p><Button label="Add Attachment" icon="pi pi-plus" size="small" severity="warn" outlined :disabled="documents.length >= 5" @click="addDocument" /></div>
    <div v-if="documents.length" class="space-y-4">
      <article v-for="(doc, index) in documents" :key="index" class="grid gap-4 rounded-xl border border-slate-200 p-4 md:grid-cols-2">
        <div class="space-y-3"><InputText v-model="doc.label" size="small" fluid placeholder="Document label" @input="sync" /><UploadSection title="Supporting document" description="PDF, JPG, or PNG, up to 10 MB" :file="doc.file" accept=".pdf,.jpg,.jpeg,.png" @upload="file => upload(index, file)" @remove="removeFile(index)" /><p class="text-xs text-slate-500">{{ doc.ocr_message }}</p></div>
        <div class="grid content-start gap-3 sm:grid-cols-2">
          <div><label class="mb-1 block text-xs font-medium text-slate-600">Reference number (optional)</label><InputText v-model="doc.reference_number" size="small" fluid @input="sync" /></div>
          <div><label class="mb-1 block text-xs font-medium text-slate-600">Expiration date (optional)</label><DatePicker v-model="doc.expirationDate" size="small" fluid showIcon dateFormat="mm/dd/yy" @date-select="syncExpiry(index)" /></div>
          <Button class="sm:col-span-2 sm:justify-self-end" label="Remove attachment" icon="pi pi-trash" size="small" severity="danger" text @click="remove(index)" />
        </div>
      </article>
    </div>
    <div v-else class="rounded-xl border border-dashed border-slate-300 py-10 text-center text-sm text-slate-500">No additional attachments. You can continue with the required documents only.</div>
    <div class="flex flex-col-reverse justify-between gap-3 border-t border-slate-200 pt-5 sm:flex-row"><Button label="Back" severity="secondary" outlined @click="$emit('prev')" /><Button label="Next: Review" icon="pi pi-arrow-right" iconPos="right" severity="warn" @click="$emit('next')" /></div>
  </section>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'
import InputText from 'primevue/inputtext'
import UploadSection from '../shared/UploadSection.vue'

const props = defineProps<{ formData: any }>()
const emit = defineEmits<{ (e: 'update:formData', data: any): void; (e: 'prev'): void; (e: 'next'): void }>()
const toast = useToast()
const localDocuments = ref<any[]>((props.formData.additionalDocuments || []).map((doc: any) => ({ ...doc, expirationDate: doc.expires_at ? new Date(`${doc.expires_at}T00:00:00`) : null })))
const documents = computed(() => localDocuments.value)
const sync = () => emit('update:formData', { additionalDocuments: documents.value.map(({ expirationDate, ...doc }) => ({ ...doc })) })
const addDocument = () => { if (documents.value.length < 5) { documents.value.push({ label: '', file: null, reference_number: '', expires_at: '', expirationDate: null, ocr_message: 'OCR runs after you choose a file.' }); sync() } }
const syncExpiry = (index: number) => {
  const date = documents.value[index]?.expirationDate
  if (date instanceof Date) documents.value[index].expires_at = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
  sync()
}
const upload = async (index: number, file: File) => {
  if (file.size > 10 * 1024 * 1024 || !['application/pdf', 'image/jpeg', 'image/png'].includes(file.type)) {
    toast.add({ severity: 'warn', summary: 'Unsupported attachment', detail: 'Choose a PDF, JPG, or PNG file up to 10 MB.', life: 3500 }); return
  }
  const doc = documents.value[index]
  doc.file = file
  doc.label ||= file.name.replace(/\.[^.]+$/, '')
  doc.reference_number = ''
  doc.expires_at = ''
  doc.expirationDate = null
  doc.ocr_message = 'Reading available details...'
  sync()
  const form = new FormData()
  form.append('document_file', file)
  try {
    const { data } = await axiosClient.post('/api/store-verification/additional-document/extract', form)
    if (documents.value[index] !== doc) return
    doc.reference_number = data?.data?.reference_number || ''
    if (data?.data?.expires_at) { doc.expires_at = data.data.expires_at; doc.expirationDate = new Date(`${data.data.expires_at}T00:00:00`) }
    doc.ocr_message = doc.reference_number || doc.expires_at ? 'Possible values detected. Please confirm them.' : 'No reference or expiry detected; enter manually only if present.'
  } catch { doc.ocr_message = 'OCR unavailable. You can still enter values manually.' }
  sync()
}
const removeFile = (index: number) => { documents.value[index].file = null; documents.value[index].reference_number = ''; documents.value[index].expires_at = ''; documents.value[index].expirationDate = null; sync() }
const remove = (index: number) => { documents.value.splice(index, 1); sync() }
</script>
