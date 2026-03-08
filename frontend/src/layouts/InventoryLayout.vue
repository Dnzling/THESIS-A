<!-- frontend/src/views/Inventory/InventoryDashboard.vue -->

<template>
    <div class="flex h-screen w-full max-w-[100vw] overflow-hidden bg-gray-50">
        <!-- Sidebar -->
        <aside class="sidebar bg-emerald-50 w-64 flex flex-col z-30 overflow-y-auto" :class="{ 
                    'open': sidebarOpen,
                    'closed': !sidebarOpen 
                }">
            <!-- Logo section -->
            <div class="px-5 py-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg">
                        <img src="/F.svg" alt="Furnisync" class="w-20 h-20">
                    </div>
    
                    <div class="leading-tight" style="font-family: 'Poppins'">
                        <h1 class="text-lg font-semibold text-gray-900">Furnisync</h1>
                        <p class="text-xs text-gray-600">Inventory</p>
                    </div>
                </div>
            </div>
    
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4">
                <!-- Loading State -->
                <div v-if="loadingNavigation" class="px-4 space-y-2">
                    <Skeleton height="40px" class="rounded-lg" />
                    <Skeleton height="40px" class="rounded-lg" />
                    <Skeleton height="40px" class="rounded-lg" />
                </div>
    
                <template v-else>
                    <!-- All Navigation Items -->
                    <div v-if="allNavigation.length > 0" class="px-4 space-y-1 pb-4">
                        <router-link v-for="item in allNavigation" :key="item.id" :to="item.route_path"
                            class="nav-item text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-600 hover:bg-emerald-50 hover:text-emerald-600 transition-colors">
                            <i :class="[item.icon || 'pi pi-circle', 'w-5']"></i>
                            <span class="flex-1">{{ item.display_name }}</span>
                            <Badge v-if="item.meta?.badge === 'count' && alertCount > 0" :value="alertCount"
                                severity="danger" size="small" />
                            <Badge v-else-if="item.badge_count && item.badge_count > 0" :value="item.badge_count"
                                severity="danger" size="small" />
                        </router-link>
                    </div>
    
                    <!-- Empty State -->
                    <div v-else class="px-4 py-8 text-center">
                        <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
                        <p class="text-sm text-gray-500">No menu items available</p>
                        <p class="text-xs text-gray-400 mt-1">Contact your administrator</p>
                    </div>
                </template>
            </nav>
        </aside>
    
        <!-- Main content -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden">
            <!-- Top header -->
            <header
                class="bg-white border-b border-gray-200 py-4 px-6 flex items-center justify-between sticky top-0 z-20 shadow-sm">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <!-- Menu button for ALL screens - visible on all devices -->
                        
    
                        <div>
                            <h1 class="text-xl font-semibold text-gray-800">
                                {{ route.meta.title || 'Inventory Management' }}
                            </h1>
                            <p class="text-sm text-gray-500">
                                {{ route.meta.subtitle || 'Track and manage your stock levels' }}
                            </p>
                        </div>
                    </div>
                </div>
    
                <!-- Header Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Notifications Bell -->
                    <NotificationBell v-if="canViewNotifications"
                        :badge="alertCount > 0 ? alertCount.toString() : undefined" badgeSeverity="danger"
                        @click="navigateToAlerts" />
    
                    <!-- Quick Actions -->
                    <Button v-if="canCreateAdjustments || canCreateTransfers" icon="pi pi-plus" severity="success" text
                        rounded @click="toggleQuickActions" />
    
                    <!-- User Profile -->
                    <div class="border-l border-gray-200 pl-4 cursor-pointer select-none" @click="openUserDialog">
                        <div class="flex items-center space-x-3 hover:bg-gray-50 px-2 py-1 rounded-lg transition">
                            <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                <span class="text-sm font-semibold text-emerald-600">{{ userInitials }}</span>
                            </div>
                            <div>
                                <h2 class="font-semibold text-gray-800 text-sm">{{ fullName }}</h2>
                                <p class="text-xs text-gray-500">{{ roleDisplay }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
    
            <!-- Breadcrumb -->
            <div class="bg-white border-b border-gray-200 px-6 py-2">
                <nav class="flex items-center space-x-2 text-sm">
                    <router-link to="/system/index" class="text-gray-500 hover:text-emerald-600">
                        <i class="pi pi-home"></i>
                    </router-link>
                    <i class="pi pi-angle-right text-gray-400 text-xs"></i>
                    <router-link to="/inventory/dashboard" class="text-gray-500 hover:text-emerald-600">
                        Inventory
                    </router-link>
                    <template v-if="breadcrumbs.length > 0">
                        <template v-for="(crumb, index) in breadcrumbs" :key="index">
                            <i class="pi pi-angle-right text-gray-400 text-xs"></i>
                            <router-link v-if="crumb.path" :to="crumb.path" class="text-gray-500 hover:text-emerald-600">
                                {{ crumb.name }}
                            </router-link>
                            <span v-else class="text-gray-800 font-medium">{{ crumb.name }}</span>
                        </template>
                    </template>
                </nav>
            </div>
    
            <!-- Scrollable content -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <router-view />
            </main>
        </div>
    
        <!-- Quick Actions Menu -->
        <Menu ref="quickActionsMenu" :model="quickActionsItems" :popup="true" />
    </div>
    
    <UserDialog ref="userDialogRef" />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { startCase, toLower } from 'lodash'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'
import Menu from 'primevue/menu'
import { useAuthStore } from '../stores/auth'
import inventoryService from '../services/inventory.service'
import UserDialog from '../components/dialogs/UserDialog.vue'
import NotificationBell from '../components/Inventory/NotificationBell.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const quickActionsMenu = ref(null)
const userDialogRef = ref(null)
const loadingNavigation = ref(false)

// Sidebar state with persistence
const sidebarOpen = ref(localStorage.getItem('sidebarOpen') !== 'false') // Default to open

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
  localStorage.setItem('sidebarOpen', sidebarOpen.value.toString())

  // Dispatch event for any components that need to know about sidebar state
  window.dispatchEvent(new CustomEvent('sidebar-toggle', {
    detail: { open: sidebarOpen.value }
  }))
}

