
<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class=" mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class=" mb-10">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Store Verification</h1>
        <p class="text-gray-600">
          Complete your store verification to access all features
        </p>
      </div>

      <!-- Verification Form -->
      <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
        <form @submit.prevent="submitVerification">
          <!-- Store Information -->
          <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
              <i class="pi pi-store mr-2"></i>
              Store Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Store Name *
                </label>
                <input
                  v-model="form.storeName"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                  placeholder="Enter your store name"
                />
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Business Registration Number *
                </label>
                <input
                  v-model="form.businessNumber"
                  type="text"
                  required
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                  placeholder="e.g., DTI-1234567"
                />
              </div>
              
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Business Address *
                </label>
                <textarea
                  v-model="form.businessAddress"
                  required
                  rows="3"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                  placeholder="Enter complete business address"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Document Uploads -->
          <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
              <i class="pi pi-file mr-2"></i>
              Required Documents
            </h2>
            
            <!-- Government ID Upload -->
            <div class="mb-8 p-4 border border-gray-200 rounded-lg">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h3 class="text-lg font-medium text-gray-800">Government Issued ID *</h3>
                  <p class="text-sm text-gray-600 mt-1">
                    Upload a clear photo of your valid government ID
                  </p>
                </div>
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">Max 5MB</span>
              </div>
              
              <!-- Upload Area -->
              <div
                @click="triggerFileInput('governmentId')"
                class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 transition"
                :class="{ 'border-green-500 bg-green-50': form.governmentId }"
              >
                <div v-if="!form.governmentId">
                  <i class="pi pi-cloud-upload text-4xl text-gray-400"></i>
                  <p class="mt-2 text-sm text-gray-600">
                    <span class="font-medium text-blue-600">Click to upload</span> government ID
                  </p>
                  <p class="text-xs text-gray-500 mt-1">
                    PNG, JPG, PDF up to 5MB
                  </p>
                </div>
                
                <!-- Uploaded File -->
                <div v-if="form.governmentId" class="flex items-center justify-center space-x-4">
                  <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="pi pi-file text-3xl text-blue-600"></i>
                  </div>
                  <div class="text-left">
                    <p class="font-medium text-gray-800">{{ form.governmentId.name }}</p>
                    <p class="text-sm text-gray-600">{{ formatFileSize(form.governmentId.size) }}</p>
                  </div>
                  <button
                    @click.stop="removeFile('governmentId')"
                    type="button"
                    class="text-red-500 hover:text-red-700"
                  >
                    <i class="pi pi-times"></i>
                  </button>
                </div>
              </div>
              <input
                ref="governmentIdInput"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                class="hidden"
                @change="handleFileUpload($event, 'governmentId')"
              />
            </div>

            <!-- Business Permit Upload -->
            <div class="p-4 border border-gray-200 rounded-lg">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h3 class="text-lg font-medium text-gray-800">Business Permit / DTI Registration *</h3>
                  <p class="text-sm text-gray-600 mt-1">
                    Upload your business permit or DTI registration certificate
                  </p>
                </div>
                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">Max 5MB</span>
              </div>
              
              <!-- Upload Area -->
              <div
                @click="triggerFileInput('businessPermit')"
                class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 transition"
                :class="{ 'border-green-500 bg-green-50': form.businessPermit }"
              >
                <div v-if="!form.businessPermit">
                  <i class="pi pi-cloud-upload text-4xl text-gray-400"></i>
                  <p class="mt-2 text-sm text-gray-600">
                    <span class="font-medium text-blue-600">Click to upload</span> business permit
                  </p>
                  <p class="text-xs text-gray-500 mt-1">
                    PNG, JPG, PDF up to 5MB
                  </p>
                </div>
                
                <!-- Uploaded File -->
                <div v-if="form.businessPermit" class="flex items-center justify-center space-x-4">
                  <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="pi pi-file text-3xl text-blue-600"></i>
                  </div>
                  <div class="text-left">
                    <p class="font-medium text-gray-800">{{ form.businessPermit.name }}</p>
                    <p class="text-sm text-gray-600">{{ formatFileSize(form.businessPermit.size) }}</p>
                  </div>
                  <button
                    @click.stop="removeFile('businessPermit')"
                    type="button"
                    class="text-red-500 hover:text-red-700"
                  >
                    <i class="pi pi-times"></i>
                  </button>
                </div>
              </div>
              <input
                ref="businessPermitInput"
                type="file"
                accept=".jpg,.jpeg,.png,.pdf"
                class="hidden"
                @change="handleFileUpload($event, 'businessPermit')"
              />
            </div>
          </div>

          <!-- Additional Notes -->
          <div class="mb-8">
            <h3 class="text-lg font-medium text-gray-800 mb-4 flex items-center">
              <i class="pi pi-info-circle mr-2"></i>
              Additional Information
            </h3>
            <textarea
              v-model="form.additionalNotes"
              rows="3"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
              placeholder="Any additional information you'd like to provide..."
            ></textarea>
          </div>

          <!-- Terms Agreement -->
          <div class="mb-8 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-start">
              <input
                v-model="form.agreedToTerms"
                type="checkbox"
                required
                class="mt-1 mr-3"
                id="terms"
              />
              <label for="terms" class="text-sm text-gray-700">
                I certify that the information provided is accurate and complete. I understand that providing false information may result in rejection of my application or termination of my store account.
              </label>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="flex justify-end space-x-4">
            <button
              type="button"
              @click="resetForm"
              class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition"
            >
              <i class="pi pi-times mr-2"></i>
              Reset
            </button>
            <button
              type="submit"
              :disabled="isSubmitting || !isFormValid"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ isSubmitting ? 'Submitting...' : 'Submit for Verification' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

