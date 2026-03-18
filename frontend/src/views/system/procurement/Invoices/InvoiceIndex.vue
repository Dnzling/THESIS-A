<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <!-- iOS-style Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-semibold text-gray-900 tracking-tight">Invoices</h1>
      </div>
    </div>

    <!-- iOS-style Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-2">
          <span class="text-gray-500 text-sm font-medium">Total Invoices</span>
          <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
            <i class="pi pi-file text-blue-600 text-sm"></i>
          </div>
        </div>
        <p class="text-2xl font-semibold text-gray-900">{{ stats.totalCount }}</p>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-2">
          <span class="text-gray-500 text-sm font-medium">Matched</span>
          <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
            <i class="pi pi-check-circle text-green-600 text-sm"></i>
          </div>
        </div>
        <p class="text-2xl font-semibold text-green-600">{{ stats.matchedCount }}</p>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between mb-2">
          <span class="text-gray-500 text-sm font-medium">Pending</span>
          <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
            <i class="pi pi-clock text-orange-600 text-sm"></i>
          </div>
        </div>
        <p class="text-2xl font-semibold text-orange-600">{{ stats.pendingCount }}</p>
      </div>

      <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 cursor-pointer hover:bg-red-50/30 transition-colors" @click="viewExceptions">
        <div class="flex items-center justify-between mb-2">
          <span class="text-gray-500 text-sm font-medium">Exceptions</span>
          <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
            <i class="pi pi-exclamation-triangle text-red-600 text-sm"></i>
          </div>
        </div>
        <p class="text-2xl font-semibold text-red-600">{{ stats.exceptionCount }}</p>
      </div>

      <div class="bg-gradient-to-br from-purple-600 to-purple-700 rounded-2xl p-5 shadow-lg">
        <div class="flex items-center justify-between mb-2">
          <span class="text-purple-100 text-sm font-medium">Total Amount</span>
          <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center">
            <i class="pi pi-credit-card text-white text-sm"></i>
          </div>
        </div>
        <p class="text-2xl font-bold text-white tracking-tight">₱{{ formatNumber(stats.totalAmount) }}</p>
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
            <span v-if="tab.count && tab.count > 0" class="bg-gray-200 text-gray-700 text-xs px-2 py-0.5 rounded-full ml-1">
              {{ tab.count }}
            </span>
          </span>
          <div 
            v-if="activeTab === index" 
            class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-500 rounded-full"
          ></div>
        </button>
      </div>

      <div class="p-6">
        <!-- Tab 1: All Invoices -->
        <div v-if="activeTab === 0" class="space-y-4">
          <!-- Filters -->
          <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div class="relative">
              <i class="pi pi-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search invoice no"
                class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
                @keyup.enter="loadInvoices"
              />
            </div>

            <select
              v-model="filters.status"
              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all appearance-none"
              @change="loadInvoices"
            >
              <option value="">All Status</option>
              <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>

            <select
              v-model="filters.match_status"
              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all appearance-none"
              @change="loadInvoices"
            >
              <option value="">Match Status</option>
              <option v-for="opt in matchStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
            </select>

            <input
              v-model="filters.date_from"
              type="date"
              placeholder="From Date"
              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
              @change="loadInvoices"
            />

            <button
              @click="loadInvoices"
              class="px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl text-sm transition-colors flex items-center justify-center gap-2"
            >
              <i class="pi pi-refresh"></i>
              <span>Refresh</span>
            </button>
          </div>

          <!-- Invoices Table -->
          <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
            <div v-if="loading" class="flex justify-center py-12">
              <div class="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else-if="invoices.length === 0" class="text-center py-12">
              <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
              <p class="text-gray-500">No invoices found</p>
            </div>

            <table v-else class="w-full text-sm">
              <thead class="bg-gray-50/80 text-gray-500 text-xs">
                <tr>
                  <th class="px-5 py-4 text-left font-medium">Invoice No.</th>
                  <th class="px-5 py-4 text-left font-medium">Supplier</th>
                  <th class="px-5 py-4 text-left font-medium">Invoice / Due Date</th>
                  <th class="px-5 py-4 text-right font-medium">Amount</th>
                  <th class="px-5 py-4 text-left font-medium">Match Status</th>
                  <th class="px-5 py-4 text-left font-medium">Status</th>
                  <th class="px-5 py-4 text-center font-medium">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-gray-50/50 transition-colors">
                  <td class="px-5 py-4">
                    <RouterLink
                      :to="`/procurement/invoices/${inv.id}`"
                      class="text-blue-500 hover:text-blue-600 font-medium"
                    >
                      {{ inv.invoice_number }}
                    </RouterLink>
                  </td>
                  <td class="px-5 py-4">
                    <div>
                      <p class="font-medium text-gray-900">{{ inv.supplier_name }}</p>
                      <p class="text-xs text-gray-500 mt-0.5">{{ inv.supplier_code }}</p>
                    </div>
                  </td>
                  <td class="px-5 py-4">
                    <div class="space-y-1">
                      <p class="text-gray-700">{{ formatDate(inv.invoice_date) }}</p>
                      <p class="text-xs text-orange-600 font-medium">{{ formatDate(inv.due_date) }}</p>
                    </div>
                  </td>
                  <td class="px-5 py-4 text-right font-medium text-green-600">₱{{ formatNumber(inv.total_amount) }}</td>
                  <td class="px-5 py-4">
                    <div class="flex items-center gap-2">
                      <div :class="getMatchDot(inv.match_status)" class="w-2 h-2 rounded-full"></div>
                      <span :class="getMatchClass(inv.match_status)" class="text-sm">
                        {{ formatStatus(inv.match_status) }}
                      </span>
                      <button
                        v-if="inv.match_status === 'pending'"
                        @click="performMatch(inv)"
                        class="ml-2 text-xs text-blue-500 hover:text-blue-600 font-medium"
                      >
                        Try Match
                      </button>
                    </div>
                  </td>
                  <td class="px-5 py-4">
                    <div class="space-y-1">
                      <div class="flex items-center gap-2">
                        <div :class="getStatusDot(inv.status)" class="w-2 h-2 rounded-full"></div>
                        <span :class="getStatusClass(inv.status)" class="text-sm">
                          {{ formatStatus(inv.status) }}
                        </span>
                      </div>
                      <div v-if="inv.payment_status" class="flex items-center gap-2 mt-1">
                        <div :class="getPaymentDot(inv.payment_status)" class="w-2 h-2 rounded-full"></div>
                        <span :class="getPaymentClass(inv.payment_status)" class="text-xs">
                          {{ formatFinanceStatus(inv.payment_status) }}
                        </span>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-4">
                    <div class="flex items-center justify-center gap-1">
                      <button
                        @click="viewInvoice(inv)"
                        class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center transition-colors"
                        title="View"
                      >
                        <i class="pi pi-eye text-gray-500 text-sm"></i>
                      </button>
                      <button
                        v-if="inv.status === 'draft'"
                        @click="editInvoice(inv)"
                        class="w-8 h-8 rounded-full hover:bg-gray-100 flex items-center justify-center transition-colors"
                        title="Edit"
                      >
                        <i class="pi pi-pencil text-gray-500 text-sm"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Pagination -->
            <div v-if="invoices.length > 0" class="flex items-center justify-between px-5 py-4 border-t border-gray-100 bg-gray-50/50">
              <p class="text-xs text-gray-500">Showing {{ invoices.length }} of {{ stats.totalCount }} invoices</p>
              <div class="flex gap-2">
                <button class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 transition-colors">
                  <i class="pi pi-chevron-left text-xs"></i>
                </button>
                <button class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center text-sm font-medium">
                  1
                </button>
                <button class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 transition-colors">
                  2
                </button>
                <button class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-600 hover:bg-gray-50 transition-colors">
                  <i class="pi pi-chevron-right text-xs"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 2: Exceptions -->
        <div v-if="activeTab === 1">
          <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          </div>

          <div v-else-if="exceptions.length === 0" class="text-center py-12">
            <i class="pi pi-check-circle text-4xl text-green-300 mb-3"></i>
            <p class="text-gray-500">No exceptions found</p>
          </div>

          <div v-else class="space-y-3">
            <div v-for="inv in exceptions" :key="inv.id" class="bg-white rounded-xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
              <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <RouterLink
                      :to="`/procurement/invoices/${inv.id}`"
                      class="text-blue-500 hover:text-blue-600 font-semibold"
                    >
                      {{ inv.invoice_number }}
                    </RouterLink>
                    <span class="text-gray-400 text-sm">{{ inv.supplier_name }}</span>
                  </div>

                  <div class="space-y-2">
                    <div v-if="inv.match_result?.issues" class="text-sm">
                      <div v-for="(issue, idx) in inv.match_result.issues" :key="idx" class="flex items-start gap-2 text-red-600 mb-1">
                        <i class="pi pi-exclamation-circle text-xs mt-0.5"></i>
                        <span class="text-gray-700">{{ issue }}</span>
                      </div>
                    </div>

                    <div class="flex flex-wrap gap-4 text-sm">
                      <div>
                        <span class="text-gray-500">PO Amount:</span>
                        <span class="font-medium ml-1">₱{{ formatNumber(inv.po_amount) }}</span>
                      </div>
                      <div>
                        <span class="text-gray-500">Invoice Amount:</span>
                        <span class="font-medium ml-1">₱{{ formatNumber(inv.invoice_amount) }}</span>
                      </div>
                      <div v-if="inv.variance">
                        <span class="text-gray-500">Variance:</span>
                        <span class="font-medium text-red-600 ml-1">₱{{ formatNumber(Math.abs(inv.variance)) }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex gap-2">
                  <button
                    @click="resolveException(inv)"
                    class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2"
                  >
                    <i class="pi pi-check"></i>
                    <span>Resolve</span>
                  </button>
                  <button
                    @click="viewInvoice(inv)"
                    class="px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2"
                  >
                    <i class="pi pi-eye"></i>
                    <span>View</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Tab 3: Pending Approval -->
        <div v-if="activeTab === 2">
          <div v-if="loading" class="flex justify-center py-12">
            <div class="w-8 h-8 border-3 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
          </div>

          <div v-else-if="pendingApprovalInvoices.length === 0" class="text-center py-12">
            <i class="pi pi-check-circle text-4xl text-green-300 mb-3"></i>
            <p class="text-gray-500">No pending approvals</p>
          </div>

          <div v-else class="space-y-3">
            <div v-for="inv in pendingApprovalInvoices" :key="inv.id" class="bg-white rounded-xl border border-gray-100 p-5 hover:shadow-sm transition-shadow">
              <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                  <div class="flex items-center gap-3 mb-2">
                    <RouterLink
                      :to="`/procurement/invoices/${inv.id}`"
                      class="text-blue-500 hover:text-blue-600 font-semibold"
                    >
                      {{ inv.invoice_number }}
                    </RouterLink>
                    <span class="text-gray-400 text-sm">{{ inv.supplier_name }}</span>
                  </div>
                  <div class="flex flex-wrap gap-4 text-sm">
                    <div>
                      <span class="text-gray-500">Amount:</span>
                      <span class="font-medium ml-1">₱{{ formatNumber(inv.total_amount) }}</span>
                    </div>
                    <div>
                      <span class="text-gray-500">Date:</span>
                      <span class="font-medium ml-1">{{ formatDate(inv.invoice_date) }}</span>
                    </div>
                  </div>
                </div>

                <button
                  @click="approveInvoice(inv)"
                  class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2"
                >
                  <i class="pi pi-check"></i>
                  <span>Approve</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Resolve Exception Dialog -->
    <div v-if="showResolveDialog" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Resolve Exception</h3>
        
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Resolution Type</label>
            <select
              v-model="resolveData.resolution_type"
              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all appearance-none"
            >
              <option value="">Select resolution</option>
              <option v-for="type in resolutionTypes" :key="type.value" :value="type.value">{{ type.label }}</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea
              v-model="resolveData.notes"
              rows="3"
              placeholder="Add resolution notes"
              class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all resize-none"
            ></textarea>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-6">
          <button
            @click="showResolveDialog = false"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl text-sm transition-colors"
          >
            Cancel
          </button>
          <button
            @click="submitResolve"
            :disabled="resolving"
            class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white font-medium rounded-xl text-sm transition-colors flex items-center gap-2"
          >
            <span v-if="resolving" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <span>{{ resolving ? 'Resolving...' : 'Resolve' }}</span>
          </button>
        </div>
      </div>
    </div>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const toast = useToast()

// State
const invoices = ref<any[]>([])
const exceptions = ref<any[]>([])
const pendingApprovalInvoices = ref<any[]>([])
const loading = ref(false)
const activeTab = ref(0)
const showResolveDialog = ref(false)
const resolving = ref(false)

const stats = ref({
  totalCount: 0,
  matchedCount: 0,
  pendingCount: 0,
  exceptionCount: 0,
  totalAmount: 0,
})

const filters = ref({
  search: '',
  status: '',
  match_status: '',
  date_from: '',
})

const resolveData = ref({
  invoice_id: null,
  resolution_type: '',
  notes: '',
})

const tabs = computed(() => [
  { label: 'All Invoices', icon: 'pi pi-list' },
  { label: 'Exceptions', icon: 'pi pi-exclamation-triangle', count: exceptionCount.value },
  { label: 'Pending Approval', icon: 'pi pi-clock', count: pendingApprovalCount.value },
])

const statusOptions = [
  { label: 'Draft', value: 'draft' },
  { label: 'Pending Approval', value: 'pending_approval' },
  { label: 'Approved', value: 'approved' },
  { label: 'Paid', value: 'paid' },
]

const matchStatusOptions = [
  { label: 'Matched', value: 'matched' },
  { label: 'Pending', value: 'pending' },
  { label: 'Exception', value: 'exception' },
]

const resolutionTypes = [
  { label: 'PO Adjustment', value: 'po_adjust' },
  { label: 'Invoice Correction', value: 'invoice_correct' },
  { label: 'Approve Override', value: 'approve_override' },
  { label: 'Reject Invoice', value: 'reject' },
]

// Computed
const exceptionCount = computed(() => exceptions.value.length)
const pendingApprovalCount = computed(() => pendingApprovalInvoices.value.length)

// Helper functions
function formatStatus(status: string): string {
  if (!status) return '-'
  return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')
}

function formatFinanceStatus(status: string): string {
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

// Methods
async function loadInvoices() {
  loading.value = true
  try {
    const response = await procurementService.getInvoices(filters.value)
    invoices.value = response.data?.data || []

    // Separate by status
    exceptions.value = invoices.value.filter((i: any) => i.match_status === 'exception')
    pendingApprovalInvoices.value = invoices.value.filter((i: any) => i.status === 'pending_approval')

    calculateStats()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load invoices',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function calculateStats() {
  stats.value.totalCount = invoices.value.length
  stats.value.matchedCount = invoices.value.filter((i: any) => i.match_status === 'matched').length
  stats.value.pendingCount = invoices.value.filter((i: any) => i.match_status === 'pending').length
  stats.value.exceptionCount = invoices.value.filter((i: any) => i.match_status === 'exception').length
  stats.value.totalAmount = invoices.value.reduce((sum: number, i: any) => sum + (i.total_amount || 0), 0)
}

async function performMatch(invoice: any) {
  try {
    loading.value = true
    await procurementService.performInvoiceMatch(invoice.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Invoice matched successfully',
      life: 3000,
    })
    loadInvoices()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to match invoice',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

async function approveInvoice(invoice: any) {
  try {
    loading.value = true
    await procurementService.approveInvoice(invoice.id)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Invoice approved',
      life: 3000,
    })
    loadInvoices()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to approve invoice',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function viewInvoice(invoice: any) {
  router.push({
    name: 'procurement.invoices.detail',
    params: { id: invoice.id },
  })
}

function editInvoice(invoice: any) {
  router.push({
    name: 'procurement.invoices.edit',
    params: { id: invoice.id },
  })
}

function resolveException(invoice: any) {
  resolveData.value.invoice_id = invoice.id
  showResolveDialog.value = true
}

async function submitResolve() {
  if (!resolveData.value.resolution_type) {
    toast.add({
      severity: 'warn',
      summary: 'Required',
      detail: 'Please select a resolution type',
      life: 3000,
    })
    return
  }

  try {
    resolving.value = true
    // TODO: Implement resolution endpoint
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Exception resolved',
      life: 3000,
    })
    showResolveDialog.value = false
    loadInvoices()
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to resolve exception',
      life: 3000,
    })
  } finally {
    resolving.value = false
  }
}

function viewExceptions() {
  activeTab.value = 1
}

function formatDate(date: string): string {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(value || 0)
}

function goToCreateInvoice() {
  router.push({ name: 'procurement.invoices.create' })
}

onMounted(() => {
  loadInvoices()
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

/* Loading spinner */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 0.8s linear infinite;
}
</style>