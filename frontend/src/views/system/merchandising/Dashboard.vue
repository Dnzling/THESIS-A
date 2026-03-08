<template>
  <div class="space-y-6">
  
    <!-- Loading State -->
    <div v-if="loading" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Skeleton v-for="i in 8" :key="i" height="120px" class="rounded-lg" />
      </div>
  
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <Skeleton v-for="i in 4" :key="i" height="320px" class="rounded-lg" />
      </div>
    </div>
  
    <!-- Dashboard Content -->
    <div v-else>
      <!-- Stats Cards Row 1 - Products -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card class="hover:shadow-lg transition-shadow cursor-pointer"
          @click="router.push({ name: 'merchandising.products' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Total Products</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ stats.total_products }}</h3>
                <p class="text-xs text-green-600 mt-1">
                  <i class="pi pi-check-circle"></i> {{ stats.active_products }} Active
                </p>
              </div>
              <div class="bg-blue-100 p-4 rounded-full">
                <i class="pi pi-box text-3xl text-blue-600"></i>
              </div>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Categories</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ stats.total_categories }}</h3>
                <p class="text-xs text-gray-500 mt-1">
                  <i class="pi pi-tag"></i> {{ stats.total_subcategories }} Subcategories
                </p>
              </div>
              <div class="bg-purple-100 p-4 rounded-full">
                <i class="pi pi-tags text-3xl text-purple-600"></i>
              </div>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">In Stock</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ stats.in_stock_products }}</h3>
                <p class="text-xs text-yellow-600 mt-1">
                  <i class="pi pi-exclamation-triangle"></i> {{ stats.low_stock_products }} Low Stock
                </p>
              </div>
              <div class="bg-green-100 p-4 rounded-full">
                <i class="pi pi-check-circle text-3xl text-green-600"></i>
              </div>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Total Value</p>
                <h3 class="text-2xl font-bold text-gray-900">₱{{ formatPrice(stats.total_inventory_value) }}</h3>
                <p class="text-xs text-gray-600 mt-1">
                  <i class="pi pi-dollar"></i> Inventory value
                </p>
              </div>
              <div class="bg-yellow-100 p-4 rounded-full">
                <i class="pi pi-dollar text-3xl text-yellow-600"></i>
              </div>
            </div>
          </template>
        </Card>
      </div>
  
      <!-- Stats Cards Row 2 - Assets & Media -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <Card class="hover:shadow-lg transition-shadow cursor-pointer"
          @click="router.push({ name: 'merchandising.3d-gallery' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">3D Models</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ stats.total_3d_models }}</h3>
                <p class="text-xs text-indigo-600 mt-1">
                  <i class="pi pi-cube"></i> {{ formatFileSize(stats.total_3d_size) }}
                </p>
              </div>
              <div class="bg-indigo-100 p-4 rounded-full">
                <i class="pi pi-cube text-3xl text-indigo-600"></i>
              </div>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow cursor-pointer"
          @click="router.push({ name: 'merchandising.assets' })">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Images</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ stats.total_images }}</h3>
                <p class="text-xs text-blue-600 mt-1">
                  <i class="pi pi-image"></i> {{ formatFileSize(stats.total_image_size) }}
                </p>
              </div>
              <div class="bg-blue-100 p-4 rounded-full">
                <i class="pi pi-image text-3xl text-blue-600"></i>
              </div>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Variations</p>
                <h3 class="text-3xl font-bold text-gray-900">{{ stats.total_variations }}</h3>
                <p class="text-xs text-purple-600 mt-1">
                  <i class="pi pi-th-large"></i> {{ stats.active_variations }} Active
                </p>
              </div>
              <div class="bg-purple-100 p-4 rounded-full">
                <i class="pi pi-th-large text-3xl text-purple-600"></i>
              </div>
            </div>
          </template>
        </Card>
  
        <Card class="hover:shadow-lg transition-shadow">
          <template #content>
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600 mb-1">Avg Price</p>
                <h3 class="text-2xl font-bold text-gray-900">₱{{ formatPrice(stats.average_price) }}</h3>
                <p class="text-xs text-gray-600 mt-1">
                  <i class="pi pi-chart-line"></i> Per product
                </p>
              </div>
              <div class="bg-orange-100 p-4 rounded-full">
                <i class="pi pi-chart-line text-3xl text-orange-600"></i>
              </div>
            </div>
          </template>
        </Card>
      </div>
  
      <!-- Charts Row 1 - Category & Stock -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Products by Category Chart -->
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-chart-pie text-purple-600"></i>
              <span>Products by Category</span>
            </div>
          </template>
          <template #content>
            <Chart v-if="categoryChartData.labels.length > 0" type="doughnut" :data="categoryChartData"
              :options="pieChartOptions" class="h-80" />
            <div v-else class="h-80 flex items-center justify-center text-gray-500">
              <div class="text-center">
                <i class="pi pi-chart-pie text-6xl text-gray-300 mb-3 block"></i>
                <p>No category data available</p>
              </div>
            </div>
          </template>
        </Card>
  
        <!-- Stock Status Chart -->
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-chart-bar text-green-600"></i>
              <span>Stock Status Distribution</span>
            </div>
          </template>
          <template #content>
            <Chart v-if="stockChartData.labels.length > 0" type="bar" :data="stockChartData" :options="barChartOptions"
              class="h-80" />
            <div v-else class="h-80 flex items-center justify-center text-gray-500">
              <div class="text-center">
                <i class="pi pi-chart-bar text-6xl text-gray-300 mb-3 block"></i>
                <p>No stock data available</p>
              </div>
            </div>
          </template>
        </Card>
      </div>
  
      <!-- Charts Row 2 - Price & Features -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Price Range Distribution -->
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-dollar text-yellow-600"></i>
              <span>Price Range Distribution</span>
            </div>
          </template>
          <template #content>
            <Chart v-if="priceRangeChartData.labels.length > 0" type="bar" :data="priceRangeChartData"
              :options="barChartOptions" class="h-80" />
            <div v-else class="h-80 flex items-center justify-center text-gray-500">
              <div class="text-center">
                <i class="pi pi-dollar text-6xl text-gray-300 mb-3 block"></i>
                <p>No price data available</p>
              </div>
            </div>
          </template>
        </Card>
  
        <!-- Product Features -->
        <Card>
          <template #title>
            <div class="flex items-center gap-2">
              <i class="pi pi-star text-blue-600"></i>
              <span>Product Features</span>
            </div>
          </template>
          <template #content>
            <Chart v-if="featuresChartData.labels.length > 0" type="polarArea" :data="featuresChartData"
              :options="polarChartOptions" class="h-80" />
            <div v-else class="h-80 flex items-center justify-center text-gray-500">
              <div class="text-center">
                <i class="pi pi-star text-6xl text-gray-300 mb-3 block"></i>
                <p>No feature data available</p>
              </div>
            </div>
          </template>
        </Card>
      </div>
  
      <!-- Recent Activity & Quick Info -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Products (1/3) -->
        <Card>
          <template #title>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i class="pi pi-clock text-blue-600"></i>
                <span>Recent Products</span>
              </div>
              <Button label="View All" text size="small" @click="router.push({ name: 'merchandising.products' })" />
            </div>
          </template>
          <template #content>
            <div v-if="recentProducts.length === 0" class="text-center py-8 text-gray-500">
              <i class="pi pi-inbox text-4xl mb-2 block"></i>
              <p>No recent products</p>
            </div>
            <div v-else class="space-y-3">
              <div v-for="product in recentProducts" :key="product.id"
                class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                @click="router.push({ name: 'merchandising.products.view', params: { id: product.id } })">
                <div class="w-10 h-10 bg-linear-to-br from-blue-100 to-blue-200 rounded flex items-center justify-center">
                  <i class="pi pi-box text-blue-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-sm text-gray-900 truncate">{{ product.product_name }}</p>
                  <p class="text-xs text-gray-500">{{ product.sku }}</p>
                </div>
                <Tag :value="product.stock_status" :severity="getStockSeverity(product.stock_status)" size="small" />
              </div>
            </div>
          </template>
        </Card>
  
        <!-- Activity Log (2/3) -->
        <Card class="lg:col-span-2">
          <template #title>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <i class="pi pi-history text-purple-600"></i>
                <span>Recent Activity</span>
              </div>
              <Button icon="pi pi-refresh" text rounded size="small" @click="loadActivityLog" />
            </div>
          </template>
          <template #content>
            <div v-if="loadingActivity" class="space-y-3">
              <Skeleton v-for="i in 5" :key="i" height="60px" class="rounded-lg" />
            </div>
            <div v-else-if="activityLog.length === 0" class="text-center py-8 text-gray-500">
              <i class="pi pi-inbox text-4xl mb-2 block"></i>
              <p>No recent activity</p>
            </div>
            <Timeline v-else :value="activityLog" class="customized-timeline">
              <template #marker="{ item }">
                <div :class="['flex items-center justify-center w-8 h-8 rounded-full', getActivityColor(item.action)]">
                  <i :class="['text-white text-sm', getActivityIcon(item.action)]"></i>
                </div>
              </template>
              <template #content="{ item }">
                <div class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                  <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                      <p class="font-semibold text-sm text-gray-900">{{ item.description }}</p>
                      <p class="text-xs text-gray-600 mt-1">{{ item.details }}</p>
                      <div class="flex items-center gap-2 mt-2">
                        <span class="text-xs text-gray-500">
                          <i class="pi pi-user text-xs"></i> {{ item.user }}
                        </span>
                        <span class="text-xs text-gray-400">•</span>
                        <span class="text-xs text-gray-500">{{ formatRelativeTime(item.created_at) }}</span>
                      </div>
                    </div>
                    <Tag :value="item.action" :severity="getActivitySeverity(item.action)" size="small" />
                  </div>
                </div>
              </template>
            </Timeline>
          </template>
        </Card>
      </div>
  
      <!-- Low Stock Alert -->
      <Card v-if="lowStockProducts.length > 0" class="mt-6 border-l-4 border-yellow-500">
        <template #title>
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <i class="pi pi-exclamation-triangle text-yellow-600"></i>
              <span>Low Stock Alert</span>
            </div>
            <Badge :value="lowStockProducts.length" severity="warning" size="large" />
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <div v-for="product in lowStockProducts" :key="product.id"
              class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition-colors cursor-pointer border border-yellow-200"
              @click="router.push({ name: 'merchandising.products.edit', params: { id: product.id } })">
              <div class="w-12 h-12 bg-yellow-200 rounded flex items-center justify-center">
                <i class="pi pi-exclamation-triangle text-yellow-600"></i>
              </div>
              <div class="flex-1">
                <p class="font-semibold text-sm text-gray-900">{{ product.product_name }}</p>
                <p class="text-xs text-gray-500">SKU: {{ product.sku }}</p>
              </div>
              <Tag value="Low Stock" severity="warning" />
            </div>
          </div>
        </template>
      </Card>
  
      <!-- Quick Actions -->
      <Card class="mt-6">
        <template #title>
          <div class="flex items-center gap-2">
            <i class="pi pi-bolt text-yellow-600"></i>
            <span>Quick Actions</span>
          </div>
        </template>
        <template #content>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <Button label="Add Product" icon="pi pi-plus" class="w-full"
              @click="router.push({ name: 'merchandising.products.create' })" />
            <Button label="View Products" icon="pi pi-list" severity="secondary" class="w-full"
              @click="router.push({ name: 'merchandising.products' })" />
            <Button label="3D Gallery" icon="pi pi-cube" severity="info" class="w-full"
              @click="router.push({ name: 'merchandising.3d-gallery' })" />
            <Button label="Upload Assets" icon="pi pi-cloud-upload" severity="success" class="w-full"
              @click="router.push({ name: 'merchandising.assets.upload' })" />
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useToast } from 'primevue/usetoast'
import merchandisingService, { type Product } from '../../../services/merchandising.service'


