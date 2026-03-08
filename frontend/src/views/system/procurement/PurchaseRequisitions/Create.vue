<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.purchase-requisitions' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create Purchase Requisition</h2>
        <p class="text-sm text-gray-500 mt-1">Prepare requisition for submission</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-4" @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Branch ID</label>
              <InputNumber v-model="form.branch_id" :min="1" fluid />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Type</label>
              <Select v-model="form.requisition_type" :options="types" optionLabel="label" optionValue="value" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Required Date</label>
              <InputText v-model="form.required_date" placeholder="YYYY-MM-DD" />
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Reason</label>
            <Textarea v-model="form.reason" rows="3" />
          </div>

          <div class="pt-2 flex justify-end gap-2">
            <Button label="Save Draft" severity="secondary" outlined type="button" :loading="saving" @click="saveDraft" />
            <Button label="Save & Submit" icon="pi pi-check" :loading="saving" type="submit" />
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

const types = [
  { label: 'Regular', value: 'regular' },
  { label: 'Urgent', value: 'urgent' },
  { label: 'New Product', value: 'new_product' },
  { label: 'Seasonal', value: 'seasonal' },
  { label: 'Emergency', value: 'emergency' }
]

const form = reactive<any>({
  branch_id: 1,
  requisition_type: 'regular',
  required_date: new Date().toISOString().slice(0, 10),
  reason: '',
  items: []
})

const save = async (submit = true) => {
  saving.value = true
  try {
    const response = await procurementService.createPurchaseRequisition(form)
    const id = response.data?.id
    if (submit && id) {
      await procurementService.submitPurchaseRequisition(id)
    }
    router.push({ name: 'procurement.purchase-requisitions' })
  } catch (error) {
    console.error('Failed to save requisition', error)
  } finally {
    saving.value = false
  }
}

const saveDraft = async () => {
  await save(false)
}

const submitForm = async () => {
  await save(true)
}
</script>
