<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Notifications</h1>
        <p class="text-gray-600 mt-1">View and manage all your inventory notifications</p>
      </div>
      <div class="flex gap-2">
        <Button
          label="Mark All as Read"
          icon="pi pi-check-circle"
          severity="secondary"
          @click="markAllAsRead"
          v-if="unreadCount > 0"
        />
        <Button
          label="Delete All"
          icon="pi pi-trash"
          severity="danger"
          text
          @click="deleteAll"
          v-if="notifications.length > 0"
        />
      </div>
    </div>

    <!-- Filters and Sorting -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Filter</label>
            <Dropdown
              v-model="filters.status"
              :options="statusFilters"
              optionLabel="label"
              optionValue="value"
              placeholder="All notifications"
              class="w-full"
              @change="fetchNotifications"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
            <Dropdown
              v-model="filters.type"
              :options="typeFilters"
              optionLabel="label"
              optionValue="value"
              placeholder="All types"
              class="w-full"
              @change="fetchNotifications"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sort</label>
            <Dropdown
              v-model="filters.sort"
              :options="sortOptions"
              optionLabel="label"
              optionValue="value"
              class="w-full"
              @change="fetchNotifications"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Unread Count Badge -->
    <div v-if="unreadCount > 0" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <i class="pi pi-bell text-2xl text-blue-600"></i>
          <div>
            <p class="font-semibold text-gray-900">{{ unreadCount }} unread notification{{ unreadCount !== 1 ? 's' : '' }}</p>
            <p class="text-sm text-gray-600">You have unread messages</p>
          </div>
        </div>
        <Button
          label="Mark All Read"
          @click="markAllAsRead"
          class="text-sm"
        />
      </div>
    </div>

    <!-- Notifications List -->
    <div v-if="loading" class="space-y-3">
      <Skeleton v-for="i in 5" :key="i" height="120px" class="rounded-lg" />
    </div>

    <div v-else-if="notifications.length === 0" class="text-center py-16">
      <i class="pi pi-inbox text-6xl text-gray-300 mb-4"></i>
      <p class="text-gray-600 text-lg">No notifications</p>
      <p class="text-gray-500 text-sm mt-2">You're all caught up!</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        :class="[
          'p-4 rounded-lg border-2 transition-colors cursor-pointer hover:shadow-md',
          notification.read_at
            ? 'border-gray-200 bg-white'
            : 'border-blue-300 bg-blue-50'
        ]"
        @click="handleNotificationClick(notification)"
      >
        <div class="flex items-start gap-4">
          <!-- Icon -->
          <div :class="['flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center text-lg', getNotificationBgClass(notification.type)]">
            <i :class="['pi', getNotificationIcon(notification.type)]"></i>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-2">
              <div>
                <h3 :class="['font-semibold text-gray-900', !notification.read_at && 'font-bold']">
                  {{ notification.title }}
                </h3>
                <p class="text-gray-600 mt-1 text-sm line-clamp-2">{{ notification.message }}</p>
              </div>
              <div class="flex-shrink-0">
                <Badge
                  v-if="!notification.read_at"
                  value="NEW"
                  severity="danger"
                />
              </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-between mt-3 gap-2">
              <p class="text-xs text-gray-500">{{ formatTime(notification.created_at) }}</p>
              <div class="flex gap-2">
                <Button
                  v-if="!notification.read_at"
                  icon="pi pi-check-circle"
                  @click.stop="markAsRead(notification)"
                  text
                  rounded
                  size="small"
                  v-tooltip="'Mark as read'"
                />
                <Button
                  v-if="notification.action_url"
                  icon="pi pi-arrow-right"
                  @click.stop="navigateTo(notification)"
                  text
                  rounded
                  size="small"
                  v-tooltip="'View'"
                />
                <Button
                  icon="pi pi-trash"
                  @click.stop="deleteNotification(notification.id)"
                  text
                  rounded
                  size="small"
                  severity="danger"
                  v-tooltip="'Delete'"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalRecords > pageSize" class="flex items-center justify-center gap-2 mt-6">
        <Button
          icon="pi pi-chevron-left"
          :disabled="currentPage === 1"
          @click="previousPage"
          text
          rounded
        />
        <span class="text-sm text-gray-600">
          Page {{ currentPage }} of {{ Math.ceil(totalRecords / pageSize) }}
        </span>
        <Button
          icon="pi pi-chevron-right"
          :disabled="currentPage >= Math.ceil(totalRecords / pageSize)"
          @click="nextPage"
          text
          rounded
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import axiosClient from '../../../axios'

