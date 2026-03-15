<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">{{ invoice?.invoice_number }}</h1>
        <p class="text-gray-500 mt-1">Invoice from {{ invoice?.supplier_name }}</p>
      </div>
      <div class="flex gap-2">
        <Button
          label="Back"
          icon="pi pi-arrow-left"
          severity="secondary"
          @click="goBack"
        />
        <Button
          v-if="invoice?.status === 'draft'"
          label="Edit"
          icon="pi pi-pencil"
          @click="editInvoice"
        />
      </div>
    </div>

    <!-- Invoice Status -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <div>
            <p class="text-gray-500 text-sm">Status</p>
            <Badge :value="invoice?.status" :severity="statusSeverity(invoice?.status)" class="mt-2" />
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div>
            <p class="text-gray-500 text-sm">3-Way Match</p>
            <Badge :value="invoice?.match_status" :severity="matchStatusSeverity(invoice?.match_status)" class="mt-2" />
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div>
            <p class="text-gray-500 text-sm">Payment Status</p>
            <Badge :value="invoice?.payment_status" class="mt-2" />
          </div>
        </template>
      </Card>
      <Card>
        <template #content>
          <div>
            <p class="text-gray-500 text-sm">Total Amount</p>
            <p class="text-2xl font-bold text-green-600 mt-2">₱ {{ formatNumber(invoice?.total_amount) }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Main Content Tabs -->
    <TabView v-model:activeIndex="activeTab">
      <!-- Invoice Details -->
      <TabPanel header="Invoice Details" headerIcon="pi pi-file" value="0">
        <template #header>
          <i class="pi pi-file mr-2"></i>
          <span>Details</span>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Invoice Information -->
          <Card>
            <template #header>
              <div class="p-4 bg-blue-50 border-b">
                <h3 class="font-semibold text-gray-800">Invoice Information</h3>
              </div>
            </template>
            <template #content>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Invoice Number</span>
                  <span class="font-semibold">{{ invoice?.invoice_number }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Invoice Date</span>
                  <span class="font-semibold">{{ formatDate(invoice?.invoice_date) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Due Date</span>
                  <span class="font-semibold">{{ formatDate(invoice?.due_date) }}</span>
                </div>
                <Divider />
                <div class="flex justify-between">
                  <span class="text-gray-600">Net Amount</span>
                  <span class="font-semibold text-lg">₱ {{ formatNumber(invoice?.net_amount) }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Tax Amount</span>
                  <span class="font-semibold">₱ {{ formatNumber(invoice?.tax_amount) }}</span>
                </div>
                <div class="flex justify-between border-t pt-2">
                  <span class="text-gray-600 font-semibold">Total Amount</span>
                  <span class="font-bold text-lg text-green-600">₱ {{ formatNumber(invoice?.total_amount) }}</span>
                </div>
              </div>
            </template>
          </Card>

          <!-- Supplier Information -->
          <Card>
            <template #header>
              <div class="p-4 bg-blue-50 border-b">
                <h3 class="font-semibold text-gray-800">Supplier Information</h3>
              </div>
            </template>
            <template #content>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Supplier Name</span>
                  <RouterLink
                    :to="`/procurement/suppliers/${invoice?.supplier_id}`"
                    class="font-semibold text-blue-600 hover:underline"
                  >
                    {{ invoice?.supplier_name }}
                  </RouterLink>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Supplier Code</span>
                  <span class="font-semibold">{{ invoice?.supplier_code }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Contact Person</span>
                  <span class="font-semibold">{{ invoice?.contact_person }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Email</span>
                  <a :href="`mailto:${invoice?.email}`" class="text-blue-600 hover:underline">
                    {{ invoice?.email }}
                  </a>
                </div>
                <Divider />
                <div class="flex justify-between">
                  <span class="text-gray-600">Payment Terms</span>
                  <span class="font-semibold">{{ invoice?.payment_terms }}</span>
                </div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Line Items -->
        <Card class="mt-6">
          <template #header>
            <div class="p-4 bg-blue-50 border-b">
              <h3 class="font-semibold text-gray-800">Line Items</h3>
            </div>
          </template>
          <template #content>
            <DataTable :value="invoice?.items || []" class="p-datatable-sm" stripedRows>
              <Column field="description" header="Description" style="width: 30%" />
              <Column field="quantity" header="Qty" style="width: 10%">
                <template #body="{ data }">
                  {{ data.quantity }}
                </template>
              </Column>
              <Column header="Unit Price" style="width: 12%">
                <template #body="{ data }">
                  ₱ {{ formatNumber(data.unit_price) }}
                </template>
              </Column>
              <Column header="Line Amount" style="width: 15%">
                <template #body="{ data }">
                  ₱ {{ formatNumber(data.quantity * data.unit_price) }}
                </template>
              </Column>
              <Column header="Tax Rate" style="width: 12%">
                <template #body="{ data }">
                  {{ data.tax_rate }}%
                </template>
              </Column>
              <Column header="Tax Amount" style="width: 15%">
                <template #body="{ data }">
                  ₱ {{ formatNumber((data.quantity * data.unit_price * data.tax_rate) / 100) }}
                </template>
              </Column>

              <template #empty>
                <div class="text-center py-4 text-gray-500">
                  No line items
                </div>
              </template>
            </DataTable>
          </template>
        </Card>
      </TabPanel>

      <!-- 3-Way Matching -->
      <TabPanel header="3-Way Matching" headerIcon="pi pi-check-circle" value="1">
        <template #header>
          <i class="pi pi-check-circle mr-2"></i>
          <span>3-Way Match</span>
          <Badge
            :value="invoice?.match_status"
            :severity="matchStatusSeverity(invoice?.match_status)"
            class="ml-2"
          />
        </template>

        <div class="space-y-6">
          <!-- Match Status Summary -->
          <Card>
            <template #header>
              <div class="p-4 bg-blue-50 border-b">
                <h3 class="font-semibold text-gray-800">Match Status</h3>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <div class="flex items-center gap-3">
                  <i v-if="invoice?.match_status === 'matched'" class="pi pi-check-circle text-2xl text-green-600" />
                  <i v-else-if="invoice?.match_status === 'exception'" class="pi pi-exclamation-circle text-2xl text-red-600" />
                  <i v-else class="pi pi-clock text-2xl text-orange-600" />
                  <div>
                    <p class="font-semibold">
                      {{ invoice?.match_status === 'matched' ? 'Invoice Matched' :
                         invoice?.match_status === 'exception' ? 'Matching Exceptions Found' :
                         'Waiting for Match' }}
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                      {{ invoice?.match_result?.message }}
                    </p>
                  </div>
                </div>

                <!-- Exception Details -->
                <div v-if="invoice?.match_result?.issues && invoice.match_result.issues.length > 0" class="bg-red-50 border border-red-200 rounded p-4">
                  <p class="font-semibold text-red-800 mb-3">Issues Found:</p>
                  <ul class="space-y-2">
                    <li v-for="(issue, idx) in invoice.match_result.issues" :key="idx" class="flex gap-2 text-sm">
                      <i class="pi pi-times text-red-600 shrink-0" />
                      <span class="text-gray-700">{{ issue }}</span>
                    </li>
                  </ul>
                </div>
              </div>
            </template>
          </Card>

          <!-- Comparison -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- PO -->
            <Card>
              <template #header>
                <div class="p-4 bg-green-50 border-b">
                  <h3 class="font-semibold text-gray-800">Purchase Order</h3>
                </div>
              </template>
              <template #content>
                <div class="space-y-2 text-sm">
                  <p><span class="text-gray-600">PO Number:</span> {{ invoice?.po_number }}</p>
                  <p><span class="text-gray-600">Quantity:</span> {{ invoice?.po_quantity }}</p>
                  <p><span class="text-gray-600">Amount:</span> ₱ {{ formatNumber(invoice?.po_amount) }}</p>
                  <p class="text-xs text-gray-500 mt-2">{{ formatDate(invoice?.po_date) }}</p>
                </div>
              </template>
            </Card>

            <!-- GRN -->
            <Card>
              <template #header>
                <div class="p-4 bg-purple-50 border-b">
                  <h3 class="font-semibold text-gray-800">Goods Receipt</h3>
                </div>
              </template>
              <template #content>
                <div class="space-y-2 text-sm">
                  <p><span class="text-gray-600">GRN Number:</span> {{ invoice?.grn_number }}</p>
                  <p><span class="text-gray-600">Quantity:</span> {{ invoice?.grn_quantity }}</p>
                  <p><span class="text-gray-600">Status:</span> {{ invoice?.grn_status }}</p>
                  <p class="text-xs text-gray-500 mt-2">{{ formatDate(invoice?.grn_date) }}</p>
                </div>
              </template>
            </Card>

            <!-- Invoice -->
            <Card>
              <template #header>
                <div class="p-4 bg-blue-50 border-b">
                  <h3 class="font-semibold text-gray-800">Invoice</h3>
                </div>
              </template>
              <template #content>
                <div class="space-y-2 text-sm">
                  <p><span class="text-gray-600">Invoice #:</span> {{ invoice?.invoice_number }}</p>
                  <p><span class="text-gray-600">Quantity:</span> {{ invoice?.invoice_quantity }}</p>
                  <p><span class="text-gray-600">Amount:</span> ₱ {{ formatNumber(invoice?.total_amount) }}</p>
                  <p class="text-xs text-gray-500 mt-2">{{ formatDate(invoice?.invoice_date) }}</p>
                </div>
              </template>
            </Card>
          </div>

          <!-- Variance Analysis -->
          <Card v-if="invoice?.match_result?.variance_analysis">
            <template #header>
              <div class="p-4 bg-orange-50 border-b">
                <h3 class="font-semibold text-gray-800">Variance Analysis</h3>
              </div>
            </template>
            <template #content>
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Quantity Variance</span>
                  <span class="font-semibold">
                    {{ invoice.match_result.variance_analysis.qty_variance }}
                    <Badge
                      :value="Math.abs(invoice.match_result.variance_analysis.qty_variance) > 0 ? 'Mismatch' : 'OK'"
                      :severity="Math.abs(invoice.match_result.variance_analysis.qty_variance) > 0 ? 'danger' : 'success'"
                      class="ml-2"
                    />
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Amount Variance</span>
                  <span class="font-semibold">
                    ₱ {{ formatNumber(invoice.match_result.variance_analysis.amt_variance) }}
                    <Badge
                      :value="Math.abs(invoice.match_result.variance_analysis.amt_variance) > 0 ? 'Out of Tolerance' : 'Within Tolerance'"
                      :severity="Math.abs(invoice.match_result.variance_analysis.amt_variance) > (invoice.total_amount * 0.02) ? 'danger' : 'success'"
                      class="ml-2"
                    />
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Variance %</span>
                  <span class="font-semibold">{{ ((invoice.match_result.variance_analysis.amt_variance / invoice.total_amount) * 100).toFixed(2) }}%</span>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </TabPanel>

      <!-- Approval & Payment -->
      <TabPanel header="Approval & Payment" headerIcon="pi pi-money-bill" value="2">
        <template #header>
          <i class="pi pi-money-bill mr-2"></i>
          <span>Approval & Payment</span>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Approval -->
          <Card>
            <template #header>
              <div class="p-4 bg-blue-50 border-b">
                <h3 class="font-semibold text-gray-800">Approval Status</h3>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <div>
                  <p class="text-gray-600 text-sm mb-2">Status</p>
                  <Badge :value="invoice?.status" :severity="statusSeverity(invoice?.status)" />
                </div>

                <div v-if="invoice?.status === 'pending_approval'" class="bg-blue-50 p-4 rounded border border-blue-200">
                  <p class="text-sm text-blue-800 mb-3">This invoice is awaiting approval before payment can be scheduled.</p>
                  <Button
                    label="Approve Invoice"
                    icon="pi pi-check"
                    class="w-full"
                    @click="approveInvoice"
                    :loading="approving"
                  />
                </div>

                <div v-if="invoice?.approvals" class="space-y-2">
                  <p class="text-sm text-gray-600 mb-2">Approval Trail:</p>
                  <div v-for="approval in invoice.approvals" :key="approval.id" class="flex gap-3 text-sm">
                    <i class="pi pi-check text-green-600" />
                    <div>
                      <p class="font-semibold">{{ approval.approved_by }}</p>
                      <p class="text-gray-500 text-xs">{{ formatDate(approval.approved_at) }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </Card>

          <!-- Payment -->
          <Card>
            <template #header>
              <div class="p-4 bg-green-50 border-b">
                <h3 class="font-semibold text-gray-800">Payment Information</h3>
              </div>
            </template>
            <template #content>
              <div class="space-y-4">
                <div>
                  <p class="text-gray-600 text-sm mb-2">Payment Status</p>
                  <Badge :value="invoice?.payment_status" />
                </div>

                <div v-if="invoice?.payment_status === 'pending'">
                  <p class="text-gray-600 text-sm mb-2">Scheduled Payment Date</p>
                  <DatePicker
                    v-model="paymentData.payment_date"
                    dateFormat="yy-mm-dd"
                    class="w-full mb-3"
                  />
                  <Button
                    label="Schedule Payment"
                    icon="pi pi-calendar"
                    class="w-full"
                    @click="schedulePayment"
                    :loading="scheduling"
                  />
                </div>

                <div v-if="invoice?.payment_status === 'scheduled'" class="bg-green-50 p-3 rounded border border-green-200">
                  <p class="text-sm text-green-800">
                    Payment Scheduled for {{ formatDate(invoice?.scheduled_payment_date) }}
                  </p>
                </div>

                <div v-if="invoice?.payment_status === 'paid'" class="bg-green-50 p-3 rounded border border-green-200">
                  <p class="text-sm text-green-800 font-semibold">
                    Paid on {{ formatDate(invoice?.payment_date) }}
                  </p>
                </div>
              </div>
            </template>
          </Card>
        </div>
      </TabPanel>

      <!-- Timeline -->
      <TabPanel header="Timeline" headerIcon="pi pi-timeline" value="3">
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
import { onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()

// State
const invoice = ref<any>(null)
const activeTab = ref(0)
const approving = ref(false)
const scheduling = ref(false)
const loading = ref(false)

const paymentData = ref({
  payment_date: new Date(),
})

const timeline = ref<any[]>([])

// Methods
async function loadInvoice() {
  loading.value = true
  try {
    const response = await procurementService.getInvoice(Number(route.params.id))
    invoice.value = response.data?.data || response.data

    // Build timeline
    buildTimeline()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load invoice',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function buildTimeline() {
  timeline.value = [
    {
      label: 'Invoice Created',
      date: formatDate(invoice.value?.invoice_date),
      color: '#3b82f6',
    },
    {
      label: 'PO Reference',
      date: formatDate(invoice.value?.po_date),
      color: '#10b981',
    },
    {
      label: 'GRN Received',
      date: formatDate(invoice.value?.grn_date),
      color: '#a855f7',
    },
  ]

  if (invoice.value?.match_date) {
    timeline.value.push({
      label: '3-Way Match Performed',
      date: formatDate(invoice.value.match_date),
      color: invoice.value.match_status === 'matched' ? '#10b981' : '#ef4444',
    })
  }

  if (invoice.value?.approved_at) {
    timeline.value.push({
      label: 'Invoice Approved',
      date: formatDate(invoice.value.approved_at),
      color: '#10b981',
    })
  }

  if (invoice.value?.payment_date) {
    timeline.value.push({
      label: 'Payment Completed',
      date: formatDate(invoice.value.payment_date),
      color: '#10b981',
    })
  }
}

async function approveInvoice() {
  approving.value = true
  try {
    await procurementService.approveInvoice(invoice.value.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Invoice approved',
      life: 3000,
    })
    loadInvoice()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to approve invoice',
      life: 3000,
    })
  } finally {
    approving.value = false
  }
}

async function schedulePayment() {
  if (!paymentData.value.payment_date) {
    toast.add({
      severity: 'warn',
      summary: 'Required',
      detail: 'Please select a payment date',
      life: 3000,
    })
    return
  }

  scheduling.value = true
  try {
    let paymentDate: string
    if (paymentData.value.payment_date instanceof Date) {
      paymentDate = (paymentData.value.payment_date.toISOString().split('T')[0] ?? '')
    } else if (paymentData.value.payment_date) {
      paymentDate = String(paymentData.value.payment_date)
    } else {
      paymentDate = (new Date().toISOString().split('T')[0] ?? '2026-03-10')
    }
    await procurementService.scheduleInvoicePayment(invoice.value.id, {
      payment_date: paymentDate,
    })
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Payment scheduled',
      life: 3000,
    })
    loadInvoice()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to schedule payment',
      life: 3000,
    })
  } finally {
    scheduling.value = false
  }
}

function statusSeverity(status: string): string {
  if (status === 'approved' || status === 'paid') return 'success'
  if (status === 'pending_approval') return 'info'
  if (status === 'draft') return 'secondary'
  return 'danger'
}

function matchStatusSeverity(status: string): string {
  if (status === 'matched') return 'success'
  if (status === 'pending') return 'warning'
  return 'danger'
}

function formatDate(date: string): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH').format(value)
}

function goBack() {
  router.push({ name: 'procurement.invoices' })
}

function editInvoice() {
  router.push({
    name: 'procurement.invoices.edit',
    params: { id: invoice.value.id },
  })
}

onMounted(() => {
  loadInvoice()
})
</script>
