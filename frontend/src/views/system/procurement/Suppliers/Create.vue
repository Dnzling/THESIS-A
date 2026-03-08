<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.suppliers' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create Supplier</h2>
        <p class="text-sm text-gray-500 mt-1">Add a new supplier profile</p>
      </div>
    </div>

    <Card>
      <template #content>
        <form class="space-y-4" @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Supplier Name</label>
              <InputText v-model="form.supplier_name" placeholder="Supplier name" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Company Name</label>
              <InputText v-model="form.company_name" placeholder="Company" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Contact Person</label>
              <InputText v-model="form.contact_person" placeholder="Contact person" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Phone</label>
              <InputText v-model="form.phone" placeholder="Phone number" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Email</label>
              <InputText v-model="form.email" placeholder="Email" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Status</label>
              <Select v-model="form.status" :options="statuses" optionLabel="label" optionValue="value" />
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Address</label>
            <Textarea v-model="form.address" rows="3" placeholder="Address" />
          </div>

          <div class="pt-2 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" text type="button" @click="router.push({ name: 'procurement.suppliers' })" />
            <Button label="Save Supplier" icon="pi pi-check" :loading="saving" type="submit" />
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

const statuses = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Blacklisted', value: 'blacklisted' }
]

const form = reactive({
  supplier_name: '',
  company_name: '',
  contact_person: '',
  email: '',
  phone: '',
  address: '',
  status: 'active' as 'active' | 'inactive' | 'blacklisted'
})

const submitForm = async () => {
  saving.value = true
  try {
    await procurementService.createSupplier(form)
    router.push({ name: 'procurement.suppliers' })
  } catch (error) {
    console.error('Failed to create supplier', error)
  } finally {
    saving.value = false
  }
}
</script>
