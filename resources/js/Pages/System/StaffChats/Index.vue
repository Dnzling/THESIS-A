<template>
    <div
        class="h-[calc(100vh-8.5rem)] min-h-[620px] overflow-hidden rounded-3xl border border-orange-100 bg-white shadow-sm"
    >
        <div class="grid h-full grid-cols-1 lg:grid-cols-[340px_1fr]">
            <section class="order-last flex min-w-0 flex-col">
                <header
                    class="flex h-20 items-center justify-between border-b border-orange-100 px-6"
                >
                    <div v-if="selected" class="flex items-center gap-3">
                        <Avatar
                            :image="selected.staff?.avatar_url"
                            :label="initials(selected.staff)"
                            shape="circle"
                            class="bg-orange-100 text-orange-700"
                        />
                        <div>
                            <h1 class="font-semibold text-slate-900">
                                {{ name(selected.staff) }}
                            </h1>
                            <p class="text-xs text-slate-500">
                                {{ selected.staff?.email }}
                            </p>
                        </div>
                    </div>
                    <div v-else>
                        <h1 class="text-xl font-semibold text-slate-900">
                            Staff Chats
                        </h1>
                        <p class="text-sm text-slate-500">
                            Select a staff conversation
                        </p>
                    </div>
                    <i class="pi pi-comments text-xl text-orange-500"></i>
                </header>

                <div
                    ref="historyEl"
                    class="flex-1 space-y-4 overflow-y-auto bg-orange-50/30 p-5 sm:p-7"
                >
                    <div
                        v-if="loadingMessages"
                        class="flex h-full items-center justify-center"
                    >
                        <ProgressSpinner class="h-10 w-10" />
                    </div>
                    <div
                        v-else-if="!selected"
                        class="flex h-full flex-col items-center justify-center text-center text-slate-400"
                    >
                        <i
                            class="pi pi-comments mb-3 text-5xl text-orange-200"
                        ></i>
                        <p>Choose a conversation from the left.</p>
                    </div>
                    <div
                        v-else-if="!messages.length"
                        class="flex h-full items-center justify-center text-sm text-slate-400"
                    >
                        No messages yet. Say hello!
                    </div>
                    <div
                        v-for="message in messages"
                        :key="message.id"
                        class="flex gap-2"
                        :class="
                            message.sender_user_id === meId
                                ? 'justify-end'
                                : 'justify-start'
                        "
                    >
                        <Avatar
                            v-if="message.sender_user_id !== meId"
                            :image="message.sender?.avatar_url"
                            :label="initials(message.sender)"
                            shape="circle"
                            size="small"
                            class="mt-1 bg-slate-200"
                        />
                        <div class="max-w-[78%]">
                            <div
                                class="rounded-2xl px-4 py-3 text-sm shadow-sm"
                                :class="
                                    message.sender_user_id === meId
                                        ? 'rounded-br-md bg-orange-500 text-white'
                                        : 'rounded-bl-md border border-slate-100 bg-white text-slate-800'
                                "
                            >
                                <p
                                    v-if="message.message"
                                    class="whitespace-pre-wrap break-words"
                                >
                                    {{ message.message }}
                                </p>
                                <img
                                    v-if="isImage(message)"
                                    :src="message.attachment_url"
                                    class="mt-2 max-h-72 max-w-full rounded-xl object-contain"
                                    :alt="message.attachment_name"
                                />
                                <a
                                    v-else-if="message.attachment_url"
                                    :href="message.attachment_url"
                                    class="mt-2 flex items-center gap-2 rounded-xl bg-black/10 p-3 font-medium hover:underline"
                                    ><i class="pi pi-paperclip"></i
                                    ><span class="truncate">{{
                                        message.attachment_name
                                    }}</span></a
                                >
                            </div>
                            <p
                                class="mt-1 px-1 text-[11px] text-slate-400"
                                :class="
                                    message.sender_user_id === meId
                                        ? 'text-right'
                                        : ''
                                "
                            >
                                {{ time(message.created_at) }}
                            </p>
                        </div>
                    </div>
                </div>

                <form
                    v-if="selected"
                    class="border-t border-orange-100 bg-white p-4"
                    @submit.prevent="send"
                >
                    <div
                        v-if="file"
                        class="mb-2 flex items-center justify-between rounded-xl bg-orange-50 px-3 py-2 text-xs text-orange-800"
                    >
                        <span class="truncate"
                            ><i class="pi pi-paperclip mr-2"></i
                            >{{ file.name }}</span
                        ><button type="button" @click="file = null">
                            <i class="pi pi-times"></i>
                        </button>
                    </div>
                    <div class="flex items-end gap-2">
                        <Textarea
                            v-model="draft"
                            autoResize
                            rows="1"
                            fluid
                            class="flex-1"
                            placeholder="Write a message"
                            @keydown.enter.exact.prevent="send"
                        />
                        <label
                            class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-xl border border-orange-200 text-orange-600 hover:bg-orange-50"
                            title="Attach file"
                            ><i class="pi pi-paperclip"></i
                            ><input
                                type="file"
                                class="hidden"
                                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip"
                                @change="chooseFile"
                        /></label>
                        <Button
                            type="submit"
                            icon="pi pi-send"
                            label="Send"
                            :loading="sending"
                            :disabled="!draft.trim() && !file"
                            class="!border-orange-500 !bg-orange-500"
                        />
                    </div>
                </form>
            </section>

            <aside class="order-first flex min-h-0 flex-col border-r border-orange-100 bg-white">
                <div class="border-b border-orange-100 p-4">
                    <h2 class="mb-3 font-semibold text-slate-900">Messages</h2>
                    <div class="flex gap-2">
                        <InputText v-model="search" fluid placeholder="Search staff or chats" />
                        <Button icon="pi pi-plus" aria-label="New message" class="!border-orange-500 !bg-orange-500" @click="newMessageVisible = true" />
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-3">
                    <button
                        v-for="chat in filtered"
                        :key="chat.id"
                        class="mb-2 flex w-full gap-3 rounded-2xl p-3 text-left transition"
                        :class="
                            selectedId === chat.id
                                ? 'bg-orange-100'
                                : 'hover:bg-orange-50'
                        "
                        @click="open(chat)"
                    >
                        <Avatar
                            :image="chat.staff?.avatar_url"
                            :label="initials(chat.staff)"
                            shape="circle"
                            class="bg-orange-200 text-orange-800"
                        />
                        <div class="min-w-0 flex-1">
                            <div class="flex justify-between gap-2">
                                <p
                                    class="truncate text-sm font-semibold text-slate-900"
                                >
                                    {{ name(chat.staff) }}
                                </p>
                                <span
                                    class="shrink-0 text-[11px] text-slate-400"
                                    >{{ shortTime(chat.last_message_at) }}</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <p
                                    class="flex-1 truncate text-xs text-slate-500"
                                >
                                    {{ chat.last_message }}
                                </p>
                                <Badge
                                    v-if="chat.unread_count"
                                    :value="chat.unread_count"
                                    severity="warn"
                                />
                            </div>
                        </div>
                    </button>
                    <p
                        v-if="!filtered.length"
                        class="p-4 text-center text-sm text-slate-400"
                    >
                        No recorded chats yet.
                    </p>
                </div>
            </aside>
        </div>
    </div>

    <Dialog v-model:visible="newMessageVisible" modal header="New Message" :style="{ width: '430px' }" :breakpoints="{ '640px': 'calc(100vw - 2rem)' }">
        <p class="mb-4 text-sm text-slate-500">Select a staff member to start a conversation.</p>
        <InputText v-model="staffSearch" fluid placeholder="Search users" class="mb-3" />
        <div class="max-h-[430px] space-y-1 overflow-y-auto pr-1">
            <button v-for="person in filteredStaff" :key="person.id" class="flex w-full items-center gap-3 rounded-2xl p-3 text-left hover:bg-orange-50" @click="startChat(person.id)">
                <Avatar :image="person.avatar_url" :label="initials(person)" shape="circle" class="bg-orange-100 text-orange-700" />
                <div class="min-w-0 flex-1"><p class="truncate font-medium text-slate-900">{{ name(person) }}</p><p class="truncate text-sm text-slate-500">{{ staffDescription(person) }}</p></div>
                <i class="pi pi-chevron-right text-xs text-orange-400"></i>
            </button>
            <p v-if="!filteredStaff.length" class="py-8 text-center text-sm text-slate-400">No staff users found.</p>
        </div>
    </Dialog>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useToast } from 'primevue/usetoast'
