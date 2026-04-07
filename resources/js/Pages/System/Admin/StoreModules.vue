<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Store Modules</h2>
        <p class="text-sm text-gray-500">Super admin override of modules per store</p>
      </div>
      <Button icon="pi pi-refresh" text rounded @click="loadModules" :loading="loading" />
    </div>

    <Card>
      <template #title>Store</template>
      <template #content>
        <div class="grid gap-4 md:grid-cols-3">
          <div class="md:col-span-2">
            <Dropdown
              v-model="selectedStoreId"
              :options="stores"
              optionLabel="name"
              optionValue="id"
              placeholder="Select a store"
              class="w-full"
              filter
              :loading="loadingStores"
              @change="loadModules"
            />
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #title>Modules</template>
      <template #content>
        <DataTable :value="modules" :loading="loading" class="p-datatable-sm">
          <Column field="name" header="Module" />
          <Column header="Effective">
            <template #body="{ data }">
              <Badge :value="data.effective_enabled ? 'Enabled' : 'Disabled'" :severity="data.effective_enabled ? 'success' : 'danger'" />
            </template>
          </Column>
          <Column header="Override">
            <template #body="{ data }">
              <div class="flex items-center gap-2">
                <Badge v-if="data.override === true" value="Forced On" severity="success" />
                <Badge v-else-if="data.override === false" value="Forced Off" severity="danger" />
                <span v-else class="text-gray-500 text-sm">None</span>
              </div>
            </template>
          </Column>
          <Column header="Actions">
            <template #body="{ data }">
              <div class="flex flex-wrap gap-2">
                <Button size="small" label="Force On" severity="success" outlined @click="setOverride(data.module_key, true)" />
                <Button size="small" label="Force Off" severity="danger" outlined @click="setOverride(data.module_key, false)" />
                <Button size="small" label="Clear" outlined @click="setOverride(data.module_key, null)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'

const toast = useToast()
const stores = ref<any[]>([])
const modules = ref<any[]>([])
const selectedStoreId = ref<number | null>(null)
const loading = ref(false)
const loadingStores = ref(false)

async function loadStores() {
  loadingStores.value = true
  try {
    // Reuse the main admin stores endpoint for consistency with StoresIndex
    const res = await axiosClient.get('/api/admin/stores', { params: { per_page: 200 } })
    let data = res.data?.data ?? res.data ?? []
    if (!Array.isArray(data) && Array.isArray(data?.data)) {
      data = data.data
    }
    console.debug('[StoreModules] stores response', res.data)
    stores.value = Array.isArray(data) ? data : []
    if (!selectedStoreId.value && stores.value.length > 0) {
      selectedStoreId.value = stores.value[0].id
    }
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load stores', life: 3000 })
  } finally {
    loadingStores.value = false
  }
}

async function loadModules() {
  if (!selectedStoreId.value) return
  loading.value = true
  try {
    const res = await axiosClient.get('/api/admin/store-modules', { params: { store_id: selectedStoreId.value } })
    console.debug('[StoreModules] modules response', res.data)
    modules.value = res.data?.data?.modules ?? []
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load modules', life: 3000 })
  } finally {
    loading.value = false
  }
}

async function setOverride(moduleKey: string, allow: boolean | null) {
  if (!selectedStoreId.value) return
  loading.value = true
  try {
    await axiosClient.post('/api/admin/store-modules/override', {
      store_id: selectedStoreId.value,
      module_key: moduleKey,
      allow,
    })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Override updated', life: 2000 })
    await loadModules()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update override', life: 3000 })
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await loadStores()
  await loadModules()
})
</script>
