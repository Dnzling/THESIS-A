<template>
  <div class="min-h-screen p-4">
    <div class="mx-auto space-y-4">
    <!-- Header -->
    <div class="flex items-center gap-4 justify-between">
      <div>
        <h1 class="text-lg font-bold text-gray-900">Products</h1>
        <p class="text-gray-500 mt-1">Manage products with supplier pricing and stock levels</p>
      </div>
      <Button
        label="New Purchase Order"
        icon="pi pi-plus"
        size="small"
        @click="goToCreatePO"
      />
    </div>


    <!-- Data Table -->
    <Card class="border border-gray-200 shadow-sm">
      <template #header>
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-5 m-4 mt-6">
          <!-- Search -->
          <span class="p-input-icon-left w-full">
            <InputText
              v-model="filters.search"
              placeholder="Search SKU, name..."
              class="w-full text-sm"
              size="small"
              @keyup.enter="loadProducts"
            />
          </span>

          <!-- Category Filter -->
          <div>
            <label class="block text-sm font-semibold mb-2">Category</label>
            <Select
              v-model="filters.category_id"
              :options="categories"
              option-label="category_name"
              option-value="id"
              placeholder="All Categories"
              class="w-full text-sm" fluid
              size="small"
              @change="loadProducts"
            />
          </div>

          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-semibold mb-2">Stock Status</label>
            <Select
              v-model="filters.status"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              placeholder="All Status"
              class="w-full text-sm" fluid
              size="small"
              @change="loadProducts"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold mb-2">Product Type</label>
            <Select
              v-model="filters.product_type"
              :options="productTypeOptions"
              option-label="label"
              option-value="value"
              placeholder="All Types"
              class="w-full text-sm"
              size="small"
              fluid
              showClear
              @change="loadProducts"
            />
          </div>

          <!-- Sort By -->
          <div>
            <label class="block text-sm font-semibold mb-2">Sort By</label>
            <Select
              v-model="filters.sort_by"
              :options="sortOptions"
              placeholder="Sort by..."
              class="w-full text-sm" fluid
              size="small"
              @change="loadProducts"
            />
          </div>
        </div>
      </template>
      <template #content>
        <DataTable
          v-if="!loading"
          :value="products"
          :lazy="true"
          :paginator="true"
          :rows="15"
          :totalRecords="totalRecords"
          :loading="loading"
          @page="onPageChange"
          responsive-layout="scroll"
          class="p-datatable-sm text-xs"
        >
          <template #header>
            <div class="flex justify-between items-center">
              <span>Total Products: {{ totalRecords }}</span>
              <Button
                icon="pi pi-refresh"
                class="p-button-rounded p-button-text"
                @click="loadProducts"
              />
            </div>
          </template>

          <Column header="Branch" style="min-width: 150px">
            <template #body="{ data }">
              <span class="text-gray-700">
                {{ data.branch_name || data.inventory?.[0]?.branch?.name || data.branch?.name || '—' }}
              </span>
            </template>
          </Column>

          <!-- SKU Column -->
          <Column field="sku" header="SKU" style="width: 10%" sortable>
            <template #body="{ data }">
              <RouterLink :to="`/procurement/products/${data.id}`" class="text-blue-600 hover:underline">
                {{ data.sku }}
              </RouterLink>
            </template>
          </Column>

          <!-- Product Name Column -->
          <Column field="product_name" header="Product Name" style="width: 25%" sortable>
            <template #body="{ data }">
              <div class="font-semibold text-gray-800">{{ data.product_name }}</div>
              <p class="text-xs text-gray-500">{{ data.category?.category_name }}</p>
            </template>
          </Column>

          <!-- Suppliers Column -->
          <Column v-if="false" header="Suppliers" style="width: 20%">
            <template #body="{ data }">
              <div class="space-y-2">
                <div
                  v-for="supplier in supplierList(data).slice(0, 2)"
                  :key="supplier.id || supplier.supplier_id || Math.random()"
                  class="flex items-start gap-2"
                >
                  <Badge
                    :value="`★ ${safeRating(supplier)}`"
                    :severity="safeRating(supplier) >= 4 ? 'success' : safeRating(supplier) >= 3 ? 'warning' : 'danger'"
                    class="min-w-max"
                  />
                  <div>
                    <RouterLink
                      :to="`/procurement/suppliers/${supplier.id || supplier.supplier_id}`"
                      class="text-blue-600 hover:underline text-sm font-semibold"
                    >
                      {{ supplier.supplier_name || supplier.name || 'Supplier' }}
                    </RouterLink>
                    <p v-if="firstPrice(supplier) !== null" class="text-sm text-green-600 font-bold">
                      ₱ {{ formatNumber(firstPrice(supplier)) }}
                    </p>
                  </div>
                </div>
                <Button
                  v-if="supplierList(data).length > 2"
                  :label="`+${supplierList(data).length - 2} more`"
                  severity="info"
                  text
                  size="small"
                  @click="showSupplierDialog(data)"
                />
              </div>
            </template>
          </Column>

          <!-- Stock Column -->
          <Column v-if="false" header="Stock" style="width: 15%">
            <template #body="{ data }">
              <div class="space-y-1 text-sm">
                <div>
                  <span class="font-semibold">On Hand:</span>
                  {{ data.current_stock }}
                </div>
                <div>
                  <span class="font-semibold">On Order:</span>
                  {{ data.quantity_on_orders }}
                </div>
                <div :class="data.current_stock < data.reorder_point ? 'text-red-600 font-bold' : ''">
                  <span class="font-semibold">Reorder Point:</span>
                  {{ data.reorder_point }}
                </div>
              </div>
            </template>
          </Column>

          <Column header="Quantity on Hand" style="min-width: 150px">
            <template #body="{ data }">
              <span class="font-semibold text-gray-900">{{ formatNumber(data.quantity_on_hand ?? data.current_stock) }}</span>
            </template>
          </Column>

          <!-- Status Column -->
          <Column header="Status" style="width: 10%">
            <template #body="{ data }">
              <Badge
                v-if="data.current_stock === 0"
                value="Out of Stock"
                severity="danger"
              />
              <Badge
                v-else-if="data.current_stock < data.reorder_point"
                value="Low Stock"
                severity="warning"
              />
              <Badge
                v-else
                value="In Stock"
                severity="success"
              />
            </template>
          </Column>

          <!-- Actions Column -->
          <Column header="Actions" style="width: 15%">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button
                  icon="pi pi-shopping-cart"
                  severity="success"
                  text
                  rounded
                  v-if="false"
                  @click="quickOrderProduct(data)"
                  v-tooltip="'Quick Order'"
                />
                <Button
                  icon="pi pi-eye"
                  text
                  rounded
                  @click="viewProduct(data)"
                  v-tooltip="'View Details'"
                />
                <Button
                  icon="pi pi-history"
                  text
                  rounded
                  v-if="false"
                  @click="viewPurchaseHistory(data)"
                  v-tooltip="'Purchase History'"
                />
              </div>
            </template>
          </Column>

          <!-- Empty State -->
          <template #empty>
            <div class="text-center py-8">
              <i class="pi pi-inbox text-4xl text-gray-300" />
              <p class="text-gray-500 mt-2">No products found</p>
            </div>
          </template>
        </DataTable>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-8">
          <ProgressSpinner/>
        </div>
      </template>
    </Card>

    <!-- Supplier Dialog -->
    <Dialog
      v-model:visible="showSuppliersModal"
      header="All Suppliers"
      :modal="true"
      style="width: 90vw; max-width: 600px"
    >
      <DataTable :value="selectedProductSuppliers">
        <Column field="supplier_name" header="Supplier" />
        <Column field="rating" header="Rating">
          <template #body="{ data }">
            <Rating v-model="data.rating" :readonly="true" />
          </template>
        </Column>
        <Column field="current_price" header="Price">
          <template #body="{ data }">
            <span class="font-bold text-green-600">₱ {{ formatNumber(data.current_price) }}</span>
          </template>
        </Column>
        <Column field="lead_time_days" header="Lead Time">
          <template #body="{ data }">
            {{ data.lead_time_days }} days
          </template>
        </Column>
      </DataTable>
    </Dialog>

    </div>

    <!-- Toast -->
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '@/services/procurement.service'