// Keyboard shortcut: Ctrl+B to toggle sidebar
const handleKeyboardShortcut = (event: KeyboardEvent) => {
  if (event.ctrlKey && event.key === 'b') {
    event.preventDefault()
    toggleSidebar()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyboardShortcut)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyboardShortcut)
})

const canViewAlerts = computed(() => authStore.hasPermission('inventory.alerts.view'))
const canViewNotifications = computed(() => authStore.hasPermission('inventory.notifications.view'))
const canCreateAdjustments = computed(() => authStore.hasPermission('inventory.adjustments.create'))
const canCreateTransfers = computed(() => authStore.hasPermission('inventory.transfers.create'))
const canGenerateAlerts = computed(() => authStore.hasPermission('inventory.alerts.generate'))

const alertSummary = ref<{ total_active: number }>({ total_active: 0 })

const openUserDialog = (event: MouseEvent) => {
  if (userDialogRef.value) {
    (userDialogRef.value as any).toggle(event);
  }
}

// Alert count
const alertCount = computed(() => alertSummary.value?.total_active || 0)

// User data
const fullName = computed(() => {
  const first = startCase(toLower(authStore.user?.first_name || ''))
  const last = startCase(toLower(authStore.user?.last_name || ''))
  return `${first} ${last}`.trim() || 'User'
})

const userInitials = computed(() => {
  const first = authStore.user?.first_name?.[0] || ''
  const last = authStore.user?.last_name?.[0] || ''
  return (first + last).toUpperCase() || 'U'
})

const roleDisplay = computed(() => startCase(authStore.user?.role || 'User'))

// Breadcrumbs
const breadcrumbs = computed(() => {
  const crumbs = []
  const title = route.meta.title as string

  if (title && title !== 'Inventory Dashboard') {
    crumbs.push({
      name: title,
      path: null
    })
  }

  return crumbs
})

