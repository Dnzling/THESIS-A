<template>
  <div class="supplier-rfq-detail space-y-4 text-sm">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" rounded outlined severity="secondary" class="!h-9 !w-9 !border-slate-200 !bg-white" aria-label="Go back" title="Go back" @click="goBack" />
        <h1 class="text-lg font-semibold text-slate-900">RFQ Details</h1>
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
        <Card class="overflow-hidden rounded-xl border border-slate-200 shadow-none">
        <template #content>
          <div v-if="rfq" class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,2fr)_minmax(13rem,1fr)]">
            <div class="space-y-4">
              <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">RFQ</p>
                <p class="mt-1 break-all text-base font-semibold text-slate-900">{{ rfq.rfq_number }}</p>
              </div>
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <div class="text-xs text-slate-500">Created</div>
                  <div class="mt-0.5 font-medium text-slate-900">{{ formatDateWithTime(rfq.created_at) }}</div>
                </div>
                <div>
                  <div class="text-xs text-slate-500">Store</div>
                  <div class="mt-0.5 font-medium text-slate-900">{{ rfq.store?.store_name || rfq.store?.name || rfq.store_name || rfq.store?.store_code || 'Not set' }}</div>
                </div>
                <div class="sm:col-span-2">
                  <div class="text-xs text-slate-500">Supplier</div>
                  <div class="mt-0.5 font-medium text-slate-900">{{ supplier?.company_name || supplier?.supplier_name || 'Your supplier account' }}</div>
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
        v-if="requestDetails || rfq?.qualification_requirements"
        class="overflow-hidden rounded-xl border border-slate-200 shadow-none"
      >
        <template #title>
          <div class="flex items-center gap-2 text-base font-semibold text-slate-900">
            <i class="pi pi-file-edit text-orange-600"></i>
            Request Details &amp; Requirements
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div v-if="requestDetails" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-800">
                <i class="pi pi-info-circle text-orange-600"></i>
                Request Details &amp; Instructions
              </div>
              <p class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ requestDetails }}</p>
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
      <Card title="Requested Products" class="overflow-hidden rounded-xl border border-slate-200 shadow-none">
        <template #content>
          <DataTable :value="rfq?.items || []" rowHover size="small" class="w-full" responsiveLayout="scroll">
            <Column header="Product">
              <template #body="{ data }">
                <div>
                  <div class="font-medium text-slate-900">{{ data.product?.product_name || 'N/A' }}</div>
                  <div v-if="data.product?.sku" class="mt-0.5 text-xs text-slate-500">{{ data.product.sku }}</div>
                  <div v-if="data.variation" class="mt-1 text-xs text-orange-700">
                    {{ data.variation.variation_name || [data.variation.size, data.variation.color, data.variation.material].filter(Boolean).join(' / ') || data.variation.variation_sku }}
                  </div>
                </div>
              </template>
            </Column>
            <Column header="Requested Quantity">
              <template #body="{ data }">
                <div>
                  <p class="font-medium text-slate-900">{{ data.quantity }} {{ data.product?.unit_of_measurement || data.unit_of_measurement || '' }}</p>
                  <p v-if="data.supplier_offer?.minimum_order_quantity" class="mt-0.5 text-xs text-slate-500">MOQ {{ data.supplier_offer.minimum_order_quantity }}</p>
                </div>
              </template>
            </Column>
            <Column field="specifications" header="Specifications">
              <template #body="{ data }">
                <p class="max-w-xs truncate text-slate-600" :title="data.specifications || ''">{{ data.specifications || 'N/A' }}</p>
              </template>
            </Column>
            <Column header="Your Quote">
              <template #body="{ data }">
                <div v-if="feedbackByItemId[data.id]" class="flex min-w-36 flex-col items-start gap-1.5">
                  <span class="font-semibold text-slate-900">{{ formatCurrency(feedbackByItemId[data.id].quoted_price) }}</span>
                  <Tag :value="formatQuoteStatus(feedbackByItemId[data.id].status)" :severity="feedbackSeverity(feedbackByItemId[data.id].status)" />
                  <Button label="View quote" text size="small" severity="warn" class="!px-0" @click="openQuoteDetails(data)" />
                </div>
                <span v-else class="text-slate-400">Not quoted</span>
              </template>
            </Column>
  
          </DataTable>
          <div v-if="rfq && !isClosedRFQ(rfq) && canEditQuotes && Object.keys(feedbackByItemId).length === 0" class="mt-4 border-t border-slate-100 pt-4">
            <Button label="Submit Quote" icon="pi pi-send" severity="warn" size="small" @click="openQuoteDialog" />
          </div>
        </template>
  
      </Card>
    </div>

    <Dialog v-model:visible="quoteDetailsVisible" modal header="Quote Details" :style="{ width: 'min(42rem, 94vw)' }">
      <div v-if="selectedQuoteItem && selectedQuoteFeedback" class="space-y-4 text-sm">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-3">
          <div>
            <p class="font-semibold text-slate-900">{{ selectedQuoteItem.product?.product_name || 'Requested product' }}</p>
            <p class="mt-1 text-xs text-slate-500">Requested: {{ selectedQuoteItem.quantity }} {{ selectedQuoteItem.product?.unit_of_measurement || '' }}</p>
          </div>
          <Tag :value="formatQuoteStatus(selectedQuoteFeedback.status)" :severity="feedbackSeverity(selectedQuoteFeedback.status)" />
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
          <div class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Unit cost</p><p class="mt-1 font-semibold text-slate-900">{{ formatCurrency(selectedQuoteFeedback.quoted_price) }}</p></div>
          <div class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Available</p><p class="mt-1 font-semibold text-slate-900">{{ selectedQuoteFeedback.available_quantity ?? 'Not set' }}</p></div>
          <div class="rounded-lg border border-orange-200 bg-orange-50/60 p-3"><p class="text-xs text-orange-700">Estimated total</p><p class="mt-1 font-semibold text-orange-900">{{ formatCurrency(calculateQuoteTotal(selectedQuoteItem, selectedQuoteFeedback)) }}</p></div>
          <div class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Delivery</p><p class="mt-1 font-medium text-slate-900">{{ formatDate(selectedQuoteFeedback.estimated_delivery_date) }}</p></div>
          <div class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Valid until</p><p class="mt-1 font-medium text-slate-900">{{ formatDate(selectedQuoteFeedback.quotation_valid_until) }}</p></div>
          <div class="rounded-lg border border-slate-200 p-3"><p class="text-xs text-slate-500">Dimensions</p><p class="mt-1 font-medium text-slate-900">{{ quoteDimensions(selectedQuoteFeedback) }}</p></div>
        </div>
        <p v-if="selectedQuoteFeedback.rejection_reason" class="rounded-lg border border-red-200 bg-red-50 p-3 text-red-700">{{ selectedQuoteFeedback.rejection_reason }}</p>
        <p v-if="selectedQuoteFeedback.product_specifications" class="whitespace-pre-line rounded-lg bg-slate-50 p-3 text-slate-700"><strong>Specifications</strong><br>{{ selectedQuoteFeedback.product_specifications }}</p>
        <p v-if="selectedQuoteFeedback.additional_notes" class="whitespace-pre-line rounded-lg bg-slate-50 p-3 text-slate-700"><strong>Notes</strong><br>{{ selectedQuoteFeedback.additional_notes }}</p>
        <div v-if="selectedQuoteFeedback.has_variant" class="rounded-lg border border-slate-200 p-3">
          <p class="font-medium text-slate-800">Proposed variants</p>
          <p class="mt-1">{{ selectedQuoteFeedback.variant_name }}</p>
          <p v-for="(variant, index) in selectedQuoteFeedback.additional_variants || []" :key="index" class="text-slate-600">{{ variant.variant_name }}</p>
        </div>
        <a v-if="selectedQuoteFeedback.attachment_path" :href="getAttachmentUrl(selectedQuoteFeedback.attachment_path)" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-orange-700 hover:underline"><i class="pi pi-paperclip" />View quotation attachment</a>
        <div v-if="selectedQuoteFeedback.negotiations?.length" class="space-y-2 border-t border-slate-100 pt-3">
          <h3 class="font-semibold text-slate-900">Counter Offers</h3>
          <div v-for="nego in selectedQuoteFeedback.negotiations" :key="nego.id" class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
            <div>
              <p class="font-semibold text-slate-900">{{ formatCurrency(nego.counter_price) }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ formatDate(nego.created_at) }} - {{ formatQuoteStatus(nego.status) }}</p>
              <p v-if="nego.notes" class="mt-1 text-xs text-slate-600">{{ nego.notes }}</p>
            </div>
            <div v-if="nego.status === 'pending'" class="flex gap-2">
              <Button label="Reject" severity="secondary" outlined size="small" @click="rejectNego(nego.id)" />
              <Button label="Accept" severity="warn" size="small" @click="acceptNego(nego.id)" />
            </div>
          </div>
        </div>
      </div>
      <template #footer><Button label="Close" severity="secondary" outlined size="small" @click="quoteDetailsVisible = false" /></template>
    </Dialog>
  
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
                <p>{{ item.quantity }} {{ item.product?.unit_of_measurement || '' }} requested</p>
                <p v-if="item.supplier_offer?.minimum_order_quantity" class="mt-1 text-xs text-slate-500">
                  Supplier MOQ: {{ item.supplier_offer.minimum_order_quantity }} {{ item.product?.unit_of_measurement || '' }}
                </p>
              </div>
              <div>
                <p class="text-sm font-semibold">Specifications</p>
                <p class="text-sm">{{ item.specifications || 'N/A' }}</p>
              </div>
            </div>

            <div v-if="item.supplier_offer" class="mb-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
              <p class="mb-3 text-sm font-semibold text-slate-800">Supplier product specifications</p>
              <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <div v-for="dimension in dimensionFields" :key="dimension.key" class="rounded-lg bg-white p-2">
                  <p class="text-[11px] text-slate-500">{{ dimension.label }}</p>
                  <p class="font-medium text-slate-800">{{ formatProductMeasurement(productValue(item, dimension.key), dimension.suffix) }}</p>
                </div>
              </div>
              <div v-if="productAttributes(item).length" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                <div v-for="attribute in productAttributes(item)" :key="attribute.key" class="rounded-lg bg-white p-2">
                  <p class="text-[11px] text-slate-500">{{ attribute.label }}</p>
                  <p class="font-medium text-slate-800">{{ attribute.value }}</p>
                </div>
              </div>
              <div v-if="productMeasurements(item).length" class="mt-3 border-t border-slate-200 pt-3">
                <p class="mb-2 text-xs font-medium text-slate-600">Additional measurements</p>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                  <div v-for="(measurement, index) in productMeasurements(item)" :key="`${measurement.label}-${index}`" class="rounded-lg bg-white p-2">
                    <p class="text-[11px] text-slate-500">{{ measurement.label }}</p>
                    <p class="font-medium text-slate-800">{{ measurement.value }} {{ measurement.unit || '' }}</p>
                  </div>
                </div>
              </div>
              <p v-if="item.specifications" class="mt-3 border-t border-slate-200 pt-3 text-xs text-slate-600">
                <span class="font-medium text-slate-800">Requested specifications:</span> {{ item.specifications }}
              </p>
              <small v-if="quoteErrors[item.id]?.specifications" class="mt-3 block text-red-600">{{ quoteErrors[item.id].specifications }}</small>
            </div>
            <div v-else class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3">
              <label :for="`supplier-specifications-${item.id}`" class="mb-1 block text-sm font-semibold text-slate-800">Your product details <span class="font-normal text-slate-500">(optional)</span></label>
              <p class="mb-2 text-xs text-slate-600">This item is not in your supplier product list. Add any details that will help describe your offer. Dimensions and weight are not required.</p>
              <Textarea :id="`supplier-specifications-${item.id}`" v-model="quoteData[item.id].product_specifications" rows="3" autoResize class="w-full" placeholder="Product specifications or details" />
            </div>
  
            <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium mb-2">Unit Cost (tax inclusive) *</label>
              <InputNumber v-model="quoteData[item.id].quoted_price" mode="currency" :min="0" :currency="rfq?.currency || 'PHP'"
                class="w-full" :invalid="Boolean(quoteErrors[item.id]?.quoted_price)"
                @update:modelValue="clearQuoteError(item.id, 'quoted_price')" />
              <small v-if="item.supplier_offer" class="mt-1 block text-slate-500">
                Prefilled from your product listing. The contract discount is applied to the quotation total.
              </small>
              <small v-else class="mt-1 block text-slate-500">Enter your tax-inclusive unit cost for this quotation.</small>
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

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mt-3">
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
                <FileUpload mode="basic" :auto="false" :customUpload="true" chooseLabel="Choose file" chooseIcon="pi pi-paperclip"
                  accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" :maxFileSize="5242880"
                  @select="setAttachment(item.id, $event)" />
                <div v-if="quoteData[item.id].attachment" class="mt-2 flex items-center gap-2 text-xs text-slate-600">
                  <i class="pi pi-file"></i><span class="truncate">{{ quoteData[item.id].attachment.name }}</span>
                  <Button icon="pi pi-times" text rounded size="small" aria-label="Remove attachment" @click="quoteData[item.id].attachment = null" />
                </div>
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
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputNumber from 'primevue/inputnumber'
import DatePicker from 'primevue/datepicker'
import FileUpload from 'primevue/fileupload'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Message from 'primevue/message'
import Textarea from 'primevue/textarea'
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
const supplier = ref(null)
const requestDetails = computed(() => [rfq.value?.description, rfq.value?.instructions]
  .map((value: string | null | undefined) => value?.trim())
  .filter(Boolean)
  .join('\n\n'))
