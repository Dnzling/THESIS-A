<template>
  <div class="supplier-portal-registration">
    <div class="text-2xl font-bold text-gray-800 mb-4">Supplier Registration & Verification</div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Registration Form -->
      <Card title="Company Information">
        <template #content>
          <form @submit.prevent="submitRegistration" class="space-y-4">
            <div>
              <label class="block text-sm font-medium mb-2">Company Name *</label>
              <InputText 
                v-model="formData.company_name"
                placeholder="Enter company name"
                class="w-full"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Contact Person *</label>
              <InputText 
                v-model="formData.contact_person"
                placeholder="Full name"
                class="w-full"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Phone *</label>
              <InputMask mask="+63 999 9999 999"
                v-model="formData.phone"
                type="tel"
                placeholder="+63"
                class="w-full"
              />
            </div>

            <div>
              <label class="block text-sm font-medium mb-2">Supplier Type *</label>
              <Select 
                v-model="formData.supplier_type"
                :options="supplierTypes"
                optionLabel="label"
                optionValue="value"
                placeholder="Select type"
                class="w-full"
              />
            </div>

          

            <div>
              <label class="block text-sm font-medium mb-2">Address *</label>
              <Textarea 
                v-model="formData.address"
                placeholder="Street address"
                rows="2"
                class="w-full"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-2">City *</label>
                <InputText v-model="formData.city" class="w-full" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">Province *</label>
                <InputText v-model="formData.province" class="w-full" />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium mb-2">Postal Code *</label>
                <InputText v-model="formData.postal_code" class="w-full" />
              </div>
              <div>
                <label class="block text-sm font-medium mb-2">Country *</label>
                <Select v-model="formData.country" :options="countries" optionLabel="name" optionValue="value" placeholder="Select country" class="w-full" />
              </div>
            </div>

            <Button 
              label="Register" 
              type="submit"
              class="w-full p-button-primary"
              :loading="submitting"
            />
          </form>
        </template>
      </Card>

      <!-- Document Upload -->
      <Card title="Verification Documents">
        <template #content>
          <div class="space-y-4">
            <!-- Required Documents List -->
            <div class="space-y-3">
              <div 
                v-for="doc in requiredDocuments" 
                :key="doc.value"
                class="p-4 border rounded hover:bg-gray-50"
              >
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-file" :class="getDocumentIcon(doc.value)"></i>
                    <span class="font-semibold">{{ doc.label }}</span>
                  </div>
                  <Tag 
                    v-if="uploadedDocuments[doc.value]"
                    value="Uploaded"
                    severity="success"
                  />
                </div>

                <FileUpload
                  :key="doc.value"
                  name="document"
                  :auto="true"
                  :disabled="!portalCreated"
                  accept="image/*,.pdf"
                  :maxFileSize="5000000"
                  :customUpload="true"
                  @uploader="(event) => uploadDocument(event, doc.value)"
                  :showUploadButton="false"
                  :showCancelButton="false"
                  class="w-full"
                >
                  <template #empty>
                    <p class="m-0">Drag and drop files here to upload.</p>
                  </template>
                </FileUpload>
              </div>
            </div>

            <Message 
              v-if="allUploaded"
              severity="success"
              text="All required documents uploaded!"
              class="w-full"
            />
          </div>
        </template>
      </Card>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="mt-6 space-y-3">
      <Skeleton height="60px" class="rounded-lg" />
      <Skeleton height="60px" class="rounded-lg" />
      <Skeleton height="200px" class="rounded-lg" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'
import Select from 'primevue/select'
import FileUpload from 'primevue/fileupload'
import Message from 'primevue/message'
import Tag from 'primevue/tag'
import Skeleton from 'primevue/skeleton'
import PageHeader from '@/Components/PageHeader.vue'
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const submitting = ref(false)
const portalCreated = ref(false)
const uploadedDocuments = ref<Record<string, boolean>>({})

const requiredDocuments = [
  { label: 'Business License', value: 'business_license' },
  { label: 'Tax ID', value: 'tax_id' },
  { label: 'Company Registration', value: 'company_registration' },
]