const router = useRouter()
const toast = useToast()

// State
const products = ref([])
const loading = ref(false)
const totalRecords = ref(0)
const currentPage = ref(0)
const categories = ref([])
const branches = ref<any[]>([])
const showBranchColumn = computed(() =>
  branches.value.length > 1 || products.value.some((product: any) => Number(product?.branch_count || 0) > 1)
)
const showSuppliersModal = ref(false)
const showProductModal = ref(false)
const selectedProduct = ref(null)
const selectedProductSuppliers = ref([])

const filters = ref({
  search: '',
  category_id: null,
  status: '',
  product_type: null,
  sort_by: 'created_at',
  page: 1,
  per_page: 15,
})

const statusOptions = ref([
  { label: 'In Stock', value: 'in_stock' },
  { label: 'Low Stock', value: 'low_stock' },
  { label: 'Out of Stock', value: 'out_of_stock' },
])

const sortOptions = ref([
  { label: 'Name (A-Z)', value: 'product_name' },
  { label: 'SKU', value: 'sku' },
  { label: 'Price (Low to High)', value: 'base_price' },
  { label: 'Newest', value: 'created_at' },
])
const productTypeOptions = ref([
  { label: 'Product', value: 'finished_good' },
  { label: 'Supply', value: 'supply' },
])

