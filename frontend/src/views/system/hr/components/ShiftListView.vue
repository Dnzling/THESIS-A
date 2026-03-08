<!-- views/system/shifts/components/ShiftListView.vue -->
<template>
  <div class="space-y-4">
    <!-- Filters -->
    <div class="flex gap-3">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="filters.search" placeholder="Search" size="small" @input="$emit('update:filters', filters)" />
      </IconField>
      <Select v-model="filters.department" :options="departmentOptions" size="small"
        placeholder="All Departments" showClear @change="$emit('update:filters', filters)" />
      <Select v-model="filters.shiftType" :options="shiftTypeOptions" size="small" placeholder="Shift Type"
        showClear @change="$emit('update:filters', filters)" />
      <DatePicker v-model="filters.date" showIcon showClear placeholder="Date" size="small"
        @date-select="$emit('update:filters', filters)" />
    </div>

    <!-- Shifts table -->
    <DataTable :value="shifts" :paginator="true" :rows="10" :loading="loading"
      paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" class="text-sm">
      <Column field="schedule_date" header="Date" :sortable="true">
        <template #body="{ data }">
          {{ formatDate(data.schedule_date) }}
        </template>
      </Column>
      <Column field="employee.full_name" header="Employee" :sortable="true">
        <template #body="{ data }">
          <div class="flex items-center gap-2">
            <Avatar :label="getInitials(data.employee.full_name)" size="small" class="bg-gray-200" />
            {{ data.employee.full_name }}
          </div>
        </template>
      </Column>
      <Column field="employee.department" header="Department" :sortable="true">
        <template #body="{ data }">
          {{ data.employee.department }}
        </template>
      </Column>
      <Column field="shift.name" header="Shift">
        <template #body="{ data }">
          <Tag :value="data.shift.name" :severity="getShiftSeverityFromColor(data.shift.color)" rounded />
        </template>
      </Column>
      <Column header="Schedule">
        <template #body="{ data }">
          {{ data.schedule_time }}
        </template>
      </Column>
      <Column field="status" header="Status">
        <template #body="{ data }">
          <Tag :value="data.status_badge.label" :severity="data.status_badge.color" rounded />
        </template>
      </Column>
      <Column field="assigned_by_name" header="Assigned By" />
      <Column header="Actions" style="width: 100px">
        <template #body="{ data }">
          <Button icon="pi pi-eye" text rounded severity="info" size="small" @click="$emit('view-details', data)" />
        </template>
      </Column>
    </DataTable>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  filters: any
  shifts: any[]
  loading: boolean
  departmentOptions: any[]
  shiftTypeOptions: any[]
}>()

defineEmits<{
  (e: 'update:filters', filters: any): void
  (e: 'view-details', shift: any): void
}>()

const formatDate = (date: string | null): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  })
}

const getInitials = (name: string): string => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const getShiftSeverityFromColor = (color: string | null): string => {
  if (!color) return 'info'
  if (color.includes('3b82f6') || color.includes('1e40af')) return 'info' // Blue
  if (color.includes('f59e0b') || color.includes('b45309')) return 'warning' // Amber
  if (color.includes('7c3aed') || color.includes('6d28d9')) return 'help' // Purple
  if (color.includes('10b981')) return 'success' // Green
  return 'info'
}
</script>