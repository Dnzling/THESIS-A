<template>
  <div class="relative inline-block">
    <!-- Bell Button with Badge -->
    <button
      @click="toggleDropdown"
      class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
      aria-label="Notifications"
    >
      <i class="pi pi-bell text-xl"></i>
      
      <!-- Badge showing unread count -->
      <span
        v-if="unreadCount > 0"
        class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Menu -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 max-h-96 flex flex-col"
        @click.stop
      >
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold text-gray-800">Notifications</h3>
          <button
            v-if="unreadCount > 0"
            @click="markAllAsRead"
            class="text-xs text-blue-600 hover:text-blue-800 font-medium"
          >
            Mark all as read
          </button>
        </div>

        <!-- Notifications List -->
        <div class="overflow-y-auto flex-1">
          <div v-if="loading" class="p-4 text-center">
            <i class="pi pi-spinner pi-spin text-gray-400"></i>
          </div>

          <div v-else-if="notifications.length === 0" class="p-8 text-center">
            <i class="pi pi-inbox text-3xl text-gray-300 mb-2"></i>
            <p class="text-gray-500">No notifications</p>
          </div>

          <div v-else class="divide-y divide-gray-100">
            <div
              v-for="notification in notifications"
              :key="notification.id"
              :class="[
                'px-4 py-3 hover:bg-gray-50 cursor-pointer transition-colors',
                { 'bg-blue-50': !notification.read_at }
              ]"
              @click="handleNotificationClick(notification)"
            >
              <div class="flex items-start gap-3">
                <!-- Icon based on type -->
                <div :class="['flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center', getNotificationBgClass(notification.type)]">
                  <i :class="['pi', getNotificationIcon(notification.type)]"></i>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900">{{ notification.title }}</p>
                  <p class="text-sm text-gray-600 mt-1 line-clamp-2">{{ notification.message }}</p>
                  <p class="text-xs text-gray-500 mt-1">{{ formatTime(notification.created_at) }}</p>
                </div>

                <!-- Unread indicator -->
                <div
                  v-if="!notification.read_at"
                  class="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0 mt-1"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="notifications.length > 0" class="px-4 py-3 border-t border-gray-200">
          <router-link
            to="/inventory/notifications"
            class="text-center text-sm text-blue-600 hover:text-blue-800 font-medium w-full block"
          >
            View all notifications →
          </router-link>
        </div>
      </div>
    </transition>

    <!-- Click outside to close -->
    <div v-if="isOpen" class="fixed inset-0 z-40" @click="isOpen = false"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue'
import axiosClient from '../../axios'

interface Notification {
  id: number
  type: string
  title: string
  message: string
  action_url?: string
  read_at?: string
  created_at: string
}

const isOpen = ref(false)
const loading = ref(false)
const notifications = ref<Notification[]>([])
const unreadCount = ref(0)

let pollInterval: number

// Get notification styling
const getNotificationBgClass = (type: string): string => {
  const classes: { [key: string]: string } = {
    alert: 'bg-red-100',
    warning: 'bg-yellow-100',
    transfer: 'bg-blue-100',
    approval: 'bg-purple-100',
    info: 'bg-green-100',
  }
  return classes[type] || 'bg-gray-100'
}

const getNotificationIcon = (type: string): string => {
  const icons: { [key: string]: string } = {
    alert: 'pi-exclamation-triangle text-red-600',
    warning: 'pi-alert text-yellow-600',
    transfer: 'pi-arrow-right-arrow-left text-blue-600',
    approval: 'pi-check-circle text-purple-600',
    info: 'pi-info-circle text-green-600',
  }
  return icons[type] || 'pi-bell text-gray-600'
}

const formatTime = (datetime: string): string => {
  const date = new Date(datetime)
  const now = new Date()
  const diff = now.getTime() - date.getTime()

  // Less than 1 minute
  if (diff < 60000) return 'just now'
  // Less than 1 hour
  if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`
  // Less than 1 day
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`
  // Less than 1 week
  if (diff < 604800000) return `${Math.floor(diff / 86400000)}d ago`

  return date.toLocaleDateString()
}

const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    fetchNotifications()
  }
}

const fetchNotifications = async () => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/inventory/notifications', {
      params: { limit: 5 }
    })
    notifications.value = response.data.data
    unreadCount.value = response.data.meta?.unread_count || 0
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  } finally {
    loading.value = false
  }
}

const markAllAsRead = async () => {
  try {
    await axiosClient.put('/api/inventory/notifications/mark-all-read')
    unreadCount.value = 0
    notifications.value = notifications.value.map(n => ({
      ...n,
      read_at: new Date().toISOString()
    }))
  } catch (error) {
    console.error('Failed to mark all as read:', error)
  }
}

const handleNotificationClick = async (notification: Notification) => {
  if (!notification.read_at) {
    try {
      await axiosClient.put(`/api/inventory/notifications/${notification.id}/read`)
      notification.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch (error) {
      console.error('Failed to mark notification as read:', error)
    }
  }

  if (notification.action_url) {
    // Navigate to the action URL if provided
    window.location.href = notification.action_url
  }
}

// Poll for new notifications every 30 seconds
const startPolling = () => {
  pollInterval = window.setInterval(async () => {
    try {
      const response = await axiosClient.get('/api/inventory/notifications/unread')
      unreadCount.value = response.data.unread_count
      // Refetch full list if dropdown is open
      if (isOpen.value) {
        fetchNotifications()
      }
    } catch (error) {
      console.error('Failed to poll notifications:', error)
    }
  }, 30000)
}

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
  }
}

onMounted(() => {
  fetchNotifications()
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
