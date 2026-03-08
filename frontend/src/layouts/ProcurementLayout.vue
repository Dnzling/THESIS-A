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
        <!-- Product Management Menu -->
        <div v-if="filteredProductMenu.length > 0" class="px-4 space-y-1 pb-4">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider pl-3 mb-3">
            Product Management
          </div>
          <router-link
            v-for="item in filteredProductMenu"
            :key="item.to"
            :to="item.to"
            class="text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors"
          >
            <i :class="item.icon"></i>
            <span>{{ item.label }}</span>
          </router-link>
        </div>

        <!-- Catalog Settings Menu -->
        <div v-if="filteredCatalogMenu.length > 0" class="px-4 space-y-1 pb-4 border-t border-gray-200 pt-4">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider pl-3 mb-3">
            Catalog Settings
          </div>
          <router-link
            v-for="item in filteredCatalogMenu"
            :key="item.to"
            :to="item.to"
            class="text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors"
          >
            <i :class="item.icon"></i>
            <span>{{ item.label }}</span>
          </router-link>
        </div>

        <!-- Analytics Menu -->
        <div v-if="filteredAnalyticsMenu.length > 0" class="px-4 space-y-1 pb-4 border-t border-gray-200 pt-4">
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider pl-3 mb-3">
            Analytics
          </div>
          <router-link
            v-for="item in filteredAnalyticsMenu"
            :key="item.to"
            :to="item.to"
            class="text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-500 hover:bg-blue-50 transition-colors"
          >
            <i :class="item.icon"></i>
            <span>{{ item.label }}</span>
          </router-link>
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
import { usePermissions, ROLES } from '../composables/usePermissions.vue'
import { startCase, toLower } from 'lodash'

const route = useRoute()
const authStore = useAuthStore()
const router = useRouter()
const permissions = usePermissions()

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
  switch (permissions.userRole.value) {
    case ROLES.SUPER_ADMIN:
      return 'Super Admin'
    case ROLES.STORE_ADMIN:
      return 'Store Admin'
    case ROLES.STORE_MANAGER:
      return 'Manager'
    case ROLES.WAREHOUSE_MANAGER:
      return 'Warehouse'
    case ROLES.INVENTORY_STAFF:
      return 'Inventory'
    case ROLES.SALES_STAFF:
      return 'Sales'
    default:
      return 'Staff'
  }
})

// ==========================================
// MENU DEFINITIONS WITH ROLE REQUIREMENTS
// ==========================================
interface MenuItem {
  to: string
  label: string
  icon: string
  requiredRoles?: typeof ROLES[keyof typeof ROLES][]
}

const productMenu: MenuItem[] = [
  {
    to: "/procurement/dashboard",
    label: "Dashboard",
    icon: "pi pi-home text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER]
  },
  {
    to: "/procurement/suppliers",
    label: "Suppliers",
    icon: "pi pi-users text-gray-500 w-5"
    // No requiredRoles = everyone can see
  },
  {
    to: "/procurement/purchase-requisitions",
    label: "Purchase Requisitions",
    icon: "pi pi-file-edit text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER]
  },
  {
    to: "/procurement/rfqs",
    label: "RFQs",
    icon: "pi pi-send text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER, ROLES.WAREHOUSE_MANAGER, ROLES.SALES_STAFF]
  },
  {
    to: "/procurement/purchase-orders",
    label: "Purchase Orders",
    icon: "pi pi-shopping-cart text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER, ROLES.SALES_STAFF]
  },
  {
    to: "/procurement/goods-receipts",
    label: "Goods Receipts",
    icon: "pi pi-inbox text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER, ROLES.WAREHOUSE_MANAGER, ROLES.INVENTORY_STAFF, ROLES.SUPPLIER_COORDINATOR]
  }
]

const catalogMenu: MenuItem[] = [
  {
    to: "/procurement/payments",
    label: "Supplier Payments",
    icon: "pi pi-wallet text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER]
  },
  {
    to: "/procurement/suppliers/create",
    label: "Add Supplier",
    icon: "pi pi-user-plus text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER]
  },
  {
    to: "/procurement/purchase-requisitions/create",
    label: "Create Requisition",
    icon: "pi pi-plus-circle text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER]
  },
  {
    to: "/procurement/rfqs/create",
    label: "Create RFQ",
    icon: "pi pi-plus text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER]
  }
]

const analyticsMenu: MenuItem[] = [
  {
    to: "/procurement/reports",
    label: "Procurement Reports",
    icon: "pi pi-chart-line text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER]
  },
  {
    to: "/procurement/dashboard",
    label: "Pending Approvals",
    icon: "pi pi-history text-gray-500 w-5",
    requiredRoles: [ROLES.SUPER_ADMIN, ROLES.STORE_ADMIN, ROLES.STORE_MANAGER]
  }
]

// ==========================================
// FILTER MENUS BASED ON USER ROLE
// ==========================================
const filteredProductMenu = computed(() => {
  return productMenu.filter(item => {
    if (!item.requiredRoles) return true // No restrictions
    return permissions.hasAnyRole(item.requiredRoles)
  })
})

const filteredCatalogMenu = computed(() => {
  return catalogMenu.filter(item => {
    if (!item.requiredRoles) return true
    return permissions.hasAnyRole(item.requiredRoles)
  })
})

const filteredAnalyticsMenu = computed(() => {
  return analyticsMenu.filter(item => {
    if (!item.requiredRoles) return true
    return permissions.hasAnyRole(item.requiredRoles)
  })
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