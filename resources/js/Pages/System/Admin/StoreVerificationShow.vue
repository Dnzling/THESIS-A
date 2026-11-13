<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <button class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm transition hover:bg-slate-50" @click="goBack">
          <i class="pi pi-chevron-left text-slate-600" />
        </button>
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-600">{{ isBranch ? 'Branch verification' : 'Store verification' }}</p>
          <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ isBranch ? (branch?.name || 'Branch verification details') : (store?.name || 'Verification details') }}</h1>
          <p class="mt-1 text-sm text-slate-500">Application #{{ verification?.id || route.params.id }} · {{ isBranch ? (store?.name || 'Store') : (store?.store_code || `STORE-${store?.id || '-'}`) }}</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">

        <Tag :value="title(status)" :severity="statusSeverity(status)" rounded />
      </div>
    </div>

    <div v-if="loading" class="grid gap-4 md:grid-cols-3">
      <div v-for="item in 6" :key="item" class="rounded-2xl border border-slate-100 bg-white p-5"><Skeleton height="6rem" /></div>
    </div>

    <template v-else-if="verification">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <SummaryCard label="Application status" :value="title(status)" icon="pi pi-shield" tone="blue" />
        <SummaryCard label="Submitted" :value="dateTime(verification.submitted_at)" icon="pi pi-calendar" tone="amber" />
        <SummaryCard label="Required documents" :value="`${documentSummary.required_valid || 0} / ${documentSummary.required_total || 0} valid`" icon="pi pi-file-check" tone="emerald" />
        <SummaryCard :label="isBranch ? 'Branch type' : 'Store type'" :value="title(isBranch ? branch?.branch_type : store?.type)" icon="pi pi-building" tone="slate" />
      </div>

      <div v-if="status === 'rejected'" class="rounded-2xl border border-red-200 bg-red-50 p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-red-600">Rejection reason</p>
        <p class="mt-2 whitespace-pre-wrap text-sm text-red-900">{{ verification.rejection_reason || '-' }}</p>
      </div>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(19rem,.75fr)]">
        <div class="space-y-6">
          <SectionCard :title="isBranch ? 'Branch information' : 'Business information'" icon="pi pi-building">
            <InfoGrid :items="businessInfo" />
          </SectionCard>

          <SectionCard :title="isBranch ? 'Store owner information' : 'Owner information'" icon="pi pi-user">
            <InfoGrid :items="ownerInfo" />
          </SectionCard>

          <SectionCard :title="isBranch ? 'Branch location' : 'Registered location'" icon="pi pi-map-marker">
            <InfoGrid :items="locationInfo" />
          </SectionCard>

          <SectionCard title="Submitted documents" icon="pi pi-images">
            <div class="mb-5 grid gap-3 sm:grid-cols-3">
              <Metric label="Submitted" :value="documentSummary.total_submitted || documentSummary.required_submitted || 0" />
              <Metric label="Valid" :value="documentSummary.required_valid || 0" tone="success" />
              <Metric label="Issues" :value="documentSummary.invalid_count || 0" :tone="documentSummary.invalid_count ? 'danger' : 'neutral'" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
              <article v-for="doc in submittedDocuments" :key="`${doc.key}-${doc.index ?? 'main'}`" class="rounded-2xl border border-slate-200 bg-slate-50">
                <div class="space-y-3 p-4">
                  <div class="flex items-start justify-between gap-2">
                    <div><p class="font-semibold text-slate-900">{{ doc.label }}</p><p class="mt-1 text-xs text-slate-500">{{ doc.required ? 'Required' : 'Optional' }} · {{ fileMeta(doc) }}</p></div>
                    <Tag :value="doc.is_valid ? 'Valid' : (doc.submitted ? 'Issue' : 'Missing')" :severity="doc.is_valid ? 'success' : (doc.submitted ? 'danger' : 'warn')" />
                  </div>
                  <dl v-if="doc.reference_number || doc.expires_at" class="space-y-2 rounded-xl border border-slate-200 bg-white p-3 text-xs">
                    <div v-if="doc.reference_number" class="flex justify-between gap-3">
                      <dt class="text-slate-500">Reference number</dt>
                      <dd class="break-all text-right font-medium text-slate-800">{{ doc.reference_number }}</dd>
                    </div>
                    <div v-if="doc.expires_at" class="flex justify-between gap-3">
                      <dt class="text-slate-500">Expiration date</dt>
                      <dd class="text-right font-medium text-slate-800">{{ dateOnly(doc.expires_at) }}</dd>
                    </div>
                  </dl>
                  <ul v-if="doc.issues?.length" class="space-y-1 text-xs text-red-600"><li v-for="issue in doc.issues" :key="issue">{{ issue }}</li></ul>
                  <div class="flex gap-2">
                    <Button label="View" icon="pi pi-eye" size="small" outlined :disabled="!doc.submitted" @click="openDocument(doc)" />
                    <Button label="Download" icon="pi pi-download" size="small" text :disabled="!doc.download_url" @click="downloadDocument(doc)" />
                  </div>
                </div>
              </article>
            </div>
            <p v-if="!submittedDocuments.length" class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No submitted attachments are available for this application.</p>
          </SectionCard>
        </div>

        <aside class="space-y-6">
          <SectionCard title="Review record" icon="pi pi-verified">
            <InfoList :items="reviewInfo" />
          </SectionCard>
          <SectionCard v-if="!isBranch" title="Store branches" icon="pi pi-sitemap">
            <div v-if="store?.branches?.length" class="space-y-3">
              <div v-for="branch in store.branches" :key="branch.id" class="rounded-xl border border-slate-200 p-3">
                <p class="font-semibold text-slate-900">{{ branch.name }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ branch.address || 'No address recorded' }}</p>
                <p class="mt-2 text-xs text-slate-600">{{ branch.contact_number || branch.phone || '-' }}</p>
              </div>
            </div>
            <p v-else class="text-sm text-slate-500">No branches recorded.</p>
          </SectionCard>
        </aside>
      </div>

      <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 pt-5">
          <Button v-if="status === 'pending'" label="Reject" icon="pi pi-times" severity="danger" outlined @click="rejectOpen = true" />
        <Button v-if="status === 'pending'" :label="isBranch ? 'Approve Branch' : 'Approve Store'" icon="pi pi-check" severity="success" :loading="processing" :disabled="!documentSummary.can_approve" @click="approveOpen = true" />
      </div>
    </template>

    <div v-else class="rounded-2xl border border-red-200 bg-red-50 p-8 text-center text-red-700">Unable to load this verification record.</div>

    <Dialog v-model:visible="approveOpen" modal :header="isBranch ? 'Approve branch verification' : 'Approve store verification'" :closable="!processing" :style="{ width: '32rem', maxWidth: '94vw' }">
      <p class="text-sm leading-6 text-slate-600">
        Approve <strong class="text-slate-900">{{ isBranch ? (branch?.name || 'this branch') : (store?.name || 'this store') }}</strong>? {{ isBranch ? 'This branch will become active; the parent store status will not change.' : 'The store will become active and its verification will be marked approved.' }}
      </p>
      <template #footer>
        <Button label="Cancel" severity="secondary" text :disabled="processing" @click="approveOpen = false" />
        <Button :label="isBranch ? 'Approve Branch' : 'Approve Store'" icon="pi pi-check" severity="success" :loading="processing" :disabled="!documentSummary.can_approve" @click="approve" />
      </template>
    </Dialog>

    <Dialog v-model:visible="rejectOpen" modal :header="isBranch ? 'Reject branch verification' : 'Reject store verification'" :style="{ width: '32rem', maxWidth: '94vw' }">
      <p class="mb-3 text-sm text-slate-600">Explain what must be corrected before {{ isBranch ? 'the branch' : 'the store' }} submits again.</p>
      <Textarea v-model="rejectionReason" rows="6" fluid placeholder="Detailed rejection reason" />
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="rejectOpen = false" />
        <Button label="Reject Verification" severity="danger" :loading="processing" :disabled="!rejectionReason.trim()" @click="reject" />
      </template>
    </Dialog>

    <Dialog v-model:visible="documentOpen" modal :header="selectedDocument?.label || 'Attachment'" :style="{ width: '62rem', maxWidth: '96vw' }" @hide="closeDocument">
      <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_17rem]">
        <div class="flex min-h-80 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
          <i v-if="documentLoading" class="pi pi-spin pi-spinner text-3xl text-slate-400" />
          <img v-else-if="documentObjectUrl && isImage(selectedDocument)" :src="documentObjectUrl" :alt="selectedDocument?.label" class="max-h-[70vh] max-w-full object-contain" />
          <iframe v-else-if="documentObjectUrl" :src="documentObjectUrl" :title="selectedDocument?.label" class="h-[70vh] w-full border-0" />
          <p v-else class="px-5 text-center text-sm text-slate-500">The attachment preview is unavailable.</p>
        </div>
        <div>
          <h3 class="text-sm font-semibold text-slate-900">Document details</h3>
          <dl class="mt-3 divide-y divide-slate-100 rounded-xl border border-slate-200 px-4">
            <div class="py-3"><dt class="text-xs text-slate-500">Document</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ selectedDocument?.label }}</dd></div>
            <div v-if="selectedDocument?.reference_number" class="py-3"><dt class="text-xs text-slate-500">Reference number</dt><dd class="mt-1 break-all text-sm font-medium text-slate-800">{{ selectedDocument.reference_number }}</dd></div>
            <div v-if="selectedDocument?.expires_at" class="py-3"><dt class="text-xs text-slate-500">Expiration date</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ dateOnly(selectedDocument.expires_at) }}</dd></div>
            <div class="py-3"><dt class="text-xs text-slate-500">File</dt><dd class="mt-1 text-sm font-medium text-slate-800">{{ fileMeta(selectedDocument || {}) }}</dd></div>
          </dl>
        </div>
      </div>
      <template #footer>
        <Button label="Close" severity="secondary" outlined @click="documentOpen = false" />
        <Button label="Download" icon="pi pi-download" severity="warn" :disabled="!selectedDocument?.download_url" @click="downloadDocument(selectedDocument)" />
      </template>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useToast } from 'primevue/usetoast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import Textarea from 'primevue/textarea'
