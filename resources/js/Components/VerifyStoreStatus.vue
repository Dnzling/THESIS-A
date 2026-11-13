<template>
  <div class="min-h-screen py-8">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
      <div class="rounded-3xl border border-orange-100 bg-gradient-to-br from-orange-50 via-white to-amber-50 p-6 shadow-sm">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
          <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-600">Store owner verification</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-950">Verification Status</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
              Track the review of your owner ID and business document submission.
            </p>
          </div>
          <div class="inline-flex items-center gap-3 rounded-full px-5 py-3 text-sm font-semibold" :class="statusClasses">
            <i :class="statusIcon"></i>
            {{ statusText }}
          </div>
        </div>
      </div>

      <div class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-slate-950">Timeline</h2>
          <div class="mt-6 space-y-6">
            <TimelineItem title="Owner IDs Submitted" :date="verificationData.submittedAt" active complete>
              Your owner ID and business documents were sent for review.
            </TimelineItem>
            <TimelineItem title="Document Review" :date="verificationData.reviewedAt" :active="isReviewing" :complete="isFinished">
              {{ reviewDescription }}
            </TimelineItem>
            <TimelineItem title="Verification Complete" :date="verificationData.completedAt" :active="isFinished" :complete="verificationData.status === 'approved'" :rejected="verificationData.status === 'rejected'">
              {{ completionDescription }}
            </TimelineItem>
          </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <h2 class="text-lg font-semibold text-slate-950">Submitted Documents</h2>
          <div class="mt-5 space-y-3">
            <div class="rounded-2xl bg-slate-50 p-4">
              <p class="text-sm font-semibold text-slate-900">Owner Primary ID</p>
              <p class="mt-1 text-xs text-slate-500">Front photo required. Back photo is optional when applicable.</p>
            </div>
            <div class="rounded-2xl bg-slate-50 p-4">
              <p class="text-sm font-semibold text-slate-900">Business Documents</p>
              <p class="mt-1 text-xs text-slate-500">Registration permit, BIR certificate, and mayor's/business permit.</p>
            </div>
            <div v-if="verificationData.reviewerNotes" class="rounded-2xl border border-red-100 bg-red-50 p-4">
              <p class="text-sm font-semibold text-red-800">Reviewer Notes</p>
              <p class="mt-1 text-sm text-red-700">{{ verificationData.reviewerNotes }}</p>
            </div>
          </div>

          <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <Button v-if="verificationData.status === 'rejected'" label="Resubmit IDs" severity="warn" icon="pi pi-refresh" @click="$emit('resubmit')" />
            <Button label="Contact Support" severity="secondary" outlined icon="pi pi-envelope" @click="contactSupport" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, h, onMounted, ref, watch } from 'vue'
import { useToast } from 'primevue/usetoast'

const props = defineProps({
  verificationData: {
    type: Object,
    default: null,
  },
})

defineEmits(['resubmit'])

const toast = useToast()
const verificationData = ref({
  submittedAt: new Date().toISOString(),
  reviewedAt: null,
  completedAt: null,
  status: 'reviewing',
  reviewerNotes: '',
})

const normalizeStatus = (status) => {
  if (status === 'under_review') return 'reviewing'
  if (status === 'verified') return 'approved'
  return status || 'pending'
}

const applyVerificationData = (data) => {
  if (!data || typeof data !== 'object') return

  const mappedStatus = normalizeStatus(data.store_status || data.status)
  const reviewedAt = data.reviewed_at || data.reviewedAt || null

  verificationData.value = {
    ...verificationData.value,
    status: mappedStatus,
    submittedAt: data.submitted_at || data.submittedAt || verificationData.value.submittedAt,
    reviewedAt,
    completedAt: mappedStatus === 'approved' ? (data.completedAt || reviewedAt) : null,
    reviewerNotes: data.rejection_reason || data.reviewerNotes || '',
  }
}

onMounted(() => applyVerificationData(props.verificationData))
watch(() => props.verificationData, applyVerificationData, { deep: true })

const isReviewing = computed(() => ['reviewing', 'approved', 'rejected'].includes(verificationData.value.status))
const isFinished = computed(() => ['approved', 'rejected'].includes(verificationData.value.status))

const statusText = computed(() => {
  switch (verificationData.value.status) {
    case 'pending': return 'Pending Submission'
    case 'reviewing': return 'Under Review'
    case 'approved': return 'Verified'
    case 'rejected': return 'Rejected'
    default: return 'Pending'
  }
})

const statusIcon = computed(() => {
  switch (verificationData.value.status) {
    case 'reviewing': return 'pi pi-spin pi-spinner'
    case 'approved': return 'pi pi-check-circle'
    case 'rejected': return 'pi pi-times-circle'
    default: return 'pi pi-clock'
  }
})

const statusClasses = computed(() => {
  switch (verificationData.value.status) {
    case 'reviewing': return 'bg-orange-100 text-orange-800'
    case 'approved': return 'bg-emerald-100 text-emerald-800'
    case 'rejected': return 'bg-red-100 text-red-800'
    default: return 'bg-slate-100 text-slate-700'
  }
})

const reviewDescription = computed(() => {
  if (verificationData.value.status === 'reviewing') return 'Admin is checking the ID photos and owner details.'
  if (isFinished.value) return 'Document review completed.'
  return 'Waiting for submitted owner IDs.'
})

const completionDescription = computed(() => {
  if (verificationData.value.status === 'approved') return 'Your store has been verified and activated.'
  if (verificationData.value.status === 'rejected') return 'Please review the notes and submit clearer or corrected IDs.'
  return 'This will update once admin finishes the review.'
})

const formatDate = (dateString) => {
  if (!dateString) return 'Pending'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const TimelineItem = (props, context) => {
  const markerClass = props.rejected
    ? 'bg-red-500 text-white'
    : props.complete
      ? 'bg-emerald-500 text-white'
      : props.active
        ? 'bg-orange-500 text-white'
        : 'bg-slate-200 text-slate-500'

  return h('div', { class: 'flex gap-4' }, [
    h('div', { class: `mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${markerClass}` }, [
      h('i', { class: props.rejected ? 'pi pi-times' : props.complete ? 'pi pi-check' : 'pi pi-clock' }),
    ]),
    h('div', [
      h('div', { class: 'flex flex-wrap items-center gap-2' }, [
        h('h3', { class: 'font-semibold text-slate-950' }, props.title),
        h('span', { class: 'text-xs text-slate-500' }, formatDate(props.date)),
      ]),
      h('p', { class: 'mt-1 text-sm text-slate-600' }, context.slots.default?.()),
    ]),
  ])
}

const contactSupport = () => {
  toast.add({ severity: 'info', summary: 'Contact Support', detail: 'support@furniturestore.com', life: 3000 })
}
</script>
