<template>
  <div class="p-4 min-h-screen">
    <div class="mb-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Button icon="pi pi-arrow-left" text severity="secondary" @click="goBack" />
        <div>
          <h1 class="text-xl font-bold text-gray-800">Reorder Suggestion Detail</h1>
          <p class="text-xs text-gray-500 mt-0.5">Review, approve, reject, or implement this suggestion.</p>
        </div>
      </div>
      <div class="flex gap-2" v-if="suggestion">
        <Button
          v-if="suggestion.status === 'pending'"
          label="Approve"
          size="small"
          icon="pi pi-check"
          severity="success"
          @click="openAction('approve')"
        />
        <Button
          v-if="suggestion.status === 'pending'"
          label="Reject"
          size="small"
          icon="pi pi-times"
          severity="danger"
          outlined
          @click="openAction('reject')"
        />
        <Button
          v-if="suggestion.status === 'approved'"
          label="Implement"
          size="small"
          icon="pi pi-send"
          severity="warn"
          @click="openAction('implement')"
        />
      </div>
    </div>

    <div v-if="loading" class="space-y-3">
      <Skeleton height="8rem" />
      <Skeleton height="14rem" />
    </div>

    <div v-else-if="suggestion" class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Product</p>
            <p class="font-semibold">{{ suggestion.product_name || '-' }}</p>
            <p class="text-xs text-gray-500">{{ suggestion.product_sku || '-' }}</p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Branch</p>
            <p class="font-semibold">{{ suggestion.branch_name || '-' }}</p>
            <p class="text-xs text-gray-500">Current Stock: {{ suggestion.current_stock || 0 }}</p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Suggested Qty</p>
            <p class="font-semibold">{{ suggestion.suggested_quantity || 0 }}</p>
            <p class="text-xs text-gray-500">Est Cost: {{ formatCurrency(suggestion.estimated_cost || 0) }}</p>
          </template>
        </Card>
        <Card>
          <template #content>
            <p class="text-xs text-gray-500">Status</p>
            <Tag :value="capitalize(suggestion.status)" :severity="statusSeverity(suggestion.status)" />
            <div class="mt-2">
              <Tag :value="capitalize(suggestion.priority)" :severity="prioritySeverity(suggestion.priority)" />
            </div>
          </template>
        </Card>
      </div>

      <Card>
        <template #title>Reason</template>
        <template #content>
          <p class="text-sm text-gray-700">{{ suggestion.reason || 'No reason provided.' }}</p>
        </template>
      </Card>

      <Card>
        <template #title>Timeline</template>
        <template #content>
          <Timeline :value="timelineEvents" align="left">
            <template #content="{ item }">
              <div class="text-sm">
                <div class="font-medium">{{ item.title }}</div>
                <div class="text-gray-500 text-xs">{{ item.time }}</div>
                <div v-if="item.note" class="text-gray-600 mt-1">{{ item.note }}</div>
              </div>
            </template>
          </Timeline>
        </template>
      </Card>
    </div>

    <Dialog v-model:visible="actionDialog.visible" :header="actionDialog.title" modal class="w-full max-w-lg">
      <div class="space-y-3">
        <p class="text-sm text-gray-600">{{ actionDialog.description }}</p>
        <Textarea v-model="actionDialog.notes" rows="4" class="w-full" placeholder="Optional notes" />
      </div>
      <template #footer>
        <Button label="Cancel" text severity="secondary" @click="actionDialog.visible = false" />
        <Button :label="actionDialog.confirmLabel" :severity="actionDialog.severity" :loading="submitting" @click="submitAction" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import inventoryService from '@/services/inventory.service'

const route = useRoute()
const router = useRouter()
const toast = useToast()

const loading = ref(false)
const submitting = ref(false)
const suggestion = ref<any | null>(null)

const actionDialog = reactive({
  visible: false,
  type: '' as 'approve' | 'reject' | 'implement' | '',
  title: '',
  description: '',
  confirmLabel: 'Confirm',
  severity: 'info' as 'success' | 'danger' | 'warn' | 'info',
  notes: '',
})

const suggestionId = computed(() => Number(route.params.id))

const normalizeSuggestion = (row: any) => ({
  ...row,
  product_name: row?.product?.product_name || row?.product?.name || '-',
  product_sku: row?.product?.sku || '-',
  branch_name: row?.branch?.name || '-',
})

