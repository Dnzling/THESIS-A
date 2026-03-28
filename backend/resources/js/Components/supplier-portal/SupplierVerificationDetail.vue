<template>
  <div v-if="supplier" class="space-y-6">
    <!-- Supplier Info -->
    <Card title="Supplier Information">
      <template #content>
        <div class="grid grid-cols-2 gap-6">
          <div>
            <p class="text-sm font-semibold text-gray-600">Contact Person</p>
            <p>{{ supplier.user?.name }}</p>
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-600">Email</p>
            <p>{{ supplier.user?.email }}</p>
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-600">Status</p>
            <Tag :value="supplier.status" :severity="getStatusSeverity(supplier.status)" />
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-600">Verification Count</p>
            <p>{{ supplier.resubmission_count }}</p>
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-600">Last Submitted</p>
            <p>{{ formatDate(supplier.last_submission_at) }}</p>
          </div>
          <div v-if="supplier.verified_by">
            <p class="text-sm font-semibold text-gray-600">Verified By</p>
            <p>{{ supplier.verified_by?.name }}</p>
          </div>
        </div>

        <Divider />

        <div v-if="supplier.rejection_reason" class="bg-red-50 p-4 rounded border border-red-200">
          <p class="text-sm font-semibold text-red-600 mb-2">Rejection Reason</p>
          <p class="text-red-700">{{ supplier.rejection_reason }}</p>
        </div>
      </template>
    </Card>

    <!-- Documents Review -->
    <Card title="Verification Documents">
      <template #content>
        <Message 
          severity="info"
          text="Review each document and approve or reject. All documents must be approved to verify the supplier."
          class="w-full mb-4"
        />

        <div class="space-y-4">
          <div 
            v-for="doc in supplier.verification_documents" 
            :key="doc.id"
            class="p-4 border rounded"
          >
            <div class="flex justify-between items-start mb-3">
              <div>
                <h4 class="font-semibold">{{ getDocumentTypeLabel(doc.document_type) }}</h4>
                <p class="text-sm text-gray-500">{{ doc.original_filename }}</p>
              </div>
              <Tag 
                :value="doc.status"
                :severity="getDocStatusSeverity(doc.status)"
              />
            </div>

            <div v-if="doc.status === 'pending'" class="space-y-3">
              <div class="flex gap-2">
                <Button 
                  label="Download"
                  icon="pi pi-download"
                  class="p-button-sm p-button-info"
                  @click="downloadDocument(doc.id)"
                />
              </div>

              <div class="bg-blue-50 p-3 rounded">
                <p class="text-sm font-semibold text-blue-600 mb-2">Review Status</p>
                <div class="space-y-2">
                  <div>
                    <RadioButton 
                      v-model="docReview[doc.id]"
                      name="docStatus"
                      value="approved"
                      class="mr-2"
                    />
                    <label>Approve</label>
                  </div>
                  <div>
                    <RadioButton 
                      v-model="docReview[doc.id]"
                      name="docStatus"
                      value="rejected"
                      class="mr-2"
                    />
                    <label>Reject</label>
                  </div>
                </div>
              </div>

              <div v-if="docReview[doc.id] === 'rejected'">
                <label class="block text-sm font-medium mb-2">Rejection Reason</label>
                <Textarea 
                  v-model="docRejectionReason[doc.id]"
                  placeholder="Why are you rejecting this document?"
                  rows="2"
                  class="w-full text-sm"
                />
              </div>

              <div class="flex gap-2">
                <Button 
                  label="Submit Review"
                  icon="pi pi-check"
                  class="p-button-sm p-button-primary"
                  @click="reviewDocument(doc.id)"
                  :loading="reviewingDoc === doc.id"
                />
              </div>
            </div>

            <div v-else-if="doc.status === 'rejected'" class="bg-red-50 p-3 rounded">
              <p class="text-sm font-semibold text-red-600">Rejection Reason</p>
              <p class="text-sm text-red-700">{{ doc.rejection_reason }}</p>
              <p class="text-xs text-red-500 mt-2">Reviewed by: {{ doc.reviewed_by?.name }}</p>
            </div>

            <div v-else-if="doc.status === 'approved'" class="bg-green-50 p-3 rounded">
              <p class="text-sm font-semibold text-green-600">✓ Approved</p>
              <p class="text-xs text-green-500">Reviewed by: {{ doc.reviewed_by?.name }} on {{ formatDate(doc.reviewed_at) }}</p>
            </div>
          </div>
        </div>
      </template>
    </Card>

    <!-- Approval Section -->
    <Card title="Supplier Verification" v-if="supplier.status === 'pending'">
      <template #content>
        <div class="space-y-3">
          <Message 
            severity="warning"
            text="All documents must be approved before you can verify this supplier."
            class="w-full"
          />

          <Button 
            label="Approve Supplier"
            icon="pi pi-check"
            class="w-full p-button-success"
            :disabled="!allDocumentsApproved"
            @click="$emit('approve', supplier.id)"
          />

          <Button 
            label="Reject Supplier"
            icon="pi pi-times"
            class="w-full p-button-danger"
            @click="$emit('reject', supplier)"
          />

          <Button 
            label="Close"
            icon="pi pi-times"
            class="w-full p-button-secondary"
            @click="$emit('close')"
          />
        </div>
      </template>
    </Card>

    <!-- Already Verified -->
    <Card v-else :title="`Supplier ${supplier.status.toUpperCase()}`">
      <template #content>
        <div class="text-center py-6">
          <i :class="[
            'pi text-6xl mb-4',
            supplier.status === 'approved' ? 'pi-check-circle text-green-600' : 'pi-times-circle text-red-600'
          ]"></i>
          <p class="text-xl font-semibold capitalize mb-4">{{ supplier.status }} on {{ formatDate(supplier.verified_at) }}</p>
          <Button 
            label="Close"
            icon="pi pi-times"
            @click="$emit('close')"
            class="p-button-secondary"
          />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import RadioButton from 'primevue/radiobutton'
