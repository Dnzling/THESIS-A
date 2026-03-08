<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.purchase-orders' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create Purchase Order</h2>
        <p class="text-sm text-gray-500 mt-1">Prepare purchase order details</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-4" @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Supplier ID</label>
              <InputNumber v-model="form.supplier_id" :min="1" fluid />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Branch ID</label>
              <InputNumber v-model="form.branch_id" :min="1" fluid />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Order Date</label>
              <InputText v-model="form.order_date" placeholder="YYYY-MM-DD" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Expected Delivery Date</label>
              <InputText v-model="form.expected_delivery_date" placeholder="YYYY-MM-DD" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Payment Terms</label>
              <InputText v-model="form.payment_terms" placeholder="e.g., net_30" />
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Notes</label>
            <Textarea v-model="form.notes" rows="3" />
          </div>

          <div class="pt-2 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" text type="button" @click="router.push({ name: 'procurement.purchase-orders' })" />
            <Button label="Save PO" icon="pi pi-check" :loading="saving" type="submit" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const saving = ref(false)

const form = reactive<any>({
  supplier_id: 1,
  branch_id: 1,
  order_date: new Date().toISOString().slice(0, 10),
  expected_delivery_date: new Date().toISOString().slice(0, 10),
  payment_terms: 'net_30',
  notes: ''
})

const submitForm = async () => {
  saving.value = true
  try {
    await procurementService.createPurchaseOrder(form)
    router.push({ name: 'procurement.purchase-orders' })
  } catch (error) {
    console.error('Failed to create purchase order', error)
  } finally {
    saving.value = false
  }
}
</script>
