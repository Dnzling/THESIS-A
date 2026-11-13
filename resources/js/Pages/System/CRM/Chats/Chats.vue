<template>
  <div class="mx-auto flex h-[calc(100dvh-150px)] min-h-[420px] max-w-7xl flex-col space-y-4 px-4 py-4 sm:px-6 lg:px-8">
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

    <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 lg:grid-cols-12">
      <Card class="min-h-0 border border-gray-100 shadow-sm rounded-2xl lg:col-span-4">
        <template #content>
          <div class="flex h-full min-h-0 flex-col">
          <InputText v-model="search" fluid placeholder="Search customer..." class="mb-3 shrink-0" />
          <div class="min-h-0 flex-1 space-y-2 overflow-auto">
            <button
              v-for="thread in threads"
              :key="thread.id"
              class="w-full text-left rounded-lg border px-3 py-2 transition"
              :class="selectedThreadId === thread.id ? 'border-blue-300 bg-blue-50' : 'border-gray-100 bg-white hover:bg-gray-50'"
              @click="selectThread(thread.id)"
            >
                <div class="flex items-center justify-between gap-2">
                <div class="flex flex-col">
                  <p class="text-sm font-medium text-gray-900 truncate">{{ thread.customer_name }}</p>
                  <p v-if="thread.store_name" class="text-xs text-gray-500 truncate">{{ thread.store_name }}</p>
                </div>
                <Tag v-if="thread.unread_count" severity="danger" :value="String(thread.unread_count)" />
              </div>
              <p class="text-xs text-gray-500 truncate mt-1">{{ thread.last_message || 'No messages yet' }}</p>
            </button>
            <p v-if="!threads.length" class="text-sm text-gray-500">No chat threads yet.</p>
          </div>
          </div>
        </template>
      </Card>

      <Card class="min-h-0 border border-gray-100 shadow-sm rounded-2xl lg:col-span-8">
        <template #content>
          <div v-if="!selectedThreadId" class="text-sm text-gray-500">Select a thread to start replying.</div>
          <div v-else class="flex h-full min-h-0 flex-col gap-3">
            <div class="min-h-0 flex-1 space-y-2 overflow-auto rounded-lg bg-gray-50 p-3">
              <div
                v-for="message in messages"
                :key="message.id"
                class="max-w-[85%] rounded-lg px-3 py-2 text-sm"
                :class="message.sender_role === 'store' ? 'ml-auto bg-blue-600 text-white' : 'bg-white text-gray-900'"
              >
                <template v-if="editingMessageId === message.id">
                  <Textarea v-model="editDraft" rows="2" fluid class="mb-2" />
                  <div class="flex items-center justify-end gap-2">
                    <Button label="Cancel" size="small" severity="secondary" text @click="cancelEditMessage" />
                    <Button label="Save" size="small" severity="info" :loading="editingMessageLoading" @click="saveEditMessage(message)" />
                  </div>
                </template>
                <p v-if="editingMessageId !== message.id && message.message" class="whitespace-pre-wrap break-words">{{ message.message }}</p>
                <button v-if="message.attachment_url && String(message.attachment_mime || '').startsWith('image/')" type="button" class="mt-2 block w-fit overflow-hidden rounded-lg border border-white/20" @click="previewImage(message.attachment_url, message.attachment_name)">
                  <img
                  :src="message.attachment_url"
                  :alt="message.attachment_name || 'Chat attachment'"
                  class="h-32 max-w-56 rounded-lg object-cover"
                  loading="lazy"
                  />
                </button>
                <a
                  v-else-if="message.attachment_url"
                  :href="message.attachment_url"
                  class="mt-2 flex items-center gap-2 rounded-lg bg-black/10 p-2 underline"
                  target="_blank"
                  rel="noopener"
                ><i class="pi pi-paperclip"></i><span class="truncate">{{ message.attachment_name || 'Download attachment' }}</span></a>
                <div v-if="message._send_state === 'failed'" class="mt-1 flex items-center gap-2">
                  <span class="text-[11px] font-semibold text-amber-200">Unsent message</span>
                  <button
                    type="button"
                    class="text-[11px] underline text-white/90 hover:text-white"
                    @click="retryMessage(message)"
                  >
                    Retry
                  </button>
                </div>
                <p v-else-if="message._send_state === 'sending'" class="mt-1 text-[11px] text-blue-100">Sending...</p>
                <div
                  v-else-if="message.sender_role === 'store' && Number(message.id) > 0 && message.message !== '[Message unsent]'"
                  class="mt-1 flex items-center justify-end gap-2"
                >
                  <button type="button" class="text-[11px] underline text-blue-100 hover:text-white" @click="startEditMessage(message)">Edit</button>
                  <button type="button" class="text-[11px] underline text-blue-100 hover:text-white" @click="unsendMessage(message)">Unsend</button>
                </div>
                <p class="mt-1 text-[11px]" :class="message.sender_role === 'store' ? 'text-blue-100' : 'text-gray-400'">{{ dt(message.created_at) }}</p>
              </div>
              <p v-if="!messages.length" class="text-sm text-gray-500">No messages yet.</p>
            </div>

            <div v-if="attachment" class="flex items-center justify-between rounded-lg bg-orange-50 px-3 py-2 text-xs text-orange-800">
              <span class="truncate"><i class="pi pi-paperclip mr-2"></i>{{ attachment.name }}</span>
              <button type="button" class="ml-2" aria-label="Remove attachment" @click="clearAttachment"><i class="pi pi-times"></i></button>
            </div>
            <div class="flex items-end gap-2">
              <Textarea v-model="draft" rows="2" fluid class="flex-1" placeholder="Type your reply..." @keydown.enter.exact.prevent="sendMessage" />
              <label class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50" title="Attach file">
                <i class="pi pi-paperclip"></i>
                <input type="file" class="hidden" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip" @change="chooseAttachment" />
              </label>
              <Button
                severity="info"
                icon="pi pi-send"
                :loading="sending"
                :disabled="sending || (!draft.trim() && !attachment) || !canManageChats"
                label="Send Reply"
                @click="sendMessage"
              />
            </div>
          </div>
        </template>
      </Card>
    </div>
    <Dialog v-model:visible="imagePreviewVisible" modal :header="imagePreviewName || 'Image attachment'" :style="{ width: 'min(900px, 92vw)' }">
      <img v-if="imagePreviewUrl" :src="imagePreviewUrl" :alt="imagePreviewName || 'Chat image'" class="mx-auto max-h-[75vh] max-w-full object-contain" />
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import crmService from '@/services/crm.service'
import { confirmAlert } from '@/utils/swal'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Dialog from 'primevue/dialog'

