<template>
  <Head v-if="pageTitle" :title="pageTitle" />
  <div
    class="flex h-screen w-full max-w-[100vw] overflow-hidden bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.12),_transparent_38%),linear-gradient(180deg,_#eff6ff_0%,_#f8fafc_42%,_#ffffff_100%)]">
    <!-- Sidebar -->
    <aside class="sidebar bg-white w-64 flex flex-col z-30 overflow-y-auto shadow-lg"
      :class="{ 'open': sidebarOpen, 'closed': !sidebarOpen }">
      <!-- Logo section -->
      <div class="px-5 py-4 border-b border-gray-200">
        <div class="flex items-center gap-3">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg">
            <img src="/F.svg" alt="Furnisync" class="w-20 h-20" />
          </div>
          <div class="leading-tight">
          <span class="portal-brand text-orange-400">FURNISYNC</span>
            <p class="text-xs text-gray-600">Platform</p>
          </div>
        </div>
      </div>
  
      <!-- Navigation by Module -->
      <nav class="flex-1 overflow-y-auto py-4">
        <!-- Loading State -->
        <div v-if="loadingNavigation" class="px-4 space-y-2">
          <Skeleton height="40px" class="rounded-lg" />
          <Skeleton height="40px" class="rounded-lg" />
          <Skeleton height="40px" class="rounded-lg" />
        </div>
  
        <!-- Module Accordions -->
        <template v-else>
          <div v-if="groupedNavigation.length > 0" class="px-2 space-y-1">
            <div v-for="moduleGroup in groupedNavigation" :key="moduleGroup.module" class="mb-3">
              <!-- Module Header (Accordion Toggle) -->
              <button v-if="moduleGroup.items.length > 0" @click="toggleModule(moduleGroup.module)"
                class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors group">
                <div class="flex items-center space-x-2">
                  <span class="uppercase tracking-wider text-xs font-bold">{{
                    formatModuleName(moduleGroup.module) }}</span>
                </div>
                <i :class="[
                          'pi transition-transform',
                          expandedModules[moduleGroup.module] ? 'pi-chevron-down' : 'pi-chevron-right'
                        ]"></i>
              </button>
  
              <!-- Module Items (Accordion Content) -->
              <transition name="accordion">
                <div v-if="expandedModules[moduleGroup.module]" class="space-y-1 mt-1">
                  <div v-for="item in moduleGroup.items" :key="item.id" class="space-y-1">
                    <button v-if="item.children?.length" @click="toggleSection(item.id)"
                      class="w-full flex items-center justify-between px-6 py-2.5 mx-1 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100 transition-colors">
                      <div class="flex items-center space-x-3">
                        <i :class="[item.icon || 'pi pi-folder', 'w-4 text-gray-400']"></i>
                        <span>{{ item.display_name }}</span>
                      </div>
                      <i :class="[
                                                  'pi text-xs transition-transform',
                                                  expandedSections[item.id] ? 'pi-chevron-down' : 'pi-chevron-right'
                                              ]"></i>
                    </button>
  
                    <Link v-else :href="item.route_path"
                      class="flex items-center justify-between px-7 py-2.5 mx-1 rounded-lg text-sm font-medium text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors group"
                      :class="{ 'bg-blue-50 text-blue-600': isActive(item.route_path) }">
                    <div class="flex items-center space-x-3 flex-1">
                      <i :class="[item.icon || 'pi pi-circle', 'w-4 text-gray-400 group-hover:text-blue-500']"></i>
                      <span>{{ item.display_name }}</span>
                    </div>
                    <Badge v-if="item.badge_count && item.badge_count > 0" :value="item.badge_count" severity="danger"
                      size="small" />
                    </Link>
  
                    <div v-if="item.children?.length && expandedSections[item.id]" class="space-y-1 ml-5">
                      <Link v-for="child in item.children" :key="child.id" :href="child.route_path"
                        class="flex items-center justify-between px-6 py-2 mx-1 rounded-lg text-sm font-medium text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-colors group"
                        :class="{ 'bg-blue-50 text-blue-600': isActive(child.route_path) }">
                      <div class="flex items-center space-x-3 flex-1">
                        <i :class="[child.icon || 'pi pi-circle', 'w-4 text-gray-300 group-hover:text-blue-500']"></i>
                        <span>{{ child.display_name }}</span>
                      </div>
                      <Badge v-if="child.badge_count && child.badge_count > 0" :value="child.badge_count"
                        severity="danger" size="small" />
                      </Link>
                    </div>
                  </div>
                </div>
              </transition>
            </div>
          </div>
  
          <!-- Empty State -->
          <div v-else class="px-4 py-8 text-center">
            <i class="pi pi-inbox text-4xl text-gray-300 mb-3"></i>
            <p class="text-sm text-gray-500">No modules available</p>
            <p class="text-xs text-gray-400 mt-1">Contact your administrator</p>
          </div>
        </template>
      </nav>
    </aside>
  
    <!-- Main Content -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
      <!-- Top Header -->
      <header
        class="bg-white border-b border-gray-200 py-4 px-6 flex items-center justify-end sticky top-0 z-20 shadow-sm">
  
  
        <!-- Header Actions -->
        <div class="flex items-center space-x-4">
          <!-- Notifications -->
          <Button icon="pi pi-bell" severity="secondary" text rounded
            :badge="unreadCount > 0 ? unreadCount.toString() : undefined" badgeSeverity="danger"
            @click="toggleNotifications" />
          <OverlayPanel ref="notificationPanel" class="w-[380px] p-0 rounded-2xl shadow-xl border border-gray-100">
            <div class="px-4 pt-4 pb-3 border-b border-gray-100 flex items-center justify-between">
              <div class="font-semibold text-gray-900">Notifications</div>
              <Button label="Mark all as read" size="small" text class="text-xs"
                :disabled="unreadCount === 0 || notificationsLoading" @click="markAllNotificationsRead" />
            </div>
  
            <div class="px-4 pt-3">
              <div class="flex items-center gap-4 text-sm">
                <button class="pb-2 border-b-2 transition"
                  :class="activeNotifTab === 'inbox' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500'"
                  @click="activeNotifTab = 'inbox'">
                  Inbox <span v-if="unreadCount" class="ml-1 text-xs bg-green-500 text-white rounded-full px-2 py-0.5">{{
                    unreadCount }}</span>
                </button>
                <button class="pb-2 border-b-2 transition"
                  :class="activeNotifTab === 'general' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500'"
                  @click="activeNotifTab = 'general'">
                  General
                </button>
                <button class="pb-2 border-b-2 transition"
                  :class="activeNotifTab === 'archived' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500'"
                  @click="activeNotifTab = 'archived'">
                  Archived
                </button>
              </div>
            </div>
  
            <div class="max-h-[420px] overflow-y-auto">
              <div v-if="notificationsLoading" class="p-4 space-y-3">
                <Skeleton height="56px" class="rounded-xl" />
                <Skeleton height="56px" class="rounded-xl" />
                <Skeleton height="56px" class="rounded-xl" />
              </div>
  
              <div v-else-if="filteredNotifications.length === 0" class="p-6 text-center text-sm text-gray-500">
                No notifications here yet.
              </div>
  
              <button v-for="notif in filteredNotifications" :key="notif.id"
                class="w-full text-left px-4 py-3 flex items-start gap-3 hover:bg-blue-50/50 transition"
                @click="openNotification(notif)">
                <div class="relative">
                  <div
                    class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center text-blue-700 font-semibold text-xs">
                    {{ getNotifInitials(notif) }}
                  </div>
                  <span v-if="!notif.is_read" class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full"></span>
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-gray-900 truncate">{{ notif.title }}</p>
                    <span class="text-xs text-gray-400 whitespace-nowrap">{{ formatTimeAgo(notif.created_at) }}</span>
                  </div>
                  <p class="text-xs text-gray-600 truncate">{{ notif.message || 'Tap to view' }}</p>
                </div>
              </button>
            </div>
          </OverlayPanel>
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
  
      <!-- Main Content Area -->
      <main class="flex-1 overflow-y-auto p-6 bg-transparent">
        <slot />
      </main>
    </div>
  
    <!-- User Dialog -->
    <UserDialog ref="userDialogRef" />
  
    <!-- Global API Response Dialog -->
    <Dialog v-model:visible="responseDialog.visible" modal :closable="false" :showHeader="false" class="w-full max-w-md"
      contentClass="p-0">
      <div class="p-6 text-center">
        <div :class="[
            'mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full',
            responseDialog.severity === 'success'
              ? 'bg-green-50 text-green-600'
              : responseDialog.severity === 'error'
                ? 'bg-red-50 text-red-600'
                : responseDialog.severity === 'warn'
                  ? 'bg-amber-50 text-amber-600'
                  : 'bg-blue-50 text-blue-600'
          ]">
          <i :class="[
              'pi text-2xl',
              responseDialog.severity === 'success'
                ? 'pi-check'
                : responseDialog.severity === 'error'
                  ? 'pi-times'
                  : responseDialog.severity === 'warn'
                    ? 'pi-exclamation-triangle'
                    : 'pi-info-circle'
            ]"></i>
        </div>
        <div class="text-lg font-semibold text-gray-900">{{ responseDialog.title }}</div>
        <div class="mt-2 text-sm text-gray-600 whitespace-pre-line">
          {{ responseDialog.message }}
        </div>
        <div class="mt-6 flex justify-center">
          <Button label="OK" class="px-8" @click="responseDialog.visible = false" />
        </div>
      </div>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { startCase, toLower, groupBy } from 'lodash'
