<template>
  <TopNav/>
  <div class="min-h-screen bg-linear-to-b from-white to-gray-50">
    <!-- Hero Section -->
    <section class="pt-20 pb-32 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <!-- Left Column: Content -->
          <div class="text-center lg:text-left">
            <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold mb-6">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
              </svg>
              Revolutionizing Furniture Management
            </div>
            
            <h1 class="text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
              Streamline Your
              <span class="text-transparent bg-clip-text bg-linear-to-r from-blue-600 to-purple-600">
                Furniture Operations
              </span>
            </h1>
            
            <p class="text-xl text-gray-600 mb-10 max-w-2xl">
              The all-in-one platform to manage, track, and optimize your furniture inventory, 
              orders, and deliveries. Perfect for manufacturers, retailers, and logistics companies.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4">
              <router-link 
                to="/pricing"
                class="px-8 py-4 bg-linear-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1"
              >
                <div class="flex items-center justify-center">
                  <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                  </svg>
                  Start Free Trial
                </div>
                <p class="text-sm font-normal mt-1 opacity-90">14-day trial • No credit card</p>
              </router-link>
              
              <button 
                @click="scrollToFeatures"
                class="px-8 py-4 bg-white text-gray-700 font-semibold rounded-xl border-2 border-gray-200 hover:border-blue-300 hover:shadow-lg transition-all flex items-center justify-center"
              >
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Watch Demo
              </button>
            </div>
            
            <!-- Trust badges -->
            <div class="mt-16">
              <p class="text-gray-500 text-sm mb-6">Trusted by industry leaders</p>
              <div class="flex flex-wrap gap-8 items-center justify-center lg:justify-start">
                <div class="text-2xl font-bold text-gray-300 opacity-70">FURNTECH</div>
                <div class="text-2xl font-bold text-gray-300 opacity-70">WOODWORKS</div>
                <div class="text-2xl font-bold text-gray-300 opacity-70">FURNISH</div>
                <div class="text-2xl font-bold text-gray-300 opacity-70">+25 others</div>
              </div>
            </div>
          </div>
          
          <!-- Right Column: Dashboard Preview -->
          <div class="relative">
            <div class="relative rounded-2xl overflow-hidden shadow-2xl">
              <!-- Dashboard Preview -->
              <div class="bg-linear-to-br from-gray-900 to-gray-800 p-6">
                <!-- Dashboard Header -->
                <div class="flex items-center justify-between mb-8">
                  <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                    <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                    <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                    <div class="text-white text-sm font-medium ml-4">Furniture Dashboard</div>
                  </div>
                  <div class="text-gray-400 text-sm">Today</div>
                </div>
                
                <!-- Dashboard Content -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                  <div class="bg-gray-800 rounded-xl p-4">
                    <div class="text-blue-400 text-sm mb-2">Total Inventory</div>
                    <div class="text-2xl font-bold text-white">1,247</div>
                    <div class="text-green-400 text-xs">↑ 12% this month</div>
                  </div>
                  <div class="bg-gray-800 rounded-xl p-4">
                    <div class="text-purple-400 text-sm mb-2">Active Orders</div>
                    <div class="text-2xl font-bold text-white">89</div>
                    <div class="text-green-400 text-xs">↑ 8% this week</div>
                  </div>
                </div>
                
                <!-- Furniture Items Grid -->
                <div class="grid grid-cols-3 gap-3">
                  <div v-for="item in furnitureItems" :key="item.id" class="bg-gray-800 rounded-lg p-3">
                    <div class="flex items-center space-x-2 mb-2">
                      <div :class="item.color" class="w-4 h-4 rounded"></div>
                      <div class="text-white text-xs font-medium">{{ item.name }}</div>
                    </div>
                    <div class="text-gray-400 text-xs">{{ item.stock }} in stock</div>
                  </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="mt-6 pt-4 border-t border-gray-700">
                  <div class="flex justify-between text-xs text-gray-400">
                    <div>Warehouse: 85% full</div>
                    <div>Deliveries: On time</div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Floating Elements -->
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-linear-to-r from-blue-400 to-blue-300 rounded-full opacity-20"></div>
            <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-linear-to-r from-purple-400 to-pink-300 rounded-full opacity-20"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section ref="featuresSection" class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Everything You Need for Furniture Management
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            From inventory tracking to order fulfillment, we've got you covered
          </p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div v-for="feature in features" :key="feature.id" class="bg-linear-to-b from-gray-50 to-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all duration-300 group">
            <div class="w-14 h-14 rounded-xl bg-linear-to-r from-blue-500 to-blue-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <component :is="feature.icon" class="w-7 h-7 text-white" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">{{ feature.title }}</h3>
            <p class="text-gray-600">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-linear-to-r from-blue-600 to-purple-600">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-4xl font-bold text-white mb-6">
          Ready to Transform Your Furniture Business?
        </h2>
        <p class="text-xl text-blue-100 mb-10">
          Join thousands of companies who have streamlined their furniture operations
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <router-link 
            to="/signup"
            class="px-8 py-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-gray-100 transition-colors shadow-2xl"
          >
            Get Started For Free
          </router-link>
          <router-link 
            to="/"
            class="px-8 py-4 bg-transparent border-2 border-white text-white font-bold rounded-xl hover:bg-white/10 transition-colors"
          >
            Schedule a Demo
          </router-link>
        </div>
        <p class="text-blue-200 text-sm mt-6">No setup fees • Cancel anytime • 24/7 support</p>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 px-4 sm:px-6 lg:px-8">
      <div class="max-w-7xl mx-auto">
        <div class="grid md:grid-cols-4 gap-8">
          <div>
            <div class="text-2xl font-bold mb-4">FurniManage</div>
            <p class="text-gray-400">The ultimate platform for furniture inventory and order management.</p>
          </div>
          <div>
            <h4 class="font-bold mb-4">Product</h4>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white">Features</a></li>
              <li><a href="#" class="hover:text-white">Pricing</a></li>
              <li><a href="#" class="hover:text-white">API</a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-bold mb-4">Company</h4>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white">About</a></li>
              <li><a href="#" class="hover:text-white">Blog</a></li>
              <li><a href="#" class="hover:text-white">Careers</a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-bold mb-4">Contact</h4>
            <ul class="space-y-2 text-gray-400">
              <li>support@furnimanage.com</li>
              <li>+1 (555) 123-4567</li>
            </ul>
          </div>
        </div>
        <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
          <p>© 2024 FurniManage. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import TopNav from '../../components/TopNav.vue'
