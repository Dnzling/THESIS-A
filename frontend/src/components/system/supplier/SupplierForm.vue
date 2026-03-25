<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <!-- Basic Info Section -->
    <div class="border-b pb-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Basic Information</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Supplier Name *</label>
          <InputText
            v-model="formData.supplier_name"
            placeholder="Enter supplier name"
            class="w-full"
            :class="{ 'ng-invalid': errors.supplier_name }"
          />
          <small v-if="errors.supplier_name" class="text-red-600">{{ errors.supplier_name }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
          <InputText
            v-model="formData.company_name"
            placeholder="Enter company name"
            class="w-full"
            :class="{ 'ng-invalid': errors.company_name }"
          />
          <small v-if="errors.company_name" class="text-red-600">{{ errors.company_name }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tax ID</label>
          <InputText
            v-model="formData.tax_id"
            placeholder="Enter tax ID"
            class="w-full"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
          <Select
            v-model="formData.category"
            :options="categoryOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select category"
            class="w-full"
            :class="{ 'ng-invalid': errors.category }"
          />
          <small v-if="errors.category" class="text-red-600">{{ errors.category }}</small>
        </div>
      </div>
    </div>

    <!-- Contact Section -->
    <div class="border-b pb-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Contact Information</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Contact Person *</label>
          <InputText
            v-model="formData.contact_person"
            placeholder="Enter contact person"
            class="w-full"
            :class="{ 'ng-invalid': errors.contact_person }"
          />
          <small v-if="errors.contact_person" class="text-red-600">{{ errors.contact_person }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
          <InputText
            v-model="formData.email"
            type="email"
            placeholder="Enter email"
            class="w-full"
            :class="{ 'ng-invalid': errors.email }"
          />
          <small v-if="errors.email" class="text-red-600">{{ errors.email }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Phone *</label>
          <InputText
            v-model="formData.phone"
            placeholder="Enter phone"
            class="w-full"
            :class="{ 'ng-invalid': errors.phone }"
            @blur="formatPhoneNumber"
          />
          <small v-if="errors.phone" class="text-red-600">{{ errors.phone }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
          <InputText
            v-model="formData.address"
            placeholder="Enter address"
            class="w-full"
            :class="{ 'ng-invalid': errors.address }"
          />
          <small v-if="errors.address" class="text-red-600">{{ errors.address }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
          <InputText
            v-model="formData.city"
            placeholder="Enter city"
            class="w-full"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
          <InputText
            v-model="formData.state"
            placeholder="Enter state"
            class="w-full"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code</label>
          <InputText
            v-model="formData.postal_code"
            placeholder="Enter postal code"
            class="w-full"
          />
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Country</label>
          <InputText
            v-model="formData.country"
            placeholder="Enter country"
            class="w-full"
          />
        </div>
      </div>
    </div>

    <!-- Payment Section -->
    <div class="border-b pb-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Terms</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Payment Terms *</label>
          <Select
            v-model="formData.payment_terms"
            :options="paymentTermsOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select payment terms"
            class="w-full"
            :class="{ 'ng-invalid': errors.payment_terms }"
          />
          <small v-if="errors.payment_terms" class="text-red-600">{{ errors.payment_terms }}</small>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Bank Details</label>
          <InputTextarea
            v-model="formData.bank_details"
            placeholder="Enter bank details"
            rows="3"
            class="w-full"
          />
        </div>
      </div>
    </div>

    <!-- Tax Profile -->
    <div class="border-b pb-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Tax Profile</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Default VAT / Tax Rate (%)</label>
          <InputNumber
            v-model="formData.default_tax_rate"
            mode="decimal"
            :min="0"
            :max="100"
            :step="0.25"
            class="w-full"
            :class="{ 'ng-invalid': errors.default_tax_rate }"
          />
          <small v-if="errors.default_tax_rate" class="text-red-600">{{ errors.default_tax_rate }}</small>
        </div>
        <div class="flex items-center gap-3 pt-7">
          <Checkbox v-model="formData.is_tax_exempt" binary inputId="taxExempt" />
          <label for="taxExempt" class="text-sm font-medium text-gray-700">Tax Exempt</label>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tax Note</label>
          <InputTextarea
            v-model="formData.tax_note"
            placeholder="Notes about tax treatment (optional)"
            rows="3"
            class="w-full"
          />
        </div>
      </div>
    </div>

    <!-- Status Section (Edit Mode) -->
    <div v-if="mode === 'edit'" class="border-b pb-6">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Status</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <Select
            v-model="formData.status"
            :options="statusOptions"
            optionLabel="label"
            optionValue="value"
            placeholder="Select status"
            class="w-full"
          />
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex justify-end gap-2 pt-4 border-t">
      <Button
        label="Cancel"
        icon="pi pi-times"
        class="p-button-text"
        @click="$emit('close')"
      />
      <Button
        :label="mode === 'edit' ? 'Update' : 'Create'"
        icon="pi pi-check"
        :loading="loading"
        @click="handleSubmit"
      />
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import supplierService from '../../../services/supplier.service'

const props = defineProps({
  supplier: {
    type: Object,
    default: null,
  },
  mode: {
    type: String,
    default: 'create',
  },
})

const emit = defineEmits(['save', 'close'])

const toast = useToast()
const loading = ref(false)

const formData = ref({
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
  category: '',
  payment_terms: '',
  status: 'active',
  tax_id: '',
  bank_details: '',
  default_tax_rate: null,
  is_tax_exempt: false,
  tax_note: '',
})

const errors = ref<any>({})

const categoryOptions = ref([
  { label: 'Raw Materials', value: 'Raw Materials' },
  { label: 'Furniture', value: 'Furniture' },
  { label: 'Accessories', value: 'Accessories' },
  { label: 'Services', value: 'Services' },
  { label: 'Equipment', value: 'Equipment' },
])

const paymentTermsOptions = ref([
  { label: 'Net 30', value: 'Net 30' },
  { label: 'Net 60', value: 'Net 60' },
  { label: 'Net 90', value: 'Net 90' },
  { label: 'COD', value: 'COD' },
  { label: 'Advance Payment', value: 'Advance Payment' },
  { label: 'Installment', value: 'Installment' },
])

const statusOptions = ref([
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Blacklisted', value: 'blacklisted' },
])

const validateForm = () => {
  errors.value = {}

  if (!formData.value.supplier_name) errors.value.supplier_name = 'Supplier name is required'
  if (!formData.value.company_name) errors.value.company_name = 'Company name is required'
  if (!formData.value.contact_person) errors.value.contact_person = 'Contact person is required'
  if (!formData.value.email) errors.value.email = 'Email is required'
  if (!formData.value.email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) errors.value.email = 'Invalid email format'
  if (!formData.value.phone) errors.value.phone = 'Phone is required'
  if (!formData.value.address) errors.value.address = 'Address is required'
  if (!formData.value.category) errors.value.category = 'Category is required'
  if (!formData.value.payment_terms) errors.value.payment_terms = 'Payment terms are required'

  return Object.keys(errors.value).length === 0
}

const formatPhoneNumber = () => {
  const digits = formData.value.phone.replace(/\D/g, '')
  if (digits.length === 10) {
    formData.value.phone = `(${digits.slice(0, 3)}) ${digits.slice(3, 6)}-${digits.slice(6)}`
  }
}

const handleSubmit = async () => {
  if (!validateForm()) {
    toast.add({
      severity: 'error',
      summary: 'Validation Error',
      detail: 'Please fix all errors before submitting',
      life: 3000,
    })
    return
  }

  loading.value = true

  try {
    if (props.mode === 'edit') {
      await supplierService.updateSupplier(props.supplier.id, formData.value)
    } else {
      await supplierService.createSupplier(formData.value)
    }

    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: `Supplier ${props.mode === 'edit' ? 'updated' : 'created'} successfully`,
      life: 3000,
    })

    emit('save')
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.message || 'An error occurred',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

watch(
  () => props.supplier,
  (newSupplier) => {
    if (newSupplier) {
      formData.value = { ...newSupplier }
    } else {
      formData.value = {
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
        category: '',
        payment_terms: '',
        status: 'active',
        tax_id: '',
        bank_details: '',
        default_tax_rate: null,
        is_tax_exempt: false,
        tax_note: '',
      }
    }
  },
  { immediate: true }
)
</script>

<style scoped>
.ng-invalid {
  border-color: #dc2626;
}
</style>