import axiosClient from '@/axios'

const route = useRoute()
const router = useRouter()
const toast = useToast()
const loading = ref(true)
const processing = ref(false)
const payload = ref<any>(null)
const approveOpen = ref(false)
const rejectOpen = ref(false)
const rejectionReason = ref('')
const documentOpen = ref(false)
const documentLoading = ref(false)
const selectedDocument = ref<any>(null)
const documentObjectUrl = ref('')
const objectUrls = new Set<string>()

const verification = computed(() => payload.value?.verification || null)
const store = computed(() => payload.value?.store || null)
const branch = computed(() => payload.value?.branch || null)
const isBranch = computed(() => payload.value?.verification_type === 'branch')
const owner = computed(() => payload.value?.owner || null)
const documents = computed(() => payload.value?.documents?.documents || [])
const submittedDocuments = computed(() => documents.value.filter((doc: any) => doc.submitted))
const documentSummary = computed(() => payload.value?.documents?.summary || {})
const status = computed(() => payload.value?.status || 'pending')
const fullName = (person: any) => [person?.fname, person?.lname].filter(Boolean).join(' ') || '-'
const value = (input: any) => input === null || input === undefined || input === '' ? '-' : String(input)
const title = (input: any) => value(input).replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase())
const dateTime = (input: any) => input ? new Date(input).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '-'
const dateOnly = (input: any) => input ? new Date(input).toLocaleDateString('en-PH', { dateStyle: 'medium' }) : '-'
const statusSeverity = (input: string) => input === 'approved' ? 'success' : input === 'rejected' ? 'danger' : 'warn'

