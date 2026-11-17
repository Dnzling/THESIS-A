<template>
  <div class="min-h-screen space-y-4 p-4">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text rounded aria-label="Go back"
          @click="router.push({ name: 'merchandising.products' })" />
        <div>
          <h1 class="text-lg font-bold text-gray-900">Archived Products</h1>
          <p class="text-xs text-gray-500">Products removed from the active catalog. Restore a product to make it available again.</p>
        </div>
      </div>
      <Button label="Product Catalog" icon="pi pi-list" severity="secondary" outlined size="small"
        @click="router.push({ name: 'merchandising.products' })" />
    </header>

    <Card class="border border-gray-200 shadow-sm">
      <template #content>
        <div class="mb-4 flex max-w-xl flex-col gap-2 sm:flex-row">
          <InputText v-model="search" placeholder="Search product name or SKU" class="w-full text-sm" size="small"
            @keydown.enter="loadArchivedProducts(1)" />
          <Button label="Search" icon="pi pi-search" severity="warn" size="small" @click="loadArchivedProducts(1)" />
        </div>

        <div v-if="loading" class="space-y-3">
          <Skeleton v-for="row in 6" :key="row" height="2rem" />
        </div>
        <DataTable v-else :value="products" lazy paginator :rows="perPage" :totalRecords="totalRecords"
          :first="(page - 1) * perPage" dataKey="id" @page="onPage" :rowsPerPageOptions="[15, 25, 50]"
          currentPageReportTemplate="Showing {first} to {last} of {totalRecords}"
          paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
          class="p-datatable-sm text-xs" responsive-layout="scroll">
          <template #empty>
            <div class="py-8 text-center text-sm text-gray-500">No archived products found.</div>
          </template>
          <Column field="product_name" header="Product" sortable style="min-width: 220px">
            <template #body="{ data }">
              <p class="font-medium text-gray-900">{{ data.product_name }}</p>
              <p class="font-mono text-[11px] text-gray-500">{{ data.sku || 'No SKU' }}</p>
            </template>
          </Column>
          <Column field="product_type" header="Type" style="min-width: 120px">
            <template #body="{ data }">{{ typeLabel(data.product_type) }}</template>
          </Column>
          <Column header="Category" style="min-width: 160px">
            <template #body="{ data }">{{ data.category?.category_name || '—' }}</template>
          </Column>
          <Column field="deleted_at" header="Archived" style="min-width: 170px">
            <template #body="{ data }">{{ formatDate(data.deleted_at) }}</template>
          </Column>
          <Column header="Actions" style="width: 15rem">
            <template #body="{ data }">
              <div v-if="canRestore" class="flex flex-wrap gap-2">
                <Button label="Restore" icon="pi pi-refresh" severity="warn" outlined rounded size="small"
                  :loading="restoringId === data.id" @click="restoreProduct(data)" />
                <Button label="Delete" icon="pi pi-trash" severity="danger" outlined rounded size="small"
                  :loading="deletingId === data.id" @click="requestPermanentDelete(data)" />
              </div>
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Dialog v-model:visible="deleteDialogVisible" modal header="Permanently delete product?" :style="{ width: 'min(92vw, 28rem)' }">
      <p class="text-sm text-gray-700">
        Delete {{ productToDelete?.product_name || 'this archived product' }} permanently? This cannot be undone.
        Products with stock or existing business history cannot be deleted.
      </p>
      <template #footer>
        <Button label="Cancel" severity="secondary" outlined @click="deleteDialogVisible = false" />
        <Button label="Delete permanently" icon="pi pi-trash" severity="warn" :loading="deletingId !== null" @click="permanentlyDelete" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import merchandisingService from '../../../../services/merchandising.service'
import { useAuthStore } from '../../../../stores/auth'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()
const canRestore = authStore.hasPermission('inventory.products.manage') || authStore.hasPermission('merchandising.products.delete')
const products = ref<any[]>([])
const loading = ref(true)
const restoringId = ref<number | null>(null)
const deletingId = ref<number | null>(null)
const deleteDialogVisible = ref(false)
const productToDelete = ref<any | null>(null)
const search = ref('')
const page = ref(1)
const perPage = ref(15)
const totalRecords = ref(0)

const loadArchivedProducts = async (requestedPage = page.value) => {
  loading.value = true
  page.value = requestedPage
  try {
    const response = await merchandisingService.getArchivedProducts({
      page: page.value,
      per_page: perPage.value,
      search: search.value.trim() || undefined,
    })
    const pagination = response?.data || {}
    products.value = Array.isArray(pagination.data) ? pagination.data : []
    totalRecords.value = Number(pagination.total || 0)
  } catch (error: any) {
    products.value = []
    totalRecords.value = 0
    toast.add({ severity: 'error', summary: 'Unable to load archive', detail: error?.response?.data?.message || 'Please try again.', life: 4000 })
  } finally {
    loading.value = false
  }
}

const onPage = (event: { page: number; rows: number }) => {
  perPage.value = event.rows
  loadArchivedProducts(event.page + 1)
}

const restoreProduct = async (product: any) => {
  restoringId.value = Number(product.id)
  try {
    await merchandisingService.restoreProduct(Number(product.id))
    toast.add({ severity: 'success', summary: 'Product restored', detail: `${product.product_name} is back in the active catalog.`, life: 3000 })
    await loadArchivedProducts()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to restore product', detail: error?.response?.data?.message || 'Please try again.', life: 4000 })
  } finally {
    restoringId.value = null
  }
}

const requestPermanentDelete = (product: any) => {
  productToDelete.value = product
  deleteDialogVisible.value = true
}

const permanentlyDelete = async () => {
  if (!productToDelete.value) return
  deletingId.value = Number(productToDelete.value.id)
  try {
    await merchandisingService.permanentlyDeleteArchivedProduct(deletingId.value)
    toast.add({ severity: 'success', summary: 'Product deleted', detail: 'The archived product was permanently deleted.', life: 3000 })
    deleteDialogVisible.value = false
    await loadArchivedProducts()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to delete product', detail: error?.response?.data?.message || 'Please try again.', life: 5000 })
  } finally {
    deletingId.value = null
  }
}

const typeLabel = (type?: string) => ({
  finished_good: 'Product',
  supply: 'Supply',
  raw_material: 'Material',
}[String(type || '')] || String(type || 'Other').replace(/[_-]+/g, ' '))

const formatDate = (date?: string) => date
  ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(date))
  : '—'

onMounted(() => loadArchivedProducts(1))
</script>
