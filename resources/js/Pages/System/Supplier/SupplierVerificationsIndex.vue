<template>
  <div class="supplier-verifications-index">
    <PageHeader title="Supplier Verification Management" icon="pi pi-check-circle" />

    <!-- Tabs -->
    <TabView class="mb-6">
      <TabPanel header="Pending" leftIcon="pi pi-clock">
        <div class="pt-4">
          <Skeleton v-if="loadingPending" height="120px" class="rounded-lg" />
          <Message
            v-else-if="pendingVerifications.length === 0"
            severity="info"
            text="No pending verification requests."
            class="w-full"
          />
          <DataTable
            v-else
            :value="pendingVerifications"
            striped-rows
            responsive-layout="scroll"
          >
            <Column header="Supplier Name">
              <template #body="{ data }">
                <div>
                  <p class="font-semibold">{{ data.user?.name }}</p>
                  <p class="text-sm text-gray-500">{{ data.user?.email }}</p>
                </div>
              </template>
            </Column>
            <Column header="Company">
              <template #body="{ data }">
                {{ data.company_name || '-' }}
              </template>
            </Column>
            <Column header="Status">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Submitted">
              <template #body="{ data }">
                {{ formatDate(data.last_submission_at) }}
              </template>
            </Column>
            <Column header="Documents">
              <template #body="{ data }">
                <Tag
                  v-if="data.verification_documents"
                  :value="`${data.verification_documents.length}/4`"
                  severity="info"
                />
              </template>
            </Column>
            <Column header="Actions" :exportable="false">
              <template #body="{ data }">
                <div class="flex gap-2">
                  <Button
                    icon="pi pi-eye"
                    class="p-button-rounded p-button-info p-button-sm"
                    @click="viewSupplierDetail(data.id)"
                    v-tooltip.top="'View Details'"
                  />
                  <Button
                    icon="pi pi-check"
                    class="p-button-rounded p-button-success p-button-sm"
                    @click="approveSupplier(data.id)"
                    v-tooltip.top="'Approve'"
                  />
                  <Button
                    icon="pi pi-times"
                    class="p-button-rounded p-button-danger p-button-sm"
                    @click="showRejectDialog(data)"
                    v-tooltip.top="'Reject'"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </TabPanel>

      <TabPanel header="Approved" leftIcon="pi pi-check">
        <div class="pt-4">
          <Skeleton v-if="loadingApproved" height="120px" class="rounded-lg" />
          <Message
            v-else-if="approvedVerifications.length === 0"
            severity="info"
            text="No approved suppliers yet."
            class="w-full"
          />
          <DataTable
            v-else
            :value="approvedVerifications"
            striped-rows
            responsive-layout="scroll"
          >
            <Column header="Supplier Name">
              <template #body="{ data }">
                <div>
                  <p class="font-semibold">{{ data.user?.name }}</p>
                  <p class="text-sm text-gray-500">{{ data.user?.email }}</p>
                </div>
              </template>
            </Column>
            <Column header="Company">
              <template #body="{ data }">
                {{ data.company_name || '-' }}
              </template>
            </Column>
            <Column header="Status">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Submitted">
              <template #body="{ data }">
                {{ formatDate(data.last_submission_at) }}
              </template>
            </Column>
            <Column header="Documents">
              <template #body="{ data }">
                <Tag
                  v-if="data.verification_documents"
                  :value="`${data.verification_documents.length}/4`"
                  severity="info"
                />
              </template>
            </Column>
            <Column header="Actions" :exportable="false">
              <template #body="{ data }">
                <div class="flex gap-2">
                  <Button
                    icon="pi pi-eye"
                    class="p-button-rounded p-button-info p-button-sm"
                    @click="viewSupplierDetail(data.id)"
                    v-tooltip.top="'View Details'"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </TabPanel>

      <TabPanel header="Rejected" leftIcon="pi pi-times">
        <div class="pt-4">
          <Skeleton v-if="loadingRejected" height="120px" class="rounded-lg" />
          <Message
            v-else-if="rejectedVerifications.length === 0"
            severity="info"
            text="No rejected suppliers."
            class="w-full"
          />
          <DataTable
            v-else
            :value="rejectedVerifications"
            striped-rows
            responsive-layout="scroll"
          >
            <Column header="Supplier Name">
              <template #body="{ data }">
                <div>
                  <p class="font-semibold">{{ data.user?.name }}</p>
                  <p class="text-sm text-gray-500">{{ data.user?.email }}</p>
                </div>
              </template>
            </Column>
            <Column header="Company">
              <template #body="{ data }">
                {{ data.company_name || '-' }}
              </template>
            </Column>
            <Column header="Status">
              <template #body="{ data }">
                <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
              </template>
            </Column>
            <Column header="Submitted">
              <template #body="{ data }">
                {{ formatDate(data.last_submission_at) }}
              </template>
            </Column>
            <Column header="Documents">
              <template #body="{ data }">
                <Tag
                  v-if="data.verification_documents"
                  :value="`${data.verification_documents.length}/4`"
                  severity="info"
                />
              </template>
            </Column>
            <Column header="Actions" :exportable="false">
              <template #body="{ data }">
                <div class="flex gap-2">
                  <Button
                    icon="pi pi-eye"
                    class="p-button-rounded p-button-info p-button-sm"
                    @click="viewSupplierDetail(data.id)"
                    v-tooltip.top="'View Details'"
                  />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </TabPanel>
    </TabView>

    <!-- Reject Dialog -->
    <Dialog 
      v-model:visible="showRejectDialogFlag"
      header="Reject Supplier"
      modal
      @hide="selectedSupplier = null"
    >
      <form @submit.prevent="rejectSupplier" class="space-y-4">
        <div>
          <p class="font-semibold mb-3">Why are you rejecting this supplier?</p>
          <Textarea 
            v-model="rejectReason"
            placeholder="Enter rejection reason"
            rows="5"
            class="w-full"
          />
        </div>

        <div class="flex gap-3 justify-end">
          <Button 
            label="Cancel"
            @click="showRejectDialogFlag = false"
            class="p-button-secondary"
          />
          <Button 
            label="Reject"
            type="submit"
            class="p-button-danger"
            :loading="submitting"
          />
        </div>
      </form>
    </Dialog>

    <!-- Detail Dialog -->
    <Dialog 
      v-model:visible="showDetailDialog"
      header="Supplier Verification Details"
      modal
      :maximizable="true"
      style="width: 90vw; max-width: 1000px"
    >
      <SupplierVerificationDetail 
        v-if="selectedSupplierDetail"
        :supplier="selectedSupplierDetail"
        @approve="approveSupplier"
        @reject="showRejectDialog"
        @close="showDetailDialog = false"
      />
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Button from 'primevue/button'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import PageHeader from '@/Components/PageHeader.vue'
import SupplierVerificationDetail from '@/Components/supplier-portal/SupplierVerificationDetail.vue'
import supplierService from '../../../services/supplier.service'

