<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4 justify-between">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Procurement Products</h1>
        <p class="text-gray-500 mt-1">Manage products with supplier pricing and stock levels</p>
      </div>
      <Button
        label="New Purchase Order"
        icon="pi pi-plus"
        class="p-button-lg"
        @click="goToCreatePO"
      />
    </div>

    <!-- Filters & Search -->
    <Card>
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Search -->
          <span class="p-input-icon-left w-full">
            <i class="pi pi-search" />
            <InputText
              v-model="filters.search"
              placeholder="Search SKU, name..."
              class="w-full"
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
              class="w-full" fluid
              @change="loadProducts"
            />
          </div>

          <!-- Status Filter -->
          <div>
            <label class="block text-sm font-semibold mb-2">Stock Status</label>
            <Select
              v-model="filters.status"
              :options="statusOptions"
              placeholder="All Status"
              class="w-full" fluid
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
              class="w-full" fluid
              @change="loadProducts"
            />
          </div>
        </div>
      </template>
    </Card>

    <!-- Data Table -->
    <Card>
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
          class="p-datatable-sm"
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
          <Column header="Suppliers" style="width: 20%">
            <template #body="{ data }">
              <div class="space-y-2">
                <div
                  v-for="supplier in data.suppliers.slice(0, 2)"
                  :key="supplier.id"
                  class="flex items-start gap-2"
                >
                  <Badge
                    :value="`★ ${supplier.rating}`"
                    :severity="supplier.rating >= 4 ? 'success' : supplier.rating >= 3 ? 'warning' : 'danger'"
                    class="min-w-max"
                  />
                  <div>
                    <RouterLink
                      :to="`/procurement/suppliers/${supplier.id}`"
                      class="text-blue-600 hover:underline text-sm font-semibold"
                    >
                      {{ supplier.supplier_name }}
                    </RouterLink>
                    <p v-if="supplier.priceHistory?.[0]" class="text-sm text-green-600 font-bold">
                      ₱ {{ formatNumber(supplier.priceHistory[0].unit_price) }}
                    </p>
                  </div>
                </div>
                <Button
                  v-if="data.suppliers.length > 2"
                  :label="`+${data.suppliers.length - 2} more`"
                  severity="info"
                  text
                  size="small"
                  @click="showSupplierDialog(data)"
                />
              </div>
            </template>
          </Column>

          <!-- Stock Column -->
          <Column header="Stock" style="width: 15%">
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
                  severity="success"
                  rounded
                  @click="quickOrderProduct(data)"
                  v-tooltip="'Quick Order'"
                />
                <Button
                  icon="pi pi-eye"
                  text
                  rounded
                  @click="viewProductDetail(data)"
                  v-tooltip="'View Details'"
                />
                <Button
                  icon="pi pi-history"
                  text
                  rounded
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

    <!-- Product Detail Dialog -->
    <Dialog
      v-model:visible="showProductModal"
      header="Product Detail"
      :modal="true"
      style="width: 90vw; max-width: 800px"
    >
      <div v-if="selectedProduct" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="font-semibold text-gray-600">SKU</label>
            <p>{{ selectedProduct.sku }}</p>
          </div>
          <div>
            <label class="font-semibold text-gray-600">Category</label>
            <p>{{ selectedProduct.category?.category_name }}</p>
          </div>
        </div>
        <div>
          <label class="font-semibold text-gray-600">Description</label>
          <p>{{ selectedProduct.description || 'N/A' }}</p>
        </div>
      </div>
    </Dialog>

    <!-- Toast -->
    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
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
const showSuppliersModal = ref(false)
const showProductModal = ref(false)
const selectedProduct = ref(null)
const selectedProductSuppliers = ref([])

const filters = ref({
  search: '',
  category_id: null,
  status: '',
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

// Methods
async function loadProducts() {
  loading.value = true
  try {
    const response = await procurementService.getProcurementProducts({
      ...filters.value,
      page: currentPage.value + 1,
    })

    products.value = response.data.data || []
    totalRecords.value = response.data.total || 0
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
  selectedProductSuppliers.value = product.suppliers
  showSuppliersModal.value = true
}

function viewProductDetail(product: any) {
  selectedProduct.value = product
  showProductModal.value = true
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
  return new Intl.NumberFormat('en-PH').format(value)
}

// Lifecycle
onMounted(() => {
  loadProducts()
})
</script>
