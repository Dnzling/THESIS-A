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
          <!-- Section 1: Basic Information -->
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
                <label class="text-sm font-semibold text-gray-700">Supplier Type <span class="text-red-500">*</span></label>
                <Select v-model="form.supplier_type" :options="supplierTypes" optionLabel="label" optionValue="value" placeholder="Select type" />
                <small v-if="errors.supplier_type" class="text-red-600">{{ errors.supplier_type }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Company Name</label>
                <InputText v-model="form.company_name" placeholder="Legal company name" />
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">TIN / Tax ID</label>
                <InputText v-model="form.tin" placeholder="Tax Identification Number" />
              </div>
            </div>
          </div>

          <Divider />

          <!-- Section 2: Contact Information -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-phone"></i> Contact Information
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Contact Person <span class="text-red-500">*</span></label>
                <InputText v-model="form.contact_person" placeholder="Name of contact person" />
                <small v-if="errors.contact_person" class="text-red-600">{{ errors.contact_person }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Email <span class="text-red-500">*</span></label>
                <InputText v-model="form.email" type="email" placeholder="contact@supplier.com" />
                <small v-if="errors.email" class="text-red-600">{{ errors.email }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Phone <span class="text-red-500">*</span></label>
                <InputText v-model="form.phone" placeholder="+63 2 XXXX XXXX" />
                <small v-if="errors.phone" class="text-red-600">{{ errors.phone }}</small>
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Mobile</label>
                <InputText v-model="form.mobile" placeholder="+63 9XX XXX XXXX" />
              </div>
            </div>
          </div>

          <Divider />

          <!-- Section 3: Address Information -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-map-marker"></i> Address Information
            </h3>
            <div class="flex flex-col gap-2 mb-4">
              <label class="text-sm font-semibold text-gray-700">Street Address</label>
              <Textarea v-model="form.address" rows="2" placeholder="Street address" />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">City/Municipality</label>
                <InputText v-model="form.city" placeholder="City" />
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Province</label>
                <InputText v-model="form.province" placeholder="Province" />
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Postal Code</label>
                <InputText v-model="form.postal_code" placeholder="Postal code" />
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Country</label>
                <InputText v-model="form.country" placeholder="Country" />
              </div>
            </div>
          </div>

          <Divider />

          <!-- Section 4: Business Terms -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-money-bill"></i> Business Terms
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Payment Terms</label>
                <Select v-model="form.payment_terms" :options="paymentTermsOptions" optionLabel="label" optionValue="value" placeholder="Select payment terms" />
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Credit Limit (₱)</label>
                <InputNumber v-model="form.credit_limit" :min="0" :use-grouping="true" :locale="'en-PH'" />
              </div>
            </div>
          </div>

          <Divider />

          <!-- Section 5: Status -->
          <div>
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
              <i class="pi pi-check-circle"></i> Status
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-gray-700">Status <span class="text-red-500">*</span></label>
                <Select v-model="form.status" :options="statuses" optionLabel="label" optionValue="value" />
                <small v-if="errors.status" class="text-red-600">{{ errors.status }}</small>
              </div>
            </div>
          </div>

          <!-- Errors Summary -->
          <div v-if="Object.keys(errors).length > 0" class="p-4 bg-red-50 border border-red-200 rounded">
            <p class="font-semibold text-red-900 mb-2">Please fix the following errors:</p>
            <ul class="text-sm text-red-800 space-y-1">
              <li v-for="(error, key) in errors" :key="key">
                <span class="font-medium">{{ key }}:</span> {{ error }}
              </li>
            </ul>
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

const statuses = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Blacklisted', value: 'blacklisted' }
]

const supplierTypes = [
  { label: 'Manufacturer', value: 'manufacturer' },
  { label: 'Wholesaler', value: 'wholesaler' },
  { label: 'Distributor', value: 'distributor' },
  { label: 'Importer', value: 'importer' },
  { label: 'Local Artisan', value: 'local_artisan' }
]

const paymentTermsOptions = [
  { label: 'Net 7 Days', value: 'net_7' },
  { label: 'Net 15 Days', value: 'net_15' },
  { label: 'Net 30 Days', value: 'net_30' },
  { label: 'Net 60 Days', value: 'net_60' },
  { label: 'Cash on Delivery', value: 'cash_on_delivery' },
  { label: 'Advance Payment', value: 'advance_payment' }
]

const form = reactive({
  supplier_name: '',
  supplier_type: '',
  company_name: '',
  contact_person: '',
  email: '',
  phone: '',
  mobile: '',
  address: '',
  city: '',
  province: '',
  postal_code: '',
  country: '',
  tin: '',
  payment_terms: '',
  credit_limit: 0,
  status: 'active' as 'active' | 'inactive' | 'blacklisted'
})

const errors = reactive<Record<string, string>>({})

const formProgress = computed(() => {
  let filled = 0
  const fields = [
    'supplier_name',
    'supplier_type',
    'contact_person',
    'email',
    'phone',
    'address',
    'city',
    'country',
    'status'
  ]
  
  fields.forEach(field => {
    if (form[field as keyof typeof form]) filled++
  })
  
  return Math.round((filled / fields.length) * 100)
})

const validateForm = (): boolean => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.supplier_name?.trim()) errors.supplier_name = 'Supplier name is required'
  if (!form.supplier_type) errors.supplier_type = 'Supplier type is required'
  if (!form.contact_person?.trim()) errors.contact_person = 'Contact person is required'
  if (!form.email?.trim()) errors.email = 'Email is required'
  if (!form.phone?.trim()) errors.phone = 'Phone is required'
  if (!form.status) errors.status = 'Status is required'
  
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
    const response = await procurementService.createSupplier(form)
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
