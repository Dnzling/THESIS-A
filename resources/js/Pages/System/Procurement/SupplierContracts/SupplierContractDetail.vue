<template>
  <div class="p-8 min-h-screen">
    <!-- Header Section -->
    <div class="mb-8">
      
      <div class="flex justify-between items-start gap-6">
        <div>
          <div class="flex items-center gap-3 mb-2">
            <h1 class="text-xl font-bold text-gray-900">{{ contract?.contract_number }}</h1>
            <Tag :value="contract?.status?.toUpperCase() || 'DRAFT'" :severity="statusSeverity(contract?.status)" />
          </div>
          <p class="text-lg text-gray-600">{{ contract?.contract_title }}</p>
        </div>
        
        <div class="flex gap-2">
          <Button
            v-if="contract?.contract_file_path"
            label="View Document"
            icon="pi pi-file"
            severity="secondary"
            outlined
            @click="openDocDialog"
            class="px-6"
          />
          <Button label="Report" icon="pi pi-flag" severity="danger" outlined @click="openReportDialog" class="px-6" />
          <Button v-if="canApproveSupplierContracts && ['draft','pending'].includes(contract?.status)" label="Approve" icon="pi pi-check-circle" severity="success" 
            @click="activateContract" :loading="activating" class="px-6" />
          <Button v-if="canApproveSupplierContracts && contract?.status === 'pending'" label="Reject" icon="pi pi-times-circle" severity="danger" outlined
            @click="openRejectContractDialog" class="px-6" />
          <Button v-if="contract?.status === 'active'" label="Request Termination" icon="pi pi-ban" severity="danger" outlined
            @click="openTerminateRequestDialog" class="px-6" />
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center items-center py-20">
      <ProgressSpinner />
    </div>

    <div v-else-if="contract">
      <!-- Quick Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg p-6 border border-gray-200 shadow-sm hover:shadow-md transition">
          <p class="text-sm font-medium text-gray-600 mb-2">Tax Rate</p>
          <p class="text-2xl font-bold text-orange-600">{{ contract?.tax_rate || 0 }}%</p>
          <p class="text-xs text-gray-500 mt-2">Applied after discount</p>
        </div>

        <div class="bg-white rounded-lg p-6 border border-gray-200 shadow-sm hover:shadow-md transition">
          <p class="text-sm font-medium text-gray-600 mb-2">Volume Discount</p>
          <p class="text-2xl font-bold text-green-600">{{ contract?.discount_percentage || 0 }}%</p>
          <p class="text-xs text-gray-500 mt-2">On all orders</p>
        </div>

        <div class="bg-white rounded-lg p-6 border border-gray-200 shadow-sm hover:shadow-md transition">
          <p class="text-sm font-medium text-gray-600 mb-2">Validity</p>
          <p class="text-2xl font-bold text-blue-600">{{ calculateDaysRemaining(contract?.end_date) }}</p>
          <p class="text-xs text-gray-500 mt-2">Contract status</p>
        </div>
      </div>

      <div v-if="contract?.termination_request_status" class="bg-amber-50 rounded-lg border border-amber-200 p-4 mb-6">
        <div class="flex items-center justify-between gap-3">
          <div>
            <p class="text-sm font-semibold text-amber-800">
              Termination Request: {{ String(contract?.termination_request_status || '').toUpperCase() }}
            </p>
            <p class="text-xs text-amber-700 mt-1">{{ contract?.termination_request_reason || '-' }}</p>
            <p class="text-xs text-amber-700 mt-1">
              Requested by: {{ terminationRequesterLabel }} • {{ formatDatetime(contract?.termination_requested_at) }}
            </p>
            <p v-if="contract?.termination_responded_at" class="text-xs text-amber-700 mt-1">
              Responded: {{ formatDatetime(contract?.termination_responded_at) }}
            </p>
            <p v-if="contract?.termination_response_notes" class="text-xs text-amber-700 mt-1">
              Rejection reason: {{ contract?.termination_response_notes }}
            </p>
          </div>
          <div v-if="canRespondTerminationRequest" class="flex gap-2">
            <Button label="Reject" severity="secondary" size="small" :loading="respondingTermination" @click="openRejectTerminationDialog" />
            <Button label="Accept & Terminate" severity="danger" size="small" :loading="respondingTermination" @click="respondTermination('accepted')" />
          </div>
          <div v-else-if="canEscalateRejectedTermination" class="flex gap-2">
            <Button label="Raise to Admin" severity="danger" size="small" outlined @click="openEscalateFromTermination" />
          </div>
        </div>
      </div>

      <div v-if="contract?.status === 'rejected'" class="bg-rose-50 rounded-lg border border-rose-200 p-4 mb-6">
        <p class="text-sm font-semibold text-rose-800">Contract Rejected</p>
        <p class="text-xs text-rose-700 mt-1">{{ contract?.rejection_reason || 'No reason provided.' }}</p>
        <p v-if="contract?.rejected_at" class="text-xs text-rose-700 mt-1">Rejected at: {{ formatDatetime(contract?.rejected_at) }}</p>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">My Submitted Reports</h3>
        <div v-if="myReportsLoading" class="text-sm text-gray-500">Loading reports...</div>
        <div v-else-if="myReports.length === 0" class="text-sm text-gray-500">No submitted reports for this contract yet.</div>
        <div v-else class="space-y-2">
          <div v-for="rep in myReports" :key="rep.id" class="border border-gray-200 rounded-lg p-3">
            <div class="flex items-center justify-between gap-2">
              <p class="font-medium text-gray-900">{{ rep.report_reason }}</p>
              <div class="flex items-center gap-2">
                <Tag :value="String(rep.status || 'pending').toUpperCase()" :severity="rep.status === 'actioned' ? 'success' : 'warning'" />
                <Button label="View" size="small" text @click="openReportViewDialog(rep)" />
              </div>
            </div>
            <p class="text-xs text-gray-500 mt-1">{{ formatDatetime(rep.created_at) }}</p>
            <p v-if="rep.action_reason" class="text-xs text-green-700 mt-1">Action: {{ rep.action_reason }}</p>
            <div v-if="Array.isArray(rep.evidence_urls) && rep.evidence_urls.length" class="mt-2 flex flex-wrap gap-2">
              <a v-for="(ev, idx) in rep.evidence_urls" :key="`${rep.id}-${idx}`" :href="evidenceUrl(ev)" target="_blank" rel="noopener">
                <img :src="evidenceUrl(ev)" class="h-14 w-14 rounded border border-gray-200 object-cover" />
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Content Sections -->
      <div class="space-y-8">
        <!-- Supplier Information Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Supplier Information</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Supplier Name</p>
              <p class="text-lg font-semibold text-gray-900">{{ contract?.supplier?.supplier_name }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Supplier Code</p>
              <p class="font-mono text-gray-900 bg-gray-50 px-3 py-2 rounded inline-block">{{ contract?.supplier?.supplier_code }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Contact Person</p>
              <p class="text-gray-900">{{ contract?.supplier?.contact_person || '—' }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Email</p>
              <a :href="`mailto:${contract?.supplier?.email}`" class="text-blue-600 hover:underline break-all">
                {{ contract?.supplier?.email || '—' }}
              </a>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Phone</p>
              <p class="text-gray-900">{{ contract?.supplier?.phone || '—' }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Contract Type</p>
              <p class="capitalize text-gray-900">{{ contract?.contract_type?.replace('_', ' ') }}</p>
            </div>
          </div>
        </div>

        <!-- Contract Details Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Contract Details</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Contract Number</p>
              <p class="text-lg font-mono text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ contract?.contract_number }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Platform</p>
              <p class="text-gray-900">{{ platformName }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Created By</p>
              <p class="text-gray-900">{{ contract?.createdBy?.fname }} {{ contract?.createdBy?.lname }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDate(contract?.created_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Financial Terms Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Financial Terms</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-6 border border-green-200">
              <p class="text-xs font-semibold text-green-700 uppercase mb-3">Volume Discount</p>
              <p class="text-3xl font-bold text-green-600">{{ contract?.discount_percentage || 0 }}% OFF</p>
              <p class="text-xs text-green-600 mt-2">Applied to PO subtotal</p>
            </div>
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-6 border border-orange-200">
              <p class="text-xs font-semibold text-orange-700 uppercase mb-3">Tax Rate</p>
              <p class="text-3xl font-bold text-orange-600">{{ contract?.tax_rate || 0 }}%</p>
              <p class="text-xs text-orange-600 mt-2">Applied to discounted amount</p>
            </div>
          </div>
        </div>

        <!-- Duration & Validity Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Duration & Validity</h2>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-3">Start Date</p>
              <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <p class="text-2xl font-bold text-green-700">{{ formatDate(contract?.start_date) }}</p>
                <p class="text-xs text-green-600 mt-2">Contract becomes effective</p>
              </div>
            </div>

            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-3">End Date</p>
              <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                <p class="text-2xl font-bold text-orange-700">{{ formatDate(contract?.end_date) }}</p>
                <p class="text-xs text-orange-600 mt-2">Contract expires</p>
              </div>
            </div>

            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-3">Duration</p>
              <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <p class="text-2xl font-bold text-blue-700">{{ contractDurationDays }} <span class="text-sm">days</span></p>
                <p class="text-xs text-blue-600 mt-2">≈ {{ Math.ceil(contractDurationDays / 30) }} months</p>
              </div>
            </div>
          </div>

          <div class="mt-8 border-t pt-8">
            <h3 class="font-semibold text-gray-900 mb-4">Progress Timeline</h3>
            <div class="bg-gray-50 rounded-lg p-4">
              <div class="flex justify-between text-sm text-gray-600 mb-3">
                <span>Started {{ daysFromStart }} days ago</span>
                <span>{{ calculateProgressPercentage }}% complete</span>
              </div>
              <ProgressBar :value="calculateProgressPercentage" class="w-full" />
            </div>
          </div>
        </div>

        <!-- Terms & Conditions Section -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8">
          <h2 class="text-xl font-bold text-gray-900 mb-6">Terms & Conditions</h2>
          <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 min-h-48 whitespace-pre-wrap text-sm text-gray-700 leading-relaxed">
            {{ contract?.terms_conditions || '✓ No additional terms specified' }}
          </div>
        </div>

      
      </div>
    </div>

    <Dialog v-model:visible="showReportDialog" header="Report Contract Issue" :modal="true" class="w-[36rem]">
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
          <Textarea v-model="reportDetails" rows="4" class="w-full" placeholder="Add specific details..." />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Attachment (image, optional)</label>
          <input type="file" accept="image/*" multiple @change="onReportFilesChanged" class="w-full text-sm" />
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

    <Dialog v-model:visible="showTerminateRequestDialog" header="Request Contract Termination" :modal="true" class="w-[36rem]">
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium mb-1">Reason</label>
          <Select v-model="terminateReason" :options="terminateReasonOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="Select a reason" />
        </div>
        <div v-if="terminateReason === 'other'">
          <label class="block text-sm font-medium mb-1">Custom Reason</label>
          <InputText v-model="terminateCustomReason" class="w-full" placeholder="Enter custom reason" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Details (optional)</label>
          <Textarea v-model="terminateDetails" rows="4" class="w-full" placeholder="Add specific details..." />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Attachment (image, optional)</label>
          <input type="file" accept="image/*" multiple @change="onTerminateFilesChanged" class="w-full text-sm" />
          <p class="text-xs text-gray-500 mt-1">{{ terminateFiles.length }} file(s) selected</p>
          <ul v-if="terminateFiles.length" class="mt-1 text-xs text-gray-600 list-disc ml-5">
            <li v-for="f in terminateFiles" :key="f.name">{{ f.name }}</li>
          </ul>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="showTerminateRequestDialog = false" />
          <Button label="Submit Request" severity="danger" :loading="terminateSubmitting" @click="submitTerminateRequest" />
        </div>
      </template>
    </Dialog>

    <Dialog v-model:visible="showRejectTerminationDialog" header="Reject Termination Request" :modal="true" class="w-[30rem]">
      <div class="space-y-3">
        <p class="text-sm text-gray-700">
          Please provide the reason for rejecting this termination request.
        </p>
        <div>
          <label class="block text-sm font-medium mb-1">Reason for rejection</label>
          <Textarea v-model="terminationRejectNotes" rows="4" class="w-full" placeholder="Explain why you are rejecting this request..." />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="showRejectTerminationDialog = false" />
          <Button label="Submit Rejection" severity="warning" :loading="respondingTermination" @click="submitRejectTermination" />
        </div>
      </template>
    </Dialog>

    <Dialog v-model:visible="showRejectContractDialog" header="Reject Contract" :modal="true" class="w-[32rem]">
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium mb-1">Reason</label>
          <Select v-model="rejectContractReason" :options="rejectContractReasonOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="Select a reason" />
        </div>
        <div v-if="rejectContractReason === 'other'">
          <label class="block text-sm font-medium mb-1">Custom Reason</label>
          <InputText v-model="rejectContractCustomReason" class="w-full" placeholder="Enter custom reason" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Details (optional)</label>
          <Textarea v-model="rejectContractDetails" rows="3" class="w-full" placeholder="Add details for supplier..." />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="showRejectContractDialog = false" />
          <Button label="Confirm Reject" severity="danger" :loading="rejectingContract" @click="submitRejectContract" />
        </div>
      </template>
    </Dialog>

    <Dialog v-model:visible="showReportViewDialog" header="Report Details" :modal="true" class="w-[34rem]">
      <div v-if="selectedReport" class="space-y-3 text-sm">
        <div>
          <p class="text-xs text-gray-500">Reason</p>
          <p class="font-medium text-gray-900">{{ selectedReport.report_reason || '-' }}</p>
        </div>
        <div>
          <p class="text-xs text-gray-500">Submitted</p>
          <p class="font-medium text-gray-900">{{ formatDatetime(selectedReport.created_at) }}</p>
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
            <a v-for="(ev, idx) in selectedReport.evidence_urls" :key="`detail-ev-${idx}`" :href="evidenceUrl(ev)" target="_blank" rel="noopener">
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

    <Dialog v-model:visible="showDocDialog" header="Contract Document" :modal="true" class="w-[72rem] max-w-[92vw]">
      <div v-if="contractDocUrl" class="space-y-3">
        <div class="flex items-center justify-between gap-3">
          <div class="text-xs text-slate-500 break-all">{{ contractDocUrl }}</div>
          <div class="flex gap-2 shrink-0">
            <Button label="Open New Tab" icon="pi pi-external-link" size="small" outlined @click="openDocNewTab" />
            <Button label="Download" icon="pi pi-download" size="small" severity="secondary" @click="downloadDoc" />
          </div>
        </div>
        <iframe :src="contractDocUrl" class="w-full h-[70vh] rounded border border-slate-200 bg-white" />
      </div>
      <div v-else class="text-sm text-slate-600">No contract document attachment available.</div>
      <template #footer>
        <Button label="Close" severity="secondary" @click="showDocDialog = false" />
      </template>
    </Dialog>
    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import ConfirmDialog from 'primevue/confirmdialog'
import procurementService from '../../../../services/procurement.service'
import { useAuthStore } from '../../../../stores/auth'
import axiosClient from '@/axios'
import Select from 'primevue/select'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const confirm = useConfirm()
const authStore = useAuthStore()
const loading = ref(false)
const activating = ref(false)
const showReportDialog = ref(false)
const reportSubmitting = ref(false)
const reportReason = ref('')
const reportCustomReason = ref('')
const reportDetails = ref('')
const reportFiles = ref<File[]>([])
const myReports = ref<any[]>([])
const myReportsLoading = ref(false)
const showReportViewDialog = ref(false)
const showDocDialog = ref(false)
const selectedReport = ref<any | null>(null)
const reportThread = ref<any[]>([])
const reportThreadLoading = ref(false)
const reportReplyText = ref('')
const reportReplyFiles = ref<File[]>([])
const reportReplySubmitting = ref(false)
const showTerminateRequestDialog = ref(false)
const terminateSubmitting = ref(false)
const terminateReason = ref('')
const terminateCustomReason = ref('')
const terminateDetails = ref('')
const terminateFiles = ref<File[]>([])
const contract = ref<any>(null)

const contractDocUrl = computed(() => {
  const raw = String(contract.value?.contract_file_path || '').trim()
  if (!raw) return ''
  if (raw.startsWith('http://') || raw.startsWith('https://') || raw.startsWith('/')) return raw
  return `/storage/${raw.replace(/^storage\//, '')}`
})
const respondingTermination = ref(false)
const showRejectTerminationDialog = ref(false)
const terminationRejectNotes = ref('')
const showRejectContractDialog = ref(false)
const rejectingContract = ref(false)
const rejectContractReason = ref('')
const rejectContractCustomReason = ref('')
const rejectContractDetails = ref('')
const platformName = 'FurniSync IMS Platform'
const canManageSupplierContracts = computed(() => authStore.hasPermission('procurement.supplier_contracts.manage'))
const canApproveSupplierContracts = computed(() => authStore.hasPermission('procurement.supplier_contracts.approve'))
const isSupplierRoute = computed(() => String(route.path || '').startsWith('/supplier-portal/'))
const mySideType = computed(() => (isSupplierRoute.value ? 'supplier' : 'store_user'))
const canRespondTerminationRequest = computed(() => {
  if (contract.value?.termination_request_status !== 'pending') return false
  const requesterType = String(contract.value?.termination_requested_by_type || '')
  return requesterType !== mySideType.value
})
const terminationRequesterLabel = computed(() => {
  const requesterType = String(contract.value?.termination_requested_by_type || '')
  if (requesterType === 'supplier') return 'Supplier'
  if (requesterType === 'store_user') return 'Store'
  return 'Unknown'
})
const canEscalateRejectedTermination = computed(() => {
  if (String(contract.value?.termination_request_status || '') !== 'rejected') return false
  return String(contract.value?.termination_requested_by_type || '') === mySideType.value
})

const formatDate = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDatetime = (date: string | null): string => {
  if (!date) return 'N/A'
  const d = new Date(date)
  return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) + ' ' + 
    d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' })
}

const calculateDaysRemaining = (deadline: string | null): string => {
  if (!deadline) return 'N/A'
  const deadlineDate = new Date(deadline)
  const today = new Date()
  const days = Math.ceil((deadlineDate.getTime() - today.getTime()) / (1000 * 3600 * 24))
  if (days < 0) return `Expired ${Math.abs(days)}d ago`
  if (days === 0) return 'Expires Today'
  if (days === 1) return 'Expires Tomorrow'
  return `Expires in ${days}d`
}

const daysFromStart = computed(() => {
  if (!contract.value?.start_date) return 0
  const startDate = new Date(contract.value.start_date)
  const today = new Date()
  return Math.ceil((today.getTime() - startDate.getTime()) / (1000 * 3600 * 24))
})

const contractDurationDays = computed(() => {
  if (!contract.value?.start_date || !contract.value?.end_date) return 0
  const start = new Date(contract.value.start_date)
  const end = new Date(contract.value.end_date)
  return Math.ceil((end.getTime() - start.getTime()) / (1000 * 3600 * 24))
})

const calculateProgressPercentage = computed(() => {
  if (contractDurationDays.value === 0) return 0
  return Math.min(Math.round((daysFromStart.value / contractDurationDays.value) * 100), 100)
})

const statusSeverity = (status: string): string => {
  const map: Record<string, string> = {
    active: 'success',
    draft: 'secondary',
    pending: 'warning',
    rejected: 'danger',
    completed: 'info',
    terminated: 'warning',
  }
  return map[status] || 'secondary'
}

const evidenceUrl = (path: string) => {
  if (!path) return '#'
  if (/^https?:\/\//i.test(path)) return path
  return `/storage/${String(path).replace(/^\/+/, '')}`
}

const statusIconClass = (status: string): string => {
  const map: Record<string, string> = {
    active: 'text-green-500 opacity-20',
    draft: 'text-gray-500 opacity-20',
    pending: 'text-amber-500 opacity-20',
    rejected: 'text-rose-500 opacity-20',
    completed: 'text-blue-500 opacity-20',
    terminated: 'text-orange-500 opacity-20',
  }
  return map[status] || 'text-gray-500 opacity-20'
}

const validityStatus = computed(() => {
  if (!contract.value?.end_date) return 'Status Unknown'
  const endDate = new Date(contract.value.end_date)
  const today = new Date()
  if (contract.value.status === 'completed') return 'Completed'
  if (contract.value.status === 'terminated') return 'Terminated'
  if (contract.value.status === 'rejected') return 'Rejected'
  if (contract.value.status === 'draft') return 'Draft - Not Yet Active'
  if (contract.value.status === 'pending') return 'Pending Store Approval'
  if (endDate < today) return 'Completed'
  const daysLeft = Math.ceil((endDate.getTime() - today.getTime()) / (1000 * 3600 * 24))
  if (daysLeft < 30) return 'Expiring Soon'
  return 'Active & Valid'
})

const validityStatusClass = computed(() => {
  const status = validityStatus.value
  if (status === 'Completed') return 'bg-blue-50 border border-blue-200'
  if (status === 'Terminated') return 'bg-orange-50 border border-orange-200'
  if (status === 'Rejected') return 'bg-rose-50 border border-rose-200'
  if (status === 'Expiring Soon') return 'bg-yellow-50 border border-yellow-200'
  if (status === 'Draft - Not Yet Active') return 'bg-gray-50 border border-gray-200'
  return 'bg-green-50 border border-green-200'
})

const validityTextClass = computed(() => {
  const status = validityStatus.value
  if (status === 'Completed') return 'text-blue-700'
  if (status === 'Terminated') return 'text-orange-700'
  if (status === 'Rejected') return 'text-rose-700'
  if (status === 'Expiring Soon') return 'text-yellow-700'
  if (status === 'Draft - Not Yet Active') return 'text-gray-700'
  return 'text-green-700'
})

const validityIcon = computed(() => {
  const status = validityStatus.value
  if (status === 'Completed') return 'pi-check-circle'
  if (status === 'Terminated') return 'pi-times-circle'
  if (status === 'Rejected') return 'pi-times-circle'
  if (status === 'Expiring Soon') return 'pi-exclamation-triangle'
  if (status === 'Draft - Not Yet Active') return 'pi-pencil'
  return 'pi-check-circle'
})

const loadContract = async () => {
  loading.value = true
  try {
    const response = await procurementService.getSupplierContract(route.params.id as string)
    contract.value = response.data
    await loadMyReports()
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load contract', life: 3000 })
    setTimeout(() => router.back(), 2000)
  } finally {
    loading.value = false
  }
}

const loadMyReports = async () => {
  if (!contract.value?.id) return
  myReportsLoading.value = true
  try {
    const res = await axiosClient.get('/api/violation-reports/my', {
      params: { contract_id: contract.value.id, per_page: 10 },
    })
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

const openDocDialog = () => {
  showDocDialog.value = true
}

const openDocNewTab = () => {
  if (!contractDocUrl.value) return
  window.open(contractDocUrl.value, '_blank', 'noopener')
}

const downloadDoc = () => {
  if (!contractDocUrl.value) return
  window.location.href = contractDocUrl.value
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

const editContract = () => {
  router.push({ name: 'procurement.supplier-contracts.edit', params: { id: route.params.id } })
}

const activateContract = async () => {
  confirm.require({
    message: 'Approve this contract and set it to active?',
    header: 'Approve Contract',
    icon: 'pi pi-check-circle',
    accept: async () => {
      activating.value = true
      try {
        await procurementService.activateSupplierContract(route.params.id as string)
        toast.add({ severity: 'success', summary: 'Success', detail: 'Contract approved and activated.', life: 3000 })
        loadContract()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error.response?.data?.message || 'Failed to approve contract',
          life: 3000,
        })
      } finally {
        activating.value = false
      }
    },
  })
}

const openReportDialog = () => {
  showReportDialog.value = true
  reportReason.value = ''
  reportCustomReason.value = ''
  reportDetails.value = ''
  reportFiles.value = []
}

const onReportFilesChanged = (event: Event) => {
  const input = event.target as HTMLInputElement
  reportFiles.value = input.files ? Array.from(input.files) : []
}

const openTerminateRequestDialog = () => {
  showTerminateRequestDialog.value = true
  terminateReason.value = ''
  terminateCustomReason.value = ''
  terminateDetails.value = ''
  terminateFiles.value = []
}

const openRejectTerminationDialog = () => {
  terminationRejectNotes.value = ''
  showRejectTerminationDialog.value = true
}

const rejectContractReasonOptions = [
  { label: 'Incomplete contract details', value: 'Incomplete contract details' },
  { label: 'Invalid dates or duration', value: 'Invalid dates or duration' },
  { label: 'Unacceptable discount/tax terms', value: 'Unacceptable discount/tax terms' },
  { label: 'Supplier compliance issue', value: 'Supplier compliance issue' },
  { label: 'Other', value: 'other' },
]

const openRejectContractDialog = () => {
  rejectContractReason.value = ''
  rejectContractCustomReason.value = ''
  rejectContractDetails.value = ''
  showRejectContractDialog.value = true
}

const submitRejectContract = async () => {
  if (!contract.value?.id) return
  const finalReason = rejectContractReason.value === 'other' ? rejectContractCustomReason.value.trim() : rejectContractReason.value
  if (!finalReason || finalReason.length < 5) {
    toast.add({ severity: 'warn', summary: 'Reason required', detail: 'Please select or provide a rejection reason.', life: 3000 })
    return
  }

  confirm.require({
    message: 'Reject this pending contract submission?',
    header: 'Confirm Rejection',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      rejectingContract.value = true
      try {
        await axiosClient.post(`/api/procurement/supplier-contracts/${contract.value.id}/reject`, {
          reason: finalReason,
          details: rejectContractDetails.value?.trim() || null,
        })
        toast.add({ severity: 'success', summary: 'Rejected', detail: 'Contract has been rejected.', life: 3000 })
        showRejectContractDialog.value = false
        await loadContract()
      } catch (error: any) {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: error?.response?.data?.message || 'Failed to reject contract.',
          life: 3000,
        })
      } finally {
        rejectingContract.value = false
      }
    },
  })
}

const onTerminateFilesChanged = (event: Event) => {
  const input = event.target as HTMLInputElement
  terminateFiles.value = input.files ? Array.from(input.files) : []
}

const submitReport = async () => {
  if (!contract.value?.id) return
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
    const endpoint = String(route.path || '').startsWith('/supplier-portal/')
      ? `/api/supplier-portal/contracts/${contract.value.id}/report`
      : `/api/procurement/supplier-contracts/${contract.value.id}/report`
    await axiosClient.post(endpoint, fd)
    toast.add({ severity: 'success', summary: 'Reported', detail: 'Contract report submitted.', life: 3000 })
    showReportDialog.value = false
    loadMyReports()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to submit report.',
      life: 3000,
    })
  } finally {
    reportSubmitting.value = false
  }
}

const reportReasonOptions = [
  { label: 'Non-delivery / Service not rendered', value: 'Non-delivery / Service not rendered' },
  { label: 'Quality does not match agreement', value: 'Quality does not match agreement' },
  { label: 'Late fulfillment / timeline breach', value: 'Late fulfillment / timeline breach' },
  { label: 'Payment / billing dispute', value: 'Payment / billing dispute' },
  { label: 'Other', value: 'other' },
]

const terminateReasonOptions = [
  { label: 'Breach of contract terms', value: 'Breach of contract terms' },
  { label: 'Repeated delays / non-fulfillment', value: 'Repeated delays / non-fulfillment' },
  { label: 'Quality or service non-compliance', value: 'Quality or service non-compliance' },
  { label: 'Pricing / billing dispute', value: 'Pricing / billing dispute' },
  { label: 'Mutual business decision', value: 'Mutual business decision' },
  { label: 'Other', value: 'other' },
]

const submitTerminateRequest = async () => {
  if (!contract.value?.id) return
  const finalReason = terminateReason.value === 'other' ? terminateCustomReason.value.trim() : terminateReason.value
  if (!finalReason || finalReason.length < 10) {
    toast.add({ severity: 'warn', summary: 'Reason required', detail: 'Please provide at least 10 characters.', life: 3000 })
    return
  }
  terminateSubmitting.value = true
  try {
    const fd = new FormData()
    fd.append('reason', finalReason)
    if (terminateDetails.value) fd.append('details', terminateDetails.value.trim())
    terminateFiles.value.forEach((f) => fd.append('attachments[]', f))
    const endpoint = String(route.path || '').startsWith('/supplier-portal/')
      ? `/api/supplier-portal/contracts/${contract.value.id}/terminate-request`
      : `/api/procurement/supplier-contracts/${contract.value.id}/terminate-request`
    await axiosClient.post(endpoint, fd)
    toast.add({ severity: 'success', summary: 'Submitted', detail: 'Termination request sent to the counterparty.', life: 3000 })
    showTerminateRequestDialog.value = false
    await loadContract()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to submit termination request.',
      life: 3000,
    })
  } finally {
    terminateSubmitting.value = false
  }
}

