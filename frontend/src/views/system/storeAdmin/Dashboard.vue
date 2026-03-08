<!-- views/system/Dashboard.vue -->
<template>
  <div class="space-y-6">
    <div v-if="!hasStore" class=" bg-gray-50 flex items-center justify-center p-6">
      <div class="text-center max-w-md">
  
        <!-- Title -->
        <h1 class="text-3xl font-bold text-gray-800 mb-4">No Store Created Yet</h1>
  
        <!-- Description -->
        <p class="text-gray-600 mb-8">
          Get started by creating your furniture store. Set up your inventory, manage products, and start selling.
        </p>
  
        <!-- Action Button -->
        <Button @click="goToStoreRegistration" severity="info" class="inline-flex items-center gap-3">
          <i class="pi pi-plus-circle"></i>
          Create Your Store
        </Button>
  
      </div>
    </div>
    <div v-else-if="!hasProduct" class=" bg-gray-50 flex items-center justify-center p-6">
      <div class="text-center max-w-md">
  
        <!-- Title -->
        <h1 class="text-3xl font-bold text-gray-800 mb-4">No Product Created Yet</h1>
  
        <!-- Description -->
        <p class="text-gray-600 mb-8">
          Get started by creating your furniture store. Set up your inventory, manage products, and start selling.
        </p>
  
        <!-- Action Button -->
        <Button @click="goToProductRegistration" severity="success" class="inline-flex items-center gap-3">
          <i class="pi pi-plus-circle"></i>
          Create Your Product
        </Button>
      </div>
    </div>
  
    <div v-else>
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-5">
        <!-- Total Products -->
        <div class="bg-white shadow rounded-xl p-6">
          <div class="flex items-center justify-between">
            <h6 class="text-sm font-semibold text-gray-500">Total Products</h6>
            <i class="pi pi-box text-blue-500 text-lg"></i>
          </div>
          <p class="text-3xl font-bold text-gray-800 my-2">{{ totalProducts }}</p>
          <p class="text-sm text-gray-500">+12% from last month</p>
        </div>
  
        <!-- Monthly Revenue -->
        <div class="bg-white shadow rounded-xl p-6">
          <div class="flex items-center justify-between">
            <h6 class="text-sm font-semibold text-gray-500">Monthly Revenue</h6>
            <i class="pi pi-wallet text-green-500 text-lg"></i>
          </div>
          <p class="text-3xl font-bold text-gray-800 my-2">₱{{ monthlyRevenue.toLocaleString() }}</p>
          <p class="text-sm text-gray-500">+15.2% from last month</p>
        </div>
  
        <!-- Active Orders -->
        <div class="bg-white shadow rounded-xl p-6">
          <div class="flex items-center justify-between">
            <h6 class="text-sm font-semibold text-gray-500">Active Orders</h6>
            <i class="pi pi-shopping-cart text-orange-500 text-lg"></i>
          </div>
          <p class="text-3xl font-bold text-gray-800 my-2">{{ activeOrders }}</p>
          <p class="text-sm text-gray-500">12 pending shipment</p>
        </div>
  
        <!-- Low Stock -->
        <div class="bg-white shadow rounded-xl p-6">
          <div class="flex items-center justify-between">
            <h6 class="text-sm font-semibold text-gray-500">Low Stock Items</h6>
            <i class="pi pi-exclamation-triangle text-red-500 text-lg"></i>
          </div>
          <p class="text-3xl font-bold text-gray-800 my-2">{{ lowStockItems }}</p>
          <p class="text-sm text-gray-500">Need restocking</p>
        </div>
      </div>
  
      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-5">
        <!-- Revenue Chart -->
        <div class="bg-white shadow rounded-xl p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Revenue Overview</h3>
            <div class="flex space-x-2">
              <Button @click="setChartView('daily')" :severity="chartView === 'daily' ? 'primary' : 'secondary'"
                size="small">
                Daily
              </Button>
              <Button @click="setChartView('monthly')" :severity="chartView === 'monthly' ? 'primary' : 'secondary'"
                size="small">
                Monthly
              </Button>
              <Button @click="setChartView('yearly')" :severity="chartView === 'yearly' ? 'primary' : 'secondary'"
                size="small">
                Yearly
              </Button>
            </div>
          </div>
          <div class="h-64">
            <canvas ref="revenueChartCanvas"></canvas>
          </div>
          <div class="mt-4 text-center text-sm text-gray-500">
            <p>Total Revenue: ₱{{ totalRevenue.toLocaleString() }}</p>
          </div>
        </div>
  
        <!-- Top Products -->
        <div class="bg-white shadow rounded-xl p-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Selling Products</h3>
          <div class="space-y-3">
            <div v-for="product in topProducts" :key="product.id"
              class="flex items-center justify-between p-3 hover:bg-gray-50 rounded">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-100 rounded flex items-center justify-center">
                  <i class="pi pi-box text-blue-600"></i>
                </div>
                <div>
                  <p class="font-medium">{{ product.name }}</p>
                  <p class="text-sm text-gray-500">{{ product.category }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="font-bold">₱{{ product.revenue.toLocaleString() }}</p>
                <p class="text-sm text-gray-500">{{ product.sold }} sold</p>
              </div>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Inventory Status -->
      <div class="bg-white shadow rounded-xl p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-lg font-semibold text-gray-800">Inventory Status</h3>
          <div class="flex items-center space-x-2">
            <i class="pi pi-search" />
            <InputText v-model="searchTerm" placeholder="Search inventory..." class="w-64" />
          </div>
        </div>
  
        <DataTable :value="filteredInventoryItems" sortMode="multiple" tableStyle="min-width: 50rem" paginator :rows="10"
          :rowsPerPageOptions="[5, 10, 20, 50]"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords} products">
          <Column field="productId" header="Product ID" sortable style="width: 15%">
            <template #body="slotProps">
              #{{ slotProps.data.productId }}
            </template>
          </Column>
  
          <Column field="productName" header="Product Name" sortable style="width: 25%">
            <template #body="slotProps">
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-blue-100 rounded flex items-center justify-center">
                  <i class="pi pi-box text-blue-600 text-sm"></i>
                </div>
                <span>{{ slotProps.data.productName }}</span>
              </div>
            </template>
          </Column>
  
          <Column field="category" header="Category" sortable style="width: 20%"></Column>
  
          <Column field="currentStock" header="Quantity" sortable style="width: 15%">
            <template #body="slotProps">
              <div class="flex items-center">
                <span class="font-medium mr-2">{{ slotProps.data.currentStock }}</span>
                <Tag :value="slotProps.data.status" :severity="getStatusSeverity(slotProps.data.status)" size="small" />
              </div>
            </template>
          </Column>
  
          <Column field="status" header="Status" sortable style="width: 15%">
            <template #body="slotProps">
              <div class="text-center">
                <div :class="`w-3 h-3 rounded-full mx-auto mb-1 ${getStatusDotColor(slotProps.data.status)}`"></div>
                <span class="text-sm">{{ slotProps.data.status }}</span>
              </div>
            </template>
          </Column>
  
          <Column header="Actions" style="width: 10%">
            <template #body="slotProps">
              <div class="flex space-x-1">
                <Button icon="pi pi-eye" size="small" text rounded severity="secondary"
                  @click="viewInventoryItem(slotProps.data)" />
                <Button icon="pi pi-pencil" size="small" text rounded severity="secondary"
                  @click="editInventoryItem(slotProps.data)" />
              </div>
            </template>
          </Column>
        </DataTable>
  
        <div class="mt-6 text-sm text-gray-500">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="flex items-center">
                <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                <span>In Stock: {{ getStatusCount('In Stock') }}</span>
              </div>
              <div class="flex items-center">
                <div class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></div>
                <span>Low Stock: {{ getStatusCount('Low Stock') }}</span>
              </div>
              <div class="flex items-center">
                <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                <span>Out of Stock: {{ getStatusCount('Out of Stock') }}</span>
              </div>
            </div>
            <div class="text-right">
              <span class="font-medium">Total Items: {{ inventoryItems.length }}</span>
            </div>
          </div>
        </div>
      </div>
  
      <!-- Recent Activity -->
      <div class="bg-white shadow rounded-xl p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activity</h3>
        <div class="space-y-3">
          <div v-for="activity in recentActivities" :key="activity.id"
            class="flex items-center space-x-3 p-3 hover:bg-gray-50 rounded">
            <div :class="`w-8 h-8 rounded-full flex items-center justify-center ${activity.bgColor}`">
              <i :class="`${activity.icon} ${activity.iconColor}`"></i>
            </div>
            <div class="flex-1">
              <p class="font-medium">{{ activity.description }}</p>
              <p class="text-sm text-gray-500">{{ activity.time }}</p>
            </div>
            <span :class="`px-2 py-1 rounded text-xs font-medium ${activity.statusColor}`">
              {{ activity.status }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, onUnmounted, watch, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Chart, registerables } from 'chart.js'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import { useAuthStore } from '../../../stores/auth'

