<template>
  <div class="p-4 min-h-screen space-y-4">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-xl font-bold text-gray-800">Inventory Activity Log Detail</h1>
        <p class="text-xs text-gray-500 mt-0.5">Audit entry with linked source record details.</p>
      </div>
      <Button label="Back" icon="pi pi-arrow-left" severity="secondary" outlined size="small" @click="goBack" />
    </div>

    <Card v-if="loading">
      <template #content>
        <div class="py-8 flex justify-center">
          <ProgressSpinner style="width: 32px; height: 32px" strokeWidth="6" />
        </div>
      </template>
    </Card>

    <template v-else-if="log">
      <Card>
        <template #title>Log Info</template>
        <template #content>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <p class="text-xs text-gray-500">Date</p>
              <p>{{ formatDateTime(log.created_at) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Action</p>
              <Tag :value="formatKey(log.action)" severity="info" />
            </div>
            <div>
              <p class="text-xs text-gray-500">Entity Type</p>
              <p>{{ formatKey(log.entity_type) }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Entity ID</p>
              <p>{{ log.entity_id || '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">User</p>
              <p>{{ log.user ? `${log.user.fname || ''} ${log.user.lname || ''}`.trim() : '-' }}</p>
            </div>
            <div>
              <p class="text-xs text-gray-500">Branch</p>
              <p>{{ log.branch?.name || '-' }}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-xs text-gray-500">Description</p>
              <p>{{ log.description || '-' }}</p>
            </div>
            <div class="md:col-span-2">
              <p class="text-xs text-gray-500">Meta</p>
              <pre class="text-xs bg-slate-50 border rounded p-3 overflow-auto">{{ pretty(log.meta) }}</pre>
            </div>
          </div>
        </template>
      </Card>

      <Card>
        <template #title>Linked Source</template>
        <template #content>
          <div v-if="source?.record" class="space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <p class="font-semibold">{{ source.type.replaceAll('_', ' ') }}</p>
                <p class="text-xs text-gray-500">Source record fetched by entity id.</p>
              </div>
              <Button
                v-if="targetRouteName && source.record?.id"
                label="Open Source Record"
                icon="pi pi-external-link"
                size="small"
                severity="info"
                @click="openSource"
              />
            </div>
            <pre class="text-xs bg-slate-50 border rounded p-3 overflow-auto">{{ pretty(source.record) }}</pre>
          </div>
          <div v-else class="text-sm text-gray-500">No source record found for this log.</div>
        </template>
      </Card>
    </template>
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

const loading = ref(false)
const log = ref<any | null>(null)
const source = ref<any | null>(null)

const logId = computed(() => Number(route.params.id))

const targetRouteName = computed(() => {
  const type = source.value?.type
  if (type === 'stock_transfer') return 'inventory.transfers.detail'
  if (type === 'stock_adjustment') return 'inventory.adjustments.detail'
  if (type === 'stock_count') return 'inventory.stock-counts.detail'
  if (type === 'branch_inventory') return 'inventory.items.edit'
  return null
})

const loadLog = async () => {
  if (!logId.value) return

  loading.value = true
  try {
    const response = await inventoryService.getActivityLog(logId.value)
    if (response.success) {
      log.value = response.data
      source.value = response.source
    }
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load activity log detail',
      life: 3000,
    })
  } finally {
    loading.value = false
  }
}

const openSource = () => {
  if (!targetRouteName.value || !source.value?.record?.id) return
  router.push({
    name: targetRouteName.value,
    params: { id: String(source.value.record.id) },
  })
}

const goBack = () => router.push({ name: 'inventory.activity-logs' })

const pretty = (value: any) => JSON.stringify(value ?? {}, null, 2)
const formatDateTime = (value?: string) => {
  if (!value) return '-'
  const d = new Date(value)
  return Number.isNaN(d.getTime()) ? '-' : d.toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
const formatKey = (value?: string) => (value ? value.replace(/^inventory\./, '').replaceAll('.', ' ') : 'N/A')

onMounted(loadLog)
</script>

