<template>
  <div class="min-h-screen p-3 md:p-4">
    <ConfirmDialog />
    <div class="max-w-5xl mx-auto">
      <div class="mb-4">
        <div class="flex items-center gap-4">
          <Button
            icon="pi pi-arrow-left"
            severity="secondary"
            text
            @click="goBack"
            v-tooltip.top="'Back to Stock Counts'"
          />
          <div>
            <h1 class="text-lg md:text-2xl font-bold text-gray-800">Create Stock Count</h1>
          </div>
        </div>
      </div>

      <Card>
        <template #content>
          <form @submit.prevent="submitForm" class="space-y-4">
            <!-- Basic Information -->
            <div>
              <h3 class="text-base font-semibold text-gray-800 mb-3">Basic Information</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1.5">
                    Reference Number <span class="text-red-500">*</span>
                  </label>
                  <InputText
                    v-model="form.reference_number"
                    placeholder="Auto-generated"
                    class="w-full"
                    :class="{ 'p-invalid': errors.reference_number }"
                    readonly
                  />
                  <small class="text-gray-500">Auto-generated from current date and time.</small>
                  <small v-if="errors.reference_number" class="p-error">{{ errors.reference_number[0] }}</small>
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1.5">Branch</label>
                  <InputText
                    :value="displayBranchLabel"
                    class="w-full"
                    readonly
                  />
                  <small class="text-gray-500">Auto-using your assigned branch inventory.</small>
                </div>
              </div>
            </div>

            <!-- Count Type -->
            <div>
              <h3 class="text-base font-semibold text-gray-800 mb-3">Count Configuration</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1.5">
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
                  />
                  <small v-if="errors.count_type" class="p-error">{{ errors.count_type[0] }}</small>
                </div>

                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1.5">
                    Count Method
                  </label>
                  <Select
                    v-model="form.count_method"
                    :options="countMethodOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select count method"
                    class="w-full"
                  />
                </div>
              </div>
            </div>

            <!-- Product Selection -->
            <div>
              <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between mb-3">
                <h3 class="text-base font-semibold text-gray-800">Products to Count</h3>
                <div class="flex flex-wrap gap-2">
                  <Button
                    label="Auto-Suggest Cycle"
                    icon="pi pi-bolt"
                    severity="warning"
                    outlined
                    size="small"
                    @click="applyCycleSuggestions"
                    type="button"
                    :loading="suggestLoading"
                    :disabled="!currentBranchId"
                  />
                  <Button
                    label="Add All Products"
                    icon="pi pi-plus"
                    severity="info"
                    outlined
                    size="small"
                    @click="addAllProducts"
                    type="button"
                    :disabled="!currentBranchId"
                  />
                  <Button
                    label="Add Selected"
                    icon="pi pi-plus"
                    severity="success"
                    size="small"
                    @click="addSelectedProducts"
                    type="button"
                    :disabled="!currentBranchId || selectedProducts.length === 0"
                  />
                </div>
              </div>

              <!-- Available Products -->
              <div class="mb-4">
                <DataTable
                  :value="availableProducts"
                  :loading="productsLoading"
                  class="p-datatable-sm text-xs"
                  tableStyle="min-width: 50rem"
                  :paginator="true"
                  :rows="10"
                  selectionMode="multiple"
                  v-model:selection="selectedProducts"
                  dataKey="id"
                >
                  <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                  <Column field="name" header="Product" style="min-width: 200px">
                    <template #body="slotProps">
                      <div class="text-sm">
                        <div class="font-medium">{{ slotProps.data.name }}</div>
                        <div class="text-gray-500">{{ slotProps.data.code }}</div>
                      </div>
                    </template>
                  </Column>
                  <Column field="category.name" header="Category" style="min-width: 120px"></Column>
                  <Column field="unit.name" header="Unit" style="min-width: 100px">
                    <template #body="slotProps">
                      {{ slotProps.data.unit?.name || 'N/A' }}
                    </template>
                  </Column>
                  <Column field="current_stock" header="Current Stock" style="min-width: 120px">
                    <template #body="slotProps">
                      {{ slotProps.data.current_stock || 0 }}
                    </template>
                  </Column>
                </DataTable>
              </div>

              <!-- Selected Products for Counting -->
              <div v-if="form.items.length > 0">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Selected for Counting</h4>
                <DataTable
                  :value="form.items"
                  class="p-datatable-sm text-xs"
                  tableStyle="min-width: 50rem"
                >
                  <Column field="product.name" header="Product" style="min-width: 200px">
                    <template #body="slotProps">
                      <div class="text-sm">
                        <div class="font-medium">{{ slotProps.data.product?.name }}</div>
                        <div class="text-gray-500">{{ slotProps.data.product?.code }}</div>
                      </div>
                    </template>
                  </Column>
                  <Column field="expected_quantity" header="Expected Qty" style="min-width: 120px">
                    <template #body="slotProps">
                      {{ slotProps.data.expected_quantity || 0 }}
                    </template>
                  </Column>
                  <Column field="counted_quantity" header="Counted Qty" style="min-width: 120px">
                    <template #body="slotProps">
                      <InputNumber
                        v-model="slotProps.data.counted_quantity"
                        :min="0"
                        class="w-full"
                        placeholder="Enter count"
                      />
                    </template>
                  </Column>
                  <Column field="discrepancy" header="Discrepancy" style="min-width: 120px">
                    <template #body="slotProps">
                      <span :class="getDiscrepancyClass(slotProps.data)">
                        {{ calculateDiscrepancy(slotProps.data) }}
                      </span>
                    </template>
                  </Column>
                  <Column header="Actions" style="min-width: 100px">
                    <template #body="slotProps">
                      <Button
                        icon="pi pi-trash"
                        severity="danger"
                        outlined
                        size="small"
                        @click="removeProduct(slotProps.index)"
                      />
                    </template>
                  </Column>
                </DataTable>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1.5">
                Notes
              </label>
              <Textarea
                v-model="form.notes"
                placeholder="Enter count notes"
                class="w-full"
                rows="4"
                :class="{ 'p-invalid': errors.notes }"
              />
              <small v-if="errors.notes" class="p-error">{{ errors.notes[0] }}</small>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-2 pt-4 border-t">
              <Button
                label="Cancel"
                severity="secondary"
                size="small"
                @click="goBack"
                type="button"
              />
              <Button
                label="Create Stock Count"
                type="submit"
                size="small"
                :loading="saving"
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
import ConfirmDialog from 'primevue/confirmdialog'
import { confirmDialog } from 'primevue/confirmationservice'
import { useToast } from 'primevue/usetoast'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import inventoryService from '../../../../services/inventory.service'