import Avatar from 'primevue/avatar'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import InputText from 'primevue/inputtext'
import ProgressSpinner from 'primevue/progressspinner'
import Textarea from 'primevue/textarea'
import chatService from '@/services/staff-chat.service'

const page = usePage()
const toast = useToast()
const meId = computed(() =>
    Number(
        (page.props.auth as any)?.user?.id ||
            JSON.parse(localStorage.getItem('user') || '{}').id
    )
)
const conversations = ref<any[]>([]),
    staff = ref<any[]>([]),
    messages = ref<any[]>([]),
    selectedId = ref<number | null>(null),
    search = ref(''),
    staffSearch = ref(''),
    draft = ref(''),
    file = ref<File | null>(null),
    sending = ref(false),
    loadingMessages = ref(false),
    historyEl = ref<HTMLElement | null>(null),
    newMessageVisible = ref(false)
const selected = computed(() =>
    conversations.value.find(c => Number(c.id) === selectedId.value)
)
const filtered = computed(() =>
    conversations.value.filter(c =>
        (name(c.staff) + ' ' + (c.last_message || ''))
            .toLowerCase()
            .includes(search.value.toLowerCase())
    )
)
const filteredStaff = computed(() =>
    staff.value.filter(u =>
        `${name(u)} ${u.email || ''} ${u.role?.display_name || ''} ${u.branch?.name || ''}`
            .toLowerCase()
            .includes(staffSearch.value.toLowerCase())
    )
)
const name = (u: any) => `${u?.fname || ''} ${u?.lname || ''}`.trim() || 'Staff'
const initials = (u: any) =>
    `${u?.fname?.[0] || ''}${u?.lname?.[0] || ''}`.toUpperCase() || 'S'
