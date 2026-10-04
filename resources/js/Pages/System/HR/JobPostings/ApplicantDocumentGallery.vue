<template>
  <div>
    <div v-if="documents.length" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
      <button
        v-for="doc in documents"
        :key="doc.id"
        type="button"
        class="group overflow-hidden rounded-xl border border-slate-200 bg-white text-left transition hover:border-orange-300 hover:shadow-sm"
        :disabled="assets[doc.id]?.loading || !assets[doc.id]?.url"
        @click="openDocument(doc)"
      >
        <div class="relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-slate-100">
          <img
            v-if="assets[doc.id]?.url && assets[doc.id]?.mimeType.startsWith('image/')"
            :src="assets[doc.id].url"
            :alt="doc.file_name || doc.document_type || 'Applicant document'"
            class="h-full w-full object-cover transition duration-200 group-hover:scale-[1.03]"
          />
          <div v-else class="flex flex-col items-center gap-2 text-slate-400">
            <i :class="[fileIcon(doc, assets[doc.id]?.mimeType), 'text-3xl']"></i>
            <span class="text-[10px] font-semibold uppercase tracking-wide">{{ fileTypeLabel(doc, assets[doc.id]?.mimeType) }}</span>
          </div>
          <div v-if="assets[doc.id]?.loading" class="absolute inset-0 flex items-center justify-center bg-white/75">
            <ProgressSpinner style="width: 26px; height: 26px" strokeWidth="5" />
          </div>
          <div v-else-if="assets[doc.id]?.error" class="absolute inset-0 flex items-center justify-center bg-white/90 px-3 text-center text-xs text-slate-500">
            Preview unavailable
          </div>
          <span class="absolute bottom-2 right-2 rounded-full bg-white/90 px-2 py-1 text-[10px] font-medium text-slate-600 shadow-sm">
            {{ assets[doc.id]?.url ? 'View' : 'Loading' }}
          </span>
        </div>
        <div class="p-2.5">
          <p class="truncate text-xs font-semibold text-slate-800">{{ doc.document_type || 'Document' }}</p>
          <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ doc.file_name || 'Attachment' }}</p>
        </div>
      </button>
    </div>
    <Message v-else severity="info" :closable="false">No documents uploaded.</Message>

    <Dialog v-model:visible="previewVisible" modal maximizable :header="activeDocument?.file_name || 'Document Preview'" :style="{ width: 'min(78rem, 96vw)' }">
      <div v-if="activeAsset" class="min-h-[65vh]">
        <img
          v-if="activeAsset.mimeType.startsWith('image/')"
          :src="activeAsset.url"
          :alt="activeDocument?.file_name || 'Document preview'"
          class="mx-auto max-h-[68vh] max-w-full rounded-lg object-contain"
        />
        <iframe
          v-else-if="activeAsset.mimeType === 'application/pdf'"
          :src="activeAsset.url"
          class="h-[68vh] w-full rounded-lg border border-slate-200"
          title="Applicant document preview"
        />
        <div v-else class="flex min-h-[65vh] flex-col items-center justify-center gap-3 text-center text-sm text-slate-500">
          <i :class="[fileIcon(activeDocument, activeAsset.mimeType), 'text-4xl']"></i>
          <p>This file type cannot be previewed here.</p>
          <Button label="Download document" icon="pi pi-download" severity="warn" size="small" @click="downloadDocument" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import hrService from '@/services/hr.services'

type ApplicantDocument = {
  id: number | string
  document_type?: string
  file_name?: string
  mime_type?: string
}

type DocumentAsset = {
  url: string
  mimeType: string
  loading: boolean
  error: boolean
}

const props = withDefaults(defineProps<{
  applicationId: number | string
  documents: ApplicantDocument[]
  portal?: boolean
}>(), { portal: false })

const assets = reactive<Record<string, DocumentAsset>>({})
const objectUrls = new Set<string>()
const previewVisible = ref(false)
const activeDocument = ref<ApplicantDocument | null>(null)
const activeAsset = computed(() => activeDocument.value ? assets[activeDocument.value.id] || null : null)
let loadSequence = 0

const releaseAssets = () => {
  objectUrls.forEach((url) => URL.revokeObjectURL(url))
  objectUrls.clear()
  Object.keys(assets).forEach((key) => delete assets[key])
}

const loadAssets = async () => {
  const sequence = ++loadSequence
  releaseAssets()
  await Promise.all(props.documents.map(async (doc) => {
    const key = String(doc.id)
    assets[key] = { url: '', mimeType: doc.mime_type || '', loading: true, error: false }
    try {
      const blob = props.portal
        ? await hrService.downloadApplicantDocument(props.applicationId, doc.id) as Blob
        : await hrService.api.get(`/api/job-applications/${props.applicationId}/documents/${doc.id}`, { responseType: 'blob' }).then((response) => response.data as Blob)
      if (sequence !== loadSequence) return
      const url = URL.createObjectURL(blob)
      objectUrls.add(url)
      assets[key] = {
        url,
        mimeType: blob.type || doc.mime_type || '',
        loading: false,
        error: false,
      }
    } catch (_) {
      if (sequence === loadSequence) {
        assets[key] = { url: '', mimeType: doc.mime_type || '', loading: false, error: true }
      }
    }
  }))
}

const openDocument = (doc: ApplicantDocument) => {
  if (!assets[doc.id]?.url) return
  activeDocument.value = doc
  previewVisible.value = true
}

const fileIcon = (doc?: ApplicantDocument | null, mimeType = '') => {
  const type = `${mimeType} ${doc?.file_name || ''}`.toLowerCase()
  if (type.includes('pdf')) return 'pi pi-file-pdf'
  if (type.includes('word') || type.includes('.doc')) return 'pi pi-file-word'
  return 'pi pi-file'
}

const fileTypeLabel = (doc?: ApplicantDocument | null, mimeType = '') => {
  const type = `${mimeType} ${doc?.file_name || ''}`.toLowerCase()
  if (type.includes('pdf')) return 'PDF'
  if (type.includes('word') || type.includes('.doc')) return 'DOC'
  return 'FILE'
}

const downloadDocument = () => {
  if (!activeDocument.value || !activeAsset.value?.url) return
  const link = document.createElement('a')
  link.href = activeAsset.value.url
  link.download = activeDocument.value.file_name || 'applicant-document'
  link.click()
}

watch(() => [props.applicationId, props.documents] as const, loadAssets, { immediate: true, deep: true })
onBeforeUnmount(() => {
  loadSequence += 1
  releaseAssets()
})
</script>
