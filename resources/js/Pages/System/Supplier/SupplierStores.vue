<template>
  <div class="max-w-7xl mx-auto space-y-4 px-4 py-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Linked Stores</h1>
        <p class="mt-1 text-sm text-slate-500">View connected stores or find a store to link.</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" @click="loadAll" :loading="loading" />
    </div>

    <Card class="rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-4 pt-4 pb-1">
          <h2 class="text-sm font-semibold text-slate-800">My Linked Stores</h2>
        </div>
      </template>
      <template #content>
        <div v-if="loading" class="grid grid-cols-1 gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
          <div v-for="row in 3" :key="row" class="space-y-3 rounded-xl border border-slate-200 p-3">
            <Skeleton height="9rem" border-radius="0.75rem" />
            <Skeleton height="1.25rem" width="65%" />
            <Skeleton height="1rem" width="45%" />
          </div>
        </div>
        <div v-else-if="linkedStores.length" class="grid grid-cols-1 gap-4 p-1 sm:grid-cols-2 xl:grid-cols-3">
          <article v-for="store in linkedStores" :key="`${store.store_id}-${store.supplier_id}`" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="relative h-36 bg-slate-100">
              <img v-if="store.store_logo_url" :src="store.store_logo_url" :alt="`${store.store_name} logo`" class="h-full w-full object-cover" />
              <div v-else class="flex h-full items-center justify-center text-slate-400"><i class="pi pi-building text-4xl" aria-hidden="true" /></div>
            </div>
            <div class="space-y-3 p-4">
              <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                  <h3 class="truncate font-semibold text-slate-900">{{ store.store_name }}</h3>
                  <p class="mt-1 text-xs text-slate-500">{{ [store.city, store.province].filter(Boolean).join(', ') || store.address || 'Location not provided' }}</p>
                </div>
                <Tag :value="contractStatusLabel(store.contract_status)" :severity="contractStatusSeverity(store.contract_status)" />
              </div>
              <div class="flex items-center justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
                <span>Linked {{ formatDate(store.linked_at) }}</span>
                <Button label="View" icon="pi pi-eye" size="small" severity="secondary" outlined @click="openStoreShow(store.store_id)" />
              </div>
            </div>
          </article>
        </div>
        <div v-else class="py-8 text-center text-sm text-slate-500">No linked stores yet.</div>
      </template>
    </Card>

    <Card class="rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-4 pt-4 pb-1">
          <h2 class="text-sm font-semibold text-slate-800">Find Stores to Link</h2>
        </div>
      </template>
      <template #content>
        <div class="mb-3 flex flex-wrap items-end gap-2 px-4 pt-2">
          <div class="w-full sm:w-80"><InputText v-model="search" size="small" class="w-full text-sm" placeholder="Search store, code, city, or province" @keyup.enter="loadSearch" /></div>
          <Button label="Search" icon="pi pi-search" size="small" class="text-xs" @click="loadSearch" :loading="searching" />
        </div>

        <div v-if="searching" class="space-y-2 p-4">
          <div v-for="row in 5" :key="row" class="grid grid-cols-2 gap-3 sm:grid-cols-5"><Skeleton v-for="cell in 5" :key="cell" height="2rem" /></div>
        </div>
        <DataTable v-else :value="searchResults" dataKey="id" size="small" paginator :rows="10" :rowsPerPageOptions="[10, 15, 25]" responsiveLayout="scroll" class="p-datatable-sm text-sm" rowHover>
          <template #empty>
            <div class="py-8 text-center text-sm text-slate-500">No matching stores found.</div>
          </template>
          <Column field="name" header="Store" sortable />
          <Column field="province" header="Province" sortable />
          <Column field="city" header="City" sortable />
          <Column field="address" header="Address" />
          <Column header="Actions" style="width:130px">
            <template #body="{ data }">
              <Button label="Link" icon="pi pi-link" size="small" class="text-xs" :loading="linkingStoreId === data.id" @click="doLinkStore(data.id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import supplierService from '../../../services/supplier.service'

const toast = useToast()
const router = useRouter()
const loading = ref(true)
const searching = ref(true)
const linkingStoreId = ref<number | null>(null)
const search = ref('')

const linkedStores = ref<any[]>([])
const searchResults = ref<any[]>([])

const formatDate = (value: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' })
}

const contractStatusLabel = (status?: string | null) => status
  ? status.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
  : 'No contract'
const contractStatusSeverity = (status?: string | null) => {
  if (status === 'active') return 'success'
  if (status === 'pending') return 'warn'
  if (status === 'rejected') return 'danger'
  if (status === 'completed') return 'info'
  return 'secondary'
}

const loadLinkedStores = async () => {
  loading.value = true
  try {
    const res = await supplierService.getLinkedStores()
    linkedStores.value = res?.data ?? []
  } finally {
    loading.value = false
  }
}

const loadSearch = async () => {
  searching.value = true
  try {
    const res = await supplierService.searchStores({ search: search.value, limit: 100 })
    searchResults.value = res?.data ?? []
  } catch {
    searchResults.value = []
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to search stores.', life: 3000 })
  } finally {
    searching.value = false
  }
}

const doLinkStore = async (storeId: number) => {
  linkingStoreId.value = storeId
  try {
    await supplierService.linkStore(storeId)
    toast.add({ severity: 'success', summary: 'Linked', detail: 'Store linked successfully.', life: 2500 })
    await loadAll()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to link store.', life: 3500 })
  } finally {
    linkingStoreId.value = null
  }
}

const openStoreShow = (storeId: number) => {
  router.push({ name: 'supplier.stores.show', params: { storeId } })
}

const loadAll = async () => {
  await Promise.all([loadLinkedStores(), loadSearch()])
}

onMounted(loadAll)
</script>
