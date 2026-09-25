<template>
  <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <button class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white shadow-sm transition hover:bg-slate-50" @click="goBack">
          <i class="pi pi-chevron-left text-slate-600" />
        </button>
        <div>
          <p class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-600">Store verification</p>
          <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ store?.name || 'Verification details' }}</h1>
          <p class="mt-1 text-sm text-slate-500">Application #{{ verification?.id || route.params.id }} · {{ store?.store_code || `STORE-${store?.id || '-'}` }}</p>
        </div>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <Button v-if="status === 'pending'" label="Reject" icon="pi pi-times" severity="danger" outlined @click="rejectOpen = true" />
        <Button v-if="status === 'pending'" label="Approve Store" icon="pi pi-check" severity="success" :loading="processing" :disabled="!documentSummary.can_approve" @click="approveOpen = true" />
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
        <SummaryCard label="Store type" :value="title(store?.type)" icon="pi pi-building" tone="slate" />
      </div>

      <div v-if="status === 'rejected'" class="rounded-2xl border border-red-200 bg-red-50 p-5">
        <p class="text-xs font-semibold uppercase tracking-wider text-red-600">Rejection reason</p>
        <p class="mt-2 whitespace-pre-wrap text-sm text-red-900">{{ verification.rejection_reason || '-' }}</p>
      </div>

      <div class="grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(19rem,.75fr)]">
        <div class="space-y-6">
          <SectionCard title="Business information" icon="pi pi-building">
            <InfoGrid :items="businessInfo" />
          </SectionCard>

          <SectionCard title="Owner information" icon="pi pi-user">
            <InfoGrid :items="ownerInfo" />
          </SectionCard>

          <SectionCard title="Registered location" icon="pi pi-map-marker">
            <InfoGrid :items="locationInfo" />
          </SectionCard>

          <SectionCard title="Submitted documents" icon="pi pi-images">
            <div class="mb-5 grid gap-3 sm:grid-cols-3">
              <Metric label="Submitted" :value="documentSummary.required_submitted || 0" />
              <Metric label="Valid" :value="documentSummary.required_valid || 0" tone="success" />
              <Metric label="Issues" :value="documentSummary.invalid_count || 0" :tone="documentSummary.invalid_count ? 'danger' : 'neutral'" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
              <article v-for="doc in documents" :key="`${doc.key}-${doc.index ?? 'main'}`" class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                <button v-if="doc.preview_url" class="block h-40 w-full overflow-hidden bg-slate-100" @click="openDocument(doc)">
                  <img v-if="thumbnailUrls[documentId(doc)]" :src="thumbnailUrls[documentId(doc)]" :alt="doc.label" class="h-full w-full object-cover transition duration-300 hover:scale-[1.03]" />
                  <iframe v-else-if="pdfPreviewUrls[documentId(doc)]" :src="`${pdfPreviewUrls[documentId(doc)]}#toolbar=0&navpanes=0&scrollbar=0`" :title="doc.label" tabindex="-1" class="pointer-events-none h-full w-full border-0" />
                  <span v-else class="flex h-full items-center justify-center"><i class="pi pi-spin pi-spinner text-2xl text-slate-400" /></span>
                </button>
                <button v-else class="flex h-40 w-full items-center justify-center bg-slate-100" :disabled="!doc.submitted" @click="openDocument(doc)">
                  <i :class="doc.submitted ? 'pi pi-file-pdf text-red-500' : 'pi pi-file text-slate-300'" class="text-5xl" />
                </button>
                <div class="space-y-3 p-4">
                  <div class="flex items-start justify-between gap-2">
                    <div><p class="font-semibold text-slate-900">{{ doc.label }}</p><p class="mt-1 text-xs text-slate-500">{{ doc.required ? 'Required' : 'Optional' }} · {{ fileMeta(doc) }}</p></div>
                    <Tag :value="doc.is_valid ? 'Valid' : (doc.submitted ? 'Issue' : 'Missing')" :severity="doc.is_valid ? 'success' : (doc.submitted ? 'danger' : 'warn')" />
                  </div>
                  <ul v-if="doc.issues?.length" class="space-y-1 text-xs text-red-600"><li v-for="issue in doc.issues" :key="issue">{{ issue }}</li></ul>
                  <div class="flex gap-2">
                    <Button label="View" icon="pi pi-eye" size="small" outlined :disabled="!doc.submitted" @click="openDocument(doc)" />
                    <Button label="Download" icon="pi pi-download" size="small" text :disabled="!doc.download_url" @click="downloadDocument(doc)" />
                  </div>
                </div>
              </article>
            </div>
          </SectionCard>
        </div>

        <aside class="space-y-6">
          <SectionCard title="Review record" icon="pi pi-verified">
            <InfoList :items="reviewInfo" />
          </SectionCard>
          <SectionCard title="Store branches" icon="pi pi-sitemap">
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
    </template>

    <div v-else class="rounded-2xl border border-red-200 bg-red-50 p-8 text-center text-red-700">Unable to load this verification record.</div>

    <Dialog v-model:visible="approveOpen" modal header="Approve store verification" :closable="!processing" :style="{ width: '32rem', maxWidth: '94vw' }">
      <p class="text-sm leading-6 text-slate-600">
        Approve <strong class="text-slate-900">{{ store?.name || 'this store' }}</strong>? The store will become active and its verification will be marked approved.
      </p>
      <template #footer>
        <Button label="Cancel" severity="secondary" text :disabled="processing" @click="approveOpen = false" />
        <Button label="Approve Store" icon="pi pi-check" severity="success" :loading="processing" :disabled="!documentSummary.can_approve" @click="approve" />
      </template>
    </Dialog>

    <Dialog v-model:visible="rejectOpen" modal header="Reject store verification" :style="{ width: '32rem', maxWidth: '94vw' }">
      <p class="mb-3 text-sm text-slate-600">Explain what must be corrected before the store submits again.</p>
      <Textarea v-model="rejectionReason" rows="6" fluid placeholder="Detailed rejection reason" />
      <template #footer>
        <Button label="Cancel" severity="secondary" text @click="rejectOpen = false" />
        <Button label="Reject Verification" severity="danger" :loading="processing" :disabled="!rejectionReason.trim()" @click="reject" />
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
const thumbnailUrls = ref<Record<string, string>>({})
const pdfPreviewUrls = ref<Record<string, string>>({})
const objectUrls = new Set<string>()