import Textarea from 'primevue/textarea'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Divider from 'primevue/divider'
import supplierService from '../../services/supplier.service'

const props = defineProps({
  supplier: {
    type: Object,
    required: true,
  },
})

defineEmits(['approve', 'reject', 'close'])

const toast = useToast()
const docReview = ref({})
const docRejectionReason = ref({})
const reviewingDoc = ref(null)

const allDocumentsApproved = computed(() => {
  if (!props.supplier.verification_documents) return false
  return props.supplier.verification_documents.every((doc: any) => doc.status === 'approved')
})

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
  }
  return map[status] || 'info'
}

const getDocStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    pending: 'info',
    approved: 'success',
    rejected: 'danger',
  }
  return map[status] || 'info'
}

const getDocumentTypeLabel = (type: string) => {
  const labels: { [key: string]: string } = {
    business_license: 'Business License',
    tax_id: 'Tax ID',
    company_registration: 'Company Registration',
    bank_details: 'Bank Details',
  }
  return labels[type] || type
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const downloadDocument = async (docId: number) => {
  try {
    await supplierService.downloadDocument(docId)
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to download document',
      life: 3000,
    })
  }
}

const reviewDocument = async (docId: number) => {
  if (!docReview.value[docId]) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Please select approval status',
      life: 3000,
    })
    return
  }

  if (docReview.value[docId] === 'rejected' && !docRejectionReason.value[docId]) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Please provide rejection reason',
      life: 3000,
    })
    return
  }

  try {
    reviewingDoc.value = docId
    await supplierService.reviewDocument(docId, {
      status: docReview.value[docId],
      rejection_reason: docRejectionReason.value[docId],
    })

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Document review submitted',
      life: 3000,
    })

    // Refresh – user will notice the document status changed
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to review document',
      life: 3000,
    })
  } finally {
    reviewingDoc.value = null
  }
}
</script>
