<template>
  <Head v-if="pageTitle" :title="pageTitle" />
  <div class="min-h-screen bg-linear-to-b from-[#e0e7f5] to-[#f6f9fd]">
    <Toast class="ecommerce-toast" />
    <ConfirmDialog group="ecommerce-logout" />
    <div class="sticky top-0 z-30">
      <header class="hidden border-b border-slate-200/70 bg-white/90 backdrop-blur-sm md:block">
      <div
        class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-2 px-2 py-1.5 sm:px-3 sm:py-2 md:px-4">
        <button link severity="secondary" @click="router.push({name: 'ecommerce.home'})"
          class="flex items-center justify-center rounded-lg">
          <span class="portal-brand text-orange-500 text-base sm:text-lg">FURNISYNC</span>
        </button>

        <form class="relative mx-4 min-w-0 max-w-xl flex-1" role="search" @submit.prevent="submitProductSearch">
          <IconField class="w-full">
            <InputIcon class="pi pi-search text-slate-400" />
            <InputText
              v-model="productSearch"
              type="search"
              placeholder="Search furniture products"
              aria-label="Search all available furniture products"
              size="small"
              @focus="desktopSearchFocused = true"
              @keydown.esc="desktopSearchFocused = false"
              class="w-full !rounded-full !border-slate-200 !bg-slate-50 !text-sm focus:!border-orange-400 focus:!ring-orange-100"
            />
          </IconField>
          <div v-if="desktopSearchFocused && productSearch.trim()" class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
            <p v-if="productSearch.trim().length < 2" class="px-3 py-4 text-xs text-slate-500">Type at least two letters.</p>
            <SearchSuggestions v-else :items="searchSuggestions" :loading="searchLoading" :error="searchError"
              @select="openSearchProduct" @search="submitProductSearch" />
          </div>
        </form>
        <nav class="flex items-center gap-1" aria-label="Browse furniture">
          <Button label="Products" text severity="secondary" size="small" @click="toggleBrowse('products')" />
          <Button label="Rooms" text severity="secondary" size="small" @click="toggleBrowse('rooms')" />
        </nav>
  
        <div class="hidden items-center gap-2 md:flex">
          <div class="relative flex h-10 w-10 items-center justify-center">
            <Button icon="pi pi-heart" text rounded class="header-action-button" aria-label="Favorites"
              v-tooltip.bottom="'Favorites'" @click="goFavorites" />
            <Badge v-if="favoriteCount > 0" :value="favoriteCount > 99 ? '99+' : String(favoriteCount)"
              severity="danger" class="header-action-badge" />
          </div>
          <div class="relative flex h-10 w-10 items-center justify-center">
            <Button
              icon="pi pi-shopping-cart"
              text
              rounded
              class="header-action-button"
              aria-label="Cart"
              v-tooltip.bottom="'Cart'"
              @click="goCart"
            />
            <Badge
              v-if="cartCount > 0"
              :value="cartCount > 99 ? '99+' : String(cartCount)"
              severity="secondary"
              class="header-action-badge"
            />
          </div>
          <div v-if="isLoggedIn" class="relative flex h-10 w-10 items-center justify-center">
            <Button
              icon="pi pi-bell"
              text
              rounded
              class="header-action-button"
              aria-label="Notifications"
              v-tooltip.bottom="'Notifications'"
              @click="goNotifications"
            />
            <Badge
              v-if="unreadNotificationCount > 0"
              :value="unreadNotificationCount > 99 ? '99+' : String(unreadNotificationCount)"
              severity="danger"
              class="header-action-badge"
            />
          </div>
          <Button v-if="!isLoggedIn" label="Login" rounded class="compact-button !text-sm" size="small" fluid
            @click="goLogin" />
          <div v-else class="flex h-10 w-10 items-center justify-center">
            <Button
              icon="pi pi-user"
              rounded
              text
              class="header-action-button"
              aria-label="Account"
              v-tooltip.bottom="'Account'"
              @click="toggleProfilePopover"
            />
          </div>
        </div>
      </div>
      </header>

      <div class="border-b border-slate-200/70 bg-white/95 px-3 py-2 backdrop-blur-sm md:hidden">
      <div class="mb-2 flex items-center justify-between gap-2">
        <button type="button" class="min-w-0 rounded-lg text-left" aria-label="Go to shop home"
          @click="router.push({ name: 'ecommerce.home' })">
          <span class="portal-brand text-base text-orange-500">FURNISYNC</span>
        </button>
        <div class="flex shrink-0 items-center gap-1">
          <button type="button" class="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-700 hover:bg-orange-50"
            aria-label="Favorites" @click="goFavorites">
            <i class="pi pi-heart" />
            <Badge v-if="favoriteCount > 0" :value="favoriteCount > 99 ? '99+' : String(favoriteCount)" severity="danger"
              class="header-action-badge" />
          </button>
          <button type="button" class="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-700 hover:bg-orange-50"
            aria-label="Cart" @click="goCart">
            <i class="pi pi-shopping-cart" />
            <Badge v-if="cartCount > 0" :value="cartCount > 99 ? '99+' : String(cartCount)" severity="secondary"
              class="header-action-badge" />
          </button>
          <button v-if="isLoggedIn" type="button" class="relative flex h-9 w-9 items-center justify-center rounded-full text-slate-700 hover:bg-orange-50"
            aria-label="Notifications" @click="goNotifications">
            <i class="pi pi-bell" />
            <Badge v-if="unreadNotificationCount > 0" :value="unreadNotificationCount > 99 ? '99+' : String(unreadNotificationCount)" severity="danger"
              class="header-action-badge" />
          </button>
          <Button v-if="!isLoggedIn" label="Login" rounded size="small" class="!px-3 !text-xs" @click="goLogin" />
          <button v-else type="button" class="flex h-9 w-9 items-center justify-center rounded-full text-slate-700 hover:bg-orange-50"
            aria-label="Account" @click="toggleProfilePopover">
            <i class="pi pi-user" />
          </button>
        </div>
      </div>
      <button type="button" class="flex w-full items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-left text-sm text-slate-500"
        aria-label="Search furniture products" @click="openMobileSearch">
        <i class="pi pi-search" />{{ productSearch || 'Search furniture products' }}
      </button>
      <div class="mt-2 flex gap-2">
        <Button label="Products" text severity="secondary" size="small" @click="toggleBrowse('products')" />
        <Button label="Rooms" text severity="secondary" size="small" @click="toggleBrowse('rooms')" />
      </div>
      </div>

    <div v-if="mobileSearchOpen" class="fixed inset-0 z-[100] flex flex-col bg-white md:hidden" role="dialog" aria-modal="true" aria-label="Search products">
      <div class="flex items-center gap-2 border-b border-slate-200 px-3 py-3">
        <Button icon="pi pi-arrow-left" text rounded severity="secondary" aria-label="Close search" @click="closeMobileSearch" />
        <form class="min-w-0 flex-1" role="search" @submit.prevent="submitProductSearch">
          <IconField class="w-full">
            <InputIcon class="pi pi-search text-slate-400" />
            <InputText ref="mobileSearchInput" v-model="productSearch" type="search" size="small" fluid
              placeholder="Search furniture products" aria-label="Search furniture products" />
          </IconField>
        </form>
      </div>
      <div class="min-h-0 flex-1 overflow-y-auto p-4">
        <p v-if="!productSearch.trim()" class="text-sm text-slate-500">Start typing to find furniture.</p>
        <p v-else-if="productSearch.trim().length < 2" class="text-sm text-slate-500">Type at least two letters.</p>
        <SearchSuggestions v-else :items="searchSuggestions" :loading="searchLoading" :error="searchError"
          @select="openSearchProduct" @search="submitProductSearch" />
      </div>
    </div>

      <section v-if="browsePanel" class="absolute inset-x-0 top-full max-h-[calc(100vh-8rem)] overflow-y-auto border-b border-slate-200 bg-white px-4 py-4 shadow-xl">
      <div class="mx-auto max-w-7xl rounded-2xl">
        <div class="mb-3 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">{{ browsePanel === 'rooms' ? 'Shop by room' : 'Explore products' }}</h2>
          <Button icon="pi pi-times" text rounded severity="secondary" size="small" aria-label="Close browse panel" @click="browsePanel = ''" />
        </div>
        <div v-if="browsePanel === 'products'" class="space-y-3">
          <div class="flex flex-wrap gap-2">
            <Button label="All Categories" severity="warn" outlined size="small" @click="openBrowseRoute('ecommerce.categories')" />
            <Button label="New Products" severity="warn" outlined size="small" @click="openBrowseRoute('ecommerce.new-products')" />
            <Button label="Trending Products" severity="warn" outlined size="small" @click="openBrowseRoute('ecommerce.trending.view')" />
          </div>
          <div class="flex flex-wrap gap-2">
            <button v-for="category in browseCategories" :key="category.id" type="button"
              class="rounded-xl border border-slate-200 px-4 py-2 text-xs text-slate-700 hover:border-orange-400"
              @click="openBrowseCategory(category.id)">{{ category.category_name }}</button>
          </div>
        </div>
        <template v-else>
          <div class="grid max-h-80 grid-cols-2 gap-3 overflow-y-auto pr-1 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            <button v-for="room in browseRooms" :key="room.id" type="button"
              class="overflow-hidden rounded-xl border text-left text-xs"
              :class="selectedRoom?.id === room.id ? 'border-orange-500' : 'border-slate-200'"
              @click="selectedRoom = room">
              <img :src="room.image_url || '/F.svg'" :alt="room.name" class="h-24 w-full object-cover" />
              <span class="block truncate p-2 font-medium">{{ room.name }}</span>
            </button>
          </div>
          <div v-if="selectedRoom" class="mt-3">
            <p class="mb-2 text-xs text-slate-500">Choose a category for {{ selectedRoom.name }}</p>
            <div class="flex flex-wrap gap-2">
              <button v-for="category in browseCategories" :key="category.id" type="button"
                class="rounded-full border border-slate-200 px-3 py-1.5 text-xs hover:border-orange-400"
                @click="openBrowseCategory(category.id, selectedRoom.slug)">{{ category.category_name }}</button>
            </div>
          </div>
        </template>
      </div>
      </section>
    </div>
  
    <main class="mx-auto w-full max-w-7xl px-3 pb-6 pt-3 sm:px-4 md:px-6 md:pb-8 md:pt-4">
      <slot />
    </main>
  
    <!-- <ScrollTop /> -->
  
    <Popover ref="profilePopoverRef" class="w-64 p-3">
  
      <div class="">
        <p class="text-base font-semibold leading-5 text-slate-900">{{ customerFullName }}</p>
        <p class="mt-1 mb-3 truncate text-xs text-slate-500">{{ customerEmail }}</p>
        <div v-if="chatThreads.length" class="rounded-md border border-slate-200 p-2">
          <p class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Recent Chats</p>
          <button v-for="thread in chatThreads" :key="thread.id" type="button"
            class="flex w-full items-center justify-between rounded px-2 py-1 text-left text-xs text-slate-700 hover:bg-slate-100"
            @click="goChatThread(thread.store_id)">
            <span class="truncate">{{ thread.store_name }}</span>
            <Tag v-if="thread.unread_count" :value="thread.unread_count" severity="warn" />
          </button>
        </div>
        <div class="space-y-1 border-t border-slate-200 pt-2">
          <Button label="Profile" icon="pi pi-user" text severity="secondary"
            class="w-full !justify-start !px-2 !py-2 text-left"
            :pt="{ root: { class: '!justify-start' }, label: { class: '!text-left' } }" @click="goProfile" />
          <Button label="Orders" icon="pi pi-shopping-bag" text severity="secondary"
            class="w-full !justify-start !px-2 !py-2 text-left"
            :pt="{ root: { class: '!justify-start' }, label: { class: '!text-left' } }" @click="goOrders" />
          <Button label="Favorites" icon="pi pi-heart" text severity="secondary"
            class="w-full !justify-start !px-2 !py-2 text-left"
            :pt="{ root: { class: '!justify-start' }, label: { class: '!text-left' } }" @click="goFavorites" />
          <Button label="Chats" icon="pi pi-comments" text severity="secondary"
            class="w-full !justify-start !px-2 !py-2 text-left"
            :pt="{ root: { class: '!justify-start' }, label: { class: '!text-left' } }" @click="goChats" />
          <Button label="Logout" icon="pi pi-sign-out" text severity="danger"
            class="w-full !justify-start !px-2 !py-2 text-left"
            :pt="{ root: { class: '!justify-start' }, label: { class: '!text-left' } }" @click="logoutCustomer" />
        </div>
      </div>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Head, usePage } from '@inertiajs/vue3'
