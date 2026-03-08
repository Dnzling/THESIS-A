<!-- views/system/shifts/components/ShiftCoverageView.vue -->
<template>
  <div class="space-y-4">
    <!-- Date selector -->
    <div class="flex items-center gap-4">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-chevron-left" text rounded size="small" @click="$emit('previous-day')" />
        <DatePicker v-model="selectedDate" dateFormat="MM dd, yy" class="w-40" @date-select="$emit('date-change')" />
        <Button icon="pi pi-chevron-right" text rounded size="small" @click="$emit('next-day')" />
      </div>
      <Tag value="Today" severity="info" v-if="isToday" />
    </div>

    <!-- Coverage by department -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="dept in departments" :key="dept.id" class="border border-gray-100 rounded-lg p-4">
        <div class="flex justify-between items-center mb-3">
          <h3 class="font-semibold">{{ dept.name }}</h3>
          <div>
            <span class="text-sm font-medium">{{ dept.scheduled }}/{{ dept.totalEmployees }}</span>
            <span class="text-xs text-gray-400 ml-1">scheduled</span>
          </div>
        </div>

        <!-- Coverage bar -->
        <div class="w-full bg-gray-100 rounded-full h-2 mb-3">
          <div class="bg-blue-500 h-2 rounded-full" :style="{ width: dept.coveragePercentage + '%' }"></div>
        </div>

        <!-- Scheduled employees list -->
        <div class="space-y-2 mt-3">
          <div v-for="emp in dept.scheduledEmployees" :key="emp.id"
            class="flex items-center justify-between text-sm">
            <div class="flex items-center gap-2">
              <Avatar :label="getInitials(emp.name)" size="small" class="bg-blue-100 text-blue-600" />
              <span>{{ emp.name }}</span>
            </div>
            <Tag :value="emp.shiftType" :severity="getShiftSeverity(emp.shiftType)" size="small" />
          </div>

          <!-- Unfilled slots -->
          <div v-for="n in dept.unfilledCount" :key="'unfilled-'+n"
            class="flex items-center gap-2 text-sm text-gray-400">
            <i class="pi pi-plus-circle text-xs"></i>
            <span>Unfilled slot {{ n }}</span>
          </div>

          <!-- Loading more indicator -->
          <div v-if="loading" class="text-center py-2">
            <i class="pi pi-spin pi-spinner text-blue-500"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// import { computed } from 'vue'

const props = defineProps<{
  selectedDate: Date
  departments: any[]
  isToday: boolean
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'previous-day'): void
  (e: 'next-day'): void
  (e: 'date-change'): void
}>()

const getInitials = (name: string): string => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getShiftSeverity = (type: string): string => {
  const map: Record<string, string> = {
    'Morning': 'info',
    'Mid': 'warning',
    'Evening': 'help',
    'Night': 'secondary'
  }
  return map[type] || 'info'
}
</script>