const staffDescription = (u: any) =>
    [u.role?.display_name, u.branch?.name].filter(Boolean).join(' · ') || u.email
const time = (v: string) =>
    v
        ? new Date(v).toLocaleString('en-PH', {
              month: 'short',
              day: 'numeric',
              hour: 'numeric',
              minute: '2-digit'
          })
        : ''
const shortTime = (v: string) =>
    v
        ? new Date(v).toLocaleTimeString('en-PH', {
              hour: 'numeric',
              minute: '2-digit'
          })
        : ''
const isImage = (m: any) => String(m.attachment_mime || '').startsWith('image/')
const rows = (r: any) =>
    Array.isArray(r?.data)
        ? r.data
        : Array.isArray(r?.data?.data)
        ? r.data.data
        : []
const load = async () => {
    const [c, s] = await Promise.all([
        chatService.conversations(),
        chatService.staff()
    ])
    conversations.value = rows(c)
    staff.value = rows(s)
    const queryId = Number(
        new URLSearchParams(location.search).get('conversation') || 0
    )
    if (queryId || conversations.value.length)
        await open(
            conversations.value.find(x => x.id === queryId) ||
                conversations.value[0]
        )
}
const open = async (c: any) => {
    if (!c) return
    selectedId.value = Number(c.id)
    loadingMessages.value = true
    try {
        const r = await chatService.messages(c.id)
        messages.value = rows(r).reverse()
        c.unread_count = 0
        await nextTick()
        if (historyEl.value)
            historyEl.value.scrollTop = historyEl.value.scrollHeight
    } finally {
        loadingMessages.value = false
    }
}
const startChat = async (userId: number) => {
    const r = await chatService.start(userId)
    newMessageVisible.value = false
    staffSearch.value = ''
    await load()
    const c = conversations.value.find(x => x.id === r.data.id)
    if (c) await open(c)
}
const chooseFile = (e: Event) => {
    file.value = (e.target as HTMLInputElement).files?.[0] || null
}
const send = async () => {
    if (!selectedId.value || (!draft.value.trim() && !file.value)) return
    const form = new FormData()
    if (draft.value.trim()) form.append('message', draft.value.trim())
    if (file.value) form.append('attachment', file.value)
    sending.value = true
    try {
        await chatService.send(selectedId.value, form)
        draft.value = ''
        file.value = null
        await open(selected.value)
        await load()
    } catch (e: any) {
        toast.add({
            severity: 'error',
            summary: 'Staff Chat',
            detail: e?.response?.data?.message || 'Message could not be sent',
            life: 3000
        })
    } finally {
        sending.value = false
    }
}
onMounted(() =>
    load().catch(() =>
        toast.add({
            severity: 'error',
            summary: 'Staff Chat',
            detail: 'Unable to load staff chats',
            life: 3000
        })
    )
)
</script>
