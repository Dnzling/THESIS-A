<template>
  <div class="mx-auto max-w-6xl space-y-5 p-4 text-sm md:p-6">
    <ConfirmDialog />
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex items-center gap-3">
        <Button icon="pi pi-chevron-left" rounded outlined aria-label="Go back" @click="router.back()" />
        <div><h1 class="text-xl font-semibold text-slate-900">{{ expense?.reference_number || 'Expense Request' }}</h1><p class="mt-1 text-sm text-slate-500">Branch maintenance and operating expense request</p></div>
      </div>
      <Badge v-if="expense" :value="statusLabel(expense.status)" :severity="statusSeverity(expense.status)" />
    </header>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <div v-if="loading" class="space-y-3"><Skeleton height="1.25rem" width="40%" /><Skeleton height="5rem" /></div>
        <div v-else-if="!expense" class="py-8 text-center text-slate-500">Expense request not found.</div>
        <div v-else class="space-y-5">
          <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Info label="Branch" :value="expense.branch?.name || branchName || '-'" />
            <Info label="Category" :value="categoryLabel(expense.category)" />
            <Info label="Requested Date" :value="formatDate(expense.requested_date)" />
            <Info label="Submitted By" :value="person(expense.creator)" />
            <Info label="Estimated Cost" :value="money(expense.estimated_amount)" />
            <Info label="Actual Cost" :value="expense.actual_amount ? money(expense.actual_amount) : 'Not submitted'" />
            <Info label="Actual Expense Date" :value="formatDate(expense.actual_expense_date)" />
            <Info label="Finance Status" :value="expense.finance_expense ? statusLabel(expense.finance_expense.status) : 'Not sent to Finance'" />
          </div>
          <div><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Request</p><h2 class="mt-1 font-semibold text-slate-900">{{ expense.title }}</h2><p class="mt-2 whitespace-pre-line text-slate-700">{{ expense.description }}</p></div>
          <div v-if="expense.review_notes" class="rounded-lg border border-slate-200 bg-slate-50 p-3"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Approval notes</p><p class="mt-1 whitespace-pre-line text-slate-700">{{ expense.review_notes }}</p><p class="mt-2 text-xs text-slate-500">Reviewed by {{ person(expense.reviewer) }}<span v-if="expense.reviewed_at"> · {{ formatDateTime(expense.reviewed_at) }}</span></p></div>
          <div v-if="expense.completion_notes"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Completion notes</p><p class="mt-1 whitespace-pre-line text-slate-700">{{ expense.completion_notes }}</p></div>
          <div v-if="expense.attachments?.length">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Supporting files</p>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-5">
              <div v-for="file in expense.attachments" :key="file.url" class="rounded-lg border border-slate-200 p-2 hover:border-orange-300">
                <button v-if="file.mime?.startsWith('image/')" type="button" class="group relative block h-28 w-full overflow-hidden rounded bg-slate-50" :aria-label="'View ' + file.name" @click.prevent="openPreview(file.url, file.name)">
                  <img v-if="privateImages[file.url]" :src="privateImages[file.url]" :alt="file.name" class="h-full w-full object-cover transition group-hover:scale-[1.03]" />
                  <span v-else class="absolute inset-0 flex items-center justify-center text-slate-400"><i class="pi pi-image text-xl" /></span>
                  <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 text-white opacity-0 transition group-hover:bg-slate-950/35 group-hover:opacity-100"><i class="pi pi-eye text-xl" /></span>
                </button>
                <a v-else :href="file.url" target="_blank" rel="noopener" class="flex h-28 items-center justify-center rounded bg-slate-50 hover:bg-slate-100" :aria-label="`Open ${file.name}`"><i class="pi pi-file-pdf text-2xl text-slate-400" /></a>
                <span class="mt-1 block truncate text-xs text-slate-600" :title="file.name">{{ file.name }}</span>
              </div>
            </div>
          </div>
          <div v-if="expense.receipt_url" class="rounded-lg border border-slate-200 p-3">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Actual receipt / invoice</p>
            <a v-if="!expense.receipt_mime?.startsWith('image/')" :href="expense.receipt_url" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-orange-700 hover:underline"><i class="pi pi-external-link" /> Open receipt</a>
            <button v-if="expense.receipt_mime?.startsWith('image/')" type="button" class="group relative block h-28 w-40 overflow-hidden rounded-lg border border-slate-200 bg-slate-50" aria-label="View expense receipt" @click="openPreview(expense.receipt_url, 'Expense receipt')">
              <img v-if="privateImages[expense.receipt_url]" :src="privateImages[expense.receipt_url]" alt="Expense receipt" class="h-full w-full object-cover transition group-hover:scale-[1.03]" />
              <span v-else class="absolute inset-0 flex items-center justify-center text-slate-400"><i class="pi pi-image text-xl" /></span>
              <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 text-white opacity-0 transition group-hover:bg-slate-950/35 group-hover:opacity-100"><i class="pi pi-eye text-xl" /></span>
            </button>
          </div>

          <div v-if="expense.finance_expense" class="rounded-lg border border-blue-100 bg-blue-50 p-3 text-sm text-blue-900">
            Sent to Finance as <strong class="font-mono">{{ expense.finance_expense.reference_number || ('Expense #' + expense.finance_expense.id) }}</strong>. Finance status: <strong>{{ statusLabel(expense.finance_expense.status) }}</strong><span v-if="expense.finance_expense.payment_date"> · Paid {{ formatDate(expense.finance_expense.payment_date) }}</span>.
          </div>
        </div>
      </template>
    </Card>

    <Card v-if="expense && expense.status === 'pending_approval' && canApprove" class="border border-orange-200 shadow-sm">
      <template #title><span class="text-base">Branch Approval</span></template>
      <template #content>
        <p class="mb-4 text-sm text-slate-600">Approve the estimated spending before the branch proceeds, or reject it with a reason.</p>
        <div class="flex justify-end gap-2"><Button label="Reject" severity="secondary" outlined :disabled="acting" @click="rejectDialog = true" /><Button label="Approve Request" icon="pi pi-check" severity="warn" :loading="acting" @click="confirmApprove" /></div>
      </template>
    </Card>

    <Card v-if="expense?.status === 'approved' && canManage" class="border border-orange-200 shadow-sm">
      <template #title><span class="text-base">Submit Actual Cost to Finance</span></template>
      <template #content>
        <Message severity="info" :closable="false" class="mb-4">Record the final amount after the work or purchase is complete. The estimate is only for approval and is not counted as OPEX.</Message>
        <div class="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-3">
          <div><p class="text-xs uppercase tracking-wide text-slate-500">Approved estimate</p><p class="mt-1 font-semibold text-slate-900">{{ money(expense.estimated_amount) }}</p></div>
          <div><p class="text-xs uppercase tracking-wide text-slate-500">Actual amount</p><p class="mt-1 font-semibold text-slate-900">{{ actualForm.amount ? money(actualForm.amount) : 'Enter final cost' }}</p></div>
          <div><p class="text-xs uppercase tracking-wide text-slate-500">Difference from estimate</p><p class="mt-1 font-semibold" :class="actualVariance > 0 ? 'text-orange-700' : 'text-slate-900'">{{ actualForm.amount ? `${actualVariance > 0 ? '+' : ''}${money(actualVariance)}` : '—' }}</p></div>
        </div>
        <Message v-if="actualVariance > 0" severity="warn" :closable="false" class="mb-4">The actual cost is above the approved estimate. Add a note explaining the difference before submitting.</Message>
        <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="confirmSubmitActual">
          <div><label class="mb-1 block text-sm font-medium text-slate-700">Actual Amount <span class="text-red-600">*</span></label><InputNumber v-model="actualForm.amount" mode="currency" currency="PHP" locale="en-PH" :min="0.01" fluid :invalid="actualAttempted && !(Number(actualForm.amount) > 0)" /><small class="mt-1 block text-xs text-slate-500">Prefilled from the approved estimate; adjust it to the final receipt total.</small></div>
          <div><label class="mb-1 block text-sm font-medium text-slate-700">Expense Date <span class="text-red-600">*</span></label><DatePicker v-model="actualForm.date" :maxDate="today" dateFormat="M d, yy" showIcon fluid :invalid="actualAttempted && !actualForm.date" /></div>
          <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Completion / Variance Notes <span v-if="actualVariance > 0" class="text-red-600">*</span></label><Textarea v-model="actualForm.notes" rows="2" autoResize fluid :invalid="actualAttempted && actualVariance > 0 && !actualForm.notes.trim()" :placeholder="actualVariance > 0 ? 'Explain why the actual cost exceeded the estimate' : 'Work completed, vendor, or other context'" /></div>
          <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Receipt or Invoice <span class="text-red-600">*</span></label><FileUpload mode="basic" name="receipt" accept="image/*,application/pdf" :maxFileSize="10485760" chooseLabel="Choose receipt" :auto="false" @select="selectReceipt" /><div v-if="receipt" class="mt-2 flex items-center gap-2 text-xs text-slate-600"><i :class="receipt.type.startsWith('image/') ? 'pi pi-image' : 'pi pi-file-pdf'" />{{ receipt.name }}<Button icon="pi pi-times" severity="danger" text rounded size="small" aria-label="Remove receipt" @click="receipt = null" /></div></div>
          <div class="sm:col-span-2 flex justify-end"><Button type="submit" label="Submit to Finance" icon="pi pi-send" severity="warn" :loading="acting" /></div>
        </form>
      </template>
    </Card>

    <Dialog v-model:visible="rejectDialog" modal header="Reject Expense Request" :style="{ width: 'min(92vw, 32rem)' }">
      <label class="mb-1 block text-sm font-medium text-slate-700">Reason <span class="text-red-600">*</span></label>
      <Textarea v-model="rejectNotes" rows="4" autoResize fluid placeholder="Explain why this request is rejected." />
      <template #footer><Button label="Keep Request" severity="secondary" outlined @click="rejectDialog = false" /><Button label="Reject Request" severity="danger" :disabled="!rejectNotes.trim()" :loading="acting" @click="reject" /></template>
    </Dialog>
    <Dialog v-model:visible="previewVisible" modal :header="previewName || 'Photo preview'" :style="{ width: 'min(92vw, 56rem)' }" :breakpoints="{ '640px': '96vw' }">
      <img v-if="previewUrl" :src="previewUrl" :alt="previewName || 'Expense photo'" class="mx-auto max-h-[75vh] max-w-full rounded-lg object-contain" />
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Button from 'primevue/button'
import Badge from 'primevue/badge'
import Card from 'primevue/card'
import ConfirmDialog from 'primevue/confirmdialog'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import FileUpload from 'primevue/fileupload'
import InputNumber from 'primevue/inputnumber'
import Message from 'primevue/message'
import Skeleton from 'primevue/skeleton'
import Textarea from 'primevue/textarea'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import storeService from '@/services/store.service'
import axios from '@/axios'

