<template>
  <Head v-if="pageTitle" :title="pageTitle" />
  <div
    class="flex h-screen w-full max-w-[100vw] overflow-hidden bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.12),_transparent_38%),linear-gradient(180deg,_#eff6ff_0%,_#f8fafc_42%,_#ffffff_100%)]">
    <!-- Sidebar -->
    <aside class="sidebar bg-white flex flex-col z-30 overflow-y-auto shadow-lg"
      :class="{ 'open': sidebarOpen, 'closed': !sidebarOpen }">
      <!-- Logo section -->
      <div class="px-5 py-4">
        <div class="flex items-center justify-between gap-3">
          <button
            type="button"
            class="flex items-center gap-3 min-w-0 rounded-2xl bg-transparent"
            :class="!sidebarOpen ? 'hover:bg-orange-400' : ''"
            :disabled="sidebarOpen"
            @click="!sidebarOpen && (sidebarOpen = true)"
          
          >
          <div class="flex items-center justify-center w-10 h-10 rounded-lg shrink-0">
            <img src="/F.svg" alt="Furnisync" class="w-20 h-20" />
          </div>
          <div v-if="sidebarOpen" class="leading-tight">
            <span class="portal-brand text-orange-400">FURNISYNC</span>
          </div>
          </button>
          <Button
            v-if="sidebarOpen"
            icon="pi pi-caret-left"
            size="small"
            text
            rounded
            class="hidden lg:inline-flex shrink-0"
            aria-label="Collapse sidebar"
            @click="sidebarOpen = false"
            v-tooltip="'Collapse sidebar'"
          />
        </div>
      </div>
  
      <!-- Navigation by Module -->
      <nav v-if="sidebarOpen" class="flex-1 overflow-y-auto py-4">
        <!-- Loading State -->
        <div v-if="loadingNavigation" class="px-4 space-y-2">
          <Skeleton height="40px" class="rounded-lg" />
          <Skeleton height="40px" class="rounded-lg" />
          <Skeleton height="40px" class="rounded-lg" />
        </div>
  
        <!-- Module Accordions -->
        <template v-else>
          <div v-if="groupedNavigation.length > 0" class="px-1">
            <Accordion multiple :value="expandedModuleValues" class="system-navigation-accordion">
              <AccordionPanel v-for="moduleGroup in groupedNavigation" :key="moduleGroup.module" size="small"
                :value="moduleGroup.module">
                <AccordionHeader size="small">
                  <span class="uppercase tracking-wider text-xs font-bold text-gray-700">
                    {{ formatModuleName(moduleGroup.module) }}
                  </span>
                </AccordionHeader>
                <AccordionContent>
                  <div class="space-y-1 mt-1">
                  <div v-for="item in moduleGroup.items" :key="item.id" class="space-y-1">
                    <Link v-if="item.route_path && !String(item.route_path).startsWith('#')" :href="item.route_path"
                      class="flex items-center justify-between px-7 py-2.5 mx-1 rounded-lg text-sm font-medium text-gray-600 hover:bg-orange-50 hover:text-orange-600 transition-colors group"
                      :class="{ 'bg-orange-50 text-orange-600': isActive(item.route_path) }">
                    <div class="flex items-center space-x-3 flex-1">
                      <i :class="[
                        item.icon || 'pi pi-circle',
                        'w-4',
                        isActive(item.route_path)
                          ? 'text-orange-600'
                          : 'text-gray-400 group-hover:text-orange-500'
                      ]"></i>
                      <span>{{ item.display_name }}</span>
                    </div>
                    <Badge v-if="item.badge_count && item.badge_count > 0" :value="item.badge_count" severity="danger"
                      size="small" />
                    </Link>
                  </div>
                  </div>
                </AccordionContent>
              </AccordionPanel>
            </Accordion>
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
        class="bg-white border-b border-gray-200 py-4 px-6 flex items-center justify-between lg:justify-end sticky top-0 z-20 shadow-sm">
        <div class="flex items-center gap-3 lg:hidden">
          <Button icon="pi pi-bars" size="small" text rounded severity="secondary" @click="sidebarOpen = !sidebarOpen" />
          <div class="text-sm font-semibold text-gray-700">Menu</div>
        </div>
  
  
        <!-- Header Actions -->
        <div class="flex items-center space-x-4">
          <!-- Notifications -->
          <Button icon="pi pi-bell" severity="secondary" text rounded
            :badge="unreadCount > 0 ? unreadCount.toString() : undefined" badgeSeverity="danger"
            @click="toggleNotifications" />
          <Popover ref="notificationPanel" class="w-[380px] p-0 rounded-2xl shadow-xl border border-gray-100">
            <div class="px-4 pt-4 pb-3 border-b border-gray-100 flex items-center justify-between">
              <div class="font-semibold text-gray-900">Notifications</div>
              <Button label="Mark all as read" size="small" text class="text-xs"
                :disabled="unreadCount === 0 || notificationsLoading" @click="markAllNotificationsRead" />
            </div>
  
            <div class="px-4 pt-3">
              <div class="flex items-center gap-4 text-sm">
                <button class="pb-2 border-b-2 transition"
                  :class="activeNotifTab === 'inbox' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500'"
                  @click="activeNotifTab = 'inbox'">
                  Inbox <span v-if="unreadCount" class="ml-1 text-xs bg-green-500 text-white rounded-full px-2 py-0.5">{{
                    unreadCount }}</span>
                </button>
                <button class="pb-2 border-b-2 transition"
                  :class="activeNotifTab === 'general' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500'"
                  @click="activeNotifTab = 'general'">
                  General
                </button>
                <button class="pb-2 border-b-2 transition"
                  :class="activeNotifTab === 'archived' ? 'border-orange-500 text-orange-600 font-semibold' : 'border-transparent text-gray-500'"
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
                class="w-full text-left px-4 py-3 flex items-start gap-3 hover:bg-orange-50/50 transition"
                @click="openNotification(notif)">
                <div class="relative">
                  <div
                    class="w-10 h-10 rounded-2xl bg-gradient-to-br from-orange-100 to-orange-50 flex items-center justify-center text-orange-700 font-semibold text-xs">
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
          </Popover>
          <!-- User Profile -->
          <div class="border-l border-gray-200 pl-4 cursor-pointer select-none" @click="openUserDialog">
            <div class="flex items-center space-x-3 hover:bg-gray-50 px-2 py-1 rounded-lg transition">
              <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center">
                <span class="text-sm font-semibold text-orange-600">{{ userInitials }}</span>
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
      <main class="flex-1 overflow-y-auto p-6 bg-transparent" :class="{ 'merchandising-hide-delete': isMerchandising }">
        <slot />
      </main>
    </div>
  
    <!-- User Dialog -->
    <UserDialog ref="userDialogRef" />

    <!-- Mobile Sidebar Overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-20 bg-black/40 lg:hidden"
      @click="sidebarOpen = false"
    ></div>
  
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
                  : 'bg-orange-50 text-orange-600'
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
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import Popover from 'primevue/popover'
import axiosClient from '@/axios'
import { useAuthStore } from '@/stores/auth'
import UserDialog from '@/Components/dialogs/UserDialog.vue'
import { onResponseDialog } from '@/utils/responseDialogBus'

