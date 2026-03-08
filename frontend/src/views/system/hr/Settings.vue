<template>
  <div class="px-6 max-w-8xl">
    <!-- Tabs Navigation -->
    <div class="border-b border-gray-200 mb-6">
      <div class="flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="[
            'flex items-center gap-2 px-1 py-4 text-sm font-medium border-b-2 transition-colors',
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
        >
          <i :class="tab.icon" class="text-base"></i>
          <span>{{ tab.label }}</span>
        </button>
      </div>
    </div>

    <!-- Content Area -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[600px]">
      <!-- Deduction Types Tab -->
      <DeductionType v-if="activeTab === 'deduction-types'" />

      <!-- Shift Swaps Tab -->
      <ShiftSwaps v-else-if="activeTab === 'shift-swaps'" />

      <!-- Other Settings Placeholder -->
      <div v-else class="flex flex-col items-center justify-center h-full text-gray-400">
        <i class="pi pi-cog text-6xl mb-4"></i>
        <p class="text-lg">Settings for {{ activeTab }} coming soon</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import DeductionType from './DeductionType.vue';
// import ShiftSwaps from './ShiftSwaps.vue'

// Tabs configuration
const tabs = [
  {
    id: 'deduction-types',
    label: 'Deduction Types',
    icon: 'pi pi-percentage'
  },
  {
    id: 'overtime-rules',
    label: 'Overtime Rules',
    icon: 'pi pi-clock'
  },
  {
    id: 'payroll-settings',
    label: 'Payroll Settings',
    icon: 'pi pi-dollar'
  },
  {
    id: 'company-info',
    label: 'Company Info',
    icon: 'pi pi-building'
  }
]

const activeTab = ref('deduction-types')
</script>