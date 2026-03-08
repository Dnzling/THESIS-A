<template>
  <Topnav/>

  <div class="min-h-screen bg-linear-to-br from-blue-50 to-indigo-50 p-4">
    <div class="max-w-4xl mx-auto pt-16">
      <!-- Animated construction elements -->
      <div class="flex justify-center mb-8">
        <div class="relative">
          <!-- Crane -->
          <div class="w-48 h-48 mx-auto">
            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-2 h-32 bg-gray-800"></div>
            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 w-32 h-2 bg-gray-800"></div>
            <div class="absolute top-16 left-1/2 transform -translate-x-1/2 w-4 h-4 bg-yellow-500 rounded-full animate-pulse"></div>
          </div>
        </div>
      </div>

      <div class="text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
          🚧 Work in Progress 🚧
        </h1>
        
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 max-w-2xl mx-auto">
          <div class="flex items-center justify-center mb-6">
            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mr-4">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
              </svg>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">Page Under Construction</h2>
          </div>
          
          <p class="text-gray-600 text-lg mb-6">
            Our team is hard at work building something amazing here. 
            We apologize for the inconvenience and appreciate your patience.
          </p>
          
          <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
            <router-link 
              to="/" 
              class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-all shadow-md hover:shadow-lg flex items-center"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
              </svg>
              Return to Homepage
            </router-link>
            
            <button 
              @click="goBack"
              class="px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition-all flex items-center"
            >
              <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Go Back
            </button>
          </div>
        </div>

        <!-- Progress bar -->
        <div class="max-w-md mx-auto mb-8">
          <div class="flex justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Construction Progress</span>
            <span class="text-sm font-medium text-blue-600">{{ progress }}%</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-3">
            <div 
              class="bg-blue-600 h-3 rounded-full transition-all duration-1000 ease-out"
              :style="{ width: progress + '%' }"
            ></div>
          </div>
        </div>

        <!-- Contact info -->
        <div class="text-gray-500 text-sm">
          <p>Need immediate assistance? <a href="mailto:support@example.com" class="text-blue-600 hover:underline">Contact our support team</a></p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import Topnav from '../../components/TopNav.vue'
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const progress = ref(0)

// Animate progress bar
onMounted(() => {
  const interval = setInterval(() => {
    if (progress.value < 65) { // Stop at 65% for "under construction" feel
      progress.value += 1
    } else {
      clearInterval(interval)
    }
  }, 50)
})

const goBack = () => {
  router.go(-1)
}
</script>

<style scoped>
@keyframes float {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}

.animate-float {
  animation: float 3s ease-in-out infinite;
}
</style>