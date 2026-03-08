<template>
    <div class="flex h-screen w-full max-w-[100vw] overflow-hidden bg-gray-50">
        <!-- Sidebar -->
        <aside class="sidebar bg-blue-50 w-64 flex flex-col z-30 overflow-y-auto"
            :class="{ 'open': sidebarOpen }">
            <!-- Logo section -->
            <div class="px-5 py-4 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg">
                        <img src="../../public/F.svg" alt="Furnisync" class="w-20 h-20">
                    </div>
    
                    <div class="leading-tight" style="font-family: 'Poppins'">
                        <h1 class="text-lg font-semibold text-gray-900">Furnisync</h1>
                        <p class="text-xs text-gray-600">Merchandising</p>
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
                    <!-- All Navigation Items (no sections since section is null) -->
                    <div v-if="allNavigation.length > 0" class="px-4 space-y-1 pb-4">
                        <router-link 
                            v-for="item in allNavigation" 
                            :key="item.id" 
                            :to="item.route_path"
                            class="nav-item text-sm font-medium flex items-center space-x-3 py-3 px-4 rounded-lg text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors"
                        >
                            <i :class="[item.icon || 'pi pi-circle', 'w-5']"></i>
                            <span class="flex-1">{{ item.display_name }}</span>
                            <Badge 
                                v-if="item.badge_count && item.badge_count > 0" 
                                :value="item.badge_count" 
                                severity="danger" 
                                size="small"
                            />
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

            <!-- Sidebar Footer -->
            <div class="px-4 py-3 border-t border-gray-200">
                <router-link 
                    to="/system/index"
                    class="flex items-center gap-2 text-sm text-gray-600 hover:text-blue-600 transition-colors py-2 px-3 rounded-lg hover:bg-gray-50"
                >
                    <i class="pi pi-arrow-left"></i>
                    <span>Back to System</span>
                </router-link>
            </div>
        </aside>
    
        <!-- Main content -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden">
            <!-- Top header -->
            <header
                class="bg-white border-b border-gray-200 py-4 px-6 flex items-center justify-between sticky top-0 z-20 shadow-sm">
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <Button icon="pi pi-bars" text rounded class="lg:hidden" @click="toggleSidebar" />
                        <div>
                            <h1 class="text-xl font-semibold text-gray-800">
                                {{ route.meta.title || 'Product Catalog' }}
                            </h1>
                            <p class="text-sm text-gray-500">
                                {{ route.meta.subtitle || 'Manage your furniture inventory' }}
                            </p>
                        </div>
                    </div>
                </div>
    
                <!-- Header Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <Button 
                        icon="pi pi-bell" 
                        severity="secondary" 
                        text 
                        rounded 
                        badge="3" 
                        badgeSeverity="danger" 
                    />
    
                    <!-- User Profile -->
                    <div class="border-l border-gray-200 pl-4 cursor-pointer select-none" @click="openUserDialog">
                        <div class="flex items-center space-x-3 hover:bg-gray-50 px-2 py-1 rounded-lg transition">
                            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-sm font-semibold text-blue-600">{{ userInitials }}</span>
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
                    <router-link to="/system/index" class="text-gray-500 hover:text-blue-600">
                        <i class="pi pi-home"></i>
                    </router-link>
                    <i class="pi pi-angle-right text-gray-400 text-xs"></i>
                    <router-link to="/merchandising/dashboard" class="text-gray-500 hover:text-blue-600">
                        Merchandising
                    </router-link>
                    <template v-if="breadcrumbs.length > 0">
                        <template v-for="(crumb, index) in breadcrumbs" :key="index">
                            <i class="pi pi-angle-right text-gray-400 text-xs"></i>
                            <router-link 
                                v-if="crumb.path" 
                                :to="crumb.path" 
                                class="text-gray-500 hover:text-blue-600"
                            >
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
    </div>
    
    <UserDialog ref="userDialogRef" />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { startCase, toLower } from 'lodash'
import UserDialog from '../components/dialogs/UserDialog.vue'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'

const route = useRoute()
const authStore = useAuthStore()
const userDialogRef = ref(null)
const loadingNavigation = ref(false)

const openUserDialog = (event: MouseEvent) => {
  if (userDialogRef.value) {
    (userDialogRef.value as any).toggle(event);
  }
}

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
  
  if (title && title !== 'Product Catalog Dashboard') {
    crumbs.push({
      name: title,
      path: null
    })
  }
  
  return crumbs
})

// ✅ Get all merchandising navigation (since section is null, show all)
const allNavigation = computed(() => {
  return authStore.navigation
    .filter(item => 
      item.module === 'merchandising' && 
      !item.parent_id &&
      item.is_active
    )
    .sort((a, b) => a.display_order - b.display_order)
})

// Sidebar toggle for mobile
const sidebarOpen = ref(false)
const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}

// Load navigation on mount
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

onMounted(() => {
  // Load navigation if not already loaded
  if (authStore.navigation.length === 0) {
    loadNavigation()
  }
})
</script>

<style scoped>
.sidebar {
    transition: all 0.3s ease;
}

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
    background: #3b82f6;
    transform: scaleY(0);
    transition: transform 0.2s ease;
}

.nav-item:hover::before,
.router-link-active::before {
    transform: scaleY(1);
}

.router-link-active {
    background-color: white;
    color: #3b82f6;
    font-weight: 600;
}

.router-link-active i {
    color: #2563eb;
}

/* Mobile responsiveness */
@media (max-width: 1024px) {
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        transform: translateX(-100%);
    }

    .sidebar.open {
        transform: translateX(0);
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }
}

/* Smooth scroll */
main {
    scroll-behavior: smooth;
}

/* Custom scrollbar */
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
</style>