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
          <!-- Notifications -->
          <Button 
            icon="pi pi-bell" 
            severity="secondary" 
            text 
            rounded 
            :badge="unreadCount > 0 ? unreadCount.toString() : undefined" 
            badgeSeverity="danger"
            @click="toggleNotifications"
          />
          <OverlayPanel ref="notificationPanel" class="w-[380px] p-0 rounded-2xl shadow-xl border border-gray-100">
            <div class="px-4 pt-4 pb-3 border-b border-gray-100 flex items-center justify-between">
              <div class="font-semibold text-gray-900">Notifications</div>
              <Button 
                label="Mark all as read" 
                size="small" 
                text 
                class="text-xs"
                :disabled="unreadCount === 0 || notificationsLoading"
                @click="markAllNotificationsRead"
              />
            </div>

            <div class="px-4 pt-3">
              <div class="flex items-center gap-4 text-sm">
                <button
                  class="pb-2 border-b-2 transition"
                  :class="activeNotifTab === 'inbox' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500'"
                  @click="activeNotifTab = 'inbox'"
                >
                  Inbox <span v-if="unreadCount" class="ml-1 text-xs bg-green-500 text-white rounded-full px-2 py-0.5">{{ unreadCount }}</span>
                </button>
                <button
                  class="pb-2 border-b-2 transition"
                  :class="activeNotifTab === 'general' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500'"
                  @click="activeNotifTab = 'general'"
                >
                  General
                </button>
                <button
                  class="pb-2 border-b-2 transition"
                  :class="activeNotifTab === 'archived' ? 'border-blue-500 text-blue-600 font-semibold' : 'border-transparent text-gray-500'"
                  @click="activeNotifTab = 'archived'"
                >
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

              <button
                v-for="notif in filteredNotifications"
                :key="notif.id"
                class="w-full text-left px-4 py-3 flex items-start gap-3 hover:bg-blue-50/50 transition"
                @click="openNotification(notif)"
              >
                <div class="relative">
                  <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center text-blue-700 font-semibold text-xs">
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
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../stores/auth'
import { startCase, toLower } from 'lodash'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import OverlayPanel from 'primevue/overlaypanel'
import axiosClient from '../axios'

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
const notificationPanel = ref()
const notifications = ref<any[]>([])
const notificationsLoading = ref(false)
const unreadCount = ref(0)
const activeNotifTab = ref<'inbox' | 'general' | 'archived'>('inbox')


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
const filteredNotifications = computed(() => {
  if (activeNotifTab.value === 'inbox') {
    return notifications.value.filter((n) => !n.is_read)
  }
  if (activeNotifTab.value === 'archived') {
    return notifications.value.filter((n) => n.is_read)
  }
  return notifications.value
})

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

const toggleNotifications = (event: MouseEvent) => {
  if (notificationPanel.value) {
    notificationPanel.value.toggle(event)
  }
}

const loadNotifications = async () => {
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
    router.push(notif.link)
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

onMounted(() => {
  loadNotifications()
})
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
