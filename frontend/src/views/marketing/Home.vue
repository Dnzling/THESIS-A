<template>
  <TopNav/>
  <div class="min-h-screen">
    <!-- Hero Section -->
    <section class="pt-20 pb-32 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-gray-50 to-white">
      <div class="max-w-7xl mx-auto">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <!-- Left Column: Content -->
          <div class="text-center lg:text-left">
            <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold mb-6">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
              </svg>
              Next-Gen Furniture Management
            </div>
            
            <h1 class="text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
              Transform Your
              <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600">
                Furniture Operations
              </span>
            </h1>
            
            <p class="text-xl text-gray-600 mb-10 max-w-2xl">
              AI-powered platform with 3D product visualization and intelligent Decision Support System (DSS) for optimal inventory management.
            </p>
            
            <div class="flex flex-col sm:flex-row gap-4">
              <router-link 
                to="/signup"
                class="px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1"
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
          </div>
          
          <!-- Right Column: 3D Viewer Preview -->
          <div class="relative">
            <div class="relative rounded-2xl overflow-hidden shadow-2xl bg-gradient-to-br from-gray-900 to-gray-800 p-6">
              <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-3">
                  <div class="w-3 h-3 bg-red-400 rounded-full"></div>
                  <div class="w-3 h-3 bg-yellow-400 rounded-full"></div>
                  <div class="w-3 h-3 bg-green-400 rounded-full"></div>
                  <div class="text-white text-sm font-medium ml-4">3D Furniture Viewer</div>
                </div>
                <div class="flex space-x-2">
                  <span class="text-xs text-gray-400 bg-gray-800 px-2 py-1 rounded">Rotate</span>
                  <span class="text-xs text-gray-400 bg-gray-800 px-2 py-1 rounded">Zoom</span>
                </div>
              </div>
              
              <!-- 3D Viewer Simulation -->
              <div class="bg-gray-800 rounded-xl p-8 mb-6">
                <div class="flex justify-center items-center">
                  <div class="relative w-64 h-64">
                    <!-- 3D Chair Visualization -->
                    <div class="absolute inset-0 flex items-center justify-center">
                      <div class="w-40 h-40 bg-gradient-to-br from-blue-400 to-purple-500 rounded-lg transform rotate-45 animate-pulse"></div>
                      <div class="absolute w-32 h-32 bg-blue-300 rounded-lg transform rotate-45"></div>
                      <div class="absolute w-24 h-24 bg-white rounded-lg"></div>
                    </div>
                    <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-32 h-2 bg-gray-700 rounded-full blur-sm"></div>
                  </div>
                </div>
                <div class="text-center mt-4">
                  <p class="text-white font-medium">Modern Office Chair</p>
                  <p class="text-gray-400 text-sm">Click and drag to rotate 360°</p>
                </div>
              </div>
              
              <div class="grid grid-cols-3 gap-2">
                <button class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-2 rounded-lg transition">Front View</button>
                <button class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-2 rounded-lg transition">Side View</button>
                <button class="bg-gray-800 hover:bg-gray-700 text-white text-sm py-2 rounded-lg transition">Top View</button>
              </div>
            </div>
            
            <!-- Floating Elements -->
            <div class="absolute -top-4 -right-4 w-24 h-24 bg-gradient-to-r from-blue-400 to-blue-300 rounded-full opacity-20 animate-pulse"></div>
            <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-gradient-to-r from-purple-400 to-pink-300 rounded-full opacity-20 animate-pulse"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section ref="featuresSection" class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Intelligent Platform for Furniture Management
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Powered by 3D visualization and AI-driven Decision Support System
          </p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div v-for="feature in features" :key="feature.id" class="bg-gradient-to-b from-gray-50 to-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all duration-300 group">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-r from-blue-500 to-blue-400 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
              <component :is="feature.icon" class="w-7 h-7 text-white" />
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-3">{{ feature.title }}</h3>
            <p class="text-gray-600">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- DSS Section - Highlighting Decision Support System -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-purple-50 to-blue-50">
      <div class="max-w-7xl mx-auto">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
          <div>
            <div class="inline-flex items-center px-4 py-2 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold mb-6">
              <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 10a8 8 0 018-8v8h8a8 8 0 11-16 0z"></path>
                <path d="M12 2.252A8.014 8.014 0 0117.748 8H12V2.252z"></path>
              </svg>
              Decision Support System
            </div>
            <h2 class="text-4xl font-bold text-gray-900 mb-6">
              AI-Powered Inventory Intelligence
            </h2>
            <p class="text-lg text-gray-600 mb-8">
              Our advanced Decision Support System analyzes historical data, seasonal trends, and market demands to provide actionable insights for optimal stock levels.
            </p>
            <div class="space-y-4 mb-8">
              <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-green-500 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                  <h4 class="font-semibold text-gray-900">Predictive Analytics</h4>
                  <p class="text-gray-600">Forecast demand with 95% accuracy using machine learning algorithms</p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-green-500 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                  <h4 class="font-semibold text-gray-900">Smart Reordering</h4>
                  <p class="text-gray-600">Automatic purchase order generation when stock reaches threshold levels</p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-green-500 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                  <h4 class="font-semibold text-gray-900">Inventory Optimization</h4>
                  <p class="text-gray-600">Minimize carrying costs while ensuring optimal stock availability</p>
                </div>
              </div>
            </div>
          </div>
          <div class="relative">
            <div class="bg-white rounded-2xl shadow-2xl p-6">
              <div class="mb-4">
                <h3 class="font-semibold text-gray-900 mb-2">DSS Dashboard Preview</h3>
                <div class="h-2 bg-gray-200 rounded-full">
                  <div class="h-2 bg-blue-600 rounded-full" style="width: 78%"></div>
                </div>
              </div>
              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Stock Health Score</span>
                  <span class="text-sm font-semibold text-blue-600">92%</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Demand Forecast Accuracy</span>
                  <span class="text-sm font-semibold text-green-600">95%</span>
                </div>
                <div class="flex justify-between items-center">
                  <span class="text-sm text-gray-600">Reorder Recommendations</span>
                  <span class="text-sm font-semibold text-orange-600">8 items</span>
                </div>
              </div>
              <div class="mt-6 pt-4 border-t border-gray-200">
                <div class="text-sm text-gray-600 mb-2">Recommended Action</div>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                  <p class="text-sm text-blue-800">Order 150 units of "Ergonomic Chair" - Stock predicted to run out in 5 days</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 3D Viewer Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
      <div class="max-w-7xl mx-auto">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-gray-900 mb-4">
            Immersive 3D Product Visualization
          </h2>
          <p class="text-xl text-gray-600 max-w-3xl mx-auto">
            Showcase your furniture in stunning 3D detail with interactive viewing experiences
          </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
          <div class="text-center p-6">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3-3m0 0l3 3m-3-3v18M21 6l-3-3m0 0l-3 3m3-3v18"></path>
              </svg>
            </div>
            <h3 class="text-xl font-bold mb-2">360° Rotation</h3>
            <p class="text-gray-600">View products from every angle with smooth drag-and-rotate controls</p>
          </div>
          <div class="text-center p-6">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </div>
            <h3 class="text-xl font-bold mb-2">Zoom & Inspect</h3>
            <p class="text-gray-600">Examine materials, finishes, and details with high-resolution zoom</p>
          </div>
          <div class="text-center p-6">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h3 class="text-xl font-bold mb-2">AR Preview</h3>
            <p class="text-gray-600">Place furniture in real spaces using augmented reality technology</p>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-r from-blue-600 to-purple-600">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-4xl font-bold text-white mb-6">
          Ready to Transform Your Furniture Business?
        </h2>
        <p class="text-xl text-blue-100 mb-10">
          Join thousands of companies using our AI-powered platform
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <router-link 
            to="/signup"
            class="px-8 py-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-gray-100 transition-colors shadow-2xl"
          >
            Start Free Trial
          </router-link>
          <router-link 
            to="/demo"
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
        <div class="grid md:grid-cols-5 gap-8">
          <div class="md:col-span-2">
            <div class="text-2xl font-bold mb-4">FurniSync</div>
            <p class="text-gray-400">The ultimate platform for furniture inventory management with 3D visualization and AI-powered decision support.</p>
          </div>
          <div>
            <h4 class="font-bold mb-4">Platform</h4>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white">3D Viewer</a></li>
              <li><a href="#" class="hover:text-white">DSS Analytics</a></li>
              <li><a href="#" class="hover:text-white">Inventory Management</a></li>
              <li><a href="#" class="hover:text-white">Order Processing</a></li>
            </ul>
          </div>
          <div>
            <h4 class="font-bold mb-4">Company</h4>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white">About Us</a></li>
              <li><a href="#" class="hover:text-white">Blog</a></li>
              <li><router-link to="/job-portal" class="hover:text-white">Job Portal</router-link></li>
              <li><router-link to="/shop" class="hover:text-white">Ecommerce</router-link></li>
            </ul>
          </div>
          <div>
            <h4 class="font-bold mb-4">Support</h4>
            <ul class="space-y-2 text-gray-400">
              <li><a href="#" class="hover:text-white">Help Center</a></li>
              <li><a href="#" class="hover:text-white">Documentation</a></li>
              <li><a href="#" class="hover:text-white">API Status</a></li>
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

// Feature Icons
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

const Model3DIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>`
}

const DSSIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>`
}

const features = ref([
  {
    id: 1,
    title: '3D Product Viewer',
    description: 'Interactive 360° product visualization with zoom controls and material inspection capabilities.',
    icon: Model3DIcon
  },
  {
    id: 2,
    title: 'Decision Support System',
    description: 'AI-powered analytics for predictive inventory management and automated reordering recommendations.',
    icon: DSSIcon
  },
  {
    id: 3,
    title: 'Smart Inventory Tracking',
    description: 'Real-time tracking across multiple warehouses with automated stock level monitoring.',
    icon: InventoryIcon
  },
  {
    id: 4,
    title: 'Order Management',
    description: 'Streamline order processing from inquiry to delivery with intelligent workflow automation.',
    icon: OrderIcon
  },
  {
    id: 5,
    title: 'Delivery Optimization',
    description: 'Optimize delivery routes and schedules with real-time tracking and customer updates.',
    icon: DeliveryIcon
  },
  {
    id: 6,
    title: 'Performance Analytics',
    description: 'Comprehensive insights into sales trends, inventory turnover, and business performance metrics.',
    icon: AnalyticsIcon
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