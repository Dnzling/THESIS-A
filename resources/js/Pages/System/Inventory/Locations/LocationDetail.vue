<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-7xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <Button
              icon="pi pi-arrow-left"
              severity="secondary"
              text
              @click="goBack"
              v-tooltip.top="'Back to Locations'"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-800">{{ location?.name || 'Location Details' }}</h1>
              <p class="text-gray-600 mt-1">Location information and inventory</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Button
              label="Edit"
              icon="pi pi-pencil"
              severity="warning"
              @click="editLocation"
              v-tooltip.top="'Edit Location'"
            />
            <Button
              label="Delete"
              icon="pi pi-trash"
              severity="danger"
              @click="confirmDelete"
              v-tooltip.top="'Delete Location'"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Location Details -->
      <div v-else-if="location" class="space-y-6">
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-tag text-blue-600 text-3xl mb-2"></i>
                <div class="text-2xl font-bold text-gray-800">{{ location.code }}</div>
                <div class="text-sm text-gray-600">Location Code</div>
              </div>
            </template>
          </Card>

          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-building text-green-600 text-3xl mb-2"></i>
                <div class="text-2xl font-bold text-gray-800 capitalize">{{ location.type }}</div>
                <div class="text-sm text-gray-600">Type</div>
              </div>
            </template>
          </Card>

          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-box text-purple-600 text-3xl mb-2"></i>
                <div class="text-2xl font-bold text-gray-800">{{ location.products_count || 0 }}</div>
                <div class="text-sm text-gray-600">Products</div>
              </div>
            </template>
          </Card>

          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-check-circle text-orange-600 text-3xl mb-2"></i>
                <div class="text-2xl font-bold text-gray-800 capitalize">{{ location.status }}</div>
                <div class="text-sm text-gray-600">Status</div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Location Information -->
          <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <Card>
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-info-circle text-blue-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Basic Information</h3>
                </div>
              </template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location Name</label>
                    <p class="text-gray-900">{{ location.name }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location Code</label>
                    <p class="text-gray-900">{{ location.code }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Warehouse</label>
                    <p class="text-gray-900">{{ location.warehouse?.name }} ({{ location.warehouse?.code }})</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <p class="text-gray-900 capitalize">{{ location.type }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <Tag
                      :value="location.status"
                      :severity="getStatusSeverity(location.status)"
                      class="capitalize"
                    />
                  </div>
                  <div v-if="location.capacity">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                    <p class="text-gray-900">{{ location.capacity.toLocaleString() }} units</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                    <p class="text-gray-900">{{ formatDate(location.created_at) }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Updated</label>
                    <p class="text-gray-900">{{ formatDate(location.updated_at) }}</p>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Location Details -->
            <Card>
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-map text-green-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Location Details</h3>
                </div>
              </template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                  <div v-if="location.aisle">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Aisle</label>
                    <p class="text-gray-900">{{ location.aisle }}</p>
                  </div>
                  <div v-if="location.rack">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Rack</label>
                    <p class="text-gray-900">{{ location.rack }}</p>
                  </div>
                  <div v-if="location.shelf">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Shelf</label>
                    <p class="text-gray-900">{{ location.shelf }}</p>
                  </div>
                  <div v-if="location.bin">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bin</label>
                    <p class="text-gray-900">{{ location.bin }}</p>
                  </div>
                  <div v-if="location.level">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                    <p class="text-gray-900">{{ location.level }}</p>
                  </div>
                  <div v-if="location.position">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                    <p class="text-gray-900">{{ location.position }}</p>
                  </div>
                </div>
                <div v-if="!location.aisle && !location.rack && !location.shelf && !location.bin && !location.level && !location.position" class="text-center py-4">
                  <p class="text-gray-500">No location details specified</p>
                </div>
              </template>
            </Card>

            <!-- Dimensions -->
            <Card v-if="location.length || location.width || location.height || location.weight_limit">
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-arrows-alt text-purple-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Dimensions</h3>
                </div>
              </template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                  <div v-if="location.length">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Length</label>
                    <p class="text-gray-900">{{ location.length }} cm</p>
                  </div>
                  <div v-if="location.width">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Width</label>
                    <p class="text-gray-900">{{ location.width }} cm</p>
                  </div>
                  <div v-if="location.height">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Height</label>
                    <p class="text-gray-900">{{ location.height }} cm</p>
                  </div>
                  <div v-if="location.weight_limit">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Weight Limit</label>
                    <p class="text-gray-900">{{ location.weight_limit }} kg</p>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Description -->
            <Card v-if="location.description">
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-file-text text-orange-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Description</h3>
                </div>
              </template>
              <template #content>
                <p class="text-gray-900 whitespace-pre-wrap">{{ location.description }}</p>
              </template>
            </Card>
          </div>

          <!-- Products Sidebar -->
          <div>
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-box text-indigo-600"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Products</h3>
                  </div>
                  <Button
                    label="View All"
                    size="small"
                    severity="secondary"
                    @click="viewProducts"
                  />
                </div>
              </template>
              <template #content>
                <div v-if="productsLoading" class="flex justify-center py-4">
                  <ProgressSpinner style="width: 30px; height: 30px" />
                </div>
                <div v-else-if="products.length === 0" class="text-center py-8">
                  <i class="pi pi-box text-gray-400 text-3xl mb-2"></i>
                  <p class="text-gray-500">No products in this location</p>
                  <Button
                    label="Add Product"
                    size="small"
                    severity="secondary"
                    @click="addProduct"
                    class="mt-2"
                  />
                </div>
                <div v-else class="space-y-3">
                  <div
                    v-for="product in products.slice(0, 5)"
                    :key="product.id"
                    class="border rounded-lg p-3 hover:bg-gray-50 cursor-pointer"
                    @click="viewProduct(product)"
                  >
                    <div class="flex items-center justify-between">
                      <div>
                        <div class="font-medium text-gray-900">{{ product.name }}</div>
                        <div class="text-sm text-gray-600">{{ product.code }}</div>
                        <div class="text-sm text-gray-500">Qty: {{ product.quantity || 0 }}</div>
                      </div>
                      <Tag
                        :value="product.status"
                        :severity="getStatusSeverity(product.status)"
                        class="capitalize"
                      />
                    </div>
                  </div>
                  <div v-if="products.length > 5" class="text-center pt-2">
                    <Button
                      label="View All Products"
                      size="small"
                      text
                      @click="viewProducts"
                    />
                  </div>
                </div>
              </template>
            </Card>
          </div>
        </div>
      </div>

      <!-- Not Found -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Location Not Found</h3>
        <p class="text-gray-600 mb-4">The location you're looking for doesn't exist or has been deleted.</p>
        <Button label="Back to Locations" @click="goBack" />
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Dialog -->
  <Dialog
    v-model:visible="deleteDialog"
    modal
    header="Confirm Delete"
    :style="{ width: '450px' }"
  >
    <div class="flex items-center gap-3">
      <i class="pi pi-exclamation-triangle text-red-500 text-2xl"></i>
      <div>
        <p class="font-medium">Are you sure you want to delete this location?</p>
        <p class="text-sm text-gray-600 mt-1">
          Location: <strong>{{ location?.name }}</strong>
        </p>
        <p v-if="location?.products_count > 0" class="text-sm text-red-600 mt-2">
          Warning: This location contains {{ location.products_count }} products.
          Deleting this location may affect inventory tracking.
        </p>
      </div>
    </div>
    <template #footer>
      <Button
        label="Cancel"
        severity="secondary"
        @click="deleteDialog = false"
      />
      <Button
        label="Delete"
        severity="danger"
        @click="deleteLocation"
        :loading="deleteLoading"
      />
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const deleteLoading = ref(false)
const deleteDialog = ref(false)
const productsLoading = ref(false)
const location = ref<any>(null)
const products = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const loadLocation = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getLocation(route.params.id as string)

    if (response.success) {
      location.value = response.data
      loadProducts()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load location',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load location',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadProducts = async () => {
  productsLoading.value = true
  try {
    const response = await inventoryService.getProducts({
      location_id: route.params.id,
      per_page: 5
    })

    if (response.success) {
      products.value = response.data || []
    }
  } catch (error: any) {
    // Silently handle product loading errors
  } finally {
    productsLoading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.locations.index' })
}

const editLocation = () => {
  router.push({ name: 'inventory.locations.edit', params: { id: route.params.id } })
}

const confirmDelete = () => {
  deleteDialog.value = true
}

const deleteLocation = async () => {
  deleteLoading.value = true
  try {
    const response = await inventoryService.deleteLocation(route.params.id as string)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Location deleted successfully',
        life: 3000
      })
      router.push({ name: 'inventory.locations.index' })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete location',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete location',
      life: 3000
    })
  } finally {
    deleteLoading.value = false
  }
}

const viewProducts = () => {
  router.push({ name: 'inventory.products.index', query: { location_id: route.params.id } })
}

const addProduct = () => {
  router.push({ name: 'inventory.products.create', query: { location_id: route.params.id } })
}

const viewProduct = (product: any) => {
  router.push({ name: 'inventory.products.detail', params: { id: product.id } })
}

const getStatusSeverity = (status: string) => {
  switch (status) {
    case 'active': return 'success'
    case 'inactive': return 'warning'
    default: return 'secondary'
  }
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadLocation()
})
</script>