interface Notification {
  id: number
  type: string
  title: string
  message: string
  action_url?: string
  read_at?: string
  created_at: string
}

const loading = ref(false)
const notifications = ref<Notification[]>([])
const unreadCount = ref(0)
const totalRecords = ref(0)
const currentPage = ref(1)
const pageSize = 10

const filters = reactive({
  status: 'all',
  type: 'all',
  sort: 'newest'
})

const statusFilters = [
  { label: 'All Notifications', value: 'all' },
  { label: 'Unread Only', value: 'unread' },
  { label: 'Read Only', value: 'read' }
]

const typeFilters = [
  { label: 'All Types', value: 'all' },
  { label: 'Alerts', value: 'alert' },
  { label: 'Transfers', value: 'transfer' },
  { label: 'Approvals', value: 'approval' },
  { label: 'System', value: 'system' }
]

const sortOptions = [
  { label: 'Newest First', value: 'newest' },
  { label: 'Oldest First', value: 'oldest' },
  { label: 'Unread First', value: 'unread_first' }
]

const getNotificationBgClass = (type: string): string => {
  const classes: { [key: string]: string } = {
    alert: 'bg-red-100 text-red-600',
    warning: 'bg-yellow-100 text-yellow-600',
    transfer: 'bg-blue-100 text-blue-600',
    approval: 'bg-purple-100 text-purple-600',
    system: 'bg-green-100 text-green-600',
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

const getNotificationIcon = (type: string): string => {
  const icons: { [key: string]: string } = {
    alert: 'pi-exclamation-circle',
    warning: 'pi-alert',
    transfer: 'pi-arrow-right-arrow-left',
    approval: 'pi-check-circle',
    system: 'pi-info-circle',
  }
  return icons[type] || 'pi-bell'
}

const formatTime = (datetime: string): string => {
  const date = new Date(datetime)
  const now = new Date()
  const diff = now.getTime() - date.getTime()

  if (diff < 60000) return 'just now'
  if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`
  if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`
  if (diff < 604800000) return `${Math.floor(diff / 86400000)}d ago`

  return date.toLocaleDateString()
}

const fetchNotifications = async (page = 1) => {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/inventory/notifications', {
      params: {
        page,
        limit: pageSize,
        status: filters.status !== 'all' ? filters.status : undefined,
        type: filters.type !== 'all' ? filters.type : undefined,
        sort: filters.sort
      }
    })
    notifications.value = response.data.data
    totalRecords.value = response.data.meta?.total || 0
    unreadCount.value = response.data.meta?.unread_count || 0
    currentPage.value = page
  } catch (error) {
    console.error('Failed to fetch notifications:', error)
  } finally {
    loading.value = false
  }
}

const markAsRead = async (notification: Notification) => {
  try {
    await axiosClient.put(`/api/inventory/notifications/${notification.id}/read`)
    notification.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  } catch (error) {
    console.error('Failed to mark as read:', error)
  }
}

const markAllAsRead = async () => {
  try {
    await axiosClient.put('/api/inventory/notifications/mark-all-read')
    notifications.value = notifications.value.map(n => ({
      ...n,
      read_at: new Date().toISOString()
    }))
    unreadCount.value = 0
  } catch (error) {
    console.error('Failed to mark all as read:', error)
  }
}

const deleteNotification = async (id: number) => {
  try {
    await axiosClient.delete(`/api/inventory/notifications/${id}`)
    notifications.value = notifications.value.filter(n => n.id !== id)
    totalRecords.value = Math.max(0, totalRecords.value - 1)
  } catch (error) {
    console.error('Failed to delete notification:', error)
  }
}

const deleteAll = async () => {
  const confirmed = confirm('Are you sure you want to delete all notifications?')
  if (!confirmed) return

  try {
    const ids = notifications.value.map(n => n.id)
    await axiosClient.post('/api/inventory/notifications/batch-delete', { ids })
    notifications.value = []
    totalRecords.value = 0
    unreadCount.value = 0
  } catch (error) {
    console.error('Failed to delete all:', error)
  }
}

const handleNotificationClick = (notification: Notification) => {
  if (!notification.read_at) {
    markAsRead(notification)
  }
}

const navigateTo = (notification: Notification) => {
  if (notification.action_url) {
    window.location.href = notification.action_url
  }
}

const previousPage = () => {
  if (currentPage.value > 1) {
    fetchNotifications(currentPage.value - 1)
  }
}

const nextPage = () => {
  const maxPage = Math.ceil(totalRecords.value / pageSize)
  if (currentPage.value < maxPage) {
    fetchNotifications(currentPage.value + 1)
  }
}

onMounted(() => {
  fetchNotifications()
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
