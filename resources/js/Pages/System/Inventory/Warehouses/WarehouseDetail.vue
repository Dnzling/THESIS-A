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
              v-tooltip.top="'Back to Warehouses'"
            />
            <div>
              <h1 class="text-3xl font-bold text-gray-800">{{ warehouse?.name || 'Warehouse Details' }}</h1>
              <p class="text-gray-600 mt-1">Warehouse information and locations</p>
            </div>
          </div>
          <div class="flex gap-2">
            <Button
              label="Edit"
              icon="pi pi-pencil"
              severity="warning"
              @click="editWarehouse"
              v-tooltip.top="'Edit Warehouse'"
            />
            <Button
              label="Delete"
              icon="pi pi-trash"
              severity="danger"
              @click="confirmDelete"
              v-tooltip.top="'Delete Warehouse'"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Warehouse Details -->
      <div v-else-if="warehouse" class="space-y-6">
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-building text-3xl text-blue-600 mb-2"></i>
                <div class="text-2xl font-bold text-gray-800">{{ warehouse.code }}</div>
                <div class="text-sm text-gray-600">Warehouse Code</div>
              </div>
            </template>
          </Card>

          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-tag text-3xl text-green-600 mb-2"></i>
                <div class="text-2xl font-bold text-gray-800 capitalize">{{ warehouse.type }}</div>
                <div class="text-sm text-gray-600">Type</div>
              </div>
            </template>
          </Card>

          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-map-marker text-3xl text-purple-600 mb-2"></i>
                <div class="text-2xl font-bold text-gray-800">{{ warehouse.locations_count || 0 }}</div>
                <div class="text-sm text-gray-600">Locations</div>
              </div>
            </template>
          </Card>

          <Card>
            <template #content>
              <div class="text-center">
                <i class="pi pi-check-circle text-3xl text-orange-600 mb-2"></i>
                <div class="text-2xl font-bold text-gray-800 capitalize">{{ warehouse.status }}</div>
                <div class="text-sm text-gray-600">Status</div>
              </div>
            </template>
          </Card>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Warehouse Information -->
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Warehouse Name</label>
                    <p class="text-gray-900">{{ warehouse.name }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Warehouse Code</label>
                    <p class="text-gray-900">{{ warehouse.code }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <p class="text-gray-900 capitalize">{{ warehouse.type }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <Tag
                      :value="warehouse.status"
                      :severity="getStatusSeverity(warehouse.status)"
                      class="capitalize"
                    />
                  </div>
                  <div v-if="warehouse.capacity">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Capacity</label>
                    <p class="text-gray-900">{{ warehouse.capacity.toLocaleString() }} units</p>
                  </div>
                  <div v-if="warehouse.manager">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Manager</label>
                    <p class="text-gray-900">{{ warehouse.manager }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Created</label>
                    <p class="text-gray-900">{{ formatDate(warehouse.created_at) }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Updated</label>
                    <p class="text-gray-900">{{ formatDate(warehouse.updated_at) }}</p>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Address Information -->
            <Card>
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-map text-green-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Address Information</h3>
                </div>
              </template>
              <template #content>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Address</label>
                    <p class="text-gray-900">{{ warehouse.address }}</p>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                      <p class="text-gray-900">{{ warehouse.city }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">State/Province</label>
                      <p class="text-gray-900">{{ warehouse.state }}</p>
                    </div>
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                      <p class="text-gray-900">{{ warehouse.postal_code }}</p>
                    </div>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <p class="text-gray-900">{{ warehouse.country }}</p>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Contact Information -->
            <Card v-if="warehouse.phone || warehouse.email">
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-phone text-purple-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Contact Information</h3>
                </div>
              </template>
              <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div v-if="warehouse.phone">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <p class="text-gray-900">{{ warehouse.phone }}</p>
                  </div>
                  <div v-if="warehouse.email">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <p class="text-gray-900">{{ warehouse.email }}</p>
                  </div>
                </div>
              </template>
            </Card>

            <!-- Description -->
            <Card v-if="warehouse.description">
              <template #header>
                <div class="flex items-center gap-2">
                  <i class="pi pi-file-text text-orange-600"></i>
                  <h3 class="text-lg font-semibold text-gray-800">Description</h3>
                </div>
              </template>
              <template #content>
                <p class="text-gray-900 whitespace-pre-wrap">{{ warehouse.description }}</p>
              </template>
            </Card>
          </div>

          <!-- Locations Sidebar -->
          <div>
            <Card>
              <template #header>
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-map-marker text-indigo-600"></i>
                    <h3 class="text-lg font-semibold text-gray-800">Locations</h3>
                  </div>
                  <Button
                    label="View All"
                    size="small"
                    severity="secondary"
                    @click="viewLocations"
                  />
                </div>
              </template>
              <template #content>
                <div v-if="locationsLoading" class="flex justify-center py-4">
                  <ProgressSpinner style="width: 30px; height: 30px" />
                </div>
                <div v-else-if="locations.length === 0" class="text-center py-8">
                  <i class="pi pi-map-marker text-gray-400 text-3xl mb-2"></i>
                  <p class="text-gray-500">No locations found</p>
                  <Button
                    label="Add Location"
                    size="small"
                    severity="secondary"
                    @click="createLocation"
                    class="mt-2"
                  />
                </div>
                <div v-else class="space-y-3">
                  <div
                    v-for="location in locations.slice(0, 5)"
                    :key="location.id"
                    class="border rounded-lg p-3 hover:bg-gray-50 cursor-pointer"
                    @click="viewLocation(location)"
                  >
                    <div class="flex items-center justify-between">
                      <div>
                        <div class="font-medium text-gray-900">{{ location.name }}</div>
                        <div class="text-sm text-gray-600">{{ location.code }}</div>
                      </div>
                      <Tag
                        :value="location.status"
                        :severity="getStatusSeverity(location.status)"
                        class="capitalize"
                      />
                    </div>
                  </div>
                  <div v-if="locations.length > 5" class="text-center pt-2">
                    <Button
                      label="View All Locations"
                      size="small"
                      text
                      @click="viewLocations"
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
        <h3 class="text-lg font-medium text-gray-900 mb-2">Warehouse Not Found</h3>
        <p class="text-gray-600 mb-4">The warehouse you're looking for doesn't exist or has been deleted.</p>
        <Button label="Back to Warehouses" @click="goBack" />
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
        <p class="font-medium">Are you sure you want to delete this warehouse?</p>
        <p class="text-sm text-gray-600 mt-1">
          Warehouse: <strong>{{ warehouse?.name }}</strong>
        </p>
        <p v-if="warehouse?.locations_count > 0" class="text-sm text-red-600 mt-2">
          Warning: This warehouse has {{ warehouse.locations_count }} locations.
          Deleting this warehouse may affect inventory tracking.
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
        @click="deleteWarehouse"
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
const locationsLoading = ref(false)
const warehouse = ref<any>(null)
const locations = ref<any[]>([])
const toast = useToast()
const router = useRouter()
const route = useRoute()

const loadWarehouse = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getWarehouse(route.params.id as string)

    if (response.success) {
      warehouse.value = response.data
      loadLocations()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load warehouse',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load warehouse',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const loadLocations = async () => {
  locationsLoading.value = true
  try {
    const response = await inventoryService.getLocations({
      warehouse_id: route.params.id,
      per_page: 5
    })

    if (response.success) {
      locations.value = response.data || []
    }
  } catch (error: any) {
    // Silently handle location loading errors
  } finally {
    locationsLoading.value = false
  }
}

const goBack = () => {
  router.push({ name: 'inventory.warehouses.index' })
}

const editWarehouse = () => {
  router.push({ name: 'inventory.warehouses.edit', params: { id: route.params.id } })
}

const confirmDelete = () => {
  deleteDialog.value = true
}

const deleteWarehouse = async () => {
  deleteLoading.value = true
  try {
    const response = await inventoryService.deleteWarehouse(route.params.id as string)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Warehouse deleted successfully',
        life: 3000
      })
      router.push({ name: 'inventory.warehouses.index' })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to delete warehouse',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to delete warehouse',
      life: 3000
    })
  } finally {
    deleteLoading.value = false
  }
}

const viewLocations = () => {
  router.push({ name: 'inventory.locations.index', query: { warehouse_id: route.params.id } })
}

const createLocation = () => {
  router.push({ name: 'inventory.locations.create', query: { warehouse_id: route.params.id } })
}

const viewLocation = (location: any) => {
  router.push({ name: 'inventory.locations.detail', params: { id: location.id } })
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
  loadWarehouse()
})
</script>