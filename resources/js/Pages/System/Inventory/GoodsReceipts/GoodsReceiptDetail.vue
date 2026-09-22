<template>
  <div class="mx-auto max-w-7xl space-y-6 pb-8">
    <div v-if="loading" class="py-12 text-center text-gray-500 space-y-2">
      <p class="text-lg font-semibold">Loading receipt...</p>
      <p class="text-sm text-gray-500">Please wait while we fetch the latest information.</p>
    </div>
    <div v-else-if="!receipt" class="py-12 text-center text-gray-500 space-y-3">
      <p class="text-lg font-semibold">Receipt not found.</p>
      <p class="text-sm text-gray-500">It may have been removed or the identifier is invalid.</p>
      <Button label="Back to Receipts" severity="secondary" class="mt-4" @click="goBack" />
    </div>
    <div v-else class="space-y-6">
      <!-- Finance-style header -->
      <div class="flex items-center justify-between">
        <div class="flex min-w-0 items-center gap-3">
          <button @click="goBack" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 transition-colors hover:bg-gray-200">
            <i class="pi pi-chevron-left text-gray-600"></i>
          </button>
          <div class="min-w-0">
            <h1 class="truncate text-2xl font-semibold tracking-tight text-gray-900 md:text-3xl">{{ receipt.grn_number }}</h1>
            <p class="mt-1 truncate text-sm text-gray-500">Goods receipt for {{ receipt.po_number || 'purchase order' }}</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            v-if="isProcurement && receipt.receipt_status !== 'full' && !resolution"
            label="Flag Deficiency"
            icon="pi pi-flag"
            severity="danger"
            size="small"
            @click="openResolutionDialog"
          />
          <Button
            v-if="canRateSupplier"
            :label="receipt.supplier_evaluation ? 'Update Supplier Rating' : 'Rate Supplier'"
            icon="pi pi-star"
            @click="openSupplierRating"
          />
          <Button
            label="Print PDF"
            severity="secondary"
            size="small"
            :loading="printing"
            @click="printReceipt"
          />
        </div>
      </div>

      <Card v-if="resolution" class="border border-amber-200 bg-amber-50/40 shadow-sm">
        <template #content>
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <div class="flex items-center gap-2">
                <i class="pi pi-flag text-amber-600"></i>
                <p class="font-semibold text-gray-900">{{ resolution.resolution_number }}</p>
                <Badge :value="formatResolutionStatus(resolution.status)" :severity="resolutionSeverity(resolution.status)" />
              </div>
              <p class="mt-1 text-sm text-gray-600">{{ formatResolutionStatus(resolution.resolution_type) }}</p>
              <p v-if="resolution.procurement_notes" class="mt-1 text-sm text-gray-600">{{ resolution.procurement_notes }}</p>
              <p v-if="resolution.supplier_rejection_reason" class="mt-1 text-sm text-red-600">Supplier reason: {{ resolution.supplier_rejection_reason }}</p>
            </div>
            <p class="text-xs text-gray-500">Follow-up GRN: {{ resolution.follow_up_receipt?.grn_number || 'Pending' }}</p>
          </div>
        </template>
      </Card>

      <!-- Finance-style summary cards -->
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="mb-3 flex items-center justify-between"><span class="text-sm font-medium text-gray-500">Receipt Status</span><div :class="statusDot(receipt.receipt_status)" class="h-2 w-2 rounded-full"></div></div>
          <span :class="statusText(receipt.receipt_status)" class="text-base font-semibold">{{ formatStatus(receipt.receipt_status) }}</span>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
          <div class="mb-3 flex items-center justify-between"><span class="text-sm font-medium text-gray-500">Quality Check</span><div :class="qualityDot(displayQualityStatus)" class="h-2 w-2 rounded-full"></div></div>
          <span :class="qualityText(displayQualityStatus)" class="text-base font-semibold">{{ formatStatus(displayQualityStatus) }}</span>
        </div>
        <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
          <span class="mb-3 block text-sm font-medium text-gray-500">Received Items</span>
          <span class="text-2xl font-bold tracking-tight text-gray-900">{{ receipt.items?.length || 0 }}</span>
          <span class="ml-1 text-sm text-gray-500">lines</span>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-gray-900 to-gray-800 p-5 shadow-lg">
          <span class="mb-3 block text-sm font-medium text-gray-400">Received Date</span>
          <span class="text-lg font-bold tracking-tight text-white">{{ formatDate(receipt.receipt_date) }}</span>
          <span class="mt-1 block text-xs text-gray-400">{{ receipt.supplier_evaluation ? `Rating ${Number(receipt.supplier_evaluation.overall_rating).toFixed(1)}/5` : 'Supplier not rated' }}</span>
        </div>
      </div>

      <!-- Main Tabs -->
      <TabView v-model:activeIndex="activeTab" class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <!-- Receipt Details -->
        <TabPanel value="0">
          <template #header>
            <span>Details</span>
          </template>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- GRN Information -->
            <Card>
              <template #header>
                <div class="p-4 bg-blue-50 border-b">
                  <h3 class="font-semibold text-gray-800">GRN Information</h3>
                </div>
              </template>
              <template #content>
                <div class="space-y-3">
                  <div class="flex items-start justify-between gap-4">
                    <span class="text-gray-600">GRN Number</span>
                    <span class="font-semibold">{{ receipt.grn_number }}</span>
                  </div>
                  <div class="flex items-start justify-between gap-4">
                    <span class="text-gray-600">Reference PO</span>
                    <RouterLink
                      :to="`/procurement/purchase-orders/${receipt.po_id}`"
                      class="font-semibold text-blue-600 hover:underline"
                    >
                      {{ receipt.po_number }}
                    </RouterLink>
                  </div>
                  <div class="flex items-start justify-between gap-4">
                    <span class="text-gray-600">Purchase Requisition</span>
                    <RouterLink
                      v-if="receipt.purchase_requisition_id"
                      :to="`/procurement/purchase-requisitions/${receipt.purchase_requisition_id}`"
                      class="font-semibold text-blue-600 hover:underline"
                    >
                      {{ receipt.purchase_requisition_number || `PR #${receipt.purchase_requisition_id}` }}
                    </RouterLink>
                    <span v-else class="font-semibold text-gray-400">-</span>
                  </div>
                  <div class="flex items-start justify-between gap-4">
                    <span class="text-gray-600">Received Date</span>
                    <span class="font-semibold">{{ formatDate(receipt.receipt_date) }}</span>
                  </div>
                  <Divider />
                  <div class="flex items-start justify-between gap-4">
                    <span class="text-gray-600">Expected Pickup</span>
                    <span class="font-semibold">{{ formatDate(receipt.expected_delivery_date) }}</span>
                  </div>
                  <div class="flex items-start justify-between gap-4">
                    <span class="text-gray-600 font-semibold">
                      {{ isLate ? 'Days Late' : 'Days Early' }}
                    </span>
                    <span :class="isLate ? 'text-red-600' : 'text-green-600'" class="font-bold">
                      {{ Math.abs(daysVariance) }} days
                    </span>
    </div>

    <Dialog v-model:visible="resolutionDialog" modal header="Resolve Goods Receipt Deficiency" :style="{ width: 'min(92vw, 680px)' }">
      <div class="space-y-5">
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Resolution</label>
          <Select v-model="resolutionForm.resolution_type" :options="resolutionTypes" optionLabel="label" optionValue="value" fluid />
        </div>
        <div class="rounded-xl border border-gray-200 overflow-hidden">
          <DataTable :value="deficientItems" size="small" class="text-sm">
            <Column field="description" header="Item" />
            <Column header="Expected"><template #body="{ data }">{{ data.po_quantity }} {{ data.unit }}</template></Column>
            <Column header="Received"><template #body="{ data }">{{ data.received_quantity }} {{ data.unit }}</template></Column>
            <Column header="Due"><template #body="{ data }"><span class="font-semibold text-red-600">{{ data.quantity_due }} {{ data.unit }}</span></template></Column>
          </DataTable>
        </div>
        <div>
          <label class="mb-2 block text-sm font-medium text-gray-700">Instructions to supplier</label>
          <Textarea v-model="resolutionForm.procurement_notes" rows="4" fluid placeholder="Describe the required replacement or remaining delivery." />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" severity="secondary" text size="small" @click="resolutionDialog = false" />
        <Button label="Send Resolution" icon="pi pi-send" size="small" :loading="resolutionSaving" @click="submitResolution" />
      </template>
    </Dialog>
  </div>
              </template>
            </Card>

            <!-- Supplier Information -->
            <Card>
              <template #header>
                <div class="p-4 bg-green-50 border-b">
                  <h3 class="font-semibold text-gray-800">Supplier Information</h3>
                </div>
              </template>
              <template #content>
                <div class="space-y-3">
                  <div class="flex items-start justify-between gap-4">
                    <span class="text-gray-600">Supplier</span>
                    <RouterLink
                      :to="`/procurement/suppliers/${receipt.supplier_id}`"
                      class="font-semibold text-blue-600 hover:underline"
                    >
                      {{ receipt.supplier_name }}
                    </RouterLink>
                  </div>
                  <div class="flex items-start justify-between gap-4">
                    <span class="text-gray-600">Contact</span>
                    <span class="font-semibold">{{ receipt.contact_person }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Email</span>
                    <a :href="`mailto:${receipt.email}`" class="text-blue-600 hover:underline">
                      {{ receipt.email }}
                    </a>
                  </div>
                  <Divider />
                <div class="flex items-start justify-between gap-4">
                  <span class="text-gray-600">Received By</span>
                  <span class="font-semibold">{{ personName(receipt.received_by) }}</span>
                </div>
                <div class="flex items-start justify-between gap-4">
                  <span class="text-gray-600">Verified By</span>
                  <span class="font-semibold">{{ personName(receipt.verified_by) || '-' }}</span>
                </div>
                </div>
              </template>
            </Card>
          </div>

          <!-- Line Items -->
          <Card class="mt-6">
            <template #header>
              <div class="p-4 bg-blue-50 border-b">
                <h3 class="font-semibold text-gray-800">Received Items</h3>
              </div>
            </template>
            <template #content>
              <DataTable :value="receipt.items || []" class="p-datatable-sm" stripedRows>
                <Column field="po_line_number" header="Line" style="width: 8%">
                  <template #body="{ data }">#{{ data.po_line_number }}</template>
                </Column>
                <Column field="description" header="Description" style="width: 28%" />
                <Column header="PO Qty" style="width: 10%">
                  <template #body="{ data }">
                    {{ data.po_quantity }}
                  </template>
                </Column>
                <Column header="Received" style="width: 10%">
                  <template #body="{ data }">
                    <Badge
                      :value="data.received_quantity"
                      :severity="data.received_quantity === data.po_quantity ? 'success' : 'warning'"
                    />
                  </template>
                </Column>
                <Column header="Unit" style="width: 8%">
                  <template #body="{ data }">
                    {{ data.unit }}
                  </template>
                </Column>
                <Column header="Variance" style="width: 12%">
                  <template #body="{ data }">
                    <div class="text-center">
                      <span v-if="data.received_quantity === data.po_quantity" class="text-green-600 font-semibold">
                        Match
                      </span>
                      <span v-else class="text-red-600 font-semibold">
                        {{ data.received_quantity - data.po_quantity }}
                      </span>
                    </div>
                  </template>
                </Column>
                <Column header="Quality" style="width: 12%">
                  <template #body="{ data }">
                    <Badge
                      :value="data.quality_status || 'Pending'"
                      :severity="
                        data.quality_status === 'good' ? 'success' :
                        data.quality_status === 'fair' ? 'warning' :
                        data.quality_status === 'defective' ? 'danger' : 'secondary'
                      "
                    />
                  </template>
                </Column>

                <template #empty>
                  <div class="text-center py-8 text-gray-500">
                    No items received
                  </div>
                </template>
              </DataTable>
            </template>
          </Card>

          <!-- Discrepancies -->
          <Card v-if="receipt.discrepancies && receipt.discrepancies.length > 0" class="mt-6">
            <template #header>
              <div class="p-4 bg-red-50 border-b">
                <h3 class="font-semibold text-red-800">Discrepancies</h3>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <div v-for="(disc, idx) in receipt.discrepancies" :key="idx" class="border-l-4 border-red-600 pl-4 py-2">
                  <p class="font-semibold text-gray-800">{{ disc.item_description }}</p>
                  <p class="text-sm text-gray-600 mt-1">{{ disc.issue }}</p>
                  <Badge :value="disc.status" class="mt-2" />
                </div>
              </div>
            </template>
          </Card>
        </TabPanel>

        <!-- Quality Check -->
        <TabPanel value="1">
          <template #header>
            <span>Quality Check</span>
            <Badge :value="displayQualityStatus" :severity="qualitySeverity(displayQualityStatus)" class="ml-2" />
          </template>

          <Card>
            <template #content>
              <div class="space-y-6">
                <!-- Overall Quality -->
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Overall Quality Status
                  </label>
                  <div class="flex gap-4">
                    <div class="flex items-center gap-2">
                      <RadioButton v-model="receipt.quality_status" value="good" />
                      <label>Good - All items acceptable</label>
                    </div>
                    <div class="flex items-center gap-2">
                      <RadioButton v-model="receipt.quality_status" value="fair" />
                      <label>Fair - Minor issues present</label>
                    </div>
                    <div class="flex items-center gap-2">
                      <RadioButton v-model="receipt.quality_status" value="poor" />
                      <label>Poor - Major issues</label>
                    </div>
                  </div>
                </div>

                <!-- Item Quality Assessment -->
                <div class="border-t pt-6">
                  <h3 class="text-lg font-semibold text-gray-800 mb-4">Item Quality Assessment</h3>
                  <DataTable :value="receipt.items || []" class="p-datatable-sm">
                    <Column field="description" header="Item" style="width: 40%" />
                    <Column header="Quality" style="width: 30%">
                      <template #body="{ data }">
                        <Select
                          v-model="data.quality_status"
                          :options="qualityOptions"
                          placeholder="Select quality"
                          class="w-full"
                        />
                      </template>
                    </Column>
                    <Column header="Defects" style="width: 30%">
                      <template #body="{ data }">
                        <InputText
                          v-model="data.defect_notes"
                          placeholder="Note any defects"
                          class="w-full"
                        />
                      </template>
                    </Column>
                  </DataTable>
                </div>

                <!-- Quality Notes -->
                <div>
                  <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Quality Assessment Notes
                  </label>
                  <Textarea
                    v-model="receipt.quality_notes"
                    placeholder="Add any quality assessment notes"
                    rows="4"
                    class="w-full"
                  />
                </div>

                <!-- Actions -->
                <div class="flex gap-2 justify-end pt-4 border-t">
                  <Button label="Cancel" severity="secondary" @click="goBack" />
                  <Button
                    label="Save Quality Check"
                    @click="saveQualityCheck"
                    :loading="saving"
                  />
                </div>
              </div>
            </template>
          </Card>
        </TabPanel>

        <!-- Timeline -->
        <TabPanel value="2">
          <template #header>
            <span>Timeline</span>
          </template>

          <div v-if="timeline.length" class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-100">
            <div v-for="item in timeline" :key="`${item.label}-${item.date}`" class="flex items-start gap-4 bg-white px-4 py-4">
              <span class="mt-1.5 h-3 w-3 shrink-0 rounded-full" :style="{ backgroundColor: item.color }" />
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <p class="font-semibold text-gray-900">{{ item.label }}</p>
                  <p class="text-xs text-gray-500">{{ item.date }}</p>
                </div>
                <div v-if="item.attachments?.length" class="mt-3 flex flex-wrap gap-2">
                  <a v-for="attachment in item.attachments" :key="attachment" :href="attachment" target="_blank" rel="noopener" class="block">
                    <img :src="attachment" alt="Receipt attachment" class="h-16 w-20 rounded-lg border border-gray-200 object-cover transition hover:opacity-80" />
                  </a>
                </div>
              </div>
            </div>
          </div>
          <p v-else class="rounded-xl border border-dashed border-gray-200 px-4 py-8 text-center text-sm text-gray-500">No timeline events available.</p>
        </TabPanel>
      </TabView>
    </div>

    <Dialog v-model:visible="supplierRatingVisible" modal :header="receipt?.supplier_evaluation ? 'Update Supplier Rating' : 'Rate Supplier Performance'" class="w-full max-w-2xl">
      <div class="space-y-5">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
          <p class="font-semibold text-slate-900">{{ receipt?.supplier_name || 'Supplier' }}</p>
          <p class="mt-1 text-sm text-slate-500">Based on {{ receipt?.grn_number }} for {{ receipt?.po_number }}</p>
        </div>

        <div v-for="criterion in ratingCriteria" :key="criterion.field" class="flex flex-col gap-2 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="font-medium text-slate-900">{{ criterion.label }}</p>
            <p class="text-xs text-slate-500">{{ criterion.description }}</p>
          </div>
          <Rating v-model="ratingForm[criterion.field]" :cancel="false" />
        </div>

        <div>
          <label class="mb-2 block text-sm font-medium text-slate-700">Remarks (optional)</label>
          <Textarea v-model="ratingForm.remarks" rows="4" class="w-full" placeholder="Add observations about this delivery or the received items" />
        </div>

        <div class="flex items-center justify-between rounded-xl bg-blue-50 px-4 py-3">
          <span class="text-sm font-medium text-blue-900">Overall rating</span>
          <span class="text-xl font-bold text-blue-700">{{ overallSupplierRating.toFixed(2) }}/5</span>
        </div>
      </div>

      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="supplierRatingVisible = false" />
        <Button label="Save Rating" icon="pi pi-check" :loading="ratingSaving" @click="saveSupplierRating" />
      </template>
    </Dialog>
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'
import InputText from 'primevue/inputtext'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()

// State
const receipt = ref<any>(null)
const activeTab = ref(0)
const saving = ref(false)
const loading = ref(false)
const printing = ref(false)
const timeline = ref<any[]>([])
const supplierRatingVisible = ref(false)
const ratingSaving = ref(false)
const isProcurement = window.location.pathname.startsWith('/procurement/')
const resolution = ref<any>(null)
const resolutionDialog = ref(false)
const resolutionSaving = ref(false)
const resolutionForm = ref({ resolution_type: 'remaining_delivery', procurement_notes: '' })
const resolutionTypes = [
  { label: 'Request Remaining Delivery', value: 'remaining_delivery' },
  { label: 'Request Replacement', value: 'replacement' },
  { label: 'Accept Partial Delivery', value: 'partial_acceptance' },
  { label: 'Reject Delivery', value: 'reject_delivery' },
]
const ratingForm = ref<Record<string, any>>({
  quality_score: 5,
  quantity_accuracy_score: 5,
  delivery_timeliness_score: 5,
  packaging_condition_score: 5,
  remarks: '',
})

const ratingCriteria = [
  { field: 'quality_score', label: 'Item Quality', description: 'Condition and conformity of received items' },
  { field: 'quantity_accuracy_score', label: 'Quantity Accuracy', description: 'Accuracy against the quantities ordered' },
  { field: 'delivery_timeliness_score', label: 'Delivery Timeliness', description: 'Performance against the expected pickup date' },
  { field: 'packaging_condition_score', label: 'Packaging & Condition', description: 'Protection and condition upon arrival' },
]

const qualityOptions = ref([
  { label: 'Good', value: 'good' },
  { label: 'Fair', value: 'fair' },
  { label: 'Defective', value: 'defective' },
])

const displayQualityStatus = computed(() => {
  return receipt.value?.quality_status || receipt.value?.receipt_status || 'pending'
})

const canRateSupplier = computed(() => Boolean(
  receipt.value?.supplier_id && authStore.hasPermission('inventory.receiving.manage')
))

const overallSupplierRating = computed(() => {
  const fields = ratingCriteria.map(({ field }) => Number(ratingForm.value[field] || 0))
  return fields.reduce((sum, score) => sum + score, 0) / fields.length
})

// Computed
const isLate = computed(() => {
  if (!receipt.value?.receipt_date || !receipt.value?.expected_delivery_date) return false
  return new Date(receipt.value.receipt_date) > new Date(receipt.value.expected_delivery_date)
})

const daysVariance = computed(() => {
  if (!receipt.value?.receipt_date || !receipt.value?.expected_delivery_date) return 0
  const expected = new Date(receipt.value.expected_delivery_date).getTime()
  const received = new Date(receipt.value.receipt_date).getTime()
  return Math.floor((received - expected) / (1000 * 60 * 60 * 24))
})

// Methods
async function loadReceipt() {
  loading.value = true
  try {
    const response = await procurementService.getGoodsReceipt(Number(route.params.id))
    const payload = response?.data ?? response
    const raw = payload?.data ?? payload
    receipt.value = normalizeReceipt(raw)
    const resolutionResponse = await procurementService.getGoodsReceiptResolution(Number(route.params.id))
    resolution.value = resolutionResponse?.data ?? null
    if (receipt.value && resolution.value?.proof_path) {
      receipt.value.resolution_proof_url = attachmentUrl(resolution.value.proof_path)
    }
    buildTimeline()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load receipt',
      life: 3000,
    })
    receipt.value = null
  } finally {
    loading.value = false
  }
}

