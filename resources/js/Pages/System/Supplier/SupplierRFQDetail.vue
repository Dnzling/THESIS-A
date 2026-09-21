<template>
  <div class="supplier-rfq-detail space-y-5">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded severity="secondary" class="!h-9 !w-9" @click="$router.back()" />
        <div>
          <div class="text-xl font-semibold text-slate-900">RFQ Details</div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <Tag :value="formatQuoteStatus(rfq?.status)" :severity="getStatusSeverity(rfq?.status)" class="rounded-full px-3 py-1 text-xs font-semibold" />
      </div>
    </div>
  
    <div v-if="loading" class="grid grid-cols-1 gap-4">
      <Skeleton height="140px" class="rounded-2xl" />
      <Skeleton height="280px" class="rounded-2xl" />
      <Skeleton height="200px" class="rounded-2xl" />
    </div>
    <div v-else class="grid grid-cols-1 gap-4">
      <Card class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
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
                  <div class="font-medium text-gray-900">{{ formatDateWithTime(rfq.created_at) }}</div>
                </div>
                <div>
                  <div class="text-gray-500">Store</div>
                  <div class="font-medium text-gray-900">{{ rfq.store?.store_name || rfq.store?.name || rfq.store_name || rfq.store?.store_code || "—" }}</div>
                </div>
              </div>
            </div>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-1">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <div class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Items</div>
                <div class="mt-1 text-2xl font-semibold text-slate-900">{{ rfq.items?.length || 0 }}</div>
              </div>
              <div class="rounded-xl border border-orange-200 bg-orange-50 p-3">
                <div class="text-[11px] font-medium uppercase tracking-wide text-orange-700">Your quotes</div>
                <div class="mt-1 text-2xl font-semibold text-orange-900">{{ Object.keys(feedbackByItemId).length }}</div>
              </div>
            </div>
          </div>
        </template>
      </Card>

      <Card
        v-if="rfq?.instructions || rfq?.qualification_requirements"
        class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm"
      >
        <template #title>
          <div class="flex items-center gap-2 text-base font-semibold text-slate-900">
            <i class="pi pi-file-edit text-orange-600"></i>
            Supplier Requirements
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div v-if="rfq?.instructions" class="rounded-xl border border-blue-100 bg-blue-50 p-4">
              <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-blue-900">
                <i class="pi pi-info-circle"></i>
                Special Instructions
              </div>
              <p class="whitespace-pre-line text-sm leading-6 text-blue-800">{{ rfq.instructions }}</p>
            </div>
            <div v-if="rfq?.qualification_requirements" class="rounded-xl border border-amber-100 bg-amber-50 p-4">
              <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-amber-900">
                <i class="pi pi-check-circle"></i>
                Qualification Requirements
              </div>
              <p class="whitespace-pre-line text-sm leading-6 text-amber-800">{{ rfq.qualification_requirements }}</p>
            </div>
          </div>
        </template>
      </Card>
  
      <!-- RFQ Items -->
      <Card title="Requested Products" class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
        <template #content>
          <DataTable :value="rfq?.items || []" stripedRows rowHover size="small" class="w-full">
            <Column header="Product">
              <template #body="{ data }">
                <div>
                  <div class="font-semibold">{{ data.product?.product_name || 'N/A' }}</div>
                  <div class="text-xs text-gray-500">{{ data.product?.sku || '' }}</div>
                  <div v-if="data.variation" class="mt-1 text-xs text-orange-700">
                    Requested variation: {{ data.variation.variation_name || [data.variation.size, data.variation.color, data.variation.material].filter(Boolean).join(' / ') || data.variation.variation_sku }}
                  </div>
                </div>
              </template>
            </Column>
            <Column header="Requested Quantity">
              <template #body="{ data }">
                <span class="font-medium text-slate-900">
                  {{ data.quantity }} {{ data.product?.unit_of_measurement || data.unit_of_measurement || '-' }}
                </span>
              </template>
            </Column>
            <Column field="specifications" header="Specifications">
              <template #body="{ data }">
                <p class="truncate max-w-xs text-gray-600">{{ data.specifications || 'N/A' }}</p>
              </template>
            </Column>
            <Column header="Your Quote">
              <template #body="{ data }">
                <div v-if="feedbackByItemId[data.id]" class="space-y-2 rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                  <div class="flex items-center justify-between gap-3 text-green-600 font-semibold">
                    <span>₱ {{ feedbackByItemId[data.id].quoted_price }}</span>
                    <a v-if="feedbackByItemId[data.id].attachment_path" :href="getAttachmentUrl(feedbackByItemId[data.id].attachment_path)" target="_blank" rel="noopener" class="shrink-0 text-xs text-orange-600 hover:underline">
                    <i class="pi pi-eye mr-1"></i>View attachment
                  </a>
                  </div>
                
                  <Tag :value="formatQuoteStatus(feedbackByItemId[data.id].status)"
                    :severity="feedbackSeverity(feedbackByItemId[data.id].status)" class="text-xs" />
                  <div class="grid grid-cols-2 gap-x-4 gap-y-1 border-t border-slate-200 pt-2">
                    <span>Available: <strong class="text-slate-800">{{ feedbackByItemId[data.id].available_quantity ?? '—' }}</strong></span>
                    <span>Unit weight: <strong class="text-slate-800">{{ feedbackByItemId[data.id].weight_kg ?? '—' }} kg</strong></span>
                    <span class="col-span-2">Dimensions: <strong class="text-slate-800">{{ quoteDimensions(feedbackByItemId[data.id]) }}</strong></span>
                    <p class="col-span-2 font-medium text-slate-700">Estimated total: ₱{{ calculateQuoteTotal(data, feedbackByItemId[data.id]) }}</p>
                    <span>Delivery: <strong class="text-slate-800">{{ formatDate(feedbackByItemId[data.id].estimated_delivery_date) }}</strong></span>
                    <span>Valid until: <strong class="text-slate-800">{{ formatDate(feedbackByItemId[data.id].quotation_valid_until) }}</strong></span>
                  </div>
                  <p v-if="feedbackByItemId[data.id].product_specifications"><strong class="text-slate-800">Specifications:</strong> {{ feedbackByItemId[data.id].product_specifications }}</p>
                  <p v-if="feedbackByItemId[data.id].additional_notes"><strong class="text-slate-800">Notes:</strong> {{ feedbackByItemId[data.id].additional_notes }}</p>
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
                          {{ formatDate(nego.created_at) }} â€¢ {{ nego.status }}
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
            <Button v-if="rfq && !isClosedRFQ(rfq) && canEditQuotes && Object.keys(feedbackByItemId).length === 0" label="Submit Quote" icon="pi pi-send"
              class="p-button-primary" @click="openQuoteDialog" />
          </div>
        </template>
  
      </Card>
    </div>
  
    <Dialog v-model:visible="quoteDialogVisible" modal header="Submit Quote" :style="{ width: 'min(70rem, 96vw)' }" :contentStyle="{ maxHeight: '78vh', overflowY: 'auto' }">
      <form @submit.prevent="submitQuote" class="space-y-4">
        <Message severity="info" text="Enter your quoted price and any additional notes for each item." class="w-full" />
        <Message v-if="quoteValidationMessage" severity="error" :closable="false" class="w-full">
          {{ quoteValidationMessage }}
        </Message>
  
        <div class="space-y-4">
          <div v-for="item in rfq?.items" :key="item.id" class="overflow-hidden rounded-lg border border-orange-200 bg-white shadow-sm">
            <div class="border-b border-orange-200 bg-orange-50 px-4 py-3">
              <h4 class="font-semibold text-orange-900">{{ item.product?.product_name || 'Item' }}</h4>
              <p class="mt-0.5 text-xs text-orange-700">
                {{ item.quantity }} {{ item.product?.unit_of_measurement || item.unit_of_measurement || '-' }} requested
              </p>
              <p v-if="item.variation" class="mt-1 text-xs text-orange-700">
                Variation: {{ item.variation.variation_name || [item.variation.size, item.variation.color, item.variation.material].filter(Boolean).join(' / ') || item.variation.variation_sku }}
              </p>
            </div>
            <div class="p-4">
  
            <div class="grid grid-cols-2 gap-4 mb-3">
              <div>
                <p class="text-sm font-semibold">Quantity</p>
                <p>{{ item.quantity }} {{ item.product?.unit_of_measurement || '-' }}</p>
              </div>
              <div>
                <p class="text-sm font-semibold">Specifications</p>
                <p class="text-sm">{{ item.specifications || 'N/A' }}</p>
              </div>
            </div>
  
            <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">Unit Price *</label>
              <InputNumber v-model="quoteData[item.id].quoted_price" mode="currency" :min="0" :currency="rfq?.currency || 'PHP'"
                class="w-full" :invalid="Boolean(quoteErrors[item.id]?.quoted_price)"
                @update:modelValue="clearQuoteError(item.id, 'quoted_price')" />
              <small v-if="quoteErrors[item.id]?.quoted_price" class="mt-1 block text-red-500">{{ quoteErrors[item.id].quoted_price }}</small>
            </div>
            <div>
              <label class="block text-sm font-medium mb-2">Available Quantity *</label>
              <InputNumber v-model="quoteData[item.id].available_quantity" :min="1" :maxFractionDigits="0" class="w-full"
                :invalid="Boolean(quoteErrors[item.id]?.available_quantity)"
                @update:modelValue="clearQuoteError(item.id, 'available_quantity')" />
              <small v-if="quoteErrors[item.id]?.available_quantity" class="mt-1 block text-red-500">{{ quoteErrors[item.id].available_quantity }}</small>
            </div>
            </div>

            <div class="mt-4 rounded-2xl border border-orange-100 bg-orange-50/50 p-4">
              <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-800">
                <input v-model="quoteData[item.id].has_variant" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-orange-600" />
                This quote includes a product variant
              </label>
              <div v-if="quoteData[item.id].has_variant" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                <div><label class="mb-1 block text-xs font-medium">Variant Name *</label><InputText v-model="quoteData[item.id].variant_name" fluid placeholder="e.g. Oak Finish - Large" /></div>
                <div><label class="mb-1 block text-xs font-medium">Supplier SKU / Model</label><InputText v-model="quoteData[item.id].supplier_sku" fluid /></div>
                <div><label class="mb-1 block text-xs font-medium">Unit of Measurement</label><InputText v-model="quoteData[item.id].unit_of_measurement" fluid placeholder="piece, set, box" /></div>
                <div><label class="mb-1 block text-xs font-medium">Size</label><InputText v-model="quoteData[item.id].variant_size" fluid /></div>
                <div><label class="mb-1 block text-xs font-medium">Color</label><InputText v-model="quoteData[item.id].variant_color" fluid /></div>
                <div><label class="mb-1 block text-xs font-medium">Material</label><InputText v-model="quoteData[item.id].variant_material" fluid /></div>
                <div><label class="mb-1 block text-xs font-medium">Texture</label><InputText v-model="quoteData[item.id].variant_texture" fluid /></div>
                <div><label class="mb-1 block text-xs font-medium">Finish</label><InputText v-model="quoteData[item.id].variant_finish" fluid /></div>
                <div class="md:col-span-2 lg:col-span-3"><label class="mb-1 block text-xs font-medium">Variant Images (up to 5)</label><input type="file" accept="image/png,image/jpeg,image/webp" multiple class="w-full text-sm" @change="setVariantImages(item.id, $event)" /></div>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mt-3">
              <div class="md:col-span-2">
                <p class="mb-2 text-sm font-semibold text-slate-700">Per-unit shipping specifications</p>
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                  <div>
                    <label class="block text-xs font-medium mb-1">Length (cm) *</label>
                    <InputNumber v-model="quoteData[item.id].length_cm" :min="0.01" :maxFractionDigits="2" fluid
                      :invalid="Boolean(quoteErrors[item.id]?.length_cm)" @update:modelValue="clearQuoteError(item.id, 'length_cm')" />
                    <small v-if="quoteErrors[item.id]?.length_cm" class="mt-1 block text-red-500">{{ quoteErrors[item.id].length_cm }}</small>
                  </div>
                  <div>
                    <label class="block text-xs font-medium mb-1">Width (cm) *</label>
                    <InputNumber v-model="quoteData[item.id].width_cm" :min="0.01" :maxFractionDigits="2" fluid
                      :invalid="Boolean(quoteErrors[item.id]?.width_cm)" @update:modelValue="clearQuoteError(item.id, 'width_cm')" />
                    <small v-if="quoteErrors[item.id]?.width_cm" class="mt-1 block text-red-500">{{ quoteErrors[item.id].width_cm }}</small>
                  </div>
                  <div>
                    <label class="block text-xs font-medium mb-1">Height (cm) *</label>
                    <InputNumber v-model="quoteData[item.id].height_cm" :min="0.01" :maxFractionDigits="2" fluid
                      :invalid="Boolean(quoteErrors[item.id]?.height_cm)" @update:modelValue="clearQuoteError(item.id, 'height_cm')" />
                    <small v-if="quoteErrors[item.id]?.height_cm" class="mt-1 block text-red-500">{{ quoteErrors[item.id].height_cm }}</small>
                  </div>
                  <div>
                    <label class="block text-xs font-medium mb-1">Weight (kg) *</label>
                    <InputNumber v-model="quoteData[item.id].weight_kg" :min="0.001" :maxFractionDigits="3" fluid
                      :invalid="Boolean(quoteErrors[item.id]?.weight_kg)" @update:modelValue="clearQuoteError(item.id, 'weight_kg')" />
                    <small v-if="quoteErrors[item.id]?.weight_kg" class="mt-1 block text-red-500">{{ quoteErrors[item.id].weight_kg }}</small>
                  </div>
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">Estimated Delivery *</label>
                <DatePicker v-model="quoteData[item.id].estimated_delivery_date" dateFormat="M d, yy" showIcon :minDate="today" class="w-full"
                  :invalid="Boolean(quoteErrors[item.id]?.estimated_delivery_date)"
                  @update:modelValue="clearQuoteError(item.id, 'estimated_delivery_date')" />
                <small v-if="quoteErrors[item.id]?.estimated_delivery_date" class="mt-1 block text-red-500">{{ quoteErrors[item.id].estimated_delivery_date }}</small>
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">Quotation Valid Until *</label>
                <DatePicker v-model="quoteData[item.id].quotation_valid_until" dateFormat="M d, yy" showIcon :minDate="today" class="w-full"
                  :invalid="Boolean(quoteErrors[item.id]?.quotation_valid_until)"
                  @update:modelValue="clearQuoteError(item.id, 'quotation_valid_until')" />
                <small v-if="quoteErrors[item.id]?.quotation_valid_until" class="mt-1 block text-red-500">{{ quoteErrors[item.id].quotation_valid_until }}</small>
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">Quotation Attachment</label>
                <input type="file" class="w-full text-sm" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" @change="setAttachment(item.id, $event)" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2">Product Specifications</label>
                <Textarea v-model="quoteData[item.id].product_specifications" rows="2" placeholder="Optional specifications" class="w-full" />
              </div>
              <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-2">Additional Notes</label>
                <Textarea v-model="quoteData[item.id].additional_notes" rows="2" placeholder="Optional notes" class="w-full" />
              </div>
            </div>
          </div>
        </div>
        </div>
  
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" @click="quoteDialogVisible = false" />
          <Button v-if="Object.keys(feedbackByItemId).length === 0" label="Submit All Quotes" type="submit"
            class="p-button-primary" :loading="submitting"  />
        </div>
      </form>
    </Dialog>

    <ConfirmDialog />
  
    <!-- Loading -->
    <div v-if="loading" class="mt-6"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import ConfirmDialog from 'primevue/confirmdialog'