const saving = ref(false)
const productsLoading = ref(false)
const errors = ref<any>({})
const availableProducts = ref<any[]>([])
const selectedProducts = ref<any[]>([])
const suggestLoading = ref(false)
const fetchedBranchName = ref('')
const toast = useToast()
const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  reference_number: '',
  count_date: new Date(),
  count_type: 'full',
  count_method: 'manual',
  items: [] as any[],
  notes: ''
})

const currentBranchId = computed(() => {
  const user = authStore.user as any
  return Number(
    user?.employee?.branch_id ||
    user?.branch?.id ||
    user?.branch_id ||
    user?.employee_branch_id ||
    0
  )
})

const currentBranchLabel = computed(() => {
  const user = authStore.user as any
  const branchName =
    user?.branch?.name ||
    user?.employee?.branch?.name ||
    user?.branch_name ||
    user?.employee?.branch_name
  const branchCode =
    user?.branch?.branch_code ||
    user?.branch?.code ||
    user?.employee?.branch?.branch_code ||
    user?.employee?.branch?.code ||
    user?.branch_code ||
    user?.employee?.branch_code
  const id = currentBranchId.value
  if (branchName && branchCode) return `${branchName} (${branchCode})`
  if (branchName) return branchName
  if (id) return `Branch #${id}`
  return 'Unassigned Branch'
})