const route = useRoute()
const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const loading = ref(true)
const acting = ref(false)
const error = ref('')
const expense = ref<any>(null)
const branchName = ref('')
const canApprove = ref(false)
const canManage = ref(false)
const rejectDialog = ref(false)
const rejectNotes = ref('')
const actualAttempted = ref(false)
const receipt = ref<File | null>(null)
const previewVisible = ref(false)
const previewUrl = ref('')
const previewName = ref('')
const privateImages = ref<Record<string, string>>({})
const apiPath = (url: string) => {
  const parsed = new URL(url, window.location.origin)
  return parsed.pathname + parsed.search
}
async function loadPrivateImage(url: string) {
  if (!url || privateImages.value[url]) return
  const response = await axios.get(apiPath(url), { responseType: 'blob' })
  privateImages.value[url] = URL.createObjectURL(response.data)
}
async function openPreview(url: string, name: string) {
  try {
    await loadPrivateImage(url)
    previewUrl.value = privateImages.value[url]
    previewName.value = name
    previewVisible.value = true
  } catch {
    error.value = 'Unable to load this photo. Please try again.'
  }
}
const today = new Date()
today.setHours(23, 59, 59, 999)
const actualForm = ref({ amount: null as number | null, date: new Date(), notes: '' })
const actualVariance = computed(() => Number(actualForm.value.amount || 0) - Number(expense.value?.estimated_amount || 0))
const money = (value: number | string) => new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(Number(value || 0))
const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium' }).format(new Date(`${value.slice(0, 10)}T12:00:00`)) : '-'
const formatDateTime = (value: string | null) => value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '-'
const person = (value: any) => [value?.fname, value?.lname].filter(Boolean).join(' ') || '-'
const categoryLabel = (value: string) => String(value || '').replace(/\b\w/g, c => c.toUpperCase())
const statusLabel = (value: string) => String(value || '').replaceAll('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
const statusSeverity = (value: string) => ({ pending_approval: 'warn', approved: 'success', rejected: 'danger', submitted_to_finance: 'info', finance_approved: 'success', finance_rejected: 'danger', paid: 'success' }[value] || 'secondary')
const Info = defineComponent({ props: { label: String, value: String }, setup(props) { return () => h('div', [h('p', { class: 'text-xs uppercase tracking-wide text-slate-500' }, props.label), h('p', { class: 'mt-1 font-medium text-slate-900' }, props.value)]) } })

async function load() {
  loading.value = true
  error.value = ''
  try {
    const response = await storeService.getBranchExpense(String(route.params.id))
    expense.value = response.data
    branchName.value = response.data?.branch?.name || ''
    canApprove.value = !!response.data?.can_approve
    canManage.value = !!response.data?.can_manage
    if (response.data?.status === 'approved' && actualForm.value.amount === null) {
      actualForm.value.amount = Number(response.data.estimated_amount || 0) || null
    }
    const photos = [
      ...(response.data?.attachments || []).filter((file: any) => file.mime?.startsWith('image/')).map((file: any) => file.url),
      ...(response.data?.receipt_mime?.startsWith('image/') && response.data?.receipt_url ? [response.data.receipt_url] : []),
    ]
    void Promise.allSettled(photos.map(loadPrivateImage))
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Unable to load this branch expense.'
  } finally { loading.value = false }
}
function confirmApprove() {
  confirm.require({ header: 'Approve expense request?', message: `Approve the estimated cost of ${money(expense.value?.estimated_amount)} for this branch?`, icon: 'pi pi-check-circle', acceptLabel: 'Approve', rejectLabel: 'Cancel', rejectProps: { severity: 'secondary', outlined: true }, acceptProps: { severity: 'warn' }, accept: approve })
}
async function approve() {
  acting.value = true
  try { const response = await storeService.approveBranchExpense(expense.value.id); toast.add({ severity: 'success', summary: 'Approved', detail: response.message, life: 3000 }); await load() }
  catch (e: any) { error.value = e?.response?.data?.message || 'Unable to approve this request.' }
  finally { acting.value = false }
}
async function reject() {
  if (!rejectNotes.value.trim()) return
  acting.value = true
  try { const response = await storeService.rejectBranchExpense(expense.value.id, rejectNotes.value.trim()); rejectDialog.value = false; toast.add({ severity: 'success', summary: 'Request rejected', detail: response.message, life: 3000 }); await load() }
  catch (e: any) { error.value = e?.response?.data?.message || 'Unable to reject this request.' }
  finally { acting.value = false }
}
function selectReceipt(event: any) { receipt.value = event.files?.[0] || null }
function confirmSubmitActual() {
  actualAttempted.value = true
  if (!(Number(actualForm.value.amount) > 0) || !actualForm.value.date || !receipt.value) return
  if (actualVariance.value > 0 && !actualForm.value.notes.trim()) return
  confirm.require({ header: 'Submit actual expense to Finance?', message: 'Finance will review the final amount and receipt before recording payment.', icon: 'pi pi-send', acceptLabel: 'Submit to Finance', rejectLabel: 'Continue Editing', rejectProps: { severity: 'secondary', outlined: true }, acceptProps: { severity: 'warn' }, accept: submitActual })
}
async function submitActual() {
  const payload = new FormData()
  payload.append('actual_amount', String(actualForm.value.amount))
  payload.append('actual_expense_date', `${actualForm.value.date.getFullYear()}-${String(actualForm.value.date.getMonth() + 1).padStart(2, '0')}-${String(actualForm.value.date.getDate()).padStart(2, '0')}`)
  payload.append('completion_notes', actualForm.value.notes)
  if (receipt.value) payload.append('receipt', receipt.value)
  acting.value = true
  try { const response = await storeService.submitBranchExpenseActual(expense.value.id, payload); toast.add({ severity: 'success', summary: 'Sent to Finance', detail: response.message, life: 3000 }); await load() }
  catch (e: any) { error.value = e?.response?.data?.message || 'Unable to submit the actual expense.' }
  finally { acting.value = false }
}
onBeforeUnmount(() => Object.values(privateImages.value).forEach(URL.revokeObjectURL))
onMounted(load)
</script>
