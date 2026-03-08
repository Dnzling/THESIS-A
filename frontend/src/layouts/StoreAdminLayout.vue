<!-- layouts/StoreAdminLayout.vue -->
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
                        <p class="text-xs text-gray-600">Store Admin</p>
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
                    <!-- All Store Admin Navigation Items -->
                    <div v-if="storeNavigation.length > 0" class="px-4 space-y-1 pb-4">
                        <div v-for="section in groupedNavigation" :key="section.name">
                            <!-- Section Header (if not null) -->
                            <div v-if="section.name" class="text-xs font-semibold text-gray-400 uppercase tracking-wider pl-3 mb-3 mt-4 first:mt-0">
                                {{ section.name }}
                            </div>
                            
                            <!-- Section Items -->
                            <router-link 
                                v-for="item in section.items" 
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
                                {{ route.meta.title || 'Store Dashboard' }}
                            </h1>
                            <p class="text-sm text-gray-500">
                                {{ route.meta.subtitle || 'Manage your store operations' }}
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
                        :badge="notificationCount > 0 ? notificationCount.toString() : undefined" 
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
                    <router-link to="/system/dashboard" class="text-gray-500 hover:text-blue-600">
                        Store
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
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { startCase, toLower } from 'lodash'
import UserDialog from '../components/dialogs/UserDialog.vue'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Skeleton from 'primevue/skeleton'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const userDialogRef = ref(null)
const loadingNavigation = ref(true)
const sidebarOpen = ref(false)

// Types
interface NavigationItem {
    id: number
    parent_id: number | null
    module: string
    section: string | null
    display_name: string
    route_path: string
    icon: string | null
    display_order: number
    is_active: boolean
    badge_count: number | null
    children?: NavigationItem[]
}

interface NavigationSection {
    name: string | null
    items: NavigationItem[]
}

// User data
const fullName = computed(() => {
    const first = startCase(toLower(authStore.user?.first_name || authStore.user?.fname || ''))
    const last = startCase(toLower(authStore.user?.last_name || authStore.user?.lname || ''))
    return `${first} ${last}`.trim() || 'User'
})

const userInitials = computed(() => {
    const first = authStore.user?.first_name?.[0] || authStore.user?.fname?.[0] || ''
    const last = authStore.user?.last_name?.[0] || authStore.user?.lname?.[0] || ''
    return (first + last).toUpperCase() || 'U'
})

const roleDisplay = computed(() => startCase(authStore.user?.role || 'Store Admin'))

// Notification count (you can implement this based on your needs)
const notificationCount = computed(() => {
    // This could come from a notifications store or API
    return 3
})

// Breadcrumbs
const breadcrumbs = computed(() => {
    const crumbs = []
    const title = route.meta.title as string
    
    if (title && title !== 'Store Dashboard') {
        crumbs.push({
            name: title,
            path: null
        })
    }
    
    return crumbs
})

// ✅ Get all store admin navigation items from API
const storeNavigation = computed<NavigationItem[]>(() => {
    return authStore.navigation
        .filter((item: NavigationItem) => 
            item.module === 'store_admin' && 
            item.is_active
        )
        .sort((a: NavigationItem, b: NavigationItem) => a.display_order - b.display_order)
})

// Group navigation items by section
const groupedNavigation = computed<NavigationSection[]>(() => {
    const sections = new Map<string | null, NavigationItem[]>()
    
    // Separate parent items and their children
    const parentItems = storeNavigation.value.filter((item: NavigationItem) => !item.parent_id)
    const childItems = storeNavigation.value.filter((item: NavigationItem) => item.parent_id)
    
    // Group children by parent
    const childrenByParent = childItems.reduce((acc: Record<number, NavigationItem[]>, item: NavigationItem) => {
        if (item.parent_id) {
            if (!acc[item.parent_id]) {
                acc[item.parent_id] = []
            }
            acc[item.parent_id].push(item)
        }
        return acc
    }, {})
    
    // Attach children to parent items
    parentItems.forEach((parent: NavigationItem) => {
        if (childrenByParent[parent.id]) {
            parent.children = childrenByParent[parent.id].sort((a, b) => a.display_order - b.display_order)
        }
    })
    
    // Group by section
    parentItems.forEach((item: NavigationItem) => {
        const section = item.section || 'General'
        if (!sections.has(section)) {
            sections.set(section, [])
        }
        sections.get(section)?.push(item)
    })
    
    // Convert to array and sort sections
    return Array.from(sections.entries())
        .map(([name, items]) => ({
            name: name === 'General' ? null : name,
            items: items.sort((a, b) => a.display_order - b.display_order)
        }))
        .sort((a, b) => {
            // Put null section (General) first
            if (a.name === null) return -1
            if (b.name === null) return 1
            return (a.name || '').localeCompare(b.name || '')
        })
})

// Load navigation on mount
const loadNavigation = async () => {
    loadingNavigation.value = true
    try {
        if (authStore.navigation.length === 0) {
            await authStore.fetchNavigation()
        }
        
        // Check if store exists (you might want to move this to a separate API call)
        const hasStore = !!authStore.user?.store?.id
        if (!hasStore && route.path !== '/system/store/verification') {
            router.push('/system/store/verification')
        }
    } catch (error) {
        console.error('Failed to load navigation:', error)
    } finally {
        loadingNavigation.value = false
    }
}

// Toggle sidebar for mobile
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value
}

// Close sidebar when route changes on mobile
watch(() => route.path, () => {
    if (window.innerWidth < 1024) {
        sidebarOpen.value = false
    }
})

// Lifecycle
onMounted(() => {
    loadNavigation()
})

// User dialog
const openUserDialog = (event: MouseEvent) => {
    if (userDialogRef.value) {
        (userDialogRef.value as any).toggle(event)
    }
}
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
        z-index: 50;
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

/* Nested menu styles (if needed) */
.nav-item .submenu {
    margin-left: 2rem;
}

.nav-item.has-children > .nav-link::after {
    content: '\e930';
    font-family: 'primeicons';
    font-size: 0.75rem;
    margin-left: auto;
    transition: transform 0.2s;
}

.nav-item.has-children.open > .nav-link::after {
    transform: rotate(90deg);
}
</style>