import Skeleton from 'primevue/skeleton'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import OverlayPanel from 'primevue/overlaypanel'
import axiosClient from '@/axios'
import { useAuthStore } from '@/stores/auth'
import UserDialog from '@/Components/dialogs/UserDialog.vue'
import { onResponseDialog } from '@/utils/responseDialogBus'

const page = usePage()
const currentPath = computed(() => String(page.url || '').split('?')[0] || '/')
const pageTitle = computed(() => page.props?.title || '')
const authStore = useAuthStore()
const isAuthenticated = computed(() => authStore.isAuthenticated)
const isBooting = ref(true)
const userDialogRef = ref(null)
const loadingNavigation = ref(false)
const sidebarOpen = ref(localStorage.getItem('sidebarOpen') !== 'false')
const notificationPanel = ref()
const notifications = ref<any[]>([])
const notificationsLoading = ref(false)
const unreadCount = ref(0)
const activeNotifTab = ref<'inbox' | 'general' | 'archived'>('inbox')
const notificationPoller = ref<number | null>(null)
const responseDialog = ref({
  visible: false,
  severity: 'success' as 'success' | 'error' | 'warn' | 'info',
  title: 'Success',
  message: ''
})
let responseDialogUnsub: (() => void) | null = null

// Track expanded/collapsed modules
const expandedModules = ref<Record<string, boolean>>({
  inventory: true,
  procurement: true,
  merchandising: true,
  hr: false,
  admin: false,
  supplier: true,
})
const expandedSections = ref<Record<string, boolean>>({})

