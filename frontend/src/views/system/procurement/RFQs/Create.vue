<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.rfqs' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create RFQ</h2>
        <p class="text-sm text-gray-500 mt-1">Prepare request for quotation details</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-4" @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Title</label>
              <InputText v-model="form.title" placeholder="RFQ title" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Issue Date</label>
              <InputText v-model="form.issue_date" placeholder="YYYY-MM-DD" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Deadline Date</label>
              <InputText v-model="form.deadline_date" placeholder="YYYY-MM-DD" />
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Description</label>
            <Textarea v-model="form.description" rows="3" />
          </div>

          <div class="pt-2 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" text type="button" @click="router.push({ name: 'procurement.rfqs' })" />
            <Button label="Save RFQ" icon="pi pi-check" :loading="saving" type="submit" />
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

const form = reactive({
  title: '',
  description: '',
  issue_date: new Date().toISOString().slice(0, 10),
  deadline_date: new Date().toISOString().slice(0, 10)
})

const submitForm = async () => {
  saving.value = true
  try {
    await procurementService.createRFQ(form)
    router.push({ name: 'procurement.rfqs' })
  } catch (error) {
    console.error('Failed to create RFQ', error)
  } finally {
    saving.value = false
  }
}
</script>
