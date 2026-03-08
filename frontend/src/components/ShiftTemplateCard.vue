<!-- views/system/shifts/components/ShiftTemplateCard.vue -->
<template>
  <div class="border border-gray-200 rounded-lg p-3 cursor-move hover:border-blue-200 hover:shadow-sm transition-all"
       draggable="true"
       @dragstart="startDrag"
       @click="$emit('apply', template)">
    <div class="flex items-center gap-2 mb-2">
      <div class="w-2 h-2 rounded-full" :class="`bg-${template.color}-500`"></div>
      <span class="font-medium text-sm">{{ template.name }}</span>
    </div>
    <div class="text-xs text-gray-500">{{ template.start }} - {{ template.end }}</div>
    <div class="text-xs text-gray-400 mt-1">{{ template.employees || 0 }} employees</div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  template: any
}>()

const emit = defineEmits(['apply'])

const startDrag = (event: DragEvent) => {
  if (event.dataTransfer) {
    event.dataTransfer.setData('text/plain', JSON.stringify(props.template))
    event.dataTransfer.effectAllowed = 'copy'
  }
}
</script>