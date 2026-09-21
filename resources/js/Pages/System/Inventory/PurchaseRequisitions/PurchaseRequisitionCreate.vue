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
                <Button type="button" label="Add Item" icon="pi pi-plus" size="small" @click="addItem" />
              </div>
  
              <DataTable :value="form.items" responsiveLayout="scroll" class="text-sm">
                <Column header="Inventory Item">
                  <template #body="slotProps">
                    <Select v-model="slotProps.data.branch_inventory_id" :options="inventoryOptions"
                      optionLabel="searchText" optionValue="value" filter fluid :loading="loadingInventory"
                      placeholder="Select product" @change="onInventoryChange(slotProps.index, $event)">
                      <template #option="optionProps">
                        <div class="flex flex-col">
                          <span class="text-sm text-gray-800">{{ optionProps.option.title }}</span>
                          <span class="text-xs text-gray-500">{{ optionProps.option.subtitle }}</span>
                        </div>
                      </template>
                      <template #value="valueProps">
                        <div v-if="getInventoryOptionByValue(valueProps.value)" class="flex flex-col">
                          <span class="text-sm text-gray-800">{{ getInventoryOptionByValue(valueProps.value)?.title
                            }}</span>
                          <span class="text-xs text-gray-500">{{ getInventoryOptionByValue(valueProps.value)?.subtitle
                            }}</span>
                        </div>
                        <span v-else class="text-sm text-gray-500">{{ valueProps.placeholder }}</span>
                      </template>
                    </Select>
                  </template>
                </Column>
  
                <Column header="Available" style="width: 110px">
                  <template #body="slotProps">
                    {{ getInventoryById(slotProps.data.branch_inventory_id)?.quantity_available ?? '-' }}
                  </template>
                </Column>
  
                <Column header="Requested Qty" style="width: 150px">
                  <template #body="slotProps">
                    <InputNumber v-model="slotProps.data.requested_quantity" :min="1" :useGrouping="false"
                      class="w-full" />
                  </template>
                </Column>
  
                <Column header="Unit" style="width: 150px">
                  <template #body="slotProps">
                    {{ getInventoryById(slotProps.data.branch_inventory_id)?.product?.unit_of_measurement || '-' }}
                  </template>
                </Column>
  
                <Column header="Unit Cost" style="width: 140px">
                  <template #body="slotProps">
                    {{ formatMoney(resolveUnitCost(getInventoryById(slotProps.data.branch_inventory_id))) }}
                  </template>
                </Column>
  
                <Column header="Unit Total" style="width: 160px">
                  <template #body="slotProps">
                    {{ formatMoney(resolveUnitCost(getInventoryById(slotProps.data.branch_inventory_id)) * Number(slotProps.data.requested_quantity || 0)) }}
                  </template>
                </Column>
  
                <Column style="width: 160px">
                  <template #body="slotProps">
                    <div class="flex gap-2">
                      <Button type="button" icon="pi pi-trash" severity="danger" text :disabled="form.items.length === 1"
                        @click="removeItem(slotProps.index)" />
                    </div>
                  </template>
                </Column>
              </DataTable>
  
              <small v-if="errors.items" class="p-error mt-2 block">{{ errors.items }}</small>
              <div v-if="previewItems.length" class="mt-4">
                <div class="text-sm font-semibold text-gray-800 mb-2">Product List Preview</div>
                <DataTable :value="previewItems" class="p-datatable-sm text-xs" responsiveLayout="scroll">
                  <Column header="Product" style="min-width: 260px">
                    <template #body="{ data }">
                      <div class="text-sm">
                        <div class="font-semibold text-gray-900">{{ data.product_name || 'N/A' }}</div>
                        <div class="text-gray-500">SKU: {{ data.sku || '-' }}</div>
                      </div>
                    </template>
                  </Column>
                  <Column header="Qty" style="width: 120px">
                    <template #body="{ data }">
                      <span class="font-semibold text-gray-900">{{ data.quantity_requested }}</span>
                    </template>
                  </Column>
                  <Column header="Unit" style="width: 120px">
                    <template #body="{ data }">
                      {{ data.unit_of_measurement || '-' }}
                    </template>
                  </Column>
  
                  <Column header="Unit Cost" style="width: 140px">
                    <template #body="{ data }">
                      {{ formatMoney(data.estimated_unit_cost) }}
                    </template>
                  </Column>
                  <Column header="Unit Total" style="width: 160px">
                    <template #body="{ data }">
                      {{ formatMoney(Number(data.quantity_requested || 0) * Number(data.estimated_unit_cost || 0)) }}
                    </template>
                  </Column>
                </DataTable>
              </div>
            </div>
  
            <div class="flex justify-end gap-2 pt-3 border-t">
              <Button type="button" label="Cancel" severity="secondary" size="small" @click="goBack" />
              <Button type="submit" label="Create Request" size="small" :loading="saving"
                :disabled="!canManage || validItems.length === 0" />
            </div>
          </form>
        </template>
      </Card>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import ConfirmDialog from 'primevue/confirmdialog'
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
}

