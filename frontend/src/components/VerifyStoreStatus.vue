<!-- src/views/system/store/VerificationStatus.vue -->
<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
    
            <!-- Header -->
            <div class="grid lg:grid-cols-2 sm:grid-cols-1">
                <div class="mb-10">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4">Verification Status</h1>
    
                    <!-- Status Badge -->
                    <div class="inline-flex items-center px-6 py-3 rounded-full text-sm font-semibold mb-6"
                        :class="statusClasses">
                        <i :class="statusIcon" class="mr-3 text-xl"></i>
                        {{ statusText }}
                    </div>
    
                    <p class="text-gray-600  mx-auto">
                        Track the progress of your store verification application
                    </p>
                </div>
    
                <!-- Status Timeline -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <h2 class="text-lg font-semibold text-gray-800 mb-6 flex items-center">
                        Application Timeline
                    </h2>
    
                    <div class="space-y-6">
                        <!-- Timeline Step 1 -->
                        <div class="flex items-start">
                            <div class="shrink-0">
                                <div
                                    class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center">
                                    <i class="pi pi-check"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-lg font-medium text-gray-800">Application Submitted </h3>
                                    <span class="text-sm text-gray-500">({{ formatDate(verificationData.submittedAt)
                                        }})</span>
                                </div>
                                <p class="text-gray-600 mt-1">
                                    Your verification request has been received
                                </p>
                            </div>
                        </div>
    
                        <!-- Timeline Step 2 -->
                        <div class="flex items-start">
                            <div class="shrink-0">
                                <div class="w-10 h-10 rounded-full" :class="step2Classes">
                                    <div class="w-full h-full rounded-full flex items-center justify-center">
                                        <i :class="step2Icon, step2TextColor"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-800">Document Review</h3>
                                    <span v-if="verificationData.reviewedAt" class="text-sm text-gray-500">
                                        {{ formatDate(verificationData.reviewedAt) }}
                                    </span>
                                </div>
                                <p class="text-gray-600 mt-1">
                                    {{ step2Description }}
                                </p>
                                <div v-if="verificationData.reviewerNotes" class="mt-2 p-3 bg-gray-50 rounded-lg">
                                    <p class="text-sm text-gray-700">
                                        <span class="font-medium">Reviewer Notes:</span> {{ verificationData.reviewerNotes
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
    
                        <!-- Timeline Step 3 -->
                        <div class="flex items-start">
                            <div class="shrink-0">
                                <div class="w-10 h-10 rounded-full" :class="step3Classes">
                                    <div class="w-full h-full rounded-full flex items-center justify-center">
                                        <i :class="step3Icon"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-800">Verification Complete</h3>
                                    <span v-if="verificationData.completedAt" class="text-sm text-gray-500">
                                        {{ formatDate(verificationData.completedAt) }}
                                    </span>
                                </div>
                                <p class="text-gray-600 mt-1">
                                    {{ step3Description }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    
    
            <!-- Submitted Information -->
            <div class="grid sm:grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Store Information -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="pi pi-store mr-2"></i>
                        Store Information
                    </h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500">Store Name</p>
                            <p class="font-medium text-gray-800">{{ verificationData.storeName }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Business Registration Number</p>
                            <p class="font-medium text-gray-800">{{ verificationData.businessNumber }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Business Address</p>
                            <p class="font-medium text-gray-800">{{ verificationData.businessAddress }}</p>
                        </div>
                    </div>
                </div>
    
                <!-- Documents -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="pi pi-file mr-2"></i>
                        Submitted Documents
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="pi pi-id-card text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Government ID</p>
                                    <p class="text-sm text-gray-600">
                                        {{ verificationData.governmentId?.name || 'Not submitted' }}
                                    </p>
                                </div>
                            </div>
                            <button v-if="verificationData.governmentId" @click="viewDocument('governmentId')"
                                class="text-blue-600 hover:text-blue-800">
                                <i class="pi pi-eye"></i>
                            </button>
                        </div>
    
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center">
                                <div class="bg-green-100 p-2 rounded-lg mr-3">
                                    <i class="pi pi-file text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">Business Permit</p>
                                    <p class="text-sm text-gray-600">
                                        {{ verificationData.businessPermit?.name || 'Not submitted' }}
                                    </p>
                                </div>
                            </div>
                            <button v-if="verificationData.businessPermit" @click="viewDocument('businessPermit')"
                                class="text-blue-600 hover:text-blue-800">
                                <i class="pi pi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
    
            <!-- Actions -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex flex-col sm:flex-row justify-between items-center">
                    <div class="mb-4 sm:mb-0">
                        <h3 class="text-lg font-medium text-gray-800 mb-2">Need help?</h3>
                        <p class="text-gray-600">Contact our support team for assistance</p>
                    </div>
                    <div class="flex space-x-4">
                        <button v-if="verificationData.status === 'pending'" @click="withdrawApplication"
                            class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition">
                            <i class="pi pi-times mr-2"></i>
                            Withdraw Application
                        </button>
                        <button v-if="verificationData.status === 'rejected'" @click="resubmitApplication"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="pi pi-refresh mr-2"></i>
                            Resubmit Application
                        </button>
                        <button @click="contactSupport"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            <i class="pi pi-envelope mr-2"></i>
                            Contact Support
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'

const toast = useToast()

// Mock verification data (in real app, fetch from API)
const verificationData = ref({
  storeName: 'My Furniture Store',
  businessNumber: 'DTI-1234567',
  businessAddress: '123 Main Street, City, Country',
  governmentId: {
    name: 'government_id.jpg',
    size: 2048576,
    type: 'image/jpeg'
  },
  businessPermit: {
    name: 'business_permit.pdf',
    size: 1048576,
    type: 'application/pdf'
  },
  submittedAt: new Date().toISOString(),
  reviewedAt: null,
  completedAt: null,
  status: 'approved', // pending, reviewing, approved, rejected
  reviewerNotes: ''
})

// Load verification data from localStorage
onMounted(() => {
  const savedData = localStorage.getItem('storeVerification')
  if (savedData) {
    verificationData.value = { ...verificationData.value, ...JSON.parse(savedData) }
  }
})

// Status computed properties
const statusText = computed(() => {
  switch (verificationData.value.status) {
    case 'pending': return 'Pending Review'
    case 'reviewing': return 'Under Review'
    case 'approved': return 'Verified ✓'
    case 'rejected': return 'Rejected'
    default: return 'Pending'
  }
})

const statusIcon = computed(() => {
  switch (verificationData.value.status) {
    case 'pending': return 'pi pi-clock'
    case 'reviewing': return 'pi pi-spin pi-spinner'
    case 'approved': return 'pi pi-check-circle'
    case 'rejected': return 'pi pi-times-circle'
    default: return 'pi pi-clock'
  }
})

const statusClasses = computed(() => {
  switch (verificationData.value.status) {
    case 'pending': return 'bg-yellow-100 text-yellow-800'
    case 'reviewing': return 'bg-blue-100 text-blue-800'
    case 'approved': return 'bg-green-100 text-green-800'
    case 'rejected': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-800'
  }
})

// Timeline step 2
const step2Classes = computed(() => {
  if (['reviewing', 'approved', 'rejected'].includes(verificationData.value.status)) {
    return 'bg-green-500'
  }
  return 'bg-gray-300'
})

const step2Icon = computed(() => {
  if (['reviewing', 'approved', 'rejected'].includes(verificationData.value.status)) {
    return 'pi pi-check'
  }
  return 'pi pi-clock'
})

const step2TextColor = computed(() => {
  if (['reviewing', 'approved', 'rejected'].includes(verificationData.value.status)) {
    return 'text-white'
  }
  return 'text-gray-600'
})

const step2Description = computed(() => {
  if (verificationData.value.status === 'reviewing') {
    return 'Your documents are currently being reviewed by our team'
  } else if (['approved', 'rejected'].includes(verificationData.value.status)) {
    return 'Document review completed'
  }
  return 'Waiting for document review'
})

// Timeline step 3
const step3Classes = computed(() => {
  if (verificationData.value.status === 'approved') {
    return 'bg-green-500'
  } else if (verificationData.value.status === 'rejected') {
    return 'bg-red-500'
  }
  return 'bg-gray-300'
})

const step3Icon = computed(() => {
  if (verificationData.value.status === 'approved') {
    return 'pi pi-check'
  } else if (verificationData.value.status === 'rejected') {
    return 'pi pi-times'
  }
  return 'pi pi-clock'
})

const step3TextColor = computed(() => {
  if (['approved', 'rejected'].includes(verificationData.value.status)) {
    return 'text-white'
  }
  return 'text-gray-600'
})

const step3Description = computed(() => {
  if (verificationData.value.status === 'approved') {
    return 'Your store has been verified and is now active'
  } else if (verificationData.value.status === 'rejected') {
    return 'Your application was rejected. Please check the review notes.'
  }
  return 'Verification pending completion'
})

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'Pending'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Actions
const viewDocument = (documentType) => {
  toast.add({
    severity: 'info',
    summary: 'View Document',
    detail: `Opening ${documentType}...`,
    life: 2000
  })
  // In real app, open the document file
}

const withdrawApplication = () => {
  toast.add({
    severity: 'warn',
    summary: 'Withdraw Application',
    detail: 'Are you sure? This action cannot be undone.',
    life: 4000
  })
  // In real app, call API to withdraw
}

const resubmitApplication = () => {
  // Redirect to verification page
  window.location.href = '/system/store/verification'
}

const contactSupport = () => {
  toast.add({
    severity: 'info',
    summary: 'Contact Support',
    detail: 'support@furniturestore.com',
    life: 3000
  })
}
</script>