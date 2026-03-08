<template>
  <div :class="['rounded-lg p-4 border', bgColorClass]">
    <div class="flex justify-between items-start">
      <div>
        <p class="text-sm font-semibold  text-gray-400">{{ stat.label }}</p>
        <p class="text-2xl font-bold mt-1">{{ stat.value }}</p>
        <p v-if="stat.change" class="text-xs mt-2" :class="changeColorClass">
          {{ stat.change }} from yesterday
        </p>
      </div>
      <div :class="['w-15 h-15 rounded-full flex items-center justify-center', iconBgClass]">
        <i :class="[stat.icon, iconColorClass]"></i>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  stat: any
}>()

const bgColorClass = computed(() => `bg-white border-gray-200`)
const iconBgClass = computed(() => `bg-${props.stat.color}-100`)
const iconColorClass = computed(() => `text-${props.stat.color}-600`)
const changeColorClass = computed(() => 
  props.stat.change.startsWith('+') ? 'text-green-600' : 
  props.stat.change.startsWith('-') ? 'text-red-600' : 
  'text-gray-600'
)
</script>