// Register Chart.js components
Chart.register(...registerables)

const authStore = useAuthStore();
const hasStore = ref<boolean>(!!authStore.user.store?.id)
const hasProduct = ref<boolean>(false)
const router = useRouter()

// Chart references
const revenueChartCanvas = ref<HTMLCanvasElement | null>(null)
let revenueChart: Chart | null = null

// Chart view state
const chartView = ref<'daily' | 'monthly' | 'yearly'>('monthly')
const totalRevenue = ref(0)

// Dashboard stats
const totalProducts = ref(1248)
const monthlyRevenue = ref(325840.25)
const activeOrders = ref(48)
const lowStockItems = ref(16)

// Static revenue data
const revenueData = {
  daily: {
    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    data: [45200, 52000, 48300, 61000, 72500, 89500, 67200]
  },
  monthly: {
    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    data: [285000, 312000, 295000, 325840, 342000, 367500, 389200, 375000, 402000, 418500, 435000, 462000]
  },
  yearly: {
    labels: ['2020', '2021', '2022', '2023', '2024'],
    data: [2850000, 3125000, 3678000, 4250000, 4620000]
  }
}

const topProducts = ref([
  { id: 1, name: 'Modern Sofa Set', category: 'Living Room', revenue: 125000, sold: 25 },
  { id: 2, name: 'Wooden Dining Table', category: 'Dining Room', revenue: 98750, sold: 19 },
  { id: 3, name: 'Office Chair', category: 'Office', revenue: 76500, sold: 51 },
  { id: 4, name: 'King Size Bed', category: 'Bedroom', revenue: 64200, sold: 12 },
  { id: 5, name: 'Bookshelf', category: 'Study', revenue: 53800, sold: 22 }
])

