<template>
  <Dialog
    :visible="true"
    modal
    dismissableMask
    :style="{ width: 'min(64rem, 96vw)' }"
    :breakpoints="{ '960px': '96vw' }"
    class="job-posting-dialog"
    @update:visible="emit('close')"
  >
    <template #header>
      <div class="space-y-1">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-surface-500">Recruitment Setup</p>
        <h2 class="text-2xl font-semibold text-surface-900">
          {{ posting ? 'Edit Job Posting' : 'Create Job Posting' }}
        </h2>
      </div>
    </template>

    <form class="space-y-6" @submit.prevent="handleSubmit">
      <Message v-if="formError" severity="error" :closable="false">
        {{ formError }}
      </Message>

      <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.9fr)]">
        <div class="space-y-6">
          <Card class="border border-surface-200 shadow-none">
            <template #content>
              <div class="grid gap-5 md:grid-cols-2">
                <div class="space-y-2 md:col-span-2">
                  <label class="text-sm font-medium text-surface-700">Job Title</label>
                  <InputText
                    v-model="form.title"
                    class="w-full"
                    placeholder="e.g. Senior Interior Designer"
                    :invalid="showFieldError('title')"
                  />
                  <small v-if="showFieldError('title')" class="text-red-500">Job title is required.</small>
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Department</label>
                  <Select
                    v-model="form.department"
                    :options="departmentOptions"
                    optionLabel="label"
                    optionValue="value"
                    filter
                    placeholder="Select department"
                    class="w-full"
                    :loading="loadingDepartments"
                    :invalid="showFieldError('department')"
                  />
                  <small v-if="showFieldError('department')" class="text-red-500">Department is required.</small>
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Hiring Role</label>
                  <Select
                    v-model="form.role_id"
                    :options="roleOptions"
                    optionLabel="label"
                    optionValue="value"
                    filter
                    placeholder="Select role to hire for"
                    class="w-full"
                    :loading="loadingRoles"
                    :invalid="showFieldError('role_id')"
                  />
                  <small v-if="showFieldError('role_id')" class="text-red-500">Hiring role is required.</small>
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Status</label>
                  <Select
                    v-model="form.status"
                    :options="statusOptions"
                    optionLabel="label"
                    optionValue="value"
                    class="w-full"
                  />
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Minimum Salary</label>
                  <InputNumber
                    v-model="form.salary_min"
                    class="w-full"
                    inputClass="w-full"
                    mode="currency"
                    currency="PHP"
                    locale="en-PH"
                    :min="0"
                    :useGrouping="true"
                    :invalid="showFieldError('salary_min') || salaryRangeInvalid"
                  />
                  <small v-if="showFieldError('salary_min')" class="text-red-500">Minimum salary is required.</small>
                </div>

                <div class="space-y-2">
                  <label class="text-sm font-medium text-surface-700">Maximum Salary</label>
                  <InputNumber
                    v-model="form.salary_max"
                    class="w-full"
                    inputClass="w-full"
                    mode="currency"
                    currency="PHP"
                    locale="en-PH"
                    :min="0"
                    :useGrouping="true"
                    :invalid="showFieldError('salary_max') || salaryRangeInvalid"
                  />
                  <small v-if="showFieldError('salary_max')" class="text-red-500">Maximum salary is required.</small>
                </div>

                <div class="space-y-2 md:col-span-2">
                  <label class="text-sm font-medium text-surface-700">Description</label>
                  <Textarea
                    v-model="form.description"
                    rows="6"
                    class="w-full"
                    autoResize
                    placeholder="Describe the role, expectations, and qualifications."
                    :invalid="showFieldError('description')"
                  />
                  <small v-if="showFieldError('description')" class="text-red-500">Description is required.</small>
                </div>
              </div>
            </template>
          </Card>

          <Card class="border border-surface-200 shadow-none">
            <template #title>
              <div class="flex items-center justify-between gap-3">
                <div>
                  <h3 class="text-base font-semibold text-surface-900">Screening Stages</h3>
                  <p class="mt-1 text-xs text-surface-500">Set the review flow applicants will move through.</p>
                </div>
                <Button type="button" label="Add Stage" icon="pi pi-plus" severity="secondary" outlined @click="addStage" />
              </div>
            </template>
            <template #content>
              <div class="space-y-3">
                <div
                  v-for="(stage, index) in form.screening_stages"
                  :key="index"
                  class="grid gap-3 rounded-2xl border border-surface-200 bg-surface-0 p-4 md:grid-cols-[auto_minmax(0,1fr)_auto]"
                >
                  <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-sm font-semibold text-blue-700">
                    {{ index + 1 }}
                  </span>
                  <div class="grid gap-3 md:grid-cols-2">
                    <div class="space-y-2">
                      <label class="text-sm font-medium text-surface-700">Stage Name</label>
                      <InputText
                        v-model="stage.name"
                        class="w-full"
                        placeholder="Stage name"
                        :invalid="submitted && !stage.name?.trim()"
                      />
                    </div>
                    <div class="space-y-2">
                      <label class="text-sm font-medium text-surface-700">Notes</label>
                      <InputText
                        v-model="stage.description"
                        class="w-full"
                        placeholder="Optional guidance for this stage"
                      />
                    </div>
                  </div>
                  <Button
                    type="button"
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    class="self-start"
                    :disabled="form.screening_stages.length <= 1"
                    @click="removeStage(index)"
                  />
                </div>
                <small v-if="submitted && !hasValidStages" class="text-red-500">
                  Add at least one screening stage with a name.
                </small>
              </div>
            </template>
          </Card>
        </div>

        <div class="space-y-6">
          <Card class="border border-blue-100 bg-blue-50/70 shadow-none">
            <template #title>
              <div class="flex items-center gap-2 text-blue-900">
                <i class="pi pi-verified text-sm" />
                <span class="text-base font-semibold">Posting Review</span>
              </div>
            </template>
            <template #content>
              <div class="space-y-4 text-sm text-surface-700">
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Title</p>
                  <p class="mt-1 font-semibold text-surface-900">{{ form.title || 'Waiting for title' }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Department</p>
                  <p class="mt-1 font-semibold text-surface-900">{{ form.department || 'Select department' }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Hiring Role</p>
                  <p class="mt-1 font-semibold text-surface-900">{{ selectedRoleLabel || 'Select role' }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Salary Range</p>
                  <p class="mt-1 font-semibold text-surface-900">
                    {{ formatCurrency(form.salary_min) }} - {{ formatCurrency(form.salary_max) }}
                  </p>
                </div>
                <Divider />
                <div class="rounded-2xl bg-white/80 p-4">
                  <p class="text-xs font-semibold uppercase tracking-wide text-surface-500">Validation Check</p>
                  <ul class="mt-3 space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                      <i :class="form.title.trim() ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-red-500'" />
                      Title is filled in
                    </li>
                    <li class="flex items-center gap-2">
                      <i :class="form.department ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-red-500'" />
                      Department is selected
                    </li>
                    <li class="flex items-center gap-2">
                      <i :class="form.role_id ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-red-500'" />
                      Hiring role is selected
                    </li>
                    <li class="flex items-center gap-2">
                      <i :class="!salaryRangeInvalid && form.salary_min > 0 && form.salary_max > 0 ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-red-500'" />
                      Salary range is valid
                    </li>
                    <li class="flex items-center gap-2">
                      <i :class="hasValidStages ? 'pi pi-check-circle text-green-600' : 'pi pi-times-circle text-red-500'" />
                      Screening stages are ready
                    </li>
                  </ul>
                </div>
                <Message v-if="salaryRangeInvalid" severity="warn" :closable="false">
                  Maximum salary must be greater than or equal to minimum salary.
                </Message>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <div class="flex w-full justify-end gap-3 pt-2">
        <Button type="button" label="Cancel" severity="secondary" outlined @click="emit('close')" />
        <Button type="submit" :label="posting ? 'Update Job Posting' : 'Create Job Posting'" />
      </div>
    </form>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import hrService from '../services/hr.services'

interface ScreeningStageForm {
  name: string
  description?: string
}

const props = defineProps({
  posting: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'save'])

const statusOptions = [
  { label: 'Open', value: 'Open' },
  { label: 'Closed', value: 'Closed' },
  { label: 'On Hold', value: 'On Hold' },
]

const defaultStages = (): ScreeningStageForm[] => [
  { name: 'Initial Review', description: 'Review applicant documents, qualifications, and fit.' },
  { name: 'Interview', description: 'Conduct the active interview and hands-on assessment.' },
]

const form = ref({
  title: '',
  department: '',
  role_id: null as number | null,
  status: 'Open',
  salary_min: 0,
  salary_max: 0,
  description: '',
  screening_stages: defaultStages(),
})

const departmentOptions = ref<{ label: string; value: string }[]>([])
const roleOptions = ref<{ label: string; value: number }[]>([])
const loadingDepartments = ref(false)
const loadingRoles = ref(false)
const submitted = ref(false)
const formError = ref('')

const selectedRoleLabel = computed(() => roleOptions.value.find((role) => role.value === form.value.role_id)?.label || '')

const normalizedPostingStages = computed(() => props.posting?.screening_stages || props.posting?.screeningStages)
const salaryRangeInvalid = computed(() => Number(form.value.salary_max || 0) < Number(form.value.salary_min || 0))
const hasValidStages = computed(() => form.value.screening_stages.some((stage) => stage.name?.trim()))

const showFieldError = (field: 'title' | 'department' | 'role_id' | 'salary_min' | 'salary_max' | 'description') => {
  if (!submitted.value) return false
  if (field === 'role_id') return !form.value.role_id
  if (field === 'salary_min') return Number(form.value.salary_min || 0) <= 0
  if (field === 'salary_max') return Number(form.value.salary_max || 0) <= 0
  return !String(form.value[field] || '').trim()
}

const addStage = () => {
  form.value.screening_stages.push({ name: '', description: '' })
}

const removeStage = (index: number) => {
  if (form.value.screening_stages.length > 1) {
    form.value.screening_stages.splice(index, 1)
  }
}

const resetForm = () => {
  form.value = {
    title: '',
    department: '',
    role_id: null,
    status: 'Open',
    salary_min: 0,
    salary_max: 0,
    description: '',
    screening_stages: defaultStages(),
  }
  submitted.value = false
  formError.value = ''
}

const loadDepartments = async () => {
  loadingDepartments.value = true
  try {
    const response = await hrService.getDepartmentOptions()
    const items = response?.data || []
    departmentOptions.value = items.map((department: any) => ({
      label: department.code ? `${department.name} (${department.code})` : department.name,
      value: department.name,
    }))
  } finally {
    loadingDepartments.value = false
  }
}

const loadRoles = async () => {
  loadingRoles.value = true
  try {
    const response = await hrService.api.get('/api/store/roles/scoped')
    const items = response?.data?.data || response?.data || []
    const excludedRoleNames = ['super_admin', 'admin', 'store_admin', 'owner', 'supplier_portal']
    const excludedCodes = ['SA', 'ADM', 'OWN', 'SUPP']

    roleOptions.value = items
      .filter((role: any) => {
        const roleName = String(role.name || '').toLowerCase()
        const roleCode = String(role.code || '').toUpperCase()
        return !excludedRoleNames.includes(roleName) && !excludedCodes.includes(roleCode)
      })
      .map((role: any) => ({
        label: role.display_name || role.name,
        value: role.id,
      }))
  } finally {
    loadingRoles.value = false
  }
}

const handleSubmit = () => {
  submitted.value = true
  formError.value = ''
  form.value.screening_stages = form.value.screening_stages
    .map((stage) => ({
      name: stage.name?.trim() || '',
      description: stage.description?.trim() || '',
    }))
    .filter((stage) => stage.name)

  if (
    !form.value.title.trim() ||
    !form.value.department.trim() ||
    !form.value.role_id ||
    !form.value.description.trim() ||
    Number(form.value.salary_min || 0) <= 0 ||
    Number(form.value.salary_max || 0) <= 0
  ) {
    formError.value = 'Please complete the required fields before saving.'
    return
  }

  if (salaryRangeInvalid.value) {
    formError.value = 'Please review the salary range before saving.'
    return
  }

  if (!form.value.screening_stages.length) {
    formError.value = 'Add at least one screening stage with a name.'
    return
  }

  emit('save', {
    ...form.value,
    title: form.value.title.trim(),
    department: form.value.department.trim(),
    description: form.value.description.trim(),
    screening_stages: form.value.screening_stages,
  })
}

const formatCurrency = (value: number | string) =>
  new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 0,
  }).format(Number(value || 0))

watch(
  () => props.posting,
  (newPosting) => {
    if (!newPosting) {
      resetForm()
      return
    }

    form.value = {
      title: newPosting.title || '',
      department: newPosting.department || '',
      role_id: newPosting.role_id || newPosting.role?.id || null,
      status: newPosting.status || 'Open',
      salary_min: Number(newPosting.salary_min || 0),
      salary_max: Number(newPosting.salary_max || 0),
      description: newPosting.description || '',
      screening_stages:
        normalizedPostingStages.value?.length
          ? normalizedPostingStages.value.map((stage: any) => ({
              name: stage.name || stage.stage_name || '',
              description: stage.description || '',
            }))
          : defaultStages(),
    }
    submitted.value = false
    formError.value = ''
  },
  { immediate: true, deep: true },
)

onMounted(async () => {
  await Promise.all([loadDepartments(), loadRoles()])
})
</script>