const page = usePage()
const currentPath = computed(() => String(page.url || '').split('?')[0] || '/')
const pageTitle = computed(() => page.props?.title || '')
const isMerchandising = computed(() => currentPath.value.startsWith('/merchandising'))
const authStore = useAuthStore()
const isAuthenticated = computed(() => authStore.isAuthenticated)
const isBooting = ref(true)
const userDialogRef = ref(null)
const loadingNavigation = ref(false)
const enabledModules = ref<string[] | null>(null)
const sidebarOpen = ref(true)
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
  admin: true,
  inventory: true,
  warehouse: true,
  procurement: true,
  merchandising: true,
  hr: true,
  crm: true,
  supplier: true,
})
const expandedModuleValues = computed<string[]>({
  get: () => Object.entries(expandedModules.value)
    .filter(([, expanded]) => expanded)
    .map(([module]) => module),
  set: (values) => {
    const openModules = new Set(values)
    const nextState: Record<string, boolean> = {}
    Object.keys(expandedModules.value).forEach((module) => {
      nextState[module] = openModules.has(module)
    })
    expandedModules.value = nextState
    localStorage.setItem('expandedModules', JSON.stringify(nextState))
  }
})

watch(
  () => authStore.systemModules,
  (modules) => {
    modules.forEach((module: any) => {
      const key = String(module?.key || '').trim().toLowerCase()
      if (key && !(key in expandedModules.value)) {
        expandedModules.value[key] = false
      }
    })
  },
  { immediate: true, deep: true }
)

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

  const normalizedRole = (authStore.userRole || '').toLowerCase()
  const requiresRbacNavigation =
    normalizedRole !== 'super_admin' &&
    !normalizedRole.includes('supplier') &&
    !normalizedRole.includes('customer')

  if (requiresRbacNavigation) {
    loadingNavigation.value = true
    try {
      await authStore.loadPermissions()
    } catch (error) {
      console.warn('Failed to load navigation during layout bootstrap:', error)
    } finally {
      loadingNavigation.value = false
    }
  } else {
    loadingNavigation.value = false
  }

  // If this is a store-scoped role, pull enabled modules so we can hide nav for disabled modules
  const roleName = (authStore.userRole || '').toLowerCase()
  if (roleName.includes('store')) {
    try {
      const res = await axiosClient.get('/api/store/modules')
      enabledModules.value = res.data?.data?.enabled_modules ?? []
    } catch (error) {
      console.error('Failed to load store modules', error)
      enabledModules.value = null
    }
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
  window.addEventListener('keydown', handleKeyboardShortcut)
  loadNotifications()
  if (!notificationPoller.value) {
    notificationPoller.value = window.setInterval(() => {
      loadNotifications()
    }, 45000)
  }
  responseDialogUnsub = onResponseDialog((payload) => {
    // Allow pages to temporarily suppress global error dialogs by setting
    // window.__suppressResponseErrors for a short period.
    if (payload.severity === 'error' && (window as any).__suppressResponseErrors) {
      return
    }
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

watch(currentPath, () => {
  if (window.innerWidth < 1024) {
    sidebarOpen.value = false
  }
})

// Keyboard shortcut: Ctrl+B to toggle sidebar
const handleKeyboardShortcut = (event: KeyboardEvent) => {
  if (event.ctrlKey && event.key === 'b') {
    event.preventDefault()
    sidebarOpen.value = !sidebarOpen.value
    localStorage.setItem('sidebarOpen', sidebarOpen.value.toString())
  }
}

const groupedNavigation = computed(() => {
  if (loadingNavigation.value && authStore.navigation.length === 0) {
    return []
  }

  const isSupplierRole = (authStore.userRole || '').toLowerCase().includes('supplier')
  const isStoreRole = (authStore.userRole || '').toLowerCase().includes('store')
  const isCustomerRole = (authStore.userRole || '').toLowerCase().includes('customer')
  const isSuperAdminRole = (authStore.userRole || '').toLowerCase() === 'super_admin'
  let baseNavigation = authStore.navigation.length > 0
    ? [...authStore.navigation]
    : []

  baseNavigation = baseNavigation.filter((item: any) => {
    const name = String(item?.name || '').toLowerCase()
    const path = String(item?.route_path || '').toLowerCase()
    return name !== 'account.profile' && !['/profile', '/shop/profile', '/supplier-portal/profile'].includes(path)
  })

  // CRM is an independent module. Keep compatibility with the existing
  // sales.crm permission/navigation record while rendering it outside Sales.
  baseNavigation = baseNavigation.map((item: any) => {
    const name = String(item?.name || '').toLowerCase()
    const routeName = String(item?.route_name || '').toLowerCase()
    const routePath = String(item?.route_path || '').toLowerCase()
    const isCrmItem = name === 'sales.crm'
      || name.startsWith('crm.')
      || routeName === 'sales.crm'
      || routeName.startsWith('crm.')
      || routePath.startsWith('/crm')

    if (!isCrmItem) return item

    return {
      ...item,
      module: 'crm',
      route_name: name === 'sales.crm' || routeName === 'sales.crm' ? 'crm.dashboard' : item.route_name,
      route_path: name === 'sales.crm' || routeName === 'sales.crm' ? '/crm/dashboard' : item.route_path,
      display_name: name === 'sales.crm' ? 'Dashboard' : item.display_name,
      is_active: true,
    }
  })

  const hasCrmNavigation = baseNavigation.some((item: any) => String(item?.module || '').toLowerCase() === 'crm')
  if (!hasCrmNavigation && authStore.hasPermission('sales.crm.view')) {
    baseNavigation.push({
      id: -905,
      name: 'crm.dashboard',
      display_name: 'Dashboard',
      module: 'crm',
      route_name: 'crm.dashboard',
      route_path: '/crm/dashboard',
      icon: 'pi pi-users',
      parent_id: null,
      display_order: 1,
      section: 'General',
      meta: null,
      is_active: true,
      badge_count: 0,
    })
  }

  if (baseNavigation.length === 0) {
    // Navigation is supplied by the backend permission response.
    // Keep the list empty when no permitted items are returned.
  }

  if (isSuperAdminRole) {
    const superAdminUsersItem = {
      id: -903,
      name: 'admin.users',
      display_name: 'Users',
      module: 'admin',
      route_name: 'admin.users',
      route_path: '/admin/users',
      icon: 'pi pi-users',
      parent_id: null,
      display_order: 50,
      section: 'General',
      meta: null,
      is_active: true,
      badge_count: 0,
    }

    const existingPaths = new Set(baseNavigation.map((item: any) => item.route_path))
    if (!existingPaths.has(superAdminUsersItem.route_path)) {
      baseNavigation = [...baseNavigation, superAdminUsersItem]
    }
  }

  if (isSupplierRole) {
    const supplierPaymentItem = {
      id: -902,
      name: 'supplier.payment_account',
      display_name: 'Payment Account',
      module: 'supplier',
      route_name: 'supplier.payment-account',
      route_path: '/supplier-portal/payment-account',
      icon: 'pi pi-wallet',
      parent_id: null,
      display_order: 998,
      section: 'General',
      meta: null,
      is_active: true,
      badge_count: 0,
    }

    const existingPaths = new Set(baseNavigation.map((item: any) => item.route_path))
    if (!existingPaths.has(supplierPaymentItem.route_path)) {
      baseNavigation = [...baseNavigation, supplierPaymentItem]
    }
  }

  let activeItems = baseNavigation.filter((item: any) => item.is_active)

  if (!isSupplierRole) {
    activeItems = activeItems.filter((item: any) => String(item?.module || '').toLowerCase() !== 'supplier')
  }

  if (isSupplierRole) {
    activeItems = activeItems.map((item: any) => {
      if (item?.name === 'supplier.payment_account' || item?.route_name === 'supplier.payment-account') {
        return {
          ...item,
          route_path: '/supplier-portal/payment-account',
        }
      }
      return item
    })
  }

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

  const filtered = activeItems.map((item: any) => ({
    ...item,
    children: childrenByParent[item.id] || [],
  }))
    .filter((item: any) => {
      if (item.meta?.is_group && (!item.children || item.children.length === 0)) {
        return false
      }

      const routePath = String(item.route_path || '').trim()
      return routePath !== '' && !routePath.startsWith('#')
    })

  const grouped: Array<{ module: string; items: any[] }> = []
  const itemsByModule = groupBy(filtered, 'module')

  const moduleOrder = ['admin', 'store', 'supplier', 'inventory', 'warehouse', 'procurement', 'merchandising', 'hr', 'finance','logistics','sales', 'crm']

  const catalogModules = authStore.systemModules
    .map((module: any) => String(module?.key || '').trim().toLowerCase())
    .filter(module => Boolean(module) && (module !== 'supplier' || isSupplierRole))
  const orderedModules = [
    ...moduleOrder.filter(module => catalogModules.includes(module) || Boolean(itemsByModule[module]?.length)),
    ...catalogModules.filter(module => !moduleOrder.includes(module)),
  ]

  // The module catalog contains every system module, but the sidebar should
  // only show groups that have at least one navigation item for this user.
  for (const module of orderedModules.filter((module) => Boolean(itemsByModule[module]?.length))) {
    grouped.push({
      module,
      items: [...(itemsByModule[module] || [])].sort((a, b) => a.display_order - b.display_order)
    })
  }

  // Include legacy navigation modules that are not in the module catalog yet.
  for (const module in itemsByModule) {
    if (!orderedModules.includes(module) && itemsByModule[module]?.length) {
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
  const catalogName = authStore.systemModules.find((item: any) => String(item?.key || '').toLowerCase() === module)?.name
  if (catalogName) return catalogName
  if (module === 'warehouse') return 'Warehouse'
  if (module == 'hr') return 'Human Resources'
  if (module === 'merchandising') return 'Merchandise'

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

const isSupplierUser = computed(() => {
  return (authStore.userRole || '').toString().toLowerCase().includes('supplier')
})

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
    let target = notif.link

    // Normalize possible absolute URLs (server may store full URL)
    if (typeof target === 'string') {
      // If the recipient is a supplier, prefer supplier-portal routes
      if (isSupplierUser.value) {
        // RFQ links (handles absolute or relative)
        const rfqMatch = target.match(/\/system\/procurement\/rfqs\/(\d+)/)
        if (rfqMatch) {
          target = `/supplier-portal/rfqs/${rfqMatch[1]}`
        }

        // PO links
        const poMatch = target.match(/\/system\/procurement\/purchase-orders\/(\d+)/)
        if (poMatch) {
          target = `/supplier-portal/pos/${poMatch[1]}/view`
        }
      } else {
        // For non-supplier users, strip the `/system` prefix so links go to `/procurement/...`
        const rfqMatch = target.match(/\/system\/procurement\/rfqs\/(\d+)/)
        if (rfqMatch) {
          target = `/procurement/rfqs/${rfqMatch[1]}`
        }

        const poMatch = target.match(/\/system\/procurement\/purchase-orders\/(\d+)/)
        if (poMatch) {
          target = `/procurement/purchase-orders/${poMatch[1]}`
        }

        // General fallback: replace leading /system/procurement with /procurement
        target = String(target).replace(/^https?:\/\/[^/]+/i, '').replace(/^\/system\/procurement/i, '/procurement')
      }
    }

    try {
      router.visit(target)
    } catch (e) {
      // fallback: direct window navigation if Inertia visit fails
      if (typeof target === 'string') window.location.href = target
    }

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

.system-navigation-accordion :deep(.p-accordionheader-toggle-icon) {
  width: 0.7rem;
  height: 0.7rem;
  font-size: 0.7rem;
}

.system-navigation-accordion :deep(.p-accordionheader) {
  padding: 0.7rem 1rem;
}

.sidebar {
  width: 16rem;
  transition: width 0.3s ease, transform 0.3s ease;
}

.sidebar.closed {
  width: 5rem;
}

@media (max-width: 1024px) {
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    transform: translateX(-100%);
    width: 260px;
  }
  .sidebar.open {
    transform: translateX(0);
  }
  .sidebar.closed {
    transform: translateX(-100%);
  }
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