// Load saved state on mount
onMounted(async () => {
  const storedToken = localStorage.getItem('auth_token')
  if (!storedToken) {
    router.visit('/login')
    return
  }

  if (!authStore.user) {
    try {
      await authStore.fetchCurrentUser()
    } catch (error) {
      // fetchCurrentUser handles logout/redirection on 401
      return
    }
  }

  if (!isAuthenticated.value) {
    router.visit('/login')
    return
  }
  isBooting.value = false

  const saved = localStorage.getItem('expandedModules')
  if (saved) {
    try {
      expandedModules.value = JSON.parse(saved)
    } catch (e) {
      // Use defaults
    }
  }
  const savedSections = localStorage.getItem('expandedSections')
  if (savedSections) {
    try {
      expandedSections.value = JSON.parse(savedSections)
    } catch (e) {
      // ignore
    }
  }
  window.addEventListener('keydown', handleKeyboardShortcut)
  loadNotifications()
  if (!notificationPoller.value) {
    notificationPoller.value = window.setInterval(() => {
      loadNotifications()
    }, 45000)
  }
  responseDialogUnsub = onResponseDialog((payload) => {
    responseDialog.value = {
      visible: true,
      severity: payload.severity,
      title: payload.title || (payload.severity === 'error' ? 'Error' : 'Success'),
      message: payload.message
    }
  })
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyboardShortcut)
  if (notificationPoller.value) {
    clearInterval(notificationPoller.value)
    notificationPoller.value = null
  }
  if (responseDialogUnsub) {
    responseDialogUnsub()
    responseDialogUnsub = null
  }
})

