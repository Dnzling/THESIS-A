<template>
    <div class="min-h-screen bg-gray-50">

    
        <main class="mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Progress Steps -->
            <div class="mb-10">
                <div class="flex items-center justify-center">
                    <div class="flex items-center">

    
                        <!-- Step 2: Branch Info -->
                        <div class="flex items-center">
                            <div :class="`w-10 h-10 rounded-full flex items-center justify-center ${
                                                                    currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500'
                                                                  }`">
                                <span class="font-semibold">1</span>
                            </div>
                            <div class="ml-3">
                                <p :class="`text-sm font-medium ${
                                                                      currentStep >= 1 ? 'text-blue-600' : 'text-gray-500'
                                                                    }`">Branch Information</p>
                            </div>
                        </div>
    
                        <!-- Connector Line -->
                        <div class="w-24 h-1 mx-4 bg-gray-200"></div>
    
                        <!-- Step 3: Review -->
                        <div class="flex items-center">
                            <div :class="`w-10 h-10 rounded-full flex items-center justify-center ${
                                                                    currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500'
                                                                  }`">
                                <span class="font-semibold">2</span>
                            </div>
                            <div class="ml-3">
                                <p :class="`text-sm font-medium ${
                                                                      currentStep >= 2 ? 'text-blue-600' : 'text-gray-500'
                                                                    }`">Review & Submit</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    

    
            <!-- Step 2:  Information Form -->
            <div v-if="currentStep === 1" class="bg-white shadow-lg rounded-xl p-8">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">Branch Information</h2>
                    <p class="text-gray-600">Add your first physical store location</p>
                </div>
    
                <form @submit.prevent="nextStep" class="space-y-6">
    
                    <div>
                        <label for="City" class="block text-sm font-medium text-gray-700 mb-2">
                            Branch Name *
                        </label>
                        <input type="text" id="name" v-model="formData.branch.name" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="e.g., Dasmarinas Branch" />
                    </div>
    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- City -->
                        <div>
                            <label for="City" class="block text-sm font-medium text-gray-700 mb-2">
                                City *
                            </label>
                            <input type="text" id="City" v-model="formData.branch.city" required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., Dasmariñas" />
                        </div>
    
                        <div>
                            <label for="City" class="block text-sm font-medium text-gray-700 mb-2">
                                Province *
                            </label>
                            <input type="text" id="Province" v-model="formData.branch.province" disabled
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        </div>
    
                    </div>
    
    
                    <!-- Address -->
                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                            Full Address *
                        </label>
                        <textarea id="address" v-model="formData.branch.address" rows="3"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Street, Barangay"></textarea>
                    </div>
    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="branch_manager_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Manager Name
                            </label>
                            <input type="text" id="branch_manager_name" v-model="formData.branch.manager_name"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., Juan Dela Cruz" />
                        </div>
                        <div>
                            <label for="branch_contact_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Branch Contact Number
                            </label>
                            <input type="tel" id="branch_contact_number" v-model="formData.branch.contact_number"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Branch-specific contact number" />
                        </div>
                    </div>
    
                    <!-- Location Coordinates (Optional) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="latitude" class="block text-sm font-medium text-gray-700 mb-2">
                                Latitude (Optional)
                            </label>
                            <input type="number" step="any" id="latitude" v-model="formData.branch.latitude"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., 14.327710" />
                        </div>
                        <div>
                            <label for="longitude" class="block text-sm font-medium text-gray-700 mb-2">
                                Longitude (Optional)
                            </label>
                            <input type="number" step="any" id="longitude" v-model="formData.branch.longitude"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., 120.983106" />
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">For map integration (can be added later)</p>
    
                    <!-- Navigation Buttons -->
                    <div class="flex justify-between pt-6 border-t border-gray-200">
                        <button type="button" @click="prevStep"
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                             Back to Store Info
                        </button>
                        <div>
                            <button type="button" @click="goToDashboard"
                                class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                                Cancel
                            </button>
                            <button type="submit"
                                class="ml-4 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                                Review Details
                            </button>
                        </div>
                    </div>
                </form>
            </div>
    
            <!-- Step 3: Review & Submit -->
            <div v-else-if="currentStep === 2" class="bg-white shadow-lg rounded-xl p-8">
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">Review & Submit</h2>
                    <p class="text-gray-600">Review branch information before submission</p>
                </div>

                <!-- Branch Information Review -->
                <div class="mb-10">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">
                        Branch Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Branch Name</p>
                            <p class="font-medium">{{ formData.branch.name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">City</p>
                            <p class="font-medium">{{ formData.branch.city }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Province</p>
                            <p class="font-medium">{{ formData.branch.province }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Branch Manager</p>
                            <p class="font-medium">{{ formData.branch.manager_name || null }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Branch Contact Number</p>
                            <p class="font-medium">{{ formData.branch.contact_number || null }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-500">Address</p>
                            <p class="font-medium">{{ formData.branch.address }}</p>
                        </div>
                        <div v-if="formData.branch.latitude">
                            <p class="text-sm text-gray-500">Latitude</p>
                            <p class="font-medium">{{ formData.branch.latitude }}</p>
                        </div>
                        <div v-if="formData.branch.longitude">
                            <p class="text-sm text-gray-500">Longitude</p>
                            <p class="font-medium">{{ formData.branch.longitude }}</p>
                        </div>
                    </div>
                </div>
    
                <!-- Terms & Conditions -->
                <div class="mb-8 p-4 bg-gray-50 rounded-lg">
                    <label class="flex items-start">
                        <input type="checkbox" v-model="termsAccepted" required
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 mt-1" />
                        <span class="ml-3 text-sm text-gray-700">
                            I confirm that all information provided is accurate and complete.
                            I agree to the <a href="#" class="text-blue-600 hover:underline">Terms of Service</a> and
                            <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>.
                        </span>
                    </label>
                </div>
    
                <!-- Navigation Buttons -->
                <div class="flex justify-between pt-6 border-t border-gray-200">
                    <button type="button" @click="prevStep"
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                        Edit Branch Details
                    </button>
                    <div>
                        <button type="button" @click="goToDashboard"
                            class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium">
                            Cancel
                        </button>
                        <button type="button" @click="submitForm" :disabled="!termsAccepted" :class="`ml-4 px-6 py-3 rounded-lg font-medium ${
                                                                    termsAccepted
                                                                      ? 'bg-green-600 text-white hover:bg-green-700'
                                                                      : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                                                  }`">
                            <span v-if="isSubmitting">
                                <i class="pi pi-spin pi-spinner mr-2"></i>
                                Creating Branch...
                            </span>
                            <span v-else>
                                <i class="pi pi-check-circle mr-2"></i>
                                Create Branch
                            </span>
                        </button> 
                    </div>
                </div>
            </div>
    
            <!-- Success Modal -->
            <div v-if="showSuccessModal"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
                <div class="bg-white rounded-xl p-8 max-w-md w-full">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="pi pi-check text-green-600 text-3xl"></i>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-4">Store Created Successfully!</h3>
                        <p class="text-gray-600 mb-6">
                            Your store "{{ formData.store.store_name }}" & "{{ formData.branch.name }}"
                            have been registered successfully.
                        </p>
                        <button @click="goToDashboard"
                            class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium">
                            Go to Dashboard
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import { useAuthStore } from '../../../stores/auth'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

// Step management
const currentStep = ref(1)

// Form data structure matching your database schema
const formData = reactive({
  store: {
    store_name: 'Sanyang',
    contact_person: 'Lacea',
    contact_number: '123123123',
    status: 'active',
  },
  branch: {
    name: '',
    address: '',
    province: 'Cavite',
    city: '',
    latitude: null as number | null,
    longitude: null as number | null,
    status: 'active',
    contact_number: '',
    branch_code: '',
    is_main_branch: true,
    manager_name: '',
  }
})

// Terms acceptance
const termsAccepted = ref(false)

// Loading and success states
const isSubmitting = ref(false)
const showSuccessModal = ref(false)

// Navigation functions
const nextStep = () => {
  // Validate current step before proceeding
  if (validateCurrentStep()) {
    currentStep.value++
  }
}

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--
  }
}

const goToDashboard = () => {
  router.push('/system')
  resetForm()
}

// Step validation
const validateCurrentStep = () => {
  switch (currentStep.value) {
    // case 1:
      // Validate store information
    //   if (!formData.store.store_name.trim()) {
    //     alert('Store name is required')
    //     return false
    //   }
    //   if (!formData.store.contact_person.trim()) {
    //     alert('Contact person is required')
    //     return false
    //   }
    //   if (!formData.store.contact_number.trim()) {
    //     alert('Contact number is required')
    //     return false
    //   }
    //   return true

    case 1:
      // Validate branch information
      if (!formData.branch.name.trim()) {
        alert('Branch name is required')
        return false
      }
      if (!formData.branch.contact_number.trim()) {
        alert('Contact number is required')
        return false
      }
      if (!formData.branch.city.trim()) {
        alert('City is required')
        return false
      }
      //   if (!formData.branch.branch_code.trim()) {
      //     alert('Branch code is required')
      //     return false
      //   }
      if (!formData.branch.address.trim()) {
        alert('Address is required')
        return false
      }
      return true

    case 2:
      // Validate terms acceptance
      if (!termsAccepted.value) {
        alert('Please accept the terms and conditions')
        return false
      }
      return true

    default:
      return true
  }
}

// Define default form values
const defaultFormData = () => {
  formData.store = {
    store_name: '',
    contact_person: '',
    contact_number: '',
    status: 'active'
  }

  formData.branch = {
    name: '',
    address: '',
    latitude: null,
    longitude: null,
    contact_number: '',
    branch_code: '',
    is_main_branch: true,
    manager_name: '',
    city: '',
    province: 'Cavite',
    status: 'active'
  }
}

// Reset form function - FIXED
const resetForm = () => {
  // Reset form data using Object.assign to maintain reactivity
  Object.assign(formData, defaultFormData())

  // Reset other reactive values
  termsAccepted.value = false
  currentStep.value = 1
  showSuccessModal.value = false
}

// Main submission function
const submitForm = async () => {
    if (!validateCurrentStep()){
        return
    }
    isSubmitting.value = true

    toast.add({
        severity: 'success',
        summary: 'Success!',
        detail: 'Store is created',
        life: 3000
    })

    localStorage.setItem('hasStore', 'true')

    showSuccessModal.value = true



    // For API
//   try {
//     // Validate all steps
//     if (!validateCurrentStep()) {
//       return
//     }
//     isSubmitting.value = true
    
//     const response = await axios.post('/api/store-registration', formData, {
//       headers: {
//         'Authorization': `Bearer ${authStore.token}`
//       }
//     })
//     if (!response.success){
        
//     }

//     resetForm();

//     // Success
//     toast.add({
//       severity: 'success',
//       summary: 'Success!',
//       detail: 'Store is created',
//       life: 3000
//     })

//     // Save store info to localStorage
//     localStorage.setItem('store', JSON.stringify({
//       id: 1, // This should come from your API response
//       ...formData,
//       created_at: new Date().toISOString()
//     }))

//     // Set store flag
//     localStorage.setItem('hasStore', 'true')

//     // Show success modal
//     showSuccessModal.value = true

//     // Optional: Redirect after delay
//     setTimeout(() => {
//       router.push({
//         path: '/login',
//         query: { registered: 'true' }
//       })
//     }, 2000)

//   } catch (error: any) {
//     console.error('Error submitting form:', error)

//     // Check if error has response property
//     const errorMessage = error.response?.data?.message
//       || error.message
//       || 'Something went wrong. Please try again.'

//     toast.add({
//       severity: 'error',
//       summary: 'Registration Failed',
//       detail: errorMessage,
//       life: 5000
//     })
//   } finally {
//     isSubmitting.value = false
//   }
}

// Optional: Fetch current location for coordinates
// const getCurrentLocation = () => {
//   if (navigator.geolocation) {
//     navigator.geolocation.getCurrentPosition(
//       (position) => {
//         formData.branch.latitude = position.coords.latitude
//         formData.branch.longitude = position.coords.longitude
//       },
//       (error) => {
//         console.warn('Could not get location:', error)
//         alert('Unable to get current location. You can enter coordinates manually.')
//       }
//     )
//   } else {
//     alert('Geolocation is not supported by your browser.')
//   }
// }

// Format phone number
const formatPhoneNumber = (value: string) => {
  // Remove all non-numeric characters
  const cleaned = value.replace(/\D/g, '')

  // Format based on length
  if (cleaned.length <= 4) {
    return cleaned
  } else if (cleaned.length <= 7) {
    return `${cleaned.slice(0, 4)} ${cleaned.slice(4)}`
  } else if (cleaned.length <= 10) {
    return `${cleaned.slice(0, 4)} ${cleaned.slice(4, 7)} ${cleaned.slice(7)}`
  } else {
    return `${cleaned.slice(0, 4)} ${cleaned.slice(4, 7)} ${cleaned.slice(7, 11)}`
  }
}

// Watch phone number fields for formatting
watch(
  () => formData.store.contact_number,
  (newValue) => {
    formData.store.contact_number = formatPhoneNumber(newValue)
  }
)

watch(
  () => formData.branch.contact_number,
  (newValue) => {
    formData.branch.contact_number = formatPhoneNumber(newValue)
  }
)
</script>