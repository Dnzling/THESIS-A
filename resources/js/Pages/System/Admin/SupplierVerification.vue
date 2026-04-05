<template>
  <div class="max-w-7xl mx-auto pb-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-2xl font-bold">Supplier Verification</h2>
        <p class="text-sm text-gray-500">Review and verify supplier accounts before approval.</p>
      </div>
    </div>

    <Card>
      <template #content>
        <div class="p-4">
          <p class="text-sm text-gray-600">Suppliers pending verification are listed below.</p>
          <div class="mt-4">
              <div class="mb-3">
                <div class="p-2 bg-white rounded shadow-sm flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <span class="p-input-icon-left">
                      <i class="pi pi-search" />
                      <InputText v-model="searchTerm" placeholder="Search supplier name or email" @keyup.enter="loadPending" />
                    </span>

                    <Select v-model="statusFilter" :options="statusOptions" optionLabel="label" optionValue="value" @change="loadPending" placeholder="Status" style="min-width:160px" />

                    <Button label="Search" icon="pi pi-search" class="p-button-outlined" @click="loadPending" />
                  </div>

                  <div>
                    <Button label="Refresh" icon="pi pi-refresh" class="p-button-text" @click="loadPending" />
                  </div>
                </div>
              </div>

              <DataTable :value="suppliers" emptyMessage="No suppliers">
              <Column field="supplier_name" header="Supplier Name" />
              <Column field="contact_person" header="Contact Person" />
               <Column header="Portal Status">
                 <template #body="{ data }">
                   <Tag :value="(data.verification_status || 'pending').toUpperCase()" :severity="statusSeverity(data.verification_status)" />
                 </template>
               </Column>
              <Column header="Submitted" >
                <template #body="{ data }">
                  <span class="text-sm">{{ new Date(data?.created_at).toLocaleString() }}</span>
                </template>
  
              </Column>
              <Column header="Action" style="width:160px;">
                <template #body="slotProps">
                  <div>
                    <Button label="View" severity="info" class="mr-2" @click="() => navigateTo(slotProps.data.id)" />
                  </div>
                </template>
              </Column>
              </DataTable> 
              <DataTable :value="suppliers" emptyMessage="No suppliers" paginator :rows="15"/>

          </div>
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="viewModal" modal class="w-2/3" :closable="false">
      <template #header>
        <h3 class="text-lg font-semibold">Supplier Details</h3>
      </template>
      <div class="p-4 max-h-[70vh] overflow-auto">
        <p><strong>Code:</strong> {{ displayCode(viewing) }}</p>
        <p><strong>Company Name:</strong> {{ displayName(viewing) }}</p>
        <p><strong>Contact Person:</strong> {{ viewing?.contact_person || viewing?.supplier?.contact_person || '-' }}</p>
        <p><strong>Contact Number:</strong> {{ displayContact(viewing) }}</p>
        <p><strong>Email:</strong> {{ viewing?.user?.email || viewing?.supplier?.email || '-' }}</p>

        <div class="mt-4">
          <p class="font-semibold">Documents</p>
          <div v-if="(viewing?.verificationDocuments || []).length === 0" class="text-sm text-gray-500">No documents uploaded.</div>
          <ul class="mt-2 space-y-2">
            <li v-for="doc in viewing?.verificationDocuments || []" :key="doc.id" class="flex items-center justify-between">
              <div>
                <div class="font-medium">{{ doc.original_filename || doc.document_type }}</div>
                <div class="text-sm text-gray-500">{{ doc.document_type }} • {{ (doc.file_size || 0) }} bytes</div>
              </div>
              <div class="space-x-2">
                <Button label="Download" size="small" @click="downloadDocument(doc.id)" />
                <Button label="View" severity="info" size="small" @click="openDocument(doc)" />
              </div>
            </li>
          </ul>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-between w-full items-center">
          <div class="flex items-center space-x-2">
            <Button label="Approve" severity="success" @click="confirmApprove(viewing.id)" />
            <Button label="Reject" severity="danger" @click="openRejectDialog(viewing.id)" />
            <Button label="Request Resubmission" severity="warning" @click="openResubmitDialog(viewing.id)" />
          </div>
          <Button label="Close" class="p-button-secondary" @click="closeView" />
        </div>
      </template>
    </Dialog>

    <!-- Reject / Resubmit Dialog -->
  <Dialog v-model:visible="actionDialog.visible" header="Action" modal :closable="false">
    <div class="p-4">
      <p class="mb-2">{{ actionDialog.title }}</p>
      <label class="block text-sm font-medium mb-1">Reason / Notes</label>
          <Textarea v-model="actionDialog.reason" rows="4" />
    </div>
    <template #footer>
      <div class="flex justify-end space-x-2">
        <Button label="Cancel" class="p-button-secondary" @click="closeActionDialog" />
        <Button :label="actionDialog.confirmLabel" @click="performAction" :severity="actionDialog.severity" />
      </div>
    </template>
  </Dialog>
    </div>
