<template>
  <div class="supplier-portal-registration mx-auto max-w-7xl space-y-6">
    <div class="relative overflow-hidden rounded-3xl bg-slate-950 px-7 py-8 text-white shadow-xl">
      <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-orange-400/20 blur-3xl"></div>
      <div class="relative"><p class="text-xs font-bold uppercase tracking-[.22em] text-orange-300">Partner onboarding</p><h1 class="mt-3 text-3xl font-bold">Supplier registration &amp; verification</h1><p class="mt-3 max-w-2xl text-sm text-slate-300">Complete your business profile and submit clear credentials for verification.</p></div>
    </div>

    <Message
      v-if="errorSummary"
      severity="error"
      class="mb-4"
    >
      <div class="text-sm">
        <p class="font-semibold mb-1">{{ errorSummary }}</p>
        <ul v-if="errorList.length" class="list-disc ml-5 space-y-0.5">
          <li v-for="(err, idx) in errorList" :key="idx">{{ err }}</li>
        </ul>
      </div>
    </Message>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_1.05fr]">
      <!-- Registration Form -->
      <Card class="overflow-hidden rounded-3xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="text-sm font-semibold text-slate-800">Company Information</div>
        </template>
        <template #content>
          <form @submit.prevent="submitRegistration" class="space-y-4">
            <div>
              <label class="block text-xs font-medium mb-1 text-slate-600">Company Name *</label>
              <InputText
                v-model="formData.company_name"
                placeholder="Enter company name"
                class="w-full p-inputtext-sm"
                :invalid="hasFieldError('company_name')"
              />
              <small v-if="hasFieldError('company_name')" class="text-red-500">{{ firstFieldError('company_name') }}</small>
            </div>

            <div>
              <label class="block text-xs font-medium mb-1 text-slate-600">Contact Person *</label>
              <InputText
                v-model="formData.contact_person"
                placeholder="Full name"
                class="w-full p-inputtext-sm"
                :invalid="hasFieldError('contact_person')"
              />
              <small v-if="hasFieldError('contact_person')" class="text-red-500">{{ firstFieldError('contact_person') }}</small>
            </div>

            <div>
              <label class="block text-xs font-medium mb-1 text-slate-600">Phone *</label>
              <InputMask mask="+63 999 9999 999"
                v-model="formData.phone"
                type="tel"
                placeholder="+63"
                class="w-full p-inputtext-sm"
                :invalid="hasFieldError('phone')"
              />
              <small v-if="hasFieldError('phone')" class="text-red-500">{{ firstFieldError('phone') }}</small>
            </div>

            <div>
              <label class="block text-xs font-medium mb-1 text-slate-600">Supplier Type *</label>
              <Select
                v-model="formData.supplier_type"
                :options="supplierTypes"
                optionLabel="label"
                optionValue="value"
                placeholder="Select type"
                class="w-full"
                size="small"
                :invalid="hasFieldError('supplier_type')"
              />
              <small v-if="hasFieldError('supplier_type')" class="text-red-500">{{ firstFieldError('supplier_type') }}</small>
            </div>

            <div>
              <label class="block text-xs font-medium mb-1 text-slate-600">Address *</label>
              <Textarea
                v-model="formData.address"
                placeholder="Street address"
                rows="2"
                class="w-full"
                :invalid="hasFieldError('address')"
              />
              <small v-if="hasFieldError('address')" class="text-red-500">{{ firstFieldError('address') }}</small>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium mb-1 text-slate-600">City *</label>
                <InputText v-model="formData.city" class="w-full p-inputtext-sm" :invalid="hasFieldError('city')" />
                <small v-if="hasFieldError('city')" class="text-red-500">{{ firstFieldError('city') }}</small>
              </div>
              <div>
                <label class="block text-xs font-medium mb-1 text-slate-600">Province *</label>
                <InputText v-model="formData.province" class="w-full p-inputtext-sm" :invalid="hasFieldError('province')" />
                <small v-if="hasFieldError('province')" class="text-red-500">{{ firstFieldError('province') }}</small>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium mb-1 text-slate-600">Postal Code *</label>
                <InputText v-model="formData.postal_code" class="w-full p-inputtext-sm" :invalid="hasFieldError('postal_code')" />
                <small v-if="hasFieldError('postal_code')" class="text-red-500">{{ firstFieldError('postal_code') }}</small>
              </div>
              <div>
                <label class="block text-xs font-medium mb-1 text-slate-600">Country *</label>
                <Select v-model="formData.country" :options="countries" optionLabel="name" optionValue="value" placeholder="Select country" class="w-full" size="small" :invalid="hasFieldError('country')" />
                <small v-if="hasFieldError('country')" class="text-red-500">{{ firstFieldError('country') }}</small>
              </div>
            </div>

            <Button
              :label="portalCreated ? 'Update Profile' : 'Submit Registration'"
              type="submit"
              size="small"
              class="w-full"
              :loading="submitting"
            />
          </form>
        </template>
      </Card>

      <!-- Document Upload -->
      <Card class="overflow-hidden rounded-3xl border border-slate-200 shadow-sm">
        <template #title>
          <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-slate-800">Verification Documents</span>
            <Tag :value="`${Object.values(uploadedDocuments).filter(Boolean).length}/${requiredDocuments.length}`" severity="info" />
          </div>
        </template>
        <template #content>
          <div class="space-y-4">
            <Message v-if="!portalCreated" severity="warn" class="text-sm">
              Submit the company information first before uploading documents.
            </Message>

            <!-- Required Documents List -->
            <div class="space-y-3">
              <div
                v-for="doc in requiredDocuments"
                :key="doc.value"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50"
              >
                <div v-if="documentPreviews[doc.value]" class="relative h-40 overflow-hidden bg-slate-200">
                  <img v-if="documentPreviews[doc.value].isImage" :src="documentPreviews[doc.value].url" :alt="doc.label" class="h-full w-full object-cover" />
                  <div v-else class="flex h-full flex-col items-center justify-center text-slate-500"><i class="pi pi-file-pdf text-4xl text-red-500"></i><span class="mt-2 text-xs font-semibold">PDF document</span></div>
                  <span class="absolute right-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-emerald-700 shadow">{{ uploadedDocuments[doc.value] ? 'Uploaded' : 'Preview' }}</span>
                </div>
                <div class="p-4">
                <div class="flex items-center justify-between mb-2">
                  <div class="flex items-center gap-2">
                    <i class="pi pi-file" :class="getDocumentIcon(doc.value)"></i>
                    <span class="text-sm font-medium text-slate-800">{{ doc.label }}</span>
                  </div>
                  <Tag 
                    v-if="uploadedDocuments[doc.value]"
                    value="Uploaded"
                    severity="success"
                  />
                </div>
                <div v-if="documentPreviews[doc.value]" class="mb-3 min-w-0"><p class="truncate text-sm font-semibold text-slate-800">{{ documentPreviews[doc.value].name }}</p><p class="mt-1 text-xs text-slate-500">{{ formatFileSize(documentPreviews[doc.value].size) }}</p></div>

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
                  class="w-full p-fileupload-sm"
                >
                  <template #empty>
                    <p class="m-0 text-xs text-slate-500">Drag and drop or click to upload (PDF/Image, max 5MB)</p>
                  </template>
                </FileUpload>
                </div>
              </div>
            </div>

            <Message 
              v-if="allUploaded"
              severity="success"
              text="All required documents uploaded. Your verification is ready for review."
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
import { ref, onBeforeUnmount, onMounted, computed } from 'vue'
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
import supplierService from '../../../services/supplier.service'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const submitting = ref(false)
const portalCreated = ref(false)
const uploadedDocuments = ref<Record<string, boolean>>({})
const documentPreviews = ref<Record<string, { url: string; name: string; size: number; isImage: boolean }>>({})
const objectUrls = new Set<string>()
const fieldErrors = ref<Record<string, string[]>>({})
const errorSummary = ref('')

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

