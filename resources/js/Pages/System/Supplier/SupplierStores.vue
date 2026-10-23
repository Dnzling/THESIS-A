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
        <div v-if="loading" class="space-y-2 p-4">
          <div v-for="row in 5" :key="row" class="grid grid-cols-2 gap-3 sm:grid-cols-5"><Skeleton v-for="cell in 5" :key="cell" height="2rem" /></div>
        </div>
        <DataTable v-else :value="linkedStores" dataKey="store_id" size="small" paginator :rows="10" :rowsPerPageOptions="[10, 15, 25]" responsiveLayout="scroll" class="p-datatable-sm text-sm" rowHover>
          <template #empty>
            <div class="py-8 text-center text-sm text-slate-500">No linked stores yet.</div>
          </template>
          <Column field="store_name" header="Store" sortable />
          <Column field="province" header="Province" sortable />
          <Column field="city" header="City" sortable />
          <Column field="address" header="Address" />
          <Column field="linked_at" header="Linked On" sortable>
            <template #body="{ data }">{{ formatDate(data.linked_at) }}</template>
          </Column>
          <Column header="Actions" style="width:120px">
            <template #body="{ data }">
              <Button label="View" icon="pi pi-eye" size="small" outlined rounded class="text-xs" @click="openStoreShow(data.store_id)" />
            </template>
          </Column>
        </DataTable>
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