</template>

<script setup lang="ts">

import { ref, onMounted } from 'vue'
// declare props passed by Inertia/Layout to avoid Extraneous attributes warnings
const props = defineProps({
  errors: { type: Object, required: false },
  auth: { type: Object, required: false },
  routeName: { type: String, required: false },
  routeParams: { type: Object, required: false },
  query: { type: Object, required: false },
  title: { type: String, required: false },
})
import axiosClient from '@/axios'

const suppliers = ref<any[]>([])
const viewing = ref<any | null>(null)
const viewModal = ref(false)

onMounted(() => {
  loadPending()
})

const statusFilter = ref<'all'|'pending'|'approved'|'rejected'>('pending')
const searchTerm = ref('')
const statusOptions = [
  { label: 'All', value: 'all' },
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
]

const actionDialog = ref({
  visible: false,
  type: null as null | 'reject' | 'resubmit' | 'approve',
  id: null as number | null,
  reason: '',
  title: '',
  confirmLabel: 'Confirm',
  severity: 'danger'
})

const loadPending = async () => {
  try {
    const params: any = { per_page: 100 }
    if (statusFilter.value && statusFilter.value !== 'all') params.status = statusFilter.value
    if (searchTerm.value) params.search = searchTerm.value

    // Prefer the supplier-portals API which already contains supplier and portal status
    const res = await axiosClient.get('/api/supplier-verifications', { params })
    const body = res?.data
    let items: any[] = []
    if (body) {
      if (body.data && Array.isArray(body.data.data)) items = body.data.data
      else if (Array.isArray(body.data)) items = body.data
      else if (Array.isArray(body)) items = body
    }

    // Map portals to table rows: include supplier fields where present
    suppliers.value = items.map((p: any) => ({
      id: p.supplier_id || p.portal_id || p.id,
      portal_id: p.id,
      supplier_name: p.supplier_name || p.company_name || p.supplier?.supplier_name,
      contact_person: p.contact_person || p.supplier?.contact_person,
      created_at: p.created_at,
      verificationDocuments: p.verificationDocuments || p.verification_documents || [],
      verification_status: p.status || 'pending',
      supplier: p.supplier || null,
    }))
  } catch (e) {
    console.error('Failed loading suppliers', e)
  }
}

const closeView = () => {
  viewing.value = null
  viewModal.value = false
}

const navigateTo = (id: number) => {
  (globalThis as any).location.href = `/admin/verification/suppliers/${id}`
}


const downloadDocument = (id: number) => {
  // open download link - backend should return binary stream
  const url = `/api/supplier-portal/documents/${id}/download`
  window.open(url, '_blank')
}

const openDocument = (doc: any) => {
  // attempt to open stored file via download route
  const url = `/api/supplier-portal/documents/${doc.id}/download`
  window.open(url, '_blank')
}

