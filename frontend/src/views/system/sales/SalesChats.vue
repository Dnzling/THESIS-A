<template>
  <div class="max-w-7xl mx-auto space-y-6 py-6 px-4 sm:px-6 lg:px-8">
    <Card class="border border-gray-100 shadow-sm rounded-2xl">
      <template #content>
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Customer Chats</h1>
            <p class="text-sm text-gray-500">Reply to ecommerce customer inquiries.</p>
          </div>
          <Button severity="info" outlined icon="pi pi-refresh" label="Refresh" @click="loadThreads" />
        </div>
      </template>
    </Card>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
      <Card class="border border-gray-100 shadow-sm rounded-2xl lg:col-span-4">
        <template #content>
          <InputText v-model="search" fluid placeholder="Search customer..." class="mb-3" />
          <div class="space-y-2 max-h-[65vh] overflow-auto">
            <button
              v-for="thread in threads"
              :key="thread.id"
              class="w-full text-left rounded-lg border px-3 py-2 transition"
              :class="selectedThreadId === thread.id ? 'border-blue-300 bg-blue-50' : 'border-gray-100 bg-white hover:bg-gray-50'"
              @click="selectThread(thread.id)"
            >
              <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-medium text-gray-900 truncate">{{ thread.customer_name }}</p>
                <Tag v-if="thread.unread_count" severity="danger" :value="String(thread.unread_count)" />
              </div>
              <p class="text-xs text-gray-500 truncate mt-1">{{ thread.last_message || 'No messages yet' }}</p>
            </button>
            <p v-if="!threads.length" class="text-sm text-gray-500">No chat threads yet.</p>
          </div>
        </template>
      </Card>

      <Card class="border border-gray-100 shadow-sm rounded-2xl lg:col-span-8">
        <template #content>
          <div v-if="!selectedThreadId" class="text-sm text-gray-500">Select a thread to start replying.</div>
          <div v-else class="space-y-3">
            <div class="max-h-[52vh] overflow-auto space-y-2 rounded-lg bg-gray-50 p-3">
              <div
                v-for="message in messages"
                :key="message.id"
                class="max-w-[85%] rounded-lg px-3 py-2 text-sm"
                :class="message.sender_role === 'store' ? 'ml-auto bg-blue-600 text-white' : 'bg-white text-gray-900'"
              >
                <p>{{ message.message }}</p>
                <p class="mt-1 text-[11px]" :class="message.sender_role === 'store' ? 'text-blue-100' : 'text-gray-400'">{{ dt(message.created_at) }}</p>
              </div>
              <p v-if="!messages.length" class="text-sm text-gray-500">No messages yet.</p>
            </div>

            <Textarea v-model="draft" rows="3" fluid :readonly="!canManageChats" placeholder="Type your reply..." />
            <Button severity="info" icon="pi pi-send" :disabled="!canManageChats || !draft.trim()" :loading="sending" label="Send Reply" @click="sendMessage" />
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import salesService from '@/services/sales.service'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'

const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()

const canManageChats = authStore.hasPermission('sales.chats.manage')
const search = ref('')
const threads = ref<any[]>([])
const selectedThreadId = ref<number | null>(null)
const selectedOrderId = ref<number | null>(Number(route.query.order_id || 0) || null)
const messages = ref<any[]>([])
const draft = ref('')
const sending = ref(false)

const extractRows = (payload: any) => Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : [])

const loadThreads = async () => {
  try {
    const response = await salesService.getChatThreads({ per_page: 50, search: search.value || undefined })
    threads.value = extractRows(response?.data)

    const preselectId = Number(route.query.thread_id || 0)
    if (preselectId && threads.value.some((t: any) => Number(t.id) === preselectId)) {
      selectedThreadId.value = preselectId
      await loadMessages()
      return
    }

    const customerUserId = Number(route.query.customer_user_id || 0)
    if (customerUserId) {
      const match = threads.value.find((t: any) => Number(t.customer_user_id) === customerUserId)
      if (match) {
        selectedThreadId.value = Number(match.id)
        await loadMessages()
        return
      }
    }

    if (!selectedThreadId.value && threads.value.length) {
      selectedThreadId.value = Number(threads.value[0].id)
      await loadMessages()
    }
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Failed to load threads', life: 2800 })
  }
}

const loadMessages = async () => {
  if (!selectedThreadId.value) return
  try {
    const response = await salesService.getChatMessages(selectedThreadId.value, { per_page: 120 })
    const rows = extractRows(response?.data)
    messages.value = [...rows].reverse()
  } catch (error: any) {
    messages.value = []
    toast.add({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Failed to load messages', life: 2800 })
  }
}

const selectThread = (threadId: number) => {
  selectedThreadId.value = Number(threadId)
  loadMessages()
}

const sendMessage = async () => {
  if (!selectedThreadId.value || !draft.value.trim() || !canManageChats) return
  sending.value = true
  try {
    await salesService.sendChatMessage(selectedThreadId.value, {
      message: draft.value.trim(),
      order_id: selectedOrderId.value || undefined,
    })
    draft.value = ''
    await loadMessages()
    await loadThreads()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Failed to send message', life: 2800 })
  } finally {
    sending.value = false
  }
}

const dt = (value: string) => value ? new Date(value).toLocaleString('en-PH') : '-'

watch(search, () => loadThreads())
onMounted(loadThreads)
</script>