import ecommerceService from '@/services/ecommerce.service'
import { useAuthStore } from '@/stores/auth'
import Popover from 'primevue/popover'
import { useToast } from 'primevue/usetoast'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import { useConfirm } from 'primevue/useconfirm'
import axiosClient from '@/axios'
import SearchSuggestions from '@/Components/Ecommerce/SearchSuggestions.vue'

const route = useRoute()
const router = useRouter()
const page = usePage()
const authStore = useAuthStore()
const toast = useToast()
const confirm = useConfirm()
const pageTitle = computed(() => String(page.props?.title || ''))
const productSearch = ref('')
const desktopSearchFocused = ref(false)
const mobileSearchOpen = ref(false)
const mobileSearchInput = ref<any>(null)
const searchSuggestions = ref<any[]>([])
const searchLoading = ref(false)
const searchError = ref('')
let searchTimer: ReturnType<typeof setTimeout> | undefined
let searchRequest = 0

async function loadSearchSuggestions(term: string, request: number) {
  searchLoading.value = true
  searchError.value = ''
  try {
    const response = await ecommerceService.getProductSuggestions(term)
    if (request !== searchRequest) return
    searchSuggestions.value = response.data?.data || []
  } catch {
    if (request === searchRequest) {
      searchSuggestions.value = []
      searchError.value = 'Search is unavailable right now. Please try again.'
    }
  } finally {
    if (request === searchRequest) searchLoading.value = false
  }
}

