<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Store Information</h2>
  
    <form @submit.prevent="handleNext">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Store Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Store Name *
          </label>
          <InputText v-model="localForm.storeName" type="text" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            placeholder="Enter store name" />
        </div>
  
        <!-- Business Type -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Business Type *
          </label>
          <Select v-model="localForm.businessType" :options="businessTypes" optionLabel="label" optionValue="value"
            placeholder="Select business type" class="w-full" />
        </div>
  
        <!-- Business Registration Number -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Business Registration Number *
          </label>
          <InputText v-model="localForm.businessNumber" type="text" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            placeholder="e.g., DTI-1234567" />
        </div>
  
        <!-- Contact Number -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Contact Number *
          </label>
          <InputText v-model="localForm.contactNumber" type="tel" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            placeholder="e.g., +639123456789" />
        </div>
  
        <!-- Email -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Business Email *
          </label>
          <InputText v-model="localForm.email" type="email" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            placeholder="business@example.com" />
        </div>
        <div class="grid grid-cols-2 gap-6">
          <!-- City -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Province
            </label>
            <InputText type="text" class="w-full" value="Cavite" read-only disabled />
          </div>
          <!-- City -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              City *
            </label>
            <Select v-model="localForm.businessAddress.city" :options="cities" optionLabel="label" optionValue="value"
              placeholder="Select city" class="w-full" />
          </div>
  
        </div>
  
  
        <!--Coordinates Lat and Long -->
        <div class="flex gap-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Latitude
            </label>
            <InputText v-model="localForm.businessAddress.latitude" type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
              placeholder="e.g., 123.00001" />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Longitude
            </label>
            <InputText v-model="localForm.businessAddress.longitude" type="text"
              class="w-full px-4 py-2 border"
              placeholder="e.g., 123.00001" />
          </div>
          <Button class="h-11 mt-auto w-1/5" icon="pi pi-map-marker" />
        </div>
  
        <!-- Business Address -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            Business Address *
          </label>
          <Textarea v-model="localForm.businessAddress.address" required rows="3" cols="30"
            class="w-full px-4 py-2"/>

        </div>
  
  
      </div>
  
      <!-- Navigation Buttons -->
      <div class="flex justify-end mt-8 pt-6 border-t border-gray-200">
        <Button type="submit" class="w-1/5" severity="contrast" label="Next" />
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import Select from 'primevue/select'
import InputText from 'primevue/inputtext'
import Textarea from 'primevue/textarea'

interface Props {
  formData: Record<string, any>
}

interface Emits {
  (e: 'update:formData', data: any): void
  (e: 'next'): void
  (e: 'prev'): void
}

const businessTypes = [
  { value: 'retail', label: 'Retail Store' },
  { value: 'restaurant', label: 'Restaurant' },
  { value: 'service', label: 'Service Provider' },
  { value: 'wholesale', label: 'Wholesale' },
  { value: 'online', label: 'Online Store' },
  { value: 'other', label: 'Other' }
]

const cities = [
  { value: 'bacoor', label: 'Bacoor' },
  { value: 'carmona', label: 'Carmona' },
  { value: 'cavite-city', label: 'Cavite City' },
  { value: 'dasmarinas', label: 'Dasmariñas' },
  { value: 'general-mariano-alvarez', label: 'General Mariano Alvarez' },
  { value: 'general-trias', label: 'General Trias' },
  { value: 'imus', label: 'Imus' },
  { value: 'indang', label: 'Indang' },
  { value: 'kawit', label: 'Kawit' },
  { value: 'magallanes', label: 'Magallanes' },
  { value: 'mendez', label: 'Mendez' },
  { value: 'naic', label: 'Naic' },
  { value: 'novaleta', label: 'Noveleta' },
  { value: 'rosario', label: 'Rosario' },
  { value: 'silang', label: 'Silang' },
  { value: 'tagaytay', label: 'Tagaytay City' },
  { value: 'tanza', label: 'Tanza' },
  { value: 'ternate', label: 'Ternate' },
  { value: 'trece-martires', label: 'Trece Martires City' }
]

const props = defineProps<Props>()
const emit = defineEmits<Emits>()

// Local form data - only update from parent on component creation
const localForm = ref({ ...props.formData })

// Validation
const handleNext = () => {
  emit('update:formData', localForm.value) // Emit final state
  window.scrollTo(0, 0)
  emit('next')
}
</script>