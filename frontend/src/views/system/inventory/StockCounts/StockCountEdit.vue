<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <div class="max-w-6xl mx-auto">
      <div class="mb-6">
        <div class="flex items-center gap-4">
          <Button
            icon="pi pi-arrow-left"
            severity="secondary"
            text
            @click="goBack"
            v-tooltip.top="'Back to Stock Count'"
          />
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Edit Stock Count</h1>
            <p class="text-gray-600 mt-1">Update stock count information and item counts</p>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center h-64">
        <ProgressSpinner />
      </div>

      <!-- Form -->
      <Card v-else-if="stockCount">
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Reference Number <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.reference_number"
                    placeholder="Enter reference number"
                    class="w-full"
                    :class="{ 'p-invalid': errors.reference_number }"
                    required
                    :disabled="stockCount?.status === 'completed'"
                  />
                  <small v-if="errors.reference_number" class="p-error">{{ errors.reference_number[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Count Date <span class="text-red-500">*</span>
                  </label>
                  <Calendar
                    v-model="form.count_date"
                    placeholder="Select count date"
                    class="w-full"
                    showIcon
                    dateFormat="yy-mm-dd"
                    :class="{ 'p-invalid': errors.count_date }"
                    required
                    :disabled="stockCount?.status === 'completed'"
                  />
                  <small v-if="errors.count_date" class="p-error">{{ errors.count_date[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Count Type <span class="text-red-500">*</span>
                  </label>
                  <Select
                    v-model="form.count_type"
                    :options="countTypeOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select count type"
                    class="w-full"
                    :class="{ 'p-invalid': errors.count_type }"
                    required
                    :disabled="stockCount?.status === 'completed'"
                  />
                  <small v-if="errors.count_type" class="p-error">{{ errors.count_type[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Count Method
                  </label>
                  <Select
                    v-model="form.count_method"
                    :options="countMethodOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select count method"
                    class="w-full"
                    :disabled="stockCount?.status === 'completed'"
                  />
                </div>
              </div>
            </div>

            <!-- Count Items -->
            <div>
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Count Items</h3>
                <div class="text-sm text-gray-600">
                  Total Items: {{ form.items.length }} |
                  Counted: {{ itemsCounted }} |
                  Discrepancies: <span class="text-red-600 font-medium">{{ discrepanciesCount }}</span>
                </div>
              </div>

              <!-- Items Table -->
              <DataTable
                :value="form.items"
                class="p-datatable-sm mb-4"
                tableStyle="min-width: 50rem"
                editMode="row"
                :disabled="stockCount?.status === 'completed'"
                @row-edit-save="onRowEditSave"
                @row-edit-cancel="onRowEditCancel"
              >
                <Column field="product.name" header="Product" style="min-width: 200px">
                  <template #body="slotProps">
                    <div class="text-sm">
                      <div class="font-medium">{{ slotProps.data.product?.name }}</div>
                      <div class="text-gray-500">{{ slotProps.data.product?.code }}</div>
                    </div>
                  </template>
                </Column>
                <Column field="expected_quantity" header="Expected Qty" style="width: 120px">
                  <template #body="slotProps">
                    {{ slotProps.data.expected_quantity || 0 }}
                  </template>
                </Column>
                <Column field="counted_quantity" header="Counted Qty" style="width: 120px">
                  <template #body="slotProps">
                    <span v-if="slotProps.data.counted_quantity !== null" class="font-medium">
                      {{ slotProps.data.counted_quantity }}
                    </span>
                    <span v-else class="text-gray-400 italic">Not counted</span>
                  </template>
                  <template #editor="slotProps">
                    <InputNumber
                      v-model="slotProps.data.counted_quantity"
                      :min="0"
                      class="w-full"
                      placeholder="Enter count"
                    />
                  </template>
                </Column>
                <Column field="discrepancy" header="Discrepancy" style="width: 120px">
                  <template #body="slotProps">
                    <span :class="getDiscrepancyClass(slotProps.data)">
                      {{ calculateDiscrepancy(slotProps.data) }}
                    </span>
                  </template>
                </Column>
                <Column field="variance_percentage" header="Variance %" style="width: 120px">
                  <template #body="slotProps">
                    <span :class="getVarianceClass(slotProps.data)">
                      {{ calculateVariance(slotProps.data) }}%
                    </span>
                  </template>
                </Column>
                <Column field="notes" header="Notes" style="min-width: 150px">
                  <template #body="slotProps">
                    {{ slotProps.data.notes }}
                  </template>
                  <template #editor="slotProps">
                    <InputText
                      v-model="slotProps.data.notes"
                      class="w-full"
                      placeholder="Enter notes"
                    />
                  </template>
                </Column>
                <Column header="Actions" style="width: 100px" v-if="stockCount?.status !== 'completed'">
                  <template #body="slotProps">
                    <Button
                      icon="pi pi-pencil"
                      severity="warning"
                      outlined
                      @click="editRow(slotProps.data)"
                      class="mr-2"
                    />
                  </Button>
                </Column>
              </DataTable>

              <!-- Summary -->
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                  <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ form.items.length }}</div>
                    <div class="text-sm text-gray-600">Total Items</div>
                  </div>
                  <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ itemsCounted }}</div>
                    <div class="text-sm text-gray-600">Items Counted</div>
                  </div>
                  <div class="text-center">
                    <div class="text-2xl font-bold text-red-600">{{ discrepanciesCount }}</div>
                    <div class="text-sm text-gray-600">Discrepancies</div>
                  </div>
                  <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ accuracyRate }}%</div>
                    <div class="text-sm text-gray-600">Accuracy Rate</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Information -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Additional Information</h3>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Notes
                </label>
                <Textarea
                  v-model="form.notes"
                  placeholder="Enter count notes"
                  class="w-full"
                  rows="4"
                  :class="{ 'p-invalid': errors.notes }"
                  :disabled="stockCount?.status === 'completed'"
                />
                <small v-if="errors.notes" class="p-error">{{ errors.notes[0] }}</small>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-6 border-t">
              <Button
                label="Cancel"
                severity="secondary"
                @click="goBack"
                type="button"
              />
              <Button
                label="Update Stock Count"
                type="submit"
                :loading="saving"
                :disabled="stockCount?.status === 'completed'"
                class="bg-blue-600 hover:bg-blue-700"
              />
            </div>
          </form>
        </template>
      </Card>

      <!-- Not Found -->
      <div v-else class="text-center py-12">
        <i class="pi pi-exclamation-triangle text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Stock Count Not Found</h3>
        <p class="text-gray-600 mb-4">The stock count you're looking for doesn't exist or has been deleted.</p>
        <Button label="Back to Stock Counts" @click="goBack" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter, useRoute } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const saving = ref(false)
const errors = ref<any>({})
const stockCount = ref<any>(null)
const editingRow = ref<any>(null)
const toast = useToast()
const router = useRouter()
const route = useRoute()

const form = reactive({
  reference_number: '',
  count_date: null as Date | null,
  count_type: '',
  count_method: '',
  items: [] as any[],
  notes: ''
})

const countTypeOptions = [
  { label: 'Full Count', value: 'full' },
  { label: 'Partial Count', value: 'partial' },
  { label: 'Cycle Count', value: 'cycle' },
  { label: 'Spot Check', value: 'spot_check' }
]

const countMethodOptions = [
  { label: 'Manual Count', value: 'manual' },
  { label: 'Barcode Scanner', value: 'barcode' },
  { label: 'RFID', value: 'rfid' },
  { label: 'Automated', value: 'automated' }
]

const itemsCounted = computed(() => {
  return form.items.filter(item => item.counted_quantity !== null && item.counted_quantity !== undefined).length
})

const discrepanciesCount = computed(() => {
  return form.items.filter(item => calculateDiscrepancy(item) !== 0).length
})

const accuracyRate = computed(() => {
  if (form.items.length === 0) return 0
  const accurateItems = form.items.filter(item => calculateDiscrepancy(item) === 0).length
  return Math.round((accurateItems / form.items.length) * 100)
})

const loadStockCount = async () => {
  loading.value = true
  try {
    const response = await inventoryService.getStockCount(route.params.id as string)

    if (response.success) {
      stockCount.value = response.data
      populateForm()
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load stock count',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load stock count',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const populateForm = () => {
  if (!stockCount.value) return

  form.reference_number = stockCount.value.reference_number || ''
  form.count_date = stockCount.value.count_date ? new Date(stockCount.value.count_date) : null
  form.count_type = stockCount.value.count_type || ''
  form.count_method = stockCount.value.count_method || ''
  form.notes = stockCount.value.notes || ''

  // Populate items
  form.items = stockCount.value.items?.map((item: any) => ({
    id: item.id,
    product_id: item.product_id,
    product: item.product,
    expected_quantity: item.expected_quantity,
    counted_quantity: item.counted_quantity,
    notes: item.notes
  })) || []
}

const editRow = (item: any) => {
  editingRow.value = { ...item }
}

const onRowEditSave = (event: any) => {
  const { newData, index } = event
  form.items[index] = newData
  editingRow.value = null
}

const onRowEditCancel = () => {
  editingRow.value = null
}

const goBack = () => {
  router.push({ name: 'inventory.stock-counts.detail', params: { id: route.params.id } })
}

const submitForm = async () => {
  saving.value = true
  errors.value = {}

  // Prepare form data
  const submitData = {
    ...form,
    count_date: form.count_date ? form.count_date.toISOString().split('T')[0] : null,
    items: form.items.map(item => ({
      id: item.id,
      product_id: item.product_id,
      expected_quantity: item.expected_quantity,
      counted_quantity: item.counted_quantity,
      notes: item.notes
    }))
  }

  try {
    const response = await inventoryService.updateStockCount(route.params.id as string, submitData)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock count updated successfully',
        life: 3000
      })
      router.push({ name: 'inventory.stock-counts.detail', params: { id: route.params.id } })
    } else {
      if (response.errors) {
        errors.value = response.errors
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: response.message || 'Failed to update stock count',
          life: 3000
        })
      }
    }
  } catch (error: any) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: error.response?.data?.message || 'Failed to update stock count',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

const calculateDiscrepancy = (item: any) => {
  if (item.counted_quantity === null || item.counted_quantity === undefined) return 0
  return item.counted_quantity - (item.expected_quantity || 0)
}

const calculateVariance = (item: any) => {
  if (!item.expected_quantity) return 0
  const discrepancy = calculateDiscrepancy(item)
  return Math.round((discrepancy / item.expected_quantity) * 100)
}

const getDiscrepancyClass = (item: any) => {
  const discrepancy = calculateDiscrepancy(item)
  if (discrepancy === 0) return 'text-green-600'
  return 'text-red-600 font-medium'
}

const getVarianceClass = (item: any) => {
  const variance = calculateVariance(item)
  if (variance === 0) return 'text-green-600'
  return variance > 0 ? 'text-blue-600' : 'text-red-600'
}

onMounted(() => {
  loadStockCount()
})
</script>