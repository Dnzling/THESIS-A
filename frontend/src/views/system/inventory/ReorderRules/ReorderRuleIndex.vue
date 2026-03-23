<template>
  <div class="p-4 min-h-screen">
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Reorder Rules</h1>
        <p class="text-xs text-gray-500 mt-0.5">Branch-based replenishment settings per product.</p>
      </div>
      <div class="flex gap-2">
        <Button
          label="Create Rule"
          icon="pi pi-plus"
          size="small"
          severity="success"
          @click="router.push({ name: 'inventory.reorder-rules.create' })"
        />
        <Button
          label="Auto Create Rules"
          icon="pi pi-plus-circle"
          size="small"
          severity="info"
          :loading="autoCreating"
          @click="autoCreateRules(false)"
        />
        <Button
          label="Sync Existing"
          icon="pi pi-refresh"
          size="small"
          severity="secondary"
          outlined
          :loading="autoCreating"
          @click="autoCreateRules(true)"
        />
      </div>
    </div>

    <Card class="mb-4">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Search</label>
            <InputText v-model="filters.search" placeholder="Product name or SKU" />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Priority</label>
            <Select v-model="filters.priority" :options="priorityOptions" optionLabel="label" optionValue="value" showClear />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Rule Type</label>
            <Select v-model="filters.rule_type" :options="ruleTypeOptions" optionLabel="label" optionValue="value" showClear />
          </div>
          <div class="flex flex-col gap-1">
            <label class="text-xs font-semibold text-gray-600">Status</label>
            <Select v-model="filters.is_active" :options="activeOptions" optionLabel="label" optionValue="value" showClear />
          </div>
        </div>
      </template>
    </Card>

    <Card>
      <template #content>
        <DataTable
          :value="rules"
          :loading="loading"
          stripedRows
          class="p-datatable-sm"
          paginator
          :rows="filters.per_page"
          :totalRecords="pagination.total"
          :first="(pagination.current_page - 1) * filters.per_page"
          @page="onPage"
        >
          <Column header="Product" style="min-width: 220px">
            <template #body="{ data }">
              <div class="text-sm">
                <div class="font-medium">{{ data.product_name || '-' }}</div>
                <div class="text-gray-500">{{ data.product_sku || '-' }}</div>
              </div>
            </template>
          </Column>
          <Column field="rule_type" header="Rule Type" style="width: 120px">
            <template #body="{ data }">{{ toLabel(data.rule_type) }}</template>
          </Column>
          <Column field="trigger_type" header="Trigger" style="width: 140px">
            <template #body="{ data }">{{ toLabel(data.trigger_type) }}</template>
          </Column>
          <Column field="reorder_point" header="Reorder Point" style="width: 120px" />
          <Column field="reorder_quantity" header="Reorder Qty" style="width: 120px" />
          <Column field="safety_stock" header="Safety" style="width: 100px" />
          <Column field="maximum_stock" header="Max" style="width: 100px" />
          <Column field="priority" header="Priority" style="width: 110px">
            <template #body="{ data }">
              <Tag :value="toLabel(data.priority)" :severity="prioritySeverity(data.priority)" />
            </template>
          </Column>
          <Column field="is_active" header="Status" style="width: 110px">
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'secondary'" />
            </template>
          </Column>
          <Column header="Actions" style="width: 150px">
            <template #body="{ data }">
              <div class="flex gap-1">
                <Button icon="pi pi-eye" rounded text severity="info" @click="openDetail(data.id)" />
                <Button icon="pi pi-pencil" rounded text severity="warning" @click="openEdit(data.id)" />
                <Button icon="pi pi-trash" rounded text severity="danger" @click="removeRule(data.id)" />
              </div>
            </template>
          </Column>
          <template #empty>
            <div class="text-center py-8 text-gray-500">No reorder rules found for this branch.</div>
          </template>
        </DataTable>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '@/services/inventory.service'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const authStore = useAuthStore()

