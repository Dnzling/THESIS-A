<template>
  <div class="min-h-screen p-4">
    <div class="max-w-4xl mx-auto">
      <div class="mb-4 flex items-center gap-3">
        <Button icon="pi pi-arrow-left" severity="secondary" text @click="goBack" />
        <div>
          <h1 class="text-xl font-bold text-gray-800">Create Purchase Requisition</h1>
          <p class="text-xs text-gray-500 mt-0.5">Request replenishment for your branch inventory.</p>
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
                <label class="text-xs font-semibold text-gray-700">
                  Inventory Item <span class="text-red-500">*</span>
                </label>
                <Select
                  v-model="form.branch_inventory_id"
                  :options="inventoryOptions"
                  optionLabel="label"
                  optionValue="value"
                  filter
                  :loading="loadingInventory"
                  placeholder="Select product"
                  :class="{ 'p-invalid': errors.branch_inventory_id }"
                />
                <small v-if="errors.branch_inventory_id" class="p-error">{{ errors.branch_inventory_id }}</small>
              </div>
            </div>

            <div v-if="selectedInventory" class="rounded-lg border border-gray-100 bg-gray-50 p-3 text-xs">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                  <div class="text-gray-500">SKU</div>
                  <div class="font-semibold text-gray-800">{{ selectedInventory.product?.sku || '-' }}</div>
                </div>
                <div>
                  <div class="text-gray-500">On Hand</div>
                  <div class="font-semibold text-gray-800">{{ selectedInventory.quantity_on_hand ?? 0 }}</div>
                </div>
                <div>
                  <div class="text-gray-500">Available</div>
                  <div class="font-semibold text-gray-800">{{ selectedInventory.quantity_available ?? 0 }}</div>
                </div>
                <div>
                  <div class="text-gray-500">Reorder Point</div>
                  <div class="font-semibold text-gray-800">{{ selectedInventory.reorder_point ?? 0 }}</div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-1.5">
                <label class="text-xs font-semibold text-gray-700">
                  Requested Quantity <span class="text-red-500">*</span>
                </label>
                <InputNumber
                  v-model="form.requested_quantity"
                  :min="1"
                  :useGrouping="false"
                  class="w-full"
                  :class="{ 'p-invalid': errors.requested_quantity }"
                />
                <small v-if="errors.requested_quantity" class="p-error">{{ errors.requested_quantity }}</small>
              </div>

              <div class="flex items-end">
                <Button
                  type="button"
                  label="Use Reorder Qty"
                  icon="pi pi-bolt"
                  severity="info"
                  outlined
                  size="small"
                  :disabled="!selectedInventory?.reorder_quantity"
                  @click="applyReorderQty"
                />
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label class="text-xs font-semibold text-gray-700">Reason / Notes</label>
              <Textarea v-model="form.notes" rows="3" class="w-full" placeholder="Why do you need this stock?" />
            </div>

            <div class="flex justify-end gap-2 pt-3 border-t">
              <Button type="button" label="Cancel" severity="secondary" size="small" @click="goBack" />
              <Button
                type="submit"
                label="Create Request"
                size="small"
                :loading="saving"
                :disabled="!canManage || !form.branch_inventory_id || !form.requested_quantity"
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

const form = reactive<{
  branch_inventory_id: number | null
  requested_quantity: number
  notes: string
}>({
  branch_inventory_id: null,
  requested_quantity: 1,
  notes: '',
})

const selectedInventory = computed(() => {
  if (!form.branch_inventory_id) return null
  return inventoryRows.value.find((r: any) => Number(r.id) === Number(form.branch_inventory_id)) || null
})

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

const applyReorderQty = () => {
  const qty = Number(selectedInventory.value?.reorder_quantity || 0)
  if (qty > 0) form.requested_quantity = qty
}

const submit = async () => {
  Object.keys(errors).forEach(k => delete errors[k])
  if (!canManage.value) return
  if (!form.branch_inventory_id) {
    errors.branch_inventory_id = 'Inventory item is required.'
    return
  }
  if (!form.requested_quantity || form.requested_quantity < 1) {
    errors.requested_quantity = 'Requested quantity must be at least 1.'
    return
  }

  saving.value = true
  try {
    const response = await inventoryService.createPurchaseRequisitionFromInventory({
      branch_inventory_id: form.branch_inventory_id,
      requested_quantity: form.requested_quantity,
      reason: form.notes || null,
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
    form.branch_inventory_id = biId
    const qty = qtyRaw ? Number(qtyRaw) : 0
    if (qty && qty > 0) {
      form.requested_quantity = qty
    } else {
      // fallback to reorder qty if available
      applyReorderQty()
    }
    if (typeof notesRaw === 'string' && notesRaw.trim()) {
      form.notes = notesRaw
    }
  }
})
</script>
