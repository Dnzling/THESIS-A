<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Reorder Suggestions</h2>
        <p class="text-sm text-gray-500 mt-1">Products below reorder point requiring new orders</p>
      </div>
      <Button
        icon="pi pi-refresh"
        rounded
        text
        @click="loadReorderSuggestions"
        :loading="loading"
      />
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Total Products</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ suggestions.length }}</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Critical Stock</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ criticalCount }}</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Est. Total Cost</p>
            <p class="text-2xl font-bold text-green-600 mt-2">₱ {{ formatNumber(totalCost) }}</p>
          </div>
        </template>
      </Card>

      <Card>
        <template #content>
          <div class="text-center">
            <p class="text-gray-600 text-sm">Suggested POs</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ suggestedPOCount }}</p>
          </div>
        </template>
      </Card>
    </div>

    <!-- Filters -->
    <Card>
      <template #content>
        <div class="flex gap-4 items-end">
          <div class="flex-1">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Category</label>
            <Select
              v-model="filterCategory"
              :options="categories"
              optionLabel="name"
              optionValue="id"
              placeholder="All Categories"
              class="w-full"
              @change="applyFilters"
            />
          </div>
          <div class="flex-1">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Filter by Supplier</label>
            <Select
              v-model="filterSupplier"
              :options="suppliers"
              optionLabel="supplier_name"
              optionValue="id"
              placeholder="All Suppliers"
              class="w-full"
              @change="applyFilters"
            />
          </div>
          <Button
            icon="pi pi-filter-slash"
            label="Clear Filters"
            severity="secondary"
            @click="clearFilters"
          />
        </div>
      </template>
    </Card>

    <!-- Suggestions Table -->
    <Card>
      <template #content>
        <DataTable
          :value="filteredSuggestions"
          :loading="loading"
          paginator
          :rows="10"
          responsiveLayout="scroll"
          class="p-datatable-sm"
        >
          <Column field="product_name" header="Product" style="width: 20%"></Column>
          <Column field="current_stock" header="Current Stock" style="width: 12%">
            <template #body="{ data }">
              <Badge
                :value="`${data.current_stock} units`"
                :severity="data.current_stock <= (data.reorder_point * 0.5) ? 'danger' : 'warning'"
              />
            </template>
          </Column>
          <Column field="reorder_point" header="Reorder Point" style="width: 12%">
            <template #body="{ data }">
              {{ data.reorder_point }} units
            </template>
          </Column>
          <Column field="suggested_qty" header="Suggested Qty" style="width: 12%">
            <template #body="{ data }">
              <span class="font-semibold text-blue-600">{{ data.suggested_qty }}</span>
            </template>
          </Column>
          <Column field="unit_price" header="Unit Price" style="width: 12%">
            <template #body="{ data }">
              ₱ {{ formatNumber(data.unit_price) }}
            </template>
          </Column>
          <Column header="Est. Cost" style="width: 12%">
            <template #body="{ data }">
              <span class="font-bold text-green-600">₱ {{ formatNumber(data.suggested_qty * data.unit_price) }}</span>
            </template>
          </Column>
          <Column field="best_supplier" header="Best Supplier" style="width: 15%">
            <template #body="{ data }">
              <div>
                <p class="font-semibold">{{ data.best_supplier }}</p>
                <p class="text-xs text-gray-500">Lead: {{ data.lead_time }} days</p>
              </div>
            </template>
          </Column>
          <Column header="Action" style="width: 15%" :frozen="true" alignFrozen="right">
            <template #body="{ data }">
              <Button
                icon="pi pi-plus"
                label="Create PO"
                class="p-button-sm"
                @click="createPO(data)"
              />
            </template>
          </Column>
        </DataTable>
      </template>
    </Card>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'
import inventoryService from '../../../../services/inventory.service'

const router = useRouter()
const toast = useToast()

// State
const suggestions = ref<any[]>([])
const categories = ref<any[]>([])
const suppliers = ref<any[]>([])
const loading = ref(false)
const filterCategory = ref(null)
const filterSupplier = ref(null)

// Computed
const criticalCount = computed(() => {
  return suggestions.value.filter(s => s.current_stock <= (s.reorder_point * 0.5)).length
})

const totalCost = computed(() => {
  return suggestions.value.reduce((sum, s) => sum + (s.suggested_qty * s.unit_price), 0)
})

const suggestedPOCount = computed(() => {
  return Math.ceil(suggestions.value.length / 3)
})

const filteredSuggestions = computed(() => {
  return suggestions.value.filter(s => {
    if (filterCategory.value && s.category_id !== filterCategory.value) return false
    if (filterSupplier.value && s.supplier_id !== filterSupplier.value) return false
    return true
  })
})

// Methods
async function loadReorderSuggestions() {
  loading.value = true
  try {
    const response = await procurementService.getReorderSuggestions()
    suggestions.value = response.data?.data || []
    
    // Load categories from inventory service
    const categoriesRes = await inventoryService.getCategories()
    categories.value = categoriesRes.data?.data || []
    
    // Load suppliers
    const suppliersRes = await procurementService.getSuppliers({ per_page: 100 })
    suppliers.value = suppliersRes.data?.data || []
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to load reorder suggestions',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  // Filters are applied via computed property
}

function clearFilters() {
  filterCategory.value = null
  filterSupplier.value = null
}

async function createPO(product: any) {
  try {
    // Create PO with suggested quantity
    const today = new Date()
    const orderDate = today.toISOString().substring(0, 10)
    const expectedDelivery = new Date(today.getTime() + (product.lead_time || 0) * 24 * 60 * 60 * 1000)
    const deliveryDate = expectedDelivery.toISOString().substring(0, 10)

    const poData: any = {
      supplier_id: product.supplier_id,
      branch_id: 1, // Default to main branch
      order_date: orderDate,
      expected_delivery_date: deliveryDate,
      line_items: [
        {
          product_id: product.product_id,
          quantity: product.suggested_qty,
          unit_price: product.unit_price,
        }
      ],
      status: 'draft' as const,
    }

    const response = await procurementService.createPurchaseOrder(poData)
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `PO created for ${product.product_name}`,
      life: 3000,
    })

    router.push({
      name: 'procurement.purchase-orders.detail',
      params: { id: response.data.data.id },
    })
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Failed to create PO',
      life: 3000,
    })
  }
}

function formatNumber(value: number): string {
  return new Intl.NumberFormat('en-PH').format(value)
}

onMounted(() => {
  loadReorderSuggestions()
})
</script>