// Navigation
const allNavigation = computed(() => {
  return authStore.navigation
    .filter(item =>
      item.module === 'inventory' &&
      !item.parent_id &&
      item.is_active
    )
    .sort((a, b) => a.display_order - b.display_order)
})

// Quick Actions Menu
const quickActionsItems = computed(() => {
  const items = []

  if (canCreateAdjustments.value) {
    items.push({
      label: 'Stock Adjustment',
      icon: 'pi pi-sync',
      command: () => router.push('/inventory/adjustments/create')
    })
  }

  if (canCreateTransfers.value) {
    items.push({
      label: 'Stock Transfer',
      icon: 'pi pi-arrow-right-arrow-left',
      command: () => router.push('/inventory/transfers/create')
    })
  }

  if (canGenerateAlerts.value) {
    items.push({
      separator: true
    })
    items.push({
      label: 'Generate Alerts',
      icon: 'pi pi-bell',
      command: () => {
        // Implement generate alerts logic
        console.log('Generate alerts clicked')
      }
    })
  }

  return items
})

const toggleQuickActions = (event: Event) => {
  if (quickActionsMenu.value) {
    (quickActionsMenu.value as any).toggle(event)
  }
}

const navigateToAlerts = () => {
  router.push('/inventory/alerts')
}

// Load navigation and alerts
const loadNavigation = async () => {
  loadingNavigation.value = true
  try {
    await authStore.fetchNavigation()
  } catch (error) {
    console.error('Failed to load navigation:', error)
  } finally {
    loadingNavigation.value = false
  }
}

const loadAlertSummary = async () => {
  if (canViewAlerts.value) {
    try {
      const response = await inventoryService.getAlerts({ per_page: 1, status: 'active' })
      const total = response?.data?.total ?? response?.data?.data?.length ?? 0
      alertSummary.value = { total_active: total }
    } catch (error) {
      console.error('Failed to load alert summary:', error)
    }
  }
}

onMounted(() => {
  if (authStore.navigation.length === 0) {
    loadNavigation()
  }
  loadAlertSummary()
})
</script>

<style scoped>
.sidebar {
    transition: all 0.3s ease;
    width: 16rem;
    /* 64 * 0.25rem = 16rem */
}

/* Desktop sidebar behavior */
@media (min-width: 1025px) {
    .sidebar {
        position: relative;
        transform: translateX(0);
    }

    .sidebar.closed {
        width: 0;
        min-width: 0;
        overflow: hidden;
        padding: 0;
        margin-left: -1rem;
        /* Adjust based on your design */
    }

    /* Smooth transition for main content when sidebar toggles */
    .flex-1 {
        transition: margin-left 0.3s ease;
    }
}

/* Mobile sidebar behavior */
@media (max-width: 1024px) {
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        transform: translateX(-100%);
        width: 16rem;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        z-index: 40;
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .sidebar.closed {
        transform: translateX(-100%);
    }

    /* Add overlay when sidebar is open on mobile */
    .sidebar.open::after {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.3);
        z-index: -1;
        pointer-events: none;
    }
}

/* Menu button styling */
.menu-button {
    transition: all 0.2s ease;
}

.menu-button:hover {
    background-color: #e5e7eb;
}

/* Navigation item styling */
.nav-item {
    position: relative;
    overflow: hidden;
}

.nav-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background: #10b981;
    transform: scaleY(0);
    transition: transform 0.2s ease;
}

.nav-item:hover::before,
.router-link-active::before {
    transform: scaleY(1);
}

.router-link-active {
    background-color: white;
    color: #10b981;
    font-weight: 600;
}

.router-link-active i {
    color: #059669;
}

/* Scrollbar styling */
main {
    scroll-behavior: smooth;
}

.sidebar::-webkit-scrollbar,
main::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track,
main::-webkit-scrollbar-track {
    background: #f1f5f9;
}

.sidebar::-webkit-scrollbar-thumb,
main::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover,
main::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Ensure content doesn't overflow */
.flex-1 {
    min-width: 0;
}

/* Header text truncation */
h1,
p {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>