import Card from 'primevue/card'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Badge from 'primevue/badge'
import Chart from 'primevue/chart'
import Timeline from 'primevue/timeline'
import { useRouter } from 'vue-router'

const router = useRouter()
const toast = useToast()

const loading = ref(true)
const loadingActivity = ref(false)

// ✅ Initialize with default values to prevent undefined errors
const stats = ref({
  total_products: 0,
  active_products: 0,
  inactive_products: 0,
  total_categories: 0,
  total_subcategories: 0,
  in_stock_products: 0,
  low_stock_products: 0,
  out_of_stock_products: 0,
  total_3d_models: 0,
  total_images: 0,
  total_variations: 0,
  active_variations: 0,
  total_3d_size: 0,
  total_image_size: 0,
  total_inventory_value: 0,
  average_price: 0,
  featured_count: 0,
  new_arrival_count: 0,
  bestseller_count: 0,
  products_by_category: [],
  stock_status_distribution: [],
  price_range_distribution: []
})

const recentProducts = ref<Product[]>([])
const lowStockProducts = ref<Product[]>([])
const activityLog = ref([])

// Chart Data
const categoryChartData = computed(() => {
  if (!stats.value.products_by_category || stats.value.products_by_category.length === 0) {
    return { labels: [], datasets: [] }
  }

  const labels = stats.value.products_by_category.map((item: any) => item.category_name || 'Uncategorized')
  const data = stats.value.products_by_category.map((item: any) => item.count)

  return {
    labels,
    datasets: [
      {
        data,
        backgroundColor: ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#6b7280'],
        hoverBackgroundColor: ['#4f46e5', '#7c3aed', '#db2777', '#d97706', '#059669', '#2563eb', '#dc2626', '#4b5563']
      }
    ]
  }
})

