<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <button 
          @click="goBack" 
          class="w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors"
        >
          <i class="pi pi-chevron-left text-gray-600 text-lg"></i>
        </button>
        <div>
          <h1 class="text-3xl font-semibold text-gray-900 tracking-tight">{{ invoice?.invoice_number }}</h1>
          <p class="text-gray-500 text-sm mt-1">{{ invoice?.supplier_name }}</p>
        </div>
      </div>
      <div v-if="invoice?.status === 'draft'" class="flex gap-2">
        <button 
          @click="editInvoice"
          class="px-5 py-2.5 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2 shadow-sm"
        >
          <i class="pi pi-pencil text-sm"></i>
          <span>Edit</span>
        </button>
      </div>
    </div>

    <!-- iOS-style Status Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
          <span class="text-gray-500 text-sm font-medium">Status</span>
          <div :class="getStatusDot(invoice?.status)" class="w-2 h-2 rounded-full"></div>
        </div>
        <span :class="getStatusClass(invoice?.status)" class="text-base font-semibold">
          {{ formatStatus(invoice?.status) }}
        </span>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
          <span class="text-gray-500 text-sm font-medium">3-Way Match</span>
          <div :class="getMatchDot(invoice?.match_status)" class="w-2 h-2 rounded-full"></div>
        </div>
        <span :class="getMatchClass(invoice?.match_status)" class="text-base font-semibold">
          {{ formatStatus(invoice?.match_status) }}
        </span>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-3">
          <span class="text-gray-500 text-sm font-medium">Payment</span>
          <div :class="getPaymentDot(invoice?.payment_status)" class="w-2 h-2 rounded-full"></div>
        </div>
        <span :class="getPaymentClass(invoice?.payment_status)" class="text-base font-semibold">
          {{ formatPaymentStatus(invoice?.payment_status) }}
        </span>
      </div>

      <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl p-5 shadow-lg">
        <span class="text-gray-400 text-sm font-medium block mb-3">Total Amount</span>
        <span class="text-2xl font-bold text-white tracking-tight">₱{{ formatNumber(invoice?.total_amount) }}</span>
      </div>
    </div>

    <!-- iOS-style Tabs -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="flex border-b border-gray-100 bg-gray-50/50 px-4">
        <button
          v-for="(tab, index) in tabs"
          :key="index"
          @click="activeTab = index"
          class="px-5 py-4 text-sm font-medium transition-colors relative"
          :class="activeTab === index ? 'text-blue-500' : 'text-gray-500 hover:text-gray-700'"
        >
          <span class="flex items-center gap-2">
            <i :class="tab.icon" class="text-base"></i>
            <span>{{ tab.label }}</span>
          </span>
          <div 
            v-if="activeTab === index" 
            class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-500 rounded-full"
          ></div>
        </button>
      </div>

      <div class="p-6">
        <!-- Tab 1: Details -->
        <div v-if="activeTab === 0" class="space-y-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Invoice Details -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Invoice Details</h3>
              <div class="bg-gray-50/50 rounded-xl p-5 space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">Invoice Number</span>
                  <span class="font-medium text-gray-900">{{ invoice?.invoice_number }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">Invoice Date</span>
                  <span class="font-medium text-gray-900">{{ formatDate(invoice?.invoice_date) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">Due Date</span>
                  <span class="font-medium text-gray-900">{{ formatDate(invoice?.due_date) }}</span>
                </div>
                <div class="flex justify-between py-2">
                  <span class="text-gray-500">Payment Terms</span>
                  <span class="font-medium text-gray-900">{{ invoice?.payment_terms || 'Net 30' }}</span>
                </div>
              </div>
            </div>

            <!-- Supplier Details -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Supplier Details</h3>
              <div class="bg-gray-50/50 rounded-xl p-5 space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">Supplier Name</span>
                  <RouterLink
                    :to="`/procurement/suppliers/${invoice?.supplier_id}`"
                    class="font-medium text-blue-500 hover:text-blue-600"
                  >
                    {{ invoice?.supplier_name }}
                  </RouterLink>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">Supplier Code</span>
                  <span class="font-medium text-gray-900">{{ invoice?.supplier_code }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">Contact Person</span>
                  <span class="font-medium text-gray-900">{{ invoice?.contact_person }}</span>
                </div>
                <div class="flex justify-between py-2">
                  <span class="text-gray-500">Email</span>
                  <a :href="`mailto:${invoice?.email}`" class="font-medium text-blue-500 hover:text-blue-600">
                    {{ invoice?.email }}
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Line Items -->
          <div class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Line Items</h3>
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
              <table class="w-full text-sm">
                <thead class="bg-gray-50/80 text-gray-500 text-xs">
                  <tr>
                    <th class="px-5 py-4 text-left font-medium">Description</th>
                    <th class="px-5 py-4 text-right font-medium">Qty</th>
                    <th class="px-5 py-4 text-right font-medium">Unit Price</th>
                    <th class="px-5 py-4 text-right font-medium">Line Total</th>
                    <th class="px-5 py-4 text-right font-medium">Tax Rate</th>
                    <th class="px-5 py-4 text-right font-medium">Tax Amount</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="(item, idx) in invoice?.items" :key="idx" class="hover:bg-gray-50/50">
                    <td class="px-5 py-4 text-gray-900">{{ item.description }}</td>
                    <td class="px-5 py-4 text-right text-gray-900">{{ item.quantity }}</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber(item.unit_price) }}</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber(item.quantity * item.unit_price) }}</td>
                    <td class="px-5 py-4 text-right text-gray-900">{{ item.tax_rate }}%</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber((item.quantity * item.unit_price * item.tax_rate) / 100) }}</td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50/80 font-medium">
                  <tr>
                    <td colspan="5" class="px-5 py-4 text-right text-gray-600">Subtotal</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber(invoice?.net_amount) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5" class="px-5 py-4 text-right text-gray-600">Tax Total</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber(invoice?.tax_amount) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5" class="px-5 py-4 text-right text-gray-900 font-semibold">Total</td>
                    <td class="px-5 py-4 text-right text-blue-600 font-semibold">₱{{ formatNumber(invoice?.total_amount) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>

        <!-- Tab 2: 3-Way Match -->
        <div v-if="activeTab === 1" class="space-y-6">
          <!-- Match Status -->
          <div class="bg-gray-50/50 rounded-xl p-6">
            <div class="flex items-center gap-4">
              <div :class="getMatchIconClass(invoice?.match_status)" class="w-12 h-12 rounded-full flex items-center justify-center">
                <i :class="getMatchIcon(invoice?.match_status)" class="text-2xl"></i>
              </div>
              <div>
                <h3 class="font-semibold text-gray-900">{{ getMatchTitle(invoice?.match_status) }}</h3>
                <p class="text-sm text-gray-500 mt-1">{{ invoice?.match_result?.message || 'All documents match successfully' }}</p>
              </div>
            </div>

            <div v-if="invoice?.match_result?.issues?.length" class="mt-4 bg-red-50 rounded-lg p-4">
              <p class="font-medium text-red-800 text-sm mb-2">Issues Found:</p>
              <ul class="space-y-1">
                <li v-for="(issue, idx) in invoice.match_result.issues" :key="idx" class="text-sm text-red-700 flex items-start gap-2">
                  <i class="pi pi-exclamation-circle text-red-500 text-xs mt-0.5"></i>
                  <span>{{ issue }}</span>
                </li>
              </ul>
            </div>

            <div v-if="isFinanceRoute && invoice?.match_status !== 'matched'" class="mt-4">
              <button
                @click="runMatch"
                :disabled="matching"
                class="px-4 py-2.5 bg-gray-900 hover:bg-gray-800 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2"
              >
                <i class="pi pi-sync"></i>
                <span>{{ matching ? 'Matching...' : 'Run 3-Way Match' }}</span>
              </button>
            </div>
          </div>

          <!-- Comparison Cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-gray-100 p-5">
              <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                  <i class="pi pi-file-pdf text-green-600 text-sm"></i>
                </div>
                <span class="font-medium text-gray-900">Purchase Order</span>
              </div>
              <div class="space-y-3">
                <p class="text-sm"><span class="text-gray-500">PO Number:</span> <span class="font-medium text-gray-900">{{ invoice?.po_number }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Quantity:</span> <span class="font-medium text-gray-900">{{ invoice?.po_quantity }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Amount:</span> <span class="font-medium text-gray-900">₱{{ formatNumber(invoice?.po_amount) }}</span></p>
                <p class="text-xs text-gray-400">{{ formatDate(invoice?.po_date) }}</p>
              </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-5">
              <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                  <i class="pi pi-box text-purple-600 text-sm"></i>
                </div>
                <span class="font-medium text-gray-900">Goods Receipt</span>
              </div>
              <div class="space-y-3">
                <p class="text-sm"><span class="text-gray-500">GRN Number:</span> <span class="font-medium text-gray-900">{{ invoice?.grn_number || '—' }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Quantity:</span> <span class="font-medium text-gray-900">{{ invoice?.grn_quantity || 0 }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Status:</span> <span class="font-medium text-gray-900">{{ invoice?.grn_status || 'Pending' }}</span></p>
                <p class="text-xs text-gray-400">{{ formatDate(invoice?.grn_date) }}</p>
              </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 p-5">
              <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <i class="pi pi-file text-blue-600 text-sm"></i>
                </div>
                <span class="font-medium text-gray-900">Invoice</span>
              </div>
              <div class="space-y-3">
                <p class="text-sm"><span class="text-gray-500">Invoice #:</span> <span class="font-medium text-gray-900">{{ invoice?.invoice_number }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Quantity:</span> <span class="font-medium text-gray-900">{{ invoice?.invoice_quantity }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Amount:</span> <span class="font-medium text-gray-900">₱{{ formatNumber(invoice?.total_amount) }}</span></p>
                <p class="text-xs text-gray-400">{{ formatDate(invoice?.invoice_date) }}</p>
              </div>
            </div>
          </div>

          <!-- Variance Analysis -->
          <div v-if="invoice?.match_result?.variance_analysis" class="bg-white rounded-xl border border-gray-100 p-5">
            <h4 class="font-medium text-gray-900 mb-4">Variance Analysis</h4>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <p class="text-xs text-gray-500 mb-1">Quantity Variance</p>
                <p class="text-lg font-semibold" :class="invoice.match_result.variance_analysis.qty_variance !== 0 ? 'text-red-600' : 'text-green-600'">
                  {{ invoice.match_result.variance_analysis.qty_variance > 0 ? '+' : '' }}{{ invoice.match_result.variance_analysis.qty_variance }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Amount Variance</p>
                <p class="text-lg font-semibold" :class="Math.abs(invoice.match_result.variance_analysis.amt_variance) > 0 ? 'text-red-600' : 'text-green-600'">
                  ₱{{ formatNumber(invoice.match_result.variance_analysis.amt_variance) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Variance %</p>
                <p class="text-lg font-semibold text-gray-900">
                  {{ ((invoice.match_result.variance_analysis.amt_variance / invoice.total_amount) * 100).toFixed(2) }}%
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 3: Approval & Payment -->
        <div v-if="activeTab === 2" class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
              <i class="pi pi-check-circle text-blue-500"></i>
              Approval Status
            </h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-gray-500">Current Status</span>
                <span :class="getStatusClass(invoice?.status)" class="font-medium">{{ formatStatus(invoice?.status) }}</span>
              </div>

              <div v-if="invoice?.status === 'pending_approval'" class="bg-blue-50 rounded-lg p-4">
                <p class="text-sm text-blue-800 mb-3">This invoice needs your approval before payment can be processed.</p>
                <button
                  @click="approveInvoice"
                  :disabled="approving"
                  class="w-full px-4 py-2.5 bg-blue-500 hover:bg-blue-600 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-colors flex items-center justify-center gap-2"
                >
                  <i class="pi pi-check"></i>
                  <span>{{ approving ? 'Approving...' : 'Approve Invoice' }}</span>
                </button>
              </div>

              <div v-if="invoice?.approvals?.length" class="space-y-3">
                <p class="text-sm font-medium text-gray-700">Approval History</p>
                <div v-for="approval in invoice.approvals" :key="approval.id" class="flex items-start gap-3 text-sm">
                  <i class="pi pi-check-circle text-green-500 text-xs mt-1"></i>
                  <div>
                    <p class="font-medium text-gray-900">{{ approval.approved_by }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(approval.approved_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-xl border border-gray-100 p-6">
            <h3 class="font-medium text-gray-900 mb-4 flex items-center gap-2">
              <i class="pi pi-credit-card text-green-500"></i>
              Payment Information
            </h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-gray-500">Payment Status</span>
                <span :class="getPaymentClass(invoice?.payment_status)" class="font-medium">{{ formatPaymentStatus(invoice?.payment_status) }}</span>
              </div>

              <div v-if="!isFinanceRoute && invoice?.payment_status === 'pending'" class="bg-gray-50 rounded-lg p-4">
                <label class="text-sm text-gray-700 block mb-2">Schedule Payment Date</label>
                <input
                  type="date"
                  v-model="paymentDate"
                  class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm mb-3"
                />
                <button
                  @click="schedulePayment"
                  :disabled="scheduling"
                  class="w-full px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl text-sm transition-colors flex items-center justify-center gap-2"
                >
                  <i class="pi pi-calendar"></i>
                  <span>{{ scheduling ? 'Scheduling...' : 'Schedule Payment' }}</span>
                </button>
              </div>

              <div v-if="isFinanceRoute && invoice?.status === 'approved' && invoice?.payment_status === 'pending'" class="bg-gray-50 rounded-lg p-4 space-y-3">
                <div>
                  <label class="text-sm text-gray-700 block mb-2">Payment Method</label>
                  <select v-model="paymentMethod" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="check">Check</option>
                    <option value="credit_card">Credit Card</option>
                  </select>
                </div>
                <div>
                  <label class="text-sm text-gray-700 block mb-2">Payment Amount</label>
                  <input
                    type="number"
                    min="0"
                    step="0.01"
                    v-model.number="paymentAmount"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm"
                  />
                </div>
                <button
                  @click="markPaid"
                  :disabled="markingPaid"
                  class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-colors flex items-center justify-center gap-2"
                >
                  <i class="pi pi-wallet"></i>
                  <span>{{ markingPaid ? 'Processing...' : 'Mark as Paid' }}</span>
                </button>
              </div>

              <div v-if="invoice?.payment_status === 'scheduled'" class="bg-green-50 rounded-lg p-4">
                <p class="text-sm text-green-800">
                  Payment scheduled for <span class="font-medium">{{ formatDate(invoice?.scheduled_payment_date) }}</span>
                </p>
              </div>

              <div v-if="invoice?.payment_status === 'paid'" class="bg-green-50 rounded-lg p-4">
                <p class="text-sm text-green-800">
                  Paid on <span class="font-medium">{{ formatDate(invoice?.payment_date) }}</span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 4: Timeline -->
        <div v-if="activeTab === 3" class="space-y-4">
          <div class="relative">
            <div v-for="(event, idx) in timeline" :key="idx" class="flex gap-4 pb-8 last:pb-0 relative">
              <!-- Timeline line -->
              <div v-if="idx < timeline.length - 1" class="absolute left-5 top-8 bottom-0 w-0.5 bg-gray-200"></div>
              
              <!-- Timeline dot -->
              <div class="relative z-10">
                <div :style="{ backgroundColor: event.color }" class="w-10 h-10 rounded-full flex items-center justify-center text-white">
                  <i :class="event.icon" class="text-sm"></i>
                </div>
              </div>
              
              <!-- Content -->
              <div class="flex-1 pb-4">
                <p class="font-medium text-gray-900">{{ event.label }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ event.date }}</p>
                <p v-if="event.description" class="text-sm text-gray-600 mt-2">{{ event.description }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'
import financeService from '../../../../services/finance.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()

// State
const invoice = ref<any>(null)
const activeTab = ref(0)
const approving = ref(false)
const scheduling = ref(false)
const matching = ref(false)
const markingPaid = ref(false)
const loading = ref(false)
const paymentDate = ref('')
const paymentMethod = ref('bank_transfer')
const paymentAmount = ref(0)
const isFinanceRoute = computed(() => String(route.name || '').startsWith('finance.'))

const tabs = [
  { label: 'Details', icon: 'pi pi-file' },
  { label: '3-Way Match', icon: 'pi pi-check-circle' },
  { label: 'Approval & Payment', icon: 'pi pi-credit-card' },
  { label: 'Timeline', icon: 'pi pi-clock' },
]

const timeline = computed(() => {
  const events = [
    {
      label: 'Invoice Created',
      date: formatDate(invoice.value?.invoice_date),
      color: '#3B82F6',
      icon: 'pi pi-plus',
      description: `Invoice ${invoice.value?.invoice_number} was created`,
    },
    {
      label: 'PO Reference',
      date: formatDate(invoice.value?.po_date),
      color: '#10B981',
      icon: 'pi pi-file-pdf',
      description: `Linked to PO ${invoice.value?.po_number}`,
    },
  ]

  if (invoice.value?.grn_date) {
    events.push({
      label: 'Goods Received',
      date: formatDate(invoice.value?.grn_date),
      color: '#8B5CF6',
      icon: 'pi pi-box',
      description: `GRN ${invoice.value?.grn_number} received`,
    })
  }

  if (invoice.value?.match_date) {
    events.push({
      label: '3-Way Match Completed',
      date: formatDate(invoice.value?.match_date),
      color: invoice.value?.match_status === 'matched' ? '#10B981' : '#EF4444',
      icon: invoice.value?.match_status === 'matched' ? 'pi pi-check-circle' : 'pi pi-exclamation-circle',
      description: `Match status: ${invoice.value?.match_status}`,
    })
  }

  if (invoice.value?.approved_at) {
    events.push({
      label: 'Invoice Approved',
      date: formatDate(invoice.value?.approved_at),
      color: '#10B981',
      icon: 'pi pi-check',
      description: 'Invoice has been approved',
    })
  }

  if (invoice.value?.payment_date) {
    events.push({
      label: 'Payment Completed',
      date: formatDate(invoice.value?.payment_date),
      color: '#10B981',
      icon: 'pi pi-credit-card',
      description: `Payment of ₱${formatNumber(invoice.value?.total_amount)} completed`,
    })
  }

  return events
})

// Methods
async function loadInvoice() {
  loading.value = true
  try {
    const response = isFinanceRoute.value
      ? await financeService.getInvoice(Number(route.params.id))
      : await procurementService.getInvoice(Number(route.params.id))
    
    const raw = response.data?.data || response.data
    const supplier = raw?.supplier || {}
    const po = raw?.purchase_order || {}
    const goodsReceipt = raw?.goods_receipt || null
    
    const normalizedItems = Array.isArray(raw?.items)
      ? raw.items.map((item: any) => ({
          ...item,
          quantity: item.quantity_invoiced ?? item.quantity ?? 0,
          unit_price: item.unit_price ?? 0,
        }))
      : []

    const poQuantity = Array.isArray(po?.items)
      ? po.items.reduce((sum: number, item: any) => sum + Number(item.quantity_ordered || 0), 0)
      : 0

    const invoiceQuantity = normalizedItems.reduce((sum: number, item: any) => sum + Number(item.quantity || 0), 0)

    let matchIssues: string[] = []
    if (raw?.match_notes) {
      try {
        const parsed = JSON.parse(raw.match_notes)
        matchIssues = Array.isArray(parsed) ? parsed : []
      } catch {
        matchIssues = []
      }
    }

    invoice.value = {
      ...raw,
      supplier_name: supplier.supplier_name,
      supplier_code: supplier.supplier_code,
      contact_person: supplier.contact_person,
      email: supplier.email,
      payment_terms: po.payment_terms,
      total_amount: Number(raw?.net_amount ?? raw?.invoice_amount ?? 0),
      items: normalizedItems,
      po_number: po.po_number,
      po_quantity: poQuantity,
      po_amount: Number(po?.total_amount ?? po?.subtotal ?? 0),
      po_date: po?.order_date,
      grn_number: goodsReceipt?.grn_number,
      grn_quantity: goodsReceipt?.total_received,
      grn_status: goodsReceipt?.receipt_status,
      grn_date: goodsReceipt?.received_at,
      invoice_quantity: invoiceQuantity,
      match_result: matchIssues.length ? { issues: matchIssues } : raw?.match_result,
    }
    paymentAmount.value = invoice.value?.total_amount || 0
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

async function approveInvoice() {
  approving.value = true
  try {
    if (isFinanceRoute.value) {
      await financeService.approveInvoice(invoice.value.id)
    } else {
      await procurementService.approveInvoice(invoice.value.id)
    }
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Invoice approved',
      life: 3000,
    })
    await loadInvoice()
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
  if (!paymentDate.value) {
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
    if (!isFinanceRoute.value) {
      await procurementService.scheduleInvoicePayment(invoice.value.id, {
        payment_date: paymentDate.value,
      })
    }
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Payment scheduled',
      life: 3000,
    })
    await loadInvoice()
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

async function runMatch() {
  if (!invoice.value?.id) return
  matching.value = true
  try {
    if (isFinanceRoute.value) {
      await financeService.matchInvoice(invoice.value.id)
    } else {
      await procurementService.performInvoiceMatch(invoice.value.id)
    }
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: '3-way match completed',
      life: 3000,
    })
    await loadInvoice()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to run 3-way match',
      life: 3000,
    })
  } finally {
    matching.value = false
  }
}

async function markPaid() {
  if (!invoice.value?.id) return
  if (!paymentAmount.value || paymentAmount.value <= 0) {
    toast.add({
      severity: 'warn',
      summary: 'Required',
      detail: 'Please enter a valid payment amount',
      life: 3000,
    })
    return
  }

  markingPaid.value = true
  try {
    await financeService.markInvoicePaid(invoice.value.id, {
      payment_method: paymentMethod.value,
      payment_amount: Number(paymentAmount.value),
    })
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Invoice marked as paid',
      life: 3000,
    })
    await loadInvoice()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to mark invoice as paid',
      life: 3000,
    })
  } finally {
    markingPaid.value = false
  }
}

// Helper functions
function formatStatus(status: string): string {
  if (!status) return '-'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

function formatPaymentStatus(status: string): string {
  const map: Record<string, string> = {
    pending: 'Pending',
    scheduled: 'Scheduled',
    paid: 'Paid',
    overdue: 'Overdue',
  }
  return map[status] || status || '-'
}

function getStatusClass(status: string): string {
  const map: Record<string, string> = {
    draft: 'text-gray-500',
    pending_approval: 'text-orange-500',
    approved: 'text-green-600',
    rejected: 'text-red-500',
    paid: 'text-green-600',
  }
  return map[status] || 'text-gray-900'
}

function getMatchClass(status: string): string {
  const map: Record<string, string> = {
    pending: 'text-orange-500',
    matched: 'text-green-600',
    exception: 'text-red-500',
  }
  return map[status] || 'text-gray-900'
}

function getPaymentClass(status: string): string {
  const map: Record<string, string> = {
    pending: 'text-orange-500',
    scheduled: 'text-blue-500',
    paid: 'text-green-600',
    overdue: 'text-red-500',
  }
  return map[status] || 'text-gray-900'
}

function getStatusDot(status: string): string {
  const map: Record<string, string> = {
    draft: 'bg-gray-400',
    pending_approval: 'bg-orange-400',
    approved: 'bg-green-500',
    rejected: 'bg-red-500',
    paid: 'bg-green-500',
  }
  return map[status] || 'bg-gray-400'
}

function getMatchDot(status: string): string {
  const map: Record<string, string> = {
    pending: 'bg-orange-400',
    matched: 'bg-green-500',
    exception: 'bg-red-500',
  }
  return map[status] || 'bg-gray-400'
}

function getPaymentDot(status: string): string {
  const map: Record<string, string> = {
    pending: 'bg-orange-400',
    scheduled: 'bg-blue-500',
    paid: 'bg-green-500',
    overdue: 'bg-red-500',
  }
  return map[status] || 'bg-gray-400'
}

function getMatchIcon(status: string): string {
  const map: Record<string, string> = {
    pending: 'pi pi-clock',
    matched: 'pi pi-check-circle',
    exception: 'pi pi-exclamation-circle',
  }
  return map[status] || 'pi pi-question'
}

function getMatchIconClass(status: string): string {
  const map: Record<string, string> = {
    pending: 'bg-orange-100 text-orange-600',
    matched: 'bg-green-100 text-green-600',
    exception: 'bg-red-100 text-red-600',
  }
  return map[status] || 'bg-gray-100 text-gray-600'
}

function getMatchTitle(status: string): string {
  const map: Record<string, string> = {
    pending: 'Waiting for Match',
    matched: 'All Documents Match',
    exception: 'Matching Exceptions Found',
  }
  return map[status] || 'Match Status Unknown'
}

function formatDate(date: string): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0)
}

function goBack() {
  if (isFinanceRoute.value) {
    router.push({ name: 'finance.payables' })
    return
  }
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

<style scoped>
/* Smooth transitions */
* {
  transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

/* iOS-style shadows */
.shadow-sm {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
}

.shadow-lg {
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08), 0 4px 10px rgba(0, 0, 0, 0.02);
}

/* Custom scrollbar */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a1a1a1;
}
</style>
