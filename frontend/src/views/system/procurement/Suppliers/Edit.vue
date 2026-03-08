<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.suppliers' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Edit Supplier</h2>
        <p class="text-sm text-gray-500 mt-1">Update supplier information</p>
      </div>
    </div>

    <div v-if="loading" class="space-y-4">
      <Skeleton height="220px" class="rounded-lg" />
      <Skeleton height="220px" class="rounded-lg" />
    </div>

    <Card v-else>
      <template #content>
        <form class="space-y-4" @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Supplier Name</label>
              <InputText v-model="form.supplier_name" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Company Name</label>
              <InputText v-model="form.company_name" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Contact Person</label>
              <InputText v-model="form.contact_person" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Phone</label>
              <InputText v-model="form.phone" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Email</label>
              <InputText v-model="form.email" />
            </div>
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Status</label>
              <Select v-model="form.status" :options="statuses" optionLabel="label" optionValue="value" />
            </div>
          </div>

          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Address</label>
            <Textarea v-model="form.address" rows="3" />
          </div>

          <div class="pt-2 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" text type="button" @click="router.push({ name: 'procurement.suppliers' })" />
            <Button label="Update Supplier" icon="pi pi-check" :loading="saving" type="submit" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import procurementService from '../../../../services/procurement.service'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const saving = ref(false)

const statuses = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Blacklisted', value: 'blacklisted' }
]

const form = reactive<any>({
  supplier_name: '',
  company_name: '',
  contact_person: '',
  email: '',
  phone: '',
  address: '',
  status: 'active'
})

const supplierId = Number(route.params.id)

const loadSupplier = async () => {
  loading.value = true
  try {
    const response = await procurementService.getSupplier(supplierId)
    Object.assign(form, response.data || {})
  } catch (error) {
    console.error('Failed to load supplier', error)
  } finally {
    loading.value = false
  }
}

const submitForm = async () => {
  saving.value = true
  try {
    await procurementService.updateSupplier(supplierId, form)
    router.push({ name: 'procurement.suppliers' })
  } catch (error) {
    console.error('Failed to update supplier', error)
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  loadSupplier()
})
</script>