const countries = [
  { name: 'Philippines', value: 'Philippines' },
  { name: 'United States', value: 'United States' },
  { name: 'Japan', value: 'Japan' },
  { name: 'China', value: 'China' },
  { name: 'Singapore', value: 'Singapore' },
]

const allUploaded = computed(() => requiredDocuments.every(doc => uploadedDocuments.value[doc.value]))
const errorList = computed(() => Object.values(fieldErrors.value).flat())

const hasFieldError = (field: string) => !!fieldErrors.value[field]?.length
const firstFieldError = (field: string) => fieldErrors.value[field]?.[0] || ''
const formatFileSize = (bytes: number) => bytes ? `${(bytes / 1024 / 1024).toFixed(2)} MB` : 'File attached'
const setPreview = (type: string, blob: Blob, name: string, size: number, mime = blob.type) => {
  const previous = documentPreviews.value[type]?.url
  if (previous) { URL.revokeObjectURL(previous); objectUrls.delete(previous) }
  const url = URL.createObjectURL(blob); objectUrls.add(url)
  documentPreviews.value[type] = { url, name, size, isImage: String(mime).startsWith('image/') }
}

const normalizeFieldLabel = (field: string): string =>
  field.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())

const validateForm = () => {
  const errors: Record<string, string[]> = {}
  const requiredFields = [
    'company_name',
    'contact_person',
    'phone',
    'supplier_type',
    'address',
    'city',
    'province',
    'postal_code',
    'country',
  ]

  requiredFields.forEach((field) => {
    const value = String((formData.value as any)[field] ?? '').trim()
    if (!value) errors[field] = [`${normalizeFieldLabel(field)} is required.`]
  })

  const phoneRaw = String(formData.value.phone || '').replace(/\D/g, '')
  if (formData.value.phone && phoneRaw.length < 12) {
    errors.phone = ['Please enter a valid phone number (e.g. +63 912 3456 789).']
  }

  fieldErrors.value = errors
  if (Object.keys(errors).length) {
    errorSummary.value = 'Please fix the highlighted fields before submitting.'
    return false
  }

  errorSummary.value = ''
  return true
}

const applyServerValidation = (error: any) => {
  const status = error?.response?.status
  const responseData = error?.response?.data ?? {}

  if (status === 422 && responseData?.errors) {
    fieldErrors.value = responseData.errors
    errorSummary.value = responseData?.message || 'Validation failed. Please review the form fields.'
    return
  }

  fieldErrors.value = {}
  errorSummary.value = responseData?.message || 'Unable to submit registration. Please try again.'
}

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
  fieldErrors.value = {}
  errorSummary.value = ''
  if (!validateForm()) return

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
    applyServerValidation(error)
    toast.add({
      severity: 'error',
      summary: 'Registration Failed',
      detail: errorSummary.value || 'Please review the fields and try again.',
      life: 3500,
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
    setPreview(documentType, file, file.name, file.size, file.type)
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
            supplierService.downloadDocument(doc.id).then((blob: Blob) => setPreview(doc.document_type, blob, doc.original_filename, doc.file_size, doc.file_mime_type)).catch(() => undefined)
          })
        }
      }
    } catch (error) {
      console.error('Error loading documents:', error)
    }
  }
  loadDocuments()
})

onBeforeUnmount(() => objectUrls.forEach(url => URL.revokeObjectURL(url)))

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