const loading = ref(false)
const autoCreating = ref(false)
const rules = ref<any[]>([])

const pagination = reactive({
  total: 0,
  current_page: 1,
})

const filters = reactive({
  search: '',
  priority: null as string | null,
  rule_type: null as string | null,
  is_active: null as boolean | null,
  page: 1,
  per_page: 15,
})

const userBranchId = Number(
  (authStore.user as any)?.branch_id ||
  (authStore.user as any)?.branch?.id ||
  (authStore.user as any)?.employee?.branch_id ||
  0
)

const priorityOptions = [
  { label: 'Critical', value: 'critical' },
  { label: 'High', value: 'high' },
  { label: 'Medium', value: 'medium' },
  { label: 'Low', value: 'low' },
]

const ruleTypeOptions = [
  { label: 'Automatic', value: 'automatic' },
  { label: 'Manual', value: 'manual' },
  { label: 'Demand Based', value: 'demand_based' },
]

const activeOptions = [
  { label: 'Active', value: true },
  { label: 'Inactive', value: false },
]

const extractRows = (payload: any): any[] => {
  if (Array.isArray(payload)) return payload
  if (Array.isArray(payload?.data)) return payload.data
  return []
}

const normalizeRule = (row: any) => ({
  ...row,
  product_name: row?.product?.product_name || row?.product?.name || '-',
  product_sku: row?.product?.sku || '-',
})

const toLabel = (value: string) => {
  const text = String(value || '')
  if (!text) return '-'
  return text
    .split('_')
    .map((w) => `${w.charAt(0).toUpperCase()}${w.slice(1)}`)
    .join(' ')
}

const prioritySeverity = (priority: string) => {
  const key = String(priority || '').toLowerCase()
  if (key === 'critical') return 'danger'
  if (key === 'high') return 'warn'
  if (key === 'medium') return 'info'
  return 'secondary'
}

const loadRules = async () => {
  loading.value = true
  try {
    const params: any = { ...filters }
    if (userBranchId > 0) params.branch_id = userBranchId
    const response = await inventoryService.getReorderRules(params)
    const rows = extractRows(response?.data).map(normalizeRule)
    rules.value = rows
    pagination.total = Number(response?.data?.total || rows.length || 0)
    pagination.current_page = Number(response?.data?.current_page || filters.page || 1)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load reorder rules',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const autoCreateRules = async (overwrite = false) => {
  autoCreating.value = true
  try {
    const payload: any = { overwrite }
    if (userBranchId > 0) payload.branch_id = userBranchId
    const response = await inventoryService.autoCreateReorderRules(payload)
    toast.add({
      severity: 'success',
      summary: 'Reorder Rules',
      detail: response?.message || 'Auto-create completed.',
      life: 3500,
    })
    await loadRules()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to auto-create reorder rules',
      life: 3500,
    })
  } finally {
    autoCreating.value = false
  }
}

const openDetail = (id: number) => {
  router.push({ name: 'inventory.reorder-rules.detail', params: { id } })
}

const openEdit = (id: number) => {
  router.push({ name: 'inventory.reorder-rules.edit', params: { id } })
}

const removeRule = async (id: number) => {
  if (!confirm('Delete this reorder rule?')) return
  try {
    await inventoryService.deleteReorderRule(id)
    toast.add({ severity: 'success', summary: 'Deleted', detail: 'Reorder rule removed.', life: 2500 })
    loadRules()
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to delete reorder rule',
      life: 3000,
    })
  }
}

const onPage = (event: any) => {
  filters.page = event.page + 1
  filters.per_page = event.rows
  loadRules()
}

let watchTimer: ReturnType<typeof setTimeout> | null = null
watch(
  () => [filters.search, filters.priority, filters.rule_type, filters.is_active],
  () => {
    filters.page = 1
    if (watchTimer) clearTimeout(watchTimer)
    watchTimer = setTimeout(() => loadRules(), 250)
  },
)

onMounted(() => {
  loadRules()
})
</script>
