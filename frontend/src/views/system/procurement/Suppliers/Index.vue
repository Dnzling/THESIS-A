<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Suppliers</h1>
        <p class="text-gray-600 mt-1">Manage supplier profiles and contacts</p>
      </div>
      <Button label="Add Supplier" icon="pi pi-plus" severity="success" @click="router.push({ name: 'procurement.suppliers.create' })" />
    </div>

    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search supplier" class="w-full" @input="onFilterChange" />
          </IconField>

          <Select v-model="filters.status" :options="statuses" optionLabel="label" optionValue="value" placeholder="Status" showClear class="w-full" @change="onFilterChange" />

          <Button label="Reset" icon="pi pi-filter-slash" severity="secondary" outlined @click="resetFilters" />
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable :value="suppliers" :loading="loading" class="p-datatable-sm" stripedRows>
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No suppliers found</p>
            </div>
          </template>

          <Column field="supplier_name" header="Supplier" />
          <Column field="contact_person" header="Contact" />
          <Column field="phone" header="Phone" />
          <Column field="email" header="Email" />
          <Column field="status" header="Status">
            <template #body="{ data }">
              <Tag :value="data.status || 'active'" :severity="statusSeverity(data.status || 'active')" />
            </template>
          </Column>
          <Column header="Actions" :frozen="true" alignFrozen="right">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-eye" severity="info" text rounded @click="router.push({ name: 'procurement.suppliers.detail', params: { id: data.id } })" />
                <Button icon="pi pi-pencil" severity="warning" text rounded @click="router.push({ name: 'procurement.suppliers.edit', params: { id: data.id } })" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import procurementService, { type Supplier } from '../../../../services/procurement.service'

const router = useRouter()
const loading = ref(false)
const suppliers = ref<Supplier[]>([])

const filters = reactive({
  search: '',
  status: null as string | null
})

const statuses = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Blacklisted', value: 'blacklisted' }
]

const loadSuppliers = async () => {
  loading.value = true
  try {
    const response = await procurementService.getSuppliers(filters)
    suppliers.value = response.data?.data || []
  } catch (error) {
    console.error('Failed to load suppliers', error)
    suppliers.value = []
  } finally {
    loading.value = false
  }
}

const statusSeverity = (status: string) => {
  if (status === 'active') return 'success'
  if (status === 'blacklisted') return 'danger'
  return 'secondary'
}

const onFilterChange = () => {
  loadSuppliers()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = null
  loadSuppliers()
}

onMounted(() => {
  loadSuppliers()
})
</script>
