<template>
  <div class="max-w-7xl mx-auto space-y-5 py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-3">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" text rounded @click="router.push('/supplier-portal/stores')" />
        <div>
          <h1 class="text-2xl font-semibold text-gray-900">Store Details</h1>
          <p class="text-xs text-gray-500 mt-1">Transactions and contracts with this store.</p>
        </div>
      </div>
      <Button
        v-if="detail?.can_create_contract"
        label="Create Contract"
        icon="pi pi-file-edit"
        size="small"
        @click="goCreateContract"
      />
    </div>

    <Card v-if="loading" class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #content>
        <div class="py-8 text-center text-sm text-gray-500">Loading store details...</div>
      </template>
    </Card>

    <template v-else-if="detail">
      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-gray-500">Store Name</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.name || '-' }}</p>
            </div>
            <div>
              <p class="text-gray-500">Store Code</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.store_code || '-' }}</p>
            </div>
            <div>
              <p class="text-gray-500">Email</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.email || '-' }}</p>
            </div>
            <div>
              <p class="text-gray-500">Phone</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.phone || '-' }}</p>
            </div>
            <div>
              <p class="text-gray-500">City</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.city || '-' }}</p>
            </div>
            <div>
              <p class="text-gray-500">Province</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.province || '-' }}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-gray-500">Address</p>
              <p class="font-semibold text-gray-900">{{ detail.store?.address || '-' }}</p>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #header>
          <div class="px-5 pt-5 pb-1 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Contracts</h2>
            <Button v-if="detail.can_create_contract" label="Create Contract" icon="pi pi-plus" size="small" outlined @click="goCreateContract" />
          </div>
        </template>
        <template #content>
          <DataTable :value="detail.contracts || []" dataKey="id" size="small" stripedRows responsiveLayout="scroll">
            <template #empty>
              <div class="py-5 text-center text-sm text-gray-500">No contracts found for this store.</div>
            </template>
            <Column field="contract_number" header="Contract #" />
            <Column field="contract_title" header="Title" />
            <Column field="created_at" header="Created">
              <template #body="{ data }">{{ formatDateTime(data.created_at) }}</template>
            </Column>
            <Column field="start_date" header="Start">
              <template #body="{ data }">{{ formatDate(data.start_date) }}</template>
            </Column>
            <Column field="end_date" header="End">
              <template #body="{ data }">{{ formatDate(data.end_date) }}</template>
            </Column>
            <Column field="status" header="Status">
              <template #body="{ data }">
                <Tag :value="String(data.status || 'draft').toUpperCase()" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Action">
              <template #body="{ data }">
                <div class="flex items-center gap-2">
                  <Button label="View" icon="pi pi-eye" text size="small" @click="viewContract(data)" />
                  <Button label="Report" icon="pi pi-flag" severity="danger" text size="small" @click="openReportDialog(data)" />
                </div>
              </template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #header>
          <div class="px-5 pt-5 pb-1">
            <h2 class="font-semibold text-gray-900">My Submitted Reports</h2>
          </div>
        </template>
        <template #content>
          <div v-if="myReportsLoading" class="py-4 text-sm text-gray-500">Loading reports...</div>
          <div v-else-if="myReports.length === 0" class="py-4 text-sm text-gray-500">No reports submitted for this store yet.</div>
          <div v-else class="space-y-2">
            <div v-for="rep in myReports" :key="rep.id" class="rounded-lg border border-gray-200 p-3">
              <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-medium text-gray-900">{{ rep.report_reason }}</p>
                <div class="flex items-center gap-2">
                  <Tag :value="String(rep.status || 'pending').toUpperCase()" :severity="rep.status === 'actioned' ? 'success' : 'warning'" />
                  <Button label="View" size="small" text @click="openReportViewDialog(rep)" />
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-1">{{ formatDateTime(rep.created_at) }}</p>
              <div v-if="Array.isArray(rep.evidence_urls) && rep.evidence_urls.length" class="mt-2 flex flex-wrap gap-2">
                <a v-for="(ev, idx) in rep.evidence_urls" :key="`${rep.id}-${idx}`" :href="evidenceUrl(ev)" target="_blank" rel="noopener">
                  <img :src="evidenceUrl(ev)" class="h-12 w-12 rounded border border-gray-200 object-cover" />
                </a>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #header>
          <div class="px-5 pt-5 pb-1">
            <h2 class="font-semibold text-gray-900">PO Transactions</h2>
          </div>
        </template>
        <template #content>
          <DataTable :value="detail.po_transactions || []" dataKey="id" size="small" stripedRows paginator :rows="10" responsiveLayout="scroll">
            <template #empty>
              <div class="py-5 text-center text-sm text-gray-500">No PO transactions found.</div>
            </template>
            <Column header="PO #">
              <template #body="{ data }">{{ data.purchase_order?.po_number || '-' }}</template>
            </Column>
            <Column header="PO Status">
              <template #body="{ data }">{{ data.purchase_order?.status || '-' }}</template>
            </Column>
            <Column field="response" header="Response" />
            <Column field="receipt_status" header="Receipt" />
            <Column field="submitted_at" header="Submitted">
              <template #body="{ data }">{{ formatDateTime(data.submitted_at) }}</template>
            </Column>
          </DataTable>
        </template>
      </Card>

      <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <template #header>
          <div class="px-5 pt-5 pb-1">
            <h2 class="font-semibold text-gray-900">RFQ Transactions</h2>
          </div>
        </template>
        <template #content>
          <DataTable :value="detail.rfq_transactions || []" dataKey="id" size="small" stripedRows paginator :rows="10" responsiveLayout="scroll">
            <template #empty>
              <div class="py-5 text-center text-sm text-gray-500">No RFQ transactions found.</div>
            </template>
            <Column header="RFQ #">
              <template #body="{ data }">{{ data.rfq?.rfq_number || '-' }}</template>
            </Column>
            <Column header="RFQ Title">
              <template #body="{ data }">{{ data.rfq?.title || '-' }}</template>
            </Column>
            <Column field="status" header="Feedback Status" />
            <Column field="quoted_price" header="Quoted Price">
              <template #body="{ data }">{{ money(data.quoted_price) }}</template>
            </Column>
            <Column field="submitted_at" header="Submitted">
              <template #body="{ data }">{{ formatDateTime(data.submitted_at) }}</template>
            </Column>
          </DataTable>
        </template>
      </Card>
    </template>

    <Dialog v-model:visible="showReportDialog" modal header="Report Contract Issue" :style="{ width: '34rem' }">
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium mb-1">Reason</label>
          <Select v-model="reportReason" :options="reportReasonOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="Select a reason" />
        </div>
        <div v-if="reportReason === 'other'">
          <label class="block text-sm font-medium mb-1">Custom Reason</label>
          <InputText v-model="reportCustomReason" class="w-full" placeholder="Enter custom reason" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Details (optional)</label>
          <Textarea v-model="reportDetails" rows="4" class="w-full" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Attachment (image, optional)</label>
          <input type="file" multiple accept="image/*" @change="onReportFilesChanged" class="w-full text-sm" />
          <p class="text-xs text-gray-500 mt-1">{{ reportFiles.length }} file(s) selected</p>
          <ul v-if="reportFiles.length" class="mt-1 text-xs text-gray-600 list-disc ml-5">
            <li v-for="f in reportFiles" :key="f.name">{{ f.name }}</li>
          </ul>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="showReportDialog = false" />
          <Button label="Submit Report" severity="danger" :loading="reportSubmitting" @click="submitReport" />
        </div>
      </template>
    </Dialog>

    <Dialog v-model:visible="showReportViewDialog" modal header="Report Details" :style="{ width: '34rem' }">
      <div v-if="selectedReport" class="space-y-3 text-sm">
        <div>
          <p class="text-xs text-gray-500">Reason</p>
          <p class="font-medium text-gray-900">{{ selectedReport.report_reason || '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Submitted</p>
          <p class="font-medium text-gray-900">{{ formatDateTime(selectedReport.created_at) }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Status</p>
          <Tag :value="String(selectedReport.status || 'pending').toUpperCase()" :severity="selectedReport.status === 'actioned' ? 'success' : 'warning'" />
        </div>
        <div>
          <p class="text-xs text-gray-500">Details</p>
          <p class="text-gray-800 whitespace-pre-wrap">{{ selectedReport.report_details || 'No additional details.' }}</p>
        </div>
        <div v-if="selectedReport.action_reason">
          <p class="text-xs text-gray-500">Admin Action</p>
          <p class="text-emerald-700">{{ selectedReport.action_reason }}</p>
        </div>
        <div v-if="Array.isArray(selectedReport.evidence_urls) && selectedReport.evidence_urls.length">
          <p class="text-xs text-gray-500">Attachments</p>
          <div class="mt-1 flex flex-wrap gap-2">
            <a v-for="(ev, idx) in selectedReport.evidence_urls" :key="`ev-detail-${idx}`" :href="evidenceUrl(ev)" target="_blank" rel="noopener">
              <img :src="evidenceUrl(ev)" class="h-14 w-14 rounded border border-gray-200 object-cover" />
            </a>
          </div>
        </div>
        <div>
          <p class="text-xs text-gray-500 mb-1">Conversation</p>
          <div v-if="reportThreadLoading" class="text-xs text-gray-500">Loading thread...</div>
          <div v-else-if="reportThread.length === 0" class="text-xs text-gray-500">No responses yet.</div>
          <div v-else class="space-y-2 max-h-44 overflow-auto pr-1">
            <div v-for="item in reportThread" :key="item.id" class="rounded border border-gray-200 p-2">
              <p class="text-xs font-semibold text-gray-700">{{ item.responder?.fname }} {{ item.responder?.lname }} <span class="text-gray-400">({{ item.responder_type }})</span></p>
              <p class="text-xs text-gray-700 whitespace-pre-wrap">{{ item.message || '-' }}</p>
              <div v-if="Array.isArray(item.attachments) && item.attachments.length" class="mt-1 flex flex-wrap gap-1">
                <a v-for="(ev, idx) in item.attachments" :key="`thr-${item.id}-${idx}`" :href="evidenceUrl(ev)" target="_blank" rel="noopener">
                  <img :src="evidenceUrl(ev)" class="h-8 w-8 rounded border border-gray-200 object-cover" />
                </a>
              </div>
            </div>
          </div>
        </div>
        <div class="border-t pt-2">
          <p class="text-xs text-gray-500 mb-1">Respond</p>
          <Textarea v-model="reportReplyText" rows="2" class="w-full" placeholder="Write your response..." />
          <input type="file" accept="image/*" multiple @change="onReportReplyFilesChanged" class="mt-2 w-full text-xs" />
          <div class="mt-2 flex justify-end">
            <Button label="Send Response" size="small" :loading="reportReplySubmitting" @click="submitReportReply" />
          </div>
        </div>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" @click="showReportViewDialog = false" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import axiosClient from '@/axios'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const detail = ref<any>(null)
const showReportDialog = ref(false)
const reportSubmitting = ref(false)
const selectedContractId = ref<number | null>(null)
const reportReason = ref('')
const reportCustomReason = ref('')
const reportDetails = ref('')
const reportFiles = ref<File[]>([])
const myReports = ref<any[]>([])
const myReportsLoading = ref(false)
const showReportViewDialog = ref(false)
const selectedReport = ref<any | null>(null)
const reportThread = ref<any[]>([])
const reportThreadLoading = ref(false)
const reportReplyText = ref('')
const reportReplyFiles = ref<File[]>([])
const reportReplySubmitting = ref(false)

const formatDate = (value: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (value: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleString('en-PH', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const money = (value: number | string | null) => {
  const n = Number(value || 0)
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(n)
}

const evidenceUrl = (path: string) => {
  if (!path) return '#'
  if (/^https?:\/\//i.test(path)) return path
  return `/storage/${String(path).replace(/^\/+/, '')}`
}

const statusSeverity = (status: string) => {
  const s = String(status || '').toLowerCase()
  if (s === 'active') return 'success'
  if (s === 'pending') return 'warning'
  if (s === 'rejected') return 'danger'
  if (s === 'terminated') return 'warning'
  if (s === 'completed') return 'info'
  return 'secondary'
}

const loadDetail = async () => {
  loading.value = true
  try {
    const storeId = Number(route.params.storeId)
    const res = await supplierService.getLinkedStoreDetail(storeId)
    detail.value = res?.data ?? null
    await loadMyReports(storeId)
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load store details.', life: 3500 })
    router.push('/supplier-portal/stores')
  } finally {
    loading.value = false
  }
}

const goCreateContract = () => {
  const supplierId = detail.value?.supplier?.id
  const storeId = detail.value?.store?.id
  router.push({
    name: 'supplier.contracts.create',
    query: {
      supplier_id: supplierId,
      supplier_name: detail.value?.supplier?.supplier_name,
      store_id: storeId,
      store_name: detail.value?.store?.name,
      from_supplier_portal: '1',
    },
  })
}

const openReportDialog = (contract: any) => {
  selectedContractId.value = Number(contract?.id || 0) || null
  reportReason.value = ''
  reportCustomReason.value = ''
  reportDetails.value = ''
  reportFiles.value = []
  showReportDialog.value = true
}

const onReportFilesChanged = (event: Event) => {
  const input = event.target as HTMLInputElement
  reportFiles.value = input.files ? Array.from(input.files) : []
}

const submitReport = async () => {
  if (!selectedContractId.value) return
  const finalReason = reportReason.value === 'other' ? reportCustomReason.value.trim() : reportReason.value
  if (!finalReason || finalReason.length < 10) {
    toast.add({ severity: 'warn', summary: 'Reason required', detail: 'Please provide at least 10 characters.', life: 3000 })
    return
  }
  reportSubmitting.value = true
  try {
    const fd = new FormData()
    fd.append('reason', finalReason)
    if (reportDetails.value) fd.append('details', reportDetails.value.trim())
    reportFiles.value.forEach((f) => fd.append('attachments[]', f))
    await axiosClient.post(`/api/supplier-portal/contracts/${selectedContractId.value}/report`, fd)
    toast.add({ severity: 'success', summary: 'Reported', detail: 'Contract report submitted.', life: 3000 })
    showReportDialog.value = false
    const storeId = Number(route.params.storeId)
    await loadMyReports(storeId)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to submit report.',
      life: 3500,
    })
  } finally {
    reportSubmitting.value = false
  }
}

const viewContract = (contract: any) => {
  const id = Number(contract?.id || 0)
  if (!id) return
  router.push({ name: 'supplier.contracts.show', params: { id } })
}

const reportReasonOptions = [
  { label: 'Non-delivery / Service not rendered', value: 'Non-delivery / Service not rendered' },
  { label: 'Quality does not match agreement', value: 'Quality does not match agreement' },
  { label: 'Late fulfillment / timeline breach', value: 'Late fulfillment / timeline breach' },
  { label: 'Payment / billing dispute', value: 'Payment / billing dispute' },
  { label: 'Other', value: 'other' },
]

const loadMyReports = async (storeId: number) => {
  myReportsLoading.value = true
  try {
    const res = await axiosClient.get('/api/violation-reports/my', { params: { store_id: storeId, per_page: 10 } })
    const payload = res?.data ?? {}
    myReports.value = payload?.data?.data ?? payload?.data ?? []
  } catch {
    myReports.value = []
  } finally {
    myReportsLoading.value = false
  }
}

const openReportViewDialog = (rep: any) => {
  selectedReport.value = rep
  loadReportThread(rep?.id)
  showReportViewDialog.value = true
}

const loadReportThread = async (reportId: number) => {
  if (!reportId) return
  reportThreadLoading.value = true
  try {
    const res = await axiosClient.get(`/api/violation-reports/${reportId}/responses`)
    reportThread.value = res?.data?.data ?? []
  } catch {
    reportThread.value = []
  } finally {
    reportThreadLoading.value = false
  }
}

const onReportReplyFilesChanged = (event: Event) => {
  const input = event.target as HTMLInputElement
  reportReplyFiles.value = input.files ? Array.from(input.files) : []
}

const submitReportReply = async () => {
  const reportId = Number(selectedReport.value?.id || 0)
  if (!reportId) return
  if (!reportReplyText.value.trim() && reportReplyFiles.value.length === 0) {
    toast.add({ severity: 'warn', summary: 'Input required', detail: 'Add message or attachment.', life: 2500 })
    return
  }
  reportReplySubmitting.value = true
  try {
    const fd = new FormData()
    if (reportReplyText.value.trim()) fd.append('message', reportReplyText.value.trim())
    reportReplyFiles.value.forEach((f) => fd.append('attachments[]', f))
    await axiosClient.post(`/api/violation-reports/${reportId}/responses`, fd)
    reportReplyText.value = ''
    reportReplyFiles.value = []
    await loadReportThread(reportId)
    toast.add({ severity: 'success', summary: 'Sent', detail: 'Response posted.', life: 2000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to send response.', life: 3000 })
  } finally {
    reportReplySubmitting.value = false
  }
}

onMounted(loadDetail)
</script>