const businessInfo = computed(() => [
  ...(isBranch.value ? [
    ['Branch name', branch.value?.name], ['Branch code', branch.value?.branch_code], ['Branch type', title(branch.value?.branch_type)],
    ['Branch status', title(branch.value?.status)], ['Parent store', store.value?.name], ['Store type', title(store.value?.type)],
  ] : [
    ['Store name', store.value?.name], ['Store code', store.value?.store_code], ['Store type', title(store.value?.type)],
    ['Store status', title(store.value?.status)], ['Business registration no.', verification.value?.business_registration_number],
    ['Registration date', dateOnly(verification.value?.business_registration_date)], ['Store email', store.value?.email], ['Store phone', store.value?.phone],
  ]),
])
const ownerInfo = computed(() => [
  ['Owner name', fullName(owner.value)], ['Owner email', owner.value?.email || store.value?.email], ['Owner phone', owner.value?.phone_number || store.value?.phone],
  ['Birthday', dateOnly(owner.value?.birthday)], ['Government ID type', title(verification.value?.gov_id_type)], ['Government ID number', verification.value?.gov_id_number],
  ['Account created', dateTime(owner.value?.created_at)], ['Store created', dateTime(store.value?.created_at)],
])
const locationInfo = computed(() => [
  ...(isBranch.value ? [
    ['Branch address', branch.value?.address], ['Barangay', branch.value?.barangay], ['City', branch.value?.city], ['Province', branch.value?.province],
    ['Coordinates', branch.value?.latitude && branch.value?.longitude ? `${branch.value.latitude}, ${branch.value.longitude}` : '-'],
  ] : [
    ['Verification address', verification.value?.address], ['Store address', store.value?.address], ['Barangay', store.value?.barangay],
    ['City', verification.value?.city || store.value?.city], ['Province', verification.value?.province],
  ]),
])
const reviewInfo = computed(() => [
  ['Submitted', dateTime(verification.value?.submitted_at)], ['Reviewed', dateTime(verification.value?.reviewed_at)],
  ['Reviewed by', fullName(verification.value?.reviewer)], ['Reviewer email', verification.value?.reviewer?.email],
  ['Last updated', dateTime(verification.value?.updated_at)], ['Record created', dateTime(verification.value?.created_at)],
])

