<template>
  <div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6 flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold text-gray-800">Edit Supplier Contract</h1>
        <p class="text-gray-600 mt-1">Update contract details and terms</p>
      </div>
      <Button label="Back to List" icon="pi pi-arrow-left" severity="secondary" @click="router.back()" />
    </div>

    <div v-if="loading" class="flex justify-center items-center py-12">
      <ProgressSpinner />
    </div>

    <form v-else @submit.prevent="submitForm" class="max-w-4xl mx-auto">
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
              <label class="text-sm font-semibold text-gray-700">Supplier <span class="text-red-500">*</span></label>
              <Select v-model="form.supplier_id" :options="suppliers" optionLabel="supplier_name" optionValue="id"
                placeholder="Select Supplier" :loading="loadingSuppliers" :disabled="form.status === 'active'" class="w-full" />
              <small v-if="errors.supplier_id" class="text-red-600">{{ errors.supplier_id }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Contract Number</label>
              <InputText v-model="form.contract_number" disabled />
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Contract Title <span class="text-red-500">*</span></label>
              <InputText v-model="form.contract_title" />
              <small v-if="errors.contract_title" class="text-red-600">{{ errors.contract_title }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Contract Type <span class="text-red-500">*</span></label>
              <Select v-model="form.contract_type" :options="contractTypes" optionLabel="label" optionValue="value"
                placeholder="Select Contract Type" :disabled="form.status === 'active'" class="w-full" />
              <small v-if="errors.contract_type" class="text-red-600">{{ errors.contract_type }}</small>
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
              <Calendar v-model="form.start_date" :show-time="false" date-format="yy-mm-dd" 
                :disabled="form.status === 'active'" />
              <small v-if="errors.start_date" class="text-red-600">{{ errors.start_date }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">End Date <span class="text-red-500">*</span></label>
              <Calendar v-model="form.end_date" :show-time="false" date-format="yy-mm-dd" />
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
              <div class="flex items-center">
                <span class="text-gray-600 mr-2">₱</span>
                <InputNumber v-model="form.minimum_order_value" :min="0" 
                  :use-grouping="true" :locale="'en-PH'" />
              </div>
              <small v-if="errors.minimum_order_value" class="text-red-600">{{ errors.minimum_order_value }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Discount Percentage <span class="text-red-500">*</span></label>
              <div class="flex items-center">
                <InputNumber v-model="form.discount_percentage" :min="0" :max="100" suffix="%" />
              </div>
              <small v-if="errors.discount_percentage" class="text-red-600">{{ errors.discount_percentage }}</small>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-sm font-semibold text-gray-700">Payment Terms (Days) <span class="text-red-500">*</span></label>
              <InputNumber v-model="form.payment_terms_days" :min="0" />
              <small v-if="errors.payment_terms_days" class="text-red-600">{{ errors.payment_terms_days }}</small>
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
            <Textarea v-model="form.terms_conditions" rows="6" class="w-full" />
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
          <div v-if="existingDocument" class="mb-4 p-4 bg-green-50 border border-green-200 rounded flex items-center justify-between">
            <div class="flex items-center gap-3">
              <i class="pi pi-file-pdf text-2xl text-red-600"></i>
              <div>
                <p class="font-semibold text-green-900">Existing Document</p>
                <p class="text-sm text-gray-600">Attached to this contract</p>
              </div>
            </div>
            <Button icon="pi pi-times" text rounded severity="danger" @click="existingDocument = null" />
          </div>
          
          <FileUpload v-if="!contractFile && !existingDocument" name="contract_file" 
            @select="onFileSelect" 
            :multiple="false" 
            accept=".pdf,.doc,.docx"
            :showUploadButton="false"
            :showCancelButton="false"
            chooseLabel="📎 Select PDF or Document"
            class="w-full" />
          
          <div v-else-if="contractFile" class="p-4 bg-blue-50 border border-blue-200 rounded flex items-center justify-between">
            <div class="flex items-center gap-3">
              <i class="pi pi-file-pdf text-2xl text-red-600"></i>
              <div>
                <p class="font-semibold text-blue-900">New Document</p>
                <p class="text-sm text-gray-600">{{ contractFile.name }} ({{ formatFileSize(contractFile.size) }})</p>
              </div>
            </div>
            <Button icon="pi pi-times" text rounded severity="danger" @click="contractFile = null" />
          </div>
        </template>
      </Card>

      <!-- Status Information -->
      <Card class="mb-6 bg-yellow-50">
        <template #content>
          <div class="flex items-start gap-3">
            <i class="pi pi-info-circle text-xl text-yellow-700 mt-1"></i>
            <div>
              <p class="font-semibold text-yellow-900">Contract Status: {{ form.status?.toUpperCase() }}</p>
              <p class="text-sm text-yellow-800 mt-2">
                <span v-if="form.status === 'draft'">
                  This contract is in draft status. You can edit all fields until it's activated.
                </span>
                <span v-else-if="form.status === 'active'">
                  This contract is active. Some fields are locked to prevent accidental modifications. Contact support if you need to make major changes.
                </span>
                <span v-else-if="form.status === 'completed'">
                  This contract is completed and is read-only.
                </span>
                <span v-else-if="form.status === 'terminated'">
                  This contract has been terminated and is read-only.
                </span>
              </p>
            </div>
          </div>
        </template>
      </Card>

      <!-- Action Buttons -->
      <div class="flex gap-3 justify-end">
        <Button label="Cancel" severity="secondary" @click="router.back()" />
        <Button label="Update Contract" severity="success" icon="pi pi-check" @click="submitForm" :loading="submitting" />
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
const loading = ref(true)
const loadingSuppliers = ref(false)
const submitting = ref(false)
const suppliers = ref<any[]>([])
const contractFile = ref<File | null>(null)
const existingDocument = ref<boolean>(false)

const form = reactive<any>({
  supplier_id: null,
  contract_number: '',
  contract_title: '',
  contract_type: 'supply',
  start_date: null,
  end_date: null,
  minimum_order_value: 0,
  discount_percentage: 0,
  payment_terms_days: 30,
  terms_conditions: '',
  status: 'draft',
})

const errors = reactive<Record<string, string>>({})

const contractTypes = [
  { label: 'Supply', value: 'supply' },
  { label: 'Service', value: 'service' },
]

const formProgress = computed(() => {
  let filled = 0
  let total = 10
  
  if (form.supplier_id) filled++
  if (form.contract_number) filled++
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
    if (contractFile.value) {
      formData.append('contract_file', contractFile.value)
    }

    await procurementService.updateSupplierContract(route.params.id as string, formData)
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Contract updated successfully',
      life: 3000,
    })
    setTimeout(() => router.push({ name: 'procurement.supplier-contracts.detail', params: { id: route.params.id } }), 1500)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Failed to update contract',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    const [contractRes, suppliersRes] = await Promise.all([
      procurementService.getSupplierContract(route.params.id as string),
      procurementService.getSuppliers({ per_page: 100 }),
    ])

    const contract = contractRes
    form.supplier_id = contract.supplier_id
    form.contract_number = contract.contract_number
    form.contract_title = contract.contract_title
    form.contract_type = contract.contract_type
    form.start_date = contract.start_date ? new Date(contract.start_date) : null
    form.end_date = contract.end_date ? new Date(contract.end_date) : null
    form.minimum_order_value = contract.minimum_order_value
    form.discount_percentage = contract.discount_percentage
    form.payment_terms_days = contract.payment_terms_days
    form.terms_conditions = contract.terms_conditions
    form.status = contract.status

    if (contract.contract_file_path) {
      existingDocument.value = true
    }

    suppliers.value = suppliersRes.data?.data || []
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load contract', life: 3000 })
    setTimeout(() => router.back(), 2000)
  } finally {
    loading.value = false
  }
})
</script>
