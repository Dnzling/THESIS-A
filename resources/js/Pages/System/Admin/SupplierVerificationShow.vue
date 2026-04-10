<template>
  <div class="max-w-3xl mx-auto py-6">
    <Card>
      <template #header>
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold">Supplier Verification</h2>
            <p class="text-sm text-gray-500">Compact view · Information · Verification · Documents</p>
          </div>
        </div>
      </template>

      <template #content>
        <div class="space-y-4 text-sm">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <div class="text-xs text-gray-500">Code</div>
              <div class="font-medium">{{ supplier?.supplier_code || '-' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Status</div>
              <div class="font-medium">{{ verificationStatus }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Name</div>
              <div class="font-medium">{{ supplier?.supplier_name || supplier?.company_name || '-' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Contact</div>
              <div class="font-medium">{{ supplier?.contact_person || '-' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Email</div>
              <div class="font-medium">{{ supplier?.email || '-' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Phone</div>
              <div class="font-medium">{{ supplier?.phone || supplier?.mobile || '-' }}</div>
            </div>
          </div>

          <Divider />

          <div>
            <div class="text-xs text-gray-500">Address</div>
            <div class="font-medium">{{ supplier?.address || '-' }}</div>
          </div>

          <Divider />

          <div>
            <div class="text-xs text-gray-500 mb-2">Verification Documents</div>
            <div class="grid grid-cols-1 gap-2">
              <div v-for="item in documents" :key="item.id" class="p-2 border rounded flex items-center justify-between text-sm">
                <div class="truncate">
                  <div class="font-medium">{{ item.original_filename || item.document_type }}</div>
                  <div class="text-xs text-gray-500">{{ item.document_type }} • {{ item.file_size || 0 }} bytes</div>
                </div>
                <div class="flex items-center space-x-2">
                  <button class="px-2 py-1 text-xs bg-blue-500 text-white rounded" @click="openDocumentDialog(item)">View</button>
                  <button class="px-2 py-1 text-xs bg-gray-200 rounded" @click="downloadDocument(item.id)">Download</button>
                </div>
              </div>
            </div>
            <div v-if="documents.length===0" class="text-sm text-gray-500">No documents.</div>
          </div>

        </div>
      </template>

      <template #footer>
        <div class="flex justify-end space-x-2">
          <Button label="Back" severity="secondary" size="small" @click="navigateBack" />
          <template v-if="isPending">
            <Button label="Reject" severity="danger" size="small" :disabled="!supplier" @click="openRejectDialog" />
            <Button label="Approve" severity="success" size="small" :disabled="!supplier" @click="openApproveConfirm" />
          </template>
        </div>
      </template>
    </Card>
    
      <Dialog v-model:visible="showDialog" maximized :modal="true" header="Document Preview">
        <div class="h-full w-full flex items-center justify-center bg-gray-50">
          <template v-if="selectedDocument">
            <img v-if="isImage(selectedDocument)" :src="selectedDocumentPreviewUrl" class="max-h-[80vh] max-w-full object-contain" />
            <iframe v-else-if="isPdf(selectedDocument)" :src="selectedDocumentPreviewUrl" class="w-full h-[80vh]" />
            <div v-else class="p-4">Cannot preview this file type. Use Download instead.</div>
          </template>
        </div>
      </Dialog>
      
      <!-- Approve confirmation dialog -->
      <Dialog v-model:visible="showApproveDialog" header="Confirm Approval" :modal="true" :closable="true">
        <div class="py-3">
          <p>Are you sure you want to approve this supplier? This will mark the portal as approved.</p>
        </div>
        <template #footer>
          <div class="flex justify-end space-x-2">
            <Button label="Cancel" severity="secondary" size="small" @click="showApproveDialog=false" />
            <Button label="Confirm" severity="success" size="small" @click="confirmApprove" />
          </div>
        </template>
      </Dialog>

      <!-- Reject dialog with reason -->
      <Dialog v-model:visible="showRejectDialog" header="Reject Supplier" :modal="true" :closable="true">
        <div class="py-2 space-y-2">
          <label class="text-sm text-gray-600">Select rejection reason</label>
          <select v-model="rejectReason" class="w-full mt-2 p-2 border rounded">
            <option value="">-- choose reason --</option>
            <option v-for="opt in rejectOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            <option value="other">Other</option>
          </select>

          <div v-if="rejectReason==='other'">
            <label class="text-sm text-gray-600">Custom reason (min 10 chars)</label>
            <textarea v-model="customRejectReason" class="w-full mt-2 p-2 border rounded" rows="3" placeholder="Enter detailed reason"></textarea>
          </div>
        </div>
        <template #footer>
          <div class="flex justify-end space-x-2">
            <Button label="Cancel" severity="secondary" size="small" @click="showRejectDialog=false" />
            <Button label="Reject" severity="danger" size="small" @click="submitReject" />
          </div>
        </template>
      </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import axiosClient from '@/axios'

const supplier = ref<any | null>(null)
const documents = ref<any[]>([])
const verificationStatus = ref<'pending' | 'approved' | 'rejected'>('pending')
const normalizeStatus = (raw: any) => {
  const s = String(raw || '').toLowerCase()
  if (['approved', 'active'].includes(s)) return 'approved'
  if (['rejected'].includes(s)) return 'rejected'
  return 'pending'
}

const isPending = computed(() => verificationStatus.value === 'pending')
// derive id from current URL if Inertia is not available
const parseIdFromPath = () => {
  try {
    const segments = (globalThis as any).location.pathname.split('/').filter(Boolean)
    // expected path: /system/admin/supplier-verifications/{id} or /admin/verification/suppliers/{id}
    const idSegment = segments[segments.length - 1]
    const maybeId = Number(idSegment)
    return Number.isNaN(maybeId) ? null : maybeId
  } catch {
    return null
  }
}
const id = parseIdFromPath()

const load = async () => {
  if (!id) return
  try {
    // Keep full supplier details from supplier endpoint.
    const detailRes = await axiosClient.get(`/api/suppliers/${id}`)
    const detailPayload = detailRes?.data?.data ?? detailRes?.data ?? null
    supplier.value = detailPayload || {}

    // Prefer portal status (same source used by verification index).
    const portalId =
      supplier.value?.portal?.id ||
      supplier.value?.supplier_portal?.id ||
      supplier.value?.portal_id ||
      null

    try {
      if (portalId) {
        const portalRes = await axiosClient.get(`/api/supplier-verifications/${portalId}`)
        const portalPayload = portalRes?.data?.data ?? portalRes?.data ?? null
        verificationStatus.value = normalizeStatus(portalPayload?.status)
      } else {
        const statusRes = await axiosClient.get(`/api/admin/suppliers/${id}`)
        const statusPayload = statusRes?.data?.data ?? statusRes?.data ?? null
        verificationStatus.value = normalizeStatus(statusPayload?.status)
      }
    } catch {
      verificationStatus.value = normalizeStatus(supplier.value?.status)
    }

    // support multiple possible document sources: verificationDocuments, portal/supplier_portal verification_documents, or inattach
    documents.value = supplier.value.verificationDocuments
      || supplier.value.portal?.verificationDocuments
      || supplier.value.supplierPortal?.verificationDocuments
      || supplier.value.supplier_portal?.verification_documents
      || supplier.value.inattach || []
    // normalize document entries to have id, original_filename, document_type
    documents.value = documents.value.map((d:any) => {
      if (!d) return d
      return {
        id: d.id || d.document_id || d.doc_id,
        original_filename: d.original_filename || d.filename || d.name || d.file_name || d.originalName,
        document_type: d.document_type || d.type || d.category || 'document',
        file_size: d.file_size || d.size || d.length || 0,
        url: d.url || d.path || d.file_path || d.file_path || null,
      }
    })
  } catch (e) {
    console.error('Failed loading supplier show', e)
  }
}

onMounted(load)

const downloadDocument = async (docId: number) => {
  if (!docId) return
  try {
    const res = await axiosClient.get(`/api/supplier-verifications/documents/${docId}/download`, {
      responseType: 'blob'
    })
    const blob = new Blob([res.data], { type: res.headers['content-type'] || 'application/octet-stream' })
    const url = URL.createObjectURL(blob)
    const win = window.open(url, '_blank')
    // revoke after a short delay
    setTimeout(() => URL.revokeObjectURL(url), 60000)
    if (!win) {
      // fallback: force download
      const a = document.createElement('a')
      a.href = url
      a.download = res.headers['content-disposition'] ? res.headers['content-disposition'].split('filename=')[1] || 'file' : 'file'
      document.body.appendChild(a)
      a.click()
      a.remove()
    }
  } catch (e) {
    console.error('Download failed', e)
    alert('Failed to fetch document. You might not have permission or the file is missing.')
  }
}

const openDocument = async (doc: any) => {
  // keep for backward compatibility; prefer dialog
  await downloadDocument(doc.id)
}

// Dialog preview state
const showDialog = ref(false)
const selectedDocument = ref<any | null>(null)
const selectedDocumentPreviewUrl = ref<string | null>(null)

const isImage = (doc: any) => {
  const mime = doc?.file_mime_type || ''
  return /image\//.test(mime) || /\.(png|jpe?g|gif|webp)$/.test(doc?.original_filename || '')
}
const isPdf = (doc: any) => {
  const mime = doc?.file_mime_type || ''
  return /pdf/.test(mime) || /\.pdf$/i.test(doc?.original_filename || '')
}

const openDocumentDialog = async (doc: any) => {
  if (!doc) return
  selectedDocument.value = doc
  try {
    if (doc.id) {
      // fetch blob via authenticated request
      const res = await axiosClient.get(`/api/supplier-verifications/documents/${doc.id}/download`, { responseType: 'blob' })
      const blob = new Blob([res.data], { type: res.headers['content-type'] || 'application/octet-stream' })
      const url = URL.createObjectURL(blob)
      selectedDocumentPreviewUrl.value = url
      showDialog.value = true
      // revoke after dialog closed; leave for 1 minute cleanup
      setTimeout(() => { if (selectedDocumentPreviewUrl.value === url) URL.revokeObjectURL(url) }, 60000)
      return
    }
    if (doc.url && /^(https?:)?\/\//.test(doc.url)) {
      selectedDocumentPreviewUrl.value = doc.url
      showDialog.value = true
      return
    }
  } catch (e) {
    console.error('Preview fetch failed', e)
    alert('Failed to load preview. Use Download instead.')
  }
}

const getPortalId = () => {
  return (
    supplier.value?.portal?.id ||
    supplier.value?.supplier_portal?.id ||
    supplier.value?.portal_id ||
    null
  )
}

const showApproveDialog = ref(false)
const showRejectDialog = ref(false)
const rejectReason = ref('')
const customRejectReason = ref('')
const rejectOptions = ref([
  { value: 'incomplete_documents', label: 'Incomplete or missing documents' },
  { value: 'mismatched_information', label: 'Mismatched supplier information' },
  { value: 'blacklisted', label: 'Supplier is blacklisted' },
  { value: 'invalid_credentials', label: 'Invalid credentials or IDs' },
])

const openApproveConfirm = () => {
  showApproveDialog.value = true
}

const confirmApprove = async () => {
  const portalId = getPortalId()
  if (!portalId) return alert('Cannot determine portal id for approval')
  try {
    await axiosClient.post(`/api/supplier-verifications/${portalId}/approve`)
    alert('Approved')
    showApproveDialog.value = false
    navigateBack()
  } catch (e) { console.error(e); const msg = e?.response?.data?.message || 'Approve failed'; alert(msg) }
}

const openRejectDialog = () => {
  if (!supplier.value) return
  showRejectDialog.value = true
  rejectReason.value = ''
}

const submitReject = async () => {
  const portalId = getPortalId()
  if (!portalId) return alert('Cannot determine portal id for rejection')
  // determine actual reason text
  let reasonText = ''
  if (rejectReason.value === 'other') {
    reasonText = customRejectReason.value || ''
  } else {
    reasonText = rejectOptions.value.find(r => r.value === rejectReason.value)?.label || rejectReason.value || ''
  }
  if (!reasonText || reasonText.length < 10) return alert('Rejection reason must be at least 10 characters.')
  try {
    await axiosClient.post(`/api/supplier-verifications/${portalId}/reject`, { rejection_reason: reasonText })
    alert('Rejected')
    showRejectDialog.value = false
    navigateBack()
  } catch (e) { console.error(e); const msg = e?.response?.data?.message || 'Reject failed'; alert(msg) }
}

const router = (() => {
  try {
    return useRouter()
  } catch {
    return null
  }
})()

const navigateBack = () => {
  if (router) {
    router.push({ path: '/admin/verification/suppliers' })
    return
  }
  window.location.href = '/admin/verification/suppliers'
}
</script>
