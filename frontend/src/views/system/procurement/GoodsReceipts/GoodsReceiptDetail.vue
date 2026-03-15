<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text rounded @click="goBack" />
        <div>
          <h1 class="text-3xl font-bold text-gray-800">{{ receipt?.grn_number }}</h1>
          <p class="text-gray-500 mt-1">Goods Receipt for {{ receipt?.po_number }}</p>
        </div>
      </div>
      <Button
        label="Back"
        icon="pi pi-arrow-left"
        severity="secondary"
        @click="goBack"
      />
    </div>

    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <div>
            <p class="text-gray-500 text-sm">Status</p>
            <Badge :value="receipt?.status" :severity="statusSeverity(receipt?.status)" class="mt-2" />
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div>
            <p class="text-gray-500 text-sm">Quality Check</p>
            <Badge :value="receipt?.quality_status" :severity="qualitySeverity(receipt?.quality_status)" class="mt-2" />
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div>
            <p class="text-gray-500 text-sm">Total Items</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ receipt?.items?.length || 0 }}</p>
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div>
            <p class="text-gray-500 text-sm">Received Date</p>
            <p class="text-xl font-bold text-gray-800 mt-2">{{ formatDate(receipt?.received_date) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Tabs -->
    <TabView v-model:activeIndex="activeTab">
      <!-- Receipt Details -->
      <TabPanel header="Details" headerIcon="pi pi-file" value="0">
        <template #header>
          <i class="pi pi-file mr-2"></i>
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
                <div class="flex justify-between">
                  <span class="text-gray-600">GRN Number</span>
                  <span class="font-semibold">{{ receipt?.grn_number }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Reference PO</span>
                  <RouterLink
                    :to="`/procurement/purchase-orders/${receipt?.po_id}`"
                    class="font-semibold text-blue-600 hover:underline"
                  >
                    {{ receipt?.po_number }}
                  </RouterLink>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Received Date</span>
                  <span class="font-semibold">{{ formatDate(receipt?.received_date) }}</span>
                </div>
                <Divider />
                <div class="flex justify-between">
                  <span class="text-gray-600">Expected Delivery</span>
                  <span class="font-semibold">{{ formatDate(receipt?.expected_delivery_date) }}</span>
                </div>
                <div class="flex justify-between">
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
                <div class="flex justify-between">
                  <span class="text-gray-600">Supplier</span>
                  <RouterLink
                    :to="`/procurement/suppliers/${receipt?.supplier_id}`"
                    class="font-semibold text-blue-600 hover:underline"
                  >
                    {{ receipt?.supplier_name }}
                  </RouterLink>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Contact</span>
                  <span class="font-semibold">{{ receipt?.contact_person }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Email</span>
                  <a :href="`mailto:${receipt?.email}`" class="text-blue-600 hover:underline">
                    {{ receipt?.email }}
                  </a>
                </div>
                <Divider />
                <div class="flex justify-between">
                  <span class="text-gray-600">Received By</span>
                  <span class="font-semibold">{{ receipt?.received_by }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Verified By</span>
                  <span class="font-semibold">{{ receipt?.verified_by || '-' }}</span>
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
            <DataTable :value="receipt?.items || []" class="p-datatable-sm" stripedRows>
              <Column field="po_line_number" header="Line" style="width: 8%">
                <template #body="{ data }">
                  #{{ data.po_line_number }}
                </template>
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
                      ✓ Match
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
        <Card v-if="receipt?.discrepancies && receipt.discrepancies.length > 0" class="mt-6">
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
      <TabPanel header="Quality Check" headerIcon="pi pi-search" value="1">
        <template #header>
          <i class="pi pi-search mr-2"></i>
          <span>Quality Check</span>
          <Badge :value="receipt?.quality_status" :severity="qualitySeverity(receipt?.quality_status)" class="ml-2" />
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
                <DataTable :value="receipt?.items || []" class="p-datatable-sm">
                  <Column field="description" header="Item" style="width: 40%" />
                  <Column header="Quality" style="width: 30%">
                    <template #body="{ data }">
                      <Dropdown
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
                  icon="pi pi-save"
                  @click="saveQualityCheck"
                  :loading="saving"
                />
              </div>
            </div>
          </template>
        </Card>
      </TabPanel>

      <!-- Timeline -->
      <TabPanel header="Timeline" headerIcon="pi pi-timeline" value="2">
        <template #header>
          <i class="pi pi-timeline mr-2"></i>
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

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()

// State
const receipt = ref<any>(null)
const activeTab = ref(0)
const saving = ref(false)
const loading = ref(false)
const timeline = ref<any[]>([])

const qualityOptions = ref([
  { label: 'Good', value: 'good' },
  { label: 'Fair', value: 'fair' },
  { label: 'Defective', value: 'defective' },
])

// Computed
const isLate = computed(() => {
  if (!receipt.value?.received_date || !receipt.value?.expected_delivery_date) return false
  return new Date(receipt.value.received_date) > new Date(receipt.value.expected_delivery_date)
})

const daysVariance = computed(() => {
  if (!receipt.value?.received_date || !receipt.value?.expected_delivery_date) return 0
  const expected = new Date(receipt.value.expected_delivery_date).getTime()
  const received = new Date(receipt.value.received_date).getTime()
  return Math.floor((received - expected) / (1000 * 60 * 60 * 24))
})

// Methods
async function loadReceipt() {
  loading.value = true
  try {
    const response = await procurementService.getGoodsReceipt(Number(route.params.id))
    receipt.value = response.data?.data || response.data
    buildTimeline()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load receipt',
      life: 3000,
    })
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
      label: 'Expected Delivery',
      date: formatDate(receipt.value?.expected_delivery_date),
      color: '#f59e0b',
    },
    {
      label: 'Goods Received',
      date: formatDate(receipt.value?.received_date),
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

function statusSeverity(status: string): string {
  if (status === 'received' || status === 'verified') return 'success'
  if (status === 'pending') return 'warning'
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

function goBack() {
  router.push({ name: 'procurement.goods-receipts' })
}

onMounted(() => {
  loadReceipt()
})
</script>
