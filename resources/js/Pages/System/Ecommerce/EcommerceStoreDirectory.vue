<template>
  <div class="space-y-4">
    <div class="rounded-3xl border border-slate-200 bg-white/70 p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-slate-900">Stores</h1>
        <div class="flex items-center gap-2">
          <InputText v-model="search" placeholder="Search store" fluid />
          <Select v-model="sort" :options="sortOptions" optionLabel="label" optionValue="value" placeholder="Sort" fluid />
          <Button icon="pi pi-search" severity="warn" @click="loadStores" size="small" fluid/>
        </div>
      </div>

      <div v-if="loading" class="mt-4 space-y-3">
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

      <div v-else class="mt-4 space-y-3">
        <Card v-for="store in stores" :key="store.id" class="border border-slate-200 shadow-none">
          <template #content>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div class="min-w-0">
                <h2 class="truncate text-lg font-semibold text-slate-900">{{ store.store_name }}</h2>
                <p class="truncate text-xs text-slate-500">{{ store.city || 'N/A City' }} · {{ store.address || '—' }}</p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs">
                  <span class="rounded-full border border-slate-200 px-2 py-1">
                    Products: <span class="font-semibold">{{ store.products_count }}</span>
                  </span>
                  <span class="rounded-full border border-slate-200 px-2 py-1">
                    Rating: <span class="font-semibold">{{ Number(store.rating_avg || 0).toFixed(2) }}</span>
                  </span>
                  <span class="rounded-full border border-slate-200 px-2 py-1">
                    Reviews: <span class="font-semibold">{{ store.rating_count }}</span>
                  </span>
                </div>
              </div>

              <div class="flex shrink-0 items-center gap-2">
                <Button label="Visit Store" severity="warn" @click="goStore(store.id)" />
              </div>
            </div>
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import EcommerceMobileWrapper from '@/Layouts/EcommerceMobileWrapper.vue'
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import ecommerceService from '@/services/ecommerce.service'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
defineOptions({
  layout: EcommerceMobileWrapper,
})


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
