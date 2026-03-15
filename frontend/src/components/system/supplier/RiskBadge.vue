<template>
  <div class="inline-flex items-center gap-2">
    <span
      class="px-3 py-1 rounded-full text-sm font-semibold text-white"
      :class="riskClass"
    >
      {{ riskLevel }}
    </span>
    <i v-if="riskScore >= 75" class="pi pi-exclamation-triangle text-red-600 text-lg" />
    <i v-else-if="riskScore >= 50" class="pi pi-exclamation-circle text-orange-600 text-lg" />
    <i v-else-if="riskScore >= 20" class="pi pi-info-circle text-yellow-600 text-lg" />
    <i v-else class="pi pi-check-circle text-green-600 text-lg" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  riskScore: number
}>()

const riskLevel = computed(() => {
  if (props.riskScore < 20) return 'Low'
  if (props.riskScore < 50) return 'Medium'
  if (props.riskScore < 75) return 'High'
  return 'Critical'
})

const riskClass = computed(() => {
  if (props.riskScore < 20) return 'bg-green-500'
  if (props.riskScore < 50) return 'bg-yellow-500'
  if (props.riskScore < 75) return 'bg-orange-500'
  return 'bg-red-500'
})
</script>
