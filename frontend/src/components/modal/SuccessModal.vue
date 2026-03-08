<!-- components/UniversalModal.vue -->
<template>
  <Dialog 
    v-model:visible="modelValue" 
    :header="title" 
    :style="{ width: '350px' }"
    :modal="true"
    :closable="true"
    :dismissableMask="true"
    @hide="onHide"
  >
    <div class="flex flex-col items-center text-center py-4">
      <!-- Success Icon -->
      <div v-if="type === 'success'" class="mb-3">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto">
          <i class="pi pi-check-circle text-4xl text-green-500"></i>
        </div>
      </div>

      <!-- Error Icon -->
      <div v-if="type === 'error'" class="mb-3">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto">
          <i class="pi pi-times-circle text-4xl text-red-500"></i>
        </div>
      </div>

      <!-- Message -->
      <p class="text-gray-700 mb-4">{{ message }}</p>
      
      <!-- Error Details (optional) -->
      <div v-if="type === 'error' && errors" class="w-full mb-4">
        <div v-if="typeof errors === 'string'" class="text-sm text-red-600 bg-red-50 p-2 rounded">
          {{ errors }}
        </div>
        <ul v-else class="text-sm text-red-600 bg-red-50 p-2 rounded text-left">
          <li v-for="(error, index) in errors" :key="index">
            {{ error }}
          </li>
        </ul>
      </div>
    </div>

    <template #footer>
      <Button 
        :label="buttonText" 
        :severity="type === 'success' ? 'success' : 'secondary'"
        class="w-full"
        @click="onConfirm"
        autofocus
      />
    </template>
  </Dialog>
</template>

<script setup>
import { watch } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'

const props = defineProps({
  // v-model for visibility
  modelValue: {
    type: Boolean,
    required: true
  },
  // 'success' or 'error'
  type: {
    type: String,
    default: 'success'
  },
  // Main message
  message: {
    type: String,
    default: ''
  },
  // Error details (optional)
  errors: {
    type: [String, Array],
    default: null
  },
  // Custom title (optional)
  title: {
    type: String,
    default: ''
  },
  // Custom button text (optional)
  buttonText: {
    type: String,
    default: 'OK'
  },
  // Auto close after seconds (0 = no auto close)
  autoClose: {
    type: Number,
    default: 0
  }
})

const emit = defineEmits(['update:modelValue', 'confirm', 'close'])

// Auto close functionality
watch(() => props.modelValue, (newVal) => {
  if (newVal && props.autoClose > 0) {
    setTimeout(() => {
      onConfirm()
    }, props.autoClose * 1000)
  }
})

// Methods
const onConfirm = () => {
  emit('confirm')
  emit('update:modelValue', false)
  emit('close')
}

const onHide = () => {
  emit('close')
}
</script>