const route = useRoute()
const toast = useToast()
const authStore = useAuthStore()

const canManageChats = computed(() => authStore.hasPermission('crm.chats.manage'))
const search = ref('')
const threads = ref<any[]>([])
const selectedThreadId = ref<number | null>(null)
const selectedOrderId = ref<number | null>(Number(route.query.order_id || 0) || null)
const messages = ref<any[]>([])
const draft = ref('')
const attachment = ref<File | null>(null)
const sending = ref(false)
const editingMessageId = ref<number | string | null>(null)
const editDraft = ref('')
const editingMessageLoading = ref(false)
const imagePreviewVisible = ref(false)
const imagePreviewUrl = ref('')
const imagePreviewName = ref('')
let tempMessageSeed = 0
let refreshTimer: ReturnType<typeof setInterval> | null = null
let refreshingChats = false

const extractRows = (payload: any) => Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : [])

const loadThreads = async () => {
  try {
    const response = await crmService.getChatThreads({ per_page: 50, search: search.value || undefined })
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
    const response = await crmService.getChatMessages(selectedThreadId.value, { per_page: 120 })
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

const chooseAttachment = (event: Event) => {
  attachment.value = (event.target as HTMLInputElement).files?.[0] || null
}

const clearAttachment = () => {
  attachment.value = null
}

const sendMessage = async () => {
  if (!selectedThreadId.value) {
    toast.add({ severity: 'warn', summary: 'Chat', detail: 'Select a thread before replying.', life: 2400 })
    return
  }
  if (!draft.value.trim() && !attachment.value) {
    toast.add({ severity: 'warn', summary: 'Chat', detail: 'Type a reply or attach a file before sending.', life: 2400 })
    return
  }
  if (!canManageChats.value) {
    toast.add({ severity: 'error', summary: 'Chat', detail: 'You do not have permission to send replies.', life: 2800 })
    return
  }
  sending.value = true
  const outgoingText = draft.value.trim()
  const outgoingAttachment = attachment.value
  const tempId = `tmp-${Date.now()}-${tempMessageSeed++}`
  const tempMessage = {
    id: tempId,
    sender_role: 'store',
    message: outgoingText,
    attachment_name: outgoingAttachment?.name,
    attachment_mime: outgoingAttachment?.type,
    attachment_url: outgoingAttachment?.type.startsWith('image/') ? URL.createObjectURL(outgoingAttachment) : null,
    created_at: new Date().toISOString(),
    _send_state: 'sending',
    _thread_id: selectedThreadId.value,
    _order_id: selectedOrderId.value || undefined,
  }
  messages.value.push(tempMessage)
  draft.value = ''
  attachment.value = null
  try {
    const payload = new FormData()
    if (outgoingText) payload.append('message', outgoingText)
    if (selectedOrderId.value) payload.append('order_id', String(selectedOrderId.value))
    if (outgoingAttachment) payload.append('attachment', outgoingAttachment)
    const response = await crmService.sendChatMessage(selectedThreadId.value, payload)
    const saved = response?.data?.data || response?.data || null
    if (saved?.id) {
      const idx = messages.value.findIndex((m: any) => m.id === tempId)
      if (idx >= 0) {
        messages.value[idx] = { ...saved, _send_state: 'sent' }
      }
    }
    await loadMessages()
    await loadThreads()
  } catch (error: any) {
    draft.value = outgoingText
    attachment.value = outgoingAttachment
    const idx = messages.value.findIndex((m: any) => m.id === tempId)
    if (idx >= 0) {
      messages.value[idx] = { ...messages.value[idx], _send_state: 'failed' }
    }
    toast.add({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Failed to send message', life: 2800 })
  } finally {
    if (tempMessage.attachment_url) URL.revokeObjectURL(tempMessage.attachment_url)
    sending.value = false
  }
}

const retryMessage = async (message: any) => {
  if (!selectedThreadId.value || sending.value) return
  const text = String(message?.message || '').trim()
  if (!text) return

  sending.value = true
  const idx = messages.value.findIndex((m: any) => m.id === message.id)
  if (idx >= 0) {
    messages.value[idx] = { ...messages.value[idx], _send_state: 'sending' }
  }

  try {
    const response = await crmService.sendChatMessage(selectedThreadId.value, {
      message: text,
      order_id: message?._order_id || selectedOrderId.value || undefined,
    })
    const saved = response?.data?.data || response?.data || null
    if (saved?.id) {
      if (idx >= 0) {
        messages.value[idx] = { ...saved, _send_state: 'sent' }
      }
    } else if (idx >= 0) {
      messages.value[idx] = { ...messages.value[idx], _send_state: 'sent' }
    }
    await loadMessages()
    await loadThreads()
  } catch (error: any) {
    if (idx >= 0) {
      messages.value[idx] = { ...messages.value[idx], _send_state: 'failed' }
    }
    toast.add({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Retry failed', life: 2800 })
  } finally {
    sending.value = false
  }
}

const dt = (value: string) => value ? new Date(value).toLocaleString('en-PH') : '-'

const previewImage = (url: string, name?: string) => {
  imagePreviewUrl.value = url
  imagePreviewName.value = name || ''
  imagePreviewVisible.value = true
}

const startEditMessage = (message: any) => {
  editingMessageId.value = message.id
  editDraft.value = String(message?.message || '')
}

const cancelEditMessage = () => {
  editingMessageId.value = null
  editDraft.value = ''
}

const saveEditMessage = async (message: any) => {
  if (!selectedThreadId.value) return
  const nextValue = String(editDraft.value || '').trim()
  if (!nextValue) {
    toast.add({ severity: 'warn', summary: 'Chat', detail: 'Message cannot be empty.', life: 2200 })
    return
  }

  editingMessageLoading.value = true
  try {
    await crmService.updateChatMessage(selectedThreadId.value, message.id, { message: nextValue })
    const idx = messages.value.findIndex((m: any) => m.id === message.id)
    if (idx >= 0) {
      messages.value[idx] = { ...messages.value[idx], message: nextValue }
    }
    cancelEditMessage()
    await loadThreads()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Failed to edit message', life: 2600 })
  } finally {
    editingMessageLoading.value = false
  }
}

const unsendMessage = async (message: any) => {
  if (!selectedThreadId.value) return
  const confirmed = await confirmAlert({
    title: 'Unsend Message?',
    text: 'This will remove the message content for everyone in this chat.',
    confirmText: 'Unsend',
    cancelText: 'Cancel',
  })
  if (!confirmed) return

  try {
    await crmService.unsendChatMessage(selectedThreadId.value, message.id)
    const idx = messages.value.findIndex((m: any) => m.id === message.id)
    if (idx >= 0) {
      messages.value[idx] = { ...messages.value[idx], message: '[Message unsent]' }
    }
    await loadThreads()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Failed to unsend message', life: 2600 })
  }
}

watch(search, () => loadThreads())

const refreshChats = async () => {
  if (refreshingChats || document.visibilityState !== 'visible') return
  refreshingChats = true
  try {
    await loadThreads()
    if (selectedThreadId.value) await loadMessages()
  } finally {
    refreshingChats = false
  }
}

onMounted(async () => {
  await loadThreads()
  refreshTimer = setInterval(refreshChats, 3000)
})

onBeforeUnmount(() => {
  if (refreshTimer) clearInterval(refreshTimer)
})
</script>

<style scoped>
:deep(.p-card-body),
:deep(.p-card-content) {
  display: flex;
  flex: 1;
  min-height: 0;
  flex-direction: column;
}
</style>
