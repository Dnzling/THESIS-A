<template>
  <div class="mx-auto max-w-4xl space-y-5 p-4 text-sm md:p-6">
    <ConfirmDialog />
    <header class="flex items-center gap-3">
      <Button icon="pi pi-chevron-left" rounded outlined aria-label="Go back" @click="router.back()" />
      <div><h1 class="text-xl font-semibold text-slate-900">New Expense Request</h1><p class="mt-1 text-sm text-slate-500">Submit an estimate for approval before the branch incurs the expense.</p></div>
    </header>

    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
    <Card class="border border-slate-200 shadow-sm">
      <template #content>
        <form class="space-y-5" @submit.prevent="requestSubmit">
          <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="mb-1 block text-sm font-medium text-slate-700">Expense Category <span class="text-red-600">*</span></label><Select v-model="form.category" :options="categories" optionLabel="label" optionValue="value" placeholder="Select category" fluid :invalid="attempted && !form.category" /></div>
            <div><label class="mb-1 block text-sm font-medium text-slate-700">Requested Date <span class="text-red-600">*</span></label><DatePicker v-model="form.requestedDate" :minDate="today" dateFormat="M d, yy" showIcon fluid :invalid="attempted && !form.requestedDate" /></div>
            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Request Title <span class="text-red-600">*</span></label><InputText v-model="form.title" placeholder="e.g. Repair display shelf" fluid :invalid="attempted && !form.title.trim()" /></div>
            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Description <span class="text-red-600">*</span></label><Textarea v-model="form.description" rows="4" autoResize fluid placeholder="Describe the issue or expense and why it is needed." :invalid="attempted && !form.description.trim()" /></div>
            <div><label class="mb-1 block text-sm font-medium text-slate-700">Estimated Cost <span class="text-red-600">*</span></label><InputNumber v-model="form.estimatedAmount" mode="currency" currency="PHP" locale="en-PH" :min="0.01" fluid :invalid="attempted && !(Number(form.estimatedAmount) > 0)" /></div>
            <div><label class="mb-1 block text-sm font-medium text-slate-700">Supporting Quote or Photos <span class="text-xs font-normal text-slate-500">(optional, up to 5)</span></label><FileUpload mode="basic" name="attachments[]" accept="image/*,application/pdf" :multiple="true" :maxFileSize="10485760" chooseLabel="Attach files" :auto="false" @select="addFiles" /></div>
          </div>
          <div v-if="files.length" class="grid grid-cols-2 gap-3 sm:grid-cols-5">
            <div v-for="(file, index) in files" :key="`${file.name}-${index}`" class="group relative overflow-hidden rounded-lg border border-slate-200 bg-white p-2">
              <button v-if="file.type.startsWith('image/')" type="button" class="relative block h-24 w-full overflow-hidden rounded bg-slate-50" :aria-label="`View ${file.name}`" @click="openPreview(filePreviews[index], file.name)">
                <img :src="filePreviews[index]" class="h-full w-full object-cover" :alt="file.name" />
                <span class="absolute inset-0 flex items-center justify-center bg-slate-950/0 text-white opacity-0 transition group-hover:bg-slate-950/35 group-hover:opacity-100 group-focus-within:opacity-100"><i class="pi pi-eye text-xl" /></span>
              </button>
              <div v-else class="flex h-24 items-center justify-center rounded bg-slate-50 text-slate-500"><i class="pi pi-file-pdf text-2xl" /></div>
              <p class="mt-1 truncate pr-7 text-xs text-slate-600" :title="file.name">{{ file.name }}</p>
              <Button type="button" icon="pi pi-times" severity="danger" rounded text size="small" class="absolute right-2 top-2 bg-white/95 opacity-0 shadow-sm transition group-hover:opacity-100 group-focus-within:opacity-100 focus:opacity-100" aria-label="Remove attachment" @click="removeFile(index)" />
            </div>
          </div>
          <div class="rounded-lg border border-orange-100 bg-orange-50 p-3 text-xs text-orange-900">The estimate is not counted as OPEX. After approval and completion, submit the actual cost and receipt for Finance review.</div>
          <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
            <Button label="Cancel" severity="secondary" outlined @click="router.back()" />
            <Button type="submit" label="Submit for Approval" icon="pi pi-send" severity="warn" :loading="saving" />
          </div>
        </form>
      </template>
    </Card>
    <Dialog v-model:visible="previewVisible" modal :header="previewName || 'Photo preview'" :style="{ width: 'min(92vw, 56rem)' }" :breakpoints="{ '640px': '96vw' }">
      <img v-if="previewUrl" :src="previewUrl" :alt="previewName || 'Uploaded photo'" class="mx-auto max-h-[75vh] max-w-full rounded-lg object-contain" />
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Card from 'primevue/card'
import ConfirmDialog from 'primevue/confirmdialog'
import DatePicker from 'primevue/datepicker'
import Dialog from 'primevue/dialog'
import FileUpload from 'primevue/fileupload'
import InputNumber from 'primevue/inputnumber'
import InputText from 'primevue/inputtext'
import Message from 'primevue/message'
import Select from 'primevue/select'
import Textarea from 'primevue/textarea'
import { useConfirm } from 'primevue/useconfirm'
import { useToast } from 'primevue/usetoast'
import storeService from '@/services/store.service'

