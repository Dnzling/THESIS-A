<template>
  <div class="space-y-4">
  
  
    <Card class="border border-slate-200 shadow-none">
      <template #content>
        <div v-if="loading" class="space-y-3">
          <Skeleton v-for="idx in 7" :key="idx" height="1.1rem" />
        </div>
        <div v-else-if="store" class="space-y-4">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
              <h1 class="text-2xl font-bold text-slate-900">{{ store.store_name }}</h1>
              <p class="text-sm text-slate-500">{{ store.city }} · {{ store.address }}</p>
            </div>
            <Button :label="store?.is_following ? 'Following' : 'Follow Store'" :text="!store?.is_following"
              severity="info" :loading="followLoading" @click="toggleFollow" />
          </div>
  
  
          <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
            <div class="rounded-xl border border-slate-200 p-3 text-sm">Products: <span class="font-semibold">{{
                store.products_count }}</span></div>
            <div class="rounded-xl border border-slate-200 p-3 text-sm">Categories: <span class="font-semibold">{{
                store.categories_count }}</span></div>
            <div class="rounded-xl border border-slate-200 p-3 text-sm">Rating: <span class="font-semibold">{{
                Number(store.rating_avg || 0).toFixed(2) }}</span></div>
            <div class="rounded-xl border border-slate-200 p-3 text-sm">Followers: <span class="font-semibold">{{
                store.followers_count }}</span></div>
          </div>
  
          <div class="flex flex-wrap gap-2">
            <Tag :value="`Response ${Number(store.badges?.response_rate || 0).toFixed(1)}%`" severity="info" />
            <Tag :value="`Cancel ${Number(store.badges?.cancellation_rate || 0).toFixed(1)}%`" severity="secondary" />
            <Tag :value="`Avg ship ${Number(store.badges?.avg_shipping_time_hours || 0).toFixed(1)}h`"
              severity="success" />
          </div>
  
          <div>
            <h2 class="mb-2 text-lg font-semibold text-slate-900">Categories</h2>
            <div class="flex flex-wrap gap-2">
              <Button v-for="category in store.categories || []" :key="category.id" :label="category.name" severity="info"
                outlined size="small" @click="goStoreProducts(category.id)" />
            </div>
          </div>
  
          <div>
            <h2 class="mb-2 text-lg font-semibold text-slate-900">Store Vouchers</h2>
            <div v-if="!(store.vouchers || []).length" class="text-sm text-slate-500">No active vouchers.</div>
            <div v-else class="grid grid-cols-1 gap-2 md:grid-cols-2">
              <div v-for="voucher in store.vouchers" :key="voucher.id"
                class="rounded-xl border border-slate-200 p-3 text-sm">
                <p class="font-semibold">{{ voucher.code }}</p>
                <p class="text-slate-600">
                  {{ voucher.discount_type === 'percent' ? `${voucher.discount_value}% off` : `PHP
                  ${Number(voucher.discount_value).toFixed(2)} off` }}
                </p>
              </div>
            </div>
          </div>
  
          <div class="flex flex-col gap-2 sm:flex-row">
            <Button label="View Products" severity="info" class="w-full sm:w-auto" @click="goStoreProducts()" />
            <Button label="View Reviews" severity="secondary" outlined class="w-full sm:w-auto" @click="goStoreReviews" />
            <Button label="Chat Store" severity="help" outlined class="w-full sm:w-auto" @click="goChatStore" />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import ecommerceService from '@/services/ecommerce.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const followLoading = ref(false)
const store = ref<any>(null)

async function loadStore() {
  loading.value = true
  try {
    const response = await ecommerceService.getStore(route.params.storeId as string)
    store.value = response.data?.data || null
  } catch {
    toast.add({ severity: 'error', summary: 'Store', detail: 'Failed to load store profile.', life: 2200 })
    goStores()
  } finally {
    loading.value = false
  }
}

async function toggleFollow() {
  if (!store.value) return
  followLoading.value = true
  try {
    if (store.value.is_following) {
      await ecommerceService.unfollowStore(store.value.id)
      store.value.is_following = false
      store.value.followers_count = Math.max(0, Number(store.value.followers_count || 0) - 1)
    } else {
      await ecommerceService.followStore(store.value.id)
      store.value.is_following = true
      store.value.followers_count = Number(store.value.followers_count || 0) + 1
    }
  } catch (error: any) {
    const detail = error?.response?.status === 401 ? 'Please login first to follow stores.' : 'Unable to update follow status.'
    toast.add({ severity: 'warn', summary: 'Follow', detail, life: 2200 })
  } finally {
    followLoading.value = false
  }
}

function goStores() {
  router.push({ name: 'ecommerce.stores' })
}

function goStoreProducts(categoryId?: number) {
  router.push({
    name: 'ecommerce.store-products',
    params: { storeId: route.params.storeId },
    query: categoryId ? { category_id: String(categoryId) } : undefined,
  })
}

function goStoreReviews() {
  router.push({
    name: 'ecommerce.store-products',
    params: { storeId: route.params.storeId },
    query: { tab: 'reviews' },
  })
}

function goChatStore() {
  if (!store.value?.id) return
  router.push({ name: 'ecommerce.chats', query: { store_id: String(store.value.id) } })
}

onMounted(loadStore)
</script>
