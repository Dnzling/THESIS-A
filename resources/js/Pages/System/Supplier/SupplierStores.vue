<template>
  <div class="max-w-7xl mx-auto space-y-5 py-5 px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Linked Stores</h1>
        <p class="text-xs text-gray-500 mt-1">View stores connected to your supplier account and search for new stores to link.</p>
      </div>
      <Button label="Refresh" icon="pi pi-refresh" severity="secondary" outlined size="small" @click="loadAll" :loading="loading" />
    </div>

    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-5 pt-5 pb-1">
          <h2 class="font-semibold text-gray-900">My Linked Stores</h2>
        </div>
      </template>
      <template #content>
        <DataTable :value="linkedStores" dataKey="store_id" :loading="loading" size="small" stripedRows paginator :rows="10" responsiveLayout="scroll">
          <template #empty>
            <div class="py-6 text-center text-sm text-gray-500">No linked stores yet.</div>
          </template>
          <Column field="store_name" header="Store" sortable />
          <Column field="province" header="Province" sortable />
          <Column field="city" header="City" sortable />
          <Column field="address" header="Address" />
          <Column field="linked_at" header="Linked On" sortable>
            <template #body="{ data }">{{ formatDate(data.linked_at) }}</template>
          </Column>
          <Column header="Action" style="width:120px">
            <template #body="{ data }">
              <Button label="View" icon="pi pi-eye" size="small" outlined @click="openStoreShow(data.store_id)" />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Card class="rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <template #header>
        <div class="px-5 pt-5 pb-1">
          <h2 class="font-semibold text-gray-900">Search Stores to Link</h2>
        </div>
      </template>
      <template #content>
        <div class="flex flex-col md:flex-row gap-2 mb-4">
          <InputText v-model="search" class="flex-1" placeholder="Search store name, code, city, province" @keyup.enter="loadSearch" />
          <Button label="Search" icon="pi pi-search" size="small" @click="loadSearch" :loading="searching" />
        </div>

        <DataTable :value="searchResults" dataKey="id" :loading="searching" size="small" stripedRows paginator :rows="10" responsiveLayout="scroll">
          <template #empty>
            <div class="py-6 text-center text-sm text-gray-500">No stores found.</div>
          </template>
          <Column field="name" header="Store" sortable />
          <Column field="province" header="Province" sortable />
          <Column field="city" header="City" sortable />
          <Column field="address" header="Address" />
          <Column header="Action" style="width:130px">
            <template #body="{ data }">
              <Button label="Link" icon="pi pi-link" size="small" :loading="linkingStoreId === data.id" @click="doLinkStore(data.id)" />
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
import supplierService from '../../../services/supplier.service'

const toast = useToast()
const router = useRouter()
const loading = ref(false)
const searching = ref(false)
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