const SummaryCard = defineComponent({ props: { label: String, value: String, icon: String, tone: String }, setup: props => () => h('div', { class: 'rounded-2xl border border-slate-100 bg-white p-5 shadow-sm' }, [h('div', { class: 'flex items-center justify-between gap-3' }, [h('div', [h('p', { class: 'text-xs font-semibold uppercase tracking-wider text-slate-500' }, props.label), h('p', { class: 'mt-2 text-lg font-semibold text-slate-950' }, props.value)]), h('span', { class: 'flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600' }, [h('i', { class: props.icon })])])]) })
const SectionCard = defineComponent({ props: { title: String, icon: String }, setup: (props, { slots }) => () => h('section', { class: 'rounded-2xl border border-slate-100 bg-white p-6 shadow-sm' }, [h('div', { class: 'mb-5 flex items-center gap-3' }, [h('span', { class: 'flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600' }, [h('i', { class: props.icon })]), h('h2', { class: 'font-semibold text-slate-950' }, props.title)]), slots.default?.()]) })
const InfoGrid = defineComponent({ props: { items: Array }, setup: props => () => h('div', { class: 'grid gap-x-6 gap-y-5 sm:grid-cols-2 lg:grid-cols-3' }, (props.items as any[]).map(item => h('div', [h('p', { class: 'text-xs font-medium uppercase tracking-wider text-slate-400' }, item[0]), h('p', { class: 'mt-1 break-words text-sm font-medium text-slate-800' }, value(item[1]))]))) })
const InfoList = defineComponent({ props: { items: Array }, setup: props => () => h('dl', { class: 'divide-y divide-slate-100' }, (props.items as any[]).map(item => h('div', { class: 'flex justify-between gap-4 py-3 text-sm' }, [h('dt', { class: 'text-slate-500' }, item[0]), h('dd', { class: 'text-right font-medium text-slate-800' }, value(item[1]))]))) })
const Metric = defineComponent({ props: { label: String, value: Number, tone: String }, setup: props => () => h('div', { class: 'rounded-xl bg-slate-50 p-3' }, [h('p', { class: 'text-xs text-slate-500' }, props.label), h('p', { class: 'mt-1 text-xl font-semibold text-slate-900' }, String(props.value ?? 0))]) })

