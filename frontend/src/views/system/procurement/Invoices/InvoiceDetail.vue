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
          <p class="text-gray-500 text-sm mt-1">{{ invoice?.supplier?.supplier_name }}</p>
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
        <span class="text-2xl font-bold text-white tracking-tight">₱{{ formatNumber(invoice?.net_amount || invoice?.total_amount) }}</span>
      </div>
    </div>

    <!-- iOS-style Tabs -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      <div class="flex border-b border-gray-100 bg-gray-50/50 px-4 overflow-x-auto">
        <button
          v-for="(tab, index) in tabs"
          :key="index"
          @click="activeTab = index"
          class="px-5 py-4 text-sm font-medium transition-colors relative whitespace-nowrap"
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
                  <span class="font-medium text-gray-900">{{ formatPaymentTerms(invoice?.payment_terms) }}</span>
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
                    {{ invoice?.supplier?.supplier_name }}
                  </RouterLink>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">Contact Person</span>
                  <span class="font-medium text-gray-900">{{ invoice?.supplier?.contact_person || '-' }}</span>
                </div>
                <div class="flex justify-between py-2">
                  <span class="text-gray-500">Email</span>
                  <a :href="`mailto:${invoice?.supplier?.email}`" class="font-medium text-blue-500 hover:text-blue-600">
                    {{ invoice?.supplier?.email || '-' }}
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Purchase Order Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Purchase Order</h3>
              <div class="bg-gray-50/50 rounded-xl p-5 space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">PO Number</span>
                  <RouterLink
                    :to="`/procurement/purchase-orders/${invoice?.purchase_order_id}`"
                    class="font-medium text-blue-500 hover:text-blue-600"
                  >
                    {{ invoice?.purchase_order?.po_number }}
                  </RouterLink>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">Order Date</span>
                  <span class="font-medium text-gray-900">{{ formatDate(invoice?.purchase_order?.order_date) }}</span>
                </div>
                <div class="flex justify-between py-2">
                  <span class="text-gray-500">Total Amount</span>
                  <span class="font-medium text-green-600">₱{{ formatNumber(invoice?.purchase_order?.total_amount) }}</span>
                </div>
              </div>
            </div>

            <!-- Goods Receipt Info -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">Goods Receipt</h3>
              <div class="bg-gray-50/50 rounded-xl p-5 space-y-3">
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">GRN Number</span>
                  <RouterLink
                    v-if="invoice?.goods_receipt"
                    :to="`/procurement/goods-receipts/${invoice?.goods_receipt_id}`"
                    class="font-medium text-blue-500 hover:text-blue-600"
                  >
                    {{ invoice?.goods_receipt?.grn_number }}
                  </RouterLink>
                  <span v-else class="font-medium text-gray-400">—</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                  <span class="text-gray-500">Receipt Date</span>
                  <span class="font-medium text-gray-900">{{ formatDate(invoice?.goods_receipt?.receipt_date) || '-' }}</span>
                </div>
                <div class="flex justify-between py-2">
                  <span class="text-gray-500">Receipt Status</span>
                  <Tag 
                    :value="formatReceiptStatus(invoice?.goods_receipt?.receipt_status)" 
                    :severity="receiptStatusSeverity(invoice?.goods_receipt?.receipt_status)"
                    class="rounded-full text-xs px-3 py-1"
                  />
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
                    <th class="px-5 py-4 text-left font-medium">Product</th>
                    <th class="px-5 py-4 text-right font-medium">Qty</th>
                    <th class="px-5 py-4 text-right font-medium">Unit Price</th>
                    <th class="px-5 py-4 text-right font-medium">Line Total</th>
                    <th class="px-5 py-4 text-right font-medium">Tax Rate</th>
                    <th class="px-5 py-4 text-right font-medium">Tax Amount</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="(item, idx) in invoice?.items" :key="idx" class="hover:bg-gray-50/50">
                    <td class="px-5 py-4">
                      <div>
                        <p class="font-medium text-gray-900">{{ item.product?.product_name || 'Unknown Product' }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">SKU: {{ item.product?.sku || '-' }}</p>
                      </div>
                    </td>
                    <td class="px-5 py-4 text-right text-gray-900">{{ item.quantity_invoiced || item.quantity }}</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber(item.unit_price) }}</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber(item.line_amount || item.unit_price * (item.quantity_invoiced || item.quantity)) }}</td>
                    <td class="px-5 py-4 text-right text-gray-900">{{ item.tax_rate }}%</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber(item.tax_amount) }}</td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50/80 font-medium">
                  <tr>
                    <td colspan="5" class="px-5 py-4 text-right text-gray-600">Subtotal</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber(invoice?.subtotal || invoice?.net_amount) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5" class="px-5 py-4 text-right text-gray-600">Shipping Cost</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber(invoice?.shipping_cost) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5" class="px-5 py-4 text-right text-gray-600">Tax Total</td>
                    <td class="px-5 py-4 text-right text-gray-900">₱{{ formatNumber(invoice?.tax_amount) }}</td>
                  </tr>
                  <tr>
                    <td colspan="5" class="px-5 py-4 text-right text-gray-900 font-semibold">Total</td>
                    <td class="px-5 py-4 text-right text-blue-600 font-semibold">₱{{ formatNumber(invoice?.net_amount || invoice?.total_amount) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Remarks -->
          <div v-if="invoice?.remarks" class="bg-gray-50 rounded-xl p-4">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Remarks</p>
            <p class="text-sm text-gray-700">{{ invoice.remarks }}</p>
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
                <p class="text-sm text-gray-500 mt-1">{{ getMatchDescription(invoice?.match_status) }}</p>
              </div>
            </div>

            <div v-if="invoice?.match_notes && invoice.match_status === 'exception'" class="mt-4 bg-red-50 rounded-lg p-4">
              <p class="font-medium text-red-800 text-sm mb-2">Issues Found:</p>
              <ul class="space-y-1">
                <li v-for="(issue, idx) in parseMatchNotes(invoice.match_notes)" :key="idx" class="text-sm text-red-700 flex items-start gap-2">
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
                <i class="pi pi-sync" :class="{ 'animate-spin': matching }"></i>
                <span>{{ matching ? 'Matching...' : 'Run 3-Way Match' }}</span>
              </button>
            </div>
          </div>

          <!-- Comparison Cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- PO Card -->
            <div class="bg-white rounded-xl border border-gray-100 p-5">
              <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                  <i class="pi pi-file-pdf text-green-600 text-sm"></i>
                </div>
                <span class="font-medium text-gray-900">Purchase Order</span>
              </div>
              <div class="space-y-3">
                <p class="text-sm"><span class="text-gray-500">PO Number:</span> <span class="font-medium text-gray-900">{{ invoice?.purchase_order?.po_number }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Quantity:</span> <span class="font-medium text-gray-900">{{ calculatePOQuantity() }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Subtotal:</span> <span class="font-medium text-gray-900">₱{{ formatNumber(Number(invoice?.purchase_order?.subtotal || 0)) }}</span></p>
                <p class="text-xs text-gray-500"><span>Tax:</span> <span class="font-medium text-gray-900">₱{{ formatNumber(Number(invoice?.purchase_order?.tax_amount || 0)) }}</span></p>
                <p class="text-xs text-gray-500"><span>Delivery:</span> <span class="font-medium text-gray-900">₱{{ formatNumber(Number(invoice?.purchase_order?.shipping_cost || 0)) }}</span></p>
                <p class="text-xs text-gray-500"><span>Total:</span> <span class="font-medium text-gray-900">₱{{ formatNumber(Number(invoice?.purchase_order?.total_amount || 0)) }}</span></p>
                <p class="text-xs text-gray-400">{{ formatDate(invoice?.purchase_order?.order_date) }}</p>
              </div>
            </div>

            <!-- GRN Card -->
            <div class="bg-white rounded-xl border border-gray-100 p-5">
              <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center">
                  <i class="pi pi-box text-purple-600 text-sm"></i>
                </div>
                <span class="font-medium text-gray-900">Goods Receipt</span>
              </div>
              <div class="space-y-3">
                <p class="text-sm"><span class="text-gray-500">GRN Number:</span> <span class="font-medium text-gray-900">{{ invoice?.goods_receipt?.grn_number || '—' }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Quantity:</span> <span class="font-medium text-gray-900">{{ calculateGRNQuantity() }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Status:</span> <span class="font-medium text-gray-900">{{ formatReceiptStatus(invoice?.goods_receipt?.receipt_status) || 'N/A' }}</span></p>
                <p class="text-xs text-gray-400">{{ formatDate(invoice?.goods_receipt?.receipt_date) }}</p>
              </div>
            </div>

            <!-- Invoice Card -->
            <div class="bg-white rounded-xl border border-gray-100 p-5">
              <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                  <i class="pi pi-file text-blue-600 text-sm"></i>
                </div>
                <span class="font-medium text-gray-900">Invoice</span>
              </div>
              <div class="space-y-3">
                <p class="text-sm"><span class="text-gray-500">Invoice #:</span> <span class="font-medium text-gray-900">{{ invoice?.invoice_number }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Quantity:</span> <span class="font-medium text-gray-900">{{ calculateInvoiceQuantity() }}</span></p>
                <p class="text-sm"><span class="text-gray-500">Subtotal:</span> <span class="font-medium text-gray-900">₱{{ formatNumber(Number(invoice?.invoice_amount || 0)) }}</span></p>
                <p class="text-xs text-gray-500"><span>Tax:</span> <span class="font-medium text-gray-900">₱{{ formatNumber(Number(invoice?.tax_amount || 0)) }}</span></p>
                <p class="text-xs text-gray-500"><span>Delivery:</span> <span class="font-medium text-gray-900">₱{{ formatNumber(Number(invoice?.shipping_cost || 0)) }}</span></p>
                <p class="text-xs text-gray-500"><span>Total:</span> <span class="font-medium text-gray-900">₱{{ formatNumber(Number(invoice?.net_amount || 0)) }}</span></p>
                <p class="text-xs text-gray-400">{{ formatDate(invoice?.invoice_date) }}</p>
              </div>
            </div>
          </div>

          <!-- Variance Analysis -->
          <div v-if="invoice?.match_status === 'exception'" class="bg-white rounded-xl border border-gray-100 p-5">
            <h4 class="font-medium text-gray-900 mb-4">Variance Analysis</h4>
            <div class="grid grid-cols-3 gap-4">
              <div>
                <p class="text-xs text-gray-500 mb-1">Quantity Variance</p>
                <p class="text-lg font-semibold text-red-600">
                  {{ calculateQuantityVariance() }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Amount Variance</p>
                <p class="text-lg font-semibold text-red-600">
                  ₱{{ formatNumber(calculateAmountVariance()) }}
                </p>
              </div>
              <div>
                <p class="text-xs text-gray-500 mb-1">Variance %</p>
                <p class="text-lg font-semibold text-gray-900">
                  {{ calculateVariancePercentage() }}%
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

              <div v-if="invoice?.purchase_order?.approvals_received?.length" class="space-y-3">
                <p class="text-sm font-medium text-gray-700">PO Approval History</p>
                <div v-for="(approval, idx) in invoice.purchase_order.approvals_received" :key="idx" class="flex items-start gap-3 text-sm">
                  <i class="pi pi-check-circle text-green-500 text-xs mt-1"></i>
                  <div>
                    <p class="font-medium text-gray-900">{{ approval.approver_name }}</p>
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

              <div class="bg-gray-50 rounded-lg p-4">
                <div class="space-y-2">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Invoice Amount:</span>
                    <span class="font-medium text-gray-900">₱{{ formatNumber(invoice?.net_amount) }}</span>
                  </div>
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Payment Due:</span>
                    <span class="font-medium text-gray-900">{{ formatDate(invoice?.due_date) }}</span>
                  </div>
                </div>
              </div>

              <div v-if="invoice?.payment_status === 'paid'" class="bg-green-50 rounded-lg p-4">
                <p class="text-sm text-green-800">
                  Paid on <span class="font-medium">{{ formatDate(invoice?.payment_date) }}</span>
                </p>
                <p v-if="invoice?.payment_method" class="text-xs text-green-600 mt-1">
                  Method: {{ invoice.payment_method }}
                </p>
              </div>

              <div v-else class="bg-blue-50 rounded-lg p-4 space-y-2">
                <p class="text-xs text-blue-700">PayMongo Status: <span class="font-medium">{{ paymongoInvoiceStatus }}</span></p>
                <button
                  :disabled="paymongoInvoiceLoading"
                  class="w-full px-4 py-2.5 bg-blue-500 hover:bg-blue-600 disabled:opacity-50 text-white font-medium rounded-xl text-sm transition-colors flex items-center justify-center gap-2"
                  @click="handleInvoicePaymongoAction"
                >
                  <i class="pi pi-wallet"></i>
                  <span>{{ paymongoInvoiceActionLabel }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 4: Timeline -->
        <div v-if="activeTab === 3" class="space-y-4">
          <div class="relative pl-6">
            <div v-for="(event, idx) in timeline" :key="idx" class="relative pb-8 last:pb-0">
              <!-- Timeline line -->
              <div v-if="idx < timeline.length - 1" class="absolute left-2 top-8 bottom-0 w-0.5 bg-gray-200"></div>
              
              <!-- Timeline dot -->
              <div class="relative z-10 flex items-start gap-4">
                <div :style="{ backgroundColor: event.color }" class="w-10 h-10 rounded-full flex items-center justify-center text-white shrink-0">
                  <i :class="event.icon" class="text-sm"></i>
                </div>
                
                <!-- Content -->
                <div class="flex-1">
                  <p class="font-medium text-gray-900">{{ event.label }}</p>
                  <p class="text-sm text-gray-500 mt-1">{{ event.date }}</p>
                  <p v-if="event.description" class="text-sm text-gray-600 mt-2">{{ event.description }}</p>
                </div>
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
import { onBeforeUnmount, onMounted, ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Tag from 'primevue/tag'
import procurementService from '../../../../services/procurement.service'
import financeService from '../../../../services/finance.service'
import paymongoService from '@/services/paymongo.service'

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
const paymongoInvoiceIntentId = ref<string | null>(null)
const paymongoInvoiceStatus = ref('idle')
const paymongoInvoiceLoading = ref(false)
const paymongoInvoicePolling = ref<ReturnType<typeof setInterval> | null>(null)
const invoiceMarkedPaidByPaymongo = ref(false)

const isFinanceRoute = computed(() => String(route.name || '').startsWith('finance.'))
const paymongoInvoiceActionLabel = computed(() => {
  if (invoice.value?.payment_status === 'paid') return 'Invoice Paid'
  if (!paymongoInvoiceIntentId.value) return 'Pay with PayMongo'
  return 'Open PayMongo Checkout'
})

const tabs = [
  { label: 'Details', icon: 'pi pi-file' },
  { label: '3-Way Match', icon: 'pi pi-check-circle' },
  { label: 'Approval & Payment', icon: 'pi pi-credit-card' },
  { label: 'Timeline', icon: 'pi pi-clock' },
]

const timeline = computed(() => {
  if (!invoice.value) return []
  
  const events = [
    {
      label: 'Invoice Created',
      date: formatDate(invoice.value?.created_at),
      color: '#3B82F6',
      icon: 'pi pi-plus',
      description: `Invoice ${invoice.value?.invoice_number} was created`,
    },
    {
      label: 'PO Created',
      date: formatDate(invoice.value?.purchase_order?.created_at),
      color: '#10B981',
      icon: 'pi pi-file-pdf',
      description: `PO ${invoice.value?.purchase_order?.po_number} was created`,
    },
  ]

  if (invoice.value?.goods_receipt?.created_at) {
    events.push({
      label: 'Goods Received',
      date: formatDate(invoice.value?.goods_receipt?.created_at),
      color: '#8B5CF6',
      icon: 'pi pi-box',
      description: `GRN ${invoice.value?.goods_receipt?.grn_number} received`,
    })
  }

  if (invoice.value?.match_date || invoice.value?.updated_at) {
    events.push({
      label: '3-Way Match Performed',
      date: formatDate(invoice.value?.match_date || invoice.value?.updated_at),
      color: invoice.value?.match_status === 'matched' ? '#10B981' : '#EF4444',
      icon: invoice.value?.match_status === 'matched' ? 'pi pi-check-circle' : 'pi pi-exclamation-circle',
      description: `Match status: ${formatStatus(invoice.value?.match_status)}`,
    })
  }

  if (invoice.value?.status === 'approved') {
    events.push({
      label: 'Invoice Approved',
      date: formatDate(invoice.value?.updated_at),
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
      description: `Payment of ₱${formatNumber(invoice.value?.net_amount)} completed`,
    })
  }

  return events
})

// Helper Functions
const formatPaymentTerms = (term: string): string => {
  const map: Record<string, string> = {
    net_30: 'Net 30 Days',
    net_60: 'Net 60 Days',
    net_15: 'Net 15 Days',
    net_7: 'Net 7 Days',
    cash_on_delivery: 'Cash on Delivery',
    advance_payment: 'Advance Payment',
  }
  return map[term] || term || 'Net 30'
}

const formatReceiptStatus = (status: string): string => {
  const map: Record<string, string> = {
    full: 'Full Receipt',
    partial: 'Partial',
    pending: 'Pending',
  }
  return map[status] || status || '-'
}

const receiptStatusSeverity = (status: string): 'success' | 'info' | 'warn' | 'secondary' => {
  const map: Record<string, any> = {
    full: 'success',
    partial: 'warn',
    pending: 'secondary',
  }
  return map[status] || 'secondary'
}

const calculatePOQuantity = (): number => {
  return invoice.value?.purchase_order?.items?.reduce((sum: number, item: any) => sum + Number(item.quantity_ordered || 0), 0) || 0
}

const calculateGRNQuantity = (): number => {
  return invoice.value?.goods_receipt?.items?.reduce((sum: number, item: any) => sum + Number(item.quantity_received || 0), 0) || 0
}

const calculateInvoiceQuantity = (): number => {
  return invoice.value?.items?.reduce((sum: number, item: any) => sum + Number(item.quantity_invoiced || item.quantity || 0), 0) || 0
}

const calculateQuantityVariance = (): string => {
  const poQty = calculatePOQuantity()
  const grnQty = calculateGRNQuantity()
  const invQty = calculateInvoiceQuantity()
  
  if (poQty !== grnQty) return `PO/GRN mismatch: ${poQty} vs ${grnQty}`
  if (poQty !== invQty) return `PO/Invoice mismatch: ${poQty} vs ${invQty}`
  if (grnQty !== invQty) return `GRN/Invoice mismatch: ${grnQty} vs ${invQty}`
  return '0'
}

const calculateAmountVariance = (): number => {
  const poAmount = Number(invoice.value?.purchase_order?.subtotal || 0)
  const invAmount = Number(invoice.value?.invoice_amount || 0)
  return Math.abs(poAmount - invAmount)
}

const calculateVariancePercentage = (): string => {
  const poAmount = Number(invoice.value?.purchase_order?.subtotal || 0)
  const invAmount = Number(invoice.value?.invoice_amount || 0)
  if (poAmount === 0) return '0'
  return ((Math.abs(poAmount - invAmount) / poAmount) * 100).toFixed(2)
}

const parseMatchNotes = (notes: string): string[] => {
  try {
    return JSON.parse(notes)
  } catch {
    return [notes]
  }
}

const getMatchDescription = (status: string): string => {
  const map: Record<string, string> = {
    pending: 'Waiting for 3-way matching to be performed',
    matched: 'All documents match successfully',
    exception: 'Discrepancies found between documents',
  }
  return map[status] || 'Match status unknown'
}

// Methods
async function loadInvoice() {
  loading.value = true
  try {
    const response = isFinanceRoute.value
      ? await financeService.getInvoice(Number(route.params.id))
      : await procurementService.getInvoice(Number(route.params.id))
    
    invoice.value = response.data?.data || response.data
    paymentAmount.value = Number(invoice.value?.net_amount || 0)
    await loadLatestPaymongoInvoiceIntent()
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

async function loadLatestPaymongoInvoiceIntent() {
  if (!invoice.value?.id) return
  try {
    const response = await paymongoService.getLatestIntentByPayable('invoice', Number(invoice.value.id))
    const latest = response?.data
    if (!latest) return

    paymongoInvoiceIntentId.value = latest.payment_intent_id || null
    paymongoInvoiceStatus.value = latest.status || paymongoInvoiceStatus.value

    if (paymongoInvoiceIntentId.value && !['succeeded', 'failed', 'canceled', 'cancelled', 'paid'].includes(String(paymongoInvoiceStatus.value).toLowerCase())) {
      startInvoicePaymongoPolling()
    }
  } catch {
    // ignore
  }
}

async function createInvoicePaymongoIntent() {
  if (!invoice.value?.id || !invoice.value?.store_id) return
  paymongoInvoiceLoading.value = true
  try {
    const intentResponse = await paymongoService.createIntent({
      amount: Math.max(Math.round(Number(invoice.value?.net_amount || invoice.value?.invoice_amount || 0) * 100), 1),
      payment_method_allowed: ['gcash'],
      store_id: Number(invoice.value.store_id),
      payable_type: 'invoice',
      payable_id: Number(invoice.value.id),
    })
    paymongoInvoiceIntentId.value = intentResponse?.data?.id || null
    paymongoInvoiceStatus.value = intentResponse?.data?.attributes?.status || 'awaiting_payment_method'
    startInvoicePaymongoPolling()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'PayMongo',
      detail: error?.response?.data?.message || 'Failed to create PayMongo intent for invoice.',
      life: 3500,
    })
  } finally {
    paymongoInvoiceLoading.value = false
  }
}

async function openInvoicePaymongoCheckout() {
  if (!paymongoInvoiceIntentId.value) return
  paymongoInvoiceLoading.value = true
  try {
    const response = await paymongoService.startGcash(paymongoInvoiceIntentId.value, {
      name: invoice.value?.supplier?.contact_person || invoice.value?.supplier?.supplier_name || 'Supplier',
      email: invoice.value?.supplier?.email || 'supplier@example.com',
      phone: invoice.value?.supplier?.phone || invoice.value?.supplier?.contact_number || '09170000000',
      return_url: window.location.href,
    })

    const redirectUrl = response?.data?.redirect_url
    if (!redirectUrl) throw new Error('PayMongo checkout URL is missing.')
    window.open(redirectUrl, '_blank')
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'PayMongo',
      detail: error?.response?.data?.message || 'Unable to open PayMongo checkout.',
      life: 3500,
    })
  } finally {
    paymongoInvoiceLoading.value = false
  }
}

async function handleInvoicePaymongoAction() {
  if (invoice.value?.payment_status === 'paid') return
  if (!paymongoInvoiceIntentId.value) {
    await createInvoicePaymongoIntent()
    await openInvoicePaymongoCheckout()
    return
  }
  await openInvoicePaymongoCheckout()
}

async function pollInvoicePaymongoStatus() {
  if (!paymongoInvoiceIntentId.value || paymongoInvoiceLoading.value) return
  paymongoInvoiceLoading.value = true
  try {
    const response = await paymongoService.getIntent(paymongoInvoiceIntentId.value)
    paymongoInvoiceStatus.value = response?.data?.data?.attributes?.status || paymongoInvoiceStatus.value

    if (['succeeded', 'paid'].includes(String(paymongoInvoiceStatus.value).toLowerCase()) && invoice.value?.payment_status !== 'paid' && !invoiceMarkedPaidByPaymongo.value) {
      invoiceMarkedPaidByPaymongo.value = true
      await financeService.markInvoicePaid(Number(invoice.value.id), {
        payment_method: 'paymongo_gcash',
        payment_amount: Number(invoice.value?.net_amount || invoice.value?.invoice_amount || 0),
      })
      await loadInvoice()
    }

    if (['succeeded', 'failed', 'canceled', 'cancelled', 'paid'].includes(String(paymongoInvoiceStatus.value).toLowerCase())) {
      stopInvoicePaymongoPolling()
    }
  } catch {
    // ignore polling failures to keep UI responsive
  } finally {
    paymongoInvoiceLoading.value = false
  }
}

function startInvoicePaymongoPolling() {
  stopInvoicePaymongoPolling()
  pollInvoicePaymongoStatus()
  paymongoInvoicePolling.value = setInterval(pollInvoicePaymongoStatus, 8000)
}

function stopInvoicePaymongoPolling() {
  if (paymongoInvoicePolling.value) {
    clearInterval(paymongoInvoicePolling.value)
    paymongoInvoicePolling.value = null
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

onBeforeUnmount(() => {
  stopInvoicePaymongoPolling()
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