const feedbackByItemId = ref<Record<number, any>>({})
const quoteData = ref({})
const quoteErrors = ref<Record<number, Record<string, string>>>({})
const quoteValidationMessage = ref('')
const canEditQuotes = ref(true)
const quoteDialogVisible = ref(false)
const quoteDetailsVisible = ref(false)
const selectedQuoteItem = ref<any>(null)
const selectedQuoteFeedback = computed(() => selectedQuoteItem.value ? feedbackByItemId.value[selectedQuoteItem.value.id] || null : null)
const today = new Date()
const dimensionFields = [
  { key: 'length_cm', label: 'Length', suffix: 'cm' },
  { key: 'width_cm', label: 'Width', suffix: 'cm' },
  { key: 'height_cm', label: 'Height', suffix: 'cm' },
  { key: 'depth_cm', label: 'Depth', suffix: 'cm' },
  { key: 'weight_kg', label: 'Weight', suffix: 'kg' },
] as const

const productValue = (item: any, key: string) => item?.variation?.[key] ?? item?.product?.[key] ?? null
const formatProductMeasurement = (value: unknown, suffix: string) => value === null || value === undefined || value === ''
  ? 'Not recorded'
  : `${Number(value).toLocaleString()} ${suffix}`
const productMeasurements = (item: any) => {
  const measurements = item?.variation?.additional_measurements ?? item?.product?.additional_measurements ?? []
  return Array.isArray(measurements) ? measurements.filter((measurement: any) => measurement?.label && measurement?.value !== null && measurement?.value !== undefined) : []
}
const productAttributes = (item: any) => {
  const variation = item?.variation
  const attributes = variation
    ? { color: variation.color, size: variation.size, material: variation.material, texture: variation.texture, finish: variation.finish }
    : item?.product?.variation_attributes || {}
  return [
    { key: 'color', label: 'Color', value: attributes.color },
    { key: 'size', label: 'Size', value: attributes.size },
    { key: 'material', label: 'Material', value: attributes.material },
    { key: 'texture', label: 'Texture', value: attributes.texture },
    { key: 'finish', label: 'Finish', value: attributes.finish },
  ].filter(attribute => String(attribute.value || '').trim())
}
const catalogSpecifications = (item: any) => {
  const dimensions = dimensionFields.map(dimension => `${dimension.label}: ${formatProductMeasurement(productValue(item, dimension.key), dimension.suffix)}`)
  const attributes = productAttributes(item).map(attribute => `${attribute.label}: ${attribute.value}`)
  const measurements = productMeasurements(item).map((measurement: any) => `${measurement.label}: ${measurement.value} ${measurement.unit || ''}`.trim())
  return [...dimensions, ...attributes, ...measurements].join('\n')
}

