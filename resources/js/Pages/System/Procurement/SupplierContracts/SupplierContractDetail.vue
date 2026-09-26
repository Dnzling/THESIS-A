<template>
  <div class="mx-auto min-h-screen max-w-7xl space-y-4 px-4 py-5 text-sm md:px-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="flex flex-wrap justify-between items-start gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-orange-600">Procurement / Supplier contract</p>
          <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="text-xl font-semibold text-slate-900">{{ contract?.contract_title || 'Supplier contract' }}</h1>
            <Tag :value="contract?.status?.toUpperCase() || 'DRAFT'" :severity="statusSeverity(contract?.status)" />
          </div>
          <p class="mt-1 text-xs text-slate-500">{{ contract?.contract_number || 'No contract number' }} <span class="mx-1 text-slate-300">/</span> {{ contract?.supplier?.supplier_name || 'Supplier not set' }}</p>
        </div>
        
        <div class="flex flex-wrap gap-2">
          <Button
            v-if="contract?.contract_file_path"
            label="View Document"
            icon="pi pi-file"
            severity="secondary"
            outlined
            @click="openDocDialog"
            size="small"
          />
          <Button label="Report issue" icon="pi pi-flag" severity="danger" outlined size="small" @click="openReportDialog" />
          <Button v-if="!isSupplierRoute && canApproveSupplierContracts && ['draft', 'pending'].includes(contract?.status)" label="Review contract" icon="pi pi-eye" severity="warn" size="small" @click="openReviewDialog" />
          <Button v-if="isSupplierRoute && contract?.status === 'pending' && contract?.submitted_by_type !== 'supplier'" label="Approve" icon="pi pi-check-circle" severity="success"
            :loading="activating" @click="activateContract" size="small" />
          <Button v-if="isSupplierRoute && contract?.status === 'pending' && contract?.submitted_by_type !== 'supplier'" label="Reject" icon="pi pi-times-circle" severity="danger" outlined
            @click="openRejectContractDialog" size="small" />
          <Button v-if="contract?.status === 'active'" label="Request Termination" icon="pi pi-ban" severity="danger" outlined
            @click="openTerminateRequestDialog" size="small" />
        </div>
      </div>
    </div>

    <div v-if="loading" class="grid gap-4 md:grid-cols-3">
      <Skeleton height="120px" v-for="n in 3" :key="n" />
      <Skeleton height="340px" class="md:col-span-3" />
    </div>

    <div v-else-if="contract">
      <div v-if="contract.status === 'pending'" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
        <p class="font-semibold text-amber-900">Waiting for contract decision</p>
        <p class="mt-1 text-xs text-amber-800">Submitted by {{ (contract.submitted_by_type || 'store').replace(/_/g, ' ') }}. Review the terms and document before approving or rejecting.</p>
      </div>

      <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs text-slate-500">Supplier</p>
          <p class="mt-1 truncate font-semibold text-slate-900">{{ contract?.supplier?.supplier_name || '-' }}</p>
          <p class="mt-1 text-xs text-slate-500">{{ contract?.supplier?.supplier_code || 'No supplier code' }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs text-slate-500">Validity</p>
          <p class="mt-1 font-semibold text-slate-900">{{ calculateDaysRemaining(contract?.end_date) }}</p>
          <p class="mt-1 text-xs text-slate-500">Ends {{ formatDate(contract?.end_date) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs text-slate-500">Volume discount</p>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ contract?.discount_percentage || 0 }}%</p>
          <p class="mt-1 text-xs text-slate-500">Applied to PO subtotal</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
          <p class="text-xs text-slate-500">Tax</p>
          <p class="mt-1 text-lg font-semibold text-slate-900">{{ contract?.is_tax_exempt ? 'Exempt' : `${contract?.tax_rate || 0}%` }}</p>
          <p class="mt-1 text-xs text-slate-500">After discount</p>
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
        <p v-if="contract?.rejected_by" class="text-xs text-rose-700 mt-1">Rejected by: {{ [contract.rejected_by.fname, contract.rejected_by.lname].filter(Boolean).join(' ') }}</p>
        <p v-if="contract?.rejected_at" class="text-xs text-rose-700 mt-1">Rejected at: {{ formatDatetime(contract?.rejected_at) }}</p>
      </div>

      <div class="mb-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="font-semibold text-slate-900 mb-3">Reported issues</h3>
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
      <div class="grid items-start gap-4 lg:grid-cols-2">
        <!-- Supplier Information Section -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="mb-5 font-semibold text-slate-900">Supplier information</h2>
          <div class="grid gap-5 sm:grid-cols-2">
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
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="mb-5 font-semibold text-slate-900">Contract details</h2>
          <div class="grid gap-5 sm:grid-cols-2">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Contract Number</p>
              <p class="text-lg font-mono text-gray-900 bg-gray-50 px-3 py-2 rounded">{{ contract?.contract_number }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Platform</p>
              <p class="text-gray-900">{{ platformName }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Store</p>
              <p class="text-gray-900">{{ contract?.store?.name || contract?.store?.store_code || `Store #${contract?.store_id}` }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Submitted By</p>
              <p class="text-gray-900 capitalize">{{ (contract?.submitted_by_type || 'store').replace(/_/g, ' ') }}</p>
            </div>
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-2">Created By</p>
              <p class="text-gray-900">{{ creatorName }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDate(contract?.created_at) }}</p>
            </div>
          </div>
        </div>

        <!-- Financial Terms Section -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="mb-5 font-semibold text-slate-900">Commercial terms</h2>
          <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p class="text-xs font-semibold text-green-700 uppercase mb-3">Volume Discount</p>
              <p class="text-3xl font-bold text-green-600">{{ contract?.discount_percentage || 0 }}% OFF</p>
              <p class="text-xs text-green-600 mt-2">Applied to PO subtotal</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
              <p class="text-xs font-semibold text-orange-700 uppercase mb-3">Tax Rate</p>
              <p class="text-3xl font-bold text-orange-600">{{ contract?.is_tax_exempt ? 'Tax Exempt' : `${contract?.tax_rate || 0}%` }}</p>
              <p class="text-xs text-orange-600 mt-2">{{ contract?.tax_note || (contract?.is_tax_exempt ? 'No tax applied' : 'Applied to discounted amount') }}</p>
            </div>
          </div>
          <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-semibold uppercase text-gray-600">Minimum Order Value</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ contract?.minimum_order_value == null ? 'No minimum order' : formatMoney(contract.minimum_order_value) }}</p>
          </div>
        </div>

        <!-- Duration & Validity Section -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="mb-5 font-semibold text-slate-900">Dates &amp; validity</h2>
          <div class="grid gap-3 sm:grid-cols-3">
            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-3">Start Date</p>
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="font-semibold text-slate-900">{{ formatDate(contract?.start_date) }}</p>
                <p class="text-xs text-green-600 mt-2">Contract becomes effective</p>
              </div>
            </div>

            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-3">End Date</p>
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="font-semibold text-slate-900">{{ formatDate(contract?.end_date) }}</p>
                <p class="text-xs text-orange-600 mt-2">Contract expires</p>
              </div>
            </div>

            <div>
              <p class="text-sm font-medium text-gray-600 uppercase mb-3">Duration</p>
              <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="font-semibold text-slate-900">{{ contractDurationDays }} <span class="text-sm">days</span></p>
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
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
          <h2 class="mb-5 font-semibold text-slate-900">Terms &amp; conditions</h2>
          <div class="bg-gray-50 rounded-lg p-6 border border-gray-200 min-h-48 whitespace-pre-wrap text-sm text-gray-700 leading-relaxed">
            {{ contract?.terms_conditions || '✓ No additional terms specified' }}
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="font-semibold text-slate-900">Contract document</h2><p class="mt-1 text-xs text-slate-500">Review the attachment before making a contract decision.</p></div>
            <Button v-if="contractDocUrl" label="View full document" icon="pi pi-external-link" severity="warn" outlined size="small" @click="openDocDialog" />
          </div>
          <div v-if="contractDocUrl" class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3"><div class="flex min-w-0 items-center gap-3"><i :class="isContractImage ? 'pi pi-image' : 'pi pi-file-pdf'" class="text-lg text-orange-600" /><div class="min-w-0"><p class="truncate font-medium text-slate-900">{{ contractDocName }}</p><p class="text-xs text-slate-500">{{ isContractImage ? 'Image attachment' : isContractPdf ? 'PDF attachment' : 'Document attachment' }}</p></div></div><Button label="Open" size="small" severity="secondary" text @click="openDocNewTab" /></div>
            <button v-if="isContractImage" type="button" class="block w-full cursor-zoom-in p-4" @click="openDocDialog"><img :src="contractDocUrl" :alt="contractDocName" class="mx-auto max-h-72 rounded object-contain" /></button>
            <iframe v-else-if="isContractPdf" :src="contractDocUrl" :title="contractDocName" class="h-72 w-full bg-white" />
            <div v-else class="p-6 text-center text-xs text-slate-500">Preview is unavailable for this file type. Open the attachment to view it.</div>
          </div>
          <div v-else class="mt-4 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-xs text-slate-500">No contract document has been attached.</div>
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

    <Dialog v-model:visible="showReviewDialog" modal header="Review supplier contract" class="w-[56rem] max-w-[95vw]">
      <div class="space-y-4 text-sm">
        <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
          <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-orange-700">{{ contract?.contract_number }}</p><h3 class="mt-1 text-lg font-semibold text-slate-900">{{ contract?.contract_title || 'Supplier contract' }}</h3><p class="mt-1 text-xs text-slate-600">Submitted by {{ (contract?.submitted_by_type || 'store').replace(/_/g, ' ') }}</p></div><Tag :value="contract?.status?.toUpperCase() || 'DRAFT'" :severity="statusSeverity(contract?.status)" /></div>
        </div>
        <div class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:grid-cols-2">
          <div><p class="text-xs text-slate-500">Supplier</p><p class="mt-1 font-semibold text-slate-900">{{ contract?.supplier?.supplier_name || '-' }}</p></div>
          <div><p class="text-xs text-slate-500">Store</p><p class="mt-1 font-semibold text-slate-900">{{ contract?.store?.name || '-' }}</p></div>
          <div><p class="text-xs text-slate-500">Effective period</p><p class="mt-1 font-medium text-slate-900">{{ formatDate(contract?.start_date) }} to {{ formatDate(contract?.end_date) }}</p></div>
          <div><p class="text-xs text-slate-500">Minimum order value</p><p class="mt-1 font-medium text-slate-900">{{ contract?.minimum_order_value == null ? 'No minimum order' : formatMoney(contract.minimum_order_value) }}</p></div>
          <div><p class="text-xs text-slate-500">Volume discount</p><p class="mt-1 font-medium text-slate-900">{{ contract?.discount_percentage || 0 }}%</p></div>
          <div><p class="text-xs text-slate-500">Tax</p><p class="mt-1 font-medium text-slate-900">{{ contract?.is_tax_exempt ? 'Tax exempt' : `${contract?.tax_rate || 0}%` }}</p></div>
        </div>
        <div><p class="mb-2 font-semibold text-slate-900">Terms &amp; conditions</p><div class="max-h-40 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-50 p-4 text-xs leading-5 text-slate-700">{{ contract?.terms_conditions || 'No additional terms provided.' }}</div></div>
        <div><div class="mb-2 flex flex-wrap items-center justify-between gap-2"><p class="font-semibold text-slate-900">Attached document</p><Button v-if="contractDocUrl" label="Open full document" icon="pi pi-external-link" text size="small" severity="warn" @click="openDocDialog" /></div>
          <div v-if="contractDocUrl" class="overflow-hidden rounded-lg border border-slate-200"><img v-if="isContractImage" :src="contractDocUrl" :alt="contractDocName" class="mx-auto max-h-60 object-contain" /><iframe v-else-if="isContractPdf" :src="contractDocUrl" :title="contractDocName" class="h-60 w-full" /><p v-else class="p-5 text-xs text-slate-600">{{ contractDocName }}. Open the file to review it.</p></div>
          <p v-else class="rounded-lg border border-dashed border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">No document attached. Check the terms carefully before approval.</p>
        </div>
        <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-slate-200 p-3 text-xs text-slate-700"><Checkbox v-model="reviewAcknowledged" binary inputId="review-acknowledged" /><span>I have reviewed the supplier, contract dates, commercial terms, and any attached document.</span></label>
      </div>
      <template #footer><div class="flex flex-wrap justify-end gap-2"><Button label="Close" severity="secondary" text size="small" @click="showReviewDialog = false" /><Button v-if="contract?.status === 'pending'" label="Reject with reason" severity="danger" outlined size="small" @click="showReviewDialog = false; openRejectContractDialog()" /><Button v-if="contract?.submitted_by_type === 'supplier'" label="Approve contract" severity="warn" size="small" :disabled="!reviewAcknowledged" :loading="activating" @click="approveReviewedContract" /></div></template>
    </Dialog>

    <Dialog v-model:visible="showRejectContractDialog" header="Reject Contract" :modal="true" class="w-[32rem]">
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium mb-1">Reason</label>
          <Select v-model="rejectContractReason" :options="isSupplierRoute ? supplierRejectReasonOptions : rejectContractReasonOptions" optionLabel="label" optionValue="value" class="w-full" placeholder="Select a reason" />
        </div>
        <div v-if="rejectContractReason === 'other'">
          <label class="block text-sm font-medium mb-1">Custom Reason</label>
          <InputText v-model="rejectContractCustomReason" class="w-full" placeholder="Enter custom reason" />
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Details (optional)</label>
          <Textarea v-model="rejectContractDetails" rows="3" class="w-full" :placeholder="isSupplierRoute ? 'Add details for the store...' : 'Add details for supplier...'" />
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
          <div class="min-w-0 truncate text-sm font-medium text-slate-900">{{ contractDocName }}</div>
          <div class="flex gap-2 shrink-0">
            <Button label="Open New Tab" icon="pi pi-external-link" size="small" outlined @click="openDocNewTab" />
            <Button label="Download" icon="pi pi-download" size="small" severity="secondary" @click="downloadDoc" />
          </div>
        </div>
        <img v-if="isContractImage" :src="contractDocUrl" :alt="contractDocName" class="mx-auto max-h-[70vh] max-w-full rounded object-contain" />
        <iframe v-else-if="isContractPdf" :src="contractDocUrl" :title="contractDocName" class="w-full h-[70vh] rounded border border-slate-200 bg-white" />
        <div v-else class="rounded-lg bg-slate-50 p-8 text-center text-sm text-slate-600">This file type cannot be previewed here. Use Open New Tab or Download.</div>
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
import Skeleton from 'primevue/skeleton'
import procurementService from '../../../../services/procurement.service'
import { useAuthStore } from '../../../../stores/auth'
import axiosClient from '@/axios'
import Select from 'primevue/select'
import Checkbox from 'primevue/checkbox'

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
const showReviewDialog = ref(false)
const reviewAcknowledged = ref(false)
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
const contractDocName = computed(() => {
  const path = String(contract.value?.contract_file_path || '')
  const filename = path.split('?')[0].split('/').pop() || 'Contract attachment'
  try { return decodeURIComponent(filename) } catch { return filename }
})
const isContractImage = computed(() => /\.(png|jpe?g|gif|webp)(\?|$)/i.test(contractDocUrl.value))
const isContractPdf = computed(() => /\.pdf(\?|$)/i.test(contractDocUrl.value))
const respondingTermination = ref(false)
const showRejectTerminationDialog = ref(false)
const terminationRejectNotes = ref('')
const showRejectContractDialog = ref(false)
const rejectingContract = ref(false)
const rejectContractReason = ref('')
const rejectContractCustomReason = ref('')
const rejectContractDetails = ref('')
const platformName = 'FurniSync IMS Platform'
const creatorName = computed(() => {
  const user = contract.value?.created_by?.user || contract.value?.createdBy?.user || contract.value?.created_by || contract.value?.createdBy
  return [user?.fname, user?.lname].filter(Boolean).join(' ') || 'Not recorded'
})
const formatMoney = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value) || 0)
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
  return Math.max(0, Math.min(Math.round((daysFromStart.value / contractDurationDays.value) * 100), 100))
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
    const response = isSupplierRoute.value
      ? await axiosClient.get(`/api/supplier-portal/contracts/${route.params.id}`)
      : await procurementService.getSupplierContract(route.params.id as string)
    const payload = response?.data?.data ?? response?.data ?? response
    if (!payload?.id) throw new Error('Contract details were not returned by the server.')
    contract.value = payload
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

const openReviewDialog = () => {
  reviewAcknowledged.value = false
  showReviewDialog.value = true
}

const approveReviewedContract = async () => {
  if (!reviewAcknowledged.value || !contract.value?.id || contract.value.submitted_by_type !== 'supplier') return
  activating.value = true
  try {
    await procurementService.activateSupplierContract(route.params.id as string)
    showReviewDialog.value = false
    toast.add({ severity: 'success', summary: 'Contract approved', detail: 'The contract is now active.', life: 3000 })
    await loadContract()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Approval failed', detail: error.response?.data?.message || 'Could not approve the contract.', life: 4000 })
  } finally {
    activating.value = false
  }
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
        if (isSupplierRoute.value) {
          await axiosClient.post(`/api/supplier-portal/contracts/${route.params.id}/approve`)
        } else {
          await procurementService.activateSupplierContract(route.params.id as string)
        }
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
const supplierRejectReasonOptions = [
  { label: 'Contract terms need changes', value: 'Contract terms need changes' },
  { label: 'Pricing or discount is not acceptable', value: 'Pricing or discount is not acceptable' },
  { label: 'Dates or duration need changes', value: 'Dates or duration need changes' },
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
        const rejectPath = isSupplierRoute.value
          ? `/api/supplier-portal/contracts/${contract.value.id}/reject`
          : `/api/procurement/supplier-contracts/${contract.value.id}/reject`
        await axiosClient.post(rejectPath, {
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