watch(productSearch, (value) => {
  clearTimeout(searchTimer)
  const request = ++searchRequest
  const term = value.trim()
  searchSuggestions.value = []
  searchError.value = ''
  if (term.length < 2) {
    searchLoading.value = false
    return
  }
  searchLoading.value = true
  searchTimer = setTimeout(() => loadSearchSuggestions(term.toLowerCase(), request), 250)
})

function openMobileSearch() {
  browsePanel.value = ''
  mobileSearchOpen.value = true
  nextTick(() => (mobileSearchInput.value?.$el || mobileSearchInput.value)?.focus?.())
}

function closeMobileSearch() {
  mobileSearchOpen.value = false
}

function handleOutsideSearch(event: MouseEvent) {
  if (!(event.target as Element)?.closest?.('[role="search"]')) desktopSearchFocused.value = false
}

function openSearchProduct(id: number) {
  desktopSearchFocused.value = false
  closeMobileSearch()
  router.push({ name: 'ecommerce.product', params: { id } })
}
const browsePanel = ref('')
const browseCategories = ref<any[]>([])
const browseRooms = ref<any[]>([])
const selectedRoom = ref<any>(null)
async function toggleBrowse(panel: string) {
  browsePanel.value = browsePanel.value === panel ? '' : panel
  if (!browsePanel.value) return
  if (!browseCategories.value.length) {
    try {
      const response = await ecommerceService.getActiveStockCategories()
      browseCategories.value = response.data?.data || []
    } catch { browseCategories.value = [] }
  }
  if (panel === 'rooms' && !browseRooms.value.length) {
    try {
      const response = await axiosClient.get('/api/public/shopping-rooms')
      browseRooms.value = response.data?.data || []
    } catch { browseRooms.value = [] }
  }
}
function openBrowseRoute(name: string) {
  browsePanel.value = ''
  router.push({ name })
}
function openBrowseCategory(categoryId: number, room?: string) {
  browsePanel.value = ''
  router.push({ name: 'ecommerce.products', query: { category_id: categoryId, ...(room ? { room } : {}) } })
}

