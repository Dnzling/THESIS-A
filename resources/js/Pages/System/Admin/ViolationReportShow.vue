<template>
  <Toast />
  <div class="mx-auto max-w-7xl space-y-4 px-4 py-5 text-sm md:px-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-xs font-semibold uppercase tracking-wider text-orange-600">Trust &amp; safety / Case {{ reportId || '-' }}</p>
        <h1 class="mt-1 text-2xl font-semibold text-slate-900">Violation report</h1>
        <p class="mt-1 text-xs text-slate-500">Review the claim and evidence before taking action.</p>
      </div>
      <Button label="Back to reports" icon="pi pi-arrow-left" severity="secondary" outlined size="small" @click="goBack" />
    </div>

    <div v-if="loading" class="grid gap-4 lg:grid-cols-3">
      <Skeleton height="170px" class="lg:col-span-3" /><Skeleton height="350px" class="lg:col-span-2" /><Skeleton height="350px" />
    </div>
    <Message v-else-if="!report" severity="warn" :closable="false">This report could not be loaded.</Message>
    <template v-else>
      <Card class="border border-slate-200 shadow-sm"><template #content>
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-4">
          <div><p class="text-xs uppercase tracking-wide text-slate-500">Reported reason</p><h2 class="mt-1 text-lg font-semibold text-slate-900">{{ report.report_reason || 'Unspecified violation' }}</h2><p class="mt-1 text-xs text-slate-500">Filed {{ formatDate(report.created_at) }} by {{ userFullName(report.reporter) }}</p></div>
          <Tag :value="formatStatus(report.status)" :severity="statusSeverity(report.status)" />
        </div>
        <div class="grid gap-4 pt-4 sm:grid-cols-2 xl:grid-cols-4">
          <div><p class="text-xs text-slate-500">Store</p><p class="mt-1 font-medium text-slate-900">{{ report.store?.name || '-' }}</p><p class="text-xs text-slate-500">{{ report.store?.store_code || 'No code' }}</p></div>
          <div><p class="text-xs text-slate-500">Supplier</p><p class="mt-1 font-medium text-slate-900">{{ report.supplier?.supplier_name || '-' }}</p><p class="text-xs text-slate-500">{{ report.supplier?.supplier_code || 'No code' }}</p></div>
          <div><p class="text-xs text-slate-500">Contract</p><p class="mt-1 font-medium text-slate-900">{{ report.contract_number || '-' }}</p><p class="text-xs text-slate-500">{{ report.contract_title || 'No contract linked' }}</p></div>
          <div><p class="text-xs text-slate-500">Reporter</p><p class="mt-1 font-medium text-slate-900">{{ userFullName(report.reporter) }}</p><p class="text-xs text-slate-500">{{ report.reporter?.email || report.reporter_type || '-' }}</p></div>
        </div>
      </template></Card>

      <div class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-4">
          <Card class="border border-slate-200 shadow-sm"><template #content>
            <h2 class="font-semibold text-slate-900">Report details</h2>
            <p class="mt-3 whitespace-pre-wrap leading-6 text-slate-700">{{ report.report_details || 'No additional details provided.' }}</p>
            <div class="mt-5 border-t border-slate-100 pt-4">
              <div class="flex items-center justify-between"><h3 class="font-semibold text-slate-900">Evidence</h3><span class="text-xs text-slate-500">{{ (report.evidence_urls || []).length }} files</span></div>
              <div v-if="report.evidence_urls?.length" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                <a v-for="(url, index) in report.evidence_urls" :key="`evidence-${index}`" :href="evidenceUrl(url)" target="_blank" rel="noopener" class="overflow-hidden rounded-lg border border-slate-200 transition hover:border-orange-400">
                  <img v-if="isImage(url)" :src="evidenceUrl(url)" :alt="`Evidence ${index + 1}`" class="h-28 w-full object-cover" />
                  <div v-else class="flex h-28 items-center justify-center bg-slate-50 text-slate-500"><i class="pi pi-file-pdf text-2xl" /></div>
                  <span class="block truncate px-2 py-1.5 text-xs text-slate-600">Evidence {{ index + 1 }}</span>
                </a>
              </div>
              <p v-else class="mt-3 rounded-lg bg-slate-50 p-4 text-xs text-slate-500">No evidence attached to the report.</p>
            </div>
          </template></Card>

          <Card class="border border-slate-200 shadow-sm"><template #content>
            <h2 class="font-semibold text-slate-900">Case conversation</h2>
            <p class="mt-1 text-xs text-slate-500">Messages and supporting files submitted for this case.</p>
            <div v-if="threadLoading" class="mt-4 space-y-2"><Skeleton v-for="n in 2" :key="n" height="70px" /></div>
            <p v-else-if="!thread.length" class="mt-4 rounded-lg bg-slate-50 p-4 text-xs text-slate-500">No responses yet.</p>
            <div v-else class="mt-4 max-h-96 space-y-3 overflow-auto pr-1">
              <div v-for="item in thread" :key="item.id" class="rounded-lg border border-slate-200 p-3">
                <div class="flex flex-wrap items-center justify-between gap-2"><p class="font-semibold text-slate-900">{{ userFullName(item.responder) }} <span class="font-normal text-slate-500">{{ formatStatus(item.responder_type) }}</span></p><time class="text-xs text-slate-500">{{ formatDate(item.created_at) }}</time></div>
                <p v-if="item.message" class="mt-2 whitespace-pre-wrap leading-5 text-slate-700">{{ item.message }}</p>
                <div v-if="Array.isArray(item.attachments) && item.attachments.length" class="mt-3 flex flex-wrap gap-2">
                  <a v-for="(ev, idx) in item.attachments" :key="`ev-${item.id}-${idx}`" :href="evidenceUrl(ev)" target="_blank" rel="noopener" class="rounded border border-slate-200 p-1 hover:border-orange-400"><img v-if="isImage(ev)" :src="evidenceUrl(ev)" class="h-12 w-12 rounded object-cover" :alt="`Attachment ${idx + 1}`" /><span v-else class="flex h-12 w-12 items-center justify-center text-slate-500"><i class="pi pi-file-pdf" /></span></a>
                </div>
              </div>
            </div>
            <div class="mt-5 border-t border-slate-100 pt-4">
              <label class="mb-2 block font-semibold text-slate-900">Admin response</label>
              <Textarea v-model="replyMessage" rows="3" class="w-full" placeholder="Write a clear response for both parties..." />
              <div class="mt-2 flex flex-wrap items-center justify-between gap-3"><input type="file" accept="image/*" multiple @change="onReplyFilesChanged" class="max-w-full text-xs" /><Button label="Send response" severity="warn" size="small" :loading="replySubmitting" @click="submitReply" /></div>
            </div>
          </template></Card>
        </div>

        <Card class="border border-slate-200 shadow-sm lg:sticky lg:top-5"><template #content>
          <h2 class="font-semibold text-slate-900">Review &amp; action</h2>
          <p class="mt-1 text-xs text-slate-500">Confirm the affected party before acting.</p>
          <div class="mt-4 rounded-lg bg-orange-50 p-3"><p class="text-xs text-orange-700">Action target</p><Tag class="mt-1" :value="actionTargetLabel" :severity="actionTargetSeverity" /></div>
          <Message v-if="isTerminationRequest" severity="warn" :closable="false" class="mt-4 text-xs">This is a termination request. Use contract management to finalize it.</Message>
          <div class="mt-5 border-t border-slate-100 pt-4"><p class="text-xs text-slate-500">Action taken</p><template v-if="report.action_type"><Tag class="mt-2" :value="formatStatus(report.action_type)" severity="info" /><p class="mt-2 whitespace-pre-wrap text-slate-700">{{ report.action_reason || 'No reason recorded.' }}</p><p class="mt-2 text-xs text-slate-500">By {{ actionedByLabel }} on {{ formatDate(report.actioned_at) }}</p></template><p v-else class="mt-2 text-slate-500">No action taken yet.</p></div>
          <div v-if="!isTerminationRequest && !actionDisabled" class="mt-5 grid gap-2 border-t border-slate-100 pt-4"><Button :label="actionTargetLabel === 'SUPPLIER' ? 'Suspend supplier' : 'Suspend store'" severity="warn" outlined size="small" fluid @click="openAction('suspend')" /><Button :label="actionTargetLabel === 'SUPPLIER' ? 'Ban supplier' : 'Ban store'" severity="danger" outlined size="small" fluid @click="openAction('ban')" /></div>
        </template></Card>
      </div>
    </template>

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
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import Toast from 'primevue/toast'
import Skeleton from 'primevue/skeleton'
import Message from 'primevue/message'

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
  const target = actionTargetLabel.value.toLowerCase()
  actionDialog.type = type
  actionDialog.reason = ''
  actionDialog.visible = true
  if (type === 'ban') {
    actionDialog.message = `Ban this ${target} from operating on the platform?`
    actionDialog.confirmLabel = `Ban ${target}`
    actionDialog.severity = 'danger'
  } else {
    actionDialog.message = `Suspend this ${target} temporarily?`
    actionDialog.confirmLabel = `Suspend ${target}`
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
    toast.add({ severity: 'success', summary: 'Action Complete', detail: `${actionTargetLabel.value.toLowerCase()} action applied.`, life: 3000 })
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

const isImage = (path: string) => /\.(png|jpe?g|gif|webp|bmp)(\?|$)/i.test(String(path || ''))

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
