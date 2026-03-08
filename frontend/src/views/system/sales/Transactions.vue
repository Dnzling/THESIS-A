<!-- views/system/Transactions.vue -->
<template>
    <div class="space-y-6">
        <!-- Debug info to check if data is loading -->
        <div v-if="!transactions || transactions.length === 0" class="bg-yellow-50 p-4 rounded-lg">
            <p>No transactions data found</p>
        </div>
    
        <!-- Header -->
        <div class="bg-white shadow rounded-xl p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Transactions</h1>
                    <p class="text-gray-600 mt-2">Manage and view all sales transactions</p>
                </div>
                <div class="flex space-x-2">
                    <Button label="New Transaction" icon="pi pi-plus" @click="showNewTransactionDialog = true" />
                    <Button label="Batch Actions" icon="pi pi-cog" severity="secondary"
                        @click="showBatchActions = !showBatchActions" />
                </div>
            </div>
        </div>
    
        <!-- Batch Actions Panel -->
        <div v-if="showBatchActions" class="bg-white shadow rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <span class="text-sm font-medium text-gray-700">{{ selectedTransactions.length }} items selected</span>
                    <div class="flex space-x-2">
                        <Button label="Export Selected" icon="pi pi-download" size="small" severity="secondary"
                            @click="exportSelected" />
                        <Button label="Mark as Processed" icon="pi pi-check-circle" size="small" @click="markAsProcessed" />
                        <Button label="Delete Selected" icon="pi pi-trash" size="small" severity="danger"
                            @click="deleteSelected" />
                    </div>
                </div>
                <Button label="Clear Selection" text size="small" @click="clearSelection" />
            </div>
        </div>
    
        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white shadow rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <h6 class="text-sm font-semibold text-gray-500">Today's Revenue</h6>
                    <i class="pi pi-money-bill text-green-500 text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800 mt-2">₱{{ formatCurrency(todaysRevenue) }}</p>
                <p class="text-sm text-green-500">{{ todayTransactions }} transactions</p>
            </div>
    
            <div class="bg-white shadow rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <h6 class="text-sm font-semibold text-gray-500">Pending</h6>
                    <i class="pi pi-clock text-yellow-500 text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800 mt-2">{{ pendingCount }}</p>
                <p class="text-sm text-yellow-500">Requires action</p>
            </div>
    
            <div class="bg-white shadow rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <h6 class="text-sm font-semibold text-gray-500">This Month</h6>
                    <i class="pi pi-chart-line text-blue-500 text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800 mt-2">₱{{ formatCurrency(monthlyRevenue) }}</p>
                <p class="text-sm text-blue-500">Monthly total</p>
            </div>
    
            <div class="bg-white shadow rounded-xl p-6">
                <div class="flex items-center justify-between">
                    <h6 class="text-sm font-semibold text-gray-500">Avg. Order Value</h6>
                    <i class="pi pi-shopping-cart text-purple-500 text-lg"></i>
                </div>
                <p class="text-2xl font-bold text-gray-800 mt-2">₱{{ formatCurrency(avgOrderValue) }}</p>
                <p class="text-sm text-purple-500">Per transaction</p>
            </div>
        </div>
    
        <!-- Filters and Search -->
        <div class="bg-white shadow rounded-xl p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 flex-1">
    
    
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <MultiSelect v-model="selectedStatus" :options="statusOptions" optionLabel="name"
                        placeholder="All Status" display="chip" class="w-full" />
                </div>
                <!-- Payment Method -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <Select v-model="selectedPaymentMethod" :options="paymentMethodOptions" optionLabel="name"
                        placeholder="All Methods" class="w-full" />
                </div>
    
                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <div class="flex items-center space-x-2">
                        <DatePicker v-model="dateRange.start" placeholder="From" showIcon dateFormat="yy-mm-dd"
                            class="flex-1" />
                        <DatePicker v-model="dateRange.end" placeholder="To" showIcon dateFormat="yy-mm-dd"
                            class="flex-1" />
                    </div>
                </div>
    
            </div>
    
            <div class="mt-4 flex justify-between items-center">
                <div class="flex space-x-2">
                    <Button label="Apply Filters" size="small" @click="applyFilters" />
                    <Button label="Clear Filters" severity="secondary" size="small" @click="clearFilters" />
                </div>
                <div class="text-sm text-gray-500">
                    Showing {{ filteredTransactions.length }} of {{ transactions.length }} transactions
                </div>
            </div>
        </div>
    
    
    
        <!-- DEBUG: Check if data is loaded
            <div v-if="debug" class="bg-blue-50 p-4 rounded-lg">
              <p>Debug: transactions count = {{ transactions.length }}</p>
              <p>Debug: filteredTransactions count = {{ filteredTransactions.length }}</p>
            </div> -->
    
        <!-- Transactions Table -->
        <div class="bg-white shadow rounded-xl p-6">
            <!-- Search -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <IconField>
                    <InputIcon>
                        <i class="pi pi-search" />
                    </InputIcon>
                    <InputText v-model="searchTerm" placeholder="Search" class="w-1/4 " />
                </IconField>
            </div>
            <div v-if="filteredTransactions.length === 0" class="text-center py-8 text-gray-500">
                <i class="pi pi-inbox text-4xl mb-4"></i>
                <p>No transactions found</p>
                <p class="text-sm mt-2">Try changing your filters or add a new transaction</p>
            </div>
    
            <DataTable v-else :value="filteredTransactions" v-model:selection="selectedTransactions" dataKey="id"
                sortMode="multiple" tableStyle="min-width: 50rem" paginator :rows="10" :rowsPerPageOptions="[5, 10, 20, 50]"
                paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
                currentPageReportTemplate="Showing {first} to {last} of {totalRecords} transactions" :loading="loading">
                <!-- Checkbox Column -->
                <Column selectionMode="multiple" style="width: 1%"></Column>
    
                <!-- Order ID -->
                <Column field="orderId" header="Order ID" sortable style="width: 10%">
                    <template #body="slotProps">
                        <div class="flex items-center">
                            <Button icon="pi pi-external-link" text rounded size="small" class="mr-2"
                                @click="viewTransaction(slotProps.data)" />
                            <span class="font-mono text-blue-600 font-medium">#{{ slotProps.data.orderId }}</span>
                        </div>
                    </template>
                </Column>
    
                <!-- Customer -->
                <Column field="customer" header="Customer" sortable style="width: 15%">
                    <template #body="slotProps">
                        <div class="flex items-center space-x-3">
                            <Avatar :label="getInitials(slotProps.data.customer)" size="small" shape="circle"
                                class="bg-blue-100 text-blue-800" />
                            <div>
                                <p class="font-medium">{{ slotProps.data.customer }}</p>
                                <p class="text-xs text-gray-500">{{ slotProps.data.email }}</p>
                            </div>
                        </div>
                    </template>
                </Column>
    
                <!-- Date & Time -->
                <Column field="date" header="Date & Time" sortable style="width: 12%">
                    <template #body="slotProps">
                        <div>
                            <p class="font-medium">{{ formatDate(slotProps.data.date) }}</p>
                            <p class="text-xs text-gray-500">{{ slotProps.data.time }}</p>
                        </div>
                    </template>
                </Column>
    
                <!-- Amount -->
                <Column field="amount" header="Amount" sortable style="width: 10%">
                    <template #body="slotProps">
                        <div class="text-right">
                            <p class="font-bold">₱{{ formatCurrency(slotProps.data.amount) }}</p>
                            <p class="text-xs text-gray-500">{{ slotProps.data.itemCount }} items</p>
                        </div>
                    </template>
                </Column>
    
                <!-- Payment Method -->
                <Column field="paymentMethod" header="Payment" sortable style="width: 12%">
                    <template #body="slotProps">
                        <div class="flex items-center space-x-2">
                            <div :class="`p-1 rounded ${getPaymentMethodClass(slotProps.data.paymentMethod)}`">
                                <i :class="`pi ${getPaymentMethodIcon(slotProps.data.paymentMethod)} text-sm`"></i>
                            </div>
                            <span>{{ slotProps.data.paymentMethod }}</span>
                        </div>
                    </template>
                </Column>
    
                <!-- Status -->
                <Column field="status" header="Status" sortable style="width: 12%">
                    <template #body="slotProps">
                        <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)"
                            :icon="getStatusIcon(slotProps.data.status)" rounded />
                    </template>
                </Column>
    
                <!-- Actions -->
                <Column header="Actions" style="width: 12%">
                    <template #body="slotProps">
                        <div class="flex space-x-1">
                            <Button icon="pi pi-eye" size="small" text rounded severity="info"
                                @click="viewTransaction(slotProps.data)" />
                            <Button icon="pi pi-pencil" size="small" text rounded severity="secondary"
                                @click="editTransaction(slotProps.data)" />
                            <Button icon="pi pi-print" size="small" text rounded severity="help"
                                @click="printInvoice(slotProps.data)" />
                            <Button icon="pi pi-trash" size="small" text rounded severity="danger"
                                @click="confirmDelete(slotProps.data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    
        <!-- Transaction Details Dialog -->
        <Dialog v-model:visible="showTransactionDialog"
            :header="selectedTransaction ? `Transaction #${selectedTransaction.orderId}` : 'Transaction Details'"
            :style="{ width: '700px' }">
            <div v-if="selectedTransaction" class="space-y-6">
                <!-- Transaction Summary -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Customer</p>
                            <p class="font-medium">{{ selectedTransaction.customer }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Date & Time</p>
                            <p class="font-medium">{{ selectedTransaction.date }} {{ selectedTransaction.time }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Payment Method</p>
                            <p class="font-medium">{{ selectedTransaction.paymentMethod }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            <Tag :value="selectedTransaction.status"
                                :severity="getStatusSeverity(selectedTransaction.status)" />
                        </div>
                    </div>
                </div>
    
                <!-- Items List -->
                <div>
                    <h4 class="font-medium text-gray-800 mb-3">Items</h4>
                    <div class="space-y-2">
                        <div v-for="item in selectedTransaction.items" :key="item.id"
                            class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <div>
                                <p class="font-medium">{{ item.name }}</p>
                                <p class="text-sm text-gray-500">Qty: {{ item.quantity }} × ₱{{ formatCurrency(item.price)
                                    }}</p>
                            </div>
                            <p class="font-bold">₱{{ formatCurrency(item.quantity * item.price) }}</p>
                        </div>
                    </div>
                </div>
    
                <!-- Totals -->
                <div class="border-t pt-4">
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Subtotal</span>
                        <span>₱{{ formatCurrency(selectedTransaction.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Shipping</span>
                        <span>₱{{ formatCurrency(selectedTransaction.shipping) }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-gray-600">Tax</span>
                        <span>₱{{ formatCurrency(selectedTransaction.tax) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-lg pt-2 border-t">
                        <span>Total</span>
                        <span>₱{{ formatCurrency(selectedTransaction.amount) }}</span>
                    </div>
                </div>
    
                <!-- Notes -->
                <div v-if="selectedTransaction.notes">
                    <h4 class="font-medium text-gray-800 mb-2">Notes</h4>
                    <p class="text-gray-600">{{ selectedTransaction.notes }}</p>
                </div>
            </div>
    
            <template #footer>
                <Button label="Close" severity="secondary" @click="showTransactionDialog = false" />
                <Button label="Print Invoice" icon="pi pi-print" @click="printInvoice(selectedTransaction)" />
                <Button label="Edit" icon="pi pi-pencil" @click="editTransaction(selectedTransaction)" />
            </template>
        </Dialog>
    
        <!-- New Transaction Dialog -->
        <Dialog v-model:visible="showNewTransactionDialog" header="New Transaction" :style="{ width: '800px' }">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Customer</label>
                        <Select v-model="newTransaction.customerId" :options="customers" optionLabel="name"
                            placeholder="Select Customer" class="w-full" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <Select v-model="newTransaction.paymentMethod" :options="paymentMethodOptions" optionLabel="name"
                            placeholder="Select Method" class="w-full" />
                    </div>
                </div>
    
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Items</label>
                    <DataTable :value="newTransaction.items" class="mb-4">
                        <Column field="product" header="Product"></Column>
                        <Column field="quantity" header="Qty" style="width: 100px">
                            <template #body="slotProps">
                                <InputNumber v-model="slotProps.data.quantity" :min="1" showButtons />
                            </template>
                        </Column>
                        <Column field="price" header="Price" style="width: 150px">
                            <template #body="slotProps">
                                <InputNumber v-model="slotProps.data.price" mode="currency" currency="PHP" locale="en-PH" />
                            </template>
                        </Column>
                        <Column header="Total" style="width: 150px">
                            <template #body="slotProps">
                                ₱{{ formatCurrency(slotProps.data.quantity * slotProps.data.price) }}
                            </template>
                        </Column>
                        <Column header="Action" style="width: 80px">
                            <template #body="slotProps">
                                <Button icon="pi pi-trash" severity="danger" text rounded
                                    @click="removeItem(slotProps.index)" />
                            </template>
                        </Column>
                    </DataTable>
                    <Button label="Add Item" icon="pi pi-plus" size="small" @click="addItem" />
                </div>
    
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Shipping</label>
                        <InputNumber v-model="newTransaction.shipping" mode="currency" currency="PHP" locale="en-PH"
                            class="w-full" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tax (%)</label>
                        <InputNumber v-model="newTransaction.taxRate" suffix="%" :min="0" :max="100" class="w-full" />
                    </div>
                </div>
    
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <Textarea v-model="newTransaction.notes" rows="3" class="w-full" />
                </div>
            </div>
    
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showNewTransactionDialog = false" />
                <Button label="Create Transaction" @click="createTransaction" />
            </template>
        </Dialog>
    
        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:visible="showDeleteDialog" header="Confirm Delete" :modal="true" :style="{ width: '400px' }">
            <div class="confirmation-content">
                <i class="pi pi-exclamation-triangle mr-3" style="font-size: 2rem" />
                <span>Are you sure you want to delete this transaction?</span>
            </div>
            <template #footer>
                <Button label="No" severity="secondary" @click="showDeleteDialog = false" />
                <Button label="Yes" severity="danger" @click="deleteTransaction" />
            </template>
        </Dialog>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import DatePicker from 'primevue/datepicker'
import Select from 'primevue/select'
import MultiSelect from 'primevue/multiselect'
import InputNumber from 'primevue/inputnumber'
import Dialog from 'primevue/dialog'
import Avatar from 'primevue/avatar'
import Textarea from 'primevue/textarea'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
// Debug mode
const debug = ref(true)

// State
const loading = ref(false)
const showBatchActions = ref(false)
const showTransactionDialog = ref(false)
const showNewTransactionDialog = ref(false)
const showDeleteDialog = ref(false)
const searchTerm = ref('')
const selectedStatus = ref<any[]>([])
const selectedPaymentMethod = ref<any>(null)
const selectedTransactions = ref<any[]>([])
const selectedTransaction = ref<any>(null)
const transactionToDelete = ref<any>(null)

// Date Range - Fixed: ensure dates are Date objects
const dateRange = ref({
  start: new Date(new Date().setDate(new Date().getDate() - 7)),
  end: new Date()
})

// New Transaction
const newTransaction = ref({
  customerId: null,
  paymentMethod: null,
  items: [
    { id: 1, product: 'Modern Sofa', quantity: 1, price: 25000 }
  ],
  shipping: 500,
  taxRate: 12,
  notes: ''
})

// Transactions Data - CORRECT VERSION
const transactions = ref<any[]>([
  {
    id: 1,
    orderId: 'ORD-2026-0012',
    customer: 'John Smith',
    email: 'john@email.com',
    date: '2026-01-15',
    time: '10:30 AM',
    amount: 25500,
    subtotal: 25000,
    shipping: 500,
    tax: 0,
    itemCount: 3,
    paymentMethod: 'Credit Card',
    status: 'Completed',
    notes: 'Customer requested express shipping',
    items: [
      { id: 1, name: 'Modern Sofa', quantity: 1, price: 25000 }
    ]
  },
  {
    id: 2,
    orderId: 'ORD-2026-0013',
    customer: 'Sarah Johnson',
    email: 'sarah@email.com',
    date: '2026-01-15',
    time: '11:45 AM',
    amount: 18700,
    subtotal: 18500,
    shipping: 200,
    tax: 0,
    itemCount: 2,
    paymentMethod: 'PayPal',
    status: 'Processing',
    notes: '',
    items: [
      { id: 1, name: 'Office Chair', quantity: 2, price: 9250 }
    ]
  },
  {
    id: 3,
    orderId: 'ORD-2026-0014',
    customer: 'Mike Wilson',
    email: 'mike@email.com',
    date: '2026-01-14',
    time: '02:15 PM',
    amount: 32500,
    subtotal: 32500,
    shipping: 0,
    tax: 0,
    itemCount: 1,
    paymentMethod: 'Bank Transfer',
    status: 'Completed',
    notes: 'Paid in full',
    items: [
      { id: 1, name: 'Dining Table Set', quantity: 1, price: 32500 }
    ]
  },
  {
    id: 4,
    orderId: 'ORD-2026-0015',
    customer: 'Emma Davis',
    email: 'emma@email.com',
    date: '2026-01-14',
    time: '03:30 PM',
    amount: 12500,
    subtotal: 12500,
    shipping: 0,
    tax: 0,
    itemCount: 4,
    paymentMethod: 'Credit Card',
    status: 'Pending',
    notes: 'Awaiting payment confirmation',
    items: [
      { id: 1, name: 'Desk Lamp', quantity: 2, price: 2500 },
      { id: 2, name: 'Bookshelf', quantity: 1, price: 7500 }
    ]
  },
  {
    id: 5,
    orderId: 'ORD-2026-0016',
    customer: 'Robert Brown',
    email: 'robert@email.com',
    date: '2026-01-13',
    time: '09:15 AM',
    amount: 42500,
    subtotal: 42500,
    shipping: 0,
    tax: 0,
    itemCount: 2,
    paymentMethod: 'Cash',
    status: 'Completed',
    notes: 'In-store purchase',
    items: [
      { id: 1, name: 'King Size Bed', quantity: 1, price: 30000 },
      { id: 2, name: 'Mattress', quantity: 1, price: 12500 }
    ]
  },
  {
    id: 6,
    orderId: 'ORD-2026-0017',
    customer: 'Lisa Anderson',
    email: 'lisa@email.com',
    date: '2026-01-13',
    time: '04:45 PM',
    amount: 18500,
    subtotal: 18500,
    shipping: 0,
    tax: 0,
    itemCount: 1,
    paymentMethod: 'Credit Card',
    status: 'Cancelled',
    notes: 'Customer cancelled order',
    items: [
      { id: 1, name: 'Coffee Table', quantity: 1, price: 18500 }
    ]
  },
  {
    id: 7,
    orderId: 'ORD-2026-0018',
    customer: 'David Miller',
    email: 'david@email.com',
    date: '2026-01-12',
    time: '01:20 PM',
    amount: 29500,
    subtotal: 29500,
    shipping: 0,
    tax: 0,
    itemCount: 3,
    paymentMethod: 'PayPal',
    status: 'Refunded',
    notes: 'Refund processed',
    items: [
      { id: 1, name: 'TV Stand', quantity: 1, price: 15000 },
      { id: 2, name: 'Bar Stool', quantity: 2, price: 7250 }
    ]
  },
  {
    id: 8,
    orderId: 'ORD-2026-0019',
    customer: 'Jennifer Lee',
    email: 'jennifer@email.com',
    date: '2026-01-12',
    time: '10:00 AM',
    amount: 15500,
    subtotal: 15500,
    shipping: 0,
    tax: 0,
    itemCount: 2,
    paymentMethod: 'Bank Transfer',
    status: 'On Hold',
    notes: 'Awaiting stock',
    items: [
      { id: 1, name: 'Nightstand', quantity: 2, price: 7750 }
    ]
  }
])

// Filter Options
const statusOptions = ref([
  { name: 'Completed', value: 'completed' },
  { name: 'Processing', value: 'processing' },
  { name: 'Pending', value: 'pending' },
  { name: 'On Hold', value: 'on_hold' },
  { name: 'Cancelled', value: 'cancelled' },
  { name: 'Refunded', value: 'refunded' }
])

const paymentMethodOptions = ref([
  { name: 'Credit Card', value: 'credit_card' },
  { name: 'PayPal', value: 'paypal' },
  { name: 'Bank Transfer', value: 'bank_transfer' },
  { name: 'Cash', value: 'cash' },
  { name: 'GCash', value: 'gcash' },
  { name: 'Maya', value: 'maya' }
])

const customers = ref([
  { id: 1, name: 'John Smith', email: 'john@email.com' },
  { id: 2, name: 'Sarah Johnson', email: 'sarah@email.com' },
  { id: 3, name: 'Mike Wilson', email: 'mike@email.com' },
  { id: 4, name: 'Emma Davis', email: 'emma@email.com' },
  { id: 5, name: 'Robert Brown', email: 'robert@email.com' }
])

// Computed Properties - FIXED VERSION
const filteredTransactions = computed(() => {
  console.log('Computing filtered transactions...')
  console.log('Search term:', searchTerm.value)
  console.log('Original transactions:', transactions.value.length)

  let filtered = [...transactions.value] // Create a copy to avoid mutating original

  // Search filter
  if (searchTerm.value) {
    const term = searchTerm.value.toLowerCase()
    filtered = filtered.filter(t =>
      t.orderId?.toLowerCase().includes(term) ||
      t.customer?.toLowerCase().includes(term) ||
      t.email?.toLowerCase().includes(term)
    )
  }

  // Status filter - FIXED: Check if selectedStatus has values
  if (selectedStatus.value && selectedStatus.value.length > 0) {
    const statuses = selectedStatus.value.map((s: any) => s.name)
    filtered = filtered.filter(t => statuses.includes(t.status))
  }

  // Payment method filter - FIXED: Check if selectedPaymentMethod exists
  if (selectedPaymentMethod.value) {
    filtered = filtered.filter(t => t.paymentMethod === selectedPaymentMethod.value.name)
  }

  // Date range filter - FIXED: Convert dates properly
  if (dateRange.value.start && dateRange.value.end) {
    const startDate = new Date(dateRange.value.start)
    const endDate = new Date(dateRange.value.end)

    filtered = filtered.filter(t => {
      try {
        const transactionDate = new Date(t.date)
        return transactionDate >= startDate && transactionDate <= endDate
      } catch (e) {
        return true // If date parsing fails, include the transaction
      }
    })
  }

  console.log('Filtered transactions:', filtered.length)
  return filtered
})

const todaysRevenue = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  return transactions.value
    .filter(t => t.date === today && t.status === 'Completed')
    .reduce((sum, t) => sum + t.amount, 0)
})

const todayTransactions = computed(() => {
  const today = new Date().toISOString().split('T')[0]
  return transactions.value.filter(t => t.date === today).length
})

const pendingCount = computed(() => {
  return transactions.value.filter(t =>
    ['Pending', 'On Hold', 'Processing'].includes(t.status)
  ).length
})

const monthlyRevenue = computed(() => {
  const currentMonth = new Date().getMonth()
  const currentYear = new Date().getFullYear()

  return transactions.value
    .filter(t => {
      try {
        const date = new Date(t.date)
        return date.getMonth() === currentMonth &&
          date.getFullYear() === currentYear &&
          t.status === 'Completed'
      } catch (e) {
        return false
      }
    })
    .reduce((sum, t) => sum + t.amount, 0)
})

const avgOrderValue = computed(() => {
  const completedOrders = transactions.value.filter(t => t.status === 'Completed')
  if (completedOrders.length === 0) return 0
  const total = completedOrders.reduce((sum, t) => sum + t.amount, 0)
  return Math.round(total / completedOrders.length)
})

// Helper Functions
const formatCurrency = (amount: number) => {
  return amount.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatDate = (dateString: string) => {
  try {
    const date = new Date(dateString)
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    })
  } catch (e) {
    return dateString
  }
}

const getInitials = (name: string) => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'Completed': return 'success'
    case 'Processing': return 'info'
    case 'Pending': return 'warning'
    case 'On Hold': return 'secondary'
    case 'Cancelled': return 'danger'
    case 'Refunded': return 'help'
    default: return 'secondary'
  }
}

const getStatusIcon = (status: string) => {
  switch (status) {
    case 'Completed': return 'pi pi-check-circle'
    case 'Processing': return 'pi pi-spinner'
    case 'Pending': return 'pi pi-clock'
    case 'On Hold': return 'pi pi-pause-circle'
    case 'Cancelled': return 'pi pi-times-circle'
    case 'Refunded': return 'pi pi-undo'
    default: return 'pi pi-question-circle'
  }
}

const getPaymentMethodIcon = (method: string) => {
  switch (method) {
    case 'Credit Card': return 'pi-credit-card'
    case 'PayPal': return 'pi-paypal'
    case 'Bank Transfer': return 'pi-building'
    case 'Cash': return 'pi-money-bill'
    case 'GCash': return 'pi-mobile'
    case 'Maya': return 'pi-wallet'
    default: return 'pi-wallet'
  }
}

const getPaymentMethodClass = (method: string) => {
  switch (method) {
    case 'Credit Card': return 'bg-blue-100 text-blue-800'
    case 'PayPal': return 'bg-indigo-100 text-indigo-800'
    case 'Bank Transfer': return 'bg-green-100 text-green-800'
    case 'Cash': return 'bg-yellow-100 text-yellow-800'
    case 'GCash': return 'bg-purple-100 text-purple-800'
    case 'Maya': return 'bg-pink-100 text-pink-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

// Action Functions
const applyFilters = () => {
  console.log('Filters applied:', {
    searchTerm: searchTerm.value,
    selectedStatus: selectedStatus.value,
    selectedPaymentMethod: selectedPaymentMethod.value,
    dateRange: dateRange.value
  })
}

const clearFilters = () => {
  searchTerm.value = ''
  selectedStatus.value = []
  selectedPaymentMethod.value = null
  dateRange.value = {
    start: new Date(new Date().setDate(new Date().getDate() - 7)),
    end: new Date()
  }
}

const viewTransaction = (transaction: any) => {
  console.log('View transaction:', transaction)
  selectedTransaction.value = transaction
  showTransactionDialog.value = true
}

const editTransaction = (transaction: any) => {
  console.log('Edit transaction:', transaction)
  selectedTransaction.value = transaction
  // Navigate to edit page or show edit dialog
}

const printInvoice = (transaction: any) => {
  console.log('Print invoice:', transaction)
  // Implement print functionality
}

const confirmDelete = (transaction: any) => {
  console.log('Confirm delete:', transaction)
  transactionToDelete.value = transaction
  showDeleteDialog.value = true
}

const deleteTransaction = () => {
  if (transactionToDelete.value) {
    const index = transactions.value.findIndex(t => t.id === transactionToDelete.value.id)
    if (index !== -1) {
      transactions.value.splice(index, 1)
      console.log('Transaction deleted')
    }
  }
  showDeleteDialog.value = false
  transactionToDelete.value = null
}

const addItem = () => {
  newTransaction.value.items.push({
    id: newTransaction.value.items.length + 1,
    product: '',
    quantity: 1,
    price: 0
  })
}

const removeItem = (index: number) => {
  newTransaction.value.items.splice(index, 1)
}

const createTransaction = () => {
  // Generate new transaction
  const newId = transactions.value.length > 0
    ? Math.max(...transactions.value.map(t => t.id)) + 1
    : 1

  const today = new Date().toISOString().split('T')[0]
  const time = new Date().toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })

  const subtotal = newTransaction.value.items.reduce((sum, item) => sum + (item.quantity * item.price), 0)
  const tax = subtotal * (newTransaction.value.taxRate / 100)
  const amount = subtotal + newTransaction.value.shipping + tax

  const customer = customers.value.find(c => c.id === newTransaction.value.customerId)

  const transaction = {
    id: newId,
    orderId: `ORD-${new Date().getFullYear()}-${String(newId).padStart(4, '0')}`,
    customer: customer?.name || 'New Customer',
    email: customer?.email || '',
    date: today,
    time: time,
    amount: amount,
    subtotal: subtotal,
    shipping: newTransaction.value.shipping,
    tax: tax,
    itemCount: newTransaction.value.items.length,
    paymentMethod: newTransaction.value.paymentMethod?.name || 'Cash',
    status: 'Pending',
    notes: newTransaction.value.notes,
    items: [...newTransaction.value.items]
  }

  transactions.value.unshift(transaction)
  showNewTransactionDialog.value = false
  resetNewTransaction()
  console.log('New transaction created:', transaction)
}

const resetNewTransaction = () => {
  newTransaction.value = {
    customerId: null,
    paymentMethod: null,
    items: [
      { id: 1, product: 'Modern Sofa', quantity: 1, price: 25000 }
    ],
    shipping: 500,
    taxRate: 12,
    notes: ''
  }
}

const exportSelected = () => {
  console.log('Export selected:', selectedTransactions.value)
  // Implement export functionality
}

const markAsProcessed = () => {
  selectedTransactions.value.forEach((transaction: any) => {
    const index = transactions.value.findIndex(t => t.id === transaction.id)
    if (index !== -1 && transactions.value[index].status !== 'Completed') {
      transactions.value[index].status = 'Completed'
    }
  })
  selectedTransactions.value = []
  showBatchActions.value = false
}

const deleteSelected = () => {
  selectedTransactions.value.forEach((transaction: any) => {
    const index = transactions.value.findIndex(t => t.id === transaction.id)
    if (index !== -1) {
      transactions.value.splice(index, 1)
    }
  })
  selectedTransactions.value = []
  showBatchActions.value = false
}

const clearSelection = () => {
  selectedTransactions.value = []
  showBatchActions.value = false
}

// Add this to debug
console.log('Transactions component initialized')
console.log('Transactions data:', transactions.value)
</script>