function buildTimeline() {
  timeline.value = [
    {
      label: 'PO Created',
      date: formatDate(receipt.value?.po_date),
      color: '#3b82f6',
    },
    {
      label: 'Expected Pickup',
      date: formatDate(receipt.value?.expected_delivery_date),
      color: '#f59e0b',
    },
    {
      label: 'Goods Received',
      date: formatDate(receipt.value?.receipt_date),
      color: isLate.value ? '#ef4444' : '#10b981',
      attachments: receipt.value?.resolution_proof_url ? [receipt.value.resolution_proof_url] : [],
    },
  ]

  if (receipt.value?.verified_date) {
    timeline.value.push({
      label: 'Quality Verified',
      date: formatDate(receipt.value.verified_date),
      color: '#10b981',
    })
  }
}

async function saveQualityCheck() {
  saving.value = true
  try {
    await procurementService.updateGoodsReceipt(receipt.value.id, {
      receipt_status: 'full',
    })
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Quality check saved',
      life: 3000,
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to save quality check',
      life: 3000,
    })
  } finally {
    saving.value = false
  }
}

function openSupplierRating() {
  const current = receipt.value?.supplier_evaluation
  ratingForm.value = {
    quality_score: Number(current?.quality_score ?? 5),
    quantity_accuracy_score: Number(current?.quantity_accuracy_score ?? 5),
    delivery_timeliness_score: Number(current?.delivery_timeliness_score ?? 5),
    packaging_condition_score: Number(current?.packaging_condition_score ?? 5),
    remarks: current?.remarks || '',
  }
  supplierRatingVisible.value = true
}