// Toggle module accordion
const toggleModule = (module: string) => {
  expandedModules.value[module] = !expandedModules.value[module]
  localStorage.setItem('expandedModules', JSON.stringify(expandedModules.value))
}

const toggleSection = (sectionId: number) => {
  expandedSections.value[sectionId] = !expandedSections.value[sectionId]
  localStorage.setItem('expandedSections', JSON.stringify(expandedSections.value))
}

// Keyboard shortcut: Ctrl+B to toggle sidebar
const handleKeyboardShortcut = (event: KeyboardEvent) => {
  if (event.ctrlKey && event.key === 'b') {
    event.preventDefault()
    sidebarOpen.value = !sidebarOpen.value
    localStorage.setItem('sidebarOpen', sidebarOpen.value.toString())
  }
}

// Group navigation items by module
const supplierFallbackNavigation = [
  {
    id: -101,
    name: 'supplier.dashboard',
    display_name: "Supplier's Dashboard",
    module: 'supplier',
    route_name: 'supplier.dashboard',
    route_path: '/supplier-portal/dashboard',
    icon: 'pi pi-home',
    parent_id: null,
    display_order: 1,
    section: 'General',
    meta: null,
    is_active: true,
    badge_count: 0,
  },
  {
    id: -102,
    name: 'supplier.purchase_orders',
    display_name: 'Purchase Orders',
    module: 'supplier',
    route_name: 'supplier.pos',
    route_path: '/supplier-portal/pos',
    icon: 'pi pi-shopping-cart',
    parent_id: null,
    display_order: 2,
    section: 'General',
    meta: null,
    is_active: true,
    badge_count: 0,
  },
  {
    id: -103,
    name: 'supplier.rfqs',
    display_name: 'RFQs',
    module: 'supplier',
    route_name: 'supplier.rfqs',
    route_path: '/supplier-portal/rfqs',
    icon: 'pi pi-file',
    parent_id: null,
    display_order: 3,
    section: 'General',
    meta: null,
    is_active: true,
    badge_count: 0,
  },
  {
    id: -104,
    name: 'supplier.transactions',
    display_name: 'Transactions',
    module: 'supplier',
    route_name: 'supplier.transactions',
    route_path: '/supplier-portal/transactions',
    icon: 'pi pi-credit-card',
    parent_id: null,
    display_order: 4,
    section: 'General',
    meta: null,
    is_active: true,
    badge_count: 0,
  },
]

// const storeFallbackNavigation = [
//   {
//     id: -201,
//     name: 'store.dashboard',
//     display_name: 'Store Dashboard',
//     module: 'store',
//     route_name: 'store.dashboard',
//     route_path: '/store/index',
//     icon: 'pi pi-home',
//     parent_id: null,
//     display_order: 1,
//     section: 'General',
//     meta: null,
//     is_active: true,
//     badge_count: 0,
//   },
//   {
//     id: -202,
//     name: 'store.registration',
//     display_name: 'Store Registration',
//     module: 'store',
//     route_name: 'StoreVerification',
//     route_path: '/store/store/verification',
//     icon: 'pi pi-building',
//     parent_id: null,
//     display_order: 2,
//     section: 'General',
//     meta: null,
//     is_active: true,
//     badge_count: 0,
//   },
//   {
//     id: -203,
//     name: 'store.roles_permissions',
//     display_name: 'Roles & Permissions',
//     module: 'store',
//     route_name: 'store.role-permissions',
//     route_path: '/store/roles-permissions',
//     icon: 'pi pi-shield',
//     parent_id: null,
//     display_order: 3,
//     section: 'General',
//     meta: null,
//     is_active: true,
//     badge_count: 0,
//   },
// ]