const apiPath = (url: string) => { try { const parsed = new URL(url, window.location.origin); return `${parsed.pathname}${parsed.search}` } catch { return url } }
const createObjectUrl = (blob: Blob) => { const url = URL.createObjectURL(blob); objectUrls.add(url); return url }
const fetchBlob = async (url: string) => { const response = await axiosClient.get(apiPath(url), { responseType: 'blob' }); return new Blob([response.data], { type: response.headers['content-type'] || response.data?.type || 'application/octet-stream' }) }
const isBranchRoute = () => route.name === 'admin.branch-validation.show' || String(route.path).includes('/store-validation/branch/')
const load = async () => { loading.value = true; try { const endpoint = isBranchRoute() ? `/api/branch-verification/${route.params.id}` : `/api/store-verification/${route.params.id}`; const response = await axiosClient.get(endpoint); payload.value = response.data?.data } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load verification', detail: error?.response?.data?.message || 'Please try again.', life: 3500 }) } finally { loading.value = false } }
const review = async (action: 'approve' | 'reject', reason?: string) => { processing.value = true; try { const endpoint = isBranch.value ? `/api/branch-verification/${route.params.id}/review` : `/api/store-verification/${route.params.id}/review`; await axiosClient.post(endpoint, { action, rejection_reason: reason }); toast.add({ severity: 'success', summary: `${isBranch.value ? 'Branch' : 'Store'} ${action === 'approve' ? 'approved' : 'rejected'}`, detail: 'The verification record has been updated.', life: 3000 }); approveOpen.value = false; rejectOpen.value = false; rejectionReason.value = ''; await load() } catch (error: any) { toast.add({ severity: 'error', summary: 'Review failed', detail: error?.response?.data?.message || 'Unable to update verification.', life: 4000 }) } finally { processing.value = false } }
const approve = () => review('approve')
const reject = () => review('reject', rejectionReason.value.trim())
const goBack = () => router.push({ name: 'AdminStoreValidation' })
const isImage = (doc: any) => String(doc?.mime_type || '').startsWith('image/')
const fileMeta = (doc: any) => !doc.submitted ? 'Not submitted' : [doc.mime_type?.split('/').pop()?.toUpperCase(), doc.size_kb ? `${doc.size_kb} KB` : null].filter(Boolean).join(' · ')
const openDocument = async (doc: any) => {
  if (!doc.preview_url) return
  selectedDocument.value = doc
  documentOpen.value = true
  documentLoading.value = true
  documentObjectUrl.value = ''
  try { documentObjectUrl.value = createObjectUrl(await fetchBlob(doc.preview_url)) }
  catch (error: any) { toast.add({ severity: 'error', summary: 'Preview unavailable', detail: error?.response?.data?.message || 'Unable to load this attachment.', life: 3500 }) }
  finally { documentLoading.value = false }
}
const closeDocument = () => {
  if (documentObjectUrl.value) {
    URL.revokeObjectURL(documentObjectUrl.value)
    objectUrls.delete(documentObjectUrl.value)
  }
  documentObjectUrl.value = ''
  selectedDocument.value = null
}
const downloadDocument = async (doc: any) => {
  if (!doc.download_url) return
  try {
    const url = createObjectUrl(await fetchBlob(doc.download_url))
    const link = document.createElement('a'); link.href = url; link.download = doc.filename || doc.label || 'attachment'; document.body.appendChild(link); link.click(); link.remove()
  } catch (error: any) { toast.add({ severity: 'error', summary: 'Download failed', detail: error?.response?.data?.message || 'Unable to download this attachment.', life: 3500 }) }
}

onMounted(load)
onBeforeUnmount(() => objectUrls.forEach(url => URL.revokeObjectURL(url)))
</script>
