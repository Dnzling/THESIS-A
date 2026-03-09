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
            v-tooltip.top="'Back to Stock Returns'"
          />
          <div>
            <h1 class="text-3xl font-bold text-gray-800">Create Stock Return</h1>
            <p class="text-gray-600 mt-1">Process a new stock return transaction</p>
          </div>
        </div>
      </div>

      <Card>
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-6">
            <!-- Basic Information -->
            <div>
              <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                  />
                  <small v-if="errors.reference_number" class="p-error">{{ errors.reference_number[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Return Date <span class="text-red-500">*</span>
                  </label>
                  <Calendar
                    v-model="form.return_date"
                    placeholder="Select return date"
                    class="w-full"
                    showIcon
                    dateFormat="yy-mm-dd"
                    :class="{ 'p-invalid': errors.return_date }"
                    required
                  />
                  <small v-if="errors.return_date" class="p-error">{{ errors.return_date[0] }}</small>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Return Reason <span class="text-red-500">*</span>
                  </label>
                  <Select
                    v-model="form.return_reason"
                    :options="returnReasonOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select reason"
                    class="w-full"
                    :class="{ 'p-invalid': errors.return_reason }"
                    required
                  />
                  <small v-if="errors.return_reason" class="p-error">{{ errors.return_reason[0] }}</small>
                </div>
              </div>
            </div>

            <!-- Return Items -->
            <div>
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Return Items</h3>
                <Button
                  label="Add Item"
                  icon="pi pi-plus"
                  severity="success"
                  @click="addItem"
                  type="button"
                />
              </div>

              <!-- Items Table -->
              <DataTable
                :value="form.items"
                class="p-datatable-sm mb-4"
                tableStyle="min-width: 50rem"
                editMode="row"
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
                  <template #editor="slotProps">
                    <Select
                      v-model="slotProps.data.product_id"
                      :options="products"
                      optionLabel="name"
                      optionValue="id"
                      placeholder="Select product"
                      class="w-full"
                      :loading="productsLoading"
                      @change="onProductChange(slotProps.data)"
                    />
                  </template>
                </Column>
                <Column field="quantity" header="Quantity" style="width: 120px">
                  <template #body="slotProps">
                    {{ slotProps.data.quantity }}
                  </template>
                  <template #editor="slotProps">
                    <InputNumber
                      v-model="slotProps.data.quantity"
                      :min="1"
                      class="w-full"
                      @input="calculateItemTotal(slotProps.data)"
                    />
                  </template>
                </Column>
                <Column field="unit_cost" header="Unit Cost" style="width: 120px">
                  <template #body="slotProps">
                    ${{ slotProps.data.unit_cost?.toFixed(2) }}
                  </template>
                  <template #editor="slotProps">
                    <InputNumber
                      v-model="slotProps.data.unit_cost"
                      :min="0"
                      mode="currency"
                      currency="USD"
                      locale="en-US"
                      class="w-full"
                      @input="calculateItemTotal(slotProps.data)"
                    />
                  </template>
                </Column>
                <Column field="total_cost" header="Total Cost" style="width: 120px">
                  <template #body="slotProps">
                    ${{ slotProps.data.total_cost?.toFixed(2) }}
                  </template>
                </Column>
                <Column field="condition" header="Condition" style="width: 140px">
                  <template #body="slotProps">
                    <Tag
                      :value="slotProps.data.condition"
                      :severity="getConditionSeverity(slotProps.data.condition)"
                      class="capitalize"
                    />
                  </template>
                  <template #editor="slotProps">
                    <Select
                      v-model="slotProps.data.condition"
                      :options="conditionOptions"
                      optionLabel="label"
                      optionValue="value"
                      class="w-full"
                    />
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
                    />
                  </template>
                </Column>
                <Column header="Actions" style="width: 100px">
                  <template #body="slotProps">
                    <Button
                      icon="pi pi-pencil"
                      severity="warning"
                      outlined
                      @click="editRow(slotProps.data)"
                      class="mr-2"
                    />
                    <Button
                      icon="pi pi-trash"
                      severity="danger"
                      outlined
                      @click="removeItem(slotProps.index)"
                    />
                  </template>
                </Column>
              </DataTable>

              <!-- Summary -->
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ totalQuantity }}</div>
                    <div class="text-sm text-gray-600">Total Quantity</div>
                  </div>
                  <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">${{ totalValue.toFixed(2) }}</div>
                    <div class="text-sm text-gray-600">Total Value</div>
                  </div>
                  <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ form.items.length }}</div>
                    <div class="text-sm text-gray-600">Items</div>
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
                  placeholder="Enter return notes"
                  class="w-full"
                  rows="4"
                  :class="{ 'p-invalid': errors.notes }"
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
                label="Create Return"
                type="submit"
                :loading="loading"
                :disabled="form.items.length === 0"
                class="bg-blue-600 hover:bg-blue-700"
              />
            </div>
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import inventoryService from '../../../../services/inventory.service'

