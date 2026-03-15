<template>
  <div class="flex h-screen w-full max-w-[100vw] overflow-hidden">
    <!-- Sidebar -->
    <aside class="sidebar bg-white w-64 shadow-lg flex flex-col z-30 overflow-y-auto">
      <!-- Logo section -->
      <div class="px-5 py-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg">
            <img src="../../public/F.svg" alt="Furnisync" class="w-20 h-20">
          </div>
          <div class="leading-tight" style="font-family: 'Poppins'">
            <h1 class="text-lg font-semibold text-gray-900">Furnisync</h1>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 overflow-y-auto py-4">
        <!-- All Procurement Navigation Items -->
        <div v-if="procurementNavigation.length > 0" class="px-4 space-y-1 pb-4">
          <router-link
            v-for="item in procurementNavigation"
            :key="item.id"
            :to="item.route_path"
            class="text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors"
          >
            <i :class="[item.icon || 'pi pi-circle', 'w-5']"></i>
            <span>{{ item.display_name }}</span>
          </router-link>
        </div>

        <!-- Empty State -->
        <div v-else class="px-4 py-8 text-center">
          <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
          <p class="text-sm text-gray-500">No menu items available</p>
          <p class="text-xs text-gray-400 mt-1">Contact your administrator</p>
        </div>
      </nav>

      <!-- Footer -->
      <div class="p-4 border-t border-gray-200">
        <button
          @click.prevent="handleLogout"
          class="flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-700 hover:bg-blue-50 transition-colors w-full text-left"
        >
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
          <!-- Role Badge -->
          <div class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
            {{ roleBadge }}
          </div>

          <div class="border-l border-gray-200 pl-4">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                <i class="fas fa-user text-blue-600"></i>
              </div>
              <div>
                <h2 class="font-semibold text-gray-800">{{ firstName }} {{ lastName }}</h2>
                <p class="text-xs text-gray-500">{{ roleLabel }}</p>
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
import { ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'
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
const roleLabel = startCase(user?.role?.replace(/_/g, ' '))

// Role badge for display
const roleBadge = computed(() => {
  switch (authStore.userRole) {
    case 'super_admin':
      return 'Super Admin'
    case 'store_admin':
      return 'Store Admin'
    case 'store_manager':
      return 'Manager'
    case 'warehouse_manager':
      return 'Warehouse'
    case 'inventory_staff':
      return 'Inventory'
    case 'sales_staff':
      return 'Sales'
    case 'supplier_coordinator':
      return 'Supplier'
    case 'hr_manager':
      return 'HR'
    case 'accountant':
      return 'Accountant'
    case 'cashier':
      return 'Cashier'
    default:
      return 'Staff'
  }
})

// ==========================================
// Fetch procurement navigation from database
// ==========================================
const procurementNavigation = computed(() => {
  return authStore.navigation
    .filter(item =>
      item.module === 'procurement' &&
      !item.parent_id &&
      item.is_active
    )
    .sort((a, b) => a.display_order - b.display_order)
})

// ==========================================
// LOGOUT HANDLER
// ==========================================
const isLoggingOut = ref(false)

const handleLogout = async () => {
  isLoggingOut.value = true
  try {
    authStore.logout()
    delete axios.defaults.headers.common['Authorization']
    router.replace('/login')
  } catch (error) {
    console.error('Logout error:', error)
    localStorage.clear()
    sessionStorage.clear()
    delete axios.defaults.headers.common['Authorization']
  } finally {
    isLoggingOut.value = false
  }
}
</script>

<style scoped>
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