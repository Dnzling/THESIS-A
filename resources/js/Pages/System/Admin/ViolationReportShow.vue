<template>
  <Toast />
  <div class="max-w-3xl mx-auto py-6">
    <Card>
      <template #header>
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold">Violation Report</h2>
            <p class="text-sm text-gray-500">Review report details and take action.</p>
          </div>
          <Button label="Back" severity="secondary" size="small" @click="goBack" />
        </div>
      </template>

      <template #content>
        <div v-if="loading" class="p-4 text-sm text-gray-500">Loading report...</div>
        <div v-else class="space-y-4 text-sm">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <div class="text-xs text-gray-500">Store</div>
              <div class="font-medium">{{ report?.store?.name || '-' }}</div>
              <div class="text-xs text-gray-400">{{ report?.store?.store_code || 'No code' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Status</div>
              <Tag :value="formatStatus(report?.status)" :severity="statusSeverity(report?.status)" />
            </div>
            <div>
              <div class="text-xs text-gray-500">Reporter</div>
              <div class="font-medium">
                {{ report?.reporter?.first_name || 'Anonymous' }} {{ report?.reporter?.last_name || '' }}
              </div>
              <div class="text-xs text-gray-400">{{ report?.reporter?.email || report?.reporter_type || '-' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Reported At</div>
              <div class="font-medium">{{ formatDate(report?.created_at) }}</div>
            </div>
          </div>

          <Divider />

          <div>
            <div class="text-xs text-gray-500">Reason</div>
            <div class="font-medium">{{ report?.report_reason || '-' }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500">Details</div>
            <div class="font-medium">{{ report?.report_details || 'No additional details.' }}</div>
          </div>

          <div v-if="(report?.evidence_urls || []).length">
            <div class="text-xs text-gray-500">Evidence</div>
            <ul class="list-disc list-inside text-sm text-blue-600">
              <li v-for="(url, index) in report?.evidence_urls" :key="`evidence-${index}`">
                <a :href="url" target="_blank" rel="noopener">{{ url }}</a>
              </li>
            </ul>
          </div>

          <Divider />

          <div>
            <div class="text-xs text-gray-500">Action Taken</div>
            <div v-if="report?.action_type" class="space-y-1">
              <Tag :value="formatStatus(report.action_type)" severity="info" />
              <div class="text-xs text-gray-500">{{ report.action_reason }}</div>
              <div class="text-xs text-gray-400">By {{ actionedByLabel }} on {{ formatDate(report.actioned_at) }}</div>
            </div>
            <div v-else class="text-sm text-gray-400">No action taken yet.</div>
          </div>
        </div>
      </template>

      <template #footer>
        <div class="flex justify-end space-x-2">
          <Button label="Suspend Store" severity="warning" size="small" :disabled="actionDisabled" @click="openAction('suspend')" />
          <Button label="Ban Store" severity="danger" size="small" :disabled="actionDisabled" @click="openAction('ban')" />
        </div>
      </template>
    </Card>

    <Dialog v-model:visible="actionDialog.visible" header="Confirm Action" modal :closable="false">
      <div class="p-2 space-y-2">
        <p class="text-sm">{{ actionDialog.message }}</p>
        <label class="text-xs font-medium text-gray-600">Reason</label>
        <Textarea v-model="actionDialog.reason" rows="4" class="w-full" />
      </div>
      <template #footer>
        <div class="flex justify-end space-x-2">
          <Button label="Cancel" severity="secondary" size="small" @click="closeAction" />
          <Button :label="actionDialog.confirmLabel" :severity="actionDialog.severity" size="small" @click="confirmAction" />
        </div>
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import axiosClient from '@/axios'
import Card from 'primevue/card'
import Button from 'primevue/button'
import Tag from 'primevue/tag'
import Divider from 'primevue/divider'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'

const router = useRouter()
const toast = useToast()
const loading = ref(false)
const report = ref<any | null>(null)

const actionDialog = reactive({
  visible: false,
  type: 'suspend',
  reason: '',
  message: '',
  confirmLabel: 'Confirm',
  severity: 'warning',
})

const parseIdFromPath = () => {
  const segments = (globalThis as any).location.pathname.split('/').filter(Boolean)
  const idSegment = segments[segments.length - 1]
  const maybeId = Number(idSegment)
  return Number.isNaN(maybeId) ? null : maybeId
}

const reportId = parseIdFromPath()

const loadReport = async () => {
  if (!reportId) return
  loading.value = true
  try {
    const response = await axiosClient.get(`/api/admin/violation-reports/${reportId}`)
    report.value = response?.data?.data || null
  } catch (error) {
    console.error('Failed to load violation report', error)
  } finally {
    loading.value = false
  }
}

const openAction = (type: 'suspend' | 'ban') => {
  actionDialog.type = type
  actionDialog.reason = ''
  actionDialog.visible = true
  if (type === 'ban') {
    actionDialog.message = 'Ban this store from operating on the platform?'
    actionDialog.confirmLabel = 'Ban Store'
    actionDialog.severity = 'danger'
  } else {
    actionDialog.message = 'Suspend this store temporarily?'
    actionDialog.confirmLabel = 'Suspend Store'
    actionDialog.severity = 'warning'
  }
}

const closeAction = () => {
  actionDialog.visible = false
}

const confirmAction = async () => {
  if (!actionDialog.reason.trim()) {
    toast.add({ severity: 'warn', summary: 'Reason Required', detail: 'Please provide a reason.', life: 3000 })
    return
  }

  try {
    const endpoint = actionDialog.type === 'ban' ? 'ban' : 'suspend'
    const response = await axiosClient.post(`/api/admin/violation-reports/${reportId}/${endpoint}`, {
      reason: actionDialog.reason.trim(),
    })
    report.value = response?.data?.data || report.value
    toast.add({ severity: 'success', summary: 'Action Complete', detail: 'Store action applied.', life: 3000 })
    actionDialog.visible = false
  } catch (error) {
    toast.add({ severity: 'error', summary: 'Action Failed', detail: 'Unable to apply action.', life: 4000 })
  }
}

const goBack = () => {
  router.push({ path: '/admin/violation-reports' })
}

const formatDate = (value: string) => {
  if (!value) return '-'
  return new Date(value).toLocaleString()
}

const formatStatus = (value: string) => {
  if (!value) return 'Unknown'
  return value.replace(/_/g, ' ').toUpperCase()
}

const statusSeverity = (status: string) => {
  if (status === 'actioned') return 'success'
  if (status === 'pending') return 'warning'
  return 'secondary'
}

const actionedByLabel = computed(() => {
  if (!report.value?.actionBy) return 'Unknown'
  return `${report.value.actionBy.first_name || ''} ${report.value.actionBy.last_name || ''}`.trim()
})

const actionDisabled = computed(() => report.value?.status === 'actioned')

onMounted(() => {
  loadReport()
})
</script>