const cartCount = ref(0)
const favoriteCount = ref(0)
const unreadNotificationCount = ref(0)
const isLoggedIn = computed(() => authStore.isAuthenticated)
const profilePopoverRef = ref()
const chatThreads = ref<any[]>([])
const customerFullName = computed(() => {
  const first = authStore.user?.first_name || authStore.user?.fname || ''
  const last = authStore.user?.last_name || authStore.user?.lname || ''
  return `${first} ${last}`.trim() || 'Customer'
})
const customerEmail = computed(() => authStore.user?.email || 'No email available')

async function loadCartCount() {
  if (!isLoggedIn.value) {
    cartCount.value = 0
    return
  }

  try {
    const response = await ecommerceService.getCarts()
    const carts = response.data?.data || []
    cartCount.value = carts.reduce((sum: number, c: any) => sum + Number(c.items_count || 0), 0)
  } catch {
    cartCount.value = 0
  }
}

async function loadFavoriteCount() {
  if (!isLoggedIn.value) {
    favoriteCount.value = 0
    return
  }
  try {
    const response = await ecommerceService.getFavorites()
    favoriteCount.value = (response.data?.data?.product_ids || []).length
  } catch {
    favoriteCount.value = 0
  }
}

function goCart() {
  router.push({ name: 'ecommerce.cart' })
}