const supplierTypes = [
  { label: 'Raw Materials', value: 'raw_materials' },
  { label: 'Furniture', value: 'furniture' },
  { label: 'Accessories', value: 'accessories' },
  { label: 'Services', value: 'services' },
]

const formData = ref({
  company_name: '',
  contact_person: '',
  phone: '',
  address: '',
  city: '',
  province: '',
  postal_code: '',
  country: 'Philippines',
  tin: '',
  payment_terms: 'net_30',
  supplier_type: 'raw_materials',
})

const paymentTerms = [
  { label: 'Cash on Delivery', value: 'cash_on_delivery' },
  { label: 'Net 7', value: 'net_7' },
  { label: 'Net 15', value: 'net_15' },
  { label: 'Net 30', value: 'net_30' },
  { label: 'Net 60', value: 'net_60' },
  { label: 'Advance Payment', value: 'advance_payment' },
]

const countries = [
  { name: 'Philippines', value: 'Philippines' },
  { name: 'United States', value: 'United States' },
  { name: 'Japan', value: 'Japan' },
  { name: 'China', value: 'China' },
  { name: 'Singapore', value: 'Singapore' },
]

const allUploaded = computed(() => requiredDocuments.every(doc => uploadedDocuments.value[doc.value]))

const getDocumentIcon = (docType: string) => {
  const icons: { [key: string]: string } = {
    business_license: 'text-blue-500',
    tax_id: 'text-green-500',
    company_registration: 'text-purple-500',
    // bank_details removed
  }
  return icons[docType] || ''
}

const submitRegistration = async () => {
  try {
    submitting.value = true
    await supplierService.registerSupplierPortal(formData.value)
    portalCreated.value = true
    
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Registration submitted. Please wait for verification.',
      life: 3000,
    })

    setTimeout(() => router.push('/supplier-portal/dashboard'), 1500)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error.response?.data?.message || 'Registration failed',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

const uploadDocument = async (event: any, documentType: string) => {
  try {
    if (!portalCreated.value) {
      toast.add({
        severity: 'warn',
        summary: 'Register First',
        detail: 'Please submit registration before uploading documents.',
        life: 3000,
      })
      return
    }
    const file = event.files[0]
    await supplierService.uploadVerificationDocument(file, documentType)

    uploadedDocuments.value[documentType] = true
    toast.add({
      severity: 'success',
      summary: 'Uploaded',
      detail: `${documentType.toUpperCase()} uploaded successfully`,
      life: 2000,
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Upload Failed',
      detail: error.response?.data?.message || 'Document upload failed',
      life: 3000,
    })
  }
}

onMounted(() => {
  // Load existing documents status
  const loadDocuments = async () => {
    try {
      const portalRes = await supplierService.getMyPortal().catch(() => null)
      if (portalRes?.data) {
        portalCreated.value = true

        const supplier = portalRes.data.supplier
        if (supplier) {
          formData.value = {
            company_name: supplier.company_name || supplier.supplier_name || '',
            contact_person: supplier.contact_person || '',
            phone: supplier.phone || '',
            address: supplier.address || '',
            city: supplier.city || '',
            province: supplier.province || '',
            postal_code: supplier.postal_code || '',
            country: supplier.country || 'Philippines',
            tin: supplier.tin || '',
            payment_terms: supplier.payment_terms || 'net_30',
            supplier_type: supplier.supplier_type ? mapSupplierTypeToPortal(supplier.supplier_type) : 'raw_materials',
          }
        }
      }

      if (portalCreated.value) {
        const res = await supplierService.getMyDocuments().catch(() => null)
        if (res?.data) {
          res.data.forEach((doc: any) => {
            uploadedDocuments.value[doc.document_type] = true
          })
        }
      }
    } catch (error) {
      console.error('Error loading documents:', error)
    }
  }
  loadDocuments()
})

const mapSupplierTypeToPortal = (type: string) => {
  const map: Record<string, string> = {
    manufacturer: 'raw_materials',
    wholesaler: 'furniture',
    distributor: 'accessories',
    importer: 'raw_materials',
    local_artisan: 'services',
  }
  return map[type] || 'raw_materials'
}
</script>

<style scoped lang="scss">
.supplier-portal-registration {
  padding: 20px;
}
</style>
