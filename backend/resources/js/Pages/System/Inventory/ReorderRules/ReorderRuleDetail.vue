<template>
  <div class="p-4 min-h-screen max-w-4xl mx-auto">
    <div class="mb-4 flex items-center justify-between gap-2">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-arrow-left" text severity="secondary" @click="goBack" />
        <div>
          <h1 class="text-xl font-bold text-gray-800">Reorder Rule Detail</h1>
          <p class="text-xs text-gray-500">Review and maintain branch replenishment rule.</p>
        </div>
      </div>
      <div class="flex gap-2">
        <Button label="Edit" icon="pi pi-pencil" severity="warning" outlined @click="editRule" />
        <Button label="Delete" icon="pi pi-trash" severity="danger" outlined :loading="deleting" @click="removeRule" />
      </div>
    </div>

    <Card v-if="!loading">
      <template #content>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div><span class="text-gray-500">Product:</span> <span class="font-medium">{{ productLabel }}</span></div>
          <div><span class="text-gray-500">Branch:</span> <span class="font-medium">{{ rule.branch?.name || '-' }}</span></div>
          <div><span class="text-gray-500">Basis:</span> <span class="font-medium">{{ toLabel(rule.basis_type || 'reorder_point') }}</span></div>
          <div><span class="text-gray-500">Reorder Point:</span> <span class="font-medium">{{ rule.reorder_point ?? '-' }}</span></div>
          <div><span class="text-gray-500">Reorder Quantity:</span> <span class="font-medium">{{ rule.reorder_quantity ?? '-' }}</span></div>
          <div><span class="text-gray-500">Safety Stock:</span> <span class="font-medium">{{ rule.safety_stock ?? '-' }}</span></div>
          <div><span class="text-gray-500">Maximum Stock:</span> <span class="font-medium">{{ rule.maximum_stock ?? '-' }}</span></div>
          <div><span class="text-gray-500">Lead Time (days):</span> <span class="font-medium">{{ rule.lead_time_days ?? '-' }}</span></div>
          <div><span class="text-gray-500">Avg Daily Demand:</span> <span class="font-medium">{{ rule.avg_daily_demand ?? '-' }}</span></div>
          <div><span class="text-gray-500">Review Period (days):</span> <span class="font-medium">{{ rule.review_period_days ?? '-' }}</span></div>
          <div>
            <span class="text-gray-500">Priority:</span>
            <Tag class="ml-2" :value="toLabel(rule.priority)" :severity="prioritySeverity(rule.priority)" />
          </div>
          <div>
            <span class="text-gray-500">Status:</span>
            <Tag class="ml-2" :value="rule.is_active ? 'Active' : 'Inactive'" :severity="rule.is_active ? 'success' : 'secondary'" />
          </div>
          <div>
            <span class="text-gray-500">Auto Generate PR/PO:</span>
            <span class="font-medium">{{ rule.auto_generate_po ? 'Yes' : 'No' }}</span>
          </div>
          <div class="md:col-span-2">
            <span class="text-gray-500">Notes:</span>
            <p class="mt-1">{{ rule.notes || '-' }}</p>
          </div>
          <div><span class="text-gray-500">Created At:</span> <span class="font-medium">{{ formatDate(rule.created_at) }}</span></div>
          <div><span class="text-gray-500">Updated At:</span> <span class="font-medium">{{ formatDate(rule.updated_at) }}</span></div>
        </div>
      </template>
    </Card>

    <Card v-else>
      <template #content>
        <div class="text-center py-8 text-gray-500">Loading rule...</div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '@/services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const id = Number(route.params.id)
const loading = ref(false)
const deleting = ref(false)
const rule = ref<any>({})

const productLabel = computed(() => {
  const name = rule.value?.product?.product_name || rule.value?.product?.name || '-'
  const sku = rule.value?.product?.sku || ''
  return sku ? `${name} (${sku})` : name
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

const formatDate = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleString('en-PH', { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' })
}

const loadRule = async () => {
  loading.value = true
  try {
    const res = await inventoryService.getReorderRule(id)
    rule.value = res?.data || {}
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to load reorder rule', life: 3000 })
    goBack()
  } finally {
    loading.value = false
  }
}

const goBack = () => router.push({ name: 'inventory.reorder-rules' })
const editRule = () => router.push({ name: 'inventory.reorder-rules.edit', params: { id } })

const removeRule = async () => {
  if (!confirm('Delete this reorder rule?')) return
  deleting.value = true
  try {
    await inventoryService.deleteReorderRule(id)
    toast.add({ severity: 'success', summary: 'Deleted', detail: 'Reorder rule removed.', life: 2500 })
    goBack()
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Error', detail: error?.response?.data?.message || 'Failed to delete reorder rule', life: 3000 })
  } finally {
    deleting.value = false
  }
}

onMounted(() => {
  loadRule()
})
</script>
