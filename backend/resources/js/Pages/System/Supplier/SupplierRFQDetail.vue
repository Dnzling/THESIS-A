<template>
  <div class="supplier-rfq-detail">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" class="p-button-rounded p-button-text" @click="$router.back()" />
        <div>
          <div class="text-xs text-gray-500 uppercase tracking-wider">Supplier Portal</div>
          <div class="text-xl font-semibold text-gray-900">RFQ Details</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Tag :value="rfq?.status || 'draft'" :severity="getStatusSeverity(rfq?.status)" />
      </div>
    </div>
  
    <div v-if="loading" class="grid grid-cols-1 gap-4">
      <Skeleton height="140px" class="rounded-2xl" />
      <Skeleton height="280px" class="rounded-2xl" />
      <Skeleton height="200px" class="rounded-2xl" />
    </div>
    <div v-else class="grid grid-cols-1 gap-4">
      <Card>
        <template #content>
          <div v-if="rfq" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-3">
              <div class="flex items-center gap-3">
                <div class="text-sm text-gray-500">RFQ</div>
                <div class="text-lg font-semibold text-gray-900">{{ rfq.rfq_number }}</div>
              </div>
              <div class="text-sm text-gray-700">{{ rfq.description || 'No description' }}</div>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <div class="text-gray-500">Created</div>
                  <div class="font-medium text-gray-900">{{ formatDate(rfq.created_at) }}</div>
                </div>
                <div>
                  <div class="text-gray-500">Deadline</div>
                  <div class="font-medium text-gray-900">{{ formatDate(rfq.deadline_date) }}</div>
                </div>
              </div>
            </div>
            <div class="space-y-3">
              <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs text-gray-500 uppercase">Items</div>
                <div class="text-2xl font-semibold text-gray-900">{{ rfq.items?.length || 0 }}</div>
              </div>
              <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs text-gray-500 uppercase">Your Quotes</div>
                <div class="text-2xl font-semibold text-gray-900">{{ Object.keys(feedbackByItemId).length }}</div>
              </div>
            </div>
          </div>
        </template>
      </Card>
  
      <!-- RFQ Items -->
      <Card title="Items">
        <template #content>
          <DataTable :value="rfq?.items || []" striped-rows class="w-full">
            <Column header="Product">
              <template #body="{ data }">
                <div>
                  <div class="font-semibold">{{ data.product?.product_name || 'N/A' }}</div>
                  <div class="text-xs text-gray-500">{{ data.product?.sku || '' }}</div>
                </div>
              </template>
            </Column>
            <Column field="quantity" header="Qty"></Column>
            <Column field="unit" header="Unit"></Column>
            <Column header="Target Price">
              <template #body="{ data }">
                {{ data.target_price ?? 'N/A' }}
              </template>
            </Column>
            <Column field="specifications" header="Specifications">
              <template #body="{ data }">
                <p class="truncate max-w-xs text-gray-600">{{ data.specifications || 'N/A' }}</p>
              </template>
            </Column>
            <Column header="Your Quote">
              <template #body="{ data }">
                <div v-if="feedbackByItemId[data.id]" class="space-y-1">
                  <div class="text-green-600 font-semibold">
                    ₱ {{ feedbackByItemId[data.id].quoted_price }}
                  </div>
                  <Tag :value="feedbackByItemId[data.id].status || 'pending'"
                    :severity="feedbackSeverity(feedbackByItemId[data.id].status)" class="text-xs" />
                  <p v-if="feedbackByItemId[data.id].rejection_reason" class="text-xs text-red-600">
                    {{ feedbackByItemId[data.id].rejection_reason }}
                  </p>
                  <div v-if="feedbackByItemId[data.id].negotiations && feedbackByItemId[data.id].negotiations.length > 0"
                    class="mt-2 p-2 rounded border border-blue-200 bg-blue-50 text-xs text-gray-700">
                    <p class="font-semibold text-blue-900 mb-1">Counter Offers</p>
                    <div v-for="nego in feedbackByItemId[data.id].negotiations" :key="nego.id"
                      class="flex items-center justify-between gap-2 border-t border-blue-100 pt-2 mt-2">
                      <div>
                        <div class="font-semibold text-blue-900">
                          {{ rfq?.currency || 'PHP' }} {{ parseFloat(nego.counter_price).toFixed(2) }}
                        </div>
                        <div class="text-[11px] text-blue-700">
                          {{ formatDate(nego.created_at) }} • {{ nego.status }}
                        </div>
                        <div v-if="nego.notes" class="text-[11px] text-gray-600 mt-1">{{ nego.notes }}</div>
                      </div>
                      <div class="flex gap-2">
                        <Button v-if="nego.status === 'pending'" size="small" label="Accept" severity="success"
                          @click="acceptNego(nego.id)" />
                        <Button v-if="nego.status === 'pending'" size="small" label="Reject" severity="danger"
                          @click="rejectNego(nego.id)" />
                      </div>
                    </div>
                  </div>
                </div>
                <p v-else class="text-gray-400">Not quoted</p>
              </template>
            </Column>
  
          </DataTable>
          <div class="mt-5 ml-auto">
            <Button v-if="rfq && !isClosedRFQ(rfq) && canEditQuotes" label="Submit Quote" icon="pi pi-send"
              class="p-button-primary" @click="openQuoteDialog" />
          </div>
        </template>
  
      </Card>
    </div>
  
    <Dialog v-model:visible="quoteDialogVisible" modal header="Submit Quote" :style="{ width: '52rem' }">
      <form @submit.prevent="submitQuote" class="space-y-4">
        <Message severity="info" text="Enter your quoted price and any additional notes for each item." class="w-full" />
  
        <div class="space-y-4">
          <div v-for="item in rfq?.items" :key="item.id" class="p-4 border rounded">
            <h4 class="font-semibold mb-3">Item: {{ item.product?.product_name || 'Item' }}</h4>
  
            <div class="grid grid-cols-2 gap-4 mb-3">
              <div>
                <p class="text-sm font-semibold">Quantity</p>
                <p>{{ item.quantity }} {{ item.unit }}</p>
              </div>
              <div>
                <p class="text-sm font-semibold">Specifications</p>
                <p class="text-sm">{{ item.specifications || 'N/A' }}</p>
              </div>
            </div>
  
          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">Quoted Price *</label>
              <InputNumber v-model="quoteData[item.id].quoted_price" mode="currency" :currency="rfq?.currency || 'PHP'"
                class="w-full" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Description</label>
              <InputText v-model="quoteData[item.id].description" placeholder="Additional notes" class="w-full" />
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Tax %</label>
              <InputNumber
                  v-model="quoteData[item.id].tax_rate"
                  mode="decimal"
                  min="0"
                  max="100"
                  suffix="%"
                  class="w-full"
                />
              </div>
            </div>
          </div>
        </div>
  
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="quoteDialogVisible = false" />
          <Button v-if="Object.keys(feedbackByItemId).length === 0" label="Submit All Quotes" type="submit"
            class="p-button-primary" :loading="submitting" />
        </div>
      </form>
    </Dialog>
  
    <!-- Loading -->
    <div v-if="loading" class="mt-6"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputNumber from 'primevue/inputnumber'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const loading = ref(false)
