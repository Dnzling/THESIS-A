<template>
  <div v-if="groups.length" class="space-y-3">
    <section v-for="group in groups" :key="group.value" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
      <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 sm:px-5">
        <h3 class="font-semibold text-slate-900">{{ group.label }}</h3>
        <span class="text-xs text-slate-500">{{ group.employees.length }} {{ group.employees.length === 1 ? 'employee' : 'employees' }}</span>
      </div>
      <button
        v-for="employee in group.employees"
        :key="employee.id"
        type="button"
        class="grid w-full gap-2 border-b border-slate-100 px-4 py-3 text-left transition-colors last:border-b-0 hover:bg-orange-50/60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-orange-500 sm:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)_auto] sm:items-center sm:gap-4 sm:px-5"
        @click="$emit('select', employee)"
      >
        <div class="min-w-0">
          <p class="truncate text-sm font-medium text-slate-900">{{ employee.name }}</p>
          <p class="mt-0.5 truncate text-xs text-slate-500">{{ employee.employeeId }} · {{ context === 'branch' ? employee.departmentLabel : employee.branchLabel }}</p>
        </div>
        <div class="min-w-0 text-xs text-slate-600">
          <p class="font-medium text-slate-800">{{ employee.shiftType }} · {{ employee.shiftStart }}–{{ employee.shiftEnd }}</p>
          <p class="mt-0.5 truncate">{{ employee.workingDays.join(' · ') }}</p>
        </div>
        <div class="flex items-center justify-between gap-3 sm:justify-end">
          <Tag :value="employee.status" :severity="employee.status === 'Active' ? 'success' : 'secondary'" class="text-xs" />
          <i class="pi pi-chevron-right text-xs text-slate-400" aria-hidden="true" />
        </div>
      </button>
    </section>
  </div>
  <div v-else class="rounded-2xl border border-dashed border-slate-200 px-5 py-12 text-center">
    <p class="font-medium text-slate-800">No employee shifts found</p>
    <p class="mt-1 text-xs text-slate-500">Try changing the search or filters above.</p>
  </div>
</template>

<script setup lang="ts">
import Tag from 'primevue/tag'

defineProps<{
  groups: { value: string; label: string; employees: any[] }[]
  context: 'branch' | 'department'
}>()

defineEmits<{ select: [employee: any] }>()
</script>
