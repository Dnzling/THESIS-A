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
      <!-- Header -->
      <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:flex-row md:items-center md:justify-between">
        <div>
          <Button label="Back to receipts" text class="mb-2 px-0" @click="goBack" />
          <div>
            <h1 class="text-2xl font-bold text-gray-900 md:text-3xl">{{ receipt.grn_number }}</h1>
            <p class="mt-1 text-sm text-gray-500">Goods receipt for purchase order {{ receipt.po_number || '-' }}</p>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button
            v-if="canRateSupplier"
            :label="receipt.supplier_evaluation ? 'Update Supplier Rating' : 'Rate Supplier'"
            icon="pi pi-star"
            @click="openSupplierRating"
          />
          <Button
            label="Print PDF"
            severity="secondary"
            :loading="printing"
            @click="printReceipt"
          />
        </div>
      </div>

      <!-- Status Cards -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <Card class="border border-slate-200 shadow-sm">
          <template #content>
            <div>
              <p class="text-gray-500 text-sm">Status</p>
              <Badge :value="receipt.receipt_status" :severity="statusSeverity(receipt.receipt_status)" class="mt-2" />
            </div>
          </template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #content>
            <div>
              <p class="text-gray-500 text-sm">Quality Check</p>
              <Badge :value="displayQualityStatus" :severity="qualitySeverity(displayQualityStatus)" class="mt-2" />
            </div>
          </template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #content>
            <div>
              <p class="text-gray-500 text-sm">Total Items</p>
              <p class="text-3xl font-bold text-blue-600 mt-2">{{ receipt.items?.length || 0 }}</p>
            </div>
          </template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #content>
            <div>
              <p class="text-gray-500 text-sm">Received Date</p>
              <p class="text-xl font-bold text-gray-800 mt-2">{{ formatDate(receipt.receipt_date) }}</p>
            </div>
          </template>
        </Card>
        <Card class="border border-slate-200 shadow-sm">
          <template #content>
            <div>
              <p class="text-gray-500 text-sm">Supplier Rating</p>
              <p class="mt-2 text-xl font-bold text-gray-800">
                {{ receipt.supplier_evaluation ? `${Number(receipt.supplier_evaluation.overall_rating).toFixed(2)}/5` : 'Not rated' }}
              </p>
            </div>
          </template>
        </Card>
      </div>

      <!-- Main Tabs -->
      <TabView v-model:activeIndex="activeTab" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <!-- Receipt Details -->
        <TabPanel header="Details" value="0">
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
        <TabPanel header="Quality Check" value="1">
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
        <TabPanel header="Timeline" value="2">
          <template #header>
            <span>Timeline</span>
          </template>

          <Timeline :value="timeline" align="left" layout="vertical">
            <template #content="{ item }">
              <div class="flex gap-3">
                <div class="text-sm">
                  <p class="font-semibold">{{ item.label }}</p>
                  <p class="text-gray-500 text-xs mt-1">{{ item.date }}</p>
                </div>
              </div>
            </template>

            <template #marker="{ item }">
              <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: item.color }" />
            </template>
          </Timeline>
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
          po_line_number: item.purchase_order_item_id || poItem.id,
          description: product.product_name || 'Unknown Product',
          po_quantity: Number(poItem.quantity_ordered ?? item.quantity_expected ?? 0),
          received_quantity: Number(item.quantity_received ?? 0),
          unit: product.unit || '',
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
  router.push({ name: 'inventory.goods-receipts' })
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
