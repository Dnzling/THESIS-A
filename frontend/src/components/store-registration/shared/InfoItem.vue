<template>
  <div :class="['p-3 bg-gray-50 rounded-lg', customClass]">
    <div class="flex justify-between items-start">
      <div>
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
          {{ label }}
        </p>
        <p v-if="value" class="mt-1 text-sm text-gray-900">
          {{ value }}
        </p>
        <p v-else class="mt-1 text-sm text-gray-400 italic">
          Not provided
        </p>
      </div>
      <slot name="action"></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  label: string
  value: string
  customClass?: string
}

withDefaults(defineProps<Props>(), {
  customClass: ''
})
</script>