const recentActivities = ref([
  {
    id: 1,
    description: 'New order #ORD-2024-0012 placed',
    time: '10 minutes ago',
    icon: 'pi pi-shopping-cart',
    iconColor: 'text-blue-600',
    bgColor: 'bg-blue-100',
    status: 'Pending',
    statusColor: 'bg-yellow-100 text-yellow-800'
  },
  {
    id: 2,
    description: 'Product "Modern Sofa" stock updated',
    time: '30 minutes ago',
    icon: 'pi pi-box',
    iconColor: 'text-green-600',
    bgColor: 'bg-green-100',
    status: 'Updated',
    statusColor: 'bg-green-100 text-green-800'
  },
  {
    id: 3,
    description: 'Monthly sales report generated',
    time: '2 hours ago',
    icon: 'pi pi-file-pdf',
    iconColor: 'text-purple-600',
    bgColor: 'bg-purple-100',
    status: 'Completed',
    statusColor: 'bg-blue-100 text-blue-800'
  },
  {
    id: 4,
    description: 'Supplier payment processed',
    time: '5 hours ago',
    icon: 'pi pi-dollar',
    iconColor: 'text-green-600',
    bgColor: 'bg-green-100',
    status: 'Paid',
    statusColor: 'bg-green-100 text-green-800'
  }
])

const goToStoreRegistration = () => {
  router.push('/system/store/verification')
}

const goToProductRegistration = () => {
  router.push('/system/productRegistration')
}

// Initialize and render chart
const initChart = () => {
  if (!revenueChartCanvas.value) return

  // Destroy existing chart if it exists
  if (revenueChart) {
    revenueChart.destroy()
  }

  const ctx = revenueChartCanvas.value.getContext('2d')
  if (!ctx) return

  const currentData = revenueData[chartView.value]

  // Calculate total revenue for current view
  totalRevenue.value = currentData.data.reduce((sum, value) => sum + value, 0)

  revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: currentData.labels,
      datasets: [{
        label: 'Revenue (₱)',
        data: currentData.data,
        borderColor: '#4f46e5',
        backgroundColor: 'rgba(79, 70, 229, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#4f46e5',
        pointBorderColor: '#ffffff',
        pointBorderWidth: 2,
        pointRadius: 5,
        pointHoverRadius: 7
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          mode: 'index',
          intersect: false,
          callbacks: {
            label: function (context) {
              return `₱${context.parsed.y.toLocaleString()}`
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          ticks: {
            callback: function (value) {
              return '₱' + value.toLocaleString()
            }
          }
        },
        x: {
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          }
        }
      }
    }
  })
}

