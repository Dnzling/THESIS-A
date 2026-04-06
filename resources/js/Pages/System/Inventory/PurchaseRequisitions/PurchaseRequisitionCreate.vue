<template>
  <div class="min-h-screen p-4">
    <ConfirmDialog />
    <div class="max-w-7xl mx-auto">
      <div class="mb-4 flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text @click="goBack" />
        <div>
          <h1 class="text-xl font-bold text-gray-800">Create Purchase Requisition</h1>
          <p class="text-xs text-gray-500 mt-0.5">Request replenishment for your branch inventory with multiple items.</p>
        </div>
      </div>

      <Card>
        <template #content>
          <form class="space-y-4" @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-700">Branch</label>
                <InputText :modelValue="branchLabel" disabled />
                <small class="text-gray-500">Auto-filled from your profile</small>
              </div>
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-700">Reason / Notes</label>
                <Textarea v-model="form.notes" rows="2" class="w-full" placeholder="Why do you need this stock?" />
              </div>
            </div>

            <div class="border rounded-lg p-3">
              <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-800">Line Items</h3>
                <Button type="button" label="Add Item" icon="pi pi-plus" size="small" outlined @click="addItem" />
              </div>

              <DataTable :value="form.items" responsiveLayout="scroll" class="text-sm">
                <Column header="Inventory Item" >
                  <template #body="slotProps">
                    <Select
                      v-model="slotProps.data.branch_inventory_id"
                      :options="inventoryOptions"
                      optionLabel="label"
                      optionValue="value"
                      filter fluid
                      :loading="loadingInventory"
                      placeholder="Select product"
                      @change="onInventoryChange(slotProps.index, $event)"
                    />
                  </template>
                </Column>

                <Column header="Available" style="width: 110px">
                  <template #body="slotProps">
                    {{ getInventoryById(slotProps.data.branch_inventory_id)?.quantity_available ?? '-' }}
                  </template>
                </Column>

                <Column header="Requested Qty" style="width: 150px">
                  <template #body="slotProps">
                    <InputNumber v-model="slotProps.data.requested_quantity" :min="1" :useGrouping="false" class="w-full" />
                  </template>
                </Column>

                <Column header="Supplier (Optional)" style="min-width: 240px">
                  <template #body="slotProps">
                    <Select
                      v-model="slotProps.data.selected_supplier_id"
                      :options="getSupplierOptionsForRow(slotProps.data)"
                      optionLabel="label"
                      optionValue="value"
                      filter fluid
                      showClear
                      placeholder="Auto-resolve"
                      :disabled="!slotProps.data.branch_inventory_id"
                    />
                  </template>
                </Column>

                <Column header="Actions" style="width: 160px">
                  <template #body="slotProps">
                    <div class="flex gap-2">
                      <Button
                        type="button"
                        icon="pi pi-bolt"
                        severity="info"
                        text
                        :disabled="!getInventoryById(slotProps.data.branch_inventory_id)?.reorder_quantity"
                        @click="applyReorderQty(slotProps.index)"
                      />
                      <Button
                        type="button"
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        :disabled="form.items.length === 1"
                        @click="removeItem(slotProps.index)"
                      />
                    </div>
                  </template>
                </Column>
              </DataTable>

              <small v-if="errors.items" class="p-error mt-2 block">{{ errors.items }}</small>
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t">
              <Button type="button" label="Cancel" severity="secondary" size="small" @click="goBack" />
              <Button
                type="submit"
                label="Create Request"
                size="small"
                :loading="saving"
                :disabled="!canManage || validItems.length === 0"
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
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import { useAuthStore } from '@/stores/auth'
import inventoryService from '@/services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const saving = ref(false)
const loadingInventory = ref(false)
const inventoryRows = ref<any[]>([])
const errors = reactive<Record<string, string>>({})

const canManage = computed(() => authStore.hasPermission('inventory.requisites.manage'))
const canViewBranchInventory = computed(() => authStore.hasPermission('inventory.branch_inventory.view'))

const currentBranchId = computed(() => {
  const user = authStore.user as any
  return Number(user?.branch?.id || user?.employee?.branch_id || user?.branch_id || 0)
})

