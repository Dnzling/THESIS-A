<!-- views/system/shifts/components/ShiftAssignmentsView.vue -->
<template>
  <div class="space-y-4">
    <!-- Header with actions -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-medium">Shift Assignments</h3>
      <div class="flex gap-2">
        <Button label="Bulk Assign" icon="pi pi-plus" severity="secondary" outlined @click="showBulkDialog = true" />
        <Button label="New Assignment" icon="pi pi-plus" severity="info" @click="showCreateDialog = true" />
      </div>
    </div>

    <!-- Filters -->
    <div class="flex gap-3">
      <IconField>
        <InputIcon class="pi pi-search" />
        <InputText v-model="filters.search" placeholder="Search employee..." size="small" />
      </IconField>
      <Select v-model="filters.type" :options="assignmentTypeOptions" placeholder="All Types" showClear size="small" />
      <Select v-model="filters.status" :options="statusOptions" placeholder="All Status" showClear size="small" />
    </div>

    <!-- Assignments table -->
    <DataTable :value="filteredAssignments" :loading="loading" :paginator="true" :rows="10" class="text-sm">
      <Column field="employee_name" header="Employee" :sortable="true">
        <template #body="{ data }">
          <div class="flex items-center gap-2">
            <Avatar :label="getInitials(data.employee_name)" size="small" class="bg-gray-200" />
            {{ data.employee_name }}
          </div>
        </template>
      </Column>
      <Column field="shift_name" header="Shift" :sortable="true" />
      <Column field="template_name" header="Template" />
      <Column field="start_date" header="Start Date">
        <template #body="{ data }">
          {{ formatDate(data.start_date) }}
        </template>
      </Column>
      <Column field="end_date" header="End Date">
        <template #body="{ data }">
          {{ data.end_date ? formatDate(data.end_date) : 'Ongoing' }}
        </template>
      </Column>
      <Column field="assignment_type" header="Type" :sortable="true">
        <template #body="{ data }">
          <Tag :value="data.assignment_type" :severity="getTypeSeverity(data.assignment_type)" rounded />
        </Template>
      </Column>
      <Column field="status_badge" header="Status">
        <template #body="{ data }">
          <Tag :value="data.status_badge.label" :severity="data.status_badge.color" rounded />
        </template>
      </Column>
      <Column header="Actions" style="width: 120px">
        <template #body="{ data }">
          <Button icon="pi pi-pencil" text rounded severity="info" size="small" @click="editAssignment(data)" />
          <Button icon="pi pi-trash" text rounded severity="danger" size="small" @click="confirmDelete(data)" />
        </template>
      </Column>
    </DataTable>

    <!-- Create/Edit Dialog -->
    <Dialog v-model:visible="showCreateDialog" :header="editingId ? 'Edit Assignment' : 'Create Assignment'" modal
      :style="{ width: '500px' }" @hide="resetForm">
      <div class="space-y-4 p-2">
        <div class="field">
          <label class="block text-sm font-medium mb-1">Employee <span class="text-red-500">*</span></label>
          <Select v-model="form.employee_id" :options="employees" optionLabel="label" optionValue="value"
            placeholder="Select employee" class="w-full" :disabled="!!editingId" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Shift <span class="text-red-500">*</span></label>
          <Select v-model="form.shift_id" :options="shifts" optionLabel="label" optionValue="value"
            placeholder="Select shift" class="w-full" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Template (Optional)</label>
          <Select v-model="form.template_id" :options="templates" optionLabel="label" optionValue="value"
            placeholder="Select template" class="w-full" showClear />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="field">
            <label class="block text-sm font-medium mb-1">Start Date <span class="text-red-500">*</span></label>
            <DatePicker v-model="form.start_date" dateFormat="yy-mm-dd" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">End Date</label>
            <DatePicker v-model="form.end_date" dateFormat="yy-mm-dd" class="w-full" />
          </div>
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Assignment Type <span class="text-red-500">*</span></label>
          <Select v-model="form.assignment_type" :options="assignmentTypeOptions" placeholder="Select type"
            class="w-full" />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Notes</label>
          <Textarea v-model="form.notes" rows="3" class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="showCreateDialog = false" />
        <Button label="Save" icon="pi pi-check" severity="info" @click="saveAssignment" :loading="saving" />
      </template>
    </Dialog>

    <!-- Bulk Assign Dialog -->
    <Dialog v-model:visible="showBulkDialog" header="Bulk Assign Employees" modal :style="{ width: '600px' }">
      <div class="space-y-4 p-2">
        <div class="field">
          <label class="block text-sm font-medium mb-1">Employees <span class="text-red-500">*</span></label>
          <MultiSelect v-model="bulkForm.employee_ids" :options="employees" optionLabel="label" optionValue="value"
            placeholder="Select employees" class="w-full" filter />
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Shift <span class="text-red-500">*</span></label>
          <Select v-model="bulkForm.shift_id" :options="shifts" optionLabel="label" optionValue="value"
            placeholder="Select shift" class="w-full" />
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div class="field">
            <label class="block text-sm font-medium mb-1">Start Date <span class="text-red-500">*</span></label>
            <DatePicker v-model="bulkForm.start_date" dateFormat="yy-mm-dd" class="w-full" />
          </div>
          <div class="field">
            <label class="block text-sm font-medium mb-1">End Date</label>
            <DatePicker v-model="bulkForm.end_date" dateFormat="yy-mm-dd" class="w-full" />
          </div>
        </div>
        <div class="field">
          <label class="block text-sm font-medium mb-1">Assignment Type <span class="text-red-500">*</span></label>
          <Select v-model="bulkForm.assignment_type" :options="assignmentTypeOptions" placeholder="Select type"
            class="w-full" />
        </div>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="showBulkDialog = false" />
        <Button label="Assign" icon="pi pi-check" severity="info" @click="saveBulkAssign" :loading="bulkSaving" />
      </template>
    </Dialog>

    <!-- Delete Confirmation -->
    <Dialog v-model:visible="showDeleteDialog" header="Confirm Delete" modal :style="{ width: '350px' }">
      <div class="p-2">
        <p>Are you sure you want to delete this assignment?</p>
        <p class="text-sm text-gray-500 mt-2">This action cannot be undone.</p>
      </div>
      <template #footer>
        <Button label="Cancel" icon="pi pi-times" text @click="showDeleteDialog = false" />
        <Button label="Delete" icon="pi pi-trash" severity="danger" @click="deleteAssignment" :loading="deleting" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  assignments: any[]
  loading: boolean
  employees: any[]
  shifts: any[]
  templates: any[]
}>()