const loading = ref(false)
const productsLoading = ref(false)
const errors = ref<any>({})
const products = ref<any[]>([])
const editingRow = ref<any>(null)
const toast = useToast()
const router = useRouter()

const form = reactive({
  reference_number: '',
  return_date: new Date(),
  return_reason: '',
  items: [] as any[],
  notes: ''
})

const returnReasonOptions = [
  { label: 'Damaged', value: 'damaged' },
  { label: 'Defective', value: 'defective' },
  { label: 'Wrong Item', value: 'wrong_item' },
  { label: 'Overstock', value: 'overstock' },
  { label: 'Expired', value: 'expired' },
  { label: 'Customer Return', value: 'customer_return' },
  { label: 'Quality Issue', value: 'quality_issue' },
  { label: 'Other', value: 'other' }
]

const conditionOptions = [
  { label: 'New', value: 'new' },
  { label: 'Good', value: 'good' },
  { label: 'Fair', value: 'fair' },
  { label: 'Poor', value: 'poor' },
  { label: 'Damaged', value: 'damaged' }
]

const totalQuantity = computed(() => {
  return form.items.reduce((sum, item) => sum + (item.quantity || 0), 0)
})

const totalValue = computed(() => {
  return form.items.reduce((sum, item) => sum + (item.total_cost || 0), 0)
})

const loadProducts = async () => {
  productsLoading.value = true
  try {
    const response = await inventoryService.getProducts({ per_page: 1000 })

    if (response.success) {
      products.value = response.data || []
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load products',
      life: 3000
    })
  } finally {
    productsLoading.value = false
  }
}

const addItem = () => {
  form.items.push({
    product_id: null,
    product: null,
    quantity: 1,
    unit_cost: 0,
    total_cost: 0,
    condition: 'good',
    notes: ''
  })
}

const removeItem = (index: number) => {
  form.items.splice(index, 1)
}

const onProductChange = (item: any) => {
  const product = products.value.find(p => p.id === item.product_id)
  if (product) {
    item.product = product
    item.unit_cost = product.cost_price || 0
    calculateItemTotal(item)
  }
}

const calculateItemTotal = (item: any) => {
  item.total_cost = (item.quantity || 0) * (item.unit_cost || 0)
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
  router.push({ name: 'inventory.stock-returns.index' })
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  // Prepare form data
  const submitData = {
    ...form,
    return_date: form.return_date ? form.return_date.toISOString().split('T')[0] : null,
    items: form.items.map(item => ({
      product_id: item.product_id,
      quantity: item.quantity,
      unit_cost: item.unit_cost,
      condition: item.condition,
      notes: item.notes
    }))
  }

  try {
    const response = await inventoryService.createStockReturn(submitData)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock return created successfully',
        life: 3000
      })
      router.push({ name: 'inventory.stock-returns.detail', params: { id: response.data.id } })
    } else {
      if (response.errors) {
        errors.value = response.errors
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: response.message || 'Failed to create stock return',
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
        detail: error.response?.data?.message || 'Failed to create stock return',
        life: 3000
      })
    }
  } finally {
    loading.value = false
  }
}

const getConditionSeverity = (condition: string) => {
  switch (condition) {
    case 'new': return 'success'
    case 'good': return 'info'
    case 'fair': return 'warning'
    case 'poor': return 'danger'
    case 'damaged': return 'danger'
    default: return 'secondary'
  }
}

onMounted(() => {
  loadProducts()
})
</script>