const formatDateTime = (value?: string | null) => {
  if (!value) return '-'
  const d = new Date(value)
  if (Number.isNaN(d.getTime())) return '-'
  return d.toLocaleString('en-PH', {
    year: 'numeric',
    month: 'short',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatCurrency = (amount: number) => new Intl.NumberFormat('en-PH', {
  style: 'currency',
  currency: 'PHP',
  minimumFractionDigits: 2,
}).format(Number(amount || 0))

const capitalize = (value: string) => {
  const text = String(value || '')
  return text ? `${text.charAt(0).toUpperCase()}${text.slice(1)}` : '-'
}

const statusSeverity = (status: string) => {
  const key = String(status || '').toLowerCase()
  if (key === 'approved') return 'success'
  if (key === 'rejected') return 'danger'
  if (key === 'implemented') return 'info'
  if (key === 'cancelled') return 'secondary'
  return 'warn'
}

const prioritySeverity = (priority: string) => {
  const key = String(priority || '').toLowerCase()
  if (key === 'critical') return 'danger'
  if (key === 'high') return 'warn'
  if (key === 'medium') return 'info'
  return 'secondary'
}

const timelineEvents = computed(() => {
  if (!suggestion.value) return []
  const s = suggestion.value
  const events = [
    {
      title: 'Suggestion Created',
      time: formatDateTime(s.suggested_at || s.created_at),
      note: s.reason || null,
    },
  ]

  if (s.approved_at) {
    events.push({
      title: s.status === 'rejected' ? 'Suggestion Rejected' : 'Suggestion Approved',
      time: formatDateTime(s.approved_at),
      note: s.approval_notes || null,
    })
  }

  if (s.implemented_at) {
    events.push({
      title: 'Suggestion Implemented',
      time: formatDateTime(s.implemented_at),
      note: s.implementation_notes || null,
    })
  }

  return events
})

const loadSuggestion = async () => {
  if (!suggestionId.value) return
  loading.value = true
  try {
    const response = await inventoryService.getReorderSuggestion(suggestionId.value)
    suggestion.value = normalizeSuggestion(response?.data)
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to load suggestion detail',
      life: 3000,
    })
    router.push({ name: 'inventory.reorder-suggestions' })
  } finally {
    loading.value = false
  }
}

const goBack = () => router.push({ name: 'inventory.reorder-suggestions' })

const openAction = (type: 'approve' | 'reject' | 'implement') => {
  actionDialog.type = type
  actionDialog.notes = ''
  actionDialog.visible = true

  if (type === 'approve') {
    actionDialog.title = 'Approve Suggestion'
    actionDialog.description = 'Approve this suggestion and allow procurement follow-through.'
    actionDialog.confirmLabel = 'Approve'
    actionDialog.severity = 'success'
    return
  }

  if (type === 'reject') {
    actionDialog.title = 'Reject Suggestion'
    actionDialog.description = 'Reject this suggestion with an optional note.'
    actionDialog.confirmLabel = 'Reject'
    actionDialog.severity = 'danger'
    return
  }

  actionDialog.title = 'Implement Suggestion'
  actionDialog.description = 'Mark this suggestion as implemented.'
  actionDialog.confirmLabel = 'Implement'
  actionDialog.severity = 'warn'
}

const submitAction = async () => {
  if (!actionDialog.type || !suggestionId.value) return

  submitting.value = true
  try {
    if (actionDialog.type === 'approve') {
      await inventoryService.approveReorderSuggestion(suggestionId.value, actionDialog.notes || undefined)
    } else if (actionDialog.type === 'reject') {
      await inventoryService.rejectReorderSuggestion(suggestionId.value, actionDialog.notes || undefined)
    } else if (actionDialog.type === 'implement') {
      await inventoryService.implementReorderSuggestion(suggestionId.value, actionDialog.notes || undefined)
    }

    actionDialog.visible = false
    await loadSuggestion()
    toast.add({
      severity: 'success',
      summary: 'Success',
      detail: 'Suggestion updated successfully.',
      life: 2500,
    })
  } catch (error: any) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: error?.response?.data?.message || 'Failed to update suggestion',
      life: 3000,
    })
  } finally {
    submitting.value = false
  }
}

onMounted(loadSuggestion)
</script>
