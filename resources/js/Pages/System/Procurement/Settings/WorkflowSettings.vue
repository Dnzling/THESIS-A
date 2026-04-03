<template>
  <div class="space-y-6">
    <Card>
      <template #content>
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Workflow Settings</h2>
            <p class="text-sm text-gray-500">Configure approval behavior per business size.</p>
          </div>
          <div class="flex gap-2">
            <Button label="Reload" icon="pi pi-refresh" severity="secondary" outlined :loading="loading" @click="loadSettings" />
            <Button label="Save Settings" icon="pi pi-save" severity="info" :loading="saving" @click="saveSettings" />
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Skeleton height="4rem" />
          <Skeleton height="4rem" />
          <Skeleton height="4rem" />
          <Skeleton height="4rem" />
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="space-y-2">
            <label class="text-sm font-semibold text-gray-700">Business Size</label>
            <Select v-model="form.business_size" :options="businessSizes" optionLabel="label" optionValue="value" fluid />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-gray-700">Workflow Mode</label>
            <Select v-model="form.workflow_mode" :options="workflowModes" optionLabel="label" optionValue="value" fluid />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-gray-700">Self-Approval Threshold</label>
            <InputNumber
              v-model="form.self_approval_threshold"
              :disabled="!form.allow_self_approval"
              mode="currency"
              currency="PHP"
              locale="en-PH"
              fluid
            />
          </div>

          <div class="space-y-2">
            <label class="text-sm font-semibold text-gray-700">Minimum Distinct Approvers</label>
            <InputNumber v-model="form.min_approvers_required" :min="1" :max="10" showButtons fluid />
          </div>

          <div class="flex items-center gap-2 pt-2">
            <Checkbox v-model="form.allow_self_approval" inputId="allow_self_approval" :binary="true" />
            <label for="allow_self_approval" class="text-sm font-medium text-gray-700">Allow self-approval</label>
          </div>

          <div class="flex items-center gap-2 pt-2">
            <Checkbox v-model="form.enforce_separation_of_duties" inputId="sod" :binary="true" />
            <label for="sod" class="text-sm font-medium text-gray-700">Enforce separation of duties</label>
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <div class="space-y-3">
          <h3 class="text-base font-semibold text-gray-800">Quick Preset</h3>
          <p class="text-sm text-gray-500">Apply a baseline policy for Small, Medium, or Enterprise.</p>
          <div class="flex flex-wrap gap-2">
            <Button
              v-for="preset in businessSizes"
              :key="preset.value"
              :label="`Apply ${preset.label}`"
              severity="info"
              outlined
              :loading="applyingPreset === preset.value"
              @click="applyPreset(preset.value)"
            />
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useToast } from 'primevue/usetoast'
import procurementService from '@/services/procurement.service'

type BusinessSize = 'small' | 'medium' | 'enterprise'
type WorkflowMode = 'simple' | 'standard' | 'strict'

const toast = useToast()
const loading = ref(false)
const saving = ref(false)
const applyingPreset = ref<BusinessSize | null>(null)

const form = reactive<{
  business_size: BusinessSize
  workflow_mode: WorkflowMode
  allow_self_approval: boolean
  self_approval_threshold: number | null
  enforce_separation_of_duties: boolean
  min_approvers_required: number
}>({
  business_size: 'medium',
  workflow_mode: 'standard',
  allow_self_approval: false,
  self_approval_threshold: null,
  enforce_separation_of_duties: true,
  min_approvers_required: 1,
})

const businessSizes = [
  { label: 'Small', value: 'small' },
  { label: 'Medium', value: 'medium' },
  { label: 'Enterprise', value: 'enterprise' },
]

const workflowModes = [
  { label: 'Simple', value: 'simple' },
  { label: 'Standard', value: 'standard' },
  { label: 'Strict', value: 'strict' },
]

function patchForm(data: any) {
  form.business_size = (data?.business_size || 'medium') as BusinessSize
  form.workflow_mode = (data?.workflow_mode || 'standard') as WorkflowMode
  form.allow_self_approval = Boolean(data?.allow_self_approval)
  form.self_approval_threshold = data?.self_approval_threshold != null ? Number(data.self_approval_threshold) : null
  form.enforce_separation_of_duties = data?.enforce_separation_of_duties !== false
  form.min_approvers_required = Number(data?.min_approvers_required || 1)
}

async function loadSettings() {
  loading.value = true
  try {
    const response = await procurementService.getWorkflowSettings()
    patchForm(response?.data || response)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load workflow settings.',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

async function saveSettings() {
  saving.value = true
  try {
    await procurementService.updateWorkflowSettings({
      business_size: form.business_size,
      workflow_mode: form.workflow_mode,
      allow_self_approval: form.allow_self_approval,
      self_approval_threshold: form.self_approval_threshold,
      enforce_separation_of_duties: form.enforce_separation_of_duties,
      min_approvers_required: form.min_approvers_required,
    })
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Workflow settings updated.', life: 2500 })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to save workflow settings.',
      life: 3000,
    })
  } finally {
    saving.value = false
  }
}

async function applyPreset(size: BusinessSize) {
  applyingPreset.value = size
  try {
    const response = await procurementService.applyWorkflowPreset(size)
    patchForm(response?.data || response)
    toast.add({ severity: 'success', summary: 'Preset Applied', detail: `${size} workflow applied.`, life: 2500 })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to apply preset.',
      life: 3000,
    })
  } finally {
    applyingPreset.value = null
  }
}

onMounted(loadSettings)
</script>