const router = useRouter()
const confirm = useConfirm()
const toast = useToast()
const today = new Date()
today.setHours(0, 0, 0, 0)
const categories = [
  { label: 'Maintenance', value: 'maintenance' }, { label: 'Utilities', value: 'utilities' },
  { label: 'Supplies', value: 'supplies' }, { label: 'Rent', value: 'rent' },
  { label: 'Transport', value: 'transport' }, { label: 'Other', value: 'other' },
]
const form = ref({ category: '', title: '', description: '', requestedDate: new Date(), estimatedAmount: null as number | null })
const files = ref<File[]>([])
const filePreviews = ref<string[]>([])
const attempted = ref(false)
const saving = ref(false)
const error = ref('')
const previewVisible = ref(false)
const previewUrl = ref('')
const previewName = ref('')
const openPreview = (url: string, name: string) => { previewUrl.value = url; previewName.value = name; previewVisible.value = true }
const addFiles = (event: any) => {
  const available = Math.max(0, 5 - files.value.length)
  const selected = (event.files as File[]).slice(0, available)
  files.value.push(...selected)
  selected.forEach(file => filePreviews.value.push(file.type.startsWith('image/') ? URL.createObjectURL(file) : ''))
  if (event.files.length > available) toast.add({ severity: 'warn', summary: 'Attachment limit', detail: 'You can attach up to five files.', life: 3000 })
}
const removeFile = (index: number) => {
  if (filePreviews.value[index]) URL.revokeObjectURL(filePreviews.value[index])
  files.value.splice(index, 1)
  filePreviews.value.splice(index, 1)
}
onBeforeUnmount(() => filePreviews.value.forEach(url => url && URL.revokeObjectURL(url)))
const valid = () => !!form.value.category && !!form.value.title.trim() && !!form.value.description.trim() && Number(form.value.estimatedAmount) > 0 && !!form.value.requestedDate
const submit = async () => {
  attempted.value = true
  if (!valid()) return
  saving.value = true
  error.value = ''
  try {
    const payload = new FormData()
    payload.append('category', form.value.category)
    payload.append('title', form.value.title.trim())
    payload.append('description', form.value.description.trim())
    payload.append('estimated_amount', String(form.value.estimatedAmount))
    payload.append('requested_date', `${form.value.requestedDate.getFullYear()}-${String(form.value.requestedDate.getMonth() + 1).padStart(2, '0')}-${String(form.value.requestedDate.getDate()).padStart(2, '0')}`)
    files.value.forEach(file => payload.append('attachments[]', file))
    const response = await storeService.createBranchExpense(payload)
    toast.add({ severity: 'success', summary: 'Request submitted', detail: response.message, life: 3000 })
    await router.replace({ name: 'store.expenses.detail', params: { id: response.data.id } })
  } catch (e: any) {
    error.value = e?.response?.data?.message || 'Unable to submit the expense request.'
  } finally {
    saving.value = false
  }
}
const requestSubmit = () => {
  attempted.value = true
  if (!valid()) return
  confirm.require({
    header: 'Submit expense request?',
    message: 'This estimate will be sent for branch approval. It will not be counted as OPEX yet.',
    acceptLabel: 'Submit Request', rejectLabel: 'Continue Editing', icon: 'pi pi-send',
    rejectProps: { severity: 'secondary', outlined: true }, acceptProps: { severity: 'warn' },
    accept: submit,
  })
}
</script>