const getStatusSeverity = (status: string) => {
  const map: { [key: string]: string } = {
    draft: 'secondary',
    sent: 'warn',
    sent_to_supplier: 'warn',
    receiving: 'info',
    awarded: 'success',
    completed: 'success',
    quotes_received: 'success',
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

const formatCurrency = (value: unknown) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: rfq.value?.currency || 'PHP',
  minimumFractionDigits: 2,
}).format(Number(value || 0))

const openQuoteDetails = (item: any) => {
  selectedQuoteItem.value = item
  quoteDetailsVisible.value = true
}

const goBack = () => {
  if (window.history.length > 1) {
    router.back()
    return
  }
  router.push({ name: 'supplier.rfqs' })
}

const formatQuoteStatus = (status: string) => {
  if (status === 'sent' || status === 'sent_to_supplier') return 'Pending'
  return String(status || 'pending').replace(/[_-]/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

const formatDate = (date: string) => {
  if (!date) return 'Not set'
  return new Date(date).toLocaleDateString()
}

const formatDateWithTime = (date: string) => {
  if (!date) return 'Not set'
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
    supplier.value = res.data.supplier || null
    const feedback = res.data.supplier_feedback || []

    // Initialize quote data (exclude tax_rate; server contract tax_rate is authoritative)
    rfq.value.items?.forEach((item: any) => {
      if (!quoteData.value[item.id]) {
        quoteData.value[item.id] = {
          quoted_price: item.supplier_offer?.unit_cost != null ? Number(item.supplier_offer.unit_cost) : null,
          available_quantity: item.quantity || null,
          length_cm: productValue(item, 'length_cm'),
          width_cm: productValue(item, 'width_cm'),
          height_cm: productValue(item, 'height_cm'),
          weight_kg: productValue(item, 'weight_kg'),
          estimated_delivery_date: null,
          quotation_valid_until: null,
          attachment: null,
          product_specifications: '',
          additional_notes: '',
          description: '',
          has_variant: false, variant_name: '', supplier_sku: '', variant_size: '', variant_color: '', variant_texture: '', variant_finish: '', variant_material: '', unit_of_measurement: item.product?.unit_of_measurement || '', variant_images: [], additional_variants: [],
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
        has_variant: Boolean(f.has_variant), variant_name: f.variant_name || '', supplier_sku: f.supplier_sku || '', variant_size: f.variant_size || '', variant_color: f.variant_color || '', variant_texture: f.variant_texture || '', variant_finish: f.variant_finish || '', variant_material: f.variant_material || '', unit_of_measurement: f.unit_of_measurement || item?.product?.unit_of_measurement || '', variant_images: [], additional_variants: f.additional_variants || [],
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
    if (item.supplier_offer) {
      for (const field of ['length_cm', 'width_cm', 'height_cm', 'weight_kg']) {
        const catalogValue = productValue(item, field)
        if (!catalogValue || Number(catalogValue) <= 0) {
          itemErrors.specifications = 'Complete the product dimensions and weight in your supplier product catalog before submitting this quote.'
        }
      }
    }
    if (!quote.estimated_delivery_date) {
      itemErrors.estimated_delivery_date = 'Select an estimated delivery date.'
    }
    if (!quote.quotation_valid_until) {
      itemErrors.quotation_valid_until = 'Select the quotation validity date.'
    }
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
        length_cm: productValue(item, 'length_cm'),
        width_cm: productValue(item, 'width_cm'),
        height_cm: productValue(item, 'height_cm'),
        weight_kg: productValue(item, 'weight_kg'),
        estimated_delivery_date: formatDateForApi(quote.estimated_delivery_date),
        quotation_valid_until: formatDateForApi(quote.quotation_valid_until),
        attachment: quote.attachment,
        product_specifications: item.supplier_offer ? catalogSpecifications(item) : (quote.product_specifications?.trim() || undefined),
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

const setAttachment = (itemId: number, event: { files?: File[] }) => {
  quoteData.value[itemId].attachment = event.files?.[0] || null
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
const quoteDimensions = (quote: any) => {
  if (!quote?.length_cm || !quote?.width_cm || !quote?.height_cm) return 'Not set'
  return `${Number(quote.length_cm).toLocaleString()} x ${Number(quote.width_cm).toLocaleString()} x ${Number(quote.height_cm).toLocaleString()} cm`
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