const branchLabel = computed(() => {
  const user = authStore.user as any
  const name = user?.branch?.name || user?.branch_name || user?.employee?.branch?.name
  const code = user?.branch?.code || user?.branch?.branch_code || user?.branch_code
  if (name && code) return `${name} (${code})`
  if (name) return name
  if (currentBranchId.value) return `Branch #${currentBranchId.value}`
  return 'Unassigned Branch'
})

type InventoryPrItem = {
  branch_inventory_id: number | null
  requested_quantity: number
  selected_supplier_id: number | null
}

const buildEmptyItem = (): InventoryPrItem => ({
  branch_inventory_id: null,
  requested_quantity: 1,
  selected_supplier_id: null,
})

const form = reactive<{
  notes: string
  items: InventoryPrItem[]
}>({
  notes: '',
  items: [buildEmptyItem()],
})

const validItems = computed(() => form.items.filter((item) => item.branch_inventory_id && item.requested_quantity > 0))

const inventoryOptions = computed(() => {
  return inventoryRows.value.map((row: any) => {
    const productName = row?.product?.product_name || row?.product_name || 'Unknown'
    const sku = row?.product?.sku || row?.sku || ''
    const variant = row?.variation?.variation_name || row?.variant_name || ''
    const label = `${productName}${variant ? ` - ${variant}` : ''}${sku ? ` (${sku})` : ''}`
    return { value: row.id, label }
  })
})

const goBack = () => router.push({ name: 'inventory.requisites.index' })

const loadInventory = async () => {
  if (!currentBranchId.value) return
  if (!canViewBranchInventory.value) {
    inventoryRows.value = []
    toast.add({
      severity: 'warn',
      summary: 'Permission Required',
      detail: 'You do not have permission to view branch inventory items.',
      life: 3500,
    })
    return
  }
  loadingInventory.value = true
  try {
    const response = await inventoryService.getInventoryItems({ branch_id: currentBranchId.value, per_page: 1000 })
    if (response?.success) {
      inventoryRows.value = Array.isArray(response.data) ? response.data : (response.data?.data || [])
    } else {
      inventoryRows.value = []
    }
  } catch (e: any) {
    inventoryRows.value = []
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: e?.response?.data?.message || 'Failed to load branch inventory',
      life: 3000,
    })
  } finally {
    loadingInventory.value = false
  }
}

const getInventoryById = (inventoryId: number | null) => {
  if (!inventoryId) return null
  return inventoryRows.value.find((r: any) => Number(r.id) === Number(inventoryId)) || null
}

const getSupplierOptionsForRow = (item: InventoryPrItem) => {
  const suppliers = Array.isArray(getInventoryById(item.branch_inventory_id)?.product?.suppliers)
    ? getInventoryById(item.branch_inventory_id)?.product?.suppliers
    : []

  return suppliers
    .slice()
    .sort((a: any, b: any) => Number(Boolean(b?.pivot?.is_preferred_supplier)) - Number(Boolean(a?.pivot?.is_preferred_supplier)))
    .map((supplier: any) => ({
      value: supplier.id,
      label: supplier.supplier_name || supplier.company_name || `Supplier #${supplier.id}`,
      isPreferred: Boolean(supplier?.pivot?.is_preferred_supplier),
    }))
}

const getDefaultSupplierIdForRow = (item: InventoryPrItem): number | null => {
  const options = getSupplierOptionsForRow(item)
  if (options.length === 0) {
    return null
  }

  const preferred = options.find((option: any) => option.isPreferred)
  if (preferred) {
    return Number(preferred.value)
  }

  if (options.length === 1) {
    return Number(options[0].value)
  }

  return null
}

const addItem = () => {
  form.items.push(buildEmptyItem())
}

const removeItem = (index: number) => {
  if (form.items.length === 1) return
  form.items.splice(index, 1)
}

const applyReorderQty = (index: number) => {
  const row = form.items[index]
  const qty = Number(getInventoryById(row.branch_inventory_id)?.reorder_quantity || 0)
  if (qty > 0) {
    row.requested_quantity = qty
  }
}

const hydrateInventoryById = async (inventoryId: number | null) => {
  if (!inventoryId) return

  try {
    const response = await inventoryService.getInventoryItem(inventoryId)
    const fullRow = response?.data || response?.data?.data || null
    if (!fullRow?.id) return

    const idx = inventoryRows.value.findIndex((r: any) => Number(r.id) === Number(fullRow.id))
    if (idx >= 0) {
      inventoryRows.value[idx] = { ...inventoryRows.value[idx], ...fullRow }
    } else {
      inventoryRows.value.push(fullRow)
    }
  } catch {
    // Keep current row when details endpoint is unavailable.
  }
}