function goFavorites() {
  profilePopoverRef.value?.hide()
  if (isLoggedIn.value) {
    router.push({ name: 'ecommerce.favorites' })
    return
  }
  router.push({ name: 'customer.login', query: { redirect: '/favorites' } })
}

function searchFromUrl() {
  const query = String(page.url || '').split('?')[1] || ''
  return new URLSearchParams(query).get('search') || ''
}

function submitProductSearch() {
  const search = productSearch.value.trim()
  desktopSearchFocused.value = false
  closeMobileSearch()
  router.push({
    name: 'ecommerce.products',
    query: search ? { search } : {},
  })
}

function goLogin() {
  router.push({ name: 'customer.login', query: { redirect: route.fullPath || '/shop' } })
}

function goProfile() {
  profilePopoverRef.value?.hide()
  router.push({ name: 'ecommerce.profile' })
}

function goNotifications() {
  router.push({ name: 'ecommerce.notifications' })
}

async function loadUnreadNotificationCount() {
  if (!isLoggedIn.value) {
    unreadNotificationCount.value = 0
    return
  }
  try {
    const response = await axiosClient.get('/api/notifications/unread', {
      params: { module: 'ecommerce' },
      headers: { 'X-Suppress-Dialog': '1' },
    })
    unreadNotificationCount.value = Number(response.data?.data?.unread_count || 0)
  } catch {
    unreadNotificationCount.value = 0
  }
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
  confirm.require({
    group: 'ecommerce-logout',
    message: 'Are you sure you want to log out?',
    header: 'Confirm Logout',
    rejectProps: {
      label: 'Cancel',
      severity: 'secondary',
      outlined: true,
    },
    acceptProps: {
      label: 'Log out',
      severity: 'danger',
    },
    accept: async () => {
      await authStore.logout({ redirect: false })
      toast.add({ severity: 'success', summary: 'Logged out', detail: 'See you again soon!', life: 1600 })
      router.push({ name: 'ecommerce.home' })
    },
  })
}

function handleCartUpdated() {
  loadCartCount()
}

function handleFavoritesUpdated() {
  loadFavoriteCount()
}

function handleNotificationsUpdated() {
  loadUnreadNotificationCount()
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

watch(() => route.fullPath, () => {
  loadCartCount()
  loadFavoriteCount()
  if (route.name === 'ecommerce.notifications') loadUnreadNotificationCount()
})
watch(() => page.url, () => {
  productSearch.value = searchFromUrl()
}, { immediate: true })
watch(isLoggedIn, () => {
  loadCartCount()
  loadFavoriteCount()
  loadChatThreads()
  if (route.name === 'ecommerce.notifications') loadUnreadNotificationCount()
})

onMounted(() => {
  document.addEventListener('pointerdown', handleOutsideSearch)
  loadCartCount()
  loadFavoriteCount()
  loadChatThreads()
  if (route.name === 'ecommerce.notifications') loadUnreadNotificationCount()
  window.addEventListener('ecommerce-cart-updated', handleCartUpdated)
  window.addEventListener('ecommerce-favorites-updated', handleFavoritesUpdated)
  window.addEventListener('ecommerce-notifications-updated', handleNotificationsUpdated)
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', handleOutsideSearch)
  clearTimeout(searchTimer)
  searchRequest++
  window.removeEventListener('ecommerce-cart-updated', handleCartUpdated)
  window.removeEventListener('ecommerce-favorites-updated', handleFavoritesUpdated)
  window.removeEventListener('ecommerce-notifications-updated', handleNotificationsUpdated)
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

.header-action-button {
  width: 2.5rem !important;
  height: 2.5rem !important;
  min-width: 2.5rem !important;
  padding: 0 !important;
}

.header-action-badge {
  position: absolute !important;
  right: -0.2rem !important;
  top: -0.2rem !important;
  min-width: 1.15rem !important;
  height: 1.15rem !important;
  padding: 0 0.25rem !important;
  font-size: 0.65rem !important;
  line-height: 1.15rem !important;
}

@media (max-width: 767px) {
  :deep(.ecommerce-toast.p-toast-top-right) {
    top: calc(env(safe-area-inset-top, 0px) + 8.75rem) !important;
    right: auto !important;
    left: 50% !important;
    width: min(24rem, calc(100vw - 1.5rem));
    transform: translateX(-50%);
  }
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
