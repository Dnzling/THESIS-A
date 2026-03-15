<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Create Supplier Contract</h1>
        <p class="text-gray-600 mt-1">Create a new contract with a supplier including terms and document attachment</p>
      </div>
      <Button :label="route.query.supplier_id ? 'Skip for Now' : 'Back to List'" icon="pi pi-arrow-left" severity="secondary" @click="skipOrCancel()" />
    </div>

    <!-- Info Banner for New Supplier Flow -->
    <div v-if="route.query.supplier_id && suppliers.length > 0" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-3">
      <i class="pi pi-info-circle text-blue-600 text-xl mt-0.5"></i>
      <div>
        <p class="font-semibold text-blue-900">Great! Supplier Created</p>
        <p class="text-sm text-blue-800 mt-1">
          Now let's create the first contract for this supplier. Fill in the contract details below to establish terms and conditions.
        </p>
      </div>
    </div>

    <form @submit.prevent="submitForm" class="max-w-4xl mx-auto">
      <ProgressBar :value="formProgress" class="mb-6" />

      <!-- Section 1: Supplier & Contract Basic Info -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-3">
            <i class="pi pi-building text-2xl text-blue-600"></i>
            <span>Supplier & Contract Information</span>
          </div>
        </template>

        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">


            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Contract Title <span class="text-red-500">*</span></label>
              <InputText v-model="form.contract_title" placeholder="e.g., Annual Furniture Supply Agreement 2026" class="w-full" />
              <small v-if="errors.contract_title" class="text-red-600">{{ errors.contract_title }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Contract Type <span class="text-red-500">*</span></label>
              <Select v-model="form.contract_type" :options="contractTypes" optionLabel="label" optionValue="value"
                placeholder="Select Contract Type" class="w-full" />
              <small v-if="errors.contract_type" class="text-red-600">{{ errors.contract_type }}</small>
              <small class="text-gray-600 mt-2" v-if="contractTypeDescription">{{ contractTypeDescription }}</small>
            </div>
          </div>
        </template>
      </Card>

      <!-- Section 2: Contract Duration -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-3">
            <i class="pi pi-calendar text-2xl text-green-600"></i>
            <span>Contract Duration</span>
          </div>
        </template>

        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Start Date <span class="text-red-500">*</span></label>
              <DatePicker v-model="form.start_date" date-format="yy-mm-dd" placeholder="Select Start Date" 
                :min-date="new Date()" class="w-full" />
              <small v-if="errors.start_date" class="text-red-600">{{ errors.start_date }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">End Date <span class="text-red-500">*</span></label>
              <DatePicker v-model="form.end_date" date-format="yy-mm-dd" placeholder="Select End Date"
                :min-date="form.start_date ? new Date(form.start_date.getTime() + 86400000) : new Date()" class="w-full" />
              <small v-if="errors.end_date" class="text-red-600">{{ errors.end_date }}</small>
              <small v-else-if="form.start_date && form.end_date" class="text-green-600">
                Duration: {{ contractDurationDays }} days
              </small>
            </div>
          </div>
        </template>
      </Card>

      <!-- Section 3: Financial Terms -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-3">
            <i class="pi pi-money-bill text-2xl text-orange-600"></i>
            <span>Financial Terms</span>
          </div>
        </template>

        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Minimum Order Value <span class="text-red-500">*</span></label>
              <div class="flex items-center gap-2">
                <span class="text-gray-600">₱</span>
                <InputNumber v-model="form.minimum_order_value" :min="0" placeholder="0.00" 
                  :use-grouping="true" :locale="'en-PH'" class="w-full" />
              </div>
              <small v-if="errors.minimum_order_value" class="text-red-600">{{ errors.minimum_order_value }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Discount Percentage <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.discount_percentage" :min="0" :max="100" placeholder="0" 
                suffix="%" class="w-full" />
              <small v-if="errors.discount_percentage" class="text-red-600">{{ errors.discount_percentage }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Payment Terms (Days) <span class="text-red-500">*</span></label>
              <div class="p-3 bg-gray-100 rounded border border-gray-300">
                <p class="text-lg font-semibold text-gray-900">
                  {{ form.payment_terms_days ? `Net ${form.payment_terms_days} Days` : 'Select a supplier first' }}
                </p>
                <p class="text-xs text-gray-600 mt-1">Auto-filled from supplier's payment terms</p>
              </div>
              <small v-if="errors.payment_terms_days" class="text-red-600">{{ errors.payment_terms_days }}</small>
              <small class="text-gray-600 mt-2" v-if="form.payment_terms_days">
                <i class="pi pi-calendar mr-1"></i>Due by {{ paymentDueDate }}
              </small>
            </div>
          </div>

          <Divider class="my-6" />

          <div class="p-4 bg-blue-50 border border-blue-200 rounded">
            <p class="text-sm text-blue-900">
              <i class="pi pi-info-circle mr-2"></i>
              <strong>Summary:</strong> Minimum order ₱{{ formatCurrency(form.minimum_order_value) }} with {{ form.discount_percentage }}% discount, payment due in {{ form.payment_terms_days }} days
            </p>
          </div>
        </template>
      </Card>

      <!-- Section 4: Terms & Conditions -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-3">
            <i class="pi pi-file-edit text-2xl text-purple-600"></i>
            <span>Terms & Conditions</span>
          </div>
        </template>

        <template #content>
          <div class="flex flex-col gap-2">
            <label class="text-sm font-semibold text-gray-700">Terms & Conditions</label>
            <Textarea v-model="form.terms_conditions" rows="6" 
              placeholder="Enter contract terms and conditions here..." class="w-full" />
            <small class="text-gray-600">{{ form.terms_conditions?.length || 0 }} / 5000 characters</small>
          </div>
        </template>
      </Card>

      <!-- Section 5: Document Attachment -->
      <Card class="mb-6">
        <template #title>
          <div class="flex items-center gap-3">
            <i class="pi pi-file text-2xl text-red-600"></i>
            <span>Contract Document (Optional)</span>
          </div>
        </template>

        <template #content>
          <FileUpload v-if="!contractFile" name="contract_file" 
            @select="onFileSelect" 
            :multiple="false" 
            accept=".pdf,.doc,.docx"
            :showUploadButton="false"
            :showCancelButton="false"
            chooseLabel="📎 Select PDF or Document"
            class="w-full" />
          
          <div v-else class="p-4 bg-green-50 border border-green-200 rounded flex items-center justify-between">
            <div class="flex items-center gap-3">
              <i class="pi pi-file-pdf text-2xl text-red-600"></i>
              <div>
                <p class="font-semibold text-green-900">{{ contractFile.name }}</p>
                <p class="text-sm text-gray-600">{{ formatFileSize(contractFile.size) }}</p>
              </div>
            </div>
            <Button icon="pi pi-times" text rounded severity="danger" @click="contractFile = null" />
          </div>
        </template>
      </Card>

      <!-- Action Buttons -->
      <div class="flex gap-3 justify-end">
        <Button 
          :label="route.query.supplier_id ? 'Skip for Now' : 'Cancel'" 
          severity="secondary" 
          @click="skipOrCancel()" />
        <Button label="Save as Draft" severity="warning" icon="pi pi-save" @click="saveDraft" :loading="submitting" />
        <Button label="Create Contract" severity="success" icon="pi pi-check" @click="submitForm" :loading="submitting" />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import procurementService from '../../../../services/procurement.service'

const router = useRouter()
const route = useRoute()
const toast = useToast()
const loading = ref(false)
const submitting = ref(false)
const suppliers = ref<any[]>([])
const contractFile = ref<File | null>(null)
const selectedSupplierName = ref<string>('Not Selected')

const form = reactive({
  supplier_id: null,
  contract_title: '',
  contract_type: 'volume_discount',
  start_date: null,
  end_date: null,
  minimum_order_value: 0,
  discount_percentage: 0,
  payment_terms_days: 30,
  terms_conditions: '',
  contract_file_path: null,
})

const errors = reactive<Record<string, string>>({})

const contractTypes = [
  { label: 'Fixed Price', value: 'fixed_price', description: 'Single fixed price for contracted items' },
  { label: 'Volume Discount', value: 'volume_discount', description: 'Discount based on order quantity/value' },
  { label: 'Consignment', value: 'consignment', description: 'Payment on sale terms' },
  { label: 'Exclusive', value: 'exclusive', description: 'Sole supplier arrangement' },
]

const contractTypeDescription = computed(() => {
  const type = contractTypes.find(t => t.value === form.contract_type)
  return type?.description || ''
})

const formProgress = computed(() => {
  let filled = 0
  let total = 9
  
  if (form.supplier_id) filled++
  if (form.contract_title) filled++
  if (form.contract_type) filled++
  if (form.start_date) filled++
  if (form.end_date) filled++
  if (form.minimum_order_value > 0) filled++
  if (form.discount_percentage) filled++
  if (form.payment_terms_days) filled++
  if (form.terms_conditions) filled++
  
  return Math.round((filled / total) * 100)
})

const contractDurationDays = computed(() => {
  if (!form.start_date || !form.end_date) return 0
  const start = new Date(form.start_date)
  const end = new Date(form.end_date)
  return Math.ceil((end.getTime() - start.getTime()) / (1000 * 3600 * 24))
})

const paymentDueDate = computed(() => {
  if (!form.start_date || !form.payment_terms_days) return 'N/A'
  const dueDate = new Date(form.start_date)
  dueDate.setDate(dueDate.getDate() + form.payment_terms_days)
  return dueDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
})

const formatCurrency = (value: number): string => {
  return (value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

const onFileSelect = (event: any) => {
  contractFile.value = event.files[0]
}

const onSupplierChange = () => {
  // Auto-fill payment terms from supplier's payment_terms field
  if (form.supplier_id) {
    const selectedSupplier = suppliers.value.find(s => s.id === form.supplier_id)
    if (selectedSupplier && selectedSupplier.payment_terms) {
      // Parse payment_terms enum values to days
      const paymentTermsMap: Record<string, number> = {
        'net_7': 7,
        'net_15': 15,
        'net_30': 30,
        'net_60': 60,
        'cash_on_delivery': 0,
        'advance_payment': 0
      }
      
      const paymentTermsValue = selectedSupplier.payment_terms.toString().toLowerCase()
      const days = paymentTermsMap[paymentTermsValue]
      if (days !== undefined) {
        form.payment_terms_days = days
      } else {
        // Fallback: extract numbers from string (for backward compatibility)
        const numbers = paymentTermsValue.match(/\d+/)
        if (numbers && numbers[0]) {
          form.payment_terms_days = parseInt(numbers[0])
        }
      }
    }
  }
}

const validateForm = (): boolean => {
  Object.keys(errors).forEach(key => delete errors[key])

  if (!form.supplier_id) errors.supplier_id = 'Please select a supplier'
  if (!form.contract_title) errors.contract_title = 'Please enter contract title'
  if (!form.contract_type) errors.contract_type = 'Please select contract type'
  if (!form.start_date) errors.start_date = 'Please select start date'
  if (!form.end_date) errors.end_date = 'Please select end date'
  if (form.start_date && form.end_date && form.end_date <= form.start_date) {
    errors.end_date = 'End date must be after start date'
  }
  if (form.minimum_order_value < 0) errors.minimum_order_value = 'Minimum order value cannot be negative'
  if (form.discount_percentage < 0 || form.discount_percentage > 100) {
    errors.discount_percentage = 'Discount must be between 0 and 100'
  }
  if (!form.payment_terms_days) errors.payment_terms_days = 'Please enter payment terms'

  return Object.keys(errors).length === 0
}

const saveDraft = async () => {
  if (!form.supplier_id || !form.contract_title) {
    toast.add({ severity: 'warn', summary: 'Incomplete', detail: 'Please fill at least Supplier and Title', life: 3000 })
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    Object.entries(form).forEach(([key, value]) => {
      if (value !== null && value !== '') {
        if (value instanceof Date) {
          formData.append(key, value.toISOString().split('T')[0])
        } else {
          formData.append(key, String(value))
        }
      }
    })
    formData.append('status', 'draft')
    if (contractFile.value) {
      formData.append('contract_file', contractFile.value)
    }

    await procurementService.createSupplierContract(formData)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Contract saved as draft',
      life: 3000,
    })
    
    // If coming from supplier creation flow, redirect to suppliers list
    const redirectName = route.query.supplier_id ? 'procurement.suppliers' : 'procurement.supplier-contracts.index'
    setTimeout(() => router.push({ name: redirectName }), 1500)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to save contract',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const submitForm = async () => {
  if (!validateForm()) {
    toast.add({ severity: 'error', summary: 'Validation Error', detail: 'Please fix all errors', life: 3000 })
    return
  }

  submitting.value = true
  try {
    const formData = new FormData()
    Object.entries(form).forEach(([key, value]) => {
      if (value !== null && value !== '') {
        if (value instanceof Date) {
          formData.append(key, value.toISOString().split('T')[0])
        } else {
          formData.append(key, String(value))
        }
      }
    })
    formData.append('status', 'active')
    if (contractFile.value) {
      formData.append('contract_file', contractFile.value)
    }

    await procurementService.createSupplierContract(formData)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Contract created successfully',
      life: 3000,
    })
    
    // If coming from supplier creation flow, redirect to suppliers list
    const redirectName = route.query.supplier_id ? 'procurement.suppliers' : 'procurement.supplier-contracts.index'
    setTimeout(() => router.push({ name: redirectName }), 1500)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to create contract',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const skipOrCancel = () => {
  if (route.query.supplier_id) {
    // Coming from supplier creation - go to suppliers list
    router.push({ name: 'procurement.suppliers' })
  } else {
    // Regular cancel - go back to contracts list
    router.push({ name: 'procurement.supplier-contracts.index' })
  }
}

onMounted(async () => {
  try {
    // Auto-select supplier if passed from supplier creation flow
    if (route.query.supplier_id) {
      form.supplier_id = parseInt(route.query.supplier_id as string)
      
      // Fetch supplier details to display name
      const response = await procurementService.getSuppliers({ per_page: 100 })
      suppliers.value = response.data?.data || []
      const supplier = suppliers.value.find(s => s.id === form.supplier_id)
      if (supplier) {
        selectedSupplierName.value = supplier.supplier_name
        onSupplierChange()
      }
      
      toast.add({
        severity: 'info',
        summary: 'Create Contract',
        detail: 'Supplier selected. Fill in the contract details below.',
        life: 3000
      })
    } else {
      // No supplier_id provided, show error
      toast.add({
        severity: 'error',
        summary: 'Error',
        detail: 'Please create a supplier first',
        life: 3000
      })
      setTimeout(() => router.push({ name: 'procurement.suppliers' }), 2000)
    }
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load supplier', life: 3000 })
  }
})
</script>