const submitting = ref(false)
const rfq = ref(null)
const feedbackByItemId = ref({})
const quoteData = ref({})
const canEditQuotes = ref(true)
const quoteDialogVisible = ref(false)

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    draft: 'secondary',
    sent: 'info',
    receiving: 'warning',
    awarded: 'success',
    completed: 'success',
    quotes_received: 'warning',
    cancelled: 'danger',
  }
  return map[status] || 'info'
}

const feedbackSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    pending: 'info',
    approved: 'success',
    rejected: 'danger',
  }
  return map[status] || 'info'
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString()
}

const loadRFQDetail = async () => {
  try {
    loading.value = true
    const rfqId = route.params.id as string
    const res = await supplierService.getSupplierRFQDetail(parseInt(rfqId))

    rfq.value = res.data.rfq
    const feedback = res.data.supplier_feedback || []

    // Initialize quote data
    rfq.value.items?.forEach((item: any) => {
      if (!quoteData.value[item.id]) {
        quoteData.value[item.id] = {
          quoted_price: null,
          description: '',
          tax_rate: Number(item.product?.tax_rate ?? 0),
        }
      }
    })

    // Load existing feedback
    feedback.forEach((f: any) => {
      feedbackByItemId.value[f.rfq_item_id] = f
      const item = rfq.value.items?.find((rfqItem: any) => rfqItem.id === f.rfq_item_id)
      quoteData.value[f.rfq_item_id] = {
        quoted_price: f.quoted_price,
        description: f.description || '',
        tax_rate: f.tax_rate ?? Number(item?.product?.tax_rate ?? 0),
      }
    })

    canEditQuotes.value = feedback.every((f: any) => (f.status || 'pending') === 'pending')
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load RFQ',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const submitQuote = async () => {
  if (!canEditQuotes.value) {
    toast.add({
      severity: 'warn',
      summary: 'Locked',
      detail: 'This RFQ is already under review. You cannot edit quotes.',
      life: 3000,
    })
    return
  }
  if (!rfq.value || isClosedRFQ(rfq.value)) {
    toast.add({
      severity: 'warn',
      summary: 'RFQ Closed',
      detail: 'This RFQ is already closed.',
      life: 3000,
    })
    return
  }

  for (const itemId in quoteData.value) {
    const item = rfq.value.items.find((i: any) => i.id == itemId)
    const quote = quoteData.value[itemId]

    if (!quote.quoted_price || quote.quoted_price <= 0) {
      toast.add({
        severity: 'error',
        summary: 'Invalid Quote',
        detail: `Please provide a valid price for item ${item?.product?.product_name || item?.id}`,
        life: 3000,
      })
      return
    }

    if (quote.tax_rate == null || quote.tax_rate < 0 || quote.tax_rate > 100) {
      toast.add({
        severity: 'error',
        summary: 'Invalid Tax',
        detail: `Please provide a valid tax percentage (0-100) for item ${item?.product?.product_name || item?.id}`,
        life: 3000,
      })
      return
    }

    try {
      submitting.value = true
      await supplierService.submitRFQFeedback({
        rfq_id: rfq.value.id,
        rfq_item_id: parseInt(itemId),
        quoted_price: quote.quoted_price,
        description: quote.description,
      tax_rate: quote.tax_rate,
      })
    } catch (error: any) {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to submit quote',
        life: 3000,
      })
      return
    }
  }

  toast.add({
    severity: 'success',
    summary: 'Success',
    detail: 'All quotes submitted successfully',
    life: 3000,
  })

  submitting.value = false
  loadRFQDetail()
}