const toast = useToast()
const loadingPending = ref(false)
const loadingApproved = ref(false)
const loadingRejected = ref(false)
const submitting = ref(false)
const pendingVerifications = ref([])
const approvedVerifications = ref([])
const rejectedVerifications = ref([])
const selectedSupplier = ref(null)
const selectedSupplierDetail = ref(null)
const showRejectDialogFlag = ref(false)
const showDetailDialog = ref(false)
const rejectReason = ref('')

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
  }
  return map[status] || 'info'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const loadPendingVerifications = async () => {
  try {
    loadingPending.value = true
    const res = await supplierService.getPendingVerifications({ per_page: 100 })
    pendingVerifications.value = res.data.data || []
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load pending verifications',
      life: 3000,
    })
  } finally {
    loadingPending.value = false
  }
}

const loadApprovedVerifications = async () => {
  try {
    loadingApproved.value = true
    const res = await supplierService.getAllVerifications({ 
      status: 'approved',
      per_page: 100 
    })
    approvedVerifications.value = res.data.data || []
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load approved verifications',
      life: 3000,
    })
  } finally {
    loadingApproved.value = false
  }
}

const loadRejectedVerifications = async () => {
  try {
    loadingRejected.value = true
    const res = await supplierService.getAllVerifications({ 
      status: 'rejected',
      per_page: 100 
    })
    rejectedVerifications.value = res.data.data || []
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load rejected verifications',
      life: 3000,
    })
  } finally {
    loadingRejected.value = false
  }
}

const approveSupplier = async (id: number) => {
  if (!confirm('Are you sure you want to approve this supplier?')) return

  try {
    submitting.value = true
    await supplierService.approveSupplierVerification(id)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Supplier approved successfully',
      life: 3000,
    })

    // Reload data
    loadPendingVerifications()
    loadApprovedVerifications()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to approve supplier',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const showRejectDialog = (supplier: any) => {
  selectedSupplier.value = supplier
  rejectReason.value = ''
  showRejectDialogFlag.value = true
}

const rejectSupplier = async () => {
  if (!rejectReason.value.trim()) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please provide a rejection reason',
      life: 3000,
    })
    return
  }

  try {
    submitting.value = true
    await supplierService.rejectSupplierVerification(selectedSupplier.value.id, {
      rejection_reason: rejectReason.value,
    })

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Supplier rejected successfully',
      life: 3000,
    })

    showRejectDialogFlag.value = false
    loadPendingVerifications()
    loadRejectedVerifications()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to reject supplier',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const viewSupplierDetail = async (id: number) => {
  try {
    const res = await supplierService.getVerificationDetail(id)
    selectedSupplierDetail.value = res.data
    showDetailDialog.value = true
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load supplier details',
      life: 3000,
    })
  }
}

onMounted(() => {
  loadPendingVerifications()
  loadApprovedVerifications()
  loadRejectedVerifications()
})
</script>

<style scoped lang="scss">
.supplier-verifications-index {
  padding: 20px;
}
</style>