const groupedNavigation = computed(() => {
  const isSupplierRole = (authStore.userRole || '').toLowerCase().includes('supplier')
  const isStoreRole = (authStore.userRole || '').toLowerCase().includes('store')
  let baseNavigation = authStore.navigation.length > 0
    ? [...authStore.navigation]
    : []

  if (baseNavigation.length === 0) {
    if (isSupplierRole) {
      baseNavigation = [...supplierFallbackNavigation]
    } else if (isStoreRole) {
      // baseNavigation = [...storeFallbackNavigation]
    }
  }

  // if (isStoreRole) {
  //   const existingPaths = new Set(baseNavigation.map((item: any) => item.route_path))
  //   const missingStoreItems = storeFallbackNavigation.filter((item) => !existingPaths.has(item.route_path))
  //   if (missingStoreItems.length > 0) {
  //     baseNavigation = [...baseNavigation, ...missingStoreItems]
  //   }
  // }

  const activeItems = baseNavigation.filter((item: any) => item.is_active)

  const itemsById = new Map<number, any>()
  activeItems.forEach((item: any) => itemsById.set(item.id, item))

  const childrenByParent: Record<number, any[]> = {}
  activeItems.forEach((item: any) => {
    if (item.parent_id) {
      if (!childrenByParent[item.parent_id]) {
        childrenByParent[item.parent_id] = []
      }
      childrenByParent[item.parent_id]!.push(item)
    }
  })

  Object.values(childrenByParent).forEach((children) => {
    children.sort((a, b) => a.display_order - b.display_order)
  })

  const parents = activeItems.filter((item: any) => !item.parent_id)
  const orphans = activeItems.filter((item: any) => item.parent_id && !itemsById.has(item.parent_id))

  const filtered = [...parents, ...orphans].map((item: any) => ({
    ...item,
    children: childrenByParent[item.id] || [],
  }))
    .filter((item: any) => {
      if (item.meta?.is_group && (!item.children || item.children.length === 0)) {
        return false
      }
      return true
    })

  const grouped: Array<{ module: string; items: any[] }> = []
  const itemsByModule = groupBy(filtered, 'module')

  const moduleOrder = ['store', 'supplier', 'inventory', 'procurement', 'merchandising', 'human resources']

  for (const module of moduleOrder) {
    if (itemsByModule[module]) {
      grouped.push({
        module,
        items: (itemsByModule[module] as any[]).sort((a, b) => a.display_order - b.display_order)
      })
    }
  }

  // Add any custom modules not in moduleOrder
  for (const module in itemsByModule) {
    if (!moduleOrder.includes(module)) {
      grouped.push({
        module,
        items: (itemsByModule[module] as any[]).sort((a, b) => a.display_order - b.display_order)
      })
    }
  }

  return grouped
})



// Format module name
const formatModuleName = (module: string): string => {
  return module
    .replace(/_/g, ' ')
    .split(' ')
    .map(word => startCase(toLower(word)))
    .join(' ')
}

// Check if route is active
const isActive = (routePath: string): boolean => {
  return currentPath.value === routePath
}

// User data
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
const user: User | null = userData ? JSON.parse(userData) : null

const fullName = computed(() => {
  const rawFirst = authStore.user?.first_name || authStore.user?.fname || ''
  const rawLast = authStore.user?.last_name || authStore.user?.lname || ''
  const fallbackName = authStore.user?.name || authStore.user?.full_name || ''
  const first = startCase(toLower(rawFirst))
  const last = startCase(toLower(rawLast))
  return `${first} ${last}`.trim() || fallbackName || 'User'
})

const userInitials = computed(() => {
  const first = authStore.user?.first_name?.[0] || authStore.user?.fname?.[0] || ''
  const last = authStore.user?.last_name?.[0] || authStore.user?.lname?.[0] || ''
  return (first + last).toUpperCase() || 'U'
})

