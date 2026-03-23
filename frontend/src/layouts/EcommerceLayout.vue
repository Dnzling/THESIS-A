<template>
  <div class="min-h-screen bg-linear-to-b from-[#e0e7f5] to-[#f6f9fd]">
    <header class="mx-auto flex w-full max-w-375 items-center justify-between gap-4 px-4 py-5 md:px-30">
      <div>
        <Button link severity="secondary" @click="router.push({name: 'ecommerce.products'})"
          class="flex items-center justify-center rounded-lg">
          <img src="/Furnishop2.png" alt="Furni Shop" class="w-auto object-contain sm:w-28 md:w-40 " />
        </Button>
      </div>
      <div class="flex items-center gap-2">
        <Button label="Stores" severity="info" outlined rounded @click="router.push({ name: 'ecommerce.stores' })" />
        <Button label="Cart" icon="pi pi-shopping-cart" :badge="cartCount.toString()" severity="info"
          badgeSeverity="secondary" class="w-45" rounded @click="goCart" />
        <Button v-if="!isLoggedIn" label="Login" fluid rounded outlined severity="info" @click="goLogin" />
        <Button v-else icon="pi pi-user" rounded severity="info" v-tooltip.bottom="'Profile'"
          @click="toggleProfilePopover" />
      </div>
    </header>
  
    <main class="mx-auto w-full max-w-375 px-10 pb-8 md:px-34">
      <RouterView />
    </main>
  
    <ScrollTop />
  
    <Popover ref="profilePopoverRef">
      <div class="min-w-44 space-y-2">
        <p class="text-sm font-semibold text-slate-900">{{ customerFullName }}</p>
        <div class="space-y-1 border-t border-slate-200 pt-2">
          <Button label="Profile" text severity="secondary" class="w-full justify-start" @click="goProfile" />
          <Button label="Orders" text severity="secondary" class="w-full justify-start" @click="goOrders" />
          <Button label="Logout" text severity="danger" class="w-full justify-start" @click="logoutCustomer" />
        </div>
      </div>
    </Popover>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import { useAuthStore } from '@/stores/auth'
import Popover from 'primevue/popover'
import { useToast } from 'primevue/usetoast'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const cartCount = ref(0)
const isLoggedIn = computed(() => authStore.isAuthenticated)
const profilePopoverRef = ref()
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
  router.push({ name: 'CustomerLogin', query: { redirect: route.fullPath || '/shop' } })
}

function goProfile() {
  profilePopoverRef.value?.hide()
  router.push({ name: 'ecommerce.profile' })
}

function toggleProfilePopover(event: Event) {
  profilePopoverRef.value?.toggle(event)
}

function goOrders() {
  profilePopoverRef.value?.hide()
  router.push({ name: 'ecommerce.orders' })
}

async function logoutCustomer() {
  profilePopoverRef.value?.hide()
  await authStore.logout()
  toast.add({ severity: 'success', summary: 'Logged out', detail: 'See you again soon!', life: 1600 })
  router.push({ name: 'CustomerLogin', query: { redirect: '/shop' } })
}

function handleCartUpdated() {
  loadCartCount()
}

watch(() => route.fullPath, loadCartCount)
watch(isLoggedIn, loadCartCount)

onMounted(() => {
  loadCartCount()
  window.addEventListener('ecommerce-cart-updated', handleCartUpdated)
})

onUnmounted(() => {
  window.removeEventListener('ecommerce-cart-updated', handleCartUpdated)
})
</script>
