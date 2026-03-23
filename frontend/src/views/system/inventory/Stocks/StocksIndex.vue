<template>
  <div class="min-h-screen p-4">
    <div class="mb-4 flex justify-between items-center">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Inventory</h1>
      </div>
      <Button
        v-if="canCreateItems"
        label="Add Item"
        icon="pi pi-plus"
        severity="success"
        size="small"
        @click="router.push({ name: 'inventory.items.create' })"
      />
    </div>

    <!-- Filters -->
    <Card class="mb-4">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
          <IconField>
            <InputIcon class="pi pi-search" />
            <InputText v-model="filters.search" placeholder="Search item name or SKU" class="w-full" size="small"/>
          </IconField>

          <Select
            v-model="filters.stock_status"
            :options="stockStatuses"
            optionLabel="label"
            optionValue="value"
            placeholder="Stock Status" size="small"
            class="w-full"
            showClear
          />

          <Select
            v-model="filters.product_type"
            :options="productTypeOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Product Type" size="small"
            class="w-full"
            showClear
          />

          <Button icon="pi pi-filter-slash" label="Reset" severity="secondary" outlined size="small" @click="resetFilters" />
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
          @sort="onSort"
          :sortField="sortField"
          :sortOrder="sortOrder"
          dataKey="id"
          :rowsPerPageOptions="[15, 25, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageSelect"
          class="p-datatable-sm"
          stripedRows
        >
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-400"></i>
              <p class="text-gray-600 mt-2">No inventory records found</p>
            </div>
          </template>

          <Column field="created_at" header="Date" sortable style="width: 12%">
            <template #body="{ data }">
              {{ formatDate(data.created_at) }}
            </template>
          </Column>

          <Column field="product.sku" header="SKU" style="width: 12%">
            <template #body="{ data }">
              {{ data.variation?.variation_sku || data.product?.sku || 'N/A' }}
            </template>
          </Column>

          <Column field="product.product_name" header="Item Name" style="width: 20%">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium text-gray-900">{{ data.product?.product_name || 'N/A' }}</div>
                <div class="text-xs text-gray-500">
                  {{ data.variation?.variation_name || (data.variation_id ? 'Variant' : 'Standard') }}
                </div>
              </div>
            </template>
          </Column>

          <Column header="Variant" style="width: 16%">
            <template #body="{ data }">
              <div v-if="data.variation_id" class="text-xs text-gray-700">
                <div>{{ data.variation?.color || '-' }} / {{ data.variation?.size || '-' }}</div>
                <div class="text-gray-500">{{ data.variation?.material || '-' }}</div>
              </div>
              <span v-else class="text-xs text-gray-500">Standard</span>
            </template>
          </Column>

          <Column field="quantity_on_hand" header="On Hand" style="width: 10%">
            <template #body="{ data }">
              <span class="font-medium">{{ data.quantity_on_hand }}</span>
            </template>
          </Column>

          <Column field="quantity_available" header="Available" style="width: 10%">
            <template #body="{ data }">
              {{ data.quantity_available }}
            </template>
          </Column>

          <Column field="reorder_point" header="Reorder Point" style="width: 10%">
            <template #body="{ data }">
              {{ data.reorder_point }}
            </template>
          </Column>

          <!-- <Column header="Location" style="width: 15%">
            <template #body="{ data }">
              <span class="text-sm text-gray-600">
                {{ [data.warehouse_section, data.aisle, data.rack, data.shelf].filter(Boolean).join('-') || 'N/A' }}
              </span>
            </template>
          </Column> -->

          <Column field="stock_status" header="Status" style="width: 13%">
            <template #body="{ data }">
              <Tag :value="getStockLabel(data.stock_status)" :severity="getStockSeverity(data.stock_status)" />
            </template>
          </Column>

          <Column header="Actions" style="width: 15%">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button
                  v-if="canUpdateItems"
                  icon="pi pi-pencil"
                  size="small"
                  text
                  severity="warning"
                  @click="router.push({ name: 'inventory.items.edit', params: { id: data.id } })"
                  v-tooltip="'Edit item'"
                />
                <Button
                  icon="pi pi-plus-circle"
                  size="small"
                  text
                  severity="info"
                  @click="createPurchaseRequisition(data)"
                  v-tooltip="'Create Purchase Requisition'"
                  v-if="['low_stock', 'out_of_stock'].includes(data.stock_status)"
                />
                <Button
                  v-if="canDeleteItems"
                  icon="pi pi-trash"
                  size="small"
                  text
                  severity="danger"
                  @click="confirmDelete(data)"
                  v-tooltip="'Delete item'"
                />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <ConfirmDialog />
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useConfirm } from 'primevue/useconfirm'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'
import { useAuthStore } from '../../../../stores/auth'

