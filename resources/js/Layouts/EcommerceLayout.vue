<template>
  <Head v-if="pageTitle" :title="pageTitle" />
  <div class="min-h-screen bg-linear-to-b from-[#e0e7f5] to-[#f6f9fd]">
    <Toast />
    <header class="sticky top-0 z-20 hidden border-b border-slate-200/70 bg-white/90 backdrop-blur-sm md:block">
      <div class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-2 px-2 py-1.5 sm:px-3 sm:py-2 md:px-4">
        <button link severity="secondary" @click="router.push({name: 'ecommerce.products'})"
          class="flex items-center justify-center rounded-lg">
          <span class="portal-brand text-orange-500 text-base sm:text-lg">FURNISYNC SHOP</span>
        </button>
      
      <div class="hidden md:flex items-center gap-1">
        <Button label="Stores" icon="pi pi-shop" severity="warn" outlined rounded class="compact-button !text-xs !px-2 !py-1.5" @click="router.push({ name: 'ecommerce.stores' })" />
        <Button label="Cart" icon="pi pi-shopping-cart" :badge="cartCount.toString()" severity="warn"
          badgeSeverity="secondary" class="compact-button !text-xs !px-2 !py-1.5" rounded @click="goCart" />
        <Button v-if="!isLoggedIn" icon="pi pi-sign-in" label="Login" rounded outlined severity="warn" class="compact-button !text-sm" size="small" @click="goLogin" />
        <Button v-else icon="pi pi-user" rounded severity="warn" class="!w-8 !h-8 sm:!w-9 sm:!h-9" v-tooltip.bottom="'Profile'"
          @click="toggleProfilePopover" />
      </div>
      </div>
    </header>
  
    <main class="mx-auto w-full max-w-7xl px-3 pb-6 pt-3 sm:px-4 md:px-6 md:pb-8 md:pt-4">
      <slot />
    </main>
  
    <!-- <ScrollTop /> -->
  
    <Popover ref="profilePopoverRef">
      <div class="min-w-44 space-y-2">
        <p class="text-sm font-semibold text-slate-900">{{ customerFullName }}</p>
        <div v-if="chatThreads.length" class="rounded-md border border-slate-200 p-2">
          <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Recent Chats</p>
          <button
            v-for="thread in chatThreads"
            :key="thread.id"
            type="button"
            class="flex w-full items-center justify-between rounded px-2 py-1 text-left text-xs text-slate-700 hover:bg-slate-100"
            @click="goChatThread(thread.store_id)"
          >
            <span class="truncate">{{ thread.store_name }}</span>
            <Tag v-if="thread.unread_count" :value="thread.unread_count" severity="warn" />
          </button>
        </div>
        <div class="space-y-1 border-t border-slate-200 pt-2">
          <Button label="Profile" text severity="secondary" class="w-full justify-start" @click="goProfile" />
          <Button label="Orders" text severity="secondary" class="w-full justify-start" @click="goOrders" />
          <Button label="Chats" text severity="secondary" class="w-full justify-start" @click="goChats" />
          <Button label="Logout" text severity="danger" class="w-full justify-start" @click="logoutCustomer" />
        </div>
      </div>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Head, usePage } from '@inertiajs/vue3'
import ecommerceService from '@/services/ecommerce.service'
import { useAuthStore } from '@/stores/auth'
import Popover from 'primevue/popover'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'

const route = useRoute()
const router = useRouter()
const page = usePage()
const authStore = useAuthStore()
const toast = useToast()
const pageTitle = computed(() => String(page.props?.title || ''))

const cartCount = ref(0)
const isLoggedIn = computed(() => authStore.isAuthenticated)
const profilePopoverRef = ref()
const chatThreads = ref<any[]>([])
const customerFullName = computed(() => {
  const first = authStore.user?.first_name || ''
  const last = authStore.user?.last_name || ''
  return `${first} ${last}`.trim() || 'Customer'
})

async function loadCartCount() {
  if (!isLoggedIn.value) {
    cartCount.value = 0
    return
  }

  try {
    const response = await ecommerceService.getCart()
    const summary = response.data?.data?.summary || {}
    cartCount.value = Number(summary.items_count || 0)
  } catch {
    cartCount.value = 0
  }
}

function goCart() {
  router.push({ name: 'ecommerce.cart' })
}

function goLogin() {
  router.push({ name: 'customer.login', query: { redirect: route.fullPath || '/shop' } })
}

function goProfile() {
  profilePopoverRef.value?.hide()
  router.push({ name: 'ecommerce.profile' })
}

function toggleProfilePopover(event: Event) {
  loadChatThreads()
  profilePopoverRef.value?.toggle(event)
}

function goOrders() {
  profilePopoverRef.value?.hide()
  router.push({ name: 'ecommerce.orders' })
}

function goChats() {
  profilePopoverRef.value?.hide()
  router.push({ name: 'ecommerce.chats' })
}

function goChatThread(storeId: number) {
  profilePopoverRef.value?.hide()
  router.push({ name: 'ecommerce.chats', query: { store_id: String(storeId) } })
}

async function logoutCustomer() {
  profilePopoverRef.value?.hide()
  await authStore.logout({ redirect: false })
  toast.add({ severity: 'success', summary: 'Logged out', detail: 'See you again soon!', life: 1600 })
  router.push({ name: 'ecommerce.products' })
}

function handleCartUpdated() {
  loadCartCount()
}

async function loadChatThreads() {
  if (!isLoggedIn.value) {
    chatThreads.value = []
    return
  }
  try {
    const response = await ecommerceService.getChatThreads({ per_page: 5 })
    const payload = response?.data?.data
    chatThreads.value = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : [])
  } catch {
    chatThreads.value = []
  }
}

watch(() => route.fullPath, loadCartCount)
watch(isLoggedIn, () => {
  loadCartCount()
  loadChatThreads()
})

onMounted(() => {
  loadCartCount()
  loadChatThreads()
  window.addEventListener('ecommerce-cart-updated', handleCartUpdated)
})

onUnmounted(() => {
  window.removeEventListener('ecommerce-cart-updated', handleCartUpdated)
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

@media (max-width: 640px) {
  .compact-button :deep(.p-button-label) {
    display: none;
  }
  .compact-button :deep(.p-button-icon) {
    margin-right: 0;
  }
}
</style>