async function saveSupplierRating() {
  if (!receipt.value?.id || ratingSaving.value) return
  ratingSaving.value = true
  try {
    const response = await procurementService.saveSupplierEvaluation(receipt.value.id, ratingForm.value as any)
    receipt.value.supplier_evaluation = response?.data ?? response
    supplierRatingVisible.value = false
    toast.add({
      severity: 'success',
      summary: 'Rating saved',
      detail: 'The supplier performance record is now available in Procurement.',
      life: 3500,
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Unable to save rating',
      detail: error?.response?.data?.message || 'Please check the ratings and try again.',
      life: 4000,
    })
  } finally {
    ratingSaving.value = false
  }
}

function statusSeverity(status: string): string {
  if (status === 'full') return 'success'
  if (status === 'partial') return 'warning'
  if (status === 'damaged' || status === 'rejected') return 'danger'
  return 'secondary'
}

function formatStatus(status: string | null | undefined): string {
  return String(status || 'Pending')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase())
}

function statusDot(status: string): string {
  if (status === 'full') return 'bg-emerald-500'
  if (status === 'partial') return 'bg-amber-500'
  if (status === 'damaged' || status === 'rejected') return 'bg-red-500'
  return 'bg-gray-400'
}

function statusText(status: string): string {
  if (status === 'full') return 'text-emerald-600'
  if (status === 'partial') return 'text-amber-600'
  if (status === 'damaged' || status === 'rejected') return 'text-red-600'
  return 'text-gray-600'
}