const displayBranchLabel = computed(() => {
  if (fetchedBranchName.value) return fetchedBranchName.value
  return currentBranchLabel.value
})

const generateReferenceNumber = () => {
  const now = new Date()
  const yyyy = now.getFullYear()
  const mm = String(now.getMonth() + 1).padStart(2, '0')
  const dd = String(now.getDate()).padStart(2, '0')
  const hh = String(now.getHours()).padStart(2, '0')
  const mi = String(now.getMinutes()).padStart(2, '0')
  const ss = String(now.getSeconds()).padStart(2, '0')
  const ms = String(now.getMilliseconds()).padStart(3, '0')
  return `SC-${yyyy}${mm}${dd}-${hh}${mi}${ss}${ms}`
}

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

const extractRows = (payload: any): any[] => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  if (Array.isArray(payload?.data?.data)) return payload.data.data
  return []
}

const loadBranchName = async () => {
  if (!currentBranchId.value) return
  try {
    const response = await inventoryService.getBranches()
    const rows = extractRows(response?.data ?? response)
    const match = rows.find((b: any) => Number(b.id) === Number(currentBranchId.value))
    if (match?.name) {
      fetchedBranchName.value = match.name
      return
    }

    const fallbackRows = extractRows(response)
    const fallbackMatch = fallbackRows.find((b: any) => Number(b.id) === Number(currentBranchId.value))
    if (fallbackMatch?.name) {
      fetchedBranchName.value = fallbackMatch.name
    }
  } catch {
    // Keep fallback label from auth profile.
  }
}