import { ref } from 'vue'

// Icons for features (you can replace with actual icon components)
const InventoryIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>`
}

const OrderIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>`
}

const DeliveryIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>`
}

const AnalyticsIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>`
}

const WarehouseIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>`
}

const SupportIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>`
}

// Furniture items for dashboard preview
const furnitureItems = ref([
  { id: 1, name: 'Sofas', stock: 245, color: 'bg-blue-500' },
  { id: 2, name: 'Chairs', stock: 189, color: 'bg-purple-500' },
  { id: 3, name: 'Tables', stock: 156, color: 'bg-green-500' },
  { id: 4, name: 'Beds', stock: 98, color: 'bg-yellow-500' },
  { id: 5, name: 'Desks', stock: 203, color: 'bg-red-500' },
  { id: 6, name: 'Cabinets', stock: 176, color: 'bg-pink-500' }
])

// Features list
const features = ref([
  {
    id: 1,
    title: 'Smart Inventory Tracking',
    description: 'Real-time tracking of furniture pieces across multiple warehouses and showrooms.',
    icon: InventoryIcon
  },
  {
    id: 2,
    title: 'Order Management',
    description: 'Streamline order processing from inquiry to delivery with automated workflows.',
    icon: OrderIcon
  },
  {
    id: 3,
    title: 'Delivery Coordination',
    description: 'Optimize delivery routes and schedules with real-time tracking and updates.',
    icon: DeliveryIcon
  },
  {
    id: 4,
    title: 'Advanced Analytics',
    description: 'Gain insights into sales trends, inventory turnover, and customer preferences.',
    icon: AnalyticsIcon
  },
  {
    id: 5,
    title: 'Warehouse Management',
    description: 'Efficient space utilization and organization for your furniture storage.',
    icon: WarehouseIcon
  },
  {
    id: 6,
    title: '24/7 Support',
    description: 'Dedicated support team specialized in furniture industry challenges.',
    icon: SupportIcon
  }
])

const featuresSection = ref<HTMLElement>()

const scrollToFeatures = () => {
  featuresSection.value?.scrollIntoView({ behavior: 'smooth' })
}
</script>

<style scoped>
/* Custom animations */
@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.animate-float {
  animation: float 3s ease-in-out infinite;
}

/* Smooth transitions */
* {
  scroll-behavior: smooth;
}
</style>