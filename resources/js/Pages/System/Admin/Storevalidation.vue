<template>
  <div class="mx-auto max-w-7xl space-y-6 pb-10">
    <section class="overflow-hidden rounded-3xl bg-slate-950 text-white shadow-xl">
      <div class="relative grid gap-8 px-6 py-8 sm:px-9 lg:grid-cols-[1fr_auto] lg:items-end">
        <div class="absolute -right-16 -top-24 h-64 w-64 rounded-full bg-emerald-400/20 blur-3xl"></div>
        <div class="relative">
          <p class="mb-3 text-xs font-bold uppercase tracking-[0.22em] text-emerald-300">Trust &amp; compliance</p>
          <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Store verification</h1>
          <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
            Review business identities, ownership details, and submitted documents from one clear workspace.
          </p>
        </div>
        <div class="relative flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-5 py-4 backdrop-blur">
          <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-300 text-slate-950">
            <i class="pi pi-clock text-lg"></i>
          </span>
          <div>
            <p class="text-2xl font-bold">{{ pendingStores.length }}</p>
            <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Awaiting review</p>
          </div>
        </div>
      </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <button
        v-for="card in statusCards"
        :key="card.value"
        type="button"
        class="group rounded-2xl border bg-white p-5 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
        :class="activeView === card.value ? card.activeClass : 'border-slate-200'"
        @click="activeView = card.value"
      >
        <div class="flex items-center justify-between">
          <span class="flex h-10 w-10 items-center justify-center rounded-xl" :class="card.iconClass">
            <i :class="card.icon"></i>
          </span>
          <span class="text-2xl font-bold text-slate-900">{{ card.count }}</span>
        </div>
        <p class="mt-4 font-semibold text-slate-800">{{ card.label }}</p>
        <p class="mt-1 text-xs text-slate-500">{{ card.description }}</p>
      </button>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
      <div class="flex flex-col gap-4 border-b border-slate-200 p-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-lg font-bold text-slate-900">{{ activeTitle }}</h2>
          <p class="mt-1 text-sm text-slate-500">{{ filteredStores.length }} application{{ filteredStores.length === 1 ? '' : 's' }}</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
          <IconField class="w-full sm:w-72">
            <InputIcon class="pi pi-search" />
            <InputText v-model="search" placeholder="Search store or owner" class="w-full" />
          </IconField>
          <Select
            v-model="documentFilter"
            :options="documentOptions"
            option-label="label"
            option-value="value"
            class="w-full sm:w-52"
          />
          <Button icon="pi pi-refresh" severity="secondary" outlined :loading="loading" aria-label="Refresh" @click="fetchStores" />
        </div>
      </div>

      <div v-if="loading" class="space-y-4 p-6">
        <Skeleton v-for="item in 3" :key="item" height="8rem" border-radius="1rem" />
      </div>

      <div v-else-if="filteredStores.length" class="divide-y divide-slate-100">
        <article
          v-for="store in filteredStores"
          :key="store.verificationId"
          class="grid gap-5 p-5 transition hover:bg-slate-50/80 md:grid-cols-[minmax(0,1.4fr)_minmax(220px,.8fr)_auto] md:items-center lg:p-6"
        >
          <div class="flex min-w-0 items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-slate-900 font-bold text-white">
              {{ initials(store.storeName) }}
            </div>
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="truncate text-base font-bold text-slate-900">{{ store.storeName }}</h3>
                <Tag :value="store.status" :severity="statusSeverity(store.status)" rounded />
              </div>
              <p class="mt-1 truncate text-sm text-slate-600">{{ store.ownerName }} · {{ store.ownerEmail }}</p>
              <p class="mt-2 flex items-start gap-2 text-xs leading-5 text-slate-500">
                <i class="pi pi-map-marker mt-0.5"></i>
                <span>{{ store.address }}</span>
              </p>
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between text-xs">
              <span class="font-semibold text-slate-600">Documents</span>
              <span class="text-slate-500">{{ store.documentCount }} submitted</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
              <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${store.documentProgress}%` }"></div>
            </div>
            <p class="mt-3 text-xs text-slate-500">Submitted {{ formatDate(store.submittedAt) }}</p>
          </div>

          <Button
            :label="store.status === 'Pending' ? 'Review' : 'View details'"
            icon="pi pi-arrow-right"
            icon-pos="right"
            :severity="store.status === 'Pending' ? undefined : 'secondary'"
            :outlined="store.status !== 'Pending'"
            @click="openStore(store)"
          />
        </article>
      </div>

      <div v-else class="px-6 py-16 text-center">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
          <i class="pi pi-inbox text-xl"></i>
        </span>
        <h3 class="mt-4 font-bold text-slate-800">No applications found</h3>
        <p class="mt-1 text-sm text-slate-500">Try another status, document filter, or search term.</p>
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import axiosClient from '../../../axios'

type ViewStatus = 'pending' | 'approved' | 'rejected' | 'all'

interface StoreRecord {
  verificationId: number
  storeName: string
  ownerName: string
  ownerEmail: string
  address: string
  status: string
  submittedAt: string | null
  documentCount: number
  documentProgress: number
}

const router = useRouter()
const loading = ref(false)
const search = ref('')
const activeView = ref<ViewStatus>('pending')
const documentFilter = ref('all')
const pendingStores = ref<StoreRecord[]>([])
const approvedStores = ref<StoreRecord[]>([])
const rejectedStores = ref<StoreRecord[]>([])

const documentOptions = [
  { label: 'All submissions', value: 'all' },
  { label: 'With documents', value: 'with' },
  { label: 'Missing documents', value: 'without' },
]

const allStores = computed(() => [...pendingStores.value, ...approvedStores.value, ...rejectedStores.value])
const activeStores = computed(() => activeView.value === 'all' ? allStores.value : {
  pending: pendingStores.value,
  approved: approvedStores.value,
  rejected: rejectedStores.value,
}[activeView.value])

const filteredStores = computed(() => {
  const term = search.value.trim().toLowerCase()
  return activeStores.value.filter((store) => {
    const matchesSearch = !term || [store.storeName, store.ownerName, store.ownerEmail, store.address]
      .some((value) => value.toLowerCase().includes(term))
    const matchesDocuments = documentFilter.value === 'all'
      || (documentFilter.value === 'with' && store.documentCount > 0)
      || (documentFilter.value === 'without' && store.documentCount === 0)
    return matchesSearch && matchesDocuments
  })
})

const statusCards = computed(() => [
  { value: 'pending', label: 'Pending review', description: 'Applications needing a decision', count: pendingStores.value.length, icon: 'pi pi-clock', iconClass: 'bg-amber-100 text-amber-700', activeClass: 'border-amber-400 ring-2 ring-amber-100' },
  { value: 'approved', label: 'Approved', description: 'Verified stores ready to operate', count: approvedStores.value.length, icon: 'pi pi-check-circle', iconClass: 'bg-emerald-100 text-emerald-700', activeClass: 'border-emerald-400 ring-2 ring-emerald-100' },
  { value: 'rejected', label: 'Rejected', description: 'Applications that need correction', count: rejectedStores.value.length, icon: 'pi pi-times-circle', iconClass: 'bg-rose-100 text-rose-700', activeClass: 'border-rose-400 ring-2 ring-rose-100' },
  { value: 'all', label: 'All applications', description: 'Complete verification history', count: allStores.value.length, icon: 'pi pi-building', iconClass: 'bg-sky-100 text-sky-700', activeClass: 'border-sky-400 ring-2 ring-sky-100' },
])

const activeTitle = computed(() => statusCards.value.find((item) => item.value === activeView.value)?.label || 'Applications')

const extractRows = (response: any) => response?.data?.data?.data || response?.data?.data || []
const mapStore = (item: any): StoreRecord => {
  const store = item.store || {}
  const summary = item.documents_summary || {}
  const documents = item.documents || []
  const count = Number(summary.total_submitted ?? documents.length ?? 0)
  const expected = Number(summary.total ?? summary.required_total ?? count)

  return {
    verificationId: Number(item.id),
    storeName: store.store_name || store.name || item.store_name || 'Unnamed store',
    ownerName: item.owner?.full_name || [item.owner?.fname, item.owner?.lname].filter(Boolean).join(' ') || store.owner_name || item.owner_name || 'Owner not provided',
    ownerEmail: item.owner?.email || store.email || item.owner_email || 'No email provided',
    address: [item.address || store.address, item.city || store.city, store.province].filter(Boolean).join(', ') || 'Address not provided',
    status: String(item.verification_status || 'pending').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()),
    submittedAt: item.submitted_at || item.created_at || null,
    documentCount: count,
    documentProgress: expected > 0 ? Math.min(100, Math.round((count / expected) * 100)) : 0,
  }
}

const fetchStores = async () => {
  loading.value = true
  try {
    const [pending, approved, rejected] = await Promise.all([
      axiosClient.get('/api/pending-verification', { params: { per_page: 100 } }),
      axiosClient.get('/api/store-verifications', { params: { status: 'approved', per_page: 100 } }),
      axiosClient.get('/api/store-verifications', { params: { status: 'rejected', per_page: 100 } }),
    ])
    pendingStores.value = extractRows(pending).map(mapStore)
    approvedStores.value = extractRows(approved).map(mapStore)
    rejectedStores.value = extractRows(rejected).map(mapStore)
  } finally {
    loading.value = false
  }
}

const openStore = (store: StoreRecord) => router.push({ name: 'admin.store-validation.show', params: { id: store.verificationId } })
const initials = (name: string) => name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase()
const statusSeverity = (status: string) => ({ Pending: 'warn', Approved: 'success', Rejected: 'danger' }[status] || 'secondary')
const formatDate = (value: string | null) => value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Not available'

onMounted(fetchStores)
</script>