const respondTermination = async (decision: 'accepted' | 'rejected') => {
  if (!contract.value?.id) return
  respondingTermination.value = true
  try {
    const endpoint = isSupplierRoute.value
      ? `/api/supplier-portal/contracts/${contract.value.id}/terminate-request/respond`
      : `/api/procurement/supplier-contracts/${contract.value.id}/terminate-request/respond`
    await axiosClient.post(endpoint, {
      decision,
      notes: decision === 'rejected' ? terminationRejectNotes.value.trim() : undefined,
    })
    toast.add({
      severity: 'success',
      summary: 'Updated',
      detail: decision === 'accepted' ? 'Termination accepted. Contract terminated.' : 'Termination request rejected.',
      life: 3000,
    })
    showRejectTerminationDialog.value = false
    terminationRejectNotes.value = ''
    await loadContract()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to respond to termination request.',
      life: 3000,
    })
  } finally {
    respondingTermination.value = false
  }
}

const submitRejectTermination = async () => {
  if (terminationRejectNotes.value.trim().length < 10) {
    toast.add({
      severity: 'warn',
      summary: 'Reason required',
      detail: 'Please provide at least 10 characters for rejection reason.',
      life: 3000,
    })
    return
  }
  await respondTermination('rejected')
}

const openEscalateFromTermination = () => {
  reportReason.value = 'Breach of contract terms'
  reportCustomReason.value = ''
  reportDetails.value = [
    'Escalation from termination request.',
    `Termination reason: ${String(contract.value?.termination_request_reason || '-')}`,
    `Counterparty rejection reason: ${String(contract.value?.termination_response_notes || '-')}`,
  ].join('\n')
  reportFiles.value = []
  showReportDialog.value = true
}

const downloadDocument = (filePath: string) => {
  if (filePath) {
    window.location.href = filePath
  }
}

onMounted(() => {
  loadContract()
})
</script>