const loadProducts = async () => {
  if (!currentBranchId.value) return

  productsLoading.value = true
  try {
    const response = await inventoryService.getInventoryItems({
      branch_id: currentBranchId.value,
      per_page: 1000
    })

    if (response.success) {
      const rows = Array.isArray(response.data) ? response.data : (response.data?.data || [])
      availableProducts.value = rows.map((item: any) => {
        const currentStock = Number(
          item.quantity_on_hand ??
          item.quantity_available ??
          item.current_stock ??
          0
        )

        return {
          ...item,
          id: item.id,
          inventory_item_id: item.id,
          product_id: item.product_id || item.product?.id || null,
          variation_id: item.variation_id || null,
          name: item.product?.product_name || item.product_name || item.name || '-',
          code: item.product?.sku || item.sku || item.code || '-',
          category: {
            ...(item.product?.category || item.category || {}),
            name: item.product?.category?.category_name || item.product?.category?.name || item.category?.name || 'Uncategorized',
          },
          unit: {
            ...(item.product?.unit || item.unit || {}),
            name: item.product?.unit?.name || item.unit?.name || item.unit_name || null,
          },
          current_stock: currentStock,
        }
      })
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

const applyCycleSuggestions = async () => {
  if (!currentBranchId.value) return

  suggestLoading.value = true
  try {
    const response = await inventoryService.getStockCountSuggestions({
      branch_id: currentBranchId.value,
      limit: 50
    })

    if (response.success) {
      const suggestions = response.data?.items || []
      form.count_type = 'cycle'

      const newItems = suggestions
        .filter((item: any) => !form.items.some(existing => existing.product_id === item.product_id))
        .map((item: any) => ({
          product_id: item.product_id,
          product: {
            id: item.product_id,
            name: item.product_name,
            code: item.sku
          },
          expected_quantity: item.current_stock || 0,
          counted_quantity: null,
          discrepancy: 0
        }))

      form.items.push(...newItems)
      toast.add({
        severity: 'success',
        summary: 'Suggestions Applied',
        detail: `Added ${newItems.length} products to cycle count`,
        life: 3000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response.message || 'Failed to load suggestions',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to load suggestions',
      life: 3000
    })
  } finally {
    suggestLoading.value = false
  }
}

const addAllProducts = () => {
  const newItems = availableProducts.value
    .filter(product => !form.items.some(item => item.inventory_item_id === product.inventory_item_id))
    .map(product => ({
      product_id: product.product_id,
      variation_id: product.variation_id || null,
      inventory_item_id: product.inventory_item_id,
      product: product,
      expected_quantity: product.current_stock || 0,
      counted_quantity: null,
      discrepancy: 0
    }))

  form.items.push(...newItems)
}

const addSelectedProducts = () => {
  const newItems = selectedProducts.value
    .filter(product => !form.items.some(item => item.inventory_item_id === product.inventory_item_id))
    .map(product => ({
      product_id: product.product_id,
      variation_id: product.variation_id || null,
      inventory_item_id: product.inventory_item_id,
      product: product,
      expected_quantity: product.current_stock || 0,
      counted_quantity: null,
      discrepancy: 0
    }))

  form.items.push(...newItems)
  selectedProducts.value = []
}

const removeProduct = (index: number) => {
  form.items.splice(index, 1)
}

const calculateDiscrepancy = (item: any) => {
  if (item.counted_quantity === null || item.counted_quantity === undefined) return '-'
  return item.counted_quantity - (item.expected_quantity || 0)
}

const getDiscrepancyClass = (item: any) => {
  const discrepancy = calculateDiscrepancy(item)
  if (discrepancy === '-') return ''
  return discrepancy === 0 ? 'text-green-600' : 'text-red-600 font-medium'
}

const goBack = () => {
  router.push({ name: 'inventory.stock-counts.index' })
}

const doCreateStockCount = async () => {
  saving.value = true
  errors.value = {}

  if (!form.reference_number) {
    form.reference_number = generateReferenceNumber()
  }

  // Ensure every item has a counted quantity
  const missingCount = form.items.find(
    (item: any) =>
      item.counted_quantity === null ||
      item.counted_quantity === undefined ||
      item.counted_quantity === ''
  )
  if (missingCount) {
    toast.add({
      severity: 'warn',
      summary: 'Count required',
      detail: 'Enter counted quantity for all items before submitting.',
      life: 3000
    })
    saving.value = false
    return
  }

  // Prepare form data
  const submitData = {
    ...form,
    branch_id: currentBranchId.value || undefined,
    // Auto-assign today's date in background
    count_date: new Date().toISOString().split('T')[0],
    items: form.items.map(item => ({
      product_id: item.product_id,
      variation_id: item.variation_id,
      inventory_item_id: item.inventory_item_id,
      expected_quantity: item.expected_quantity,
      counted_quantity: item.counted_quantity
    }))
  }

  try {
    const response = await inventoryService.createStockCount(submitData)

    if (response.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: 'Stock count created successfully',
        life: 3000
      })
      router.push({ name: 'inventory.stock-counts.detail', params: { id: response.data.id } })
    } else {
      if (response.errors) {
        errors.value = response.errors
      } else {
        toast.add({
          severity: 'error',
          summary: 'Error',
          detail: response.message || 'Failed to create stock count',
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
        detail: error.response?.data?.message || 'Failed to create stock count',
        life: 3000
      })
    }
  } finally {
    saving.value = false
  }
}

const submitForm = async () => {
  if (!form.items || form.items.length === 0) {
    toast.add({ severity: 'warn', summary: 'No Items', detail: 'Please add at least one product to count', life: 3000 })
    return
  }

  confirmDialog({
    message: 'Create this stock count? You can edit later if needed.',
    header: 'Confirm Create',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      await doCreateStockCount()
    }
  })
}

onMounted(() => {
  form.reference_number = generateReferenceNumber()

  ;(async () => {
    // Ensure we have the latest user profile (branch assignment is often not included in the login payload).
    try {
      await authStore.fetchCurrentUser()
    } catch {
      // If this fails, we'll still fall back to whatever was in localStorage.
    }

    if (!currentBranchId.value) {
      toast.add({
        severity: 'warn',
        summary: 'Branch Required',
        detail: 'No branch is assigned to your user profile. Please contact your admin.',
        life: 4000
      })
      return
    }

    await loadBranchName()
    await loadProducts()
  })()
})
</script>