const roleDisplay = computed(() => {
  const role = authStore.user?.role
  if (typeof role === 'string') return startCase(role)
  return startCase(authStore.user?.role_name || role?.display_name || role?.name || 'User')
})

// Breadcrumbs
// User dialog
const openUserDialog = (event: MouseEvent) => {
  if (userDialogRef.value) {
    (userDialogRef.value as any).toggle(event)
  }
}

const filteredNotifications = computed(() => {
  if (activeNotifTab.value === 'inbox') {
    return notifications.value.filter((n) => !n.is_read)
  }
  if (activeNotifTab.value === 'archived') {
    return notifications.value.filter((n) => n.is_read)
  }
  return notifications.value
})

const toggleNotifications = (event: MouseEvent) => {
  if (notificationPanel.value) {
    notificationPanel.value.toggle(event)
  }
}

const loadNotifications = async () => {
  if (!isAuthenticated.value) return
  notificationsLoading.value = true
  try {
    const response = await axiosClient.get('/api/notifications', { params: { per_page: 20 } })
    const payload = response?.data || response
    notifications.value = payload?.data || []
    unreadCount.value = payload?.meta?.unread_count ?? notifications.value.filter((n: any) => !n.is_read).length
  } catch (error) {
    console.error('Failed to load notifications', error)
  } finally {
    notificationsLoading.value = false
  }
}

const markAllNotificationsRead = async () => {
  if (!isAuthenticated.value) return
  if (notificationsLoading.value) return
  try {
    await axiosClient.put('/api/notifications/mark-all-read')
    notifications.value = notifications.value.map((n: any) => ({ ...n, is_read: true, read_at: new Date().toISOString() }))
    unreadCount.value = 0
  } catch (error) {
    console.error('Failed to mark notifications as read', error)
  }
}

const openNotification = async (notif: any) => {
  if (!isAuthenticated.value) return
  if (!notif.is_read) {
    try {
      await axiosClient.put(`/api/notifications/${notif.id}/read`)
      notif.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (error) {
      console.error('Failed to mark notification as read', error)
    }
  }
  if (notif.link) {
    router.visit(notif.link)
    if (notificationPanel.value) notificationPanel.value.hide()
  }
}

const getNotifInitials = (notif: any) => {
  const text = notif.title || 'N'
  const parts = text.trim().split(' ')
  if (parts.length === 1) return parts[0][0]?.toUpperCase() || 'N'
  return `${parts[0][0] || ''}${parts[1][0] || ''}`.toUpperCase() || 'N'
}

const formatTimeAgo = (iso: string) => {
  if (!iso) return ''
  const now = new Date()
  const then = new Date(iso)
  const diff = Math.floor((now.getTime() - then.getTime()) / 1000)
  if (diff < 60) return 'Just now'
  if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
  return then.toLocaleDateString()
}

watch(isAuthenticated, (value) => {
  if (isBooting.value) return
  if (value) return
  if (notificationPoller.value) {
    clearInterval(notificationPoller.value)
    notificationPoller.value = null
  }
  notifications.value = []
  unreadCount.value = 0
  router.visit('/login')
})

</script>

<style scoped>
@font-face {
  font-family: 'Barabara';
  src: url('/fonts/BARABARA-final.otf') format('opentype');
  font-weight: 400;
  font-style: normal;
  font-display: swap;
}

.portal-brand {
  font-family: 'Barabara', sans-serif;
}

.sidebar {
  transition: all 0.3s ease;
}

/* .router-link-active {
  @apply bg-blue-50 text-blue-600 font-semibold;
} */

/* Accordion animation */
.accordion-enter-active,
.accordion-leave-active {
  transition: all 0.2s ease;
  max-height: 500px;
  overflow: hidden;
}

.accordion-enter-from,
.accordion-leave-to {
  opacity: 0;
  max-height: 0;
  overflow: hidden;
}

.accordion-enter-to,
.accordion-leave-from {
  opacity: 1;
  max-height: 500px;
}
</style>