const onInventoryChange = async (index: number, event: any) => {
  const item = form.items[index]
  item.branch_inventory_id = Number(event?.value || item.branch_inventory_id || 0) || null
  item.selected_supplier_id = null

  await hydrateInventoryById(item.branch_inventory_id)
  item.selected_supplier_id = getDefaultSupplierIdForRow(item)
}

const doCreate = async () => {
  Object.keys(errors).forEach(k => delete errors[k])
  saving.value = true
  try {
    const payloadItems = validItems.value.map((item) => {
      const inventoryRow = getInventoryById(item.branch_inventory_id)

      return {
        product_id: Number(inventoryRow?.product_id),
        variation_id: inventoryRow?.variation_id ?? null,
        selected_supplier_id: item.selected_supplier_id || null,
        quantity_requested: Number(item.requested_quantity),
        estimated_unit_cost: Number(inventoryRow?.product?.cost_price ?? inventoryRow?.product?.base_price ?? 0),
        tax_rate: Number(inventoryRow?.product?.tax_rate ?? 0),
        specifications: null,
      }
    })

    const response = await inventoryService.createPurchaseRequisitionFromInventory({
      reason: form.notes || 'Stock replenishment request.',
      requisition_type: 'regular',
      items: payloadItems,
      auto_submit: true,
    })

    if (response?.success) {
      toast.add({ severity: 'success', summary: 'Created', detail: 'Purchase requisition created.', life: 2500 })
      router.push({ name: 'inventory.requisites.detail', params: { id: response.data?.id } })
    } else {
      toast.add({ severity: 'error', summary: 'Error', detail: response?.message || 'Failed to create request', life: 3000 })
    }
  } catch (e: any) {
    const apiErrors = e?.response?.data?.errors
    if (apiErrors && typeof apiErrors === 'object') {
      Object.entries(apiErrors).forEach(([k, v]: any) => {
        errors[k] = Array.isArray(v) ? v[0] : String(v)
      })
    } else {
      toast.add({ severity: 'error', summary: 'Error', detail: e?.response?.data?.message || 'Failed to create request', life: 3000 })
    }
  } finally {
    saving.value = false
  }
}

const submit = async () => {
  Object.keys(errors).forEach(k => delete errors[k])
  if (!canManage.value) return
  if (validItems.value.length === 0) {
    errors.items = 'Please add at least one valid item with quantity.'
    return
  }

  confirmDialog({
    message: 'Are you sure you want to create this purchase requisition?',
    header: 'Confirm Create',
    icon: 'pi pi-exclamation-triangle',
    accept: async () => {
      await doCreate()
    },
  })
}

onMounted(async () => {
  try {
    if (!authStore.user) await authStore.fetchCurrentUser()
  } catch {}

  if (!currentBranchId.value) {
    toast.add({
      severity: 'warn',
      summary: 'Branch Required',
      detail: 'No branch is assigned to your user profile.',
      life: 4000,
    })
  }

  await loadInventory()

  // Auto-fill when coming from Branch Inventory "Create PR"
  const q = route.query || {}
  const biRaw = Array.isArray(q.branch_inventory_id) ? q.branch_inventory_id[0] : q.branch_inventory_id
  const qtyRaw = Array.isArray(q.requested_quantity) ? q.requested_quantity[0] : q.requested_quantity
  const notesRaw = Array.isArray(q.notes) ? q.notes[0] : q.notes

  const biId = biRaw ? Number(biRaw) : 0
  if (biId) {
    form.items = [buildEmptyItem()]
    form.items[0].branch_inventory_id = biId
    await hydrateInventoryById(biId)

    const qty = qtyRaw ? Number(qtyRaw) : 0
    if (qty && qty > 0) {
      form.items[0].requested_quantity = qty
    } else {
      // fallback to reorder qty if available
      applyReorderQty(0)
    }

    form.items[0].selected_supplier_id = getDefaultSupplierIdForRow(form.items[0])

    if (typeof notesRaw === 'string' && notesRaw.trim()) {
      form.notes = notesRaw
    }
  }
})
</script>