// Methods
async function loadProducts() {
  loading.value = true
  try {
    const response = await procurementService.getProcurementProducts({
      ...filters.value,
      page: currentPage.value + 1,
    })

    const payload = response.data ?? {}
    const rows = Array.isArray(payload.data)
      ? payload.data
      : Array.isArray(payload)
        ? payload
        : Array.isArray(payload.items)
          ? payload.items
          : Array.isArray(payload.data?.data)
            ? payload.data.data
            : []

    products.value = rows
    totalRecords.value = payload.total ?? payload.meta?.total ?? rows.length
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load products',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function onPageChange(event: any) {
  currentPage.value = event.page
  loadProducts()
}

function showSupplierDialog(product: any) {
  selectedProduct.value = product
  selectedProductSuppliers.value = Array.isArray(product.suppliers) ? product.suppliers : []
  showSuppliersModal.value = true
}

function viewProduct(product: any) {
  router.push({
    name: 'procurement.products.detail',
    params: { id: product.id },
    query: product.branch_id ? { branch_id: product.branch_id } : undefined,
  })
}

async function viewPurchaseHistory(product: any) {
  try {
    const response = await procurementService.getProductHistory(product.id)
    // Would typically open a modal with history details
    toast.add({
      severity: 'info',
      summary: 'Purchase History',
      detail: `${response.data.length} purchases recorded`,
      life: 3000,
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load purchase history',
      life: 3000,
    })
  }
}

function quickOrderProduct(product: any) {
  router.push({
    name: 'procurement.purchase-orders.create',
    query: { product_id: product.id },
  })
}

function goToCreatePO() {
  router.push({ name: 'procurement.purchase-orders.create' })
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH').format(Number(value || 0))
}

async function loadBranches() {
  try {
    const response = await procurementService.getBranches({ active_only: true })
    const data = response?.data?.data || response?.data || []
    branches.value = Array.isArray(data) ? data : []
  } catch {
    branches.value = []
  }
}

function supplierList(row: any) {
  return Array.isArray(row?.suppliers) ? row.suppliers : []
}

function safeRating(supplier: any): number {
  const rating = Number(supplier?.rating ?? 0)
  return Number.isFinite(rating) ? rating : 0
}

function firstPrice(supplier: any): number | null {
  if (Array.isArray(supplier?.priceHistory) && supplier.priceHistory.length > 0) {
    const price = supplier.priceHistory[0]?.unit_price
    const num = Number(price)
    return Number.isFinite(num) ? num : null
  }
  if (supplier?.current_price !== undefined) {
    const num = Number(supplier.current_price)
    return Number.isFinite(num) ? num : null
  }
  return null
}

// Lifecycle
onMounted(async () => {
  await loadBranches()
  loadProducts()
})
</script>
