<!-- components/ShiftTemplate.vue -->
<template>
  <div :class="['p-3 rounded-lg cursor-move border', bgColorClass]"
       draggable="true"
       @dragstart="dragStart">
    <p class="font-medium text-sm">{{ template.name }}</p>
    <p class="text-xs opacity-75">{{ template.start }} - {{ template.end }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  template: any
}>()

const emit = defineEmits(['apply'])

const bgColorClass = computed(() => {
  const colors = {
    'Morning': 'bg-blue-50 border-blue-200 hover:bg-blue-100',
    'Evening': 'bg-orange-50 border-orange-200 hover:bg-orange-100',
    'Night': 'bg-purple-50 border-purple-200 hover:bg-purple-100'
  }
  return colors[props.template.type] || 'bg-gray-50 border-gray-200 hover:bg-gray-100'
})

const dragStart = (event: DragEvent) => {
  event.dataTransfer?.setData('text/plain', JSON.stringify(props.template))
}
</script>