function qualityDot(status: string): string {
  if (status === 'good') return 'bg-emerald-500'
  if (status === 'fair' || status === 'pending') return 'bg-amber-500'
  if (status === 'defective' || status === 'poor') return 'bg-red-500'
  return 'bg-gray-400'
}

function qualityText(status: string): string {
  if (status === 'good') return 'text-emerald-600'
  if (status === 'fair' || status === 'pending') return 'text-amber-600'
  if (status === 'defective' || status === 'poor') return 'text-red-600'
  return 'text-gray-600'
}

function qualitySeverity(status: string): string {
  if (status === 'good') return 'success'
  if (status === 'fair' || status === 'pending') return 'warning'
  if (status === 'defective' || status === 'poor') return 'danger'
  return 'secondary'
}

function formatDate(date: string): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

function attachmentUrl(path: string | null | undefined): string | null {
  if (!path) return null
  if (String(path).startsWith('http')) return String(path)
  return `/storage/${String(path).replace(/^\/+/, '')}`
}

function normalizeReceipt(raw: any) {
  if (!raw) return null
  const po = raw.purchase_order || {}
  const supplier = po.supplier || {}
  const items = Array.isArray(raw.items)
    ? raw.items.map((item: any) => {
        const poItem = item.purchase_order_item || {}
        const product = item.product || {}
        return {
          id: item.id,
          product_id: item.product_id,
          po_line_number: item.purchase_order_item_id || poItem.id,
          description: product.product_name || 'Unknown Product',
          po_quantity: Number(poItem.quantity_ordered ?? item.quantity_expected ?? 0),
          received_quantity: Number(item.quantity_received ?? 0),
          damaged_quantity: Number(item.quantity_damaged ?? 0),
          unit: product.unit_of_measurement || product.unit || '',
          quality_status: item.condition || 'pending',
          defect_notes: item.notes || '',
        }
      })
    : []

  return {
    ...raw,
    receipt_date: raw.receipt_date,
    receipt_status: raw.receipt_status,
    po_id: po.id,
    po_number: po.po_number,
    po_date: po.order_date,
    expected_delivery_date: po.expected_delivery_date,
    purchase_requisition_id: po.purchase_requisition?.id || po.purchase_requisition_id || null,
    purchase_requisition_number: po.purchase_requisition?.pr_number || null,
    resolution_proof_url: attachmentUrl(raw.resolution?.proof_path || raw.resolution_proof_path),
    supplier_id: po.supplier_id,
    supplier_name: supplier.supplier_name,
    contact_person: supplier.contact_person,
    email: supplier.email,
    received_by: raw.received_by,
    verified_by: raw.verified_by,
    items,
  }
}

