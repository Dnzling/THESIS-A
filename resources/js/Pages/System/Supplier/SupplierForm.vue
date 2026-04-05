<template>
  <div class="space-y-6">
    <form @submit.prevent="submitForm" class="space-y-6">
      <!-- Section 1: Basic Information -->
      <div class="border-b pb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <i class="pi pi-info-circle text-blue-600"></i>
          Basic Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">
              <span class="text-red-500">*</span> Supplier Name
            </label>
            <InputText
              v-model="form.supplier_name"
              placeholder="Enter supplier name..."
              class="w-full"
              :class="{ 'ng-invalid ng-touched': errors.supplier_name }"
            />
            <small v-if="errors.supplier_name" class="text-red-500">{{ errors.supplier_name }}</small>
          </div>

          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">
              <span class="text-red-500">*</span> Company Name
            </label>
            <InputText
              v-model="form.company_name"
              placeholder="Enter company name..."
              class="w-full"
              :class="{ 'ng-invalid ng-touched': errors.company_name }"
            />
            <small v-if="errors.company_name" class="text-red-500">{{ errors.company_name }}</small>
          </div>

          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">
              <span class="text-red-500">*</span> Tax ID
            </label>
            <InputText
              v-model="form.tax_id"
              placeholder="Enter tax ID..."
              class="w-full"
            />
          </div>

          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">
              <span class="text-red-500">*</span> Category
            </label>
            <Select
              v-model="form.category"
              :options="categoryOptions"
              option-label="label"
              option-value="value"
              placeholder="Select category..."
              class="w-full"
              :class="{ 'ng-invalid ng-touched': errors.category }"
            />
            <small v-if="errors.category" class="text-red-500">{{ errors.category }}</small>
          </div>
        </div>
      </div>

      <!-- Section 2: Contact Information -->
      <div class="border-b pb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <i class="pi pi-phone text-purple-600"></i>
          Contact Information
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">
              <span class="text-red-500">*</span> Contact Person
            </label>
            <InputText
              v-model="form.contact_person"
              placeholder="Enter contact person name..."
              class="w-full"
              :class="{ 'ng-invalid ng-touched': errors.contact_person }"
            />
            <small v-if="errors.contact_person" class="text-red-500">{{ errors.contact_person }}</small>
          </div>

          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">
              <span class="text-red-500">*</span> Email
            </label>
            <InputText
              v-model="form.email"
              type="email"
              placeholder="Enter email address..."
              class="w-full"
              :class="{ 'ng-invalid ng-touched': errors.email }"
            />
            <small v-if="errors.email" class="text-red-500">{{ errors.email }}</small>
          </div>

          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">
              <span class="text-red-500">*</span> Phone
            </label>
            <InputText
              v-model="form.phone"
              placeholder="Enter phone number..."
              class="w-full"
              @blur="form.phone = formatPhoneNumber(form.phone)"
              :class="{ 'ng-invalid ng-touched': errors.phone }"
            />
            <small v-if="errors.phone" class="text-red-500">{{ errors.phone }}</small>
          </div>

          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">Address</label>
            <InputText
              v-model="form.address"
              placeholder="Enter address..."
              class="w-full"
              :class="{ 'ng-invalid ng-touched': errors.address }"
            />
            <small v-if="errors.address" class="text-red-500">{{ errors.address }}</small>
          </div>

          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">City</label>
            <InputText
              v-model="form.city"
              placeholder="Enter city..."
              class="w-full"
            />
          </div>

          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">State</label>
            <InputText
              v-model="form.state"
              placeholder="Enter state/province..."
              class="w-full"
            />
          </div>

          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">Postal Code</label>
            <InputText
              v-model="form.postal_code"
              placeholder="Enter postal code..."
              class="w-full"
            />
          </div>

          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">Country</label>
            <InputText
              v-model="form.country"
              placeholder="Enter country..."
              class="w-full"
            />
          </div>
        </div>
      </div>

      <!-- Section 3: Payment Terms -->
      <div class="border-b pb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <i class="pi pi-credit-card text-green-600"></i>
          Payment Terms
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">
              <span class="text-red-500">*</span> Payment Terms
            </label>
            <Select
              v-model="form.payment_terms"
              :options="paymentTermsOptions"
              editable
              placeholder="Select or type payment terms..."
              class="w-full"
              :class="{ 'ng-invalid ng-touched': errors.payment_terms }"
            />
            <small v-if="errors.payment_terms" class="text-red-500">{{ errors.payment_terms }}</small>
          </div>

          <!-- Bank details removed from registration -->
        </div>
      </div>

      <!-- Section 4: Status (Edit Mode Only) -->
      <div v-if="mode === 'edit'" class="border-b pb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
          <i class="pi pi-check-circle text-orange-600"></i>
          Status
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="text-sm font-semibold text-gray-700 block mb-2">Supplier Status</label>
            <Select
              v-model="form.status"
              :options="statusOptions"
              option-label="label"
              option-value="value"
              placeholder="Select status..."
              class="w-full"
            />
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2 justify-end border-t pt-6">
        <Button
          label="Cancel"
          icon="pi pi-times"
          @click="$emit('close')"
          class="p-button-text"
        />
        <Button
          :label="mode === 'create' ? 'Create Supplier' : 'Update Supplier'"
          icon="pi pi-check"
          type="submit"
          :loading="saving"
        />
      </div>
    </form>

    <Toast />
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import supplierService from '../../../services/supplier.service'