const confirmApprove = (id: number) => {
  if (!window.confirm('Approve this supplier?')) return
  approveSupplier(id)
}

const approveSupplier = async (id: number) => {
  confirmAction('Approve supplier?', async () => {
    try {
      await axiosClient.post(`/api/supplier-verifications/${id}/approve`)
      suppliers.value = suppliers.value.filter(s => s.id !== id)
      closeView()
    } catch (e) {
      console.error('Approve failed', e)
      alert('Approve failed')
    }
  })
}

const openRejectPrompt = async (id: number) => {
  const reason = window.prompt('Enter rejection reason (min 5 chars):')
  if (!reason || reason.length < 5) return alert('Rejection reason required (min 5 chars).')
  await rejectSupplier(id, reason)
}

const rejectSupplier = async (id: number, reason: string) => {
  try {
    await axiosClient.post(`/api/supplier-verifications/${id}/reject`, { rejection_reason: reason })
    suppliers.value = suppliers.value.filter(s => s.id !== id)
    alert('Supplier rejected')
  } catch (e) {
    console.error('Reject failed', e)
    alert('Reject failed')
  }
}

const openRejectDialog = (id: number) => {
  actionDialog.value = {
    visible: true,
    type: 'reject',
    id,
    reason: '',
    title: 'Provide rejection reason (min 5 chars)',
    confirmLabel: 'Reject',
    severity: 'danger'
  }
}

const openResubmitDialog = (id: number) => {
  actionDialog.value = {
    visible: true,
    type: 'resubmit',
    id,
    reason: '',
    title: 'Describe what needs resubmission (min 5 chars)',
    confirmLabel: 'Request Resubmission',
    severity: 'warning'
  }
}

const performAction = async () => {
  const ad = actionDialog.value
  if (!ad.id) return
  if (!ad.reason || ad.reason.length < 5) return alert('Reason required (min 5 chars)')

  try {
    if (ad.type === 'reject') {
      await axiosClient.post(`/api/supplier-verifications/${ad.id}/reject`, { rejection_reason: ad.reason })
      suppliers.value = suppliers.value.filter(s => s.id !== ad.id)
      alert('Supplier rejected')
    } else if (ad.type === 'resubmit') {
      await axiosClient.post(`/api/supplier-verifications/${ad.id}/request-resubmission`, { notes: ad.reason })
      suppliers.value = suppliers.value.filter(s => s.id !== ad.id)
      alert('Resubmission requested')
    }
    closeActionDialog()
    closeView()
  } catch (e) {
    console.error('Action failed', e)
    alert('Action failed')
  }
}

const closeActionDialog = () => {
  actionDialog.value = { visible: false, type: null, id: null, reason: '', title: '', confirmLabel: 'Confirm', severity: 'danger' }
}

const confirmAction = (message: string, fn: () => Promise<void>) => {
  if (window.confirm(message)) fn()
}
const displayCode = (portal: any) => {
  if (!portal) return '-'
  const name = portal.supplier_name || portal.company_name || portal.supplier?.supplier_name || portal.user?.email || 'SUPP'
  const short = slugify(name).split('-').slice(0,2).map((s: string) => s.slice(0,3)).join('').toUpperCase()
  return `${short}-${(portal.id || Math.floor(Math.random()*9000)+1000)}`
}

const displayName = (portal: any) => {
  if (!portal) return '-'
  return portal.supplier?.supplier_name || portal.company_name || portal.user?.email || 'Unknown Supplier'
}

const displayContact = (portal: any) => {
  if (!portal) return '-'
  // Prefer the phone (contact number) submitted in the portal
  return portal.phone || portal.supplier?.contact_person || portal.contact_person || portal.user?.phone || 'N/A'
}

const statusSeverity = (status: string|null|undefined) => {
  switch ((status || 'pending')) {
    case 'approved': return 'success'
    case 'rejected': return 'danger'
    case 'pending': return 'warning'
    default: return 'info'
  }
}

</script>

