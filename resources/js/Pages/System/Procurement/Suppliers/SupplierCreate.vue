<template>
  <div class="max-w-7xl mx-auto space-y-6 pb-6">
    <div class="flex items-center gap-3">
      <Button icon="pi pi-arrow-left" text rounded @click="router.push({ name: 'procurement.suppliers' })" />
      <div>
        <h2 class="text-2xl font-bold text-gray-800">Create Supplier</h2>
        <p class="text-sm text-gray-500 mt-1">Add a new supplier profile with contact and business information</p>
      </div>
    </div>

    <Card>
      <template #header>
        <ProgressBar :value="formProgress" class="w-full"></ProgressBar>
      </template>
      <template #content>
        <form class="space-y-6" @submit.prevent="submitForm">
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-building"></i> Basic Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Supplier Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.supplier_name" placeholder="e.g., Premium Furniture Co." />
                <small v-if="errors.supplier_name" class="text-red-600">{{ errors.supplier_name }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
                <InputText v-model="form.email" type="email" placeholder="contact@supplier.com" />
                <small v-if="errors.email" class="text-red-600">{{ errors.email }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Contact Person First Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.contact_person_first" placeholder="First name" />
                <small v-if="errors.contact_person_first" class="text-red-600">{{ errors.contact_person_first }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Contact Person Last Name <span class="text-red-500">*</span></label>
                <InputText v-model="form.contact_person_last" placeholder="Last name" />
                <small v-if="errors.contact_person_last" class="text-red-600">{{ errors.contact_person_last }}</small>
              </div>
              
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="pt-4 flex justify-end gap-2 border-t">
            <Button label="Cancel" severity="secondary" text type="button" @click="router.push({ name: 'procurement.suppliers' })" />
            <Button label="Save Supplier" icon="pi pi-check" :loading="saving" type="submit" severity="success" />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const toast = useToast()
const saving = ref(false)

const form = reactive({
  supplier_name: '',
  contact_person: '',
  contact_person_first: '',
  contact_person_last: '',
  email: '',
})

const errors = reactive<Record<string, string>>({})

const formProgress = computed(() => {
  let filled = 0
  const fields = ['supplier_name', 'contact_person_first', 'contact_person_last', 'email']
  fields.forEach(field => { if (form[field as keyof typeof form]) filled++ })
  return Math.round((filled / fields.length) * 100)
})

const validateForm = (): boolean => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.supplier_name?.trim()) errors.supplier_name = 'Supplier name is required'
  if (!form.contact_person_first?.trim()) errors.contact_person_first = 'Contact person first name is required'
  if (!form.contact_person_last?.trim()) errors.contact_person_last = 'Contact person last name is required'
  if (!form.email?.trim()) errors.email = 'Email is required'
  if (form.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
    errors.email = 'Please enter a valid email address'
  }

  return Object.keys(errors).length === 0
}

const submitForm = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please fix all errors before submitting',
      life: 3000
    })
    return
  }

  saving.value = true
  try {
    // combine first/last into contact_person for backend
    form.contact_person = [form.contact_person_first, form.contact_person_last].filter(Boolean).join(' ').trim()
    const payload = { ...form }
    const response = await procurementService.createSupplier(payload)
    const supplierId = response.id || response.data?.id
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Supplier created! Now add a contract for this supplier.',
      life: 3000
    })
    
    // Redirect to contract creation with supplier pre-selected
    setTimeout(() => {
      router.push({ 
        name: 'procurement.supplier-contracts.create',
        query: { supplier_id: supplierId }
      })
    }, 1500)
  } catch (error: any) {
    console.error('Failed to create supplier', error)
    const errorMessage = error.response?.data?.message || 'Failed to create supplier'
    const errorDetails = error.response?.data?.errors
    
    if (errorDetails) {
      Object.entries(errorDetails).forEach(([key, messages]: [string, any]) => {
        errors[key] = Array.isArray(messages) ? messages[0] : messages
      })
    }
    
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: errorMessage,
      life: 4000
    })
  } finally {
    saving.value = false
  }
}
</script>