const buildEmptyItem = (): InventoryPrItem => ({
  branch_inventory_id: null,
  requested_quantity: 1,
})

const form = reactive<{
  notes: string
  items: InventoryPrItem[]
}>({
  notes: '',
  items: [buildEmptyItem()],
})

const validItems = computed(() => form.items.filter((item) => item.branch_inventory_id && item.requested_quantity > 0))
const previewItems = computed(() => {
  return validItems.value.map((item) => {
    const inventoryRow = getInventoryById(item.branch_inventory_id)
    return {
      product_name: inventoryRow?.product?.product_name || inventoryRow?.product_name || 'N/A',
      sku: inventoryRow?.variation?.variation_sku || inventoryRow?.product?.sku || inventoryRow?.sku || '-',
      unit_of_measurement: inventoryRow?.product?.unit_of_measurement || inventoryRow?.unit_of_measurement || '-',
      quantity_requested: Number(item.requested_quantity || 0),
      estimated_unit_cost: resolveUnitCost(inventoryRow),
    }
  })
})
const inventoryOptions = computed(() => {
  return inventoryRows.value
    .slice()
    .sort((a: any, b: any) => Number(a?.quantity_available ?? 0) - Number(b?.quantity_available ?? 0))
    .map((row: any) => {
      const productName = row?.product?.product_name || row?.product_name || 'Unknown'
      const sku = row?.product?.sku || row?.sku || ''
      const variant = row?.variation?.variation_name || row?.variant_name || ''
      const stock = Number(row?.quantity_available ?? 0)
      const title = `${productName}${variant ? ` - ${variant}` : ''}${sku ? ` (${sku})` : ''}`
      const subtitle = `Stock: ${stock}`
      return {
        value: row.id,
        title,
        subtitle,
        searchText: `${title} ${subtitle}`,
      }
    })
})

const getInventoryOptionByValue = (value: number | string | null | undefined) => {
  if (!value) return null
  return inventoryOptions.value.find((option: any) => Number(option.value) === Number(value)) || null
}

const goBack = () => router.push({ name: 'inventory.requisites.index' })
const formatMoney = (value: any) => {
  const n = Number(value || 0)
  return n.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })
}

const resolveUnitCost = (inventoryRow: any): number => {
  return Number(
    inventoryRow?.variation?.cost_price ??
    inventoryRow?.product?.inventory_cost_price ??
    inventoryRow?.product?.cost_price ??
    inventoryRow?.cost_price ??
    inventoryRow?.unit_cost ??
    inventoryRow?.average_cost ??
    inventoryRow?.product?.base_price ??
    0
  )
}

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

  await hydrateInventoryById(item.branch_inventory_id)
  applyReorderQty(index)
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
        quantity_requested: Number(item.requested_quantity),
        estimated_unit_cost: resolveUnitCost(inventoryRow),
        tax_rate: Number(inventoryRow?.product?.tax_rate ?? 0),
        specifications: null,
      }
    })

    const response = await inventoryService.createPurchaseRequisitionFromInventory({
      reason: form.notes || 'Stock replenishment request.',
      requisition_type: 'regular',
      items: payloadItems,
    })

    if (response?.success) {
      toast.add({ severity: 'success', summary: 'Draft Created', detail: 'Purchase requisition saved as a draft. Submit it from the detail page when ready.', life: 3500 })
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
  await doCreate()
}

onMounted(async () => {
  try {
    if (!authStore.user) await authStore.fetchCurrentUser()
  } catch { }

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

    if (typeof notesRaw === 'string' && notesRaw.trim()) {
      form.notes = notesRaw
    }
  }
})

watch(currentBranchId, async (branchId, previousBranchId) => {
  if (!branchId || branchId === previousBranchId) return

  if (!branchLabel.value || branchLabel.value === 'Unassigned Branch') {
    await loadInventory()
  }
})
</script>