// Set chart view and update
const setChartView = (view: 'daily' | 'monthly' | 'yearly') => {
  chartView.value = view
}

// Watch for chart view changes
watch(chartView, () => {
  initChart()
})

onMounted(() => {
  // const storeExists = localStorage.getItem('hasStore');
  // const productExists = localStorage.getItem('hasProduct')
  // hasStore.value = storeExists === 'true'
  // hasProduct.value = productExists === 'true'

  // Initialize chart after component is mounted
  setTimeout(() => {
    initChart()
  }, 100)
})

onUnmounted(() => {
  // Clean up chart instance
  if (revenueChart) {
    revenueChart.destroy()
  }
})

// Add these inside your script setup
const searchTerm = ref('')

// Static inventory data with productId field
const inventoryItems = ref([
  { productId: 'FUR-001', productName: 'Modern Sofa Set', category: 'Living Room', currentStock: 45, maxStock: 100, status: 'In Stock' },
  { productId: 'FUR-002', productName: 'Wooden Dining Table', category: 'Dining Room', currentStock: 12, maxStock: 50, status: 'In Stock' },
  { productId: 'FUR-003', productName: 'Office Chair', category: 'Office', currentStock: 5, maxStock: 30, status: 'Low Stock' },
  { productId: 'FUR-004', productName: 'King Size Bed', category: 'Bedroom', currentStock: 8, maxStock: 25, status: 'In Stock' },
  { productId: 'FUR-005', productName: 'Bookshelf', category: 'Study', currentStock: 22, maxStock: 40, status: 'In Stock' },
  { productId: 'FUR-006', productName: 'Coffee Table', category: 'Living Room', currentStock: 3, maxStock: 20, status: 'Low Stock' },
  { productId: 'FUR-007', productName: 'Dining Chair Set', category: 'Dining Room', currentStock: 0, maxStock: 60, status: 'Out of Stock' },
  { productId: 'FUR-008', productName: 'Desk Lamp', category: 'Office', currentStock: 18, maxStock: 35, status: 'In Stock' },
  { productId: 'FUR-009', productName: 'Wardrobe', category: 'Bedroom', currentStock: 7, maxStock: 15, status: 'Low Stock' },
  { productId: 'FUR-010', productName: 'TV Stand', category: 'Living Room', currentStock: 25, maxStock: 45, status: 'In Stock' },
  { productId: 'FUR-011', productName: 'Bar Stool', category: 'Dining Room', currentStock: 30, maxStock: 50, status: 'In Stock' },
  { productId: 'FUR-012', productName: 'Filing Cabinet', category: 'Office', currentStock: 2, maxStock: 10, status: 'Low Stock' },
  { productId: 'FUR-013', productName: 'Nightstand', category: 'Bedroom', currentStock: 15, maxStock: 25, status: 'In Stock' },
  { productId: 'FUR-014', productName: 'Recliner Chair', category: 'Living Room', currentStock: 0, maxStock: 12, status: 'Out of Stock' },
  { productId: 'FUR-015', productName: 'Buffet Table', category: 'Dining Room', currentStock: 9, maxStock: 18, status: 'In Stock' },
])

// Filtered items computed property
const filteredInventoryItems = computed(() => {
  if (!searchTerm.value) return inventoryItems.value

  const term = searchTerm.value.toLowerCase()
  return inventoryItems.value.filter(item =>
    item.productId.toLowerCase().includes(term) ||
    item.productName.toLowerCase().includes(term) ||
    item.category.toLowerCase().includes(term) ||
    item.status.toLowerCase().includes(term)
  )
})

// Helper functions
const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'In Stock': return 'success'
    case 'Low Stock': return 'warning'
    case 'Out of Stock': return 'danger'
    default: return 'info'
  }
}

const getStatusDotColor = (status: string) => {
  switch (status) {
    case 'In Stock': return 'bg-green-500'
    case 'Low Stock': return 'bg-yellow-500'
    case 'Out of Stock': return 'bg-red-500'
    default: return 'bg-gray-400'
  }
}

const getStatusCount = (status: string) => {
  return inventoryItems.value.filter(item => item.status === status).length
}

const viewInventoryItem = (item: any) => {
  // Implement view logic
  console.log('View item:', item)
}

const editInventoryItem = (item: any) => {
  // Implement edit logic
  console.log('Edit item:', item)
}
</script>