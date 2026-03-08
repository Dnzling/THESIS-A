<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Inventory Items</h1>
        <p class="text-gray-600 mt-1">View and manage inventory across all branches</p>
      </div>
    </div>

    <!-- Filters -->
    <Card class="mb-6">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search item name or SKU" class="w-full" />
          </IconField>

          <Select
            v-model="filters.stock_status"
            :options="stockStatuses"
            optionLabel="label"
            optionValue="value"
            placeholder="Stock Status"
            class="w-full"
            showClear
          />

          <Button icon="pi pi-search" label="Search" @click="loadItems" />
          <Button icon="pi pi-filter-slash" label="Reset" severity="secondary" @click="resetFilters" />
        </div>
      </template>
    </Card>

    <!-- Items Table -->
    <Card>
      <template #content>
        <DataTable
          :value="items"
          :loading="loading"
          paginator
          :rows="filters.per_page"
          :totalRecords="totalRecords"
          :lazy="true"
          @page="onPage"
          dataKey="id"
          :rowsPerPageOptions="[15, 25, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          class="p-datatable-sm"
          stripedRows
        >
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No inventory records found</p>
            </div>
          </template>

          <Column field="product.sku" header="SKU" style="width: 12%">
            <template #body="{ data }">
              {{ data.product?.sku || 'N/A' }}
            </template>
          </Column>

          <Column field="product.product_name" header="Item Name" style="width: 20%">
            <template #body="{ data }">
              {{ data.product?.product_name || 'N/A' }}
            </template>
          </Column>

          <Column field="branch.name" header="Branch" style="width: 15%">
            <template #body="{ data }">
              {{ data.branch?.name || 'N/A' }}
            </template>
          </Column>

          <Column field="quantity_on_hand" header="On Hand" style="width: 12%">
            <template #body="{ data }">
              <span class="font-medium">{{ data.quantity_on_hand }}</span>
            </template>
          </Column>

          <Column field="reorder_point" header="Reorder Level" style="width: 12%">
            <template #body="{ data }">
              {{ data.reorder_point }}
            </template>
          </Column>

          <Column field="status" header="Status" style="width: 15%">
            <template #body="{ data }">
              <Tag :value="getStockLabel(data)" :severity="getStockSeverity(data)" />
            </template>
          </Column>

          <Column header="Actions" style="width: 14%">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-eye" size="small" text severity="info" @click="viewDetails(data)" v-tooltip="'View details'" />
                <Button icon="pi pi-pencil" size="small" text severity="warning" @click="editItem(data)" v-tooltip="'Adjust stock'" />
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
import { useToast } from 'primevue/usetoast'
import axios from 'axios'

const loading = ref(false)
const items = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()

const filters = reactive({
  search: '',
  stock_status: null as string | null,
  page: 1,
  per_page: 15
})

const stockStatuses = [
  { label: 'In Stock', value: 'in_stock' },
  { label: 'Low Stock', value: 'low_stock' },
  { label: 'Out of Stock', value: 'out_of_stock' }
]

const loadItems = async () => {
  loading.value = true
  try {
    const params: any = {
      page: filters.page,
      per_page: filters.per_page
    }

    if (filters.search) params.search = filters.search
    if (filters.stock_status) params.stock_status = filters.stock_status

    const response = await axios.get('/api/inventory/items', { params })

    if (response.data?.data) {
      items.value = response.data.data
      totalRecords.value = response.data.total || items.value.length
    } else {
      items.value = []
      totalRecords.value = 0
    }
  } catch (error: any) {
    console.error('Failed to load inventory', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load inventory',
      life: 3000
    })
    items.value = []
    totalRecords.value = 0
  } finally {
    loading.value = false
  }
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadItems()
}

const resetFilters = () => {
  filters.search = ''
  filters.stock_status = null
  filters.page = 1
  filters.per_page = 15
  loadItems()
}

const getStockLabel = (item: any) => {
  const qty = item.quantity_on_hand || 0
  const reorder = item.reorder_point || 0

  if (qty <= 0) return 'Out of Stock'
  if (qty <= reorder) return 'Low Stock'
  return 'In Stock'
}

const getStockSeverity = (item: any) => {
  const qty = item.quantity_on_hand || 0
  const reorder = item.reorder_point || 0

  if (qty <= 0) return 'danger'
  if (qty <= reorder) return 'warning'
  return 'success'
}

const viewDetails = (item: any) => {
  console.log('View item details:', item)
  // Can navigate to detail view if needed
}

const editItem = (item: any) => {
  console.log('Edit item:', item)
  router.push({ name: 'inventory.items.edit', params: { id: item.id } })
}

onMounted(() => {
  loadItems()
})
</script>