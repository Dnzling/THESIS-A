<template>
  <div class="supplier-po-detail">
    <div class="flex items-center mb-6">
      <Button 
        icon="pi pi-arrow-left"
        class="p-button-rounded p-button-text"
        @click="$router.back()"
      />
      <PageHeader title="Purchase Order Details" icon="pi pi-shopping-cart" />
    </div>

    <div v-if="!loading" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- PO Information -->
      <div class="lg:col-span-2">
        <Card title="Purchase Order" class="mb-6">
          <template #content>
            <div v-if="po" class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm font-semibold text-gray-600">PO Number</p>
                  <p class="text-lg">{{ po.po_number }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-600">Status</p>
                  <Tag :value="po.status" :severity="getStatusSeverity(po.status)" />
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-600">Created</p>
                  <p>{{ formatDate(po.created_at) }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-600">Expected Delivery</p>
                  <p>{{ formatDate(po.expected_delivery_date) }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-600">Total Amount</p>
                  <p class="text-green-600 font-bold">${{ parseFloat(po.total_amount || 0).toFixed(2) }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-600">Delivery Address</p>
                  <p class="text-sm">{{ getDeliveryAddress(po) }}</p>
                </div>
              </div>

              <Divider />

              <div>
                <h4 class="font-semibold mb-3">Notes</h4>
                <p class="text-gray-700">{{ po.notes || 'No notes' }}</p>
              </div>
            </div>
          </template>
        </Card>

        <!-- PO Items -->
        <Card title="Items" class="mb-6">
          <template #content>
            <DataTable :value="po?.items || []" striped-rows class="w-full">
              <Column header="Product">
                <template #body="{ data }">
                  <div>
                    <div class="font-semibold">{{ data.product?.product_name || 'N/A' }}</div>
                    <div class="text-xs text-gray-500">{{ data.product?.sku || '' }}</div>
                  </div>
                </template>
              </Column>
              <Column field="quantity_ordered" header="Qty"></Column>
              <Column field="unit_cost" header="Unit Cost"></Column>
              <Column field="line_total" header="Total">
                <template #body="{ data }">
                  ${{ parseFloat(data.line_total || 0).toFixed(2) }}
                </template>
              </Column>
            </DataTable>
          </template>
        </Card>

        <!-- Response Form -->
        <Card 
          v-if="!feedback && canRespondToPO(po)"
          title="Your Response" 
          class="mb-6"
        >
          <template #content>
            <form @submit.prevent="submitResponse" class="space-y-4">
              <div>
                <label class="block text-sm font-medium mb-2">Your Response *</label>
                <RadioButton 
                  v-model="responseData.response"
                  name="response"
                  value="accepted"
                  class="mr-2"
                />
                <label for="accepted" class="mr-6">Accept PO</label>
                
                <RadioButton 
                  v-model="responseData.response"
                  name="response"
                  value="rejected"
                  class="mr-2"
                />
                <label for="rejected">Reject PO</label>
              </div>

              <div v-if="responseData.response === 'rejected'">
                <label class="block text-sm font-medium mb-2">Rejection Reason *</label>
                <Textarea 
                  v-model="responseData.rejection_reason"
                  placeholder="Please explain why you're rejecting this PO"
                  rows="4"
                  class="w-full"
                />
              </div>

              <div v-if="responseData.response === 'accepted'" class="space-y-4">
                <Message 
                  severity="info"
                  text="Please confirm expected delivery date and quantity."
                  class="w-full"
                />

                <div>
                  <label class="block text-sm font-medium mb-2">Expected Delivery Date *</label>
                  <DatePicker 
                    v-model="responseData.expected_delivery_date"
                    date-format="yy-mm-dd"
                    :min-date="new Date()"
                    class="w-full"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium mb-2">Delivery Quantity *</label>
                  <InputNumber 
                    v-model="responseData.delivery_quantity"
                    :min="1"
                    class="w-full"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium mb-2">Delivery Notes</label>
                  <Textarea 
                    v-model="responseData.delivery_notes"
                    placeholder="Any special instructions or notes"
                    rows="3"
                    class="w-full"
                  />
                </div>
              </div>

              <div class="flex gap-3">
                <Button 
                  label="Submit Response"
                  type="submit"
                  class="p-button-primary"
                  :loading="submitting"
                />
                <Button 
                  label="Cancel"
                  type="button"
                  @click="$router.back()"
                  class="p-button-secondary"
                />
              </div>
            </form>
          </template>
        </Card>

        <!-- Existing Feedback -->
        <Card 
          v-if="feedback"
          :title="`Your Response - ${feedback.response.toUpperCase()}`"
          class="mb-6"
        >
          <template #content>
            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <p class="text-sm font-semibold text-gray-600">Response Status</p>
                  <Tag 
                    :value="feedback.response"
                    :severity="feedback.response === 'accepted' ? 'success' : 'danger'"
                  />
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-600">Submitted At</p>
                  <p>{{ formatDate(feedback.submitted_at) }}</p>
                </div>
              </div>

              <Divider />

              <div v-if="feedback.response === 'accepted'">
                <div class="grid grid-cols-2 gap-4 mb-4">
                  <div>
                    <p class="text-sm font-semibold text-gray-600">Expected Delivery</p>
                    <p>{{ formatDate(feedback.expected_delivery_date) }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-gray-600">Delivery Quantity</p>
                    <p>{{ feedback.delivery_quantity }}</p>
                  </div>
                </div>

                <div v-if="feedback.delivery_notes" class="mb-4">
                  <p class="text-sm font-semibold text-gray-600 mb-2">Delivery Notes</p>
                  <p>{{ feedback.delivery_notes }}</p>
                </div>

                <Divider v-if="feedback.receipt_status === 'pending'" />

                <div v-if="feedback.receipt_status === 'pending'">
                  <h4 class="font-semibold mb-3">Confirm Receipt</h4>
                  <form @submit.prevent="confirmReceipt" class="space-y-3">
                    <div>
                      <label class="block text-sm font-medium mb-2">Received Quantity *</label>
                      <InputNumber 
                        v-model="receiptData.delivery_quantity"
                        :min="1"
                        class="w-full"
                      />
                    </div>
                    <div>
                      <label class="block text-sm font-medium mb-2">Receipt Notes</label>
                      <Textarea 
                        v-model="receiptData.delivery_notes"
                        placeholder="Any notes about the delivery"
                        rows="2"
                        class="w-full"
                      />
                    </div>
                    <Button 
                      label="Confirm Receipt"
                      type="submit"
                      class="p-button-success"
                      :loading="submitting"
                    />
                  </form>
                </div>

                <Tag 
                  v-else
                  value="Receipt Confirmed"
                  severity="success"
                  class="mt-3"
                />
              </div>

              <div v-else-if="feedback.response === 'rejected'">
                <p class="text-sm font-semibold text-gray-600 mb-2">Rejection Reason</p>
                <p>{{ feedback.rejection_reason }}</p>
              </div>
            </div>
          </template>
        </Card>
      </div>

      <!-- Summary Panel -->
      <div>
        <Card title="PO Summary" class="sticky top-4">
          <template #content>
            <div v-if="po" class="space-y-4">
              <div>
                <p class="text-sm font-semibold text-gray-600">Supplier</p>
                <p>{{ po.supplier?.company_name }}</p>
              </div>

              <Divider />

              <div>
                <p class="text-sm font-semibold text-gray-600">Total Items</p>
                <p class="text-2xl">{{ po.items?.length || 0 }}</p>
              </div>

              <div>
                <p class="text-sm font-semibold text-gray-600">Total Amount</p>
                <p class="text-2xl text-green-600 font-bold">
                  ${{ parseFloat(po.total_amount || 0).toFixed(2) }}
                </p>
              </div>

              <Divider />

              <div v-if="feedback">
                <p class="text-sm font-semibold text-gray-600 mb-2">Your Response</p>
                <Tag 
                  :value="feedback.response"
                  :severity="feedback.response === 'accepted' ? 'success' : 'danger'"
                  class="w-full justify-center"
                />
              </div>

              <Button 
                label="Back to POs"
                icon="pi pi-arrow-left"
                @click="$router.push('/supplier-portal/pos')"
                class="w-full p-button-secondary"
              />
            </div>
          </template>
        </Card>
      </div>
    </div>

    <!-- Loading -->
    <ProgressSpinner v-if="loading" class="mt-6" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import RadioButton from 'primevue/radiobutton'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Divider from 'primevue/divider'
import ProgressSpinner from 'primevue/progressspinner'
import PageHeader from '../../../components/PageHeader.vue'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const loading = ref(false)
const submitting = ref(false)
const po = ref(null)
const feedback = ref(null)

const responseData = ref({
  response: 'accepted',
  rejection_reason: '',
  expected_delivery_date: null,
  delivery_quantity: null,
  delivery_notes: '',
})

const receiptData = ref({
  delivery_quantity: null,
  delivery_notes: '',
})

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    draft: 'secondary',
    pending_approval: 'warning',
    partially_approved: 'warning',
    fully_approved: 'success',
    finance_approved: 'success',
    ordered: 'info',
    received: 'success',
    partially_received: 'warning',
    rejected: 'danger',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const loadPODetail = async () => {
  try {
    loading.value = true
    const poId = route.params.id as string
    const res = await supplierService.getSupplierPODetail(parseInt(poId))
    
    po.value = res.data.po
    feedback.value = res.data.supplier_feedback

    if (feedback.value) {
      receiptData.value.delivery_quantity = feedback.value.delivery_quantity
      receiptData.value.delivery_notes = feedback.value.delivery_notes || ''
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load PO',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const canRespondToPO = (data: any) => {
  if (!data) return false
  const blockedStatuses = ['cancelled', 'rejected', 'received', 'partially_received']
  return !blockedStatuses.includes(data.status)
}

const getDeliveryAddress = (data: any) => {
  if (!data) return 'N/A'
  const branch = data.branch
  if (!branch) {
    return data.delivery_address || 'N/A'
  }
  const parts = [
    branch.name,
    branch.address,
    branch.city,
    branch.province,
  ].filter(Boolean)
  return parts.join(', ') || 'N/A'
}

const submitResponse = async () => {
  if (responseData.value.response === 'rejected' && !responseData.value.rejection_reason) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please provide a rejection reason',
      life: 3000,
    })
    return
  }

  if (responseData.value.response === 'accepted') {
    if (!responseData.value.expected_delivery_date || !responseData.value.delivery_quantity) {
      toast.add({
        severity: 'error',
        summary: 'Validation Error',
        detail: 'Please fill in all required fields',
        life: 3000,
      })
      return
    }
  }

  try {
    submitting.value = true
    await supplierService.submitPOFeedback({
      purchase_order_id: po.value.id,
      response: responseData.value.response,
      rejection_reason: responseData.value.rejection_reason,
      expected_delivery_date: responseData.value.expected_delivery_date?.toISOString().split('T')[0],
      delivery_quantity: responseData.value.delivery_quantity,
      delivery_notes: responseData.value.delivery_notes,
    })

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Your response has been submitted',
      life: 3000,
    })

    loadPODetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to submit response',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const confirmReceipt = async () => {
  if (!receiptData.value.delivery_quantity) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please enter received quantity',
      life: 3000,
    })
    return
  }

  try {
    submitting.value = true
    await supplierService.confirmPOReceipt(feedback.value.id, receiptData.value)

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Receipt confirmed successfully',
      life: 3000,
    })

    loadPODetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to confirm receipt',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

onMounted(() => {
  loadPODetail()
})
</script>

<style scoped lang="scss">
.supplier-po-detail {
  padding: 20px;
}
</style>
