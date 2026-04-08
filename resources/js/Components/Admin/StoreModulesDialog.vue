<template>
  <Dialog v-model:visible="visibleLocal" :header="dialogTitle" :modal="true" :style="{ width: '850px' }" @hide="handleHide">
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <div class="text-sm text-gray-600">
          Toggle modules for <span class="font-semibold">{{ storeName || 'this store' }}</span>, then click Save.
        </div>
        <Button label="Save Changes" icon="pi pi-save" :disabled="!hasChanges || loading" :loading="loading" @click="saveOverrides" />
      </div>

      <DataTable :value="modules" :loading="loading" class="p-datatable-sm">
        <Column field="name" header="Module" />
        <Column header="Effective">
          <template #body="{ data }">
            <Badge :value="data.effective_enabled ? 'Enabled' : 'Disabled'"
              :severity="data.effective_enabled ? 'success' : 'danger'" />
          </template>
        </Column>
        <Column header="Override?">
          <template #body="{ data }">
            <div class="flex items-center gap-3">
              <InputSwitch
                :modelValue="draftOverrides[data.module_key] ?? data.effective_enabled === true"
                @update:modelValue="(val: boolean) => onToggleDraft(data.module_key, val)"
                :disabled="loading"
              />
              <Button size="small" text label="Reset" :disabled="loading" @click="resetDraft(data.module_key)" />
              <Badge v-if="draftOverrides[data.module_key] === true" value="Forced On" severity="success" />
              <Badge v-else-if="draftOverrides[data.module_key] === false" value="Forced Off" severity="danger" />
              <span v-else class="text-gray-500 text-sm">None</span>
              <Badge v-if="draftOverrides[data.module_key] !== originalOverrides[data.module_key]" value="Pending" severity="warning" />
            </div>
          </template>
        </Column>
      </DataTable>
    </div>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import Dialog from 'primevue/dialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Badge from 'primevue/badge'
import InputSwitch from 'primevue/inputswitch'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'

const props = defineProps<{
  modelValue: boolean
  storeId: number | null
  storeName?: string | null
}>()

const emit = defineEmits(['update:modelValue'])

const toast = useToast()
const visibleLocal = ref<boolean>(props.modelValue)
const modules = ref<any[]>([])
const loading = ref(false)
const draftOverrides = ref<Record<string, boolean | null>>({})
const originalOverrides = ref<Record<string, boolean | null>>({})

const hasChanges = computed(() =>
  Object.keys(draftOverrides.value).some((k) => draftOverrides.value[k] !== originalOverrides.value[k])
)

const dialogTitle = computed(() => `Modules Override${props.storeName ? ' • ' + props.storeName : ''}`)

watch(() => props.modelValue, (val) => {
  visibleLocal.value = val
  if (val) {
    loadModules()
  }
})

watch(visibleLocal, (val) => emit('update:modelValue', val))

async function loadModules() {
  if (!props.storeId) return
  loading.value = true
  try {
    const res = await axiosClient.get('/api/admin/store-modules', { params: { store_id: props.storeId } })
    modules.value = res.data?.data?.modules ?? []
    draftOverrides.value = {}
    originalOverrides.value = {}
    modules.value.forEach((m: any) => {
      draftOverrides.value[m.module_key] = m.override ?? null
      originalOverrides.value[m.module_key] = m.override ?? null
    })
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to load modules', life: 3000 })
  } finally {
    loading.value = false
  }
}

function onToggleDraft(moduleKey: string, value: boolean) {
  draftOverrides.value[moduleKey] = value
}

function resetDraft(moduleKey: string) {
  draftOverrides.value[moduleKey] = null
}

async function saveOverrides() {
  if (!props.storeId) return
  const changes = Object.keys(draftOverrides.value).filter(
    (key) => draftOverrides.value[key] !== originalOverrides.value[key]
  )
  if (changes.length === 0) return

  loading.value = true
  try {
    for (const moduleKey of changes) {
      await axiosClient.post('/api/admin/store-modules/override', {
        store_id: props.storeId,
        module_key: moduleKey,
        allow: draftOverrides.value[moduleKey],
      })
    }
    originalOverrides.value = { ...draftOverrides.value }
    toast.add({ severity: 'success', summary: 'Saved', detail: 'Overrides updated', life: 2000 })
    await loadModules()
  } catch (e) {
    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to update overrides', life: 3000 })
  } finally {
    loading.value = false
  }
}

function handleHide() {
  visibleLocal.value = false
}
</script>