const emit = defineEmits<{
  (e: 'refresh'): void
  (e: 'create', data: any): void
  (e: 'update', id: number, data: any): void
  (e: 'delete', id: number): void
  (e: 'bulk-assign', data: any): void
}>()

// State
const filters = ref({
  search: '',
  type: null as string | null,
  status: null as string | null
})

const showCreateDialog = ref(false)
const showBulkDialog = ref(false)
const showDeleteDialog = ref(false)
const editingId = ref<number | null>(null)
const saving = ref(false)
const bulkSaving = ref(false)
const deleting = ref(false)

const form = ref({
  employee_id: null as number | null,
  shift_id: null as number | null,
  template_id: null as number | null,
  start_date: null as Date | null,
  end_date: null as Date | null,
  assignment_type: null as string | null,
  notes: ''
})

const bulkForm = ref({
  employee_ids: [] as number[],
  shift_id: null as number | null,
  template_id: null as number | null,
  start_date: null as Date | null,
  end_date: null as Date | null,
  assignment_type: null as string | null
})

// Options
const assignmentTypeOptions = [
  { label: 'Permanent', value: 'permanent' },
  { label: 'Temporary', value: 'temporary' },
  { label: 'Cover', value: 'cover' }
]

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' }
]

// Computed
const filteredAssignments = computed(() => {
  let filtered = props.assignments

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(a => 
      a.employee_name?.toLowerCase().includes(search)
    )
  }

  if (filters.value.type) {
    filtered = filtered.filter(a => a.assignment_type === filters.value.type)
  }

  if (filters.value.status === 'active') {
    filtered = filtered.filter(a => a.is_active)
  } else if (filters.value.status === 'inactive') {
    filtered = filtered.filter(a => !a.is_active)
  }

  return filtered
})

// Methods
const getInitials = (name: string): string => {
  if (!name) return '?'
  return name.split(' ').map(n => n[0]).join('').toUpperCase()
}

const formatDate = (date: string | null): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getTypeSeverity = (type: string): string => {
  const map: Record<string, string> = {
    'permanent': 'success',
    'temporary': 'warning',
    'cover': 'info'
  }
  return map[type] || 'secondary'
}

const editAssignment = (assignment: any) => {
  editingId.value = assignment.id
  form.value = {
    employee_id: assignment.employee_id,
    shift_id: assignment.shift_id,
    template_id: assignment.template_id,
    start_date: new Date(assignment.start_date),
    end_date: assignment.end_date ? new Date(assignment.end_date) : null,
    assignment_type: assignment.assignment_type,
    notes: assignment.notes || ''
  }
  showCreateDialog.value = true
}

const confirmDelete = (assignment: any) => {
  editingId.value = assignment.id
  showDeleteDialog.value = true
}

const resetForm = () => {
  editingId.value = null
  form.value = {
    employee_id: null,
    shift_id: null,
    template_id: null,
    start_date: null,
    end_date: null,
    assignment_type: null,
    notes: ''
  }
}

const saveAssignment = async () => {
  saving.value = true
  try {
    if (editingId.value) {
      await emit('update', editingId.value, form.value)
    } else {
      await emit('create', form.value)
    }
    showCreateDialog.value = false
    resetForm()
  } finally {
    saving.value = false
  }
}

const saveBulkAssign = async () => {
  bulkSaving.value = true
  try {
    await emit('bulk-assign', bulkForm.value)
    showBulkDialog.value = false
    bulkForm.value = {
      employee_ids: [],
      shift_id: null,
      template_id: null,
      start_date: null,
      end_date: null,
      assignment_type: null
    }
  } finally {
    bulkSaving.value = false
  }
}

const deleteAssignment = async () => {
  if (!editingId.value) return
  deleting.value = true
  try {
    await emit('delete', editingId.value)
    showDeleteDialog.value = false
    editingId.value = null
  } finally {
    deleting.value = false
  }
}
</script>