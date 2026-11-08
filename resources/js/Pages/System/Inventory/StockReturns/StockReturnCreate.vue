<template>
  <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6">
    <header class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded aria-label="Go back" @click="router.back()" />
      <div>
        <h1 class="text-xl font-bold text-slate-900">Return Damaged Item to Supplier</h1>
        <p class="text-sm text-slate-500">Create a supplier disposition request. Stock stays quarantined until it is physically dispatched.</p>
      </div>
    </header>

    <Skeleton v-if="loading" height="20rem" borderRadius="1rem" />
    <Message v-else-if="pageError" severity="error" :closable="false">{{ pageError }}</Message>
    <Card v-else-if="inventoryItem" class="border border-slate-200 shadow-sm">
      <template #content>
        <form class="space-y-5" @submit.prevent="confirmVisible = true">
          <Message severity="info" :closable="false">This records the proposed return only. Approving it does not reduce stock; the damaged on-hand quantity is removed when the return is marked as dispatched.</Message>
          <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
              <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Damaged item</p>
              <p class="mt-1 font-semibold text-slate-900">{{ inventoryItem.product?.product_name || 'Product' }}</p>
              <p class="text-sm text-slate-600">{{ inventoryItem.variation?.variation_name || 'Standard item' }} · SKU {{ inventoryItem.variation?.variation_sku || inventoryItem.product?.sku || '-' }}</p>
              <p class="mt-2 text-sm text-red-700">{{ inventoryItem.quantity_damaged || 0 }} damaged unit(s) recorded at {{ inventoryItem.branch?.name || 'this branch' }}</p>
            </div>

            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Supplier <span class="text-red-600">*</span></label>
              <Select v-model="form.supplier_id" :options="suppliers" optionLabel="supplier_name" optionValue="id" placeholder="Select linked supplier" filter fluid :loading="suppliersLoading" />
              <small class="text-xs text-slate-500">Only active suppliers linked to this product are listed.</small>
            </div>
            <div class="space-y-1.5">
              <label class="text-sm font-medium text-slate-700">Quantity to return <span class="text-red-600">*</span></label>
              <InputNumber v-model="form.quantity" :min="1" :max="Number(inventoryItem.quantity_damaged || 0)" :useGrouping="false" showButtons fluid />
            </div>
            <div class="space-y-1.5 sm:col-span-2">
              <label class="text-sm font-medium text-slate-700">Issue details</label>
              <Textarea v-model="form.reason_details" rows="3" autoResize fluid placeholder="Describe the damage and requested supplier action." />
            </div>
            <div class="space-y-1.5 sm:col-span-2">
              <label class="text-sm font-medium text-slate-700">Internal notes</label>
              <Textarea v-model="form.notes" rows="2" autoResize fluid placeholder="Optional notes for the return record." />
            </div>
          </div>

          <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
            <Button label="Cancel" severity="secondary" outlined type="button" @click="router.back()" />
            <Button label="Submit Return Request" icon="pi pi-send" severity="warn" type="submit" :disabled="!canSubmit" :loading="saving" />
          </div>
        </form>
      </template>
    </Card>

    <Dialog v-model:visible="confirmVisible" modal header="Submit supplier return?" :style="{ width: 'min(28rem, calc(100vw - 2rem))' }">
      <p class="text-sm leading-6 text-slate-600">Submit {{ form.quantity }} damaged unit(s) to {{ selectedSupplier?.supplier_name || 'the selected supplier' }} for disposition? The quantity remains recorded in damaged stock until dispatch.</p>
      <template #footer>
        <Button label="Keep Editing" severity="secondary" outlined @click="confirmVisible = false" />
        <Button label="Submit Request" severity="warn" :loading="saving" @click="submit" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Card from 'primevue/card'
import Dialog from 'primevue/dialog'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'
import inventoryService from '@/services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const inventoryItem = ref<any>(null)
const suppliers = ref<any[]>([])
const loading = ref(true)
const suppliersLoading = ref(false)
const saving = ref(false)
const pageError = ref('')
const confirmVisible = ref(false)
const form = reactive({ supplier_id: null as number | null, quantity: 1, reason_details: '', notes: '' })
const selectedSupplier = computed(() => suppliers.value.find(row => Number(row.id) === Number(form.supplier_id)))
const canSubmit = computed(() => Boolean(form.supplier_id && Number(form.quantity) > 0 && Number(form.quantity) <= Number(inventoryItem.value?.quantity_damaged || 0)))

const submit = async () => {
  if (!canSubmit.value || saving.value || !inventoryItem.value) return
  saving.value = true
  try {
    const response = await inventoryService.createStockReturn({
      from_branch_id: Number(inventoryItem.value.branch_id),
      supplier_id: Number(form.supplier_id),
      return_type: 'supplier_return',
      return_reason: 'damaged',
      reason_details: form.reason_details || null,
      notes: form.notes || null,
      items: [{
        branch_inventory_id: Number(inventoryItem.value.id),
        product_id: Number(inventoryItem.value.product_id),
        variation_id: inventoryItem.value.variation_id || null,
        quantity_returned: Number(form.quantity),
        condition: 'damaged',
        return_reason: form.reason_details || 'Damaged stock supplier disposition',
      }],
    })
    const payload = response?.data ?? response
    const id = Number(payload?.id || payload?.data?.id)
    toast.add({ severity: 'success', summary: 'Return request submitted', detail: 'The damaged stock remains quarantined until dispatch.', life: 3500 })
    confirmVisible.value = false
    if (id) router.push({ name: 'inventory.stock-returns.detail', params: { id } })
    else router.back()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Unable to submit', detail: error?.response?.data?.message || Object.values(error?.response?.data?.errors || {}).flat()[0] || 'The supplier return could not be submitted.', life: 5000 })
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  if (String(route.query.damaged_return || '') !== '1') {
    pageError.value = 'Open this form from an inventory item with damaged units recorded.'
    loading.value = false
    return
  }
  try {
    const inventoryId = Number(route.query.branch_inventory_id || 0)
    const response = await inventoryService.getInventoryItem(inventoryId)
    inventoryItem.value = response?.data ?? response
    if (!inventoryItem.value?.id || Number(inventoryItem.value.quantity_damaged || 0) < 1) throw new Error('This item has no damaged quantity available to return.')
    form.quantity = Math.min(Number(inventoryItem.value.quantity_damaged), Math.max(1, Number(route.query.requested_quantity || inventoryItem.value.quantity_damaged)))
    suppliersLoading.value = true
    const supplierResponse = await inventoryService.getSuppliers({
      per_page: 100,
      status: 'active',
      product_ids: [Number(inventoryItem.value.product_id)],
    })
    const supplierPayload = supplierResponse?.data ?? supplierResponse
    suppliers.value = Array.isArray(supplierPayload?.data) ? supplierPayload.data : Array.isArray(supplierPayload) ? supplierPayload : []
    suppliers.value = suppliers.value.filter((supplier: any) => String(supplier.status || 'active').toLowerCase() === 'active')
    if (!suppliers.value.length) pageError.value = 'No active supplier is linked to this product. Link the supplier to the product before requesting a supplier return.'
  } catch (error: any) {
    pageError.value = error?.response?.data?.message || error?.message || 'Unable to load the damaged item or supplier list.'
  } finally {
    suppliersLoading.value = false
    loading.value = false
  }
})
</script>