const loading = ref(false)
const items = ref<any[]>([])
const totalRecords = ref(0)
const toast = useToast()
const confirm = useConfirm()
const router = useRouter()
const authStore = useAuthStore()

const canCreateItems = computed(() => authStore.hasPermission('inventory.items.create'))
const canUpdateItems = computed(() => authStore.hasPermission('inventory.items.update'))
const canDeleteItems = computed(() => authStore.hasPermission('inventory.items.delete'))

const filters = reactive({
  search: '',
  stock_status: null as string | null,
  product_type: null as string | null,
  page: 1,
  per_page: 15
})
const sortField = ref('created_at')
const sortOrder = ref(-1)
let searchTimer: ReturnType<typeof setTimeout> | null = null

const stockStatuses = [
  { label: 'In Stock', value: 'in_stock' },
  { label: 'Low Stock', value: 'low_stock' },
  { label: 'Out of Stock', value: 'out_of_stock' },
  { label: 'Overstock', value: 'overstock' }
]
const productTypeOptions = [
  { label: 'Finished Good', value: 'finished_good' },
  { label: 'Raw Material', value: 'raw_material' }
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
    if (filters.product_type) params.product_type = filters.product_type
    params.sort_by = sortField.value
    params.sort_order = sortOrder.value === 1 ? 'asc' : 'desc'

    const response = await inventoryService.getInventoryItems(params)

    if (response?.data) {
      items.value = response.data
      totalRecords.value = response.meta?.total ?? items.value.length
    } else {
      items.value = []
      totalRecords.value = 0
    }
  } catch (error: any) {
    console.error('Failed to load inventory', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to load inventory',
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

const onSort = (event: any) => {
  sortField.value = event.sortField || 'created_at'
  sortOrder.value = event.sortOrder || -1
  filters.page = 1
  loadItems()
}

const resetFilters = () => {
  filters.search = ''
  filters.stock_status = null
  filters.product_type = null
  filters.page = 1
  filters.per_page = 15
  sortField.value = 'created_at'
  sortOrder.value = -1
  loadItems()
}

const formatDate = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
}

const getStockLabel = (status: string) => {
  const labels: Record<string, string> = {
    in_stock: 'In Stock',
    low_stock: 'Low Stock',
    out_of_stock: 'Out of Stock',
    overstock: 'Overstock'
  }
  return labels[status] ?? status
}

const getStockSeverity = (status: string) => {
  const severities: Record<string, string> = {
    in_stock: 'success',
    low_stock: 'warning',
    out_of_stock: 'danger',
    overstock: 'info'
  }
  return severities[status] ?? 'secondary'
}

const confirmDelete = (item: any) => {
  confirm.require({
    message: `Are you sure you want to delete the inventory record for "${item.product?.product_name || 'this item'}"?`,
    header: 'Confirm Delete',
    icon: 'pi pi-exclamation-triangle',
    accept: () => deleteItem(item.id)
  })
}

const deleteItem = async (id: number) => {
  if (!canDeleteItems.value) {
    toast.add({
      severity: 'warn',
      summary: 'Unauthorized',
      detail: 'You do not have permission to delete inventory records.',
      life: 3000
    })
    return
  }

  try {
    await inventoryService.deleteInventoryItem(id)
    toast.add({
      severity: 'success',
      summary: 'Deleted',
      detail: 'Inventory record deleted successfully',
      life: 3000
    })
    loadItems()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'Failed to delete inventory record',
      life: 3000
    })
  }
}

const createPurchaseRequisition = (item: any) => {
  router.push({
    name: 'procurement.purchase-requisitions.create',
    query: {
      branch_inventory_id: item.id,
      branch_id: item.branch_id,
      product_id: item.product_id,
      variation_id: item.variation_id || undefined,
    }
  })
}

onMounted(() => {
  loadItems()
})

watch([() => filters.stock_status, () => filters.product_type, () => filters.per_page], () => {
  filters.page = 1
  loadItems()
})

watch(() => filters.search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    filters.page = 1
    loadItems()
  }, 350)
})
</script>
