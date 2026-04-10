<template>
  <Toast />
  <div class="max-w-5xl mx-auto py-6">
    <Card class="rounded-xl border border-slate-200 shadow-sm">
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
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
              <div class="text-xs text-gray-500">Store</div>
              <div class="font-medium">{{ report?.store?.name || '-' }}</div>
              <div class="text-xs text-gray-400">{{ report?.store?.store_code || 'No code' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Supplier</div>
              <div class="font-medium">{{ report?.supplier?.supplier_name || '-' }}</div>
              <div class="text-xs text-gray-400">{{ report?.supplier?.supplier_code || 'No code' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Status</div>
              <Tag :value="formatStatus(report?.status)" :severity="statusSeverity(report?.status)" />
            </div>
            <div>
              <div class="text-xs text-gray-500">Reporter</div>
              <div class="font-medium">
                {{ userFullName(report?.reporter) }}
              </div>
              <div class="text-xs text-gray-400">{{ report?.reporter?.email || report?.reporter_type || '-' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Reported At</div>
              <div class="font-medium">{{ formatDate(report?.created_at) }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Contract</div>
              <div class="font-medium">{{ report?.contract_number || '-' }}</div>
              <div class="text-xs text-gray-400">{{ report?.contract_title || 'No contract info' }}</div>
            </div>
            <div>
              <div class="text-xs text-gray-500">Action Target</div>
              <Tag :value="actionTargetLabel" :severity="actionTargetSeverity" />
              <div class="text-xs text-gray-400 mt-1">Who will be affected by suspend/ban.</div>
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
            <div class="mt-2 flex flex-wrap gap-2">
              <a v-for="(url, index) in report?.evidence_urls" :key="`evidence-${index}`" :href="evidenceUrl(url)" target="_blank" rel="noopener">
                <img :src="evidenceUrl(url)" class="h-20 w-20 rounded border border-slate-200 object-cover" />
              </a>
            </div>
          </div>

          <Divider />

          <div v-if="isTerminationRequest" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            This is a termination request raised by one contract party. Review the evidence before taking administrative action.
          </div>

          <div>
            <div class="text-xs text-gray-500">Action Taken</div>
            <div v-if="report?.action_type" class="space-y-1">
              <Tag :value="formatStatus(report.action_type)" severity="info" />
              <div class="text-xs text-gray-500">{{ report.action_reason }}</div>
              <div class="text-xs text-gray-400">By {{ actionedByLabel }} on {{ formatDate(report.actioned_at) }}</div>
            </div>
            <div v-else class="text-sm text-gray-400">No action taken yet.</div>
          </div>

          <Divider />

          <div>
            <div class="text-xs text-gray-500 mb-2">Case Conversation</div>
            <div v-if="threadLoading" class="text-sm text-gray-500">Loading conversation...</div>
            <div v-else-if="thread.length === 0" class="text-sm text-gray-400">No responses yet.</div>
            <div v-else class="space-y-2 max-h-56 overflow-auto pr-1">
              <div v-for="item in thread" :key="item.id" class="rounded border border-gray-200 p-2">
                <p class="text-xs font-semibold text-gray-700">{{ userFullName(item.responder) }} <span class="text-gray-400">({{ item.responder_type }})</span></p>
                <p class="text-xs text-gray-700 whitespace-pre-wrap">{{ item.message || '-' }}</p>
                <div v-if="Array.isArray(item.attachments) && item.attachments.length" class="mt-1 flex flex-wrap gap-1">
                  <a v-for="(ev, idx) in item.attachments" :key="`ev-${item.id}-${idx}`" :href="evidenceUrl(ev)" target="_blank" rel="noopener">
                    <img :src="evidenceUrl(ev)" class="h-8 w-8 rounded border border-slate-200 object-cover" />
                  </a>
                </div>
              </div>
            </div>
          </div>

          <div class="border-t pt-3">
            <div class="text-xs text-gray-500 mb-1">Admin Response</div>
            <Textarea v-model="replyMessage" rows="3" class="w-full" placeholder="Write admin response..." />
            <input type="file" accept="image/*" multiple @change="onReplyFilesChanged" class="mt-2 w-full text-xs" />
            <div class="mt-2 flex justify-end">
              <Button label="Send Response" size="small" :loading="replySubmitting" @click="submitReply" />
            </div>
          </div>
        </div>
      </template>

      <template #footer>
        <div class="flex justify-end space-x-2">
          <Button v-if="!isTerminationRequest" label="Suspend Store" severity="warning" size="small" :disabled="actionDisabled" @click="openAction('suspend')" />
          <Button v-if="!isTerminationRequest" label="Ban Store" severity="danger" size="small" :disabled="actionDisabled" @click="openAction('ban')" />
          <span v-if="isTerminationRequest" class="text-xs text-gray-500">Use contract management flow to finalize termination.</span>
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
const thread = ref<any[]>([])
const threadLoading = ref(false)
const replyMessage = ref('')
const replyFiles = ref<File[]>([])
const replySubmitting = ref(false)

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

const loadThread = async () => {
  if (!reportId) return
  threadLoading.value = true
  try {
    const res = await axiosClient.get(`/api/violation-reports/${reportId}/responses`)
    thread.value = res?.data?.data || []
  } catch {
    thread.value = []
  } finally {
    threadLoading.value = false
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

const userFullName = (u: any) => {
  if (!u) return 'Anonymous'
  return [u.first_name || u.fname, u.last_name || u.lname].filter(Boolean).join(' ') || 'Anonymous'
}

const evidenceUrl = (path: string) => {
  if (!path) return '#'
  if (/^https?:\/\//i.test(path)) return path
  return `/storage/${String(path).replace(/^\/+/, '')}`
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
const isTerminationRequest = computed(() => report.value?.action_type === 'termination_requested')
const actionTargetLabel = computed(() => {
  const rt = String(report.value?.reporter_type || '')
  return ['store_user', 'employee'].includes(rt) ? 'SUPPLIER' : 'STORE'
})
const actionTargetSeverity = computed(() => (actionTargetLabel.value === 'SUPPLIER' ? 'danger' : 'warning'))

const onReplyFilesChanged = (event: Event) => {
  const input = event.target as HTMLInputElement
  replyFiles.value = input.files ? Array.from(input.files) : []
}

const submitReply = async () => {
  if (!reportId) return
  if (!replyMessage.value.trim() && replyFiles.value.length === 0) {
    toast.add({ severity: 'warn', summary: 'Input Required', detail: 'Add message or attachment.', life: 2500 })
    return
  }
  replySubmitting.value = true
  try {
    const fd = new FormData()
    if (replyMessage.value.trim()) fd.append('message', replyMessage.value.trim())
    replyFiles.value.forEach((f) => fd.append('attachments[]', f))
    await axiosClient.post(`/api/violation-reports/${reportId}/responses`, fd)
    replyMessage.value = ''
    replyFiles.value = []
    await loadThread()
    toast.add({ severity: 'success', summary: 'Sent', detail: 'Response posted.', life: 2000 })
  } catch (error: any) {
    toast.add({ severity: 'error', summary: 'Send Failed', detail: error?.response?.data?.message || 'Unable to send response.', life: 3000 })
  } finally {
    replySubmitting.value = false
  }
}

onMounted(() => {
  loadReport()
  loadThread()
})
</script>
