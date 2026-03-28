<template>
  <div class="space-y-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-bold text-slate-900">Store Chats</h1>
      <Button label="Refresh" icon="pi pi-refresh" outlined class="w-full sm:w-auto" @click="loadThreads" />
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
      <Card v-if="!lockedStoreContext" class="border border-slate-200 shadow-none lg:col-span-4">
        <template #content>
          <div class="space-y-2">
            <div v-if="threadsLoading" class="space-y-2">
              <Skeleton v-for="i in 5" :key="i" height="3.5rem" />
            </div>
            <button
              v-for="thread in threads"
              :key="thread.id"
              type="button"
              class="w-full rounded-lg border p-3 text-left transition"
              :class="activeStoreId === thread.store_id ? 'border-blue-500 bg-blue-50' : 'border-slate-200 hover:border-slate-300'"
              @click="openThread(thread.store_id)"
            >
              <div class="flex items-center justify-between gap-2">
                <p class="text-sm font-semibold text-slate-900">{{ thread.store_name }}</p>
                <Tag v-if="thread.unread_count" :value="thread.unread_count" severity="info" />
              </div>
              <p class="mt-1 line-clamp-1 text-xs text-slate-500">{{ thread.last_message || 'No messages yet.' }}</p>
            </button>
            <p v-if="!threadsLoading && !threads.length" class="text-sm text-slate-500">No chats yet.</p>
          </div>
        </template>
      </Card>

      <Card class="border border-slate-200 shadow-none" :class="lockedStoreContext ? 'lg:col-span-12' : 'lg:col-span-8'">
        <template #content>
          <div v-if="!activeStoreId" class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-slate-500">
            Select a store chat to start.
          </div>

          <div v-else class="space-y-3">
            <div class="max-h-[58vh] space-y-2 overflow-auto rounded-lg bg-slate-50 p-3">
              <div v-if="messagesLoading" class="space-y-2">
                <Skeleton v-for="i in 5" :key="`msg-${i}`" height="3rem" />
              </div>
              <div
                v-for="msg in messages"
                :key="msg.id"
                class="max-w-[80%] rounded-lg px-3 py-2 text-sm"
                :class="msg.sender_role === 'customer' ? 'ml-auto bg-blue-600 text-white' : 'bg-white text-slate-800'"
              >
                <p>{{ msg.message }}</p>
                <p class="mt-1 text-[11px]" :class="msg.sender_role === 'customer' ? 'text-blue-100' : 'text-slate-400'">
                  {{ formatDateTime(msg.created_at) }}
                </p>
              </div>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
              <Textarea v-model="draftMessage" rows="2" autoResize class="w-full" placeholder="Type your message..." />
              <Button label="Send" icon="pi pi-send" severity="info" class="w-full sm:w-auto" :loading="sending" @click="sendMessage" />
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import ecommerceService from '@/services/ecommerce.service'

const route = useRoute()
const toast = useToast()

const threads = ref<any[]>([])
const messages = ref<any[]>([])
const threadsLoading = ref(false)
const messagesLoading = ref(false)
const sending = ref(false)
const draftMessage = ref('')
const activeStoreId = ref<number | null>(null)
const lockedStoreId = computed(() => Number(route.query.store_id || 0))
const lockedStoreContext = computed(() => lockedStoreId.value > 0)
const lockedProductName = computed(() => String(route.query.product_name || '').trim())

function formatDateTime(value: string) {
  return value ? new Date(value).toLocaleString('en-PH') : '-'
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
    toast.add({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Failed to load chats', life: 3000 })
  } finally {
    threadsLoading.value = false
  }
}

async function openThread(storeId: number) {
  if (!storeId) return
  activeStoreId.value = storeId
  messagesLoading.value = true
  try {
    const res = await ecommerceService.getStoreChatMessages(storeId, { per_page: 100 })
    const rows = extractRows(res.data?.data)
    messages.value = [...rows].reverse()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Chat', detail: error?.response?.data?.message || 'Failed to load messages', life: 3000 })
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
    toast.add({ severity: 'error', summary: 'Send Failed', detail: error?.response?.data?.message || 'Unable to send message', life: 3000 })
  } finally {
    sending.value = false
  }
}

onMounted(loadThreads)

watch(lockedStoreId, async (storeId) => {
  if (!storeId) return
  await openThread(storeId)
  if (lockedProductName.value && !draftMessage.value.trim()) {
    draftMessage.value = `Hi! I'm interested in "${lockedProductName.value}". Is it available?`
  }
})
</script>