// Form data
const form = ref({
  storeName: '',
  businessNumber: '',
  businessAddress: '',
  governmentId: null,
  businessPermit: null,
  additionalNotes: '',
  agreedToTerms: false
})

// File inputs refs
const governmentIdInput = ref(null)
const businessPermitInput = ref(null)

const isSubmitting = ref(false)

// Check if form is valid
const isFormValid = computed(() => {
  return form.value.storeName &&
    form.value.businessNumber &&
    form.value.businessAddress &&
    form.value.governmentId &&
    form.value.businessPermit &&
    form.value.agreedToTerms
})

// Trigger file input
const triggerFileInput = (field) => {
  if (field === 'governmentId') {
    governmentIdInput.value?.click()
  } else if (field === 'businessPermit') {
    businessPermitInput.value?.click()
  }
}

// Handle file upload
const handleFileUpload = (event, field) => {
  const file = event.target.files[0]
  if (!file) return

  // Check file size (5MB max)
  if (file.size > 5 * 1024 * 1024) {
    toast.add({
      severity: 'error',
      summary: 'File too large',
      detail: 'Please upload files smaller than 5MB',
      life: 3000
    })
    return
  }

  // Check file type
  const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']
  if (!validTypes.includes(file.type)) {
    toast.add({
      severity: 'error',
      summary: 'Invalid file type',
      detail: 'Please upload JPG, PNG, or PDF files only',
      life: 3000
    })
    return
  }

  form.value[field] = file
}

// Remove file
const removeFile = (field) => {
  form.value[field] = null
}

// Format file size
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Reset form
const resetForm = () => {
  form.value = {
    storeName: '',
    businessNumber: '',
    businessAddress: '',
    governmentId: null,
    businessPermit: null,
    additionalNotes: '',
    agreedToTerms: false
  }
}

// Submit verification
const submitVerification = async () => {
  if (!isFormValid.value) return

  isSubmitting.value = true

  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1500))

    // Save to localStorage for demo
    const verificationData = {
      ...form.value,
      submittedAt: new Date().toISOString(),
      status: 'pending',
      governmentId: form.value.governmentId ? {
        name: form.value.governmentId.name,
        size: form.value.governmentId.size,
        type: form.value.governmentId.type
      } : null,
      businessPermit: form.value.businessPermit ? {
        name: form.value.businessPermit.name,
        size: form.value.businessPermit.size,
        type: form.value.businessPermit.type
      } : null
    }

    localStorage.setItem('storeVerification', JSON.stringify(verificationData))

    toast.add({
      severity: 'success',
      summary: 'Verification Submitted!',
      detail: 'Your documents are under review. You will be notified via email.',
      life: 5000
    })

    // Reset form after success
    resetForm()

  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Submission Failed',
      detail: 'Please try again later',
      life: 3000
    })
  } finally {
    isSubmitting.value = false
  }
}
</script>