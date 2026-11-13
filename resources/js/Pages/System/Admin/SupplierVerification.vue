<template>
  <div class="mx-auto max-w-7xl space-y-5 pb-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-xl font-semibold text-slate-900">Supplier Verification</h1>
        <p class="mt-1 text-sm text-slate-500">Review supplier registration details and submitted documents.</p>
      </div>
      <Button label="Back to Suppliers" icon="pi pi-arrow-left" outlined rounded size="small" severity="secondary" @click="router.push({ name: 'admin.suppliers.index' })" />
    </div>

    <section class="flex flex-wrap items-center gap-x-7 gap-y-2 border-b border-slate-200 px-1">
      <button v-for="filter in statusTabs" :key="filter.value" type="button" class="border-b-2 px-1 pb-3 text-sm font-medium transition-colors" :class="status === filter.value ? 'border-orange-500 text-orange-600' : 'border-transparent text-slate-600 hover:text-orange-600'" :aria-pressed="status === filter.value" @click="status = filter.value">
        {{ filter.label }} <span class="ml-1 text-xs text-slate-400">{{ filter.count }}</span>
      </button>
    </section>

    <Card class="overflow-hidden border border-slate-200 shadow-none">
      <template #content>
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h2 class="font-semibold text-slate-900">{{ activeTitle }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ filtered.length }} supplier application{{ filtered.length === 1 ? '' : 's' }}</p>
          </div>
          <div class="flex flex-col gap-2 sm:flex-row">
            <IconField class="w-full sm:w-72"><InputIcon class="pi pi-search" /><InputText v-model="search" placeholder="Search supplier or contact" class="w-full" size="small" /></IconField>
            <Select v-model="documentFilter" :options="documentOptions" option-label="label" option-value="value" size="small" class="w-full sm:w-48" />
            <Button icon="pi pi-refresh" severity="secondary" outlined rounded size="small" aria-label="Refresh applications" :loading="loading" @click="load" />
          </div>
        </div>

        <div v-if="loading" class="space-y-4 py-5"><Skeleton v-for="item in 3" :key="item" height="7rem" border-radius=".75rem" /></div>
        <div v-else-if="filtered.length" class="divide-y divide-slate-100">
          <article v-for="supplier in filtered" :key="supplier.portalId" class="grid gap-4 py-5 transition hover:bg-slate-50/70 md:grid-cols-[minmax(0,1.4fr)_minmax(180px,.7fr)_auto] md:items-center">
            <div class="flex min-w-0 items-start gap-3">
              <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-sm font-semibold text-slate-700">{{ initials(supplier.name) }}</span>
              <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-slate-900">{{ supplier.name }}</h3><Badge :value="title(supplier.status)" :severity="statusSeverity(supplier.status)" /></div>
                <p class="mt-1 break-all text-sm text-slate-600">{{ supplier.contact }} <span class="text-slate-400">·</span> {{ supplier.email }}</p>
                <p class="mt-2 text-xs text-slate-500">Submitted {{ formatDate(supplier.createdAt) }}</p>
              </div>
            </div>
            <div>
              <div class="flex justify-between gap-2 text-xs"><span class="font-medium text-slate-600">Documents received</span><span class="text-slate-500">{{ supplier.documents }}</span></div>
              <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" :style="{ width: supplier.documents ? '100%' : '0%' }"></div></div>
            </div>
            <Button :label="supplier.status === 'pending' ? 'Review' : 'View details'" icon="pi pi-arrow-right" icon-pos="right" :severity="supplier.status === 'pending' ? undefined : 'secondary'" :outlined="supplier.status !== 'pending'" size="small" @click="open(supplier.portalId)" />
          </article>
        </div>
        <div v-else class="py-14 text-center">
          <i class="pi pi-inbox text-2xl text-slate-400"></i>
          <h3 class="mt-3 font-semibold text-slate-800">No supplier applications found</h3>
          <p class="mt-1 text-sm text-slate-500">Try another status, document filter, or search term.</p>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import axiosClient from '@/axios'
import Badge from 'primevue/badge'
import Button from 'primevue/button'
import Card from 'primevue/card'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Skeleton from 'primevue/skeleton'

const router = useRouter()
const rows = ref<any[]>([])
const loading = ref(false)
const search = ref('')
const status = ref('pending')
const documentFilter = ref('all')
const documentOptions = [
  { label: 'All submissions', value: 'all' },
  { label: 'With documents', value: 'with' },
  { label: 'No documents', value: 'without' },
]
const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  return rows.value.filter((supplier) => {
    const matchesStatus = status.value === 'all' || supplier.status === status.value
    const matchesTerm = !term || [supplier.name, supplier.contact, supplier.email].some((value) => String(value || '').toLowerCase().includes(term))
    const matchesDocuments = documentFilter.value === 'all' || (documentFilter.value === 'with' ? supplier.documents > 0 : supplier.documents === 0)
    return matchesStatus && matchesTerm && matchesDocuments
  })
})
const counts = computed(() => ({
  pending: rows.value.filter((row) => row.status === 'pending').length,
  approved: rows.value.filter((row) => row.status === 'approved').length,
  rejected: rows.value.filter((row) => row.status === 'rejected').length,
  all: rows.value.length,
}))
const statusTabs = computed(() => [
  { value: 'pending', label: 'Pending review', count: counts.value.pending },
  { value: 'approved', label: 'Approved', count: counts.value.approved },
  { value: 'rejected', label: 'Rejected', count: counts.value.rejected },
  { value: 'all', label: 'All applications', count: counts.value.all },
])
const activeTitle = computed(() => statusTabs.value.find((tab) => tab.value === status.value)?.label || 'Applications')

async function load() {
  loading.value = true
  try {
    const response = await axiosClient.get('/api/supplier-verifications', { params: { per_page: 200 } })
    const result = response.data?.data?.data || response.data?.data || []
    rows.value = result.map((item: any) => ({
      portalId: Number(item.portal_id || item.id),
      name: item.supplier_name || item.company_name || item.supplier?.supplier_name || 'Unnamed supplier',
      contact: item.contact_person || item.supplier?.contact_person || 'No contact person',
      email: item.email || item.user?.email || item.supplier?.email || 'No email',
      status: String(item.status || 'pending').toLowerCase(),
      createdAt: item.created_at || null,
      documents: (item.verificationDocuments || item.verification_documents || []).length,
    }))
  } finally {
    loading.value = false
  }
}

function open(id: number) { router.push({ name: 'admin.supplier-verification.show', params: { id } }) }
function title(value: string) { return String(value || 'unknown').replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()) }
function statusSeverity(value: string) { return ({ pending: 'warn', approved: 'success', rejected: 'danger' } as Record<string, string>)[value] || 'secondary' }
function initials(value: string) { return String(value || 'S').split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() }
function formatDate(value: string | null) { return value ? new Intl.DateTimeFormat('en-PH', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : 'Not available' }

onMounted(load)
</script>
