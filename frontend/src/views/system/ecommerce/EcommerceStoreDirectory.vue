<template>
  <div class="space-y-4">
    <div class="rounded-3xl border border-slate-200 bg-white/70 p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-slate-900">Stores</h1>
        <div class="flex items-center gap-2">
          <InputText v-model="search" placeholder="Search store or city" />
          <Select v-model="sort" :options="sortOptions" optionLabel="label" optionValue="value" placeholder="Sort" class="w-40" />
          <Button label="Search" severity="info" @click="loadStores" />
        </div>
      </div>

      <div v-if="loading" class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Card v-for="idx in 6" :key="idx" class="border border-slate-200 shadow-none">
          <template #content>
            <div class="space-y-2">
              <Skeleton height="1.4rem" width="70%" />
              <Skeleton height="1rem" width="40%" />
              <Skeleton height="1rem" width="100%" />
              <Skeleton height="2rem" width="100%" />
            </div>
          </template>
        </Card>
      </div>

      <div v-else class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Card v-for="store in stores" :key="store.id" class="border border-slate-200 shadow-none">
          <template #content>
            <div class="space-y-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ store.store_name }}</h2>
                <p class="text-xs text-slate-500">{{ store.city || 'N/A City' }}</p>
              </div>

              <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="rounded-lg border border-slate-200 p-2">Products: <span class="font-semibold">{{ store.products_count }}</span></div>
                <div class="rounded-lg border border-slate-200 p-2">Categories: <span class="font-semibold">{{ store.categories_count }}</span></div>
                <div class="rounded-lg border border-slate-200 p-2">Rating: <span class="font-semibold">{{ Number(store.rating_avg || 0).toFixed(2) }}</span></div>
                <div class="rounded-lg border border-slate-200 p-2">Reviews: <span class="font-semibold">{{ store.rating_count }}</span></div>
              </div>

              <div class="flex flex-wrap gap-2 text-xs">
                <Tag :value="`Response ${Number(store.badges?.response_rate || 0).toFixed(1)}%`" severity="info" />
                <Tag :value="`Cancel ${Number(store.badges?.cancellation_rate || 0).toFixed(1)}%`" severity="secondary" />
                <Tag :value="`Ship ${Number(store.badges?.avg_shipping_time_hours || 0).toFixed(1)}h`" severity="success" />
              </div>

              <div class="flex items-center gap-2">
                <Button label="Visit Store" severity="info" fluid @click="goStore(store.id)" />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'

const router = useRouter()
const loading = ref(false)
const stores = ref<any[]>([])
const search = ref('')
const sort = ref<'latest' | 'name'>('latest')
const sortOptions = [
  { label: 'Latest', value: 'latest' },
  { label: 'Name', value: 'name' },
]

async function loadStores() {
  loading.value = true
  try {
    const response = await ecommerceService.getStores({
      search: search.value || undefined,
      sort: sort.value,
      per_page: 24,
    })
    stores.value = response.data?.data?.data || []
  } finally {
    loading.value = false
  }
}

function goStore(storeId: number) {
  router.push({ name: 'ecommerce.store-profile', params: { storeId } })
}

onMounted(loadStores)
</script>
