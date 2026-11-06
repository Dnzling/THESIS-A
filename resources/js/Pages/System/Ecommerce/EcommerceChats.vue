<template>
  <div class="h-[calc(100dvh-190px)] min-h-[520px] overflow-hidden rounded-3xl border border-orange-100 bg-white shadow-sm">
    <div
      class="grid h-full min-w-0 grid-cols-1"
      :class="lockedStoreContext
        ? 'grid-rows-1 lg:grid-cols-1'
        : 'grid-rows-[minmax(130px,0.4fr)_minmax(300px,1fr)] lg:grid-cols-[340px_minmax(0,1fr)] lg:grid-rows-1'"
    >
      <aside v-if="!lockedStoreContext" class="flex min-h-0 flex-col border-b border-orange-100 bg-white lg:border-b-0 lg:border-r">
        <div class="border-b border-orange-100 p-4">
          <div class="mb-3 flex items-center justify-between">
            <div>
              <h1 class="font-semibold text-slate-900">Store Chats</h1>
              <p class="text-xs text-slate-500">Message your stores</p>
            </div>
            <Button icon="pi pi-refresh" text rounded aria-label="Refresh chats" class="!text-orange-600" @click="loadThreads" />
          </div>
          <InputText v-model="threadSearch" fluid placeholder="Search stores" />
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto p-3">
          <button
            v-for="thread in filteredThreads"
            :key="thread.id"
            type="button"
            class="mb-2 flex w-full items-center gap-3 rounded-2xl p-3 text-left transition"
            :class="activeStoreId === Number(thread.store_id) ? 'bg-orange-100' : 'hover:bg-orange-50'"
            @click="openThread(Number(thread.store_id))"
          >
            <Avatar :image="thread.store_logo" :label="storeInitials(thread.store_name)" shape="circle" class="shrink-0 bg-orange-100 text-orange-700" />
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between gap-2">
                <p class="truncate text-sm font-semibold text-slate-900">{{ thread.store_name }}</p>
                <span class="shrink-0 text-[11px] text-slate-400">{{ shortTime(thread.last_message_at) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <p class="flex-1 truncate text-xs text-slate-500">{{ thread.last_message || 'No messages yet' }}</p>
                <Badge v-if="thread.unread_count" :value="thread.unread_count" severity="warn" />
              </div>
            </div>
          </button>
          <p v-if="!threadsLoading && !filteredThreads.length" class="p-4 text-center text-sm text-slate-400">No store chats found.</p>
        </div>
      </aside>

      <section class="flex min-h-0 min-w-0 flex-col">
        <header class="flex h-20 shrink-0 items-center justify-between border-b border-orange-100 px-4 sm:px-6">
          <div v-if="activeStoreId" class="flex min-w-0 items-center gap-3">
            <Avatar :image="activeStoreLogo" :label="storeInitials(activeStoreName)" shape="circle" class="shrink-0 bg-orange-100 text-orange-700" />
            <div class="min-w-0">
              <h2 class="truncate font-semibold text-slate-900">{{ activeStoreName || 'Store' }}</h2>
              <p class="text-xs text-slate-500">Store conversation</p>
            </div>
          </div>
          <div v-else>
            <h1 class="font-semibold text-slate-900">Store Chats</h1>
            <p class="text-sm text-slate-500">Select a store conversation</p>
          </div>
          <i class="pi pi-comments text-xl text-orange-500"></i>
        </header>

        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-orange-50/30 p-4 sm:p-6">
          <div v-if="!activeStoreId" class="flex h-full flex-col items-center justify-center text-center text-slate-400">
            <i class="pi pi-comments mb-3 text-5xl text-orange-200"></i>
            <p>Choose a store conversation from the left.</p>
          </div>
          <div v-else-if="!messages.length && !messagesLoading" class="flex h-full items-center justify-center text-sm text-slate-400">No messages yet. Say hello!</div>
          <div v-for="msg in messages" :key="msg.id" class="flex gap-2" :class="msg.sender_role === 'customer' ? 'justify-end' : 'justify-start'">
            <Avatar v-if="msg.sender_role !== 'customer'" :image="activeStoreLogo" :label="storeInitials(activeStoreName)" shape="circle" size="small" class="mt-1 shrink-0 bg-orange-100 text-orange-700" />
            <div class="max-w-[78%] min-w-0">
              <div class="rounded-2xl px-4 py-3 text-sm shadow-sm" :class="msg.sender_role === 'customer' ? 'rounded-br-md bg-orange-500 text-white' : 'rounded-bl-md border border-slate-100 bg-white text-slate-800'">
                <template v-if="editingMessageId === msg.id">
                  <Textarea v-model="editDraftMessage" rows="2" autoResize class="mb-2 w-full" />
                  <div class="flex items-center justify-end gap-2">
                    <Button label="Cancel" size="small" severity="secondary" text @click="cancelEditMessage" />
                    <Button label="Save" size="small" severity="info" :loading="editingLoading" @click="saveEditMessage(msg)" />
                  </div>
                </template>
                <p v-else class="whitespace-pre-wrap break-words">{{ msg.message }}</p>
                <div v-if="msg.sender_role === 'customer' && Number(msg.id) > 0 && msg.message !== '[Message unsent]' && editingMessageId !== msg.id" class="mt-1 flex items-center justify-end gap-2">
                  <button type="button" class="text-[11px] underline text-white/80 hover:text-white" @click="startEditMessage(msg)">Edit</button>
                  <button type="button" class="text-[11px] underline text-white/80 hover:text-white" @click="unsendMessage(msg)">Unsend</button>
                </div>
              </div>
              <p class="mt-1 px-1 text-[11px] text-slate-400" :class="msg.sender_role === 'customer' ? 'text-right' : ''">{{ formatDateTime(msg.created_at) }}</p>
            </div>
          </div>
        </div>

        <form v-if="activeStoreId" class="shrink-0 border-t border-orange-100 bg-white p-4" @submit.prevent="sendMessage">
          <div class="flex items-end gap-2">
            <Textarea v-model="draftMessage" autoResize rows="1" fluid class="flex-1" placeholder="Write a message" @keydown.enter.exact.prevent="sendMessage" />
            <Button type="submit" icon="pi pi-send" label="Send" :loading="sending" :disabled="!draftMessage.trim() || sending" class="!border-orange-500 !bg-orange-500" />
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import { confirmAlert, showAlert } from '@/utils/swal'
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
defineOptions({
  layout: EcommerceMobileWrapper,
})


const route = useRoute()
const threads = ref<any[]>([])
const threadSearch = ref('')
const messages = ref<any[]>([])
const threadsLoading = ref(false)
const messagesLoading = ref(false)
const sending = ref(false)
const draftMessage = ref('')
const editingMessageId = ref<number | string | null>(null)
const editDraftMessage = ref('')
const editingLoading = ref(false)
const activeStoreId = ref<number | null>(null)
const activeStoreName = ref('')
const activeStoreLogo = ref('')
let refreshTimer: ReturnType<typeof setInterval> | null = null
let refreshingChats = false
const filteredThreads = computed(() => threads.value.filter((thread) => String(thread.store_name || '').toLowerCase().includes(threadSearch.value.trim().toLowerCase())))
const lockedStoreId = computed(() => Number(route.query.store_id || 0))
const lockedStoreContext = computed(() => lockedStoreId.value > 0)
const lockedProductName = computed(() => String(route.query.product_name || '').trim())

function formatDateTime(value: string) {
  return value ? new Date(value).toLocaleString('en-PH') : '-'
}

function storeInitials(name: string) {
  return String(name || 'Store').split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase()
}

function shortTime(value: string) {
  return value ? new Date(value).toLocaleTimeString('en-PH', { hour: 'numeric', minute: '2-digit' }) : ''
}

function extractRows(payload: any): any[] {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  return []
}

async function loadThreads() {
  threadsLoading.value = true
  try {
    const res = await ecommerceService.getChatThreads({ per_page: 50 })
    threads.value = extractRows(res.data?.data)

    const preferredStoreId = lockedStoreId.value
    if (preferredStoreId) {
      await openThread(preferredStoreId)
    } else if (!activeStoreId.value && threads.value.length) {
      await openThread(Number(threads.value[0].store_id))
    }
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Failed to load chats' })
  } finally {
    threadsLoading.value = false
  }
}

async function openThread(storeId: number) {
  if (!storeId) return
  const switchingThread = activeStoreId.value !== storeId
  activeStoreId.value = storeId
  if (switchingThread) messages.value = []
  activeStoreName.value = threads.value.find((t) => Number(t.store_id) === storeId)?.store_name || ''
  activeStoreLogo.value = threads.value.find((t) => Number(t.store_id) === storeId)?.store_logo || ''
  messagesLoading.value = true
  try {
    const res = await ecommerceService.getStoreChatMessages(storeId, { per_page: 100 })
    const rows = extractRows(res.data?.data)
    messages.value = [...rows].reverse()
    activeStoreName.value = String(res.data?.store?.name || activeStoreName.value || '')
    activeStoreLogo.value = String(res.data?.store?.logo || activeStoreLogo.value || '')
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Failed to load messages' })
  } finally {
    messagesLoading.value = false
  }
}

async function sendMessage() {
  const message = draftMessage.value.trim()
  if (!message || !activeStoreId.value) return
  sending.value = true
  try {
    await ecommerceService.sendStoreChatMessage(activeStoreId.value, { message })
    draftMessage.value = ''
    await openThread(activeStoreId.value)
    await loadThreads()
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Send Failed', detail: error?.response?.data?.message || 'Unable to send message' })
  } finally {
    sending.value = false
  }
}

function startEditMessage(msg: any) {
  editingMessageId.value = msg.id
  editDraftMessage.value = String(msg?.message || '')
}

function cancelEditMessage() {
  editingMessageId.value = null
  editDraftMessage.value = ''
}

async function saveEditMessage(msg: any) {
  if (!activeStoreId.value) return
  const message = String(editDraftMessage.value || '').trim()
  if (!message) {
    showAlert({ severity: 'warn', summary: 'Chat', detail: 'Message cannot be empty.' })
    return
  }

  editingLoading.value = true
  try {
    await ecommerceService.updateStoreChatMessage(activeStoreId.value, msg.id, { message })
    const idx = messages.value.findIndex((m: any) => m.id === msg.id)
    if (idx >= 0) {
      messages.value[idx] = { ...messages.value[idx], message }
    }
    cancelEditMessage()
    await loadThreads()
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Edit Failed', detail: error?.response?.data?.message || 'Unable to edit message' })
  } finally {
    editingLoading.value = false
  }
}

async function unsendMessage(msg: any) {
  if (!activeStoreId.value) return
  const confirmed = await confirmAlert({
    title: 'Unsend Message?',
    text: 'This will remove the message content for everyone in this chat.',
    confirmText: 'Unsend',
    cancelText: 'Cancel',
  })
  if (!confirmed) return

  try {
    await ecommerceService.unsendStoreChatMessage(activeStoreId.value, msg.id)
    const idx = messages.value.findIndex((m: any) => m.id === msg.id)
    if (idx >= 0) {
      messages.value[idx] = { ...messages.value[idx], message: '[Message unsent]' }
    }
    await loadThreads()
  } catch (error: any) {
    showAlert({ severity: 'error', summary: 'Unsend Failed', detail: error?.response?.data?.message || 'Unable to unsend message' })
  }
}

async function refreshChats() {
  if (refreshingChats || document.visibilityState !== 'visible') return
  refreshingChats = true
  try {
    await loadThreads()
    if (activeStoreId.value && !lockedStoreId.value) await openThread(activeStoreId.value)
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

watch(lockedStoreId, async (storeId) => {
  if (!storeId) return
  await openThread(storeId)
  if (lockedProductName.value && !draftMessage.value.trim()) {
    draftMessage.value = `Hi! I'm interested in "${lockedProductName.value}". Is it available?`
  }
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