const verification = computed(() => payload.value?.verification || null)
const store = computed(() => payload.value?.store || null)
const owner = computed(() => payload.value?.owner || null)
const documents = computed(() => payload.value?.documents?.documents || [])
const documentSummary = computed(() => payload.value?.documents?.summary || {})
const status = computed(() => payload.value?.status || 'pending')
const fullName = (person: any) => [person?.fname, person?.lname].filter(Boolean).join(' ') || '-'
const value = (input: any) => input === null || input === undefined || input === '' ? '-' : String(input)
const title = (input: any) => value(input).replaceAll('_', ' ').replace(/\b\w/g, char => char.toUpperCase())
const dateTime = (input: any) => input ? new Date(input).toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' }) : '-'
const dateOnly = (input: any) => input ? new Date(input).toLocaleDateString('en-PH', { dateStyle: 'medium' }) : '-'
const statusSeverity = (input: string) => input === 'approved' ? 'success' : input === 'rejected' ? 'danger' : 'warn'

const businessInfo = computed(() => [
  ['Store name', store.value?.name], ['Store code', store.value?.store_code], ['Store type', title(store.value?.type)],
  ['Store status', title(store.value?.status)], ['Business registration no.', verification.value?.business_registration_number],
  ['Registration date', dateOnly(verification.value?.business_registration_date)], ['Store email', store.value?.email], ['Store phone', store.value?.phone],
])
const ownerInfo = computed(() => [
  ['Owner name', fullName(owner.value)], ['Owner email', owner.value?.email || store.value?.email], ['Owner phone', owner.value?.phone_number || store.value?.phone],
  ['Birthday', dateOnly(owner.value?.birthday)], ['Government ID type', title(verification.value?.gov_id_type)], ['Government ID number', verification.value?.gov_id_number],
  ['Account created', dateTime(owner.value?.created_at)], ['Store created', dateTime(store.value?.created_at)],
])
const locationInfo = computed(() => [
  ['Verification address', verification.value?.address], ['Store address', store.value?.address], ['Barangay', store.value?.barangay],
  ['City', verification.value?.city || store.value?.city], ['Province', store.value?.province]
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
const documentId = (doc: any) => `${doc.key}-${doc.index ?? 'main'}`
const createObjectUrl = (blob: Blob) => { const url = URL.createObjectURL(blob); objectUrls.add(url); return url }
const fetchBlob = async (url: string) => { const response = await axiosClient.get(apiPath(url), { responseType: 'blob' }); return new Blob([response.data], { type: response.headers['content-type'] || response.data?.type || 'application/octet-stream' }) }
const loadThumbnails = async () => {
  const previewable = documents.value.filter((doc: any) => doc.preview_url)
  await Promise.all(previewable.map(async (doc: any) => {
    try {
      const url = createObjectUrl(await fetchBlob(doc.preview_url))
      if (isImage(doc)) thumbnailUrls.value[documentId(doc)] = url
      else if (String(doc.mime_type) === 'application/pdf') pdfPreviewUrls.value[documentId(doc)] = url
    } catch { /* The card remains available for retry. */ }
  }))
}
const load = async () => { loading.value = true; try { const response = await axiosClient.get(`/api/store-verification/${route.params.id}`); payload.value = response.data?.data; await loadThumbnails() } catch (error: any) { toast.add({ severity: 'error', summary: 'Unable to load verification', detail: error?.response?.data?.message || 'Please try again.', life: 3500 }) } finally { loading.value = false } }
const review = async (action: 'approve' | 'reject', reason?: string) => { processing.value = true; try { await axiosClient.post(`/api/store-verification/${route.params.id}/review`, { action, rejection_reason: reason }); toast.add({ severity: 'success', summary: action === 'approve' ? 'Store approved' : 'Store rejected', detail: 'The verification record has been updated.', life: 3000 }); approveOpen.value = false; rejectOpen.value = false; rejectionReason.value = ''; await load() } catch (error: any) { toast.add({ severity: 'error', summary: 'Review failed', detail: error?.response?.data?.message || 'Unable to update verification.', life: 4000 }) } finally { processing.value = false } }
const approve = () => review('approve')
const reject = () => review('reject', rejectionReason.value.trim())
const goBack = () => router.push({ name: 'AdminStoreValidation' })
const isImage = (doc: any) => String(doc?.mime_type || '').startsWith('image/')
const fileMeta = (doc: any) => !doc.submitted ? 'Not submitted' : [doc.mime_type?.split('/').pop()?.toUpperCase(), doc.size_kb ? `${doc.size_kb} KB` : null].filter(Boolean).join(' · ')
const openDocument = async (doc: any) => {
  if (!doc.preview_url) return
  try { window.open(createObjectUrl(await fetchBlob(doc.preview_url)), '_blank', 'noopener') }
  catch (error: any) { toast.add({ severity: 'error', summary: 'Preview unavailable', detail: error?.response?.data?.message || 'Unable to load this attachment.', life: 3500 }) }
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
