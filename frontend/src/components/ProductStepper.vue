<template>
  <div class="stepper-container">
    <div class="flex items-center justify-between relative">
      <!-- Connecting Lines -->
      <div class="absolute top-6 left-0 right-0 h-1 bg-gray-200 -z-10"></div>
      
      <!-- Steps -->
      <div 
        v-for="(step, index) in steps" 
        :key="index"
        class="flex flex-col items-center flex-1 relative"
        @click="goToStep(index + 1)"
      >
        <!-- Step Circle -->
        <div 
          class="w-12 h-12 rounded-full flex items-center justify-center mb-2 cursor-pointer transition-all duration-300"
          :class="[
            index + 1 === currentStep 
              ? 'bg-primary-600 text-white shadow-lg scale-110' 
              : index + 1 < currentStep 
                ? 'bg-green-500 text-white' 
                : 'bg-gray-200 text-gray-500'
          ]"
        >
          <i :class="[step.icon, 'text-lg']"></i>
        </div>
        
        <!-- Step Label -->
        <span 
          class="text-sm font-medium"
          :class="[
            index + 1 === currentStep 
              ? 'text-primary-600' 
              : index + 1 < currentStep 
                ? 'text-green-600' 
                : 'text-gray-500'
          ]"
        >
          {{ step.label }}
        </span>
        
        <!-- Step Number -->
        <span 
          class="absolute -top-1 -right-1 w-6 h-6 rounded-full text-xs flex items-center justify-center"
          :class="[
            index + 1 === currentStep 
              ? 'bg-primary-800 text-white' 
              : index + 1 < currentStep 
                ? 'bg-green-700 text-white' 
                : 'bg-gray-300 text-gray-600'
          ]"
        >
          {{ index + 1 }}
        </span>
      </div>
    </div>
    
    <!-- Current Step Title -->
    <div class="mt-8 text-center">
      <h3 class="text-xl font-semibold text-gray-800">
        {{ steps[currentStep - 1].label }}
      </h3>
      <p class="text-gray-600 mt-1">
        Step {{ currentStep }} of {{ steps.length }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

const props = defineProps({
  steps: {
    type: Array,
    required: true
  },
  currentStep: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['update:currentStep'])

const goToStep = (step) => {
  if (step <= props.currentStep) {
    emit('update:currentStep', step)
  }
}
</script>

<style scoped>
.stepper-container {
  position: relative;
}

.stepper-container::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(
    to right,
    #10b981 var(--progress, 0%),
    #e5e7eb var(--progress, 0%)
  );
  transform: translateY(-50%);
  z-index: 0;
  --progress: calc((var(--current-step) - 1) / (var(--total-steps) - 1) * 100%);
}

:deep(.p-stepper) {
  background: transparent;
}
</style>