import supplierService from '../../../services/supplier.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()
const loading = ref(false)
const submitting = ref(false)
const rfq = ref(null)
const feedbackByItemId = ref({})
const quoteData = ref({})
const quoteErrors = ref<Record<number, Record<string, string>>>({})
const quoteValidationMessage = ref('')
const canEditQuotes = ref(true)
const quoteDialogVisible = ref(false)
const today = new Date()

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

const formatQuoteStatus = (status: string) => {
  return String(status || 'pending').replace(/[_-]/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

const formatDate = (date: string) => {
  if (!date) return 'â€”'
  return new Date(date).toLocaleDateString()
}

const formatDateWithTime = (date: string) => {
  if (!date) return 'â€”'
  return new Date(date).toLocaleString('en-US', {
    weekday: 'short',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: 'numeric'
  })
}

const loadRFQDetail = async () => {
  try {
    loading.value = true
    const rfqId = route.params.id as string
    const res = await supplierService.getSupplierRFQDetail(parseInt(rfqId))

    rfq.value = res.data.rfq
    const feedback = res.data.supplier_feedback || []

    // Initialize quote data (exclude tax_rate; server contract tax_rate is authoritative)
    rfq.value.items?.forEach((item: any) => {
      if (!quoteData.value[item.id]) {
        quoteData.value[item.id] = {
          quoted_price: null,
          available_quantity: item.quantity || null,
          length_cm: null,
          width_cm: null,
          height_cm: null,
          weight_kg: null,
          estimated_delivery_date: null,
          quotation_valid_until: null,
          attachment: null,
          product_specifications: '',
          additional_notes: '',
          description: '',
          has_variant: false, variant_name: '', supplier_sku: '', variant_size: '', variant_color: '', variant_texture: '', variant_finish: '', variant_material: '', unit_of_measurement: item.product?.unit_of_measurement || '', variant_images: [],
        }
      }
    })

    // Load existing feedback
    feedback.forEach((f: any) => {
      feedbackByItemId.value[f.rfq_item_id] = f
      const item = rfq.value.items?.find((rfqItem: any) => rfqItem.id === f.rfq_item_id)
      quoteData.value[f.rfq_item_id] = {
        quoted_price: f.quoted_price,
        available_quantity: f.available_quantity,
        length_cm: Number(f.length_cm || 0) || null,
        width_cm: Number(f.width_cm || 0) || null,
        height_cm: Number(f.height_cm || 0) || null,
        weight_kg: Number(f.weight_kg || 0) || null,
        estimated_delivery_date: f.estimated_delivery_date ? new Date(f.estimated_delivery_date) : null,
        quotation_valid_until: f.quotation_valid_until ? new Date(f.quotation_valid_until) : null,
        attachment: null,
        product_specifications: f.product_specifications || '',
        additional_notes: f.additional_notes || '',
        description: f.description || '',
        has_variant: Boolean(f.has_variant), variant_name: f.variant_name || '', supplier_sku: f.supplier_sku || '', variant_size: f.variant_size || '', variant_color: f.variant_color || '', variant_texture: f.variant_texture || '', variant_finish: f.variant_finish || '', variant_material: f.variant_material || '', unit_of_measurement: f.unit_of_measurement || item?.product?.unit_of_measurement || '', variant_images: [],
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

const clearQuoteError = (itemId: number, field: string) => {
  if (!quoteErrors.value[itemId]?.[field]) return
  delete quoteErrors.value[itemId][field]
  if (Object.keys(quoteErrors.value[itemId]).length === 0) {
    delete quoteErrors.value[itemId]
  }
  if (Object.keys(quoteErrors.value).length === 0) {
    quoteValidationMessage.value = ''
  }
}

const validateAllQuotes = () => {
  const errors: Record<number, Record<string, string>> = {}

  for (const item of rfq.value?.items || []) {
    const quote = quoteData.value[item.id] || {}
    const itemErrors: Record<string, string> = {}

    if (!quote.quoted_price || Number(quote.quoted_price) <= 0) {
      itemErrors.quoted_price = 'Enter a unit price greater than zero.'
    }
    if (!quote.available_quantity || Number(quote.available_quantity) <= 0) {
      itemErrors.available_quantity = 'Enter the available quantity.'
    }
    for (const field of ['length_cm', 'width_cm', 'height_cm', 'weight_kg']) {
      if (!quote[field] || Number(quote[field]) <= 0) {
        itemErrors[field] = `Enter a valid ${field === 'weight_kg' ? 'weight' : field.replace('_cm', '')}.`
      }
    }
    if (!quote.estimated_delivery_date) {
      itemErrors.estimated_delivery_date = 'Select an estimated delivery date.'
    }
    if (!quote.quotation_valid_until) {
      itemErrors.quotation_valid_until = 'Select the quotation validity date.'
    }
    if (quote.has_variant && !String(quote.variant_name || '').trim()) itemErrors.variant_name = 'Enter the proposed variant name.'

    if (Object.keys(itemErrors).length > 0) {
      errors[item.id] = itemErrors
    }
  }

  quoteErrors.value = errors
  quoteValidationMessage.value = Object.keys(errors).length > 0
    ? 'Complete all required quotation fields for every listed product before submitting.'
    : ''

  return Object.keys(errors).length === 0
}

const submitQuote = () => {
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

  if (!validateAllQuotes()) {
    toast.add({
      severity: 'warn',
      summary: 'Incomplete Quotations',
      detail: quoteValidationMessage.value,
      life: 4000,
    })
    return
  }

  confirm.require({
    header: 'Submit all quotations?',
    message: 'Please review all quotation details. Once submitted, they will be sent to the business for evaluation.',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'No',
    acceptLabel: 'Submit',
    acceptClass: 'p-button-primary',
    accept: performQuoteSubmission,
  })
}

const performQuoteSubmission = async () => {
  if (!rfq.value) return

  submitting.value = true
  try {
    for (const item of rfq.value.items || []) {
      const quote = quoteData.value[item.id]
      await supplierService.submitRFQFeedback({
        rfq_id: rfq.value.id,
        rfq_item_id: Number(item.id),
        quoted_price: quote.quoted_price,
        available_quantity: Math.round(Number(quote.available_quantity)),
        length_cm: quote.length_cm,
        width_cm: quote.width_cm,
        height_cm: quote.height_cm,
        weight_kg: quote.weight_kg,
        estimated_delivery_date: formatDateForApi(quote.estimated_delivery_date),
        quotation_valid_until: formatDateForApi(quote.quotation_valid_until),
        attachment: quote.attachment,
        product_specifications: quote.product_specifications,
        additional_notes: quote.additional_notes,
        description: quote.description,
        has_variant: quote.has_variant,
        variant_name: quote.variant_name,
        supplier_sku: quote.supplier_sku,
        variant_size: quote.variant_size,
        variant_color: quote.variant_color,
        variant_texture: quote.variant_texture,
        variant_finish: quote.variant_finish,
        variant_material: quote.variant_material,
        unit_of_measurement: quote.unit_of_measurement,
        variant_images: quote.variant_images,
      })
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'All quotes submitted successfully',
      life: 3000,
    })
    quoteDialogVisible.value = false
    await loadRFQDetail()
  } catch (error: any) {
    const validationErrors = error.response?.data?.errors || {}
    const firstValidationError = Object.values(validationErrors)
      .flat()
      .find((message: any) => typeof message === 'string') as string | undefined
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: firstValidationError || error.response?.data?.message || 'Failed to submit quote',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const setAttachment = (itemId: number, event: Event) => {
  quoteData.value[itemId].attachment = (event.target as HTMLInputElement).files?.[0] || null
}

const formatDateForApi = (value: Date | string | null) => {
  if (!value) return ''
  const date = value instanceof Date ? value : new Date(value)
  // DatePicker values are local calendar dates. Avoid toISOString(), which
  // shifts a Philippine midnight date to the prior UTC day.
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const getAttachmentUrl = (path: string) => path.startsWith('http') ? path : `/storage/${path.replace(/^\/+/, '')}`

const calculateQuoteTotal = (item: any, quote: any) => {
  return (Number(item?.quantity || 0) * Number(quote?.quoted_price || 0)).toFixed(2)
}
const setVariantImages = (itemId: number, event: Event) => {
  quoteData.value[itemId].variant_images = Array.from((event.target as HTMLInputElement).files || []).slice(0, 5)
}

const quoteDimensions = (quote: any) => {
  if (!quote?.length_cm || !quote?.width_cm || !quote?.height_cm) return '—'
  return `${Number(quote.length_cm).toLocaleString()} × ${Number(quote.width_cm).toLocaleString()} × ${Number(quote.height_cm).toLocaleString()} cm`
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
  quoteErrors.value = {}
  quoteValidationMessage.value = ''
  Object.values(quoteData.value).forEach((quote: any) => {
    if (!quote.quotation_valid_until) {
      const validUntil = new Date(today)
      validUntil.setDate(validUntil.getDate() + 5)
      quote.quotation_valid_until = validUntil
    }
  })
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
