<!-- layouts/SystemLayout.vue -->
<template>
  <div class="flex h-screen w-full max-w-[100vw] overflow-hidden">
    <!-- Sidebar -->
    <aside class="sidebar bg-white w-64 shadow-lg flex flex-col z-30 overflow-y-auto">
      <!-- Logo section -->
      <div class="px-5 py-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
          <!-- Logo / Icon -->
          <div class="flex items-center justify-center w-10 h-10 rounded-lg">
            <!-- Optional image -->
            <img src="../../public/F.svg" alt="Furnisync" class="w-20 h-20">
          </div>
  
          <!-- Brand Text -->
          <div class="leading-tight" style="font-family: 'Poppins'">
            <h1 class="text-lg font-semibold text-gray-900">
              Furnisync
            </h1>
  
          </div>
        </div>
      </div>
  
  
      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4">
        <div class="px-4 space-y-1 pb-4">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider pl-3 mb-3">Menu</div>
  
          <router-link v-for="item in adminMenu" :key="item.to" :to="item.to"
            class="text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors">
            <i :class="item.icon"></i>
            <span>{{ item.label }}</span>
          </router-link>
        </div>
      </nav>
  
      <!-- Footer -->
      <div class="p-4 border-t border-gray-200">
        <button @click.prevent="handleLogout"
          class="flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-700 hover:bg-blue-50 transition-colors w-full text-left">
          <i class="fas fa-sign-out-alt text-blue-600 w-5"></i>
          <span>Logout</span>
        </button>
      </div>
  
    </aside>
  
    <!-- Main content -->
    <div class="flex-1 flex flex-col h-screen">
      <!-- Top header -->
      <header class="bg-white border-b border-gray-200 py-4 px-6 flex items-center justify-between sticky top-0 z-20">
        <div>
          <h1 class="font-semibold text-gray-800">
            {{ route.meta.title }}
          </h1>
          <p class="text-sm text-gray-400">
            {{ route.meta.subtitle }}
          </p>
        </div>
  
        <div class="flex items-center space-x-4">
          <div class="border-l border-gray-200 pl-4">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-user text-blue-600"></i>
              </div>
              <div>
                <h2 class="font-semibold text-gray-800">{{firstName}} {{ lastName }}</h2>
                <p class="text-xs text-gray-500">{{role}}</p>
              </div>
            </div>
          </div>
        </div>
      </header>
  
      <!-- Scrollable content -->
      <main class="flex-1 overflow-y-auto p-5 bg-gray-50">
        <router-view />
      </main>
    </div>
  </div>
</template>


<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth'
import { startCase, toLower } from 'lodash'

const route = useRoute()
const authStore = useAuthStore()
const router = useRouter()

type User = {
  id: number
  user_id: string
  first_name: string
  last_name: string
  full_name: string
  role: string
  email: string
}

const userData = localStorage.getItem('user')
const user: User | null = userData ? JSON.parse(userData) as User : null

const firstName = startCase(toLower(user?.first_name))
const lastName = startCase(toLower(user?.last_name))
const role = startCase(user?.role)


// Updated menu for furniture management system
const adminMenu = [
  {
    to: "/admin/dashboard",
    label: "Dashboard",
    icon: "pi pi-home text-gray-500 w-5"
  },
  {
    to: "/admin/subscription",
    label: "Subscription Management",
    icon: "pi pi-credit-card text-gray-500 w-5"
  },
  {
    to: "/admin/store-validation",
    label: "Store Validation",
    icon: "pi pi-verified text-gray-500 w-5"
  },
  {
    to: "/admin/customer-validation",
    label: "Customer Validation",
    icon: "pi pi-user text-gray-500 w-5"
  },
  {
    to: "/admin/stores",
    label: "Stores",
    icon: "pi pi-building text-gray-500 w-5"
  },
  {
    to: "/admin/roles-permissions",
    label: "Roles & Permissions",
    icon: "pi pi-shield"
  },
  {
    to: "/admin/analytics-&-reports",
    label: "Analytics & Reports",
    icon: "pi pi-chart-bar text-gray-500 w-5"
  },
  {
    to: "/admin/support-&-maintenance",
    label: "Support & Maintenance",
    icon: "pi pi-cog text-gray-500 w-5"
  },
  {
    to: "/admin/users",
    label: "Users",
    icon: "pi pi-users text-gray-500 w-5"
  }
]


const isLoggingOut = ref(false)

const handleLogout = async () => {
  isLoggingOut.value = true

  try {
    console.log('1. Current token before logout:', authStore.token)
    console.log('2. Current localStorage:', {
      auth_token: localStorage.getItem('auth_token'),
      user: localStorage.getItem('user')
    })
    authStore.logout()
    delete axios.defaults.headers.common['Authorization']
    router.replace('/login')

  } catch (error) {
    console.error('Logout error:', error)

    // Emergency cleanup
    localStorage.clear()
    sessionStorage.clear()
    delete axios.defaults.headers.common['Authorization']
  } finally {
    isLoggingOut.value = false
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.router-link-active {
  background-color: #dbeafe;
  color: #2563eb;
  font-weight: 600;
}

.router-link-active span {
  color: #2563eb;
}

.router-link-active i {
  color: #2563eb;
}

.sidebar {
  transition: all 0.3s ease;
}
</style>