function personName(person: any) {
  if (!person) return ''
  const first = person.fname || person.first_name || ''
  const last = person.lname || person.last_name || ''
  return `${first} ${last}`.trim()
}

function goBack() {
  router.push({ name: isProcurement ? 'procurement.goods-receipts' : 'inventory.goods-receipts' })
}

const deficientItems = computed(() => (receipt.value?.items || [])
  .map((item: any) => ({ ...item, quantity_due: Math.max(0, item.po_quantity - item.received_quantity) + item.damaged_quantity }))
  .filter((item: any) => item.quantity_due > 0))

function openResolutionDialog() {
  resolutionDialog.value = true
}

async function submitResolution() {
  if (!receipt.value?.id || deficientItems.value.length === 0) return
  resolutionSaving.value = true
  try {
    const response = await procurementService.flagGoodsReceiptResolution(receipt.value.id, {
      ...resolutionForm.value,
      items: deficientItems.value.map((item: any) => ({ goods_receipt_item_id: item.id, product_id: item.product_id, quantity_due: item.quantity_due })),
    })
    resolution.value = response.data
    resolutionDialog.value = false
    toast.add({ severity: 'success', summary: 'Resolution Sent', detail: 'The supplier can now respond from the PO details.', life: 3000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to Flag', detail: error.response?.data?.message || 'Failed to create resolution', life: 4000 })
  } finally {
    resolutionSaving.value = false
  }
}

function formatResolutionStatus(value: string) {
  return String(value || '').replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase())
}

function resolutionSeverity(status: string) {
  if (status === 'resolved') return 'success'
  if (status === 'rejected') return 'danger'
  if (status === 'delivery_submitted') return 'info'
  return 'warn'
}

async function printReceipt() {
  if (!receipt.value?.id || printing.value) return
  printing.value = true
  try {
    const response = await procurementService.generateGRPdf(receipt.value.id)
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = URL.createObjectURL(blob)
    window.open(url, '_blank')
  } catch (error) {
    console.error('Failed to print goods receipt', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Unable to open PDF',
      life: 3000,
    })
  } finally {
    printing.value = false
  }
}

onMounted(() => {
  loadReceipt()
})
</script>
