<!-- views/system/shifts/components/ShiftCard.vue -->
<template>
  <div class="bg-blue-50 border border-blue-200 rounded-lg p-2 text-sm group relative">
    <div class="flex justify-between items-start">
      <div>
        <span class="font-medium text-blue-700">{{ shift.type }}</span>
        <div class="text-xs text-gray-600 mt-1">
          {{ formatTime(shift.startTime) }} - {{ formatTime(shift.endTime) }}
        </div>
      </div>
      <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <Button icon="pi pi-pencil" text rounded severity="info" size="small" @click="$emit('edit', shift)" />
        <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="$emit('remove', shift.id)" />
      </div>
    </div>
    <div v-if="shift.notes" class="text-xs text-gray-500 mt-1 truncate">{{ shift.notes }}</div>
    <div v-if="shift.isOvertime" class="absolute top-0 right-0 -mt-1 -mr-1">
      <Tag value="OT" severity="warning" size="small" rounded />
    </div>
  </div>
</template>

<script setup lang="ts">
import Button from 'primevue/button'
import Tag from 'primevue/tag'

defineProps<{
  shift: any
}>()

defineEmits(['edit', 'remove'])

const formatTime = (time: string) => {
  if (!time) return '--'
  const [hours, minutes] = time.split(':')
  const hour = parseInt(hours)
  const ampm = hour >= 12 ? 'PM' : 'AM'
  const hour12 = hour % 12 || 12
  return `${hour12}:${minutes} ${ampm}`
}
</script>