const isClosedRFQ = (data: any) => {
  const closedStatuses = ['cancelled', 'awarded', 'completed', 'quotes_received', 'closed']
  if (closedStatuses.includes(data?.status)) {
    return true
  }
  if (data?.deadline_date) {
    const deadline = new Date(data.deadline_date)
    const today = new Date()
    deadline.setHours(0, 0, 0, 0)
    today.setHours(0, 0, 0, 0)
    return deadline < today
  }
  return false
}

const acceptNego = async (id: number) => {
  try {
    submitting.value = true
    await supplierService.acceptNegotiation(id)
    toast.add({
      severity: 'success',
      summary: 'Accepted',
      detail: 'Negotiation accepted.',
      life: 3000,
    })
    await loadRFQDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to accept negotiation',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const rejectNego = async (id: number) => {
  try {
    submitting.value = true
    await supplierService.rejectNegotiation(id)
    toast.add({
      severity: 'success',
      summary: 'Rejected',
      detail: 'Negotiation rejected.',
      life: 3000,
    })
    await loadRFQDetail()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to reject negotiation',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const openQuoteDialog = () => {
  quoteDialogVisible.value = true
}

onMounted(() => {
  loadRFQDetail()
})
</script>

<style scoped lang="scss">
.supplier-rfq-detail {
  padding: 16px;
}
</style>
