<template>
  <EcommerceLayout>
    <div class="pb-20 md:pb-0 pt-5">
      <slot />
    </div>

    <nav
      class="fixed bottom-0 left-0 right-0 z-30 border-t border-slate-200 bg-white/95 backdrop-blur md:hidden"
      aria-label="Mobile navigation"
    >
      <div class="mx-auto w-full max-w-7xl px-4">
        <div class="grid grid-cols-5 gap-1 py-2 text-[11px] font-medium text-slate-500">
          <button
            type="button"
            class="flex flex-col items-center gap-1 rounded-lg px-1 py-2 transition-colors"
            :class="isActive(['ecommerce.products']) ? 'text-orange-600' : 'hover:text-slate-900'"
            @click="goTo('ecommerce.products')"
          >
            <i class="pi pi-home text-lg" />
            <span>Home</span>
          </button>
          <button
            type="button"
            class="flex flex-col items-center gap-1 rounded-lg px-1 py-2 transition-colors"
            :class="isActive(['ecommerce.stores', 'ecommerce.store-profile', 'ecommerce.store-products']) ? 'text-orange-600' : 'hover:text-slate-900'"
            @click="goTo('ecommerce.stores')"
          >
            <i class="pi pi-shop text-lg" />
            <span>Stores</span>
          </button>
          <button
            type="button"
            class="flex flex-col items-center gap-1 rounded-lg px-1 py-2 transition-colors"
            :class="isActive(['ecommerce.orders', 'ecommerce.order-detail', 'ecommerce.order-cancel', 'ecommerce.order-return', 'ecommerce.order-review']) ? 'text-orange-600' : 'hover:text-slate-900'"
            @click="goAuth('ecommerce.orders')"
          >
            <i class="pi pi-receipt text-lg" />
            <span>Orders</span>
          </button>
          <button
            type="button"
            class="relative flex flex-col items-center gap-1 rounded-lg px-1 py-2 transition-colors"
            :class="isActive(['ecommerce.cart', 'ecommerce.checkout']) ? 'text-orange-600' : 'hover:text-slate-900'"
            @click="goAuth('ecommerce.cart')"
          >
            <i class="pi pi-shopping-cart text-lg" />
            <span>Cart</span>
            <Badge
              v-if="cartCount"
              :value="String(cartCount)"
              severity="danger"
              class="!absolute !-top-1 !right-3"
            />
          </button>
          <button
            type="button"
            class="flex flex-col items-center gap-1 rounded-lg px-1 py-2 transition-colors"
            :class="isActive(['ecommerce.profile']) ? 'text-orange-600' : 'hover:text-slate-900'"
            @click="goAuth('ecommerce.profile')"
          >
            <i class="pi pi-user text-lg" />
            <span>Profile</span>
          </button>
        </div>
      </div>
    </nav>
  </EcommerceLayout>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import ecommerceService from '@/services/ecommerce.service'
import EcommerceLayout from '@/Layouts/EcommerceLayout.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isLoggedIn = computed(() => authStore.isAuthenticated)
const cartCount = ref(0)

function isActive(names: string[]) {
  return names.includes(String(route.name || ''))
}

function goTo(name: string) {
  router.push({ name })
}

function goAuth(name: string) {
  if (isLoggedIn.value) {
    router.push({ name })
    return
  }
  router.push({ name: 'customer.login', query: { redirect: route.fullPath || '/shop' } })
}

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