const stockChartData = computed(() => {
  if (!stats.value.stock_status_distribution || stats.value.stock_status_distribution.length === 0) {
    return { labels: [], datasets: [] }
  }

  const distribution = stats.value.stock_status_distribution

  return {
    labels: distribution.map((item: any) => item.stock_status),
    datasets: [
      {
        label: 'Products',
        data: distribution.map((item: any) => item.count),
        backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#6b7280'],
        borderColor: ['#059669', '#d97706', '#dc2626', '#4b5563'],
        borderWidth: 2,
        borderRadius: 8
      }
    ]
  }
})

const priceRangeChartData = computed(() => {
  if (!stats.value.price_range_distribution || stats.value.price_range_distribution.length === 0) {
    return { labels: [], datasets: [] }
  }

  const distribution = stats.value.price_range_distribution

  return {
    labels: distribution.map((item: any) => item.range),
    datasets: [
      {
        label: 'Products',
        data: distribution.map((item: any) => item.count),
        backgroundColor: '#f59e0b',
        borderColor: '#d97706',
        borderWidth: 2,
        borderRadius: 8
      }
    ]
  }
})

const featuresChartData = computed(() => {
  return {
    labels: ['Featured', 'New Arrivals', 'Bestsellers', 'With 3D', 'With Images'],
    datasets: [
      {
        data: [
          stats.value.featured_count || 0,
          stats.value.new_arrival_count || 0,
          stats.value.bestseller_count || 0,
          stats.value.total_3d_models || 0,
          stats.value.total_images || 0
        ],
        backgroundColor: ['#6366f1', '#10b981', '#f59e0b', '#8b5cf6', '#3b82f6'],
        borderColor: '#fff',
        borderWidth: 2
      }
    ]
  }
})

const pieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
      labels: {
        usePointStyle: true,
        padding: 15
      }
    }
  }
}

const barChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        stepSize: 1
      }
    }
  }
}

const polarChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom'
    }
  }
}

// Methods
const loadDashboardStats = async () => {
  loading.value = true
  try {
    const response = await merchandisingService.getDashboardStats()
    stats.value = { ...stats.value, ...response.data }

    // Load recent products
    const productsResponse = await merchandisingService.getProducts({
      per_page: 5,
      sort_by: 'created_at',
      sort_order: 'desc'
    })
    recentProducts.value = productsResponse.data.data || []

    // Load low stock products
    const lowStockResponse = await merchandisingService.getProducts({
      stock_status: 'Low Stock',
      per_page: 6
    })
    lowStockProducts.value = lowStockResponse.data.data || []

  } catch (error: any) {
    console.error('Failed to load dashboard stats:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load dashboard statistics',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadActivityLog = async () => {
  loadingActivity.value = true
  try {
    const response = await merchandisingService.getActivityLog({ per_page: 10 })
    activityLog.value = response.data.data || []
  } catch (error: any) {
    console.error('Failed to load activity log:', error)
    // Don't show error toast for activity log, just keep it empty
    activityLog.value = []
  } finally {
    loadingActivity.value = false
  }
}

const getStockSeverity = (status: string) => {
  const severities: Record<string, string> = {
    'In Stock': 'success',
    'Low Stock': 'warning',
    'Out of Stock': 'danger',
    'Pre-order': 'info'
  }
  return severities[status] || 'secondary'
}

const getActivityColor = (action: string) => {
  const colors: Record<string, string> = {
    'created': 'bg-green-500',
    'updated': 'bg-blue-500',
    'deleted': 'bg-red-500',
    'uploaded': 'bg-purple-500',
    'price_changed': 'bg-yellow-500'
  }
  return colors[action] || 'bg-gray-500'
}

const getActivityIcon = (action: string) => {
  const icons: Record<string, string> = {
    'created': 'pi pi-plus',
    'updated': 'pi pi-pencil',
    'deleted': 'pi pi-trash',
    'uploaded': 'pi pi-upload',
    'price_changed': 'pi pi-dollar'
  }
  return icons[action] || 'pi pi-info-circle'
}

const getActivitySeverity = (action: string) => {
  const severities: Record<string, string> = {
    'created': 'success',
    'updated': 'info',
    'deleted': 'danger',
    'uploaded': 'secondary',
    'price_changed': 'warning'
  }
  return severities[action] || 'secondary'
}

const formatPrice = (price: number) => {
  if (!price || isNaN(price)) return '0.00'
  return new Intl.NumberFormat('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

const formatFileSize = (bytes: number) => {
  if (!bytes || bytes === 0 || isNaN(bytes)) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const formatRelativeTime = (dateString: string) => {
  if (!dateString) return 'N/A'

  const date = new Date(dateString)
  const now = new Date()
  const diff = now.getTime() - date.getTime()

  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'Just now'
  if (minutes < 60) return `${minutes}m ago`
  if (hours < 24) return `${hours}h ago`
  if (days < 7) return `${days}d ago`

  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

onMounted(() => {
  loadDashboardStats()
  loadActivityLog()
})
</script>