const emit = defineEmits(['save', 'close'])
const props = defineProps<{
  initialData?: any
  mode: 'create' | 'edit'
}>()

const toast = useToast()

const saving = ref(false)
const errors = ref<Record<string, string>>({})

const form = ref({
  supplier_name: '',
  company_name: '',
  contact_person: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  postal_code: '',
  country: '',
  payment_terms: '',
  tax_id: '',
  category: '',
  status: 'active'
})

const categoryOptions = [
  { label: 'Raw Materials', value: 'raw_materials' },
  { label: 'Furniture', value: 'furniture' },
  { label: 'Accessories', value: 'accessories' },
  { label: 'Services', value: 'services' }
]

const paymentTermsOptions = [
  { label: 'Net 15', value: 'net_15' },
  { label: 'Net 30', value: 'net_30' },
  { label: 'Net 45', value: 'net_45' },
  { label: 'Net 60', value: 'net_60' },
  { label: 'COD (Cash on Delivery)', value: 'cod' },
  { label: 'Prepaid', value: 'prepaid' }
]

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Blacklisted', value: 'blacklisted' }
]

watch(
  () => props.initialData,
  (newData) => {
    if (newData) {
      form.value = { ...newData }
    }
  }
)

const validateSupplierForm = (data: any): { valid: boolean; errors: Record<string, string> } => {
  const validationErrors: Record<string, string> = {}

  if (!data.supplier_name?.trim()) validationErrors.supplier_name = 'Supplier name is required'
  if (!data.company_name?.trim()) validationErrors.company_name = 'Company name is required'
  if (!data.contact_person?.trim()) validationErrors.contact_person = 'Contact person is required'
  if (!data.email?.trim()) validationErrors.email = 'Email is required'
  if (data.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) validationErrors.email = 'Email format is invalid'
  if (!data.phone?.trim()) validationErrors.phone = 'Phone number is required'
  if (!data.address?.trim()) validationErrors.address = 'Address is required'
  if (!data.category) validationErrors.category = 'Category is required'
  if (!data.payment_terms?.trim()) validationErrors.payment_terms = 'Payment terms are required'

  return {
    valid: Object.keys(validationErrors).length === 0,
    errors: validationErrors
  }
}

const formatPhoneNumber = (phone: string): string => {
  const digits = phone.replace(/\D/g, '')
  if (digits.length === 10) {
    return `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`
  }
  return phone
}

const createSupplier = async (data: any) => {
  return supplierService.createSupplier(data)
}

const updateSupplier = async (id: number, data: any) => {
  return supplierService.updateSupplier(id, data)
}

const submitForm = async () => {
  const validation = validateSupplierForm(form.value)
  if (!validation.valid) {
    errors.value = validation.errors
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please fix the errors in the form',
      life: 3000
    })
    return
  }

  errors.value = {}
  saving.value = true

  try {
    let response
    if (props.mode === 'create') {
      response = await createSupplier(form.value)
    } else {
      response = await updateSupplier(props.initialData.id, form.value)
    }

    if (response?.success) {
      toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `Supplier ${props.mode === 'create' ? 'created' : 'updated'} successfully`,
        life: 3000
      })
      emit('save')
    } else {
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: response?.message || 'Failed to save supplier',
        life: 3000
      })
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'An error occurred',
      life: 3000
    })
  } finally {
    saving.value = false
  }
}
</script>
